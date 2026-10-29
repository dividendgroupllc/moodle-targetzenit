<?php
// Dars jadvali ma'lumotlari va sana mantig'i.
//
// Ma'lumot Frappe'dan keladi (local_targetzenit_push_timetable) va faqat
// "kun + dars raqami" ko'rinishida saqlanadi — aniq sanalar yo'q. Sanalar shu
// yerda, chorak oraliqlari va bayramlar asosida hisoblanadi. Shuning uchun
// chorak sanasi yoki bayram ro'yxati Frappe'da o'zgarsa, jadval o'zi to'g'rilanadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class timetable {

    /** Maktab dush-juma ishlaydi. */
    const DAYS = [1 => 'Dushanba', 2 => 'Seshanba', 3 => 'Chorshanba', 4 => 'Payshanba', 5 => 'Juma'];

    /** Jadvalda mavjud oxirgi o'quv yili. */
    public static function current_year(): string {
        global $DB;
        $year = $DB->get_field_sql(
            'SELECT academicyear FROM {local_targetzenit_timetable}
              GROUP BY academicyear ORDER BY academicyear DESC',
            null, IGNORE_MULTIPLE
        );
        return $year ?: '';
    }

    /** Chorak oraliqlari. */
    public static function terms(string $year): array {
        global $DB;
        return $DB->get_records('local_targetzenit_calendar',
            ['academicyear' => $year, 'entrytype' => 'term'], 'startdate ASC');
    }

    /** ['YYYY-MM-DD' => 'Bayram nomi'] */
    public static function holidays(string $year): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_calendar',
            ['academicyear' => $year, 'entrytype' => 'holiday']);
        $out = [];
        foreach ($rows as $r) {
            $out[$r->startdate] = $r->name;
        }
        return $out;
    }

    /**
     * Shu sana dars kunimi?
     * Qaytaradi: ['ok' => bool, 'reason' => string] — sabab foydalanuvchiga ko'rsatiladi.
     */
    public static function school_day(string $date, array $terms, array $holidays): array {
        $dow = (int) date('N', strtotime($date));
        if ($dow >= 6) {
            return ['ok' => false, 'reason' => 'Dam olish kuni'];
        }
        if (isset($holidays[$date])) {
            return ['ok' => false, 'reason' => $holidays[$date]];
        }
        foreach ($terms as $t) {
            if ($date >= $t->startdate && $date <= $t->enddate) {
                return ['ok' => true, 'reason' => $t->name];
            }
        }
        return ['ok' => false, 'reason' => "Chorakdan tashqari (ta'til)"];
    }

    /** Berilgan sana tushgan haftaning dushanba-juma sanalari: [dayno => 'YYYY-MM-DD'] */
    public static function week_dates(string $anchor): array {
        $ts = strtotime($anchor);
        $dow = (int) date('N', $ts);
        $monday = strtotime('-' . ($dow - 1) . ' days', $ts);
        $out = [];
        foreach (array_keys(self::DAYS) as $d) {
            $out[$d] = date('Y-m-d', strtotime('+' . ($d - 1) . ' days', $monday));
        }
        return $out;
    }

    /** Guruhning haftalik darslari. */
    public static function for_group(string $year, string $group): array {
        global $DB;
        return $DB->get_records('local_targetzenit_timetable',
            ['academicyear' => $year, 'groupkey' => $group], 'dayno ASC, lessonno ASC');
    }

    /** O'qituvchining haftalik darslari. */
    public static function for_teacher(string $year, string $teacherkey): array {
        global $DB;
        return $DB->get_records('local_targetzenit_timetable',
            ['academicyear' => $year, 'teacherkey' => $teacherkey], 'dayno ASC, lessonno ASC');
    }

    /** Bir kunlik butun maktab jadvali (umumiy ko'rinish uchun). */
    public static function for_day(string $year, int $dayno): array {
        global $DB;
        return $DB->get_records('local_targetzenit_timetable',
            ['academicyear' => $year, 'dayno' => $dayno], 'groupname ASC, lessonno ASC');
    }

    /** Jadvaldagi hamma guruh: [groupkey => groupname] */
    public static function all_groups(string $year): array {
        global $DB;
        $rows = $DB->get_records_sql(
            'SELECT DISTINCT groupkey, groupname FROM {local_targetzenit_timetable}
              WHERE academicyear = :y ORDER BY groupname',
            ['y' => $year]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r->groupkey] = $r->groupname;
        }
        return $out;
    }

    /**
     * Foydalanuvchi o'qituvchimi? Kalit — Moodle user.idnumber (Frappe'dagi
     * Employee ID), u jadvaldagi teacherkey bilan bir xil.
     */
    public static function teacher_key_of(\stdClass $user, string $year): string {
        global $DB;
        if (empty($user->idnumber)) {
            return '';
        }
        $bor = $DB->record_exists('local_targetzenit_timetable',
            ['academicyear' => $year, 'teacherkey' => $user->idnumber]);
        return $bor ? $user->idnumber : '';
    }

    /**
     * Kalit nomuvofiqligi: jadvalda shu odamning DARSI bor, lekin kaliti
     * boshqa.
     *
     * NEGA KERAK: `teacher_key_of()` moslik topmasa bo'sh qaytaradi va sahifa
     * jimgina "sinf jadvali" rejimiga o'tadi — o'qituvchi o'z darslari o'rniga
     * butun sinfning jadvalini ko'radi va "Umumiy jadval" yorlig'i ham
     * yo'qoladi. Hech qanday xato chiqmaydi, shuning uchun sabab topilmaydi.
     *
     * Bu aynan shunday bo'lgan: Frappe'da `Instructor` ga `Employee`
     * bog'langach kalit `INSTR-<ism>` dan `HR-EMP-xxxxx` ga o'zgardi, jadval
     * qatorlarida esa eski kalit qoldi. Yechim — Frappe'dan jadvalni qayta
     * yuborish.
     *
     * Aniqlash usuli: ism bo'yicha. Kalit mos kelmagani uchun boshqa yo'l yo'q
     * — ism faqat SHU ogohlantirish uchun ishlatiladi, ma'lumot bog'lash uchun
     * emas.
     */
    public static function kalit_nomuvofiqmi(\stdClass $user, string $year): bool {
        global $DB;

        if (empty($user->idnumber)) {
            return false;
        }
        // Kaliti bo'yicha topilsa — nomuvofiqlik yo'q.
        if ($DB->record_exists('local_targetzenit_timetable',
                ['academicyear' => $year, 'teacherkey' => $user->idnumber])) {
            return false;
        }
        $ism = fullname($user);
        if (trim($ism) === '') {
            return false;
        }
        // `teachername` — char ustun, shuning uchun oddiy tenglik yetarli.
        return $DB->record_exists('local_targetzenit_timetable',
            ['academicyear' => $year, 'teachername' => $ism]);
    }

    /**
     * Foydalanuvchining guruhlari — yozilgan kurslaridan olinadi.
     * Kurs idnumber'i "<yil>::<guruh>::<fan>" ko'rinishida, ya'ni o'quvchi
     * qaysi sinfda ekanini alohida uzatish shart emas.
     */
    public static function groups_of(int $userid, string $year): array {
        global $DB;
        $sql = "SELECT DISTINCT c.idnumber
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND ue.status = 0
                   AND c.idnumber LIKE :pattern";
        $rows = $DB->get_fieldset_sql($sql, [
            'userid' => $userid,
            'pattern' => $year . '::%',
        ]);
        $out = [];
        foreach ($rows as $idn) {
            $qism = explode('::', $idn);
            if (count($qism) >= 3) {
                $out[$qism[1]] = $qism[1];
            }
        }
        return $out;
    }

    /** Kurs idnumber -> Moodle kurs id (jadvaldan kursga havola qo'yish uchun). */
    public static function course_ids(array $idnumbers): array {
        global $DB;
        $idnumbers = array_values(array_unique(array_filter($idnumbers)));
        if (!$idnumbers) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($idnumbers, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('course', "idnumber $in", $params, '', 'id, idnumber');
        $out = [];
        foreach ($rows as $r) {
            $out[$r->idnumber] = $r->id;
        }
        return $out;
    }
}
