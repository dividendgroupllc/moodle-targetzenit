<?php
// Dars audio yozuvi.
//
// OQIM (Frappe'dagi `dars_audio.py` dan ko'chirilgan, u yerda sinab ko'rilgan):
//   brauzer MediaRecorder -> har 15 soniyada bo'lak -> serverga yuklanadi
//   (temp/local_targetzenit/audio/<lessonid>/000001.part) -> "To'xtatish"
//   bosilganda bo'laklar tartib bilan BAYT-BA-BAYT birlashtiriladi
//   (opus/webm oqimi uchun bu to'g'ri, ffmpeg kerak emas) -> Moodle fayl
//   omboriga joylanadi va darsga biriktiriladi.
//
// NEGA BO'LAK-BO'LAK: brauzer yopilsa, telefon uxlasa yoki internet uzilsa ham
// yozilgan qism serverda qoladi. Moodle'ning o'z `tiny_recordrtc` vositasi esa
// butun yozuvni brauzer xotirasida to'playdi — 45 daqiqalik dars uchun yaramaydi.
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

    /** Shu darsning audio yozuvi (bo'lsa). */
    public static function get(int $lessonid): ?\stdClass {
        global $DB;
        $rec = $DB->get_record('local_targetzenit_audio', ['lessonid' => $lessonid]);
        return $rec ?: null;
    }

    /** Yozishni boshlash. Eski tugallanmagan bo'laklar tozalanadi. */
    public static function start(int $lessonid, string $mime, int $teacherid): \stdClass {
        global $DB;

        $dir = self::tempdir($lessonid);
        foreach (glob($dir . '/*.part') ?: [] as $f) {
            @unlink($f);
        }

        $rec = self::get($lessonid);
        if ($rec) {
            $rec->status = 'recording';
            $rec->mimetype = $mime;
            $rec->teacherid = $teacherid;
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);
        } else {
            $rec = (object) [
                'lessonid' => $lessonid,
                'status' => 'recording',
                'mimetype' => $mime,
                'filesize' => 0,
                'duration' => 0,
                'teacherid' => $teacherid,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $rec->id = $DB->insert_record('local_targetzenit_audio', $rec);
        }
        return $rec;
    }

    /** Bitta bo'lakni saqlash. Qaytaradi: shu paytgacha yig'ilgan hajm. */
    public static function chunk(int $lessonid, int $seq, string $data): int {
        if (strlen($data) > self::MAX_CHUNK) {
            throw new \moodle_exception('Bo\'lak juda katta');
        }
        $dir = self::tempdir($lessonid);
        $hajm = self::staged_size($lessonid) + strlen($data);
        if ($hajm > self::maxbytes()) {
            throw new \moodle_exception('Yozuv hajmi chegaradan oshdi');
        }
        file_put_contents(sprintf('%s/%06d.part', $dir, $seq), $data);
        return $hajm;
    }

    /** Hozirgacha yuklangan bo'laklar hajmi. */
    public static function staged_size(int $lessonid): int {
        $jami = 0;
        foreach (glob(self::tempdir($lessonid) . '/*.part') ?: [] as $f) {
            $jami += filesize($f);
        }
        return $jami;
    }

    /**
     * Yozishni tugatish: bo'laklarni birlashtirib Moodle fayl omboriga joylaydi.
     * Bo'laklar tartib raqami bo'yicha ketma-ket qo'shiladi.
     */
    public static function finish(int $lessonid, \context $context, int $duration = 0): \stdClass {
        global $DB;

        $rec = self::get($lessonid);
        if (!$rec) {
            throw new \moodle_exception('Yozuv sessiyasi topilmadi');
        }

        $dir = self::tempdir($lessonid);
        $bolaklar = glob($dir . '/*.part') ?: [];
        sort($bolaklar, SORT_STRING);

        if (!$bolaklar) {
            $rec->status = 'failed';
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);
            throw new \moodle_exception('Birorta ham bo\'lak kelmadi');
        }

        $ext = self::ext($rec->mimetype);
        $birlashgan = $dir . '/birlashgan.' . $ext;
        $out = fopen($birlashgan, 'wb');
        foreach ($bolaklar as $b) {
            $in = fopen($b, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        // Eski faylni almashtiramiz (qayta yozilgan bo'lsa)
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, self::COMPONENT, self::FILEAREA, $lessonid);

        $filename = 'dars-' . $lessonid . '-' . date('Ymd-His') . '.' . $ext;
        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $lessonid,
            'filepath' => '/',
            'filename' => $filename,
        ], $birlashgan);

        $rec->status = 'ready';
        $rec->filename = $filename;
        $rec->filesize = filesize($birlashgan);
        $rec->duration = $duration;
        $rec->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $rec);

        // Vaqtinchalik fayllarni tozalaymiz
        foreach ($bolaklar as $b) {
            @unlink($b);
        }
        @unlink($birlashgan);
        @rmdir($dir);

        // AI tahlilni DARHOL navbatga qo'yamiz — 15 daqiqalik jadvalni kutmaydi.
        // Moodle croni har daqiqada ishlagani uchun tahlil ~1 daqiqada boshlanadi.
        if (ai::enabled()) {
            task\analyse_one::queue($lessonid);
        }

        return $rec;
    }

    /** Yozishni bekor qilish (bo'laklar o'chadi). */
    public static function cancel(int $lessonid): void {
        global $DB;
        $dir = self::tempdir($lessonid);
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
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
     * @param int $jimlik_daqiqa oxirgi bo'lakdan keyin shuncha daqiqa o'tgan bo'lsa
     */
    public static function finalize_stale(int $jimlik_daqiqa = 20): array {
        global $DB;

        $ochiq = $DB->get_records('local_targetzenit_audio', ['status' => 'recording']);
        $yakunlandi = 0;
        $tashlandi = 0;

        foreach ($ochiq as $a) {
            $dir = self::tempdir((int) $a->lessonid);
            $bolaklar = glob($dir . '/*.part') ?: [];

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
                $oxirgi = max($oxirgi, (int) filemtime($b));
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
                // Davomiylik: bo'laklar soni x 15 soniya (taxminiy)
                self::finish((int) $a->lessonid, $ctx, count($bolaklar) * 15);
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
