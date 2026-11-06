<?php
// Moodle -> Frappe: rahbar qarorlarini BERISH (Frappe so'rab oladi).
//
// NEGA SHUNDAY: Moodle hech qachon Frappeni chaqirmaydi — token faqat Frappe
// tomonda. Shuning uchun javoblarni Frappe o'zi so'rab oladi va nusxaga
// yozadi: investor paneli, sotuv nazorati va qarzdorlik paneli uchun.
// MUROJAAT_ARXITEKTURA.md 2-bo'lim.
//
// `since` — oxirgi so'rovdan keyin O'ZGARGANLAR (timemodified bo'yicha).
// Frappe shu qiymatni o'zida saqlaydi, ya'ni har safar butun jadval
// tashilmaydi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class get_murojaat extends external_api {

    /** Bitta so'rovda nechta qator (Frappe sahifalab oladi). */
    const MAX = 500;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'since' => new external_value(PARAM_INT,
                'Shu vaqtdan keyin o\'zgarganlar (unix). 0 = hammasi', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Qatorlar soni', VALUE_DEFAULT, 200),
        ]);
    }

    public static function execute(int $since = 0, int $limit = 200): array {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $params = self::validate_parameters(self::execute_parameters(),
            ['since' => $since, 'limit' => $limit]);
        $limit = min(max((int) $params['limit'], 1), self::MAX);

        $rows = $DB->get_records_select('local_targetzenit_murojaat',
            'timemodified > :since', ['since' => (int) $params['since']],
            'timemodified ASC', '*', 0, $limit);

        $out = [];
        $oxirgi = (int) $params['since'];
        foreach ($rows as $r) {
            $oxirgi = max($oxirgi, (int) $r->timemodified);
            $out[] = [
                'frappename' => (string) $r->frappename,
                'holat' => (string) $r->holat,
                'yonalish' => (string) $r->yonalish,
                'masul' => self::idnumber_by_userid((int) $r->masuluserid),
                'masulism' => \local_targetzenit\murojaat::ism((int) $r->masuluserid),
                'korildivaqti' => (int) $r->korildivaqti,
                'qarorturi' => (string) ($r->qarorturi ?? ''),
                'qaror' => (string) ($r->qaror ?? ''),
                'qarorism' => \local_targetzenit\murojaat::ism((int) $r->qarorkim),
                'qarorvaqti' => (int) $r->qarorvaqti,
                'otaonakordi' => (int) $r->otaonakordi,
                'yopilganvaqti' => (int) $r->yopilganvaqti,
                'izohsoni' => (int) $DB->count_records('local_targetzenit_murojaat_izoh',
                    ['murojaatid' => $r->id]),
                'timemodified' => (int) $r->timemodified,
            ];
        }

        return [
            'rows' => $out,
            'since' => $oxirgi,
            // Yana qolgan bo'lsa Frappe darhol keyingi sahifani so'raydi
            'yana' => count($out) >= $limit ? 1 : 0,
        ];
    }

    /** Moodle user.id -> idnumber (Frappe Employee.name). */
    protected static function idnumber_by_userid(int $userid): string {
        global $DB;
        if (!$userid) {
            return '';
        }
        return (string) ($DB->get_field('user', 'idnumber', ['id' => $userid]) ?: '');
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'frappename' => new external_value(PARAM_TEXT, 'Frappe Murojaat.name'),
                    'holat' => new external_value(PARAM_TEXT, 'Holat'),
                    'yonalish' => new external_value(PARAM_TEXT, 'CEO yoki Direktor'),
                    'masul' => new external_value(PARAM_TEXT, "Mas'ul rahbar idnumber"),
                    'masulism' => new external_value(PARAM_TEXT, "Mas'ul ismi"),
                    'korildivaqti' => new external_value(PARAM_INT, "Ko'rilgan vaqt"),
                    'qarorturi' => new external_value(PARAM_TEXT, 'Qaror turi'),
                    'qaror' => new external_value(PARAM_RAW, 'Qanday hal qilindi'),
                    'qarorism' => new external_value(PARAM_TEXT, 'Qaror kim tomonidan'),
                    'qarorvaqti' => new external_value(PARAM_INT, 'Qaror vaqti'),
                    'otaonakordi' => new external_value(PARAM_INT, "Ota-ona ko'rgan vaqti"),
                    'yopilganvaqti' => new external_value(PARAM_INT, 'Yopilgan vaqt'),
                    'izohsoni' => new external_value(PARAM_INT, 'Izohlar soni'),
                    'timemodified' => new external_value(PARAM_INT, "O'zgargan vaqt"),
                ]),
                'Qatorlar'
            ),
            'since' => new external_value(PARAM_INT, 'Keyingi so\'rov uchun kursor'),
            'yana' => new external_value(PARAM_INT, 'Yana qator qolganmi (1/0)'),
        ]);
    }
}
