<?php
// Frappe -> Moodle: dars jadvali va o'quv kalendarini qabul qilish.
//
// Yo'nalish faqat bitta: Frappe yuboradi, Moodle qabul qiladi va saqlaydi.
// Har push butun o'quv yilini ALMASHTIRADI (o'chirib, yangidan yozadi) —
// chunki Frappe yagona manba va qisman yangilash chalkashlik keltiradi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class push_timetable extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'academicyear' => new external_value(PARAM_TEXT, "O'quv yili, masalan 2026-2027"),
            'lessons' => new external_multiple_structure(
                new external_single_structure([
                    'groupkey' => new external_value(PARAM_TEXT, 'Frappe Student Group docname'),
                    'groupname' => new external_value(PARAM_TEXT, 'Guruh ko\'rinadigan nomi'),
                    'subjectkey' => new external_value(PARAM_TEXT, 'Frappe Course docname'),
                    'subjectname' => new external_value(PARAM_TEXT, 'Fan nomi'),
                    'teacherkey' => new external_value(PARAM_TEXT, 'Employee ID yoki INSTR-<ism>', VALUE_DEFAULT, ''),
                    'teachername' => new external_value(PARAM_TEXT, 'O\'qituvchi F.I.Sh.', VALUE_DEFAULT, ''),
                    'dayno' => new external_value(PARAM_INT, '1=Dushanba ... 5=Juma'),
                    'dayname' => new external_value(PARAM_TEXT, 'Kun nomi', VALUE_DEFAULT, ''),
                    'lessonno' => new external_value(PARAM_INT, 'Dars raqami'),
                    'starttime' => new external_value(PARAM_TEXT, 'HH:MM', VALUE_DEFAULT, ''),
                    'endtime' => new external_value(PARAM_TEXT, 'HH:MM', VALUE_DEFAULT, ''),
                    'room' => new external_value(PARAM_TEXT, 'Xona', VALUE_DEFAULT, ''),
                    'courseidnumber' => new external_value(PARAM_TEXT, 'Moodle kursining idnumber i', VALUE_DEFAULT, ''),
                ]),
                'Dars jadvali qatorlari'
            ),
            'mode' => new external_value(
                PARAM_ALPHA,
                "replace = shu o'quv yilini o'chirib yangidan yozish, append = ustiga qo'shish",
                VALUE_DEFAULT,
                'replace'
            ),
            'calendar' => new external_multiple_structure(
                new external_single_structure([
                    'entrytype' => new external_value(PARAM_ALPHA, 'term yoki holiday'),
                    'name' => new external_value(PARAM_TEXT, 'Nomi'),
                    'startdate' => new external_value(PARAM_TEXT, 'YYYY-MM-DD'),
                    'enddate' => new external_value(PARAM_TEXT, 'YYYY-MM-DD'),
                ]),
                "Chorak oraliqlari va bayramlar",
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    public static function execute(string $academicyear, array $lessons,
                                   string $mode = 'replace', array $calendar = []): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'academicyear' => $academicyear,
            'lessons' => $lessons,
            'mode' => $mode,
            'calendar' => $calendar,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $year = $params['academicyear'];
        $now = time();

        $transaction = $DB->start_delegated_transaction();

        // --- Dars jadvali
        // PHP'ning max_input_vars chegarasi tufayli Frappe jadvalni bo'lib yuboradi:
        // birinchi bo'lak 'replace' (eskisini o'chiradi), qolganlari 'append'.
        if ($params['mode'] === 'replace') {
            $DB->delete_records('local_targetzenit_timetable', ['academicyear' => $year]);
        }
        $rows = [];
        foreach ($params['lessons'] as $l) {
            $rows[] = (object) [
                'academicyear' => $year,
                'groupkey' => $l['groupkey'],
                'groupname' => $l['groupname'],
                'subjectkey' => $l['subjectkey'],
                'subjectname' => $l['subjectname'],
                'teacherkey' => $l['teacherkey'],
                'teachername' => $l['teachername'],
                'dayno' => $l['dayno'],
                'dayname' => $l['dayname'],
                'lessonno' => $l['lessonno'],
                'starttime' => $l['starttime'],
                'endtime' => $l['endtime'],
                'room' => $l['room'],
                'courseidnumber' => $l['courseidnumber'],
                'timemodified' => $now,
            ];
        }
        if ($rows) {
            $DB->insert_records('local_targetzenit_timetable', $rows);
        }

        // --- Kalendar: kelgan bo'lsa almashtiramiz, bo'sh kelsa tegmaymiz
        $calcount = 0;
        if (!empty($params['calendar'])) {
            $DB->delete_records('local_targetzenit_calendar', ['academicyear' => $year]);
            $crows = [];
            foreach ($params['calendar'] as $c) {
                $crows[] = (object) [
                    'academicyear' => $year,
                    'entrytype' => $c['entrytype'],
                    'name' => $c['name'],
                    'startdate' => $c['startdate'],
                    'enddate' => $c['enddate'],
                    'timemodified' => $now,
                ];
            }
            if ($crows) {
                $DB->insert_records('local_targetzenit_calendar', $crows);
                $calcount = count($crows);
            }
        }

        $transaction->allow_commit();

        return [
            'lessons' => count($rows),
            'calendar' => $calcount,
            'academicyear' => $year,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lessons' => new external_value(PARAM_INT, 'Saqlangan dars qatorlari'),
            'calendar' => new external_value(PARAM_INT, 'Saqlangan kalendar yozuvlari'),
            'academicyear' => new external_value(PARAM_TEXT, "O'quv yili"),
        ]);
    }
}
