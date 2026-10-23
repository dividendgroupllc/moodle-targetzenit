<?php
// Muddati o'tgan dars audiolarini o'chirish.
//
// Audio VAQTINCHALIK: haftasiga ~7.5 GB to'planadi (934 dars x ~8 MB).
// Shuning uchun muddat qat'iy — transkript qilingan yoki qilinmaganidan
// qat'i nazar o'chadi. Transkript matni bazada qoladi.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_audio extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('audio_cleanup', 'local_targetzenit');
    }

    public function execute(): void {
        $n = \local_targetzenit\audio::cleanup();
        if ($n['soni']) {
            mtrace(sprintf("local_targetzenit: %d ta dars audiosi o'chirildi, %.1f MB bo'shadi",
                $n['soni'], $n['bayt'] / 1048576));
        } else {
            mtrace('local_targetzenit: muddati o\'tgan audio yo\'q');
        }
    }
}
