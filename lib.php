<?php
// Plagin ilgaklari (hooks).

defined('MOODLE_INTERNAL') || die();

/**
 * Chap menyuga "Dars jadvali" havolasini qo'shadi.
 * Hamma kirgan foydalanuvchi ko'radi — sahifaning o'zi kim nimani
 * ko'rishini (o'quvchi/o'qituvchi) ichkarida hal qiladi.
 */
function local_targetzenit_extend_navigation(global_navigation $navigation) {
    global $USER;

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

    // Direktor paneli — ruxsati borlarga. Jadvaldan YUQORIDA turadi:
    // direktor uchun kundalik kirish nuqtasi shu, jadval emas.
    if (has_capability('local/targetzenit:viewteachers', context_system::instance())) {
        $dir = navigation_node::create(
            get_string('dir_panel', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/direktor.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzdirektor',
            new pix_icon('i/report', '')
        );
        $dir->showinflatnavigation = true;
        $navigation->add_node($dir);
    }

    // "Mening oyligim" — chap menyuda (dashbordda ham ko'rinadi).
    //
    // NEGA SHU YERDA HAM: yuqori (primary) menyuga qo'yilgan edi, lekin Boost
    // temasi sig'magan elementlarni "More" ro'yxatiga yashiradi va havola
    // ko'rinmay qoldi. Chap menyu dashbordda doim ochiq — xodim shu yerdan
    // o'tadi.
    //
    // Shart: oylik ma'lumoti BOR odam. O'quvchi va ota-onada bunday qator
    // bo'lmaydi, shuning uchun ularga havola chiqmaydi.
    if (\local_targetzenit\payroll::has_any((int) $USER->id)) {
        $oylik = navigation_node::create(
            get_string('mypayroll', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/oylik.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzpayroll',
            new pix_icon('i/payment', '')
        );
        $oylik->showinflatnavigation = true;
        $navigation->add_node($oylik);
    }
}

/**
 * Foydalanuvchi profiliga "Mening oyligim" havolasi.
 *
 * Chap menyudan tashqari ikkinchi, barqaror kirish nuqtasi: tema o'zgarsa yoki
 * menyu yig'ilib qolsa ham xodim o'z profilidan oyligini topadi. Faqat O'Z
 * profilida va faqat oylik ma'lumoti bor bo'lsa ko'rinadi.
 */
function local_targetzenit_myprofile_navigation(\core_user\output\myprofile\tree $tree,
                                                $user, $iscurrentuser, $course) {
    if (!$iscurrentuser || !\local_targetzenit\payroll::has_any((int) $user->id)) {
        return;
    }
    $tree->add_node(new \core_user\output\myprofile\node(
        'miscellaneous',
        'tzpayroll',
        get_string('mypayroll', 'local_targetzenit'),
        null,
        new moodle_url('/local/targetzenit/oylik.php')
    ));
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

    // Shu kursda dars o'tadigan odam YOKI butun maktab tahlilini ko'rish
    // huquqi borlar (direktor/zavuch). O'quvchilarga ochiq emas.
    //
    // NEGA IKKINCHI SHART: direktorning kurs kontekstida `teach` ruxsati
    // yo'q (ataylab — u baho qo'ymasligi kerak), lekin tahlilni audio bilan
    // tekshirishi kerak. Aks holda tahlil sahifasidagi pleyer 403 berardi.
    if (!has_capability('local/targetzenit:teach', $context)) {
        require_capability('local/targetzenit:viewanalysis', context_system::instance());
    }

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
