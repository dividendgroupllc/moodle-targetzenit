<?php
// Chorak jurnali — e-maktab/qog'oz jurnal uslubi.
//
// Uch zona: chapda o'quvchilar, o'rtada baholar setkasi (chorakdagi dars
// kunlari bo'yicha), o'ngda shu chorakning mavzulari va uy vazifalari.
//
// KATAK QIYMATI bitta: bo'sh (keldi, baho yo'q) | raqam 1-10 (keldi, baho) |
// k (kelmadi) | s (sababli) | kech (kechikdi). Yo'qlama alohida ustun emas.
//
// DARS KUNLARI saqlanmaydi — ular jadval (timetable) + chorak oralig'i +
// bayramlardan hisoblanadi. Shuning uchun jadval yoki bayram o'zgarsa,
// jurnal ustunlari o'zi to'g'rilanadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class journal {

    /** Katakka yozilishi mumkin bo'lgan harflar. */
    const CODES = [
        'k' => 'absent',
        's' => 'excused',
        'kech' => 'late',
    ];

    /** Teskari: yozuvdan katak matniga. */
    const CODES_BACK = [
        'absent' => 'k',
        'excused' => 's',
        'late' => 'kech',
    ];

    /**
     * Chorakdagi dars kunlari: [['date' => 'YYYY-MM-DD', 'lessonno' => n], ...]
     * Jadvaldan olinadi, bayram va dam olish kunlari chiqarib tashlanadi.
     */
    public static function lessons_in_term(string $courseidnumber, string $start, string $end,
                                           array $holidays): array {
        global $DB;

        $jadval = $DB->get_records('local_targetzenit_timetable',
            ['courseidnumber' => $courseidnumber], 'dayno ASC, lessonno ASC');
        if (!$jadval) {
            return [];
        }

        // Kun bo'yicha guruhlaymiz: [dayno => [lessonno, ...]]
        $kunlar = [];
        foreach ($jadval as $j) {
            $kunlar[(int) $j->dayno][] = (int) $j->lessonno;
        }

        $out = [];
        $kun = strtotime($start);
        $oxiri = strtotime($end);
        while ($kun <= $oxiri) {
            $sana = date('Y-m-d', $kun);
            $dow = (int) date('N', $kun);
            if (isset($kunlar[$dow]) && !isset($holidays[$sana])) {
                foreach ($kunlar[$dow] as $no) {
                    $out[] = ['date' => $sana, 'lessonno' => $no, 'key' => $sana . '#' . $no];
                }
            }
            $kun = strtotime('+1 day', $kun);
        }
        return $out;
    }

    /** Shu kursning chorakdagi dars yozuvlari: ['YYYY-MM-DD#n' => record] */
    public static function lesson_records(int $courseid, string $start, string $end): array {
        global $DB;
        $rows = $DB->get_records_select('local_targetzenit_lesson',
            'courseid = :cid AND lessondate >= :a AND lessondate <= :b',
            ['cid' => $courseid, 'a' => $start, 'b' => $end]);
        $out = [];
        foreach ($rows as $r) {
            $out[$r->lessondate . '#' . $r->lessonno] = $r;
        }
        return $out;
    }

    /** Baho/davomat kataklari: [userid][lessonid] => record */
    public static function cells(array $lessonids): array {
        global $DB;
        if (!$lessonids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('local_targetzenit_record', "lessonid $in", $params);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->userid][(int) $r->lessonid] = $r;
        }
        return $out;
    }

    /**
     * Katak matnini yozuvga aylantiradi.
     * Qaytaradi: ['attendance' => ..., 'grade' => ...] yoki tushunarsiz bo'lsa null.
     */
    public static function parse_cell(string $raw): ?array {
        $v = \core_text::strtolower(trim($raw));

        if ($v === '') {
            return ['attendance' => 'present', 'grade' => null];
        }
        if (isset(self::CODES[$v])) {
            return ['attendance' => self::CODES[$v], 'grade' => null];
        }
        if (ctype_digit($v)) {
            $n = (int) $v;
            if ($n >= 1 && $n <= lesson::MAX_GRADE) {
                return ['attendance' => 'present', 'grade' => $n];
            }
        }
        return null;   // tushunarsiz — tegilmaydi
    }

    /** Yozuvdan katak matni. */
    public static function cell_text(?\stdClass $rec): string {
        if (!$rec) {
            return '';
        }
        if ($rec->grade !== null && $rec->grade !== '') {
            return (string) $rec->grade;
        }
        return self::CODES_BACK[$rec->attendance] ?? '';
    }

    /**
     * Excel/CSV dan chorak rejasini o'qiydi: № | Mavzu | Uy vazifa.
     * Sana yo'q — mavzular chorakdagi darslarga KETMA-KET joylanadi.
     * Qaytaradi: [['topic' => ..., 'homework' => ...], ...]
     */
    public static function read_plan(string $filepath): array {
        $reja = [];

        $kengaytma = \core_text::strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if ($kengaytma === 'csv') {
            $fh = fopen($filepath, 'r');
            $rows = [];
            while (($r = fgetcsv($fh, 0, ',')) !== false) {
                $rows[] = $r;
            }
            fclose($fh);
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        }

        foreach ($rows as $r) {
            $a = isset($r[0]) ? trim((string) $r[0]) : '';
            $b = isset($r[1]) ? trim((string) $r[1]) : '';
            $c = isset($r[2]) ? trim((string) $r[2]) : '';

            // Sarlavha qatorini o'tkazib yuboramiz (birinchi katak raqam emas)
            if ($b === '' && $c === '') {
                continue;
            }
            if (!is_numeric($a) && $reja === []) {
                continue;
            }
            $reja[] = ['topic' => \core_text::substr($b, 0, 1000),
                       'homework' => \core_text::substr($c, 0, 1000)];
        }
        return $reja;
    }

    /**
     * Rejani chorakdagi darslarga ketma-ket yozadi.
     * MUHIM: o'qituvchi qo'lda yozgan mavzuni QAYTARIB TASHLAMAYDI —
     * faqat bo'sh mavzular to'ldiriladi (agar $overwrite false bo'lsa).
     */
    public static function apply_plan(int $courseid, array $lessons, array $reja,
                                      int $teacherid, bool $overwrite = false): array {
        $yozildi = 0;
        $otkazildi = 0;

        foreach ($lessons as $i => $l) {
            if (!isset($reja[$i])) {
                break;
            }
            $dars = lesson::get_or_create($courseid, $l['date'], (int) $l['lessonno'], $teacherid);
            $bor = trim((string) $dars->topic) !== '' || trim((string) $dars->homework) !== '';
            if ($bor && !$overwrite) {
                $otkazildi++;
                continue;
            }
            lesson::save_lesson((int) $dars->id, $reja[$i]['topic'], $reja[$i]['homework'], $teacherid);
            $yozildi++;
        }

        return ['yozildi' => $yozildi, 'otkazildi' => $otkazildi, 'reja' => count($reja),
                'darslar' => count($lessons)];
    }
}
