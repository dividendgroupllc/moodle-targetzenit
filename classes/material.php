<?php
// Darsning DOSKADAGI MATERIALI — o'qituvchi ko'rsatgan slayd/rasm/PDF.
//
// NEGA KERAK (2026-11-11, foydalanuvchi talabi): AI tahlili faqat AUDIONI
// eshitadi, doskani KO'RMAYDI. Shu sababli tahlilda qayta-qayta "ko'rgazmali
// vositalardan foydalanmadingiz" deb yozilardi — o'qituvchi aslida slayd
// ko'rsatgan bo'lsa ham. Bu nohaq baho.
//
// Yechim: o'qituvchi darsda doskada ko'rsatgan faylni shu yerga biriktiradi,
// AI esa tahlilda uni KO'RADI va dalil sifatida hisobga oladi (ya'ni tegishli
// kategoriyalar `low` ishonch bilan tashlab yuborilmaydi, balki haqiqiy ball
// oladi — `ai.php` dagi "DOSKADAGI MATERIAL" bo'limiga qara).
//
// BITTA FAYL: ataylab. O'qituvchiga "hammasini yuklang" demaymiz — darsning
// asosiy ko'rgazmasi bitta bo'ladi (slayd to'plami yoki rasm), va bitta fayl
// yuklash ikki bosqichli ish emas.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class material {

    const COMPONENT = 'local_targetzenit';
    const FILEAREA = 'lessonboard';

    /**
     * Bitta fayl uchun chegara — AI o'qish chegarasi bilan BIR XIL (10 MB).
     *
     * NEGA TENG: agar yuklash chegarasi kattaroq bo'lsa, o'qituvchi 15 MB
     * PDF yuklab, "AI buni o'qiy olmaydi" degan tushunarsiz xabar olardi.
     * Teng bo'lsa qoida sodda: qabul qilingan rasm yoki PDF ALBATTA o'qiladi.
     * (base64 ~1.37x shishiradi: 10 MB -> 13.7 MB, modelning 15 MB
     * chegarasiga sig'adi.)
     */
    const MAX_BYTES = 10485760;      // 10 MB
    const AI_MAX = 10485760;         // = MAX_BYTES

    /**
     * Ruxsat etilgan turlar.
     *
     * `korinadi` — AI mazmunini O'QIY OLADIMI. Rasm va PDF ni o'qiydi;
     * PowerPoint/Word ni o'qiy olmaydi, lekin ularni ham qabul qilamiz:
     * fayl borligi ham dalil (AI "ko'rgazmali vosita yo'q" deb yozmaydi).
     */
    const TURLAR = [
        'image/png' => ['ext' => 'png', 'korinadi' => true],
        'image/jpeg' => ['ext' => 'jpg', 'korinadi' => true],
        'image/webp' => ['ext' => 'webp', 'korinadi' => true],
        'image/gif' => ['ext' => 'gif', 'korinadi' => true],
        'application/pdf' => ['ext' => 'pdf', 'korinadi' => true],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            => ['ext' => 'pptx', 'korinadi' => false],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            => ['ext' => 'docx', 'korinadi' => false],
        'application/vnd.ms-powerpoint' => ['ext' => 'ppt', 'korinadi' => false],
        'application/msword' => ['ext' => 'doc', 'korinadi' => false],
    ];

    /** Shu darsga biriktirilgan material (bo'lsa). */
    public static function get(int $lessonid, \context $context): ?\stored_file {
        $fs = get_file_storage();
        $fayllar = $fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA,
            $lessonid, 'id', false);
        return $fayllar ? reset($fayllar) : null;
    }

    /** Faylga havola (ruxsati borlarga). */
    public static function url(int $lessonid, \context $context): ?\moodle_url {
        $f = self::get($lessonid, $context);
        if (!$f) {
            return null;
        }
        return \moodle_url::make_pluginfile_url($context->id, self::COMPONENT,
            self::FILEAREA, $lessonid, '/', $f->get_filename());
    }

    /** AI shu faylning MAZMUNINI o'qiy oladimi (tur + hajm)? */
    public static function korinadimi(\stored_file $f): bool {
        return !empty(self::TURLAR[$f->get_mimetype()]['korinadi'])
            && $f->get_filesize() <= self::AI_MAX;
    }

    /** Inson o'qiydigan tur nomi. */
    public static function tur_nomi(\stored_file $f): string {
        return strtoupper(self::TURLAR[$f->get_mimetype()]['ext'] ?? 'fayl');
    }

    /**
     * Yuklangan faylni saqlash. BITTA fayl: eskisi almashtiriladi.
     *
     * Moodle formasi ishlatilmaydi (dars kokpiti qo'lda yozilgan sahifa),
     * shuning uchun tekshiruvlar SHU YERDA qo'lda bajariladi: hajm, tur
     * (fayl MAZMUNIDAN aniqlanadi, nomiga ishonmaymiz) va antivirus.
     *
     * @return string|null xato matni; muvaffaqiyatli bo'lsa null
     */
    public static function save_upload(int $lessonid, \context $context, string $maydon): ?string {
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

        // TURNI FAYL MAZMUNIDAN aniqlaymiz — kengaytmaga ishonib bo'lmaydi.
        $mime = \file_storage::mimetype($yuk['tmp_name'], $yuk['name']);
        if (!isset(self::TURLAR[$mime])) {
            return get_string('mt_err_type', 'local_targetzenit',
                implode(', ', array_map(function ($t) {
                    return strtoupper($t['ext']);
                }, self::TURLAR)));
        }

        // Antivirus (Moodle sozlamasida yoqilgan bo'lsa ishlaydi)
        if (class_exists('\core\antivirus\manager')) {
            try {
                \core\antivirus\manager::scan_file($yuk['tmp_name'], $yuk['name'], true);
            } catch (\Throwable $e) {
                return get_string('mt_err_virus', 'local_targetzenit');
            }
        }

        $nom = clean_param($yuk['name'], PARAM_FILE);
        if ($nom === '') {
            $nom = 'material.' . self::TURLAR[$mime]['ext'];
        }

        $fs = get_file_storage();

        // XAVFSIZ TARTIB: avval YANGI faylni vaqtinchalik nom bilan yaratamiz,
        // keyin eskilarini o'chiramiz, so'ng nomini joyiga qo'yamiz. Teskarisi
        // bo'lsa, yaratish xato berganda o'qituvchi eski faylidan ham ayrilardi.
        $vaqtinchalik = 'yangi-' . $nom;
        $yangi = $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $lessonid,
            'filepath' => '/',
            'filename' => $vaqtinchalik,
        ], $yuk['tmp_name']);

        foreach ($fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA,
                $lessonid, 'id', false) as $eski) {
            if ($eski->get_filename() !== $vaqtinchalik) {
                $eski->delete();
            }
        }
        $yangi->rename('/', $nom);

        return null;
    }

    /** Materialni o'chirish. */
    public static function delete(int $lessonid, \context $context): void {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, self::COMPONENT, self::FILEAREA, $lessonid);
    }
}
