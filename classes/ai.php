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
        // --- Ta'lim sifati (ICALT) ---
        'clarity' => ['nom' => "Tushuntirish aniqligi", 'vazn' => 12, 'guruh' => "Ta'lim sifati"],
        'activating' => ['nom' => "O'quvchilar faolligi", 'vazn' => 12, 'guruh' => "Ta'lim sifati"],
        'topic_match' => ['nom' => "Mavzuga muvofiqlik", 'vazn' => 11, 'guruh' => "Ta'lim sifati"],
        'concept_development' => ['nom' => "Tushunchani chuqurlashtirish", 'vazn' => 5, 'guruh' => "Ta'lim sifati"],
        'strategies' => ['nom' => "O'quv strategiyalarini o'rgatish", 'vazn' => 5, 'guruh' => "Ta'lim sifati"],
        'differentiation' => ['nom' => "Tabaqalashtirilgan yondashuv", 'vazn' => 3, 'guruh' => "Ta'lim sifati"],

        // --- Emotsional qo'llab-quvvatlash (CLASS) ---
        'positive_climate' => ['nom' => "Ijobiy muhit", 'vazn' => 8, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'negative_climate' => ['nom' => "Salbiy holatlarning yo'qligi", 'vazn' => 8, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'teacher_sensitivity' => ['nom' => "O'qituvchining sezgirligi", 'vazn' => 6, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'student_perspective' => ['nom' => "O'quvchi fikriga e'tibor", 'vazn' => 4, 'guruh' => "Emotsional qo'llab-quvvatlash"],

        // --- Sinfni tashkil etish (CLASS) ---
        'management' => ['nom' => "Xulq-atvorni boshqarish", 'vazn' => 8, 'guruh' => "Sinfni tashkil etish"],
        'time_on_task' => ['nom' => "Vaqtdan samarali foydalanish", 'vazn' => 7, 'guruh' => "Sinfni tashkil etish"],

        // --- Fikr-mulohaza va til (CLASS) ---
        'feedback_quality' => ['nom' => "Fikr-mulohaza sifati", 'vazn' => 7, 'guruh' => "Fikr-mulohaza va til"],
        'language_modeling' => ['nom' => "Nutq namunasi va til boyligi", 'vazn' => 4, 'guruh' => "Fikr-mulohaza va til"],
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
        $mime = self::audio_mime($audio, $file);

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

    /**
     * Audio MIME turi.
     *
     * DIQQAT: `$file->get_mimetype()` ISHLATILMAYDI — Moodle `.webm` ni
     * **video/webm** deb belgilaydi (filetypes.php), biz esa uni `audio` turi
     * sifatida yuboramiz va Gemini "invalid argument" (HTTP 400) qaytaradi.
     * Shuning uchun brauzer bergan turni (audio::start da saqlangan) olamiz,
     * u bo'lmasa kengaytmadan aniqlaymiz.
     */
    protected static function audio_mime(\stdClass $audio, \stored_file $file): string {
        $ruxsat = [
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp3', 'audio/wav',
            'audio/aac', 'audio/flac', 'audio/m4a', 'audio/opus', 'audio/aiff',
        ];

        $m = strtolower(trim((string) ($audio->mimetype ?? '')));
        if (in_array($m, $ruxsat, true)) {
            return $m;
        }

        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $xarita = [
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg',
            'm4a' => 'audio/m4a',
            'mp4' => 'audio/m4a',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
        ];
        return $xarita[$ext] ?? 'audio/webm';
    }

    /** So'rov matni — natija QAT'IY JSON bo'lishi kerak. */
    protected static function prompt(\stdClass $dars, ?\stdClass $course): string {
        $mavzu = trim((string) $dars->topic);
        $kurs = $course ? $course->fullname : '';

        $kodlar = implode(', ', array_keys(self::CATEGORIES));

        return "Sen dars kuzatuvi bo'yicha xalqaro metodist-ekspertsan. Senga O'ZBEKISTONdagi "
            . "xalqaro maktabda yozib olingan dars audiosi beriladi.\n"
            . "Dars: {$kurs}. Sana: {$dars->lessondate}, {$dars->lessonno}-dars. "
            . "O'qituvchi jurnalga yozgan mavzu: \"" . ($mavzu ?: '(yozilmagan)') . "\".\n\n"

            . "BAHOLASH RAMKASI: ICALT (xalqaro dars kuzatuvi), CLASS (Pianta — sinfdagi "
            . "o'zaro munosabatlar) va Jahon banki TEACH. Har kategoriya 0-100 FOIZDA "
            . "baholanadi (ha/yo'q EMAS):\n\n"

            . "TA'LIM SIFATI:\n"
            . "- clarity: maqsad aytildimi, bosqichma-bosqich tushuntirish, misollar, "
            . "tushunganini tekshirish, sodda til\n"
            . "- activating: nechta o'quvchi gapirdi, ochiq savollar, fikrlashga undash, mustaqil ish\n"
            . "- topic_match: jurnaldagi mavzu darsda qanchalik yoritildi\n"
            . "- concept_development: sabab-oqibat, \"nega\" savollari, tahlil va umumlashtirish "
            . "(yodlash emas, tushunish)\n"
            . "- strategies: qanday o'rganishni o'rgatish, mulohaza yuritishni ovoz chiqarib "
            . "modellashtirish, xulosa chiqarish\n"
            . "- differentiation: turli darajadagi o'quvchiga moslash, alohida yordam\n\n"

            . "EMOTSIONAL QO'LLAB-QUVVATLASH:\n"
            . "- positive_climate: iliqlik, hurmat, ism bilan murojaat, rag'bat, hazil, "
            . "o'quvchilar o'rtasidagi do'stona ohang\n"
            . "- negative_climate: BU TESKARI o'lchov — salbiy holat qancha KAM bo'lsa, ball "
            . "shuncha YUQORI. 100 = baqirish, kinoya, kamsitish, qo'rqitish umuman yo'q. "
            . "Past ball faqat ovozda ANIQ eshitilgan holatlar uchun\n"
            . "- teacher_sensitivity: qiynalayotgan o'quvchini payqadimi, savolga javob berdimi, "
            . "tushunmaganini sezib qayta tushuntirdimi\n"
            . "- student_perspective: o'quvchi fikrini so'radimi, tanlov berdimi, mustaqillikka "
            . "imkon berdimi, g'oyalarini jiddiy qabul qildimi\n\n"

            . "SINFNI TASHKIL ETISH:\n"
            . "- management: tartib, aniq ko'rsatmalar, chalg'ishga xotirjam munosabat, o'tishlar tezligi\n"
            . "- time_on_task: darsning necha foizi haqiqiy o'quv ishiga ketdi\n\n"

            . "FIKR-MULOHAZA VA TIL:\n"
            . "- feedback_quality: shunchaki \"to'g'ri/noto'g'ri\" emas — NEGA to'g'ri, qayerda "
            . "xato, qanday tuzatish kerakligini tushuntirish\n"
            . "- language_modeling: o'qituvchining nutqi boy va aniqmi, yangi so'zlarni "
            . "izohlaydimi, o'quvchi javobini kengaytiradimi\n\n"

            . "MUHIM — HALOLLIK QOIDASI: senda FAQAT audio bor. Imo-ishora, doskadagi ish, "
            . "sinf joylashuvi va yozma ishlar ko'rinmaydi. Har kategoriyaga `confidence` qo'y: "
            . "\"high\" (ovozda aniq eshitildi), \"medium\" (qisman), \"low\" (audio orqali "
            . "baholab bo'lmaydi). Dalil bo'lmasa \"low\" qil — taxmin qilib ball QO'YMA.\n\n"

            . "TAQIQ: o'qituvchining ichki hissiy holati, charchoqligi, stressi yoki "
            . "shaxsiyati haqida XULOSA CHIQARMA. Faqat ovozda eshitilgan XATTI-HARAKATNI "
            . "tasvirla. `wellbeing_notes` ga faqat kuzatilgan faktni yoz (masalan: "
            . "\"uch marta ovoz ko'tarildi\") — tashxis yoki taxmin emas.\n\n"

            . "DALIL QOIDASI: har kategoriyaning `evidence` maydoni ANIQ va RAQAMLI bo'lsin. "
            . "Namunalar:\n"
            . "  yaxshi: \"Faqat 3 o'quvchi javob berdi; 12 savoldan 3 tasi ochiq savol edi.\"\n"
            . "  yaxshi: \"Mavzuning 3 qismidan 2 tasi yoritildi, masalalar yechish qismi o'tilmadi.\"\n"
            . "  yaxshi: \"45 daqiqadan 9 daqiqasi tashkiliy ishlarga ketdi.\"\n"
            . "  yomon: \"O'qituvchi yaxshi tushuntirdi.\" (raqam ham, aniq fakt ham yo'q)\n\n"

            . "FAQAT JSON qaytar, boshqa hech qanday matn yo'q:\n"
            . '{'
            . '"transcript":"darsning to\'liq matni: O\'qituvchi: ... O\'quvchi: ...",'
            . '"summary":"3-5 jumlali xulosa",'
            . '"categories":['
            . '{"code":"topic_match","score":70,"confidence":"high",'
            . '"actual_topic":"darsda aslida nima o\'tildi",'
            . '"evidence":"aniq raqamli dalil","advice":"bitta amaliy tavsiya"}'
            . '],'
            . '"metrics":{"teacher_talk_pct":70,"student_talk_pct":30,"questions_total":14,'
            . '"open_questions":5,"wait_time_avg_sec":2,"students_addressed":9,'
            . '"on_task_pct":85,"language":{"uz":60,"en":40}},'
            . '"wellbeing_notes":["kuzatilgan fakt, tashxis emas"],'
            . '"recommendations":["tavsiya 1","tavsiya 2","tavsiya 3"]'
            . "}\n\n"

            . "categories massivida YUQORIDAGI 14 kodning HAMMASI bo'lsin: {$kodlar}. "
            . "score butun son (0-100). Umumiy foizni O'ZING hisoblama — tizim hisoblaydi. "
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
                throw new \RuntimeException('Kvota tugagan (' . $model . ')');
            }
            if ($kod >= 500 || $kod === 0) {
                sleep(5 * ($i + 1));     // band — kutib qayta urinamiz
                continue;
            }
            // Sababni keyin qidirmaslik uchun kontekstni ham yozamiz
            throw new \RuntimeException(sprintf(
                'Gemini HTTP %d [model=%s, mime=%s, hajm=%.1fMB]: %s',
                $kod, $model, $mime, strlen($b64) / 1048576,
                \core_text::substr((string) $javob, 0, 250)
            ));
        }
        throw new \RuntimeException('Gemini javob bermadi (' . $model . ')');
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
            throw new \RuntimeException('Javobni JSON sifatida o\'qib bo\'lmadi');
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
