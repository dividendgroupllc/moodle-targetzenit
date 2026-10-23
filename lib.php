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
