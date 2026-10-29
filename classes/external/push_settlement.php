<?php
// Frappe -> Moodle: xodim bilan hisob-kitob (oy kesimida).
//
// TAMOYIL: sonlarni FRAPPE hisoblaydi. Bu yerda hisob-kitob yo'q — Moodle
// investor panelidagi bilan AYNAN BIR XIL qiymatni ko'rsatadi, chunki manba
// bitta (`investor_dashboard.personal_months`).
//
// TOZALASH: har yurish `runid` belgisi bilan keladi. Yozilgan qatorga o'sha
// belgi qo'yiladi, oxirida esa `keep` dagi xodimlarning SHU YURISHDA
// yangilanmagan qatorlari o'chiriladi. Vaqt solishtirilmaydi — Frappe va
// Moodle soatlari farq qilishi mumkin, belgi esa shunchaki tenglik uchun.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class push_settlement extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'runid' => new external_value(PARAM_INT,
                "Yurish belgisi. Bitta sinxronning hamma bo'lagida bir xil "
                . "bo'lishi shart — ortiqcha qatorlar shu bo'yicha topiladi."),
            'keep' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Employee.name'),
                "Tozalanadigan xodimlar. Bo'sh bo'lsa tozalash qilinmaydi. "
                . "OXIRGI bo'lakda yuboriladi — aks holda hali kelmagan "
                . "bo'lakning qatorlari o'chib ketardi.",
                VALUE_DEFAULT, []
            ),
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'useridnumber' => new external_value(PARAM_TEXT, 'Frappe Employee.name'),
                    'oy' => new external_value(PARAM_TEXT, 'YYYY-MM'),
                    'valyuta' => new external_value(PARAM_TEXT, 'Valyuta kodi', VALUE_DEFAULT, 'UZS'),
                    'oylik' => new external_value(PARAM_FLOAT, 'Shu oydagi ish haqi', VALUE_DEFAULT, 0),
                    'nach' => new external_value(PARAM_FLOAT, 'Nachisleniya', VALUE_DEFAULT, 0),
                    'paid' => new external_value(PARAM_FLOAT, "To'langan", VALUE_DEFAULT, 0),
                    'advance' => new external_value(PARAM_FLOAT, "Bog'lanmagan to'lov", VALUE_DEFAULT, 0),
                    'debt' => new external_value(PARAM_FLOAT, 'Qoldiq', VALUE_DEFAULT, 0),
                ]),
                "Hisob-kitob qatorlari. Bo'sh bo'lishi mumkin.",
                VALUE_DEFAULT, []
            ),
        ]);
    }

    public static function execute(int $runid, array $keep = [], array $rows = []): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(),
            ['runid' => $runid, 'keep' => $keep, 'rows' => $rows]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $runid = $params['runid'];
        $yangi = 0;
        $yangilandi = 0;

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['rows'] as $r) {
            $ref = trim($r['useridnumber']);
            $oy = trim($r['oy']);
            if ($ref === '' || !preg_match('/^\d{4}-\d{2}$/', $oy)) {
                continue;
            }
            $cur = trim($r['valyuta']) ?: 'UZS';

            $yozuv = (object) [
                'useridnumber' => $ref,
                'oy' => $oy,
                'valyuta' => $cur,
                'oylik' => $r['oylik'],
                'nach' => $r['nach'],
                'paid' => $r['paid'],
                'advance' => $r['advance'],
                'debt' => $r['debt'],
                'timemodified' => $runid,
            ];

            $mavjud = $DB->get_record('local_targetzenit_settle',
                ['useridnumber' => $ref, 'oy' => $oy, 'valyuta' => $cur], 'id');
            if ($mavjud) {
                $yozuv->id = $mavjud->id;
                $DB->update_record('local_targetzenit_settle', $yozuv);
                $yangilandi++;
            } else {
                $DB->insert_record('local_targetzenit_settle', $yozuv);
                $yangi++;
            }
        }

        // Shu xodimlarning bu yurishda KELMAGAN qatorlari — eskirgan, o'chadi.
        $ochirildi = 0;
        $qoldiriladigan = array_values(array_filter(array_map('trim', $params['keep'])));
        if ($qoldiriladigan) {
            [$insql, $inparams] = $DB->get_in_or_equal($qoldiriladigan, SQL_PARAMS_NAMED, 'ref');
            $shart = "useridnumber $insql AND timemodified <> :run";
            $shartparams = array_merge(['run' => $runid], $inparams);
            $ochirildi = $DB->count_records_select('local_targetzenit_settle',
                $shart, $shartparams);
            $DB->delete_records_select('local_targetzenit_settle', $shart, $shartparams);
        }

        $transaction->allow_commit();

        return [
            'yangi' => $yangi,
            'yangilandi' => $yangilandi,
            'ochirildi' => $ochirildi,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'yangi' => new external_value(PARAM_INT, "Yangi qatorlar"),
            'yangilandi' => new external_value(PARAM_INT, 'Yangilangan qatorlar'),
            'ochirildi' => new external_value(PARAM_INT, "O'chirilgan eskirgan qatorlar"),
        ]);
    }
}
