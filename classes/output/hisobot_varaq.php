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

    /** Hero kartasidagi asosiy matn — logotip ko'kidan biroz ochroq. */
    const HERO_INK = '#0b3f7d';
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

    /**
     * Hujjat raqami — SVG gradient `id` lari uchun.
     *
     * NEGA KERAK: guruh rejimida bitta sahifada 30 ta hujjat bo'ladi.
     * `id` HTML'da hujjat bo'ylab yagona, ya'ni «bar-high» gradienti 30
     * marta takrorlansa brauzer birinchisini oladi va qolganlari jim
     * buziladi. Shuning uchun har hujjatning o'z prefiksi bor.
     */
    protected static $uid = 0;

    /** Shu hujjatdagi `id` — `hr3-bar-yuqori`. */
    protected static function id(string $nom): string {
        return 'hr' . self::$uid . '-' . $nom;
    }

    protected static function url(string $nom): string {
        return 'url(#' . self::id($nom) . ')';
    }

    /**
     * GRADIENT VA SOYALAR — hujjat boshida bir marta.
     *
     * Hajm (3D his) CSS bilan EMAS, SVG bilan beriladi: CSS soyasi va
     * gradienti qog'ozga tushmaydi («Print background graphics» o'chiq),
     * SVG esa rasm sifatida doim bosiladi. Shuning uchun har bir
     * gradient shu yerda e'lon qilinadi va diagrammalar unga `url(#...)`
     * bilan murojaat qiladi — bitta joyda turgani uchun ranglar hamma
     * diagrammada bir xil bo'ladi.
     */
    protected static function defs(): string {
        $g = '';
        foreach (self::DARAJA_RANG as $kod => $rang) {
            // Ustunning yuqori qirrasi ochroq, pasti to'qroq — yorug'lik
            // tepadan tushgandek. Shu bitta narsa tekis ustunga hajm beradi.
            $g .= '<linearGradient id="' . self::id('bar-' . $kod) . '" x1="0" y1="0"'
                . ' x2="0" y2="1">'
                . '<stop offset="0" stop-color="' . self::ochroq($rang, 0.30) . '"/>'
                . '<stop offset="0.45" stop-color="' . $rang . '"/>'
                . '<stop offset="1" stop-color="' . self::toqroq($rang, 0.18) . '"/>'
                . '</linearGradient>';
            // Halqa endi YORUG' fonda turadi, ya'ni daraja rangining o'zi
            // to'g'ri keladi — yorqin variant oqarib ketardi.
            $g .= '<linearGradient id="' . self::id('arc-' . $kod) . '" x1="0" y1="0"'
                . ' x2="1" y2="1">'
                . '<stop offset="0" stop-color="' . self::ochroq($rang, 0.18) . '"/>'
                . '<stop offset="1" stop-color="' . self::toqroq($rang, 0.10) . '"/>'
                . '</linearGradient>';
        }
        // HERO FONI — OQQA YAQIN.
        //
        // Ikki marta ochartirildi va oxirida umuman yorug' qilindi
        // (foydalanuvchi talabi 2026-11-13: «A4 ga chiqarganda real aniq
        // ko'rinsin»). Sabab texnik, did emas:
        //   · to'q maydon lazer/siyoh printerda ko'p bo'yoq yeydi va
        //     yo'l-yo'l (banding) bo'lib chiqadi;
        //   · OQ matn to'q fonda bosilganda harf chetida bo'yoq yoyilib,
        //     mayda yozuv «semirib» ketadi — to'q matn oq fonda esa
        //     har doim tiniqroq.
        // Shuning uchun hero endi yorug' karta: fon oqqa yaqin, matn to'q
        // ko'k. Brend esa chap chetdagi qizil tasma va logotipda qoladi.
        $g .= '<linearGradient id="' . self::id('hero') . '" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="#ffffff"/>'
            . '<stop offset="1" stop-color="#eff6fd"/></linearGradient>';
        $g .= '<linearGradient id="' . self::id('track') . '" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0" stop-color="#dfe6ee"/>'
            . '<stop offset="1" stop-color="#f2f6fa"/></linearGradient>';
        $g .= '<linearGradient id="' . self::id('radar') . '" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0" stop-color="#1b5f9e" stop-opacity="0.34"/>'
            . '<stop offset="1" stop-color="#00214a" stop-opacity="0.12"/></linearGradient>';
        $g .= '<filter id="' . self::id('soya') . '" x="-20%" y="-40%"'
            . ' width="140%" height="200%">'
            // Soya ataylab JUDA YENGIL: qog'ozda kuchli soya ustun
            // atrofida kulrang halqa bo'lib chiqadi va chekkani loyqalatadi.
            . '<feDropShadow dx="0" dy="1.2" stdDeviation="1.1"'
            . ' flood-color="#00214a" flood-opacity="0.16"/></filter>';
        $g .= '<filter id="' . self::id('soya2') . '" x="-30%" y="-30%"'
            . ' width="160%" height="160%">'
            . '<feDropShadow dx="0" dy="3" stdDeviation="4"'
            . ' flood-color="#000000" flood-opacity="0.35"/></filter>';

        return '<svg class="tz-hr-defs" width="0" height="0" aria-hidden="true">'
            . '<defs>' . $g . '</defs></svg>';
    }

    /** Rangni oqartiradi (gradientning yuqori qirrasi uchun). */
    protected static function ochroq(string $hex, float $k): string {
        return self::aralash($hex, 255, $k);
    }

    /** Rangni qoraytiradi (gradientning pastki qirrasi uchun). */
    protected static function toqroq(string $hex, float $k): string {
        return self::aralash($hex, 0, $k);
    }

    protected static function aralash(string $hex, int $maqsad, float $k): string {
        $hex = ltrim($hex, '#');
        $out = '#';
        for ($i = 0; $i < 3; $i++) {
            $c = hexdec(substr($hex, $i * 2, 2));
            $out .= str_pad(dechex((int) round($c + ($maqsad - $c) * $k)), 2, '0', STR_PAD_LEFT);
        }
        return $out;
    }

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

        self::$uid++;
        $o = \html_writer::start_div('tz-hr-doc' . ($oxirgi ? ' tz-hr-oxirgi' : ''));
        $o .= self::defs();
        $o .= self::masthead($d);
        $o .= self::hero($d);
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

    // ================================================================ HERO

    /**
     * BOSH BLOK: o'quvchi + umumiy natija + uchta asosiy raqam.
     *
     * NEGA BITTA KATTA SVG: bu yerda gradient fon, oq matn va halqali
     * diagramma bor — ularning hammasi CSS bilan qilinsa qog'ozda YO'QOLADI
     * (brauzer fonni bosmaydi) va varaqning yuqori qismi bo'm-bo'sh chiqadi.
     * SVG esa rasm: qanday ko'rinsa, shunday bosiladi. Shuning uchun blokning
     * O'ZI diagramma qilib chizilgan.
     *
     * Ichidagi tartib tasodifiy emas: ota-ona varaqni qo'lga olganda
     * birinchi ko'radigan narsa — bolaning ismi va bitta katta raqam,
     * keyin «qaysi fan kuchli / qaysi fan sust».
     */
    protected static function hero(\stdClass $d): string {
        $j = $d->jami;
        $u = $d->oquvchi;
        $w = 1000;
        $h = 276;
        $rang = self::rang($j->daraja);

        $s = '<svg class="tz-hr-hero" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">';
        $s .= '<rect x="1" y="1" width="' . ($w - 2) . '" height="' . ($h - 2)
            . '" rx="16" fill="' . self::url('hero') . '" stroke="#cfdff0"'
            . ' stroke-width="1.5"/>';

        // Fondagi yumshoq shakllar — juda past shaffoflikda: ular maydonga
        // chuqurlik beradi, lekin bosmada deyarli bo'yoq yemaydi.
        $s .= '<circle cx="880" cy="20" r="170" fill="' . self::NAVY
            . '" fill-opacity="0.04"/>'
            . '<circle cx="880" cy="20" r="110" fill="' . self::NAVY
            . '" fill-opacity="0.035"/>'
            . '<circle cx="70" cy="286" r="130" fill="' . self::NAVY
            . '" fill-opacity="0.03"/>'
            . '<rect x="1" y="1" width="7" height="' . ($h - 2) . '" rx="3.5" fill="'
            . self::QIZIL . '"/>';

        // --- avatar
        $bosh = '';
        foreach (preg_split('/\s+/u', trim(fullname($u))) as $qism) {
            if ($qism !== '' && \core_text::strlen($bosh) < 2) {
                $bosh .= \core_text::strtoupper(\core_text::substr($qism, 0, 1));
            }
        }
        // Avatar — kichik to'q doira. Bitta mayda maydon bosmaga og'irlik
        // qilmaydi, lekin varaqqa urg'u beradi.
        $s .= '<circle cx="92" cy="92" r="46" fill="' . self::HERO_INK . '"/>'
            . self::matn($bosh, 92, 92, 34, '#ffffff', 'middle', 700);

        // --- ism va ma'lumot
        // ISM TO'LIQ YOZILADI — hech qachon qisqartirilmaydi.
        //
        // Avval uzun ism «…» bilan kesilardi. Bu shunchaki maket emas:
        // hujjat ota-onaga beriladi va farzandining ismi to'liq turishi
        // hurmat belgisi (foydalanuvchi talabi 2026-11-13). Shuning uchun
        // matn kesilmaydi — SHRIFT KICHRAYADI, ism esa butun qoladi.
        $s .= self::matn(fullname($u), 160, 74,
            self::olcham(fullname($u), 560, 38, 20), self::HERO_INK, 'start', 800);

        $qator2 = array_filter([
            $d->guruh !== '' ? $d->guruh : null,
            $d->tutor !== '' ? self::t('hr_tutor') . ': ' . $d->tutor : null,
        ]);
        // Sinf rahbarining ismi ham kesilmaydi — xuddi shu sabab.
        $qator2 = implode('   ·   ', $qator2);
        $s .= self::matn($qator2, 160, 118, self::olcham($qator2, 600, 21, 13),
            '#14559b', 'start', 600);

        $qator3 = array_filter([
            $u->idnumber ? self::t('hr_studentid') . ': ' . $u->idnumber : null,
            $d->term->name,
            self::sana($d->term->startdate) . ' – ' . self::sana($d->term->enddate),
        ]);
        $qator3 = implode('   ·   ', $qator3);
        $s .= self::matn($qator3, 160, 150, self::olcham($qator3, 600, 16, 10),
            self::MUTED, 'start', 400);

        // --- halqali diagramma (umumiy natija)
        $s .= self::gauge(860, 104, 70, $j->foiz, $j->daraja, $rang);

        // --- ajratuvchi va uchta raqam
        $s .= '<line x1="40" y1="186" x2="960" y2="186" stroke="#cfdff0"'
            . ' stroke-width="1.5"/>';

        $stat = [];
        $stat[] = $j->kuchli
            ? [self::t('hr_kpi_best'), self::qisqart($j->kuchli['fan'], 16),
               self::foiz($j->kuchli['foiz']) . ' · ' . self::daraja_nomi($j->kuchli['daraja']),
               self::DARAJA_RANG['yuqori']]
            : [self::t('hr_kpi_best'), '—', self::t('hr_notest'), '#b6cde8'];
        $stat[] = $j->sust
            ? [self::t('hr_kpi_weak'), self::qisqart($j->sust['fan'], 16),
               self::foiz($j->sust['foiz']) . ' · ' . self::daraja_nomi($j->sust['daraja']),
               self::rang($j->sust['daraja'])]
            : [self::t('hr_kpi_weak'), '—', self::t('hr_notest'), '#b6cde8'];

        if ($j->sinf_foiz !== null && $j->foiz !== null) {
            $farq = round($j->foiz - $j->sinf_foiz, 1);
            $stat[] = [self::t('hr_kpi_vsclass'),
                ($farq > 0 ? '+' : '') . self::foiz($farq),
                self::t('hr_kpi_vsclass_hint', self::foiz($j->sinf_foiz)),
                self::DARAJA_RANG[$farq >= 0 ? 'yuqori' : 'past']];
        } else {
            $stat[] = [self::t('hr_kpi_tests'), (string) $j->testlar,
                self::t('hr_kpi_tests_hint', $j->fan), self::HERO_INK];
        }

        $x = 46;
        foreach ($stat as $i => $st) {
            if ($i > 0) {
                $s .= '<line x1="' . ($x - 24) . '" y1="206" x2="' . ($x - 24)
                    . '" y2="264" stroke="#cfdff0" stroke-width="1.5"/>';
            }
            $s .= self::matn($st[0], $x, 220, 15, self::MUTED, 'start', 700, 1.1)
                . self::matn($st[1], $x, 248, 26, $st[3], 'start', 800)
                . self::matn($st[2], $x, 268, 15, self::MUTED, 'start', 400);
            $x += 304;
        }

        return $s . '</svg>';
    }

    /**
     * Halqali ko'rsatkich (gauge) — umumiy natija.
     *
     * Doira `stroke-dasharray` bilan to'ldiriladi: yoyni `path` bilan
     * chizishdan sodda va burchak hisobida xato qilish imkoni yo'q.
     */
    protected static function gauge(int $cx, int $cy, int $r, ?float $foiz,
                                     ?string $daraja, string $rang): string {
        $aylana = 2 * M_PI * $r;
        $uzun = $foiz === null ? 0 : $aylana * max(0, min(100, $foiz)) / 100;

        $s = '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none"'
            . ' stroke="#dbe7f4" stroke-width="17"/>';
        if ($uzun > 0) {
            $s .= '<g>'
                . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none"'
                . ' stroke="' . self::url('arc-' . $daraja) . '" stroke-width="17"'
                . ' stroke-linecap="round" stroke-dasharray="' . round($uzun, 1) . ' '
                . round($aylana, 1) . '"'
                . ' transform="rotate(-90 ' . $cx . ' ' . $cy . ')"/></g>';
        }
        // Uchala satr HALQA ICHIDA: tashqarida ular hero chetiga yoki
        // ajratuvchi chiziqqa urilib qolardi.
        $s .= self::matn(\core_text::strtoupper(self::t('hr_kpi_result')),
                $cx, $cy - 33, 11, self::MUTED, 'middle', 700)
            . self::matn($foiz === null ? '—' : self::foiz($foiz),
                $cx, $cy + 2, 44, self::HERO_INK, 'middle', 800)
            // Natija yo'q bo'lsa halqa ichida FAQAT chiziqcha qoladi:
            // «test topshirilmagan» matni doiradan oshib ketardi, buni
            // «Qisqacha» bloki baribir to'liq gap bilan aytadi.
            . ($foiz === null ? ''
                : self::matn(self::daraja_nomi($daraja), $cx, $cy + 40, 17,
                    self::rang($daraja), 'middle', 700));
        return $s;
    }

    /**
     * Matn berilgan kenglikka sig'adigan shrift o'lchami.
     *
     * SVG matni o'z-o'zidan o'ralmaydi va sig'masa chetidan chiqib ketadi.
     * Brauzerdan haqiqiy kenglikni so'rab bo'lmaydi (server tomonida
     * chizilmoqda), shuning uchun baho: Arial bold harfning o'rtacha
     * kengligi ~0.62 em. Baho ataylab EHTIYOTKOR (biroz kattaroq) —
     * natijada matn kerakdan bir oz kichik chiqishi mumkin, lekin hech
     * qachon chetga chiqmaydi.
     *
     * 0.58 bilan boshlangan edi va «Muhammadsobira Abdurahmonova» (28 ta
     * harf) halqaga tegib ketdi: keng harflar (m, b, d) ko'p bo'lsa
     * o'rtacha kenglik oshadi. 0.62 + kichikroq maydon bilan 40 harfli
     * ism ham sig'adi.
     *
     * @param string $matn matn
     * @param float $kenglik mavjud joy (viewBox birligida)
     * @param int $maks odatdagi o'lcham
     * @param int $min bundan kichraymaydi (o'qilishi kerak)
     */
    protected static function olcham(string $matn, float $kenglik, int $maks,
                                      int $min): int {
        $uzunlik = max(1, \core_text::strlen($matn));
        $kerak = (int) floor($kenglik / ($uzunlik * 0.62));
        return max($min, min($maks, $kerak));
    }

    /** SVG matn — takrorlanadigan atributlarni bir joyga yig'adi. */
    protected static function matn(string $matn, $x, $y, int $olcham, string $rang,
                                    string $anchor = 'start', int $ogirlik = 400,
                                    float $sp = 0): string {
        return '<text x="' . $x . '" y="' . $y . '" font-size="' . $olcham
            . '" fill="' . $rang . '" text-anchor="' . $anchor
            . '" font-weight="' . $ogirlik . '"'
            . ($sp ? ' letter-spacing="' . $sp . '"' : '')
            . ' dominant-baseline="' . ($anchor === 'middle' && $olcham > 20
                ? 'central' : 'alphabetic') . '"'
            . ' font-family="Arial, Helvetica, sans-serif">' . s($matn) . '</text>';
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
                \html_writer::div(
                    hisobot_ikona::nishon($f['fan'], 22)
                    . \html_writer::tag('span', s($f['fan'])), 'tz-hr-swname')
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
        $s = '<svg class="tz-hr-ulush" viewBox="0 0 ' . $w . ' 14"'
            . ' preserveAspectRatio="none" role="img">'
            . '<rect width="' . $w . '" height="14" rx="7" fill="' . self::url('track') . '"/>';
        if ($foiz !== null) {
            $uzun = round(max(14, $w * max(0, min(100, $foiz)) / 100), 1);
            $s .= '<rect width="' . $uzun . '" height="14" rx="7" fill="'
                . self::url('bar-' . $daraja) . '"/>'
                . '<rect x="4" y="3" width="' . round(max(2, $uzun - 8), 1)
                . '" height="3" rx="1.5" fill="#ffffff" fill-opacity="0.30"/>';
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
            . self::svg_bar($qatorlar)
            // Shkala aynan SHU diagrammani tushuntiradi, shuning uchun u
            // alohida karta emas, diagrammaning tagidagi tasma. Alohida
            // turganda u «yana bir blok» bo'lib, varaqni uzaytirardi.
            . self::shkala(),
            'tz-hr-card');
    }

    /** Diagramma qatorlari — `hisobot::student()` dagi tartibda (yuqoridan pastga). */
    protected static function qatorlar(\stdClass $d): array {
        $out = [];
        foreach ($d->fanlar as $cid => $f) {
            // TESTI YO'Q FAN DIAGRAMMAGA TUSHMAYDI (talab 2026-11-13).
            //
            // Avval bunday fan bo'sh qator bo'lib turardi («nega bu fan
            // yo'q?» degan savol bo'lmasin deb). Amalda esa jadvalda
            // 24 ta fan bor va ko'pchiligidan test olinmagan — natijada
            // diagramma bo'sh qatorlar bilan to'lib, mavjud natijalar
            // ko'rinmay qolardi. Fanning to'liq ro'yxati baribir
            // «Testlar tafsiloti» jadvalida.
            if ($f['foiz'] === null) {
                continue;
            }
            $out[] = ['nom' => $f['fan'], 'foiz' => $f['foiz'],
                      'daraja' => $f['daraja'], 'sinf' => $f['sinf'],
                      'cid' => (int) $cid, 'sharhli' => !empty($f['sharh'])];
        }
        return $out;
    }

    /** Fan sharhi blokining langari — diagrammadan unga o'tiladi. */
    protected static function langar(int $cid): string {
        return self::id('fan-' . $cid);
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
        $qh = 34;
        $yust = 40;
        $past = 14;
        $h = $yust + count($qatorlar) * $qh + $past;
        $pastki = $yust + count($qatorlar) * $qh;

        $px = fn(float $ball) => $x0 + $kw * max(0, min(100, $ball)) / 100;
        $s = '<svg class="tz-hr-svg" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">';

        // DARAJA CHEGARALARI — rangli maydon emas, nozik uzuq chiziq.
        //
        // Avval butun trek uchta rangli bandga bo'lingan edi; har qatorda
        // uch rang takrorlanib, ustunning O'Z rangi ularning ichida
        // yo'qolib ketardi. Chiziq bir xil ma'noni beradi va diagramma
        // toza qoladi — daraja baribir ustun rangi, belgisi va shkalada bor.
        foreach ([hisobot::DARAJA_ORTA, hisobot::DARAJA_YUQORI] as $tik) {
            $x = round($px($tik), 1);
            $s .= '<line x1="' . $x . '" y1="' . ($yust - 4) . '" x2="' . $x . '" y2="'
                . $pastki . '" stroke="#c9d2db" stroke-width="1.2"'
                . ' stroke-dasharray="4 4"/>';
        }
        foreach ([0, hisobot::DARAJA_ORTA, hisobot::DARAJA_YUQORI, 100] as $tik) {
            $s .= self::matn($tik . '%', round($px($tik), 1), $yust - 14, 15,
                self::MUTED, 'middle', 600);
        }

        $y = $yust;
        foreach ($qatorlar as $q) {
            $yc = $y + $qh / 2;

            // Har qator alohida GURUH: `<title>` shu guruhga tegishli
            // bo'ladi, ya'ni izoh aynan ustiga kelingan fan uchun chiqadi.
            // Ildiz `<svg>` ga qo'yilsa butun diagrammaning nomi bo'lib
            // qolardi.
            $izoh = $q['nom'] . ': '
                . ($q['foiz'] === null
                    ? self::t('hr_notest')
                    : self::foiz($q['foiz']) . ' · ' . self::daraja_nomi($q['daraja'])
                      . ($q['sinf'] !== null
                          ? ' · ' . self::t('hr_classavg') . ' ' . self::foiz($q['sinf'])
                          : ''));
            // SHARHGA HAVOLA: ekranda fan nomiga bosilsa shu fanning
            // o'qituvchi sharhiga o'tadi. Bu diagrammani «o'qiladigan»
            // qiladi — ota-ona past natijani ko'rib, darhol o'qituvchi
            // nima deganini o'qiy oladi. Bosmada havola zararsiz.
            $havola = $q['sharhli'];
            $s .= $havola ? '<a href="#' . self::langar($q['cid']) . '">' : '';
            $s .= '<g class="tz-hr-qator' . ($havola ? ' tz-hr-bosiladi' : '')
                . '"><title>' . s($izoh)
                . ($havola ? ' · ' . s(self::t('hr_gotoreview')) : '') . '</title>';
            // Ko'rinmas «ushlash maydoni»: sichqoncha qatorning istalgan
            // joyiga kelganda izoh chiqadi, faqat ustunning o'ziga emas.
            $s .= '<rect class="tz-hr-hit" x="0" y="' . $y . '" width="' . $w
                . '" height="' . $qh . '" rx="8" fill="' . self::NAVY
                . '" fill-opacity="0"/>';

            // Fan belgisi — trekning chap chetida, ustun bilan bir qatorda.
            // Doimiy ustunda turgani uchun ko'z ularni pastga qarab
            // «o'qiy» oladi va fan nomini o'qimay ham taniydi.
            $s .= hisobot_ikona::nishon_svg($q['nom'], $x0 - 42, $yc - 14, 28);
            $s .= self::matn(self::qisqart($q['nom'], 27), $x0 - 52, $yc + 6, 17,
                self::INK, 'end', 650);

            // Trek — yengil gradient, yumaloq uchli. Ustun bo'sh joyda ham
            // «qancha qolgani» ko'rinib tursin.
            $s .= '<rect x="' . $x0 . '" y="' . ($yc - 11) . '" width="' . $kw
                . '" height="22" rx="11" fill="' . self::url('track') . '"/>';

            if ($q['foiz'] === null) {
                $s .= self::matn(self::t('hr_notest'), $x0 + 14, $yc + 5, 14,
                    self::MUTED, 'start', 400) . '</g>' . ($havola ? '</a>' : '');
                $y += $qh;
                continue;
            }

            $uzun = max(22, $px((float) $q['foiz']) - $x0);
            $grad = self::url('bar-' . $q['daraja']);

            // Ustun + soya + yuqori qirradagi yorug'lik chizig'i: shu
            // uchtasi birgalikda tekis to'rtburchakka hajm beradi.
            $s .= '<g filter="' . self::url('soya') . '">'
                . '<rect x="' . $x0 . '" y="' . ($yc - 11) . '" width="' . round($uzun, 1)
                . '" height="22" rx="11" fill="' . $grad . '"/></g>'
                . '<rect x="' . ($x0 + 6) . '" y="' . ($yc - 7) . '" width="'
                . round(max(2, $uzun - 12), 1) . '" height="4" rx="2"'
                . ' fill="#ffffff" fill-opacity="0.28"/>';

            // SINF O'RTACHASI — uchburchak va qisqa chiziq, oq hoshiya bilan
            // (ham rangli ustun, ham och trek ustida ko'rinishi kerak).
            if ($q['sinf'] !== null) {
                $xs = round($px((float) $q['sinf']), 1);
                $s .= '<line x1="' . $xs . '" y1="' . ($yc - 11) . '" x2="' . $xs
                    . '" y2="' . ($yc + 11) . '" stroke="#ffffff" stroke-width="5"/>'
                    . '<line x1="' . $xs . '" y1="' . ($yc - 11) . '" x2="' . $xs
                    . '" y2="' . ($yc + 11) . '" stroke="' . self::NAVY
                    . '" stroke-width="2.5"/>'
                    . '<polygon points="' . ($xs - 6) . ',' . ($yc - 20) . ' '
                    . ($xs + 6) . ',' . ($yc - 20) . ' ' . $xs . ',' . ($yc - 12)
                    . '" fill="' . self::NAVY . '"/>';
            }

            $s .= self::matn(self::foiz($q['foiz']), $x1 + 16, $yc + 6, 18,
                self::INK, 'start', 800) . '</g>' . ($havola ? '</a>' : '');
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

        // Radar ikki fanda ko'pburchak bo'lmaydi, 12 tadan ortiqda esa
        // yorliqlar bir-birining ustiga chiqadi — ikkala holatda ham
        // chizilmaydi.
        if ($n < self::RADAR_MIN || $n > self::RADAR_MAX) {
            return '';
        }

        // TARQOQLIK — «muvozanat» so'zining raqamli o'lchovi. Shaklga
        // qarab «keng/tor» deyish subyektiv, farqni esa ota-ona o'zi
        // tekshira oladi: eng yuqori va eng past fan orasidagi masofa.
        $foizlar = array_map(fn($q) => (float) $q['foiz'], $bor);
        $tarqoq = (int) round(max($foizlar) - min($foizlar));
        $baho = $tarqoq <= 15 ? 'tekis' : ($tarqoq <= 35 ? 'orta' : 'katta');

        return \html_writer::div(
            \html_writer::div(
                \html_writer::tag('h2', self::t('hr_radar_title'), ['class' => 'tz-hr-h2'])
                . \html_writer::div(self::t('hr_radar_hint'), 'tz-hr-hint')
                . \html_writer::div(
                    \html_writer::tag('span', s(self::t('hr_spread')), ['class' => 'tz-hr-spk'])
                    . \html_writer::tag('span', $tarqoq . '%', ['class' => 'tz-hr-spv'])
                    . \html_writer::div(s(self::t('hr_spread_' . $baho)), 'tz-hr-spw'),
                    'tz-hr-spread'),
                'tz-hr-radartext')
            . \html_writer::div(self::svg_radar($bor), 'tz-hr-radarwrap'),
            'tz-hr-card tz-hr-radarcard');
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
        $s .= '<polygon points="' . implode(' ', $p) . '" fill="' . self::url('radar')
            . '" stroke="' . self::NAVY . '" stroke-width="2.5"'
            . ' stroke-linejoin="round" filter="' . self::url('soya') . '"/>';
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
            'tz-hr-legend');
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
                        \html_writer::div(
                            hisobot_ikona::nishon($f['fan'], 24)
                            . \html_writer::div(
                                \html_writer::tag('b', s($f['fan']))
                                . \html_writer::div(s($f['oqituvchi'] ?: '—'),
                                    'tz-hr-sub')),
                            'tz-hr-fancell'),
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

            // Kategoriya belgilari SHU FANNING rangida chiziladi: besh
            // qator bir fanga tegishli ekani ko'rinib tursin va varaq
            // bo'ylab har fan o'z rangi bilan tanilsin.
            $fanrang = hisobot_ikona::rang($f['fan']);

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
                    \html_writer::div(
                        hisobot_ikona::kat($kat, $fanrang)
                        . \html_writer::tag('span', s($yorliq[$kat] ?? $kat)),
                        'tz-hr-katlab')
                    . \html_writer::div(s($matn), 'tz-hr-kattext'),
                    'tz-hr-kat');
            }
            if ($qatorlar === '') {
                continue;
            }

            $muallif = trim(($bosh->afirstname ?? '') . ' ' . ($bosh->alastname ?? ''));
            if ($muallif === '') {
                $muallif = $f['oqituvchi'] ?: '—';
            }

            // SHARH YONIDA FOIZ KO'RSATILMAYDI (aniqlashtirildi 2026-11-13).
            //
            // Avval shu yerda fanning test foizi turardi. Bu YOLG'ON bog'lanish
            // yasardi: o'qituvchi sharhni test baliga qarab emas, butun chorak
            // davomida darsda kuzatganidan kelib chiqib yozadi. Raqam yonida
            // turganda esa ota-ona «40% olgani uchun shunday yozibdi» deb
            // o'qiydi va sharhning o'z qiymati yo'qoladi.
            //
            // Raqam varaqning yuqori qismida — diagramma va jadvalda; sharh
            // esa ALOHIDA dalil sifatida turadi.

            $bloklar .= \html_writer::start_div('tz-hr-card tz-hr-review',
                ['id' => self::langar((int) $f['courseid']),
                 'style' => '--fan: ' . $fanrang]);
            $bloklar .= \html_writer::div(
                \html_writer::div(
                    // To'ldirilgan nishon — fan nomi yonida; o'qituvchi
                    // ismi oldida esa kichik, ochiq nishon (foydalanuvchi
                    // talabi 2026-11-13): kim qaysi fandan dars berishi
                    // ismning o'zidan ko'rinib tursin.
                    \html_writer::div(hisobot_ikona::nishon($f['fan'], 30, true),
                        'tz-hr-rbadge')
                    . \html_writer::div(s($f['fan']), 'tz-hr-rsubject')
                    . \html_writer::div(
                        hisobot_ikona::nishon($f['fan'], 18)
                        . \html_writer::tag('span', s($muallif), ['class' => 'tz-hr-rname'])
                        . \html_writer::tag('span',
                            ' · ' . s(self::t('hr_teacher_of', $f['fan']))),
                        'tz-hr-rteacher')
                    . \html_writer::div(
                        \html_writer::tag('span',
                            s(self::sana(date('Y-m-d', (int) $bosh->timecreated))),
                            ['class' => 'tz-hr-rdate']), 'tz-hr-rright'),
                    'tz-hr-rhead')
                . \html_writer::div($qatorlar, 'tz-hr-kats'),
                'tz-hr-rbody');
            $bloklar .= \html_writer::end_div();
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
