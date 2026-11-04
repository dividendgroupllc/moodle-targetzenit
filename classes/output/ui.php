<?php
// Panel chizuvchi elementlari — YAGONA joy.
//
// NEGA ALOHIDA SINF: bu elementlar (chip, halqa, chiziq, sparkline, plita)
// direktor panelida ham, o'qituvchi panelida ham AYNI BIR XIL ko'rinishi
// kerak. Ular avval `direktor.php` ichida global funksiya edi; ikkinchi
// sahifa paydo bo'lishi bilan nusxalash yo'li yopildi — rang shkalasi yoki
// norma belgisi o'zgarganda ikki joyni yangilash esdan chiqadi.
//
// `direktor.php` dagi `tz_*` funksiyalari shu sinfga o'giriladi: ko'rinish
// fayllari (`views/*.php`) o'sha nomlar bilan ishlayveradi.

namespace local_targetzenit\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_targetzenit\dashboard;

class ui {

    /** Holat chipi: rang + MATN (rang yolg'iz ma'no tashlamaydi). */
    public static function chip(string $status, string $matn, string $title = ''): string {
        return html_writer::tag('span', s($matn),
            ['class' => 'tz-dir-chip tz-dir-' . $status, 'title' => $title]);
    }

    /** Ism bosh harflari — avatar uchun. */
    public static function av(string $ism): string {
        $q = preg_split('/\s+/', trim($ism)) ?: [];
        $b = '';
        foreach (array_slice($q, 0, 2) as $w) {
            $b .= \core_text::substr($w, 0, 1);
        }
        return html_writer::tag('span', s($b ?: '—'), ['class' => 'tz-dir-av']);
    }

    /**
     * Ball halqasi. Rang NORMAGA NISBATAN — mutlaq shkalada emas.
     */
    public static function donut(?int $ball): string {
        if ($ball === null) {
            return html_writer::tag('span', '—', ['class' => 'tz-dir-sub']);
        }
        $band = dashboard::band($ball);
        $rang = ['good' => 'var(--tz-good)', 'warning' => 'var(--tz-warning)',
                 'serious' => 'var(--tz-serious)', 'critical' => 'var(--tz-critical)'][$band['status']]
            ?? 'var(--tz-brand)';
        $c = 94.2;                       // 2*pi*15
        $t = round($c * min(100, max(0, $ball)) / 100, 1);

        $svg = '<svg width="38" height="38" viewBox="0 0 38 38" aria-hidden="true">'
            . '<circle cx="19" cy="19" r="15" fill="none" stroke="var(--tz-line)" stroke-width="4"/>'
            . '<circle cx="19" cy="19" r="15" fill="none" stroke="' . $rang . '" stroke-width="4"'
            . ' stroke-linecap="round" stroke-dasharray="' . $t . ' ' . ($c - $t) . '"'
            . ' transform="rotate(-90 19 19)"/>'
            . '<text x="19" y="22.5" text-anchor="middle" font-size="10" font-weight="700"'
            . ' fill="currentColor">' . (int) $ball . '</text></svg>';

        return html_writer::div($svg . html_writer::div(
            html_writer::tag('b', $ball . '%')
            . html_writer::div(get_string('dir_overall', 'local_targetzenit'), 'tz-dir-sub')),
            'tz-dir-donut');
    }

    /** Normaga nisbatan chiziq: to'ldirish + norma belgisi. */
    public static function bar(?float $ball, ?int $norma = null): string {
        if ($ball === null) {
            return '';
        }
        $ichi = html_writer::tag('i', '', ['style' => 'width:' . min(100, max(0, $ball)) . '%']);
        if ($norma !== null) {
            $ichi .= html_writer::tag('u', '', [
                'style' => 'left:' . $norma . '%',
                'title' => get_string('dir_normis', 'local_targetzenit', $norma),
            ]);
        }
        return html_writer::div($ichi, 'tz-dir-bar');
    }

    /** Sparkline — bitta chiziq, raqamsiz. Tashqi kutubxona yo'q (CSP). */
    public static function spark(array $qiymat): string {
        $qiymat = array_values(array_filter($qiymat, fn($v) => $v !== null));
        if (count($qiymat) < 2) {
            return '';
        }
        $w = 96;
        $h = 24;
        $n = count($qiymat);
        $yol = [];
        foreach ($qiymat as $i => $v) {
            $x = round($i * ($w - 2) / ($n - 1) + 1, 1);
            $y = round($h - 2 - (min(100, max(0, $v)) / 100) * ($h - 4), 1);
            $yol[] = ($i ? 'L' : 'M') . $x . ' ' . $y;
        }
        return '<svg class="tz-dir-spark" width="' . $w . '" height="' . $h . '"'
            . ' viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true">'
            . '<path d="' . implode(' ', $yol) . '" fill="none" stroke="currentColor"'
            . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    /** Stat plitasi. */
    public static function tile(string $qiymat, string $yorliq, string $izoh = '',
                                string $qoshimcha = '', string $status = ''): string {
        $h = html_writer::div(s($yorliq), 'tz-dir-lab');
        $h .= html_writer::div(s($qiymat), 'tz-dir-num' . ($status ? ' tz-dir-t-' . $status : ''));
        if ($izoh !== '') {
            $h .= html_writer::div(s($izoh), 'tz-dir-sub');
        }
        if ($qoshimcha !== '') {
            $h .= html_writer::div($qoshimcha, 'tz-dir-sparkwrap');
        }
        return html_writer::div($h, 'tz-dir-tile');
    }

    /**
     * Nolga (normaga) markazlashgan diverging chiziq.
     *
     * NEGA RADAR EMAS: 15 kategoriyani radar bilan ko'rsatish keng tarqalgan
     * xato — burchaklar maydonni buzadi va taqqoslash mumkin bo'lmaydi.
     * Ma'lumotning vazifasi "normadan yuqorimi yoki pastmi", ya'ni polyarlik;
     * to'g'ri forma — hamma qatorning nol nuqtasi BITTA vertikal chiziqda
     * turgan diverging bar (DIREKTOR_PANELI.md 5.5).
     *
     * @param float|null $delta normadan farq (musbat = yuqori)
     * @param int $chegara shkalaning yarim kengligi (±)
     */
    public static function diverge(?float $delta, int $chegara = 30): string {
        if ($delta === null) {
            return html_writer::div(
                html_writer::tag('i', '', ['class' => 'tz-oq-nil']), 'tz-oq-dv');
        }
        $q = max(-$chegara, min($chegara, $delta));
        $kenglik = abs($q) / $chegara * 50;              // yarmi = 50%
        $yon = $q >= 0 ? 'left:50%' : ('right:50%');
        $rang = $q >= 0 ? 'tz-oq-pos' : 'tz-oq-neg';
        return html_writer::div(
            html_writer::tag('i', '', [
                'class' => $rang,
                'style' => $yon . ';width:' . round($kenglik, 2) . '%',
            ]), 'tz-oq-dv');
    }
}
