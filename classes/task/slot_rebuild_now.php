<?php
// Jadval Frappe'dan kelgandan keyin rejani DARHOL qayta qurish (adhoc).
//
// NEGA: push'dan keyin kechasigacha kutilsa, direktor paneli eski jadval
// bo'yicha ishlaydi va "o'qituvchi almashdi" degan o'zgarish bir kun
// ko'rinmaydi. Moodle croni har daqiqada ishlagani uchun reja ~1 daqiqada
// yangilanadi.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class slot_rebuild_now extends \core\task\adhoc_task {

    public function get_name(): string {
        return get_string('task_slotbuild', 'local_targetzenit');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        $year = (string) ($data->year ?? '');
        if ($year === '') {
            $year = \local_targetzenit\timetable::current_year();
        }
        if ($year === '') {
            return;
        }

        \core_php_time_limit::raise(600);

        // Jadval kuchga kirgan sana O'ZGARGAN bo'lsa — o'sha sanadan
        // bugungacha bo'lgan muzlatilgan slotlarni ochamiz; `build()` ularni
        // yangi jadval bo'yicha qayta quradi va oxirida yana muzlatadi.
        // Sabab va xavfsizlik shartlari: `slot::muzdan_chiqar()` izohida.
        $sanadan = trim((string) ($data->effectivefrom ?? ''));
        $oxirgi = (string) get_config('local_targetzenit', 'slot_effectivefrom');
        if ($sanadan !== '' && $sanadan !== $oxirgi) {
            $ochildi = \local_targetzenit\slot::muzdan_chiqar($year, $sanadan);
            set_config('slot_effectivefrom', $sanadan, 'local_targetzenit');
            mtrace("local_targetzenit: jadval {$sanadan} dan kuchga kirdi — "
                . "{$ochildi} slot muzdan chiqarildi");
        }

        $s = \local_targetzenit\staff::rebuild($year);
        mtrace("local_targetzenit: o'qituvchilar yangilandi — {$s['jami']} ta");

        $r = \local_targetzenit\slot::build($year);
        mtrace("local_targetzenit: reja yangilandi — +{$r['qoshildi']} / "
            . "~{$r['yangilandi']} / -{$r['ochirildi']}");

        // Yangi slotlar uchun fakt qatorlari ham kerak — aks holda panel
        // ularni "reja yo'q" deb ko'rsatadi.
        $from = date('Y-m-d', strtotime('-7 days'));
        $to = date('Y-m-d', strtotime('+30 days'));
        \local_targetzenit\fact::refresh($from, $to);
    }

    /** Jadval push'idan keyin navbatga qo'yish. */
    public static function queue(string $year): void {
        $task = new self();
        $task->set_custom_data(['year' => $year]);
        $task->set_component('local_targetzenit');
        \core\task\manager::queue_adhoc_task($task, true);   // true = takrorlamaydi
    }
}
