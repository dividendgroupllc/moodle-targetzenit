<?php
// Butun o'quv yili faktini qayta hisoblash — har kuni kechasi.
//
// NEGA KERAK: 15 daqiqalik yangilash faqat oxirgi haftani ko'radi. Eski
// darsga keyinroq baho qo'yilsa, yozuv qo'lda o'chirilsa yoki tahlil
// kechikib tugasa — o'sha o'zgarish faqat shu yurishda hisobga olinadi.
//
// Qayta hisoblash IDEMPOTENT, shuning uchun har kecha xavfsiz yurgiziladi.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class fact_rebuild extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_factrebuild', 'local_targetzenit');
    }

    public function execute(): void {
        $year = \local_targetzenit\timetable::current_year();
        if ($year === '') {
            return;
        }
        $terms = \local_targetzenit\timetable::terms($year);
        if (!$terms) {
            mtrace('local_targetzenit: kalendar yo\'q — fakt qayta hisoblanmadi');
            return;
        }

        $from = min(array_map(fn($t) => $t->startdate, $terms));
        $to = max(array_map(fn($t) => $t->enddate, $terms));

        \core_php_time_limit::raise(900);
        $r = \local_targetzenit\fact::refresh($from, $to, function($x) { mtrace($x); });
        mtrace("local_targetzenit: fakt to'liq qayta hisoblandi {$from} … {$to} — "
            . "{$r['slotlar']} slot, +{$r['qoshildi']} / ~{$r['yangilandi']}");
    }
}
