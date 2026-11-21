<?php
// «Dars tahlillari» — PORTAL ko'rinishi (o'qituvchi). Maket «tahlil» bo'limi:
// audio yozib olingan darslar JADVALDA, eng oxirgisi birinchi; qatorni bosganda
// to'liq AI tahlili + zavuch fikri + JAVOB formasi ochiladi. oqituvchi.php
// ichidan FAQAT $portal=true va $view='yozuvlar' bo'lganda chaqiriladi.
// Ma'lumot: dashboard::recordings (AI), feedback::latest + fidbek::render (fikr).
defined('MOODLE_INTERNAL') || die();

use local_targetzenit\dashboard;
use local_targetzenit\feedback;
use local_targetzenit\output\fidbek;
use local_targetzenit\teacher;

global $PAGE;

$natija = dashboard::recordings(
    ['teacher' => $userid, 'from' => $davr['from'], 'to' => $davr['to']],
    $page, teacher::QATOR);
$rows = $natija['rows'];

echo html_writer::div(get_string('oqp_analyses', 'local_targetzenit'), 'tzp-h1');
echo html_writer::div(get_string('oqp_an_sub', 'local_targetzenit', $natija['jami']), 'tzp-sub');

if (!$rows) {
    echo html_writer::div(get_string('oqp_an_empty', 'local_targetzenit'), 'tzp-empty m-3');
} else {
    $lids = array_map(fn($r) => (int) $r->lessonid, $rows);
    $fb = feedback::latest($lids, false);

    // Band holatini pill rangiga o'tkazish.
    $pillrang = ['good' => 'g', 'warning' => 'a', 'critical' => 'r', 'none' => 'wt'];

    echo html_writer::start_div('tzp-card p-0 tz-scroll');
    echo html_writer::start_tag('table', ['class' => 'tz-tah']);
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['oqp_an_date', 'oqp_an_group', 'oqp_an_rec', 'oqp_an_score',
              'oqp_an_sum', 'oqp_an_fb', ''] as $h) {
        echo html_writer::tag('th', $h === '' ? '' : get_string($h, 'local_targetzenit'));
    }
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    $i = 0;
    foreach ($rows as $r) {
        $i++;
        $lid = (int) $r->lessonid;

        // Sana
        $sana = html_writer::tag('b', userdate(strtotime($r->lessondate), '%d-%b'))
            . html_writer::tag('small', $r->lessonno . '-para');

        // Guruh · dars
        $grp = html_writer::tag('b', s((string) $r->groupname))
            . (trim((string) $r->subjectname) !== '' ? ' · ' . s((string) $r->subjectname) : '')
            . html_writer::tag('small', s(\core_text::substr(trim((string) $r->topic), 0, 46)));

        // Yozuv davomiyligi
        $dur = (int) $r->duration;
        $yozuv = '🎙 ' . ($dur ? gmdate($dur >= 3600 ? 'G\soat i\d' : 'i\d', $dur) : '—');

        // AI baho
        if ($r->ball !== null) {
            $prang = $pillrang[$r->band['status']] ?? 'wt';
            $baho = html_writer::tag('span', $r->ball . '%',
                ['class' => 'tz-spill tz-spill-' . $prang]);
        } else {
            $baho = html_writer::tag('span', get_string('ai_pending', 'local_targetzenit'),
                ['class' => 'tz-spill tz-spill-wt']);
        }

        // AI qisqa xulosa
        $xul = trim((string) $r->xulosa);
        $xulcell = $xul !== ''
            ? s(\core_text::substr($xul, 0, 90)) . (\core_text::strlen($xul) > 90 ? '…' : '')
            : html_writer::tag('span', '—', ['class' => 'text-muted']);

        // Zavuch fikri (oxirgi)
        $f = $fb[$lid] ?? null;
        if ($f && (int) $f['soni'] > 0) {
            $last = $f['oxirgi'];
            $javobi = ($last->kind === 'javob');
            $zav = html_writer::tag('span', s(dashboard::ism($last->firstname, $last->lastname)),
                ['class' => 'tz-zbor'])
                . ($javobi ? ' ' . html_writer::tag('span',
                    get_string('oqp_an_replied', 'local_targetzenit'), ['class' => 'tz-zok'])
                : ' ' . html_writer::tag('span',
                    get_string('oqp_an_await', 'local_targetzenit'), ['class' => 'tz-zwait']));
        } else {
            $zav = html_writer::tag('span', get_string('oqp_an_nofb', 'local_targetzenit'),
                ['class' => 'tz-znone']);
        }

        // --- ASOSIY QATOR (bosilganda detalь ochiladi)
        echo html_writer::start_tag('tr', ['class' => 'tz-arow', 'data-i' => $i,
            'onclick' => 'tzTahTog(' . $i . ')']);
        echo html_writer::tag('td', $sana, ['class' => 'tz-c-date']);
        echo html_writer::tag('td', $grp, ['class' => 'tz-c-grp']);
        echo html_writer::tag('td', $yozuv, ['class' => 'tz-nowrap']);
        echo html_writer::tag('td', $baho);
        echo html_writer::tag('td', $xulcell, ['class' => 'tz-c-xul']);
        echo html_writer::tag('td', $zav, ['class' => 'tz-c-zav']);
        echo html_writer::tag('td', get_string('oqp_an_view', 'local_targetzenit') . ' ▾',
            ['class' => 'tz-exp']);
        echo html_writer::end_tag('tr');

        // --- DETAL QATORI (yashirin)
        $det = '';
        if ($xul !== '') {
            $det .= html_writer::div(
                html_writer::tag('span', 'AI', ['class' => 'tz-aibadge']) . ' ' . s($xul),
                'tz-dl-ai');
        }
        // Zavuch fikri zanjiri + JAVOB formasi (yadro fidbek — handle ulangan)
        $det .= fidbek::render($lid, $userid, '', false);
        // Dars kokpitiga o'tish
        $det .= html_writer::div(html_writer::link(
            new moodle_url('/local/targetzenit/dars.php', [
                'courseid' => (int) $r->courseid, 'date' => $r->lessondate,
                'lessonno' => (int) $r->lessonno]),
            get_string('oqp_an_cockpit', 'local_targetzenit') . ' →',
            ['class' => 'btn btn-sm btn-outline-primary']), 'mt-2');

        echo html_writer::tag('tr',
            html_writer::tag('td', html_writer::div($det, 'tz-adet-box'), ['colspan' => 7]),
            ['class' => 'tz-adet', 'id' => 'tzd-' . $i, 'hidden' => 'hidden']);
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();

    echo $OUTPUT->paging_bar($natija['jami'], $page, teacher::QATOR, $url(['view' => 'yozuvlar']));

    // Qatorni ochish/yopish.
    $PAGE->requires->js_amd_inline("
        window.tzTahTog = function(i) {
            var d = document.getElementById('tzd-' + i);
            if (!d) { return; }
            d.hidden = !d.hidden;
            var row = d.previousElementSibling;
            if (row) {
                row.classList.toggle('tz-on', !d.hidden);
                var e = row.querySelector('.tz-exp');
                if (e) { e.textContent = (d.hidden ? '"
                . get_string('oqp_an_view', 'local_targetzenit') . " ▾' : '"
                . get_string('oqp_an_close', 'local_targetzenit') . " ▴'); }
            }
        };");
}
