<?php
// Mobil ilovadan murojaat ustida ishlash: komment, qaror, ota-ona ko'rdi.
//
// Uchta funksiya emas, BITTA funksiya + `amal` parametri: ilova shablonida
// har tugma uchun alohida WS nomi yozishdan ko'ra bitta nomni ishlatish
// qulay, qolgani parametr bilan hal bo'ladi.
//
// Ruxsat `murojaat::` klassining o'zida tekshiriladi — veb sahifa ham
// aynan o'sha metodlardan o'tadi, ya'ni ilovada boshqa qoida paydo
// bo'lmaydi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_targetzenit\murojaat;

defined('MOODLE_INTERNAL') || die();

class mobile_murojaat extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'murojaatid' => new external_value(PARAM_INT, 'Murojaat id'),
            'amal' => new external_value(PARAM_ALPHA,
                'izoh | qaror | olish | kordim'),
            'matn' => new external_value(PARAM_TEXT, 'Izoh yoki qaror matni', VALUE_DEFAULT, ''),
            'qarorturi' => new external_value(PARAM_TEXT, 'Qaror turi', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $murojaatid, string $amal, string $matn = '',
                                    string $qarorturi = ''): array {
        global $USER;

        $context = \context_system::instance();
        self::validate_context($context);

        $params = self::validate_parameters(self::execute_parameters(), [
            'murojaatid' => $murojaatid, 'amal' => $amal,
            'matn' => $matn, 'qarorturi' => $qarorturi,
        ]);

        $m = murojaat::get((int) $params['murojaatid']);
        if (!$m) {
            throw new \moodle_exception('invalidrecord', 'error');
        }

        switch ($params['amal']) {
            case 'izoh':
                murojaat::izoh_qosh($m, (string) $params['matn']);
                break;

            case 'qaror':
                murojaat::qaror($m, (string) $params['qarorturi'], (string) $params['matn']);
                break;

            case 'olish':
                murojaat::masul_ol($m);
                break;

            case 'kordim':
                // Ota-ona (o'quvchi akkaunti) javobni o'qidi — rahbar
                // "yetib bordimi" ni biladi. Ruxsat: faqat o'z farzandi.
                murojaat::otaona_kordi($m, (int) $USER->id);
                break;

            default:
                throw new \moodle_exception('invalidparameter', 'debug');
        }

        $yangi = murojaat::get((int) $m->id);
        return [
            'ok' => 1,
            'holat' => (string) $yangi->holat,
            'qarorturi' => (string) ($yangi->qarorturi ?? ''),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_INT, '1 = bajarildi'),
            'holat' => new external_value(PARAM_TEXT, 'Yangi holat'),
            'qarorturi' => new external_value(PARAM_TEXT, 'Qaror turi'),
        ]);
    }
}
