<?php
// PSIXOLOG PANELI — butun maktab (1–11 sinf + xodimlar) bilan ishlash:
// individual suhbat qaydlari, muammolar (case), test/so'rovnoma, statistika.
// PSIXOLOG_ARXITEKTURA.md.
//
// MAXFIYLIK: suhbat/case MATNI faqat `local/targetzenit:psixolog` ruxsati
// borga. Rahbariyat (`viewteachers`) faqat `stat` ko'rinishini ochadi —
// sonlar, matn va ism YO'Q (foydalanuvchi qarori, 2026-11-21).

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\output\shell;
use local_targetzenit\psixolog;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/psixolog.php'));

$psixologmi = has_capability('local/targetzenit:psixolog', $context);
$rahbarmi = has_capability('local/targetzenit:viewteachers', $context);

$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

if (!$psixologmi && !$rahbarmi) {
    // `shell::setup()` ATAYLAB chaqirilmaydi — embedded maketda ruxsati
    // yo'q odam menyusiz qamalib qoladi (murojaat.php bilan bir xil).
    $PAGE->set_title($t('psix_panel'));
    $PAGE->set_heading($t('psix_panel'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification($t('psix_noaccess'), 'error');
    echo $OUTPUT->footer();
    exit;
}

$view = optional_param('view', $psixologmi ? 'oquvchilar' : 'stat', PARAM_ALPHA);
if (!$psixologmi) {
    $view = 'stat';                       // rahbariyatga faqat statistika.
}

$kurs = psixolog::kurs();

// --------------------------------------------------------------- AMALLAR
// Hammasi POST + sesskey (GET bilan yozish brauzer prefetch'ida ham ishlardi).
$amal = optional_param('amal', '', PARAM_ALPHA);
if ($amal !== '' && $psixologmi && confirm_sesskey()) {

    $qayt = new moodle_url('/local/targetzenit/psixolog.php',
        ['view' => optional_param('qayt', $view, PARAM_ALPHA)]);

    switch ($amal) {
        case 'suhbat':
            $uid = required_param('userid', PARAM_INT);
            $odam = core_user::get_user($uid, '*', MUST_EXIST);
            $sana = strtotime(optional_param('sana', '', PARAM_TEXT)) ?: time();
            $caseid = optional_param('caseid', 0, PARAM_INT);
            psixolog::suhbat_qosh($uid, psixolog::persontype($odam), $sana,
                required_param('turi', PARAM_ALPHA),
                required_param('matn', PARAM_TEXT),
                optional_param('tavsiya', '', PARAM_TEXT), $caseid);
            if ($caseid) {
                $qayt->params(['view' => 'caselar', 'ochiq' => $caseid]);
            }
            break;

        case 'suhbatochir':
            psixolog::suhbat_ochir(required_param('id', PARAM_INT));
            break;

        case 'caseadd':
            $uid = required_param('userid', PARAM_INT);
            $odam = core_user::get_user($uid, '*', MUST_EXIST);
            $id = psixolog::case_qosh($uid, psixolog::persontype($odam),
                required_param('sarlavha', PARAM_TEXT),
                optional_param('tavsif', '', PARAM_TEXT),
                required_param('manba', PARAM_ALPHA),
                required_param('muhimlik', PARAM_ALPHA));
            $qayt->params(['view' => 'caselar', 'ochiq' => $id]);
            break;

        case 'caseholat':
            $id = required_param('id', PARAM_INT);
            psixolog::case_holat($id, required_param('holat', PARAM_ALPHA));
            $qayt->params(['view' => 'caselar', 'ochiq' => $id]);
            break;

        case 'caseyop':
            $id = required_param('id', PARAM_INT);
            psixolog::case_yop($id, required_param('xulosa', PARAM_TEXT));
            $qayt->params(['view' => 'caselar', 'ochiq' => $id]);
            break;

        // ------------------------- tashqi (qog'oz) test natijalari
        case 'testadd':
            $sana = strtotime(optional_param('sana', '', PARAM_TEXT)) ?: time();
            $id = psixolog::test_qosh(required_param('nomi', PARAM_TEXT), $sana,
                optional_param('izoh', '', PARAM_TEXT));
            // Yaratilgach to'g'ri natija kiritishga o'tiladi — psixologning
            // navbatdagi ishi aynan shu.
            $qayt->params(['view' => 'natija', 'test' => $id]);
            break;

        case 'testochir':
            psixolog::test_ochir(required_param('id', PARAM_INT));
            $qayt->params(['view' => 'testlar']);
            break;

        case 'natijasaqla':
            $testid = required_param('test', PARAM_INT);
            if (!psixolog::test_get($testid)) {
                throw new moodle_exception('invalidrecord', 'error');
            }
            // Setkadagi qatorlar `ids` da keladi; maydon nomlari ball_<id> /
            // izoh_<id> — Moodle formasida massiv kalit qoidasi ([a-z0-9_-])
            // buzilmasin (jurnal saboqlari, 0.9.4).
            $ids = array_filter(array_map('intval',
                explode(',', optional_param('ids', '', PARAM_SEQUENCE))));
            foreach ($ids as $uid) {
                psixolog::natija_saqla($testid, $uid,
                    optional_param('ball_' . $uid, '', PARAM_TEXT),
                    optional_param('izoh_' . $uid, '', PARAM_TEXT));
            }
            $qayt->params(['view' => 'natija', 'test' => $testid,
                'guruh' => optional_param('guruh', 0, PARAM_INT),
                'saqlandi' => 1]);
            break;
    }
    redirect($qayt);
}

// ---------------------------------------------------------- yordamchilar

/** Yashirin maydonlar — amal formalarida (murojaat naqshi). */
function tz_psix_hidden(string $amal, array $maydonlar = []): string {
    $h = '';
    foreach (['sesskey' => sesskey(), 'amal' => $amal] + $maydonlar as $k => $v) {
        $h .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => $k, 'value' => $v]);
    }
    return $h;
}

/** Oddiy select (html_writer::select ning `nothing` tuzog'isiz). */
function tz_psix_select(string $nom, array $opts, string $joriy,
                         ?string $bosh = null): string {
    $o = '';
    if ($bosh !== null) {
        $o .= html_writer::tag('option', s($bosh), ['value' => '']);
    }
    foreach ($opts as $k => $v) {
        $attr = ['value' => s((string) $k)];
        if ((string) $k === $joriy) {
            $attr['selected'] = 'selected';
        }
        $o .= html_writer::tag('option', s((string) $v), $attr);
    }
    return html_writer::tag('select', $o, ['name' => $nom]);
}

/** Yangi suhbat qaydi formasi (odam oldindan tanlangan holda). */
function tz_psix_suhbat_form(stdClass $odam, int $caseid, string $qayt): string {
    $t = fn(string $k) => get_string($k, 'local_targetzenit');

    $turlar = [];
    foreach (psixolog::SUHBAT_TURLARI as $k) {
        $turlar[$k] = $t('psix_t_' . $k);
    }

    $f = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-form']);
    $f .= tz_psix_hidden('suhbat', ['userid' => $odam->id, 'caseid' => $caseid,
        'qayt' => $qayt]);
    $f .= html_writer::div(html_writer::tag('b', s(fullname($odam))), 'tz-mur-ism');
    $f .= html_writer::start_div('tz-mur-filtr');
    $f .= html_writer::empty_tag('input', ['type' => 'date', 'name' => 'sana',
        'value' => date('Y-m-d')]);
    $f .= tz_psix_select('turi', $turlar, 'individual');
    $f .= html_writer::end_div();
    $f .= html_writer::tag('textarea', '', ['name' => 'matn', 'rows' => 4,
        'required' => 'required', 'class' => 'tz-fb-ctext',
        'placeholder' => $t('psix_talk_text')]);
    $f .= html_writer::tag('textarea', '', ['name' => 'tavsiya', 'rows' => 2,
        'class' => 'tz-fb-ctext', 'placeholder' => $t('psix_talk_advice')]);
    $f .= html_writer::div($t('psix_confidential'), 'tz-mur-ogoh');
    $f .= html_writer::tag('button', $t('psix_save'),
        ['type' => 'submit', 'class' => 'tz-fb-csend']);
    $f .= html_writer::end_tag('form');
    return $f;
}

// --------------------------------------------------------------- QOBIQ
shell::setup($PAGE, $t('psix_panel'));
echo $OUTPUT->header();

$sub = $kurs ? format_string($kurs->fullname) : $t('psix_nokurs_short');
// Natija kiritish — «Testlar» bo'limining ichki sahifasi, menyuda o'sha band yonadi.
shell::start_menu($view === 'natija' ? 'testlar' : $view,
    $t('psix_panel'), $sub, '', shell::psix_menu());

echo html_writer::start_div('tz-mur');

$pill = fn(string $matn, string $cls = '') => html_writer::tag('span', s($matn),
    ['class' => 'tz-mur-pill' . ($cls ? ' ' . $cls : '')]);
$selfurl = fn(array $p = []) => new moodle_url('/local/targetzenit/psixolog.php', $p);

$turi_nomi = fn(string $k) => $t('psix_t_' . $k);
$manba_nomi = fn(string $k) => $t('psix_src_' . $k);
$muhim_nomi = fn(string $k) => $t('psix_imp_' . $k);
$holat_nomi = fn(string $k) => $t('psix_st_' . $k);

switch ($view) {

// ============================================================ O'QUVCHILAR
case 'oquvchilar':
default:
    if (!$kurs) {
        echo $OUTPUT->notification($t('psix_nokurs'), 'warning');
        break;
    }

    $guruhid = optional_param('guruh', 0, PARAM_INT);
    $qidiruv = trim(optional_param('q', '', PARAM_TEXT));
    $guruhlar = psixolog::guruhlar((int) $kurs->id);

    // Filtr formasi (GET).
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-mur-filtr']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view',
        'value' => 'oquvchilar']);
    echo tz_psix_select('guruh', $guruhlar, (string) $guruhid, $t('psix_allgroups'));
    echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q',
        'value' => $qidiruv, 'placeholder' => $t('psix_search')]);
    echo html_writer::tag('button', $t('mur_apply'),
        ['type' => 'submit', 'class' => 'tz-mur-btn']);
    echo html_writer::end_tag('form');

    if (!$guruhid && $qidiruv === '') {
        echo html_writer::div($t('psix_pickgroup'), 'tz-dir-none');
        break;
    }

    $odamlar = psixolog::odamlar((int) $kurs->id, $guruhid, $qidiruv);
    if (!$odamlar) {
        echo html_writer::div($t('psix_none'), 'tz-dir-none');
        break;
    }

    $ids = array_keys($odamlar);
    $oxirgi = psixolog::oxirgi_suhbat($ids);
    $caselar = psixolog::ochiq_case($ids);
    $xarita = $guruhid ? [] : psixolog::guruh_xarita((int) $kurs->id);

    $rows = '';
    foreach ($odamlar as $o) {
        $sinf = $guruhid ? ($guruhlar[$guruhid] ?? '') : ($xarita[(int) $o->id] ?? '—');
        $amallar =
            html_writer::link($selfurl(['view' => 'suhbatlar', 'userid' => $o->id]),
                $t('psix_talk_add'), ['class' => 'tz-dir-chip'])
            . ' ' . html_writer::link($selfurl(['view' => 'caselar', 'userid' => $o->id]),
                $t('psix_case_add'), ['class' => 'tz-dir-chip']);
        if (psixolog::persontype($o) === 'student') {
            $amallar .= ' ' . html_writer::link(
                new moodle_url('/local/targetzenit/oquvchi360.php', ['userid' => $o->id]),
                $t('psix_card360'), ['class' => 'tz-dir-chip']);
        }
        $rows .= html_writer::tag('tr',
            html_writer::tag('td', s(fullname($o)))
            . html_writer::tag('td', s($sinf))
            . html_writer::tag('td', isset($oxirgi[(int) $o->id])
                ? userdate($oxirgi[(int) $o->id], get_string('strftimedateshort')) : '—')
            . html_writer::tag('td', empty($caselar[(int) $o->id]) ? '—'
                : $pill((string) $caselar[(int) $o->id], 'tz-mur-p-warn'))
            . html_writer::tag('td', $amallar));
    }
    echo html_writer::div(html_writer::tag('table',
        html_writer::tag('thead', html_writer::tag('tr',
            html_writer::tag('th', $t('psix_person'))
            . html_writer::tag('th', $t('psix_group'))
            . html_writer::tag('th', $t('psix_lasttalk'))
            . html_writer::tag('th', $t('psix_opencases'))
            . html_writer::tag('th', '')))
        . html_writer::tag('tbody', $rows),
        ['class' => 'tz-dir-table']), 'tz-dir-tablewrap');
    break;

// ============================================================= SUHBATLAR
case 'suhbatlar':
    $uid = optional_param('userid', 0, PARAM_INT);
    $caseid = optional_param('caseid', 0, PARAM_INT);
    $fturi = optional_param('turi', '', PARAM_ALPHA);
    $qidiruv = trim(optional_param('q', '', PARAM_TEXT));

    // Yangi qayd — odam ro'yxatdan tanlab kelinganda forma ochiq turadi.
    if ($uid && ($odam = core_user::get_user($uid))) {
        echo html_writer::tag('details',
            html_writer::tag('summary', $t('psix_talk_add'), ['class' => 'tz-mur-sum'])
            . tz_psix_suhbat_form($odam, $caseid, 'suhbatlar'),
            ['open' => 'open', 'class' => 'tz-mur-blok']);
    } else {
        echo html_writer::div($t('psix_talk_hint') . ' '
            . html_writer::link($selfurl(['view' => 'oquvchilar']),
                $t('psix_nav_people'), ['class' => 'tz-dir-chip']),
            'tz-mur-ogoh');
    }

    // Filtr.
    $turlar = [];
    foreach (psixolog::SUHBAT_TURLARI as $k) {
        $turlar[$k] = $turi_nomi($k);
    }
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-mur-filtr']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view',
        'value' => 'suhbatlar']);
    echo tz_psix_select('turi', $turlar, $fturi, $t('psix_all'));
    echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q',
        'value' => $qidiruv, 'placeholder' => $t('psix_search')]);
    echo html_writer::tag('button', $t('mur_apply'),
        ['type' => 'submit', 'class' => 'tz-mur-btn']);
    echo html_writer::end_tag('form');

    $sahifa = optional_param('p', 0, PARAM_INT);
    $filtr = ['turi' => $fturi, 'q' => $qidiruv];
    if ($uid) {
        $filtr['userid'] = $uid;
    }
    if ($caseid) {
        $filtr['caseid'] = $caseid;
    }
    $lenta = psixolog::suhbatlar($filtr, $sahifa);
    if (!$lenta) {
        echo html_writer::div($t('psix_none'), 'tz-dir-none');
        break;
    }

    foreach ($lenta as $sh) {
        $chips = $pill($turi_nomi($sh->turi));
        if ($sh->casenomi) {
            $chips .= $pill($sh->casenomi, 'tz-mur-p-warn');
        }
        $sarlavha = html_writer::tag('summary',
            html_writer::div(userdate((int) $sh->sana, get_string('strftimedateshort'))
                . ' · ' . s(fullname($sh)), 'tz-mur-ism')
            . html_writer::div(s(shorten_text($sh->matn, 160)), 'tz-mur-qisqa')
            . html_writer::div($chips, 'tz-mur-pills'),
            ['class' => 'tz-mur-sum']);

        $ichi = html_writer::div(format_text($sh->matn, FORMAT_PLAIN), 'tz-mur-quote');
        if (trim((string) $sh->tavsiya) !== '') {
            $ichi .= html_writer::div(
                html_writer::tag('b', $t('psix_talk_advice')) . ' '
                . format_text($sh->tavsiya, FORMAT_PLAIN), 'tz-mur-sec');
        }
        if ((int) $sh->psixologid === (int) $USER->id) {
            $of = html_writer::start_tag('form', ['method' => 'post',
                'style' => 'display:inline',
                'onsubmit' => "return confirm(" . json_encode($t('psix_delete') . '?') . ")"]);
            $of .= tz_psix_hidden('suhbatochir', ['id' => $sh->id, 'qayt' => 'suhbatlar']);
            $of .= html_writer::tag('button', $t('psix_delete'),
                ['type' => 'submit', 'class' => 'tz-dir-chip']);
            $of .= html_writer::end_tag('form');
            $ichi .= html_writer::div($of, 'tz-mur-sec');
        }

        echo html_writer::tag('details', $sarlavha . $ichi, ['class' => 'tz-mur-blok']);
    }
    if (count($lenta) === 30) {
        echo html_writer::link($selfurl(array_merge(['view' => 'suhbatlar',
            'p' => $sahifa + 1, 'turi' => $fturi, 'q' => $qidiruv],
            $uid ? ['userid' => $uid] : [])),
            $t('psix_more'), ['class' => 'tz-mur-btn tz-mur-btn2']);
    }
    break;

// =============================================================== CASELAR
case 'caselar':
    $uid = optional_param('userid', 0, PARAM_INT);
    $fholat = optional_param('holat', '', PARAM_ALPHA);
    $qidiruv = trim(optional_param('q', '', PARAM_TEXT));
    $ochiqid = optional_param('ochiq', 0, PARAM_INT);

    // Yangi case — odam ro'yxatdan tanlab kelinganda.
    if ($uid && ($odam = core_user::get_user($uid))) {
        $manbalar = [];
        foreach (psixolog::CASE_MANBALAR as $k) {
            $manbalar[$k] = $manba_nomi($k);
        }
        $muhimliklar = [];
        foreach (psixolog::CASE_MUHIMLIK as $k) {
            $muhimliklar[$k] = $muhim_nomi($k);
        }
        $f = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-form']);
        $f .= tz_psix_hidden('caseadd', ['userid' => $uid, 'qayt' => 'caselar']);
        $f .= html_writer::div(html_writer::tag('b', s(fullname($odam))), 'tz-mur-ism');
        $f .= html_writer::empty_tag('input', ['type' => 'text', 'name' => 'sarlavha',
            'required' => 'required', 'placeholder' => $t('psix_case_title'),
            'class' => 'tz-fb-ctext']);
        $f .= html_writer::tag('textarea', '', ['name' => 'tavsif', 'rows' => 3,
            'class' => 'tz-fb-ctext', 'placeholder' => $t('psix_case_desc')]);
        $f .= html_writer::start_div('tz-mur-filtr');
        $f .= tz_psix_select('manba', $manbalar, 'kuzatuv');
        $f .= tz_psix_select('muhimlik', $muhimliklar, 'orta');
        $f .= html_writer::end_div();
        $f .= html_writer::div($t('psix_confidential'), 'tz-mur-ogoh');
        $f .= html_writer::tag('button', $t('psix_save'),
            ['type' => 'submit', 'class' => 'tz-fb-csend']);
        $f .= html_writer::end_tag('form');
        echo html_writer::tag('details',
            html_writer::tag('summary', $t('psix_case_add'), ['class' => 'tz-mur-sum'])
            . $f, ['open' => 'open', 'class' => 'tz-mur-blok']);
    }

    // Holat chiplari + qidiruv.
    $chip = function (string $kod, string $nom) use ($selfurl, $fholat, $qidiruv) {
        return html_writer::link($selfurl(['view' => 'caselar', 'holat' => $kod,
            'q' => $qidiruv]), $nom,
            ['class' => 'tz-dir-chip' . ($fholat === $kod ? ' tz-on' : '')]);
    };
    $chiplar = $chip('', $t('psix_all'));
    foreach (psixolog::CASE_HOLATLAR as $k) {
        $chiplar .= ' ' . $chip($k, $holat_nomi($k));
    }
    echo html_writer::div($chiplar, 'tz-mur-filtr');
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-mur-filtr']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view',
        'value' => 'caselar']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'holat',
        'value' => $fholat]);
    echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q',
        'value' => $qidiruv, 'placeholder' => $t('psix_search')]);
    echo html_writer::tag('button', $t('mur_apply'),
        ['type' => 'submit', 'class' => 'tz-mur-btn']);
    echo html_writer::end_tag('form');

    $royxat = psixolog::caselar($fholat, $qidiruv);
    if (!$royxat) {
        echo html_writer::div($t('psix_none'), 'tz-dir-none');
        break;
    }
    $sonlar = psixolog::case_suhbat_soni(array_keys($royxat));

    foreach ($royxat as $c) {
        $yopiq = $c->holat === 'yopildi';
        $chips = $pill($holat_nomi($c->holat), $yopiq ? 'tz-mur-p-ok'
            : ($c->holat === 'ochiq' ? 'tz-mur-p-warn' : ''));
        $chips .= $pill($muhim_nomi($c->muhimlik),
            $c->muhimlik === 'yuqori' ? 'tz-mur-p-bad' : '');
        $chips .= $pill($manba_nomi($c->manba));
        if (!empty($sonlar[(int) $c->id])) {
            $chips .= $pill($t('psix_talks_n', $sonlar[(int) $c->id]));
        }

        $sarlavha = html_writer::tag('summary',
            html_writer::div(s(fullname($c)) . ' — ' . s($c->sarlavha), 'tz-mur-ism')
            . html_writer::div($chips, 'tz-mur-pills'),
            ['class' => 'tz-mur-sum']);

        $ichi = '';
        if (trim((string) $c->tavsif) !== '') {
            $ichi .= html_writer::div(format_text($c->tavsif, FORMAT_PLAIN), 'tz-mur-quote');
        }
        if ($yopiq && trim((string) $c->xulosa) !== '') {
            $ichi .= html_writer::div(
                html_writer::tag('b', $t('psix_case_concl')) . ' '
                . format_text($c->xulosa, FORMAT_PLAIN)
                . html_writer::div(userdate((int) $c->yopilganvaqti,
                    get_string('strftimedateshort')), 'tz-mur-qisqa'), 'tz-mur-sec');
        }

        $ichi .= html_writer::div(
            html_writer::link($selfurl(['view' => 'suhbatlar', 'caseid' => $c->id,
                'userid' => $c->userid]), $t('psix_case_talks'),
                ['class' => 'tz-dir-chip'])
            . (psixolog::persontype($c) === 'student'
                ? ' ' . html_writer::link(new moodle_url('/local/targetzenit/oquvchi360.php',
                    ['userid' => $c->userid]), $t('psix_card360'),
                    ['class' => 'tz-dir-chip'])
                : ''),
            'tz-mur-sec');

        // Amallar: holat o'zgartirish / xulosa bilan yopish.
        if (!$yopiq) {
            $hf = '';
            foreach (['ochiq', 'kuzatuvda'] as $h) {
                if ($h === $c->holat) {
                    continue;
                }
                $bf = html_writer::start_tag('form',
                    ['method' => 'post', 'style' => 'display:inline']);
                $bf .= tz_psix_hidden('caseholat', ['id' => $c->id, 'holat' => $h,
                    'qayt' => 'caselar']);
                $bf .= html_writer::tag('button', '→ ' . $holat_nomi($h),
                    ['type' => 'submit', 'class' => 'tz-dir-chip']);
                $bf .= html_writer::end_tag('form');
                $hf .= $bf . ' ';
            }
            $yf = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-cform']);
            $yf .= tz_psix_hidden('caseyop', ['id' => $c->id, 'qayt' => 'caselar']);
            $yf .= html_writer::tag('textarea', '', ['name' => 'xulosa', 'rows' => 2,
                'required' => 'required', 'class' => 'tz-fb-ctext',
                'placeholder' => $t('psix_case_concl')]);
            $yf .= html_writer::tag('button', $t('psix_case_close'),
                ['type' => 'submit', 'class' => 'tz-fb-csend']);
            $yf .= html_writer::end_tag('form');
            $ichi .= html_writer::div($hf, 'tz-mur-sec') . $yf;
        }

        echo html_writer::tag('details', $sarlavha . $ichi,
            ['class' => 'tz-mur-blok']
            + ($ochiqid === (int) $c->id ? ['open' => 'open'] : []));
    }
    break;

// =============================================================== TESTLAR
case 'testlar':
    if (!$kurs) {
        echo $OUTPUT->notification($t('psix_nokurs'), 'warning');
        break;
    }

    // Yangi quiz/feedback — STANDART modedit (psixolog markaziy kursda
    // editingteacher, hamma yadro imkoniyati o'zida — imtihon moduli
    // tamoyili: qayta ixtiro YO'Q).
    $yangi = fn(string $mod) => new moodle_url('/course/modedit.php', [
        'add' => $mod, 'course' => $kurs->id, 'section' => 0, 'return' => 0, 'sr' => 0,
    ]);
    echo html_writer::div(
        html_writer::link($yangi('quiz'), '+ ' . $t('psix_tests_new_quiz'),
            ['class' => 'tz-mur-btn'])
        . ' ' . html_writer::link($yangi('feedback'), '+ ' . $t('psix_tests_new_fb'),
            ['class' => 'tz-mur-btn'])
        . ' ' . html_writer::link(new moodle_url('/course/view.php', ['id' => $kurs->id]),
            $t('psix_tests_course'), ['class' => 'tz-mur-btn tz-mur-btn2']),
        'tz-mur-filtr');
    echo html_writer::div($t('psix_tests_hint'), 'tz-mur-ogoh');

    // ------- TASHQI (QOG'OZ) TESTLAR — natijasi qo'lda kiritiladi.
    $tf = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-form']);
    $tf .= tz_psix_hidden('testadd', ['qayt' => 'testlar']);
    $tf .= html_writer::empty_tag('input', ['type' => 'text', 'name' => 'nomi',
        'required' => 'required', 'placeholder' => $t('psix_ext_name'),
        'class' => 'tz-fb-ctext']);
    $tf .= html_writer::start_div('tz-mur-filtr');
    $tf .= html_writer::empty_tag('input', ['type' => 'date', 'name' => 'sana',
        'value' => date('Y-m-d')]);
    $tf .= html_writer::end_div();
    $tf .= html_writer::tag('textarea', '', ['name' => 'izoh', 'rows' => 2,
        'class' => 'tz-fb-ctext', 'placeholder' => $t('psix_ext_note')]);
    $tf .= html_writer::tag('button', $t('psix_save'),
        ['type' => 'submit', 'class' => 'tz-fb-csend']);
    $tf .= html_writer::end_tag('form');

    echo html_writer::tag('details',
        html_writer::tag('summary',
            html_writer::div($t('psix_ext_title'), 'tz-mur-ism')
            . html_writer::div($t('psix_ext_hint'), 'tz-mur-qisqa'),
            ['class' => 'tz-mur-sum'])
        . html_writer::div('+ ' . $t('psix_ext_new'), 'tz-mur-lab') . $tf,
        ['class' => 'tz-mur-blok']);

    $tashqi = psixolog::testlar_tashqi();
    if ($tashqi) {
        $trows = '';
        foreach ($tashqi as $ts) {
            $of = html_writer::start_tag('form', ['method' => 'post',
                'style' => 'display:inline',
                'onsubmit' => "return confirm(" . json_encode($t('psix_delete') . '?') . ")"]);
            $of .= tz_psix_hidden('testochir', ['id' => $ts->id, 'qayt' => 'testlar']);
            $of .= html_writer::tag('button', $t('psix_delete'),
                ['type' => 'submit', 'class' => 'tz-dir-chip']);
            $of .= html_writer::end_tag('form');
            $trows .= html_writer::tag('tr',
                html_writer::tag('td', s($ts->nomi))
                . html_writer::tag('td', userdate((int) $ts->sana,
                    get_string('strftimedateshort')))
                . html_writer::tag('td', (string) $ts->natijalar)
                . html_writer::tag('td',
                    html_writer::link($selfurl(['view' => 'natija', 'test' => $ts->id]),
                        $t('psix_ext_enter'), ['class' => 'tz-dir-chip'])
                    . ' ' . ((int) $ts->psixologid === (int) $USER->id ? $of : '')));
        }
        echo html_writer::div(html_writer::tag('table',
            html_writer::tag('thead', html_writer::tag('tr',
                html_writer::tag('th', $t('psix_tests_name'))
                . html_writer::tag('th', $t('psix_ext_date'))
                . html_writer::tag('th', $t('psix_tests_done'))
                . html_writer::tag('th', '')))
            . html_writer::tag('tbody', $trows),
            ['class' => 'tz-dir-table']), 'tz-dir-tablewrap');
    }

    // ------- MOODLE'DAGI (onlayn) testlar.
    echo html_writer::div($t('psix_online_title'), 'tz-mur-lab');
    $testlar = psixolog::testlar($kurs);
    if (!$testlar) {
        echo html_writer::div($t('psix_tests_none'), 'tz-dir-none');
        break;
    }

    $rows = '';
    foreach ($testlar as $ts) {
        $natija = $ts->modname === 'quiz'
            ? new moodle_url('/mod/quiz/report.php', ['id' => $ts->cmid, 'mode' => 'overview'])
            : new moodle_url('/mod/feedback/analysis.php', ['id' => $ts->cmid]);
        $rows .= html_writer::tag('tr',
            html_writer::tag('td',
                html_writer::link($ts->url, s($ts->nomi))
                . (!$ts->visible ? ' ' . $pill($t('psix_hidden'), 'tz-mur-p-warn') : ''))
            . html_writer::tag('td', $ts->modname === 'quiz'
                ? $pill($t('psix_kind_quiz')) : $pill($t('psix_kind_fb'), 'tz-mur-p-ok'))
            . html_writer::tag('td', $ts->timeopen
                ? userdate($ts->timeopen, get_string('strftimedatetimeshort')) : '—')
            . html_writer::tag('td', $ts->timeclose
                ? userdate($ts->timeclose, get_string('strftimedatetimeshort')) : '—')
            . html_writer::tag('td', (string) $ts->javoblar)
            . html_writer::tag('td',
                html_writer::link($natija, $t('psix_tests_results'),
                    ['class' => 'tz-dir-chip'])
                . ' ' . html_writer::link(new moodle_url('/course/modedit.php',
                    ['update' => $ts->cmid, 'return' => 0]), $t('psix_tests_edit'),
                    ['class' => 'tz-dir-chip'])));
    }
    echo html_writer::div(html_writer::tag('table',
        html_writer::tag('thead', html_writer::tag('tr',
            html_writer::tag('th', $t('psix_tests_name'))
            . html_writer::tag('th', $t('psix_talk_type'))
            . html_writer::tag('th', $t('psix_tests_open'))
            . html_writer::tag('th', $t('psix_tests_close'))
            . html_writer::tag('th', $t('psix_tests_done'))
            . html_writer::tag('th', '')))
        . html_writer::tag('tbody', $rows),
        ['class' => 'tz-dir-table']), 'tz-dir-tablewrap');
    break;

// ================================================= TASHQI TEST NATIJALARI
case 'natija':
    $testid = required_param('test', PARAM_INT);
    $test = psixolog::test_get($testid);
    if (!$test) {
        throw new moodle_exception('invalidrecord', 'error');
    }
    if (!$kurs) {
        echo $OUTPUT->notification($t('psix_nokurs'), 'warning');
        break;
    }

    if (optional_param('saqlandi', 0, PARAM_INT)) {
        echo $OUTPUT->notification($t('psix_res_saved'), 'success');
    }

    // Test sarlavhasi.
    echo html_writer::div(
        html_writer::div(s($test->nomi) . ' · '
            . userdate((int) $test->sana, get_string('strftimedateshort')), 'tz-mur-ism')
        . (trim((string) $test->izoh) !== ''
            ? html_writer::div(s($test->izoh), 'tz-mur-qisqa') : '')
        . html_writer::div(
            html_writer::link($selfurl(['view' => 'testlar']), '← ' . $t('psix_nav_tests'),
                ['class' => 'tz-dir-chip']),
            'tz-mur-pills'),
        'tz-mur-blok');

    // Sinf tanlash (GET) — setka sinf-sinf to'ldiriladi (jurnal naqshi).
    $guruhid = optional_param('guruh', 0, PARAM_INT);
    $guruhlar = psixolog::guruhlar((int) $kurs->id);
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-mur-filtr']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view',
        'value' => 'natija']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'test',
        'value' => $testid]);
    echo tz_psix_select('guruh', $guruhlar, (string) $guruhid, $t('psix_allgroups'));
    echo html_writer::tag('button', $t('mur_apply'),
        ['type' => 'submit', 'class' => 'tz-mur-btn']);
    echo html_writer::end_tag('form');

    if (!$guruhid) {
        echo html_writer::div($t('psix_res_pick'), 'tz-dir-none');
        break;
    }

    $odamlar = psixolog::odamlar((int) $kurs->id, $guruhid, '', 500);
    if (!$odamlar) {
        echo html_writer::div($t('psix_none'), 'tz-dir-none');
        break;
    }
    $natijalar = psixolog::natijalar($testid);

    // Setka: har qatorda ball/daraja + izoh. IKKALASI bo'sh = natija o'chadi.
    echo html_writer::start_tag('form', ['method' => 'post']);
    echo tz_psix_hidden('natijasaqla', ['test' => $testid, 'guruh' => $guruhid,
        'qayt' => 'natija', 'ids' => implode(',', array_keys($odamlar))]);

    $rows = '';
    foreach ($odamlar as $o) {
        $n = $natijalar[(int) $o->id] ?? null;
        $rows .= html_writer::tag('tr',
            html_writer::tag('td', s(fullname($o)))
            . html_writer::tag('td', html_writer::empty_tag('input', [
                'type' => 'text', 'name' => 'ball_' . $o->id,
                'value' => $n ? (string) $n->ball : '',
                'placeholder' => $t('psix_res_ball'),
                'style' => 'width:9em']))
            . html_writer::tag('td', html_writer::empty_tag('input', [
                'type' => 'text', 'name' => 'izoh_' . $o->id,
                'value' => $n ? (string) $n->izoh : '',
                'placeholder' => $t('psix_res_izoh'),
                'style' => 'width:100%;min-width:16em'])));
    }
    echo html_writer::div(html_writer::tag('table',
        html_writer::tag('thead', html_writer::tag('tr',
            html_writer::tag('th', $t('psix_person'))
            . html_writer::tag('th', $t('psix_res_ball'))
            . html_writer::tag('th', $t('psix_res_izoh'))))
        . html_writer::tag('tbody', $rows),
        ['class' => 'tz-dir-table']), 'tz-dir-tablewrap');

    echo html_writer::div($t('psix_res_hint'), 'tz-mur-ogoh');
    echo html_writer::tag('button', $t('psix_save'),
        ['type' => 'submit', 'class' => 'tz-fb-csend']);
    echo html_writer::end_tag('form');
    break;

// ============================================================ STATISTIKA
case 'stat':
    // Rahbariyat ham ko'radi — FAQAT SONLAR (maxfiylik qarori).
    $stat = psixolog::stat($kurs);

    $plita = fn(string $lab, $val, string $cls = '') => html_writer::div(
        html_writer::div($lab, 'tz-mur-lab') . html_writer::div((string) $val, 'tz-mur-num'),
        'tz-mur-tile ' . $cls);
    echo html_writer::div(
        $plita($t('psix_stat_talks'), $stat['jami'])
        . $plita($t('psix_st_ochiq'), $stat['caselar']['ochiq'],
            $stat['caselar']['ochiq'] ? 'tz-mur-warn' : '')
        . $plita($t('psix_st_kuzatuvda'), $stat['caselar']['kuzatuvda'])
        . $plita($t('psix_st_yopildi'), $stat['caselar']['yopildi'], 'tz-mur-ok'),
        'tz-mur-tiles');

    $kesim = function (string $sarlavha, array $data, ?callable $nomla = null) {
        if (!$data) {
            return;
        }
        $eng = max($data) ?: 1;
        $out = html_writer::div(s($sarlavha), 'tz-mur-lab');
        foreach ($data as $k => $soni) {
            $nom = $nomla ? $nomla((string) $k) : (string) $k;
            $out .= html_writer::div(
                html_writer::div(s($nom), 'tz-mur-kesnom')
                . html_writer::div(html_writer::div('', 'tz-mur-kesfill',
                    ['style' => 'width:' . round($soni / $eng * 100) . '%']), 'tz-mur-kesbar')
                . html_writer::div((string) $soni, 'tz-mur-kesson'),
                ['class' => 'tz-mur-kesrow']);
        }
        echo html_writer::div($out, 'tz-mur-kesim');
    };
    $kesim($t('psix_stat_month'), $stat['oylar']);
    $kesim($t('psix_stat_bygroup'), $stat['sinflar']);
    $kesim($t('psix_stat_bytype'), $stat['turlar'],
        fn(string $k) => get_string('psix_t_' . $k, 'local_targetzenit'));

    echo html_writer::div($t('psix_stat_note'), 'tz-mur-ogoh');
    break;
}

echo html_writer::end_div();    // .tz-mur

shell::end();
echo $OUTPUT->footer();
