<?php
// BALL KIRITISH SETKASI — YAGONA chizuvchi.
//
// Ikki auditoriya, bitta ekran (`output\fidbek` / `output\sharhblok` bilan
// bir xil tamoyil):
//   koordinator  `baholash.php?view=ball&sitting=N`  — har qanday sitting
//   o'qituvchi   (keyingi bosqich)                   — FAQAT o'z kursidagi BSB
//
// Ruxsat faqat KIMNING sittingiga yozishni hal qiladi, ekran esa bir xil —
// aks holda ikki ekran vaqt o'tib bir-biridan ajralib ketadi va nizomdagi
// qoida (30% chegarasi, 72 soatlik oyna) bir joyda bo'lib, boshqasida
// tushib qolardi.
//
// BU SAHIFA O'TGAN CHORAKNI KIRITISHNING ASOSIY VOSITASI
// (BAHOLASH_ARXITEKTURA.md 3.2): dastur ishlamagan chorakda imtihon
// qog'ozda o'tgan va ballar faqat shu yerdan kiradi.

namespace local_targetzenit\output;

use local_targetzenit\dashboard;
use local_targetzenit\exam;
use local_targetzenit\gradelog;
use local_targetzenit\lesson;

defined('MOODLE_INTERNAL') || die();

class ballsetka {

    /**
     * Setkani chizadi.
     *
     * @param \stdClass $sit `exam::sitting()` qatori
     * @param string $action forma qayerga yuborilsin
     * @param bool $yozish ball kiritish mumkinmi (false = faqat o'qish)
     */
    public static function render(\stdClass $sit, string $action,
                                    bool $yozish = true): string {
        global $DB;

        $t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);
        $context = \context_course::instance((int) $sit->courseid, IGNORE_MISSING);
        if (!$context) {
            return \html_writer::div($t('bh_nocourse'), 'tz-dir-sub');
        }

        $oquvchilar = lesson::students($context);
        $natijalar = exam::results((int) $sit->id);
        $q = exam::qachon_flat($sit);
        $max = (int) $sit->maxscore;
        $chegara = round($max * (float) exam::sozlama('chegara_foiz',
            exam::CHEGARA_FOIZ), 2);

        // Chorak natijalari — «ball kiritdim, baho qanday chiqdi» savoliga
        // javob SHU YERDA bo'lishi kerak, boshqa sahifada emas.
        $chorak = exam::term_scores((int) $sit->courseid, $sit->academicyear,
            $sit->termname);

        $h = '';

        // ============================================================ SARLAVHA
        $h .= \html_writer::div(
            \html_writer::tag('h4', s($sit->examname), ['class' => 'tz-dir-cardtitle'])
            . \html_writer::div(
                \html_writer::tag('b', s((string) $sit->groupname)) . ' · '
                . $t('bh_turi_' . $sit->turi) . ' · '
                . $t('bh_shakli_' . $sit->shakli) . ' · '
                . userdate(strtotime($q['examdate']), '%d.%m.%Y')
                . ($q['starttime'] ? ' ' . s($q['starttime']) : '')
                . ($q['room'] ? ' · ' . s($q['room']) : '')
                . ' · ' . $t('bh_maxscore') . ': ' . $max,
                'tz-dir-sub'), 'mb-2');

        // --- ONLAYN ogohlantirishi
        //
        // Qo'lda kiritish ONLAYN uchun ham ochiq qoladi: nizom qayta
        // topshirish va tuzatishni talab qiladi. Lekin natija yig'ish vazifasi
        // (keyingi bosqich) quiz'dan o'qiydi, ya'ni qo'lda yozilgan ball
        // ustiga yozilishi mumkin — shuni ochiq aytamiz.
        if ($sit->shakli === 'onlayn') {
            $h .= \html_writer::div($t('bh_onlinewarn'), 'tz-bh-clash');
        }

        // ============================================================= PLITALAR
        $kiritilgan = 0;
        foreach ($oquvchilar as $o) {
            $r = $natijalar[(int) $o->id] ?? null;
            if ($r && $r->score !== null) {
                $kiritilgan++;
            }
        }
        $jami = count($oquvchilar);

        $h .= \html_writer::div(
            ui::tile((string) $jami, $t('bh_students'))
            . ui::tile((string) $kiritilgan, $t('bh_entered'),
                $t('bh_ofstudents', $jami))
            . ui::tile((string) ($jami - $kiritilgan), $t('bh_missing'),
                $t('bh_missinghint'), '',
                ($jami - $kiritilgan) > 0 ? 'warning' : ''),
            'tz-dir-hero');

        if (!$oquvchilar) {
            return $h . \html_writer::div($t('bh_nostudents'), 'tz-dir-sub');
        }

        // O'zgarish sanoqlari BITTA so'rovda — halqa ichida `count_records`
        // yozilsa 25 o'quvchi = 25 so'rov bo'lardi.
        $ozgarishlar = [];
        $resultids = [];
        foreach ($natijalar as $r) {
            $resultids[] = (int) $r->id;
        }
        if ($resultids) {
            [$in, $params] = $DB->get_in_or_equal($resultids, SQL_PARAMS_NAMED);
            $params['turi'] = 'summativ';
            $rows = $DB->get_records_sql("
                SELECT recordid, COUNT(1) AS soni
                  FROM {local_targetzenit_grade_log}
                 WHERE turi = :turi AND recordid $in
              GROUP BY recordid", $params);
            foreach ($rows as $x) {
                $ozgarishlar[(int) $x->recordid] = (int) $x->soni;
            }
        }

        // ================================================================ SETKA
        $h .= \html_writer::start_tag('form', ['method' => 'post',
            'action' => $action, 'class' => 'tz-bh-ball']);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'amal', 'value' => 'ball']);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'sitting', 'value' => (int) $sit->id]);

        $h .= \html_writer::start_div('tz-dir-card');
        $h .= \html_writer::start_div('tz-dir-tablewrap');
        $h .= \html_writer::start_tag('table', ['class' => 'tz-dir-table tz-bh-grid-t']);
        $h .= \html_writer::start_tag('thead') . \html_writer::start_tag('tr');
        foreach (['bh_student', 'bh_score', 'bh_state', 'bh_retake',
                  'bh_counted', 'bh_termscore'] as $k) {
            $h .= \html_writer::tag('th', $t($k));
        }
        $h .= \html_writer::end_tag('tr') . \html_writer::end_tag('thead');
        $h .= \html_writer::start_tag('tbody');

        // 72 soatlik oyna o'tgan qator bormi — sabab maydoni shunga qarab
        // MAJBURIY bo'ladi (e-kundalik qoidasi).
        $kechbor = false;

        foreach ($oquvchilar as $o) {
            $sid = (int) $o->id;
            $r = $natijalar[$sid] ?? null;
            $k = 's' . $sid;
            $kech = $r && gradelog::oyna_otdimi((int) $r->timemodified);
            if ($kech) {
                $kechbor = true;
            }

            $h .= \html_writer::start_tag('tr');

            // --- o'quvchi
            $h .= \html_writer::tag('td', \html_writer::div(
                \html_writer::link(
                    new \moodle_url('/local/targetzenit/baholarim.php', ['userid' => $sid]),
                    s(dashboard::ism($o->firstname, $o->lastname)))
                . ((string) $o->idnumber !== ''
                    ? \html_writer::div(s($o->idnumber), 'tz-dir-sub') : ''),
                'tz-dir-cellname'));

            // --- ball
            //
            // `step=0.01`: nizomda ball butun, lekin mezonlar bo'yicha
            // yarim ball chiqishi mumkin, shuning uchun kasr qabul qilinadi.
            $h .= \html_writer::tag('td', $yozish
                ? \html_writer::empty_tag('input', [
                    'type' => 'number', 'name' => 'ball[' . $k . ']',
                    'min' => 0, 'max' => $max, 'step' => '0.01',
                    'value' => ($r && $r->rawscore !== null)
                        ? self::son($r->rawscore) : '',
                    'class' => 'tz-bh-score',
                    'placeholder' => '0–' . $max])
                : (($r && $r->score !== null) ? self::son($r->score) : '—'));

            // --- holat
            $h .= \html_writer::tag('td', $yozish
                ? \html_writer::select(exam::holatlar_menu(), 'holat[' . $k . ']',
                    $r ? $r->holat : 'ishladi', false,
                    ['class' => 'tz-bh-state'])
                : ($r ? $t('bh_holat_' . $r->holat) : '—'));

            // --- qayta topshirish
            $h .= \html_writer::tag('td', $yozish
                ? \html_writer::checkbox('retake[' . $k . ']', 1,
                    (bool) ($r && (int) $r->retake === 1), '')
                : (($r && (int) $r->retake === 1) ? get_string('yes') : ''));

            // --- hisoblangan ball (chegara qo'llangandan KEYIN)
            //
            // Ikki ustun ataylab: o'qituvchi/koordinator kiritgan XOM ball
            // va nizom qo'llanganidan keyingi HAQIQIY ball. Bitta ustun
            // bo'lsa «men 80 yozdim, nega 30 turibdi?» savoli chiqardi.
            $hisob = '—';
            if ($r && $r->score !== null) {
                $hisob = \html_writer::tag('b', self::son($r->score));
                if ((int) $r->capped === 1) {
                    $hisob .= ' ' . \html_writer::tag('span', $t('bh_capped'),
                        ['class' => 'tz-dir-chip tz-dir-warning',
                         'title' => $t('bh_cappedhint', self::son($chegara))]);
                }
                if ($kech) {
                    $hisob .= \html_writer::div($t('bh_late'), 'tz-dir-sub');
                }
                $soni = $ozgarishlar[(int) $r->id] ?? 0;
                if ($soni > 1) {
                    $hisob .= \html_writer::div($t('bh_changes', $soni), 'tz-dir-sub');
                }
            }
            $h .= \html_writer::tag('td', $hisob);

            // --- chorak bahosi (vazn qayta taqsimlangani bilan)
            $ts = $chorak[$sid] ?? null;
            $ch = '—';
            if ($ts && $ts->total !== null) {
                $ch = \html_writer::tag('b', $ts->total)
                    . ' ' . \html_writer::tag('span', (string) $ts->baho,
                        ['class' => 'tz-dir-chip tz-bh-baho-' . $ts->baho]);
                // Qaysi qismlar hisoblangani MAJBURIY ko'rsatiladi (9.1):
                // «faqat CHSB bo'yicha» bo'lmasa 30/40 nega 75 bo'lganini
                // hech kim tushuntirib bera olmaydi.
                if ((int) $ts->divisor !== 100) {
                    $ch .= \html_writer::div(
                        $t('bh_parts_hint', self::qismlar($ts->parts)), 'tz-dir-sub');
                }
                if ((int) $ts->frozen === 1) {
                    $ch .= \html_writer::div($t('bh_frozen'), 'tz-dir-sub');
                }
            }
            $h .= \html_writer::tag('td', $ch);

            $h .= \html_writer::end_tag('tr');
        }

        $h .= \html_writer::end_tag('tbody') . \html_writer::end_tag('table');
        $h .= \html_writer::end_div() . \html_writer::end_div();

        if ($yozish) {
            // --- 72 SOATLIK OYNA: sabab maydoni
            $h .= \html_writer::start_div('tz-bh-reason'
                . ($kechbor ? ' tz-bh-reason-on' : ''));
            $h .= \html_writer::tag('label', $t('bh_reason'), ['class' => 'tz-dir-lab']);
            $h .= \html_writer::empty_tag('input', ['type' => 'text',
                'name' => 'sabab', 'size' => 60, 'maxlength' => 255,
                'placeholder' => $t('bh_reasonph')]);
            $h .= \html_writer::div($kechbor
                ? $t('bh_reasonneeded', gradelog::OYNA_SOAT)
                : $t('bh_reasonopt', gradelog::OYNA_SOAT), 'tz-dir-sub');
            $h .= \html_writer::end_div();

            $h .= \html_writer::div(
                \html_writer::empty_tag('input', ['type' => 'submit',
                    'class' => 'btn btn-primary', 'value' => $t('bh_savescores')])
                . ' ' . \html_writer::tag('span', $t('bh_savehint'),
                    ['class' => 'tz-dir-sub']), 'mt-3');
        }

        $h .= \html_writer::end_tag('form');
        return $h;
    }

    /** Ballni ortiqcha nollarsiz ko'rsatadi: 30.00 -> 30, 30.50 -> 30.5 */
    public static function son($v): string {
        if ($v === null || $v === '') {
            return '';
        }
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }

    /** `fb,bsb,chsb` -> o'qiladigan ro'yxat. */
    protected static function qismlar(?string $parts): string {
        $nomlar = [
            'fb' => get_string('bh_part_fb', 'local_targetzenit'),
            'bsb' => get_string('bh_part_bsb', 'local_targetzenit'),
            'chsb' => get_string('bh_part_chsb', 'local_targetzenit'),
        ];
        $out = [];
        foreach (explode(',', (string) $parts) as $p) {
            if (isset($nomlar[$p])) {
                $out[] = $nomlar[$p];
            }
        }
        return implode(' + ', $out);
    }

    /**
     * Yuborilgan setkani qabul qiladi. Sahifa boshida, chiqarishdan OLDIN.
     *
     * Ruxsat `exam::result_save()` ICHIDA tekshiriladi — bitta joyda, chunki
     * yozish yo'li bitta bo'lishi kerak (veb bugun, mobil ilova ertaga).
     *
     * @return array ['saqlandi' => int, 'chorak' => int] yoki [] (yuborilmagan)
     */
    public static function handle(): array {
        $sittingid = optional_param('sitting', 0, PARAM_INT);
        if (!$sittingid || optional_param('amal', '', PARAM_ALPHA) !== 'ball') {
            return [];
        }
        require_sesskey();

        $sit = exam::sitting($sittingid);
        if (!$sit) {
            throw new \moodle_exception('bh_noexam', 'local_targetzenit');
        }
        $context = \context_course::instance((int) $sit->courseid);
        $oquvchilar = lesson::students($context);

        // DIQQAT: `PARAM_TEXT`, `PARAM_FLOAT` EMAS — bo'sh maydon `PARAM_FLOAT`
        // da 0 ga aylanadi va «ball qo'yilmagan» bilan «0 ball» farqi
        // yo'qoladi. Nizomda bu farq muhim: 0 — natija, bo'sh — natija yo'q.
        $ballar = optional_param_array('ball', [], PARAM_TEXT);
        $holatlar = optional_param_array('holat', [], PARAM_ALPHA);
        $retakelar = optional_param_array('retake', [], PARAM_INT);
        $sabab = trim(optional_param('sabab', '', PARAM_TEXT));

        $bor = exam::results($sittingid);
        $saqlandi = 0;
        $studentids = [];

        foreach ($oquvchilar as $o) {
            $sid = (int) $o->id;
            $k = 's' . $sid;

            $xom = trim((string) ($ballar[$k] ?? ''));
            if ($xom !== '' && !is_numeric($xom)) {
                continue;              // chalkash kiritma saqlanmaydi
            }
            $ball = ($xom === '') ? null : (float) $xom;
            $holat = $holatlar[$k] ?? 'ishladi';
            $retake = !empty($retakelar[$k]);

            // O'ZGARMAGAN QATOR O'TKAZIB YUBORILADI — bu shart.
            //
            // `result_save()` 72 soatlik oyna o'tgan qatorga sabab talab
            // qiladi. Hamma qatorni shartsiz saqlasak, koordinator eski
            // sittingda BITTA o'quvchini o'zgartirsa ham, qolgan 24 qator
            // «sabab yo'q» deb yiqilardi va hech narsa saqlanmasdi.
            $eski = $bor[$sid] ?? null;
            if ($eski) {
                $eskiball = ($eski->rawscore === null) ? null : (float) $eski->rawscore;
                $bir = ($eskiball === null && $ball === null)
                    || ($eskiball !== null && $ball !== null
                        && abs($eskiball - $ball) < 0.005);
                if ($bir && $eski->holat === $holat
                        && (int) $eski->retake === ($retake ? 1 : 0)) {
                    continue;
                }
            } else if ($ball === null && $holat === 'ishladi' && !$retake) {
                // Yangi qator uchun bo'sh forma — yozuv yaratmaymiz, aks holda
                // bir marta saqlashda 25 ta bo'sh natija paydo bo'lardi.
                continue;
            }

            exam::result_save($sittingid, $sid, $ball, $holat, $retake, $sabab);
            $saqlandi++;
            $studentids[] = $sid;
        }

        // Chorak bahosini DARHOL qayta hisoblash — koordinator kiritgan
        // ballning natijasini shu ekranda ko'rishi kerak, kechasi vazifa
        // yurgandan keyin emas.
        //
        // Og'ir emas: bitta guruh = ~25 o'quvchi. Butun maktab bo'ylab
        // qayta hisoblash esa vazifaning ishi (5-bosqich).
        $chorak = 0;
        foreach ($studentids as $sid) {
            if (exam::term_score_save($sid, (int) $sit->courseid,
                    $sit->academicyear, $sit->termname)) {
                $chorak++;
            }
        }

        return ['saqlandi' => $saqlandi, 'chorak' => $chorak];
    }
}
