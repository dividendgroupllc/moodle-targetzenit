<?php
/**
 * «O'quv koordinatori» rolini yaratish — BIR MARTALIK vosita.
 *
 *   php local/targetzenit/cli/koordinator_rol.php --yarat
 *   php local/targetzenit/cli/koordinator_rol.php --qidir=Aliyev
 *   php local/targetzenit/cli/koordinator_rol.php --ber=HR-EMP-00001
 *   php local/targetzenit/cli/koordinator_rol.php --bergan
 *   php local/targetzenit/cli/koordinator_rol.php --tekshir=HR-EMP-00001
 *
 * NEGA PLAGIN O'ZI YARATMAYDI: Moodle'da rol yaratish ma'murning qarori.
 * Migratsiyada avtomatik yaratish jim yiqilishi mumkin va keyin rol
 * sozlamalari qo'lda o'zgartirilsa, har deployda qaytib ketardi
 * (`direktor_rol.php` bilan bir xil sabab).
 *
 * ================================ IKKI ROL, NEGA? ============================
 *
 * Koordinatorga ikki xil narsa kerak:
 *   (1) BIZNING sahifalarimiz — `manageexams`, `viewexamresults`. Bular
 *       CONTEXT_SYSTEM capability lari, ya'ni TIZIM kontekstida tekshiriladi.
 *   (2) MOODLE YADROSI — kursga `quiz` yaratish, savol bankini boshqarish.
 *
 * Bitta rol bilan bo'lmaydi: ruxsat Moodle'da YUQORIDAN PASTGA oqadi.
 * Rolni `<yil>` kategoriyasiga biriktirsak, kategoriya ichidagi kurslarda
 * hammasi ishlaydi, lekin TIZIM kontekstidagi `manageexams` tekshiruvi
 * YIQILADI (kategoriya tizimdan pastda). Hammasini tizimga biriktirsak esa
 * koordinator SAYTDAGI HAR QANDAY kursda faoliyat tahrirlay oladigan bo'lib
 * qoladi — o'qituvchining topshirig'ini o'chirib yuborishi mumkin.
 *
 * Shuning uchun:
 *   `tzkoordinator`  TIZIM kontekstida   — faqat bizning capability lar
 *   `tzexamops`      KATEGORIYA ichida   — yadro capability lari, `<yil>` bilan
 *                                          cheklangan («barcha guruhlar» aynan shu)
 *
 * ARXETIP ATAYLAB BO'SH: `manager` arxetipi berilsa, kelajakda
 * `'manager' => CAP_ALLOW` deb e'lon qilingan HAR QANDAY yangi capability
 * koordinatorga avtomatik tushardi — masalan `viewallpayroll` (butun
 * maktabning oyligi). Hamma ruxsat shu yerda, ro'yxat bilan beriladi.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

use local_targetzenit\timetable;

/** Tizim kontekstidagi rol — bizning sahifalar. */
const TZK_ROL = 'tzkoordinator';

/** Kategoriya kontekstidagi rol — Moodle yadrosi amallari. */
const TZK_OPS_ROL = 'tzexamops';

/** Bizning capability lar (tizim kontekstida). */
const TZK_RUXSATLAR = [
    'local/targetzenit:manageexams',
    'local/targetzenit:viewexamresults',
    // Koordinator imtihon jadvalini dars jadvali bilan solishtiradi
    'local/targetzenit:viewalltimetable',
];

/**
 * Yadro capability lari (KATEGORIYA kontekstida).
 *
 * BERILMAYDI (ataylab):
 *   moodle/grade:edit        — bahoni qo'lda tuzatish; baho faqat modul orqali
 *   moodle/course:delete     — kursni o'chirish
 *   moodle/role:assign       — rol tarqatish
 *   local/targetzenit:teach  — koordinator dars o'tmaydi (direktor bilan bir xil)
 */
const TZK_OPS_RUXSATLAR = [
    // Kursni ko'rish (yashirin markaziy baholash kursi ham)
    'moodle/course:view',
    'moodle/course:viewhiddencourses',
    'moodle/course:viewparticipants',
    // `create_module()` aynan shuni talab qiladi
    'moodle/course:manageactivities',
    'moodle/course:activityvisibility',
    // Imtihon (quiz)
    'mod/quiz:addinstance',
    'mod/quiz:manage',
    'mod/quiz:preview',
    'mod/quiz:viewreports',
    'mod/quiz:grade',
    // Savol banki (Moodle 5.x da alohida modul)
    'mod/qbank:addinstance',
    'mod/qbank:view',
    // Savollar
    'moodle/question:add',
    'moodle/question:editall',
    'moodle/question:viewall',
    'moodle/question:useall',
    'moodle/question:managecategory',
    'moodle/question:moveall',
    // Natijani ko'rish (tuzatish emas)
    'moodle/grade:viewall',
];

[$opt, $un] = cli_get_params(
    ['help' => false, 'yarat' => false, 'bergan' => false, 'ber' => '',
     'qidir' => '', 'tekshir' => '', 'kategoriya' => ''],
    ['h' => 'help']
);

if ($opt['help'] || (!$opt['yarat'] && !$opt['bergan'] && $opt['ber'] === ''
        && $opt['qidir'] === '' && $opt['tekshir'] === '')) {
    cli_writeln("--yarat                 ikki rolni yaratadi (bor bo'lsa ruxsatlarini tiklaydi)");
    cli_writeln("--kategoriya=<id>       `<yil>` kategoriyasi (berilmasa nomidan topiladi)");
    cli_writeln("--qidir=<matn>          xodimni topadi va Employee ID sini ko'rsatadi");
    cli_writeln("--ber=<idnumber>        IKKI rolni ham shu xodimga beradi");
    cli_writeln("--bergan                rol kimga berilganini ko'rsatadi");
    cli_writeln("--tekshir=<idnumber>    shu odamda qaysi ruxsat BOR-YO'Qligini ko'rsatadi");
    exit(0);
}

$syscontext = context_system::instance();

/**
 * `<yil>` kurs kategoriyasini topadi.
 *
 * Kurslar `moodle_kurs.py` tomonidan `<yil>` -> `<guruh>` ko'rinishida
 * joylangan, ya'ni ildizdagi kategoriya nomi o'quv yili («2026-2027»).
 */
function tzk_kategoriya(string $berilgan): ?stdClass {
    global $DB;

    if ($berilgan !== '') {
        return $DB->get_record('course_categories', ['id' => (int) $berilgan]) ?: null;
    }
    $yil = timetable::current_year();
    if ($yil === '') {
        return null;
    }
    // Ildiz darajasidagi (parent = 0) kategoriya — o'quv yili
    $cat = $DB->get_record('course_categories', ['name' => $yil, 'parent' => 0]);
    if (!$cat) {
        $cat = $DB->get_record('course_categories', ['name' => $yil]);
    }
    return $cat ?: null;
}

// -------------------------------------------------------------------- YARAT
if ($opt['yarat']) {

    // ---------- (1) tizim roli: bizning sahifalar
    $rol = $DB->get_record('role', ['shortname' => TZK_ROL]);
    if (!$rol) {
        // ARXETIP BO'SH — yuqoridagi izohga qarang
        $rolid = create_role("O'quv koordinatori", TZK_ROL,
            "Summativ baholashni (BSB/CHSB/LI) tashkil qiladi, natijani tahlil "
            . "qiladi. Dars o'tmaydi va kundalik baho qo'ymaydi.", '');
        $rol = $DB->get_record('role', ['id' => $rolid]);
        cli_writeln("Rol yaratildi: " . TZK_ROL . " id={$rolid}");
    } else {
        cli_writeln("Rol bor: " . TZK_ROL . " id={$rol->id} — ruxsatlari tiklanadi");
    }
    set_role_contextlevels($rol->id, [CONTEXT_SYSTEM]);

    foreach (array_merge(TZK_RUXSATLAR,
            ['moodle/site:viewparticipants', 'moodle/user:viewdetails']) as $cap) {
        if (!get_capability_info($cap, false)) {
            cli_writeln("  ! ruxsat topilmadi: {$cap} — plagin yangilanganmi?");
            continue;
        }
        assign_capability($cap, CAP_ALLOW, $rol->id, $syscontext->id, true);
        cli_writeln("  + {$cap}");
    }

    // ---------- (2) kategoriya roli: Moodle yadrosi amallari
    $ops = $DB->get_record('role', ['shortname' => TZK_OPS_ROL]);
    if (!$ops) {
        $opsid = create_role("Imtihon operatsiyalari", TZK_OPS_ROL,
            "Kurslarda imtihon (quiz) va savol bankini boshqarish. `<yil>` "
            . "kategoriyasiga biriktiriladi — shunda barcha guruhlar qamraladi.", '');
        $ops = $DB->get_record('role', ['id' => $opsid]);
        cli_writeln("Rol yaratildi: " . TZK_OPS_ROL . " id={$opsid}");
    } else {
        cli_writeln("Rol bor: " . TZK_OPS_ROL . " id={$ops->id} — ruxsatlari tiklanadi");
    }
    // Faqat KATEGORIYA darajasida biriktiriladi: tizimga qo'yilsa butun
    // saytda faoliyat tahrirlash ochilib ketardi.
    set_role_contextlevels($ops->id, [CONTEXT_COURSECAT]);

    $cat = tzk_kategoriya($opt['kategoriya']);
    if (!$cat) {
        cli_writeln('');
        cli_writeln("! `<yil>` kategoriyasi topilmadi. Ruxsatlar KATEGORIYA");
        cli_writeln("  kontekstida beriladi, shuning uchun uni ko'rsatish kerak:");
        cli_writeln("  --kategoriya=<id>  (Site administration -> Courses -> Manage)");
        exit(1);
    }
    $catcontext = context_coursecat::instance($cat->id);
    cli_writeln("Kategoriya: {$cat->name} (id={$cat->id})");

    foreach (TZK_OPS_RUXSATLAR as $cap) {
        if (!get_capability_info($cap, false)) {
            cli_writeln("  ! ruxsat topilmadi: {$cap}");
            continue;
        }
        assign_capability($cap, CAP_ALLOW, $ops->id, $catcontext->id, true);
        cli_writeln("  + {$cap}");
    }

    $syscontext->mark_dirty();
    $catcontext->mark_dirty();
    cli_writeln('');
    cli_writeln("Tayyor. Rolni berish: --ber=<Employee ID>");
}

// -------------------------------------------------------------------- QIDIR
// DIQQAT: bu faqat KO'RSATISH — rol berish baribir `idnumber` bilan ketadi,
// ism bilan hech qachon (unic-id qoidasi).
if ($opt['qidir'] !== '') {
    $q = '%' . $DB->sql_like_escape($opt['qidir']) . '%';
    $rows = $DB->get_records_select('user',
        "deleted = 0 AND idnumber <> '' AND ("
        . $DB->sql_like('firstname', ':q1', false) . ' OR '
        . $DB->sql_like('lastname', ':q2', false) . ' OR '
        . $DB->sql_like('idnumber', ':q3', false) . ' OR '
        . $DB->sql_like('email', ':q4', false) . ')',
        ['q1' => $q, 'q2' => $q, 'q3' => $q, 'q4' => $q],
        'lastname ASC', 'id, firstname, lastname, idnumber, email', 0, 30);

    if (!$rows) {
        cli_writeln("Topilmadi. Xodim Frappe'da `Employee` bo'lib, sinxron o'tganmi?");
    }
    foreach ($rows as $u) {
        cli_writeln('  ' . str_pad(fullname($u), 32) . str_pad((string) $u->idnumber, 18)
            . $u->email);
    }
    cli_writeln('');
    cli_writeln("Rol berish: --ber=<yuqoridagi Employee ID>");
}

// ---------------------------------------------------------------------- BER
if ($opt['ber'] !== '') {
    $rol = $DB->get_record('role', ['shortname' => TZK_ROL]);
    $ops = $DB->get_record('role', ['shortname' => TZK_OPS_ROL]);
    if (!$rol || !$ops) {
        cli_error("Rollar yo'q — avval --yarat");
    }
    // QOIDA: odam Employee ID (user.idnumber) bo'yicha topiladi, ism bilan
    // HECH QACHON. Hisob Frappe'da yaratilib 07:00 sinxronida keladi.
    $user = $DB->get_record('user', ['idnumber' => $opt['ber'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['ber']}\n"
            . "Hisob Frappe'da `Employee` sifatida yaratilishi va sinxron o'tishi kerak.");
    }

    role_assign($rol->id, $user->id, $syscontext->id);
    cli_writeln("  + " . TZK_ROL . " (tizim)");

    $cat = tzk_kategoriya($opt['kategoriya']);
    if (!$cat) {
        cli_writeln("  ! kategoriya topilmadi — " . TZK_OPS_ROL . " BERILMADI.");
        cli_writeln("    Imtihon yaratish ishlamaydi. --kategoriya=<id> bilan qayta urinib ko'ring.");
    } else {
        role_assign($ops->id, $user->id, context_coursecat::instance($cat->id)->id);
        cli_writeln("  + " . TZK_OPS_ROL . " ({$cat->name} kategoriyasi)");
    }
    cli_writeln("Rol berildi: " . fullname($user) . " ({$user->idnumber})");
}

// ----------------------------------------------------------------- TEKSHIR
// "Access denied" muammosi ruxsatdanmi yoki sahifa marshrutidanmi — shuni
// aniqlash uchun. Ruxsatlar bor, lekin sahifa ochilmasa, muammo sahifada.
if ($opt['tekshir'] !== '') {
    $user = $DB->get_record('user', ['idnumber' => $opt['tekshir'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['tekshir']}");
    }
    cli_writeln(fullname($user) . " ({$user->idnumber})");
    cli_writeln('');

    $rollar = get_user_roles($syscontext, $user->id, false);
    cli_writeln('Tizim kontekstidagi rollar: '
        . ($rollar ? implode(', ', array_map(fn($r) => $r->shortname, $rollar)) : "YO'Q"));

    $cat = tzk_kategoriya($opt['kategoriya']);
    if ($cat) {
        $catcontext = context_coursecat::instance($cat->id);
        $catrollar = get_user_roles($catcontext, $user->id, false);
        cli_writeln("«{$cat->name}» kategoriyasidagi rollar: "
            . ($catrollar
                ? implode(', ', array_map(fn($r) => $r->shortname, $catrollar))
                : "YO'Q"));
    }
    cli_writeln('');

    cli_writeln('--- TIZIM konteksti');
    foreach (TZK_RUXSATLAR as $cap) {
        cli_writeln('  ' . (has_capability($cap, $syscontext, $user) ? '[bor]    ' : "[yo'q]   ") . $cap);
    }

    if ($cat) {
        cli_writeln('');
        cli_writeln("--- «{$cat->name}» KATEGORIYASI konteksti");
        foreach (TZK_OPS_RUXSATLAR as $cap) {
            cli_writeln('  ' . (has_capability($cap, $catcontext, $user) ? '[bor]    ' : "[yo'q]   ") . $cap);
        }

        // Haqiqiy tekshiruv: kategoriya ichidagi BITTA kursda ishlaydimi.
        // Kategoriya ruxsati pastga meros bo'lishi kerak; bo'lmasa imtihon
        // yaratish jim yiqiladi va sababi ko'rinmaydi.
        //
        // DIQQAT: kurslar `<yil>` ning BEVOSITA ichida EMAS — ular
        // `<yil>` -> `<guruh>` ko'rinishida bir qavat pastda
        // (`moodle_kurs.py`). Faqat `category = <yil>` deb qidirgan edim va
        // tekshiruv JIM o'tkazib yuborilardi — aynan shu blok eng muhim
        // tekshiruv bo'lgani uchun xato qimmat edi. Shuning uchun butun
        // DARAXT bo'ylab qidiriladi (`path` bo'yicha).
        $kurs = $DB->get_record_sql(
            "SELECT c.id, c.fullname, cc.name AS katnomi
               FROM {course} c
               JOIN {course_categories} cc ON cc.id = c.category
              WHERE cc.id = :c OR " . $DB->sql_like('cc.path', ':p') . "
           ORDER BY c.id",
            ['c' => $cat->id, 'p' => $DB->sql_like_escape($cat->path) . '/%'],
            IGNORE_MULTIPLE);
        if (!$kurs) {
            cli_writeln('');
            cli_writeln("! Kategoriya daraxtida birorta kurs topilmadi — meros");
            cli_writeln("  tekshirilmadi. Kurslar sinxroni (`moodle_kurs.py`) o'tganmi?");
        }
        if ($kurs) {
            $kc = context_course::instance($kurs->id);
            cli_writeln('');
            cli_writeln("--- Namuna KURS: " . format_string($kurs->fullname)
                . ' («' . format_string($kurs->katnomi) . '» kategoriyasida)');
            foreach (['moodle/course:manageactivities', 'mod/quiz:addinstance',
                      'moodle/question:add', 'local/targetzenit:gradeexam'] as $cap) {
                cli_writeln('  ' . (has_capability($cap, $kc, $user) ? '[bor]    ' : "[yo'q]   ") . $cap);
            }
        }
    }

    cli_writeln('');
    cli_writeln("`teach`, `grade:edit` va `site:config` YO'Q bo'lishi TO'G'RI:");
    cli_writeln("koordinator dars o'tmaydi, kundalik baho qo'ymaydi va saytni");
    cli_writeln("sozlamaydi. Summativ ballni `manageexams` bilan kiritadi.");
    foreach (['local/targetzenit:teach', 'moodle/grade:edit', 'moodle/site:config',
              'local/targetzenit:viewallpayroll'] as $cap) {
        cli_writeln('  ' . (has_capability($cap, $syscontext, $user) ? '[BOR!]   ' : "[yo'q]   ") . $cap);
    }
}

// ------------------------------------------------------------------- BERGAN
if ($opt['bergan']) {
    foreach ([TZK_ROL, TZK_OPS_ROL] as $shortname) {
        $rol = $DB->get_record('role', ['shortname' => $shortname]);
        cli_writeln("--- {$shortname}");
        if (!$rol) {
            cli_writeln("  rol hali yaratilmagan");
            continue;
        }
        // Kontekst ataylab cheklanmaydi: rol qayerga biriktirilgani ham
        // ko'rinishi kerak (tizim yoki kategoriya).
        $rows = $DB->get_records_sql("
            SELECT ra.id, u.firstname, u.lastname, u.idnumber, u.email,
                   ctx.contextlevel, ctx.instanceid
              FROM {role_assignments} ra
              JOIN {user} u ON u.id = ra.userid
              JOIN {context} ctx ON ctx.id = ra.contextid
             WHERE ra.roleid = :rid AND u.deleted = 0
          ORDER BY u.lastname", ['rid' => $rol->id]);

        if (!$rows) {
            cli_writeln("  rol hech kimga berilmagan");
        }
        foreach ($rows as $u) {
            $joy = ((int) $u->contextlevel === CONTEXT_SYSTEM)
                ? 'tizim'
                : 'kategoriya#' . $u->instanceid;
            cli_writeln('  ' . str_pad(fullname($u), 32)
                . str_pad((string) $u->idnumber, 18) . str_pad($joy, 16) . $u->email);
        }
    }
}
