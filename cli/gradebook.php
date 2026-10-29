<?php
/**
 * Gradebook bilan bog'liq diagnostika va backfill.
 *
 *   php local/targetzenit/cli/gradebook.php --holat
 *   php local/targetzenit/cli/gradebook.php --sinov       # bitta bahoni urinib ko'rish
 *   php local/targetzenit/cli/gradebook.php --backfill
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$opt, $un] = cli_get_params(
    ['help' => false, 'holat' => false, 'sinov' => false, 'backfill' => false, 'courseid' => 0],
    ['h' => 'help']
);

if ($opt['help'] || (!$opt['holat'] && !$opt['sinov'] && !$opt['backfill'])) {
    cli_writeln("--holat     gradebook holatini ko'rsatadi");
    cli_writeln("--sinov     bitta bahoni gradebook'ga yozishga urinadi va xatoni KO'RSATADI");
    cli_writeln("--backfill  barcha mavjud baholarni gradebook'ga ko'chiradi [--courseid=N]");
    exit(0);
}

use local_targetzenit\gradebook;

if ($opt['holat']) {
    foreach (gradebook::holat() as $k => $v) {
        cli_writeln(str_pad($k, 20) . $v);
    }
}

if ($opt['sinov']) {
    $r = $DB->get_record_sql("
        SELECT r.id, r.userid, r.grade, l.id AS lessonid, l.courseid
          FROM {local_targetzenit_record} r
          JOIN {local_targetzenit_lesson} l ON l.id = r.lessonid
         WHERE r.grade IS NOT NULL
      ORDER BY r.id DESC", [], IGNORE_MULTIPLE);
    if (!$r) {
        cli_writeln("Bizning jadvalda umuman baho yo'q.");
        exit(0);
    }
    cli_writeln("Sinov: record {$r->id}, dars {$r->lessonid}, kurs {$r->courseid}, "
        . "user {$r->userid}, baho {$r->grade}");
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $r->lessonid]);
    // Bu yerda try/catch YO'Q — xato to'liq stack bilan chiqsin.
    gradebook::push($dars, (int) $r->userid, $r->grade);
    cli_writeln("push() xatosiz tugadi.");
    foreach (gradebook::holat() as $k => $v) {
        cli_writeln(str_pad($k, 20) . $v);
    }
}

if ($opt['backfill']) {
    $n = gradebook::backfill((int) $opt['courseid'], function ($s) {
        cli_writeln($s);
    });
    cli_writeln("Jami {$n['jami']} | ko'chirildi {$n['ok']} | xato {$n['xato']}");
    foreach ($n['xabarlar'] as $x) {
        cli_writeln("  ! $x");
    }
}
