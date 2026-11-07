<?php
// MUROJAATLAR — ota-ona shikoyat/taklifi: ma'lumot qatlami.
//
// EGALIK (MUROJAAT_ARXITEKTURA.md 1-bo'lim): murojaatning TUG'ILISHI Frappe'da
// (kim qachon gaplashdi, kim shikoyat qildi), YURITILISHI esa shu yerda —
// rahbar ko'radi, komment yozadi, qaror qabul qiladi. Ikki tomonlama yozish
// yo'q: Frappe manba maydonlarini yuboradi, Moodle yuritish maydonlarini
// o'zi yozadi, Frappe ularni faqat o'qib ko'rsatadi.
//
// QOIDA: veb sahifa ham, mobil ilova ham FAQAT shu klassdan oziqlanadi.
// Ikkinchi SQL yozilsa ikki ko'rinish bir-biridan uzilib ketadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class murojaat {

    /** Yo'nalish -> uni ko'radigan ruxsat. */
    const CAPS = [
        'CEO' => 'local/targetzenit:murojaatceo',
        'Direktor' => 'local/targetzenit:murojaatdirektor',
    ];

    /** Rahbar tanlay oladigan qarorlar. */
    const QARORLAR = ['Hal qilindi', 'Inobatga olindi', 'Keyinroq ko\'riladi',
                      'Rad etildi', 'Ahamiyatsiz'];

    /**
     * Ota-onaga bildirishnoma YUBORILMAYDIGAN qaror. «Ahamiyatsiz» — AI xato
     * topgan holat: ota-ona hech narsa so'ramagan bo'lsa unga xabar yuborish
     * chalkashlik tug'diradi.
     */
    const JIM_QARORLAR = ['Ahamiyatsiz'];

    const HOLATLAR = ['Yangi', 'Ko\'rib chiqilmoqda', 'Yopildi'];

    const MAX = 4000;

    // ------------------------------------------------------------- ruxsatlar
    /** Joriy foydalanuvchi ko'radigan yo'nalishlar. */
    public static function yonalishlar(?\context $context = null): array {
        $context = $context ?: \context_system::instance();
        if (is_siteadmin()) {
            return array_keys(self::CAPS);
        }
        $out = [];
        foreach (self::CAPS as $yonalish => $cap) {
            if (has_capability($cap, $context)) {
                $out[] = $yonalish;
            }
        }
        return $out;
    }

    public static function rahbarmi(): bool {
        return (bool) self::yonalishlar();
    }

    /** Shu murojaatni ko'rish/yozish huquqi bormi. */
    public static function ruxsatmi(\stdClass $m): bool {
        return in_array($m->yonalish, self::yonalishlar(), true);
    }

    public static function tekshir(\stdClass $m): void {
        if (!self::ruxsatmi($m)) {
            throw new \moodle_exception('nopermissions', 'error', '',
                get_string('mur_notyours', 'local_targetzenit'));
        }
    }

    // ---------------------------------------------------------------- o'qish
    /**
     * Rahbar navbati. `$holat`: '' (hammasi) | 'ochiq' | 'muddati' | 'menda'
     * yoki aniq holat nomi.
     */
    public static function royxat(string $holat = 'ochiq', string $qidiruv = '',
                                   int $limit = 300, array $filtr = []): array {
        global $DB, $USER;

        $yonalishlar = self::yonalishlar();
        if (!$yonalishlar) {
            return [];
        }
        [$ysql, $params] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        $shart = ["m.yonalish $ysql"];

        if ($holat === 'ochiq' || $holat === 'muddati' || $holat === 'menda') {
            $shart[] = "m.holat <> 'Yopildi'";
            if ($holat === 'muddati') {
                $shart[] = 'm.slasana > 0 AND m.slasana < :bugun';
                $params['bugun'] = time();
            } else if ($holat === 'menda') {
                $shart[] = 'm.masuluserid = :men';
                $params['men'] = $USER->id;
            }
        } else if ($holat !== '' && in_array($holat, self::HOLATLAR, true)) {
            $shart[] = 'm.holat = :holat';
            $params['holat'] = $holat;
        }

        // Frappe panelidagi filtrlar bilan bir xil to'plam: turi, kategoriya,
        // kim biriktirilgan. "__yoq__" — biriktirilmaganlar (egasiz navbat
        // modulning eng xavfli holati, shuning uchun alohida ko'rinadi).
        if (!empty($filtr['turi'])) {
            $shart[] = 'm.turi = :turi';
            $params['turi'] = $filtr['turi'];
        }
        if (!empty($filtr['kategoriya'])) {
            $shart[] = 'm.kategoriya = :kat';
            $params['kat'] = $filtr['kategoriya'];
        }
        if (!empty($filtr['masul'])) {
            if ($filtr['masul'] === '__yoq__') {
                $shart[] = 'm.masuluserid = 0';
            } else {
                $shart[] = 'm.masuluserid = :masul';
                $params['masul'] = (int) $filtr['masul'];
            }
        }

        if (trim($qidiruv) !== '') {
            $q = '%' . $DB->sql_like_escape(trim($qidiruv)) . '%';
            $shart[] = '(' . $DB->sql_like('m.studentname', ':q1', false) . ' OR '
                . $DB->sql_like('m.matn', ':q2', false) . ' OR '
                . $DB->sql_like('m.payername', ':q3', false) . ')';
            $params['q1'] = $params['q2'] = $params['q3'] = $q;
        }

        // Muddati o'tganlar tepada, keyin muhimlik, keyin yangisi.
        $sql = "SELECT m.*
                  FROM {local_targetzenit_murojaat} m
                 WHERE " . implode(' AND ', $shart) . "
              ORDER BY m.ochilgan DESC";
        $rows = array_values($DB->get_records_sql($sql, $params, 0, $limit));
        return self::tartibla($rows);
    }

    /** Muddati o'tgan -> muhim -> yangi. SQL'da emas: ustunlik mantiqi o'zgaradi. */
    protected static function tartibla(array $rows): array {
        $og = ['Yuqori' => 0, "O'rta" => 1, 'Past' => 2];
        $now = time();
        usort($rows, function ($a, $b) use ($og, $now) {
            $ka = ($a->slasana > 0 && $a->slasana < $now && $a->holat !== 'Yopildi') ? 0 : 1;
            $kb = ($b->slasana > 0 && $b->slasana < $now && $b->holat !== 'Yopildi') ? 0 : 1;
            if ($ka !== $kb) {
                return $ka <=> $kb;
            }
            $ma = $og[$a->muhimlik] ?? 1;
            $mb = $og[$b->muhimlik] ?? 1;
            if ($ma !== $mb) {
                return $ma <=> $mb;
            }
            return $b->ochilgan <=> $a->ochilgan;
        });
        return $rows;
    }

    public static function get(int $id): ?\stdClass {
        global $DB;
        $m = $DB->get_record('local_targetzenit_murojaat', ['id' => $id]);
        return $m ?: null;
    }

    /** Statistika — rahbar paneli tepasidagi plitalar. */
    public static function stats(): array {
        global $DB, $USER;

        $yonalishlar = self::yonalishlar();
        if (!$yonalishlar) {
            return ['ochiq' => 0, 'yangi' => 0, 'korilmagan' => 0, 'muddati' => 0,
                    'menda' => 0, 'oyyopildi' => 0, 'biriktirilmagan' => 0,
                    'ortkun' => 0];
        }
        [$ysql, $p] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        $t = fn(string $qoshimcha, array $extra = []) => (int) $DB->count_records_select(
            'local_targetzenit_murojaat', "yonalish $ysql AND $qoshimcha", $p + $extra);

        $oyboshi = make_timestamp((int) date('Y'), (int) date('n'), 1);
        return [
            'ochiq' => $t("holat <> 'Yopildi'"),
            'yangi' => $t("holat = 'Yangi'"),
            'korilmagan' => $t("holat <> 'Yopildi' AND korildivaqti = 0"),
            'muddati' => $t("holat <> 'Yopildi' AND slasana > 0 AND slasana < :n",
                            ['n' => time()]),
            'menda' => $t("holat <> 'Yopildi' AND masuluserid = :men", ['men' => $USER->id]),
            'oyyopildi' => $t("holat = 'Yopildi' AND yopilganvaqti >= :oy", ['oy' => $oyboshi]),
            // Egasiz navbat — hech kim javob bermaydigan murojaat
            'biriktirilmagan' => $t("holat <> 'Yopildi' AND masuluserid = 0"),
            'ortkun' => self::ortacha_kun($yonalishlar),
        ];
    }

    /** Bu oy yopilganlarning ochilishdan yopilishgacha o'rtacha kuni. */
    protected static function ortacha_kun(array $yonalishlar): float {
        global $DB;

        [$ysql, $p] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        $p['oy'] = make_timestamp((int) date('Y'), (int) date('n'), 1);
        $rows = $DB->get_records_select('local_targetzenit_murojaat',
            "yonalish $ysql AND holat = 'Yopildi' AND yopilganvaqti >= :oy",
            $p, '', 'id, ochilgan, yopilganvaqti');
        $kunlar = [];
        foreach ($rows as $r) {
            if ($r->ochilgan && $r->yopilganvaqti >= $r->ochilgan) {
                $kunlar[] = ($r->yopilganvaqti - $r->ochilgan) / DAYSECS;
            }
        }
        return $kunlar ? round(array_sum($kunlar) / count($kunlar), 1) : 0;
    }

    /** Filtr ro'yxati uchun: real ishlayotgan mas'ullar + biriktirilmaganlar. */
    public static function masullar(): array {
        global $DB;

        $yonalishlar = self::yonalishlar();
        if (!$yonalishlar) {
            return [];
        }
        [$ysql, $p] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        $rows = $DB->get_records_sql(
            "SELECT masuluserid, COUNT(*) soni
               FROM {local_targetzenit_murojaat}
              WHERE yonalish $ysql AND masuluserid > 0
           GROUP BY masuluserid
           ORDER BY soni DESC", $p);
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (string) $r->masuluserid,
                      'nomi' => self::ism((int) $r->masuluserid),
                      'soni' => (int) $r->soni];
        }
        $yoq = $DB->count_records_select('local_targetzenit_murojaat',
            "yonalish $ysql AND masuluserid = 0 AND holat <> 'Yopildi'", $p);
        if ($yoq) {
            $out[] = ['id' => '__yoq__',
                      'nomi' => get_string('mur_noowner', 'local_targetzenit'),
                      'soni' => (int) $yoq];
        }
        return $out;
    }

    /** Filtr ro'yxati uchun: uchraydigan turlar va kategoriyalar. */
    public static function turlar(): array {
        return ['Shikoyat', 'Taklif', 'Muammo', "So'rov"];
    }

    public static function kategoriyalar(): array {
        global $DB;

        $yonalishlar = self::yonalishlar();
        if (!$yonalishlar) {
            return [];
        }
        [$ysql, $p] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        return $DB->get_fieldset_sql(
            "SELECT DISTINCT kategoriya
               FROM {local_targetzenit_murojaat}
              WHERE yonalish $ysql AND kategoriya IS NOT NULL AND kategoriya <> ''
           ORDER BY kategoriya", $p);
    }

    /**
     * Kategoriya kesimi — qaysi masala ko'p takrorlanyapti.
     *
     * DIQQAT — POSTGRESQL: guruhlash FAQAT ustun bo'yicha ketadi, parametrli
     * ifoda bo'yicha emas. Avval
     * `GROUP BY COALESCE(NULLIF(kategoriya, ''), :yoq2)` yozilgan edi va
     * MySQL'da ishlardi; PostgreSQL esa SELECT'dagi `$1` va GROUP BY'dagi
     * `$3` parametrlarini BOSHQA ifoda deb hisoblab «column must appear in
     * the GROUP BY clause» xatosi bilan butun sahifani yiqitardi
     * (2026-11-07, prodda topildi). Yorliq («Kategoriya yo'q») endi PHP
     * tomonda qo'yiladi — guruhlashga hech qanday parametr kirmaydi.
     */
    public static function kesim(int $limit = 12): array {
        global $DB;
        $yonalishlar = self::yonalishlar();
        if (!$yonalishlar) {
            return [];
        }
        [$ysql, $p] = $DB->get_in_or_equal($yonalishlar, SQL_PARAMS_NAMED, 'yo');
        $sql = "SELECT kategoriya,
                       COUNT(*) soni,
                       SUM(CASE WHEN holat <> 'Yopildi' THEN 1 ELSE 0 END) ochiq
                  FROM {local_targetzenit_murojaat}
                 WHERE yonalish $ysql
              GROUP BY kategoriya";

        // `get_records_sql` ishlatilmaydi: kalit kategoriya bo'lardi va
        // NULL bilan '' bitta kalitga tushib, qator yo'qolardi.
        $yoq = get_string('mur_nocat', 'local_targetzenit');
        $yigma = [];
        $rs = $DB->get_recordset_sql($sql, $p);
        foreach ($rs as $r) {
            $nomi = ($r->kategoriya === null || trim((string) $r->kategoriya) === '')
                ? $yoq : $r->kategoriya;
            if (!isset($yigma[$nomi])) {
                $yigma[$nomi] = (object) ['nomi' => $nomi, 'soni' => 0, 'ochiq' => 0];
            }
            $yigma[$nomi]->soni += (int) $r->soni;
            $yigma[$nomi]->ochiq += (int) $r->ochiq;
        }
        $rs->close();

        $yigma = array_values($yigma);
        usort($yigma, fn($a, $b) => $b->soni <=> $a->soni);
        return array_slice($yigma, 0, $limit);
    }

    /**
     * Izohlar. `$otaona = true` bo'lsa faqat ota-onaga ko'rinadigan qatorlar
     * (ichki kommentlar chiqmaydi).
     */
    public static function izohlar(int $murojaatid, bool $otaona = false): array {
        global $DB;
        $shart = ['murojaatid' => $murojaatid];
        if ($otaona) {
            $shart['korinish'] = 'otaona';
        }
        $rows = $DB->get_records('local_targetzenit_murojaat_izoh', $shart, 'timecreated ASC');
        if (!$rows) {
            return [];
        }
        $userids = array_filter(array_map(fn($r) => (int) $r->userid, $rows));
        $ismlar = [];
        if ($userids) {
            foreach ($DB->get_records_list('user', 'id', array_unique($userids),
                    '', 'id, firstname, lastname') as $u) {
                $ismlar[(int) $u->id] = fullname($u);
            }
        }
        foreach ($rows as $r) {
            $r->ism = $ismlar[(int) $r->userid]
                ?? get_string('mur_system', 'local_targetzenit');
        }
        return array_values($rows);
    }

    /**
     * Sotuv bo'limining aloqa tarixi — "kim qachon gaplashdi".
     *
     * Rahbar buni ko'rmasa ota-onaga ikkinchi marta bir xil javob beriladi;
     * Frappe panelida ham aynan shu jadval turadi.
     */
    public static function suhbatlar(int $murojaatid, int $limit = 30): array {
        global $DB;
        return array_values($DB->get_records('local_targetzenit_murojaat_suhbat',
            ['murojaatid' => $murojaatid], 'vaqt DESC', '*', 0, $limit));
    }

    /**
     * Shu o'quvchi bo'yicha BOSHQA murojaatlar — masala takrorlanayotganini
     * ko'rsatadi. Bitta murojaatni yopish emas, sababni yo'q qilish kerak.
     */
    public static function boshqalari(\stdClass $m, int $limit = 10): array {
        global $DB;

        if (!$m->studentidnumber && !$m->studentuserid) {
            return [];
        }
        $shart = $m->studentidnumber
            ? 'studentidnumber = :sid' : 'studentuserid = :sid';
        $p = ['sid' => $m->studentidnumber ?: $m->studentuserid, 'id' => $m->id];
        return array_values($DB->get_records_select('local_targetzenit_murojaat',
            "$shart AND id <> :id", $p, 'ochilgan DESC',
            'id, turi, kategoriya, holat, qarorturi, ochilgan, matn', 0, $limit));
    }

    // ----------------------------------------------------------------- yozish
    /** Rahbar kartani ochdi — «ko'rib chiqildimi» savoliga javob shu. */
    public static function korildi(\stdClass $m): void {
        global $DB, $USER;
        self::tekshir($m);
        $yangi = [];
        if (!$m->korildivaqti) {
            $yangi['korildikim'] = $USER->id;
            $yangi['korildivaqti'] = time();
        }
        if ($m->holat === 'Yangi') {
            $yangi['holat'] = "Ko'rib chiqilmoqda";
        }
        if (!$yangi) {
            return;
        }
        $yangi['id'] = $m->id;
        $yangi['timemodified'] = time();
        $DB->update_record('local_targetzenit_murojaat', (object) $yangi);
        foreach ($yangi as $k => $v) {
            $m->$k = $v;
        }
    }

    /** Ichki komment (ota-onaga KO'RINMAYDI). */
    public static function izoh_qosh(\stdClass $m, string $body,
                                      string $korinish = 'rahbar'): int {
        global $DB, $USER;
        self::tekshir($m);
        $body = trim($body);
        if ($body === '') {
            return 0;
        }
        $id = self::izoh_yoz($m->id, $USER->id, $body, 'komment', $korinish);
        if ($m->holat === 'Yangi') {
            $DB->update_record('local_targetzenit_murojaat',
                (object) ['id' => $m->id, 'holat' => "Ko'rib chiqilmoqda",
                          'timemodified' => time()]);
            $m->holat = "Ko'rib chiqilmoqda";
        }
        return $id;
    }

    /** Timeline qatori (ruxsat tekshirmaydi — chaqiruvchi tekshiradi). */
    public static function izoh_yoz(int $murojaatid, int $userid, string $body,
                                     string $turi = 'komment',
                                     string $korinish = 'rahbar'): int {
        global $DB;
        return (int) $DB->insert_record('local_targetzenit_murojaat_izoh', (object) [
            'murojaatid' => $murojaatid,
            'userid' => $userid,
            'turi' => $turi,
            'korinish' => $korinish,
            'body' => \core_text::substr(trim($body), 0, self::MAX),
            'timecreated' => time(),
        ]);
    }

    /**
     * RAHBAR QARORI — modulning yuragi. Qaror yozilgach murojaat yopiladi va
     * ota-onaga bildirishnoma ketadi (bolasining ilovasiga push).
     */
    public static function qaror(\stdClass $m, string $qarorturi, string $qaror): bool {
        global $DB, $USER;
        self::tekshir($m);

        $qaror = trim($qaror);
        if ($qaror === '') {
            throw new \moodle_exception('mur_needtext', 'local_targetzenit');
        }
        if (!in_array($qarorturi, self::QARORLAR, true)) {
            $qarorturi = 'Hal qilindi';
        }
        $now = time();
        $DB->update_record('local_targetzenit_murojaat', (object) [
            'id' => $m->id,
            'qarorturi' => $qarorturi,
            'qaror' => \core_text::substr($qaror, 0, self::MAX),
            'qarorkim' => $USER->id,
            'qarorvaqti' => $now,
            'holat' => 'Yopildi',
            'yopilganvaqti' => $now,
            'masuluserid' => $m->masuluserid ?: $USER->id,
            'timemodified' => $now,
        ]);
        // Ota-ona javobni ko'radi (2026-11-04 qarori: shikoyat matni + javob)
        self::izoh_yoz($m->id, $USER->id, "$qarorturi: $qaror", 'qaror', 'otaona');

        $m = self::get((int) $m->id);
        if (!in_array($qarorturi, self::JIM_QARORLAR, true)) {
            self::xabar_ber($m);
        }
        return true;
    }

    /** Murojaatni o'ziga olish. */
    public static function masul_ol(\stdClass $m): void {
        global $DB, $USER;
        self::tekshir($m);
        if ((int) $m->masuluserid === (int) $USER->id) {
            return;
        }
        $DB->update_record('local_targetzenit_murojaat',
            (object) ['id' => $m->id, 'masuluserid' => $USER->id, 'timemodified' => time()]);
        self::izoh_yoz($m->id, $USER->id,
            get_string('mur_tookit', 'local_targetzenit'), 'holat');
        $m->masuluserid = $USER->id;
    }

    /** Boshqa yo'nalishga uzatish (masala CEO'ga emas, direktorga tegishli). */
    public static function uzat(\stdClass $m, string $yonalish, string $izoh = ''): void {
        global $DB, $USER;
        self::tekshir($m);
        if (!isset(self::CAPS[$yonalish]) || $yonalish === $m->yonalish) {
            return;
        }
        $DB->update_record('local_targetzenit_murojaat', (object) [
            'id' => $m->id, 'yonalish' => $yonalish, 'masuluserid' => 0,
            'timemodified' => time(),
        ]);
        self::izoh_yoz($m->id, $USER->id,
            $m->yonalish . ' -> ' . $yonalish . ($izoh !== '' ? ': ' . $izoh : ''), 'holat');
        $m->yonalish = $yonalish;
        $m->masuluserid = 0;
    }

    // ------------------------------------------------------ ota-ona tomoni
    /**
     * O'quvchi akkauntidagi murojaatlar. Ota-ona bolasining logini bilan
     * kiradi — Moodle'da ota-ona akkaunti yo'q (faqat Student/Instructor/
     * Employee sinxronlanadi).
     */
    public static function meniki(int $userid, int $limit = 50): array {
        global $DB;
        return array_values($DB->get_records('local_targetzenit_murojaat',
            ['studentuserid' => $userid], 'ochilgan DESC', '*', 0, $limit));
    }

    /** Ota-ona javobni ko'rdi — rahbar "yetib bordimi" ni biladi. */
    public static function otaona_kordi(\stdClass $m, int $userid): void {
        global $DB;
        if ((int) $m->studentuserid !== $userid || $m->otaonakordi || !$m->qarorvaqti) {
            return;
        }
        $DB->update_record('local_targetzenit_murojaat',
            (object) ['id' => $m->id, 'otaonakordi' => time(), 'timemodified' => time()]);
        $m->otaonakordi = time();
    }

    /** Qaror haqida o'quvchi akkauntiga bildirishnoma (pochta + ilovaga push). */
    protected static function xabar_ber(\stdClass $m): void {
        if (!$m->studentuserid) {
            return;     // Moodle akkaunti topilmagan — panel buni ko'rsatadi
        }
        $user = \core_user::get_user((int) $m->studentuserid);
        if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
            return;
        }
        $url = new \moodle_url('/local/targetzenit/murojaatim.php', ['id' => $m->id]);
        $qisqa = get_string('mur_msgsubject', 'local_targetzenit');
        $matn = get_string('mur_msgbody', 'local_targetzenit', [
            'murojaat' => format_text($m->matn, FORMAT_PLAIN),
            'qaror' => format_text((string) $m->qaror, FORMAT_PLAIN),
        ]);

        $xabar = new \core\message\message();
        $xabar->component = 'local_targetzenit';
        $xabar->name = 'murojaat';
        $xabar->userfrom = \core_user::get_noreply_user();
        $xabar->userto = $user;
        $xabar->subject = $qisqa;
        $xabar->fullmessage = $matn;
        $xabar->fullmessageformat = FORMAT_PLAIN;
        $xabar->fullmessagehtml = '<p>' . s($matn) . '</p>';
        $xabar->smallmessage = $qisqa;
        $xabar->notification = 1;
        $xabar->contexturl = $url->out(false);
        $xabar->contexturlname = $qisqa;
        message_send($xabar);
    }

    // ------------------------------------------------------------ yordamchi
    /** Muddat holati: ['kechikdi'|'bugun'|'bor'|'yoq', kun]. */
    public static function muddat(\stdClass $m): array {
        if ($m->holat === 'Yopildi' || !$m->slasana) {
            return ['yoq', 0];
        }
        $kun = (int) floor(($m->slasana - time()) / DAYSECS);
        if ($kun < 0) {
            return ['kechikdi', abs($kun)];
        }
        return $kun === 0 ? ['bugun', 0] : ['bor', $kun];
    }

    /** Foydalanuvchi ismi (0 bo'lsa chiziqcha). */
    public static function ism(int $userid): string {
        global $DB;
        if (!$userid) {
            return '—';
        }
        $u = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
        return $u ? fullname($u) : '—';
    }
}
