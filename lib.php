<?php
// Plagin ilgaklari (hooks).

defined('MOODLE_INTERNAL') || die();

/**
 * Chap menyuga "Dars jadvali" havolasini qo'shadi.
 * Hamma kirgan foydalanuvchi ko'radi — sahifaning o'zi kim nimani
 * ko'rishini (o'quvchi/o'qituvchi) ichkarida hal qiladi.
 */
function local_targetzenit_extend_navigation(global_navigation $navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    $node = navigation_node::create(
        get_string('timetable', 'local_targetzenit'),
        new moodle_url('/local/targetzenit/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'tztimetable',
        new pix_icon('i/calendar', '')
    );
    $node->showinflatnavigation = true;
    $navigation->add_node($node);
}

/**
 * Dars audio faylini berish.
 * Faqat shu kursda dars o'tish huquqi borlar eshitadi — o'quvchilarga ochiq emas.
 */
function local_targetzenit_pluginfile($course, $cm, $context, $filearea, $args,
                                      $forcedownload, array $options = []) {
    if ($filearea !== \local_targetzenit\audio::FILEAREA) {
        return false;
    }
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return false;
    }
    require_login($course, false, $cm);
    require_capability('local/targetzenit:teach', $context);

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_targetzenit',
        \local_targetzenit\audio::FILEAREA, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Jadval o'lchamini moslash tugmalari (50% – 150%).
 * Tanlov brauzerda eslab qolinadi; $kalit har sahifa uchun alohida.
 */
function local_targetzenit_zoom_panel(string $kalit): string {
    global $PAGE;

    $PAGE->requires->js(new moodle_url('/local/targetzenit/js/zoom.js'));
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var boshla = function() {
                if (window.tzZoomInit) { window.tzZoomInit('" . addslashes($kalit) . "'); }
                else { setTimeout(boshla, 150); }
            };
            boshla();
        });
    ");

    $html = html_writer::tag('span', "O'lcham:", ['class' => 'tz-zoom-label']);
    foreach ([50, 75, 100, 125, 150] as $d) {
        $html .= html_writer::tag('button', $d . '%', [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary',
            'data-zoom' => $d,
        ]);
    }
    return html_writer::div($html, 'tz-zoom', ['id' => 'tz-zoom']);
}
