<?php
// REJALASHTIRILGAN DARS O'RNI (slot) — rejaning birligi.
//
//     slot = (o'quv yili, sana, dars raqami, guruh, fan)
//
// NEGA SAQLANADI: `journal::lessons_in_term()` sanalarni har so'rovda PHP'da
// hisoblaydi. Bitta kurs uchun bu arzon, direktor paneli uchun emas —
// 307 kurs x ~70 dars = chorakda ~21 000 dars o'rni. Ularni aylanib chiqib
// keyin saralash/filtrlash/yig'ish SQL'da umuman mumkin emas.
//
// MUZLATISH QOIDASI (eng muhim qaror):
//   o'tgan sanalar uchun slot FAQAT QO'SHILADI, hech qachon o'zgartirilmaydi
//   va o'chirilmaydi.
// Sabab: jadval o'quv yili davomida o'zgaradi (o'qituvchi almashadi, dars
// ko'chadi). O'tmishni ham qayta qursak, direktor ko'rgan "oktabrda 78%"
// ertaga 71% bo'lib qoladi va sababini hech kim topmaydi. Kuzatuv tarixi
// o'zgarmas bo'lishi shart.
//
// Faqat QO'SHISH ruxsat etilgani ataylab: panel birinchi marta ishga
// tushganda sentabr-oktabr ham to'ldirilishi kerak, aks holda chorak
// xaritasi bo'sh bo'ladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class slot {

    /** Bitta qadamda shuncha kun ishlanadi (xotira uchun). */
    const BLOK_KUN = 31;

    /** Taqqoslanadigan maydonlar — biri o'zgarsa qator yangilanadi. */
    const SOLISH = ['dayno', 'termname', 'groupname', 'subjectname', 'teacherkey',
                    'teacheruserid', 'courseidnumber', 'courseid', 'starttime',
                    'endtime', 'room'];

    /**
     * Slot kaliti.
     *
     * NEGA MD5: tabiiy kalit (yil + sana + dars raqami + guruh + fan) uzun
     * CHAR ustunlardan iborat va utf8mb4 da indeks chegarasiga yaqinlashadi.
     * Hash qisqa va aniq; PHP tomonda diff ham xuddi shu kalit bilan ketadi.
     *
     * NEGA `courseidnumber` KALITGA KIRMAYDI: u jadvalda bo'sh bo'lishi mumkin,
     * guruh va fan esa har doim to'lgan (`_timetable` da NOT NULL).
     */
    public static function key(string $year, string $date, int $lessonno,
                              string $groupkey, string $subjectkey): string {
        return md5($year . '|' . $date . '|' . $lessonno . '|' . $groupkey . '|' . $subjectkey);
    }

    /**
     * Jadval x kalendardan slotlarni qayta quradi.
     *
     * @param string $year o'quv yili
     * @param string|null $from YYYY-MM-DD (standart: birinchi chorak boshi)
     * @param string|null $to YYYY-MM-DD (standart: oxirgi chorak oxiri)
     * @param callable|null $xabar CLI uchun progress
     */
    public static function build(string $year, ?string $from = null, ?string $to = null,
                                 ?callable $xabar = null): array {
        global $DB;

        $natija = ['qoshildi' => 0, 'yangilandi' => 0, 'ochirildi' => 0,
                   'muzlatildi' => 0, 'kunlar' => 0, 'tegilmadi' => 0];

        if ($year === '') {
            return $natija;
        }

        $terms = timetable::terms($year);
        if (!$terms) {
            return $natija;   // kalendar yo'q — sanani hisoblab bo'lmaydi
        }
        $holidays = timetable::holidays($year);

        // Oraliq: standart holda butun o'quv yili
        $boshi = $from ?: min(array_map(fn($t) => $t->startdate, $terms));
        $oxiri = $to ?: max(array_map(fn($t) => $t->enddate, $terms));
        if ($boshi > $oxiri) {
            return $natija;
        }

        // Jadval: kun bo'yicha guruhlab olamiz
        $jadval = $DB->get_records('local_targetzenit_timetable',
            ['academicyear' => $year], 'dayno ASC, lessonno ASC');
        if (!$jadval) {
            return $natija;
        }
        $kunlar = [];
        foreach ($jadval as $j) {
            $kunlar[(int) $j->dayno][] = $j;
        }

        $userlar = staff::key_to_user($year);
        $kurslar = timetable::course_ids(array_map(fn($r) => $r->courseidnumber, $jadval));
        $bugun = date('Y-m-d');
        $now = time();

        // Blok-blok ishlaymiz: butun yilni bir vaqtda xotiraga olmaymiz
        $blok_boshi = $boshi;
        while ($blok_boshi <= $oxiri) {
            $blok_oxiri = min($oxiri, date('Y-m-d',
                strtotime('+' . (self::BLOK_KUN - 1) . ' days', strtotime($blok_boshi))));

            $kerakli = [];
            $kun = strtotime($blok_boshi);
            $chegara = strtotime($blok_oxiri);
            while ($kun <= $chegara) {
                $sana = date('Y-m-d', $kun);
                $kun = strtotime('+1 day', $kun);

                $holat = timetable::school_day($sana, $terms, $holidays);
                if (!$holat['ok']) {
                    continue;
                }
                $dow = (int) date('N', strtotime($sana));
                if (!isset($kunlar[$dow])) {
                    continue;
                }
                $natija['kunlar']++;

                foreach ($kunlar[$dow] as $j) {
                    $kalit = self::key($year, $sana, (int) $j->lessonno,
                        (string) $j->groupkey, (string) $j->subjectkey);
                    // Bir xil kalit ikki marta chiqsa (jadvalda dublikat qator)
                    // birinchisi qoladi — unikal indeks shunday talab qiladi.
                    if (isset($kerakli[$kalit])) {
                        continue;
                    }
                    $tkey = (string) ($j->teacherkey ?? '');
                    $kerakli[$kalit] = (object) [
                        'slotkey' => $kalit,
                        'academicyear' => $year,
                        'lessondate' => $sana,
                        'dayno' => $dow,
                        'lessonno' => (int) $j->lessonno,
                        'termname' => \core_text::substr((string) $holat['reason'], 0, 255),
                        'groupkey' => (string) $j->groupkey,
                        'groupname' => (string) $j->groupname,
                        'subjectkey' => (string) $j->subjectkey,
                        'subjectname' => (string) $j->subjectname,
                        'teacherkey' => $tkey !== '' ? $tkey : null,
                        'teacheruserid' => $userlar[$tkey] ?? null,
                        'courseidnumber' => $j->courseidnumber ?: null,
                        'courseid' => $kurslar[$j->courseidnumber] ?? null,
                        'starttime' => $j->starttime,
                        'endtime' => $j->endtime,
                        'room' => $j->room,
                        'frozen' => $sana < $bugun ? 1 : 0,
                        'timemodified' => $now,
                    ];
                }
            }

            // Shu blokdagi mavjud slotlar
            $mavjud = $DB->get_records_select('local_targetzenit_slot',
                'academicyear = :y AND lessondate >= :a AND lessondate <= :b',
                ['y' => $year, 'a' => $blok_boshi, 'b' => $blok_oxiri]);
            $mavjud_kalit = [];
            foreach ($mavjud as $m) {
                $mavjud_kalit[(string) $m->slotkey] = $m;
            }

            $qoshiladi = [];
            foreach ($kerakli as $kalit => $yangi) {
                if (!isset($mavjud_kalit[$kalit])) {
                    $qoshiladi[] = $yangi;
                    continue;
                }
                $eski = $mavjud_kalit[$kalit];
                unset($mavjud_kalit[$kalit]);

                // MUZLATISH: o'tmishga tegilmaydi
                if ($yangi->lessondate < $bugun) {
                    $natija['tegilmadi']++;
                    continue;
                }
                $ozgardi = false;
                foreach (self::SOLISH as $maydon) {
                    if ((string) ($eski->$maydon ?? '') !== (string) ($yangi->$maydon ?? '')) {
                        $ozgardi = true;
                        break;
                    }
                }
                if ($ozgardi) {
                    $yangi->id = $eski->id;
                    $DB->update_record('local_targetzenit_slot', $yangi);
                    $natija['yangilandi']++;
                }
            }

            if ($qoshiladi) {
                $DB->insert_records('local_targetzenit_slot', $qoshiladi);
                $natija['qoshildi'] += count($qoshiladi);
            }

            // Jadvaldan chiqib ketgan slotlar — faqat KELAJAKDAN o'chiriladi
            foreach ($mavjud_kalit as $ortiqcha) {
                if ($ortiqcha->lessondate < $bugun) {
                    $natija['tegilmadi']++;
                    continue;
                }
                // Fakt qatori ham ketadi — slot yo'q bo'lsa u ma'nosiz
                $DB->delete_records('local_targetzenit_fact', ['slotid' => $ortiqcha->id]);
                $DB->delete_records('local_targetzenit_slot', ['id' => $ortiqcha->id]);
                $natija['ochirildi']++;
            }

            if ($xabar) {
                $xabar("  {$blok_boshi} … {$blok_oxiri}: +{$natija['qoshildi']} / "
                    . "~{$natija['yangilandi']} / -{$natija['ochirildi']}");
            }

            $blok_boshi = date('Y-m-d', strtotime('+1 day', strtotime($blok_oxiri)));
        }

        // O'tgan sanalarni muzlatamiz.
        //
        // DIQQAT: bu `build()` ning OXIRIDA turadi, ya'ni `muzdan_chiqar()`
        // bilan ochilgan oraliq shu yurishda qayta qurilib bo'lgach yana
        // muzlatiladi — aynan shu kerak: bir marta yangilanadi, keyin tarix
        // bo'lib qotadi.
        $natija['muzlatildi'] = $DB->count_records_select('local_targetzenit_slot',
            'academicyear = :y AND lessondate < :d AND frozen = 0',
            ['y' => $year, 'd' => $bugun]);
        if ($natija['muzlatildi']) {
            $DB->execute("UPDATE {local_targetzenit_slot}
                             SET frozen = 1
                           WHERE academicyear = :y AND lessondate < :d AND frozen = 0",
                ['y' => $year, 'd' => $bugun]);
        }

        return $natija;
    }

    /**
     * Yangi jadval kuchga kirgan sanadan boshlab slotlarni muzdan chiqaradi.
     *
     * NEGA MUZLATISH QOIDASIGA ISTISNO: odatda o'tgan slotga tegilmaydi —
     * `build()` har yurishda `lessondate < bugun` bo'lgan hammasini muzlatadi,
     * shunda keyin kelgan jadval tarixni qayta yozmaydi.
     *
     * Lekin jadval almashinuvi KECHIKIB kelishi mumkin: 2026-11-09 kechasi
     * yangi jadval Frappe'ga import qilingan, Moodlega esa 11-10 ertalab
     * yetgan. O'sha paytda 11-09 va 11-10 allaqachon muzlatilgan edi, ya'ni
     * ikki kun eski jadvalda qolib ketdi (12 115 slot muzlatilgan, chegara
     * 11-10 da) va hafta ko'rinishida "jadval o'zgarmagan" bo'lib ko'rindi.
     *
     * Shu sababli Frappe jadval bilan birga versiyaning AMAL QILISH SANASINI
     * (`Jadval Versiyasi.amal_boshlanishi`) yuboradi. Sana O'ZGARGANDA — ya'ni
     * yangi versiya kuchga kirganda — shu sanadan bugungacha bo'lgan slotlar
     * muzdan chiqariladi va `build()` ularni yangi jadval bo'yicha qayta
     * quradi.
     *
     * Sana o'zgarmasa HECH NARSA qilinmaydi: aks holda har kunlik sinxron
     * butun o'quv yilini qayta yozib, tarixni yo'q qilardi.
     *
     * Jurnal yozuvlari (`local_targetzenit_lesson`) o'chmaydi — ular
     * `courseid + lessondate + lessonno` bilan saqlanadi, slot id siga
     * bog'lanmagan.
     *
     * @return int muzdan chiqarilgan slot soni
     */
    public static function muzdan_chiqar(string $year, string $sanadan): int {
        global $DB;

        if ($year === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sanadan)) {
            return 0;
        }
        $bugun = timetable::bugun();
        if ($sanadan >= $bugun) {
            return 0;            // kelajak baribir muzlatilmagan
        }
        $shart = 'academicyear = :y AND lessondate >= :a AND lessondate < :b AND frozen = 1';
        $par = ['y' => $year, 'a' => $sanadan, 'b' => $bugun];
        $soni = $DB->count_records_select('local_targetzenit_slot', $shart, $par);
        if ($soni) {
            $DB->execute("UPDATE {local_targetzenit_slot} SET frozen = 0 WHERE $shart", $par);
        }
        return $soni;
    }

    /**
     * Bayram kunlariga tushib qolgan slotlarni tozalaydi.
     *
     * NEGA MUZLATISH QOIDASIGA ISTISNO: odatda o'tgan slotlar o'zgartirilmaydi,
     * chunki jadval o'zgarishi tarixni buzmasligi kerak. Bu holat boshqacha —
     * bayram ro'yxati umuman YUBORILMAGAN edi, ya'ni o'sha darslar hech qachon
     * BO'LMAGAN. Bu jadval o'zgarishi emas, yetishmagan ma'lumotning kelishi.
     *
     * EHTIYOT: jurnal yozuvi (mavzu/yo'qlama/yozuv) bor slot O'CHIRILMAYDI —
     * dars haqiqatda o'tilgan bo'lishi yoki bayram sanasi xato kiritilgan
     * bo'lishi mumkin. Ular alohida sanaladi va qarorni odam qabul qiladi.
     *
     * @param bool $quruq true = faqat hisoblaydi, o'chirmaydi
     */
    public static function holiday_cleanup(string $year, bool $quruq = true): array {
        global $DB;

        $bayramlar = timetable::holidays($year);
        $natija = ['bayram' => count($bayramlar), 'slot' => 0,
                   'ochirildi' => 0, 'tegilmadi' => 0, 'kunlar' => []];
        if (!$bayramlar) {
            return $natija;
        }

        list($in, $params) = $DB->get_in_or_equal(array_keys($bayramlar), SQL_PARAMS_NAMED, 'd');
        $params['y'] = $year;

        $rows = $DB->get_records_sql("
            SELECT s.id, s.lessondate, s.groupname, s.subjectname,
                   COALESCE(f.hastopic, 0) AS hastopic,
                   COALESCE(f.attendancecount, 0) AS yoqlama,
                   COALESCE(f.audiostatus, 'none') AS audiostatus
              FROM {local_targetzenit_slot} s
         LEFT JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE s.academicyear = :y AND s.lessondate $in", $params);

        $ochir = [];
        foreach ($rows as $r) {
            $natija['slot']++;
            $natija['kunlar'][$r->lessondate] = ($natija['kunlar'][$r->lessondate] ?? 0) + 1;

            // Jurnalda iz qoldirgan dars — tegmaymiz
            if ((int) $r->hastopic || (int) $r->yoqlama
                    || !in_array($r->audiostatus, ['none', ''], true)) {
                $natija['tegilmadi']++;
                continue;
            }
            $ochir[] = (int) $r->id;
        }

        if (!$quruq && $ochir) {
            foreach (array_chunk($ochir, 500) as $bolak) {
                list($fin, $fp) = $DB->get_in_or_equal($bolak, SQL_PARAMS_NAMED, 'f');
                $DB->delete_records_select('local_targetzenit_fact', "slotid $fin", $fp);
                $DB->delete_records_select('local_targetzenit_slot', "id $fin", $fp);
            }
            $natija['ochirildi'] = count($ochir);
        } else {
            $natija['ochirildi'] = $quruq ? 0 : 0;
            $natija['ochiriladi'] = count($ochir);
        }

        ksort($natija['kunlar']);
        return $natija;
    }

    /** Sana oralig'idagi slotlar (fakt qayta hisoblash uchun). */
    public static function in_range(string $from, string $to, ?string $year = null): array {
        global $DB;
        $where = 'lessondate >= :a AND lessondate <= :b';
        $params = ['a' => $from, 'b' => $to];
        if ($year !== null && $year !== '') {
            $where .= ' AND academicyear = :y';
            $params['y'] = $year;
        }
        return $DB->get_records_select('local_targetzenit_slot', $where, $params,
            'lessondate ASC, lessonno ASC');
    }
}
