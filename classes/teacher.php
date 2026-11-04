<?php
// BITTA O'QITUVCHI kesimidagi o'qish qatlami — o'qituvchi paneli uchun.
//
// NEGA ALOHIDA SINF: `dashboard` butun maktabni KUN kesimida ko'radi
// (direktorning savoli "bugun nima bo'ldi?"), `reyting` esa o'qituvchilarni
// bir-biriga taqqoslaydi. O'qituvchining o'z savoli boshqa: "MENING hamma
// darslarim bo'yicha nima holat va qaysi darsda nima yetishmayapti?" —
// ya'ni bitta odam, uzun davr, dars-dars kesimi. Shu so'rovlar o'z joyida
// turadi, aks holda `dashboard` uchinchi vazifani ham olib yuk bo'lardi.
//
// MUHIM: hamma hisob `_fact.attributeduserid` bo'yicha — ya'ni dars
// HAQIQATDA KIM YOZGANIGA qarab (attribution qoidasi, DIREKTOR_PANELI.md
// 3.4). O'rnini bosgan odam o'z hisobotida o'zi yozgan darsni ko'radi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class teacher {

    /** Qatorlar sahifasidagi dars soni. */
    const QATOR = 30;

    /** Kategoriya profili uchun eng kam namuna — bundan kam bo'lsa susaytiriladi. */
    const KAM_NAMUNA = 5;

    /**
     * Davr oralig'i.
     *
     * `hammasi` — kuzatuv boshlanishidan bugungacha. O'qituvchining asosiy
     * savoli "BARCHA darslarim" bo'lgani uchun u ham tanlov sifatida bor;
     * `reyting::period()` da bunday kod yo'q, chunki reyting doim taqqoslanadigan
     * teng davrni talab qiladi.
     *
     * @param string $kod hafta | oy | chorak | hammasi
     */
    public static function period(string $kod): array {
        if ($kod !== 'hammasi') {
            return reyting::period($kod);
        }
        $from = dashboard::kuzatuv_boshi();
        $to = date('Y-m-d');
        $kun = max(1, (int) round((strtotime($to) - strtotime($from)) / DAYSECS) + 1);
        return [
            'kod' => 'hammasi',
            'nom' => get_string('oq_all', 'local_targetzenit'),
            'from' => $from,
            'to' => $to,
            'kun' => $kun,
            // Oldingi teng davr YO'Q — kuzatuvdan oldin ma'lumot yo'q.
            'oldin_from' => $from,
            'oldin_to' => $from,
        ];
    }

    /** Mavjud davr kodlari — sahifa va bu sinf bitta ro'yxatdan yuradi. */
    public static function periods(): array {
        return [
            'hafta' => 'dir_week',
            'oy' => 'dir_month',
            'chorak' => 'dir_term',
            'hammasi' => 'oq_all',
        ];
    }

    /**
     * Davr bo'yicha umumiy hisob.
     *
     * Foizlar FAQAT vaqti o'tgan darslardan hisoblanadi — hali bo'lmagan
     * darsni "bajarilmagan" deb ko'rsatish yolg'on bo'lardi.
     *
     * Baho ALOHIDA turadi va majburiy uchlikka (mavzu, yo'qlama, yozuv)
     * kirmaydi: baho har darsda kutilmaydi, aks holda baho qo'yilmagan
     * mutlaqo normal dars "bajarilmagan" bo'lib chiqardi.
     */
    public static function summary(int $userid, array $davr): \stdClass {
        global $DB;

        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        $r = $DB->get_record_sql("
            SELECT COUNT(1) AS jami,
                   SUM(CASE WHEN $otgan THEN 1 ELSE 0 END) AS otgan,
                   SUM(CASE WHEN $otgan AND f.hastopic = 1 THEN 1 ELSE 0 END) AS mavzu,
                   SUM(CASE WHEN $otgan AND f.hashomework = 1 THEN 1 ELSE 0 END) AS vazifa,
                   SUM(CASE WHEN $otgan AND f.attendancecount > 0 THEN 1 ELSE 0 END) AS yoqlama,
                   SUM(CASE WHEN $otgan AND f.gradecount > 0 THEN 1 ELSE 0 END) AS baho,
                   SUM(CASE WHEN $otgan AND f.audiostatus IN ($yoz) THEN 1 ELSE 0 END) AS yozuv,
                   SUM(CASE WHEN $otgan THEN f.audioseconds ELSE 0 END) AS audiosoniya,
                   SUM(CASE WHEN f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
                            THEN 1 ELSE 0 END) AS tahlil,
                   SUM(CASE WHEN f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
                            THEN f.overallscore ELSE 0 END) AS ballyigindi,
                   SUM(CASE WHEN f.substituted = 1 THEN 1 ELSE 0 END) AS orninbosdi
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b",
            ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']]);

        $otgansoni = (int) ($r->otgan ?? 0);
        $tahlil = (int) ($r->tahlil ?? 0);

        return (object) [
            'jami' => (int) ($r->jami ?? 0),
            'otgan' => $otgansoni,
            'mavzu' => dashboard::foiz((int) $r->mavzu, $otgansoni),
            'vazifa' => dashboard::foiz((int) $r->vazifa, $otgansoni),
            'yoqlama' => dashboard::foiz((int) $r->yoqlama, $otgansoni),
            'baho' => dashboard::foiz((int) $r->baho, $otgansoni),
            'yozuv' => dashboard::foiz((int) $r->yozuv, $otgansoni),
            'yozuvsoni' => (int) $r->yozuv,
            'audiosoniya' => (int) ($r->audiosoniya ?? 0),
            'tahlil' => $tahlil,
            'ball' => $tahlil ? (int) round(((float) $r->ballyigindi) / $tahlil) : null,
            'orninbosdi' => (int) ($r->orninbosdi ?? 0),
            // Majburiy uchlikning HAMMASI bajarilgan darslar ulushi
            'tolik' => self::tolik_foiz($userid, $davr),
        ];
    }

    /** Majburiy uchlik (mavzu + yo'qlama + yozuv) to'liq bajarilgan darslar ulushi. */
    protected static function tolik_foiz(int $userid, array $davr): ?int {
        global $DB;

        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        $r = $DB->get_record_sql("
            SELECT COUNT(1) AS otgan,
                   SUM(CASE WHEN f.hastopic = 1 AND f.attendancecount > 0
                             AND f.audiostatus IN ($yoz) THEN 1 ELSE 0 END) AS tolik
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b AND $otgan",
            ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']]);

        return dashboard::foiz((int) ($r->tolik ?? 0), (int) ($r->otgan ?? 0));
    }

    /** Filtr kodlari va ularning SQL shartlari. */
    protected static function filtr_sql(string $filter): string {
        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        switch ($filter) {
            // Diqqat talab qiladigan darslar: majburiy uchlikdan biri yo'q.
            case 'nuqson':
                return "$otgan AND (f.hastopic = 0 OR f.attendancecount = 0"
                    . " OR f.audiostatus NOT IN ($yoz))";
            case 'yozuvsiz':
                return "$otgan AND f.audiostatus NOT IN ($yoz)";
            case 'tahlilli':
                return "f.analysestatus = 'ok' AND f.overallscore IS NOT NULL";
            case 'kelgusi':
                return "NOT $otgan";
            default:
                return '';
        }
    }

    /** Filtr tugmalari uchun sanoqlar. */
    public static function counts(int $userid, array $davr): array {
        global $DB;

        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        $r = $DB->get_record_sql("
            SELECT COUNT(1) AS jami,
                   SUM(CASE WHEN $otgan AND (f.hastopic = 0 OR f.attendancecount = 0
                             OR f.audiostatus NOT IN ($yoz)) THEN 1 ELSE 0 END) AS nuqson,
                   SUM(CASE WHEN $otgan AND f.audiostatus NOT IN ($yoz) THEN 1 ELSE 0 END) AS yozuvsiz,
                   SUM(CASE WHEN f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
                            THEN 1 ELSE 0 END) AS tahlilli,
                   SUM(CASE WHEN NOT ($otgan) THEN 1 ELSE 0 END) AS kelgusi
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b",
            ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']]);

        return [
            'all' => (int) ($r->jami ?? 0),
            'nuqson' => (int) ($r->nuqson ?? 0),
            'yozuvsiz' => (int) ($r->yozuvsiz ?? 0),
            'tahlilli' => (int) ($r->tahlilli ?? 0),
            'kelgusi' => (int) ($r->kelgusi ?? 0),
        ];
    }

    /**
     * Dars-dars ro'yxat.
     *
     * Eng yangisi YUQORIDA: o'qituvchi odatda oxirgi darslarini tekshiradi,
     * sentabrdagisini emas.
     *
     * @param string $filter all | nuqson | yozuvsiz | tahlilli | kelgusi
     * @return array ['rows' => [...], 'jami' => int]
     */
    public static function lessons(int $userid, array $davr, string $filter = 'all',
                                   int $page = 0, int $perpage = self::QATOR,
                                   array $qosh = []): array {
        global $DB;

        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        $params = ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']];
        $shart = ['f.attributeduserid = :uid', 's.lessondate >= :a', 's.lessondate <= :b'];

        $f = self::filtr_sql($filter);
        if ($f !== '') {
            $shart[] = $f;
        }
        if (!empty($qosh['groupkey'])) {
            $shart[] = 's.groupkey = :gk';
            $params['gk'] = $qosh['groupkey'];
        }
        if (!empty($qosh['subjectkey'])) {
            $shart[] = 's.subjectkey = :sk';
            $params['sk'] = $qosh['subjectkey'];
        }
        $where = 'WHERE ' . implode(' AND ', $shart);

        $manba = "FROM {local_targetzenit_slot} s
                  JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             LEFT JOIN {local_targetzenit_lesson} l ON l.id = f.lessonid";

        $jami = $DB->count_records_sql("SELECT COUNT(1) $manba $where", $params);

        $rows = $DB->get_records_sql("
            SELECT s.id AS slotid, s.lessondate, s.lessonno, s.starttime, s.endtime,
                   s.groupname, s.groupkey, s.subjectname, s.subjectkey, s.courseid,
                   s.room, s.termname,
                   f.lessonid, f.hastopic, f.hashomework, f.studentcount,
                   f.attendancecount, f.gradecount, f.absentcount, f.latecount,
                   f.audiostatus, f.audioseconds, f.analysestatus, f.overallscore,
                   f.normdelta, f.lowconfcount, f.substituted, f.vacancy, f.latehours,
                   f.feedbackcount,
                   CASE WHEN $otgan THEN 1 ELSE 0 END AS otgan,
                   CASE WHEN f.audiostatus IN ($yoz) THEN 1 ELSE 0 END AS yozuvbor,
                   l.topic, l.homework
              $manba $where
          ORDER BY s.lessondate DESC, s.lessonno DESC", $params, $page * $perpage, $perpage);

        $out = [];
        foreach ($rows as $r) {
            $r->otgan = (int) $r->otgan === 1;
            $r->yozuvbor = (int) $r->yozuvbor === 1;
            $r->band = dashboard::band($r->overallscore === null ? null : (int) $r->overallscore);
            $r->topicshort = \core_text::substr(trim((string) $r->topic), 0, 70);
            $r->hwshort = \core_text::substr(trim((string) $r->homework), 0, 70);
            $out[] = $r;
        }
        return ['rows' => $out, 'jami' => (int) $jami];
    }

    /**
     * Bir kundagi darslarning JURNAL HOLATI — jadvalning "bugun"
     * ko'rinishidagi amal tugmalari uchun.
     *
     * Kalit: "{courseid}:{lessonno}" — jadval qatorida aynan shu ikkisi bor.
     *
     * NEGA `_slot`/`_fact` EMAS: fakt rejalashtirilgan vazifa bilan quriladi
     * va bugungi dars uchun hali qator bo'lmasligi mumkin. Jurnal yozuvi esa
     * o'qituvchi dars sahifasini ochishi bilan paydo bo'ladi — tugma holati
     * aynan shunga bog'liq.
     *
     * @return array [kalit => stdClass{lessonid, topic, audiostatus, duration, yozuvlar}]
     */
    public static function day_state(string $date, array $courseids): array {
        global $DB;

        $courseids = array_values(array_unique(array_filter($courseids)));
        if (!$courseids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['d'] = $date;

        $rows = $DB->get_records_sql("
            SELECT l.id AS lessonid, l.courseid, l.lessonno, l.topic, l.homework,
                   a.status AS audiostatus, a.duration,
                   (SELECT COUNT(1) FROM {local_targetzenit_record} r
                     WHERE r.lessonid = l.id) AS yozuvlar
              FROM {local_targetzenit_lesson} l
         LEFT JOIN {local_targetzenit_audio} a ON a.lessonid = l.id
             WHERE l.lessondate = :d AND l.courseid $in", $params);

        $out = [];
        foreach ($rows as $r) {
            $r->yozuvbor = in_array((string) $r->audiostatus,
                ['ready', 'transcribed', 'deleted'], true);
            $out[(int) $r->courseid . ':' . (int) $r->lessonno] = $r;
        }
        return $out;
    }

    /**
     * Guruh × fan kesimi — "qaysi sinfda qanday ishlayapman".
     *
     * @return array qatorlar: guruh, fan, darslar, bajarilgan, ball, n
     */
    public static function breakdown(int $userid, array $davr): array {
        global $DB;

        $otgan = dashboard::otgan_sql();
        $yoz = dashboard::YOZILGAN;

        // `get_recordset_sql` — guruh ikki ustundan iborat, `get_records_sql`
        // birinchi ustunni kalit qilib qatorlarni yo'qotardi.
        $rs = $DB->get_recordset_sql("
            SELECT s.groupkey, s.subjectkey,
                   MAX(s.groupname) AS groupname, MAX(s.subjectname) AS subjectname,
                   COUNT(1) AS jami,
                   SUM(CASE WHEN $otgan THEN 1 ELSE 0 END) AS otgan,
                   SUM(CASE WHEN $otgan AND f.hastopic = 1 AND f.attendancecount > 0
                             AND f.audiostatus IN ($yoz) THEN 1 ELSE 0 END) AS tolik,
                   SUM(CASE WHEN f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
                            THEN 1 ELSE 0 END) AS tahlil,
                   SUM(CASE WHEN f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
                            THEN f.overallscore ELSE 0 END) AS ballyigindi
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b
          GROUP BY s.groupkey, s.subjectkey",
            ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']]);

        $out = [];
        foreach ($rs as $r) {
            $tahlil = (int) $r->tahlil;
            $out[] = (object) [
                'groupkey' => (string) $r->groupkey,
                'subjectkey' => (string) $r->subjectkey,
                'groupname' => (string) $r->groupname,
                'subjectname' => (string) $r->subjectname,
                'jami' => (int) $r->jami,
                'otgan' => (int) $r->otgan,
                'tolik' => dashboard::foiz((int) $r->tolik, (int) $r->otgan),
                'tahlil' => $tahlil,
                'ball' => $tahlil ? (int) round(((float) $r->ballyigindi) / $tahlil) : null,
            ];
        }
        $rs->close();

        usort($out, fn($a, $b) => strnatcasecmp($a->groupname . $a->subjectname,
                                                $b->groupname . $b->subjectname));
        return $out;
    }

    /**
     * 15 kategoriya profili — har biri O'Z xalqaro o'rtachasiga nisbatan.
     *
     * Tartib `ai::CATEGORIES` dagidek (guruhlar bo'yicha), ballsiz
     * kategoriyalar ham qatorda qoladi: "audio orqali baholanmadi" bo'sh
     * joydan ko'ra ko'proq ma'lumot beradi (5-tamoyil).
     */
    public static function profile(int $userid, array $davr): array {
        $xom = aicat::profile($userid, $davr['from'], $davr['to']);

        $out = [];
        foreach (ai::CATEGORIES as $kod => $k) {
            $bor = $xom[$kod] ?? null;
            $out[] = (object) [
                'kod' => $kod,
                'nom' => $k['nom'],
                'guruh' => $k['guruh'],
                'vazn' => $k['vazn'],
                'norma' => $k['norm'],
                'avg' => $bor ? $bor['avg'] : null,
                'delta' => $bor ? round($bor['avg'] - $k['norm'], 1) : null,
                'n' => $bor ? $bor['n'] : 0,
                'kam' => $bor ? ($bor['n'] < self::KAM_NAMUNA) : false,
            ];
        }
        return $out;
    }

    /** Profildan eng kuchli va eng kuchsiz tomon (normaga nisbatan). */
    public static function kuchli_kuchsiz(array $profil): array {
        $bor = array_values(array_filter($profil, fn($p) => $p->delta !== null));
        if (!$bor) {
            return ['kuchli' => null, 'kuchsiz' => null];
        }
        usort($bor, fn($a, $b) => $b->delta <=> $a->delta);
        return ['kuchli' => $bor[0], 'kuchsiz' => end($bor)];
    }

    /**
     * Umumiy ball trendi — sparkline uchun.
     *
     * Davr kunlar bo'yicha o'rtachaga yig'iladi, keyin PHP'da teng
     * bo'laklarga bo'linadi. SQL'da hafta funksiyasi ishlatilmaydi: u
     * MySQL va PostgreSQL'da boshqa-boshqa yoziladi.
     *
     * @return array foizlar ro'yxati (null bo'lmaydi)
     */
    public static function trend(int $userid, array $davr, int $nuqta = 10): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT s.lessondate,
                   SUM(f.overallscore) AS yigindi,
                   COUNT(1) AS soni
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b
               AND f.analysestatus = 'ok' AND f.overallscore IS NOT NULL
          GROUP BY s.lessondate
          ORDER BY s.lessondate ASC",
            ['uid' => $userid, 'a' => $davr['from'], 'b' => $davr['to']]);

        $kunlar = array_values($rows);
        if (count($kunlar) < 2) {
            return [];
        }
        $bolak = max(1, (int) ceil(count($kunlar) / $nuqta));
        $out = [];
        foreach (array_chunk($kunlar, $bolak) as $guruh) {
            $y = 0;
            $n = 0;
            foreach ($guruh as $k) {
                $y += (float) $k->yigindi;
                $n += (int) $k->soni;
            }
            if ($n) {
                $out[] = (int) round($y / $n);
            }
        }
        return $out;
    }

    /**
     * Jadvaldagi yuklama va fanlar (`_staff` dan).
     *
     * `_staff` o'quv yili kesimida qurilgan o'lchov jadvali; yo'q bo'lsa
     * (kalit nomuvofiq yoki hali qurilmagan) null qaytadi va sahifa bu
     * qatorni ko'rsatmaydi — nol deb ko'rsatish yolg'on bo'lardi.
     */
    public static function load(int $userid, string $year): ?\stdClass {
        return staff::by_user($userid, $year);
    }

    /**
     * Sahifa kimni ko'rsatadi: o'zinimi yoki boshqa odamnimi.
     *
     * O'qituvchi O'ZI haqida hamma narsani ko'radi (qaror 2026-10-29), boshqa
     * odamni ko'rish uchun `viewteachers` kerak.
     */
    public static function korish_mumkinmi(int $userid): bool {
        global $USER;
        if ($userid === (int) $USER->id) {
            return true;
        }
        return has_capability('local/targetzenit:viewteachers', \context_system::instance());
    }

    /**
     * Yozuv lentasining guruh filtri — FAQAT shu odamning guruhlari.
     *
     * `dashboard::recording_filters()` butun maktabni qaytaradi (direktor
     * uchun); o'qituvchiga esa o'zi dars o'tmagan sinflar ro'yxati keraksiz
     * va u boshqa odamning qamrovini ko'rsatib qo'yadi.
     *
     * @return array [groupkey => groupname]
     */
    public static function record_groups(int $userid, string $from, string $to): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT DISTINCT s.groupkey, s.groupname
              FROM {local_targetzenit_audio} a
              JOIN {local_targetzenit_lesson} l ON l.id = a.lessonid
              JOIN {local_targetzenit_fact} f ON f.lessonid = l.id
              JOIN {local_targetzenit_slot} s ON s.id = f.slotid
             WHERE COALESCE(f.attributeduserid, l.teacherid) = :uid
               AND l.lessondate >= :a AND l.lessondate <= :b
               AND s.groupkey IS NOT NULL AND s.groupkey <> ''
          ORDER BY s.groupname ASC",
            ['uid' => $userid, 'a' => $from, 'b' => $to]);

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->groupkey] = (string) $r->groupname;
        }
        return $out;
    }

    /** Shu odamda umuman dars bormi — menyuda havola ko'rsatishni hal qiladi. */
    public static function has_any(int $userid): bool {
        global $DB;
        return $DB->record_exists('local_targetzenit_fact', ['attributeduserid' => $userid]);
    }
}
