<?php
// Mobil ilova uchun ko'rinish qatlami.
//
// Har metod ilovaga TAYYOR HTML bo'lagini qaytaradi; ilova uni o'z
// komponentlari bilan chizadi. Qaytish shakli qat'iy:
//   templates  — [['id' => 'main', 'html' => ...]]
//   javascript — ilovada ishlaydigan JS (hozircha kerak emas)
//   otherdata  — shablondagi ngModel uchun boshlang'ich qiymatlar
//
// QOIDA: ma'lumot mantig'i BU YERDA yozilmaydi. Metodlar `classes/*.php`
// dagi o'sha funksiyalarni chaqiradi (veb sahifalar ham shulardan oziqlanadi).
// Yangi SQL yozilsa veb va ilova sonlari bir-biridan uziladi — bu turdagi
// nosozlikni topish eng qiyini.

namespace local_targetzenit\output;

use local_targetzenit\payroll;
use local_targetzenit\timetable;

defined('MOODLE_INTERNAL') || die();

class mobile {

    /**
     * Asosiy panel — 0-bosqich.
     *
     * Hozircha faqat bog'lanish ishlayotganini va qaysi panellar kelishini
     * ko'rsatadi. Maqsad: ilova qatlamining o'zini eng kichik kod bilan
     * tasdiqlash. Sozlama (enablemobilewebservice, kesh, servis) bilan
     * muammo bo'lsa — shu yerda chiqadi, murakkab shablon ichida emas.
     *
     * @param array $args ilova yuboradigan kontekst (userid, appversioncode, ...)
     */
    public static function main(array $args): array {
        global $OUTPUT, $USER;

        // Web-servis chaqiruvi allaqachon autentifikatsiyadan o'tgan, ammo
        // kontekstni tasdiqlash kerak: metod to'g'ridan-to'g'ri ham
        // chaqirilishi mumkin.
        $context = \context_system::instance();
        self::tekshir($context);

        $ref = payroll::ref_for((int) $USER->id);

        $data = [
            'ism' => fullname($USER),
            'bogli' => $ref !== '',
            'oylik_bor' => payroll::has_any((int) $USER->id),
            'jadval_bor' => timetable::current_year() !== '',
            'wwwroot' => (new \moodle_url('/local/targetzenit/index.php'))->out(false),
            'oylik_url' => (new \moodle_url('/local/targetzenit/oylik.php'))->out(false),
            'baho_url' => (new \moodle_url('/local/targetzenit/baholarim.php'))->out(false),
        ];

        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template('local_targetzenit/mobile_main', $data),
            ]],
            'javascript' => '',
            'otherdata' => [],
            'files' => [],
        ];
    }

    /**
     * Dars jadvali — 1-bosqich.
     *
     * Joriy haftaning kun bo'yicha ro'yxati. O'qituvchiga O'Z darslari,
     * o'quvchiga o'z sinfining darslari.
     *
     * "Umumiy jadval" ATAYLAB yo'q: 934 qator telefonda ishlamaydi, u veb'da
     * qoladi. Kun tanlash ham yo'q — butun hafta bir ro'yxatda, bugungi kun
     * belgilangan holda. Telefonda bu bosishdan qulayroq.
     */
    public static function timetable_view(array $args): array {
        global $OUTPUT, $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['yil' => $year, 'bor' => false, 'kunlar' => [], 'xabar' => ''];

        if ($year === '') {
            $data['xabar'] = get_string('timetable_empty', 'local_targetzenit');
            return self::javob('mobile_timetable', $data);
        }

        // Kim sifatida ko'rsatiladi: o'qituvchi bo'lsa o'z darslari, aks holda
        // o'z sinfiniki. Mantiq veb sahifadagi (`index.php`) bilan bir xil.
        $teacherkey = timetable::teacher_key_of($USER, $year);
        $oqituvchi = $teacherkey !== '';
        if ($oqituvchi) {
            $rows = timetable::for_teacher($year, $teacherkey);
        } else {
            $guruhlar = timetable::groups_of((int) $USER->id, $year);
            $guruh = $guruhlar ? array_key_first($guruhlar) : '';
            $rows = $guruh ? timetable::for_group($year, $guruh) : [];
        }

        if (!$rows) {
            $data['xabar'] = $oqituvchi
                ? get_string('timetable_empty', 'local_targetzenit')
                : get_string('tt_noclass', 'local_targetzenit');
            return self::javob('mobile_timetable', $data);
        }

        $terms = timetable::terms($year);
        $holidays = timetable::holidays($year);
        $hafta = timetable::week_dates(date('Y-m-d'));
        $bugun = date('Y-m-d');

        foreach (array_keys(timetable::DAYS) as $dayno) {
            $sana = $hafta[$dayno];
            $holat = timetable::school_day($sana, $terms, $holidays);

            $darslar = [];
            foreach ($rows as $r) {
                if ((int) $r->dayno !== $dayno) {
                    continue;
                }
                $vaqt = trim((string) $r->starttime);
                if (!empty($r->endtime)) {
                    $vaqt .= ' – ' . $r->endtime;
                }
                $darslar[] = [
                    'lessonno' => (int) $r->lessonno,
                    'vaqt' => $vaqt,
                    'fan' => format_string($r->subjectname),
                    'xona' => format_string((string) $r->room),
                    // O'qituvchi qaysi SINFGA kirishini, o'quvchi esa KIM
                    // o'tishini bilishi kerak — ehtiyoj teskari.
                    'kim' => format_string((string)
                        ($oqituvchi ? $r->groupname : $r->teachername)),
                ];
            }

            $data['kunlar'][] = [
                // Hafta kuni nomi yadrodan olinadi — uch til uchun tayyor.
                'nomi' => get_string(strtolower(date('l', strtotime($sana))), 'calendar'),
                'sana' => userdate(strtotime($sana), get_string('strftimedateshort')),
                'bugun' => ($sana === $bugun),
                'yopiq' => !$holat['ok'],
                'sabab' => $holat['reason'],
                'darslar' => $darslar,
                'bor' => (bool) $darslar,
            ];
        }
        $data['bor'] = true;

        return self::javob('mobile_timetable', $data);
    }

    /** Ilova kutadigan javob shakli — har metodda takrorlanmasligi uchun. */
    private static function javob(string $shablon, array $data): array {
        global $OUTPUT;
        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template(
                    'local_targetzenit/' . $shablon, $data),
            ]],
            'javascript' => '',
            'otherdata' => [],
            'files' => [],
        ];
    }

    /**
     * Kontekstni tasdiqlash — har metodda takrorlanmasligi uchun.
     *
     * `require_login()` ATAYLAB chaqirilmaydi: chaqiruv web-servis orqali
     * keladi va autentifikatsiya allaqachon o'tgan; WS kontekstida
     * `require_login()` qayta yo'naltirishga urinib xato berishi mumkin.
     * `validate_context()` esa aynan shu holat uchun mo'ljallangan.
     */
    private static function tekshir(\context $context): void {
        \core_external\external_api::validate_context($context);
    }
}
