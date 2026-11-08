<?php
// BAHOLASH PANELI — o'quv koordinatori.
//
// Sahifa javob beradigan savol: «chorakda qaysi baholashlar bor, qaysi
// guruh qachon va qayerda ishlaydi, ballar kiritilganmi?»
//
// Uch ko'rinish:
//   royxat   baholashlar ro'yxati (standart)
//   yangi    yaratish / tahrirlash formasi
//   jadval   IMTIHON JADVALI quruvchisi — guruh x sana x vaqt x xona
//
// O'TGAN SANA RUXSAT ETILADI: dastur ishlamagan chorakni kiritish birinchi
// darajali talab (BAHOLASH_ARXITEKTURA.md 3.2). Bunday baholash `retro=1`
// bo'lib belgilanadi, onlayn quiz yaratilmaydi va bildirishnoma yuborilmaydi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\exam;
use local_targetzenit\output\shell;
use local_targetzenit\output\ui;
use local_targetzenit\timetable;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:manageexams', $context);

$view = optional_param('view', 'royxat', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$chorak = optional_param('chorak', '', PARAM_TEXT);
$turi = optional_param('turi', '', PARAM_ALPHA);
$fan = optional_param('fan', '', PARAM_TEXT);

if (!in_array($view, ['royxat', 'yangi', 'jadval'], true)) {
    $view = 'royxat';
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/baholash.php', ['view' => $view]));
shell::setup($PAGE, get_string('bh_nav', 'local_targetzenit'));

$year = timetable::current_year();
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

$url = function(array $p = []) use ($chorak, $turi, $fan) {
    $asos = [];
    foreach (['chorak' => $chorak, 'turi' => $turi, 'fan' => $fan] as $k => $v) {
        if ($v !== '') {
            $asos[$k] = $v;
        }
    }
    return new moodle_url('/local/targetzenit/baholash.php', $p + $asos);
};

// ======================================================== AMALLAR (chiqishdan OLDIN)
// POST/Redirect/GET — saqlagach sahifa yangilansa forma qayta yuborilmaydi.

$xabar = '';
$amal = optional_param('amal', '', PARAM_ALPHA);

if ($amal !== '' && confirm_sesskey()) {

    if ($amal === 'saqla') {
        // --- baholashni yaratish / tahrirlash
        $d = (object) [
            'id' => optional_param('examid', 0, PARAM_INT),
            'turi' => optional_param('f_turi', 'CHSB', PARAM_ALPHA),
            'academicyear' => $year,
            'termname' => optional_param('f_chorak', '', PARAM_TEXT),
            'subjectkey' => optional_param('f_fan', '', PARAM_TEXT),
            'gradelevel' => optional_param('f_daraja', '', PARAM_TEXT),
            'name' => optional_param('f_nom', '', PARAM_TEXT),
            'shakli' => optional_param('f_shakli', 'qogoz', PARAM_ALPHA),
            'examdate' => optional_param('f_sana', '', PARAM_TEXT),
            'starttime' => optional_param('f_vaqt', '', PARAM_TEXT),
            'room' => optional_param('f_xona', '', PARAM_TEXT),
            'duration' => optional_param('f_davomiylik', 45, PARAM_INT),
            'maxscore' => optional_param('f_maxball', 100, PARAM_INT),
            'randomcount' => optional_param('f_tasodif', 0, PARAM_INT),
        ];
        if ($d->termname === '' || $d->subjectkey === '' || $d->gradelevel === '') {
            throw new moodle_exception('bh_fillall', 'local_targetzenit');
        }
        if (trim($d->name) === '') {
            $fanlar = exam::fanlar($year);
            $d->name = exam::taklif_nom($d->turi, $d->termname,
                $fanlar[$d->subjectkey] ?? $d->subjectkey, $d->gradelevel);
        }
        $yangiid = exam::save($d);

        // Guruhlar DARHOL biriktiriladi: koordinator «yaratdim, lekin hech
        // narsa ko'rinmayapti» holatiga tushmasligi kerak. Sitting yaratish
        // yengil amal (bir nechta INSERT) — quiz yaratish esa 6-bosqichda,
        // adhoc vazifada.
        exam::ensure_sittings($yangiid);

        redirect($url(['view' => 'jadval', 'id' => $yangiid]),
            $t('bh_saved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($amal === 'guruhlar' && $id) {
        $n = exam::ensure_sittings($id);
        redirect($url(['view' => 'jadval', 'id' => $id]),
            $t('bh_groupsadded', (object) $n), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($amal === 'jadval' && $id) {
        // --- imtihon jadvalini saqlash: har guruhga sana/vaqt/xona
        //
        // QOIDA: forma massiv kalitlari faqat [a-z0-9_-] bo'lishi kerak —
        // `optional_param_array()` mos kelmaganini JIMGINA tashlab yuboradi
        // (0.9.4 saboqi). Shuning uchun kalit `s<sittingid>`.
        $sanalar = optional_param_array('sana', [], PARAM_TEXT);
        $vaqtlar = optional_param_array('vaqt', [], PARAM_TEXT);
        $xonalar = optional_param_array('xona', [], PARAM_TEXT);

        foreach (exam::sittings($id) as $s) {
            $k = 's' . $s->id;
            exam::sitting_siljit((int) $s->id,
                $sanalar[$k] ?? null, $vaqtlar[$k] ?? null, $xonalar[$k] ?? null);
        }
        redirect($url(['view' => 'jadval', 'id' => $id]),
            $t('bh_saved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($amal === 'status' && $id) {
        exam::status($id, optional_param('yangistatus', '', PARAM_ALPHA));
        redirect($url(['view' => 'jadval', 'id' => $id]),
            $t('bh_saved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

shell::start_menu($view, $t('bh_nav'), $t('bh_hint'), '', shell::bh_menu());

echo html_writer::start_div('tz-dir-wrap tz-bh');

if ($year === '') {
    echo $OUTPUT->notification($t('timetable_empty'), 'info');
    echo html_writer::end_div();
    shell::end();
    echo $OUTPUT->footer();
    exit;
}

$choraklar = [];
foreach (timetable::terms($year) as $term) {
    $choraklar[$term->name] = $term->name;
}
$fanlar = exam::fanlar($year);
$darajalar = exam::darajalar($year);

// ================================================================== RO'YXAT
if ($view === 'royxat') {

    echo html_writer::div(html_writer::link($url(['view' => 'yangi']),
        '+ ' . $t('bh_new'), ['class' => 'btn btn-primary']), 'mb-3');

    // --- filtrlar (yozuvlar lentasi bilan bir xil ko'rinish)
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-dir-filters',
        'action' => (new moodle_url('/local/targetzenit/baholash.php'))->out(false)]);
    echo html_writer::div(
        html_writer::tag('label', $t('bh_term'))
        . html_writer::select(['' => $t('bh_allterms')] + $choraklar, 'chorak',
            $chorak, false), 'tz-dir-f');
    echo html_writer::div(
        html_writer::tag('label', $t('bh_type'))
        . html_writer::select(['' => $t('bh_alltypes')] + exam::turlar_menu(),
            'turi', $turi, false), 'tz-dir-f');
    echo html_writer::div(
        html_writer::tag('label', $t('subject'))
        . html_writer::select(['' => $t('bh_allsubjects')] + $fanlar, 'fan',
            $fan, false), 'tz-dir-f');
    echo html_writer::div(
        html_writer::empty_tag('input', ['type' => 'submit',
            'class' => 'btn btn-primary btn-sm', 'value' => $t('rec_apply')])
        . ' ' . html_writer::link(new moodle_url('/local/targetzenit/baholash.php'),
            $t('rec_reset'), ['class' => 'btn btn-sm btn-link']), 'tz-dir-f');
    echo html_writer::end_tag('form');

    $rows = exam::royxat($year, ['termname' => $chorak, 'turi' => $turi,
                                 'subjectkey' => $fan]);

    // Plitalar ro'yxatning O'ZIDAN hisoblanadi — alohida so'rov yozilsa u
    // filtrdan uzilib qoladi.
    $retro = 0;
    $tolib = 0;
    foreach ($rows as $r) {
        if ((int) $r->retro === 1) {
            $retro++;
        }
        if ((int) $r->natijalar > 0) {
            $tolib++;
        }
    }
    echo html_writer::div(
        ui::tile((string) count($rows), $t('bh_count'))
        . ui::tile((string) $tolib, $t('bh_withresults'), $t('bh_withresultshint'))
        . ui::tile((string) $retro, $t('bh_retro'), $t('bh_retrohint')),
        'tz-dir-hero');

    if (!$rows) {
        echo $OUTPUT->notification($t('bh_empty'), 'info');
    } else {
        echo html_writer::start_div('tz-dir-card');
        echo html_writer::start_div('tz-dir-tablewrap');
        echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);
        echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
        foreach (['bh_date', 'bh_type', 'bh_name', 'bh_form', 'bh_groups',
                  'bh_scores', 'bh_status'] as $k) {
            echo html_writer::tag('th', $t($k));
        }
        echo html_writer::end_tag('tr') . html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($rows as $r) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td',
                html_writer::div(userdate(strtotime($r->examdate), '%d.%m.%Y'))
                . ($r->starttime
                    ? html_writer::div(s($r->starttime), 'tz-dir-sub') : ''));
            echo html_writer::tag('td', html_writer::tag('span',
                $t('bh_turi_' . $r->turi), ['class' => 'tz-dir-chip']));
            echo html_writer::tag('td', html_writer::link(
                $url(['view' => 'jadval', 'id' => $r->id]), s($r->name)));
            echo html_writer::tag('td', $t('bh_shakli_' . $r->shakli)
                . ((int) $r->retro === 1
                    ? ' ' . html_writer::tag('span', $t('bh_retro'),
                        ['class' => 'tz-dir-chip tz-dir-warning']) : ''));
            echo html_writer::tag('td', (string) $r->sittings);
            echo html_writer::tag('td', (string) $r->natijalar);
            echo html_writer::tag('td', html_writer::tag('span',
                $t('bh_status_' . $r->status),
                ['class' => 'tz-dir-chip'
                    . ($r->status === 'yopildi' ? ' tz-dir-good' : '')]));
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
        echo html_writer::end_div() . html_writer::end_div();
    }
}

// ============================================================ YARATISH FORMASI
if ($view === 'yangi') {
    $e = $id ? exam::get($id) : null;

    echo html_writer::div(html_writer::link($url(['view' => 'royxat']),
        '← ' . $t('bh_navall'), ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-2');

    echo html_writer::start_div('tz-dir-card tz-bh-form');
    echo html_writer::tag('h4', $e ? $t('bh_edit') : $t('bh_new'),
        ['class' => 'tz-dir-cardtitle']);

    echo html_writer::start_tag('form', ['method' => 'post',
        'action' => (new moodle_url('/local/targetzenit/baholash.php'))->out(false)]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'amal', 'value' => 'saqla']);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'examid', 'value' => $e ? $e->id : 0]);

    /** Bitta maydon: yorliq + izoh + element. */
    $maydon = function(string $yorliq, string $ichi, string $izoh = '') {
        return html_writer::div(
            html_writer::tag('label', $yorliq, ['class' => 'tz-dir-lab'])
            . $ichi
            . ($izoh !== '' ? html_writer::div($izoh, 'tz-dir-sub') : ''),
            'tz-bh-field');
    };

    echo html_writer::start_div('tz-bh-grid');

    echo $maydon($t('bh_type'), html_writer::select(exam::turlar_menu(), 'f_turi',
        $e ? $e->turi : 'CHSB', false), $t('bh_typehint'));

    echo $maydon($t('bh_term'), html_writer::select($choraklar, 'f_chorak',
        $e ? $e->termname : '', ['' => $t('bh_choose')]));

    echo $maydon($t('subject'), html_writer::select($fanlar, 'f_fan',
        $e ? $e->subjectkey : '', ['' => $t('bh_choose')]));

    echo $maydon($t('bh_level'), html_writer::select($darajalar, 'f_daraja',
        $e ? $e->gradelevel : '', ['' => $t('bh_choose')]), $t('bh_levelhint'));

    echo $maydon($t('bh_form'), html_writer::select(exam::shakllar_menu(),
        'f_shakli', $e ? $e->shakli : 'qogoz', false), $t('bh_formhint'));

    echo $maydon($t('bh_date'), html_writer::empty_tag('input',
        ['type' => 'date', 'name' => 'f_sana', 'required' => 'required',
         'value' => $e ? $e->examdate : date('Y-m-d')]), $t('bh_datehint'));

    echo $maydon($t('bh_time'), html_writer::empty_tag('input',
        ['type' => 'time', 'name' => 'f_vaqt',
         'value' => $e ? (string) $e->starttime : '']), $t('bh_timehint'));

    echo $maydon($t('bh_duration'), html_writer::empty_tag('input',
        ['type' => 'number', 'name' => 'f_davomiylik', 'min' => 5, 'max' => 300,
         'value' => $e ? $e->duration : 45]));

    echo $maydon($t('bh_room'), html_writer::empty_tag('input',
        ['type' => 'text', 'name' => 'f_xona',
         'value' => $e ? (string) $e->room : '']), $t('bh_roomhint'));

    echo $maydon($t('bh_maxscore'), html_writer::empty_tag('input',
        ['type' => 'number', 'name' => 'f_maxball', 'min' => 1, 'max' => 1000,
         'value' => $e ? $e->maxscore : 100]), $t('bh_maxscorehint'));

    echo $maydon($t('bh_random'), html_writer::empty_tag('input',
        ['type' => 'number', 'name' => 'f_tasodif', 'min' => 0, 'max' => 200,
         'value' => $e ? $e->randomcount : 0]), $t('bh_randomhint'));

    echo html_writer::end_div();   // .tz-bh-grid

    // Nom oxirida: bo'sh qoldirilsa tizim o'zi yasaydi
    echo $maydon($t('bh_name'), html_writer::empty_tag('input',
        ['type' => 'text', 'name' => 'f_nom', 'size' => 60,
         'value' => $e ? $e->name : '']), $t('bh_namehint'));

    echo html_writer::div(html_writer::empty_tag('input', ['type' => 'submit',
        'class' => 'btn btn-primary', 'value' => $t('bh_savenext')]), 'mt-3');
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

// ======================================================= IMTIHON JADVALI
if ($view === 'jadval') {
    $e = $id ? exam::get($id) : null;
    if (!$e) {
        echo $OUTPUT->notification($t('bh_noexam'), 'warn');
        echo html_writer::end_div();
        shell::end();
        echo $OUTPUT->footer();
        exit;
    }

    echo html_writer::div(
        html_writer::link($url(['view' => 'royxat']), '← ' . $t('bh_navall'),
            ['class' => 'btn btn-sm btn-outline-secondary'])
        . ' ' . html_writer::link($url(['view' => 'yangi', 'id' => $e->id]),
            $t('bh_edit'), ['class' => 'btn btn-sm btn-outline-secondary']),
        'mb-2');

    echo html_writer::tag('h4', s($e->name), ['class' => 'tz-dir-cardtitle'])
        . html_writer::div(
            $t('bh_turi_' . $e->turi) . ' · ' . $t('bh_shakli_' . $e->shakli)
            . ' · ' . userdate(strtotime($e->examdate), '%d.%m.%Y')
            . ($e->starttime ? ' ' . s($e->starttime) : '')
            . ' · ' . $t('bh_maxscore') . ': ' . $e->maxscore
            . ((int) $e->retro === 1 ? ' · ' . $t('bh_retro') : ''),
            'tz-dir-sub mb-3');

    // --- O'tgan davr ogohlantirishi: nega quiz yaratilmaydi
    if ((int) $e->retro === 1) {
        echo $OUTPUT->notification($t('bh_retronotice'), 'info');
    }

    // --- TO'QNASHUVLAR
    //
    // Kelajak sanada bu TO'SIQ bo'ladi (6-bosqichda generatsiya to'xtaydi),
    // o'tgan davrda esa faqat ogohlantiradi — o'tmishni tuzatib bo'lmaydi.
    $toqnash = exam::toqnashuv((int) $e->id);
    if ($toqnash) {
        $h = html_writer::div($t((int) $e->retro === 1
            ? 'bh_clash_retro' : 'bh_clash_future'), 'tz-dir-lab');
        $lst = '';
        foreach ($toqnash as $x) {
            $lst .= html_writer::tag('li',
                html_writer::tag('b', s($x['guruh'])) . ' · '
                . userdate(strtotime($x['sana']), '%d.%m.%Y') . ' — ' . s($x['xabar']));
        }
        $h .= html_writer::tag('ul', $lst);
        echo html_writer::div($h, 'tz-bh-clash');
    }

    $sittings = exam::sittings((int) $e->id);

    echo html_writer::start_tag('form', ['method' => 'post',
        'action' => (new moodle_url('/local/targetzenit/baholash.php'))->out(false)]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'id', 'value' => $e->id]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'amal', 'value' => 'jadval']);

    if (!$sittings) {
        echo $OUTPUT->notification($t('bh_nogroups'), 'warn');
    } else {
        echo html_writer::start_div('tz-dir-card');
        echo html_writer::start_div('tz-dir-tablewrap');
        echo html_writer::start_tag('table', ['class' => 'tz-dir-table tz-bh-sit']);
        echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
        foreach (['bh_group', 'bh_date', 'bh_time', 'bh_room', 'bh_status'] as $k) {
            echo html_writer::tag('th', $t($k));
        }
        echo html_writer::end_tag('tr') . html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($sittings as $s) {
            $q = exam::qachon($e, $s);
            $k = 's' . $s->id;

            echo html_writer::start_tag('tr');
            echo html_writer::tag('td',
                html_writer::tag('b', s($s->groupname))
                . html_writer::div(s((string) $s->coursename), 'tz-dir-sub'));

            // Bo'sh maydon = baholashdan MEROS. Placeholder'da meros
            // qiymati ko'rinib turadi, shunda koordinator «bo'sh qoldirsam
            // nima bo'ladi» deb o'ylamaydi.
            echo html_writer::tag('td', html_writer::empty_tag('input',
                ['type' => 'date', 'name' => 'sana[' . $k . ']',
                 'value' => (string) ($s->examdate ?? ''),
                 'title' => $t('bh_inherit', $e->examdate)]));

            echo html_writer::tag('td', html_writer::empty_tag('input',
                ['type' => 'time', 'name' => 'vaqt[' . $k . ']',
                 'value' => (string) ($s->starttime ?? ''),
                 'title' => $t('bh_inherit', (string) $e->starttime)]));

            echo html_writer::tag('td', html_writer::empty_tag('input',
                ['type' => 'text', 'name' => 'xona[' . $k . ']', 'size' => 10,
                 'value' => (string) ($s->room ?? ''),
                 'placeholder' => (string) $e->room,
                 'title' => $t('bh_inherit', (string) $e->room)]));

            // HAQIQIY sana/vaqt — meros hisobga olingan holda. Koordinator
            // bo'sh maydon nimaga aylanganini KO'RIB turishi kerak, aks holda
            // «qo'ymadim, demak yo'q» deb o'ylaydi.
            echo html_writer::tag('td',
                html_writer::tag('span', $t('bh_sit_' . $s->status),
                    ['class' => 'tz-dir-chip'
                        . ($s->status === 'xato' ? ' tz-dir-critical' : '')])
                . html_writer::div(userdate(strtotime($q['examdate']), '%d.%m.%Y')
                    . ($q['starttime'] ? ' · ' . s($q['starttime']) : '')
                    . ($q['room'] ? ' · ' . s($q['room']) : ''), 'tz-dir-sub')
                . ($s->xato ? html_writer::div(s($s->xato), 'tz-dir-sub') : ''));
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
        echo html_writer::end_div() . html_writer::end_div();

        echo html_writer::div(html_writer::empty_tag('input', ['type' => 'submit',
            'class' => 'btn btn-primary', 'value' => $t('bh_savetimetable')])
            . ' ' . html_writer::tag('span', $t('bh_inherithint'),
                ['class' => 'tz-dir-sub']), 'mt-3');
    }
    echo html_writer::end_tag('form');

    // --- Guruhlarni qayta biriktirish (yangi guruh qo'shilgan bo'lsa)
    echo html_writer::div(html_writer::link(
        $url(['view' => 'jadval', 'id' => $e->id, 'amal' => 'guruhlar',
              'sesskey' => sesskey()]),
        $t('bh_refreshgroups'), ['class' => 'btn btn-sm btn-outline-secondary'])
        . ' ' . html_writer::tag('span', $t('bh_refreshhint'),
            ['class' => 'tz-dir-sub']), 'mt-4');
}

echo html_writer::end_div();   // .tz-bh
shell::end();
echo $OUTPUT->footer();
