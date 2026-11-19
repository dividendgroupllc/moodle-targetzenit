<?php
// E'LONLAR — o'qituvchi/rahbariyat sahifasi: yozish + o'z e'lonlari ro'yxati
// (o'qilganlik statistikasi bilan). Maket «E'lonlar» bo'limi.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\elon;
use local_targetzenit\journal;
use local_targetzenit\output\maket;
use local_targetzenit\timetable;

require_login();
$context = context_system::instance();
$year = timetable::current_year();

$mening = journal::my_courses((int) $USER->id, $year);
$maktabhuquq = has_capability('local/targetzenit:viewteachers', $context);

if (!$mening && !$maktabhuquq) {
    // Dars bermaydigan va rahbar bo'lmagan — e'lon yoza olmaydi.
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('elon_nav', 'local_targetzenit'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/elon.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('elon_nav', 'local_targetzenit'));
$PAGE->requires->css('/local/targetzenit/styles.css');

// ------------------------------------------------------------------ YARATISH
if (optional_param('yarat', 0, PARAM_INT) && confirm_sesskey()) {
    $scope = optional_param('scope', 'guruh', PARAM_ALPHA);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    $sarlavha = trim(optional_param('sarlavha', '', PARAM_TEXT));

    // Ruxsat: maktab scope faqat rahbariyatga; guruh scope faqat o'z kursiga.
    $xato = '';
    if ($scope === 'maktab') {
        if (!$maktabhuquq) {
            $xato = get_string('elon_err_scope', 'local_targetzenit');
        }
    } else {
        $scope = 'guruh';
        if (!isset($mening[$courseid])) {
            $xato = get_string('elon_err_group', 'local_targetzenit');
        }
    }
    if ($sarlavha === '') {
        $xato = get_string('elon_err_title', 'local_targetzenit');
    }

    if ($xato === '') {
        elon::yarat((object) [
            'scope' => $scope,
            'courseid' => $courseid,
            'turi' => optional_param('turi', 'oddiy', PARAM_ALPHA),
            'sarlavha' => $sarlavha,
            'matn' => optional_param('matn', '', PARAM_TEXT),
            'sana' => optional_param('sana', '', PARAM_TEXT),
            'vaqt' => optional_param('vaqt', '', PARAM_TEXT),
            'xona' => optional_param('xona', '', PARAM_TEXT),
        ]);
        redirect($PAGE->url, get_string('elon_sent', 'local_targetzenit'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($PAGE->url, $xato, null, \core\output\notification::NOTIFY_ERROR);
}

// -------------------------------------------------------------------- BEKOR
if ($bekor = optional_param('bekor', 0, PARAM_INT)) {
    require_sesskey();
    $e = $DB->get_record('local_targetzenit_elon', ['id' => $bekor]);
    if ($e && ((int) $e->muallifuserid === (int) $USER->id || $maktabhuquq)) {
        elon::bekor($bekor);
        redirect($PAGE->url, get_string('elon_cancelled', 'local_targetzenit'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($PAGE->url);
}

maket::boshla('elon');
echo html_writer::start_div('tz-ishstoli-wrap');

echo html_writer::tag('div', get_string('elon_nav', 'local_targetzenit'), ['class' => 'tzp-h1']);
echo html_writer::tag('div', get_string('elon_sub', 'local_targetzenit'), ['class' => 'tzp-sub']);

// ------------------------------------------------------------ YANGI E'LON
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('elon_new', 'local_targetzenit'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yarat', 'value' => 1]);

// Qator: auditoriya (guruh/maktab) + tur
echo html_writer::start_div('tz-elon-row');

// Scope/guruh: tanlagich — o'z guruhlari + (rahbariyatga) maktab
echo html_writer::start_tag('select', ['name' => 'scopegroup', 'id' => 'tz-elon-sg',
    'class' => 'form-control', 'onchange' => 'tzElonScope(this)']);
foreach ($mening as $cid => $nom) {
    echo html_writer::tag('option', format_string((string) $nom),
        ['value' => 'g' . $cid]);
}
if ($maktabhuquq) {
    echo html_writer::tag('option', get_string('elon_school', 'local_targetzenit'),
        ['value' => 'maktab']);
}
echo html_writer::end_tag('select');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'scope', 'id' => 'tz-elon-scope', 'value' => 'guruh']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'id' => 'tz-elon-cid', 'value' => (int) array_key_first($mening ?: [0 => 0])]);

echo html_writer::start_tag('select', ['name' => 'turi', 'id' => 'tz-elon-turi', 'class' => 'form-control']);
foreach (elon::TURLAR as $t) {
    echo html_writer::tag('option', get_string('elon_turi_' . $t, 'local_targetzenit'),
        ['value' => $t]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'sarlavha',
    'class' => 'form-control mt-2', 'maxlength' => 255, 'required' => 'required',
    'placeholder' => get_string('elon_titleph', 'local_targetzenit')]);
echo html_writer::tag('textarea', '', ['name' => 'matn', 'rows' => 3,
    'class' => 'form-control mt-2', 'maxlength' => 8000,
    'placeholder' => get_string('elon_textph', 'local_targetzenit')]);

// Majlis maydonlari (turi=majlis bo'lsa ko'rinadi)
echo html_writer::start_div('tz-elon-majlis mt-2', ['id' => 'tz-elon-majlis', 'hidden' => 'hidden']);
echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'sana', 'class' => 'form-control me-2']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'vaqt', 'class' => 'form-control me-2']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'xona', 'class' => 'form-control',
    'maxlength' => 64, 'placeholder' => get_string('elon_room', 'local_targetzenit')]);
echo html_writer::end_div();

echo html_writer::div(html_writer::tag('button', get_string('elon_publish', 'local_targetzenit'),
    ['type' => 'submit', 'class' => 'btn btn-primary']), 'mt-2');
echo html_writer::end_tag('form');
echo html_writer::end_div();

$PAGE->requires->js_amd_inline("
    require([], function() {
        var sg = document.getElementById('tz-elon-sg');
        var sc = document.getElementById('tz-elon-scope');
        var cid = document.getElementById('tz-elon-cid');
        var turi = document.getElementById('tz-elon-turi');
        var maj = document.getElementById('tz-elon-majlis');
        window.tzElonScope = function(el) {
            if (el.value === 'maktab') { sc.value = 'maktab'; cid.value = '0'; }
            else { sc.value = 'guruh'; cid.value = el.value.substring(1); }
        };
        if (turi) {
            turi.addEventListener('change', function() {
                maj.hidden = (turi.value !== 'majlis');
            });
        }
    });
");

// ------------------------------------------------------- MENING E'LONLARIM
$royxat = elon::mine((int) $USER->id);
$stats = elon::seen_stats(array_map(static fn($e) => (int) $e->id, $royxat));

echo html_writer::tag('h5', get_string('elon_mine', 'local_targetzenit'), ['class' => 'mt-4']);
if (!$royxat) {
    echo html_writer::div(get_string('elon_none', 'local_targetzenit'), 'tzp-empty');
} else {
    foreach ($royxat as $e) {
        if ($e->holat === 'bekor') {
            continue;
        }
        // TEG (maket): majlis -> «MAJLIS» (binafsha); maktab -> «MAKTAB» (ko'k);
        // guruh -> guruh NOMI (brend).
        if ($e->turi === 'majlis') {
            $tegmatn = get_string('elon_turi_majlis', 'local_targetzenit');
            $tegcls = 'tz-elon-tmajlis';
        } else if ($e->scope === 'maktab') {
            $tegmatn = get_string('elon_school_tag', 'local_targetzenit');
            $tegcls = 'tz-elon-tmaktab';
        } else {
            $tegmatn = format_string((string) ($mening[$e->courseid] ?? '—'));
            $tegcls = 'tz-elon-tguruh';
        }
        // Muhim bo'lsa qo'shimcha belgi
        $muhim = ($e->turi === 'muhim')
            ? ' ' . html_writer::tag('span', get_string('elon_turi_muhim', 'local_targetzenit'),
                ['class' => 'tz-elon-teg tz-elon-tmuhim']) : '';

        $seen = $stats[(int) $e->id] ?? 0;
        $jami = elon::auditoriya_soni($e);
        $foiz = $jami ? round($seen / $jami * 100) : 0;

        $majlis = $e->turi === 'majlis' && $e->sana
            ? html_writer::div('📅 ' . userdate(strtotime($e->sana), '%d-%B')
                . ($e->vaqt ? ' ' . s($e->vaqt) : '')
                . ($e->xona ? ' · ' . s($e->xona) : ''), 'tz-elon-majlis-info')
            : '';

        echo html_writer::div(
            html_writer::div(
                html_writer::tag('span', s($tegmatn), ['class' => 'tz-elon-teg ' . $tegcls])
                . $muhim
                . ' ' . html_writer::tag('b', s($e->sarlavha))
                . html_writer::tag('span', '', ['style' => 'flex:1'])
                . html_writer::tag('span',
                    userdate((int) $e->timecreated, '%d-%B %H:%M'),
                    ['class' => 'text-muted small']),
                'tz-elon-head')
            . ($e->matn ? html_writer::tag('p', s((string) $e->matn), ['class' => 'mb-1 mt-1']) : '')
            . $majlis
            . html_writer::div(
                html_writer::tag('span',
                    get_string('elon_read', 'local_targetzenit') . ' '
                    . html_writer::tag('b', $seen . ' / ' . $jami),
                    ['class' => 'tz-elon-seen'])
                . html_writer::tag('span',
                    html_writer::tag('i', '', ['style' => 'width:' . $foiz . '%']),
                    ['class' => 'tz-elon-bar'])
                . html_writer::link(
                    new moodle_url($PAGE->url, ['bekor' => (int) $e->id, 'sesskey' => sesskey()]),
                    get_string('elon_cancel', 'local_targetzenit'),
                    ['class' => 'text-danger small ms-3',
                     'onclick' => 'return confirm('
                        . json_encode(get_string('elon_cancel_q', 'local_targetzenit')) . ')']),
                'tz-elon-foot'),
            'tz-elon-item ' . ($e->turi === 'majlis' ? 'tz-elon-majlis-card' : ''));
    }
}

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
