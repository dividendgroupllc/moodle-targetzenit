<?php
// Mobil ilovadan dars mavzusi va uy vazifasini saqlash.
//
// TAMOYIL: saqlash mantig'i bu yerda yozilmaydi — `lesson::save_lesson()`
// chaqiriladi, veb kokpiti ham o'shani ishlatadi. Shu sababli uy vazifa
// Moodle'da `mod_assign` topshirig'i bo'lib ochiladi va dars gradebook'da
// ustun oladi: ikki kanal bir xil natija beradi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_targetzenit\lesson;

defined('MOODLE_INTERNAL') || die();

class mobile_save_lesson extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'lessonid' => new external_value(PARAM_INT, 'local_targetzenit_lesson.id'),
            'topic' => new external_value(PARAM_TEXT, 'Dars mavzusi', VALUE_DEFAULT, ''),
            'homework' => new external_value(PARAM_TEXT, 'Uy vazifa', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $lessonid, string $topic = '',
                                   string $homework = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'lessonid' => $lessonid,
            'topic' => $topic,
            'homework' => $homework,
        ]);

        $dars = $DB->get_record('local_targetzenit_lesson',
            ['id' => $params['lessonid']], '*', MUST_EXIST);

        $context = \context_course::instance((int) $dars->courseid);
        self::validate_context($context);
        require_capability('local/targetzenit:teach', $context);

        lesson::save_lesson((int) $dars->id, $params['topic'], $params['homework'],
            (int) $USER->id);

        return ['status' => true,
                'xabar' => get_string('app_saved', 'local_targetzenit')];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Saqlandimi'),
            'xabar' => new external_value(PARAM_TEXT, 'Qisqa xabar'),
        ]);
    }
}
