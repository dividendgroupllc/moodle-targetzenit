<?php
// IMTIHONLAR — o'qituvchi portali, imtihonlar ro'yxati (ekran A).
//
// IMTIHON_ARXITEKTURA.md 3-bo'lim. Test (quiz) + Yozma ish (assign) bitta
// jadvalda: guruh, tur, sana, holat, topshirgan/baholangan. Orqada Moodle;
// amal havolalari yadro sahifalariga (view/report/grade) olib boradi.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\imtihon;
use local_targetzenit\topshiriq;
use local_targetzenit\output\maket;

require_login();
$context = context_system::instance();
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/imtihonlar.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title($t('im_nav'));
$PAGE->requires->css('/local/targetzenit/styles.css');

$rows = imtihon::royxat((int) $USER->id);
list($faol, $baho, $buhafta) = imtihon::stat($rows);
$yaratkurslar = topshiriq::yaratish_kurslari((int) $USER->id);

maket::boshla('imtihon');
echo html_writer::start_div('tz-ishstoli-wrap');

// --------------------------------------------------- SARLAVHA + YARATISH
echo html_writer::start_div('tz-im-head');
echo html_writer::div(
    html_writer::tag('div', $t('im_nav'), ['class' => 'tzp-h1'])
    . html_writer::tag('div', $t('im_sub'), ['class' => 'tzp-sub']));
if ($yaratkurslar) {
    echo html_writer::link(new moodle_url('/local/targetzenit/imtihon_yangi.php'),
        $t('im_new'), ['class' => 'btn btn-primary btn-sm tz-nowrap']);
}
echo html_writer::end_div();

// --------------------------------------------------- STATISTIKA
$tile = fn(int $n, string $lab, string $cls) => html_writer::div(
    html_writer::tag('span', $n, ['class' => 'tz-stat-num'])
    . html_writer::tag('span', $lab, ['class' => 'tz-stat-lab']),
    'tz-stat ' . $cls);
echo html_writer::div(
    $tile($faol, $t('im_stat_live'), 'tz-stat-ok')
    . $tile($baho, $t('im_stat_grade'), 'tz-stat-warn')
    . $tile($buhafta, $t('im_stat_week'), ''),
    'tz-statgrid');

// --------------------------------------------------- RO'YXAT
if (!$rows) {
    echo html_writer::div($t('im_empty'), 'tzp-empty m-3');
} else {
    echo html_writer::start_div('tzp-card p-0 tz-scroll');
    echo html_writer::start_tag('table', ['class' => 'tz-im-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    foreach (['im_col_exam', 'im_col_group', 'im_col_type', 'im_col_date',
              'im_col_status', 'im_col_progress', ''] as $h) {
        echo html_writer::tag('th', $h === '' ? '' : $t($h));
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($rows as $r) {
        // Sana: muddat bo'lsa muddat, aks holda ochilish.
        if ((int) $r->duedate) {
            $sana = $t('im_muddat') . ' ' . userdate((int) $r->duedate, '%d-%b');
        } else if ((int) $r->ochilish) {
            $sana = userdate((int) $r->ochilish, '%d-%b');
        } else {
            $sana = '—';
        }

        // Jarayon: topshirgan/jami + baholangan.
        $jami = (int) $r->jami;
        $foiz = $jami ? round(100 * (int) $r->topshirgan / $jami) : 0;
        $prog = $t('im_prog', (object) [
            'topshirgan' => (int) $r->topshirgan,
            'jami' => $jami,
            'baholangan' => (int) $r->baholangan,
        ]);
        $prog .= html_writer::tag('span',
            html_writer::tag('i', '', ['style' => 'width:' . $foiz . '%']),
            ['class' => 'tz-im-pbar']);

        list($amallab, $amalurl, $asosiy) = imtihon::amal($r);

        $hujayra = html_writer::tag('td',
            html_writer::tag('b', s($r->name)));
        $hujayra .= html_writer::tag('td', s($r->guruh));
        $turcell = html_writer::tag('span',
            $r->tur === 'test' ? $t('im_type_test') : $t('im_type_yoz'),
            ['class' => 'tz-extype tz-extype-' . $r->tur]);
        if (!empty($r->nizom)) {
            $turcell .= ' ' . html_writer::tag('span', s($r->nizom),
                ['class' => 'tz-extype tz-extype-nizom', 'title' => $t('im_w_nizom')]);
        }
        $hujayra .= html_writer::tag('td', $turcell);
        $hujayra .= html_writer::tag('td', s($sana), ['class' => 'tz-nowrap']);
        $hujayra .= html_writer::tag('td', html_writer::tag('span',
            $t($r->holatkey), ['class' => 'tz-exstat tz-exstat-' . $r->holat]));
        $hujayra .= html_writer::tag('td', $prog, ['class' => 'tz-im-prog']);
        $hujayra .= html_writer::tag('td', html_writer::link($amalurl,
            $t($amallab) . ' →',
            ['class' => 'btn btn-sm ' . ($asosiy ? 'btn-primary' : 'btn-outline-secondary')]));

        echo html_writer::tag('tr', $hujayra);
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
