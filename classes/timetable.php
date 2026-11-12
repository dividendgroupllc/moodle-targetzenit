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

    /**
     * Hafta kuni nomi foydalanuvchining tilida.
     *
     * `DAYS` dagi qiymatlar o'zbekcha qotib yozilgan va ular faqat ichki
     * tartib uchun. Ko'rsatish uchun Moodle yadrosining `calendar`
     * satrlari olinadi — ingliz/rus/o'zbek uchun tayyor, qayta tarjima
     * yozilmaydi.
     */
    public static function kun_nomi(int $dayno): string {
        static $kalit = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
            4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];
        return isset($kalit[$dayno])
            ? get_string($kalit[$dayno], 'calendar')
            : (self::DAYS[$dayno] ?? (string) $dayno);
    }

    /**
     * Foydalanuvchining BUGUNI, YYYY-MM-DD.
     *
     * NEGA `date('Y-m-d')` EMAS: PHP ning `date()` i SERVER mintaqasida
     * ishlaydi. 2026-11-10 da o'lchangan: Moodle sayt mintaqasi
     * `Europe/London`, serverning OS vaqti UTC, maktab esa `Asia/Tashkent`
     * (+5). Ya'ni mahalliy 00:00–05:00 orasida sahifa "bugun" deb OLDINGI
     * kunni ko'rsatardi, dushanba ertalab esa butun hafta avvalgi haftaga
     * siljib ketardi. Chet ellik o'qituvchilarda profil mintaqasi boshqa
     * bo'lsa farq yana boshqacha.
     *
     * `usergetdate()` — Moodle ning standart yo'li: foydalanuvchi
     * profilidagi mintaqa, u `99` bo'lsa sayt sozlamasi.
     *
     * DIQQAT: sayt mintaqasini `Asia/Tashkent` ga qo'yish ham kerak
     * (Site administration -> Location -> Location settings). Kod to'g'ri
     * mintaqani SO'RAYDI, lekin qaysi mintaqa ekanini sozlama aytadi.
     */
    public static function bugun(): string {
        $d = usergetdate(time());
        return sprintf('%04d-%02d-%02d', $d['year'], $d['mon'], $d['mday']);
    }

    /** Foydalanuvchining bugungi hafta kuni: 1=Dushanba ... 7=Yakshanba. */
    public static function bugun_dayno(): int {
        $d = usergetdate(time());
        // `wday`: 0=Yakshanba ... 6=Shanba -> ISO: 1=Dushanba ... 7=Yakshanba
        return (int) $d['wday'] === 0 ? 7 : (int) $d['wday'];
    }

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
            return ['ok' => false,
                'reason' => get_string('tt_weekend', 'local_targetzenit')];
        }
        if (isset($holidays[$date])) {
            return ['ok' => false, 'reason' => $holidays[$date]];
        }
        foreach ($terms as $t) {
            if ($date >= $t->startdate && $date <= $t->enddate) {
                return ['ok' => true, 'reason' => $t->name];
            }
        }
        return ['ok' => false,
            'reason' => get_string('tt_outofterm', 'local_targetzenit')];
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

    /**
     * Guruhning haftalik darslari.
     *
     * NEGA SHUNCHAKI `groupkey = :g` EMAS: qo'shma va daraja darslarida
     * `groupkey` — BLOK guruhi (masalan «ENGLISH 8A+8B+9A+9B (qo'shma)»),
     * sinf emas. Aniq tenglik bilan qidirilganda blok darslari hech qaysi
     * sinf jadvaliga tushmaydi: 2026-11-10 da 8A dushanbasida manbada 10
     * dars bor edi, sahifa 3 tasini ko'rsatdi.
     *
     * Shuning uchun `memberclasses` ham tekshiriladi — dars tegishli
     * sinflar `|sinf|sinf|` ko'rinishida saqlanadi. Ajratgichlar tufayli
     * `LIKE '%|8 A(full)|%'` faqat TO'LIQ nomni ushlaydi.
     *
     * `groupkey` tengligi saqlanib qoladi, chunki:
     *   - blok guruhini ATAYLAB tanlash mumkin (tanlagichda turadi);
     *   - migratsiyadan keyin, birinchi push'gacha `memberclasses` bo'sh.
     */
    public static function for_group(string $year, string $group): array {
        global $DB;
        return $DB->get_records_select(
            'local_targetzenit_timetable',
            'academicyear = :y AND (groupkey = :g OR ' . $DB->sql_like('memberclasses', ':a') . ')',
            ['y' => $year, 'g' => $group, 'a' => '%|' . $DB->sql_like_escape($group) . '|%'],
            'dayno ASC, lessonno ASC'
        );
    }

    /**
     * Maktabdagi SINFLAR ro'yxati, tabiiy tartibda (1A, 1B, 2A … 11B).
     *
     * Manba — `memberclasses`: har dars qaysi sinflarga tegishli ekani shunda
     * turadi (`|8 A(full)|8 B(hybrid)|`). `groupkey` dan olish mumkin emas,
     * chunki blok darsida u blok nomi bo'ladi.
     *
     * Tartib ARALASH SATR sifatida emas, raqam+harf bo'yicha: aks holda
     * «10 A(full)» «2 A(full)» dan oldin kelib qolardi.
     */
    public static function sinflar(string $year): array {
        global $DB;
        $rows = $DB->get_fieldset_sql(
            'SELECT DISTINCT memberclasses FROM {local_targetzenit_timetable}
              WHERE academicyear = :y AND memberclasses IS NOT NULL AND memberclasses <> :bosh',
            ['y' => $year, 'bosh' => '']
        );
        $toplam = [];
        foreach ($rows as $mc) {
            foreach (explode('|', (string) $mc) as $s) {
                $s = trim($s);
                if ($s !== '') {
                    $toplam[$s] = true;
                }
            }
        }
        $out = array_keys($toplam);
        usort($out, function ($a, $b) {
            $pa = preg_match('/^\s*(\d+)\s*([A-Za-z])/', $a, $ma) ? [(int) $ma[1], $ma[2]] : [999, $a];
            $pb = preg_match('/^\s*(\d+)\s*([A-Za-z])/', $b, $mb) ? [(int) $mb[1], $mb[2]] : [999, $b];
            return $pa <=> $pb;
        });
        return $out;
    }

    /**
     * Bir kunlik panjara: "<sinf>|<dars №>" -> shu katakdagi darslar.
     *
     * Bitta katakda bir nechta dars bo'lishi MUMKIN va bu xato emas: sinf
     * daraja guruhlariga bo'linsa (ENGLISH High/Middle/Low) har daraja
     * alohida qator bo'ladi.
     */
    public static function panjara(string $year, int $dayno): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_timetable',
            ['academicyear' => $year, 'dayno' => $dayno], 'lessonno ASC, groupname ASC');
        $out = [];
        foreach ($rows as $r) {
            $sinflar = array_filter(array_map('trim', explode('|', (string) $r->memberclasses)));
            if (!$sinflar) {
                // `memberclasses` bo'sh (eski push) — hech bo'lmasa guruhning
                // o'zi ustun bo'lib turadi, dars yo'qolib ketmasin.
                $sinflar = [$r->groupkey];
            }
            foreach ($sinflar as $s) {
                $out[$s . '|' . (int) $r->lessonno][] = $r;
            }
        }
        return $out;
    }

    /**
     * Blok (qo'shma/daraja) guruhlarining kalitlari.
     *
     * Blok darsi — `memberclasses` da o'z `groupkey` idan boshqa sinflar
     * turgan qator. Sinf guruhida esa `memberclasses` o'zidan iborat
     * (`|8 A(full)|`), ya'ni ikkisini ishonchli ajratish mumkin.
     *
     * Kerak: `groups_of()` o'quvchiga standart guruhni tanlaganda SINF ni
     * olishi kerak — blokni olsa, u o'z sinfining boshqa darslarini
     * ko'rmaydi.
     */
    public static function blok_groupkeys(string $year): array {
        global $DB;
        $rows = $DB->get_records_sql(
            'SELECT DISTINCT groupkey, memberclasses FROM {local_targetzenit_timetable}
              WHERE academicyear = :y',
            ['y' => $year]
        );
        $out = [];
        foreach ($rows as $r) {
            if ((string) $r->memberclasses !== '' && $r->memberclasses !== '|' . $r->groupkey . '|') {
                $out[$r->groupkey] = true;
            }
        }
        return $out;
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
        // Blok kurslariga ham yozilgan bo'lishi mumkin (daraja guruhi ham
        // kurs). Standart ko'rinish SINF jadvali bo'lishi kerak, shuning
        // uchun blok guruhlari ro'yxat OXIRIGA suriladi: `index.php`
        // birinchisini oladi.
        $bloklar = self::blok_groupkeys($year);
        $sinf = [];
        $blok = [];
        foreach ($rows as $idn) {
            $qism = explode('::', $idn);
            if (count($qism) < 3) {
                continue;
            }
            if (isset($bloklar[$qism[1]])) {
                $blok[$qism[1]] = $qism[1];
            } else {
                $sinf[$qism[1]] = $qism[1];
            }
        }
        return $sinf + $blok;
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
