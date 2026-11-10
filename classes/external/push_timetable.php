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
                    'groupname' => new external_value(PARAM_RAW, 'Guruh ko\'rinadigan nomi'),
                    'subjectkey' => new external_value(PARAM_TEXT, 'Frappe Course docname'),
                    'subjectname' => new external_value(PARAM_RAW, 'Fan nomi'),
                    'teacherkey' => new external_value(PARAM_TEXT, 'Employee ID yoki INSTR-<ism>', VALUE_DEFAULT, ''),
                    'teachername' => new external_value(PARAM_RAW, 'O\'qituvchi F.I.Sh.', VALUE_DEFAULT, ''),
                    'dayno' => new external_value(PARAM_INT, '1=Dushanba ... 5=Juma'),
                    'dayname' => new external_value(PARAM_RAW, 'Kun nomi', VALUE_DEFAULT, ''),
                    'lessonno' => new external_value(PARAM_INT, 'Dars raqami'),
                    'starttime' => new external_value(PARAM_TEXT, 'HH:MM', VALUE_DEFAULT, ''),
                    'endtime' => new external_value(PARAM_TEXT, 'HH:MM', VALUE_DEFAULT, ''),
                    'room' => new external_value(PARAM_RAW, 'Xona', VALUE_DEFAULT, ''),
                    'courseidnumber' => new external_value(PARAM_TEXT, 'Moodle kursining idnumber i', VALUE_DEFAULT, ''),
                    // Dars qaysi SINFLARGA tegishli: |1 A(full)|1 B(hybrid)|
                    //
                    // NEGA ALOHIDA: `groupkey` qo'shma/daraja darsida BLOK
                    // guruhi bo'ladi, sinf emas. Sinf jadvalini faqat shu
                    // ustun orqali to'liq yig'ib bo'ladi (`for_group`).
                    //
                    // KALIT maydon, shuning uchun `PARAM_TEXT`: moslik aniq
                    // bo'lishi shart va jimgina o'zgartirilishi mumkin emas —
                    // nomuvofiqlik bo'lsa sinxron baland ovozda yiqilsin.
                    'memberclasses' => new external_value(PARAM_TEXT,
                        "Dars tegishli sinflar, |sinf|sinf| ko'rinishida", VALUE_DEFAULT, ''),
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
                    'name' => new external_value(PARAM_RAW, 'Nomi'),
                    'startdate' => new external_value(PARAM_TEXT, 'YYYY-MM-DD'),
                    'enddate' => new external_value(PARAM_TEXT, 'YYYY-MM-DD'),
                ]),
                "Chorak oraliqlari va bayramlar",
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Ko'rsatish uchun matnni tozalaydi.
     *
     * NEGA KERAK: `PARAM_TEXT` ni web-servis QAT'IY tekshiradi —
     * `if ((string) $param !== (string) $cleaned) throw invalid_parameter_exception`.
     * Ya'ni manbadagi bitta HTML tegi (masalan Frappe'ning `Text Editor`
     * maydonidan kelgan `<p>...</p>`) BUTUN jadval sinxronini yiqitadi.
     * 2026-11-03 da bayram nomi tufayli aynan shunday bo'lgan.
     *
     * Shuning uchun KO'RSATISH maydonlari `PARAM_RAW` bilan qabul qilinadi va
     * shu yerda tozalanadi. KALITLAR (`groupkey`, `teacherkey`, ...) esa
     * `PARAM_TEXT` bo'lib qoladi: ular aniq mos kelishi shart va jimgina
     * o'zgartirilishi mumkin emas.
     */
    protected static function nom(?string $matn, int $uz = 255): string {
        return \core_text::substr(trim(clean_param((string) $matn, PARAM_TEXT)), 0, $uz);
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
                'groupname' => self::nom($l['groupname']),
                'subjectkey' => $l['subjectkey'],
                'subjectname' => self::nom($l['subjectname']),
                'teacherkey' => $l['teacherkey'],
                'teachername' => self::nom($l['teachername']),
                'dayno' => $l['dayno'],
                'dayname' => self::nom($l['dayname'], 50),
                'lessonno' => $l['lessonno'],
                'starttime' => $l['starttime'],
                'endtime' => $l['endtime'],
                'room' => self::nom($l['room'], 100),
                'courseidnumber' => $l['courseidnumber'],
                'memberclasses' => \core_text::substr($l['memberclasses'], 0, 1000),
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
                    'name' => self::nom($c['name']),
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

        // Reja (slot) jadvalga tayanadi — push tugashi bilan uni qayta
        // quramiz. Fonda bajariladi: jadval 900+ qatordan iborat va bu
        // ishni web-so'rov ichida qilsak, Frappe tomonda timeout bo'ladi.
        \local_targetzenit\task\slot_rebuild_now::queue($year);

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
