<?php
// Rejani (slot) jadval x kalendardan qayta qurish — har kuni kechasi.
//
// Avval o'qituvchilar ro'yxati (kalit ko'prigi), keyin slotlar: slot
// `teacheruserid` ni shu ko'prikdan oladi, tartib muhim.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class slot_build extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_slotbuild', 'local_targetzenit');
    }

    public function execute(): void {
        $year = \local_targetzenit\timetable::current_year();
        if ($year === '') {
            mtrace('local_targetzenit: jadval bo\'sh — reja qurilmadi');
            return;
        }

        $s = \local_targetzenit\staff::rebuild($year);
        mtrace("local_targetzenit: o'qituvchilar — {$s['jami']} ta "
            . "({$s['ok']} bog'langan, {$s['userless']} user yo'q, "
            . "{$s['legacykey']} eski kalit)");
        if ($s['userless'] || $s['legacykey']) {
            // JIM YIQILISHNI ko'rinadigan qilamiz: bu kalitlarning darslari
            // hech kimning hisobotiga tushmaydi.
            mtrace('  OGOHLANTIRISH: bog\'lanmagan kalitlar bor — '
                . 'Frappe\'dan jadvalni qayta yuborish kerak bo\'lishi mumkin');
        }

        $r = \local_targetzenit\slot::build($year, null, null,
            function($x) { mtrace($x); });
        mtrace("local_targetzenit: reja — +{$r['qoshildi']} qo'shildi, "
            . "{$r['yangilandi']} yangilandi, {$r['ochirildi']} o'chirildi, "
            . "{$r['tegilmadi']} o'tmish tegilmadi");
    }
}
