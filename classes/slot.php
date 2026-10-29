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

        // O'tgan sanalarni muzlatamiz
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
