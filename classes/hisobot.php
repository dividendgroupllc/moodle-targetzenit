<?php
// OTA-ONA UCHUN O'QUVCHI HISOBOTI — ma'lumot yig'uvchi qatlam.
//
// NIMA UCHUN KERAK: ota-onalar majlisida bitta varaqda «farzandim qaysi
// fanda yaxshi, qaysi fanda sust?» savoliga javob kerak.
//
// QAMROV (foydalanuvchi aniqlashtirdi 2026-11-13): maktabda HOZIRCHA
// chorak bahosi ham, davomat (yo'qlama) ham YO'Q. Bor narsa — oktabrda
// o'tkazilgan TEST natijalari (`_exam_result`) va o'qituvchilarning besh
// kategoriyali sharhi (`{comments}` + `_sharh`).
//
// Shuning uchun hisobot CHORAK HISOBOTI EMAS: u «bolaning fanlar
// kesimidagi hozirgi holati». Yo'q ma'lumotni ko'rsatish uchun joy ham
// qoldirilmaydi — bo'sh ustun ota-onada «nega bo'sh?» savolini tug'diradi.
//
// SHU KLASS HECH NARSA CHIZMAYDI. U faqat bitta o'quvchi uchun to'rt
// manbadan bitta struktura yasaydi; chizish `output\hisobot_varaq` da.
// NEGA AJRATILDI: bir xil ma'lumot ikki joyda kerak bo'ladi — bitta
// o'quvchi varag'i va guruhni ketma-ket chop etish (majlis uchun 30 varaq).
// Yig'ish bilan chizish bir faylda bo'lsa guruh rejimi uni nusxalashga
// majbur qilardi.
//
// MUHIM QOIDA: bu hisobot OTA-ONAGA beriladi, ya'ni `maxfiy` sharhlar
// bu yerga HECH QACHON tushmaydi — `sharh::mine()` ni ishlatamiz, u
// so'rovning o'zida `visibility = 'ochiq'` ni talab qiladi. Maxfiy qaydni
// filtrlash CHIZISHDA emas, MANBADA bo'lishi kerak (sharh modulining
// o'z qoidasi: «blokni yashirish himoya emas»).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class hisobot {

    /** Bitta hisobotga tushadigan fanlar soni chegarasi — maket buzilmasligi uchun. */
    const MAX_FAN = 30;

    /** Guruhni ketma-ket chop etishda bir urinishda nechta o'quvchi. */
    const MAX_OQUVCHI = 60;

    /**
     * DARAJA CHEGARALARI (foiz).
     *
     * Maktabning O'Z shkalasi — test natijalari jadvalidagi
     * `Low / Middle / High` ustuni aynan shunday hisoblangan
     * (`1Quarter...Test_natijalari_sinflar.xlsx`): 80 dan yuqori — High,
     * 50-79 — Middle, 50 dan past — Low.
     *
     * Nizomning 86/66/30 bandlari BU YERDA ISHLATILMAYDI: ular chorak
     * bahosi uchun va hozir chorak bahosi yo'q. Ikki xil shkalani bir
     * varaqqa qo'yish ota-onani chalg'itadi.
     */
    const DARAJA_YUQORI = 80;
    const DARAJA_ORTA = 50;

    /** Foizdan daraja kodi. */
    public static function daraja(?float $foiz): ?string {
        if ($foiz === null) {
            return null;
        }
        if ($foiz >= self::DARAJA_YUQORI) {
            return 'yuqori';
        }
        return ($foiz >= self::DARAJA_ORTA) ? 'orta' : 'past';
    }

    // ========================================================= BITTA O'QUVCHI

    /**
     * Bitta o'quvchining hisoboti.
     *
     * @param int $studentid o'quvchining `user.id`
     * @param string $year `2026-2027`
     * @param \stdClass $term `_calendar` dagi davr qatori (name, startdate, enddate)
     */
    public static function student(int $studentid, string $year, \stdClass $term): \stdClass {
        global $DB;

        $d = (object) [
            'oquvchi' => \core_user::get_user($studentid, '*'),
            'year' => $year,
            'term' => $term,
            'guruh' => '',
            'tutor' => '',
            'fanlar' => [],
            'jami' => (object) [
                'fan' => 0,            // nechta fandan test topshirgan
                'testlar' => 0,        // nechta test
                'foiz' => null,        // umumiy natija
                'daraja' => null,
                'kuchli' => null,      // eng yaxshi fan  [fan, foiz]
                'sust' => null,        // eng past fan
                'sinfdan_yuqori' => 0, // nechta fanda sinf o'rtachasidan yuqori
                'sinf_foiz' => null,   // sinfning shu o'quvchi topshirgan testlardagi o'rtachasi
                'sharh' => 0,
            ],
        ];
        if (!$d->oquvchi) {
            return $d;
        }

        // ------------------------------------------------------------- KURSLAR
        // Faqat SHU o'quv yilining kurslari. `<yil>::<guruh>::<fan>` bo'lmagan
        // kurs (qo'lda yaratilgan) hisobotga tushmaydi — uning fani va guruhi
        // noma'lum, ota-onaga «??» ko'rsatishdan ko'ra tushmagani yaxshi.
        $kurslar = [];
        foreach (enrol_get_users_courses($studentid, true, 'id, fullname, shortname, idnumber')
                as $c) {
            $qism = explode('::', (string) $c->idnumber);
            if (count($qism) < 3 || $qism[0] !== $year) {
                continue;
            }
            $c->zguruh = $qism[1];
            $c->zfan = $qism[2];
            $kurslar[(int) $c->id] = $c;
        }
        if (!$kurslar) {
            return $d;
        }
        if (count($kurslar) > self::MAX_FAN) {
            $kurslar = array_slice($kurslar, 0, self::MAX_FAN, true);
        }
        $cids = array_keys($kurslar);

        // --------------------------------------------------------- SINF / TUTOR
        // `timetable::groups_of()` blok (daraja) guruhlarini ro'yxat OXIRIGA
        // suradi, ya'ni birinchisi — o'quvchining SINFI.
        $guruhlar = timetable::groups_of($studentid, $year);
        $d->guruh = (string) (reset($guruhlar) ?: '');
        if ($d->guruh !== '') {
            // Tutor xaritasi SO'ROVDA BIR MARTA olinadi: guruh rejimida bu
            // metod 30 marta chaqiriladi.
            static $tutormap = [];
            if (!isset($tutormap[$year])) {
                $tutormap[$year] = tutor::map($year);
            }
            $tutorid = $tutormap[$year][$d->guruh] ?? 0;
            if ($tutorid) {
                $tu = \core_user::get_user($tutorid, 'id, firstname, lastname');
                if ($tu) {
                    $d->tutor = fullname($tu);
                }
            }
        }

        // ---------------------------------------------------------- O'QITUVCHI
        $oqituvchilar = self::oqituvchilar($cids, $year, $term);

        // ------------------------------------------------------------- SHARHLAR
        // `mine()` — faqat `ochiq`, hamma fan bo'ylab, YANGIDAN eskiga.
        // `guruhla()` to'plam (batch) bo'yicha yig'adi va tartibni saqlaydi,
        // ya'ni har kursning BIRINCHI to'plami — eng yangisi.
        $sharhlar = [];
        $sharh_soni = [];
        foreach (sharh::guruhla(sharh::mine($studentid, 1000)) as $tp) {
            $cid = (int) $tp['bosh']->courseid;
            $sharh_soni[$cid] = ($sharh_soni[$cid] ?? 0) + 1;
            if (!isset($sharhlar[$cid])) {
                $sharhlar[$cid] = $tp;
            }
        }

        // --------------------------------------------------------- TEST NATIJA
        $idn = [];
        foreach ($kurslar as $cid => $c) {
            $idn[(string) $c->idnumber] = $cid;
        }
        $natijalar = [];
        $sittinglar = [];
        foreach (exam::for_student($studentid, $year, $term->name) as $r) {
            $cid = $idn[(string) $r->courseidnumber] ?? 0;
            if (!$cid) {
                continue;
            }

            // BEKOR QILINGAN GURUH — hisobotda umuman yo'q.
            //
            // Baholash sinf darajasi bo'yicha yaratiladi: «2-sinf» tanlansa
            // 2A ham, 2B ham sitting oladi. Agar fan faqat bitta sinfda
            // o'tilsa, koordinator ikkinchisini «Guruhni olib tashlash»
            // bilan bekor qiladi. `for_student()` esa SITTING bo'yicha qator
            // qaytaradi va bekor qilinganini tekshirmaydi — natijada 2A
            // varag'ida o'sha fan bo'sh baho bilan turardi (2026-11-14).
            if ((string) ($r->sitting_status ?? '') === 'bekor') {
                continue;
            }

            // BALL KIRITILMAGAN TEST HAM KO'RSATILMAYDI (talab 2026-11-14).
            //
            // Sitting mavjud bo'lsa-yu ball kiritilmagan bo'lsa, `score`
            // NULL keladi. Ota-onaga «ART — / —» qatori hech narsa demaydi,
            // aksincha «bola topshirmaganmi yoki unutilganmi?» degan savol
            // tug'diradi. Varaqda faqat HAQIQATDA bahosi bor test turadi.
            if ($r->score === null) {
                continue;
            }
            // Shu yerdan keyin `score` ALBATTA bor (yuqorida filtrlandi).
            $maks = (float) $r->maxscore;
            $r->zfoiz = $maks > 0 ? round((float) $r->score / $maks * 100, 1) : null;
            $natijalar[$cid][] = $r;
            $sittinglar[(int) $r->sittingid] = $maks;
        }
        $sinfort = self::sinf_ortacha(array_keys($sittinglar));

        // ============================================================= YIG'ISH
        $ball = 0.0;
        $maksjami = 0.0;
        $sinfball = 0.0;
        $sinfmaks = 0.0;

        foreach ($kurslar as $cid => $c) {
            $testlar = $natijalar[$cid] ?? [];

            // Fan bo'yicha yig'indi: bir fandan bir nechta test bo'lishi
            // mumkin, shuning uchun foiz BALLAR YIG'INDISIDAN hisoblanadi
            // (testlarning foizini o'rtachalash savollar sonini e'tiborsiz
            // qoldirardi — 10 savollik test 40 savollik bilan teng vaznga
            // ega bo'lib qolardi).
            $fball = 0.0;
            $fmaks = 0.0;
            $fsinf = 0.0;
            $fsinfmaks = 0.0;
            $soni = 0;
            foreach ($testlar as $r) {
                if ($r->score === null || (float) $r->maxscore <= 0) {
                    continue;
                }
                $fball += (float) $r->score;
                $fmaks += (float) $r->maxscore;
                $soni++;
                $o = $sinfort[(int) $r->sittingid] ?? null;
                if ($o !== null) {
                    $fsinf += $o['ortacha'];
                    $fsinfmaks += (float) $r->maxscore;
                }
            }

            $foiz = $fmaks > 0 ? round($fball / $fmaks * 100, 1) : null;
            $sinf = $fsinfmaks > 0 ? round($fsinf / $fsinfmaks * 100, 1) : null;

            $d->fanlar[$cid] = [
                'courseid' => $cid,
                'fan' => $c->zfan,
                'guruh' => $c->zguruh,
                'kurs' => $c->fullname,
                'oqituvchi' => $oqituvchilar[$cid] ?? '',
                'foiz' => $foiz,
                'daraja' => self::daraja($foiz),
                'ball' => $fball,
                'maks' => $fmaks,
                'testlar' => $soni,
                'sinf' => $sinf,
                'farq' => ($foiz !== null && $sinf !== null)
                    ? round($foiz - $sinf, 1) : null,
                'imtihon' => $testlar,
                'sharh' => $sharhlar[$cid] ?? null,
                'sharh_soni' => $sharh_soni[$cid] ?? 0,
            ];

            if ($foiz !== null) {
                $ball += $fball;
                $maksjami += $fmaks;
                $sinfball += $fsinf;
                $sinfmaks += $fsinfmaks;
                $d->jami->fan++;
                $d->jami->testlar += $soni;
                if ($d->fanlar[$cid]['farq'] !== null && $d->fanlar[$cid]['farq'] > 0) {
                    $d->jami->sinfdan_yuqori++;
                }
            }
            $d->jami->sharh += $sharh_soni[$cid] ?? 0;
        }

        // NATIJASI BOR FAN BIRINCHI, keyin foiz bo'yicha YUQORIDAN PASTGA.
        //
        // Tartib shu hisobotning ASOSIY savoliga javob beradi: «qaysi fanda
        // yaxshi, qaysida sust». Alifbo tartibi bu savolni yashirardi —
        // ota-ona sakkizta qatorni solishtirib chiqishi kerak bo'lardi.
        uasort($d->fanlar, function($a, $b) {
            if (($a['foiz'] === null) !== ($b['foiz'] === null)) {
                return $a['foiz'] === null ? 1 : -1;
            }
            if ($a['foiz'] !== null && $a['foiz'] != $b['foiz']) {
                return $b['foiz'] <=> $a['foiz'];
            }
            return strnatcasecmp($a['fan'], $b['fan']);
        });

        if ($maksjami > 0) {
            $d->jami->foiz = round($ball / $maksjami * 100, 1);
            $d->jami->daraja = self::daraja($d->jami->foiz);
        }
        if ($sinfmaks > 0) {
            $d->jami->sinf_foiz = round($sinfball / $sinfmaks * 100, 1);
        }

        $bor = array_values(array_filter($d->fanlar, fn($f) => $f['foiz'] !== null));
        if ($bor) {
            $d->jami->kuchli = $bor[0];
            $d->jami->sust = $bor[count($bor) - 1];
        }
        return $d;
    }

    /**
     * Sinf o'rtachasi — har bir o'tirish (sitting) bo'yicha.
     *
     * NEGA KERAK: yolg'iz «68%» ota-onaga hech narsa demaydi. «68% — sinf
     * o'rtachasi 54%» esa darhol ma'noga ega bo'ladi. Qiyos SINF bo'yicha
     * (bitta sitting = bitta guruh bitta testda), butun maktab bo'yicha
     * emas: boshqa sinf boshqa dastur bo'yicha o'qiydi.
     *
     * Bolalar REYTINGI chiqarilmaydi va o'rin raqami ko'rsatilmaydi —
     * ota-onalar majlisida bu bolani sinfdoshlari bilan taqqoslashga
     * aylanadi. Faqat o'rtacha bilan solishtirish beriladi.
     *
     * @return array [sittingid => ['ortacha' => float, 'soni' => int]]
     */
    protected static function sinf_ortacha(array $sittingids): array {
        global $DB;

        if (!$sittingids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($sittingids, SQL_PARAMS_NAMED, 's');
        $rows = $DB->get_records_sql("
            SELECT r.sittingid, AVG(r.score) AS ortacha, COUNT(1) AS soni
              FROM {local_targetzenit_exam_result} r
             WHERE r.sittingid $in AND r.score IS NOT NULL
          GROUP BY r.sittingid", $params);

        $out = [];
        foreach ($rows as $r) {
            // Bitta natija bo'lsa «sinf o'rtachasi» aslida shu bolaning
            // o'z bali bo'lib qoladi — bunday qiyos yolg'on, shuning
            // uchun ikkitadan kam bo'lsa qaytarilmaydi.
            if ((int) $r->soni >= 2) {
                $out[(int) $r->sittingid] = [
                    'ortacha' => (float) $r->ortacha,
                    'soni' => (int) $r->soni,
                ];
            }
        }
        return $out;
    }


    /**
     * Kurs -> o'qituvchi ismi.
     *
     * MANBA TARTIBI MUHIM:
     *   1) `_slot` — SHU chorakda haqiqatda dars o'tgan odam (eng ishonchli:
     *      jadval o'zgarsa slot o'tmishni muzlatib saqlaydi);
     *   2) `_timetable` — joriy reja (slot hali qurilmagan bo'lsa);
     *   3) bo'sh.
     * Sharh muallifi ataylab ISHLATILMAYDI: sharhni almashtirgan o'qituvchi
     * yozishi mumkin, u esa «dars beruvchi» degani emas. Sharh bloki muallif
     * ismini o'zi yonida ko'rsatadi.
     *
     * @return array [courseid => "Ism Familiya"]
     */
    protected static function oqituvchilar(array $cids, string $year,
                                            \stdClass $term): array {
        global $DB;

        if (!$cids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($cids, SQL_PARAMS_NAMED, 'c');
        $params += ['y' => $year, 'a' => $term->startdate, 'b' => $term->enddate];

        $out = [];
        // Eng ko'p dars o'tgan odam — bitta kursda almashtirish bo'lgan
        // bo'lsa ham asosiy o'qituvchi chiqadi.
        $rows = $DB->get_records_sql("
            SELECT " . $DB->sql_concat('s.courseid', "'-'", 's.teacheruserid') . " AS uniq,
                   s.courseid, s.teacheruserid, u.firstname, u.lastname,
                   COUNT(1) AS soni
              FROM {local_targetzenit_slot} s
              JOIN {user} u ON u.id = s.teacheruserid
             WHERE s.academicyear = :y AND s.courseid $in
               AND s.lessondate >= :a AND s.lessondate <= :b
          GROUP BY s.courseid, s.teacheruserid, u.firstname, u.lastname
          ORDER BY COUNT(1) DESC", $params);
        foreach ($rows as $r) {
            $cid = (int) $r->courseid;
            if (!isset($out[$cid])) {
                $out[$cid] = fullname($r);
            }
        }

        // Qolgani uchun REJA (jadval). Bu yerda ism matn ko'rinishida yotadi
        // (`teachername`), Moodle hisobiga bog'lanmagan bo'lishi mumkin.
        $yetmagan = array_diff($cids, array_keys($out));
        if ($yetmagan) {
            $kurs = $DB->get_records_list('course', 'id', $yetmagan, '', 'id, idnumber');
            $idnomlar = [];
            foreach ($kurs as $c) {
                if ($c->idnumber) {
                    $idnomlar[(string) $c->idnumber] = (int) $c->id;
                }
            }
            if ($idnomlar) {
                list($in2, $p2) = $DB->get_in_or_equal(array_keys($idnomlar),
                    SQL_PARAMS_NAMED, 'i');
                $p2['y'] = $year;
                $rows = $DB->get_records_sql("
                    SELECT " . $DB->sql_concat('t.courseidnumber', "'-'", 't.teacherkey')
                           . " AS uniq,
                           t.courseidnumber, t.teachername, COUNT(1) AS soni
                      FROM {local_targetzenit_timetable} t
                     WHERE t.academicyear = :y AND t.courseidnumber $in2
                       AND t.teachername IS NOT NULL AND t.teachername <> ''
                  GROUP BY t.courseidnumber, t.teacherkey, t.teachername
                  ORDER BY COUNT(1) DESC", $p2);
                foreach ($rows as $r) {
                    $cid = $idnomlar[(string) $r->courseidnumber] ?? 0;
                    if ($cid && !isset($out[$cid])) {
                        $out[$cid] = (string) $r->teachername;
                    }
                }
            }
        }
        return $out;
    }

    // ======================================================== GURUH (MAJLIS)

    /**
     * Guruhdagi o'quvchilar — majlis uchun ketma-ket chop etish.
     *
     * Guruh kursi BIR NECHTA (har fan alohida kurs), shuning uchun o'quvchilar
     * ro'yxati ularning BIRLASHMASI: bitta fandan boshqa guruhga ko'chgan
     * o'quvchi ham ro'yxatda qolishi kerak.
     *
     * @return array [userid => user] familiya bo'yicha
     */
    /**
     * Natija KIRITILGAN eng oxirgi chorak nomi (yoki null).
     *
     * NEGA KERAK: varaq standart holatda BUGUNGI chorakni ochadi. Maktab
     * imtihonni oktabrda (1-chorak) o'tkazib, ballarni noyabrda (2-chorak)
     * kiritsa, varaq bo'sh chiqadi — baho ham, diagramma ham yo'q
     * (`svg_bar()` `foiz === null` da bo'sh qaytaradi). Odam buni «hisobot
     * ishlamayapti» deb tushunadi, aslida shunchaki boshqa chorakka qarab
     * turgan bo'ladi.
     *
     * Shuning uchun bugungi chorakda natija bo'lmasa, natija BOR eng
     * oxirgi chorakka o'tamiz. Odam aniq chorak tanlasa — unga tegilmaydi.
     */
    public static function oxirgi_natijali_chorak(string $year): ?string {
        global $DB;

        $r = $DB->get_record_sql("
            SELECT e.termname, MAX(e.examdate) AS oxirgi
              FROM {local_targetzenit_exam_result} r
              JOIN {local_targetzenit_exam_sitting} s ON s.id = r.sittingid
              JOIN {local_targetzenit_exam} e ON e.id = s.examid
             WHERE e.academicyear = :yil AND r.score IS NOT NULL
          GROUP BY e.termname
          ORDER BY oxirgi DESC", ['yil' => $year], IGNORE_MULTIPLE);

        return $r ? (string) $r->termname : null;
    }

    public static function guruh_oquvchilari(string $year, string $groupkey): array {
        global $DB;

        $kurslar = $DB->get_records_select('course', $DB->sql_like('idnumber', ':p'),
            ['p' => $DB->sql_like_escape($year . '::' . $groupkey . '::') . '%'],
            '', 'id, idnumber');
        if (!$kurslar) {
            return [];
        }

        $out = [];
        foreach ($kurslar as $c) {
            foreach (lesson::students(\context_course::instance((int) $c->id)) as $u) {
                $out[(int) $u->id] = $u;
            }
        }
        uasort($out, fn($a, $b) => strnatcasecmp(
            $a->lastname . ' ' . $a->firstname, $b->lastname . ' ' . $b->firstname));

        if (count($out) > self::MAX_OQUVCHI) {
            $out = array_slice($out, 0, self::MAX_OQUVCHI, true);
        }
        return $out;
    }

    /** Shu o'quv yilidagi guruhlar: [groupkey => groupkey] — tanlagich uchun. */
    public static function guruhlar(string $year): array {
        global $DB;

        $rows = $DB->get_fieldset_select('local_targetzenit_timetable',
            'DISTINCT groupkey', 'academicyear = :y ORDER BY groupkey',
            ['y' => $year]);
        $out = [];
        foreach ($rows as $g) {
            $out[(string) $g] = (string) $g;
        }
        return $out;
    }

    // ============================================================== RUXSATLAR

    /**
     * Shu odam shu o'quvchining hisobotini ocha oladimi.
     *
     * `baholarim.php` dagi tekshiruv bilan AYNI MANTIQ: o'zi, tizim darajasida
     * ruxsati bor ma'mur, yoki shu o'quvchiga dars beradigan o'qituvchi.
     * Qo'shimcha: guruh TUTORI — majlisni aynan u o'tkazadi.
     */
    public static function korish_mumkinmi(int $studentid): bool {
        global $USER;

        if ($studentid === (int) $USER->id) {
            return true;
        }
        $ctx = \context_system::instance();
        if (has_capability('local/targetzenit:viewalltimetable', $ctx)
                || has_capability('local/targetzenit:viewteachers', $ctx)) {
            return true;
        }
        foreach (enrol_get_users_courses($studentid, true, 'id') as $c) {
            if (has_capability('local/targetzenit:teach',
                    \context_course::instance((int) $c->id))) {
                return true;
            }
        }
        // Tutor: o'z guruhidagi har bir o'quvchi.
        $year = timetable::current_year();
        if ($year) {
            $map = tutor::map($year);
            foreach (timetable::groups_of($studentid, $year) as $g) {
                if ((int) ($map[$g] ?? 0) === (int) $USER->id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Shu odam umuman biror guruhning to'plam hisobotini ocha oladimi —
     * MENYU havolasini ko'rsatish uchun.
     *
     * NEGA ALOHIDA: menyu bandi «qaysi guruh» ni bilmaydi, u parametrsiz
     * havola beradi (sahifa tanlagich chiqaradi). Shart esa sahifadagi
     * tekshiruv bilan bir xil bo'lishi SHART, aks holda havola bosi berk
     * yo'lga olib borardi.
     */
    public static function tutormi(int $userid): bool {
        $ctx = \context_system::instance();
        if (has_capability('local/targetzenit:viewalltimetable', $ctx)
                || has_capability('local/targetzenit:viewteachers', $ctx)) {
            return true;
        }
        $year = timetable::current_year();
        if (!$year) {
            return false;
        }
        return in_array($userid, array_map('intval', tutor::map($year)), true);
    }

    /** Guruhni to'liq chop etish ruxsati: ma'mur, zavuch/direktor yoki shu guruh tutori. */
    public static function guruh_mumkinmi(string $year, string $groupkey): bool {
        global $USER;

        $ctx = \context_system::instance();
        if (has_capability('local/targetzenit:viewalltimetable', $ctx)
                || has_capability('local/targetzenit:viewteachers', $ctx)) {
            return true;
        }
        return (int) (tutor::map($year)[$groupkey] ?? 0) === (int) $USER->id;
    }
}
