<?php
// O'QUV REJA (KTP) MAVZULARI — rejani dars jadvaliga biriktirish.
//
// EGALIK: mavzular ro'yxati FRAPPE da tug'iladi (zam direktor Excel bilan
// yuklaydi), biriktirish esa SHU YERDA bajariladi. Sabab: qaysi kuni dars
// bor, bayram qachon, jadval o'zgardimi — bularning hammasi faqat Moodle'da
// (`_slot`) ma'lum. Frappe'ga slot ro'yxatini tashish ortiqcha aylanma
// bo'lardi.
//
// BIRIKTIRISH QOIDASI (foydalanuvchi qarori, 2026-11-08):
// chorakdagi darslar sanasi bo'yicha tartiblanadi, mavzular esa `tartib`
// bo'yicha — va ustma-ust qo'yiladi. `soat` = mavzu necha darsni egallaydi.
//
//   1-mavzu (2 soat) -> 1- va 2-dars
//   2-mavzu (1 soat) -> 3-dars
//
// NEGA SANA EXCELDA EMAS: jadval o'zgarsa (bayram, almashtirish) sanalar
// siljib ketadi va qo'lda tuzatish kerak bo'lardi. Tartib esa o'zgarmaydi —
// shuning uchun jadval o'zgarganda biriktirish QAYTA hisoblanadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class mavzu {

    /**
     * Chorak nomidan raqamini ajratadi: «2026-2027 (1-chorak)» -> 1.
     *
     * XATO BO'LGAN (2026-11-09 tuzatildi): avval shunchaki BIRINCHI raqam
     * olinardi (`/(\d+)/`). Frappe'dagi `Academic Term` nomi esa
     * «2026-2027 (1-chorak)» ko'rinishida — birinchi raqam 2026 chiqardi.
     * Natijada to'rt chorak ham 2026 kalitini olib, bittasiga aylanardi va
     * 1..4 choraklarning mavzulari HECH QAYSI chorak oralig'iga tushmay
     * «sig'madi» bo'lib qolardi: biriktirildi = 0.
     *
     * Shuning uchun avval «chorak» so'zi yonidagi raqam qidiriladi, keyin
     * 1..4 oralig'idagi yakka raqam; yil (4 xonali) hech qachon olinmaydi.
     */
    public static function chorak_raqami(string $nom): int {
        // «1-chorak», «1 chorak», «2-term», «3 quarter». `(?<!\d)` — oldidan
        // yana raqam bo'lmasin, aks holda «2026-2027 Quarter 4» dan «27» olinadi.
        $naqshlar = [
            '/(?<!\d)(\d{1,2})(?!\d)\s*[-–—]?\s*(?:chorak|term|quarter|четверт)/iu',
            '/(?:chorak|term|quarter|четверт)\w*\s*[-–—]?\s*(?<!\d)(\d{1,2})(?!\d)/iu',
        ];
        foreach ($naqshlar as $naqsh) {
            if (preg_match($naqsh, $nom, $m) && $m[1] >= 1 && $m[1] <= 4) {
                return (int) $m[1];
            }
        }
        // Zaxira: 1..4 oralig'idagi YAKKA raqam (yil emas). Oxirgisini olamiz —
        // «2026-2027 (3)» kabi nomlarda chorak oxirida turadi.
        if (preg_match_all('/(?<!\d)(\d)(?!\d)/', $nom, $m)) {
            foreach (array_reverse($m[1]) as $raqam) {
                if ($raqam >= 1 && $raqam <= 4) {
                    return (int) $raqam;
                }
            }
        }
        return 0;
    }

    /**
     * O'quv yilining choraklari: raqam -> [startdate, enddate].
     *
     * Kalendar Frappe'dan keladi (`_calendar`, entrytype=term). Nomida
     * raqam bo'lmasa, tartib bo'yicha raqamlanadi.
     */
    public static function choraklar(string $year): array {
        $out = [];
        $n = 0;
        foreach (timetable::terms($year) as $t) {
            $n++;
            $raqam = self::chorak_raqami((string) $t->name) ?: $n;
            // HIMOYA: ikki chorak bitta raqamni olsa (nom g'alati bo'lsa),
            // avvalgisi JIMGINA ustiga yozilib ketardi va choraklar soni
            // kamayib qolardi. Bunday holda tartib raqamiga qaytamiz —
            // `terms()` startdate bo'yicha tartiblangan, ya'ni tartib to'g'ri.
            if (isset($out[$raqam])) {
                $raqam = $n;
            }
            $out[$raqam] = ['boshi' => $t->startdate, 'oxiri' => $t->enddate,
                            'nomi' => $t->name];
        }
        ksort($out);
        return $out;
    }

    /**
     * Rejani slotlarga biriktiradi.
     *
     * @param string $year o'quv yili
     * @param array $faqat ixtiyoriy filtr: ['subjectkey' => ..., 'groupkey' => ...]
     * @return array hisobot
     */
    public static function biriktir(string $year, array $faqat = []): array {
        global $DB;

        $shart = ['academicyear' => $year];
        foreach (['subjectkey', 'groupkey'] as $k) {
            if (!empty($faqat[$k])) {
                $shart[$k] = $faqat[$k];
            }
        }

        // Qaysi (fan, guruh) juftligida reja bor
        $juftlar = $DB->get_records_sql(
            "SELECT DISTINCT subjectkey, groupkey
               FROM {local_targetzenit_mavzu}
              WHERE " . self::shart_sql($shart, $p),
            $p);

        $choraklar = self::choraklar($year);
        $natija = ['juftlar' => 0, 'biriktirildi' => 0, 'bosh_slot' => 0,
                   'sigmagan' => 0, 'ogohlantirish' => []];

        foreach ($juftlar as $j) {
            $natija['juftlar']++;
            $hisobot = self::juftlikni_biriktir($year, $j->subjectkey, $j->groupkey,
                $choraklar);
            $natija['biriktirildi'] += $hisobot['biriktirildi'];
            $natija['bosh_slot'] += $hisobot['bosh_slot'];
            $natija['sigmagan'] += $hisobot['sigmagan'];
            if ($hisobot['sigmagan']) {
                $natija['ogohlantirish'][] = sprintf(
                    '%s / %s: %d mavzu darsga sig\'madi',
                    $j->subjectkey, $j->groupkey, $hisobot['sigmagan']);
            }
        }
        return $natija;
    }

    /** Oddiy WHERE yig'uvchi (parametrlar havola orqali qaytadi). */
    protected static function shart_sql(array $shart, ?array &$params): string {
        $params = [];
        $bolaklar = [];
        foreach ($shart as $maydon => $qiymat) {
            $bolaklar[] = "$maydon = :$maydon";
            $params[$maydon] = $qiymat;
        }
        return implode(' AND ', $bolaklar);
    }

    /**
     * Bitta (fan, guruh) juftligi uchun biriktirish.
     *
     * Har chorak ALOHIDA hisoblanadi: 1-chorak mavzulari 2-chorak darslariga
     * oqib o'tmasligi kerak. Choraksiz (chorak=0) mavzular esa butun yilga
     * tegishli va chorakli mavzulardan KEYIN joylashtiriladi.
     */
    protected static function juftlikni_biriktir(string $year, string $subjectkey,
                                                  string $groupkey, array $choraklar): array {
        global $DB;

        $hisobot = ['biriktirildi' => 0, 'bosh_slot' => 0, 'sigmagan' => 0];

        $mavzular = $DB->get_records('local_targetzenit_mavzu',
            ['academicyear' => $year, 'subjectkey' => $subjectkey, 'groupkey' => $groupkey],
            'chorak ASC, tartib ASC');
        if (!$mavzular) {
            return $hisobot;
        }

        // Chorak bo'yicha guruhlaymiz
        $bolingan = [];
        foreach ($mavzular as $m) {
            $bolingan[(int) $m->chorak][] = $m;
        }

        foreach ($bolingan as $chorak => $qator) {
            // Chorak oralig'i; chorak=0 (butun yil) bo'lsa — yilning hammasi
            if ($chorak && isset($choraklar[$chorak])) {
                $boshi = $choraklar[$chorak]['boshi'];
                $oxiri = $choraklar[$chorak]['oxiri'];
            } else if ($chorak) {
                $hisobot['sigmagan'] += count($qator);
                continue;           // bunday chorak kalendarda yo'q
            } else {
                $boshi = '0000-00-00';
                $oxiri = '9999-12-31';
            }

            $slotlar = $DB->get_records_select('local_targetzenit_slot',
                'academicyear = :y AND subjectkey = :fan AND groupkey = :guruh
                 AND lessondate >= :a AND lessondate <= :b',
                ['y' => $year, 'fan' => $subjectkey, 'guruh' => $groupkey,
                 'a' => $boshi, 'b' => $oxiri],
                'lessondate ASC, lessonno ASC', 'id, mavzu, mavzuid');

            $slotlar = array_values($slotlar);
            $i = 0;
            foreach ($qator as $m) {
                $soat = max((int) $m->soat, 1);
                for ($k = 0; $k < $soat; $k++) {
                    if (!isset($slotlar[$i])) {
                        $hisobot['sigmagan']++;
                        break 1;        // bu mavzuning qolgan soatlari ham sig'maydi
                    }
                    $slot = $slotlar[$i];
                    $i++;
                    // O'zgarmagan bo'lsa yozmaymiz — bekorga UPDATE qilmaslik
                    // uchun (PostgreSQL'da o'lik qator to'planadi)
                    if ((string) $slot->mavzu === (string) $m->mavzu
                            && (int) $slot->mavzuid === (int) $m->id) {
                        continue;
                    }
                    $DB->update_record('local_targetzenit_slot', (object) [
                        'id' => $slot->id,
                        'mavzu' => $m->mavzu,
                        'mavzuid' => (int) $m->id,
                    ]);
                    $hisobot['biriktirildi']++;
                }
            }

            // Mavzu yetmagan darslar — rejada bo'sh joy, ko'rsatib qo'yamiz
            for ($j = $i; $j < count($slotlar); $j++) {
                $hisobot['bosh_slot']++;
                if ($slotlar[$j]->mavzuid) {
                    $DB->update_record('local_targetzenit_slot', (object) [
                        'id' => $slotlar[$j]->id, 'mavzu' => null, 'mavzuid' => 0,
                    ]);
                }
            }
        }
        return $hisobot;
    }

    /**
     * Shu darsga rejada qanday mavzu turibdi.
     *
     * O'qituvchi kokpitida ko'rsatiladi: «reja bo'yicha bugun shu mavzu».
     * Dars o'tilgach fakt `_lesson.topic` ga yoziladi — reja o'zgarmaydi.
     */
    public static function slot_mavzusi(string $year, string $date, int $lessonno,
                                         string $groupkey, string $subjectkey): ?\stdClass {
        global $DB;

        $slot = $DB->get_record('local_targetzenit_slot', [
            'slotkey' => slot::key($year, $date, $lessonno, $groupkey, $subjectkey),
        ], 'id, mavzu, mavzuid');
        if (!$slot || !$slot->mavzuid) {
            return null;
        }
        return $DB->get_record('local_targetzenit_mavzu', ['id' => $slot->mavzuid]);
    }

    /** Qamrov: rejadagi mavzular va ulardan nechtasi o'tilgan. */
    public static function qamrov(string $year, string $subjectkey, string $groupkey): array {
        global $DB;

        $jami = (int) $DB->count_records('local_targetzenit_mavzu', [
            'academicyear' => $year, 'subjectkey' => $subjectkey, 'groupkey' => $groupkey,
        ]);
        // O'tilgan deb hisoblanadi: slotga mavzu biriktirilgan VA o'sha
        // kun/dars uchun `_lesson` qatori bor (o'qituvchi dars o'tgan)
        $otildi = (int) $DB->get_field_sql(
            "SELECT COUNT(DISTINCT s.mavzuid)
               FROM {local_targetzenit_slot} s
               JOIN {local_targetzenit_lesson} l
                 ON l.courseid = s.courseid AND l.lessondate = s.lessondate
                AND l.lessonno = s.lessonno
              WHERE s.academicyear = :y AND s.subjectkey = :fan
                AND s.groupkey = :guruh AND s.mavzuid > 0",
            ['y' => $year, 'fan' => $subjectkey, 'guruh' => $groupkey]);

        return ['jami' => $jami, 'otildi' => $otildi,
                'foiz' => $jami ? round($otildi / $jami * 100) : 0];
    }
}
