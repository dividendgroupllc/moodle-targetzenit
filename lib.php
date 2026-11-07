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

    // MUROJAATLAR — CEO, direktor va zavuch uchun.
    //
    // NEGA ALOHIDA YORLIQ: sahifa direktor panelining yon menyusida ham bor,
    // lekin CEO direktor paneliga KIRA OLMAYDI (`viewteachers` ruxsati unda
    // yo'q). Yorliqsiz u manzilni qo'lda yozishi kerak edi — 2026-11-07 da
    // shu muammo chiqdi.
    if (\local_targetzenit\murojaat::rahbarmi()) {
        $mur = navigation_node::create(
            get_string('mur_panel', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/murojaat.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzmurojaat',
            new pix_icon('i/flagged', '')
        );
        $mur->showinflatnavigation = true;
        $navigation->add_node($mur);
    }

    // "Murojaatim" — o'quvchi (ota-ona) o'z murojaatiga javobni shu yerdan
    // ko'radi. Faqat murojaati BO'LGANLARGA: hamma o'quvchining menyusini
    // bo'sh sahifa bilan to'ldirishning ma'nosi yo'q.
    if (\local_targetzenit\murojaat::meniki((int) $USER->id, 1)) {
        $mm = navigation_node::create(
            get_string('mur_mine', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/murojaatim.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzmurojaatim',
            new pix_icon('i/notifications', '')
        );
        $mm->showinflatnavigation = true;
        $navigation->add_node($mm);
    }

    // "Mening darslarim" — o'qituvchining o'z paneli.
    //
    // Shart: shu odamga YOZILGAN darsi bor. `_fact.attributeduserid` bo'yicha
    // tekshiriladi, ya'ni o'rnini bosib dars o'tgan odam ham ko'radi, dars
    // o'tmaydigan xodim esa ko'rmaydi. Jadvaldan YUQORIDA turmaydi: jadval
    // kundalik kirish nuqtasi bo'lib qoladi, panel esa haftada bir ochiladi.
    if (\local_targetzenit\teacher::has_any((int) $USER->id)) {
        $oq = navigation_node::create(
            get_string('oq_mypanel', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/oqituvchi.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzoqituvchi',
            new pix_icon('i/grades', '')
        );
        $oq->showinflatnavigation = true;
        $navigation->add_node($oq);
    }

    // "O'qituvchilar sharhi" — o'quvchining (ota-onaning) o'z sahifasi.
    //
    // Shart: shu o'quvchi haqida OCHIQ sharh bor. Yo'q bo'lsa havola
    // chiqmaydi — bo'sh sahifa savol tug'diradi. Bildirishnomadagi havola
    // ham shu sahifaga olib boradi, ya'ni menyu ikkinchi, barqaror yo'l.
    if (\local_targetzenit\sharh::has_any((int) $USER->id)) {
        $sh = navigation_node::create(
            get_string('sh_mine', 'local_targetzenit'),
            new moodle_url('/local/targetzenit/sharhlarim.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tzsharhlarim',
            new pix_icon('i/comment', '')
        );
        $sh->showinflatnavigation = true;
        $navigation->add_node($sh);
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

/**
 * MOODLE COMMENT QUYI TIZIMI — parametr tekshiruvi.
 *
 * MAJBURIY: `core_comment\manager::validate()` plagin chaqiruvini standart
 * qiymat **false** bilan so'raydi va javob `false` bo'lsa
 * `invalidcommentparam` xatosini otadi. Ya'ni bu funksiya yozilmasa
 * `can_post()` ning o'zi ishlamaydi.
 *
 * Faqat o'zimizning maydonimizga ruxsat beramiz: comment quyi tizimi
 * umumiy, `commentarea` ni tekshirmasak begona AJAX so'rovi bizning
 * nomimizdan yozib ketishi mumkin.
 */
function local_targetzenit_comment_validate(stdClass $params) {
    if (($params->commentarea ?? '') !== \local_targetzenit\sharh::AREA) {
        return false;
    }
    // `itemid` = o'quvchining user.id, kontekst esa KURS (guruh + fan).
    if (empty($params->itemid) || empty($params->context)) {
        return false;
    }
    return (int) $params->context->contextlevel === CONTEXT_COURSE;
}

/**
 * MOODLE COMMENT QUYI TIZIMI — kim yozadi, kim o'qiydi.
 *
 * DIQQAT: Moodle javobni o'zining `moodle/comment:post` / `:view`
 * ruxsatlari bilan VA amali bilan birlashtiradi. `moodle/comment:post`
 * esa kurs kontekstida O'QUVCHIDA HAM bor (core arxetipi), shuning uchun
 * yozishni `local/targetzenit:teach` ga qattiq bog'laymiz — aks holda
 * o'quvchi o'zi haqida sharh yozib qo'yardi.
 *
 * Ko'rish: dars o'tadigan o'qituvchi, SHU o'quvchining o'zi (itemid) va
 * butun maktabni ko'rish huquqi borlar (direktor/zavuch).
 */
function local_targetzenit_comment_permissions(stdClass $params) {
    global $USER;

    if (($params->commentarea ?? '') !== \local_targetzenit\sharh::AREA) {
        return ['post' => false, 'view' => false];
    }
    $post = has_capability('local/targetzenit:teach', $params->context);
    $view = $post
        || (int) $USER->id === (int) $params->itemid
        || has_capability('local/targetzenit:viewteachers', context_system::instance());

    return ['post' => $post, 'view' => $view];
}
