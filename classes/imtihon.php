<?php
// IMTIHON — o'qituvchi portalining imtihon moduli (IMTIHON_ARXITEKTURA.md).
//
// QAROR (0.2-tamoyil): yadro ustida qayta ixtiro YO'Q. Imtihon = Moodle
// `quiz` (Test, avto) yoki `assign` (Yozma ish, qo'lda). Ro'yxat, holat,
// sanoqlar — hammasi `topshiriq` backendidan qayta ishlatiladi; bu klass
// faqat IMTIHON ramkasini qo'shadi (tur: test/yozma, holat: qoralama…tugagan).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class imtihon {

    /**
     * O'qituvchining imtihonlari (quiz + assign), muddat bo'yicha kamayishda.
     * Har qator `topshiriq::royxat()` shaklida + qo'shimcha: `tur`, `holat`,
     * `holatkey` (til kaliti).
     *
     * @param int $userid o'qituvchi
     * @param array|null $courseids NULL = o'z kurslari; aks holda berilgani
     *        (koordinator butun maktabni ko'radi)
     */
    public static function royxat(int $userid, ?array $courseids = null): array {
        if ($courseids === null) {
            $courseids = topshiriq::mening_kurslarim($userid);
        }
        if (!$courseids) {
            return [];
        }
        global $DB;
        $rows = topshiriq::royxat(null, $courseids);
        // Nizom bog'lanishlari (BSB/CHSB/LI tegi) — bitta so'rovda.
        $nizom = [];
        if ($rows) {
            list($in, $p) = $DB->get_in_or_equal(
                array_map(fn($r) => (int) $r->cmid, $rows), SQL_PARAMS_NAMED);
            $nizom = $DB->get_records_sql("
                SELECT i.cmid, e.turi
                  FROM {local_targetzenit_imtihon} i
                  JOIN {local_targetzenit_exam} e ON e.id = i.examid
                 WHERE i.cmid $in", $p);
        }
        foreach ($rows as $r) {
            $r->tur = ($r->turi === 'quiz') ? 'test' : 'yozma';
            $r->nizom = isset($nizom[(int) $r->cmid]) ? (string) $nizom[(int) $r->cmid]->turi : '';
            list($r->holat, $r->holatkey) = self::holat($r);
        }
        return $rows;
    }

    /**
     * Imtihon holati: [kalit, til_kaliti].
     *   draft  — yashirin / tayyorlanmoqda (ko'rinmaydigan modul)
     *   plan   — rejalashtirilgan (hali ochilmagan)
     *   live   — faol (o'quvchilar topshirmoqda)
     *   grade  — baholanmoqda (muddat o'tgan, baholanmagan ish bor)
     *   closed — tugagan (hammasi baholangan yoki baholanmaydigan)
     */
    public static function holat(\stdClass $r): array {
        $now = time();
        if (empty($r->visible)) {
            return ['draft', 'im_st_draft'];
        }
        $ochilish = (int) $r->ochilish;
        $muddat = (int) $r->duedate;
        if ($ochilish && $now < $ochilish) {
            return ['plan', 'im_st_plan'];
        }
        if ($muddat && $now > $muddat) {
            if ((int) $r->topshirgan > 0 && (int) $r->baholangan < (int) $r->topshirgan) {
                return ['grade', 'im_st_grade'];
            }
            return ['closed', 'im_st_closed'];
        }
        return ['live', 'im_st_live'];
    }

    /**
     * Holatga mos amal: [til_kaliti, moodle_url, asosiymi].
     * draft→tahrir(sozlama) · plan→ko'rish · live→kuzatish · grade→baholash ·
     * closed→natijalar.
     */
    public static function amal(\stdClass $r): array {
        switch ($r->holat) {
            case 'draft':
                return ['im_amal_edit', topshiriq::sozlama_url((int) $r->cmid), false];
            case 'plan':
                return ['im_amal_open', topshiriq::ochish_url($r), false];
            case 'live':
                return ['im_amal_watch', topshiriq::ochish_url($r), false];
            case 'grade':
                return ['im_amal_grade',
                    new \moodle_url('/local/targetzenit/imtihon_baho.php', ['cmid' => (int) $r->cmid]),
                    true];
            case 'closed':
            default:
                return ['im_amal_results',
                    new \moodle_url('/local/targetzenit/imtihon_baho.php', ['cmid' => (int) $r->cmid]),
                    false];
        }
    }

    /**
     * Imtihon (quiz yoki assign) YARATADI — Moodle `create_module` orqali
     * (homework.php va quiz generator naqshi). BARCHA mexanika yadroda.
     *
     * @param array $d sehrgar ma'lumoti: oila(test|yozma), courseid, name,
     *        timeopen, duration_min | duedate, attempts, shuffle, review,
     *        behaviour, password, subnet, securewindow, grade, subfile, subtext, blind
     * @return \stdClass|null yaratilgan modul (coursemodule bor) yoki null
     */
    public static function yarat(array $d): ?\stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $test = ($d['oila'] ?? 'test') === 'test';
        $info = new \stdClass();
        $info->course = (int) $d['courseid'];
        $info->section = 0;
        $info->visible = 1;
        $info->name = \core_text::substr(trim((string) ($d['name'] ?? '')), 0, 255);
        if ($info->name === '') {
            $info->name = get_string('im_nav', 'local_targetzenit');
        }
        $info->intro = '';
        $info->introformat = FORMAT_HTML;
        // create_module MAJBURIY talab qiladi (FEATURE_MOD_INTRO): editor massivi.
        $info->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0];

        if ($test) {
            $info->modulename = 'quiz';
            $dur = max(0, (int) ($d['duration_min'] ?? 0)) * 60;
            $info->timeopen = (int) ($d['timeopen'] ?? 0);
            $info->timeclose = ($info->timeopen && $dur) ? $info->timeopen + $dur + 900 : 0;
            $info->timelimit = $dur;
            $info->overduehandling = 'autosubmit';
            $info->graceperiod = 0;
            $info->preferredbehaviour = (string) ($d['behaviour'] ?? 'deferredfeedback');
            $info->canredoquestions = 0;
            $info->attempts = max(0, (int) ($d['attempts'] ?? 1));
            $info->attemptonlast = 0;
            $info->grademethod = 1;              // QUIZ_GRADEHIGHEST
            $info->decimalpoints = 2;
            $info->questiondecimalpoints = -1;
            self::quiz_review($info, (string) ($d['review'] ?? 'closed'));
            $info->questionsperpage = 1;
            $info->navmethod = 'free';
            $info->shuffleanswers = !empty($d['shuffle']) ? 1 : 0;
            $info->sumgrades = 0;
            $info->grade = (float) ($d['grade'] ?? 100);
            $info->quizpassword = (string) ($d['password'] ?? '');
            $info->subnet = (string) ($d['subnet'] ?? '');          // faqat sinf IP/tarmog'i
            $info->browsersecurity = '';
            // SEB (Safe Exam Browser): 4 = CLIENT_CONFIG — SEB talab qilinadi,
            // qo'shimcha config kerak emas. O'qituvchida SEB ruxsati bo'lmasa
            // yadro jim o'tkazib yuboradi (xato bermaydi).
            $info->seb_requiresafeexambrowser = !empty($d['seb']) ? 4 : 0;
            // Offline (Moodle mobil ilova) — SEB bilan birga EMAS.
            $info->allowofflineattempts = (!empty($d['offline']) && empty($d['seb'])) ? 1 : 0;
            $info->delay1 = 0;
            $info->delay2 = 0;
            $info->showuserpicture = 0;
            $info->showblocks = 0;
            $info->timecreated = time();
            $info->timemodified = time();
        } else {
            $info->modulename = 'assign';
            $info->duedate = (int) ($d['duedate'] ?? 0);
            $info->allowsubmissionsfromdate = (int) ($d['timeopen'] ?? 0);
            $info->cutoffdate = 0;
            $info->gradingduedate = 0;
            $info->timelimit = 0;
            $info->grade = (float) ($d['grade'] ?? 100);
            $info->submissiondrafts = 0;
            $info->requiresubmissionstatement = 0;
            $info->sendnotifications = 0;
            $info->sendlatenotifications = 0;
            $info->sendstudentnotifications = 1;
            $info->teamsubmission = 0;
            $info->requireallteammemberssubmit = 0;
            $info->teamsubmissiongroupingid = 0;
            $info->blindmarking = !empty($d['blind']) ? 1 : 0;
            $info->hidegrader = 0;
            $info->markingworkflow = 0;
            $info->markingallocation = 0;
            $info->markinganonymous = 0;
            $info->attemptreopenmethod = 'none';
            $info->maxattempts = -1;
            $info->completionsubmit = 0;
            $info->preventsubmissionnotingroup = 0;
            $info->activityeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0];
            $info->assignsubmission_onlinetext_enabled = !empty($d['subtext']) ? 1 : 0;
            $info->assignsubmission_onlinetext_wordlimitenabled = 0;
            $info->assignsubmission_onlinetext_wordlimit = 0;
            $info->assignsubmission_file_enabled = empty($d['subfile']) ? 0 : 1;
            $info->assignsubmission_file_maxfiles = 3;
            $info->assignsubmission_file_maxsizebytes = 0;
            $info->assignsubmission_file_filetypes = '';
            $info->assignsubmission_comments_enabled = 0;
            $info->assignfeedback_comments_enabled = 1;
            $info->assignfeedback_comments_commentinline = 0;
            $info->assignfeedback_file_enabled = 1;
            $info->assignfeedback_editpdf_enabled = 1;   // PDF ustiga belgi (editpdf)
        }

        try {
            return create_module($info);
        } catch (\Throwable $e) {
            self::$xato = $e->getMessage();
            debugging('local_targetzenit imtihon yaratilmadi: ' . $e->getMessage(),
                DEBUG_DEVELOPER);
            return null;
        }
    }

    /** Oxirgi yaratishdagi xato matni (tashxis uchun). */
    public static $xato = '';

    /**
     * Quiz «ko'rib chiqish» profilini flat maydonlarga yoyadi (generator shakli).
     *   during     — mashq: har bosqichda hammasi ko'rinadi
     *   marksonly  — faqat ball, yopilgach
     *   closed     — IMTIHON standarti: yopilgach to'liq (javob+izoh)
     */
    protected static function quiz_review(\stdClass $info, string $profil): void {
        $fields = ['attempt', 'correctness', 'maxmarks', 'marks', 'specificfeedback',
                   'generalfeedback', 'rightanswer', 'overallfeedback'];
        $stages = ['during', 'immediately', 'open', 'closed'];
        foreach ($stages as $st) {
            foreach ($fields as $f) {
                $info->{$f . $st} = 0;
            }
        }
        if ($profil === 'during') {
            foreach ($stages as $st) {
                foreach ($fields as $f) {
                    $info->{$f . $st} = 1;
                }
            }
        } else if ($profil === 'marksonly') {
            $info->attemptclosed = 1;
            $info->marksclosed = 1;
            $info->maxmarksclosed = 1;
            $info->overallfeedbackclosed = 1;
        } else {   // closed
            foreach ($fields as $f) {
                $info->{$f . 'closed'} = 1;
            }
        }
    }

    /** Ro'yxat statistikasi: [faol, baholanmoqda, bu_hafta]. */
    public static function stat(array $rows): array {
        $now = time();
        $hafta = $now + 7 * 86400;
        $faol = $grade = $buhafta = 0;
        foreach ($rows as $r) {
            if ($r->holat === 'live') {
                $faol++;
            }
            if ($r->holat === 'grade') {
                $grade++;
            }
            $sana = (int) $r->ochilish ?: (int) $r->duedate;
            if ($sana >= $now && $sana <= $hafta) {
                $buhafta++;
            }
        }
        return [$faol, $grade, $buhafta];
    }

    // ======================================================= NIZOM BOG'LANISHI

    /** Bog'lanish yozuvi (cmid bo'yicha) yoki null. */
    public static function nizom_link(int $cmid): ?\stdClass {
        global $DB;
        $r = $DB->get_record('local_targetzenit_imtihon', ['cmid' => $cmid]);
        return $r ?: null;
    }

    /**
     * Imtihonni nizom (_exam) ga bog'laydi: o'qituvchining O'Z kursi uchun
     * _exam + bitta sitting yaratadi va _imtihon bog'lanishini yozadi.
     * exam::save KOORDINATOR ruxsatini talab qiladi — shuning uchun bu yerda
     * to'g'ridan-to'g'ri (lekin FAQAT o'z kursida, teach ruxsati bilan).
     *
     * @return array|null ['examid','sittingid'] yoki null
     */
    public static function nizom_yarat(int $cmid, string $turi, string $termname, int $userid): ?array {
        global $DB;
        if (!in_array($turi, ['BSB', 'CHSB', 'LI'], true)) {
            return null;
        }
        $cm = get_coursemodule_from_id('', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return null;
        }
        $ctx = \context_course::instance((int) $cm->course, IGNORE_MISSING);
        if (!$ctx || !has_capability('local/targetzenit:teach', $ctx, $userid)) {
            return null;
        }
        $course = $DB->get_record('course', ['id' => $cm->course], 'id, idnumber, fullname');
        $q = explode('::', (string) ($course->idnumber ?? ''));
        $year = trim($q[0] ?? '');
        $groupname = trim($q[1] ?? '');
        $subjectkey = trim($q[2] ?? '');
        if ($year === '' || $subjectkey === '') {
            return null;   // idnumber Frappe formatida emas — nizomga ulab bo'lmaydi
        }
        $gradelevel = exam::daraja($groupname);

        // Modul bahosining maksimali = _exam maxscore (shkala moslashsin).
        $modmax = (int) $DB->get_field($cm->modname, 'grade', ['id' => $cm->instance]);
        if ($modmax <= 0) {
            $modmax = 100;
        }

        $examid = (int) $DB->insert_record('local_targetzenit_exam', (object) [
            'turi' => $turi,
            'academicyear' => $year,
            'termname' => $termname,
            'subjectkey' => $subjectkey,
            'gradelevel' => $gradelevel,
            'name' => \core_text::substr((string) $cm->name, 0, 255),
            'shakli' => $cm->modname === 'quiz' ? 'onlayn' : 'qogoz',
            'examdate' => date('Y-m-d'),
            'starttime' => null,
            'room' => null,
            'duration' => 45,
            'maxscore' => max(1, $modmax),
            'randomcount' => 0,
            'retro' => 0,
            'status' => 'tasdiqlangan',
            'createdby' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $sittingid = (int) $DB->insert_record('local_targetzenit_exam_sitting', (object) [
            'examid' => $examid,
            'courseid' => (int) $cm->course,
            'groupkey' => $groupname,
            'groupname' => $groupname,
            'examdate' => date('Y-m-d'),
            'starttime' => null,
            'room' => null,
            'quizcmid' => $cm->modname === 'quiz' ? $cmid : null,
            'status' => 'otkazildi',
            'timegenerated' => time(),
        ]);
        $DB->insert_record('local_targetzenit_imtihon', (object) [
            'cmid' => $cmid,
            'moduli' => $cm->modname,
            'examid' => $examid,
            'sittingid' => $sittingid,
            'egauserid' => $userid,
            'timecreated' => time(),
        ]);
        return ['examid' => $examid, 'sittingid' => $sittingid];
    }

    /**
     * Moodle modul baholarini nizom _exam_result ga IMPORT qiladi (tekshirilgan
     * exam::result_save orqali) va har o'quvchi uchun chorak balini qayta
     * hisoblaydi. O'qituvchi Moodle'da baholagach bir tugma bilan chaqiradi.
     *
     * @return array ['n' => yozildi, 'jami' => baholangan, 'xato' => kod|'']
     */
    public static function import_ballar(int $cmid, int $userid): array {
        global $DB;
        $link = self::nizom_link($cmid);
        if (!$link) {
            return ['n' => 0, 'jami' => 0, 'xato' => 'nolink'];
        }
        $exam = exam::get((int) $link->examid);
        $sitting = $DB->get_record('local_targetzenit_exam_sitting', ['id' => $link->sittingid]);
        if (!$exam || !$sitting) {
            return ['n' => 0, 'jami' => 0, 'xato' => 'noexam'];
        }
        $cm = get_coursemodule_from_id('', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return ['n' => 0, 'jami' => 0, 'xato' => 'nocm'];
        }

        if ($cm->modname === 'quiz') {
            $rows = $DB->get_records_sql(
                "SELECT userid, grade FROM {quiz_grades}
                  WHERE quiz = :q AND grade IS NOT NULL", ['q' => $cm->instance]);
        } else {
            $rows = $DB->get_records_sql(
                "SELECT userid, MAX(grade) AS grade FROM {assign_grades}
                  WHERE assignment = :a AND grade IS NOT NULL AND grade >= 0
               GROUP BY userid", ['a' => $cm->instance]);
        }
        $modmax = (float) $DB->get_field($cm->modname, 'grade', ['id' => $cm->instance]);
        if ($modmax <= 0) {
            $modmax = (float) max(1, (int) $exam->maxscore);
        }

        $n = 0;
        foreach ($rows as $r) {
            $raw = round((float) $r->grade / $modmax * (int) $exam->maxscore, 2);
            try {
                exam::result_save((int) $link->sittingid, (int) $r->userid, $raw);
                exam::term_score_save((int) $r->userid, (int) $sitting->courseid,
                    (string) $exam->academicyear, (string) $exam->termname);
                $n++;
            } catch (\Throwable $e) {
                // Kursga yozilmagan / ruxsat yo'q — o'tkazib yuboramiz.
                debugging('imtihon import: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return ['n' => $n, 'jami' => count($rows), 'xato' => ''];
    }
}
