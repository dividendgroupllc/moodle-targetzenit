<?php
// Dars kokpiti: mavzu, uy vazifa, yo'qlama va baho.
//
// Bitta "dars" = (kurs + sana + dars raqami). Jadval (timetable) qaysi kunlarda
// qaysi dars bo'lishini aytadi, bu yerda esa darsning HAQIQATDA qanday
// o'tgani saqlanadi: mavzu, kim keldi, kim qanday baho oldi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class lesson {

    /** Yo'qlama belgilari. */
    const ATTEND = [
        'present' => 'Keldi',
        'absent'  => 'Kelmadi',
        'late'    => 'Kechikdi',
        'excused' => 'Sababli',
    ];

    /** Qisqa belgilar — jurnal setkasi uchun. */
    const ATTEND_SHORT = [
        'present' => '',
        'absent'  => 'n/b',
        'late'    => 'kech',
        'excused' => 'sab',
    ];

    const MAX_GRADE = 10;

    /** Shu dars yozuvi; bo'lmasa yaratadi. */
    public static function get_or_create(int $courseid, string $date, int $lessonno, int $teacherid): \stdClass {
        global $DB;
        $rec = $DB->get_record('local_targetzenit_lesson',
            ['courseid' => $courseid, 'lessondate' => $date, 'lessonno' => $lessonno]);
        if ($rec) {
            return $rec;
        }
        $rec = (object) [
            'courseid' => $courseid,
            'lessondate' => $date,
            'lessonno' => $lessonno,
            'topic' => '',
            'homework' => '',
            'teacherid' => $teacherid,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $rec->id = $DB->insert_record('local_targetzenit_lesson', $rec);
        return $rec;
    }

    /** Kursdagi o'quvchilar (faqat student roli), familiya bo'yicha. */
    public static function students(\context_course $context): array {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => 'student'], 'id');
        if (!$role) {
            return [];
        }
        return get_role_users($role->id, $context, false,
            'u.id, u.firstname, u.lastname, u.idnumber',
            'u.lastname ASC, u.firstname ASC');
    }

    /** Shu darsdagi yozuvlar: [userid => record] */
    public static function records(int $lessonid): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_record', ['lessonid' => $lessonid]);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->userid] = $r;
        }
        return $out;
    }

    /** Bitta o'quvchining yozuvini saqlaydi (bor bo'lsa yangilaydi). */
    public static function save_record(int $lessonid, int $userid, string $attendance, $grade, string $comment): void {
        global $DB;

        if (!isset(self::ATTEND[$attendance])) {
            $attendance = 'present';
        }
        $grade = ($grade === '' || $grade === null) ? null : max(1, min(self::MAX_GRADE, (int) $grade));
        $comment = \core_text::substr(trim($comment), 0, 255);

        $rec = $DB->get_record('local_targetzenit_record', ['lessonid' => $lessonid, 'userid' => $userid]);
        $eski_baho = $rec ? $rec->grade : false;

        if ($rec) {
            $rec->attendance = $attendance;
            $rec->grade = $grade;
            $rec->comment = $comment;
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_record', $rec);
        } else {
            $DB->insert_record('local_targetzenit_record', (object) [
                'lessonid' => $lessonid,
                'userid' => $userid,
                'attendance' => $attendance,
                'grade' => $grade,
                'comment' => $comment,
                'timemodified' => time(),
            ]);
        }

        // Baho Moodle'ning STANDART gradebook'iga ham yoziladi — o'quvchi uni
        // Moodle'ning o'z "Grades" sahifasi va mobil ilovasida ko'radi.
        // Faqat O'ZGARGANDA yuboriladi: gradebook yangilash og'ir amal, jurnalda
        // esa bir saqlashda yuzlab katak o'tadi.
        if ($eski_baho === false || (string) $eski_baho !== (string) $grade) {
            $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid]);
            if ($dars) {
                gradebook::push($dars, $userid, $grade);
            }
        }
    }

    /** Mavzu va uy vazifani saqlaydi. */
    public static function save_lesson(int $lessonid, string $topic, string $homework, int $teacherid): void {
        global $DB;

        $eski = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid]);

        $DB->update_record('local_targetzenit_lesson', (object) [
            'id' => $lessonid,
            'topic' => \core_text::substr(trim($topic), 0, 1000),
            'homework' => \core_text::substr(trim($homework), 0, 1000),
            'teacherid' => $teacherid,
            'timemodified' => time(),
        ]);

        $yangi = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid]);
        if (!$yangi) {
            return;
        }

        // Mavzu o'zgarsa — gradebook ustunining nomi ham yangilanadi
        if (!$eski || $eski->topic !== $yangi->topic) {
            gradebook::lesson_item($yangi);
        }

        // Uy vazifa yozilgan/o'zgargan bo'lsa — STANDART mod_assign yaratiladi
        // yoki matni yangilanadi. Bo'sh bo'lsa hech narsa yaratilmaydi.
        if (!$eski || $eski->homework !== $yangi->homework) {
            homework::ensure($yangi);
        }
    }

    /**
     * Jurnal: shu kursning oxirgi N darsi va ularning yozuvlari.
     * Qaytaradi: ['lessons' => [...], 'cells' => [userid][lessonid] => record]
     */
    public static function journal(int $courseid, int $limit = 12): array {
        global $DB;

        $lessons = $DB->get_records('local_targetzenit_lesson', ['courseid' => $courseid],
            'lessondate DESC, lessonno DESC', '*', 0, $limit);
        $lessons = array_reverse($lessons, true);   // eskisidan yangisiga
        if (!$lessons) {
            return ['lessons' => [], 'cells' => []];
        }

        list($in, $params) = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('local_targetzenit_record', "lessonid $in", $params);

        $cells = [];
        foreach ($rows as $r) {
            $cells[(int) $r->userid][(int) $r->lessonid] = $r;
        }
        return ['lessons' => $lessons, 'cells' => $cells];
    }

    /** O'quvchining shu kursdagi o'rtacha bahosi (jurnal uchun). */
    public static function average(array $cells_for_user): ?float {
        $baholar = [];
        foreach ($cells_for_user as $r) {
            if ($r->grade !== null && $r->grade !== '') {
                $baholar[] = (int) $r->grade;
            }
        }
        return $baholar ? round(array_sum($baholar) / count($baholar), 1) : null;
    }
}
