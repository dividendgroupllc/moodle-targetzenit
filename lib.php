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
