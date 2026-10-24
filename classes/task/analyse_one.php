<?php
// Bitta dars yozuvini darhol tahlil qilish (adhoc vazifa).
//
// Yozuv tugashi bilan navbatga qo'yiladi. Moodle croni har DAQIQADA ishlagani
// uchun tahlil ~1 daqiqada boshlanadi — 15 daqiqalik jadvalni kutmaydi.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class analyse_one extends \core\task\adhoc_task {

    public function get_name(): string {
        return get_string('ai_analyse', 'local_targetzenit');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        $lessonid = (int) ($data->lessonid ?? 0);
        if (!$lessonid) {
            return;
        }

        if (!\local_targetzenit\ai::enabled()) {
            mtrace('local_targetzenit: AI o\'chirilgan — tahlil qilinmadi');
            return;
        }

        $audio = \local_targetzenit\audio::get($lessonid);
        if (!$audio || $audio->status !== 'ready') {
            mtrace("local_targetzenit: dars {$lessonid} — audio tayyor emas");
            return;
        }
        if (!empty($audio->analysis)) {
            mtrace("local_targetzenit: dars {$lessonid} allaqachon tahlil qilingan");
            return;
        }

        \core_php_time_limit::raise(900);
        mtrace("local_targetzenit: dars {$lessonid} tahlil qilinmoqda...");
        $ok = \local_targetzenit\ai::analyse($audio);
        mtrace($ok ? '  tayyor' : '  xato: ' .
            (\local_targetzenit\audio::get($lessonid)->aierror ?? 'noma\'lum'));
    }

    /** Yozuv tugagach navbatga qo'yish. */
    public static function queue(int $lessonid): void {
        $task = new self();
        $task->set_custom_data(['lessonid' => $lessonid]);
        $task->set_component('local_targetzenit');
        \core\task\manager::queue_adhoc_task($task, true);   // true = takrorlamaydi
    }
}
