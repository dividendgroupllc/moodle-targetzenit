<?php
// FAN VA KATEGORIYA BELGILARI — hisobot varag'i uchun chizma ikonkalar.
//
// NEGA EMOJI EMAS (foydalanuvchi «emoji» so'ragan edi, javob — shu):
//   · emoji har tizimda boshqacha chiziladi (Windows, Android, printer
//     drayveri), ba'zan umuman kvadrat bo'lib chiqadi;
//   · rangli emoji bosmada rastr bo'lib, mayda o'lchamda loyqalanadi;
//   · hujjatning o'z palitrasiga bo'ysunmaydi — varaq «chat» ga o'xshab
//     qoladi.
// SVG chizma belgi esa vektorda bosiladi, rangi bizniki va o'lchami
// istalgancha. Ko'rinishi — emoji bergan narsani beradi: fanni bir
// qarashda tanitadi.
//
// HAR FANNING O'Z RANGI BOR, lekin u faqat BELGIDA ishlatiladi.
// Diagramma ustunining rangi — DARAJA (yuqori/o'rta/past). Ikkisini
// aralashtirsak, rang ikki narsani anglatib qolardi va ota-ona ustun
// yashilmi degani «yaxshimi yoki matematikami?» deb o'ylardi.
//
// Fan nomi Frappe'dan erkin matn bo'lib keladi (`English`, `Ona tili va
// adabiyoti`, `Математика`), shuning uchun moslik KALIT SO'ZLAR bo'yicha
// va uch tilda qidiriladi. Topilmasa — neytral «kitob» belgisi.

namespace local_targetzenit\output;

defined('MOODLE_INTERNAL') || die();

class hisobot_ikona {

    /** Belgisi topilmagan fan uchun. */
    const ZAXIRA = ['kod' => 'umumiy', 'rang' => '#495057'];

    /**
     * Fan turlari: kalit so'zlar (kichik harfda) va rang.
     *
     * TARTIB MUHIM: ro'yxat yuqoridan pastga tekshiriladi, birinchi
     * moslik yutadi. Shuning uchun aniqroq so'zlar («ona tili») umumiy
     * so'zlardan («til») OLDIN turadi.
     */
    const FANLAR = [
        'matematika' => ['rang' => '#4263eb', 'sozlar' => [
            'math', 'matem', 'алгебра', 'geometr', 'матем', 'hisob']],
        'informatika' => ['rang' => '#1c7ed6', 'sozlar' => [
            ' it', 'it ', 'informat', 'computer', 'kompyuter', 'информат',
            'coding', 'dasturlash', 'robot']],
        'biznes' => ['rang' => '#7048e8', 'sozlar' => [
            'business', 'biznes', 'бизнес', 'menejment', 'marketing']],
        'iqtisod' => ['rang' => '#0b7285', 'sozlar' => [
            'econom', 'iqtisod', 'эконом', 'moliya', 'finance']],
        'ingliz' => ['rang' => '#0ca678', 'sozlar' => [
            'english', 'ingliz', 'англ', 'chet tili', 'foreign', 'german',
            'deutsch', 'nemis', 'arab', 'turk', 'xitoy', 'chinese']],
        'adabiyot' => ['rang' => '#e8590c', 'sozlar' => [
            'ona tili', 'adabiyot', 'literat', 'rus tili', 'russian',
            'русск', 'литерат', 'o\'zbek', 'ozbek', 'til va']],
        'fizika' => ['rang' => '#5f3dc4', 'sozlar' => ['fizika', 'physic', 'физик']],
        'kimyo' => ['rang' => '#2b8a3e', 'sozlar' => ['kimyo', 'chemi', 'хими']],
        'biologiya' => ['rang' => '#37b24d', 'sozlar' => [
            'biolog', 'биолог', 'tabiat', 'ekolog']],
        'tarix' => ['rang' => '#a1662f', 'sozlar' => [
            'tarix', 'histor', 'истор', 'huquq', 'tarbiya asos']],
        'geografiya' => ['rang' => '#1098ad', 'sozlar' => [
            'geograf', 'геогра', 'astronom']],
        'sport' => ['rang' => '#f03e3e', 'sozlar' => [
            'jismoniy', 'sport', 'physical', 'физкульт', 'tarbiya (jism']],
        'sanat' => ['rang' => '#d6336c', 'sozlar' => [
            'musiqa', 'music', 'san\'at', 'sanat', 'tasvir', 'rasm', 'art',
            'музык', 'изо', 'mehnat', 'texnolog']],
    ];

    /**
     * Belgilarning chizma qismi — 24x24 maydonda, FAQAT CHIZIQ (stroke).
     *
     * Chiziqli uslub ataylab: to'ldirilgan (fill) belgi mayda o'lchamda
     * qora dog'ga aylanadi va qog'ozda siyoh yeydi. Chiziq esa 10px da
     * ham tanilib turadi.
     */
    const CHIZMA = [
        // π — mayda o'lchamda ham tanilaveradigan yagona matematika
        // belgisi. To'rt amal (+ − × ÷) sinab ko'rildi: 5 mm da u
        // nuqtalar to'plamiga aylanib, yuzga o'xshab qoldi.
        'matematika' => '<path d="M4 7.4h16"/><path d="M8.8 7.4v11.2"/>'
            . '<path d="M15.2 7.4v8.4c0 1.7 1 2.8 2.6 2.8"/>'
            . '<path d="M4 7.4c0-2 1.4-3 3.4-3h9.2c2 0 3.4 1 3.4 3"/>',
        // monitor + kod qavslari
        'informatika' => '<rect x="2.8" y="4" width="18.4" height="13" rx="2"/>'
            . '<path d="M9.5 21h5M12 17v4"/>'
            . '<path d="M9.6 8.6L7.4 10.6l2.2 2M14.4 8.6l2.2 2-2.2 2"/>',
        // portfel
        'biznes' => '<rect x="2.8" y="7" width="18.4" height="12.2" rx="2"/>'
            . '<path d="M8 7V5.6A1.6 1.6 0 019.6 4h4.8A1.6 1.6 0 0116 5.6V7"/>'
            . '<path d="M2.8 12.2h18.4M11 11v2.4h2V11"/>',
        // o'suvchi grafik
        'iqtisod' => '<path d="M3.8 4.5v15h15.4"/>'
            . '<path d="M7 15.2l3.4-3.9 3 2.4 5.2-6.2"/>'
            . '<path d="M15.4 7.5h3.2v3.2"/>',
        // globus
        'ingliz' => '<circle cx="12" cy="12" r="8.2"/><path d="M3.8 12h16.4"/>'
            . '<path d="M12 3.8c2.6 2.7 2.6 13.7 0 16.4M12 3.8c-2.6 2.7-2.6 13.7 0 16.4"/>',
        // ochiq kitob
        'adabiyot' => '<path d="M11.4 6.4C9 5 6.2 4.6 3.4 5.2v12.4c2.8-.6 5.6-.2 8 1.2z"/>'
            . '<path d="M12.6 6.4C15 5 17.8 4.6 20.6 5.2v12.4c-2.8-.6-5.6-.2-8 1.2z"/>'
            . '<path d="M12 6.6v12.2"/>',
        // atom
        'fizika' => '<circle cx="12" cy="12" r="2"/>'
            . '<ellipse cx="12" cy="12" rx="9" ry="3.8"/>'
            . '<ellipse cx="12" cy="12" rx="9" ry="3.8" transform="rotate(60 12 12)"/>'
            . '<ellipse cx="12" cy="12" rx="9" ry="3.8" transform="rotate(-60 12 12)"/>',
        // kolba
        'kimyo' => '<path d="M9.6 3.2v6.2L4.4 18a2.1 2.1 0 001.8 3.2h11.6A2.1 2.1 0 0019.6 18l-5.2-8.6V3.2"/>'
            . '<path d="M8.2 3.2h7.6"/><path d="M6.8 14.6h10.4"/>',
        // barg
        'biologiya' => '<path d="M4.6 19.4c-1.4-7.4 3-14.6 15.6-15.2 1 10.4-5.2 16.2-15.6 15.2z"/>'
            . '<path d="M4.6 19.4C8 15.4 11.4 12.4 17 9.6"/>',
        // qum soati
        'tarix' => '<path d="M6.4 3.4h11.2M6.4 20.6h11.2"/>'
            . '<path d="M8.2 3.4v3.4c0 2 3.8 3.4 3.8 5.2s-3.8 3.2-3.8 5.2v3.4"/>'
            . '<path d="M15.8 3.4v3.4c0 2-3.8 3.4-3.8 5.2s3.8 3.2 3.8 5.2v3.4"/>',
        // xarita
        'geografiya' => '<path d="M9 4.2L3.4 6.6v13.2L9 17.4l6 2.4 5.6-2.4V4.2L15 6.6z"/>'
            . '<path d="M9 4.2v13.2M15 6.6v13.2"/>',
        // to'p
        'sport' => '<circle cx="12" cy="12" r="8.2"/>'
            . '<path d="M12 4.4l2.8 4.4-2.8 2.4-2.8-2.4z"/>'
            . '<path d="M4.3 10.1l4.4 1.9.9 3.6-3.6 2.1M19.7 10.1l-4.4 1.9-.9 3.6 3.6 2.1"/>',
        // musiqa nota
        'sanat' => '<path d="M9 18.2V5.4l10-2.2v12.6"/>'
            . '<ellipse cx="6.6" cy="18.4" rx="2.6" ry="2.2"/>'
            . '<ellipse cx="16.6" cy="16.2" rx="2.6" ry="2.2"/>',
        // kitob (zaxira)
        'umumiy' => '<path d="M4.2 4.4h11a3 3 0 013 3v12.2H7.2a3 3 0 01-3-3z"/>'
            . '<path d="M4.2 16.6a3 3 0 013-3h11"/><path d="M8.4 8.2h6.4"/>',
    ];

    /** BESH KATEGORIYA belgisi — sharh bloki uchun. */
    const KATEGORIYA = [
        // akademik bilim — bitiruv qalpog'i
        'akademik' => '<path d="M2.6 8.6L12 4.2l9.4 4.4-9.4 4.4z"/>'
            . '<path d="M6.6 11.1v4.9c0 1.5 2.4 2.7 5.4 2.7s5.4-1.2 5.4-2.7v-4.9"/>'
            . '<path d="M21.4 8.6v5"/>',
        // darsdagi faollik — gapiruvchi pufak
        'faollik' => '<path d="M3.4 5.2h17.2v10.4H9.2l-5.8 4.4z"/>'
            . '<path d="M8.4 10.4h.01M12 10.4h.01M15.6 10.4h.01"/>',
        // ijtimoiy ko'nikma — ikki odam
        'ijtimoiy' => '<circle cx="9" cy="7.8" r="3.1"/>'
            . '<path d="M3.2 19.4c0-3.2 2.6-5.2 5.8-5.2s5.8 2 5.8 5.2"/>'
            . '<path d="M16.2 5.9a3.1 3.1 0 010 5.8"/>'
            . '<path d="M17.6 14.6c2 .9 3.2 2.5 3.2 4.8"/>',
        // mas'uliyat — belgilangan ro'yxat
        'masuliyat' => '<rect x="4" y="3.4" width="16" height="17.2" rx="2.2"/>'
            . '<path d="M8 9.2l2 2 4-4.2"/><path d="M8 15.8h8"/>',
        // tanqidiy fikrlash — lampochka
        'tanqidiy' => '<path d="M9.2 17.4h5.6M10.2 20.4h3.6"/>'
            . '<path d="M12 3.2A6.2 6.2 0 008.4 14.4c.7.5 1.1 1.3 1.1 2.1h5c0-.8.4-1.6 1.1-2.1A6.2 6.2 0 0012 3.2z"/>',
        // eski erkin sharh
        'umumiy' => '<circle cx="12" cy="12" r="8.4"/><path d="M12 7.6v5.2M12 16.2v.01"/>',
    ];

    /** Fan nomidan tur va rang: ['kod' => ..., 'rang' => ...]. */
    public static function fan(string $nom): array {
        $past = ' ' . \core_text::strtolower(trim($nom)) . ' ';
        foreach (self::FANLAR as $kod => $f) {
            foreach ($f['sozlar'] as $soz) {
                if (strpos($past, $soz) !== false) {
                    return ['kod' => $kod, 'rang' => $f['rang']];
                }
            }
        }
        return self::ZAXIRA;
    }

    /** Fan rangi — faqat belgida ishlatiladi (daraja rangi bilan aralashmaydi). */
    public static function rang(string $fan): string {
        return self::fan($fan)['rang'];
    }

    /**
     * Doira ichidagi fan belgisi (HTML).
     *
     * @param string $fan fan nomi
     * @param int $olcham piksel
     * @param bool $toldir to'q doira (oq belgi) yoki och doira (rangli belgi)
     */
    public static function nishon(string $fan, int $olcham = 26,
                                   bool $toldir = false): string {
        $f = self::fan($fan);
        $chizma = self::CHIZMA[$f['kod']] ?? self::CHIZMA['umumiy'];
        $ichrang = $toldir ? '#ffffff' : $f['rang'];

        return '<svg class="tz-hr-nishon" width="' . $olcham . '" height="' . $olcham
            . '" viewBox="0 0 34 34" role="img" aria-hidden="true">'
            . '<circle cx="17" cy="17" r="16" fill="' . ($toldir ? $f['rang'] : '#ffffff')
            . '" stroke="' . $f['rang'] . '" stroke-width="' . ($toldir ? 0 : 1.4) . '"'
            . ($toldir ? '' : ' stroke-opacity="0.45"') . '/>'
            . ($toldir ? '' : '<circle cx="17" cy="17" r="16" fill="' . $f['rang']
                . '" fill-opacity="0.10"/>')
            . '<g transform="translate(5 5) scale(1)" fill="none" stroke="' . $ichrang
            . '" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">'
            . $chizma . '</g></svg>';
    }

    /**
     * Diagramma ichiga qo'yiladigan belgi (SVG fragmenti).
     *
     * Diagramma o'zi SVG bo'lgani uchun bu yerda `<svg>` emas, `<g>`
     * qaytariladi: ichma-ich `<svg>` koordinatalarni chalkashtiradi.
     */
    public static function nishon_svg(string $fan, float $x, float $y,
                                       float $olcham = 24): string {
        $f = self::fan($fan);
        $chizma = self::CHIZMA[$f['kod']] ?? self::CHIZMA['umumiy'];
        $r = $olcham / 2;
        $k = ($olcham * 0.66) / 24;        // 24x24 chizmani doiraga moslash
        $ofset = $r - ($olcham * 0.66) / 2;

        return '<g transform="translate(' . round($x, 1) . ' ' . round($y, 1) . ')">'
            . '<circle cx="' . $r . '" cy="' . $r . '" r="' . $r . '" fill="' . $f['rang']
            . '" fill-opacity="0.12"/>'
            . '<circle cx="' . $r . '" cy="' . $r . '" r="' . $r . '" fill="none" stroke="'
            . $f['rang'] . '" stroke-width="1.2" stroke-opacity="0.45"/>'
            . '<g transform="translate(' . round($ofset, 2) . ' ' . round($ofset, 2)
            // Chiziq qalinligi 24 birlikli maydonda beriladi; masshtabdan
            // keyin u ~1.2 birlik bo'lib chiqadi — mayda doirada ham
            // chiziq ko'rinadi, lekin dog' bo'lib qolmaydi.
            . ') scale(' . round($k, 4) . ')" fill="none" stroke="' . $f['rang']
            . '" stroke-width="1.9"'
            . ' stroke-linecap="round" stroke-linejoin="round">' . $chizma . '</g></g>';
    }

    /** Kategoriya belgisi (HTML) — sharh qatoridagi yorliq oldida. */
    public static function kat(string $kod, string $rang, int $olcham = 17): string {
        $chizma = self::KATEGORIYA[$kod] ?? self::KATEGORIYA['umumiy'];
        return '<svg class="tz-hr-katikon" width="' . $olcham . '" height="' . $olcham
            . '" viewBox="0 0 24 24" fill="none" stroke="' . $rang
            . '" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
            . ' role="img" aria-hidden="true">' . $chizma . '</svg>';
    }
}
