<?php
// Copyright (c) 2026, Target Zenit
//
// PSIXOLOG MODULI — ma'lumot qatlami (PSIXOLOG_ARXITEKTURA.md).
//
// MAXFIYLIK QOIDASI (foydalanuvchi qarori, 2026-11-21): suhbat/case MATNI
// faqat `local/targetzenit:psixolog` ruxsati borga. Rahbariyat `stat()` dan
// faqat SONLARNI oladi — bu klassdan matn qaytaradigan hech bir metod
// psixolog tekshiruvisiz chaqirilmaydi (sahifa `require_psixolog()` qiladi).
//
// Markaziy kurs `PSIXOLOGIYA::<yil>` ni FRAPPE sinxroni yaratadi va to'ldiradi
// (moodle_psixologiya.py): barcha faol o'quvchi student rolida, sinf-sinf
// guruhlarda; xodimlar «Xodimlar» guruhida; psixolog editingteacher.
// Testlar (quiz/feedback) o'sha kursda yashaydi — jadval YO'Q, yadro saqlaydi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class psixolog {

    /** Markaziy kurs idnumber prefiksi — moodle_psixologiya.py bilan BIR XIL. */
    const KURS_PREFIX = 'PSIXOLOGIYA::';

    /** Xodimlar guruhining nomi — moodle_psixologiya.py bilan BIR XIL. */
    const XODIM_GURUH = 'Xodimlar';

    const SUHBAT_TURLARI = ['individual', 'guruhiy', 'otaona', 'kuzatuv'];
    const CASE_MANBALAR = ['kuzatuv', 'oqituvchi', 'otaona', 'murojaat', 'test'];
    const CASE_MUHIMLIK = ['past', 'orta', 'yuqori'];
    const CASE_HOLATLAR = ['ochiq', 'kuzatuvda', 'yopildi'];

    // ------------------------------------------------------------- ruxsat

    public static function mumkinmi(): bool {
        return has_capability('local/targetzenit:psixolog', \context_system::instance());
    }

    public static function rahbarmi(): bool {
        return has_capability('local/targetzenit:viewteachers', \context_system::instance());
    }

    /** Suhbat/case matniga tegadigan HAR amal oldidan — maxfiylik chegarasi. */
    public static function require_psixolog(): void {
        require_capability('local/targetzenit:psixolog', \context_system::instance());
    }

    // ------------------------------------------------------ markaziy kurs

    /** Markaziy kurs (PSIXOLOGIYA::<yil>) yoki null — hali sinxron o'tmagan. */
    public static function kurs(?string $year = null): ?\stdClass {
        global $DB;

        $year = $year ?? timetable::current_year();
        if ($year !== '') {
            $k = $DB->get_record('course', ['idnumber' => self::KURS_PREFIX . $year]);
            if ($k) {
                return $k;
            }
        }
        // Yil topilmasa (jadval hali yuklanmagan bo'lsa ham panel ishlasin):
        // prefiksli eng yangi kurs.
        $rows = $DB->get_records_select('course',
            $DB->sql_like('idnumber', ':p'),
            ['p' => $DB->sql_like_escape(self::KURS_PREFIX) . '%'],
            'idnumber DESC', '*', 0, 1);
        return $rows ? reset($rows) : null;
    }

    /** Kurs guruhlari (sinflar + Xodimlar), tabiiy tartibda: 2 A dan keyin 10 A. */
    public static function guruhlar(int $courseid): array {
        $out = [];
        foreach (groups_get_all_groups($courseid) as $g) {
            $out[(int) $g->id] = format_string($g->name);
        }
        \core_collator::asort($out, \core_collator::SORT_NATURAL);
        // «Xodimlar» ro'yxat oxirida tursin — sinflar orasiga aralashmasin.
        foreach ($out as $id => $nom) {
            if ($nom === self::XODIM_GURUH) {
                unset($out[$id]);
                $out[$id] = $nom;
            }
        }
        return $out;
    }

    /** {userid: guruh nomi} — statistika va ro'yxatlarda sinfni ko'rsatish uchun. */
    public static function guruh_xarita(int $courseid): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT gm.id, gm.userid, g.name
              FROM {groups_members} gm
              JOIN {groups} g ON g.id = gm.groupid
             WHERE g.courseid = :c", ['c' => $courseid]);

        $out = [];
        foreach ($rows as $r) {
            // Odam bitta sinfda; «Xodimlar» dan tashqari birinchisi yetadi.
            if (!isset($out[(int) $r->userid]) || $out[(int) $r->userid] === self::XODIM_GURUH) {
                $out[(int) $r->userid] = format_string($r->name);
            }
        }
        return $out;
    }

    /**
     * Markaziy kursga yozilgan odamlar (faol yozilish). $groupid berilsa shu
     * guruh, bo'lmasa qidiruv bilan (bo'sh qidiruv + guruhsiz = birinchi 50).
     */
    public static function odamlar(int $courseid, int $groupid = 0,
                                    string $q = '', int $limit = 200): array {
        global $DB, $USER;

        $ctx = \context_course::instance($courseid);
        [$esql, $params] = get_enrolled_sql($ctx, '', $groupid, true);

        $where = "u.deleted = 0 AND u.id <> :ozim";
        $params['ozim'] = (int) $USER->id;
        if ($q !== '') {
            $like = '%' . $DB->sql_like_escape($q) . '%';
            $where .= ' AND (' . $DB->sql_like('u.firstname', ':q1', false)
                . ' OR ' . $DB->sql_like('u.lastname', ':q2', false)
                . ' OR ' . $DB->sql_like('u.idnumber', ':q3', false) . ')';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        return $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname, u.idnumber, u.picture,
                   u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
              FROM {user} u
              JOIN ($esql) e ON e.id = u.id
             WHERE $where
          ORDER BY u.lastname, u.firstname", $params, 0, $limit);
    }

    /** Odam o'quvchimi, xodimmi — idnumber prefiksidan (unic-id qoidasi). */
    public static function persontype(\stdClass $user): string {
        $idn = (string) ($user->idnumber ?? '');
        return (str_starts_with($idn, 'HR-EMP') || str_starts_with($idn, 'INSTR-'))
            ? 'staff' : 'student';
    }

    // ----------------------------------------------------------- suhbatlar

    /** {userid: oxirgi suhbat vaqti} — ro'yxatda N+1 bo'lmasin. */
    public static function oxirgi_suhbat(array $userids): array {
        global $DB;

        if (!$userids) {
            return [];
        }
        [$in, $p] = $DB->get_in_or_equal(array_map('intval', $userids), SQL_PARAMS_NAMED);
        $rows = $DB->get_records_sql(
            "SELECT userid, MAX(sana) AS oxirgi
               FROM {local_targetzenit_psix_suhbat}
              WHERE userid $in GROUP BY userid", $p);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->userid] = (int) $r->oxirgi;
        }
        return $out;
    }

    /** {userid: ochiq case soni}. */
    public static function ochiq_case(array $userids): array {
        global $DB;

        if (!$userids) {
            return [];
        }
        [$in, $p] = $DB->get_in_or_equal(array_map('intval', $userids), SQL_PARAMS_NAMED);
        $rows = $DB->get_records_sql(
            "SELECT userid, COUNT(1) AS soni
               FROM {local_targetzenit_psix_case}
              WHERE holat <> 'yopildi' AND userid $in GROUP BY userid", $p);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->userid] = (int) $r->soni;
        }
        return $out;
    }

    public static function suhbat_qosh(int $userid, string $persontype, int $sana,
                                        string $turi, string $matn, string $tavsiya,
                                        int $caseid = 0): int {
        global $DB, $USER;

        self::require_psixolog();
        if (!in_array($turi, self::SUHBAT_TURLARI, true)) {
            $turi = 'individual';
        }
        $matn = trim($matn);
        if ($matn === '') {
            throw new \moodle_exception('invaliddata', 'error');
        }
        if ($caseid && !$DB->record_exists('local_targetzenit_psix_case', ['id' => $caseid])) {
            $caseid = 0;
        }
        $id = $DB->insert_record('local_targetzenit_psix_suhbat', (object) [
            'userid' => $userid,
            'persontype' => $persontype === 'staff' ? 'staff' : 'student',
            'caseid' => $caseid,
            'psixologid' => (int) $USER->id,
            'sana' => $sana ?: time(),
            'turi' => $turi,
            'matn' => $matn,
            'tavsiya' => trim($tavsiya),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        if ($caseid) {
            $DB->set_field('local_targetzenit_psix_case', 'timemodified', time(),
                ['id' => $caseid]);
        }
        return (int) $id;
    }

    /** Faqat O'Z yozuvini o'chiradi — boshqa psixolognikiga tegilmaydi. */
    public static function suhbat_ochir(int $id): void {
        global $DB, $USER;

        self::require_psixolog();
        $DB->delete_records('local_targetzenit_psix_suhbat',
            ['id' => $id, 'psixologid' => (int) $USER->id]);
    }

    /**
     * Suhbatlar lentasi (ism-familiya bilan birga, N+1 siz).
     *
     * @param array $filtr ['userid' =>, 'turi' =>, 'caseid' =>, 'q' =>]
     */
    public static function suhbatlar(array $filtr = [], int $page = 0,
                                      int $perpage = 30): array {
        global $DB;

        self::require_psixolog();

        $where = ['1 = 1'];
        $p = [];
        if (!empty($filtr['userid'])) {
            $where[] = 's.userid = :u';
            $p['u'] = (int) $filtr['userid'];
        }
        if (!empty($filtr['turi'])) {
            $where[] = 's.turi = :t';
            $p['t'] = $filtr['turi'];
        }
        if (isset($filtr['caseid']) && $filtr['caseid'] !== null) {
            $where[] = 's.caseid = :c';
            $p['c'] = (int) $filtr['caseid'];
        }
        if (!empty($filtr['q'])) {
            $like = '%' . $DB->sql_like_escape($filtr['q']) . '%';
            $where[] = '(' . $DB->sql_like('u.firstname', ':q1', false)
                . ' OR ' . $DB->sql_like('u.lastname', ':q2', false) . ')';
            $p += ['q1' => $like, 'q2' => $like];
        }
        $w = implode(' AND ', $where);

        return $DB->get_records_sql("
            SELECT s.*, u.firstname, u.lastname, u.idnumber, u.middlename,
                   u.firstnamephonetic, u.lastnamephonetic, u.alternatename,
                   c.sarlavha AS casenomi
              FROM {local_targetzenit_psix_suhbat} s
              JOIN {user} u ON u.id = s.userid
         LEFT JOIN {local_targetzenit_psix_case} c ON c.id = s.caseid
             WHERE $w
          ORDER BY s.sana DESC, s.id DESC", $p, $page * $perpage, $perpage);
    }

    // --------------------------------------------------------------- case

    public static function case_qosh(int $userid, string $persontype, string $sarlavha,
                                      string $tavsif, string $manba, string $muhimlik): int {
        global $DB, $USER;

        self::require_psixolog();
        $sarlavha = trim($sarlavha);
        if ($sarlavha === '') {
            throw new \moodle_exception('invaliddata', 'error');
        }
        return (int) $DB->insert_record('local_targetzenit_psix_case', (object) [
            'userid' => $userid,
            'persontype' => $persontype === 'staff' ? 'staff' : 'student',
            'psixologid' => (int) $USER->id,
            'sarlavha' => $sarlavha,
            'tavsif' => trim($tavsif),
            'manba' => in_array($manba, self::CASE_MANBALAR, true) ? $manba : 'kuzatuv',
            'muhimlik' => in_array($muhimlik, self::CASE_MUHIMLIK, true) ? $muhimlik : 'orta',
            'holat' => 'ochiq',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    public static function case_get(int $id): ?\stdClass {
        global $DB;

        self::require_psixolog();
        return $DB->get_record('local_targetzenit_psix_case', ['id' => $id]) ?: null;
    }

    public static function case_holat(int $id, string $holat): void {
        global $DB;

        self::require_psixolog();
        if (!in_array($holat, self::CASE_HOLATLAR, true) || $holat === 'yopildi') {
            return;                     // yopish faqat xulosa bilan (case_yop).
        }
        $DB->update_record('local_targetzenit_psix_case', (object) [
            'id' => $id, 'holat' => $holat, 'yopilganvaqti' => 0,
            'timemodified' => time(),
        ]);
    }

    /** Yopish XULOSASIZ bo'lmaydi — murojaat modulidagi qaror qoidasi kabi. */
    public static function case_yop(int $id, string $xulosa): void {
        global $DB;

        self::require_psixolog();
        $xulosa = trim($xulosa);
        if ($xulosa === '') {
            throw new \moodle_exception('invaliddata', 'error');
        }
        $DB->update_record('local_targetzenit_psix_case', (object) [
            'id' => $id, 'holat' => 'yopildi', 'xulosa' => $xulosa,
            'yopilganvaqti' => time(), 'timemodified' => time(),
        ]);
    }

    public static function caselar(string $holat = '', string $q = '',
                                    int $userid = 0): array {
        global $DB;

        self::require_psixolog();

        $where = ['1 = 1'];
        $p = [];
        if ($holat !== '' && in_array($holat, self::CASE_HOLATLAR, true)) {
            $where[] = 'c.holat = :h';
            $p['h'] = $holat;
        }
        if ($userid) {
            $where[] = 'c.userid = :u';
            $p['u'] = $userid;
        }
        if ($q !== '') {
            $like = '%' . $DB->sql_like_escape($q) . '%';
            $where[] = '(' . $DB->sql_like('u.firstname', ':q1', false)
                . ' OR ' . $DB->sql_like('u.lastname', ':q2', false)
                . ' OR ' . $DB->sql_like('c.sarlavha', ':q3', false) . ')';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        $w = implode(' AND ', $where);

        return $DB->get_records_sql("
            SELECT c.*, u.firstname, u.lastname, u.idnumber, u.middlename,
                   u.firstnamephonetic, u.lastnamephonetic, u.alternatename
              FROM {local_targetzenit_psix_case} c
              JOIN {user} u ON u.id = c.userid
             WHERE $w
          ORDER BY CASE c.holat WHEN 'yopildi' THEN 1 ELSE 0 END,
                   c.timemodified DESC", $p, 0, 200);
    }

    /** Case'ga nechta suhbat bog'langani — ro'yxatda N+1 siz. */
    public static function case_suhbat_soni(array $caseids): array {
        global $DB;

        if (!$caseids) {
            return [];
        }
        [$in, $p] = $DB->get_in_or_equal(array_map('intval', $caseids), SQL_PARAMS_NAMED);
        $rows = $DB->get_records_sql(
            "SELECT caseid, COUNT(1) AS soni
               FROM {local_targetzenit_psix_suhbat}
              WHERE caseid $in GROUP BY caseid", $p);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->caseid] = (int) $r->soni;
        }
        return $out;
    }

    // ------------------------------------------------------------- testlar

    /**
     * Markaziy kursdagi quiz + feedback ro'yxati, javob soni bilan.
     *
     * Sonlar IKKI so'rovda (har faoliyatga alohida EMAS): quiz uchun tugagan
     * urinishlar, feedback uchun yakunlangan javoblar.
     */
    public static function testlar(\stdClass $kurs): array {
        global $DB;

        $modinfo = get_fast_modinfo($kurs);
        $out = [];
        $quizids = [];
        $fbids = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname !== 'quiz' && $cm->modname !== 'feedback') {
                continue;
            }
            if ($cm->deletioninprogress) {
                continue;
            }
            $out[(int) $cm->id] = (object) [
                'cmid' => (int) $cm->id,
                'instance' => (int) $cm->instance,
                'modname' => $cm->modname,
                'nomi' => (string) $cm->name,
                'visible' => (bool) $cm->visible,
                'url' => $cm->url,
                'javoblar' => 0,
                'timeopen' => 0,
                'timeclose' => 0,
            ];
            if ($cm->modname === 'quiz') {
                $quizids[] = (int) $cm->instance;
            } else {
                $fbids[] = (int) $cm->instance;
            }
        }

        $sanalar = [];
        if ($quizids) {
            [$in, $p] = $DB->get_in_or_equal($quizids, SQL_PARAMS_NAMED);
            foreach ($DB->get_records_select('quiz', "id $in", $p,
                    '', 'id, timeopen, timeclose') as $qz) {
                $sanalar['quiz' . $qz->id] = [(int) $qz->timeopen, (int) $qz->timeclose];
            }
            $rows = $DB->get_records_sql(
                "SELECT quiz, COUNT(DISTINCT userid) AS soni
                   FROM {quiz_attempts}
                  WHERE preview = 0 AND state = 'finished' AND quiz $in
               GROUP BY quiz", $p);
            foreach ($rows as $r) {
                $sanalar['quiz' . $r->quiz][2] = (int) $r->soni;
            }
        }
        if ($fbids) {
            [$in, $p] = $DB->get_in_or_equal($fbids, SQL_PARAMS_NAMED);
            foreach ($DB->get_records_select('feedback', "id $in", $p,
                    '', 'id, timeopen, timeclose, anonymous') as $fb) {
                $sanalar['feedback' . $fb->id] = [(int) $fb->timeopen, (int) $fb->timeclose];
            }
            $rows = $DB->get_records_sql(
                "SELECT feedback, COUNT(1) AS soni
                   FROM {feedback_completed}
                  WHERE feedback $in GROUP BY feedback", $p);
            foreach ($rows as $r) {
                $sanalar['feedback' . $r->feedback][2] = (int) $r->soni;
            }
        }

        foreach ($out as $t) {
            $k = $t->modname . $t->instance;
            if (isset($sanalar[$k])) {
                $t->timeopen = $sanalar[$k][0];
                $t->timeclose = $sanalar[$k][1];
                $t->javoblar = $sanalar[$k][2] ?? 0;
            }
        }
        return $out;
    }

    // ------------------------------------------- tashqi (qog'oz) testlar

    /**
     * Tashqi test = Moodle'dan TASHQARIDA o'tkazilgan (qog'oz metodika);
     * natijasi panelda qo'lda kiritiladi. Moodle quiz/feedback'ka teginmaydi.
     */
    public static function test_qosh(string $nomi, int $sana, string $izoh): int {
        global $DB, $USER;

        self::require_psixolog();
        $nomi = trim($nomi);
        if ($nomi === '') {
            throw new \moodle_exception('invaliddata', 'error');
        }
        return (int) $DB->insert_record('local_targetzenit_psix_test', (object) [
            'nomi' => $nomi,
            'sana' => $sana ?: time(),
            'izoh' => trim($izoh),
            'psixologid' => (int) $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    public static function test_get(int $id): ?\stdClass {
        global $DB;

        self::require_psixolog();
        return $DB->get_record('local_targetzenit_psix_test', ['id' => $id]) ?: null;
    }

    /** Faqat O'Z testini o'chiradi — natijalari bilan birga. */
    public static function test_ochir(int $id): void {
        global $DB, $USER;

        self::require_psixolog();
        if (!$DB->record_exists('local_targetzenit_psix_test',
                ['id' => $id, 'psixologid' => (int) $USER->id])) {
            return;
        }
        $DB->delete_records('local_targetzenit_psix_natija', ['testid' => $id]);
        $DB->delete_records('local_targetzenit_psix_test', ['id' => $id]);
    }

    /** Tashqi testlar ro'yxati, kiritilgan natija soni bilan (N+1 siz). */
    public static function testlar_tashqi(): array {
        global $DB;

        self::require_psixolog();
        $rows = $DB->get_records('local_targetzenit_psix_test', null,
            'sana DESC, id DESC', '*', 0, 100);
        if (!$rows) {
            return [];
        }
        [$in, $p] = $DB->get_in_or_equal(array_keys($rows), SQL_PARAMS_NAMED);
        $sonlar = $DB->get_records_sql(
            "SELECT testid, COUNT(1) AS soni
               FROM {local_targetzenit_psix_natija}
              WHERE testid $in GROUP BY testid", $p);
        foreach ($rows as $r) {
            $r->natijalar = isset($sonlar[$r->id]) ? (int) $sonlar[$r->id]->soni : 0;
        }
        return $rows;
    }

    /** Shu testning natijalari: {userid: yozuv}. */
    public static function natijalar(int $testid): array {
        global $DB;

        self::require_psixolog();
        $out = [];
        foreach ($DB->get_records('local_targetzenit_psix_natija',
                ['testid' => $testid]) as $r) {
            $out[(int) $r->userid] = $r;
        }
        return $out;
    }

    /**
     * Bitta o'quvchining natijasini saqlaydi: IKKI maydon ham bo'sh bo'lsa
     * yozuv O'CHIRILADI (setkada katakni tozalash = natijani olib tashlash).
     */
    public static function natija_saqla(int $testid, int $userid,
                                         string $ball, string $izoh): void {
        global $DB, $USER;

        self::require_psixolog();
        $ball = trim($ball);
        $izoh = trim($izoh);
        $bor = $DB->get_record('local_targetzenit_psix_natija',
            ['testid' => $testid, 'userid' => $userid]);

        if ($ball === '' && $izoh === '') {
            if ($bor) {
                $DB->delete_records('local_targetzenit_psix_natija', ['id' => $bor->id]);
            }
            return;
        }
        if ($bor) {
            if ((string) $bor->ball === $ball && (string) $bor->izoh === $izoh) {
                return;                         // o'zgarmagan — DB'ga tegmaymiz.
            }
            $DB->update_record('local_targetzenit_psix_natija', (object) [
                'id' => $bor->id, 'ball' => $ball, 'izoh' => $izoh,
                'psixologid' => (int) $USER->id, 'timemodified' => time(),
            ]);
            return;
        }
        $DB->insert_record('local_targetzenit_psix_natija', (object) [
            'testid' => $testid, 'userid' => $userid,
            'ball' => $ball, 'izoh' => $izoh,
            'psixologid' => (int) $USER->id,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    // ---------------------------------------------------------- statistika

    /**
     * Rahbariyatga ham ochiq statistika — FAQAT SONLAR, matn va ism YO'Q
     * (maxfiylik qarori). Sinf nomi markaziy kurs guruhidan olinadi.
     *
     * Aggregatsiya ataylab PHP'da: oy kesimi DB-ga bog'liq funksiyalarsiz
     * (PostgreSQL!), yillik hajm bir necha ming qator — yengil.
     */
    public static function stat(?\stdClass $kurs): array {
        global $DB;

        $boshi = time() - 370 * DAYSECS;    // oxirgi o'quv yili atrofida
        $xarita = $kurs ? self::guruh_xarita((int) $kurs->id) : [];

        $oylar = [];
        $sinflar = [];
        $turlar = [];
        $jami = 0;
        $rows = $DB->get_records_select('local_targetzenit_psix_suhbat',
            'sana >= :b', ['b' => $boshi], '', 'id, userid, sana, turi');
        foreach ($rows as $r) {
            $jami++;
            $oy = userdate((int) $r->sana, '%Y-%m');
            $oylar[$oy] = ($oylar[$oy] ?? 0) + 1;
            $sinf = $xarita[(int) $r->userid] ?? '—';
            $sinflar[$sinf] = ($sinflar[$sinf] ?? 0) + 1;
            $turlar[$r->turi] = ($turlar[$r->turi] ?? 0) + 1;
        }
        ksort($oylar);
        \core_collator::ksort($sinflar, \core_collator::SORT_NATURAL);
        arsort($turlar);

        $caselar = ['ochiq' => 0, 'kuzatuvda' => 0, 'yopildi' => 0];
        foreach ($DB->get_records_sql("
                SELECT holat, COUNT(1) AS soni
                  FROM {local_targetzenit_psix_case} GROUP BY holat") as $r) {
            $caselar[$r->holat] = (int) $r->soni;
        }

        return [
            'jami' => $jami,
            'oylar' => $oylar,
            'sinflar' => $sinflar,
            'turlar' => $turlar,
            'caselar' => $caselar,
        ];
    }
}
