<?php
// O'quvchi (va ota-ona kelajakda) ko'radigan ma'lumot: o'z baholari,
// davomati va uy vazifalari.
//
// Baholar Moodle gradebook'ida emas, bizning jadvalimizda — shuning uchun
// Moodle'ning standart "Grades" sahifasi bo'sh ko'rinadi va o'quvchiga
// alohida sahifa kerak.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class student_view {

    /**
     * O'quvchining chorakdagi baholari, fanlar bo'yicha.
     * Qaytaradi: [courseid => ['course' => obj, 'items' => [...], 'ortacha' => float|null]]
     */
    public static function grades(int $userid, string $start, string $end): array {
        global $DB;

        $sql = "SELECT r.id, l.courseid, l.lessondate, l.lessonno, l.topic,
                       r.grade, r.attendance
                  FROM {local_targetzenit_record} r
                  JOIN {local_targetzenit_lesson} l ON l.id = r.lessonid
                 WHERE r.userid = :uid
                   AND l.lessondate >= :a AND l.lessondate <= :b
              ORDER BY l.courseid, l.lessondate, l.lessonno";
        $rows = $DB->get_records_sql($sql, ['uid' => $userid, 'a' => $start, 'b' => $end]);

        $natija = [];
        foreach ($rows as $r) {
            $cid = (int) $r->courseid;
            if (!isset($natija[$cid])) {
                $natija[$cid] = ['items' => [], 'baholar' => []];
            }
            $natija[$cid]['items'][] = $r;
            if ($r->grade !== null && $r->grade !== '') {
                $natija[$cid]['baholar'][] = (int) $r->grade;
            }
        }

        // Kurs nomlari va o'rtacha
        if ($natija) {
            $courses = $DB->get_records_list('course', 'id', array_keys($natija),
                '', 'id, fullname, shortname');
            foreach ($natija as $cid => &$v) {
                $v['course'] = $courses[$cid] ?? null;
                $v['ortacha'] = $v['baholar']
                    ? round(array_sum($v['baholar']) / count($v['baholar']), 1) : null;
                $v['davomat'] = self::attendance_summary($v['items']);
            }
            unset($v);

            uasort($natija, function($a, $b) {
                return strcmp($a['course']->fullname ?? '', $b['course']->fullname ?? '');
            });
        }
        return $natija;
    }

    /** Davomat xulosasi: nechta kelmadi / sababli / kechikdi. */
    protected static function attendance_summary(array $items): array {
        $x = ['absent' => 0, 'excused' => 0, 'late' => 0, 'jami' => count($items)];
        foreach ($items as $i) {
            if (isset($x[$i->attendance])) {
                $x[$i->attendance]++;
            }
        }
        return $x;
    }

    /**
     * Yaqin kunlardagi uy vazifalari — o'quvchi yozilgan kurslar bo'yicha.
     * Bugundan orqaga $kun kun va oldinga qaraydi.
     */
    public static function homework(int $userid, string $year, int $kun = 7): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :uid AND ue.status = 0 AND c.idnumber LIKE :p";
        $cids = $DB->get_fieldset_sql($sql, ['uid' => $userid, 'p' => $year . '::%']);
        if (!$cids) {
            return [];
        }

        list($in, $params) = $DB->get_in_or_equal($cids, SQL_PARAMS_NAMED);
        $params['a'] = date('Y-m-d', strtotime("-{$kun} days"));
        $params['b'] = date('Y-m-d', strtotime("+{$kun} days"));

        $rows = $DB->get_records_select('local_targetzenit_lesson',
            "courseid $in AND lessondate >= :a AND lessondate <= :b
             AND homework IS NOT NULL AND homework <> ''",
            $params, 'lessondate DESC, lessonno DESC');

        if (!$rows) {
            return [];
        }
        $courses = $DB->get_records_list('course', 'id', $cids, '', 'id, fullname');
        foreach ($rows as $r) {
            $r->coursename = $courses[$r->courseid]->fullname ?? '';
        }
        return $rows;
    }
}
