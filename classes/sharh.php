<?php
// O'QUVCHI SHARHI — o'qituvchining o'quvchi haqidagi yozma izohi.
//
// NIMA UCHUN KERAK: `_record.comment` bitta DARSGA bog'langan bir og'iz izoh,
// chorak yoki umumiy xarakteristika uchun joy yo'q edi. Ota-ona «bolam qanday
// o'qiyapti?» deb so'raganda baho va davomatdan boshqa hech narsa yo'q.
//
// MATN BU JADVALDA SAQLANMAYDI — u Moodle'ning STANDART `{comments}`
// jadvalida yotadi:
//   component   = local_targetzenit
//   commentarea = studentreview
//   itemid      = o'quvchining user.id  (kim haqida)
//   contextid   = KURS konteksti        (qaysi guruh + fan)
//   userid      = kim yozdi
// `local_targetzenit_sharh` esa faqat HOLAT (o'quvchi ko'rdimi, maxfiymi) va
// indeksli filtr uchun. Matnni ikki joyda saqlasak ular bir-biridan ajralib
// ketadi, shuning uchun yagona manba — `{comments}`.
//
// NEGA MOODLE COMMENTI: plaginning umumiy tamoyili «avval Moodle'ning o'z
// vositasi». Comment quyi tizimi tayyor holda ruxsat tekshiruvi, hodisa
// (event), tozalash va o'chirish berib turadi; biz faqat `lib.php` dagi
// `comment_validate` / `comment_permissions` chaqiruvlarini yozamiz.
//
// QAMROV (qaror 2026-11-06): sharh KURSGA bog'lanadi, ya'ni «guruh + fan».
// O'qituvchining kursi allaqachon shunday (`<yil>::<guruh>::<fan>`), shuning
// uchun guruh filtri alohida maydonsiz kelib chiqadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class sharh {

    /** Moodle comment maydoni nomi. O'zgartirilsa eski sharhlar «yo'qoladi». */
    const AREA = 'studentreview';

    /** Matn uzunligi chegarasi — xarakteristika, insho emas. */
    const MAX = 4000;

    /**
     * Kimga ko'rinadi:
     *   `ochiq`  — o'quvchi ko'radi va bildirishnoma oladi (standart);
     *   `maxfiy` — o'quvchiga KO'RINMAYDI va bildirishnoma yuborilmaydi;
     *               shu kursda dars o'tuvchilar va direktor/zavuch ko'radi.
     */
    const VIS = ['ochiq', 'maxfiy'];

    /** Qatorda ko'rsatiladigan qisqa matn uzunligi. */
    const QISQA = 110;

    /**
     * BESH KATEGORIYA (foydalanuvchi talabi 2026-11-10).
     *
     * Har o'qituvchi har o'quvchiga shu beshtasi bo'yicha yozadi. Tartib
     * MUHIM: forma ham, ko'rinish ham shu ketma-ketlikda chiqadi, ya'ni
     * o'qituvchi har o'quvchida bir xil tartibni ko'radi va o'rganib qoladi.
     *
     * Kalitlar O'ZGARMAYDI — ular bazada yozilgan. Yorliqlar til
     * satrlarida (`sh_kat_*`).
     */
    const KATEGORIYALAR = [
        'akademik',     // Academic Performance — akademik bilim
        'faollik',      // Classroom Participation — darsdagi faollik
        'ijtimoiy',     // Social and Emotional Skills
        'masuliyat',    // Responsibility and Independence
        'tanqidiy',     // Critical Thinking and Creativity
    ];

    /**
     * 2026-11-10 dan OLDIN yozilgan erkin sharhlarning kategoriyasi.
     *
     * Ular o'chirilmaydi va ko'chirilmaydi — «Umumiy» yorlig'i ostida
     * ko'rinishda qoladi. Eski ma'lumotni qayta talqin qilish xato bo'lardi:
     * o'qituvchi o'sha matnni qaysi kategoriya deb yozganini bilmaymiz.
     */
    const ESKI_KAT = 'umumiy';

    // ===================================================== COMMENT QUYI TIZIMI

    /**
     * Shu (kurs, o'quvchi) juftligi uchun Moodle comment menejeri.
     *
     * DIQQAT: konstruktorning o'zi `check_permissions()` ni chaqiradi, ya'ni
     * `lib.php` dagi `local_targetzenit_comment_permissions()` shu yerda ishga
     * tushadi. Callback yozilmasa `plugin_callback` standart
     * `['post' => false, 'view' => false]` qaytaradi va hech kim yoza olmaydi.
     */
    protected static function mgr(int $courseid, int $studentid): \core_comment\manager {
        return new \core_comment\manager((object) [
            'context' => \context_course::instance($courseid),
            'component' => 'local_targetzenit',
            'area' => self::AREA,
            'itemid' => $studentid,
            'courseid' => $courseid,
        ]);
    }

    // ============================================================== YOZISH

    /**
     * Sharh qo'shadi va (ochiq bo'lsa) o'quvchiga bildirishnoma yuboradi.
     *
     * @return int yangi holat qatorining id si (0 = matn bo'sh, saqlanmadi)
     */
    public static function add(int $courseid, int $studentid, string $body,
                                string $visibility = 'ochiq',
                                string $kategoriya = self::ESKI_KAT,
                                ?string $batch = null): int {
        global $DB, $USER;

        $body = trim($body);
        if ($body === '' || !$courseid || !$studentid) {
            return 0;
        }
        if (!in_array($visibility, self::VIS, true)) {
            $visibility = 'ochiq';
        }
        if ($kategoriya !== self::ESKI_KAT
                && !in_array($kategoriya, self::KATEGORIYALAR, true)) {
            $kategoriya = self::ESKI_KAT;
        }

        $context = \context_course::instance($courseid);

        // IKKI TEKSHIRUV, IKKI XIL SAVOL. Birinchisi: shu odam shu kursda
        // dars o'tadimi. Ikkinchisi: sharh yozilayotgan odam shu kursning
        // O'QUVCHISIMI — aks holda `studentid` ni qo'lda o'zgartirib butun
        // maktabdagi istalgan odam haqida yozib ketish mumkin bo'lardi
        // (formani yashirish himoya emas).
        require_capability('local/targetzenit:teach', $context);
        if (!self::oquvchimi($courseid, $studentid)) {
            throw new \moodle_exception('nopermissions', 'error', '',
                get_string('sh_notstudent', 'local_targetzenit'));
        }

        // Matn Moodle'ning comment quyi tizimiga yoziladi. `FORMAT_PLAIN`:
        // o'qituvchi HTML yozmaydi va sharh har joyda bir xil ko'rinishi kerak.
        $cmt = self::mgr($courseid, $studentid)
            ->add(\core_text::substr($body, 0, self::MAX), FORMAT_PLAIN);

        $id = (int) $DB->insert_record('local_targetzenit_sharh', (object) [
            'commentid' => (int) $cmt->id,
            'courseid' => $courseid,
            'studentid' => $studentid,
            'authorid' => (int) $USER->id,
            'kategoriya' => $kategoriya,
            'batch' => $batch,
            'visibility' => $visibility,
            'status' => 'new',
            'seenat' => 0,
            'timecreated' => time(),
        ]);

        // BILDIRISHNOMA BU YERDA YUBORILMAYDI.
        //
        // Besh kategoriya = besh `add()` chaqiruvi, ya'ni o'quvchi bitta
        // sharh uchun BESH xabar olardi. Xabar `add_batch()` da, to'plam
        // tugagach BIR MARTA yuboriladi.
        return $id;
    }

    /**
     * BESH KATEGORIYA BO'YICHA sharhni bir to'plam qilib saqlaydi.
     *
     * Bu modulning ASOSIY yozish yo'li. `add()` esa bitta kategoriyani
     * yozadi va faqat shu metod (yoki eski ma'lumot) uchun ishlatiladi.
     *
     * @param array $matnlar [kategoriya => matn]
     * @return array ['batch' => string, 'yozildi' => int]
     */
    public static function add_batch(int $courseid, int $studentid,
                                      array $matnlar,
                                      string $visibility = 'ochiq'): array {
        $toza = [];
        foreach (self::KATEGORIYALAR as $kat) {
            $toza[$kat] = trim((string) ($matnlar[$kat] ?? ''));
        }

        // HAMMA BESHTASI TO'LDIRILISHI SHART (foydalanuvchi talabi:
        // «har birini yoniga yozishi kerak»).
        //
        // Nega qattiq tekshiruv: sharhning maqsadi — o'quvchining to'liq
        // manzarasi. Bittasi bo'sh qolsa hisobot «akademik bilimi bor,
        // ijtimoiy ko'nikmasi yo'q» degan noto'g'ri xulosaga olib keladi,
        // aslida esa shunchaki yozilmagan. Bo'sh qoldirishga ruxsat bersak,
        // amalda faqat birinchi maydon to'ldiriladi.
        $bosh = [];
        foreach ($toza as $kat => $matn) {
            if ($matn === '') {
                $bosh[] = get_string('sh_kat_' . $kat, 'local_targetzenit');
            }
        }
        if ($bosh) {
            throw new \moodle_exception('sh_allrequired', 'local_targetzenit', '',
                implode(', ', $bosh));
        }

        // To'plam kaliti — besh qator shuni bo'lishadi va ko'rinishda bitta
        // sharh bo'lib yig'iladi.
        $batch = \core\uuid::generate();
        $batch = substr(str_replace('-', '', $batch), 0, 32);

        $yozildi = 0;
        foreach ($toza as $kat => $matn) {
            if (self::add($courseid, $studentid, $matn, $visibility, $kat, $batch)) {
                $yozildi++;
            }
        }

        // Bildirishnoma — TO'PLAM uchun BITTA. Birinchi kategoriya matni
        // qisqa ko'rinish sifatida ketadi.
        if ($yozildi && $visibility === 'ochiq') {
            try {
                self::xabar($courseid, $studentid,
                    $toza[self::KATEGORIYALAR[0]]);
            } catch (\Throwable $e) {
                debugging('local_targetzenit: sharh bildirishnomasi yuborilmadi — '
                    . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return ['batch' => $batch, 'yozildi' => $yozildi];
    }

    /**
     * Qatorlarni TO'PLAMLARGA guruhlaydi — bitta saqlash = bitta sharh.
     *
     * `batch` NULL bo'lgan eski erkin sharh o'zi alohida to'plam bo'ladi
     * (kaliti `old<id>`), ya'ni eski ma'lumot ham bir xil yo'l bilan
     * chiziladi va chizuvchida ikkinchi shoxcha kerak bo'lmaydi.
     *
     * @param array $rows `select()` qatorlari (tartibi saqlanadi)
     * @return array har biri ['bosh' => record, 'matnlar' => [kat => record]]
     */
    public static function guruhla(array $rows): array {
        $out = [];
        foreach ($rows as $r) {
            $kalit = ($r->batch !== null && $r->batch !== '')
                ? 'b' . $r->batch
                : 'old' . $r->id;
            if (!isset($out[$kalit])) {
                $out[$kalit] = ['bosh' => $r, 'matnlar' => []];
            }
            $out[$kalit]['matnlar'][$r->kategoriya] = $r;
        }
        return $out;
    }

    /** Kategoriya yorliqlari: [kalit => nomi]. Tartib `KATEGORIYALAR` dagidek. */
    public static function kategoriyalar_menu(): array {
        $out = [];
        foreach (self::KATEGORIYALAR as $kat) {
            $out[$kat] = get_string('sh_kat_' . $kat, 'local_targetzenit');
        }
        return $out;
    }

    /** Shu odam shu kursning o'quvchisimi. */
    public static function oquvchimi(int $courseid, int $studentid): bool {
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }
        return is_enrolled($context, $studentid);
    }

    /**
     * Moodle'ning STANDART bildirishnoma tizimi orqali xabar.
     *
     * O'quvchining akkauntiga boradi — qo'ng'iroqcha, pochta va mobil ilova.
     * Ota-ona bolasining logini bilan kiradi (Moodle'da ota-ona akkaunti
     * yo'q), ya'ni «o'quvchilar paneliga notification» aynan shu yo'l.
     */
    protected static function xabar(int $courseid, int $studentid, string $body): void {
        global $DB, $USER;

        $oluvchi = \core_user::get_user($studentid);
        if (!$oluvchi || !empty($oluvchi->deleted) || !empty($oluvchi->suspended)) {
            return;
        }
        $kurs = (string) $DB->get_field('course', 'fullname', ['id' => $courseid]);
        $sarlavha = get_string('sh_msgsubject', 'local_targetzenit', $kurs);
        $qisqa = \core_text::substr(trim($body), 0, 200);

        $xabar = new \core\message\message();
        $xabar->component = 'local_targetzenit';
        $xabar->name = 'sharh';
        $xabar->userfrom = $USER;
        $xabar->userto = $oluvchi;
        $xabar->subject = $sarlavha;
        $xabar->fullmessage = $qisqa;
        $xabar->fullmessageformat = FORMAT_PLAIN;
        $xabar->fullmessagehtml = \html_writer::tag('p', s($qisqa));
        $xabar->smallmessage = $qisqa;
        $xabar->notification = 1;
        $xabar->contexturl = (new \moodle_url('/local/targetzenit/sharhlarim.php'))->out(false);
        $xabar->contexturlname = get_string('sh_mine', 'local_targetzenit');

        message_send($xabar);
    }

    // =============================================================== O'QISH

    /**
     * Umumiy SELECT. `{comments}` ga INNER JOIN — comment core vositasi bilan
     * o'chirilsa qator ro'yxatdan ham chiqib ketadi (shunday bo'lishi kerak:
     * matn yagona manbada).
     */
    protected static function select(): string {
        return "SELECT s.id, s.commentid, s.courseid, s.studentid, s.authorid,
                       s.kategoriya, s.batch,
                       s.visibility, s.status, s.seenat, s.timecreated,
                       c.content, c.format,
                       a.firstname AS afirstname, a.lastname AS alastname,
                       st.firstname AS sfirstname, st.lastname AS slastname,
                       st.idnumber AS sidnumber,
                       co.fullname AS coursename, co.idnumber AS courseidnumber
                  FROM {local_targetzenit_sharh} s
                  JOIN {comments} c ON c.id = s.commentid
             LEFT JOIN {user} a ON a.id = s.authorid
             LEFT JOIN {user} st ON st.id = s.studentid
             LEFT JOIN {course} co ON co.id = s.courseid";
    }

    /** `maxfiy` qatorlarni chiqarib tashlash sharti. */
    protected static function vis_shart(bool $maxfiyham, array &$params): string {
        if ($maxfiyham) {
            return '';
        }
        $params['vis'] = 'ochiq';
        return ' AND s.visibility = :vis';
    }

    /**
     * Bitta o'quvchining shu kursdagi sharhlari — ESKIDAN YANGIGA.
     *
     * Tartib ataylab o'sib boradi: xarakteristika vaqt bo'yicha o'qiladi
     * («sentabrda shunday edi, dekabrda bunday bo'ldi»).
     */
    public static function for_student(int $courseid, int $studentid,
                                        bool $maxfiyham = false): array {
        global $DB;

        $params = ['c' => $courseid, 's' => $studentid];
        $shart = 's.courseid = :c AND s.studentid = :s' . self::vis_shart($maxfiyham, $params);

        return $DB->get_records_sql(self::select()
            . " WHERE $shart ORDER BY s.timecreated ASC, s.id ASC", $params);
    }

    /**
     * Kursdagi o'quvchilarning OXIRGI sharhi va jami soni — jadval qatori uchun.
     *
     * Hammasini olib PHP'da yig'amiz: bitta kursda 20-30 o'quvchi, har birida
     * bir nechta sharh. Bu SQL oyna funksiyasidan sodda va ikki bazada
     * (MySQL/Postgres) bir xil ishlaydi — `feedback::latest()` bilan bir naqsh.
     *
     * @return array [studentid => ['oxirgi' => record, 'soni' => int]]
     */
    public static function latest(int $courseid, bool $maxfiyham = false): array {
        global $DB;

        $params = ['c' => $courseid];
        $shart = 's.courseid = :c' . self::vis_shart($maxfiyham, $params);

        $rows = $DB->get_records_sql(self::select()
            . " WHERE $shart ORDER BY s.timecreated ASC, s.id ASC", $params);

        // SONI = TO'PLAMLAR soni, qatorlar soni EMAS.
        //
        // Besh kategoriya = besh qator, ya'ni oddiy sanoq bitta sharhni
        // «5 sharh» qilib ko'rsatardi. O'qituvchi «men bir marta yozdim,
        // nega beshta?» deb o'ylardi.
        $out = [];
        $tplar = [];
        foreach ($rows as $r) {
            $k = (int) $r->studentid;
            if (!isset($out[$k])) {
                $out[$k] = ['oxirgi' => null, 'soni' => 0];
                $tplar[$k] = [];
            }
            $out[$k]['oxirgi'] = $r;      // tartib ASC — oxirgisi ustiga yoziladi

            $tp = ($r->batch !== null && $r->batch !== '')
                ? 'b' . $r->batch : 'old' . $r->id;
            $tplar[$k][$tp] = true;
        }
        foreach ($out as $k => $v) {
            $out[$k]['soni'] = count($tplar[$k]);
        }
        return $out;
    }

    /**
     * O'quvchining O'ZI ko'radigan sharhlari — hamma fan bo'ylab, yangidan eskiga.
     * `maxfiy` qaydlar hech qachon qaytarilmaydi.
     */
    public static function mine(int $studentid, int $limit = 200): array {
        global $DB;

        return $DB->get_records_sql(self::select()
            . " WHERE s.studentid = :s AND s.visibility = :vis
             ORDER BY s.timecreated DESC, s.id DESC",
            ['s' => $studentid, 'vis' => 'ochiq'], 0, $limit);
    }

    /** O'quvchining o'qilmagan sharhlari soni. */
    public static function unseen(int $studentid): int {
        global $DB;
        return (int) $DB->count_records_sql("
            SELECT COUNT(1)
              FROM {local_targetzenit_sharh} s
              JOIN {comments} c ON c.id = s.commentid
             WHERE s.studentid = :s AND s.visibility = :vis AND s.status = :st",
            ['s' => $studentid, 'vis' => 'ochiq', 'st' => 'new']);
    }

    /** Shu o'quvchida umuman sharh bormi — menyu havolasi uchun. */
    public static function has_any(int $studentid): bool {
        global $DB;
        return $DB->record_exists('local_targetzenit_sharh',
            ['studentid' => $studentid, 'visibility' => 'ochiq']);
    }

    /** O'quvchi o'z sahifasini ochdi — ochiq sharhlar «ko'rildi» bo'ladi. */
    public static function korildi(int $studentid): void {
        global $DB;
        $DB->execute("UPDATE {local_targetzenit_sharh}
                         SET status = :yangi, seenat = :vaqt
                       WHERE studentid = :s AND visibility = :vis AND status = :eski",
            ['yangi' => 'seen', 'vaqt' => time(), 's' => $studentid,
             'vis' => 'ochiq', 'eski' => 'new']);
    }

    // ====================================================== DIREKTOR LENTASI

    /**
     * Butun maktab bo'ylab sharhlar — «kim qaysi o'quvchiga nima deb yozdi».
     *
     * @param array $filtr muallif(int) | guruh(string) | oquvchi(string qidiruv)
     *                     | from, to ('YYYY-MM-DD') | korilmagan(bool)
     */
    public static function royxat(array $filtr = [], int $limit = 300): array {
        global $DB;

        $params = [];
        $shart = ['1 = 1'];

        if (!empty($filtr['muallif'])) {
            $shart[] = 's.authorid = :muallif';
            $params['muallif'] = (int) $filtr['muallif'];
        }
        if (!empty($filtr['guruh'])) {
            // Kurs idnumber: `<yil>::<guruh>::<fan>` — guruh o'rtadagi bo'lak.
            $shart[] = $DB->sql_like('co.idnumber', ':guruh');
            $params['guruh'] = '%::' . $DB->sql_like_escape($filtr['guruh']) . '::%';
        }
        if (!empty($filtr['oquvchi'])) {
            // Ism va familiya bo'yicha qidiruv — qaysi tartibda yozilganidan
            // qat'i nazar topilsin, shuning uchun ikki maydon alohida.
            $q = '%' . $DB->sql_like_escape($filtr['oquvchi']) . '%';
            $shart[] = '(' . $DB->sql_like('st.firstname', ':q1', false)
                . ' OR ' . $DB->sql_like('st.lastname', ':q2', false)
                . ' OR ' . $DB->sql_like('st.idnumber', ':q3', false) . ')';
            $params['q1'] = $q;
            $params['q2'] = $q;
            $params['q3'] = $q;
        }
        if (!empty($filtr['from'])) {
            $shart[] = 's.timecreated >= :from';
            $params['from'] = strtotime($filtr['from'] . ' 00:00:00');
        }
        if (!empty($filtr['to'])) {
            $shart[] = 's.timecreated <= :to';
            $params['to'] = strtotime($filtr['to'] . ' 23:59:59');
        }
        if (!empty($filtr['korilmagan'])) {
            $shart[] = "s.status = 'new' AND s.visibility = 'ochiq'";
        }

        return $DB->get_records_sql(self::select()
            . ' WHERE ' . implode(' AND ', $shart)
            . ' ORDER BY s.timecreated DESC, s.id DESC', $params, 0, $limit);
    }

    /**
     * Direktor filtrlari uchun ro'yxatlar — FAQAT sharh yozgan odamlar va
     * sharh bor guruhlar. Butun maktab ro'yxati tanlagichni foydasiz
     * uzunlikka cho'zardi.
     *
     * @return array ['muallif' => [id => ism], 'guruh' => [guruh => guruh]]
     */
    public static function filtrlar(): array {
        global $DB;

        $mualliflar = $DB->get_records_sql("
            SELECT DISTINCT a.id, a.firstname, a.lastname
              FROM {local_targetzenit_sharh} s
              JOIN {user} a ON a.id = s.authorid
          ORDER BY a.lastname ASC, a.firstname ASC");
        $m = [];
        foreach ($mualliflar as $r) {
            $m[(int) $r->id] = dashboard::ism($r->firstname, $r->lastname);
        }

        $idnumbers = $DB->get_fieldset_sql("
            SELECT DISTINCT co.idnumber
              FROM {local_targetzenit_sharh} s
              JOIN {course} co ON co.id = s.courseid
             WHERE co.idnumber IS NOT NULL AND co.idnumber <> ''");
        $g = [];
        foreach ($idnumbers as $idn) {
            $qism = explode('::', (string) $idn);
            if (count($qism) >= 3 && $qism[1] !== '') {
                $g[$qism[1]] = $qism[1];
            }
        }
        // «1 A» dan keyin «2 A», «10 A» esa oxirida — alifbo tartibida
        // «10 A» «2 A» dan oldin turib qolardi.
        uksort($g, fn($a, $b) => strnatcasecmp($a, $b));

        return ['muallif' => $m, 'guruh' => $g];
    }

    // =============================================================== YORDAM

    /** Kurs idnumber'idan «guruh · fan» ko'rinishidagi yorliq. */
    public static function kurs_yorlig(?string $idnumber, ?string $fullname): string {
        $qism = explode('::', (string) $idnumber);
        if (count($qism) >= 3) {
            return $qism[1] . ' · ' . $qism[2];
        }
        return (string) $fullname;
    }

    /** Sharh matni — `{comments}` dagi xom matn (format qo'llanmagan). */
    public static function matn(\stdClass $r): string {
        return (string) $r->content;
    }
}
