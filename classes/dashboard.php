<?php
// Direktor paneli uchun o'qish qatlami — hamma so'rov shu yerda.
//
// Sahifa (direktor.php) faqat chizadi; hisob-kitob va SQL bu yerda turadi,
// shunda keyingi ekranlar (o'qituvchilar ro'yxati, o'qituvchi kartasi) xuddi
// shu metodlarni qayta ishlatadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class dashboard {

    /**
     * Audio YOZILGAN deb hisoblanadigan holatlar.
     *
     * `deleted` ham shu yerda: audio 15 kundan keyin avtomatik o'chadi, lekin
     * yozuv OLINGAN edi. Uni "yozilmagan" deb ko'rsatsak, sentabr-oktabr
     * ma'lumoti butunlay noto'g'ri chiqardi.
     */
    const YOZILGAN = "'ready', 'transcribed', 'deleted'";

    /** Diqqat navbati necha kun orqaga qaraydi. */
    const NAVBAT_KUN = 14;

    /** Har daraja uchun navbatda ko'rsatiladigan qator soni. */
    const NAVBAT_QATOR = 5;

    /** Normadan shuncha past bo'lsa — diqqat talab qiladi. */
    const PAST_CHEGARA = -15;

    /**
     * KUZATUV BOSHLANISH SANASI — baholash qaysi kundan boshlanadi.
     *
     * Qaror (2026-10-29): dastur ishga tushgan kundan. Sana QO'LDA
     * yozilmaydi, ma'lumotdan aniqlanadi: `_lesson` dagi eng birinchi
     * yozuvning yaratilgan vaqti = jurnal haqiqatda ishlatila boshlagan kun.
     *
     * NEGA KERAK: reja 1-sentabrdan to'lgan (31 945 slot), jurnal esa
     * keyinroq ishga tushgan. Boshlanish sanasi bo'lmasa o'qituvchi jurnal
     * mavjud bo'lmagan kunlar uchun ham "dars o'tmagan" deb ko'rinadi.
     *
     * Sozlamadagi qiymat (`kuzatuvboshi`) bo'sh bo'lmasa — u ustun turadi.
     */
    public static function kuzatuv_boshi(): string {
        $qolda = trim((string) get_config('local_targetzenit', 'kuzatuvboshi'));
        if ($qolda !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $qolda)) {
            return $qolda;
        }

        global $DB;
        $birinchi = (int) $DB->get_field_sql(
            'SELECT MIN(timecreated) FROM {local_targetzenit_lesson} WHERE timecreated > 0');
        if (!$birinchi) {
            // Jurnal hali umuman ishlatilmagan — bugundan boshlaymiz, aks holda
            // butun o'quv yili "bajarilmagan" bo'lib ko'rinardi.
            return date('Y-m-d');
        }
        return date('Y-m-d', $birinchi);
    }

    /** Sana kuzatuv oynasiga kiradimi. */
    public static function kuzatuvda(string $date): bool {
        return $date >= self::kuzatuv_boshi();
    }

    /**
     * "Allaqachon o'tgan dars" sharti.
     *
     * Bugungi kun uchun hozirgi vaqtga qaraladi — hali bo'lmagan darsni
     * "bajarilmagan" deb ko'rsatish yolg'on bo'lardi.
     *
     * NEGA PARAMETR EMAS, LITERAL: Moodle'da bitta nomli parametrni bir
     * so'rovda IKKI MARTA ishlatish mumkin emas — `fix_sql_params()`
     * placeholder'lar SONINI parametrlar soni bilan taqqoslaydi va
     * `invalidqueryparam` tashlaydi. Bu shart esa bitta so'rovda 5 martagacha
     * takrorlanadi. Qiymatlar `date()` dan, ya'ni SERVER hosil qiladi —
     * foydalanuvchi kiritmaydi, shuning uchun literal xavfsiz.
     *
     * @return string SQL sharti
     */
    protected static function otgan_sql(string $alias = 's'): string {
        $bugun = date('Y-m-d');
        $vaqt = date('H:i');
        return "({$alias}.lessondate < '{$bugun}'"
            . " OR ({$alias}.lessondate = '{$bugun}'"
            . " AND ({$alias}.endtime IS NULL OR {$alias}.endtime <= '{$vaqt}')))";
    }

    /**
     * Ism — faqat `firstname`/`lastname` dan.
     *
     * `fullname()` ISHLATILMAYDI: u to'liq user yozuvini kutadi va maydon
     * yetishmasa debugging xabari chiqaradi. Bizda yig'ma so'rovlardan faqat
     * ikki maydon keladi.
     */
    public static function ism(?string $firstname, ?string $lastname): string {
        return trim(trim((string) $lastname) . ' ' . trim((string) $firstname));
    }

    // ------------------------------------------------------------- HERO
    /**
     * Bugungi umumiy manzara.
     *
     * `otgan` — vaqti o'tgan darslar; foizlar FAQAT shulardan hisoblanadi.
     */
    public static function today_summary(string $date): \stdClass {
        global $DB;

        $otgan = self::otgan_sql();
        $params = ['d' => $date];
        $yoz = self::YOZILGAN;

        $r = $DB->get_record_sql("
            SELECT COUNT(1) AS jami,
                   SUM(CASE WHEN $otgan THEN 1 ELSE 0 END) AS otgan,
                   SUM(CASE WHEN $otgan AND f.hastopic = 1 THEN 1 ELSE 0 END) AS mavzu,
                   SUM(CASE WHEN $otgan AND f.attendancecount > 0 THEN 1 ELSE 0 END) AS yoqlama,
                   SUM(CASE WHEN $otgan AND f.gradecount > 0 THEN 1 ELSE 0 END) AS baho,
                   SUM(CASE WHEN $otgan AND f.audiostatus IN ($yoz) THEN 1 ELSE 0 END) AS yozuv,
                   SUM(CASE WHEN f.analysestatus = 'ok' THEN 1 ELSE 0 END) AS tahlil,
                   SUM(f.normdelta) AS delta_yigindi,
                   COUNT(f.normdelta) AS delta_soni
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE s.lessondate = :d", $params);

        $out = (object) [
            'jami' => (int) ($r->jami ?? 0),
            'otgan' => (int) ($r->otgan ?? 0),
            'mavzu' => (int) ($r->mavzu ?? 0),
            'yoqlama' => (int) ($r->yoqlama ?? 0),
            'baho' => (int) ($r->baho ?? 0),
            'yozuv' => (int) ($r->yozuv ?? 0),
            'tahlil' => (int) ($r->tahlil ?? 0),
            // O'rtacha ball — YIG'INDI/SANOQ dan hisoblanadi, saqlangan
            // o'rtachadan emas: o'rtachalar davrlar bo'ylab qo'shilmaydi.
            'delta' => (int) ($r->delta_soni ?? 0)
                ? (int) round(((float) $r->delta_yigindi) / ((int) $r->delta_soni)) : null,
            'delta_n' => (int) ($r->delta_soni ?? 0),
        ];
        $out->qoldi = max(0, $out->jami - $out->otgan);
        return $out;
    }

    /** Foiz — bo'luvchi nol bo'lsa null (0% deb ko'rsatish yolg'on bo'lardi). */
    public static function foiz(int $qism, int $jami): ?int {
        return $jami > 0 ? (int) round($qism * 100 / $jami) : null;
    }

    // -------------------------------------------------------- SPARKLINE
    /**
     * Oxirgi N dars kuni bo'yicha jurnal va yozuv ulushi.
     * Faqat kuzatuv oynasidagi va vaqti o'tgan darslar.
     */
    public static function trend(string $date, int $kun = 14): array {
        global $DB;

        $otgan = self::otgan_sql();
        $params = ['a' => self::kuzatuv_boshi(), 'b' => $date];
        $yoz = self::YOZILGAN;

        $rows = $DB->get_records_sql("
            SELECT s.lessondate,
                   COUNT(1) AS jami,
                   SUM(CASE WHEN f.hastopic = 1 THEN 1 ELSE 0 END) AS mavzu,
                   SUM(CASE WHEN f.audiostatus IN ($yoz) THEN 1 ELSE 0 END) AS yozuv
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             WHERE s.lessondate >= :a AND s.lessondate <= :b AND $otgan
          GROUP BY s.lessondate
          ORDER BY s.lessondate DESC", $params, 0, $kun);

        $out = [];
        foreach (array_reverse($rows) as $r) {
            $jami = (int) $r->jami;
            $out[] = [
                'date' => $r->lessondate,
                'jami' => $jami,
                'mavzu' => self::foiz((int) $r->mavzu, $jami),
                'yozuv' => self::foiz((int) $r->yozuv, $jami),
            ];
        }
        return $out;
    }

    // ---------------------------------------------------- DIQQAT NAVBATI
    /**
     * Diqqat navbati — ustuvorlik tartibida.
     *
     * NAVBAT HISOBOT EMAS, AMAL RO'YXATI. Shuning uchun u butun o'quv yiliga
     * emas, oxirgi {@see NAVBAT_KUN} kunga qaraydi: "kecha mavzu yozilmagan"
     * ni tuzatish mumkin, "sentabrda yozilmagan" ni endi yo'q. Hisobot
     * (ro'yxat, chorak xaritasi) esa butun oynani qamraydi.
     */
    public static function attention(string $date): array {
        global $DB;

        $oyna_boshi = max(self::kuzatuv_boshi(),
            date('Y-m-d', strtotime('-' . self::NAVBAT_KUN . ' days', strtotime($date))));
        if ($oyna_boshi > $date) {
            return [];
        }

        $items = [];
        $otgan = self::otgan_sql();
        $yoz = self::YOZILGAN;
        $umumiy = ['a' => $oyna_boshi, 'b' => $date];

        $tanlov = "s.id AS slotid, s.lessondate, s.lessonno, s.starttime, s.endtime,
                   s.groupname, s.subjectname, s.courseid, s.teacherkey,
                   f.attributeduserid, f.lessonid, f.normdelta, f.lowconfcount,
                   f.latehours, f.audiostatus, f.analysestatus,
                   u.firstname, u.lastname, st.teachername";
        $manba = "FROM {local_targetzenit_slot} s
                  JOIN {local_targetzenit_fact} f ON f.slotid = s.id
             LEFT JOIN {user} u ON u.id = f.attributeduserid
             LEFT JOIN {local_targetzenit_staff} st
                    ON st.academicyear = s.academicyear AND st.teacherkey = s.teacherkey";
        $oyna = "WHERE s.lessondate >= :a AND s.lessondate <= :b AND $otgan";

        // --- 1-daraja: mavzu ham yo'qlama ham yo'q (eng aniq hodisa)
        $rows = $DB->get_records_sql("
            SELECT $tanlov $manba $oyna AND f.hastopic = 0 AND f.attendancecount = 0
          ORDER BY s.lessondate DESC, s.lessonno DESC",
            $umumiy, 0, self::NAVBAT_QATOR);
        foreach ($rows as $r) {
            $items[] = self::item($r, 1, 'critical', "jurnal to'ldirilmagan",
                "mavzu yozilmagan · yo'qlama yo'q");
        }
        $jami1 = $DB->count_records_sql("
            SELECT COUNT(1) $manba $oyna AND f.hastopic = 0 AND f.attendancecount = 0",
            $umumiy);

        // --- 2-daraja: yozuv yo'q — O'QITUVCHI BO'YICHA GURUHLANADI.
        // Yozuv har darsda majburiy, ya'ni bu kuniga ~70 marta uchraydi;
        // qator-qator ko'rsatilsa navbat ishlatib bo'lmaydigan ro'yxatga
        // aylanadi.
        $grows = $DB->get_records_sql("
            SELECT COALESCE(f.attributeduserid, 0) AS uid,
                   MAX(u.firstname) AS firstname, MAX(u.lastname) AS lastname,
                   MAX(st.teachername) AS teachername,
                   MAX(s.teacherkey) AS teacherkey,
                   COUNT(1) AS otgan,
                   SUM(CASE WHEN f.audiostatus NOT IN ($yoz) THEN 1 ELSE 0 END) AS yozuvsiz,
                   SUM(CASE WHEN s.lessondate = :bugun THEN 1 ELSE 0 END) AS bugun,
                   SUM(CASE WHEN s.lessondate = :bugun2
                             AND f.audiostatus NOT IN ($yoz) THEN 1 ELSE 0 END) AS bugun_yozuvsiz
              $manba $oyna
          GROUP BY COALESCE(f.attributeduserid, 0)
            HAVING SUM(CASE WHEN f.audiostatus NOT IN ($yoz) THEN 1 ELSE 0 END) > 0
          ORDER BY SUM(CASE WHEN f.audiostatus NOT IN ($yoz) THEN 1 ELSE 0 END) DESC",
            array_merge($umumiy, ['bugun' => $date, 'bugun2' => $date]),
            0, self::NAVBAT_QATOR);

        foreach ($grows as $g) {
            $ism = $g->firstname || $g->lastname
                ? self::ism($g->firstname, $g->lastname)
                : ($g->teachername ?: $g->teacherkey);
            $items[] = (object) [
                'level' => 2,
                'status' => 'serious',
                'kind' => "yozuv olinmagan",
                'title' => $ism ?: "o'qituvchi tayinlanmagan",
                'detail' => $g->bugun
                    ? "bugun {$g->bugun} darsdan {$g->bugun_yozuvsiz} tasida yozuv yo'q"
                        . " · oxirgi " . self::NAVBAT_KUN . " kunda {$g->yozuvsiz}/{$g->otgan}"
                    : "oxirgi " . self::NAVBAT_KUN . " kunda {$g->yozuvsiz}/{$g->otgan} darsda yozuv yo'q",
                'userid' => (int) $g->uid,
                'slotid' => 0,
                'courseid' => 0,
                'lessondate' => $date,
                'lessonno' => 0,
            ];
        }

        // --- 3-daraja: tahlil normadan ancha past (va ishonchli)
        $rows = $DB->get_records_sql("
            SELECT $tanlov $manba $oyna
               AND f.analysestatus = 'ok' AND f.normdelta IS NOT NULL
               AND f.normdelta <= :chegara AND f.lowconfcount <= 3
          ORDER BY f.normdelta ASC",
            array_merge($umumiy, ['chegara' => self::PAST_CHEGARA]), 0, self::NAVBAT_QATOR);
        foreach ($rows as $r) {
            $items[] = self::item($r, 3, 'serious', 'tahlil normadan past',
                "xalqaro o'rtachadan " . (int) $r->normdelta . '%');
        }

        // --- 4-daraja: jurnal kech to'ldirilgan
        $kech = (int) norm::value('jurnal_kechikish_soat');
        $rows = $DB->get_records_sql("
            SELECT $tanlov $manba $oyna AND f.latehours IS NOT NULL AND f.latehours > :kech
          ORDER BY f.latehours DESC",
            array_merge($umumiy, ['kech' => $kech]), 0, self::NAVBAT_QATOR);
        foreach ($rows as $r) {
            $items[] = self::item($r, 4, 'warning', 'jurnal kech to\'ldirilgan',
                (int) $r->latehours . " soat o'tib yozilgan (norma {$kech})");
        }

        // --- 5-daraja: yozuv bor, tahlil yiqilgan.
        // BU TIZIM MUAMMOSI — o'qituvchi buning uchun javob bermasligi kerak,
        // shuning uchun alohida daraja va boshqa matn.
        $rows = $DB->get_records_sql("
            SELECT $tanlov $manba $oyna AND f.analysestatus = 'failed'
          ORDER BY s.lessondate DESC", $umumiy, 0, self::NAVBAT_QATOR);
        foreach ($rows as $r) {
            $items[] = self::item($r, 5, 'warning', 'tahlil yiqilgan (tizim)',
                "yozuv olingan, AI tahlili o'tmadi — o'qituvchining aybi emas");
        }

        return ['items' => $items, 'oyna_boshi' => $oyna_boshi, 'jami1' => (int) $jami1];
    }

    /** Navbat qatori — bitta darsga tegishli. */
    protected static function item(\stdClass $r, int $level, string $status,
                                    string $kind, string $detail): \stdClass {
        $ism = $r->firstname || $r->lastname
            ? self::ism($r->firstname, $r->lastname)
            : ($r->teachername ?: $r->teacherkey);
        return (object) [
            'level' => $level,
            'status' => $status,
            'kind' => $kind,
            'title' => trim(($r->subjectname ?: '') . ' · ' . ($r->groupname ?: ''), ' ·')
                . ' — ' . ($ism ?: "o'qituvchi tayinlanmagan"),
            'detail' => $detail,
            'userid' => (int) ($r->attributeduserid ?? 0),
            'slotid' => (int) $r->slotid,
            'courseid' => (int) ($r->courseid ?? 0),
            'lessondate' => $r->lessondate,
            'lessonno' => (int) $r->lessonno,
            'starttime' => $r->starttime,
        ];
    }

    // ------------------------------------------------------- KUN SETKASI
    /**
     * Bugungi kun setkasi: o'qituvchi x dars raqami.
     *
     * Katak qiymati — MAJBURIY uchta vazifadan nechtasi bajarilgan
     * (mavzu, yo'qlama, yozuv). Baho bu hisobga KIRMAYDI: u har darsda
     * kutilmaydi (normasi Frappe'dan keladi), shuning uchun alohida belgi
     * bilan ko'rsatiladi — aks holda baho qo'yilmagan normal dars
     * "bajarilmagan" bo'lib chiqardi.
     */
    public static function day_grid(string $date): array {
        global $DB;

        $otgan = self::otgan_sql();
        $params = ['d' => $date];
        $yoz = self::YOZILGAN;

        $rows = $DB->get_records_sql("
            SELECT s.id AS slotid, s.lessonno, s.starttime, s.endtime, s.groupname,
                   s.subjectname, s.courseid, s.lessondate, s.teacherkey,
                   f.attributeduserid, f.hastopic, f.attendancecount, f.gradecount,
                   f.studentcount, f.audiostatus, f.analysestatus, f.normdelta,
                   CASE WHEN $otgan THEN 1 ELSE 0 END AS otgan,
                   CASE WHEN f.audiostatus IN ($yoz) THEN 1 ELSE 0 END AS yozuvbor,
                   u.firstname, u.lastname, st.teachername, st.holat AS staffholat
              FROM {local_targetzenit_slot} s
              JOIN {local_targetzenit_fact} f ON f.slotid = s.id
         LEFT JOIN {user} u ON u.id = f.attributeduserid
         LEFT JOIN {local_targetzenit_staff} st
                ON st.academicyear = s.academicyear AND st.teacherkey = s.teacherkey
             WHERE s.lessondate = :d
          ORDER BY s.lessonno ASC", $params);

        $setka = [];
        $darslar = [];

        foreach ($rows as $r) {
            $uid = (int) ($r->attributeduserid ?? 0);
            // Bog'lanmagan o'qituvchi (vakansiya) ham ko'rinishi kerak —
            // uning darslari hech kimning hisobotiga tushmaydi, buni
            // direktor bilishi kerak.
            $kalit = $uid ? 'u' . $uid : 'k' . $r->teacherkey;
            if (!isset($setka[$kalit])) {
                $setka[$kalit] = (object) [
                    'userid' => $uid,
                    'ism' => ($r->firstname || $r->lastname)
                        ? self::ism($r->firstname, $r->lastname)
                        : ($r->teachername ?: $r->teacherkey),
                    'bogli' => (bool) $uid,
                    'fanlar' => [],
                    'kataklar' => [],
                ];
            }
            if ($r->subjectname) {
                $setka[$kalit]->fanlar[$r->subjectname] = true;
            }

            $bajarildi = (int) $r->hastopic
                + ((int) $r->attendancecount > 0 ? 1 : 0)
                + (int) $r->yozuvbor;

            $setka[$kalit]->kataklar[(int) $r->lessonno] = (object) [
                'slotid' => (int) $r->slotid,
                'courseid' => (int) ($r->courseid ?? 0),
                'lessondate' => $r->lessondate,
                'lessonno' => (int) $r->lessonno,
                'otgan' => (int) $r->otgan === 1,
                'bajarildi' => $bajarildi,
                'mavzu' => (int) $r->hastopic,
                'yoqlama' => (int) $r->attendancecount,
                'oquvchi' => (int) $r->studentcount,
                'baho' => (int) $r->gradecount,
                'yozuv' => (int) $r->yozuvbor,
                'tahlil' => $r->analysestatus,
                'delta' => $r->normdelta === null ? null : (int) $r->normdelta,
                'fan' => $r->subjectname,
                'guruh' => $r->groupname,
                'vaqt' => $r->starttime,
            ];
            $darslar[(int) $r->lessonno] = $r->starttime;
        }

        ksort($darslar);
        // Ism bo'yicha — reyting emas, ro'yxat
        uasort($setka, fn($a, $b) => strnatcasecmp((string) $a->ism, (string) $b->ism));

        return ['qatorlar' => $setka, 'darslar' => $darslar];
    }

    // ------------------------------------------------- MA'LUMOT SOG'LIGI
    /**
     * Panel yuqorisidagi ogohlantirishlar.
     *
     * JIM YIQILISHNI ko'rinadigan qilish uchun: bu holatlarda panel raqamlari
     * noto'g'ri bo'ladi, lekin o'zidan hech qanday xato chiqmaydi.
     */
    public static function health(string $year): array {
        global $DB;

        $out = [];

        $bayram = $DB->count_records('local_targetzenit_calendar',
            ['academicyear' => $year, 'entrytype' => 'holiday']);
        if (!$bayram) {
            $out[] = (object) [
                'status' => 'serious',
                'matn' => "O'quv kalendarida bayram kunlari yo'q. Bayram ham dars kuni "
                    . "deb hisoblanadi va o'qituvchida \"dars o'tmagan\" bo'lib ko'rinadi.",
                'amal' => "Frappe'dan kalendarni bayramlar bilan qayta yuborish kerak.",
            ];
        }

        $muammo = staff::problems($year);
        if ($muammo) {
            $dars = 0;
            $ismlar = [];
            foreach ($muammo as $m) {
                $dars += (int) $m->lessonsweek;
                $ismlar[] = $m->teachername ?: $m->teacherkey;
            }
            $out[] = (object) [
                'status' => 'warning',
                'matn' => count($muammo) . " o'qituvchi kaliti Moodle hisobiga bog'lanmagan "
                    . "— haftada {$dars} dars hech kimning hisobotiga tushmaydi: "
                    . implode(', ', $ismlar),
                'amal' => "Vakansiya bo'lsa — normal. Aks holda Frappe'da `Instructor` ga "
                    . "`Employee` bog'lab, jadvalni qayta yuborish kerak.",
            ];
        }

        if (!norm::has_any($year)) {
            $out[] = (object) [
                'status' => 'warning',
                'matn' => "Baholash normalari Frappe'dan kelmagan — zaxira qiymatlar "
                    . "ishlatilmoqda.",
                'amal' => "Frappe'da `local_targetzenit_push_norms` chaqiruvini yoqish kerak.",
            ];
        }

        return $out;
    }
}
