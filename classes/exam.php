<?php
// SUMMATIV BAHOLASH — ma'lumot qatlami (FB / BSB / LI / CHSB).
//
// RASMIY ASOS: Maktabgacha va maktab ta'limi vaziri 248-son buyrug'i ilovasi
// (Nizom) + e-kundalik qoidalari. Barcha raqamlar `BAHOLASH_ARXITEKTURA.md`
// da manbasi bilan yozilgan.
//
//   Chorak = 100 ball:  FB 10%  +  BSB/LI 50%  +  CHSB 40%
//   Baho:   86-100 -> 5 · 66-85 -> 4 · 30-65 -> 3 · 0-29 -> 2
//
// BITTA BAHOLASH = e-kundalikdagi JURNAL USTUNI: ish turi + o'tkazilish
// sanasi + maksimal ball. Model ataylab shunga mos qilingan.
//
// QOIDA: veb sahifa ham, vazifa ham, mobil ilova ham FAQAT shu klassdan
// oziqlanadi. Ikkinchi SQL yozilsa ko'rinishlar bir-biridan uzilib ketadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class exam {

    // ====================================================== NIZOM RAQAMLARI
    //
    // DIQQAT: bular STANDART qiymatlar. Nizom o'zgarishi mumkin, shuning
    // uchun ular plagin sozlamasidan o'qiladi (`sozlama()`), kodga qotib
    // yozilmaydi. Kodda turgan qiymat — zaxira.

    /** Summativ ish turlari. `LI` = loyiha ishi (e-kundalikdagi uchinchi ustun). */
    const TURLAR = ['BSB', 'CHSB', 'LI'];

    /** Baholash shakli. */
    const SHAKLLAR = ['onlayn', 'qogoz'];

    /** Baholash holati. */
    const HOLATLAR = ['qoralama', 'tasdiqlangan', 'otkazildi', 'yopildi'];

    /**
     * O'quvchining shu baholashdagi holati.
     *   ishladi         — oddiy natija
     *   uzrli           — uzrli sababga ko'ra qatnashmagan (qayta topshiradi, to'liq ball)
     *   uzrsiz          — uzrsiz qatnashmagan (maksimal 30%)
     *   chetlashtirildi — tartibbuzarlik, dalolatnoma tuzilgan (maksimal 30%)
     *   ozod            — kasalliklar ro'yxati bo'yicha ozod (aniq 30%)
     *   imtiyoz         — olimpiada/sertifikat imtiyozi (CHSB dan ozod)
     */
    const NATIJA_HOLATLAR = ['ishladi', 'uzrli', 'uzrsiz', 'chetlashtirildi',
                             'ozod', 'imtiyoz'];

    /** 30% chegarasi qo'llanadigan holatlar (nizom 12-band). */
    const CHEGARALI = ['uzrsiz', 'chetlashtirildi'];

    /** Chorak ulushlari: FB 10 + (BSB va LI birgalikda) 50 + CHSB 40 = 100. */
    const VAZN_FB = 10;
    const VAZN_BSB = 50;
    const VAZN_CHSB = 40;

    /** Nizomdagi 30% chegarasi va ozod etilganga yoziladigan ball. */
    const CHEGARA_FOIZ = 0.30;

    /** Ball -> baho. Tartib MUHIM: yuqoridan pastga tekshiriladi. */
    const BANDLAR = [86 => 5, 66 => 4, 30 => 3, 0 => 2];

    /**
     * BSB soni haftalik soatga qarab (nizom: «haftalik soatlardan kelib
     * chiqib»). Kalit — soatning YUQORI chegarasi.
     */
    const BSB_SONI = [2 => 1, 4 => 2, 999 => 3];

    /** Bir o'quv kunida bitta sinfga ruxsat etilgan chegara (nizom 7-band). */
    const KUN_FAN_CHEGARA = 3;
    const KUN_DAQIQA_CHEGARA = 300;      // 5 soat

    /** Kundalik bahoning maksimumi — FB ning 10 balliga mos (6 + 4). */
    const FB_MAX = 10;

    // =========================================================== SOZLAMALAR

    /**
     * Nizom raqamini sozlamadan o'qiydi, bo'lmasa zaxirani qaytaradi.
     *
     * NEGA: nizom o'zgarsa kod o'zgarmasligi kerak — 10/50/40 va 30%
     * chegarasi vaqt o'tib boshqa qiymat bo'lishi mumkin.
     */
    public static function sozlama(string $nom, $zaxira) {
        $v = get_config('local_targetzenit', 'bh_' . $nom);
        return ($v === false || $v === null || $v === '') ? $zaxira : $v;
    }

    /** Chorak ulushlari: ['fb' => 10, 'bsb' => 50, 'chsb' => 40] */
    public static function vaznlar(): array {
        return [
            'fb' => (float) self::sozlama('vazn_fb', self::VAZN_FB),
            'bsb' => (float) self::sozlama('vazn_bsb', self::VAZN_BSB),
            'chsb' => (float) self::sozlama('vazn_chsb', self::VAZN_CHSB),
        ];
    }

    // ============================================================ YORDAMLAR

    /**
     * Guruh nomidan sinf darajasi: «7 A» -> «7», «10 B» -> «10».
     *
     * Boshidagi raqamlar olinadi. Raqamsiz guruh (masalan «Tutor») bo'lsa
     * bo'sh qaytadi va u baholashga tushmaydi — bu xato emas, shunday
     * guruhlar bor.
     */
    public static function daraja(string $groupname): string {
        if (preg_match('/^\s*(\d{1,2})/', $groupname, $m)) {
            return $m[1];
        }
        return '';
    }

    /** «HH:MM» -> daqiqa. Noto'g'ri bo'lsa null. «9:00» ham qabul qilinadi. */
    public static function daqiqa(?string $hhmm): ?int {
        if (!$hhmm || !preg_match('/^(\d{1,2}):(\d{2})/', trim($hhmm), $m)) {
            return null;
        }
        $s = (int) $m[1];
        $d = (int) $m[2];
        if ($s > 23 || $d > 59) {
            return null;
        }
        return $s * 60 + $d;
    }

    /** Daqiqa -> «HH:MM». */
    public static function vaqt(int $daqiqa): string {
        return sprintf('%02d:%02d', intdiv($daqiqa, 60), $daqiqa % 60);
    }

    /** Ball -> baho (2..5). */
    public static function baho(?int $total): ?int {
        if ($total === null) {
            return null;
        }
        foreach (self::BANDLAR as $chegara => $baho) {
            if ($total >= $chegara) {
                return $baho;
            }
        }
        return 2;
    }

    /** Shu fanga chorakda nechta BSB tegishli (haftalik soatga qarab). */
    public static function bsb_soni(float $haftalik_soat): int {
        foreach (self::BSB_SONI as $chegara => $soni) {
            if ($haftalik_soat <= $chegara) {
                return $soni;
            }
        }
        return 3;
    }

    /** `_term_score` ning unikal kaliti — Postgres indeks chegarasi uchun md5. */
    public static function termkey(int $studentid, int $courseid,
                                    string $year, string $termname): string {
        return md5($studentid . '|' . $courseid . '|' . $year . '|' . $termname);
    }

    // ======================================================== TANLAGICHLAR

    /**
     * O'quv yilidagi fanlar: [subjectkey => subjectname].
     *
     * Manba — dars JADVALI, kurs ro'yxati emas: jadvalda fan nomi odam
     * o'qiydigan ko'rinishda turadi (`subjectname`), kurs `idnumber` ida esa
     * faqat kalit bor.
     */
    public static function fanlar(string $year): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT subjectkey, MAX(subjectname) AS nomi
              FROM {local_targetzenit_timetable}
             WHERE academicyear = :yil AND subjectkey <> ''
          GROUP BY subjectkey", ['yil' => $year]);

        $out = [];
        foreach ($rows as $r) {
            $out[$r->subjectkey] = $r->nomi ?: $r->subjectkey;
        }
        \core_collator::asort($out);
        return $out;
    }

    /**
     * O'quv yilidagi sinf darajalari: ['7' => '7-sinf', ...].
     *
     * Guruh nomidan ajratiladi («7 A» -> «7»), chunki daraja alohida maydon
     * sifatida Frappe'dan kelmaydi.
     */
    public static function darajalar(string $year): array {
        global $DB;

        $nomlar = $DB->get_fieldset_sql("
            SELECT DISTINCT groupname
              FROM {local_targetzenit_timetable}
             WHERE academicyear = :yil", ['yil' => $year]);

        $out = [];
        foreach ($nomlar as $n) {
            $d = self::daraja((string) $n);
            if ($d !== '') {
                $out[$d] = get_string('bh_class', 'local_targetzenit', $d);
            }
        }
        // Raqamli tartib: 10-sinf 2-sinfdan KEYIN turishi kerak
        uksort($out, fn($a, $b) => (int) $a <=> (int) $b);
        return $out;
    }

    /** Baholash turlari tanlagich uchun: [kod => nomi]. */
    public static function turlar_menu(): array {
        $out = [];
        foreach (self::TURLAR as $t) {
            $out[$t] = get_string('bh_turi_' . $t, 'local_targetzenit');
        }
        return $out;
    }

    /** Shakllar tanlagichi. */
    public static function shakllar_menu(): array {
        $out = [];
        foreach (self::SHAKLLAR as $t) {
            $out[$t] = get_string('bh_shakli_' . $t, 'local_targetzenit');
        }
        return $out;
    }

    /** Natija holatlari tanlagichi. */
    public static function holatlar_menu(): array {
        $out = [];
        foreach (self::NATIJA_HOLATLAR as $t) {
            $out[$t] = get_string('bh_holat_' . $t, 'local_targetzenit');
        }
        return $out;
    }

    /** Taklif qilinadigan nom — koordinator har safar qo'lda yozmasin. */
    public static function taklif_nom(string $turi, string $termname,
                                       string $fan, string $daraja): string {
        return trim($termname . ' · ' . $turi . ' · ' . $fan . ' · ' . $daraja);
    }

    // ============================================================= BAHOLASH

    public static function get(int $id): ?\stdClass {
        global $DB;
        $r = $DB->get_record('local_targetzenit_exam', ['id' => $id]);
        return $r ?: null;
    }

    /**
     * Baholashlar ro'yxati.
     *
     * @param array $filtr turi | termname | subjectkey | gradelevel | status
     *                     | from, to ('YYYY-MM-DD') | retro
     */
    public static function royxat(string $year, array $filtr = [],
                                   int $limit = 500): array {
        global $DB;

        $shart = ['e.academicyear = :yil'];
        $params = ['yil' => $year];

        foreach (['turi', 'termname', 'subjectkey', 'gradelevel', 'status'] as $f) {
            if (!empty($filtr[$f])) {
                $shart[] = "e.$f = :$f";
                $params[$f] = $filtr[$f];
            }
        }
        if (isset($filtr['retro']) && $filtr['retro'] !== '') {
            $shart[] = 'e.retro = :retro';
            $params['retro'] = (int) $filtr['retro'];
        }
        if (!empty($filtr['from'])) {
            $shart[] = 'e.examdate >= :from';
            $params['from'] = $filtr['from'];
        }
        if (!empty($filtr['to'])) {
            $shart[] = 'e.examdate <= :to';
            $params['to'] = $filtr['to'];
        }
        // O'QITUVCHI QAMROVI: faqat o'zi dars beradigan kursda sitting'i
        // bor baholashlar. Ro'yxatni kesish MAJBURIY — aks holda fan
        // o'qituvchisi butun maktabning imtihon jadvalini ko'rib qolardi.
        if (!empty($filtr['courseids'])) {
            list($in, $p) = $DB->get_in_or_equal($filtr['courseids'],
                SQL_PARAMS_NAMED, 'oc');
            $shart[] = "EXISTS (SELECT 1 FROM {local_targetzenit_exam_sitting} ss
                                 WHERE ss.examid = e.id AND ss.courseid $in)";
            $params += $p;
        }

        // Sitting va natija sanog'i bitta so'rovda — ro'yxat sahifasida har
        // qator uchun alohida so'rov yozilsa 50 baholash = 100 so'rov.
        return $DB->get_records_sql("
            SELECT e.*,
                   (SELECT COUNT(1) FROM {local_targetzenit_exam_sitting} s
                     WHERE s.examid = e.id) AS sittings,
                   (SELECT COUNT(1) FROM {local_targetzenit_exam_result} r
                     WHERE r.examid = e.id AND r.score IS NOT NULL) AS natijalar
              FROM {local_targetzenit_exam} e
             WHERE " . implode(' AND ', $shart) . "
          ORDER BY e.examdate DESC, e.id DESC", $params, 0, $limit);
    }

    /**
     * Baholash yaratadi yoki yangilaydi.
     *
     * O'TGAN SANA RUXSAT ETILADI (`retro`): dastur ishlamagan chorakni
     * kiritish birinchi darajali talab. Bunday baholashga onlayn quiz
     * yaratilmaydi va bildirishnoma yuborilmaydi.
     */
    public static function save(\stdClass $d): int {
        global $DB, $USER;

        require_capability('local/targetzenit:manageexams',
            \context_system::instance());

        $turi = in_array($d->turi ?? '', self::TURLAR, true) ? $d->turi : 'CHSB';
        $shakli = in_array($d->shakli ?? '', self::SHAKLLAR, true) ? $d->shakli : 'qogoz';
        $sana = (string) ($d->examdate ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sana)) {
            throw new \moodle_exception('bh_baddate', 'local_targetzenit');
        }

        // O'tgan sana -> majburan qog'oz. Onlayn quiz'ning `timeopen` i o'tib
        // ketgan bo'ladi va hech kim ishlamaydi, ya'ni yaratishning ma'nosi yo'q.
        $retro = ($sana < date('Y-m-d')) ? 1 : 0;
        if ($retro) {
            $shakli = 'qogoz';
        }

        $yozuv = (object) [
            'turi' => $turi,
            'academicyear' => (string) $d->academicyear,
            'termname' => (string) $d->termname,
            'subjectkey' => (string) $d->subjectkey,
            'gradelevel' => (string) $d->gradelevel,
            'name' => \core_text::substr(trim((string) $d->name), 0, 255),
            'shakli' => $shakli,
            'examdate' => $sana,
            'starttime' => self::daqiqa($d->starttime ?? null) === null
                ? null : trim((string) $d->starttime),
            'room' => trim((string) ($d->room ?? '')) ?: null,
            'duration' => max(5, (int) ($d->duration ?? 45)),
            'maxscore' => max(1, (int) ($d->maxscore ?? 100)),
            'randomcount' => max(0, (int) ($d->randomcount ?? 0)),
            'retro' => $retro,
            'timemodified' => time(),
        ];

        if (!empty($d->id)) {
            $yozuv->id = (int) $d->id;
            // `status`, `qbankcmid`, `qcategoryid` ataylab tegilmaydi —
            // ularni generatsiya va tasdiqlash o'zgartiradi, forma emas.
            $DB->update_record('local_targetzenit_exam', $yozuv);
            return (int) $d->id;
        }

        $yozuv->status = 'qoralama';
        $yozuv->createdby = (int) $USER->id;
        $yozuv->timecreated = time();
        return (int) $DB->insert_record('local_targetzenit_exam', $yozuv);
    }

    /**
     * Maksimal ballni o'zgartiradi — Excel'dan kelgan «savollar soni» uchun.
     *
     * DIQQAT: `maxscore` BAHOLASHGA tegishli, ya'ni hamma guruhga umumiy.
     * Uni o'zgartirish saqlangan ballarning FOIZINI o'zgartiradi
     * (`term_score()` da `score / maxscore`): 18/20 = 90%, lekin max 100
     * bo'lsa o'sha 18 = 18% bo'lib qoladi.
     *
     * HIMOYA: saqlangan ballardan birortasi yangi maksimaldan katta bo'lsa
     * O'ZGARTIRILMAYDI — aks holda ball jimgina kesilib, o'quvchi haqsiz
     * past natija olardi. Shunda odam avval o'sha ballni tuzatadi.
     *
     * @return bool o'zgardimi
     */
    public static function maxscore_ozgartir(int $examid, int $yangi): bool {
        global $DB;

        require_capability('local/targetzenit:manageexams',
            \context_system::instance());

        $exam = self::get($examid);
        if (!$exam || $yangi < 1) {
            return false;
        }
        if ((int) $exam->maxscore === $yangi) {
            return false;            // o'zgarish yo'q — xato ham emas
        }
        if ($exam->status === 'yopildi') {
            throw new \moodle_exception('bh_maxclosed', 'local_targetzenit');
        }

        $eng = $DB->get_field_sql(
            "SELECT MAX(rawscore) FROM {local_targetzenit_exam_result}
              WHERE examid = :e", ['e' => $examid]);
        if ($eng !== null && $eng !== false && (float) $eng > $yangi) {
            throw new \moodle_exception('bh_maxtoolow', 'local_targetzenit', '',
                (object) ['yangi' => $yangi, 'eng' => (float) $eng]);
        }

        $DB->update_record('local_targetzenit_exam', (object) [
            'id' => $examid, 'maxscore' => $yangi, 'timemodified' => time(),
        ]);
        return true;
    }

    /** Holatni o'zgartiradi. */
    public static function status(int $examid, string $status): void {
        global $DB;
        require_capability('local/targetzenit:manageexams',
            \context_system::instance());
        if (!in_array($status, self::HOLATLAR, true)) {
            return;
        }
        $DB->update_record('local_targetzenit_exam', (object) [
            'id' => $examid, 'status' => $status, 'timemodified' => time(),
        ]);
    }

    // =============================================== KURSLAR VA SITTINGLAR

    /**
     * Shu baholashga tushadigan kurslar: `<yil>::<guruh>::<fan>`, guruhning
     * raqamli qismi = `gradelevel`.
     *
     * NEGA LIKE + PHP FILTRI: sinf darajasi guruh nomining ICHIDA
     * («7 A» -> 7), SQL'da ishonchli ajratib bo'lmaydi (10-sinf 1-sinf bilan
     * aralashadi). LIKE yil va fanni qisqartiradi, daraja PHP'da tekshiriladi.
     *
     * @return array [courseid => ['courseid','groupkey','groupname','idnumber']]
     */
    public static function courses_for(string $year, string $subjectkey,
                                        string $gradelevel): array {
        global $DB;

        // DIQQAT: `sql_like_escape` FAQAT literal bo'laklarga qo'llanadi.
        // Butun naqshni ekranlasak o'rtadagi `%` ham ekranlanib qolardi va
        // so'rov hech narsa topmasdi.
        $naqsh = $DB->sql_like_escape($year) . '::%::'
            . $DB->sql_like_escape($subjectkey);

        // Yashirin kurslar ham olinadi — markaziy baholash kursi yashirin
        // bo'lishi mumkin va chetda qolib ketmasligi kerak.
        $rows = $DB->get_records_select('course',
            $DB->sql_like('idnumber', ':p'), ['p' => $naqsh],
            '', 'id, idnumber, fullname');

        $out = [];
        foreach ($rows as $c) {
            $q = explode('::', (string) $c->idnumber);
            if (count($q) < 3 || $q[2] !== $subjectkey) {
                continue;              // LIKE `%` ichida `::` bo'lishi mumkin
            }
            if (self::daraja($q[1]) !== $gradelevel) {
                continue;
            }
            $out[(int) $c->id] = [
                'courseid' => (int) $c->id,
                'groupkey' => $q[1],
                'groupname' => $q[1],
                'idnumber' => (string) $c->idnumber,
            ];
        }

        // Tartib TABIIY: «2 A» dan keyin «10 A» keladi, aks holda alifbo
        // tartibida «10 A» «2 A» dan oldin turib qolardi.
        uasort($out, fn($a, $b) => strnatcasecmp($a['groupname'], $b['groupname']));
        return $out;
    }

    /**
     * BITTA sitting + o'zining baholashi.
     *
     * Ball kiritish ekrani ikkisini ham talab qiladi (maksimal ball, turi,
     * shakli baholashda; guruh va kurs sittingda), shuning uchun bitta
     * so'rovda olinadi.
     */
    /**
     * Foydalanuvchi ball kiritadigan/ko'radigan kurslar.
     *
     * Manba — `gradeexam` KURS kontekstida: nizom bo'yicha summativ ishni
     * fan o'qituvchisi tekshiradi, ya'ni ruxsat kursga bog'langan.
     * Koordinatorda bu ruxsat tizim darajasida bor, shuning uchun unga
     * bu ro'yxat kerak emas (u hammasini ko'radi).
     */
    public static function mening_kurslarim(int $userid): array {
        $out = [];
        foreach (enrol_get_users_courses($userid, true, 'id') as $c) {
            if (has_capability('local/targetzenit:gradeexam',
                    \context_course::instance($c->id), $userid)) {
                $out[] = (int) $c->id;
            }
        }
        return $out;
    }

    /**
     * Shu sitting'ga kirish mumkinmi (ball ko'rish/kiritish).
     *
     * QOIDA: koordinator — hammasiga; o'qituvchi — faqat o'z kursiga.
     * Tekshiruv SAHIFADA emas, shu yerda: ball setkasi ikki joydan
     * chaqiriladi va tekshiruvni takrorlash — unutish demak.
     */
    public static function sitting_ruxsat(\stdClass $sit, int $userid = 0): bool {
        global $USER;

        $userid = $userid ?: (int) $USER->id;
        if (has_capability('local/targetzenit:manageexams',
                \context_system::instance(), $userid)) {
            return true;
        }
        return has_capability('local/targetzenit:gradeexam',
            \context_course::instance((int) $sit->courseid), $userid);
    }

    public static function sitting(int $sittingid): ?\stdClass {
        global $DB;

        $r = $DB->get_record_sql("
            SELECT s.*, e.turi, e.name AS examname, e.shakli, e.maxscore,
                   e.duration, e.termname, e.academicyear, e.subjectkey,
                   e.retro, e.status AS examstatus,
                   e.examdate AS e_examdate, e.starttime AS e_starttime,
                   e.room AS e_room,
                   c.fullname AS coursename, c.idnumber AS courseidnumber
              FROM {local_targetzenit_exam_sitting} s
              JOIN {local_targetzenit_exam} e ON e.id = s.examid
         LEFT JOIN {course} c ON c.id = s.courseid
             WHERE s.id = :id", ['id' => $sittingid]);

        return $r ?: null;
    }

    /**
     * `sitting()` qaytargan yassi qatordan `qachon()` uchun ikki obyekt.
     *
     * NEGA: `qachon()` baholash va sittingni alohida kutadi, `sitting()` esa
     * ikkisini bitta qatorga qo'shib beradi. Qayta ajratish shu yerda,
     * chaqiruvchida takrorlanmasin.
     */
    public static function qachon_flat(\stdClass $r): array {
        return self::qachon(
            (object) ['examdate' => $r->e_examdate, 'starttime' => $r->e_starttime,
                      'room' => $r->e_room, 'duration' => $r->duration],
            (object) ['examdate' => $r->examdate, 'starttime' => $r->starttime,
                      'room' => $r->room]);
    }

    /** Baholashning sittinglari (imtihon jadvali qatorlari). */
    public static function sittings(int $examid): array {
        global $DB;

        return $DB->get_records_sql("
            SELECT s.*, c.fullname AS coursename, c.idnumber AS courseidnumber
              FROM {local_targetzenit_exam_sitting} s
         LEFT JOIN {course} c ON c.id = s.courseid
             WHERE s.examid = :e
          ORDER BY s.groupname ASC", ['e' => $examid]);
    }

    /**
     * Sittingni mos kurslar bo'yicha YARATADI (bor bo'lsa tegmaydi).
     *
     * Idempotent: `exam_course` unikal indeksi tufayli ikki marta chaqirilsa
     * dublikat bo'lmaydi. Bu MUHIM — generatsiya adhoc vazifada ishlaydi va
     * vazifa qayta urinishi mumkin.
     *
     * @return array ['qoshildi' => int, 'bor' => int, 'kurslar' => int]
     */
    public static function ensure_sittings(int $examid): array {
        global $DB;

        $exam = self::get($examid);
        if (!$exam) {
            throw new \moodle_exception('bh_noexam', 'local_targetzenit');
        }
        $kurslar = self::courses_for($exam->academicyear, $exam->subjectkey,
            $exam->gradelevel);

        $bor = $DB->get_records_menu('local_targetzenit_exam_sitting',
            ['examid' => $examid], '', 'courseid, id');

        $qoshildi = 0;
        foreach ($kurslar as $cid => $k) {
            if (isset($bor[$cid])) {
                continue;
            }
            $DB->insert_record('local_targetzenit_exam_sitting', (object) [
                'examid' => $examid,
                'courseid' => $cid,
                'groupkey' => $k['groupkey'],
                'groupname' => $k['groupname'],
                // Sana/vaqt/xona ATAYLAB null — baholashdan meros oladi.
                // Koordinator keraklisini keyin siljitadi (3.1-bo'lim).
                'examdate' => null,
                'starttime' => null,
                'room' => null,
                'status' => 'kutilmoqda',
                'timegenerated' => time(),
            ]);
            $qoshildi++;
        }
        return ['qoshildi' => $qoshildi, 'bor' => count($bor),
                'kurslar' => count($kurslar)];
    }

    /**
     * Sittingning HAQIQIY sanasi/vaqti/xonasi — meros hisobga olingan holda.
     *
     * @return array ['examdate','starttime','room','boshi','oxiri'] (daqiqada)
     */
    public static function qachon(\stdClass $exam, \stdClass $sitting): array {
        $sana = $sitting->examdate ?: $exam->examdate;
        $vaqt = $sitting->starttime ?: $exam->starttime;
        $xona = $sitting->room ?: $exam->room;

        $boshi = self::daqiqa($vaqt);
        return [
            'examdate' => $sana,
            'starttime' => $vaqt,
            'room' => $xona,
            'boshi' => $boshi,
            'oxiri' => ($boshi === null) ? null : $boshi + (int) $exam->duration,
        ];
    }

    /** Sittingning sanasi/vaqti/xonasini siljitadi. NULL = merosga qaytaradi. */
    public static function sitting_siljit(int $sittingid, ?string $sana,
                                           ?string $vaqt, ?string $xona): void {
        global $DB;

        require_capability('local/targetzenit:manageexams',
            \context_system::instance());

        if ($sana !== null && $sana !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sana)) {
            throw new \moodle_exception('bh_baddate', 'local_targetzenit');
        }
        $DB->update_record('local_targetzenit_exam_sitting', (object) [
            'id' => $sittingid,
            'examdate' => ($sana === '' ? null : $sana),
            'starttime' => (self::daqiqa($vaqt) === null ? null : trim((string) $vaqt)),
            'room' => (trim((string) $xona) === '' ? null : trim((string) $xona)),
        ]);
    }

    // ======================================================= TO'QNASHUVLAR

    /**
     * Nizomdagi kun chegarasi va dars jadvali bilan to'qnashuv.
     *
     * Nizom 7-band: bitta sinfda bir o'quv kunida **3 fandan ortiq emas** va
     * umumiy vaqti **5 soatdan oshmasligi** kerak.
     *
     * @return array har biri ['guruh','sana','tur','xabar']; bo'sh = toza
     */
    public static function toqnashuv(int $examid): array {
        global $DB;

        $exam = self::get($examid);
        if (!$exam) {
            return [];
        }
        $sittings = self::sittings($examid);
        if (!$sittings) {
            return [];
        }

        $xatolar = [];

        foreach ($sittings as $s) {
            $q = self::qachon($exam, $s);
            $sana = $q['examdate'];

            // --- (1) Shu guruh, shu kunda qancha baholash bor
            //
            // DIQQAT: `examdate` sittingda ham, baholashda ham bo'lishi
            // mumkin (meros), shuning uchun COALESCE bilan olinadi.
            $qatorlar = $DB->get_records_sql("
                SELECT s2.id, e2.subjectkey, e2.duration, e2.name,
                       COALESCE(s2.examdate, e2.examdate) AS sana
                  FROM {local_targetzenit_exam_sitting} s2
                  JOIN {local_targetzenit_exam} e2 ON e2.id = s2.examid
                 WHERE s2.groupkey = :g AND s2.id <> :sid
                   AND e2.academicyear = :yil
                   AND COALESCE(s2.examdate, e2.examdate) = :sana",
                ['g' => $s->groupkey, 'sid' => $s->id,
                 'yil' => $exam->academicyear, 'sana' => $sana]);

            $fanlar = [$exam->subjectkey => true];
            $daqiqa = (int) $exam->duration;
            foreach ($qatorlar as $r) {
                $fanlar[$r->subjectkey] = true;
                $daqiqa += (int) $r->duration;
            }

            $fanchegara = (int) self::sozlama('kun_fan', self::KUN_FAN_CHEGARA);
            $vaqtchegara = (int) self::sozlama('kun_daqiqa', self::KUN_DAQIQA_CHEGARA);

            if (count($fanlar) > $fanchegara) {
                $xatolar[] = ['guruh' => $s->groupname, 'sana' => $sana,
                    'tur' => 'fan',
                    'xabar' => get_string('bh_clash_subjects', 'local_targetzenit',
                        (object) ['soni' => count($fanlar), 'chegara' => $fanchegara])];
            }
            if ($daqiqa > $vaqtchegara) {
                $xatolar[] = ['guruh' => $s->groupname, 'sana' => $sana,
                    'tur' => 'vaqt',
                    'xabar' => get_string('bh_clash_hours', 'local_targetzenit',
                        (object) ['daqiqa' => $daqiqa, 'chegara' => $vaqtchegara])];
            }

            // --- (2) Dars jadvali bilan ustma-ustlik
            //
            // Faqat vaqt berilgan bo'lsa tekshiriladi: vaqt yo'q baholash
            // kun bo'yi bo'lishi mumkin va hech narsa bilan solishtirilmaydi.
            if ($q['boshi'] !== null) {
                $dow = (int) date('N', strtotime($sana));
                $darslar = $DB->get_records('local_targetzenit_timetable',
                    ['academicyear' => $exam->academicyear,
                     'groupkey' => $s->groupkey, 'dayno' => $dow]);

                foreach ($darslar as $d) {
                    $db = self::daqiqa($d->starttime);
                    $de = self::daqiqa($d->endtime);
                    if ($db === null) {
                        continue;
                    }
                    $de = $de ?? ($db + 45);
                    // Oraliqlar kesishadimi
                    if ($q['boshi'] < $de && $db < $q['oxiri']) {
                        $xatolar[] = ['guruh' => $s->groupname, 'sana' => $sana,
                            'tur' => 'dars',
                            'xabar' => get_string('bh_clash_lesson', 'local_targetzenit',
                                (object) ['dars' => $d->lessonno,
                                          'fan' => $d->subjectname,
                                          'vaqt' => $d->starttime])];
                        break;        // bitta guruh uchun bitta xabar yetarli
                    }
                }
            }
        }
        return $xatolar;
    }

    // ============================================================= NATIJALAR

    /**
     * Sittingdagi natijalar: [studentid => record].
     * Hali kiritilmaganlar ham qaytadi (`score = null`).
     */
    public static function results(int $sittingid): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_exam_result',
            ['sittingid' => $sittingid]);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->studentid] = $r;
        }
        return $out;
    }

    /**
     * Nizomdagi 30% chegarasini qo'llaydi.
     *
     * Nizom 12-band:
     *   uzrli                        -> olgan bali TO'LIQ
     *   uzrsiz | chetlashtirildi     -> maksimal 30%
     *   30 foizdan PAST natija       -> maksimal 30% (ya'ni o'zgarmaydi)
     *   ozod (kasallik ro'yxati)     -> ANIQ 30%
     *
     * @return array ['score' => float|null, 'capped' => int]
     */
    public static function chegarala($score, string $holat, int $maxscore,
                                      bool $retake = false, $oldraw = null): array {
        $foiz = (float) self::sozlama('chegara_foiz', self::CHEGARA_FOIZ);
        $chegara = round($maxscore * $foiz, 2);

        if ($holat === 'ozod') {
            // Ozod etilganga ANIQ 30% yoziladi — bu jazo emas, nizom qoidasi
            return ['score' => $chegara, 'capped' => 0];
        }
        if ($holat === 'imtiyoz') {
            // Olimpiada/sertifikat imtiyozi: nizomda ball ko'rsatilmagan,
            // shuning uchun ball YOZILMAYDI va chorak hisobida bu qism
            // mavjud emas deb olinadi (vazn qayta taqsimlanadi).
            return ['score' => null, 'capped' => 0];
        }

        if ($score === null || $score === '') {
            // BALL YO'Q — holatga qarab ikki xil ma'no:
            //
            // `uzrli` -> qayta topshirishni KUTADI, ya'ni hali natija yo'q
            //            (null) va chorak hisobida bu qism mavjud emas.
            // `uzrsiz` / `chetlashtirildi` -> **0**, null EMAS.
            //
            // NEGA SHU MUHIM: vazn qayta taqsimlanadi, ya'ni `null` qism
            // hisobdan CHIQADI. Uzrsiz kelmagan o'quvchiga null qo'ysak,
            // imtihonga kelmaslik uning chorak bahosini KO'TARARDI —
            // tizim qatnashmaslikni mukofotlardi.
            if (in_array($holat, self::CHEGARALI, true)) {
                return ['score' => 0.0, 'capped' => 0];
            }
            return ['score' => null, 'capped' => 0];
        }

        $score = max(0, min((float) $maxscore, (float) $score));

        // --- Nizomdagi 30% chegarasi QAYTA TOPSHIRISHDA qo'llanadi
        //
        // Nizom 12-band: uzrsiz qatnashmagan, tartibbuzarlik uchun
        // chetlashtirilgan HAMDA 30 foizdan past natija ko'rsatgan
        // o'quvchining qayta topshirishdagi maksimal bali 30 foizdan
        // oshmaydi. Uzrli sababli qatnashmagan esa TO'LIQ ball oladi.
        if ($retake) {
            $past = ($oldraw !== null && $oldraw !== '' && (float) $oldraw < $chegara);
            if (in_array($holat, self::CHEGARALI, true) || $past) {
                if ($score > $chegara) {
                    return ['score' => $chegara, 'capped' => 1];
                }
            }
        } else if (in_array($holat, self::CHEGARALI, true) && $score > $chegara) {
            // Qayta topshirish belgilanmagan, lekin holat uzrsiz/chetlashtirilgan
            // bo'lsa ham chegara amal qiladi — aks holda belgini qo'yib
            // chegarani aylanib o'tish mumkin bo'lardi.
            return ['score' => $chegara, 'capped' => 1];
        }
        return ['score' => round($score, 2), 'capped' => 0];
    }

    /**
     * Bitta o'quvchining natijasini saqlaydi.
     *
     * RUXSAT: koordinator (`manageexams`, tizim) yoki shu KURSNING
     * o'qituvchisi (`gradeexam`, kurs konteksti) — nizom bo'yicha BSB ni
     * fan o'qituvchisi tekshiradi.
     *
     * TARIX: har o'zgarish `_grade_log` ga tushadi va 72 soatlik oyna
     * tekshiriladi (e-kundalik qoidasi).
     *
     * @return int natija qatorining id si
     */
    public static function result_save(int $sittingid, int $studentid, $score,
                                        string $holat = 'ishladi',
                                        bool $retake = false,
                                        string $reason = ''): int {
        global $DB, $USER;

        $sitting = $DB->get_record('local_targetzenit_exam_sitting',
            ['id' => $sittingid], '*', MUST_EXIST);
        $exam = self::get((int) $sitting->examid);
        if (!$exam) {
            throw new \moodle_exception('bh_noexam', 'local_targetzenit');
        }
        $context = \context_course::instance((int) $sitting->courseid);

        // Tugmani yashirish himoya EMAS — ruxsat shu yerda tekshiriladi.
        if (!has_capability('local/targetzenit:manageexams', \context_system::instance())) {
            require_capability('local/targetzenit:gradeexam', $context);
        }
        if (!in_array($holat, self::NATIJA_HOLATLAR, true)) {
            $holat = 'ishladi';
        }
        // O'quvchi shu kursda bormi — `studentid` ni qo'lda o'zgartirib
        // begona o'quvchiga ball yozishning oldi olinadi.
        if (!is_enrolled($context, $studentid)) {
            throw new \moodle_exception('bh_notstudent', 'local_targetzenit');
        }

        $bor = $DB->get_record('local_targetzenit_exam_result',
            ['examid' => $exam->id, 'studentid' => $studentid]);

        // `oldraw` — chegaralashdan oldingi ESKI ball. Nizomdagi «30 foizdan
        // past natija ko'rsatgan» shartini aynan shu hal qiladi.
        $chegaralangan = self::chegarala($score, $holat, (int) $exam->maxscore,
            $retake, $bor ? $bor->rawscore : null);

        $yozuv = (object) [
            'examid' => (int) $exam->id,
            'sittingid' => $sittingid,
            'studentid' => $studentid,
            'courseid' => (int) $sitting->courseid,
            'score' => $chegaralangan['score'],
            'holat' => $holat,
            'retake' => $retake ? 1 : 0,
            'capped' => $chegaralangan['capped'],
            // Chegaralashdan OLDINGI ball — dalil. Chegara qo'llanganda
            // «aslida qancha olgan edi» savoliga javob shu yerda.
            'rawscore' => ($score === null || $score === '') ? null : (float) $score,
            'enteredby' => (int) $USER->id,
            'timemodified' => time(),
        ];

        if ($bor) {
            // --- 72 SOATLIK OYNA (e-kundalik qoidasi)
            $tekshiruv = gradelog::tekshir((int) $bor->timemodified);
            if (!$tekshiruv['mumkin']) {
                throw new \moodle_exception('nopermissions', 'error', '',
                    $tekshiruv['sabab']);
            }
            if ($tekshiruv['tasdiqkerak'] && trim($reason) === '') {
                throw new \moodle_exception('bh_reasonrequired', 'local_targetzenit',
                    '', gradelog::OYNA_SOAT);
            }

            $yozuv->id = (int) $bor->id;
            $DB->update_record('local_targetzenit_exam_result', $yozuv);

            gradelog::qayd('summativ', (int) $bor->id, $studentid,
                (int) $sitting->courseid, $bor->score, $yozuv->score, $reason,
                (int) $bor->timemodified,
                $tekshiruv['tasdiqkerak'] ? (int) $USER->id : 0);

            return (int) $bor->id;
        }

        $id = (int) $DB->insert_record('local_targetzenit_exam_result', $yozuv);
        gradelog::qayd('summativ', $id, $studentid, (int) $sitting->courseid,
            null, $yozuv->score, $reason);
        return $id;
    }

    // ========================================================= CHORAK BAHOSI

    /**
     * Bitta o'quvchining chorak bahosini HISOBLAYDI (saqlamaydi).
     *
     *   FB   = o'rtacha(kundalik baholar)                     -> 0..10
     *   BSB  = SUM(bsb+li ball) / SUM(maxscore) * 50          -> 0..50
     *   CHSB = chsb ball / maxscore * 40                      -> 0..40
     *
     * VAZN QAYTA TAQSIMLANADI — faqat MAVJUD qismlar hisoblanadi:
     *   jami = mavjud_ball / mavjud_vazn * 100
     *
     * Bu NORMAL holatda hech narsani o'zgartirmaydi (hammasi bor -> /100).
     * O'tgan chorakda esa dastur ishlamagani uchun FB va BSB yo'q — va
     * o'quvchi mavjud bo'lmagan narsa uchun jazolanmasligi kerak
     * (`ai::overall()` dagi `confidence=low` qoidasining o'sha mantiqi).
     *
     * @return \stdClass fb, bsb, chsb, total, baho, parts, divisor
     */
    public static function term_score(int $studentid, int $courseid,
                                       string $year, string $termname): \stdClass {
        global $DB;

        $vazn = self::vaznlar();
        $natija = (object) ['fb' => null, 'bsb' => null, 'chsb' => null,
                            'total' => null, 'baho' => null,
                            'parts' => '', 'divisor' => null];

        // --- chorak oralig'i
        $term = $DB->get_record('local_targetzenit_calendar',
            ['academicyear' => $year, 'entrytype' => 'term', 'name' => $termname]);
        if (!$term) {
            return $natija;
        }

        // --- FB: kundalik baholarning o'rtachasi
        //
        // `grade IS NOT NULL` SHART: yo'qlama qo'yilgan, lekin baho
        // qo'yilmagan katak o'rtachani pasaytirmasligi kerak.
        $ort = $DB->get_field_sql("
            SELECT AVG(r.grade)
              FROM {local_targetzenit_record} r
              JOIN {local_targetzenit_lesson} l ON l.id = r.lessonid
             WHERE r.userid = :u AND l.courseid = :c
               AND r.grade IS NOT NULL
               AND l.lessondate >= :boshi AND l.lessondate <= :oxiri",
            ['u' => $studentid, 'c' => $courseid,
             'boshi' => $term->startdate, 'oxiri' => $term->enddate]);

        if ($ort !== null && $ort !== false) {
            // 1..10 shkala FB ning 10 balliga TO'G'RIDAN-TO'G'RI mos tushadi
            // (nizom: ishtirok 6 + uy vazifa 4 = 10).
            $natija->fb = round(min(self::FB_MAX, (float) $ort)
                / self::FB_MAX * $vazn['fb'], 2);
        }

        // --- Summativ qismlar
        $summativ = $DB->get_records_sql("
            SELECT r.id, e.turi, r.score, e.maxscore
              FROM {local_targetzenit_exam_result} r
              JOIN {local_targetzenit_exam} e ON e.id = r.examid
             WHERE r.studentid = :u AND r.courseid = :c
               AND e.academicyear = :yil AND e.termname = :chorak
               AND r.score IS NOT NULL",
            ['u' => $studentid, 'c' => $courseid,
             'yil' => $year, 'chorak' => $termname]);

        $bsb_ball = 0.0;
        $bsb_max = 0.0;
        $chsb_ball = 0.0;
        $chsb_max = 0.0;
        foreach ($summativ as $r) {
            if ($r->turi === 'CHSB') {
                $chsb_ball += (float) $r->score;
                $chsb_max += (float) $r->maxscore;
            } else {
                // BSB va LI (loyiha ishi) BIRGALIKDA 50% ulushga kiradi
                $bsb_ball += (float) $r->score;
                $bsb_max += (float) $r->maxscore;
            }
        }
        if ($bsb_max > 0) {
            $natija->bsb = round($bsb_ball / $bsb_max * $vazn['bsb'], 2);
        }
        if ($chsb_max > 0) {
            $natija->chsb = round($chsb_ball / $chsb_max * $vazn['chsb'], 2);
        }

        // --- Vazn qayta taqsimlash
        $ball = 0.0;
        $bol = 0.0;
        $parts = [];
        foreach (['fb', 'bsb', 'chsb'] as $k) {
            if ($natija->$k !== null) {
                $ball += (float) $natija->$k;
                $bol += $vazn[$k];
                $parts[] = $k;
            }
        }
        if ($bol <= 0) {
            return $natija;            // hech narsa yo'q — baho chiqarilmaydi
        }

        $natija->total = (int) round($ball / $bol * 100);
        $natija->baho = self::baho($natija->total);
        $natija->parts = implode(',', $parts);
        $natija->divisor = (int) round($bol);
        return $natija;
    }

    /**
     * Hisoblangan chorak bahosini SAQLAYDI.
     *
     * `frozen=1` bo'lsa TEGILMAYDI: chorak yopilgach kundalik bahoga bitta
     * tuzatish kiritilsa ham o'tgan chorak bahosi o'zgarmasligi kerak — bu
     * ota-ona oldida tushuntirib bo'lmaydigan holat (`_slot.frozen` bilan
     * bir xil sabab).
     *
     * @return bool saqlandimi (false = muzlatilgan)
     */
    public static function term_score_save(int $studentid, int $courseid,
                                            string $year, string $termname): bool {
        global $DB;

        $kalit = self::termkey($studentid, $courseid, $year, $termname);
        $bor = $DB->get_record('local_targetzenit_term_score', ['termkey' => $kalit]);
        if ($bor && (int) $bor->frozen === 1) {
            return false;
        }

        $h = self::term_score($studentid, $courseid, $year, $termname);
        $yozuv = (object) [
            'studentid' => $studentid,
            'courseid' => $courseid,
            'academicyear' => $year,
            'termname' => $termname,
            'termkey' => $kalit,
            'fb' => $h->fb,
            'bsb' => $h->bsb,
            'chsb' => $h->chsb,
            'total' => $h->total,
            'baho' => $h->baho,
            'parts' => $h->parts !== '' ? $h->parts : null,
            'divisor' => $h->divisor,
            'timemodified' => time(),
        ];

        if ($bor) {
            $yozuv->id = (int) $bor->id;
            $DB->update_record('local_targetzenit_term_score', $yozuv);
        } else {
            $yozuv->frozen = 0;
            $DB->insert_record('local_targetzenit_term_score', $yozuv);
        }
        return true;
    }

    /** Chorakni muzlatadi / muzdan chiqaradi (faqat koordinator). */
    public static function muzlat(int $courseid, string $year, string $termname,
                                   bool $muzlat = true): int {
        global $DB;

        require_capability('local/targetzenit:manageexams',
            \context_system::instance());

        $params = ['c' => $courseid, 'yil' => $year, 'chorak' => $termname];
        $soni = $DB->count_records_select('local_targetzenit_term_score',
            'courseid = :c AND academicyear = :yil AND termname = :chorak', $params);

        $DB->execute("UPDATE {local_targetzenit_term_score}
                         SET frozen = :f, timemodified = :vaqt
                       WHERE courseid = :c AND academicyear = :yil
                         AND termname = :chorak",
            ['f' => $muzlat ? 1 : 0, 'vaqt' => time()] + $params);

        return $soni;
    }

    /** Saqlangan chorak natijalari: [studentid => record]. */
    public static function term_scores(int $courseid, string $year,
                                        string $termname): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_term_score',
            ['courseid' => $courseid, 'academicyear' => $year,
             'termname' => $termname]);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->studentid] = $r;
        }
        return $out;
    }

    // ================================================== JURNAL VA KO'RINISH

    /**
     * Shu kursning shu chorakdagi baholashlari — jurnal USTUNLARI uchun.
     *
     * Jurnal ustunlari `journal::lessons_in_term()` da jadvaldan hisoblanadi
     * va imtihon kuni u ro'yxatda bo'lmasligi mumkin. Shuning uchun imtihon
     * ustunlari ALOHIDA qo'shiladi — jurnal hech qachon mavjud ma'lumotni
     * yashirmasligi kerak (0.9.2 saboqi).
     *
     * @return array har biri: sittingid, examid, turi, name, sana, vaqt,
     *               maxscore, shakli, retro
     */
    public static function journal_columns(int $courseid, string $year,
                                            string $termname): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT s.id AS sittingid, e.id AS examid, e.turi, e.name,
                   COALESCE(s.examdate, e.examdate) AS sana,
                   COALESCE(s.starttime, e.starttime) AS vaqt,
                   COALESCE(s.room, e.room) AS xona,
                   e.maxscore, e.shakli, e.retro, e.status
              FROM {local_targetzenit_exam_sitting} s
              JOIN {local_targetzenit_exam} e ON e.id = s.examid
             WHERE s.courseid = :c AND e.academicyear = :yil
               AND e.termname = :chorak
          ORDER BY COALESCE(s.examdate, e.examdate) ASC, e.id ASC",
            ['c' => $courseid, 'yil' => $year, 'chorak' => $termname]);

        return array_values($rows);
    }

    /**
     * Shu sittinglardagi natijalar: [sittingid][studentid] => record.
     * Jurnal setkasini bitta so'rovda to'ldirish uchun.
     */
    public static function results_for(array $sittingids): array {
        global $DB;

        if (!$sittingids) {
            return [];
        }
        [$in, $params] = $DB->get_in_or_equal($sittingids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('local_targetzenit_exam_result',
            "sittingid $in", $params);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->sittingid][(int) $r->studentid] = $r;
        }
        return $out;
    }

    /**
     * O'quvchining kutilayotgan va o'tgan baholashlari — `baholarim.php` uchun.
     *
     * Nizom: «chorak davomidagi baholashning soni, muddatlari, shakllari ...
     * chorak boshida o'quvchilarga yetkaziladi» — shuning uchun KELAJAKDAGI
     * baholashlar ham qaytariladi.
     */
    public static function for_student(int $studentid, string $year,
                                        string $termname = ''): array {
        global $DB;

        $shart = ['e.academicyear = :yil'];
        $params = ['yil' => $year, 'u' => $studentid];
        if ($termname !== '') {
            $shart[] = 'e.termname = :chorak';
            $params['chorak'] = $termname;
        }

        return $DB->get_records_sql("
            SELECT s.id AS sittingid, e.id AS examid, e.turi, e.name, e.shakli,
                   COALESCE(s.examdate, e.examdate) AS sana,
                   COALESCE(s.starttime, e.starttime) AS vaqt,
                   COALESCE(s.room, e.room) AS xona,
                   e.duration, e.maxscore, e.termname, e.subjectkey,
                   c.fullname AS coursename, c.idnumber AS courseidnumber,
                   r.score, r.holat, r.capped, r.retake
              FROM {local_targetzenit_exam_sitting} s
              JOIN {local_targetzenit_exam} e ON e.id = s.examid
              JOIN {course} c ON c.id = s.courseid
              JOIN {enrol} en ON en.courseid = c.id
              JOIN {user_enrolments} ue ON ue.enrolid = en.id AND ue.userid = :u
         LEFT JOIN {local_targetzenit_exam_result} r
                ON r.sittingid = s.id AND r.studentid = ue.userid
             WHERE " . implode(' AND ', $shart) . " AND ue.status = 0
          ORDER BY COALESCE(s.examdate, e.examdate) DESC, e.id DESC", $params);
    }
}
