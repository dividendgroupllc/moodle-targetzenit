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
use local_targetzenit\murojaat;
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
    /**
     * Nechta o'quv kuni ko'rsatiladi (bugun + oldingilari).
     *
     * Har kun ichida darslar, har darsda o'quvchilar ro'yxati keladi —
     * ya'ni sahifa hajmi shu songa ko'paytiriladi. 5 kun odatda 2 hafta
     * ichidagi unutilgan yo'qlamani qoplaydi va telefon uchun og'ir
     * bo'lmaydi.
     */
    const KUN_ORQAGA = 5;

    public static function lesson_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $year = timetable::current_year();
        $data = ['bor' => false, 'xabar' => '', 'kunlar' => [],
                 'maxgrade' => lesson::MAX_GRADE];

        $teacherkey = $year === '' ? '' : timetable::teacher_key_of($USER, $year);
        if ($teacherkey === '') {
            $data['xabar'] = get_string('app_nolessontoday', 'local_targetzenit');
            return self::javob('mobile_lessons', $data);
        }

        $rows = timetable::for_teacher($year, $teacherkey);
        $kurslar = timetable::course_ids(array_map(
            fn($r) => $r->courseidnumber, $rows));
        $terms = timetable::terms($year);
        $holidays = timetable::holidays($year);

        // Bugundan ORQAGA qarab o'quv kunlarini yig'amiz: o'qituvchi kechagi
        // yoki undan oldingi darsni ham baholay olishi kerak. Dam olish va
        // bayram kunlari o'tkazib yuboriladi — ularda dars yo'q.
        $sanalar = [];
        $kun = 0;
        while (count($sanalar) < self::KUN_ORQAGA && $kun < 30) {
            $sana = date('Y-m-d', strtotime("-{$kun} days"));
            $kun++;
            if (!timetable::school_day($sana, $terms, $holidays)['ok']) {
                continue;
            }
            $sanalar[] = $sana;
        }

        $birinchi_kun = true;
        foreach ($sanalar as $sana) {
            $dayno = (int) date('N', strtotime($sana));
            $darslar = [];
            $birinchi_dars = $birinchi_kun;

            foreach ($rows as $r) {
                if ((int) $r->dayno !== $dayno) {
                    continue;
                }
                $dars = self::dars_bloki($r, $kurslar, $sana, $birinchi_dars);
                if ($dars === null) {
                    continue;
                }
                $darslar[] = $dars;
                $birinchi_dars = false;
            }

            if (!$darslar) {
                continue;
            }
            $data['kunlar'][] = [
                'sana' => userdate(strtotime($sana), get_string('strftimedaydate')),
                'bugun' => $sana === date('Y-m-d'),
                'ochiq_blok' => $birinchi_kun,
                'darslar' => $darslar,
            ];
            $birinchi_kun = false;
        }

        if (!$data['kunlar']) {
            $data['xabar'] = get_string('app_nolessontoday', 'local_targetzenit');
        } else {
            $data['bor'] = true;
        }

        // Kiritish maydonlari qiymati `CONTENT_OTHERDATA` dan olinadi.
        $otherdata = [];
        foreach ($data['kunlar'] as $k) {
            foreach ($k['darslar'] as $d) {
                if (empty($d['lessonid'])) {
                    continue;
                }
                $otherdata['tp_' . $d['lessonid']] = $d['mavzu'] ?? '';
                $otherdata['hw_' . $d['lessonid']] = $d['uyvazifa'] ?? '';
                foreach ($d['oquvchilar'] as $o) {
                    $otherdata['gr_' . $d['lessonid'] . '_' . $o['userid']] = $o['baho'];
                }
            }
        }

        return self::javob('mobile_lessons', $data, $otherdata);
    }

    /**
     * Bitta dars bloki: mavzu, uy vazifa, ovoz, o'quvchilar ro'yxati.
     *
     * Kurs topilmasa yoki kontekst yo'q bo'lsa null qaytaradi — bunday
     * darsni ochib bo'lmaydi.
     */
    private static function dars_bloki(\stdClass $r, array $kurslar,
                                       string $sana, bool $ochiq): ?array {
        global $USER;

        $cid = $kurslar[$r->courseidnumber] ?? 0;
        if (!$cid) {
            return null;
        }
        $cc = \context_course::instance($cid, IGNORE_MISSING);
        if (!$cc) {
            return null;
        }

        $vaqt = trim((string) $r->starttime);
        if (!empty($r->endtime)) {
            $vaqt .= ' – ' . $r->endtime;
        }

        $dars = [
            'courseid' => $cid,
            'lessonno' => (int) $r->lessonno,
            'vaqt' => $vaqt,
            'fan' => format_string($r->subjectname),
            'guruh' => format_string((string) $r->groupname),
            'xona' => format_string((string) $r->room),
            'ochiq_blok' => $ochiq,
            'ruxsat' => has_capability('local/targetzenit:teach', $cc),
            'oquvchilar' => [],
            'xabar' => '',
        ];

        if (!$dars['ruxsat']) {
            $dars['xabar'] = get_string('app_noteach', 'local_targetzenit');
            return $dars;
        }

        $yozuv = lesson::get_or_create($cid, $sana, (int) $r->lessonno,
            (int) $USER->id);
        $dars['lessonid'] = (int) $yozuv->id;
        $dars['mavzu'] = (string) $yozuv->topic;
        $dars['uyvazifa'] = (string) $yozuv->homework;

        // Ovoz yozish BRAUZERDA ochiladi: ilova plagin sahifasi ichidan
        // mikrofonga kira olmaydi (MediaRecorder u yerda ishlamaydi).
        $dars['audio_yoqilgan'] = (bool) get_config('local_targetzenit',
            'audioenabled');
        $dars['rec_url'] = (new \moodle_url('/local/targetzenit/rec.php',
            ['lessonid' => $yozuv->id]))->out(false);
        $dars['dars_url'] = (new \moodle_url('/local/targetzenit/dars.php',
            ['courseid' => $cid, 'date' => $sana,
             'lessonno' => (int) $r->lessonno]))->out(false);

        $ovoz = \local_targetzenit\audio::get((int) $yozuv->id);
        $dars['audio_bor'] = $ovoz && in_array($ovoz->status,
            \local_targetzenit\audio::FAYLLI, true);
        $dars['audio_xabar'] = $dars['audio_bor']
            ? get_string('app_hasaudio', 'local_targetzenit',
                number_format($ovoz->filesize / 1048576, 1))
            : '';

        $oquvchilar = lesson::students($cc);
        if (!$oquvchilar) {
            $dars['xabar'] = get_string('app_nostudents', 'local_targetzenit');
            return $dars;
        }

        $yozuvlar = lesson::records((int) $yozuv->id);
        foreach ($oquvchilar as $st) {
            $rec = $yozuvlar[$st->id] ?? null;
            $holat = $rec->attendance ?? 'present';
            $dars['oquvchilar'][] = [
                'userid' => (int) $st->id,
                'ism' => fullname($st),
                'baho' => ($rec && $rec->grade !== null) ? (string) $rec->grade : '',
                'holat' => $holat,
                'keldi' => $holat === 'present',
                'kelmadi' => $holat === 'absent',
                'kechikdi' => $holat === 'late',
                'sababli' => $holat === 'excused',
            ];
        }
        return $dars;
    }

    // DIQQAT: alohida `lesson_cockpit()` metodi OLIB TASHLANDI. U
    // `core-site-plugins-new-content` orqali ochilardi, ya'ni ilova
    // versiyasiga bog'liq edi. Endi kokpit `lesson_view()` ning o'zida,
    // har dars ichida yig'iladigan blok bo'lib keladi — hech qanday
    // navigatsiya kerak emas.

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
                // Birinchi kurs ochiq boshlanadi, qolganlari yig'ilgan.
                'birinchi' => empty($data['kurslar']),
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
     * TUZILISH VA TARTIB VEB BILAN BIR XIL:
     *   1) to'rtta plita  2) hisob zanjiri  3) kunma-kun davomat
     *   4) oylar kesimida hisob-kitob
     *
     * HAMMA OY ko'rsatiladi, eng yangisidan boshlab. Oy TANLOVI qo'yilmadi:
     * u sahifadan sahifaga o'tishni talab qiladi. O'rniga har oy yig'iladigan
     * blok (`<details>`) — filtr vazifasini bajaradi va hech qanday ilova
     * direktivasiga bog'liq emas.
     */
    public static function payroll_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $data = ['bor' => false, 'xabar' => '', 'oylar' => [], 'hisob' => [],
                 'hisob_bor' => false];

        $ref = payroll::ref_for((int) $USER->id);
        if ($ref === '') {
            $data['xabar'] = get_string('pr_noidnumber', 'local_targetzenit');
            return self::javob('mobile_payroll', $data);
        }

        $birinchi = true;
        foreach (payroll::months($ref) as $qisqa) {
            $rec = payroll::month($ref, (int) $qisqa->yil, (int) $qisqa->oy);
            if (!$rec) {
                continue;
            }
            $data['oylar'][] = self::oylik_oyi($rec, $birinchi);
            $birinchi = false;
        }

        if (!$data['oylar']) {
            $data['xabar'] = get_string('pr_nodata', 'local_targetzenit');
            return self::javob('mobile_payroll', $data);
        }
        $data['bor'] = true;

        $belgilar = ['paid' => 'st_s_paid', 'over' => 'st_s_over',
            'partial' => 'st_s_partial', 'unpaid' => 'st_s_unpaid',
            'advance' => 'st_s_advance'];
        foreach (payroll::settle($ref) as $m) {
            $data['hisob'][] = [
                'oy' => payroll::oy_yorlig($m->oy),
                'valyuta' => $m->valyuta,
                'oylik' => $m->oylik > 0.5 ? payroll::pul($m->oylik, $m->valyuta) : '—',
                'nach' => payroll::pul($m->nach, $m->valyuta),
                'paid' => payroll::pul($m->paid, $m->valyuta),
                'avans' => $m->advance > 0.5
                    ? get_string('st_unlinked', 'local_targetzenit',
                        payroll::pul($m->advance, $m->valyuta))
                    : '',
                'qoldiq' => $m->debt > 0.5
                    ? payroll::pul($m->debt, $m->valyuta)
                    : ($m->debt < -0.5 ? '+' . payroll::pul(-$m->debt, $m->valyuta) : '0'),
                'qarz' => $m->debt > 0.5,
                'ortiq' => $m->debt < -0.5,
                'holat' => get_string($belgilar[payroll::holat($m)], 'local_targetzenit'),
                'yaxshi' => in_array(payroll::holat($m), ['paid', 'over', 'advance'], true),
            ];
        }
        $data['hisob_bor'] = (bool) $data['hisob'];

        return self::javob('mobile_payroll', $data);
    }

    /**
     * Bitta oyning to'liq tafsiloti — plitalar, hisob zanjiri, kunma-kun.
     * Tuzilish va tartib veb sahifasidagi bilan bir xil.
     */
    private static function oylik_oyi(\stdClass $rec, bool $ochiq_boshlansin): array {
        $soatbay = $rec->tolovturi === 'soat';
        $t = fn($k, $a = null) => get_string($k, 'local_targetzenit', $a);
        $birlik = $t($soatbay ? 'pr_unit_hour' : 'pr_unit_day');
        $pul = fn($x) => payroll::pul($x, $rec->valyuta);

        $oy = [
            'oy' => payroll::oy_yorlig(sprintf('%04d-%02d', $rec->yil, $rec->oy)),
            'jami' => $pul($rec->jami),
            'ochiq_blok' => $ochiq_boshlansin,
            'ochiq' => $rec->holat !== 'Yopiq',
            'ochiq_xabar' => $t('pr_open_notice', userdate((int) $rec->timemodified)),
            'koef_yorliq' => $t($soatbay ? 'pr_hours' : 'pr_koef'),
        ];

        $oy['plitalar'] = [
            ['yorliq' => $t('pr_takehome'), 'qiymat' => $pul($rec->jami),
             'izoh' => '', 'asosiy' => true],
            ['yorliq' => $t($soatbay ? 'pr_workedhours' : 'pr_workeddays'),
             'qiymat' => (float) $rec->ishlagan . ' ' . $birlik,
             'izoh' => $soatbay ? '' : $t('pr_ofnorm', (int) $rec->normakun),
             'asosiy' => false],
            ['yorliq' => $t('pr_bonus'), 'qiymat' => $pul($rec->bonus),
             'izoh' => '', 'asosiy' => false],
            ['yorliq' => $t('pr_attended'),
             'qiymat' => (int) $rec->kelgankun . ' ' . $t('pr_unit_day'),
             'izoh' => '', 'asosiy' => false],
        ];

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

        $oy['qadamlar'] = array_map(fn($q) => [
            'op' => $q[0], 'op_bor' => $q[0] !== '',
            'yorliq' => $q[1], 'qiymat' => $q[2], 'turi' => $q[3],
        ], $qadam);

        $kunlar = payroll::days($rec);
        $oy['kunlar_bor'] = (bool) $kunlar;
        $oy['kirish_bor'] = false;
        $oy['chiqish_bor'] = false;
        $oy['kun_sarlavha'] = [];
        $oy['kun_koef'] = [];
        $oy['kun_kirish'] = [];
        $oy['kun_chiqish'] = [];
        foreach ($kunlar as $k) {
            if (trim((string) ($k['kirish'] ?? '')) !== '') {
                $oy['kirish_bor'] = true;
            }
            if (trim((string) ($k['chiqish'] ?? '')) !== '') {
                $oy['chiqish_bor'] = true;
            }
            $kun = (int) ($k['kun'] ?? 0);
            $vaqt = make_timestamp((int) $rec->yil, (int) $rec->oy, $kun ?: 1);
            $oy['kun_sarlavha'][] = [
                'kun' => $kun,
                'hafta' => userdate($vaqt, '%a'),
                'dam' => in_array((int) date('w', $vaqt), [0, 6], true),
            ];
            $koef = $k['koef'] ?? null;
            $oy['kun_koef'][] = [
                'matn' => $koef === null ? '—' : (string) (float) $koef,
                'bor' => $koef !== null && (float) $koef > 0,
                'yoq' => $koef !== null && (float) $koef <= 0,
            ];
            $oy['kun_kirish'][] = ['matn' => ($k['kirish'] ?? '') ?: '·'];
            $oy['kun_chiqish'][] = ['matn' => ($k['chiqish'] ?? '') ?: '·'];
        }

        return $oy;
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
                'birinchi' => empty($data['kurslar']),
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

    // ====================================================== MUROJAATLAR
    // Ota-ona shikoyat/taklifi. Ikki panel: rahbar (ko'radi, izoh, qaror) va
    // o'quvchi/ota-ona (shikoyat matni + javob).
    //
    // Ma'lumot mantig'i `classes/murojaat.php` da — veb sahifa ham AYNI shu
    // metodlardan o'tadi, ya'ni ilovada boshqa qoida paydo bo'lmaydi.
    // Tuzilish ham veb sahifa bilan bir xil: har murojaat yig'iladigan blok
    // (`<details>`), izoh va qaror o'sha blok ichida.

    /** Rahbar paneli (CEO / direktor / zavuch). */
    public static function murojaat_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $holat = isset($args['holat']) ? (string) $args['holat'] : 'ochiq';
        $data = ['bor' => false, 'xabar' => '', 'murojaatlar' => [], 'stats' => []];

        if (!murojaat::yonalishlar($context)) {
            $data['xabar'] = get_string('mur_noaccess', 'local_targetzenit');
            return self::javob('mobile_murojaat', $data);
        }

        $rows = murojaat::royxat($holat);
        $st = murojaat::stats();
        $data['stats'] = [
            ['lab' => get_string('mur_k_open', 'local_targetzenit'), 'val' => $st['ochiq']],
            ['lab' => get_string('mur_k_unseen', 'local_targetzenit'), 'val' => $st['korilmagan']],
            ['lab' => get_string('mur_k_overdue', 'local_targetzenit'), 'val' => $st['muddati']],
            ['lab' => get_string('mur_k_mine', 'local_targetzenit'), 'val' => $st['menda']],
        ];

        if (!$rows) {
            $data['xabar'] = get_string('mur_empty', 'local_targetzenit');
            return self::javob('mobile_murojaat', $data);
        }

        $otherdata = [];
        foreach ($rows as $m) {
            [$mkod, $mkun] = murojaat::muddat($m);
            $izohlar = [];
            foreach (murojaat::izohlar((int) $m->id) as $i) {
                $izohlar[] = [
                    'ism' => $i->ism,
                    'vaqt' => userdate($i->timecreated, get_string('strftimedatetimeshort')),
                    'turi' => $i->turi,
                    'body' => $i->body,
                ];
            }
            $data['murojaatlar'][] = [
                'id' => (int) $m->id,
                'turi' => $m->turi,
                'yonalish' => $m->yonalish,
                'holat' => $m->holat,
                'yopiq' => $m->holat === 'Yopildi',
                'muhim' => $m->muhimlik === 'Yuqori',
                'korilmagan' => !$m->korildivaqti,
                'kategoriya' => $m->kategoriya ?: get_string('mur_nocat', 'local_targetzenit'),
                'oquvchi' => $m->studentname ?: ($m->payername ?: '—'),
                'sinf' => $m->sinf,
                'otaona' => $m->payername,
                'telefon' => $m->payerphone,
                'menejer' => $m->managername ?: '—',
                'suhbat' => $m->suhbatvaqti
                    ? userdate($m->suhbatvaqti, get_string('strftimedatetimeshort')) : '—',
                'matn' => $m->matn,
                'masul' => $m->masuluserid
                    ? murojaat::ism((int) $m->masuluserid)
                    : get_string('mur_noowner', 'local_targetzenit'),
                'menda' => (int) $m->masuluserid === (int) $USER->id,
                'akkauntsiz' => !$m->studentuserid,
                'muddat' => $mkod === 'kechikdi'
                        ? get_string('mur_overdue', 'local_targetzenit', $mkun)
                        : ($mkod === 'bugun' ? get_string('mur_duetoday', 'local_targetzenit')
                        : ($mkod === 'bor' ? get_string('mur_duein', 'local_targetzenit', $mkun) : '')),
                'kechikdi' => $mkod === 'kechikdi',
                'qarorturi' => $m->qarorturi,
                'qaror' => $m->qaror,
                'qarorkim' => $m->qarorkim ? murojaat::ism((int) $m->qarorkim) : '',
                'otaonakordi' => $m->otaonakordi
                    ? userdate($m->otaonakordi, get_string('strftimedatetimeshort')) : '',
                'izohlar' => $izohlar,
                'qarorlar' => array_map(fn($q) => ['nomi' => $q], murojaat::QARORLAR),
                'boshqa' => $m->yonalish === 'CEO' ? 'Direktor' : 'CEO',
                'web_url' => (new \moodle_url('/local/targetzenit/murojaat.php',
                    ['ochiq' => $m->id]))->out(false),
            ];
            // ngModel boshlang'ich qiymatlari: har murojaat uchun alohida
            // kalit (dars kokpitidagi `tp_`/`hw_` bilan bir xil naqsh).
            $otherdata['iz_' . $m->id] = '';
            $otherdata['qm_' . $m->id] = '';
            $otherdata['qt_' . $m->id] = murojaat::QARORLAR[0];
        }
        $data['bor'] = true;
        return self::javob('mobile_murojaat', $data, $otherdata);
    }

    /**
     * O'quvchi akkauntidagi panel — OTA-ONA shuni o'qiydi (Moodle'da ota-ona
     * akkaunti yo'q, u bolasining logini bilan kiradi).
     */
    public static function murojaatim_view(array $args): array {
        global $USER;

        $context = \context_system::instance();
        self::tekshir($context);

        $data = ['bor' => false, 'xabar' => '', 'murojaatlar' => []];
        $rows = murojaat::meniki((int) $USER->id);
        if (!$rows) {
            $data['xabar'] = get_string('mur_emptymine', 'local_targetzenit');
            return self::javob('mobile_murojaatim', $data);
        }

        foreach ($rows as $m) {
            $javobbor = (bool) $m->qarorvaqti;
            $data['murojaatlar'][] = [
                'id' => (int) $m->id,
                'turi' => $m->turi,
                'kategoriya' => $m->kategoriya ?: get_string('mur_nocat', 'local_targetzenit'),
                'sana' => userdate($m->ochilgan, get_string('strftimedateshort')),
                'matn' => $m->matn,
                'javobbor' => $javobbor,
                'qarorturi' => $m->qarorturi,
                'qaror' => $m->qaror,
                'qarorvaqti' => $javobbor
                    ? userdate($m->qarorvaqti, get_string('strftimedatetimeshort')) : '',
                // Javob kelgani ochiq turadi: ota-ona uzun ro'yxatni
                // varaqlab "javob bormi" deb qidirib yurmasligi kerak
                'ochiq_blok' => $javobbor && !$m->otaonakordi,
                'kordim' => (bool) $m->otaonakordi,
            ];
        }
        $data['bor'] = true;
        return self::javob('mobile_murojaatim', $data);
    }

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
