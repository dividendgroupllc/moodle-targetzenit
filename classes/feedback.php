<?php
// DIREKTOR FIDBEGI — darsni ko'rib o'qituvchiga qoldirilgan yozma izoh.
//
// TAMOYIL (DIREKTOR_PANELI.md 5.1, 6-band): o'qituvchi o'zi haqida HAMMA
// narsani ko'radi. Shuning uchun fidbek standart holda o'qituvchiga
// KO'RINADI va u bildirishnoma oladi. `private` — ma'muriyat ichidagi qayd,
// ataylab tanlanadigan istisno.
//
// OHANG: `kind` uch xil — tavsiya (standart), ogohlantirish, rahmat.
// Standart "tavsiya" ataylab: panelning maqsadi ayblash emas, rivojlantirish
// (AI so'rovidagi "ayblov so'zlarini ishlatma" qoidasi bilan bir xil ruh).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class feedback {

    /**
     * Fidbek ohangi. `javob` — o'qituvchining javobi (zanjirdagi javob
     * yozilganda avtomatik qo'yiladi, tanlanmaydi).
     */
    const KINDS = ['tavsiya', 'ogohlantirish', 'rahmat', 'javob'];

    /** Direktor tanlay oladigan ohanglar. */
    const KINDS_YOZISH = ['tavsiya', 'ogohlantirish', 'rahmat'];

    /** Kimga ko'rinadi. */
    const VIS = ['teacher', 'private'];

    /** Matn uzunligi chegarasi — izoh, maqola emas. */
    const MAX = 4000;

    /**
     * Fidbek qo'shadi va o'qituvchiga bildirishnoma yuboradi.
     *
     * @return int yangi yozuv id si (0 = matn bo'sh, saqlanmadi)
     */
    public static function add(int $lessonid, int $teacheruserid, string $body,
                                string $kind = 'tavsiya',
                                string $visibility = 'teacher',
                                int $parentid = 0): int {
        global $DB, $USER;

        $body = trim($body);
        if ($body === '' || !$lessonid || !$teacheruserid) {
            return 0;
        }

        // O'QITUVCHI O'ZI yozsa — bu JAVOB. Ohang va ko'rinish tanlanmaydi:
        // o'qituvchi o'ziga ogohlantirish yozmaydi va o'z javobini
        // o'zidan yashira olmaydi.
        $ozi = (int) $USER->id === $teacheruserid;
        if ($ozi) {
            $kind = 'javob';
            $visibility = 'teacher';
        } else {
            if (!in_array($kind, self::KINDS_YOZISH, true)) {
                $kind = 'tavsiya';
            }
            if (!in_array($visibility, self::VIS, true)) {
                $visibility = 'teacher';
            }
        }

        $now = time();
        $id = $DB->insert_record('local_targetzenit_feedback', (object) [
            'lessonid' => $lessonid,
            'parentid' => max(0, $parentid),
            'teacheruserid' => $teacheruserid,
            'authoruserid' => (int) $USER->id,
            'kind' => $kind,
            'visibility' => $visibility,
            'body' => \core_text::substr($body, 0, self::MAX),
            'status' => 'new',
            'seenat' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        // Faktdagi sanoq — diqqat navbati va kartalar uchun
        try {
            self::sanoq_yangila($lessonid);
        } catch (\Throwable $e) {
            debugging('local_targetzenit: fidbek sanog\'i yangilanmadi — '
                . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Bildirishnoma kimga: direktor yozsa — o'qituvchiga; o'qituvchi
        // javob yozsa — javob berilayotgan odamga (odatda direktorga).
        // Yiqilsa fidbek saqlanishi to'smasligi kerak: asosiy yozuv yuqorida.
        if ($visibility === 'teacher') {
            $oluvchi = $teacheruserid;
            if ($ozi) {
                $oluvchi = $parentid
                    ? (int) $DB->get_field('local_targetzenit_feedback', 'authoruserid',
                        ['id' => $parentid])
                    : (int) $DB->get_field_sql(
                        "SELECT authoruserid FROM {local_targetzenit_feedback}
                          WHERE lessonid = :l AND authoruserid <> :u
                       ORDER BY timecreated DESC",
                        ['l' => $lessonid, 'u' => (int) $USER->id], IGNORE_MULTIPLE);
            }
            if ($oluvchi && $oluvchi !== (int) $USER->id) {
                try {
                    self::xabar($id, $lessonid, $oluvchi, $body);
                } catch (\Throwable $e) {
                    debugging('local_targetzenit: fidbek bildirishnomasi yuborilmadi — '
                        . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
        return (int) $id;
    }

    /** Moodle'ning STANDART bildirishnoma tizimi orqali xabar. */
    protected static function xabar(int $id, int $lessonid, int $teacheruserid,
                                     string $body): void {
        global $DB, $USER;

        $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid],
            'id, courseid, lessondate, lessonno');
        if (!$dars) {
            return;
        }
        $kurs = $DB->get_field('course', 'fullname', ['id' => $dars->courseid]);
        $oluvchi = \core_user::get_user($teacheruserid);
        if (!$oluvchi) {
            return;
        }

        $sarlavha = get_string('fb_msgsubject', 'local_targetzenit', (object) [
            'kurs' => (string) $kurs,
            'sana' => userdate(strtotime($dars->lessondate), '%d.%m.%Y'),
        ]);
        $qisqa = \core_text::substr(trim($body), 0, 200);

        $xabar = new \core\message\message();
        $xabar->component = 'local_targetzenit';
        $xabar->name = 'feedback';
        $xabar->userfrom = $USER;
        $xabar->userto = $oluvchi;
        $xabar->subject = $sarlavha;
        $xabar->fullmessage = $qisqa;
        $xabar->fullmessageformat = FORMAT_PLAIN;
        $xabar->fullmessagehtml = \html_writer::tag('p', s($qisqa));
        $xabar->smallmessage = $qisqa;
        $xabar->notification = 1;
        $xabar->contexturl = (new \moodle_url('/local/targetzenit/dars.php', [
            'courseid' => $dars->courseid,
            'date' => $dars->lessondate,
            'lessonno' => $dars->lessonno,
        ]))->out(false);
        $xabar->contexturlname = $sarlavha;

        message_send($xabar);
    }

    /**
     * Darsning fidbeklari.
     *
     * @param bool $maxfiyham `private` qaydlar ham qaytarilsinmi
     */
    public static function for_lesson(int $lessonid, bool $maxfiyham = false): array {
        global $DB;

        $shart = 'f.lessonid = :l';
        $params = ['l' => $lessonid];
        if (!$maxfiyham) {
            $shart .= ' AND f.visibility = :v';
            $params['v'] = 'teacher';
        }

        // Suhbat tartibi: ESKIDAN YANGIGA — javob o'zidan oldingi xabardan
        // keyin turadi, aks holda muloqot teskari o'qiladi.
        return $DB->get_records_sql("
            SELECT f.*, u.firstname, u.lastname
              FROM {local_targetzenit_feedback} f
         LEFT JOIN {user} u ON u.id = f.authoruserid
             WHERE $shart
          ORDER BY f.timecreated ASC", $params);
    }

    /**
     * Darslarning OXIRGI xabari va jami soni — jadval qatoridagi qisqa
     * ko'rinish uchun.
     *
     * @return array [lessonid => ['oxirgi' => record, 'soni' => int]]
     */
    public static function latest(array $lessonids, bool $maxfiyham = false): array {
        global $DB;

        if (!$lessonids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
        $shart = "f.lessonid $in";
        if (!$maxfiyham) {
            $shart .= ' AND f.visibility = :v';
            $params['v'] = 'teacher';
        }

        // Hammasini olib PHP'da yig'amiz: sahifada 20 dars, har birida bir
        // nechta xabar — bu SQL'da oyna funksiyasi yozishdan sodda va
        // ikki baza (MySQL/Postgres) uchun ham bir xil ishlaydi.
        $rows = $DB->get_records_sql("
            SELECT f.id, f.lessonid, f.kind, f.body, f.timecreated, f.status,
                   f.visibility, f.authoruserid, u.firstname, u.lastname
              FROM {local_targetzenit_feedback} f
         LEFT JOIN {user} u ON u.id = f.authoruserid
             WHERE $shart
          ORDER BY f.timecreated ASC", $params);

        $out = [];
        foreach ($rows as $r) {
            $l = (int) $r->lessonid;
            if (!isset($out[$l])) {
                $out[$l] = ['oxirgi' => null, 'soni' => 0];
            }
            $out[$l]['oxirgi'] = $r;      // tartib ASC — oxirgisi yuqoriga yoziladi
            $out[$l]['soni']++;
        }
        return $out;
    }

    /** Darslar bo'yicha fidbek soni: [lessonid => soni] */
    public static function counts(array $lessonids, bool $maxfiyham = false): array {
        global $DB;

        if (!$lessonids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
        $shart = "lessonid $in";
        if (!$maxfiyham) {
            $shart .= ' AND visibility = :v';
            $params['v'] = 'teacher';
        }
        $rows = $DB->get_records_sql("
            SELECT lessonid, COUNT(1) AS soni
              FROM {local_targetzenit_feedback}
             WHERE $shart GROUP BY lessonid", $params);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->lessonid] = (int) $r->soni;
        }
        return $out;
    }

    /**
     * Shu odam shu darsga JAVOB yozishi mumkinmi.
     *
     * NEGA KERAK: formani yashirish yetarli emas — `fbteacher` ni o'z id siga
     * qo'yib, `fblesson` ga BOSHQA odamning darsini yozib yuborish mumkin.
     * `add()` esa "o'zi yozdi" deb qabul qilardi va begona darsda fidbek
     * qatori paydo bo'lardi (`_fact.feedbackcount` ham o'sha darsda o'sardi).
     *
     * Shart: dars shu odamga YOZILGAN (fakt yoki jurnal bo'yicha), yoki unga
     * murojaat qilingan fidbek allaqachon bor — javob aynan shunga beriladi.
     */
    public static function javob_mumkinmi(int $lessonid, int $userid): bool {
        global $DB;

        if (!$lessonid || !$userid) {
            return false;
        }
        if ($DB->record_exists('local_targetzenit_lesson',
                ['id' => $lessonid, 'teacherid' => $userid])) {
            return true;
        }
        if ($DB->record_exists('local_targetzenit_fact',
                ['lessonid' => $lessonid, 'attributeduserid' => $userid])) {
            return true;
        }
        return $DB->record_exists('local_targetzenit_feedback',
            ['lessonid' => $lessonid, 'teacheruserid' => $userid, 'visibility' => 'teacher']);
    }

    /** O'qituvchi ko'rgan deb belgilash (o'z fidbegini ochganda). */
    public static function korildi(int $lessonid, int $teacheruserid): void {
        global $DB;
        $DB->execute("UPDATE {local_targetzenit_feedback}
                         SET status = :yangi, seenat = :vaqt, timemodified = :vaqt2
                       WHERE lessonid = :l AND teacheruserid = :u
                         AND visibility = :v AND status = :eski",
            ['yangi' => 'seen', 'vaqt' => time(), 'vaqt2' => time(),
             'l' => $lessonid, 'u' => $teacheruserid,
             'v' => 'teacher', 'eski' => 'new']);
    }

    /** `_fact.feedbackcount` ni haqiqiy songa tenglashtiradi. */
    public static function sanoq_yangila(int $lessonid): void {
        global $DB;
        $soni = $DB->count_records('local_targetzenit_feedback', ['lessonid' => $lessonid]);
        $DB->set_field('local_targetzenit_fact', 'feedbackcount', $soni,
            ['lessonid' => $lessonid]);
    }
}
