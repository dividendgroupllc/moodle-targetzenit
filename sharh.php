<?php
// O'QUVCHILAR SHARHI — o'qituvchining yozish sahifasi.
//
// Sahifa javob beradigan savol: «shu guruhdagi har bir o'quvchi haqida men
// nima deb yozdim va kimga hali hech narsa yozmadim?» — shuning uchun
// markazda guruhning TO'LIQ o'quvchilar ro'yxati turadi, sharh yozilganlari
// emas. Bo'sh qator ko'rinib turishi kerak: u ish qoldiq ekanini aytadi.
//
// Qamrov KURS = guruh + fan (qaror 2026-11-06). O'qituvchining kursi
// allaqachon shunday (`<yil>::<guruh>::<fan>`), shuning uchun «guruh bo'yicha
// filtr» — kurs tanlagichining o'zi.
//
// Matn Moodle'ning standart `{comments}` jadvaliga yoziladi — `classes/sharh.php`.

require(__DIR__ . '/../../config.php');

use local_targetzenit\journal;
use local_targetzenit\lesson;
use local_targetzenit\output\shell;
use local_targetzenit\output\sharhblok;
use local_targetzenit\output\ui;
use local_targetzenit\sharh;
use local_targetzenit\timetable;

require_login();

$courseid = optional_param('cid', 0, PARAM_INT);
$qidiruv = trim(optional_param('q', '', PARAM_TEXT));
$open = optional_param('open', 0, PARAM_INT);
$ochir = optional_param('ochir', 0, PARAM_INT);
$edit = optional_param('edit', '', PARAM_ALPHANUM);

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/targetzenit/sharh.php'));
// To'liq ekran qobig'i — o'qituvchi panelining boshqa sahifalari bilan ayni
// bir xil (`output\shell`), menyu `shell::oq_menu()` dan keladi.
shell::setup($PAGE, get_string('sh_nav', 'local_targetzenit'));

$year = timetable::current_year();
$kurslar = $year ? journal::my_courses((int) $USER->id, $year) : [];

// ARXIV KURSLAR — o'qituvchi SHARH YOZGAN, lekin endi jadvalida yo'q.
//
// NEGA KERAK (2026-11-13 da chiqqan muammo): Frappe kurs sinxroni jadvalda
// yo'q (guruh x fan) juftliklaridan o'qituvchini OLIB TASHLAYDI (jurnalda
// «Jadvalda yo'q, lekin kursi bor guruh: 63 ta»; 2026-11-12 da 42 yozilish
// suspend qilingan). `journal::my_courses()` faol yozilishni talab qilgani
// uchun o'qituvchi kecha yozgan sharhlarini bugun KO'RMAY QOLDI.
//
// QOIDA: «men qaysi guruhga dars beraman» va «men nima yozganman» — ikki
// boshqa savol. Jadval birinchisini hal qiladi, ikkinchisini YOPMAYDI.
$arxiv = [];
foreach (sharh::mening_kurslarim((int) $USER->id) as $c) {
    if (!isset($kurslar[(int) $c->id])) {
        $arxiv[(int) $c->id] = $c;
        $kurslar[(int) $c->id] = $c->fullname;
    }
}

// Tanlangan kurs ro'yxatda bo'lishi SHART: `cid` ni qo'lda o'zgartirib
// begona kursni ochishga yo'l qo'yilmaydi (yozish tekshiruvi `sharh::add()`
// da ham bor, lekin ro'yxatni ham ko'rsatmaslik kerak).
if ($courseid && !isset($kurslar[$courseid])) {
    $courseid = 0;
}
if (!$courseid && $kurslar) {
    $courseid = (int) array_key_first($kurslar);
}

/** Joriy sahifaning havolasi — tanlovlar saqlanadi. */
$url = function(array $p = []) use ($courseid, $qidiruv) {
    $asos = ['cid' => $courseid];
    if ($qidiruv !== '') {
        $asos['q'] = $qidiruv;
    }
    return new moodle_url('/local/targetzenit/sharh.php', $p + $asos);
};

// ------------------------------------------------------------------- YOZISH
// Chiqarishdan OLDIN: saqlagach redirect qilamiz, shunda sahifa yangilansa
// forma qayta yuborilmaydi (POST/Redirect/GET).
// ------------------------------------------------------------- O'CHIRISH
// Chiqarishdan OLDIN: amaldan keyin redirect, shunda sahifa yangilansa
// o'chirish qayta bajarilmaydi.
if ($ochir && confirm_sesskey()) {
    // Ruxsat `sharh::ochir()` ichida ham tekshiriladi — havolani qo'lda
    // yozish mumkin, tugmani yashirish himoya emas.
    $n = sharh::ochir($ochir);
    redirect($url(['open' => $open]),
        get_string('sh_deleted', 'local_targetzenit', $n), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

if (sharhblok::handle()) {
    redirect($url(['open' => optional_param('shstudent', 0, PARAM_INT)]),
        get_string('sh_saved', 'local_targetzenit'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Qidiruv maydoni sarlavha yonida — o'qituvchi ism yozib darhol topadi.
$vositalar = '';
if ($courseid) {
    $vositalar = html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/local/targetzenit/sharh.php'),
        'class' => 'tz-sh-search']);
    $vositalar .= html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'cid', 'value' => $courseid]);
    $vositalar .= html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q',
        'value' => $qidiruv, 'class' => 'form-control form-control-sm',
        'placeholder' => get_string('sh_searchph', 'local_targetzenit')]);
    $vositalar .= html_writer::tag('button', get_string('sh_search', 'local_targetzenit'),
        ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-secondary']);
    if ($qidiruv !== '') {
        $vositalar .= html_writer::link(
            new moodle_url('/local/targetzenit/sharh.php', ['cid' => $courseid]),
            get_string('sh_clear', 'local_targetzenit'),
            ['class' => 'btn btn-sm btn-link']);
    }
    $vositalar .= html_writer::end_tag('form');
}

shell::start_menu('sharh', get_string('sh_nav', 'local_targetzenit'),
    get_string('sh_hint', 'local_targetzenit'), $vositalar,
    shell::oq_menu((int) $USER->id, true));

echo html_writer::start_div('tz-oq tz-sh');

if (!$kurslar) {
    // Eng ko'p uchraydigan sabab: odam `teach` ruxsati bor kursga yozilmagan
    // (yoki o'quv yili jadvali hali kelmagan) — shuni aytib to'xtaymiz.
    echo $OUTPUT->notification(get_string('sh_nocourses', 'local_targetzenit'), 'info');
    echo html_writer::end_div();
    shell::end();
    echo $OUTPUT->footer();
    exit;
}

// ===================================================== GURUH (KURS) TANLASH
// Yorliq `<yil>::<guruh>::<fan>` dan chiqadi, shuning uchun idnumber kerak.
// Kurslar BITTA so'rovda olinadi: halqa ichida `get_field` yozilsa
// o'qituvchining har bir kursi uchun alohida so'rov ketardi.
$idnumberlar = $kurslar
    ? $DB->get_records_list('course', 'id', array_keys($kurslar), '', 'id, idnumber')
    : [];

echo html_writer::start_div('tz-dir-tabs tz-sh-tabs');
foreach ($kurslar as $cid => $nom) {
    $yorliq = sharh::kurs_yorlig($idnumberlar[$cid]->idnumber ?? '', $nom);
    // Arxiv kurs ko'zga tashlanib turishi kerak: o'qituvchi NEGA u yerda
    // yangi sharh yoza olmayotganini tushunishi uchun.
    $arxivmi = isset($arxiv[(int) $cid]);
    echo html_writer::link(
        new moodle_url('/local/targetzenit/sharh.php', ['cid' => $cid]),
        s($yorliq) . ($arxivmi
            ? ' ' . html_writer::tag('span', get_string('sh_archived', 'local_targetzenit'),
                ['class' => 'tz-sh-arxchip']) : ''),
        ['class' => 'tz-dir-tab' . ($courseid === (int) $cid ? ' tz-on' : '')
            . ($arxivmi ? ' tz-sh-tabarx' : '')]);
}
echo html_writer::end_div();

$context = context_course::instance($courseid);

// RUXSAT: shu kursda dars o'tadi YOKI shu kursga o'zi sharh yozgan.
// Ikkinchi yo'l yozilish suspend qilinganda ham ochiq qoladi — o'qituvchi
// o'z so'zini ko'rishdan mahrum bo'lmasligi kerak. Yozish esa baribir
// `teach` talab qiladi (`sharh::add()` ichida), ya'ni arxiv kursda forma
// o'zi chizilmaydi.
if (!sharh::kurs_korish_mumkinmi($courseid)) {
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('sh_nocourseaccess', 'local_targetzenit'));
}

// Arxiv kursda nima bo'layotganini OCHIQ aytamiz, aks holda «nega yozolmayman»
// degan savol javobsiz qoladi.
if (isset($arxiv[$courseid])) {
    echo $OUTPUT->notification(get_string('sh_archivednotice', 'local_targetzenit'),
        'info');
}

$oquvchilar = lesson::students($context);

// Ism/familiya bo'yicha filtr — SERVERDA, chunki ro'yxat sahifalanishi
// mumkin va qidiruv natijasi havolada saqlanishi kerak (JS filtri sahifa
// yangilanganda yo'qoladi).
if ($qidiruv !== '') {
    $past = core_text::strtolower($qidiruv);
    $oquvchilar = array_filter($oquvchilar, function($o) use ($past) {
        $hamma = core_text::strtolower(
            $o->firstname . ' ' . $o->lastname . ' ' . (string) $o->idnumber);
        return core_text::strpos($hamma, $past) !== false;
    });
}

// Maxfiy qaydlarni ham MUALLIF ko'radi. Bu yerda hamma qator chiqariladi
// (`$maxfiyham = true`), chunki sahifa o'qituvchining o'z kursi — begona
// odam `teach` ruxsati bo'lmaganda bu yergacha kelmaydi.
$oxirgilar = sharh::latest($courseid, true);

$jami = count($oquvchilar);
$yozilgan = 0;
foreach ($oquvchilar as $o) {
    if (!empty($oxirgilar[(int) $o->id]['soni'])) {
        $yozilgan++;
    }
}

echo html_writer::div(
    ui::tile((string) $jami, get_string('sh_students', 'local_targetzenit'))
    . ui::tile((string) $yozilgan, get_string('sh_written', 'local_targetzenit'),
        get_string('sh_ofstudents', 'local_targetzenit', $jami))
    . ui::tile((string) ($jami - $yozilgan), get_string('sh_missing', 'local_targetzenit'),
        get_string('sh_missinghint', 'local_targetzenit')),
    'tz-dir-hero');

if (!$oquvchilar) {
    echo $OUTPUT->notification($qidiruv !== ''
        ? get_string('sh_nomatch', 'local_targetzenit')
        : get_string('sh_noenrolled', 'local_targetzenit'), 'info');
    echo html_writer::end_div();
    shell::end();
    echo $OUTPUT->footer();
    exit;
}

// ============================================================ O'QUVCHILAR
echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-tablewrap');
echo html_writer::start_tag('table', ['class' => 'tz-dir-table tz-sh-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['sh_student', 'sh_count', 'sh_last'] as $k) {
    echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
}
echo html_writer::tag('th', '');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$action = $url(['open' => $open])->out(false);

foreach ($oquvchilar as $o) {
    $sid = (int) $o->id;
    $ism = \local_targetzenit\dashboard::ism($o->firstname, $o->lastname);
    $oxirgi = $oxirgilar[$sid] ?? null;
    $soni = (int) ($oxirgi['soni'] ?? 0);
    $ochiq = ($open === $sid);

    echo html_writer::start_tag('tr', ['class' => $ochiq ? 'tz-sh-open' : '']);

    // Ism o'quvchining baho kartasiga olib boradi — sharh yozishdan oldin
    // «bu kim va qanday o'qiyapti» degan savolga javob kerak bo'ladi.
    echo html_writer::tag('td',
        html_writer::div(
            html_writer::link(
                new moodle_url('/local/targetzenit/baholarim.php', ['userid' => $sid]),
                s($ism), ['class' => 'tz-sh-name'])
            . ((string) $o->idnumber !== ''
                ? html_writer::div(s($o->idnumber), 'tz-dir-sub') : ''),
            'tz-dir-cellname'));

    echo html_writer::tag('td', $soni
        ? html_writer::tag('span', (string) $soni, ['class' => 'tz-dir-chip'])
        : html_writer::tag('span', '—', ['class' => 'tz-dir-sub']));

    // Qisqa ko'rinish — OXIRGI to'plamning xulosasi.
    //
    // Bir qatorli yozish maydoni OLIB TASHLANDI: sharh endi besh
    // kategoriyadan iborat va bitta `<input>` ga sig'maydi.
    echo html_writer::tag('td',
        sharhblok::compactcell($courseid, $sid, $oxirgi, $action, ['open' => $open]),
        ['class' => 'tz-sh-lastcell']);

    // Tugma yorlig'i HOLATGA qarab: hali sharh yo'q bo'lsa «Sharh yozish»,
    // bor bo'lsa «Ochish». Yozish endi faqat shu yo'l bilan bo'ladi,
    // shuning uchun tugma nima qilishini aytib turishi kerak.
    if ($ochiq) {
        $yorliq = get_string('sh_close', 'local_targetzenit');
        $sinf = 'btn btn-sm btn-outline-secondary';
    } else if ($soni) {
        $yorliq = get_string('sh_openrow', 'local_targetzenit');
        $sinf = 'btn btn-sm btn-outline-secondary';
    } else {
        $yorliq = get_string('sh_write', 'local_targetzenit');
        $sinf = 'btn btn-sm btn-primary';
    }
    echo html_writer::tag('td',
        html_writer::link($url(['open' => $ochiq ? 0 : $sid]), $yorliq,
            ['class' => $sinf]),
        ['class' => 'tz-sh-actcell']);

    echo html_writer::end_tag('tr');

    // --- ochilgan karta: to'liq zanjir + to'liq forma (maxfiy tanlovi bilan)
    //
    // ALOHIDA SAHIFA EMAS: o'qituvchi 25 o'quvchi bo'ylab yurib izoh yozadi,
    // har safar sahifa almashsa ro'yxatdagi joyi yo'qoladi. `<tr>` sifatida
    // ochilgani uchun JS ham kerak emas.
    if ($ochiq) {
        $zanjir = sharh::for_student($courseid, $sid, true);
        echo html_writer::start_tag('tr', ['class' => 'tz-sh-detail']);
        echo html_writer::start_tag('td', ['colspan' => 4]);
        echo html_writer::start_div('tz-sh-card');
        echo html_writer::div(get_string('sh_about', 'local_targetzenit', s($ism)),
            'tz-dir-lab');
        // O'chirish havolasi — FAQAT sayt ma'muriga; TAHRIRLASH — faqat
        // muallifga. Ikkisining ham ruxsati chizuvchida tekshiriladi
        // (`ochirish_mumkinmi()` / `tahrirlash_mumkinmi()`).
        echo sharhblok::thread($zanjir, true, false,
            fn(int $shid) => $url(['open' => $open, 'ochir' => $shid,
                                   'sesskey' => sesskey()]),
            fn(string $b) => $url(['open' => $sid, 'edit' => $b]));

        // TAHRIR FORMASI yoki YANGI SHARH formasi — bittasi.
        //
        // Ikkisi bir vaqtda ko'rinsa o'qituvchi qaysi biriga yozayotganini
        // bilmaydi: biri mavjud sharhni o'zgartiradi, ikkinchisi yangi
        // sharh yaratadi.
        $tahrirlanadi = [];
        $tahrirmaxfiy = false;
        if ($edit !== '') {
            $qatorlari = sharh::batch_rows($edit);
            // Shu o'quvchiga tegishlimi va tahrirlash mumkinmi — tekshiramiz,
            // aks holda havolani qo'lda yozib boshqa o'quvchining sharhini
            // ochib olish mumkin bo'lardi.
            $b = $qatorlari ? reset($qatorlari) : null;
            if ($b && (int) $b->studentid === $sid
                    && sharh::tahrirlash_mumkinmi($b)) {
                foreach ($qatorlari as $kat => $r) {
                    $tahrirlanadi[$kat] = sharh::matn($r);
                }
                $tahrirmaxfiy = ($b->visibility === 'maxfiy');
            }
        }

        if ($tahrirlanadi) {
            echo html_writer::div(
                html_writer::link($url(['open' => $sid]),
                    '← ' . get_string('sh_cancel', 'local_targetzenit'),
                    ['class' => 'btn btn-sm btn-outline-secondary']), 'mt-3 mb-2');
            echo sharhblok::form($courseid, $sid, $action, false,
                ['open' => $sid], $tahrirlanadi, $edit, $tahrirmaxfiy);
        } else {
            echo sharhblok::form($courseid, $sid, $action, false, ['open' => $sid]);
        }
        echo html_writer::end_div();
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
    }
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();   // .tz-dir-tablewrap
echo html_writer::end_div();   // .tz-dir-card

echo html_writer::end_div();   // .tz-oq
shell::end();
echo $OUTPUT->footer();
