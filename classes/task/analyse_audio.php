<?php
// Yozib olingan darslarni AI bilan tahlil qilish.
//
// Har yurishda cheklangan sondagi yozuv olinadi — bitta so'rov bir necha
// daqiqa ketishi mumkin va Gemini kvotasi cheklangan.

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class analyse_audio extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('ai_task', 'local_targetzenit');
    }

    public function execute(): void {
        // 1) Tashlab ketilgan yozuvlarni yakunlaymiz (sahifa qayta yuklangan,
        //    brauzer yopilgan hollar). Bu AI'dan QAT'I NAZAR bajariladi.
        $y = \local_targetzenit\audio::finalize_stale();
        if ($y['yakunlandi']) {
            mtrace("local_targetzenit: {$y['yakunlandi']} ta tashlab ketilgan yozuv yakunlandi");
        }

        // 2) Tahlil
        if (!\local_targetzenit\ai::enabled()) {
            mtrace('local_targetzenit: AI tahlil o\'chirilgan yoki kalit yo\'q');
            return;
        }

        $soni = (int) (get_config('local_targetzenit', 'aiperrun') ?: 3);
        $navbat = \local_targetzenit\ai::pending($soni);

        if (!$navbat) {
            mtrace('local_targetzenit: tahlil kutayotgan dars yo\'q');
            return;
        }

        $ok = 0;
        $xato = 0;
        foreach ($navbat as $audio) {
            mtrace("  dars {$audio->lessonid} tahlil qilinmoqda...");
            if (\local_targetzenit\ai::analyse($audio)) {
                $ok++;
            } else {
                $xato++;
                // Sabab bazaga yozilgan — qayta urinish keyingi yurishda
                $yangi = \local_targetzenit\audio::get((int) $audio->lessonid);
                mtrace('    xato: ' . ($yangi->aierror ?? 'noma\'lum'));
            }
        }
        mtrace("local_targetzenit: tahlil tugadi — {$ok} muvaffaqiyatli, {$xato} xato");
    }
}
