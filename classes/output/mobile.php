<?php
// Mobil ilova uchun ko'rinish qatlami.
//
// Har metod ilovaga TAYYOR HTML bo'lagini qaytaradi; ilova uni o'z
// komponentlari bilan chizadi. Qaytish shakli qat'iy:
//   templates  — [['id' => 'main', 'html' => ...]]
//   javascript — ilovada ishlaydigan JS (hozircha kerak emas)
//   otherdata  — shablondagi ngModel uchun boshlang'ich qiymatlar
//
// QOIDA: ma'lumot mantig'i BU YERDA yozilmaydi. Metodlar `classes/*.php`
// dagi o'sha funksiyalarni chaqiradi (veb sahifalar ham shulardan oziqlanadi).
// Yangi SQL yozilsa veb va ilova sonlari bir-biridan uziladi — bu turdagi
// nosozlikni topish eng qiyini.

namespace local_targetzenit\output;

use local_targetzenit\journal;
use local_targetzenit\lesson;
use local_targetzenit\payroll;
use local_targetzenit\timetable;

defined('MOODLE_INTERNAL') || die();

class mobile {

    /**
     * Asosiy panel — 0-bosqich.
     *
     * Hozircha faqat bog'lanish ishlayotganini va qaysi panellar kelishini
     * ko'rsatadi. Maqsad: ilova qatlamining o'zini eng kichik kod bilan
     * tasdiqlash. Sozlama (enablemobilewebservice, kesh, servis) bilan
     * muammo bo'lsa — shu yerda chiqadi, murakkab shablon ichida emas.
     *
     * @param array $args ilova yuboradigan kontekst (userid, appversioncode, ...)
     */
    public static function main(array $args): array {
        global $OUTPUT, $USER;

        // Web-servis chaqiruvi allaqachon autentifikatsiyadan o'tgan, ammo
        // kontekstni tasdiqlash kerak: metod to'g'ridan-to'g'ri ham
        // chaqirilishi mumkin.
        $context = \context_system::instance();
        self::tekshir($context);

        $ref = payroll::ref_for((int) $USER->id);

        $data = [
            'ism' => fullname($USER),
            'bogli' => $ref !== '',
            'oylik_bor' => payroll::has_any((int) $USER->id),
            'jadval_bor' => timetable::current_year() !== '',
            'wwwroot' => (new \moodle_url('/local/targetzenit/index.php'))->out(false),
            'oylik_url' => (new \moodle_url('/local/targetzenit/oylik.php'))->out(false),
            'baho_url' => (new \moodle_url('/local/targetzenit/baholarim.php'))->out(false),
        ];

        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template('local_targetzenit/mobile_main', $data),
            ]],
            'javascript' => '',
            'otherdata' => [],
            'files' => [],
        ];
    }

    /**
     * Dars jadvali — 1-bosqich.
     *
     * Joriy haftaning kun bo'yicha ro'yxati. O'qituvchiga O'Z darslari,
     * o'quvchiga o'z sinfining darslari.
     *
     * "Umumiy jadval" ATAYLAB yo'q: 934 qator telefonda ishlamaydi, u veb'da
     * qoladi. Kun tanlash ham yo'q — butun hafta bir ro'yxatda, bugungi kun
     * belgilangan holda. Telefonda bu bosishdan qulayroq.
     */
    public static function timetable_view(array $args): array {
        global $OUTPUT, $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['yil' => $year, 'bor' => false, 'kunlar' => [], 'xabar' => ''];

        if ($year === '') {
            $data['xabar'] = get_string('timetable_empty', 'local_targetzenit');
            return self::javob('mobile_timetable', $data);
        }

        // Kim sifatida ko'rsatiladi: o'qituvchi bo'lsa o'z darslari, aks holda
        // o'z sinfiniki. Mantiq veb sahifadagi (`index.php`) bilan bir xil.
        $teacherkey = timetable::teacher_key_of($USER, $year);
        $oqituvchi = $teacherkey !== '';
        if ($oqituvchi) {
            $rows = timetable::for_teacher($year, $teacherkey);
        } else {
            $guruhlar = timetable::groups_of((int) $USER->id, $year);
            $guruh = $guruhlar ? array_key_first($guruhlar) : '';
            $rows = $guruh ? timetable::for_group($year, $guruh) : [];
        }

        if (!$rows) {
            $data['xabar'] = $oqituvchi
                ? get_string('timetable_empty', 'local_targetzenit')
                : get_string('tt_noclass', 'local_targetzenit');
            return self::javob('mobile_timetable', $data);
        }

        $terms = timetable::terms($year);
        $holidays = timetable::holidays($year);
        $hafta = timetable::week_dates(date('Y-m-d'));
        $bugun = date('Y-m-d');

        foreach (array_keys(timetable::DAYS) as $dayno) {
            $sana = $hafta[$dayno];
            $holat = timetable::school_day($sana, $terms, $holidays);

            $darslar = [];
            foreach ($rows as $r) {
                if ((int) $r->dayno !== $dayno) {
                    continue;
                }
                $vaqt = trim((string) $r->starttime);
                if (!empty($r->endtime)) {
                    $vaqt .= ' – ' . $r->endtime;
                }
                $darslar[] = [
                    'lessonno' => (int) $r->lessonno,
                    'vaqt' => $vaqt,
                    'fan' => format_string($r->subjectname),
                    'xona' => format_string((string) $r->room),
                    // O'qituvchi qaysi SINFGA kirishini, o'quvchi esa KIM
                    // o'tishini bilishi kerak — ehtiyoj teskari.
                    'kim' => format_string((string)
                        ($oqituvchi ? $r->groupname : $r->teachername)),
                ];
            }

            $data['kunlar'][] = [
                // Hafta kuni nomi yadrodan olinadi — uch til uchun tayyor.
                'nomi' => get_string(strtolower(date('l', strtotime($sana))), 'calendar'),
                'sana' => userdate(strtotime($sana), get_string('strftimedateshort')),
                'bugun' => ($sana === $bugun),
                'yopiq' => !$holat['ok'],
                'sabab' => $holat['reason'],
                'darslar' => $darslar,
                'bor' => (bool) $darslar,
            ];
        }
        $data['bor'] = true;

        return self::javob('mobile_timetable', $data);
    }

    /**
     * Bugungi darslar ro'yxati — kokpitga kirish nuqtasi.
     *
     * O'qituvchi dars paytida telefonni ochadi va darhol o'z darsini topishi
     * kerak. Shuning uchun hafta emas, FAQAT bugun va faqat o'ziniki.
     */
    public static function lesson_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['bor' => false, 'xabar' => '', 'darslar' => [],
                 'sana' => userdate(time(), get_string('strftimedaydate'))];

        $teacherkey = $year === '' ? '' : timetable::teacher_key_of($USER, $year);
        if ($teacherkey === '') {
            $data['xabar'] = get_string('app_nolessontoday', 'local_targetzenit');
            return self::javob('mobile_lessons', $data);
        }

        $bugun = date('Y-m-d');
        $dayno = (int) date('N');
        $rows = timetable::for_teacher($year, $teacherkey);
        $kurslar = timetable::course_ids(array_map(
            fn($r) => $r->courseidnumber, $rows));

        foreach ($rows as $r) {
            if ((int) $r->dayno !== $dayno) {
                continue;
            }
            $cid = $kurslar[$r->courseidnumber] ?? 0;
            if (!$cid) {
                continue;             // kursi topilmagan dars — ochib bo'lmaydi
            }
            $vaqt = trim((string) $r->starttime);
            if (!empty($r->endtime)) {
                $vaqt .= ' – ' . $r->endtime;
            }
            $data['darslar'][] = [
                'courseid' => $cid,
                'date' => $bugun,
                'lessonno' => (int) $r->lessonno,
                'vaqt' => $vaqt,
                'fan' => format_string($r->subjectname),
                'guruh' => format_string((string) $r->groupname),
                'xona' => format_string((string) $r->room),
            ];
        }

        if (!$data['darslar']) {
            $data['xabar'] = get_string('app_nolessontoday', 'local_targetzenit');
        } else {
            $data['bor'] = true;
        }
        return self::javob('mobile_lessons', $data);
    }

    /**
     * Dars kokpiti — o'quvchilar ro'yxati, yo'qlama va baho.
     *
     * YOZISH shablon ichidagi `core-site-plugins-call-ws` tugmalari orqali
     * `local_targetzenit_mobile_save_record` ga boradi. Yo'qlama bitta
     * bosishda saqlanadi (dars paytida eng tez-tez qilinadigan amal), baho
     * esa kiritilib, yonidagi tugma bilan.
     */
    public static function lesson_cockpit(array $args): array {
        global $USER;

        $courseid = (int) ($args['courseid'] ?? 0);
        $sana = (string) ($args['date'] ?? date('Y-m-d'));
        $lessonno = (int) ($args['lessonno'] ?? 1);

        $course = get_course($courseid);
        $context = \context_course::instance($courseid);
        self::tekshir($context);

        $data = [
            'kurs' => format_string($course->fullname),
            'sana' => userdate(strtotime($sana), get_string('strftimedaydate')),
            'lessonno' => $lessonno,
            'maxgrade' => lesson::MAX_GRADE,
            'ruxsat' => has_capability('local/targetzenit:teach', $context),
            'oquvchilar' => [],
            'xabar' => '',
        ];

        if (!$data['ruxsat']) {
            $data['xabar'] = get_string('app_noteach', 'local_targetzenit');
            return self::javob('mobile_cockpit', $data);
        }

        // Dars yozuvi shu yerda yaratiladi: o'qituvchi kokpitni ochgan
        // paytda mavjud bo'lishi kerak, aks holda birinchi saqlashda
        // `lessonid` bo'lmaydi.
        $dars = lesson::get_or_create($courseid, $sana, $lessonno, (int) $USER->id);
        $data['lessonid'] = (int) $dars->id;
        $data['mavzu'] = (string) $dars->topic;

        $oquvchilar = lesson::students($context);
        if (!$oquvchilar) {
            $data['xabar'] = get_string('app_nostudents', 'local_targetzenit');
            return self::javob('mobile_cockpit', $data);
        }

        $yozuvlar = lesson::records((int) $dars->id);
        $otherdata = [];
        foreach ($oquvchilar as $st) {
            $rec = $yozuvlar[$st->id] ?? null;
            $holat = $rec->attendance ?? 'present';
            $baho = ($rec && $rec->grade !== null) ? (string) $rec->grade : '';

            $data['oquvchilar'][] = [
                'userid' => (int) $st->id,
                'ism' => fullname($st),
                'holat' => $holat,
                'baho' => $baho,
                // Shablonda `{{# keldi }}` kabi ishlatish uchun — mustache'da
                // tenglikni tekshirib bo'lmaydi.
                'keldi' => $holat === 'present',
                'kelmadi' => $holat === 'absent',
                'kechikdi' => $holat === 'late',
                'sababli' => $holat === 'excused',
            ];
            // Ilova input qiymatlarini `CONTENT_OTHERDATA` dan oladi.
            $otherdata['gr_' . $st->id] = $baho;
        }

        return self::javob('mobile_cockpit', $data, $otherdata);
    }

    /**
     * Choraklik baholar — o'qituvchi uchun umumiy jadval.
     *
     * Qatorlar — o'quvchilar, ustunlar — choraklar. ATAYLAB shunday:
     * jurnalning to'liq setkasi (o'quvchi x dars) telefonga sig'maydi,
     * chorak o'rtachalari esa 4 ustun bo'lib sig'adi va o'qituvchiga aynan
     * shu manzara kerak — kim orqada qolyapti.
     *
     * Sahifadan sahifaga o'tish YO'Q: o'qituvchining hamma kursi bitta
     * ro'yxatda. Navigatsiya direktivasiga tayanmaslik ataylab — u ilova
     * versiyasiga bog'liq.
     */
    public static function grades_view(array $args): array {
        global $DB, $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['bor' => false, 'xabar' => '', 'choraklar' => [], 'kurslar' => []];

        $teacherkey = $year === '' ? '' : timetable::teacher_key_of($USER, $year);
        if ($teacherkey === '') {
            $data['xabar'] = get_string('app_nocourses', 'local_targetzenit');
            return self::javob('mobile_grades', $data);
        }

        $terms = array_values(timetable::terms($year));
        if (!$terms) {
            $data['xabar'] = get_string('mg_noterms', 'local_targetzenit');
            return self::javob('mobile_grades', $data);
        }
        foreach ($terms as $t) {
            $data['choraklar'][] = ['nomi' => format_string($t->name)];
        }

        // O'qituvchining kurslari — jadvaldan, takrorsiz.
        $rows = timetable::for_teacher($year, $teacherkey);
        $idnumbers = array_unique(array_map(fn($r) => $r->courseidnumber, $rows));
        $kurslar = timetable::course_ids($idnumbers);

        foreach ($kurslar as $idnumber => $courseid) {
            $cc = \context_course::instance((int) $courseid, IGNORE_MISSING);
            if (!$cc || !has_capability('local/targetzenit:teach', $cc)) {
                continue;
            }
            $oquvchilar = lesson::students($cc);
            if (!$oquvchilar) {
                continue;
            }

            // Shu kursning butun yildagi darslari — bir marta o'qiladi,
            // keyin chorak bo'yicha ajratiladi.
            $darslar = $DB->get_records('local_targetzenit_lesson',
                ['courseid' => $courseid], '', 'id, lessondate');
            if (!$darslar) {
                continue;
            }
            $kataklar = journal::cells(array_keys($darslar));

            $qatorlar = [];
            foreach ($oquvchilar as $st) {
                $uzani = $kataklar[(int) $st->id] ?? [];
                if (!$uzani) {
                    continue;        // bu o'quvchida umuman yozuv yo'q
                }

                $hujayralar = [];
                $jami_baho = 0;
                $jami_yoq = 0;
                foreach ($terms as $t) {
                    $chorak = [];
                    foreach ($uzani as $lessonid => $rec) {
                        $sana = $darslar[$lessonid]->lessondate ?? '';
                        if ($sana < $t->startdate || $sana > $t->enddate) {
                            continue;
                        }
                        $chorak[] = $rec;
                        if ($rec->grade !== null && $rec->grade !== '') {
                            $jami_baho++;
                        }
                        if ($rec->attendance === 'absent') {
                            $jami_yoq++;
                        }
                    }
                    $ortacha = lesson::average($chorak);
                    $hujayralar[] = [
                        'qiymat' => $ortacha === null ? '—' : (string) $ortacha,
                        'bor' => $ortacha !== null,
                        // Past baho ko'zga tashlanishi kerak — o'qituvchi
                        // aynan shuni qidiradi.
                        'past' => $ortacha !== null && $ortacha < 6,
                    ];
                }

                $qatorlar[] = [
                    'ism' => fullname($st),
                    'hujayralar' => $hujayralar,
                    'baho_soni' => get_string('app_gradecount', 'local_targetzenit',
                        $jami_baho),
                    'yoq_soni' => $jami_yoq
                        ? get_string('app_absences', 'local_targetzenit', $jami_yoq)
                        : '',
                ];
            }

            $data['kurslar'][] = [
                'nomi' => format_string($DB->get_field('course', 'fullname',
                    ['id' => $courseid])),
                'bor' => (bool) $qatorlar,
                'oquvchilar' => $qatorlar,
            ];
        }

        if (!$data['kurslar']) {
            $data['xabar'] = get_string('app_nocourses', 'local_targetzenit');
        } else {
            $data['bor'] = true;
        }
        return self::javob('mobile_grades', $data);
    }

    /**
     * Mening oyligim — veb sahifasining (`oylik.php`) ilovadagi aksi.
     *
     * TUZILISH VEB BILAN BIR XIL va tartibi ham o'sha:
     *   1) to'rtta plita: qo'lga tegadigan, ishlagan, bonus, kelgan kun
     *   2) hisob zanjiri (oylik ÷ norma = kun narxi × ishlangan + bonus
     *      = hisoblandi − ushlanmalar = qo'lga)
     *   3) kunma-kun davomat
     *   4) oylar kesimida hisob-kitob
     *
     * Oy TANLOVI yo'q: eng yangi oy ko'rsatiladi, tarix esa 4-jadvalda
     * baribir bor. Tanlov qo'yilsa sahifadan sahifaga o'tish kerak bo'lardi.
     */
    public static function payroll_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $data = ['bor' => false, 'xabar' => '', 'ochiq' => false];
        $ref = payroll::ref_for((int) $USER->id);
        if ($ref === '') {
            $data['xabar'] = get_string('pr_noidnumber', 'local_targetzenit');
            return self::javob('mobile_payroll', $data);
        }

        $rec = payroll::month($ref);
        if (!$rec) {
            $data['xabar'] = get_string('pr_nodata', 'local_targetzenit');
            return self::javob('mobile_payroll', $data);
        }

        $soatbay = $rec->tolovturi === 'soat';
        $birlik = get_string($soatbay ? 'pr_unit_hour' : 'pr_unit_day',
            'local_targetzenit');
        $kunbirlik = get_string('pr_unit_day', 'local_targetzenit');
        $pul = fn($x) => payroll::pul($x, $rec->valyuta);
        $t = fn($k, $a = null) => get_string($k, 'local_targetzenit', $a);

        $data['bor'] = true;
        $data['oy'] = payroll::oy_yorlig(sprintf('%04d-%02d', $rec->yil, $rec->oy));
        $data['ochiq'] = $rec->holat !== 'Yopiq';
        $data['ochiq_xabar'] = $t('pr_open_notice', userdate((int) $rec->timemodified));

        // 1) Plitalar — veb bilan bir xil tartibda.
        $data['plitalar'] = [
            ['yorliq' => $t('pr_takehome'), 'qiymat' => $pul($rec->jami),
             'izoh' => '', 'asosiy' => true],
            ['yorliq' => $t($soatbay ? 'pr_workedhours' : 'pr_workeddays'),
             'qiymat' => (float) $rec->ishlagan . ' ' . $birlik,
             'izoh' => $soatbay ? '' : $t('pr_ofnorm', (int) $rec->normakun),
             'asosiy' => false],
            ['yorliq' => $t('pr_bonus'), 'qiymat' => $pul($rec->bonus),
             'izoh' => '', 'asosiy' => false],
            ['yorliq' => $t('pr_attended'),
             'qiymat' => (int) $rec->kelgankun . ' ' . $kunbirlik,
             'izoh' => '', 'asosiy' => false],
        ];

        // 2) Hisob zanjiri — operatorlar Frappe hisobi bilan bir xil.
        if ($soatbay) {
            $qadam = [
                ['', $t('pr_hourrate'), $pul($rec->oylik), ''],
                ['×', $t('pr_workedhours'), (float) $rec->ishlagan . ' ' . $birlik, ''],
                ['+', $t('pr_bonus_penalty'), $pul($rec->bonus), ''],
                ['=', $t('pr_gross'), $pul($rec->hisob), 'res'],
            ];
        } else {
            $qadam = [
                ['', $t('pr_salary'), $pul($rec->oylik), ''],
                ['÷', $t('pr_normdays'), (int) $rec->normakun . ' ' . $birlik, ''],
                ['=', $t('pr_dayrate'), $pul($rec->kunliknarx), 'res'],
                ['×', $t('pr_workeddays'), (float) $rec->ishlagan . ' ' . $birlik, ''],
                ['+', $t('pr_bonus_penalty'), $pul($rec->bonus), ''],
                ['=', $t('pr_gross'), $pul($rec->hisob), 'res'],
            ];
        }
        $qadam[] = ['−', $t('pr_tax'), $pul($rec->nalog), 'neg'];
        $qadam[] = ['−', $t('pr_meals'), $pul($rec->pitaniya), 'neg'];
        $qadam[] = ['=', $t('pr_takehome'), $pul($rec->jami), 'final'];

        $data['qadamlar'] = array_map(fn($q) => [
            'op' => $q[0], 'op_bor' => $q[0] !== '',
            'yorliq' => $q[1], 'qiymat' => $q[2], 'turi' => $q[3],
        ], $qadam);

        // 3) Kunma-kun davomat — gorizontal, veb bilan bir xil.
        $kunlar = payroll::days($rec);
        $kirish_bor = false;
        $chiqish_bor = false;
        foreach ($kunlar as $k) {
            if (trim((string) ($k['kirish'] ?? '')) !== '') {
                $kirish_bor = true;
            }
            if (trim((string) ($k['chiqish'] ?? '')) !== '') {
                $chiqish_bor = true;
            }
        }
        $data['kunlar_bor'] = (bool) $kunlar;
        $data['kirish_bor'] = $kirish_bor;
        $data['chiqish_bor'] = $chiqish_bor;
        $data['kun_sarlavha'] = [];
        $data['kun_koef'] = [];
        $data['kun_kirish'] = [];
        $data['kun_chiqish'] = [];
        $data['koef_yorliq'] = $t($soatbay ? 'pr_hours' : 'pr_koef');
        foreach ($kunlar as $k) {
            $kun = (int) ($k['kun'] ?? 0);
            $vaqt = make_timestamp((int) $rec->yil, (int) $rec->oy, $kun ?: 1);
            $data['kun_sarlavha'][] = [
                'kun' => $kun,
                'hafta' => userdate($vaqt, '%a'),
                'dam' => in_array((int) date('w', $vaqt), [0, 6], true),
            ];
            $koef = $k['koef'] ?? null;
            $data['kun_koef'][] = [
                'matn' => $koef === null ? '—' : (string) (float) $koef,
                'bor' => $koef !== null && (float) $koef > 0,
                'yoq' => $koef !== null && (float) $koef <= 0,
            ];
            $data['kun_kirish'][] = ['matn' => ($k['kirish'] ?? '') ?: '·'];
            $data['kun_chiqish'][] = ['matn' => ($k['chiqish'] ?? '') ?: '·'];
        }

        // 4) Oylar kesimida hisob-kitob.
        $belgilar = ['paid' => 'st_s_paid', 'over' => 'st_s_over',
            'partial' => 'st_s_partial', 'unpaid' => 'st_s_unpaid',
            'advance' => 'st_s_advance'];
        $data['hisob'] = [];
        foreach (payroll::settle($ref) as $m) {
            $data['hisob'][] = [
                'oy' => payroll::oy_yorlig($m->oy),
                'valyuta' => $m->valyuta,
                'oylik' => $m->oylik > 0.5 ? payroll::pul($m->oylik, $m->valyuta) : '—',
                'nach' => payroll::pul($m->nach, $m->valyuta),
                'paid' => payroll::pul($m->paid, $m->valyuta),
                'avans' => $m->advance > 0.5
                    ? $t('st_unlinked', payroll::pul($m->advance, $m->valyuta)) : '',
                'qoldiq' => $m->debt > 0.5
                    ? payroll::pul($m->debt, $m->valyuta)
                    : ($m->debt < -0.5 ? '+' . payroll::pul(-$m->debt, $m->valyuta) : '0'),
                'qarz' => $m->debt > 0.5,
                'ortiq' => $m->debt < -0.5,
                'holat' => $t($belgilar[payroll::holat($m)]),
                'yaxshi' => in_array(payroll::holat($m), ['paid', 'over', 'advance'], true),
            ];
        }
        $data['hisob_bor'] = (bool) $data['hisob'];

        return self::javob('mobile_payroll', $data);
    }

    /** Chorak jurnalida bitta kursda ko'rsatiladigan eng ko'p dars soni. */
    const JURNAL_DARS = 20;

    /**
     * Chorak jurnali — butun sinf bitta setkada.
     *
     * NEGA KERAK: Moodle ilovasida o'qituvchi uchun "Grader report" YO'Q —
     * ilova baholarni faqat foydalanuvchining O'Z baholari sifatida
     * ko'rsatadi. Butun sinfni bir ekranda ko'rish faqat shu yerda mumkin.
     *
     * Setka yon tomonga suriladi. Darslar soni {@see self::JURNAL_DARS} bilan
     * cheklangan (eng YANGILARI): chorak oxirida 60 ta ustun bo'lib ketadi va
     * telefonda u ishlamaydi. To'liq jurnal veb'da qoladi.
     */
    public static function journal_view(array $args): array {
        global $DB, $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['bor' => false, 'xabar' => '', 'chorak' => '', 'kurslar' => [],
                 'cheklov' => ''];

        $teacherkey = $year === '' ? '' : timetable::teacher_key_of($USER, $year);
        if ($teacherkey === '') {
            $data['xabar'] = get_string('app_nocourses', 'local_targetzenit');
            return self::javob('mobile_journal', $data);
        }

        // Joriy chorak — bugungi sana qaysi oraliqqa tushsa.
        $bugun = date('Y-m-d');
        $chorak = null;
        foreach (timetable::terms($year) as $t) {
            if ($bugun >= $t->startdate && $bugun <= $t->enddate) {
                $chorak = $t;
                break;
            }
        }
        if (!$chorak) {
            $data['xabar'] = get_string('app_noterm_now', 'local_targetzenit');
            return self::javob('mobile_journal', $data);
        }
        $data['chorak'] = get_string('app_journalterm', 'local_targetzenit',
            format_string($chorak->name));

        $rows = timetable::for_teacher($year, $teacherkey);
        $kurslar = timetable::course_ids(
            array_unique(array_map(fn($r) => $r->courseidnumber, $rows)));

        foreach ($kurslar as $courseid) {
            $cc = \context_course::instance((int) $courseid, IGNORE_MISSING);
            if (!$cc || !has_capability('local/targetzenit:teach', $cc)) {
                continue;
            }
            $oquvchilar = lesson::students($cc);
            if (!$oquvchilar) {
                continue;
            }

            // Shu chorakdagi darslar, sana bo'yicha. Ko'p bo'lsa eng
            // YANGILARI olinadi — o'qituvchiga oxirgi darslar kerak.
            $darslar = $DB->get_records_select('local_targetzenit_lesson',
                'courseid = :cid AND lessondate >= :a AND lessondate <= :b',
                ['cid' => $courseid, 'a' => $chorak->startdate, 'b' => $chorak->enddate],
                'lessondate ASC, lessonno ASC', 'id, lessondate, lessonno');
            if (!$darslar) {
                continue;
            }
            if (count($darslar) > self::JURNAL_DARS) {
                $darslar = array_slice($darslar, -self::JURNAL_DARS, null, true);
                $data['cheklov'] = get_string('app_journallimit',
                    'local_targetzenit', self::JURNAL_DARS);
            }

            $kataklar = journal::cells(array_keys($darslar));

            $sarlavha = [];
            foreach ($darslar as $d) {
                $sarlavha[] = ['belgi' => userdate(strtotime($d->lessondate), '%d.%m')];
            }

            $qatorlar = [];
            foreach ($oquvchilar as $st) {
                $uzani = $kataklar[(int) $st->id] ?? [];
                $hujayralar = [];
                foreach ($darslar as $lessonid => $d) {
                    $rec = $uzani[$lessonid] ?? null;
                    $matn = journal::cell_text($rec);
                    $hujayralar[] = [
                        'matn' => $matn === '' ? '·' : $matn,
                        // Baho emas, yo'qlama belgisi bo'lsa boshqa rangda.
                        'yoq' => $rec && ($rec->grade === null || $rec->grade === '')
                                 && $rec->attendance !== 'present',
                    ];
                }
                $ortacha = lesson::average($uzani);
                $qatorlar[] = [
                    'ism' => fullname($st),
                    'hujayralar' => $hujayralar,
                    'ortacha' => $ortacha === null ? '—' : (string) $ortacha,
                ];
            }

            $data['kurslar'][] = [
                'nomi' => format_string($DB->get_field('course', 'fullname',
                    ['id' => $courseid])),
                'sarlavha' => $sarlavha,
                'oquvchilar' => $qatorlar,
            ];
        }

        if (!$data['kurslar']) {
            $data['xabar'] = get_string('app_nogradesyet', 'local_targetzenit');
        } else {
            $data['bor'] = true;
        }
        return self::javob('mobile_journal', $data);
    }

    /** Ilova kutadigan javob shakli — har metodda takrorlanmasligi uchun. */
    private static function javob(string $shablon, array $data,
                                  array $otherdata = []): array {
        global $OUTPUT;
        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template(
                    'local_targetzenit/' . $shablon, $data),
            ]],
            'javascript' => '',
            'otherdata' => $otherdata,
            'files' => [],
        ];
    }

    /**
     * Kontekstni tasdiqlash — har metodda takrorlanmasligi uchun.
     *
     * `require_login()` ATAYLAB chaqirilmaydi: chaqiruv web-servis orqali
     * keladi va autentifikatsiya allaqachon o'tgan; WS kontekstida
     * `require_login()` qayta yo'naltirishga urinib xato berishi mumkin.
     * `validate_context()` esa aynan shu holat uchun mo'ljallangan.
     */
    private static function tekshir(\context $context): void {
        \core_external\external_api::validate_context($context);
    }
}
