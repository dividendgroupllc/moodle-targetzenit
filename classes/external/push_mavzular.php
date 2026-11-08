<?php
// Frappe -> Moodle: o'quv reja (KTP) mavzularini qabul qilish.
//
// Frappe reja ro'yxatini yuboradi, Moodle esa uni dars jadvaliga biriktiradi
// (`\local_targetzenit\mavzu::biriktir`). Nega biriktirish shu yerda —
// `classes/mavzu.php` boshidagi izohga qarang.
//
// ALMASHTIRISH QOIDASI: faqat yuborilgan (fan, guruh, chorak) uchliklari
// almashadi. Zam direktor 2-chorakni qayta yuklaganda 1-chorak rejasi
// (va unga biriktirilgan darslar) joyida qoladi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_targetzenit\mavzu;

defined('MOODLE_INTERNAL') || die();

class push_mavzular extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'academicyear' => new external_value(PARAM_TEXT, "O'quv yili, masalan 2026-2027"),
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'subjectkey' => new external_value(PARAM_TEXT, 'Frappe Course nomi'),
                    'groupkey' => new external_value(PARAM_TEXT, 'Frappe Student Group nomi'),
                    'chorak' => new external_value(PARAM_INT, '1..4; 0 = butun yil', VALUE_DEFAULT, 0),
                    'tartib' => new external_value(PARAM_INT, 'Chorak ichidagi tartib', VALUE_DEFAULT, 0),
                    'bolim' => new external_value(PARAM_TEXT, "Bo'lim", VALUE_DEFAULT, ''),
                    'mavzu' => new external_value(PARAM_TEXT, 'Mavzu'),
                    'soat' => new external_value(PARAM_INT, 'Necha darsga', VALUE_DEFAULT, 1),
                    'darsturi' => new external_value(PARAM_TEXT, 'Dars turi', VALUE_DEFAULT, ''),
                    'frappereja' => new external_value(PARAM_TEXT, 'Oquv Reja.name', VALUE_DEFAULT, ''),
                ]),
                'Reja qatorlari', VALUE_DEFAULT, []
            ),
            'biriktir' => new external_value(PARAM_INT,
                'Darslarga biriktirish ham bajarilsinmi (oxirgi bo\'lakda 1)',
                VALUE_DEFAULT, 1),
        ]);
    }

    public static function execute(string $academicyear, array $rows = [],
                                    int $biriktir = 1): array {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $params = self::validate_parameters(self::execute_parameters(), [
            'academicyear' => $academicyear, 'rows' => $rows, 'biriktir' => $biriktir,
        ]);
        $yil = $params['academicyear'];
        $now = time();

        // 1) Qaysi uchliklar almashadi
        $uchliklar = [];
        foreach ($params['rows'] as $r) {
            if (trim((string) $r['mavzu']) === '') {
                continue;
            }
            $kalit = $r['subjectkey'] . '|' . $r['groupkey'] . '|' . (int) $r['chorak'];
            $uchliklar[$kalit] = [$r['subjectkey'], $r['groupkey'], (int) $r['chorak']];
        }

        $ochirildi = 0;
        foreach ($uchliklar as [$fan, $guruh, $chorak]) {
            $ochirildi += $DB->count_records('local_targetzenit_mavzu', [
                'academicyear' => $yil, 'subjectkey' => $fan,
                'groupkey' => $guruh, 'chorak' => $chorak,
            ]);
            $DB->delete_records('local_targetzenit_mavzu', [
                'academicyear' => $yil, 'subjectkey' => $fan,
                'groupkey' => $guruh, 'chorak' => $chorak,
            ]);
        }

        // 2) Yangi qatorlar
        $qoshildi = 0;
        foreach ($params['rows'] as $r) {
            if (trim((string) $r['mavzu']) === '') {
                continue;
            }
            $DB->insert_record('local_targetzenit_mavzu', (object) [
                'academicyear' => $yil,
                'subjectkey' => (string) $r['subjectkey'],
                'groupkey' => (string) $r['groupkey'],
                'chorak' => (int) $r['chorak'],
                'tartib' => (int) $r['tartib'],
                'bolim' => \core_text::substr((string) $r['bolim'], 0, 255),
                'mavzu' => (string) $r['mavzu'],
                'soat' => max((int) $r['soat'], 1),
                'darsturi' => \core_text::substr((string) $r['darsturi'], 0, 50),
                'frappereja' => \core_text::substr((string) $r['frappereja'], 0, 64),
                'timemodified' => $now,
            ]);
            $qoshildi++;
        }

        // 3) Darslarga biriktirish — faqat tegilgan fan/guruhlar uchun
        $hisobot = ['juftlar' => 0, 'biriktirildi' => 0, 'bosh_slot' => 0,
                    'sigmagan' => 0, 'ogohlantirish' => []];
        if ($params['biriktir']) {
            $korilgan = [];
            foreach ($uchliklar as [$fan, $guruh, $chorak]) {
                $kalit = $fan . '|' . $guruh;
                if (isset($korilgan[$kalit])) {
                    continue;
                }
                $korilgan[$kalit] = true;
                $h = mavzu::biriktir($yil, ['subjectkey' => $fan, 'groupkey' => $guruh]);
                foreach (['juftlar', 'biriktirildi', 'bosh_slot', 'sigmagan'] as $k) {
                    $hisobot[$k] += $h[$k];
                }
                $hisobot['ogohlantirish'] = array_merge($hisobot['ogohlantirish'],
                    $h['ogohlantirish']);
            }
        }

        return [
            'ochirildi' => $ochirildi,
            'qoshildi' => $qoshildi,
            'juftlar' => $hisobot['juftlar'],
            'biriktirildi' => $hisobot['biriktirildi'],
            'bosh_slot' => $hisobot['bosh_slot'],
            'sigmagan' => $hisobot['sigmagan'],
            'ogohlantirish' => array_slice($hisobot['ogohlantirish'], 0, 20),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ochirildi' => new external_value(PARAM_INT, "Almashtirilgan eski qatorlar"),
            'qoshildi' => new external_value(PARAM_INT, 'Yangi qatorlar'),
            'juftlar' => new external_value(PARAM_INT, 'Fan x guruh juftligi'),
            'biriktirildi' => new external_value(PARAM_INT, 'Mavzu biriktirilgan darslar'),
            'bosh_slot' => new external_value(PARAM_INT, "Mavzusiz qolgan darslar"),
            'sigmagan' => new external_value(PARAM_INT, "Darsga sig'magan mavzular"),
            'ogohlantirish' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Ogohlantirish'), 'Ogohlantirishlar'),
        ]);
    }
}
