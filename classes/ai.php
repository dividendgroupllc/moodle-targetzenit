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

    /**
     * Baholash kategoriyalari — ICALT (International Comparative Analysis of
     * Learning and Teaching) ning 6 domeni + Jahon banki TEACH dan "vaqtdan
     * foydalanish".
     *
     * NEGA ICALT: u xalqaro TAQQOSLASH uchun maxsus tasdiqlangan (Niderlandiya,
     * J.Koreya, JAR, Indoneziya, Gonkong, Pokiston). Danielson FFT ning 4
     * domenidan ikkitasi (rejalashtirish, kasbiy mas'uliyat) dars davomida
     * umuman kuzatilmaydi, shuning uchun audio tahlilga to'liq mos kelmaydi.
     *
     * VAZNLAR audio orqali qanchalik ishonchli baholanishiga ham qarab qo'yilgan:
     * tushuntirish va faollashtirish ovozda yaxshi eshitiladi, tabaqalashtirish
     * esa deyarli eshitilmaydi — shuning uchun uning vazni eng kichik.
     */
    const CATEGORIES = [
        'clarity'       => ['nom' => "Tushuntirish aniqligi",             'vazn' => 20],
        'activating'    => ['nom' => "O'quvchilar faolligi",              'vazn' => 20],
        'topic_match'   => ['nom' => "Mavzuga muvofiqlik",                'vazn' => 15],
        'climate'       => ['nom' => "Xavfsiz va rag'batlantiruvchi muhit", 'vazn' => 12],
        'management'    => ['nom' => "Sinfni boshqarish va tartib",       'vazn' => 12],
        'time_on_task'  => ['nom' => "Vaqtdan samarali foydalanish",      'vazn' => 9],
        'strategies'    => ['nom' => "O'quv strategiyalarini o'rgatish",  'vazn' => 8],
        'differentiation' => ['nom' => "Tabaqalashtirilgan yondashuv",    'vazn' => 4],
    ];

    public static function enabled(): bool {
        return (bool) get_config('local_targetzenit', 'aienabled')
            && trim((string) get_config('local_targetzenit', 'geminikey')) !== '';
    }

    /**
     * Tahlil kutayotgan yozuvlar (audio bor, tahlil yo'q).
     *
     * Xato bergan yozuvga DARHOL qayta urinilmaydi — 1 soat kutiladi.
     * Aks holda doimiy xato (masalan fayl buzuq) har 15 daqiqada kvotani
     * behuda yeb ketardi.
     */
    public static function pending(int $limit): array {
        global $DB;
        return $DB->get_records_select('local_targetzenit_audio',
            "status = 'ready' AND (analysis IS NULL OR analysis = '') AND filesize > 0
             AND (aierror IS NULL OR aierror = '' OR timemodified < :chegara)",
            ['chegara' => time() - HOURSECS],
            'timecreated ASC', '*', 0, $limit);
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

        $kat = [];
        foreach (self::CATEGORIES as $kod => $k) {
            $kat[] = "{$kod} ({$k['nom']})";
        }

        return "Sen dars kuzatuvi bo'yicha xalqaro metodist-ekspertsan. Senga O'ZBEKISTONdagi "
            . "xalqaro maktabda yozib olingan dars audiosi beriladi.\n"
            . "Dars: {$kurs}. Sana: {$dars->lessondate}, {$dars->lessonno}-dars. "
            . "O'qituvchi jurnalga yozgan mavzu: \"" . ($mavzu ?: '(yozilmagan)') . "\".\n\n"

            . "BAHOLASH RAMKASI: ICALT (International Comparative Analysis of Learning and "
            . "Teaching) ning 6 domeni va Jahon banki TEACH vositasidan vaqtdan foydalanish. "
            . "Har kategoriya 0 dan 100 gacha FOIZDA baholanadi (ha/yo'q EMAS):\n"
            . "- clarity: tushuntirish aniqligi — maqsad aytildimi, bosqichma-bosqich, misollar, "
            . "tushunganini tekshirish, sodda til\n"
            . "- activating: o'quvchilar faolligi — nechta o'quvchi gapirdi, ochiq savollar, "
            . "fikrlashga undash, mustaqil ish\n"
            . "- topic_match: jurnalga yozilgan mavzu darsda qanchalik yoritildi\n"
            . "- climate: xavfsiz va rag'batlantiruvchi muhit — hurmatli ohang, qo'llab-quvvatlash, "
            . "xatoga munosabat, ism bilan murojaat\n"
            . "- management: sinfni boshqarish — tartib, o'tishlar tezligi, chalg'ishga munosabat\n"
            . "- time_on_task: vaqtdan samarali foydalanish — darsning necha foizi o'quv ishiga ketdi\n"
            . "- strategies: o'quv strategiyalarini o'rgatish — qanday o'rganishni tushuntirish, "
            . "mulohaza yuritishni modellashtirish, xulosa\n"
            . "- differentiation: tabaqalashtirilgan yondashuv — turli darajadagi o'quvchiga moslash, "
            . "alohida yordam\n\n"

            . "MUHIM — HALOLLIK QOIDASI: senda FAQAT audio bor. Imo-ishora, doskadagi ish, "
            . "sinf joylashuvi va yozma ishlar ko'rinmaydi; tartibbuzarlik ovozda bilinmasligi "
            . "mumkin. Shuning uchun har kategoriyaga `confidence` qo'y: "
            . "\"high\" (ovozda aniq eshitildi), \"medium\" (qisman), "
            . "\"low\" (audio orqali baholab bo'lmaydi). Taxmin qilib yuqori ball QO'YMA — "
            . "dalil bo'lmasa `confidence` ni \"low\" qil.\n\n"

            . "DALIL QOIDASI: har kategoriyaning `evidence` maydoni ANIQ va RAQAMLI bo'lsin — "
            . "umumiy gap yozma. Namunalar:\n"
            . "  yaxshi: \"Jurnaldagi mavzuning 3 qismidan 2 tasi yoritildi, uchinchisi "
            . "(masalalar yechish) umuman o'tilmadi.\"\n"
            . "  yaxshi: \"Darsda faqat 3 o'quvchi javob berdi; 14 savoldan 4 tasi ochiq savol edi.\"\n"
            . "  yaxshi: \"45 daqiqadan 9 daqiqasi tashkiliy ishlarga va chalg'ishga ketdi.\"\n"
            . "  yomon: \"O'qituvchi yaxshi tushuntirdi.\" (raqam ham, aniq fakt ham yo'q)\n\n"

            . "FAQAT JSON qaytar, boshqa hech qanday matn yo'q:\n"
            . '{'
            . '"transcript":"darsning to\'liq matni, so\'zlovchilar ajratilgan: O\'qituvchi: ... O\'quvchi: ...",'
            . '"summary":"3-5 jumlali xulosa",'
            . '"categories":['
            . '{"code":"topic_match","score":70,"confidence":"high",'
            . '"actual_topic":"darsda aslida nima o\'tildi",'
            . '"evidence":"Jurnalda \'Kasrlarni qo\'shish\' yozilgan; o\'qituvchi mavzuni tushuntirdi, '
            . 'lekin mustaqil yechish qismi o\'tilmadi.","advice":"bitta amaliy tavsiya"},'
            . '{"code":"activating","score":50,"confidence":"high",'
            . '"evidence":"Faqat 3 o\'quvchi faol qatnashdi; 12 savoldan 3 tasi ochiq savol edi.",'
            . '"advice":"..."}'
            . '],'
            . '"metrics":{"teacher_talk_pct":70,"student_talk_pct":30,"questions_total":14,'
            . '"open_questions":5,"wait_time_avg_sec":2,"students_addressed":9,'
            . '"on_task_pct":85,"language":{"uz":60,"en":40}},'
            . '"recommendations":["qisqa amaliy tavsiya 1","tavsiya 2","tavsiya 3"]'
            . "}\n\n"

            . "categories massivida YUQORIDAGI 7 kodning HAMMASI bo'lsin: " . implode(', ', $kat) . ". "
            . "score butun son (0-100). Umumiy foizni O'ZING hisoblama — uni tizim hisoblaydi. "
            . "Transkriptni o'zbek tilida yoz, ingliz tilida gapirilgan joylarni o'z tilida qoldir.";
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

    /**
     * Umumiy foiz — vaznli o'rtacha. MODEL hisoblamaydi, kod hisoblaydi:
     * shunda hisob shaffof bo'ladi va modeldan modelga o'zgarib ketmaydi.
     *
     * `confidence = low` bo'lgan kategoriya hisobga OLINMAYDI va vaznlar
     * qolganlari bo'yicha qayta taqsimlanadi — audio ko'rmagan narsa
     * o'qituvchining umumiy bahosini pasaytirmasligi kerak.
     */
    public static function overall(array $categories): array {
        $yigindi = 0.0;
        $vazn_jami = 0;
        $hisobda = [];
        $chetda = [];

        foreach ($categories as $c) {
            $kod = $c['code'] ?? '';
            if (!isset(self::CATEGORIES[$kod])) {
                continue;
            }
            if (($c['confidence'] ?? '') === 'low') {
                $chetda[] = $kod;
                continue;
            }
            $vazn = self::CATEGORIES[$kod]['vazn'];
            $yigindi += ((int) ($c['score'] ?? 0)) * $vazn;
            $vazn_jami += $vazn;
            $hisobda[] = $kod;
        }

        return [
            'score' => $vazn_jami ? (int) round($yigindi / $vazn_jami) : null,
            'hisobda' => $hisobda,
            'chetda' => $chetda,
        ];
    }

    protected static function save(\stdClass $audio, array $javob, string $model): bool {
        global $DB;

        $transkript = (string) ($javob['transcript'] ?? '');
        unset($javob['transcript']);   // tahlil JSON'ida takrorlanmasin

        // Kategoriyalarga nomlarni qo'shamiz va umumiy foizni hisoblaymiz
        if (!empty($javob['categories']) && is_array($javob['categories'])) {
            foreach ($javob['categories'] as &$c) {
                $kod = $c['code'] ?? '';
                $c['name'] = self::CATEGORIES[$kod]['nom'] ?? $kod;
                $c['weight'] = self::CATEGORIES[$kod]['vazn'] ?? 0;
                $c['score'] = max(0, min(100, (int) ($c['score'] ?? 0)));
            }
            unset($c);
            $javob['overall'] = self::overall($javob['categories']);
        }

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
