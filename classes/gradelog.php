<?php
// BAHO O'ZGARISHLARI TARIXI — e-kundalikning «O'zgarishlar tarixi» talabi.
//
// QOIDA (e-kundalik): kiritilgan bahoni **72 soat ichida** o'zgartirish
// mumkin; o'zgarishlar tarixda kuzatiladi. 72 soatdan keyin tuzatish uchun
// tuman/shahar ta'lim bo'limiga rasmiy xat kerak — bizda uning o'rnini
// KOORDINATOR/DIREKTOR TASDIG'I egallaydi.
//
// NEGA ALOHIDA SINF: tarix ikki xil bahoga tegishli — kundalik (FB,
// `_record`) va summativ (`_exam_result`). Ikkisi ikki joyda yozilsa qoida
// bir-biridan ajralib ketadi va «72 soat» bir yerda 72, boshqasida 48
// bo'lib qoladi.
//
// YOZUV O'CHIRILMAYDI: bu dalil. `yozuvlar.php` dagi «kuzatuvchi o'zi baho
// qo'yadigan dalilni yo'q qila olmaydi» qoidasi bilan bir xil.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class gradelog {

    /** Tuzatish erkin bo'lgan oyna — e-kundalik qoidasi. */
    const OYNA_SOAT = 72;

    /** Baho turi. */
    const TURLAR = ['fb', 'summativ'];

    /**
     * O'zgarishni qayd qiladi.
     *
     * DIQQAT: qiymat O'ZGARMAGAN bo'lsa hech narsa yozilmaydi — aks holda
     * jurnalni har saqlashda (jurnalda bir saqlashda yuzlab katak)
     * ma'nosiz qatorlar to'planardi.
     *
     * @param string $turi `fb` | `summativ`
     * @param int $recordid `_record.id` yoki `_exam_result.id`
     * @param string|null $old eski qiymat (null = birinchi kiritish)
     * @param string|null $new yangi qiymat
     * @param int $firstentry birinchi kiritilgan vaqt (0 = bilinmaydi)
     * @return int yozuv id si (0 = yozilmadi)
     */
    public static function qayd(string $turi, int $recordid, int $studentid,
                                 int $courseid, $old, $new,
                                 string $reason = '', int $firstentry = 0,
                                 int $approvedby = 0): int {
        global $DB, $USER;

        if (!in_array($turi, self::TURLAR, true)) {
            $turi = 'fb';
        }
        $old = self::matn($old);
        $new = self::matn($new);
        if ($old === $new) {
            return 0;
        }

        $now = time();
        $kech = ($firstentry > 0) ? (int) floor(($now - $firstentry) / 3600) : 0;

        return (int) $DB->insert_record('local_targetzenit_grade_log', (object) [
            'turi' => $turi,
            'recordid' => $recordid,
            'studentid' => $studentid,
            'courseid' => $courseid,
            'oldvalue' => $old,
            'newvalue' => $new,
            'userid' => (int) $USER->id,
            'reason' => $reason !== '' ? $reason : null,
            'approvedby' => $approvedby,
            'latehours' => max(0, $kech),
            'timecreated' => $now,
        ]);
    }

    /** Qiymatni taqqoslanadigan matnga keltiradi (null va '' bir xil emas). */
    protected static function matn($v): ?string {
        if ($v === null || $v === '') {
            return null;
        }
        return \core_text::substr((string) $v, 0, 64);
    }

    /**
     * 72 soatlik oyna o'tganmi.
     *
     * @param int $firstentry birinchi kiritilgan vaqt (0 = hali kiritilmagan)
     */
    public static function oyna_otdimi(int $firstentry): bool {
        if ($firstentry <= 0) {
            return false;            // birinchi kiritish — oyna boshlanmagan
        }
        return (time() - $firstentry) > self::OYNA_SOAT * 3600;
    }

    /**
     * Shu bahoni hozir o'zgartirish mumkinmi, va mumkin bo'lmasa — nega.
     *
     * Oyna o'tgan bo'lsa tuzatish FAQAT `manageexams` ruxsati borga
     * (koordinator/direktor) va MAJBURIY izoh bilan ochiq. Nizomdagi
     * «rasmiy xat» o'rnini shu egallaydi.
     *
     * @return array ['mumkin' => bool, 'tasdiqkerak' => bool, 'sabab' => string]
     */
    public static function tekshir(int $firstentry): array {
        if (!self::oyna_otdimi($firstentry)) {
            return ['mumkin' => true, 'tasdiqkerak' => false, 'sabab' => ''];
        }
        $bor = has_capability('local/targetzenit:manageexams',
            \context_system::instance());

        return [
            'mumkin' => $bor,
            'tasdiqkerak' => true,
            'sabab' => get_string('bh_windowclosed', 'local_targetzenit',
                self::OYNA_SOAT),
        ];
    }

    /**
     * Bitta bahoning tarixi — yangidan eskiga.
     *
     * @param string $turi `fb` | `summativ`
     */
    public static function tarix(string $turi, int $recordid): array {
        global $DB;

        return $DB->get_records_sql("
            SELECT g.*, u.firstname, u.lastname, u.idnumber,
                   a.firstname AS afirstname, a.lastname AS alastname
              FROM {local_targetzenit_grade_log} g
         LEFT JOIN {user} u ON u.id = g.userid
         LEFT JOIN {user} a ON a.id = g.approvedby
             WHERE g.turi = :t AND g.recordid = :r
          ORDER BY g.timecreated DESC, g.id DESC",
            ['t' => $turi, 'r' => $recordid]);
    }

    /** O'quvchining hamma tuzatishlari — ma'muriy tekshiruv uchun. */
    public static function student_tarixi(int $studentid, int $limit = 100): array {
        global $DB;

        return $DB->get_records_sql("
            SELECT g.*, u.firstname, u.lastname
              FROM {local_targetzenit_grade_log} g
         LEFT JOIN {user} u ON u.id = g.userid
             WHERE g.studentid = :s
          ORDER BY g.timecreated DESC", ['s' => $studentid], 0, $limit);
    }

    /**
     * KECH tuzatishlar ro'yxati — direktor uchun diqqat navbati.
     * 72 soatdan keyin o'zgartirilgan baholar.
     */
    public static function kech_tuzatishlar(string $from = '', string $to = '',
                                             int $limit = 300): array {
        global $DB;

        $shart = ['g.latehours > :oyna'];
        $params = ['oyna' => self::OYNA_SOAT];
        if ($from !== '') {
            $shart[] = 'g.timecreated >= :from';
            $params['from'] = strtotime($from . ' 00:00:00');
        }
        if ($to !== '') {
            $shart[] = 'g.timecreated <= :to';
            $params['to'] = strtotime($to . ' 23:59:59');
        }

        return $DB->get_records_sql("
            SELECT g.*, u.firstname, u.lastname,
                   s.firstname AS sfirstname, s.lastname AS slastname,
                   c.fullname AS coursename, c.idnumber AS courseidnumber
              FROM {local_targetzenit_grade_log} g
         LEFT JOIN {user} u ON u.id = g.userid
         LEFT JOIN {user} s ON s.id = g.studentid
         LEFT JOIN {course} c ON c.id = g.courseid
             WHERE " . implode(' AND ', $shart) . "
          ORDER BY g.timecreated DESC", $params, 0, $limit);
    }
}
