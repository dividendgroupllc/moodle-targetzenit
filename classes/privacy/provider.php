<?php
// Maxfiylik API'si: bu plagin shaxsiy ma'lumot saqlamaydi.
// Bo'lmasa admin panelida "Plugins missing privacy implementation" ogohlantirishi chiqadi.

namespace local_targetzenit\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
