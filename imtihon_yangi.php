<?php
// IMTIHON YARATISH SEHRGARI (ekran B, IMTIHON_ARXITEKTURA.md 4-bo'lim).
// Tur -> asosiy -> sozlamalar; yuborilganda haqiqiy Moodle quiz/assign
// yaratiladi (imtihon::yarat -> create_module). Test uchun keyin savol
// muharririga (yadro), yozma uchun ro'yxatga qaytadi.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\imtihon;
use local_targetzenit\timetable;
use local_targetzenit\topshiriq;
use local_targetzenit\output\maket;

require_login();
$context = context_system::instance();
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/imtihon_yangi.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title($t('im_new'));
$PAGE->requires->css('/local/targetzenit/styles.css');

$kurslar = topshiriq::yaratish_kurslari((int) $USER->id);

// --------------------------------------------------- YARATISH (POST)
$yaratxato = '';
if (optional_param('yarat', 0, PARAM_INT) && confirm_sesskey()) {
    $oila = optional_param('oila', 'test', PARAM_ALPHA);
    $courseid = optional_param('courseid', 0, PARAM_INT);

    if (!isset($kurslar[$courseid]) || !in_array($oila, ['test', 'yozma'], true)) {
        $yaratxato = $t('im_w_err') . ' — guruh yoki tur noto\'g\'ri tanlandi.';
    } else {
        $d = [
            'oila' => $oila,
            'courseid' => $courseid,
            'name' => optional_param('name', '', PARAM_TEXT),
            'timeopen' => strtotime((string) optional_param('timeopen', '', PARAM_TEXT)) ?: 0,
            'grade' => optional_param('grade', 100, PARAM_INT),
        ];
        if ($oila === 'test') {
            $d['duration_min'] = optional_param('duration', 40, PARAM_INT);
            $d['attempts'] = optional_param('attempts', 1, PARAM_INT);
            $d['shuffle'] = optional_param('shuffle', 0, PARAM_INT);
            $d['review'] = optional_param('review', 'closed', PARAM_ALPHA);
            $d['behaviour'] = optional_param('behaviour', 'deferredfeedback', PARAM_ALPHA);
            $xavf = optional_param('xavf', 'oddiy', PARAM_ALPHA);
            if ($xavf === 'sinf' || $xavf === 'qattiq') {
                $d['password'] = trim(optional_param('password', '', PARAM_TEXT));
            }
            if ($xavf === 'qattiq') {
                $d['seb'] = 1;   // Safe Exam Browser
            }
            $d['subnet'] = trim(optional_param('subnet', '', PARAM_TEXT));   // sinf IP
            $d['offline'] = optional_param('offline', 0, PARAM_INT);
        } else {
            $d['duedate'] = strtotime((string) optional_param('duedate', '', PARAM_TEXT)) ?: 0;
            $d['subfile'] = optional_param('subfile', 1, PARAM_INT);
            $d['subtext'] = optional_param('subtext', 0, PARAM_INT);
        }

        $created = imtihon::yarat($d);
        if ($created && !empty($created->coursemodule)) {
            // Nizom bog'lanishi (ixtiyoriy): BSB/CHSB/LI -> _exam + sitting +
            // _imtihon. Shundan keyin baholash ekranidagi «Import» tugmasi
            // ballarni chorakka ko'chiradi.
            $nizom = optional_param('nizom', '', PARAM_ALPHA);
            $termname = trim(optional_param('termname', '', PARAM_TEXT));
            if (in_array($nizom, ['BSB', 'CHSB', 'LI'], true) && $termname !== '') {
                imtihon::nizom_yarat((int) $created->coursemodule, $nizom, $termname, (int) $USER->id);
            }
            if ($oila === 'test') {
                // Savol muharririga (portal qobig'i — yadro oqimini o'raydi).
                redirect(new moodle_url('/local/targetzenit/imtihon_savol.php',
                    ['cmid' => (int) $created->coursemodule]),
                    $t('im_w_created_q'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
            redirect(new moodle_url('/local/targetzenit/imtihonlar.php'),
                $t('im_w_created'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        // Yaratilmadi — xatoni SAHIFADA ko'rsatamiz (tashxis).
        $yaratxato = $t('im_w_err')
            . (imtihon::$xato !== '' ? ' — ' . imtihon::$xato : '');
    }
}

maket::boshla('imtihon');
echo html_writer::start_div('tz-ishstoli-wrap');

echo html_writer::div(html_writer::link(new moodle_url('/local/targetzenit/imtihonlar.php'),
    '← ' . $t('im_nav'), ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-3');
echo html_writer::tag('div', $t('im_new'), ['class' => 'tzp-h1']);
echo html_writer::tag('div', $t('im_w_sub'), ['class' => 'tzp-sub']);

if ($yaratxato !== '') {
    echo $OUTPUT->notification($yaratxato, \core\output\notification::NOTIFY_ERROR);
}

if (!$kurslar) {
    echo html_writer::div($t('im_w_nocourse'), 'tzp-empty m-3');
    echo html_writer::end_div();
    maket::tugat();
    return;
}

echo html_writer::start_tag('form', ['method' => 'post',
    'action' => $PAGE->url->out(false), 'class' => 'tz-wiz']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yarat', 'value' => 1]);

echo html_writer::start_div('tz-wizcols');
echo html_writer::start_div('tz-wizmain');

// ---- 1. TUR
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', '1 · ' . $t('im_w_type'), ['class' => 'tzp-h3']);
echo html_writer::start_div('tz-wtur');
foreach (['test' => ['📝', 'im_type_test_f', 'im_w_test_d'],
          'yozma' => ['📄', 'im_type_yoz_f', 'im_w_yoz_d']] as $k => [$ic, $nom, $desc]) {
    echo html_writer::start_tag('label', ['class' => 'tz-wturc']);
    echo html_writer::empty_tag('input', ['type' => 'radio', 'name' => 'oila', 'value' => $k]
        + ($k === 'test' ? ['checked' => 'checked'] : []));
    echo html_writer::div(html_writer::tag('span', $ic, ['class' => 'tz-wturc-i'])
        . html_writer::tag('b', $t($nom)) . html_writer::tag('p', $t($desc)));
    echo html_writer::end_tag('label');
}
echo html_writer::end_div();
echo html_writer::end_div();

// ---- 2. ASOSIY
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', '2 · ' . $t('im_w_main'), ['class' => 'tzp-h3']);
echo html_writer::div(
    html_writer::tag('label', $t('im_col_group'))
    . html_writer::select($kurslar, 'courseid', '', false,
        ['class' => 'form-control']), 'tz-fld');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_name'))
    . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name',
        'class' => 'form-control', 'placeholder' => $t('im_w_name_ph')]), 'tz-fld');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_start'))
    . html_writer::empty_tag('input', ['type' => 'datetime-local', 'name' => 'timeopen',
        'class' => 'form-control']), 'tz-fld');
// Test: davomiylik; Yozma: muddat (JS almashtiradi)
echo html_writer::div(
    html_writer::tag('label', $t('im_w_duration'))
    . html_writer::empty_tag('input', ['type' => 'number', 'name' => 'duration',
        'class' => 'form-control', 'value' => 40, 'min' => 0]), 'tz-fld tz-only-test');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_due'))
    . html_writer::empty_tag('input', ['type' => 'datetime-local', 'name' => 'duedate',
        'class' => 'form-control']), 'tz-fld tz-only-yoz');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_maxscore'))
    . html_writer::empty_tag('input', ['type' => 'number', 'name' => 'grade',
        'class' => 'form-control', 'value' => 100, 'min' => 1]), 'tz-fld');

// Nizom bog'lanishi (ixtiyoriy) — chorak balliga qo'shilsinmi.
$termopts = [];
foreach (timetable::terms(timetable::current_year()) as $tr) {
    $termopts[(string) $tr->name] = (string) $tr->name;
}
echo html_writer::div(
    html_writer::tag('label', $t('im_w_nizom'))
    . html_writer::select([
        '' => $t('im_w_nizom_none'),
        'BSB' => $t('im_w_nizom_bsb'),
        'CHSB' => $t('im_w_nizom_chsb'),
        'LI' => $t('im_w_nizom_li'),
    ], 'nizom', '', false, ['class' => 'form-control']), 'tz-fld');
if ($termopts) {
    echo html_writer::div(
        html_writer::tag('label', $t('im_w_term'))
        . html_writer::select($termopts, 'termname', '', false,
            ['class' => 'form-control']), 'tz-fld');
}
echo html_writer::div($t('im_w_nizom_note'), 'tzp-note');
echo html_writer::end_div();

// ---- 3. SOZLAMALAR (TEST)
echo html_writer::start_div('tzp-card tz-only-test');
echo html_writer::tag('div', '3 · ' . $t('im_w_settings'), ['class' => 'tzp-h3']);
echo html_writer::div(
    html_writer::tag('label', $t('im_w_attempts'))
    . html_writer::select([1 => '1 (' . $t('im_nav') . ')', 2 => '2', 0 => $t('im_w_unlimited')],
        'attempts', 1, false, ['class' => 'form-control']), 'tz-fld');
echo html_writer::tag('label',
    html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'shuffle',
        'value' => 1, 'checked' => 'checked']) . ' ' . $t('im_w_shuffle'),
    ['class' => 'tz-chk']);
echo html_writer::div(
    html_writer::tag('label', $t('im_w_review'))
    . html_writer::select([
        'closed' => $t('im_w_rev_closed'),
        'during' => $t('im_w_rev_during'),
        'marksonly' => $t('im_w_rev_marks'),
    ], 'review', 'closed', false, ['class' => 'form-control']), 'tz-fld');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_security'))
    . html_writer::select([
        'oddiy' => $t('im_w_sec_simple'),
        'sinf' => $t('im_w_sec_class'),
        'qattiq' => $t('im_w_sec_strict'),
    ], 'xavf', 'oddiy', false, ['class' => 'form-control']), 'tz-fld');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_password'))
    . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'password',
        'class' => 'form-control', 'autocomplete' => 'off']), 'tz-fld');
echo html_writer::div(
    html_writer::tag('label', $t('im_w_subnet'))
    . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'subnet',
        'class' => 'form-control', 'autocomplete' => 'off',
        'placeholder' => '192.168.1.0/24']), 'tz-fld');
echo html_writer::tag('label',
    html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'offline', 'value' => 1])
    . ' ' . $t('im_w_offline'), ['class' => 'tz-chk']);
echo html_writer::div($t('im_w_sec_note'), 'tzp-note');
echo html_writer::end_div();

// ---- 3. SOZLAMALAR (YOZMA)
echo html_writer::start_div('tzp-card tz-only-yoz');
echo html_writer::tag('div', '3 · ' . $t('im_w_settings'), ['class' => 'tzp-h3']);
echo html_writer::tag('label',
    html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'subfile',
        'value' => 1, 'checked' => 'checked']) . ' ' . $t('im_w_subfile'), ['class' => 'tz-chk']);
echo html_writer::tag('label',
    html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'subtext',
        'value' => 1]) . ' ' . $t('im_w_subtext'), ['class' => 'tz-chk']);
echo html_writer::div($t('im_w_yoz_note'), 'tzp-note');
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', $t('im_w_create'),
        ['type' => 'submit', 'class' => 'btn btn-primary']), 'mt-2');
echo html_writer::end_div();   // tz-wizmain

// ---- O'NG USTUN: jonli xulosa + keyingi qadamlar
echo html_writer::start_div('tz-wizside');
$kv = fn(string $lab, string $id, string $val) => html_writer::div(
    html_writer::tag('span', $lab)
    . html_writer::tag('b', $val, ['id' => $id]), 'tz-kv');
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', $t('im_w_summary'), ['class' => 'tzp-h3']);
echo $kv($t('im_w_type'), 'sum-tur', $t('im_type_test_f'));
echo $kv($t('im_col_group'), 'sum-guruh', '—');
echo $kv($t('im_w_start'), 'sum-sana', '—');
echo $kv($t('im_w_duration'), 'sum-dav', '40');
echo $kv($t('im_w_attempts'), 'sum-urin', '1');
echo $kv($t('im_w_security'), 'sum-xavf', $t('im_w_sec_simple'));
echo $kv($t('im_w_nizom'), 'sum-nizom', $t('im_w_nizom_none'));
echo html_writer::div($t('im_w_behind'), 'tzp-note', ['id' => 'sum-behind']);
echo html_writer::end_div();

echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', $t('im_w_nextsteps'), ['class' => 'tzp-h3']);
echo html_writer::start_tag('ul', ['class' => 'tz-todo']);
echo html_writer::tag('li', html_writer::tag('span', '4', ['class' => 'tz-todo-n tz-todo-a'])
    . html_writer::div(html_writer::tag('b', $t('im_s_new'))
        . html_writer::tag('small', $t('im_w_ns_q'))));
echo html_writer::tag('li', html_writer::tag('span', '5', ['class' => 'tz-todo-n tz-todo-b'])
    . html_writer::div(html_writer::tag('b', $t('im_s_publish'))
        . html_writer::tag('small', $t('im_w_ns_pub'))));
echo html_writer::end_tag('ul');
echo html_writer::end_div();
echo html_writer::end_div();   // tz-wizside

echo html_writer::end_div();   // tz-wizcols
echo html_writer::end_tag('form');

// Test/Yozma almashtirish + jonli xulosa
$jslabels = json_encode([
    'turtest' => $t('im_type_test_f'), 'turyoz' => $t('im_type_yoz_f'),
    'behindtest' => $t('im_w_behind'), 'behindyoz' => $t('im_w_behind_yoz'),
    'dash' => '—',
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$PAGE->requires->js_amd_inline("
    require([], function() {
        var L = $jslabels;
        var form = document.querySelector('.tz-wiz');
        function val(n){ var e = form.querySelector('[name='+n+']'); return e ? e.value : ''; }
        function seltext(n){ var s = form.querySelector('select[name='+n+']');
            return (s && s.selectedIndex >= 0) ? s.options[s.selectedIndex].text : ''; }
        function sett(id, v){ var e = document.getElementById(id); if (e) { e.textContent = v; } }
        function upd() {
            var ch = form.querySelector('input[name=oila]:checked');
            var ist = ch && ch.value === 'test';
            document.querySelectorAll('.tz-only-test').forEach(function(e){ e.hidden = !ist; });
            document.querySelectorAll('.tz-only-yoz').forEach(function(e){ e.hidden = ist; });
            sett('sum-tur', ist ? L.turtest : L.turyoz);
            sett('sum-guruh', seltext('courseid') || L.dash);
            var s = val('timeopen'); sett('sum-sana', s ? s.replace('T', ' · ') : L.dash);
            sett('sum-dav', ist ? ((val('duration') || '0') + ' daqiqa')
                : (val('duedate') ? val('duedate').replace('T', ' · ') : L.dash));
            sett('sum-urin', ist ? (seltext('attempts') || L.dash) : L.dash);
            sett('sum-xavf', ist ? (seltext('xavf') || L.dash) : L.dash);
            sett('sum-nizom', seltext('nizom') || L.dash);
            sett('sum-behind', ist ? L.behindtest : L.behindyoz);
        }
        form.addEventListener('input', upd);
        form.addEventListener('change', upd);
        upd();
    });");

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
