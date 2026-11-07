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

        // TEZLIK: ismlar va izoh sonlari GURUHLAB o'qiladi. Avval har qator
        // uchun alohida so'rov ketardi (ism x2, idnumber, count) — 200
        // qatorda 800 so'rov, va bu har 15 daqiqada takrorlanardi.
        $ismlar = \local_targetzenit\murojaat::ismlar(array_merge(
            array_map(fn($r) => (int) $r->masuluserid, $rows),
            array_map(fn($r) => (int) $r->qarorkim, $rows)
        ));
        $izohsoni = self::izoh_sonlari(array_map(fn($r) => (int) $r->id, $rows));

        $nom = function (int $userid) use ($ismlar): string {
            $u = $ismlar[$userid] ?? null;
            return $u ? fullname($u) : '—';
        };

        $out = [];
        $oxirgi = (int) $params['since'];
        foreach ($rows as $r) {
            $oxirgi = max($oxirgi, (int) $r->timemodified);
            $masul = $ismlar[(int) $r->masuluserid] ?? null;
            $out[] = [
                'frappename' => (string) $r->frappename,
                'holat' => (string) $r->holat,
                'yonalish' => (string) $r->yonalish,
                'masul' => (string) ($masul->idnumber ?? ''),
                'masulism' => $nom((int) $r->masuluserid),
                'korildivaqti' => (int) $r->korildivaqti,
                'qarorturi' => (string) ($r->qarorturi ?? ''),
                'qaror' => (string) ($r->qaror ?? ''),
                'qarorism' => $nom((int) $r->qarorkim),
                'qarorvaqti' => (int) $r->qarorvaqti,
                'otaonakordi' => (int) $r->otaonakordi,
                'yopilganvaqti' => (int) $r->yopilganvaqti,
                'izohsoni' => $izohsoni[(int) $r->id] ?? 0,
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

    /** murojaatid -> izohlar soni. Bitta so'rov. */
    protected static function izoh_sonlari(array $ids): array {
        global $DB;

        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return [];
        }
        [$sql, $p] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'm');
        $out = [];
        $rs = $DB->get_recordset_sql(
            "SELECT murojaatid, COUNT(*) soni
               FROM {local_targetzenit_murojaat_izoh}
              WHERE murojaatid $sql
           GROUP BY murojaatid", $p);
        foreach ($rs as $r) {
            $out[(int) $r->murojaatid] = (int) $r->soni;
        }
        $rs->close();
        return $out;
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
