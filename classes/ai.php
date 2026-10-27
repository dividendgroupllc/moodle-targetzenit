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
    /**
     * Kategoriyalar: nom, vazn va XALQARO O'RTACHA (`norm`).
     *
     * `norm` — CLASS (1-7 shkala, foizga o'tkazilgan: (ball-1)/6) va ICALT
     * bo'yicha yirik tadqiqotlardagi o'rtacha qiymatlar. Bu juda muhim:
     * instruksional chuqurlik (concept_development, feedback_quality,
     * language_modeling) DUNYO BO'YLAB past — 24-41%. Normani ko'rsatmasak,
     * o'qituvchi o'zini muvaffaqiyatsiz deb his qiladi, aslida esa u xalqaro
     * o'rtacha darajada ishlayotgan bo'ladi.
     */
    const CATEGORIES = [
        // --- Ta'lim sifati (ICALT) ---
        'clarity' => ['nom' => "Tushuntirish aniqligi", 'vazn' => 11, 'norm' => 65, 'guruh' => "Ta'lim sifati"],
        'activating' => ['nom' => "O'quvchilar faolligi", 'vazn' => 11, 'norm' => 50, 'guruh' => "Ta'lim sifati"],
        'topic_match' => ['nom' => "Mavzuga muvofiqlik", 'vazn' => 10, 'norm' => 80, 'guruh' => "Ta'lim sifati"],
        'student_understanding' => ['nom' => "O'quvchilar o'zlashtirish darajasi", 'vazn' => 8, 'norm' => 60, 'guruh' => "Ta'lim sifati"],
        'concept_development' => ['nom' => "Tushunchani chuqurlashtirish", 'vazn' => 5, 'norm' => 30, 'guruh' => "Ta'lim sifati"],
        'strategies' => ['nom' => "O'quv strategiyalarini o'rgatish", 'vazn' => 5, 'norm' => 40, 'guruh' => "Ta'lim sifati"],
        'differentiation' => ['nom' => "Tabaqalashtirilgan yondashuv", 'vazn' => 3, 'norm' => 35, 'guruh' => "Ta'lim sifati"],

        // --- Emotsional qo'llab-quvvatlash (CLASS) ---
        'positive_climate' => ['nom' => "Ijobiy muhit", 'vazn' => 7, 'norm' => 70, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'negative_climate' => ['nom' => "Salbiy holatlarning yo'qligi", 'vazn' => 7, 'norm' => 85, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'teacher_sensitivity' => ['nom' => "O'qituvchining sezgirligi", 'vazn' => 6, 'norm' => 65, 'guruh' => "Emotsional qo'llab-quvvatlash"],
        'student_perspective' => ['nom' => "O'quvchi fikriga e'tibor", 'vazn' => 4, 'norm' => 45, 'guruh' => "Emotsional qo'llab-quvvatlash"],

        // --- Sinfni tashkil etish (CLASS) ---
        'management' => ['nom' => "Xulq-atvorni boshqarish", 'vazn' => 7, 'norm' => 67, 'guruh' => "Sinfni tashkil etish"],
        'time_on_task' => ['nom' => "Vaqtdan samarali foydalanish", 'vazn' => 6, 'norm' => 70, 'guruh' => "Sinfni tashkil etish"],

        // --- Fikr-mulohaza va til (CLASS) ---
        'feedback_quality' => ['nom' => "Fikr-mulohaza sifati", 'vazn' => 7, 'norm' => 32, 'guruh' => "Fikr-mulohaza va til"],
        'language_modeling' => ['nom' => "Nutq namunasi va til boyligi", 'vazn' => 3, 'norm' => 41, 'guruh' => "Fikr-mulohaza va til"],
    ];

    /** Vaznli xalqaro o'rtacha — "odatdagi dars" necha foiz oladi. */
    public static function norm_overall(): int {
        $y = 0;
        $v = 0;
        foreach (self::CATEGORIES as $k) {
            $y += $k['norm'] * $k['vazn'];
            $v += $k['vazn'];
        }
        return (int) round($y / $v);
    }

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

    /** Ishlatilgan model (so'rovdan keyin to'ldiriladi). */
    protected static $ishlatilgan = '';

    protected static function models(): array {
        $asosiy = trim((string) get_config('local_targetzenit', 'geminimodel')) ?: 'gemini-3.8-flash';
        $zaxira = trim((string) get_config('local_targetzenit', 'geminifallback'));
        return array_values(array_unique(array_filter([$asosiy, $zaxira])));
    }

    /** Model ro'yxati bo'yicha so'rov: biri yiqilsa keyingisi sinaladi. */
    protected static function so_rov(string $prompt, ?string $b64 = null,
                                     ?string $mime = null, bool $json = true) {
        $oxirgi = '';
        foreach (self::models() as $m) {
            try {
                $natija = self::call($m, $prompt, $b64, $mime, $json);
                self::$ishlatilgan = $m;
                return $natija;
            } catch (\Throwable $e) {
                $oxirgi = $e->getMessage();
                continue;
            }
        }
        throw new \RuntimeException($oxirgi ?: 'Model javob bermadi');
    }

    /**
     * Bitta yozuvni tahlil qiladi. IKKI BOSQICHDA:
     *   1) audio 5 daqiqalik bo'laklarga bo'linib, har biri alohida matnga o'giriladi
     *   2) to'liq matn (audiosiz) bo'yicha tahlil qilinadi
     *
     * NEGA SHUNDAY: 45 daqiqalik darsni bitta so'rovda so'rasak, modelning javob
     * uzunligi chegarasiga urilib, transkript O'RTASIDAN KESILADI — matn chala
     * qoladi va tahlil ham shunga yarasha noto'g'ri chiqadi. Bo'lak-bo'lak
     * so'rasak, har bir javob kichik bo'ladi va hech narsa tushib qolmaydi.
     */
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

        $mime = self::audio_mime($audio, $file);
        $course = $DB->get_record('course', ['id' => $dars->courseid], 'id, fullname, idnumber');

        // Faylni vaqtinchalik papkaga chiqaramiz (ffmpeg fayl bilan ishlaydi)
        $ish = make_temp_directory('local_targetzenit/ai/' . $audio->lessonid);
        $asl = $ish . '/asl.' . pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        $file->copy_content_to($asl);

        try {
            // ---------- 1-bosqich: bo'lak-bo'lak transkripsiya
            $bolaklar = self::split($asl, $ish);
            $daq = (int) (get_config('local_targetzenit', 'aisegmentminutes') ?: 5);

            $qismlar = [];
            foreach ($bolaklar as $i => $yol) {
                if (filesize($yol) > self::MAX_BYTES) {
                    continue;   // g'ayrioddiy katta bo'lak — o'tkazamiz
                }
                $boshlanish = $i * $daq * 60;
                $matn = self::so_rov(
                    self::prompt_transcribe($boshlanish, $daq),
                    base64_encode(file_get_contents($yol)),
                    $mime,
                    false
                );
                $belgi = sprintf('[%02d:%02d]', intdiv($boshlanish, 60), $boshlanish % 60);
                $qismlar[] = $belgi . "\n" . trim($matn);
            }

            if (!$qismlar) {
                return self::fail($audio, 'Transkript olinmadi');
            }
            $transkript = implode("\n\n", $qismlar);

            // ---------- 2-bosqich: to'liq matn bo'yicha tahlil (audiosiz)
            $javob = self::so_rov(self::prompt_analyse($dars, $course, $transkript));
            $javob['transcript'] = $transkript;

            return self::save($audio, $javob, self::$ishlatilgan);

        } catch (\Throwable $e) {
            return self::fail($audio, $e->getMessage());
        } finally {
            foreach (glob($ish . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($ish);
        }
    }

    /** ffmpeg yo'li (sozlamadan yoki tizimdan). */
    protected static function ffmpeg(): ?string {
        $yol = trim((string) get_config('local_targetzenit', 'ffmpegpath'));
        if ($yol && is_executable($yol)) {
            return $yol;
        }
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $n) {
            if (is_executable($n)) {
                return $n;
            }
        }
        return null;
    }

    /**
     * Audioni N daqiqalik bo'laklarga ajratadi.
     * ffmpeg bo'lmasa — bitta bo'lak qaytaradi (eski xatti-harakat).
     */
    protected static function split(string $asl, string $ish): array {
        $ffmpeg = self::ffmpeg();
        $daq = (int) (get_config('local_targetzenit', 'aisegmentminutes') ?: 5);

        if (!$ffmpeg || $daq < 1) {
            return [$asl];
        }

        $ext = pathinfo($asl, PATHINFO_EXTENSION) ?: 'webm';
        $namuna = $ish . '/qism_%03d.' . $ext;

        // -c copy: qayta kodlash yo'q — tez va sifat yo'qolmaydi
        $cmd = escapeshellcmd($ffmpeg) . ' -hide_banner -loglevel error -y'
            . ' -i ' . escapeshellarg($asl)
            . ' -f segment -segment_time ' . ($daq * 60)
            . ' -c copy ' . escapeshellarg($namuna) . ' 2>&1';
        @exec($cmd, $chiqish, $kod);

        $bolaklar = glob($ish . '/qism_*.' . $ext) ?: [];
        sort($bolaklar, SORT_STRING);

        if ($kod !== 0 || !$bolaklar) {
            debugging('local_targetzenit: ffmpeg bo\'lolmadi — bitta bo\'lak bilan davom etamiz. '
                . implode(' ', (array) $chiqish), DEBUG_DEVELOPER);
            return [$asl];
        }
        return $bolaklar;
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

    /**
     * Transkripsiya so'rovi — FAQAT matn, tahlil yo'q.
     *
     * Javob kichik bo'lgani uchun model uni oxirigacha yozadi; bitta uzun
     * so'rovda esa javob chegarasiga urilib, matn o'rtasidan kesilardi.
     */
    protected static function prompt_transcribe(int $boshlanish, int $daqiqa): string {
        $dan = sprintf('%02d:%02d', intdiv($boshlanish, 60), $boshlanish % 60);

        return "Bu — O'ZBEK maktabidagi darsning {$dan} dan boshlanadigan "
            . "{$daqiqa} daqiqalik bo'lagi.\n\n"
            . "Vazifa: audioni SO'ZMA-SO'Z matnga o'girib ber. Boshqa hech narsa yozma — "
            . "na izoh, na tahlil, na sarlavha.\n\n"
            . "Qoidalar:\n"
            . "- Har gapni kim aytganini belgila: \"O'qituvchi:\" yoki \"O'quvchi:\" "
            . "(ismi eshitilsa \"O'quvchi (Aziz):\")\n"
            . "- HECH NARSANI tushirib qoldirma va qisqartirma — qisqa javoblar, "
            . "takroriy so'zlar, to'xtalishlar ham yozilsin\n"
            . "- Eshitilmagan joyni [eshitilmadi] deb belgila, taxmin qilma\n"
            . "- Ingliz tilida aytilgan joylarni ingliz tilida qoldir\n"
            . "- Bo'lak oxirigacha yoz, o'rtada to'xtama";
    }

    /** Tahlil so'rovi — AUDIOSIZ, tayyor matn bo'yicha. Natija QAT'IY JSON. */
    protected static function prompt_analyse(\stdClass $dars, ?\stdClass $course,
                                             string $transkript): string {
        $mavzu = trim((string) $dars->topic);
        $kurs = $course ? $course->fullname : '';

        $kodlar = implode(', ', array_keys(self::CATEGORIES));

        // Kurs idnumber: "<yil>::<guruh>::<fan>" — fan va sinfni shundan ajratamiz
        $guruh = '';
        $fan = '';
        if ($course && !empty($course->idnumber)) {
            $qism = explode('::', $course->idnumber);
            $guruh = $qism[1] ?? '';
            $fan = $qism[2] ?? '';
        }

        return "Sen dars kuzatuvi bo'yicha xalqaro metodist-ekspertsan. Senga O'ZBEKISTONdagi "
            . "xalqaro maktabda o'tilgan darsning TO'LIQ TRANSKRIPTI beriladi "
            . "(audio bo'lak-bo'lak matnga o'girilgan, [MM:SS] belgilari vaqtni ko'rsatadi).\n"
            . "Dars: {$kurs}. Sinf: " . ($guruh ?: "noma'lum") . ". Fan: " . ($fan ?: "noma'lum") . ". "
            . "Sana: {$dars->lessondate}, {$dars->lessonno}-dars. "
            . "O'qituvchi jurnalga yozgan mavzu: \"" . ($mavzu ?: '(yozilmagan)') . "\".\n\n"

            . "MAQSAD: o'qituvchini tanqid qilish EMAS — darsni umumiy tahlil qilish va "
            . "rivojlanishga yordam berish. Ohang xolis va qo'llab-quvvatlovchi bo'lsin: "
            . "nima yaxshi bo'lganini ham, nimani yaxshilash mumkinligini ham ayt. "
            . "Ayblov so'zlarini ishlatma.\n\n"

            . "FAN VA YOSHNI HISOBGA OL: har fanning o'z tabiati bor. Jismoniy tarbiya, "
            . "san'at yoki musiqa darsida ko'p gapirish kutilmaydi; til darsida o'quvchi "
            . "nutqi ko'p bo'lishi kerak; matematikada mulohaza yuritish ovoz chiqarib "
            . "modellanadi. Boshlang'ich sinfda ko'rsatma qisqa va takroriy bo'lishi "
            . "normal. Mezonlarni SHU fan va sinfga qarab talqin qil.\n\n"

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
            . "- differentiation: turli darajadagi o'quvchiga moslash, alohida yordam\n"
            . "- student_understanding: O'QUVCHILARNING O'ZLASHTIRISH DARAJASI — javoblari "
            . "to'g'rimi, chuqurmi yoki yuzakimi; tushunmovchilik va xato tasavvurlar bormi; "
            . "dars oxirida mavzuni egallaganlari sezildimi. Bu o'qituvchini emas, "
            . "SINFNING natijasini baholaydi\n\n"

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

            . "categories massivida YUQORIDAGI 15 kodning HAMMASI bo'lsin: {$kodlar}. "
            . "score butun son (0-100). Umumiy foizni O'ZING hisoblama — tizim hisoblaydi. "
            . "Transkriptni QAYTARMA — u allaqachon bizda.\n\n"
            . "=== DARS TRANSKRIPTI ===\n" . $transkript;
    }

    /** Bitta modelga so'rov; vaqtinchalik xatolarda qayta urinadi. */
    protected static function call(string $model, string $prompt, ?string $b64 = null,
                                   ?string $mime = null, bool $json = true) {
        $kalit = trim((string) get_config('local_targetzenit', 'geminikey'));

        $input = [['type' => 'text', 'text' => $prompt]];
        if ($b64 !== null) {
            $input[] = ['type' => 'audio', 'data' => $b64, 'mime_type' => $mime];
        }
        $body = json_encode(['model' => $model, 'input' => $input]);

        for ($i = 0; $i < self::URINISH; $i++) {
            $curl = new \curl();
            $curl->setHeader(['x-goog-api-key: ' . $kalit, 'Content-Type: application/json']);
            $javob = $curl->post(self::ENDPOINT, $body, [
                'CURLOPT_TIMEOUT' => 900,
                'CURLOPT_CONNECTTIMEOUT' => 30,
            ]);
            $kod = (int) ($curl->get_info()['http_code'] ?? 0);

            if ($kod === 200) {
                return $json ? self::parse($javob) : self::matn($javob);
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
                $kod, $model, (string) $mime, strlen((string) $b64) / 1048576,
                \core_text::substr((string) $javob, 0, 250)
            ));
        }
        throw new \RuntimeException('Gemini javob bermadi (' . $model . ')');
    }

    /** Javobdan sof matnni ajratib olish (transkripsiya bosqichi uchun). */
    protected static function matn(string $raw): string {
        $j = json_decode($raw, true);
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
        return trim($matn !== '' ? $matn : $raw);
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

        // DIQQAT: `transcript` TALAB QILINMAYDI — 0.13.0 dan beri transkript
        // alohida bosqichda olinadi va modelga "qaytarma" deyilgan. Avval shu
        // yerda `transcript` talab qilingani uchun TO'G'RI javob ham rad etilardi.
        if (!is_array($natija) || (!isset($natija['categories']) && !isset($natija['summary']))) {
            throw new \RuntimeException('Javobni JSON sifatida o\'qib bo\'lmadi. Model javobi: '
                . \core_text::substr(trim($matn), 0, 300));
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
