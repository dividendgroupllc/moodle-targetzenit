<?php
// E'LONLAR — o'qituvchi o'z guruhiga, rahbariyat butun maktabga; o'quvchi
// (ota-ona farzand akkaunti bilan) ko'radi, o'qilganlik kuzatiladi, push ketadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class elon {

    const TURLAR = ['oddiy', 'muhim', 'majlis'];

    /** Yangi e'lon yaratadi va o'quvchilarga xabarnoma yuboradi. */
    public static function yarat(\stdClass $d): int {
        global $DB, $USER;

        $rec = (object) [
            'muallifuserid' => (int) $USER->id,
            'scope' => in_array($d->scope ?? '', ['maktab', 'guruh'], true) ? $d->scope : 'guruh',
            'courseid' => (int) ($d->courseid ?? 0),
            'turi' => in_array($d->turi ?? '', self::TURLAR, true) ? $d->turi : 'oddiy',
            'sarlavha' => \core_text::substr(trim((string) ($d->sarlavha ?? '')), 0, 255),
            'matn' => \core_text::substr(trim((string) ($d->matn ?? '')), 0, 8000),
            'sana' => !empty($d->sana) ? $d->sana : null,
            'vaqt' => !empty($d->vaqt) ? $d->vaqt : null,
            'xona' => !empty($d->xona) ? \core_text::substr(trim((string) $d->xona), 0, 64) : null,
            'holat' => 'chiqdi',
            'timecreated' => time(),
        ];
        if ($rec->scope === 'maktab') {
            $rec->courseid = 0;
        }
        $rec->id = $DB->insert_record('local_targetzenit_elon', $rec);

        try {
            self::xabar_yubor($rec);
        } catch (\Throwable $e) {
            debugging('elon xabar: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return (int) $rec->id;
    }

    /** E'lon auditoriyasidagi o'quvchilar (userid => user). */
    public static function auditoriya(\stdClass $elon): array {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'student'], 'id');
        if (!$role) {
            return [];
        }
        if ((string) $elon->scope === 'guruh' && (int) $elon->courseid > 0) {
            $ctx = \context_course::instance((int) $elon->courseid, IGNORE_MISSING);
            if (!$ctx) {
                return [];
            }
            return get_role_users($role->id, $ctx, false, 'u.id, u.firstname, u.lastname');
        }
        // Maktab: joriy yildagi barcha kurslarning o'quvchilari (unikal)
        $year = timetable::current_year();
        $cids = $DB->get_fieldset_select('course', 'id',
            $DB->sql_like('idnumber', ':p'), ['p' => $year . '::%']);
        $out = [];
        foreach ($cids as $cid) {
            $ctx = \context_course::instance((int) $cid, IGNORE_MISSING);
            if (!$ctx) {
                continue;
            }
            foreach (get_role_users($role->id, $ctx, false, 'u.id, u.firstname, u.lastname') as $u) {
                $out[(int) $u->id] = $u;
            }
        }
        return $out;
    }

    /** Auditoriyadagi har o'quvchiga Moodle xabarnomasi (push + veb). */
    protected static function xabar_yubor(\stdClass $elon): void {
        global $USER;

        $auditoriya = self::auditoriya($elon);
        if (!$auditoriya) {
            return;
        }
        $sarlavha = ($elon->turi === 'majlis'
            ? '📅 ' : ($elon->turi === 'muhim' ? '❗ ' : '📢 '))
            . format_string($elon->sarlavha);
        $url = new \moodle_url('/local/targetzenit/elonlarim.php');

        foreach ($auditoriya as $u) {
            $msg = new \core\message\message();
            $msg->component = 'local_targetzenit';
            $msg->name = 'elon';
            $msg->userfrom = $USER;
            $msg->userto = (int) $u->id;
            $msg->subject = $sarlavha;
            $msg->fullmessage = format_string($elon->sarlavha) . "\n\n"
                . (string) $elon->matn;
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = \html_writer::tag('b', s($elon->sarlavha))
                . \html_writer::tag('p', nl2br(s((string) $elon->matn)));
            $msg->smallmessage = $sarlavha;
            $msg->notification = 1;
            $msg->contexturl = $url;
            $msg->contexturlname = get_string('elon_nav', 'local_targetzenit');
            message_send($msg);
        }
    }

    /** O'quvchiga ko'rinadigan e'lonlar (guruhlari + maktab), yangisi yuqorida. */
    public static function for_student(int $userid): array {
        global $DB;

        $cids = $DB->get_fieldset_sql("
            SELECT DISTINCT c.id
              FROM {course} c
              JOIN {enrol} e ON e.courseid = c.id
              JOIN {user_enrolments} ue ON ue.enrolid = e.id
             WHERE ue.userid = :uid AND ue.status = 0", ['uid' => $userid]);

        $params = ['holat' => 'chiqdi'];
        $shart = "holat = :holat AND (scope = 'maktab'";
        if ($cids) {
            [$in, $inparams] = $DB->get_in_or_equal($cids, SQL_PARAMS_NAMED, 'c');
            $shart .= " OR (scope = 'guruh' AND courseid $in)";
            $params += $inparams;
        }
        $shart .= ')';
        return $DB->get_records_select('local_targetzenit_elon', $shart, $params,
            'timecreated DESC');
    }

    /** O'qituvchi/rahbariyat yozgan e'lonlar (muallif bo'yicha). */
    public static function mine(int $userid): array {
        global $DB;
        return $DB->get_records('local_targetzenit_elon',
            ['muallifuserid' => $userid], 'timecreated DESC');
    }

    /** O'qilgan deb belgilaydi (bir marta yoziladi). */
    public static function korildi(int $elonid, int $userid): void {
        global $DB;
        if (!$DB->record_exists('local_targetzenit_elon_seen',
                ['elonid' => $elonid, 'userid' => $userid])) {
            $DB->insert_record('local_targetzenit_elon_seen', (object) [
                'elonid' => $elonid,
                'userid' => $userid,
                'seenat' => time(),
            ]);
        }
    }

    /** Auditoriya soni (nechta o'quvchiga mo'ljallangan). */
    public static function auditoriya_soni(\stdClass $elon): int {
        return count(self::auditoriya($elon));
    }

    /** O'qilganlik statistikasi: [elonid => seen]. */
    public static function seen_stats(array $elonids): array {
        global $DB;
        if (!$elonids) {
            return [];
        }
        [$in, $params] = $DB->get_in_or_equal($elonids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_sql("
            SELECT elonid, COUNT(1) AS seen
              FROM {local_targetzenit_elon_seen}
             WHERE elonid $in GROUP BY elonid", $params);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->elonid] = (int) $r->seen;
        }
        return $out;
    }

    /** E'lonni bekor qiladi (muallif yoki rahbariyat). */
    public static function bekor(int $elonid): void {
        global $DB;
        $DB->set_field('local_targetzenit_elon', 'holat', 'bekor', ['id' => $elonid]);
    }
}
