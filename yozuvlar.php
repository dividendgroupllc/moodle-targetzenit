<?php
// Dars yozuvlari — ma'muriy ro'yxat.
// Kim yozgan, qancha joy egallagan, tahlil qilinganmi — va o'chirish.

require(__DIR__ . '/../../config.php');

use local_targetzenit\audio;

// ODDIY sahifa, admin sahifasi EMAS.
//
// NEGA O'ZGARTIRILDI: avval `admin_externalpage_setup()` ishlatilardi va u
// sahifani ADMIN DARAXTIDAN qidiradi. Daraxtga qo'shish kodi esa
// `settings.php` da `if ($hassiteconfig)` ichida — ya'ni sahifa faqat
// `moodle/site:config` bor odam uchun RO'YXATGA OLINADI. Direktorda bu
// ruxsat yo'q (va bo'lmasligi ham kerak), shuning uchun sahifa o'zi
// `viewanalysis` ni talab qilsa ham "Access denied" berardi.
require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewanalysis', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/yozuvlar.php'));
$PAGE->set_pagelayout('standard');

$ochir = optional_param('ochir', 0, PARAM_INT);
$turi = optional_param('turi', 'audio', PARAM_ALPHA);   // audio | hammasi
$sahifa = optional_param('page', 0, PARAM_INT);
$qidiruv = optional_param('q', '', PARAM_TEXT);
const PER_PAGE = 50;

$PAGE->set_title(get_string('recordings', 'local_targetzenit'));
$PAGE->set_heading(get_string('recordings', 'local_targetzenit'));

// ------------------------------------------------------------------ O'CHIRISH
if ($ochir && confirm_sesskey()) {
    $a = $DB->get_record('local_targetzenit_audio', ['id' => $ochir], '*', MUST_EXIST);
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid], 'id, courseid');

    if ($dars) {
        $ctx = context_course::instance($dars->courseid, IGNORE_MISSING);
        if ($ctx) {
            get_file_storage()->delete_area_files($ctx->id, audio::COMPONENT, audio::FILEAREA, $a->lessonid);
        }
    }

    if ($turi === 'hammasi') {
        // Audio ham, transkript ham, tahlil ham — butunlay
        $DB->delete_records('local_targetzenit_audio', ['id' => $a->id]);
        $xabar = "Yozuv butunlay o'chirildi (audio, transkript va tahlil).";
    } else {
        // Faqat audio — transkript va tahlil qoladi
        $a->status = 'deleted';
        $a->filename = null;
        $a->filesize = 0;
        $a->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $a);
        $xabar = "Audio o'chirildi. Transkript va tahlil saqlanib qoldi.";
    }
    redirect(new moodle_url('/local/targetzenit/yozuvlar.php'), $xabar, null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// ------------------------------------------------------------------ RO'YXAT
$where = '1=1';
$params = [];
if ($qidiruv !== '') {
    $where .= ' AND (' . $DB->sql_like('c.fullname', ':q1', false) . ' OR ' .
        $DB->sql_like('l.topic', ':q2', false) . ')';
    $params['q1'] = '%' . $DB->sql_like_escape($qidiruv) . '%';
    $params['q2'] = '%' . $DB->sql_like_escape($qidiruv) . '%';
}

$sql = "SELECT a.*, l.lessondate, l.lessonno, l.topic, l.courseid,
               c.fullname AS coursename,
               u.firstname, u.lastname
          FROM {local_targetzenit_audio} a
          JOIN {local_targetzenit_lesson} l ON l.id = a.lessonid
          JOIN {course} c ON c.id = l.courseid
     LEFT JOIN {user} u ON u.id = a.teacherid
         WHERE $where
      ORDER BY l.lessondate DESC, a.timecreated DESC";

$jami = $DB->count_records_sql(
    "SELECT COUNT(1) FROM {local_targetzenit_audio} a
       JOIN {local_targetzenit_lesson} l ON l.id = a.lessonid
       JOIN {course} c ON c.id = l.courseid
      WHERE $where", $params);

$rows = $DB->get_records_sql($sql, $params, $sahifa * PER_PAGE, PER_PAGE);

// Umumiy hajm
$hajm = (int) $DB->get_field_sql("SELECT SUM(filesize) FROM {local_targetzenit_audio}");
$kun = (int) (get_config('local_targetzenit', 'audioretentiondays') ?: 15);

echo $OUTPUT->header();

echo html_writer::div(
    "Jami <b>{$jami}</b> yozuv · diskda <b>" . number_format($hajm / 1048576, 1) . " MB</b> · " .
    "audio <b>{$kun} kun</b> saqlanadi, so'ng avtomatik o'chadi (transkript qoladi)",
    'alert alert-info'
);

// Qidiruv
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'value' => $qidiruv,
    'class' => 'form-control mr-2', 'placeholder' => 'Kurs yoki mavzu bo\'yicha...']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Qidirish',
    'class' => 'btn btn-secondary']);
if ($qidiruv !== '') {
    echo html_writer::link(new moodle_url('/local/targetzenit/yozuvlar.php'), 'Tozalash',
        ['class' => 'btn btn-link']);
}
echo html_writer::end_tag('form');

if (!$rows) {
    echo $OUTPUT->notification('Yozuv topilmadi.', 'info');
} else {
    $t = new html_table();
    $t->head = ['Sana', 'Kurs / mavzu', "O'qituvchi", 'Davomiylik', 'Hajm', 'Holat', 'Amallar'];
    $t->attributes['class'] = 'generaltable';

    foreach ($rows as $r) {
        $ctx = context_course::instance($r->courseid, IGNORE_MISSING);
        $url = ($ctx && in_array($r->status, audio::FAYLLI, true))
            ? audio::file_url((int) $r->lessonid, $ctx) : null;

        $holat = [
            'recording' => '<span class="text-danger">● yozilmoqda</span>',
            'ready' => '<span class="text-success">audio bor</span>',
            'transcribed' => '<span class="text-primary">tahlil qilingan</span>',
            'deleted' => '<span class="text-muted">audio o\'chirilgan</span>',
            'failed' => '<span class="text-danger">xato</span>',
        ][$r->status] ?? s($r->status);

        if (!empty($r->aierror)) {
            $holat .= html_writer::div(s($r->aierror), 'small text-muted');
        }

        // Dars kokpiti EMAS, faqat-o'qish tahlil sahifasi.
        //
        // NEGA: kokpit kurs kontekstida `teach` ruxsatini talab qiladi va
        // direktorda u ataylab yo'q (u baho qo'ymasligi kerak) — eski havola
        // unda "ruxsat yo'q" sahifasiga olib borardi.
        $amallar = html_writer::link(
            new moodle_url('/local/targetzenit/dars_tahlil.php',
                ['lessonid' => $r->lessonid]),
            get_string('dir_analysis', 'local_targetzenit'),
            ['class' => 'btn btn-sm btn-outline-primary mr-1']);

        if ($url) {
            $amallar .= html_writer::link($url, 'Tinglash',
                ['class' => 'btn btn-sm btn-outline-primary mr-1', 'target' => '_blank']);
            $amallar .= html_writer::link(
                new moodle_url('/local/targetzenit/yozuvlar.php',
                    ['ochir' => $r->id, 'turi' => 'audio', 'sesskey' => sesskey()]),
                'Audioni o\'chirish',
                ['class' => 'btn btn-sm btn-outline-warning mr-1',
                 'onclick' => "return confirm('Audio o\\'chiriladi. Transkript va tahlil qoladi. Davom etamizmi?')"]);
        }
        $amallar .= html_writer::link(
            new moodle_url('/local/targetzenit/yozuvlar.php',
                ['ochir' => $r->id, 'turi' => 'hammasi', 'sesskey' => sesskey()]),
            'Butunlay',
            ['class' => 'btn btn-sm btn-outline-danger',
             'onclick' => "return confirm('Audio, transkript va tahlil BUTUNLAY o\\'chiriladi. Davom etamizmi?')"]);

        $t->data[] = [
            userdate(strtotime($r->lessondate), '%d.%m.%Y') .
                html_writer::div($r->lessonno . '-dars', 'small text-muted'),
            html_writer::tag('b', format_string($r->coursename)) .
                html_writer::div(format_string($r->topic ?? ''), 'small text-muted'),
            $r->firstname ? fullname($r) : '—',
            $r->duration ? gmdate('H:i:s', (int) $r->duration) : '—',
            $r->filesize ? number_format($r->filesize / 1048576, 1) . ' MB' : '—',
            $holat,
            $amallar,
        ];
    }
    echo html_writer::table($t);
    echo $OUTPUT->paging_bar($jami, $sahifa, PER_PAGE,
        new moodle_url('/local/targetzenit/yozuvlar.php', ['q' => $qidiruv]));
}

echo $OUTPUT->footer();
