<?php
// Faktni yangilash — oxirgi kunlar uchun, har 15 daqiqada.
//
// Oyna ataylab kichik: o'qituvchi jurnalni odatda shu kuni yoki ertasiga
// to'ldiradi. Butun chorak kechasi qayta hisoblanadi (`fact_rebuild`).

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class fact_refresh extends \core\task\scheduled_task {

    /** Necha kun orqaga qaraladi. */
    const ORQAGA = 7;

    public function get_name(): string {
        return get_string('task_factrefresh', 'local_targetzenit');
    }

    public function execute(): void {
        $from = date('Y-m-d', strtotime('-' . self::ORQAGA . ' days'));
        $to = date('Y-m-d', strtotime('+1 day'));

        $r = \local_targetzenit\fact::refresh($from, $to);
        mtrace("local_targetzenit: fakt {$from} … {$to} — {$r['slotlar']} slot, "
            . "+{$r['qoshildi']} / ~{$r['yangilandi']} / ={$r['tegilmadi']}");
    }
}
