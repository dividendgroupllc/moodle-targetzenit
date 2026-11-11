<?php
// O'QUVCHI SHARHI BLOKI — YAGONA chizuvchi.
//
// Xuddi shu blok uch joyda ko'rinadi:
//   sharh.php       o'qituvchi yozadi (guruh -> o'quvchi ro'yxati)
//   sharhlar.php    direktor lentasi (faqat o'qish)
//   sharhlarim.php  o'quvchi/ota-ona ko'radi (faqat o'qish)
//
// Kod nusxalansa, ko'rinish qoidasi (maxfiy belgisi, «ko'rildi», tartib)
// o'zgarganda uch joyni yangilash esdan chiqadi va sahifalar bir-biridan
// farq qila boshlaydi — `output\fidbek` bilan bir xil sabab.

namespace local_targetzenit\output;

use local_targetzenit\dashboard;
use local_targetzenit\sharh;

defined('MOODLE_INTERNAL') || die();

class sharhblok {

    /**
     * Sharhlar zanjiri.
     *
     * @param array $rows `sharh::for_student()` / `mine()` / `royxat()` qatorlari
     * @param bool $kimyozdi muallif ismi ko'rsatilsinmi (o'quvchiga — ha,
     *                       o'qituvchining o'z ro'yxatida ham ha: bir guruhga
     *                       bir necha fan o'qituvchisi yozadi)
     * @param bool $qaysikurs guruh/fan yorlig'i ko'rsatilsinmi (o'quvchi
     *                        sahifasida — ha, kurs ichida — yo'q)
     * @param callable|null $ochirurl `fn(int $sharhid): moodle_url` — berilsa
     *        SAYT MA'MURIGA o'chirish tugmasi chiziladi.
     *        ATAYLAB parametr: o'quvchi/ota-ona sahifasi uni BERMAYDI, ya'ni
     *        tugma u yerda tasodifan paydo bo'lib qolmaydi. Ruxsat esa
     *        baribir `sharh::ochir()` ichida tekshiriladi.
     */
    public static function thread(array $rows, bool $kimyozdi = true,
                                    bool $qaysikurs = false,
                                    ?callable $ochirurl = null): string {
        $t = fn(string $k) => get_string($k, 'local_targetzenit');
        $ochirsin = ($ochirurl !== null) && sharh::ochirish_mumkinmi();

        if (!$rows) {
            return \html_writer::div($t('sh_none'), 'tz-dir-sub');
        }
        $yorliqlar = sharh::kategoriyalar_menu();
        $h = \html_writer::start_div('tz-sh-list');

        // TO'PLAM = bitta saqlash (besh kategoriya). Eski erkin sharh ham
        // to'plam bo'lib keladi, shuning uchun bu yerda ikkinchi shoxcha yo'q.
        foreach (sharh::guruhla($rows) as $tp) {
            $b = $tp['bosh'];
            $maxfiy = ($b->visibility === 'maxfiy');

            $meta = \html_writer::tag('span',
                userdate($b->timecreated, '%d.%m.%Y %H:%M'), ['class' => 'tz-dir-sub']);

            if ($kimyozdi) {
                $meta = \html_writer::tag('b',
                    s(dashboard::ism($b->afirstname ?? '', $b->alastname ?? ''))) . ' · ' . $meta;
            }
            if ($qaysikurs) {
                $meta .= ' · ' . \html_writer::tag('span',
                    s(sharh::kurs_yorlig($b->courseidnumber ?? '', $b->coursename ?? '')),
                    ['class' => 'tz-dir-chip']);
            }
            if ($maxfiy) {
                // Maxfiy qayd o'quvchiga HECH QACHON yuborilmaydi (so'rovda
                // filtrlanadi), lekin xodim o'z ekranida buni ko'rib turishi
                // kerak — aks holda «o'quvchi o'qidimi?» degan savol tug'iladi.
                $meta .= ' ' . \html_writer::tag('span', $t('sh_private'),
                    ['class' => 'tz-dir-chip tz-dir-warning']);
            } else if ($b->status === 'seen') {
                $meta .= ' ' . \html_writer::tag('span',
                    $t('sh_seen') . ($b->seenat
                        ? ' · ' . userdate($b->seenat, '%d.%m.%Y') : ''),
                    ['class' => 'tz-dir-sub']);
            } else {
                $meta .= ' ' . \html_writer::tag('span', $t('sh_notseen'),
                    ['class' => 'tz-dir-sub']);
            }

            $h .= \html_writer::start_div('tz-sh-item' . ($maxfiy ? ' tz-sh-maxfiy' : ''));
            $h .= \html_writer::div($meta, 'tz-sh-meta');

            // Kategoriyalar BELGILANGAN TARTIBDA chiqadi, bazadan kelgan
            // tartibda emas: o'qituvchi va ota-ona har sharhda bir xil
            // ketma-ketlikni ko'rishi kerak.
            foreach ($yorliqlar as $kat => $nom) {
                if (!isset($tp['matnlar'][$kat])) {
                    continue;
                }
                $h .= \html_writer::div(
                    \html_writer::div(s($nom), 'tz-sh-katlab')
                    // Matn `{comments}` da FORMAT_PLAIN bilan saqlanadi —
                    // HTML emas, shuning uchun `s()` + `nl2br()`.
                    . \html_writer::tag('p',
                        nl2br(s(sharh::matn($tp['matnlar'][$kat]))),
                        ['class' => 'tz-sh-body']),
                    'tz-sh-kat');
            }

            // Eski erkin sharh (`umumiy`) — yorlig'i bilan, ko'chirilmasdan
            if (isset($tp['matnlar'][sharh::ESKI_KAT])) {
                $h .= \html_writer::div(
                    \html_writer::div($t('sh_kat_umumiy'), 'tz-sh-katlab')
                    . \html_writer::tag('p',
                        nl2br(s(sharh::matn($tp['matnlar'][sharh::ESKI_KAT]))),
                        ['class' => 'tz-sh-body']),
                    'tz-sh-kat tz-sh-kat-old');
            }

            // --- O'CHIRISH (faqat sayt ma'muri) — TO'PLAM bo'yicha
            if ($ochirsin) {
                $h .= \html_writer::div(\html_writer::link(
                    $ochirurl((int) $b->id), $t('sh_delete'),
                    ['class' => 'btn btn-sm btn-outline-danger',
                     // Besh kategoriya birga ketadi — foydalanuvchi shuni
                     // bilib turib tasdiqlashi kerak.
                     'onclick' => 'return confirm('
                         . json_encode($t('sh_delete_q')) . ')']),
                    'tz-sh-del');
            }

            $h .= \html_writer::end_div();
        }

        $h .= \html_writer::end_div();
        return $h;
    }

    /**
     * Yozish formasi.
     *
     * @param string $action forma qayerga yuborilsin (bo'sh = shu sahifa)
     * @param bool $qator bir qatorli ko'rinish (jadval katagida) yoki to'liq
     * @param array $qaytar formadan keyin saqlanadigan qo'shimcha parametrlar
     */
    public static function form(int $courseid, int $studentid, string $action,
                                 bool $qator = false, array $qaytar = []): string {
        $t = fn(string $k) => get_string($k, 'local_targetzenit');

        // Formani ko'rsatish ham, qabul qilish ham bir xil shartga tayanadi.
        // QOIDA (fidbek modulidan): forma ko'rinmasligi himoya EMAS —
        // `handle()` da ham ayni tekshiruv turadi.
        if (!$courseid || !$studentid) {
            return '';
        }
        if (!has_capability('local/targetzenit:teach',
                \context_course::instance($courseid, IGNORE_MISSING)
                    ?: \context_system::instance())) {
            return '';
        }

        $h = \html_writer::start_tag('form', [
            'method' => 'post', 'action' => $action, 'class' => 'tz-sh-form',
            'data-tzstop' => '1',
        ]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'shcourse', 'value' => $courseid]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'shstudent', 'value' => $studentid]);
        foreach ($qaytar as $nom => $qiymat) {
            $h .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => $nom, 'value' => (string) $qiymat]);
        }

        // BESH KATEGORIYA — har biriga o'z maydoni, yonida yorlig'i va
        // nima yozilishi kerakligi haqida qisqa ko'rsatma.
        //
        // QOIDA: forma massiv kalitlari faqat [a-z0-9_-] bo'lishi kerak —
        // `optional_param_array()` mos kelmaganini JIMGINA tashlab yuboradi
        // (0.9.4 saboqi). Kategoriya kalitlari shu talabga mos.
        $h .= \html_writer::div($t('sh_allhint'), 'tz-dir-sub mb-2');

        foreach (sharh::kategoriyalar_menu() as $kat => $nom) {
            $h .= \html_writer::start_div('tz-sh-field');
            $h .= \html_writer::tag('label', s($nom), ['class' => 'tz-sh-katlab']);
            $h .= \html_writer::div($t('sh_hint_' . $kat), 'tz-dir-sub');
            $h .= \html_writer::tag('textarea', '', [
                'name' => 'shkat[' . $kat . ']', 'rows' => 3,
                'class' => 'tz-sh-text', 'maxlength' => sharh::MAX,
                'required' => 'required',
                'placeholder' => $t('sh_ph_' . $kat),
            ]);
            $h .= \html_writer::end_div();
        }

        $h .= \html_writer::start_div('tz-sh-tools');
        // STANDART — OCHIQ: sharhning maqsadi o'quvchiga va ota-onaga
        // qaytarma berish. «Maxfiy» ataylab tanlanadigan istisno.
        $h .= \html_writer::tag('label',
            \html_writer::checkbox('shprivate', 1, false, '') . ' ' . $t('sh_privatelab'),
            ['class' => 'tz-sh-check', 'title' => $t('sh_privatehelp')]);
        $h .= \html_writer::empty_tag('input', ['type' => 'submit',
            'class' => 'btn btn-sm btn-primary', 'value' => $t('sh_send')]);
        $h .= \html_writer::end_div();

        $h .= \html_writer::end_tag('form');
        return $h;
    }

    /**
     * Qisqa ko'rinish — jadval qatorining o'z katagida.
     *
     * BIR QATORLI YOZISH MAYDONI OLIB TASHLANDI: endi sharh besh
     * kategoriyadan iborat va uni bitta `<input>` ga sig'dirib bo'lmaydi.
     * Katak faqat OXIRGI to'plamning xulosasini ko'rsatadi, yozish esa
     * qatorni ochganda to'liq forma bilan bo'ladi.
     *
     * @param array|null $oxirgi `sharh::latest()` dan kelgan qator
     */
    public static function compactcell(int $courseid, int $studentid,
                                        ?array $oxirgi, string $action,
                                        array $qaytar = []): string {
        $t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

        $h = \html_writer::start_div('tz-sh-cell');

        $son = $oxirgi['oxirgi'] ?? null;
        if (!$son) {
            $h .= \html_writer::div($t('sh_none'), 'tz-dir-sub');
            $h .= \html_writer::end_div();
            return $h;
        }

        $xom = sharh::matn($son);
        $matn = \core_text::substr(trim($xom), 0, sharh::QISQA);

        $h .= \html_writer::div(
            \html_writer::tag('b',
                s(dashboard::ism($son->afirstname ?? '', $son->alastname ?? '')))
            . ' · ' . userdate($son->timecreated, '%d.%m.%Y')
            . ($son->visibility === 'maxfiy'
                ? ' ' . \html_writer::tag('span', $t('sh_private'),
                    ['class' => 'tz-dir-chip tz-dir-warning']) : ''),
            'tz-sh-cmeta');

        // Qaysi kategoriya ekani yoziladi: aks holda «bu matn nimaga
        // tegishli» degan savol tug'iladi.
        $kat = ($son->kategoriya === sharh::ESKI_KAT)
            ? $t('sh_kat_umumiy')
            : $t('sh_kat_' . $son->kategoriya);

        $h .= \html_writer::div(
            \html_writer::tag('span', s($kat), ['class' => 'tz-sh-katchip'])
            . ' ' . s($matn)
            . (\core_text::strlen($xom) > sharh::QISQA ? '…' : ''),
            'tz-sh-ctext', ['title' => s($xom)]);

        $h .= \html_writer::end_div();
        return $h;
    }

    /**
     * Yuborilgan formani qabul qiladi. Sahifa boshida, chiqarishdan OLDIN
     * chaqiriladi (POST/Redirect/GET — forma qayta yuborilmasin).
     *
     * @return int saqlangan sharh id si (0 = yuborilmagan)
     */
    public static function handle(): int {
        $courseid = optional_param('shcourse', 0, PARAM_INT);
        $studentid = optional_param('shstudent', 0, PARAM_INT);
        if (!$courseid || !$studentid) {
            return 0;
        }
        require_sesskey();

        $matnlar = optional_param_array('shkat', [], PARAM_TEXT);
        $maxfiy = optional_param('shprivate', 0, PARAM_INT);

        // Ruxsat, «shu kursning o'quvchisimi» va «beshtasi to'ldirilganmi»
        // tekshiruvlari `sharh::add_batch()` / `add()` ICHIDA — bitta joyda,
        // chunki yozish yo'li bitta bo'lishi kerak (veb forma bugun, mobil
        // ilova ertaga).
        $n = sharh::add_batch($courseid, $studentid, $matnlar,
            $maxfiy ? 'maxfiy' : 'ochiq');

        return (int) $n['yozildi'];
    }
}
