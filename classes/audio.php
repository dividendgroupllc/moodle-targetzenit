<?php
// Dars audio yozuvi.
//
// OQIM:
//   brauzer MediaRecorder -> har 15 soniyada bo'lak -> serverga yuklanadi
//   (temp/local_targetzenit/audio/<lessonid>/<avlod>-<tartib>.part) ->
//   "To'xtatish" bosilganda bo'laklar yig'iladi -> Moodle fayl omboriga
//   joylanadi va darsga biriktiriladi.
//
// NEGA BO'LAK-BO'LAK: brauzer yopilsa, telefon uxlasa yoki internet uzilsa ham
// yozilgan qism serverda qoladi. Moodle'ning o'z `tiny_recordrtc` vositasi esa
// butun yozuvni brauzer xotirasida to'playdi — 45 daqiqalik dars uchun yaramaydi.
//
// ====================== AVLOD (generation) — ENG MUHIM QOIDA ======================
// Bitta MediaRecorder bergan bo'laklar BITTA uzluksiz bayt oqimi: birinchi
// bo'lakda EBML sarlavhasi, qolganlarida faqat cluster'lar. Shuning uchun bir
// avlod ichida bayt-ba-bayt ulash TO'G'RI va ffmpeg kerak emas.
//
// Lekin yozuv uzilib MediaRecorder QAYTA yaratilsa (mikrofon sug'urildi,
// brauzer to'xtatdi, sahifa qayta yuklandi), yangi oqim YANGI SARLAVHA bilan
// boshlanadi. Ikki sarlavhali faylni esa pleyer ham, ffmpeg ham BIRINCHI
// qismdan keyin O'QIMAYDI. 2026-11-10 da o'lchab ko'rildi: 10s + 10s
// bayt-ulanmasi 10s ko'rsatadi — ya'ni uzilishdan keyingi butun dars
// JIMGINA yo'qolardi (va AI transkripti ham faqat birinchi qismni ko'rardi).
//
// Yechim: har MediaRecorder = alohida AVLOD. Avlod ICHIDA bayt-ulash,
// avlodlar ORASIDA ffmpeg `concat` demuxer (`-c copy` — qayta kodlashsiz,
// sifat yo'qolmaydi). Natija bitta butun fayl bo'ladi.
//
// ====================== SESSIYA — bir vaqtda ko'p dars ======================
// Bir vaqtda 10+ dars yozilishi normal holat. Bo'laklar papkasi `lessonid`
// bo'yicha ajralgan va bazada `lessonid` UNIKAL — turli darslar hech qachon
// aralashmaydi (holat `audio.php` dagi yagona kalit — lessonid).
//
// Ammo BITTA dars ikki oyna yoki ikki qurilmada ochilsa, ikki yozuvchi bir
// papkaga yozardi va tartib raqamlari ustma-ust tushardi. Shuning uchun
// `start` har chaqiruvda YANGI `sessionid` beradi; eski sessiyadan kelgan
// bo'lak RAD etiladi va brauzer buni ko'rib o'z yozuvini to'xtatadi.
// Yozilib bo'lgan narsa esa O'CHIRILMAYDI — yangi sessiya keyingi avlod
// sifatida USTIGA QO'SHIB yozadi (sahifa qayta yuklansa dars yo'qolmasin).
//
// AUDIO VAQTINCHALIK: transkriptdan keyin yoki muddat o'tgach o'chadi
// (`cleanup_audio` vazifasi). Transkript matni qoladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class audio {

    const COMPONENT = 'local_targetzenit';
    const FILEAREA = 'lessonaudio';

    /** Bitta bo'lak uchun shift (odatda ~50 KB keladi). */
    const MAX_CHUNK = 8388608;      // 8 MB

    /** Brauzer bo'lak uzunligi — davomiylikni taxminlashda ishlatiladi. */
    const SLICE_SEC = 15;

    /**
     * Maktab vaqt mintaqasi. Fayl nomlari va diagnostika SHU vaqtda yoziladi:
     * server UTC da ishlaydi, sayt sozlamasi esa o'zgarib ketishi mumkin
     * (2026-11-12 ga qadar u Europe/London edi va hamma soat 5 soat orqada
     * ko'rinardi). O'zbekistonda yozgi vaqt yo'q, ya'ni siljish bo'lmaydi.
     */
    const MAKTAB_TZ = 'Asia/Tashkent';

    /**
     * Tashlab ketilgan bo'laklarni YANGI yozuvga qo'shib yuborish chegarasi.
     * Shundan oshgan qoldiq boshqa darsga tegishli deb hisoblanadi.
     */
    const RESUME_WINDOW = 3600;     // 1 soat (bitta dars davomidan uzun emas)

    /**
     * Fayl HALI BOR deb hisoblanadigan holatlar.
     * `transcribed` ham shu yerda: tahlil qilingani audioni o'chirmaydi —
     * u 15 kun davomida matn bilan yonma-yon turadi (AI aniqligini
     * tekshirish uchun asl ovoz kerak).
     */
    const FAYLLI = ['ready', 'transcribed'];

    const MIME_EXT = [
        'audio/webm' => 'webm',
        'audio/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
        'audio/mpeg' => 'mp3',
    ];

    /** Soniyani m:ss ko'rinishida (xabarlar uchun). */
    protected static function hhmm(int $sek): string {
        return intdiv($sek, 60) . ':' . sprintf('%02d', $sek % 60);
    }

    /** Shu dars uchun bo'laklar saqlanadigan papka. */
    protected static function tempdir(int $lessonid): string {
        return make_temp_directory(self::COMPONENT . '/audio/' . $lessonid);
    }

    protected static function maxbytes(): int {
        return (int) (get_config(self::COMPONENT, 'audiomaxmb') ?: 120) * 1024 * 1024;
    }

    protected static function ext(?string $mime): string {
        return self::MIME_EXT[$mime] ?? 'webm';
    }

    /** ffmpeg yo'li (sozlamadan yoki tizimdan). */
    public static function ffmpeg(): ?string {
        $yol = trim((string) get_config(self::COMPONENT, 'ffmpegpath'));
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

    /** ffprobe yo'li — ffmpeg yonida turadi. */
    public static function ffprobe(): ?string {
        $f = self::ffmpeg();
        if ($f) {
            $p = preg_replace('/ffmpeg(\.exe)?$/', 'ffprobe$1', $f);
            if ($p && $p !== $f && is_executable($p)) {
                return $p;
            }
        }
        foreach (['/usr/bin/ffprobe', '/usr/local/bin/ffprobe'] as $n) {
            if (is_executable($n)) {
                return $n;
            }
        }
        return null;
    }

    /** Faylning haqiqiy davomiyligi (soniya) yoki null. */
    public static function probe_duration(string $path): ?int {
        $probe = self::ffprobe();
        if (!$probe || !is_readable($path)) {
            return null;
        }
        $cmd = escapeshellcmd($probe) . ' -v error -show_entries format=duration'
            . ' -of default=nw=1:nk=1 ' . escapeshellarg($path) . ' 2>/dev/null';
        $chiqish = [];
        @exec($cmd, $chiqish, $kod);
        $s = (float) trim(implode('', $chiqish));
        return ($kod === 0 && $s > 0) ? (int) round($s) : null;
    }

    /** Audio oqimining namuna tezligi va kanal soni (standart 48 kHz mono). */
    protected static function stream_info(string $path): array {
        $probe = self::ffprobe();
        $hz = 48000;
        $kanal = 1;
        if ($probe && is_readable($path)) {
            $cmd = escapeshellcmd($probe) . ' -v error -select_streams a:0'
                . ' -show_entries stream=sample_rate,channels'
                . ' -of default=nw=1:nk=1 ' . escapeshellarg($path) . ' 2>/dev/null';
            $chiqish = [];
            @exec($cmd, $chiqish, $kod);
            if ($kod === 0 && count($chiqish) >= 2) {
                $hz = max(8000, (int) trim($chiqish[0]));
                $kanal = max(1, (int) trim($chiqish[1]));
            }
        }
        return [$hz, $kanal];
    }

    /**
     * Faylda HAQIQATAN qancha ovoz borligi (soniya).
     *
     * NEGA `probe_duration()` YETARLI EMAS — 2026-11-11 da qimmatga tushgan saboq:
     * bo'lak-bo'lak yig'ilgan webm'da konteyner davomiyligi YO'Q (`duration=N/A`),
     * bo'laklar yaratilmay qolganda esa vaqt belgilari OLDINGA sakraydi va ffprobe
     * o'sha BO'SHLIQNI ham davomiylik deb ko'rsatadi. Shu sabab 3 daqiqalik yozuv
     * bazaga "33 daqiqa" bo'lib tushdi va AI jim joylarga dars matnini o'ylab topdi.
     *
     * Shuning uchun DEKODLANGAN PCM hajmini o'lchaymiz: ffmpeg `-f null` chiqishidagi
     * `audio:NNNkB` faqat HAQIQIY paketlardan hosil bo'ladi, bo'shliqni sanamaydi.
     */
    public static function audio_seconds(string $path): ?float {
        $ffmpeg = self::ffmpeg();
        if (!$ffmpeg || !is_readable($path)) {
            return null;
        }
        $cmd = escapeshellcmd($ffmpeg) . ' -hide_banner -nostats'
            . ' -i ' . escapeshellarg($path) . ' -f null - 2>&1';
        $chiqish = [];
        @exec($cmd, $chiqish);
        if (!preg_match('/audio:\s*(\d+)\s*kB/i', implode("\n", (array) $chiqish), $m)) {
            return null;
        }
        list($hz, $kanal) = self::stream_info($path);
        $sek = ((int) $m[1] * 1024) / ($hz * 2 * $kanal);
        return $sek > 0 ? $sek : null;
    }

    /** Shu darsning audio yozuvi (bo'lsa). */
    public static function get(int $lessonid): ?\stdClass {
        global $DB;
        $rec = $DB->get_record('local_targetzenit_audio', ['lessonid' => $lessonid]);
        return $rec ?: null;
    }

    /**
     * Shu darsning navbatdagi bo'laklari, (avlod, tartib) bo'yicha saralangan.
     *
     * Nom shakli `<avlod>-<tartib>.part`. Eski shakl (`<tartib>.part`) ham
     * o'qiladi — deploy paytida yozilayotgan dars yo'qolmasligi uchun.
     */
    public static function parts(int $lessonid): array {
        $natija = [];
        foreach (glob(self::tempdir($lessonid) . '/*.part') ?: [] as $f) {
            $nom = basename($f);
            if (preg_match('/^(\d+)-(\d+)\.part$/', $nom, $m)) {
                $avlod = (int) $m[1];
                $tartib = (int) $m[2];
            } else if (preg_match('/^(\d+)\.part$/', $nom, $m)) {
                $avlod = 1;                 // eski shakl
                $tartib = (int) $m[1];
            } else {
                continue;                   // begona fayl — tegmaymiz
            }
            $natija[] = (object) ['avlod' => $avlod, 'tartib' => $tartib, 'yol' => $f];
        }
        usort($natija, function ($a, $b) {
            return [$a->avlod, $a->tartib] <=> [$b->avlod, $b->tartib];
        });
        return $natija;
    }

    /**
     * Tugallanmagan bo'laklarni CHETGA OLISH (o'chirmasdan).
     *
     * O'qituvchi "boshidan yozish" ni tanlaganda eski bo'laklar yo'qolmasin:
     * ular `tashlandi-<vaqt>/` papkasiga ko'chiriladi. Bu papkani Moodle'ning
     * vaqtinchalik fayl tozalagichi o'zi olib tashlaydi, lekin yaqin
     * kunlarda qo'lda tiklash mumkin bo'lib qoladi.
     */
    protected static function archive_parts(int $lessonid, array $bolaklar): void {
        $dir = self::tempdir($lessonid);
        $arxiv = $dir . '/tashlandi-' . date('Ymd-His');
        if (!is_dir($arxiv) && !@mkdir($arxiv, 0777, true)) {
            // Ko'chirib bo'lmadi — eski xatti-harakat (o'chirish)
            self::wipe($lessonid);
            return;
        }
        foreach ($bolaklar as $b) {
            @rename($b->yol, $arxiv . '/' . basename($b->yol));
        }
        debugging('local_targetzenit: dars ' . $lessonid . ' — ' . count($bolaklar)
            . ' bo\'lak chetga olindi: ' . $arxiv, DEBUG_DEVELOPER);
    }

    /** Papkani bo'laklardan tozalash. */
    protected static function wipe(int $lessonid): void {
        foreach (glob(self::tempdir($lessonid) . '/*') ?: [] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }

    /**
     * Yozishni boshlash.
     *
     * Agar shu darsda TUGALLANMAGAN yozuv qolgan bo'lsa (sahifa qayta
     * yuklandi, brauzer o'ldi, boshqa oyna ochildi) — bo'laklar
     * O'CHIRILMAYDI: yangi sessiya keyingi AVLOD dan davom etadi.
     * Shunda o'qituvchi "boshlash" ni qayta bossa ham dars boshidan
     * yozilgan qism yo'qolmaydi.
     *
     * @return \stdClass baza qatori + `_avlod`, `_davom`, `_bolaklar`
     */
    public static function start(int $lessonid, string $mime, int $teacherid,
            string $rejim = 'davom'): \stdClass {
        global $DB;

        self::tempdir($lessonid);
        $bolaklar = self::parts($lessonid);
        $rec = self::get($lessonid);

        // Qoldiq qanchalik yangi? Juda eski bo'lsa — boshqa darsning izi.
        $oxirgi = 0;
        foreach ($bolaklar as $b) {
            $oxirgi = max($oxirgi, (int) @filemtime($b->yol));
        }
        $yangi_qoldiq = $oxirgi > time() - self::RESUME_WINDOW;

        // ============ DAVOM ETTIRISH YOKI BOSHIDAN? — O'QITUVCHI TANLAYDI
        //
        // 2026-11-12 da bilib olindi (dars 201): tizim "sahifam qayta
        // yuklandi, davom et" va "noto'g'ri boshladim, boshidan yozaman"
        // holatlarini AJRATA OLMAYDI — ikkisida ham tugallanmagan bo'laklar
        // qoladi va "Boshlash" bosiladi.
        //
        // Avval kod HAR DOIM davom ettirardi. Natijada 33 soniyalik noto'g'ri
        // boshlangan parcha 20 daqiqalik darsning OLDIGA ulanib qolgan va
        // faylda "ikki xil ovoz" paydo bo'lgan.
        //
        // Endi tanlov `rejim` bilan keladi: `davom` yoki `yangi`.
        $qoldiq_bor = $bolaklar && $yangi_qoldiq && $rec && $rec->status === 'recording';
        $davom = $qoldiq_bor && $rejim !== 'yangi';

        if ($davom) {
            $avlod = (int) end($bolaklar)->avlod + 1;
        } else {
            if ($bolaklar) {
                // BO'LAKLAR O'CHIRILMAYDI — chetga olinadi. Agar o'qituvchi
                // adashib "boshidan" tanlasa, yozuv tiklanadigan bo'lib
                // qolsin (`tashlandi-<vaqt>/` papkasida).
                self::archive_parts($lessonid, $bolaklar);
            }
            $avlod = 1;
            $bolaklar = [];
        }

        $sessionid = bin2hex(random_bytes(8));

        if ($rec) {
            // BOSHQA FOYDALANUVCHI qoldig'i ustiga yozilyaptimi? Bu holda
            // faylda ikki xil ovoz bo'lishi mumkin — belgilab qo'yamiz, aks
            // holda keyin sababini jurnaldan qidirishga to'g'ri keladi
            // (2026-11-12 da aynan shunday bo'ldi).
            $ogoh = null;
            if ($davom && $rec->teacherid && (int) $rec->teacherid !== $teacherid) {
                $ogoh = 'DIQQAT: yozuvni ikki xil foydalanuvchi boshlagan '
                    . '(' . (int) $rec->teacherid . ' va ' . $teacherid . ') — '
                    . 'faylda ikki xil ovoz bo\'lishi mumkin';
                debugging('local_targetzenit: dars ' . $lessonid . ' — ' . $ogoh,
                    DEBUG_DEVELOPER);
            }

            $rec->status = 'recording';
            $rec->sessionid = $sessionid;
            $rec->mimetype = $mime;
            $rec->teacherid = $teacherid;
            $rec->warning = $ogoh;
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);
        } else {
            $rec = (object) [
                'lessonid' => $lessonid,
                'status' => 'recording',
                'sessionid' => $sessionid,
                'mimetype' => $mime,
                'filesize' => 0,
                'duration' => 0,
                'segments' => 0,
                'teacherid' => $teacherid,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $rec->id = $DB->insert_record('local_targetzenit_audio', $rec);
        }

        $rec->_avlod = $avlod;
        $rec->_davom = $davom;
        $rec->_bolaklar = count($bolaklar);
        return $rec;
    }

    /**
     * Bitta bo'lakni saqlash.
     *
     * Sessiya tekshiruvi: boshqa oyna/qurilma yozuvni egallagan bo'lsa,
     * eski brauzerning bo'lagi QABUL QILINMAYDI — aks holda ikki oqim
     * bir faylga aralashardi.
     *
     * @return array ['bytes' => yig'ilgan hajm, 'parts' => bo'laklar soni]
     */
    public static function chunk(int $lessonid, int $avlod, int $seq,
            string $sessionid, string $data, ?\context $context = null): array {

        if (strlen($data) > self::MAX_CHUNK) {
            throw new audio_xato('katta', 'Bo\'lak juda katta');
        }

        $rec = self::get($lessonid);
        if (!$rec) {
            throw new audio_xato('yoq', 'Yozuv sessiyasi topilmadi — qaytadan boshlang');
        }
        if ($rec->status !== 'recording') {
            // Yozuv yakunlangan. Ikki holat bor:
            //
            //  a) CRON muddatidan oldin yakunlagan. Internet 30 daqiqadan
            //     ko'proq yo'q edi, bo'lak kelmadi, cron "tashlab ketilgan"
            //     deb o'yladi — lekin o'sha brauzer hali TIRIK va aloqa
            //     qaytgach navbatini yuborayapti. Bunda yozuvni QAYTA
            //     OCHAMIZ: tayyor fayl 0-avlod bo'lib qaytadi, oxirida
            //     hammasi qayta ulanadi va hech narsa yo'qolmaydi.
            //     (`sessionid` saqlanib turgani — shu brauzer ekanining dalili;
            //     brauzerning o'zi yakunlaganda kalit tozalanadi.)
            //
            //  b) Boshqa hamma holat — bo'lakni QABUL QILMAYMIZ, aks holda
            //     `finish` tayyor faylni kech kelgan parcha bilan
            //     almashtirib yuborardi.
            if ($context && !empty($rec->sessionid) && $sessionid === $rec->sessionid
                    && in_array($rec->status, self::FAYLLI, true)) {
                self::reopen($rec, $context);
            } else {
                throw new audio_xato('tugagan',
                    'Bu yozuv allaqachon saqlangan — yangi bo\'lak qabul qilinmaydi');
            }
        }
        if (!empty($rec->sessionid) && $sessionid !== $rec->sessionid) {
            throw new audio_xato('boshqa_sessiya',
                'Bu dars boshqa oyna yoki qurilmada yozilmoqda');
        }

        $dir = self::tempdir($lessonid);
        $hajm = self::staged_size($lessonid) + strlen($data);
        if ($hajm > self::maxbytes()) {
            throw new audio_xato('chegara', 'Yozuv hajmi chegaradan oshdi');
        }

        // ATOMAR YOZISH: avval `.tmp`, keyin `rename`. To'g'ridan-to'g'ri
        // yozilganda so'rov yarmida uzilsa, yarim bo'lak qolib ketardi va
        // birlashtirishda ovoz buzilardi.
        $nishon = sprintf('%s/%03d-%06d.part', $dir, $avlod, $seq);
        $vaqtinchalik = $nishon . '.tmp';
        if (file_put_contents($vaqtinchalik, $data) !== strlen($data)) {
            @unlink($vaqtinchalik);
            throw new audio_xato('disk', 'Bo\'lakni diskka yozib bo\'lmadi');
        }
        if (!rename($vaqtinchalik, $nishon)) {
            @unlink($vaqtinchalik);
            throw new audio_xato('disk', 'Bo\'lakni joyiga qo\'yib bo\'lmadi');
        }

        return ['bytes' => $hajm, 'parts' => count(self::parts($lessonid))];
    }

    /**
     * Muddatidan oldin yakunlangan yozuvni qayta ochish.
     *
     * Tayyor fayl 0-AVLOD bo'lib vaqtinchalik papkaga qaytadi — shunda
     * keyingi `finish` uni kech kelgan bo'laklar bilan birga qayta ulaydi.
     * Yarim darsga qilingan AI tahlili tozalanadi: to'liq fayl saqlangach
     * tahlil o'zi qaytadan navbatga tushadi.
     */
    protected static function reopen(\stdClass $rec, \context $context): void {
        global $DB;

        $dir = self::tempdir((int) $rec->lessonid);
        $fs = get_file_storage();
        $fayllar = $fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA,
            (int) $rec->lessonid, 'id', false);
        $f = $fayllar ? reset($fayllar) : null;
        if ($f) {
            $f->copy_content_to($dir . '/000-000001.part');
            foreach ($fayllar as $x) {
                $x->delete();
            }
        }

        $rec->status = 'recording';
        $rec->filename = null;
        $rec->filesize = 0;
        $rec->transcript = null;
        $rec->analysis = null;
        $rec->aierror = null;
        $rec->analysedat = 0;
        $rec->warning = null;
        $rec->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $rec);

        debugging('local_targetzenit: dars ' . $rec->lessonid
            . ' yozuvi qayta ochildi — kech kelgan bo\'laklar qo\'shiladi', DEBUG_DEVELOPER);
    }

    /**
     * Brauzer hodisalari jurnalini saqlash (QORA QUTI).
     *
     * Yozuv nega jim qolganini KEYIN aniqlash uchun. Jurnal brauzerdan
     * JSON bo'lib keladi: har qator [soniya, hodisa, izoh].
     * Audio yoki shaxsiy ma'lumot YO'Q — faqat texnik hodisalar.
     *
     * Sessiya tekshirilmaydi: jurnal yo'qotishdan ko'ra ortiqcha yozilsa
     * yaxshi, u faqat diagnostika uchun.
     */
    public static function save_diag(int $lessonid, string $json): void {
        global $DB;
        $rec = self::get($lessonid);
        if (!$rec) {
            return;
        }
        // Chegara: juda uzun jurnal bazani shishirmasin (oxiri muhimroq)
        if (strlen($json) > 60000) {
            $json = substr($json, -60000);
        }
        $rec->diag = $json;
        $rec->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $rec);
    }

    /** Hozirgacha yuklangan bo'laklar hajmi. */
    public static function staged_size(int $lessonid): int {
        $jami = 0;
        foreach (glob(self::tempdir($lessonid) . '/*.part') ?: [] as $f) {
            $jami += (int) @filesize($f);
        }
        return $jami;
    }

    /**
     * Yozishni tugatish: bo'laklarni butun faylga yig'ib Moodle omboriga joylaydi.
     *
     * Bir avlod ichida — bayt-ba-bayt (bitta uzluksiz oqim).
     * Avlodlar orasida — ffmpeg `concat` (har avlodning o'z sarlavhasi bor,
     * bayt-ulash ularni o'qilmas qilib qo'yadi — fayl boshidagi izohga qara).
     *
     * @param string|null $sessionid brauzer sessiyasi; cron uchun null
     */
    public static function finish(int $lessonid, \context $context, int $duration = 0,
            ?string $sessionid = null): \stdClass {
        global $DB;

        // QULF: `finish` ikki marta kelishi mumkin (brauzer javobni kutmay
        // qayta yuboradi). Qulfsiz ikkinchi chaqiruv bo'laklarni o'chirilgan
        // holda topib yozuvni «failed» deb belgilardi — tayyor fayl ustiga.
        $qulfzavod = \core\lock\lock_config::get_lock_factory('local_targetzenit_audio');
        $qulf = $qulfzavod->get_lock('lesson_' . $lessonid, 20);
        if (!$qulf) {
            throw new audio_xato('band', 'Yozuv shu payt saqlanmoqda — bir oz kutib turing');
        }

        try {
            $rec = self::get($lessonid);
            if (!$rec) {
                throw new audio_xato('yoq', 'Yozuv sessiyasi topilmadi');
            }
            if ($sessionid !== null && !empty($rec->sessionid)
                    && $sessionid !== $rec->sessionid) {
                throw new audio_xato('boshqa_sessiya',
                    'Bu dars boshqa oyna yoki qurilmada yozilmoqda');
            }

            $dir = self::tempdir($lessonid);
            $bolaklar = self::parts($lessonid);

            if (!$bolaklar) {
                // IDEMPOTENT: avvalgi chaqiruv muvaffaqiyatli yakunlagan bo'lsa,
                // tayyor natijani qaytaramiz — «failed» qilib buzmaymiz.
                if (in_array($rec->status, self::FAYLLI, true) && !empty($rec->filename)) {
                    return $rec;
                }
                $rec->status = 'failed';
                $rec->sessionid = null;
                $rec->timemodified = time();
                $DB->update_record('local_targetzenit_audio', $rec);
                throw new audio_xato('bosh', 'Birorta ham bo\'lak kelmadi');
            }

            $ext = self::ext($rec->mimetype);
            $ogohlantirish = [];

            // --- 1) Avlodlar bo'yicha guruhlab, har birini bayt-ba-bayt ulaymiz
            $guruh = [];
            foreach ($bolaklar as $b) {
                $guruh[$b->avlod][] = $b;
            }
            ksort($guruh, SORT_NUMERIC);

            $avlodfayllar = [];
            foreach ($guruh as $avlod => $list) {
                // UZILISH TEKSHIRUVI: tartib raqamlari 1..N uzluksiz bo'lishi kerak.
                // Uzilgan bo'lsa ovozda teshik bor — bu JIM qolmasligi shart.
                $raqamlar = array_map(function ($x) { return $x->tartib; }, $list);
                $kutilgan = max($raqamlar);
                if (count($raqamlar) !== $kutilgan) {
                    $ogohlantirish[] = sprintf('%d-avlodda %d/%d bo\'lak',
                        $avlod, count($raqamlar), $kutilgan);
                }

                $yol = sprintf('%s/avlod-%03d.%s', $dir, $avlod, $ext);
                $out = fopen($yol, 'wb');
                if (!$out) {
                    throw new audio_xato('disk', 'Birlashtirish uchun fayl ochilmadi');
                }
                foreach ($list as $b) {
                    $in = fopen($b->yol, 'rb');
                    if (!$in) {
                        continue;
                    }
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                }
                fclose($out);
                if (filesize($yol) > 0) {
                    $avlodfayllar[] = $yol;
                }
            }

            if (!$avlodfayllar) {
                throw new audio_xato('bosh', 'Bo\'laklar bo\'sh chiqdi');
            }

            // --- 2) Avlodlarni bitta faylga qo'shamiz
            $birlashgan = $dir . '/birlashgan.' . $ext;
            if (count($avlodfayllar) === 1) {
                // BITTA avlod bo'lsa ham remux SHART: MediaRecorder bergan
                // oqimda davomiylik yo'q va pleyer surib qo'yishga ruxsat
                // bermaydi (`remux()` izohiga qara).
                if (!self::remux($avlodfayllar[0], $birlashgan)) {
                    rename($avlodfayllar[0], $birlashgan);
                    $ogohlantirish[] = 'remux bo\'lmadi — pleyerda vaqt chizig\'i chiqmasligi mumkin';
                }
            } else {
                // `join_segments` ffmpeg concat bilan ulaydi va sarlavhani
                // o'zi qaytadan yozadi — ya'ni natija allaqachon qidiriladigan.
                $xato = self::join_segments($avlodfayllar, $birlashgan, $dir);
                if ($xato) {
                    $ogohlantirish[] = $xato;
                }
            }
            if (!file_exists($birlashgan) || filesize($birlashgan) === 0) {
                throw new audio_xato('birlashma', 'Birlashtirilgan fayl bo\'sh chiqdi');
            }

            // --- 3) HAQIQIY OVOZ UZUNLIGI va KESILISH tekshiruvi
            //
            // Brauzer bergan `$duration` — devor soati, ya'ni "necha daqiqa
            // yozuv tugmasi bosilgan turdi". Agar oyna fonga o'tib Chrome uni
            // muzlatsa, MediaRecorder bo'lak bermay qo'yadi, lekin soat
            // yuraveradi — natijada 3 daqiqalik ovoz "33 daqiqa" bo'lib
            // saqlanadi (2026-11-11, dars 159). Shuning uchun uchta mustaqil
            // o'lchov bilan tekshiramiz.
            $devorsoat = $duration;

            // (a) ENG ISHONCHLI VA ARZON: nechta bo'lak keldi? Har bo'lak
            //     ~15 soniya, demak kutilgan ovoz = bo'lak soni x 15.
            //     ffmpeg kerak emas, hamma holatda ishlaydi.
            $bolakdan = count($bolaklar) * self::SLICE_SEC;

            // (b) ANIQ: dekodlangan PCM hajmi (bo'shliqni sanamaydi)
            $ovozsek = self::audio_seconds($birlashgan);

            // (c) Konteyner davomiyligi — faqat zaxira uchun; bo'shliqni ham
            //     qo'shib ko'rsatishi mumkin, shuning uchun unga tayanmaymiz.
            $konteyner = self::probe_duration($birlashgan);

            $ovoz = $ovozsek !== null ? (int) round($ovozsek) : $bolakdan;
            $kesilgan = false;

            if ($devorsoat > 120 && $ovoz < $devorsoat * 0.8) {
                $kesilgan = true;
                $ogohlantirish[] = sprintf(
                    'KESILGAN: %s ovoz bor, %s yozilgan (%d bo\'lak) — '
                    . 'yozuv oynasi ishlashdan to\'xtagan',
                    self::hhmm($ovoz), self::hhmm($devorsoat), count($bolaklar));
            }

            // Davomiylik sifatida HAQIQIY ovoz uzunligini yozamiz: panellar va
            // hisobotlar yo'q narsani ko'rsatmasligi kerak.
            $duration = $ovoz ?: ($konteyner ?: $devorsoat);

            // --- 4) Omborga joylaymiz: AVVAL yangi faylni yaratamiz, keyin
            //        eskisini o'chiramiz. Teskarisi bo'lsa, yaratish xato
            //        bersa dars butunlay yo'qolardi.
            $fs = get_file_storage();
            $eskilar = $fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA,
                $lessonid, 'id', false);

            // FAYL NOMI MAKTAB VAQTIDA (Asia/Tashkent) — server UTC da ishlasa
            // ham. 2026-11-12 da sayt vaqt mintaqasi Europe/London edi va
            // nomlardagi soat 5 soat orqada chiqardi; nom tarixda qoladi,
            // shuning uchun uni sozlamaga bog'lamaymiz.
            $filename = 'dars-' . $lessonid . '-'
                . (new \DateTime('now', new \DateTimeZone(self::MAKTAB_TZ)))->format('Ymd-His')
                . '.' . $ext;
            $fs->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILEAREA,
                'itemid' => $lessonid,
                'filepath' => '/',
                'filename' => $filename,
            ], $birlashgan);

            foreach ($eskilar as $eski) {
                if ($eski->get_filename() !== $filename) {
                    $eski->delete();
                }
            }

            $rec->status = 'ready';
            // Brauzer o'zi yakunlagan bo'lsa kalitni tozalaymiz: endi kech
            // kelgan parcha yozuvni qayta ocha olmasligi kerak. Cron
            // yakunlaganda (`$sessionid === null`) kalit QOLADI — brauzer
            // hali tirik bo'lishi mumkin, qaytib kelsa davom etadi.
            if ($sessionid !== null) {
                $rec->sessionid = null;
            }
            $rec->filename = $filename;
            $rec->filesize = filesize($birlashgan);
            $rec->duration = $duration;
            $rec->segments = count($avlodfayllar);
            $rec->audiblesec = $ovoz;
            $rec->wallsec = $devorsoat;
            $rec->warning = $ogohlantirish
                ? \core_text::substr(implode('; ', $ogohlantirish), 0, 255) : null;
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);

            if ($ogohlantirish) {
                debugging('local_targetzenit: dars ' . $lessonid . ' audio ogohlantirishi — '
                    . $rec->warning, DEBUG_DEVELOPER);
            }

            // Vaqtinchalik fayllarni tozalaymiz (faqat natija saqlangandan KEYIN)
            self::wipe($lessonid);
            @rmdir($dir);

            // AI tahlilni DARHOL navbatga qo'yamiz — 15 daqiqalik jadvalni kutmaydi.
            // Moodle croni har daqiqada ishlagani uchun tahlil ~1 daqiqada boshlanadi.
            //
            // KESILGAN yozuv tahlilga BERILMAYDI. Sabab (2026-11-11): jim
            // joylarga model dars matnini O'YLAB TOPADI va zavuch soxta
            // bahoni o'qiydi. Tahlilsiz qolgani — soxta tahlildan yaxshi.
            if ($kesilgan) {
                debugging('local_targetzenit: dars ' . $lessonid
                    . ' AI tahliliga berilmadi — yozuv kesilgan', DEBUG_DEVELOPER);
            } else if (ai::enabled()) {
                task\analyse_one::queue($lessonid);
            }

            return $rec;

        } finally {
            $qulf->release();
        }
    }

    /**
     * Faylni QIDIRILADIGAN holga keltirish (remux, qayta kodlashsiz).
     *
     * NEGA KERAK — 2026-11-11 da o'lchab aniqlangan: MediaRecorder "oqim"
     * (live) webm beradi, unda DAVOMIYLIK ham, qidiruv indeksi (Cues) ham
     * YO'Q. Brauzer bunday faylni `duration = Infinity` deb ko'radi, ya'ni
     * pleyerda suriladigan vaqt chizig'i CHIQMAYDI — o'qituvchi 10- yoki
     * 15-daqiqaga o'tolmaydi, faqat boshidan tinglaydi.
     *
     * `ffmpeg -c copy` sarlavhani qaytadan yozadi: davomiylik va Cues paydo
     * bo'ladi. Sinovda: `duration=Infinity` -> `1978.7s`, `seekable` to'liq
     * fayl, 120-soniyaga o'tish ishladi. Qayta kodlash YO'Q, shuning uchun
     * sifat o'zgarmaydi va 45 daqiqalik fayl ~1 soniyada qayta yoziladi.
     *
     * Yonma-yon foyda: `probe_duration()` endi ishlaydi, AI bo'laklarga
     * to'g'ri kesadi, hisobotlarda haqiqiy uzunlik ko'rinadi.
     *
     * @return bool muvaffaqiyatli bo'lsa true (aks holda xom fayl qoladi)
     */
    public static function remux(string $xom, string $nishon): bool {
        $ffmpeg = self::ffmpeg();
        if (!$ffmpeg) {
            return false;
        }
        $cmd = escapeshellcmd($ffmpeg) . ' -hide_banner -loglevel error -y'
            . ' -i ' . escapeshellarg($xom)
            . ' -c copy ' . escapeshellarg($nishon) . ' 2>&1';
        $chiqish = [];
        @exec($cmd, $chiqish, $kod);

        if ($kod === 0 && file_exists($nishon) && filesize($nishon) > 0) {
            return true;
        }
        @unlink($nishon);
        debugging('local_targetzenit: remux bo\'lmadi — '
            . implode(' ', (array) $chiqish), DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Avlodlarni bitta o'qiladigan faylga qo'shish.
     *
     * ffmpeg `concat` demuxer sarlavhalarni to'g'ri qayta yozadi; `-c copy`
     * bo'lgani uchun qayta kodlash yo'q. ffmpeg topilmasa — bayt-ulashga
     * qaytamiz, lekin bu holda faqat BIRINCHI avlod o'qilishini aytib
     * qo'yamiz (jim yo'qotishdan ko'ra ogohlantirish yaxshi).
     *
     * @return string|null ogohlantirish matni yoki null
     */
    protected static function join_segments(array $fayllar, string $nishon, string $dir): ?string {
        $ffmpeg = self::ffmpeg();

        if ($ffmpeg) {
            $royxat = $dir . '/royxat.txt';
            $satr = '';
            foreach ($fayllar as $f) {
                $satr .= "file '" . str_replace("'", "'\\''", $f) . "'\n";
            }
            file_put_contents($royxat, $satr);

            foreach ([' -c copy', ' -c:a libopus -b:a 24k'] as $kodek) {
                $cmd = escapeshellcmd($ffmpeg) . ' -hide_banner -loglevel error -y'
                    . ' -f concat -safe 0 -i ' . escapeshellarg($royxat)
                    . $kodek . ' ' . escapeshellarg($nishon) . ' 2>&1';
                $chiqish = [];
                @exec($cmd, $chiqish, $kod);
                if ($kod === 0 && file_exists($nishon) && filesize($nishon) > 0) {
                    @unlink($royxat);
                    return count($fayllar) > 1
                        ? sprintf('%d qismdan ulandi (yozuv uzilgan)', count($fayllar))
                        : null;
                }
                @unlink($nishon);
            }
            @unlink($royxat);
            debugging('local_targetzenit: ffmpeg concat bo\'lmadi — bayt-ulashga qaytamiz',
                DEBUG_DEVELOPER);
        }

        // Zaxira yo'l: bayt-ulash. Faqat birinchi avlod eshitiladi.
        $out = fopen($nishon, 'wb');
        foreach ($fayllar as $f) {
            $in = fopen($f, 'rb');
            if ($in) {
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        }
        fclose($out);
        return sprintf('DIQQAT: ffmpeg yo\'q — %d qism ulanmadi, faqat birinchisi eshitiladi',
            count($fayllar));
    }

    /** Yozishni bekor qilish (bo'laklar o'chadi). */
    public static function cancel(int $lessonid): void {
        global $DB;
        self::wipe($lessonid);
        @rmdir(self::tempdir($lessonid));
        $rec = self::get($lessonid);
        if ($rec && $rec->status === 'recording') {
            $DB->delete_records('local_targetzenit_audio', ['id' => $rec->id]);
        }
    }

    /**
     * Tashlab ketilgan yozuvlarni yakunlash.
     *
     * O'qituvchi "To'xtatish" ni bosmay brauzerni yopsa yoki sahifa qayta
     * yuklansa, bo'laklar serverda qolib ketadi. Shu vazifa ularni fayl qilib
     * yig'adi — aks holda yozilgan dars behuda yo'qolardi.
     *
     * @param int $jimlik_daqiqa oxirgi bo'lakdan keyin shuncha daqiqa o'tgan bo'lsa.
     *   Uzoq internet uzilishida brauzer hali yozayotgan bo'lishi mumkin —
     *   shuning uchun oyna keng, va `chunk()` bunday yozuvni qayta ocha oladi.
     */
    public static function finalize_stale(int $jimlik_daqiqa = 30): array {
        global $DB;

        $ochiq = $DB->get_records('local_targetzenit_audio', ['status' => 'recording']);
        $yakunlandi = 0;
        $tashlandi = 0;

        foreach ($ochiq as $a) {
            $bolaklar = self::parts((int) $a->lessonid);

            if (!$bolaklar) {
                // Bo'lak umuman kelmagan — bo'sh sessiya, tozalaymiz
                if ($a->timemodified < time() - ($jimlik_daqiqa * MINSECS)) {
                    $DB->delete_records('local_targetzenit_audio', ['id' => $a->id]);
                    $tashlandi++;
                }
                continue;
            }

            // Oxirgi bo'lak qachon kelgan?
            $oxirgi = 0;
            foreach ($bolaklar as $b) {
                $oxirgi = max($oxirgi, (int) @filemtime($b->yol));
            }
            if ($oxirgi > time() - ($jimlik_daqiqa * MINSECS)) {
                continue;   // hali yozilyapti — tegmaymiz
            }

            $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid], 'id, courseid');
            if (!$dars) {
                continue;
            }
            $ctx = \context_course::instance($dars->courseid, IGNORE_MISSING);
            if (!$ctx) {
                continue;
            }

            try {
                // Davomiylik: bo'laklar soni x bo'lak uzunligi (taxminiy —
                // `finish` ichida ffprobe aniqrog'i bilan almashtiradi)
                self::finish((int) $a->lessonid, $ctx, count($bolaklar) * self::SLICE_SEC);
                $yakunlandi++;
            } catch (\Throwable $e) {
                debugging('local_targetzenit: yakunlab bo\'lmadi — ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return ['yakunlandi' => $yakunlandi, 'tashlandi' => $tashlandi];
    }

    /** Faylga havola (faqat ruxsati borlarga). */
    public static function file_url(int $lessonid, \context $context): ?\moodle_url {
        $rec = self::get($lessonid);
        if (!$rec || !in_array($rec->status, self::FAYLLI, true) || !$rec->filename) {
            return null;
        }
        return \moodle_url::make_pluginfile_url($context->id, self::COMPONENT,
            self::FILEAREA, $lessonid, '/', $rec->filename);
    }

    /**
     * Muddati o'tgan audiolarni o'chiradi (transkript matni qoladi).
     * Qaytaradi: o'chirilgan yozuvlar soni va bo'shagan bayt.
     */
    public static function cleanup(): array {
        global $DB;

        $kun = (int) (get_config(self::COMPONENT, 'audioretentiondays') ?: 15);
        $chegara = time() - ($kun * DAYSECS);

        $eskilar = $DB->get_records_select('local_targetzenit_audio',
            "status IN ('ready', 'transcribed') AND timecreated < :t", ['t' => $chegara]);

        $fs = get_file_storage();
        $soni = 0;
        $bayt = 0;

        foreach ($eskilar as $a) {
            $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid], 'id, courseid');
            if ($dars) {
                $ctx = \context_course::instance($dars->courseid, IGNORE_MISSING);
                if ($ctx) {
                    $fs->delete_area_files($ctx->id, self::COMPONENT, self::FILEAREA, $a->lessonid);
                }
            }
            $bayt += (int) $a->filesize;
            $a->status = 'deleted';
            $a->filename = null;
            $a->filesize = 0;
            $a->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $a);
            $soni++;
        }

        return ['soni' => $soni, 'bayt' => $bayt];
    }
}
