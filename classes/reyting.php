<?php
// REYTINGLAR — o'qituvchi, sinf va fan kesimida.
//
// IKKI QAT'IY QOIDA (ikkalasi ham tajribadan kelib chiqqan):
//
// 1) NORMAGA NISBATAN, mutlaq emas. Xalqaro o'rtacha — 57% (GTT, CLASS va ICALT
//    tadqiqotlari, `ai::norm_overall()`). Mutlaq shkalada 61% "yomon"
//    ko'rinadi, aslida esa o'rtachadan yuqori. Kuchli/kuchsiz tomon ham
//    HAR KATEGORIYANING O'Z normasiga nisbatan hisoblanadi: "Tabaqalashtirilgan
//    yondashuv 54%" mutlaq shkalada eng past ko'rinadi, lekin uning xalqaro
//    o'rtachasi 35% — ya'ni bu aslida KUCHLI tomon.
//
// 2) NAMUNA HAJMI. Ikki ta darsdan chiqqan 95% — fakt emas, tasodif.
//    {@see MIN_NAMUNA} dan kam dars tahlil qilingan o'qituvchi asosiy
//    reytingga kirmaydi, alohida ro'yxatda ko'rsatiladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class reyting {

    /** Reytingga kirish uchun eng kam tahlil qilingan dars soni. */
    const MIN_NAMUNA = 3;

    /** Teng natijada ko'proq dars tahlil qilingan yuqorida turadi. */
    const TENG_FARQ = 0.5;

    /**
     * Davr oralig'i va undan oldingi teng uzunlikdagi oraliq (trend uchun).
     *
     * @param string $kod hafta | oy | chorak
     */
    public static function period(string $kod, ?string $bugun = null): array {
        $bugun = $bugun ?: date('Y-m-d');
        $ts = strtotime($bugun);

        if ($kod === 'hafta') {
            $dow = (int) date('N', $ts);
            $from = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
            $nom = 'Bu hafta';
        } else if ($kod === 'chorak') {
            $from = null;
            $nom = 'Chorak';
            $year = timetable::current_year();
            foreach ($year ? timetable::terms($year) : [] as $t) {
                if ($bugun >= $t->startdate && $bugun <= $t->enddate) {
                    $from = $t->startdate;
                    $nom = $t->name;
                    break;
                }
            }
            if (!$from) {
                // Chorakdan tashqarida (ta'til) — oxirgi 90 kun
                $from = date('Y-m-d', strtotime('-90 days', $ts));
            }
        } else {
            $kod = 'oy';
            $from = date('Y-m-01', $ts);
            $nom = 'Bu oy';
        }

        // Kuzatuv boshlanishidan oldin qaramaymiz
        $from = max($from, dashboard::kuzatuv_boshi());

        $kun = max(1, (int) round((strtotime($bugun) - strtotime($from)) / DAYSECS) + 1);
        $oldin_to = date('Y-m-d', strtotime('-1 day', strtotime($from)));
        $oldin_from = date('Y-m-d', strtotime('-' . ($kun - 1) . ' days', strtotime($oldin_to)));

        return ['kod' => $kod, 'nom' => $nom, 'from' => $from, 'to' => $bugun,
                'kun' => $kun, 'oldin_from' => $oldin_from, 'oldin_to' => $oldin_to];
    }

    /**
     * Tahlil qilingan darslar bo'yicha yig'indi.
     *
     * YIG'INDI VA SANOQ qaytariladi, o'rtacha EMAS — o'rtachalar davrlar
     * bo'ylab qo'shilmaydi, yig'indi esa qo'shiladi.
     *
     * @param string $kesim teacher | class | subject
     */
    protected static function agg(string $kesim, string $from, string $to,
                                   array $filtr = []): array {
        global $DB;

        $maydon = [
            'teacher' => 'f.attributeduserid',
            'class' => 's.groupkey',
            'subject' => 's.subjectkey',
        ][$kesim] ?? 'f.attributeduserid';

        $params = ['a' => $from, 'b' => $to];
        $shart = ["s.lessondate >= :a", "s.lessondate <= :b",
                  "f.analysestatus = 'ok'", 'f.overallscore IS NOT NULL',
                  "$maydon IS NOT NULL"];

        if (!empty($filtr['subjectkey'])) {
            $shart[] = 's.subjectkey = :fs';
            $params['fs'] = $filtr['subjectkey'];
        }
        if (!empty($filtr['groupkey'])) {
            $shart[] = 's.groupkey = :fg';
            $params['fg'] = $filtr['groupkey'];
        }
        if (!empty($filtr['userid'])) {
            $shart[] = 'f.attributeduserid = :fu';
            $params['fu'] = $filtr['userid'];
        }
        $where = 'WHERE ' . implode(' AND ', $shart);

        // Ko'rsatiladigan nom kesimga qarab
        $nom = [
            'teacher' => 'MAX(u.lastname) AS lastname, MAX(u.firstname) AS firstname,
                          MAX(st.teachername) AS teachername, MAX(s.subjectname) AS subjectname',
            'class' => 'MAX(s.groupname) AS nomi',
            'subject' => 'MAX(s.subjectname) AS nomi',
        ][$kesim];

        $join = $kesim === 'teacher'
            ? "LEFT JOIN {user} u ON u.id = f.attributeduserid
               LEFT JOIN {local_targetzenit_staff} st
                      ON st.academicyear = s.academicyear AND st.teacherkey = s.teacherkey"
            : '';

        $rows = $DB->get_records_sql("
            SELECT $maydon AS kalit,
                   COUNT(1) AS darslar,
                   SUM(f.overallscore) AS ballyigindi,
                   $nom
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
              $join
              $where
          GROUP BY $maydon", $params);

        $out = [];
        foreach ($rows as $r) {
            $darslar = (int) $r->darslar;
            if (!$darslar) {
                continue;
            }
            $out[(string) $r->kalit] = (object) [
                'kalit' => (string) $r->kalit,
                'darslar' => $darslar,
                'ball' => round(((float) $r->ballyigindi) / $darslar, 1),
                'nomi' => $kesim === 'teacher'
                    ? (($r->firstname || $r->lastname)
                        ? dashboard::ism($r->firstname, $r->lastname)
                        : ($r->teachername ?: '—'))
                    : (string) ($r->nomi ?? ''),
                'fan' => $kesim === 'teacher' ? (string) ($r->subjectname ?? '') : '',
            ];
        }
        return $out;
    }

    /**
     * Kategoriya profili — kesim bo'yicha, har kategoriyaning O'Z normasiga
     * nisbatan.
     *
     * `confidence = low` ballar hisobga olinmaydi (`ai::overall()` bilan bir
     * xil qoida).
     *
     * @return array [kalit => [code => ['avg' => float, 'delta' => float, 'n' => int]]]
     */
    public static function categories(string $kesim, string $from, string $to): array {
        global $DB;

        $maydon = [
            'teacher' => 'f.attributeduserid',
            'class' => 's.groupkey',
            'subject' => 's.subjectkey',
        ][$kesim] ?? 'f.attributeduserid';

        // `get_recordset_sql` — `get_records_sql` BIRINCHI ustunni kalit qiladi va
        // takrorlansa qatorlarni YO'QOTADI. Bu yerda guruh (kesim + kategoriya)
        // ikki ustundan iborat, shuning uchun rekordset ishlatiladi.
        $rs = $DB->get_recordset_sql("
            SELECT $maydon AS kalit, ac.code,
                   AVG(ac.score) AS ortacha, COUNT(1) AS soni
              FROM {local_targetzenit_aicat} ac
              JOIN {local_targetzenit_fact} f ON f.lessonid = ac.lessonid
              JOIN {local_targetzenit_slot} s ON s.id = f.slotid
             WHERE s.lessondate >= :a AND s.lessondate <= :b
               AND (ac.confidence IS NULL OR ac.confidence <> :low)
               AND $maydon IS NOT NULL
          GROUP BY $maydon, ac.code",
            ['a' => $from, 'b' => $to, 'low' => 'low']);

        $out = [];
        foreach ($rs as $r) {
            $kod = (string) $r->code;
            if (!isset(ai::CATEGORIES[$kod])) {
                continue;
            }
            $avg = round((float) $r->ortacha, 1);
            $out[(string) $r->kalit][$kod] = [
                'avg' => $avg,
                // NORMAGA NISBATAN: "Tabaqalashtirilgan yondashuv 54%" mutlaq
                // shkalada eng past, lekin normasi 35% — ya'ni +19, kuchli tomon.
                'delta' => round($avg - ai::CATEGORIES[$kod]['norm'], 1),
                'n' => (int) $r->soni,
                'nom' => ai::cat_name($kod),
                'ishonchli' => (int) $r->soni >= ai::min_n($kod),
            ];
        }
        $rs->close();
        return $out;
    }

    /**
     * Profildan eng kuchli va eng kuchsiz tomon (normaga nisbatan).
     *
     * `min_n` bajarilmagan mezon chiqarib tashlanadi (Praetorius 2014) —
     * tasodifiy tebranishni "kuchsiz tomon" deb atash o'qituvchini noto'g'ri
     * yo'naltiradi.
     */
    public static function kuchli_kuchsiz(array $profil): array {
        $profil = array_filter($profil, fn($p) => !empty($p['ishonchli']));
        if (!$profil) {
            return ['kuchli' => null, 'kuchsiz' => null];
        }
        $tartib = $profil;
        uasort($tartib, fn($a, $b) => $b['delta'] <=> $a['delta']);
        return [
            'kuchli' => reset($tartib) ?: null,
            'kuchsiz' => end($tartib) ?: null,
        ];
    }

    /**
     * O'qituvchilar reytingi.
     *
     * @return array ['rows' => [...], 'kam' => [...]] — `kam`: namunasi yetarli emas
     */
    public static function teachers(array $davr, array $filtr = []): array {
        $joriy = self::agg('teacher', $davr['from'], $davr['to'], $filtr);
        $oldin = self::agg('teacher', $davr['oldin_from'], $davr['oldin_to'], $filtr);
        $profil = self::categories('teacher', $davr['from'], $davr['to']);

        $rows = [];
        $kam = [];

        foreach ($joriy as $kalit => $r) {
            $kk = self::kuchli_kuchsiz($profil[$kalit] ?? []);
            $eski = $oldin[$kalit] ?? null;

            $qator = (object) [
                'kalit' => $kalit,
                'userid' => (int) $kalit,
                'nomi' => $r->nomi,
                'fan' => $r->fan,
                'darslar' => $r->darslar,
                'ball' => $r->ball,
                'band' => dashboard::band((int) round($r->ball)),
                'oldingi' => $eski ? $eski->ball : null,
                'trend' => $eski ? round($r->ball - $eski->ball, 1) : null,
                'kuchli' => $kk['kuchli'],
                'kuchsiz' => $kk['kuchsiz'],
            ];

            if ($r->darslar < self::MIN_NAMUNA) {
                $kam[] = $qator;
            } else {
                $rows[] = $qator;
            }
        }

        // Teng natijada ko'proq dars tahlil qilingan yuqorida
        usort($rows, function($a, $b) {
            if (abs($a->ball - $b->ball) < self::TENG_FARQ) {
                return $b->darslar <=> $a->darslar;
            }
            return $b->ball <=> $a->ball;
        });
        usort($kam, fn($a, $b) => $b->ball <=> $a->ball);

        return ['rows' => $rows, 'kam' => $kam];
    }

    /** Sinf yoki fan reytingi. */
    public static function groups(string $kesim, array $davr, array $filtr = []): array {
        $joriy = self::agg($kesim, $davr['from'], $davr['to'], $filtr);
        $oldin = self::agg($kesim, $davr['oldin_from'], $davr['oldin_to'], $filtr);

        $rows = [];
        foreach ($joriy as $kalit => $r) {
            if ($r->darslar < self::MIN_NAMUNA) {
                continue;
            }
            $eski = $oldin[$kalit] ?? null;
            $rows[] = (object) [
                'kalit' => $kalit,
                'nomi' => $r->nomi,
                'darslar' => $r->darslar,
                'ball' => $r->ball,
                'band' => dashboard::band((int) round($r->ball)),
                'trend' => $eski ? round($r->ball - $eski->ball, 1) : null,
            ];
        }
        usort($rows, function($a, $b) {
            if (abs($a->ball - $b->ball) < self::TENG_FARQ) {
                return $b->darslar <=> $a->darslar;
            }
            return $b->ball <=> $a->ball;
        });
        return $rows;
    }

    /**
     * Maktab bo'yicha umumiy xulosa: o'rtacha, o'zgarish, eng kuchli va
     * rivojlantirish kerak bo'lgan yo'nalishlar.
     */
    public static function summary(array $davr): array {
        global $DB;

        $hisob = function(string $a, string $b) use ($DB) {
            $r = $DB->get_record_sql("
                SELECT COUNT(1) AS darslar, SUM(f.overallscore) AS yigindi
                  FROM {local_targetzenit_slot} s
                  JOIN {local_targetzenit_fact} f ON f.slotid = s.id
                 WHERE s.lessondate >= :a AND s.lessondate <= :b
                   AND f.analysestatus = 'ok' AND f.overallscore IS NOT NULL",
                ['a' => $a, 'b' => $b]);
            $n = (int) ($r->darslar ?? 0);
            return ['n' => $n, 'ball' => $n ? round(((float) $r->yigindi) / $n, 1) : null];
        };

        $joriy = $hisob($davr['from'], $davr['to']);
        $oldin = $hisob($davr['oldin_from'], $davr['oldin_to']);

        // Maktab bo'yicha kategoriya profili — normaga nisbatan tartiblangan
        $rows = $DB->get_records_sql("
            SELECT ac.code, AVG(ac.score) AS ortacha, COUNT(1) AS soni
              FROM {local_targetzenit_aicat} ac
              JOIN {local_targetzenit_fact} f ON f.lessonid = ac.lessonid
              JOIN {local_targetzenit_slot} s ON s.id = f.slotid
             WHERE s.lessondate >= :a AND s.lessondate <= :b
               AND (ac.confidence IS NULL OR ac.confidence <> :low)
          GROUP BY ac.code",
            ['a' => $davr['from'], 'b' => $davr['to'], 'low' => 'low']);

        $profil = [];
        foreach ($rows as $r) {
            $kod = (string) $r->code;
            if (!isset(ai::CATEGORIES[$kod])) {
                continue;
            }
            $avg = round((float) $r->ortacha, 1);
            // Namunasi yetarli bo'lmagan mezon kuchli/kuchsiz ro'yxatiga
            // umuman kirmaydi (Praetorius 2014).
            if ((int) $r->soni < ai::min_n($kod)) {
                continue;
            }
            $profil[] = [
                'code' => $kod,
                'nom' => ai::cat_name($kod),
                'avg' => $avg,
                'norm' => ai::CATEGORIES[$kod]['norm'],
                'delta' => round($avg - ai::CATEGORIES[$kod]['norm'], 1),
                'n' => (int) $r->soni,
            ];
        }
        usort($profil, fn($a, $b) => $b['delta'] <=> $a['delta']);

        return [
            'ball' => $joriy['ball'],
            'n' => $joriy['n'],
            'oldingi' => $oldin['ball'],
            'ozgarish' => ($joriy['ball'] !== null && $oldin['ball'] !== null)
                ? round($joriy['ball'] - $oldin['ball'], 1) : null,
            'norma' => ai::norm_overall(),
            'kuchli' => array_slice($profil, 0, 3),
            'kuchsiz' => array_slice(array_reverse($profil), 0, 3),
        ];
    }

    /** Davr ichidagi haftalik ball trendi (sparkline uchun). */
    public static function trend(array $davr, int $nuqta = 8): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT s.lessondate, COUNT(1) AS darslar, SUM(f.overallscore) AS yigindi
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE s.lessondate >= :a AND s.lessondate <= :b
               AND f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
          GROUP BY s.lessondate
          ORDER BY s.lessondate ASC",
            ['a' => $davr['from'], 'b' => $davr['to']]);

        if (!$rows) {
            return [];
        }
        // Kunlarni teng bo'laklarga yig'amiz — har kuni tahlil bo'lmasligi mumkin
        $kunlar = array_values($rows);
        $bolak = max(1, (int) ceil(count($kunlar) / $nuqta));
        $out = [];
        foreach (array_chunk($kunlar, $bolak) as $guruh) {
            $n = 0;
            $y = 0;
            foreach ($guruh as $k) {
                $n += (int) $k->darslar;
                $y += (float) $k->yigindi;
            }
            if ($n) {
                $out[] = (int) round($y / $n);
            }
        }
        return $out;
    }
}
