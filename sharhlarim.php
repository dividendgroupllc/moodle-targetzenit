<?php
// O'QITUVCHILAR SHARHI — O'QUVCHI AKKAUNTIDAGI KO'RINISH.
//
// OTA-ONA shu sahifani o'qiydi: Moodle'da ota-ona akkaunti yo'q (faqat
// Student/Instructor/Employee sinxronlanadi), shuning uchun u bolasining
// logini bilan kiradi — `murojaatim.php` bilan bir xil mantiq.
//
// Bildirishnomadagi havola ham shu yerga olib keladi. Sahifa ochilishi bilan
// sharhlar «ko'rildi» deb belgilanadi: o'qituvchi va direktor «yetib
// bordimi» ni bilishi kerak, yetmagan bo'lsa qo'ng'iroq qiladi.
//
// MAXFIY qaydlar bu yerda HECH QACHON ko'rinmaydi — `sharh::mine()` ularni
// so'rovning o'zida chiqarib tashlaydi (formani yoki blokni yashirish
// himoya emas).
//
// FAQAT O'QISH (qaror 2026-11-06): ota-ona javob yozmaydi. E'tirozi bo'lsa
// MUROJAATLAR moduli bor — u yerda javob egasi va muddati belgilangan.

require(__DIR__ . '/../../config.php');

use local_targetzenit\output\sharhblok;
use local_targetzenit\sharh;

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$ozim = ((int) $userid === (int) $USER->id);

// Boshqasining sahifasini ochish: ma'mur/direktor (tizim darajasida ruxsat)
// YOKI shu o'quvchiga dars beradigan o'qituvchi. Ikkinchi shart ataylab:
// `baholarim.php` dagi sharh havolasi o'qituvchi uchun ham ishlashi kerak,
// aks holda u boshi berk yo'l bo'lardi. O'qituvchining roli KURS kontekstida
// berilgani uchun tizim darajasida tekshirish yetarli emas (`baholarim.php`
// bilan AYNI mantiq).
//
// Maxfiy qaydlar baribir ko'rinmaydi — `sharh::mine()` ularni so'rovning
// o'zida chiqarib tashlaydi.
if (!$ozim) {
    // DIQQAT: `viewalltimetable` ATAYLAB ISHLATILMAYDI — u core arxetipi
    // bo'yicha HAR BIR o'qituvchida bor va butun maktabning sharhini ochib
    // qo'yardi. Sharh baho kartasidan ko'ra shaxsiyroq: u xodimning
    // subyektiv bahosi, shuning uchun qamrov «shu o'quvchiga dars beraman»
    // bilan cheklanadi.
    $ruxsat = has_capability('local/targetzenit:viewteachers', $context);
    if (!$ruxsat) {
        foreach (enrol_get_users_courses($userid, true, 'id') as $c) {
            if (has_capability('local/targetzenit:teach', context_course::instance($c->id))) {
                $ruxsat = true;
                break;
            }
        }
    }
    if (!$ruxsat) {
        throw new moodle_exception('nopermissions', 'error', '',
            get_string('sh_mine', 'local_targetzenit'));
    }
}
$odam = $ozim ? $USER : core_user::get_user($userid, '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/sharhlarim.php',
    $ozim ? [] : ['userid' => $userid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('sh_mine', 'local_targetzenit'));
$PAGE->set_heading($ozim ? get_string('sh_mine', 'local_targetzenit') : fullname($odam));

$rows = sharh::mine((int) $userid);

// «Ko'rildi» faqat O'ZI ochganda yoziladi — direktor ko'rib chiqsa
// o'qituvchi «ota-ona o'qidi» degan noto'g'ri xulosaga kelardi.
if ($ozim && $rows) {
    sharh::korildi((int) $userid);
}

echo $OUTPUT->header();

$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

if (!$rows) {
    echo $OUTPUT->notification($t('sh_emptymine'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('tz-sh tz-sh-parent');
echo html_writer::div($t('sh_minehint'), 'tz-dir-sub mb-3');

// Fan bo'yicha guruhlanadi: ota-ona «matematikada nima deyilgan» deb
// qaraydi, vaqt bo'yicha aralash ro'yxat o'qilishi qiyin. Guruh ichida
// tartib yangidan eskiga (`sharh::mine()` dan shunday keladi).
$kurslar = [];
foreach ($rows as $r) {
    $kurslar[(int) $r->courseid]['yorliq'] =
        sharh::kurs_yorlig($r->courseidnumber ?? '', $r->coursename ?? '');
    $kurslar[(int) $r->courseid]['rows'][] = $r;
}

foreach ($kurslar as $k) {
    echo html_writer::tag('details',
        html_writer::tag('summary',
            html_writer::div(s($k['yorliq']), 'tz-sh-pcourse')
            . html_writer::div($t('sh_countof', count($k['rows'])), 'tz-dir-sub'),
            ['class' => 'tz-sh-psum'])
        // Muallif ismi KERAK (bir guruhga bir necha o'qituvchi yozadi),
        // kurs yorlig'i esa KERAK EMAS — u sarlavhada turibdi.
        . sharhblok::thread($k['rows'], true, false),
        ['class' => 'tz-sh-pblok', 'open' => 'open']);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
