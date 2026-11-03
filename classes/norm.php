<?php
// NORMALAR — "nima kutiladi" degan qiymatlar.
//
// MANBA: MOODLE (plagin sozlamalari). Qaror 2026-11-03 da o'zgardi —
// avval Frappe'dan kelishi rejalashtirilgan edi, lekin umumiy tamoyil
// aniqlashtirildi: **moliya ERP'da, dars jarayoni Moodle'da**. Baholash
// dars jarayonining qismi, shuning uchun normasi ham shu yerda.
//
// Ustunlik tartibi: plagin sozlamasi -> `_norm` jadvali (Frappe push'i,
// eski integratsiya uchun qoldirilgan) -> {@see ZAXIRA}.
//
// ZAXIRA QIYMAT: hech qayerda belgilanmagan bo'lsa panel jim NOLGA
// tushmasligi kerak — nol "hamma narsa yomon" degan yolg'on signal berardi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class norm {

    /**
     * Zaxira qiymatlar (foizda).
     *
     * `yozuv_ulushi` = 100: yozuv HAR DARSDA majburiy (qaror 2026-10-29).
     * Bu qiymat ataylab shu yerda ham turadi — Frappe normani yubormasa ham
     * panel yozuvni majburiy deb hisoblashi kerak.
     */
    const ZAXIRA = [
        'mavzu_ulushi' => 100,      // mavzu har darsda yoziladi
        'yoqlama_qamrovi' => 80,    // sinf ro'yxatining necha foizi belgilanishi kerak
        'baho_ulushi' => 40,        // darslarning necha foizida baho bo'lishi kutiladi
        'uy_vazifa_ulushi' => 50,
        'yozuv_ulushi' => 100,      // majburiy
        'jurnal_kechikish_soat' => 48,  // shundan ko'p bo'lsa — kech to'ldirilgan
    ];

    /**
     * Norma qatorining unikal kaliti.
     *
     * NEGA HASH: tabiiy kalit (yil + qamrov + qamrov kaliti + kod) 365 belgi
     * chiqadi, Moodle'ning indeks chegarasi esa 333 belgi.
     */
    public static function key(string $year, string $scope, string $scopekey,
                               string $code): string {
        return md5($year . '|' . $scope . '|' . $scopekey . '|' . $code);
    }

    /** Normalar keshi: [year => [scope|scopekey|code => value]] */
    protected static $kesh = [];

    protected static function load(string $year): array {
        global $DB;

        if (isset(self::$kesh[$year])) {
            return self::$kesh[$year];
        }
        $rows = $DB->get_records('local_targetzenit_norm', ['academicyear' => $year]);
        $xarita = [];
        foreach ($rows as $r) {
            $xarita[$r->scope . '|' . $r->scopekey . '|' . $r->code] = (float) $r->value;
        }
        self::$kesh[$year] = $xarita;
        return $xarita;
    }

    /**
     * Norma qiymati.
     *
     * Eng ANIQ moslik yutadi: teacher -> group -> subject -> global -> zaxira.
     *
     * @param array $ctx ['year' =>, 'teacherkey' =>, 'groupkey' =>, 'subjectkey' =>]
     * @return array ['value' => float, 'source' => 'frappe'|'zaxira', 'scope' => string]
     */
    public static function get(string $code, array $ctx = []): array {
        $year = (string) ($ctx['year'] ?? timetable::current_year());
        $xarita = $year !== '' ? self::load($year) : [];

        // 1) Moodle sozlamasi — eng ustun manba
        $sozlama = get_config('local_targetzenit', 'norm_' . $code);
        if ($sozlama !== false && trim((string) $sozlama) !== '') {
            return ['value' => (float) $sozlama, 'source' => 'moodle', 'scope' => 'global'];
        }

        $tartib = [
            ['teacher', (string) ($ctx['teacherkey'] ?? '')],
            ['group', (string) ($ctx['groupkey'] ?? '')],
            ['subject', (string) ($ctx['subjectkey'] ?? '')],
            ['global', ''],
        ];

        foreach ($tartib as [$scope, $kalit]) {
            if ($scope !== 'global' && $kalit === '') {
                continue;
            }
            $k = $scope . '|' . $kalit . '|' . $code;
            if (isset($xarita[$k])) {
                return ['value' => $xarita[$k], 'source' => 'frappe', 'scope' => $scope];
            }
        }

        return [
            'value' => (float) (self::ZAXIRA[$code] ?? 0),
            'source' => 'zaxira',
            'scope' => 'zaxira',
        ];
    }

    /** Qisqa shakl — faqat qiymat kerak bo'lganda. */
    public static function value(string $code, array $ctx = []): float {
        return self::get($code, $ctx)['value'];
    }

    /** Umuman norma kelganmi (UI ogohlantirishi uchun). */
    public static function has_any(string $year): bool {
        global $DB;
        return $DB->record_exists('local_targetzenit_norm', ['academicyear' => $year]);
    }

    /** Push'dan keyin keshni tozalash. */
    public static function kesh_tozala(): void {
        self::$kesh = [];
    }
}
