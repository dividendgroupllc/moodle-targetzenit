<?php
// Frappe -> Moodle: oylik tabel ma'lumotini qabul qilish.
//
// TAMOYIL: oylikni FRAPPE hisoblaydi. Moodle hech narsa hisoblamaydi, faqat
// kelgan sonlarni saqlaydi va xodimga ko'rsatadi. Shuning uchun bu yerda
// hisob-kitob yo'q — faqat upsert.
//
// BOG'LANISH: `useridnumber` = Frappe `Employee.name`. Moodle foydalanuvchisi
// shu qiymat `user.idnumber` da bo'lgani bo'yicha topiladi. Ism yoki email
// bo'yicha hech qachon qidirilmaydi — ism o'zgaradi, ID o'zgarmaydi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class push_payroll extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'yil' => new external_value(PARAM_INT, 'Yil'),
            'oy' => new external_value(PARAM_INT, 'Oy (1-12)'),
            'holat' => new external_value(PARAM_TEXT, 'Ochiq yoki Yopiq', VALUE_DEFAULT, 'Ochiq'),
            'valyuta' => new external_value(PARAM_TEXT, 'Valyuta kodi', VALUE_DEFAULT, 'UZS'),
            'keep' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Employee.name'),
                "Shu oyda QOLISHI kerak bo'lgan kalitlar. Bo'sh bo'lsa tozalash "
                . "qilinmaydi. Frappe darslarni bo'lab yuboradi, shuning uchun "
                . "ortiqcha qatorlarni o'chirish OXIRGI chaqiruvda bir marta "
                . "bajariladi — aks holda birinchi bo'lak ikkinchisi tomonidan "
                . "o'chib ketardi.",
                VALUE_DEFAULT,
                []
            ),
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'useridnumber' => new external_value(PARAM_TEXT, 'Frappe Employee.name'),
                    'employeename' => new external_value(PARAM_TEXT, 'Xodim ismi', VALUE_DEFAULT, ''),
                    'lavozim' => new external_value(PARAM_TEXT, 'Lavozim', VALUE_DEFAULT, ''),
                    'tolovturi' => new external_value(PARAM_ALPHA, 'kun yoki soat', VALUE_DEFAULT, 'kun'),
                    'normakun' => new external_value(PARAM_INT, 'Norma ish kuni', VALUE_DEFAULT, 0),
                    'kelgankun' => new external_value(PARAM_INT, 'Kelgan kunlar soni', VALUE_DEFAULT, 0),
                    'ishlagan' => new external_value(PARAM_FLOAT, "Ishlagan kun/soat yig'indisi", VALUE_DEFAULT, 0),
                    'kunliknarx' => new external_value(PARAM_FLOAT, 'Kunlik (soatbayda soatlik) narx', VALUE_DEFAULT, 0),
                    'oylik' => new external_value(PARAM_FLOAT, 'Shartnoma oyligi', VALUE_DEFAULT, 0),
                    'bonus' => new external_value(PARAM_FLOAT, 'Bonus (jarima manfiy)', VALUE_DEFAULT, 0),
                    'nalog' => new external_value(PARAM_FLOAT, 'Nalog', VALUE_DEFAULT, 0),
                    'pitaniya' => new external_value(PARAM_FLOAT, 'Pitaniya', VALUE_DEFAULT, 0),
                    'hisob' => new external_value(PARAM_FLOAT, 'Hisoblangan ish haqi', VALUE_DEFAULT, 0),
                    'jami' => new external_value(PARAM_FLOAT, "Qo'lga tegadigan summa", VALUE_DEFAULT, 0),
                    'kunlarjson' => new external_value(PARAM_RAW, 'Kunlar JSON', VALUE_DEFAULT, ''),
                ]),
                "Xodimlar qatori. Bo'sh bo'lishi mumkin — tozalash qadami "
                . "faqat `keep` bilan keladi. MAJBURIY QILIB BO'LMAYDI: Frappe "
                . "so'rovni forma maydonlari ko'rinishida yuboradi va bo'sh "
                . "massiv hech qanday maydon bermaydi, ya'ni parametr "
                . "so'rovdan butunlay yo'qoladi.",
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    public static function execute(int $yil, int $oy, string $holat = 'Ochiq',
                                   string $valyuta = 'UZS', array $keep = [],
                                   array $rows = []): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'yil' => $yil, 'oy' => $oy, 'holat' => $holat,
            'valyuta' => $valyuta, 'keep' => $keep, 'rows' => $rows,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $yil = $params['yil'];
        $oy = $params['oy'];
        if ($oy < 1 || $oy > 12) {
            throw new \invalid_parameter_exception('oy 1-12 oralig\'ida bo\'lishi kerak');
        }

        $now = time();
        $yangi = 0;
        $yangilandi = 0;


        $transaction = $DB->start_delegated_transaction();

        foreach ($params['rows'] as $r) {
            $ref = trim($r['useridnumber']);
            if ($ref === '') {
                continue;
            }

            $yozuv = (object) [
                'useridnumber' => $ref,
                'employeename' => $r['employeename'],
                'yil' => $yil,
                'oy' => $oy,
                'lavozim' => $r['lavozim'],
                'tolovturi' => $r['tolovturi'] === 'soat' ? 'soat' : 'kun',
                'normakun' => $r['normakun'],
                'kelgankun' => $r['kelgankun'],
                'ishlagan' => $r['ishlagan'],
                'kunliknarx' => $r['kunliknarx'],
                'oylik' => $r['oylik'],
                'bonus' => $r['bonus'],
                'nalog' => $r['nalog'],
                'pitaniya' => $r['pitaniya'],
                'hisob' => $r['hisob'],
                'jami' => $r['jami'],
                'valyuta' => $params['valyuta'],
                'holat' => $params['holat'] === 'Yopiq' ? 'Yopiq' : 'Ochiq',
                'kunlarjson' => $r['kunlarjson'],
                'timemodified' => $now,
            ];

            $mavjud = $DB->get_record('local_targetzenit_payroll',
                ['useridnumber' => $ref, 'yil' => $yil, 'oy' => $oy], 'id');
            try {
                if ($mavjud) {
                    $yozuv->id = $mavjud->id;
                    $DB->update_record('local_targetzenit_payroll', $yozuv);
                    $yangilandi++;
                } else {
                    $DB->insert_record('local_targetzenit_payroll', $yozuv);
                    $yangi++;
                }
            } catch (\dml_exception $e) {
                // Moodle DML xatosi tashqariga faqat "dmlwriteexception" bo'lib
                // chiqadi — haqiqiy sabab `debuginfo` da qoladi va Frappe
                // logida ko'rinmaydi. Sababni xabarga qo'shamiz, aks holda
                // har xatoda server loglarini qo'lda kavlash kerak bo'ladi.
                $sabab = "payroll yozish xatosi (ref=$ref, $yil-$oy): "
                    . $e->getMessage() . ' | ' . ($e->debuginfo ?? '');
                throw new \moodle_exception('error', 'local_targetzenit', '', $sabab, $sabab);
            }
        }

        // Frappe'da tabeldan chiqib ketgan xodim (ishdan ketdi, belgisi olindi)
        // Moodle'da osilib qolmasin. Bu FAQAT `keep` berilganda bajariladi —
        // ya'ni Frappe barcha bo'laklarni yuborib bo'lgandan keyin.
        $ochirildi = 0;
        $qoldiriladigan = array_values(array_filter(array_map('trim', $params['keep'])));
        if ($qoldiriladigan) {
            [$insql, $inparams] = $DB->get_in_or_equal($qoldiriladigan, SQL_PARAMS_NAMED,
                'ref', false);
            $shart = "yil = :y AND oy = :o AND useridnumber $insql";
            $shartparams = array_merge(['y' => $yil, 'o' => $oy], $inparams);
            $ochirildi = $DB->count_records_select('local_targetzenit_payroll',
                $shart, $shartparams);
            $DB->delete_records_select('local_targetzenit_payroll', $shart, $shartparams);
        }

        $transaction->allow_commit();

        return [
            'yangi' => $yangi,
            'yangilandi' => $yangilandi,
            'ochirildi' => $ochirildi,
            'boglanmagan' => self::boglanmagan($yil, $oy),
        ];
    }

    /** Moodle'da `idnumber` i mos keluvchi foydalanuvchi topilmagan qatorlar. */
    private static function boglanmagan(int $yil, int $oy): int {
        global $DB;
        return $DB->count_records_sql("
            SELECT COUNT(1)
              FROM {local_targetzenit_payroll} p
         LEFT JOIN {user} u ON u.idnumber = p.useridnumber AND u.deleted = 0
             WHERE p.yil = :y AND p.oy = :o AND u.id IS NULL",
            ['y' => $yil, 'o' => $oy]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'yangi' => new external_value(PARAM_INT, "Yangi qo'shilgan qatorlar"),
            'yangilandi' => new external_value(PARAM_INT, 'Yangilangan qatorlar'),
            'ochirildi' => new external_value(PARAM_INT, "O'chirilgan ortiqcha qatorlar"),
            'boglanmagan' => new external_value(PARAM_INT,
                "Moodle foydalanuvchisi topilmagan qatorlar (idnumber mos kelmadi)"),
        ]);
    }
}
