<?php
// Audio yozuvi xatosi — KOD bilan.
//
// NEGA ALOHIDA SINF: brauzer xatoni ikki turga ajratishi SHART.
//   - vaqtinchalik (internet yo'q, server band) -> bo'lakni navbatda saqlab
//     qayta-qayta urinish kerak, ovoz yo'qolmasligi uchun;
//   - qat'iy (sessiya tugagan, boshqa oyna egalladi, hajm chegarasi) ->
//     urinishdan foyda yo'q, o'qituvchiga aytib yozuvni to'xtatish kerak.
//
// Kodsiz brauzer ikkisini ajrata olmaydi va qat'iy xatoda CHEKSIZ urinib,
// navbatni to'xtatib qo'yadi — o'sha paytda "To'xtatish" ham osilib qoladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class audio_xato extends \moodle_exception {

    /** Brauzer tushunadigan kod: tugagan | boshqa_sessiya | chegara | katta | disk | yoq | band | bosh | birlashma */
    public $kod;

    /** Shu kodlarda qayta urinish FOYDASIZ — brauzer yozuvni to'xtatadi. */
    const QATIY = ['tugagan', 'boshqa_sessiya', 'chegara', 'katta', 'yoq', 'ochirilgan'];

    public function __construct(string $kod, string $matn) {
        $this->kod = $kod;
        // Plagin `error` satri — '{$a}', ya'ni xabar o'zi ko'rinadi.
        parent::__construct('error', 'local_targetzenit', '', $matn);
        $this->message = $matn;
    }

    public static function qatiymi(string $kod): bool {
        return in_array($kod, self::QATIY, true);
    }
}
