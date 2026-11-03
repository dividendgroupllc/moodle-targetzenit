<?php
// GURUH TUTORI (sinf rahbari).
//
// MUAMMO: jadvalda ba'zi fanlar VAKANT — o'qituvchi hali tayinlanmagan
// (`INSTR-AI VACANT`, `INSTR-Chess vakant`, `INSTR-TUTOR`, ...). Haftada 82 ta
// shunday dars bor va ular hech kimning hisobotiga tushmaydi: slotda
// `teacheruserid = NULL`, demak faktda ham `attributeduserid = NULL`.
//
// QOIDA (foydalanuvchi qarori, 2026-11-03): vakant fanning darsini GURUHNING
// O'Z TUTORI o'tadi. Shuning uchun bunday darslar o'sha tutorga yoziladi va
// panelda ko'rinadi.
//
// NEGA MOODLE'DA SAQLANADI: Frappe'da guruh ↔ tutor bog'lanishi UMUMAN YO'Q —
// tutor u yerda faqat `Employee.designation` dagi lavozim ("Tutor"/"Tyutor",
// oylik tabel kategoriyasi). Qoida: moliya ERP'da, dars jarayoni Moodle'da,
// shuning uchun biriktirish shu yerda yuritiladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class tutor {

    /**
     * Qator kaliti.
     *
     * NEGA HASH: `groupkey` 255 belgili CHAR va Moodle'ning qo'shma indeks
     * chegarasi 333 belgi — `_norm` da aynan shu tuzoqqa tushilgan edi.
     */
    public static function key(string $year, string $groupkey): string {
        return md5($year . '|' . $groupkey);
    }

    /** Shu o'quv yili uchun `groupkey => userid` xaritasi. */
    public static function map(string $year): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_tutor', ['academicyear' => $year],
            '', 'id, groupkey, userid');
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->groupkey] = (int) $r->userid;
        }
        return $out;
    }

    /** Biriktirishni saqlaydi. `$userid = 0` bo'lsa — biriktirish olib tashlanadi. */
    public static function set(string $year, string $groupkey, string $groupname,
                                int $userid): void {
        global $DB;

        $kalit = self::key($year, $groupkey);
        $mavjud = $DB->get_record('local_targetzenit_tutor', ['tutorkey' => $kalit], 'id');

        if (!$userid) {
            if ($mavjud) {
                $DB->delete_records('local_targetzenit_tutor', ['id' => $mavjud->id]);
            }
            return;
        }

        $qator = (object) [
            'tutorkey' => $kalit,
            'academicyear' => $year,
            'groupkey' => $groupkey,
            'groupname' => \core_text::substr($groupname, 0, 255),
            'userid' => $userid,
            'timemodified' => time(),
        ];
        if ($mavjud) {
            $qator->id = $mavjud->id;
            $DB->update_record('local_targetzenit_tutor', $qator);
        } else {
            $DB->insert_record('local_targetzenit_tutor', $qator);
        }
    }

    /**
     * Guruhlar va ularning vakant dars yuki.
     *
     * Biriktirish sahifasi shu ro'yxat bo'yicha chiziladi: eng ko'p vakant
     * darsi bor guruh yuqorida turadi — direktor nimadan boshlashni ko'radi.
     *
     * @return array [groupkey => ['nom' =>, 'vakant' =>, 'fanlar' =>, 'userid' =>]]
     */
    public static function groups(string $year): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT " . $DB->sql_concat('t.groupkey', "'#'", 't.subjectkey') . " AS uniq,
                   t.groupkey, t.groupname, t.subjectname,
                   COUNT(1) AS soni,
                   MAX(CASE WHEN t.teacherkey IS NULL OR t.teacherkey = '' THEN 1
                            WHEN st.userid IS NULL THEN 1 ELSE 0 END) AS vakant
              FROM {local_targetzenit_timetable} t
         LEFT JOIN {local_targetzenit_staff} st
                ON st.academicyear = t.academicyear AND st.teacherkey = t.teacherkey
             WHERE t.academicyear = :y
          GROUP BY t.groupkey, t.groupname, t.subjectkey, t.subjectname", ['y' => $year]);

        $biriktirilgan = self::map($year);
        $out = [];

        foreach ($rows as $r) {
            $g = (string) $r->groupkey;
            if (!isset($out[$g])) {
                $out[$g] = [
                    'nom' => (string) $r->groupname,
                    'vakant' => 0,
                    'fanlar' => [],
                    'userid' => $biriktirilgan[$g] ?? 0,
                ];
            }
            if ((int) $r->vakant === 1) {
                $out[$g]['vakant'] += (int) $r->soni;
                $out[$g]['fanlar'][] = (string) $r->subjectname;
            }
        }

        // Eng ko'p vakant darsi bor guruh birinchi, keyin nom bo'yicha
        uasort($out, function($a, $b) {
            if ($a['vakant'] !== $b['vakant']) {
                return $b['vakant'] <=> $a['vakant'];
            }
            return strnatcasecmp($a['nom'], $b['nom']);
        });
        return $out;
    }

    /**
     * Tutor bo'la oladigan odamlar: jadvalda darsi bor va Moodle hisobiga
     * bog'langan o'qituvchilar.
     *
     * @return array [userid => "Ism Familiya (lavozim/fanlar)"]
     */
    public static function candidates(string $year): array {
        $out = [];
        foreach (staff::all($year) as $st) {
            if (empty($st->userid)) {
                continue;
            }
            $out[(int) $st->userid] = trim($st->teachername . ' — ' . $st->subjects, ' —');
        }
        asort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    /** Tutori biriktirilmagan, lekin vakant darsi bor guruhlar. */
    public static function missing(string $year): array {
        $out = [];
        foreach (self::groups($year) as $g => $info) {
            if ($info['vakant'] > 0 && !$info['userid']) {
                $out[$g] = $info;
            }
        }
        return $out;
    }
}
