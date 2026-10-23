<?php
// Dars yozuvini AI bilan tahlil qilish (Gemini).
//
// Frappe'dagi `telefoniya/stt.py` yondashuvi ko'chirildi: audio to'g'ridan-to'g'ri
// modelga beriladi (transkript va tahlil BIR so'rovda), kvota tugasa zaxira
// modelga o'tiladi, 503 (band) da qayta uriniladi.
//
// NEGA MOODLE TOMONIDA: audio shu yerda yotibdi (har dars ~8 MB), tahlil esa
// darsning yonida ko'rinishi kerak. Frappe moliya/ma'muriyat bo'lib qoladi.
//
// NATIJA QAT'IY JSON: transkript + xulosa + mavzuga muvofiqlik + dars tuzilishi
// + nutq ulushi. Audio 15 kundan keyin o'chadi, bu natija esa qoladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class ai {

    const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';
    const URINISH = 3;
    /** Inline audio uchun amaliy chegara (base64 ~1.37x shishiradi). */
    const MAX_BYTES = 15728640;   // 15 MB

    public static function enabled(): bool {
        return (bool) get_config('local_targetzenit', 'aienabled')
            && trim((string) get_config('local_targetzenit', 'geminikey')) !== '';
    }

    /** Tahlil kutayotgan yozuvlar (audio bor, tahlil yo'q). */
    public static function pending(int $limit): array {
        global $DB;
        return $DB->get_records_select('local_targetzenit_audio',
            "status = 'ready' AND (analysis IS NULL OR analysis = '') AND filesize > 0",
            [], 'timecreated ASC', '*', 0, $limit);
    }

    /** Bitta yozuvni tahlil qiladi. Qaytaradi: muvaffaqiyatlimi. */
    public static function analyse(\stdClass $audio): bool {
        global $DB;

        $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $audio->lessonid]);
        if (!$dars) {
            return self::fail($audio, 'Dars topilmadi');
        }

        $context = \context_course::instance($dars->courseid, IGNORE_MISSING);
        if (!$context) {
            return self::fail($audio, 'Kurs topilmadi');
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, audio::COMPONENT, audio::FILEAREA,
            $audio->lessonid, 'itemid', false);
        $file = $files ? reset($files) : null;
        if (!$file) {
            return self::fail($audio, 'Audio fayl topilmadi');
        }
        if ($file->get_filesize() > self::MAX_BYTES) {
            return self::fail($audio, 'Audio juda katta (' .
                round($file->get_filesize() / 1048576) . ' MB)');
        }

        $b64 = base64_encode($file->get_content());
        $mime = $file->get_mimetype() ?: 'audio/webm';

        $course = $DB->get_record('course', ['id' => $dars->courseid], 'id, fullname');
        $prompt = self::prompt($dars, $course);

        $model = trim((string) get_config('local_targetzenit', 'geminimodel')) ?: 'gemini-3.8-flash';
        $zaxira = trim((string) get_config('local_targetzenit', 'geminifallback'));
        $modellar = array_values(array_unique(array_filter([$model, $zaxira])));

        $oxirgi = '';
        foreach ($modellar as $m) {
            try {
                $javob = self::call($m, $prompt, $b64, $mime);
                return self::save($audio, $javob, $m);
            } catch (\Throwable $e) {
                $oxirgi = $e->getMessage();
                // Kvota yoki bandlik — keyingi modelni sinaymiz
                continue;
            }
        }
        return self::fail($audio, $oxirgi ?: 'Model javob bermadi');
    }

    /** So'rov matni — natija QAT'IY JSON bo'lishi kerak. */
    protected static function prompt(\stdClass $dars, ?\stdClass $course): string {
        $mavzu = trim((string) $dars->topic);
        $kurs = $course ? $course->fullname : '';

        return "Sen maktab darslarini tahlil qiluvchi metodist yordamchisisan. "
            . "Senga O'ZBEK maktabida yozib olingan dars audiosi beriladi. "
            . "Dars: {$kurs}. Sana: {$dars->lessondate}, {$dars->lessonno}-dars. "
            . "O'qituvchi jurnalga yozgan mavzu: \"" . ($mavzu ?: '(yozilmagan)') . "\".\n\n"
            . "Vazifa: audioni tinglab, FAQAT JSON qaytar (boshqa hech qanday matn yo'q). "
            . "JSON tuzilishi aynan shunday bo'lsin:\n"
            . '{'
            . '"transcript": "darsning to\'liq matni, so\'zlovchilar ajratilgan: O\'qituvchi: ... O\'quvchi: ...",'
            . '"summary": "3-5 jumlali xulosa",'
            . '"topic_match": {"covered": true, "confidence": "high|medium|low", "actual_topic": "darsda aslida nima o\'tildi", "note": "izoh"},'
            . '"structure": [{"stage": "Tashkiliy qism", "present": true, "minutes": 3, "note": ""},'
            . '{"stage": "Uy vazifa tekshiruvi", "present": true, "minutes": 7, "note": ""},'
            . '{"stage": "Yangi mavzu", "present": true, "minutes": 20, "note": ""},'
            . '{"stage": "Mustahkamlash", "present": false, "minutes": 0, "note": ""},'
            . '{"stage": "Uy vazifa berildi", "present": true, "minutes": 2, "note": ""}],'
            . '"speech": {"teacher_pct": 70, "student_pct": 30, "questions_asked": 12,'
            . '"answers_received": 9, "language": {"uz": 60, "en": 40}},'
            . '"recommendations": ["qisqa tavsiya 1", "qisqa tavsiya 2"]'
            . "}\n\n"
            . "Qoidalar: transkriptni o'zbek tilida yoz (ingliz tilida gapirilgan joylarni "
            . "o'z tilida qoldir). Taxmin qilma — eshitilmagan narsani \"present\": false deb belgila. "
            . "Foizlar butun son bo'lsin. JSON to'g'ri formatda bo'lishi SHART.";
    }

    /** Bitta modelga so'rov; vaqtinchalik xatolarda qayta urinadi. */
    protected static function call(string $model, string $prompt, string $b64, string $mime): array {
        $kalit = trim((string) get_config('local_targetzenit', 'geminikey'));

        $body = json_encode([
            'model' => $model,
            'input' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'audio', 'data' => $b64, 'mime_type' => $mime],
            ],
        ]);

        for ($i = 0; $i < self::URINISH; $i++) {
            $curl = new \curl();
            $curl->setHeader(['x-goog-api-key: ' . $kalit, 'Content-Type: application/json']);
            $javob = $curl->post(self::ENDPOINT, $body, [
                'CURLOPT_TIMEOUT' => 900,
                'CURLOPT_CONNECTTIMEOUT' => 30,
            ]);
            $kod = (int) ($curl->get_info()['http_code'] ?? 0);

            if ($kod === 200) {
                return self::parse($javob);
            }
            if ($kod === 429) {
                // Kvota tugagan — qayta urinish befoyda, zaxira modelga o'tiladi
                throw new \moodle_exception('Kvota tugagan (' . $model . ')');
            }
            if ($kod >= 500 || $kod === 0) {
                sleep(5 * ($i + 1));     // band — kutib qayta urinamiz
                continue;
            }
            throw new \moodle_exception('Gemini HTTP ' . $kod . ': ' .
                \core_text::substr((string) $javob, 0, 200));
        }
        throw new \moodle_exception('Gemini javob bermadi (' . $model . ')');
    }

    /** Javobdan JSON ajratib olish (model matn ichiga o'rab yuborishi mumkin). */
    protected static function parse(string $raw): array {
        $j = json_decode($raw, true);

        // Javob tuzilishidan matnni qazib olamiz
        $matn = '';
        if (is_array($j)) {
            $qidir = function ($a) use (&$qidir, &$matn) {
                foreach ($a as $k => $v) {
                    if (is_array($v)) {
                        $qidir($v);
                    } else if ($k === 'text' && is_string($v) && strlen($v) > strlen($matn)) {
                        $matn = $v;
                    }
                }
            };
            $qidir($j);
        }
        if ($matn === '') {
            $matn = $raw;
        }

        // ```json ... ``` ichidan olish
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $matn, $m)) {
            $matn = $m[1];
        }
        $boshi = strpos($matn, '{');
        $oxiri = strrpos($matn, '}');
        if ($boshi !== false && $oxiri !== false && $oxiri > $boshi) {
            $matn = substr($matn, $boshi, $oxiri - $boshi + 1);
        }

        $natija = json_decode($matn, true);
        if (!is_array($natija) || !isset($natija['transcript'])) {
            throw new \moodle_exception('Javobni JSON sifatida o\'qib bo\'lmadi');
        }
        return $natija;
    }

    protected static function save(\stdClass $audio, array $javob, string $model): bool {
        global $DB;

        $transkript = (string) ($javob['transcript'] ?? '');
        unset($javob['transcript']);   // tahlil JSON'ida takrorlanmasin

        $audio->transcript = $transkript;
        $audio->analysis = json_encode($javob, JSON_UNESCAPED_UNICODE);
        $audio->aimodel = \core_text::substr($model, 0, 64);
        $audio->aierror = null;
        $audio->analysedat = time();
        $audio->status = 'transcribed';
        $audio->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $audio);
        return true;
    }

    protected static function fail(\stdClass $audio, string $xato): bool {
        global $DB;
        $audio->aierror = \core_text::substr($xato, 0, 255);
        $audio->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $audio);
        return false;
    }

    /** Darsning tahlili (dekodlangan). */
    public static function for_lesson(int $lessonid): ?array {
        $a = audio::get($lessonid);
        if (!$a || empty($a->analysis)) {
            return null;
        }
        $j = json_decode($a->analysis, true);
        return is_array($j) ? ['audio' => $a, 'data' => $j] : null;
    }
}
