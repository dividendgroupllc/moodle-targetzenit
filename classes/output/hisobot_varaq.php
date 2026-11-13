<?php
// OTA-ONA UCHUN O'QUVCHI HISOBOTI — CHIZUVCHI (A4 varaq).
//
// MAQSAD (foydalanuvchi aniqlashtirdi 2026-11-13): ota-ona varaqqa qarab
// FARZANDI QAYSI FANDA KUCHLI, QAYSI FANDA SUST ekanini va maktabdagi
// umumiy holatini ko'rishi kerak. Shuning uchun hujjat ikki savolga javob
// beradi va uchinchisini ko'tarmaydi:
//   1) raqam  — fanlar kesimida test natijasi, sinf o'rtachasi bilan yonma-yon;
//   2) so'z   — o'qituvchilarning besh yo'nalish bo'yicha sharhi.
//
// MAKTABDA HOZIRCHA CHORAK BAHOSI HAM, YO'QLAMA HAM YO'Q. Yo'q narsa
// uchun ustun ham qoldirilmaydi: bo'sh katak ota-onada «nega bo'sh?»
// savolini tug'diradi va hujjatga ishonchni yo'qotadi.
//
// UCHTA QAT'IY QOIDA:
//
// 1) RANG — CSS FONIDA EMAS, SVG ICHIDA.
//    Brauzerning «Print background graphics» standart holatda O'CHIQ. CSS
//    `background` bilan chizilgan diagramma printerdan OQ chiqadi. SVG
//    `fill` esa rasm sifatida doim bosiladi. Shuning uchun har bir muhim
//    rang — diagramma, daraja belgisi — SVG ichida; CSS ranglar faqat
//    bezak (chiziq, matn) uchun.
//
// 2) RAQAM YONIDA DOIM SO'Z VA QIYOS.
//    «68%» ota-onaga hech narsa demaydi. «68% — O'rta daraja, sinf
//    o'rtachasi 54%» esa darhol ma'noga ega. Shkala ham varaqning
//    o'zida turadi.
//
// 3) MAKET QAT'IY EMAS, OQUVCHAN.
//    Fan soni ham, sharh uzunligi ham oldindan noma'lum. Majburiy bet
//    uzilishi FAQAT o'quvchilar orasida; qolgani `break-inside: avoid`
//    bilan oqadi.
//
// Ma'lumotni `local_targetzenit\hisobot::student()` tayyorlaydi. Bu klass
// so'rov YUBORMAYDI.

namespace local_targetzenit\output;

use local_targetzenit\hisobot;
use local_targetzenit\sharh;

defined('MOODLE_INTERNAL') || die();

class hisobot_varaq {

    /** Maktab rangi — logotipdan olingan (`pix/logo.png`). */
    const NAVY = '#00214a';
    const QIZIL = '#ff1100';

    const INK = '#1d2a38';
    const MUTED = '#5b6b7c';
    const LINE = '#c9d2db';

    /**
     * Daraja rangi.
     *
     * Logotipning QIZILI (#ff1100) past daraja uchun ishlatilmaydi: u
     * brend rangi, undagi «xavf» ma'nosi varaqning sarlavhasiga ham
     * ko'chib o'tardi. Past daraja — bosiqroq g'ishtrang, ya'ni «e'tibor
     * bering», «yomon bola» emas.
     */
    const DARAJA_RANG = ['yuqori' => '#1b7f46', 'orta' => '#1b5f9e', 'past' => '#c2410c'];

    /** Diagramma foni: [dan, gacha, daraja, och rang]. */
    const BANDLAR = [
        [hisobot::DARAJA_YUQORI, 100, 'yuqori', '#e7f3ec'],
        [hisobot::DARAJA_ORTA, hisobot::DARAJA_YUQORI, 'orta', '#e8f1fa'],
        [0, hisobot::DARAJA_ORTA, 'past', '#fdeee7'],
    ];

    /** Radar faqat shu oraliqda ma'noli. */
    const RADAR_MIN = 3;
    const RADAR_MAX = 12;

    /** «Kuchli» / «sust» ro'yxatida nechtagacha fan ko'rsatiladi. */
    const ROYXAT_MAX = 4;

    protected static function t(string $kalit, $a = null): string {
        return get_string($kalit, 'local_targetzenit', $a);
    }

    /** Foizni ko'rsatish: `68%` (kasr qismi faqat kerak bo'lsa). */
    protected static function foiz($f): string {
        if ($f === null) {
            return '—';
        }
        $f = (float) $f;
        return (abs($f - round($f)) < 0.05 ? (string) (int) round($f)
                                           : number_format($f, 1, '.', '')) . '%';
    }

    protected static function daraja_nomi(?string $kod): string {
        return $kod ? self::t('hr_lvl_' . $kod) : self::t('hr_notest');
    }

    protected static function rang(?string $daraja): string {
        return self::DARAJA_RANG[$daraja] ?? self::MUTED;
    }

    // ===================================================== TO'LIQ HUJJAT

    /**
     * Bitta o'quvchining varag'i.
     *
     * @param \stdClass $d `hisobot::student()` natijasi
     * @param bool $oxirgi oxirgi o'quvchimi (guruh rejimida bet uzilishi uchun)
     */
    public static function chiz(\stdClass $d, bool $oxirgi = true): string {
        if (empty($d->oquvchi)) {
            return '';
        }

        $o = \html_writer::start_div('tz-hr-doc' . ($oxirgi ? ' tz-hr-oxirgi' : ''));
        $o .= self::masthead($d);
        $o .= self::shaxs($d);
        $o .= self::kpi($d);
        $o .= self::xulosa($d);
        $o .= self::kuchli_sust($d);
        $o .= self::ustun_diagramma($d);
        $o .= self::radar_va_shkala($d);
        $o .= self::jadval($d);
        $o .= self::sharhlar($d);
        $o .= self::imzo($d);
        $o .= \html_writer::end_div();
        return $o;
    }

    // ============================================================ SARLAVHA

    protected static function masthead(\stdClass $d): string {
        global $OUTPUT;

        $chap = \html_writer::empty_tag('img', [
            'src' => $OUTPUT->image_url('logo', 'local_targetzenit')->out(false),
            'alt' => '', 'class' => 'tz-hr-logo']);

        $ong = \html_writer::div(self::t('hr_year') . ': ' . s($d->year), 'tz-hr-mline')
            . \html_writer::div(s($d->term->name), 'tz-hr-mline tz-hr-strong')
            . \html_writer::div(self::t('hr_printed') . ': '
                . userdate(time(), get_string('strftimedate', 'langconfig')), 'tz-hr-mline');

        return \html_writer::div(
            \html_writer::div($chap, 'tz-hr-mlogo')
            . \html_writer::div(
                \html_writer::tag('h1', self::t('hr_title'), ['class' => 'tz-hr-h1'])
                . \html_writer::div(self::t('hr_subtitle'), 'tz-hr-h1sub'),
                'tz-hr-mtitle')
            . \html_writer::div($ong, 'tz-hr-mmeta'),
            'tz-hr-mast');
    }

    // ======================================================== O'QUVCHI KARTI

    protected static function shaxs(\stdClass $d): string {
        $u = $d->oquvchi;
        $ism = fullname($u);

        $qatorlar = [
            [self::t('hr_group'), $d->guruh !== '' ? $d->guruh : '—'],
            [self::t('hr_tutor'), $d->tutor !== '' ? $d->tutor : '—'],
            [self::t('hr_studentid'), (string) ($u->idnumber ?: '—')],
            [self::t('hr_period'), $d->term->name . ' · '
                . self::sana($d->term->startdate) . ' – ' . self::sana($d->term->enddate)],
        ];
        $matn = '';
        foreach ($qatorlar as $q) {
            $matn .= \html_writer::div(
                \html_writer::tag('span', s($q[0]), ['class' => 'tz-hr-k'])
                . \html_writer::tag('span', s($q[1]), ['class' => 'tz-hr-v']),
                'tz-hr-kv');
        }

        return \html_writer::div(
            \html_writer::div(self::bosh_harf($ism), 'tz-hr-av')
            . \html_writer::div(
                \html_writer::tag('div', s($ism), ['class' => 'tz-hr-name'])
                . \html_writer::div($matn, 'tz-hr-kvs'),
                'tz-hr-who'),
            'tz-hr-card tz-hr-person');
    }

    /** Ism bosh harflari — SVG doira (rasm yo'q o'quvchida ham varaq to'liq). */
    protected static function bosh_harf(string $ism): string {
        $h = '';
        foreach (preg_split('/\s+/u', trim($ism)) as $b) {
            if ($b !== '' && \core_text::strlen($h) < 2) {
                $h .= \core_text::strtoupper(\core_text::substr($b, 0, 1));
            }
        }
        return '<svg class="tz-hr-avsvg" viewBox="0 0 80 80" role="img" aria-hidden="true">'
            . '<circle cx="40" cy="40" r="39" fill="' . self::NAVY . '"/>'
            . '<text x="40" y="40" fill="#ffffff" font-size="30" font-weight="700"'
            . ' text-anchor="middle" dominant-baseline="central"'
            . ' font-family="Arial, Helvetica, sans-serif">' . s($h) . '</text></svg>';
    }

    // ================================================================= KPI

    protected static function kpi(\stdClass $d): string {
        $j = $d->jami;
        $rang = self::rang($j->daraja);

        $plitalar = [];

        // 1. Umumiy natija — varaqning bosh raqami.
        $plitalar[] = [
            self::foiz($j->foiz),
            self::t('hr_kpi_result'),
            $j->daraja ? self::daraja_nomi($j->daraja) : self::t('hr_notest'),
            $rang,
        ];

        // 2-3. Kuchli va sust fan — ota-onaning ASOSIY savoli, shuning
        // uchun u diagrammani o'qishdan OLDIN javobini oladi.
        $plitalar[] = $j->kuchli
            ? [self::qisqart($j->kuchli['fan'], 14), self::t('hr_kpi_best'),
               self::foiz($j->kuchli['foiz']) . ' · ' . self::daraja_nomi($j->kuchli['daraja']),
               self::DARAJA_RANG['yuqori']]
            : ['—', self::t('hr_kpi_best'), self::t('hr_notest'), self::MUTED];

        $plitalar[] = $j->sust
            ? [self::qisqart($j->sust['fan'], 14), self::t('hr_kpi_weak'),
               self::foiz($j->sust['foiz']) . ' · ' . self::daraja_nomi($j->sust['daraja']),
               self::rang($j->sust['daraja'])]
            : ['—', self::t('hr_kpi_weak'), self::t('hr_notest'), self::MUTED];

        // 4. Sinf bilan qiyos — yolg'iz foiz ma'nosiz, o'lchov kerak.
        if ($j->sinf_foiz !== null && $j->foiz !== null) {
            $farq = round($j->foiz - $j->sinf_foiz, 1);
            $plitalar[] = [
                ($farq > 0 ? '+' : '') . self::foiz($farq),
                self::t('hr_kpi_vsclass'),
                self::t('hr_kpi_vsclass_hint', self::foiz($j->sinf_foiz)),
                $farq >= 0 ? self::DARAJA_RANG['yuqori'] : self::DARAJA_RANG['past'],
            ];
        } else {
            $plitalar[] = [(string) $j->testlar, self::t('hr_kpi_tests'),
                self::t('hr_kpi_tests_hint', $j->fan), self::NAVY];
        }

        $o = '';
        foreach ($plitalar as $p) {
            $o .= \html_writer::div(
                '<svg class="tz-hr-tilebar" viewBox="0 0 100 4" preserveAspectRatio="none">'
                . '<rect width="100" height="4" fill="' . $p[3] . '"/></svg>'
                . \html_writer::div(s($p[0]), 'tz-hr-tilenum'
                    . (\core_text::strlen((string) $p[0]) > 6 ? ' tz-hr-tilesm' : ''))
                . \html_writer::div(s($p[1]), 'tz-hr-tilelab')
                . \html_writer::div(s($p[2]), 'tz-hr-tilehint'),
                'tz-hr-tile');
        }
        return \html_writer::div($o, 'tz-hr-tiles');
    }

    // ============================================================== XULOSA

    /**
     * «Umumiy holat» — ikki-uch gapli matn.
     *
     * NEGA MATN: diagrammani hamma ham o'qiy olmaydi, lekin gapni hamma
     * o'qiydi. Bu yerda HECH QANDAY baho berilmaydi («yaxshi o'qiyapti»,
     * «dangasa»), faqat raqamlar gap shakliga o'tkaziladi — xulosani
     * ota-ona o'zi chiqaradi.
     */
    protected static function xulosa(\stdClass $d): string {
        $j = $d->jami;
        $ism = fullname($d->oquvchi);

        if ($j->foiz === null) {
            return \html_writer::div(
                \html_writer::div(self::t('hr_sum_title'), 'tz-hr-sumlab')
                . \html_writer::div(self::t('hr_sum_none'), 'tz-hr-sumtext'),
                'tz-hr-card tz-hr-sum');
        }

        $gaplar = [];
        $gaplar[] = self::t('hr_sum_s1', (object) [
            'ism' => s($ism), 'fan' => $j->fan, 'testlar' => $j->testlar,
            'foiz' => self::foiz($j->foiz), 'daraja' => self::daraja_nomi($j->daraja)]);

        if ($j->kuchli && $j->sust && $j->kuchli['fan'] !== $j->sust['fan']) {
            $gaplar[] = self::t('hr_sum_s2', (object) [
                'kuchli' => s($j->kuchli['fan']), 'kfoiz' => self::foiz($j->kuchli['foiz']),
                'sust' => s($j->sust['fan']), 'sfoiz' => self::foiz($j->sust['foiz'])]);
        }
        if ($j->sinf_foiz !== null) {
            $gaplar[] = self::t('hr_sum_s3', (object) [
                'n' => $j->sinfdan_yuqori, 'fan' => $j->fan,
                'sinf' => self::foiz($j->sinf_foiz)]);
        }
        if ($j->sharh) {
            $gaplar[] = self::t('hr_sum_s4', $j->sharh);
        }

        return \html_writer::div(
            \html_writer::div(self::t('hr_sum_title'), 'tz-hr-sumlab')
            . \html_writer::div(implode(' ', $gaplar), 'tz-hr-sumtext'),
            'tz-hr-card tz-hr-sum');
    }

    // ====================================================== KUCHLI VA SUST

    /**
     * «Kuchli tomonlar» va «Qo'llab-quvvatlash kerak» — ikki ustun.
     *
     * Bu blok hujjatning MAQSADI: ota-ona majlisdan aynan shu ikki
     * ro'yxatni eslab qaytishi kerak.
     *
     * Tanlash qoidasi ATAYLAB sodda va bir xil: daraja. «Past» fanlar
     * o'ngda, «yuqori» fanlar chapda. Agar birorta ham yuqori (yoki past)
     * daraja bo'lmasa — eng yaxshi (eng past) fan ko'rsatiladi, chunki
     * bo'sh ustun savol tug'diradi.
     */
    protected static function kuchli_sust(\stdClass $d): string {
        $bor = array_values(array_filter($d->fanlar, fn($f) => $f['foiz'] !== null));
        if (!$bor) {
            return '';
        }

        $kuchli = array_values(array_filter($bor, fn($f) => $f['daraja'] === 'yuqori'));
        $sust = array_values(array_filter($bor, fn($f) => $f['daraja'] === 'past'));

        if (!$kuchli) {
            $kuchli = [$bor[0]];
        }
        if (!$sust) {
            $oxirgi = $bor[count($bor) - 1];
            // Eng past fan ALLAQACHON kuchlilar ro'yxatida bo'lsa (hammasi
            // yuqori daraja) — o'ng ustun ro'yxat emas, tabrik bo'ladi.
            $sust = ($oxirgi['daraja'] === 'yuqori') ? [] : [$oxirgi];
        }

        $kuchli = array_slice($kuchli, 0, self::ROYXAT_MAX);
        $sust = array_slice($sust, 0, self::ROYXAT_MAX);

        return \html_writer::div(
            self::royxat(self::t('hr_strong'), self::t('hr_strong_hint'),
                $kuchli, 'yuqori', '')
            . self::royxat(self::t('hr_weak'), self::t('hr_weak_hint'),
                $sust, 'past', self::t('hr_allstrong')),
            'tz-hr-row tz-hr-sw');
    }

    protected static function royxat(string $sarlavha, string $izoh, array $fanlar,
                                      string $tur, string $bosh): string {
        $rang = self::DARAJA_RANG[$tur];
        $ichi = '';
        foreach ($fanlar as $f) {
            $ichi .= \html_writer::div(
                \html_writer::div(s($f['fan']), 'tz-hr-swname')
                . \html_writer::div(self::svg_mini((float) $f['foiz'], $f['daraja']),
                    'tz-hr-swbar')
                . \html_writer::div(s(self::foiz($f['foiz'])), 'tz-hr-swpct'),
                'tz-hr-swrow');
        }
        if ($ichi === '') {
            $ichi = \html_writer::div(s($bosh), 'tz-hr-swempty');
        }

        return \html_writer::div(
            \html_writer::div(
                '<svg class="tz-hr-dot" viewBox="0 0 12 12" aria-hidden="true">'
                . '<rect width="12" height="12" rx="3" fill="' . $rang . '"/></svg>'
                . \html_writer::tag('span', s($sarlavha), ['class' => 'tz-hr-swtitle']),
                'tz-hr-swhead')
            . \html_writer::div(s($izoh), 'tz-hr-hint')
            . $ichi,
            'tz-hr-card tz-hr-swcard');
    }

    /** Kichik foiz chizig'i (ro'yxat va jadval uchun). */
    protected static function svg_mini(?float $foiz, ?string $daraja): string {
        $w = 200;
        $s = '<svg class="tz-hr-ulush" viewBox="0 0 ' . $w . ' 12"'
            . ' preserveAspectRatio="none" role="img">'
            . '<rect width="' . $w . '" height="12" rx="2" fill="#eef2f6"/>';
        if ($foiz !== null) {
            $s .= '<rect width="' . round($w * max(0, min(100, $foiz)) / 100, 1)
                . '" height="12" rx="2" fill="' . self::rang($daraja) . '"/>';
        }
        return $s . '</svg>';
    }

    // ========================================================= DIAGRAMMALAR

    protected static function ustun_diagramma(\stdClass $d): string {
        $qatorlar = self::qatorlar($d);
        // Birorta natija yo'q bo'lsa diagramma CHIZILMAYDI: bo'sh o'qlar
        // va «test topshirilmagan» qatorlari varaqni to'ldiradi, lekin
        // hech narsa aytmaydi. Buni «Qisqacha» bloki allaqachon aytgan.
        if (!$qatorlar || $d->jami->foiz === null) {
            return '';
        }
        $sinfbor = (bool) array_filter($qatorlar, fn($q) => $q['sinf'] !== null);

        return \html_writer::div(
            \html_writer::tag('h2', self::t('hr_chart_title'), ['class' => 'tz-hr-h2'])
            . \html_writer::div($sinfbor
                ? self::t('hr_chart_hint') : self::t('hr_chart_hint_nocls'), 'tz-hr-hint')
            . self::svg_bar($qatorlar),
            'tz-hr-card');
    }

    /** Diagramma qatorlari — `hisobot::student()` dagi tartibda (yuqoridan pastga). */
    protected static function qatorlar(\stdClass $d): array {
        $out = [];
        foreach ($d->fanlar as $f) {
            $out[] = ['nom' => $f['fan'], 'foiz' => $f['foiz'],
                      'daraja' => $f['daraja'], 'sinf' => $f['sinf']];
        }
        return $out;
    }

    /**
     * Fanlar bo'yicha gorizontal ustun diagramma + sinf o'rtachasi belgisi.
     *
     * NEGA GORIZONTAL: fan nomlari uzun (`Ona tili va adabiyoti`), vertikal
     * ustunda ular qiyshaytiriladi yoki qisqaradi.
     * NEGA TARTIBLANGAN: eng yuqori natija tepada, eng past pastda —
     * «qaysi fanda sust» savoliga javob diagrammaning SHAKLIDA ko'rinadi.
     */
    protected static function svg_bar(array $qatorlar): string {
        $w = 1000;
        $x0 = 300;
        $x1 = 895;
        $kw = $x1 - $x0;
        $qh = 32;
        $yust = 36;
        $past = 18;
        $h = $yust + count($qatorlar) * $qh + $past;

        $px = fn(float $ball) => $x0 + $kw * max(0, min(100, $ball)) / 100;
        $s = '<svg class="tz-hr-svg" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">';

        // Daraja bandlari fon sifatida (SVG ichida — doim bosiladi)
        foreach (self::BANDLAR as [$dan, $gacha, $kod, $och]) {
            $s .= '<rect x="' . round($px($dan), 1) . '" y="' . $yust
                . '" width="' . round($px($gacha) - $px($dan), 1)
                . '" height="' . (count($qatorlar) * $qh) . '" fill="' . $och . '"/>';
        }
        foreach ([0, hisobot::DARAJA_ORTA, hisobot::DARAJA_YUQORI, 100] as $tik) {
            $x = round($px($tik), 1);
            $s .= '<line x1="' . $x . '" y1="' . $yust . '" x2="' . $x . '" y2="'
                . ($yust + count($qatorlar) * $qh)
                . '" stroke="#ffffff" stroke-width="1.5"/>'
                . '<text x="' . $x . '" y="' . ($yust - 11) . '" font-size="15" fill="'
                . self::MUTED . '" text-anchor="middle"'
                . ' font-family="Arial, Helvetica, sans-serif">' . $tik . '%</text>';
        }

        $y = $yust;
        foreach ($qatorlar as $q) {
            $yc = $y + $qh / 2;

            $s .= '<text x="' . ($x0 - 14) . '" y="' . $yc . '" font-size="16" fill="'
                . self::INK . '" text-anchor="end" dominant-baseline="central"'
                . ' font-family="Arial, Helvetica, sans-serif">'
                . s(self::qisqart($q['nom'], 32)) . '</text>';

            if ($q['foiz'] === null) {
                // Testi yo'q fan YASHIRILMAYDI (ota-ona «nega bu fan yo'q?»
                // demasligi uchun), lekin rangli band ostida qolmaydi.
                $s .= '<rect x="' . $x0 . '" y="' . $y . '" width="' . $kw
                    . '" height="' . $qh . '" fill="#ffffff"/>'
                    . '<text x="' . ($x0 + 10) . '" y="' . $yc . '" font-size="14" fill="'
                    . self::MUTED . '" dominant-baseline="central"'
                    . ' font-family="Arial, Helvetica, sans-serif">'
                    . s(self::t('hr_notest')) . '</text>';
                $y += $qh;
                continue;
            }

            $rang = self::rang($q['daraja']);
            $uzun = max(2, $px((float) $q['foiz']) - $x0);
            $s .= '<rect x="' . $x0 . '" y="' . ($yc - 9) . '" width="' . round($uzun, 1)
                . '" height="18" rx="3" fill="' . $rang . '"/>';

            // SINF O'RTACHASI — uchburchak belgi va nozik chiziq.
            // Ustunning USTIDA turadi, ya'ni ustun uni bekitmaydi.
            if ($q['sinf'] !== null) {
                $xs = round($px((float) $q['sinf']), 1);
                // OQ HOSHIYA: belgi ham rangli ustun ustida, ham och band
                // ustida turadi — ikkala fonda ham ko'rinishi uchun avval
                // qalin oq, ustiga ingichka to'q chiziq chiziladi.
                // Belgi ustunning O'ZIDA turadi: uchburchak tepada, ostida
                // faqat ustun balandligidagi qisqa chiziq. Oq hoshiya —
                // belgi rangli ustunda ham, och bandda ham ko'rinsin.
                $s .= '<line x1="' . $xs . '" y1="' . ($yc - 9) . '" x2="' . $xs
                    . '" y2="' . ($yc + 9) . '" stroke="#ffffff" stroke-width="5"/>'
                    . '<line x1="' . $xs . '" y1="' . ($yc - 9) . '" x2="' . $xs
                    . '" y2="' . ($yc + 9) . '" stroke="' . self::NAVY
                    . '" stroke-width="2.5"/>'
                    . '<polygon points="' . ($xs - 6) . ',' . ($yc - 18) . ' '
                    . ($xs + 6) . ',' . ($yc - 18) . ' ' . $xs . ',' . ($yc - 10)
                    . '" fill="' . self::NAVY . '"/>';
            }

            $s .= '<text x="' . ($x1 + 16) . '" y="' . $yc . '" font-size="17"'
                . ' font-weight="700" fill="' . self::INK . '" dominant-baseline="central"'
                . ' font-family="Arial, Helvetica, sans-serif">'
                . s(self::foiz($q['foiz'])) . '</text>';
            $y += $qh;
        }

        return $s . '</svg>';
    }

    /**
     * Radar va daraja shkalasi — YONMA-YON.
     *
     * Ikkalasi ham kichik blok; alohida turganda varaqning yarmini yeb
     * qo'yadi va jadval keyingi betga tushadi (30 nusxada — 30 ta ortiqcha
     * varaq).
     */
    protected static function radar_va_shkala(\stdClass $d): string {
        $bor = array_values(array_filter(self::qatorlar($d), fn($q) => $q['foiz'] !== null));
        $n = count($bor);

        if (!$n) {
            return '';            // diagramma yo'q — shkala ham keraksiz
        }
        if ($n < self::RADAR_MIN || $n > self::RADAR_MAX) {
            return \html_writer::div(self::shkala(), 'tz-hr-row tz-hr-row1');
        }

        $radar = \html_writer::div(
            \html_writer::tag('h2', self::t('hr_radar_title'), ['class' => 'tz-hr-h2'])
            . \html_writer::div(self::t('hr_radar_hint'), 'tz-hr-hint')
            . self::svg_radar($bor),
            'tz-hr-card tz-hr-radarcard');

        return \html_writer::div($radar . self::shkala(), 'tz-hr-row');
    }

    /** Fanlar muvozanati — ko'pburchak (radar). */
    protected static function svg_radar(array $qatorlar): string {
        $n = count($qatorlar);
        $cx = 290;
        $cy = 198;
        $r = 128;
        $w = 580;
        $h = 382;

        $nuqta = function(int $i, float $ball) use ($cx, $cy, $r, $n) {
            $burchak = -M_PI / 2 + 2 * M_PI * $i / $n;
            $dd = $r * max(0, min(100, $ball)) / 100;
            return [round($cx + $dd * cos($burchak), 1), round($cy + $dd * sin($burchak), 1)];
        };

        $s = '<svg class="tz-hr-svg tz-hr-radar" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">';
        foreach ([25, 50, 75, 100] as $halqa) {
            $p = [];
            for ($i = 0; $i < $n; $i++) {
                $p[] = implode(',', $nuqta($i, $halqa));
            }
            $s .= '<polygon points="' . implode(' ', $p) . '" fill="'
                . ($halqa === 100 ? '#f7f9fb' : 'none') . '" stroke="' . self::LINE
                . '" stroke-width="1"/>';
        }
        for ($i = 0; $i < $n; $i++) {
            [$x, $y] = $nuqta($i, 100);
            $s .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . $x . '" y2="' . $y
                . '" stroke="' . self::LINE . '" stroke-width="1"/>';

            [$lx, $ly] = $nuqta($i, 118);
            $anchor = 'middle';
            if ($lx > $cx + 6) {
                $anchor = 'start';
            } else if ($lx < $cx - 6) {
                $anchor = 'end';
            }
            $s .= '<text x="' . $lx . '" y="' . $ly . '" font-size="19" fill="' . self::INK
                . '" text-anchor="' . $anchor . '" dominant-baseline="central"'
                . ' font-family="Arial, Helvetica, sans-serif">'
                . s(self::qisqart($qatorlar[$i]['nom'], 14)) . '</text>';
        }
        $p = [];
        for ($i = 0; $i < $n; $i++) {
            $p[] = implode(',', $nuqta($i, (float) $qatorlar[$i]['foiz']));
        }
        $s .= '<polygon points="' . implode(' ', $p) . '" fill="' . self::NAVY
            . '" fill-opacity="0.16" stroke="' . self::NAVY . '" stroke-width="2.5"/>';
        for ($i = 0; $i < $n; $i++) {
            [$x, $y] = $nuqta($i, (float) $qatorlar[$i]['foiz']);
            $s .= '<circle cx="' . $x . '" cy="' . $y . '" r="6" fill="'
                . self::rang($qatorlar[$i]['daraja'])
                . '" stroke="#ffffff" stroke-width="1.5"/>';
        }
        return $s . '</svg>';
    }

    // ============================================================== SHKALA

    /** Daraja shkalasi varaqning o'zida — «80% nima degani?» savoli qolmasin. */
    protected static function shkala(): string {
        $o = '';
        foreach (self::BANDLAR as [$dan, $gacha, $kod, $och]) {
            $o .= \html_writer::div(
                '<svg class="tz-hr-dot" viewBox="0 0 12 12" aria-hidden="true">'
                . '<rect width="12" height="12" rx="3" fill="'
                . self::DARAJA_RANG[$kod] . '"/></svg>'
                . \html_writer::tag('b', $dan . '–'
                    . ($gacha >= 100 ? 100 : $gacha - 1) . '%')
                . ' · ' . s(self::daraja_nomi($kod)),
                'tz-hr-scale');
        }
        // Sinf o'rtachasi belgisining izohi — diagrammada u izohsiz
        // tushunarsiz bo'lardi.
        $o .= \html_writer::div(
            '<svg class="tz-hr-dot" viewBox="0 0 12 12" aria-hidden="true">'
            . '<polygon points="1,2 11,2 6,9" fill="' . self::NAVY . '"/></svg>'
            . \html_writer::tag('b', self::t('hr_classavg')),
            'tz-hr-scale');

        return \html_writer::div(
            \html_writer::div($o, 'tz-hr-scales')
            . \html_writer::div(self::t('hr_scale_hint'), 'tz-hr-hint'),
            'tz-hr-card tz-hr-legend');
    }

    // ============================================================== JADVAL

    /** Test tafsiloti — har bir test alohida qator. */
    protected static function jadval(\stdClass $d): string {
        $qatorlar = '';
        foreach ($d->fanlar as $f) {
            foreach ($f['imtihon'] as $r) {
                $maks = (float) $r->maxscore;
                $foiz = $r->zfoiz ?? null;
                $daraja = hisobot::daraja($foiz);

                $qatorlar .= \html_writer::tag('tr',
                    \html_writer::tag('td',
                        \html_writer::tag('b', s($f['fan']))
                        . \html_writer::div(s($f['oqituvchi'] ?: '—'), 'tz-hr-sub'),
                        ['class' => 'tz-hr-fan'])
                    . \html_writer::tag('td',
                        s($r->name)
                        . \html_writer::div(s(self::sana((string) $r->sana)), 'tz-hr-sub'))
                    . \html_writer::tag('td',
                        $r->score !== null
                            ? \html_writer::tag('b', self::raqam($r->score))
                              . ' / ' . self::raqam($maks)
                            : \html_writer::tag('span',
                                s(self::t('bh_holat_' . $r->holat)), ['class' => 'tz-hr-mut']),
                        ['class' => 'tz-hr-num'])
                    . \html_writer::tag('td', self::svg_mini($foiz, $daraja),
                        ['class' => 'tz-hr-ul'])
                    . \html_writer::tag('td',
                        $foiz !== null
                            ? self::chip(self::foiz($foiz) . ' · ' . self::daraja_nomi($daraja),
                                $daraja)
                            : \html_writer::tag('span', '—', ['class' => 'tz-hr-mut']),
                        ['class' => 'tz-hr-num'])
                    . \html_writer::tag('td',
                        $f['sinf'] !== null ? s(self::foiz($f['sinf']))
                            : \html_writer::tag('span', '—', ['class' => 'tz-hr-mut']),
                        ['class' => 'tz-hr-num tz-hr-mut']));
            }
        }
        if ($qatorlar === '') {
            return \html_writer::div(self::t('hr_notests'), 'tz-hr-card tz-hr-empty');
        }

        $bosh = \html_writer::tag('tr',
            \html_writer::tag('th', self::t('hr_col_subject'))
            . \html_writer::tag('th', self::t('hr_col_exam'))
            . \html_writer::tag('th', self::t('hr_col_score'))
            . \html_writer::tag('th', self::t('hr_col_percent'))
            . \html_writer::tag('th', self::t('hr_col_level'))
            . \html_writer::tag('th', self::t('hr_col_class')));

        return \html_writer::div(
            self::yugurish($d)
            . \html_writer::tag('h2', self::t('hr_table_title'), ['class' => 'tz-hr-h2'])
            . \html_writer::div(self::t('hr_table_hint'), 'tz-hr-hint')
            . \html_writer::tag('table',
                \html_writer::tag('thead', $bosh) . \html_writer::tag('tbody', $qatorlar),
                ['class' => 'tz-hr-table']),
            'tz-hr-card tz-hr-flow');
    }

    /** Rangli belgi — SVG emas, matn uzunligi oldindan noma'lum. */
    protected static function chip(string $matn, ?string $daraja): string {
        return \html_writer::tag('span', s($matn),
            ['class' => 'tz-hr-chip', 'style' => 'border-color:' . self::rang($daraja)
                . ';color:' . self::rang($daraja)]);
    }

    protected static function raqam($q): string {
        if ($q === null) {
            return '—';
        }
        return s(rtrim(rtrim(number_format((float) $q, 1, '.', ''), '0'), '.'));
    }

    // ============================================================= SHARHLAR

    /**
     * O'qituvchilar sharhi — varaqning YURAGI.
     *
     * Har fan alohida blok: fan + O'QITUVCHI ISMI + sana, so'ng besh
     * kategoriya. Kategoriya nomi yorliq ustunida turadi — ota-ona
     * «mas'uliyat haqida nima deyilgan?» deb qidirganda yorliqlar
     * ustunining o'zi yo'l ko'rsatadi.
     */
    protected static function sharhlar(\stdClass $d): string {
        $bloklar = '';
        $yorliq = sharh::kategoriyalar_menu();
        $yorliq[sharh::ESKI_KAT] = self::t('sh_kat_' . sharh::ESKI_KAT);
        $tartib = array_merge(sharh::KATEGORIYALAR, [sharh::ESKI_KAT]);

        foreach ($d->fanlar as $f) {
            if (empty($f['sharh'])) {
                continue;
            }
            $tp = $f['sharh'];
            $bosh = $tp['bosh'];

            $qatorlar = '';
            foreach ($tartib as $kat) {
                if (empty($tp['matnlar'][$kat])) {
                    continue;
                }
                $matn = trim(sharh::matn($tp['matnlar'][$kat]));
                if ($matn === '') {
                    continue;
                }
                $qatorlar .= \html_writer::div(
                    \html_writer::div(s($yorliq[$kat] ?? $kat), 'tz-hr-katlab')
                    . \html_writer::div(s($matn), 'tz-hr-kattext'),
                    'tz-hr-kat');
            }
            if ($qatorlar === '') {
                continue;
            }

            $muallif = trim(($bosh->afirstname ?? '') . ' ' . ($bosh->alastname ?? ''));
            $meta = s($muallif !== '' ? $muallif : ($f['oqituvchi'] ?: '—'))
                . ' · ' . s(self::t('hr_teacher_of', $f['fan']));

            // Fan natijasi sharh yonida: o'qituvchining so'zi va raqam bir
            // joyda turganda ota-ona ularni bog'lab o'qiydi.
            $natija = ($f['foiz'] !== null)
                ? self::chip(self::foiz($f['foiz']), $f['daraja']) : '';

            $bloklar .= \html_writer::div(
                \html_writer::div(
                    \html_writer::div(s($f['fan']), 'tz-hr-rsubject')
                    . \html_writer::div($meta, 'tz-hr-rteacher')
                    . \html_writer::div($natija . ' '
                        . \html_writer::tag('span',
                            s(self::sana(date('Y-m-d', (int) $bosh->timecreated))),
                            ['class' => 'tz-hr-rdate']), 'tz-hr-rright'),
                    'tz-hr-rhead')
                . \html_writer::div($qatorlar, 'tz-hr-kats'),
                'tz-hr-card tz-hr-review');
        }

        $ichi = self::yugurish($d)
            . \html_writer::tag('h2', self::t('hr_reviews_title'),
                ['class' => 'tz-hr-h2 tz-hr-hbig'])
            . \html_writer::div(self::t('hr_reviews_hint'), 'tz-hr-hint');

        if ($bloklar === '') {
            $ichi .= \html_writer::div(self::t('hr_noreviews'), 'tz-hr-card tz-hr-empty');
        }

        return \html_writer::div($ichi . $bloklar, 'tz-hr-section');
    }

    // ================================================================ IMZO

    protected static function imzo(\stdClass $d): string {
        $chiziq = fn(string $kim) => \html_writer::div(
            \html_writer::div('', 'tz-hr-line')
            . \html_writer::div(s($kim), 'tz-hr-linelab'), 'tz-hr-sig');

        return \html_writer::div(
            \html_writer::div(self::t('hr_footnote'), 'tz-hr-foot')
            . \html_writer::div(
                $chiziq(self::t('hr_sig_tutor') . ($d->tutor !== '' ? ': ' . $d->tutor : ''))
                . $chiziq(self::t('hr_sig_parent')),
                'tz-hr-sigs'),
            'tz-hr-card tz-hr-sigcard');
    }

    // =============================================================== YORDAM

    /**
     * «Kimning varag'i» satri — bo'lim boshida.
     *
     * Hujjat 2-3 betli va majlisda varaqlar qo'ldan qo'lga o'tadi. Brauzer
     * bosmasida kolontitul yo'q, shuning uchun ikkinchi va uchinchi betdagi
     * bo'lim sarlavhasi yonida ism takrorlanadi.
     */
    protected static function yugurish(\stdClass $d): string {
        $qism = array_filter([fullname($d->oquvchi), $d->guruh, $d->term->name]);
        return \html_writer::div(s(implode(' · ', $qism)), 'tz-hr-runner');
    }

    protected static function sana(string $iso): string {
        $t = strtotime($iso);
        return $t ? userdate($t, get_string('strftimedateshort', 'langconfig')) : $iso;
    }

    protected static function qisqart(string $matn, int $uzunlik): string {
        return (\core_text::strlen($matn) > $uzunlik)
            ? \core_text::substr($matn, 0, $uzunlik - 1) . '…'
            : $matn;
    }
}
