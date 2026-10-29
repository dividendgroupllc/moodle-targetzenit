<?php
// Frappe -> Moodle: baholash normalarini qabul qilish.
//
// Yo'nalish faqat bitta: Frappe yuboradi, Moodle saqlaydi va KO'RSATADI.
// Moodle bu qiymatlarni hech qachon hisoblamaydi va o'zgartirmaydi —
// `_payroll` bilan bir xil tamoyil (Frappe yagona manba).
//
// Norma nima: "darslarning necha foizida baho bo'lishi kutiladi" kabi
// qiymatlar. Zavuch baholash sozlamalari allaqachon Frappe tomonda
// yuritiladi; Moodle'da ikkinchi nusxa yaratish ikki manba muammosini
// keltirardi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class push_norms extends external_api {

    /** Ruxsat etilgan qamrovlar. */
    const SCOPES = ['global', 'subject', 'group', 'teacher'];

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'academicyear' => new external_value(PARAM_TEXT, "O'quv yili, masalan 2026-2027"),
            'norms' => new external_multiple_structure(
                new external_single_structure([
                    'scope' => new external_value(PARAM_ALPHA,
                        'global | subject | group | teacher', VALUE_DEFAULT, 'global'),
                    'scopekey' => new external_value(PARAM_TEXT,
                        "Fan/guruh/xodim kaliti; scope=global da bo'sh", VALUE_DEFAULT, ''),
                    'code' => new external_value(PARAM_ALPHANUMEXT,
                        'Norma kodi, masalan baho_ulushi'),
                    'value' => new external_value(PARAM_FLOAT, 'Qiymat (foizda yoki soatda)'),
                ]),
                'Normalar'
            ),
            'mode' => new external_value(PARAM_ALPHA,
                "replace = shu o'quv yilini o'chirib yangidan yozish, append = ustiga qo'shish",
                VALUE_DEFAULT, 'replace'),
        ]);
    }

    public static function execute(string $academicyear, array $norms,
                                   string $mode = 'replace'): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'academicyear' => $academicyear,
            'norms' => $norms,
            'mode' => $mode,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $year = $params['academicyear'];
        $now = time();

        $transaction = $DB->start_delegated_transaction();

        if ($params['mode'] === 'replace') {
            $DB->delete_records('local_targetzenit_norm', ['academicyear' => $year]);
        }

        $saqlandi = 0;
        $otkazildi = 0;

        foreach ($params['norms'] as $n) {
            $scope = in_array($n['scope'], self::SCOPES, true) ? $n['scope'] : 'global';
            $scopekey = $scope === 'global' ? '' : (string) $n['scopekey'];
            $code = (string) $n['code'];

            if ($code === '' || ($scope !== 'global' && $scopekey === '')) {
                // Qamrovi ko'rsatilmagan norma jim ishlatilsa, u butun maktabga
                // tegishli bo'lib qolardi — shuning uchun o'tkazib yuboramiz.
                $otkazildi++;
                continue;
            }

            $qator = (object) [
                'academicyear' => $year,
                'scope' => $scope,
                'scopekey' => \core_text::substr($scopekey, 0, 255),
                'code' => \core_text::substr($code, 0, 40),
                'value' => (float) $n['value'],
                'timemodified' => $now,
            ];

            // `append` rejimida bir xil kalit qayta kelsa — yangilanadi.
            $mavjud = $DB->get_record('local_targetzenit_norm', [
                'academicyear' => $qator->academicyear,
                'scope' => $qator->scope,
                'scopekey' => $qator->scopekey,
                'code' => $qator->code,
            ], 'id');

            if ($mavjud) {
                $qator->id = $mavjud->id;
                $DB->update_record('local_targetzenit_norm', $qator);
            } else {
                $DB->insert_record('local_targetzenit_norm', $qator);
            }
            $saqlandi++;
        }

        $transaction->allow_commit();
        \local_targetzenit\norm::kesh_tozala();

        return [
            'saqlandi' => $saqlandi,
            'otkazildi' => $otkazildi,
            'academicyear' => $year,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'saqlandi' => new external_value(PARAM_INT, 'Saqlangan normalar'),
            'otkazildi' => new external_value(PARAM_INT, "Qamrovi noto'g'ri bo'lgani uchun o'tkazilgan"),
            'academicyear' => new external_value(PARAM_TEXT, "O'quv yili"),
        ]);
    }
}
