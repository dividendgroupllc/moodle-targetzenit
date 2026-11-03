<?php
// Mobil ilovadan yo'qlama va baho saqlash.
//
// NEGA ALOHIDA FUNKSIYA: panellarning o'zi `tool_mobile_get_content` orqali
// keladi va ro'yxatga qo'shish talab qilmaydi. Ammo bu YOZISH amali —
// ilova uni to'g'ridan-to'g'ri chaqiradi, shuning uchun funksiya
// `db/services.php` da e'lon qilinadi va `moodle_mobile_app` servisiga
// qo'shiladi.
//
// TAMOYIL: saqlash mantig'i BU YERDA yozilmaydi. `lesson::save_record()`
// chaqiriladi — veb kokpiti ham o'shani ishlatadi. Shu sababli baho
// Moodle gradebook'iga ham o'sha yo'l bilan tushadi va ikki kanal ikki xil
// natija bermaydi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_targetzenit\gradebook;
use local_targetzenit\lesson;

defined('MOODLE_INTERNAL') || die();

class mobile_save_record extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'lessonid' => new external_value(PARAM_INT, 'local_targetzenit_lesson.id'),
            'userid' => new external_value(PARAM_INT, "O'quvchining Moodle user id"),
            'attendance' => new external_value(PARAM_ALPHA,
                'present | absent | late | excused', VALUE_DEFAULT, 'present'),
            // Baho PARAM_RAW_TRIMMED: bo'sh satr "bahoni olib tashla" degani,
            // PARAM_INT esa uni 0 ga aylantirib yuborardi.
            'grade' => new external_value(PARAM_RAW_TRIMMED, 'Baho yoki bo\'sh satr',
                VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $lessonid, int $userid,
                                   string $attendance = 'present',
                                   string $grade = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'lessonid' => $lessonid,
            'userid' => $userid,
            'attendance' => $attendance,
            'grade' => $grade,
        ]);

        $dars = $DB->get_record('local_targetzenit_lesson',
            ['id' => $params['lessonid']], '*', MUST_EXIST);

        // Ruxsat KURS kontekstida tekshiriladi: o'qituvchi faqat o'zi dars
        // o'tadigan kursda yoza oladi. Web-servis to'g'ridan-to'g'ri ham
        // chaqirilishi mumkin, shuning uchun bu tekshiruv majburiy.
        $context = \context_course::instance((int) $dars->courseid);
        self::validate_context($context);
        require_capability('local/targetzenit:teach', $context);

        // O'quvchi shu kursda bormi — boshqa kursning o'quvchisiga baho
        // qo'yib bo'lmasligi kerak.
        if (!is_enrolled($context, $params['userid'], '', true)) {
            throw new \moodle_exception('error', 'local_targetzenit', '',
                'user not enrolled', 'Bu o\'quvchi kursda emas');
        }

        $baho = $params['grade'] === '' ? null : $params['grade'];
        lesson::save_record((int) $dars->id, $params['userid'],
            $params['attendance'], $baho, '');

        // Yakuniy baholarni qayta hisoblash FAQAT baho tegilganda: yo'qlama
        // bosilganda ham chaqirilsa har bosishda qimmat amal bajarilardi.
        if ($params['grade'] !== '') {
            try {
                gradebook::regrade((int) $dars->courseid);
            } catch (\Throwable $e) {
                gradebook::xato_yoz($e, "mobil regrade kurs {$dars->courseid}");
            }
        }

        return ['status' => true, 'xabar' => get_string('app_saved', 'local_targetzenit')];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Saqlandimi'),
            'xabar' => new external_value(PARAM_TEXT, 'Qisqa xabar'),
        ]);
    }
}
