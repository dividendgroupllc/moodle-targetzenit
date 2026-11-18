<?php
// ISH STOLI — Moodle Dashboard (/my) ICHIGA kiradigan o'qituvchi kontenti.
//
// QAROR TARIXI (2026-11-18): avval alohida bosh.php sahifasi bor edi —
// menyuda ikkita «Dashboard» paydo bo'lib chalg'itdi va foydalanuvchi uni
// bekor qildi. Endi yagona dashboard — Moodle'ning o'zi (/my), bizning
// maket-kontent esa SHU SAHIFANING ichiga ilgak orqali qo'shiladi
// (hook_callbacks::dashboard_inject). O'qituvchiga standart bloklar CSS
// bilan yashiriladi (#page-my-index.tz-ishstoli .block), o'quvchi va
// boshqalarga /my butunlay odatdagidek qoladi.
//
// Ma'lumot manbalari — faqat mavjud qatlamlar: _timetable/_slot (jadval),
// teacher::day_state/counts (holat), topshiriq (mod_assign/quiz), _exam_sitting,
// _feedback. Bu kontent HECH NARSA yozmaydi.

namespace local_targetzenit\output;

use html_writer;
use local_targetzenit\mavzu;
use local_targetzenit\teacher;
use local_targetzenit\timetable;
use local_targetzenit\topshiriq;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class ishstoli {

    /** Dashboard ichiga qo'yiladigan to'liq HTML. */
    public static function render(): string {
        global $DB, $USER, $OUTPUT;

        $date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $bugunmi = ($date === date('Y-m-d'));

        // ----------------------------------------------------- MA'LUMOT
        $year = timetable::current_year();
        $terms = timetable::terms($year);
        $holidays = timetable::holidays($year);
        $teacherkey = timetable::teacher_key_of($USER, $year);

        $jadval = $teacherkey ? timetable::for_teacher($year, $teacherkey) : [];
        $dn = (int) date('N', strtotime($date));
        $kunjadvali = array_values(array_filter($jadval,
            static fn($r) => (int) $r->dayno === $dn));
        $kun = timetable::school_day($date, $terms, $holidays);

        $courseids = timetable::course_ids(array_map(
            static fn($r) => $r->courseidnumber, $jadval));

        $holat = [];
        if ($kunjadvali) {
            $cids = [];
            foreach ($kunjadvali as $r) {
                if (!empty($courseids[$r->courseidnumber])) {
                    $cids[] = (int) $courseids[$r->courseidnumber];
                }
            }
            $holat = teacher::day_state($date, $cids);
        }

        // Kun xulosasi: nechtasi o'tdi, hozir qaysi guruh bilan.
        $hozirv = date('H:i');
        $otdi = 0;
        $hozirgiguruh = '';
        if ($kun['ok']) {
            foreach ($kunjadvali as $r) {
                if ($bugunmi) {
                    if ($r->starttime && $r->endtime
                            && $hozirv >= $r->starttime && $hozirv <= $r->endtime) {
                        $hozirgiguruh = format_string($r->groupname);
                    } else if ($r->endtime && $hozirv > $r->endtime) {
                        $otdi++;
                    }
                } else if ($date < date('Y-m-d')) {
                    $otdi++;
                }
            }
        }

        // «Keyingi dars kuni» — dam olish/bo'sh kun chekka holati.
        $keyingikun = null;
        if ($teacherkey && $jadval && (!$kun['ok'] || !$kunjadvali)) {
            $kundagi = [];
            foreach ($jadval as $r) {
                $kundagi[(int) $r->dayno] = true;
            }
            for ($i = 1; $i <= 14; $i++) {
                $d = date('Y-m-d', strtotime($date . " +{$i} day"));
                $k2 = timetable::school_day($d, $terms, $holidays);
                if ($k2['ok'] && !empty($kundagi[(int) date('N', strtotime($d))])) {
                    $keyingikun = $d;
                    break;
                }
            }
        }

        // Joriy chorak — «kutilayotgan ishlar» davri.
        $davr = null;
        foreach ($terms as $t) {
            if (date('Y-m-d') >= $t->startdate && date('Y-m-d') <= $t->enddate) {
                $davr = ['from' => $t->startdate, 'to' => date('Y-m-d')];
                break;
            }
        }
        $counts = $davr ? teacher::counts((int) $USER->id, $davr)
                        : ['all' => 0, 'nuqson' => 0, 'tahlilli' => 0];

        $tekshirilmagan = 0;
        try {
            foreach (topshiriq::royxat($year,
                    topshiriq::mening_kurslarim((int) $USER->id)) as $r) {
                $tekshirilmagan += max(0, (int) $r->topshirgan - (int) $r->baholangan);
            }
        } catch (\Throwable $e) {
            $tekshirilmagan = 0;   // hisoblagich panelni yiqitmasin
        }

        $imtihonlar7 = [];
        if ($courseids) {
            list($in, $params) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED);
            $params['a'] = date('Y-m-d');
            $params['b'] = date('Y-m-d', strtotime('+7 days'));
            $imtihonlar7 = $DB->get_records_sql("
                SELECT s.id, e.turi, e.name, c.fullname AS coursename,
                       COALESCE(s.examdate, e.examdate) AS sana,
                       COALESCE(s.starttime, e.starttime) AS vaqt,
                       COALESCE(s.room, e.room) AS xona, e.shakli
                  FROM {local_targetzenit_exam_sitting} s
                  JOIN {local_targetzenit_exam} e ON e.id = s.examid
                  JOIN {course} c ON c.id = s.courseid
                 WHERE s.courseid $in AND s.status <> 'bekor' AND e.status <> 'qoralama'
                   AND COALESCE(s.examdate, e.examdate) >= :a
                   AND COALESCE(s.examdate, e.examdate) <= :b
              ORDER BY COALESCE(s.examdate, e.examdate) ASC", $params);
        }

        $fidbek = (int) $DB->count_records_select('local_targetzenit_feedback',
            "teacheruserid = :u AND visibility = 'teacher' AND (seenat IS NULL OR seenat = 0)",
            ['u' => (int) $USER->id]);

        // ----------------------------------------------------- KO'RSATISH
        ob_start();

        // DIQQAT: bu HTML Boost'da FOOTERDAN KEYIN, body darajasida chiqadi
        // (standard_after_main_region_html shunday joylashgan) — shuning
        // uchun boshida YASHIRIN turadi, pastdagi skript uni #region-main
        // ichiga ko'chirib ko'rsatadi. Aks holda kontent ekran chetiga
        // yoyilib, footer ostida qolardi (2026-11-18 lokal sinov saboqi).
        echo html_writer::start_div('tz-ishstoli-wrap',
            ['id' => 'tz-ishstoli', 'hidden' => 'hidden']);
        // Ichki konteyner — maketdagi .wrap: 1240px, markazda.
        echo html_writer::start_div('tz-ishstoli-ich');

        // Maketdagi h1 salomlashuv (Moodle'ning /my sarlavhasi CSS bilan
        // yashiriladi — ikki marta salomlashmaslik uchun).
        echo html_writer::tag('div',
            get_string('dir_afternoon', 'local_targetzenit', s($USER->firstname)) . '!',
            ['class' => 'tzp-h1']);

        if ($kun['ok'] && $kunjadvali) {
            $sub = $bugunmi
                ? get_string('bosh_sub', 'local_targetzenit', (object) [
                        'jami' => count($kunjadvali), 'otdi' => $otdi])
                    . ($hozirgiguruh !== ''
                        ? get_string('bosh_sub_now', 'local_targetzenit', $hozirgiguruh)
                        : '')
                : get_string('bosh_sub_other', 'local_targetzenit', count($kunjadvali));
            echo html_writer::div($sub, 'tzp-sub');
        }

        // Sana paneli: ‹ › + kalendar + «Bugun». Havolalar /my ning o'ziga.
        $prev = date('Y-m-d', strtotime($date . ' -1 day'));
        $next = date('Y-m-d', strtotime($date . ' +1 day'));
        $lbl = userdate(strtotime($date), '%A, %d-%B')
            . ($bugunmi ? ' (' . get_string('bosh_today', 'local_targetzenit') . ')' : '');

        echo html_writer::start_div('tzp-datebar');
        echo html_writer::link(new moodle_url('/local/targetzenit/bosh.php', ['date' => $prev]), '‹',
            ['class' => 'tzp-btn']);
        echo html_writer::tag('b', $lbl, ['class' => 'tzp-dlbl']);
        echo html_writer::link(new moodle_url('/local/targetzenit/bosh.php', ['date' => $next]), '›',
            ['class' => 'tzp-btn']);
        echo html_writer::start_tag('form', ['method' => 'get',
            'action' => new moodle_url('/local/targetzenit/bosh.php'), 'class' => 'd-inline m-0']);
        echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'date',
            'value' => $date, 'class' => 'form-control d-inline-block',
            'style' => 'width:auto', 'onchange' => 'this.form.submit()']);
        echo html_writer::end_tag('form');
        if (!$bugunmi) {
            echo html_writer::link(new moodle_url('/local/targetzenit/bosh.php'),
                get_string('bosh_today', 'local_targetzenit'),
                ['class' => 'tzp-btn tzp-btn-pri']);
        }
        echo html_writer::end_div();

        echo html_writer::start_div('tzp-cols');

        // ------------------------------------------ CHAP: KUN DARSLARI
        echo html_writer::start_div('');
        echo html_writer::start_div('tzp-card');
        echo html_writer::tag('h5',
            get_string('bosh_lessons', 'local_targetzenit')
            . html_writer::tag('span', count($kunjadvali), ['class' => 'tzp-cnt']));

        $keyingibtn = $keyingikun
            ? html_writer::div(html_writer::link(
                new moodle_url('/local/targetzenit/bosh.php', ['date' => $keyingikun]),
                get_string('bosh_nextday', 'local_targetzenit',
                    userdate(strtotime($keyingikun), '%A, %d-%B')),
                ['class' => 'tzp-btn tzp-btn-pri']), 'mt-2')
            : '';

        if (!$teacherkey) {
            echo html_writer::div(get_string('bosh_nokey', 'local_targetzenit'),
                'tzp-empty');
        } else if (!$kun['ok']) {
            echo html_writer::div(s($kun['reason']) . $keyingibtn, 'tzp-empty');
        } else if (!$kunjadvali) {
            echo html_writer::div(
                get_string('timetable_nolessons', 'local_targetzenit') . $keyingibtn,
                'tzp-empty');
        } else {
            foreach ($kunjadvali as $r) {
                $cid = (int) ($courseids[$r->courseidnumber] ?? 0);
                $h = $cid ? ($holat[$cid . ':' . (int) $r->lessonno] ?? null) : null;

                $sinf = 'tzp-lesson';
                $hozirgi = false;
                if ($bugunmi && $r->starttime && $r->endtime) {
                    if ($hozirv >= $r->starttime && $hozirv <= $r->endtime) {
                        $sinf .= ' tzp-now';
                        $hozirgi = true;
                    } else if ($hozirv > $r->endtime) {
                        $sinf .= ' tzp-past';
                    }
                } else if (!$bugunmi && $date < date('Y-m-d')) {
                    $sinf .= ' tzp-past';
                }

                $mavzutxt = $h && trim((string) $h->topic) !== ''
                    ? format_string($h->topic) : '';
                if ($mavzutxt === '') {
                    try {
                        $reja = mavzu::slot_mavzusi($year, $date, (int) $r->lessonno,
                            (string) $r->groupkey, (string) $r->subjectkey);
                        if ($reja) {
                            $mavzutxt = get_string('bosh_ktp', 'local_targetzenit',
                                format_string($reja->mavzu));
                        }
                    } catch (\Throwable $e) {
                        $mavzutxt = '';
                    }
                }

                $chiplar = '';
                $otganmi = $bugunmi
                    ? ($hozirgi || ($r->endtime && $hozirv > $r->endtime))
                    : ($date < date('Y-m-d'));
                if ($otganmi) {
                    $chip = static function (bool $ok, string $nom): string {
                        return html_writer::tag('span', $nom . ' ' . ($ok ? '✓' : '✗'),
                            ['class' => 'tzp-st ' . ($ok ? 'tzp-ok' : 'tzp-no')]);
                    };
                    // Har dars uchun 4 kriteriya: mavzu, davomat, uy vazifa, yozuv.
                    $chiplar = $chip($h && trim((string) $h->topic) !== '',
                            get_string('oq_c_topic', 'local_targetzenit'))
                        . $chip($h && (int) $h->yozuvlar > 0,
                            get_string('oq_c_att', 'local_targetzenit'))
                        . $chip($h && trim((string) $h->homework) !== '',
                            get_string('oq_c_hw', 'local_targetzenit'))
                        . $chip((bool) ($h->yozuvbor ?? false),
                            get_string('oq_c_rec', 'local_targetzenit'));
                }

                $tugmalar = '';
                if ($cid) {
                    $p = ['courseid' => $cid, 'date' => $date,
                          'lessonno' => (int) $r->lessonno];
                    $yozilgan = (bool) ($h->yozuvbor ?? false);
                    $tugmalar = html_writer::link(
                            new moodle_url('/local/targetzenit/rec.php', $p),
                            ($yozilgan ? '✓ ' : '● ')
                            . get_string($yozilgan ? 'tt_recorded' : 'tt_record',
                                'local_targetzenit'),
                            ['class' => 'tzp-btn' . ($yozilgan ? '' : ' tzp-btn-rec')
                                . ' tz-recbtn',
                             'target' => '_blank', 'rel' => 'noopener'])
                        . ' ' . html_writer::link(
                            new moodle_url('/local/targetzenit/dars.php', $p),
                            get_string('oq_open', 'local_targetzenit'),
                            ['class' => 'tzp-btn' . ($hozirgi ? ' tzp-btn-pri' : '')]);
                }

                echo html_writer::div(
                    html_writer::div(
                        html_writer::tag('b', s($r->starttime))
                        . html_writer::tag('small',
                            get_string('ck_lessonshort', 'local_targetzenit',
                                (int) $r->lessonno)),
                        'tzp-time')
                    . html_writer::div(
                        html_writer::tag('b', format_string($r->groupname) . ' · '
                            . format_string($r->subjectname))
                        . ' ' . html_writer::tag('span', s($r->room),
                            ['class' => 'text-muted small'])
                        . ($hozirgi ? ' ' . html_writer::tag('span',
                            get_string('bosh_now', 'local_targetzenit'),
                            ['class' => 'tzp-nowtag']) : '')
                        . ($mavzutxt !== ''
                            ? html_writer::tag('span', $mavzutxt,
                                ['class' => 'tzp-topic']) : '')
                        . ($chiplar !== '' ? html_writer::div($chiplar) : ''),
                        'tzp-what')
                    . html_writer::div($tugmalar),
                    $sinf);
            }
        }
        echo html_writer::end_div();   // card

        echo html_writer::start_div('tzp-card');
        echo html_writer::tag('h5', get_string('timetable', 'local_targetzenit'));
        echo html_writer::div(
            html_writer::link(new moodle_url('/local/targetzenit/index.php',
                ['view' => 'week']),
                get_string('bosh_week', 'local_targetzenit'), ['class' => 'tzp-btn'])
            . ' ' . html_writer::link(new moodle_url('/local/targetzenit/index.php',
                ['view' => 'all']),
                get_string('timetable_all', 'local_targetzenit'),
                ['class' => 'tzp-btn']));
        echo html_writer::end_div();
        echo html_writer::end_div();   // chap ustun

        // --------------------------------- O'NG: KUTILAYOTGAN ISHLAR
        echo html_writer::start_div('');
        echo html_writer::start_div('tzp-card');
        $todo = [];

        if ($tekshirilmagan > 0) {
            $todo[] = [$tekshirilmagan, 'tzp-r',
                html_writer::link(new moodle_url('/local/targetzenit/topshiriqlar.php'),
                    get_string('bosh_t_sub', 'local_targetzenit')),
                get_string('bosh_t_sub2', 'local_targetzenit')];
        }
        if ((int) $counts['nuqson'] > 0) {
            $todo[] = [(int) $counts['nuqson'], 'tzp-a',
                html_writer::link(new moodle_url('/local/targetzenit/oqituvchi.php',
                    ['filter' => 'nuqson']),
                    get_string('bosh_t_nuqson', 'local_targetzenit')),
                get_string('bosh_t_nuqson2', 'local_targetzenit')];
        }
        foreach (array_slice($imtihonlar7, 0, 5) as $im) {
            $todo[] = ['◆', 'tzp-b',
                html_writer::link(new moodle_url('/local/targetzenit/baholash.php'),
                    s($im->turi) . ' — ' . format_string($im->name)),
                format_string($im->coursename) . ' · '
                    . userdate(strtotime($im->sana), '%d.%m')
                    . ($im->vaqt ? ' ' . s($im->vaqt) : '')
                    . ($im->xona ? ' · ' . s($im->xona) : '')];
        }
        if (count($imtihonlar7) > 5) {
            $todo[] = ['…', 'tzp-b',
                html_writer::link(new moodle_url('/local/targetzenit/baholash.php'),
                    get_string('bosh_more', 'local_targetzenit',
                        count($imtihonlar7) - 5)),
                ''];
        }
        if ($fidbek > 0) {
            $todo[] = [$fidbek, 'tzp-b',
                html_writer::link(new moodle_url('/local/targetzenit/oqituvchi.php'),
                    get_string('bosh_t_fb', 'local_targetzenit')),
                get_string('bosh_t_fb2', 'local_targetzenit')];
        }

        echo html_writer::tag('h5',
            get_string('bosh_todo', 'local_targetzenit')
            . html_writer::tag('span', count($todo), ['class' => 'tzp-cnt']));
        if (!$todo) {
            echo html_writer::tag('div', get_string('bosh_t_empty', 'local_targetzenit'),
                ['class' => 'text-muted']);
        } else {
            echo html_writer::start_tag('ul', ['class' => 'tzp-todo']);
            foreach ($todo as [$son, $rang, $sarlavha, $izoh]) {
                echo html_writer::tag('li',
                    html_writer::tag('span', $son, ['class' => 'tzp-n ' . $rang])
                    . html_writer::div($sarlavha
                        . html_writer::tag('small', $izoh)));
            }
            echo html_writer::end_tag('ul');
        }
        echo html_writer::end_div();

        echo html_writer::start_div('tzp-card');
        echo html_writer::tag('h5', get_string('bosh_metrics', 'local_targetzenit'));
        if (!$davr) {
            echo html_writer::tag('div', get_string('bosh_noterm', 'local_targetzenit'),
                ['class' => 'text-muted small mb-2']);
        }
        echo html_writer::div(
            html_writer::tag('span', get_string('bosh_m_lessons', 'local_targetzenit'))
            . html_writer::tag('b', $davr ? (string) (int) $counts['all'] : '—'),
            'tzp-kv');
        echo html_writer::div(
            html_writer::tag('span', get_string('bosh_m_tahlil', 'local_targetzenit'))
            . html_writer::tag('b', $davr ? (string) (int) $counts['tahlilli'] : '—'),
            'tzp-kv');
        echo html_writer::div(
            html_writer::tag('span', get_string('mypayroll', 'local_targetzenit'))
            . html_writer::link(new moodle_url('/local/targetzenit/oylik.php'), '→'),
            'tzp-kv');
        echo html_writer::div(
            html_writer::link(new moodle_url('/local/targetzenit/oqituvchi.php'),
                get_string('bosh_m_panel', 'local_targetzenit'), ['class' => 'tzp-btn']),
            'mt-2');
        echo html_writer::end_div();
        echo html_writer::end_div();   // o'ng ustun

        echo html_writer::end_div();   // tzp-cols
        echo html_writer::end_div();   // ich
        echo html_writer::end_div();   // wrap

        // (1) Kontentni sahifaning asosiy ustuniga KO'CHIRISH — yuqoridagi
        //     izohga qarang. #region-main topilmasa (tema o'zgargan bo'lsa),
        //     baribir ko'rsatamiz: noto'g'ri joyda bo'lsa ham yo'qolmasin.
        // (2) Yozuv tugmalari kichik oynada — oddiy <script>: ilgak footer
        //     bosqichida ishlaydi, $PAGE->requires bu paytda kech.
        echo '<script>document.addEventListener("DOMContentLoaded",function(){'
            . 'var w=document.getElementById("tz-ishstoli");'
            . 'var m=document.getElementById("region-main");'
            . 'if(w&&m){m.appendChild(w);}'
            . 'if(w){w.hidden=false;}'
            . 'document.querySelectorAll(".tz-ishstoli-wrap .tz-recbtn").forEach(function(a){'
            . 'a.addEventListener("click",function(e){'
            . 'var p=window.open(a.href,"tzrec","width=420,height=460,menubar=no,toolbar=no,location=no");'
            . 'if(p){e.preventDefault();p.focus();}});});});</script>';

        return ob_get_clean();
    }
}
