<?php
// DARS YOZUVLARI — direktor uchun lenta.
//
// Tuzilma Frappe'dagi «Sotuv nazorati» sahifasidan olingan: master qator
// (kim, qayerda, nima, qachon, qancha, AI bahosi, qisqa xulosa) va qatorni
// bosganda ochiladigan karta — chapda ovoz, o'ngda qisqa tahlil, pastda
// dars jarayoniga o'tish tugmasi.
//
// ODDIY sahifa, admin sahifasi EMAS: `admin_externalpage_setup()` sahifani
// ADMIN DARAXTIDAN qidiradi, daraxtga qo'shish esa `settings.php` da
// `if ($hassiteconfig)` ichida — direktorda `moodle/site:config` yo'q va
// sahifa o'z ruxsati bo'lsa ham "Access denied" berardi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\ai;
use local_targetzenit\audio;
use local_targetzenit\dashboard;
use local_targetzenit\output\shell;
use local_targetzenit\timetable;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewanalysis', $context);

$oqituvchi = optional_param('oqituvchi', 0, PARAM_INT);
$guruh = optional_param('guruh', '', PARAM_TEXT);
$from = optional_param('from', '', PARAM_TEXT);
$to = optional_param('to', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$ochir = optional_param('ochir', 0, PARAM_INT);
$turi = optional_param('turi', 'audio', PARAM_ALPHA);   // audio | hammasi

$tekshir = fn($v, $zaxira) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $zaxira;
$from = $tekshir($from, date('Y-m-d', strtotime('-30 days')));
$to = $tekshir($to, date('Y-m-d'));

const TZ_YOZUV_PER_PAGE = 20;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/yozuvlar.php'));
shell::setup($PAGE, get_string('recordings', 'local_targetzenit'));

/** Holat chipi — rang + MATN (rang yolg'iz ma'no tashlamaydi). */
function tz_rec_chip(string $status, string $matn, string $title = ''): string {
    return html_writer::tag('span', s($matn),
        ['class' => 'tz-dir-chip tz-dir-' . $status, 'title' => $title]);
}

// ------------------------------------------------------------------ O'CHIRISH
if ($ochir && confirm_sesskey()) {
    $a = $DB->get_record('local_targetzenit_audio', ['id' => $ochir], '*', MUST_EXIST);
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid], 'id, courseid');

    if ($dars) {
        $ctx = context_course::instance($dars->courseid, IGNORE_MISSING);
        if ($ctx) {
            get_file_storage()->delete_area_files($ctx->id, audio::COMPONENT,
                audio::FILEAREA, $a->lessonid);
        }
    }
    if ($turi === 'hammasi') {
        // Audio ham, transkript ham, tahlil ham — butunlay
        $DB->delete_records('local_targetzenit_audio', ['id' => $a->id]);
        $xabar = get_string('rec_deleted_all', 'local_targetzenit');
    } else {
        // Faqat audio — transkript va tahlil qoladi
        $a->status = 'deleted';
        $a->filename = null;
        $a->filesize = 0;
        $a->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $a);
        $xabar = get_string('rec_deleted_audio', 'local_targetzenit');
    }
    redirect(new moodle_url('/local/targetzenit/yozuvlar.php'), $xabar, null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$year = timetable::current_year();
$filtrlar = $year ? dashboard::recording_filters($year)
                  : ['oqituvchilar' => [], 'guruhlar' => []];

$natija = dashboard::recordings([
    'teacher' => $oqituvchi, 'groupkey' => $guruh, 'from' => $from, 'to' => $to,
], $page, TZ_YOZUV_PER_PAGE);

$url = fn(array $p = []) => new moodle_url('/local/targetzenit/yozuvlar.php', $p + [
    'oqituvchi' => $oqituvchi, 'guruh' => $guruh, 'from' => $from, 'to' => $to]);

$norma = ai::norm_overall();

echo $OUTPUT->header();
shell::start('yozuvlar', get_string('recordings', 'local_targetzenit'),
    get_string('rec_sub', 'local_targetzenit', $natija['jami']));

// ====================================================== FILTRLAR
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-dir-filters',
    'action' => (new moodle_url('/local/targetzenit/yozuvlar.php'))->out(false)]);

echo html_writer::div(
    html_writer::tag('label', get_string('dir_colteacher', 'local_targetzenit'))
    . html_writer::select([0 => get_string('rec_allteachers', 'local_targetzenit')]
        + $filtrlar['oqituvchilar'], 'oqituvchi', $oqituvchi, false), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('dir_colgroup', 'local_targetzenit'))
    . html_writer::select(['' => get_string('rec_allgroups', 'local_targetzenit')]
        + $filtrlar['guruhlar'], 'guruh', $guruh, false), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_from', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'from', 'value' => $from]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_to', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'to', 'value' => $to]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary btn-sm',
        'value' => get_string('rec_apply', 'local_targetzenit')])
    . ' ' . html_writer::link(new moodle_url('/local/targetzenit/yozuvlar.php'),
        get_string('rec_reset', 'local_targetzenit'), ['class' => 'btn btn-sm btn-link']),
    'tz-dir-f');

echo html_writer::end_tag('form');

// ============================================================ LENTA
if (!$natija['rows']) {
    echo html_writer::div(
        html_writer::tag('b', get_string('rec_none', 'local_targetzenit'))
        . html_writer::div(get_string('rec_none_help', 'local_targetzenit'), 'tz-dir-sub'),
        'tz-dir-card');
} else {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::start_div('tz-dir-tablewrap');
    echo html_writer::start_tag('table', ['class' => 'tz-dir-table tz-rec']);

    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['dir_colteacher', 'rec_coltopic', 'rec_colwhen', 'rec_collength',
              'rec_colai', 'rec_colsummary'] as $k) {
        echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
    }
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($natija['rows'] as $r) {
        $id = 'tzrec' . (int) $r->audioid;

        // ------------------------------------------------ master qator
        echo html_writer::start_tag('tr', ['class' => 'tz-rec-row', 'data-tzrec' => $id,
            'tabindex' => '0', 'role' => 'button',
            'title' => get_string('rec_expand', 'local_targetzenit')]);

        echo html_writer::tag('td', html_writer::div(
            html_writer::tag('span', '▸', ['class' => 'tz-rec-caret'])
            . html_writer::div(
                html_writer::div(s($r->ism), 'tz-dir-chipname')
                . html_writer::div(s(trim(($r->subjectname ?: '') . ' · '
                    . ($r->groupname ?: ''), ' ·')), 'tz-dir-sub')),
            'tz-dir-cellname'));

        echo html_writer::tag('td',
            s(\core_text::substr(trim((string) $r->topic), 0, 70) ?: '—')
            . ($r->vacancy ? ' ' . tz_rec_chip('warning',
                get_string('rec_vacancy', 'local_targetzenit')) : ''));

        echo html_writer::tag('td',
            userdate(strtotime($r->lessondate), '%d.%m.%Y')
            . html_writer::div(userdate((int) $r->timecreated, '%H:%M'), 'tz-dir-sub'));

        $daq = (int) round(((int) $r->duration) / 60);
        echo html_writer::tag('td', $r->duration
            ? html_writer::tag('b', $daq . ' ' . get_string('rec_min', 'local_targetzenit'))
            : html_writer::tag('span', '—', ['class' => 'tz-dir-sub']));

        // AI — IJOBIYmi yoki yo'q: NORMAGA NISBATAN (59%), mutlaq shkalada emas
        if ($r->ball === null) {
            echo html_writer::tag('td', $r->aierror
                ? tz_rec_chip('warning', get_string('dir_failed', 'local_targetzenit'),
                    (string) $r->aierror)
                : tz_rec_chip('none', get_string('rec_pending', 'local_targetzenit')));
        } else {
            echo html_writer::tag('td',
                tz_rec_chip($r->band['status'], $r->band['nom'],
                    get_string('dir_normis', 'local_targetzenit', $norma))
                . html_writer::div($r->ball . '% · '
                    . ($r->band['delta'] >= 0 ? '+' : '') . $r->band['delta'] . '%',
                    'tz-dir-sub'));
        }

        $qisqa = \core_text::substr($r->xulosa, 0, 150);
        echo html_writer::tag('td', html_writer::div(
            s($qisqa . (\core_text::strlen($r->xulosa) > 150 ? '…' : '')) ?: '—',
            'tz-rec-xulosa'));

        echo html_writer::end_tag('tr');

        // ------------------------------------------- ochiladigan karta
        echo html_writer::start_tag('tr',
            ['class' => 'tz-rec-drill', 'id' => $id, 'hidden' => 'hidden']);
        echo html_writer::start_tag('td', ['colspan' => 6]);
        echo html_writer::start_div('tz-rec-card');

        // --- chap: ovoz
        echo html_writer::start_div('tz-rec-left');
        $ctx = $r->courseid ? context_course::instance((int) $r->courseid, IGNORE_MISSING) : null;
        $aurl = $ctx ? audio::file_url((int) $r->lessonid, $ctx) : null;
        if ($aurl) {
            // preload="none" — qator ochilmaguncha audio yuklanmaydi
            echo '<audio class="tz-rec-audio" controls preload="none" src="'
                . $aurl->out(false) . '"></audio>';
            echo html_writer::div(
                ($r->filesize ? number_format($r->filesize / 1048576, 1) . ' MB' : '')
                . ($r->hasmatn ? ' · ' . get_string('rec_hastext', 'local_targetzenit') : ''),
                'tz-dir-sub mt-1');
        } else {
            echo html_writer::div($r->audiostatus === 'deleted'
                ? get_string('dir_audiogone', 'local_targetzenit')
                : get_string('dir_audionone', 'local_targetzenit'), 'tz-dir-sub');
        }
        echo html_writer::end_div();

        // --- o'ng: qisqa tahlil
        echo html_writer::start_div('tz-rec-right');
        if ($r->xulosa !== '') {
            echo html_writer::tag('p', s($r->xulosa), ['class' => 'tz-rec-full']);
        }
        foreach ([['rec_strong', $r->kuchli, 'good'], ['rec_weak', $r->kuchsiz, 'serious']]
                 as [$yorliq, $k, $st]) {
            if (!$k) {
                continue;
            }
            echo html_writer::div(
                tz_rec_chip($st, get_string($yorliq, 'local_targetzenit'))
                . ' ' . html_writer::tag('b', s($k['nom']))
                . html_writer::tag('span', ' ' . $k['ball'] . '% ('
                    . ($k['delta'] >= 0 ? '+' : '') . $k['delta'] . '%)',
                    ['class' => 'tz-dir-sub']), 'tz-rec-cat');
        }
        foreach ($r->tavsiyalar as $t) {
            echo html_writer::div('→ ' . s($t), 'tz-rec-tavsiya');
        }
        if ($r->xulosa === '' && !$r->kuchli) {
            echo html_writer::div($r->aierror
                ? s((string) $r->aierror)
                : get_string('rec_pending', 'local_targetzenit'), 'tz-dir-sub');
        }
        echo html_writer::end_div();
        echo html_writer::end_div();

        // --- amal tugmalari
        echo html_writer::start_div('tz-rec-actions');
        echo html_writer::link(
            new moodle_url('/local/targetzenit/dars_tahlil.php', ['lessonid' => $r->lessonid]),
            get_string('rec_openlesson', 'local_targetzenit'),
            ['class' => 'btn btn-sm btn-primary']);
        if ($aurl) {
            echo html_writer::link($url(['ochir' => $r->audioid, 'turi' => 'audio',
                'sesskey' => sesskey()]), get_string('rec_delaudio', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-warning',
                 'onclick' => 'return confirm('
                     . json_encode(get_string('rec_delaudio_q', 'local_targetzenit')) . ')']);
        }
        echo html_writer::link($url(['ochir' => $r->audioid, 'turi' => 'hammasi',
            'sesskey' => sesskey()]), get_string('rec_delall', 'local_targetzenit'),
            ['class' => 'btn btn-sm btn-outline-danger',
             'onclick' => 'return confirm('
                 . json_encode(get_string('rec_delall_q', 'local_targetzenit')) . ')']);
        echo html_writer::end_div();

        echo html_writer::end_tag('td') . html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo $OUTPUT->paging_bar($natija['jami'], $page, TZ_YOZUV_PER_PAGE, $url());
}

shell::end();

// Qatorni ochish/yopish — kichik vanilla JS, tashqi kutubxonasiz.
// Yopilganda audio to'xtatiladi: bir vaqtda ikki yozuv jaranglamasin.
$PAGE->requires->js_amd_inline("
require([], function() {
    document.querySelectorAll('.tz-rec-row').forEach(function (qator) {
        var ochish = function () {
            var d = document.getElementById(qator.dataset.tzrec);
            if (!d) { return; }
            d.hidden = !d.hidden;
            qator.classList.toggle('tz-on', !d.hidden);
            if (d.hidden) {
                var a = d.querySelector('audio');
                if (a) { a.pause(); }
            }
        };
        qator.addEventListener('click', ochish);
        qator.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ochish(); }
        });
    });
});
");

echo $OUTPUT->footer();
