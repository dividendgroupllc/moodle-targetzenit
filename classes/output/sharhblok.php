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
     */
    public static function thread(array $rows, bool $kimyozdi = true,
                                    bool $qaysikurs = false): string {
        $t = fn(string $k) => get_string($k, 'local_targetzenit');

        if (!$rows) {
            return \html_writer::div($t('sh_none'), 'tz-dir-sub');
        }
        $h = \html_writer::start_div('tz-sh-list');

        foreach ($rows as $r) {
            $maxfiy = ($r->visibility === 'maxfiy');

            $meta = \html_writer::tag('span',
                userdate($r->timecreated, '%d.%m.%Y %H:%M'), ['class' => 'tz-dir-sub']);

            if ($kimyozdi) {
                $meta = \html_writer::tag('b',
                    s(dashboard::ism($r->afirstname ?? '', $r->alastname ?? ''))) . ' · ' . $meta;
            }
            if ($qaysikurs) {
                $meta .= ' · ' . \html_writer::tag('span',
                    s(sharh::kurs_yorlig($r->courseidnumber ?? '', $r->coursename ?? '')),
                    ['class' => 'tz-dir-chip']);
            }
            if ($maxfiy) {
                // Maxfiy qayd o'quvchiga HECH QACHON yuborilmaydi (so'rovda
                // filtrlanadi), lekin xodim o'z ekranida buni ko'rib turishi
                // kerak — aks holda «o'quvchi o'qidimi?» degan savol tug'iladi.
                $meta .= ' ' . \html_writer::tag('span', $t('sh_private'),
                    ['class' => 'tz-dir-chip tz-dir-warning']);
            } else if ($r->status === 'seen') {
                $meta .= ' ' . \html_writer::tag('span',
                    $t('sh_seen') . ($r->seenat
                        ? ' · ' . userdate($r->seenat, '%d.%m.%Y') : ''),
                    ['class' => 'tz-dir-sub']);
            } else {
                $meta .= ' ' . \html_writer::tag('span', $t('sh_notseen'),
                    ['class' => 'tz-dir-sub']);
            }

            $h .= \html_writer::start_div('tz-sh-item' . ($maxfiy ? ' tz-sh-maxfiy' : ''));
            $h .= \html_writer::div($meta, 'tz-sh-meta');
            // Matn `{comments}` da FORMAT_PLAIN bilan saqlanadi — HTML emas,
            // shuning uchun `s()` + `nl2br()`, `format_text()` emas.
            $h .= \html_writer::tag('p', nl2br(s(sharh::matn($r))), ['class' => 'tz-sh-body']);
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

        // Katak ichidagi bosish qatorni ochmasligi kerak — `data-tzstop` ni
        // JS tekshiradi (lenta bilan bir xil naqsh).
        $h = \html_writer::start_tag('form', [
            'method' => 'post', 'action' => $action,
            'class' => $qator ? 'tz-sh-cform' : 'tz-sh-form',
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

        if ($qator) {
            $h .= \html_writer::empty_tag('input', ['type' => 'text', 'name' => 'shbody',
                'maxlength' => sharh::MAX, 'placeholder' => $t('sh_addshort')]);
            $h .= \html_writer::tag('button', '➤', ['type' => 'submit',
                'class' => 'tz-sh-csend', 'title' => $t('sh_send')]);
        } else {
            $h .= \html_writer::tag('textarea', '', [
                'name' => 'shbody', 'rows' => 4, 'class' => 'tz-sh-text',
                'maxlength' => sharh::MAX, 'placeholder' => $t('sh_placeholder'),
            ]);
            $h .= \html_writer::start_div('tz-sh-tools');
            // STANDART — OCHIQ: sharhning maqsadi o'quvchiga va ota-onaga
            // qaytarma berish. «Maxfiy» ataylab tanlanadigan istisno
            // (ma'muriy qayd), shuning uchun belgisiz holda ochiq qoladi.
            $h .= \html_writer::tag('label',
                \html_writer::checkbox('shprivate', 1, false, '') . ' ' . $t('sh_privatelab'),
                ['class' => 'tz-sh-check', 'title' => $t('sh_privatehelp')]);
            $h .= \html_writer::empty_tag('input', ['type' => 'submit',
                'class' => 'btn btn-sm btn-primary', 'value' => $t('sh_send')]);
            $h .= \html_writer::end_div();
        }

        $h .= \html_writer::end_tag('form');
        return $h;
    }

    /**
     * Qisqa ko'rinish — jadval qatorining o'z katagida: oxirgi sharh
     * kichraytirilgan holda + bir qatorli yozish maydoni.
     *
     * Kartani ochmasdan yozish mumkin bo'lishi kerak: o'qituvchi 25 ta
     * o'quvchini ko'zdan kechirib ba'zisiga bir og'iz izoh qoldiradi.
     *
     * @param array|null $oxirgi `sharh::latest()` dan kelgan qator
     */
    public static function compactcell(int $courseid, int $studentid,
                                        ?array $oxirgi, string $action,
                                        array $qaytar = []): string {
        $t = fn(string $k) => get_string($k, 'local_targetzenit');

        $h = \html_writer::start_div('tz-sh-cell', ['data-tzstop' => '1']);

        $soni = (int) ($oxirgi['soni'] ?? 0);
        $son = $oxirgi['oxirgi'] ?? null;

        if ($son) {
            $xom = sharh::matn($son);
            $matn = \core_text::substr(trim($xom), 0, sharh::QISQA);
            $h .= \html_writer::div(
                \html_writer::tag('b',
                    s(dashboard::ism($son->afirstname ?? '', $son->alastname ?? '')))
                . ' · ' . userdate($son->timecreated, '%d.%m.%Y')
                . ($soni > 1 ? ' · ' . \html_writer::tag('span', (string) $soni,
                    ['class' => 'tz-sh-count', 'title' => $t('sh_count_title')]) : '')
                . ($son->visibility === 'maxfiy'
                    ? ' ' . \html_writer::tag('span', $t('sh_private'),
                        ['class' => 'tz-dir-chip tz-dir-warning']) : ''),
                'tz-sh-cmeta');
            $h .= \html_writer::div(
                s($matn) . (\core_text::strlen($xom) > sharh::QISQA ? '…' : ''),
                'tz-sh-ctext', ['title' => s($xom)]);
        } else {
            $h .= \html_writer::div($t('sh_none'), 'tz-dir-sub');
        }

        $h .= self::form($courseid, $studentid, $action, true, $qaytar);
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

        $body = optional_param('shbody', '', PARAM_TEXT);
        $maxfiy = optional_param('shprivate', 0, PARAM_INT);

        // Ruxsat va «shu kursning o'quvchisimi» tekshiruvi `sharh::add()`
        // ichida — bitta joyda, chunki yozish yo'li bitta bo'lishi kerak
        // (veb forma bugun, mobil ilova ertaga).
        return \local_targetzenit\sharh::add($courseid, $studentid, $body,
            $maxfiy ? 'maxfiy' : 'ochiq');
    }
}
