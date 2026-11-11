<?php
// IMTIHON NATIJALARINI EXCEL'DAN YUKLASH.
//
// MUAMMO: o'tgan test qog'ozda o'tgan va ballari o'qituvchining Excel
// faylida. 79 guruh x 25 o'quvchi ni qo'lda kiritish real emas.
//
// TAMOYIL: import HECH NARSA YOZMAYDI. U faqat fayldan ball o'qib, mavjud
// BALL SETKASINI TO'LDIRADI — odam ko'rib, tekshirib, keyin «Saqlash»
// bosadi. Shunda:
//   · yozish yo'li BITTA bo'lib qoladi (`exam::result_save()`), ya'ni
//     nizomdagi 30% chegarasi, 72 soatlik oyna va o'zgarishlar tarixi
//     import uchun alohida yozilmaydi;
//   · ism bo'yicha moslashtirish NATIJASI ko'rinadi — bu MAJBURIY, chunki
//     tizimlararo bog'lanish faqat o'zgarmas ID bilan bo'lishi kerak va
//     ism bo'yicha topilgan moslik odam tasdiqlashi shart.
//
// FAYL FORMATI QOTIB BELGILANMAYDI: o'qituvchining fayli har xil bo'ladi,
// shuning uchun ustunlar SARLAVHA SO'ZLARIDAN aniqlanadi (uz/ru/en) va
// topilmasa tushunarli xabar beriladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class exam_import {

    /** Ism/F.I.Sh. ustunining sarlavhasida uchraydigan so'zlar. */
    const ISM_SOZLAR = ['fish', 'f.i.sh', 'fio', 'ism', 'familiya', 'oquvchi',
                        "o'quvchi", 'student', 'name', 'учен', 'фио', 'имя'];

    /** O'quvchi ID ustuni. */
    const ID_SOZLAR = ['id', 'idnumber', 'student id', 'studentid', 'kod',
                       'код', 'шифр'];

    /** Ball/natija ustuni. */
    const BALL_SOZLAR = ['ball', 'natija', 'bal', 'togri', "to'g'ri", 'score',
                         'result', 'mark', 'балл', 'результат', 'оценка'];

    /** «Savollar soni» yozilgan katakni topish uchun. */
    const SAVOL_SOZLAR = ['savol', 'savollar', 'question', 'вопрос'];

    /** Blokli varaqda savollar soni ustuni. */
    const SAVOLSONI_SOZLAR = ['savollar soni', 'ballar soni', 'savol soni',
                              'вопросов', 'questions'];

    /** Blokli varaqda ball ustuni (aniq sarlavha). */
    const TOGRI_SOZLAR = ["to'g'ri javob", 'togri javob', 'правильных',
                          'correct'];

    /** Fan bloki EMAS — yig'indi ustunlari. Import qilinmaydi. */
    const JAMI_SOZLAR = ['jami', 'umumiy', 'итог', 'всего', 'total'];

    /** Jadval oxirini bildiruvchi qatorlar (ism o'rnida shu matn turadi). */
    const OXIR_SOZLAR = ['o\'rtacha', 'ortacha', 'jami', 'umumiy', 'средн',
                         'итог', 'average'];

    /** Fan bloki bo'lmagan varaqlar. */
    const SERVIS_VARAQLAR = ['umumiy', 'darajalar', 'jami', 'svod', 'итог'];

    /** Qancha qator o'qiladi — tasodifiy katta fayl xotirani yemasin. */
    const MAX_QATOR = 2000;

    // ============================================================== O'QISH

    /**
     * Faylni qatorlar massiviga o'qiydi.
     *
     * PhpSpreadsheet Moodle avtoyuklagichida bor
     * (`lib/phpspreadsheet/...`), CSV esa o'z kuchimiz bilan —
     * `journal::read_plan()` dagi bilan bir xil yo'l.
     */
    public static function oqi(string $filepath): array {
        return self::varaqlar($filepath)['faol'];
    }

    /**
     * HAMMA VARAQNI o'qiydi: ['varaqlar' => [nom => qatorlar], 'faol' => ...].
     *
     * NEGA KERAK: real fayl (Target maktabining «Test natijalari» fayli) har
     * sinf uchun ALOHIDA VARAQ bo'lib tuzilgan — 1A, 1B, 2A ... 11B. Shunda
     * bitta fayl butun maktabga yetadi va koordinator har guruhda o'sha
     * faylni yuklaydi, tizim esa guruh nomiga mos varaqni o'zi topadi.
     */
    public static function varaqlar(string $filepath): array {
        $kengaytma = \core_text::strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if ($kengaytma !== 'csv' && $kengaytma !== 'txt') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $out = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $out[$sheet->getTitle()] = array_slice(
                    $sheet->toArray(null, true, false, false), 0, self::MAX_QATOR);
            }
            $faol = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
            return ['varaqlar' => $out,
                    'faol' => array_slice($faol, 0, self::MAX_QATOR)];
        }

        $rows = self::csv($filepath);
        return ['varaqlar' => ['CSV' => $rows], 'faol' => $rows];
    }

    /** CSV ni qatorlarga o'qiydi. */
    protected static function csv(string $filepath): array {
        $kengaytma = \core_text::strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if ($kengaytma === 'csv' || $kengaytma === 'txt') {
            $fh = fopen($filepath, 'r');
            if (!$fh) {
                throw new \moodle_exception('bh_imp_unreadable', 'local_targetzenit');
            }
            // Ajratgich vergulmi yoki nuqtali vergulmi — birinchi qatordan
            // aniqlanadi. Rus Excel'i nuqtali vergul bilan saqlaydi.
            $birinchi = fgets($fh);
            $ajr = (substr_count((string) $birinchi, ';')
                > substr_count((string) $birinchi, ',')) ? ';' : ',';
            rewind($fh);

            $rows = [];
            while (($r = fgetcsv($fh, 0, $ajr)) !== false) {
                $rows[] = $r;
                if (count($rows) >= self::MAX_QATOR) {
                    break;
                }
            }
            fclose($fh);
            return $rows;
        }

        throw new \moodle_exception('bh_imp_unreadable', 'local_targetzenit');
    }

    /**
     * Guruh nomiga mos varaqni topadi: «3 A» <-> «3A» <-> «3a».
     *
     * Bo'shliq va registr e'tiborsiz — Frappe'da guruh «3 A», faylda «3A».
     * Servis varaqlari (Umumiy, Darajalar) chetlab o'tiladi.
     */
    public static function varaq_tanla(array $varaqlar, string $groupname): ?string {
        $qidir = preg_replace('/\s+/u', '',
            \core_text::strtolower(trim($groupname)));
        if ($qidir === '') {
            return null;
        }
        foreach ($varaqlar as $nom => $x) {
            $n = preg_replace('/\s+/u', '', \core_text::strtolower(trim($nom)));
            if (in_array($n, self::SERVIS_VARAQLAR, true)) {
                continue;
            }
            if ($n === $qidir) {
                return $nom;
            }
        }
        return null;
    }

    // =========================================================== ANIQLASH

    /** Katak matnini taqqoslash uchun soddalashtiradi. */
    protected static function kalit(?string $v): string {
        $v = \core_text::strtolower(trim((string) $v));
        // Apostroflarning hamma ko'rinishi bir xil bo'lsin: o‘quvchi,
        // o'quvchi, oʻquvchi — bir xil so'z.
        $v = str_replace(["\u{2018}", "\u{2019}", "\u{02BB}", "\u{02BC}", '`', "'"], '', $v);
        return preg_replace('/\s+/u', ' ', $v);
    }

    /**
     * ISM USTUNINI topadi: [qator indeksi, ustun indeksi] yoki null.
     *
     * IKKI HIMOYA — ikkisi ham haqiqiy faylda kerak bo'ldi:
     *
     * 1. Katak UZUN BO'LMASLIGI kerak. Faylning sarlavhasida
     *    «3A sinf — test natijalari (har bir o'quvchiga 50 ta savol)»
     *    deb yozilgan va unda «o'quvchi» so'zi bor — tekshiruvsiz u ism
     *    ustuni deb qabul qilinardi va butun tahlil buzilardi (2026-11-11).
     * 2. Sarlavha qatorida KAMIDA IKKI to'ldirilgan katak bo'lishi kerak.
     *    Sarlavha (title) qatorida odatda bitta katak bo'ladi, jadval
     *    sarlavhasida esa bir nechta.
     */
    protected static function ism_ustun(array $rows): ?array {
        foreach (array_slice($rows, 0, 12, true) as $i => $r) {
            if (!is_array($r)) {
                continue;
            }
            $tolgan = 0;
            foreach ($r as $katak) {
                if (trim((string) $katak) !== '') {
                    $tolgan++;
                }
            }
            if ($tolgan < 2) {
                continue;                // sarlavha (title) qatori
            }
            foreach ($r as $j => $katak) {
                $matn = trim((string) $katak);
                if ($matn === '' || \core_text::strlen($matn) > 60) {
                    continue;            // uzun matn — izoh, sarlavha emas
                }
                if (self::mosmi($matn, self::ISM_SOZLAR)) {
                    return [$i, $j];
                }
            }
        }
        return null;
    }

    /** Sarlavha katagi shu so'zlardan birini o'z ichiga oladimi. */
    protected static function mosmi(?string $katak, array $sozlar): bool {
        $k = self::kalit($katak);
        if ($k === '') {
            return false;
        }
        foreach ($sozlar as $soz) {
            if (strpos($k, self::kalit($soz)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * FAN BLOKLARI ko'rinishidagi varaqni tahlil qiladi.
     *
     * Target maktabining «Test natijalari» fayli aynan shunday tuzilgan:
     *
     *   N | O'quvchi ism familiyasi |      Math      |     English     | JAMI
     *     |                         | Savollar | To'g'ri | Savollar | To'g'ri | ...
     *   1 | Ravshanbekova Parizoda  |    30    |    0    |    25    |    7   | ...
     *
     * Ya'ni bitta varaqda BIR NECHTA FAN yonma-yon turadi. Shuning uchun
     * import qaysi blokni olishini BILISHI kerak — u baholashning fanidan
     * aniqlanadi (`blok_tanla()`), topilmasa odam tanlaydi.
     *
     * `JAMI` bloki ATAYLAB chetlab o'tiladi: u yig'indi, fan emas.
     *
     * @return array|null null = varaq blokli emas (oddiy rejimga o'tiladi)
     */
    public static function tahlil_bloklar(array $rows): ?array {
        // --- sarlavha qatori: ism ustuni bor qator
        $topildi = self::ism_ustun($rows);
        if ($topildi === null) {
            return null;
        }
        [$sh, $ismustun] = $topildi;
        if (!isset($rows[$sh + 1]) || !is_array($rows[$sh + 1])) {
            return null;
        }

        $sarlavha = $rows[$sh];          // fan nomlari shu qatorda
        $sub = $rows[$sh + 1];           // ustun sarlavhalari shu qatorda

        // --- № ustuni: jadval oxirini aniqlash uchun eng ishonchli belgi
        $nustun = null;
        foreach ($sarlavha as $j => $katak) {
            if (preg_match('/^\s*(n|№|#|t\/r)\s*$/iu', (string) $katak)) {
                $nustun = $j;
                break;
            }
        }

        // --- BLOKLAR: har «To'g'ri javob» ustuni bitta blokning ball ustuni
        $bloklar = [];
        foreach ($sub as $sc => $katak) {
            if (!self::mosmi($katak, self::TOGRI_SOZLAR)) {
                continue;
            }

            // Savollar soni ustuni — orqadagi 3 ustun ichida
            $savolustun = null;
            for ($j = $sc - 1; $j >= max(0, $sc - 3); $j--) {
                if (self::mosmi($sub[$j] ?? '', self::SAVOLSONI_SOZLAR)) {
                    $savolustun = $j;
                    break;
                }
            }

            // Fan nomi — sarlavha qatorida, shu ustundan orqada turgan
            // birinchi bo'sh bo'lmagan katak
            $fan = '';
            for ($j = $sc; $j >= 0; $j--) {
                $v = trim((string) ($sarlavha[$j] ?? ''));
                if ($v !== '' && $j !== $ismustun && $j !== $nustun) {
                    $fan = $v;
                    break;
                }
            }
            if ($fan === '' || self::mosmi($fan, self::JAMI_SOZLAR)) {
                continue;                // yig'indi bloki — fan emas
            }

            $bloklar[] = ['fan' => $fan, 'ballustun' => $sc,
                          'savolustun' => $savolustun, 'savollar' => null];
        }

        if (count($bloklar) < 2) {
            // Bitta blok — oddiy «ball ustuni» rejimidan farq qilmaydi,
            // ortiqcha murakkablik kerak emas.
            return null;
        }

        // --- ma'lumot qatorlari
        $qatorlar = [];
        foreach (array_slice($rows, $sh + 2, null, true) as $i => $r) {
            if (!is_array($r)) {
                continue;
            }
            $ism = trim((string) ($r[$ismustun] ?? ''));

            // JADVAL OXIRI. Uch belgi, chunki fayl ostida yana jadvallar bor
            // («darajalar bo'yicha guruhlar») va ular o'quvchi ismlarini
            // QAYTA sanab chiqadi — ularni import qilib yuborish xato bo'lardi.
            if ($nustun !== null) {
                $n = trim((string) ($r[$nustun] ?? ''));
                if ($n === '' || !ctype_digit($n)) {
                    break;
                }
            } else if ($ism === '') {
                break;
            }
            if (self::mosmi($ism, self::OXIR_SOZLAR)) {
                break;
            }

            $ballar = [];
            foreach ($bloklar as $bi => $b) {
                $ballar[$bi] = trim((string) ($r[$b['ballustun']] ?? ''));
                // Savollar soni ustunda har qatorda takrorlanadi — birinchi
                // sonli qiymatni olamiz.
                if ($bloklar[$bi]['savollar'] === null && $b['savolustun'] !== null) {
                    $sv = trim((string) ($r[$b['savolustun']] ?? ''));
                    if (is_numeric($sv) && (float) $sv > 0) {
                        $bloklar[$bi]['savollar'] = (int) round((float) $sv);
                    }
                }
            }

            $qatorlar[] = ['qator' => $i + 1, 'ism' => $ism, 'id' => '',
                           'ballar' => $ballar];
        }

        if (!$qatorlar) {
            return null;
        }
        return ['rejim' => 'bloklar', 'bloklar' => $bloklar,
                'qatorlar' => $qatorlar];
    }

    /**
     * Baholashning faniga mos blokni topadi.
     *
     * Fayldagi nom («Math», «English (og'zaki / video)») Moodle'dagi fan
     * kaliti/nomi bilan aynan bir xil bo'lishi SHART EMAS, shuning uchun
     * ikki tomonlama qism-moslik ham qabul qilinadi. Topilmasa null —
     * va odam o'zi tanlaydi (taxmin qilib yozish eng qimmat xato).
     */
    public static function blok_tanla(array $bloklar, string $subjectkey,
                                       string $subjectname = ''): ?int {
        $nomzodlar = array_filter([self::kalit($subjectkey), self::kalit($subjectname)]);
        if (!$nomzodlar) {
            return null;
        }
        // Avval ANIQ moslik
        foreach ($bloklar as $i => $b) {
            if (in_array(self::kalit($b['fan']), $nomzodlar, true)) {
                return $i;
            }
        }
        // Keyin qism-moslik, lekin FAQAT bittasi mos kelsa
        $topilgan = [];
        foreach ($bloklar as $i => $b) {
            $f = self::kalit($b['fan']);
            foreach ($nomzodlar as $n) {
                if ($f !== '' && $n !== ''
                        && (strpos($f, $n) !== false || strpos($n, $f) !== false)) {
                    $topilgan[$i] = true;
                    break;
                }
            }
        }
        return (count($topilgan) === 1) ? (int) array_key_first($topilgan) : null;
    }

    /** Tanlangan blokning qatorlarini `moslashtir()` kutgan ko'rinishga keltiradi. */
    public static function blok_qatorlari(array $tahlil, int $blok): array {
        $out = [];
        foreach ($tahlil['qatorlar'] as $q) {
            $out[] = ['qator' => $q['qator'], 'ism' => $q['ism'], 'id' => '',
                      'xom' => (string) ($q['ballar'][$blok] ?? '')];
        }
        return $out;
    }

    /**
     * Faylni tahlil qiladi: sarlavha qatori, ustunlar, savollar soni.
     *
     * IKKI REJIM:
     *   `ball`      — bitta ball ustuni (F.I.Sh. | Ball)
     *   `matritsa`  — har savol uchun alohida ustun, qiymat 0/1;
     *                 ball = yig'indi, savollar soni = ustunlar soni
     *
     * Matritsa rejimi ataylab qo'llab-quvvatlanadi: foydalanuvchi «faylimdan
     * savollar soni va har bir o'quvchi natijasini olishi kerak» degan, va
     * savol-savol varaq aynan shu ikkisini beradi.
     *
     * @return array ['rejim', 'savollar', 'qatorlar' => [['ism','id','ball']]]
     */
    public static function tahlil(array $rows): array {
        // FAN BLOKLARI birinchi tekshiriladi: real fayl shunday tuzilgan va
        // uni oddiy «bitta ball ustuni» deb o'qisak, birinchi uchragan
        // «To'g'ri javob» ustuni olinib, fan noto'g'ri bo'lib ketardi.
        $blokli = self::tahlil_bloklar($rows);
        if ($blokli !== null) {
            return $blokli;
        }

        $idustun = null;
        $ballustun = null;
        $savolustunlar = [];

        // Sarlavha qatori va ism ustuni — ikki himoya bilan (`ism_ustun()`)
        $topildi = self::ism_ustun($rows);
        if ($topildi === null) {
            throw new \moodle_exception('bh_imp_nohead', 'local_targetzenit');
        }
        [$sarlavha, $ismustun] = $topildi;

        foreach ($rows[$sarlavha] as $j => $katak) {
            if ($j === $ismustun) {
                continue;
            }
            if ($idustun === null && self::mosmi($katak, self::ID_SOZLAR)) {
                $idustun = $j;
            } else if ($ballustun === null && self::mosmi($katak, self::BALL_SOZLAR)) {
                $ballustun = $j;
            }
        }

        // --- savollar soni: «Savollar soni: 20» kabi katakni izlaymiz
        $savollar = null;
        foreach (array_slice($rows, 0, 10) as $r) {
            if (!is_array($r)) {
                continue;
            }
            foreach ($r as $j => $katak) {
                if (!self::mosmi($katak, self::SAVOL_SOZLAR)) {
                    continue;
                }
                // Raqam shu katak ichida bo'lishi mumkin («Savollar: 20»)
                if (preg_match('/(\d{1,3})/', (string) $katak, $m)) {
                    $savollar = (int) $m[1];
                    break 2;
                }
                // Yoki yonidagi kataklarda
                foreach (array_slice($r, $j + 1, 3) as $keyingi) {
                    if (is_numeric(trim((string) $keyingi))) {
                        $savollar = (int) trim((string) $keyingi);
                        break 3;
                    }
                }
            }
        }

        // --- MATRITSA tekshiruvi: ball ustuni topilmagan bo'lsa, ism
        // ustunidan keyingi ketma-ket raqamli ustunlar savollar bo'lishi
        // mumkin. Qiymatlar faqat 0/1 (yoki bo'sh) bo'lsa — matritsa.
        $rejim = 'ball';
        if ($ballustun === null) {
            $nomzod = [];
            $kenglik = 0;
            foreach ($rows as $r) {
                if (is_array($r)) {
                    $kenglik = max($kenglik, count($r));
                }
            }
            for ($j = $ismustun + 1; $j < $kenglik; $j++) {
                if ($j === $idustun) {
                    continue;
                }
                $nomzod[] = $j;
            }

            $faqatbir = true;
            $sonli = 0;
            foreach (array_slice($rows, $sarlavha + 1) as $r) {
                if (!is_array($r)) {
                    continue;
                }
                foreach ($nomzod as $j) {
                    $v = trim((string) ($r[$j] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    if (!is_numeric($v)) {
                        $faqatbir = false;
                        break 2;
                    }
                    $sonli++;
                    if ((float) $v !== 0.0 && (float) $v !== 1.0) {
                        $faqatbir = false;
                        break 2;
                    }
                }
            }
            if ($faqatbir && count($nomzod) >= 3 && $sonli > 0) {
                $rejim = 'matritsa';
                $savolustunlar = $nomzod;
                // Savollar soni fayldan: ustunlar soni. Katakdagi yozuvdan
                // ustun — chunki bu haqiqiy varaqning o'zi.
                $savollar = count($nomzod);
            }
        }

        if ($rejim === 'ball' && $ballustun === null) {
            throw new \moodle_exception('bh_imp_noscore', 'local_targetzenit');
        }

        // --- qatorlarni yig'amiz
        $qatorlar = [];
        foreach (array_slice($rows, $sarlavha + 1, null, true) as $i => $r) {
            if (!is_array($r)) {
                continue;
            }
            $ism = trim((string) ($r[$ismustun] ?? ''));
            $id = ($idustun !== null) ? trim((string) ($r[$idustun] ?? '')) : '';
            if ($ism === '' && $id === '') {
                continue;                 // bo'sh qator — jadval oxiri
            }

            if ($rejim === 'matritsa') {
                $ball = 0.0;
                $bor = false;
                foreach ($savolustunlar as $j) {
                    $v = trim((string) ($r[$j] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    $bor = true;
                    $ball += (float) $v;
                }
                $xom = $bor ? (string) $ball : '';
            } else {
                $xom = trim((string) ($r[$ballustun] ?? ''));
            }

            $qatorlar[] = [
                'qator' => $i + 1,        // foydalanuvchiga 1-asosli ko'rsatiladi
                'ism' => $ism,
                'id' => $id,
                'xom' => $xom,
            ];
        }

        return ['rejim' => $rejim, 'savollar' => $savollar, 'qatorlar' => $qatorlar];
    }

    // ====================================================== MOSLASHTIRISH

    /**
     * Ismni taqqoslanadigan BELGILAR TO'PLAMIGA aylantiradi.
     *
     * NEGA TO'PLAM, satr emas: fayldagi tartib «Familiya Ism» bo'lishi
     * mumkin, Moodle'da esa «Ism Familiya». To'plam bo'lsa tartib muhim
     * bo'lmaydi. Otasining ismi fayldan tushib qolgan bo'lishi ham mumkin,
     * shuning uchun quyida QISMAN moslik ham qabul qilinadi.
     */
    public static function tokenlar(string $ism): array {
        $k = self::kalit($ism);
        $k = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $k);
        $t = array_filter(explode(' ', trim(preg_replace('/\s+/u', ' ', $k))),
            fn($x) => \core_text::strlen($x) > 1);
        return array_values(array_unique($t));
    }

    /**
     * Fayl qatorlarini kursdagi o'quvchilarga moslashtiradi.
     *
     * TARTIB QAT'IY:
     *   1) `idnumber` bo'yicha — ANIQ moslik (o'zgarmas unikal ID qoidasi);
     *   2) ism tokenlari to'liq mos kelsa;
     *   3) fayldagi tokenlar o'quvchining tokenlari ICHIDA bo'lsa va
     *      kamida ikkitasi mos kelsa (otasining ismi tushib qolgan hol).
     *
     * Ikki o'quvchiga mos kelgan qator YOZILMAYDI — «ikki xil» deb
     * belgilanadi. Taxmin qilib yozish eng qimmat xato: ball boshqa
     * o'quvchiga tushib ketadi va buni keyin topish deyarli imkonsiz.
     *
     * @param array $qatorlar `tahlil()` dan
     * @param array $oquvchilar `lesson::students()` dan [userid => record]
     * @return array ['ball' => [userid => float], 'hisobot' => [...]]
     */
    public static function moslashtir(array $qatorlar, array $oquvchilar,
                                       int $maxscore): array {
        // Indekslar
        $idbyidnumber = [];
        $tokenlar = [];
        foreach ($oquvchilar as $o) {
            $idn = self::kalit((string) $o->idnumber);
            if ($idn !== '') {
                $idbyidnumber[$idn] = (int) $o->id;
            }
            $tokenlar[(int) $o->id] = self::tokenlar(
                $o->lastname . ' ' . $o->firstname);
        }

        $ball = [];
        $hisobot = [];
        $band = [];                        // bitta o'quvchiga ikki qator tushmasin

        foreach ($qatorlar as $q) {
            $natija = self::ball_parse($q['xom'], $maxscore);

            // --- (1) idnumber
            $uid = null;
            $usul = '';
            $idk = self::kalit($q['id']);
            if ($idk !== '' && isset($idbyidnumber[$idk])) {
                $uid = $idbyidnumber[$idk];
                $usul = 'id';
            }

            // --- (2)/(3) ism
            if ($uid === null && $q['ism'] !== '') {
                $ft = self::tokenlar($q['ism']);
                $topilgan = [];
                foreach ($tokenlar as $sid => $st) {
                    if (!$ft || !$st) {
                        continue;
                    }
                    sort($ft);
                    $kesim = array_intersect($ft, $st);
                    if (count($ft) === count($st) && count($kesim) === count($ft)) {
                        $topilgan[$sid] = 'toliq';
                    } else if (count($kesim) >= 2 && count($kesim) === count($ft)) {
                        $topilgan[$sid] = 'qisman';
                    }
                }
                // Aniq moslik qisman moslikdan ustun
                $aniq = array_keys($topilgan, 'toliq', true);
                if (count($aniq) === 1) {
                    $uid = $aniq[0];
                    $usul = 'ism';
                } else if (!$aniq && count($topilgan) === 1) {
                    $uid = (int) array_key_first($topilgan);
                    $usul = 'qisman';
                } else if (count($aniq) > 1 || count($topilgan) > 1) {
                    $hisobot[] = ['qator' => $q['qator'], 'ism' => $q['ism'],
                        'holat' => 'ikkixil', 'ball' => $natija['matn']];
                    continue;
                }
            }

            if ($uid === null) {
                $hisobot[] = ['qator' => $q['qator'], 'ism' => $q['ism'],
                    'holat' => 'topilmadi', 'ball' => $natija['matn']];
                continue;
            }
            if (isset($band[$uid])) {
                $hisobot[] = ['qator' => $q['qator'], 'ism' => $q['ism'],
                    'holat' => 'takror', 'ball' => $natija['matn']];
                continue;
            }
            if ($natija['ball'] === null) {
                // BO'SH katak XATO EMAS — shu fandan test topshirmagan
                // o'quvchi (haqiqiy faylda ko'p uchraydi). Matn bo'lsa esa
                // (masalan «kelmadi») — bu tuzatish kerak bo'lgan hol.
                $hisobot[] = ['qator' => $q['qator'], 'ism' => $q['ism'],
                    'holat' => ($natija['matn'] === '') ? 'bosh' : 'ballyoq',
                    'ball' => $natija['matn']];
                continue;
            }

            $band[$uid] = true;
            $ball[$uid] = $natija['ball'];
            $hisobot[] = ['qator' => $q['qator'], 'ism' => $q['ism'],
                'holat' => $usul, 'ball' => $natija['matn'],
                'userid' => $uid,
                'moodle' => dashboard::ism(
                    $oquvchilar[$uid]->firstname, $oquvchilar[$uid]->lastname)];
        }

        return ['ball' => $ball, 'hisobot' => $hisobot];
    }

    /**
     * Katakdagi qiymatni ballga aylantiradi.
     *
     * Qabul qiladi: `18` · `18,5` · `18.5` · `90%` (maksimal ballning foizi).
     * Matn (`kelmadi`, `n/a`) — ball EMAS, hisobotda ko'rinadi.
     */
    public static function ball_parse(string $xom, int $maxscore): array {
        $xom = trim($xom);
        if ($xom === '') {
            return ['ball' => null, 'matn' => ''];
        }
        $foiz = (strpos($xom, '%') !== false);
        $son = str_replace([' ', '%'], '', $xom);
        $son = str_replace(',', '.', $son);

        if (!is_numeric($son)) {
            return ['ball' => null, 'matn' => $xom];
        }
        $v = (float) $son;
        if ($foiz) {
            $v = $maxscore * $v / 100;
        }
        $v = max(0, min((float) $maxscore, $v));
        return ['ball' => round($v, 2), 'matn' => $xom];
    }
}
