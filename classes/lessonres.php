<?php
// Dars resurslari — o'quvchiga KO'RINADIGAN slayd/video/havolalar.
//
// Doska materiali (`material.php`, filearea=lessonboard) bilan ADASHTIRMANG:
// u bitta fayl va AI tahlili uchun dalil; bu esa o'qituvchi darsga tayyorlagan
// va o'quvchi uyda qaytib ochadigan materiallar to'plami (portal 2-bosqichi).
//
// Har resurs — `_lessonres` da bitta qator (unikal id). Fayl turi uchun fayl
// Moodle omborida filearea=lessonres, itemid=<qator id> da yotadi; havola turi
// uchun url ustuni ishlatiladi. Qator va fayl 1:1 — o'chirishda ikkisi birga.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class lessonres {

    const COMPONENT = 'local_targetzenit';
    const FILEAREA = 'lessonres';

    /** Bitta darsga ko'pi bilan nechta resurs (fayl + havola birga). */
    const LIMIT = 10;

    /**
     * Bitta fayl chegarasi — 100 MB (video uchun). Amalda server PHP
     * sozlamalari (upload_max_filesize) bundan kichik bo'lsa, o'sha g'olib.
     */
    const MAX_BYTES = 104857600;

    /** Qabul qilinadigan turlar (doska materiali ro'yxati + video/audio). */
    const TURLAR = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/msword' => 'doc',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
    ];

    /**
     * Darsning resurslari, tartib bo'yicha.
     *
     * @param bool $faqatochiq true = faqat o'quvchiga ochiqlari (koringan=1)
     */
    public static function rows(int $lessonid, bool $faqatochiq = false): array {
        global $DB;
        $shart = ['lessonid' => $lessonid];
        if ($faqatochiq) {
            $shart['koringan'] = 1;
        }
        return array_values($DB->get_records('local_targetzenit_lessonres',
            $shart, 'tartib ASC, id ASC'));
    }

    /** Fayl turidagi resursning saqlangan fayli. */
    public static function file_for(\stdClass $res, \context $context): ?\stored_file {
        if ($res->turi !== 'fayl') {
            return null;
        }
        $fs = get_file_storage();
        $fayllar = $fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA,
            (int) $res->id, 'id', false);
        return $fayllar ? reset($fayllar) : null;
    }

    /** Resursga havola: fayl uchun pluginfile URL, havola uchun url ustuni. */
    public static function url_for(\stdClass $res, \context $context): ?string {
        if ($res->turi === 'havola') {
            return (string) $res->url;
        }
        $f = self::file_for($res, $context);
        if (!$f) {
            return null;
        }
        return \moodle_url::make_pluginfile_url($context->id, self::COMPONENT,
            self::FILEAREA, (int) $res->id, '/', $f->get_filename())->out(false);
    }

    /** Ko'rsatiladigan nom: title bo'sh bo'lsa fayl nomi / url. */
    public static function nom(\stdClass $res, \context $context): string {
        if (trim((string) $res->title) !== '') {
            return (string) $res->title;
        }
        if ($res->turi === 'havola') {
            return (string) $res->url;
        }
        $f = self::file_for($res, $context);
        return $f ? $f->get_filename() : '';
    }

    /** Inson o'qiydigan tur belgisi (PDF, MP4, HAVOLA...). */
    public static function tur_belgisi(\stdClass $res, \context $context): string {
        if ($res->turi === 'havola') {
            return get_string('res_link', 'local_targetzenit');
        }
        $f = self::file_for($res, $context);
        return $f ? strtoupper(self::TURLAR[$f->get_mimetype()] ?? 'FAYL') : 'FAYL';
    }

    /**
     * Yuklangan FAYLNI resurs qilib qo'shadi.
     *
     * Tartib material::save_upload bilan bir xil (qo'lda yozilgan sahifa,
     * Moodle formasi yo'q): hajm, tur (fayl MAZMUNIDAN), antivirus. Farqi —
     * avval `_lessonres` qatori ochiladi (unikal id kerak, chunki fayl
     * itemid'i shu id), fayl yaratish yiqilsa qator ham o'chiriladi.
     *
     * @return string|null xato matni; muvaffaqiyatli bo'lsa null
     */
    public static function add_file(int $lessonid, \context $context, string $maydon,
                                     string $title, int $userid): ?string {
        global $DB;

        if (count(self::rows($lessonid)) >= self::LIMIT) {
            return get_string('res_err_limit', 'local_targetzenit', self::LIMIT);
        }
        if (empty($_FILES[$maydon]) || !is_array($_FILES[$maydon])) {
            return get_string('mt_err_empty', 'local_targetzenit');
        }
        $yuk = $_FILES[$maydon];

        if ((int) $yuk['error'] === UPLOAD_ERR_NO_FILE) {
            return get_string('mt_err_empty', 'local_targetzenit');
        }
        if ((int) $yuk['error'] === UPLOAD_ERR_INI_SIZE
                || (int) $yuk['error'] === UPLOAD_ERR_FORM_SIZE) {
            return get_string('mt_err_big', 'local_targetzenit',
                round(self::MAX_BYTES / 1048576));
        }
        if ((int) $yuk['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($yuk['tmp_name'])) {
            return get_string('mt_err_upload', 'local_targetzenit');
        }
        if (filesize($yuk['tmp_name']) > self::MAX_BYTES) {
            return get_string('mt_err_big', 'local_targetzenit',
                round(self::MAX_BYTES / 1048576));
        }

        $mime = \file_storage::mimetype($yuk['tmp_name'], $yuk['name']);
        if (!isset(self::TURLAR[$mime])) {
            return get_string('res_err_type', 'local_targetzenit',
                strtoupper(implode(', ', array_unique(array_values(self::TURLAR)))));
        }

        if (class_exists('\core\antivirus\manager')) {
            try {
                \core\antivirus\manager::scan_file($yuk['tmp_name'], $yuk['name'], true);
            } catch (\Throwable $e) {
                return get_string('mt_err_virus', 'local_targetzenit');
            }
        }

        $nom = clean_param($yuk['name'], PARAM_FILE);
        if ($nom === '') {
            $nom = 'resurs.' . self::TURLAR[$mime];
        }

        $resid = $DB->insert_record('local_targetzenit_lessonres', (object) [
            'lessonid' => $lessonid,
            'turi' => 'fayl',
            'url' => null,
            'title' => \core_text::substr(trim($title), 0, 255),
            'tartib' => self::keyingi_tartib($lessonid),
            'koringan' => 1,
            'userid' => $userid,
            'timecreated' => time(),
        ]);

        try {
            get_file_storage()->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILEAREA,
                'itemid' => (int) $resid,
                'filepath' => '/',
                'filename' => $nom,
            ], $yuk['tmp_name']);
        } catch (\Throwable $e) {
            // Fayl yaratilmadi — yarim qolgan qator ham turmasin.
            $DB->delete_records('local_targetzenit_lessonres', ['id' => $resid]);
            return get_string('mt_err_upload', 'local_targetzenit');
        }
        return null;
    }

    /**
     * HAVOLANI resurs qilib qo'shadi (YouTube, interaktiv qo'llanma...).
     *
     * @return string|null xato matni; muvaffaqiyatli bo'lsa null
     */
    public static function add_link(int $lessonid, string $url, string $title,
                                     int $userid): ?string {
        global $DB;

        if (count(self::rows($lessonid)) >= self::LIMIT) {
            return get_string('res_err_limit', 'local_targetzenit', self::LIMIT);
        }
        $url = trim($url);
        if (clean_param($url, PARAM_URL) !== $url
                || !preg_match('#^https?://#i', $url)) {
            return get_string('res_err_url', 'local_targetzenit');
        }

        $DB->insert_record('local_targetzenit_lessonres', (object) [
            'lessonid' => $lessonid,
            'turi' => 'havola',
            'url' => \core_text::substr($url, 0, 1333),
            'title' => \core_text::substr(trim($title), 0, 255),
            'tartib' => self::keyingi_tartib($lessonid),
            'koringan' => 1,
            'userid' => $userid,
            'timecreated' => time(),
        ]);
        return null;
    }

    /**
     * Resursni o'chiradi (fayli bilan).
     *
     * Dars tekshiruvi SHART: id boshqa kursning resursi bo'lsa, o'z kursida
     * `teach` huquqi bor o'qituvchi uni o'chira olmasligi kerak.
     */
    public static function delete(int $resid, int $lessonid, \context $context): bool {
        global $DB;

        $res = $DB->get_record('local_targetzenit_lessonres',
            ['id' => $resid, 'lessonid' => $lessonid]);
        if (!$res) {
            return false;
        }
        get_file_storage()->delete_area_files($context->id, self::COMPONENT,
            self::FILEAREA, (int) $res->id);
        $DB->delete_records('local_targetzenit_lessonres', ['id' => $res->id]);
        return true;
    }

    /** Keyingi tartib raqami. */
    protected static function keyingi_tartib(int $lessonid): int {
        global $DB;
        return (int) $DB->get_field('local_targetzenit_lessonres',
            'COALESCE(MAX(tartib), 0) + 1', ['lessonid' => $lessonid]);
    }
}
