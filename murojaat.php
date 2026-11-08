<?php
// OTA-ONA MUROJAATLARI — rahbar sahifasi (CEO / direktor / zavuch).
//
// Murojaat sotuv bo'limida tug'iladi (Frappe), shu yerda HAL QILINADI va
// javob ota-onaga bolasining ilovasi orqali boradi —
// MUROJAAT_ARXITEKTURA.md.
//
// NEGA BITTA SAHIFA: ro'yxat va karta ikki sahifaga bo'linsa rahbar har
// murojaat uchun ikki marta bosadi va orqaga qaytadi. Shu yerda har murojaat
// yig'iladigan blok (<details>) ichida to'liq ko'rinadi — izoh va qaror
// o'sha joyda yoziladi. Mobil ilova shabloni ham AYNI shu tuzilishda, ya'ni
// ikki ko'rinish bir-biriga mos tushadi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\murojaat;
use local_targetzenit\output\shell;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/murojaat.php'));
$yonalishlar = murojaat::yonalishlar($context);
if (!$yonalishlar) {
    // DIQQAT: bu yerda `shell::setup()` ATAYLAB chaqirilmaydi. U sahifani
    // `embedded` maketga o'tkazadi — Moodle navigatsiyasi butunlay
    // chizilmaydi va ruxsati yo'q odam xabarni o'qib, hech qayerga
    // o'tolmay qoladi. Oddiy maketda esa menyu joyida turadi.
    $PAGE->set_title(get_string('mur_panel', 'local_targetzenit'));
    $PAGE->set_heading(get_string('mur_panel', 'local_targetzenit'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('mur_noaccess', 'local_targetzenit'), 'error');
    echo $OUTPUT->footer();
    exit;
}

shell::setup($PAGE, get_string('mur_panel', 'local_targetzenit'));

$holat = optional_param('holat', 'ochiq', PARAM_ALPHANUMEXT);
$qidiruv = trim(optional_param('q', '', PARAM_TEXT));
$ochiq = optional_param('ochiq', 0, PARAM_INT);      // qaysi blok ochiq turadi
// Frappe panelidagi bilan bir xil filtr to'plami
$fturi = optional_param('turi', '', PARAM_TEXT);
$fkat = optional_param('kat', '', PARAM_TEXT);
$fmasul = optional_param('masul', '', PARAM_TEXT);
$filtr = ['turi' => $fturi, 'kategoriya' => $fkat, 'masul' => $fmasul];
// Filtrlar amallardan keyin ham saqlanib qolsin (redirect shu ro'yxat bilan)
$holatlar = ['holat' => $holat, 'q' => $qidiruv, 'turi' => $fturi,
             'kat' => $fkat, 'masul' => $fmasul];

// --------------------------------------------------------------- AMALLAR
// Hammasi POST + sesskey: GET bilan yozish brauzer prefetch'ida ham
// ishlab ketardi.
$amal = optional_param('amal', '', PARAM_ALPHA);
if ($amal !== '' && confirm_sesskey()) {
    $id = required_param('id', PARAM_INT);
    $m = murojaat::get($id);
    if (!$m) {
        throw new moodle_exception('invalidrecord', 'error');
    }
    murojaat::tekshir($m);

    $qayt = new moodle_url('/local/targetzenit/murojaat.php',
        $holatlar + ['ochiq' => $id]);

    switch ($amal) {
        case 'izoh':
            murojaat::izoh_qosh($m, required_param('matn', PARAM_TEXT));
            break;
        case 'qaror':
            murojaat::qaror($m, required_param('qarorturi', PARAM_TEXT),
                required_param('matn', PARAM_TEXT));
            break;
        case 'olish':
            murojaat::masul_ol($m);
            break;
        case 'uzat':
            murojaat::uzat($m, required_param('yonalish', PARAM_TEXT),
                optional_param('matn', '', PARAM_TEXT));
            break;
    }
    redirect($qayt, get_string('mur_saved', 'local_targetzenit'), 0,
        \core\output\notification::NOTIFY_SUCCESS);
}

$stats = murojaat::stats();
$rows = murojaat::royxat($holat, $qidiruv, 300, $filtr);
$kesim = murojaat::kesim();

// ---------------------------------------------------------------- CHIZISH
echo $OUTPUT->header();

$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);
$u = fn(array $p = []) => new moodle_url('/local/targetzenit/murojaat.php', $p);

// Qobiq: yon panel + sarlavha. U bo'lmasa `embedded` maketda sahifada
// orqaga qaytish yo'li qolmaydi — 2026-11-08 da aynan shu bo'ldi.
shell::start_menu('murojaat', $t('mur_panel'), implode(' · ', $yonalishlar),
    '', shell::mur_menu());

echo html_writer::start_div('tz-mur');

// ---- plitalar
// DIQQAT: `tz-dir-tile` / `tz-dir-two` ISHLATILMAYDI — ular direktor
// panelidagi IKKI katta karta uchun (katta shrift, keng padding). Bu yerda
// 7 ta kichik ko'rsatkich kerak, shuning uchun o'z klasslari.
$plita = function (string $lab, $val, string $cls = '') {
    return html_writer::div(
        html_writer::div($lab, 'tz-mur-lab') . html_writer::div($val, 'tz-mur-num'),
        'tz-mur-tile ' . $cls);
};
echo html_writer::div(
    $plita($t('mur_k_open'), $stats['ochiq'])
    . $plita($t('mur_k_unseen'), $stats['korilmagan'],
        $stats['korilmagan'] ? 'tz-mur-warn' : '')
    . $plita($t('mur_k_overdue'), $stats['muddati'],
        $stats['muddati'] ? 'tz-mur-bad' : '')
    . $plita($t('mur_k_unassigned'), $stats['biriktirilmagan'],
        $stats['biriktirilmagan'] ? 'tz-mur-warn' : '')
    . $plita($t('mur_k_mine'), $stats['menda'])
    . $plita($t('mur_k_closedmonth'), $stats['oyyopildi'], 'tz-mur-ok')
    . $plita($t('mur_k_avgdays'), $stats['ortkun'] . ' ' . $t('mur_days')),
    'tz-mur-tiles');

// ---- kategoriya kesimi: bitta murojaatni yopish emas, SABABNI yo'q qilish
if ($kesim) {
    $eng = max(array_map(fn($k) => (int) $k->soni, $kesim)) ?: 1;
    $qatorlar = '';
    foreach ($kesim as $k) {
        // Kategoriya bosilsa ro'yxat o'sha kategoriyaga filtrlanadi — rahbar
        // "bu masala necha marta takrorlangan" ni bir bosishda ko'radi
        $qatorlar .= html_writer::link(
            $u(['holat' => '', 'kat' => $k->nomi]),
            html_writer::div(s($k->nomi), 'tz-mur-kesnom')
            . html_writer::div(html_writer::tag('i', '',
                ['style' => 'width:' . round($k->soni / $eng * 100) . '%']), 'tz-mur-kesbar')
            . html_writer::div((int) $k->soni . ((int) $k->ochiq
                ? ' <span class="tz-dir-sub">(' . (int) $k->ochiq . ')</span>' : ''),
                'tz-mur-kesson'),
            ['class' => 'tz-mur-kesrow']);
    }
    echo html_writer::div(
        html_writer::div($t('mur_repeat'), 'tz-dir-lab') . $qatorlar,
        'tz-mur-kesim');
}

// ---- filtr chiplari
$chiplar = ['ochiq' => 'mur_f_open', 'Yangi' => 'mur_f_new', 'muddati' => 'mur_f_overdue',
            'menda' => 'mur_f_mine', 'Yopildi' => 'mur_f_closed', '' => 'mur_f_all'];
$chip = '';
foreach ($chiplar as $kod => $kalit) {
    $chip .= html_writer::link($u($holatlar + ['holat' => $kod]), $t($kalit),
        ['class' => 'tz-dir-tab' . ($holat === $kod ? ' tz-on' : '')]);
}
echo html_writer::div($chip, 'tz-dir-tabs');

// ---- filtrlar va qidiruv (bitta forma — hammasi birga yuboriladi)
$tanlagich = function (string $nom, string $joriy, array $variantlar, string $bosh) {
    $opt = html_writer::tag('option', $bosh, ['value' => '']);
    foreach ($variantlar as $qiymat => $yorliq) {
        $opt .= html_writer::tag('option', s($yorliq), array_merge(
            ['value' => $qiymat], (string) $qiymat === $joriy ? ['selected' => 'selected'] : []));
    }
    return html_writer::tag('select', $opt, ['name' => $nom]);
};

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-mur-filtr']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'holat', 'value' => $holat]);
echo $tanlagich('turi', $fturi,
    array_combine(murojaat::turlar(), murojaat::turlar()), $t('mur_f_alltypes'));
$katlar = murojaat::kategoriyalar();
echo $tanlagich('kat', $fkat, array_combine($katlar, $katlar), $t('mur_f_allcats'));
$masullar = [];
foreach (murojaat::masullar() as $ms) {
    $masullar[$ms['id']] = $ms['nomi'] . ' (' . $ms['soni'] . ')';
}
echo $tanlagich('masul', $fmasul, $masullar, $t('mur_f_allowners'));
echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q',
    'value' => $qidiruv, 'placeholder' => $t('mur_search')]);
echo html_writer::tag('button', $t('mur_apply'), ['type' => 'submit', 'class' => 'tz-mur-btn']);
if ($fturi || $fkat || $fmasul || $qidiruv) {
    echo html_writer::link($u(['holat' => $holat]), $t('mur_clear'),
        ['class' => 'tz-mur-btn tz-mur-btn2']);
}
echo html_writer::end_tag('form');

// ---- ro'yxat
if (!$rows) {
    echo html_writer::div($t('mur_empty'), 'tz-dir-none');
} else {
    // TEZLIK: izohlar, suhbatlar, boshqa murojaatlar va ismlar HAMMASI
    // oldindan, bitta-bittadan so'rov bilan olinadi. Avval har blok ichida
    // alohida o'qilardi — 15 qatorli ro'yxat 100 dan ortiq so'rov berardi.
    $idlar = array_map(fn($x) => (int) $x->id, $rows);
    $oldindan = [
        'izohlar' => murojaat::izohlar_koplab($idlar),
        'suhbatlar' => murojaat::suhbatlar_koplab($idlar),
        'boshqalari' => murojaat::boshqalari_koplab($rows),
        'ismlar' => murojaat::ismlar(array_merge(
            array_map(fn($x) => (int) $x->masuluserid, $rows),
            array_map(fn($x) => (int) $x->korildikim, $rows),
            array_map(fn($x) => (int) $x->qarorkim, $rows)
        )),
    ];
    foreach ($rows as $m) {
        echo tz_murojaat_blok($m, $ochiq, $holatlar, $oldindan);
    }
}

echo html_writer::end_div();
shell::end();
echo $OUTPUT->footer();

/**
 * Bitta murojaat bloki: sarlavha + to'liq tafsilot + izoh/qaror formasi.
 *
 * Tuzilish Frappe panelidagi karta bilan bir xil ketma-ketlikda: ota-ona
 * gapi -> kim murojaat qildi (qarz holati bilan) -> rahbar qarori ->
 * izohlar -> sotuv bilan aloqa tarixi -> shu o'quvchining boshqa
 * murojaatlari. Ikki tizimda bir xil tartib bo'lishi muhim: rahbar
 * kompyuterda ham, ilovada ham bir xil joydan qaraydi.
 *
 * Alohida funksiya (klass emas): bu CHIZISH, ma'lumot mantig'i emas.
 */
function tz_murojaat_blok(stdClass $m, int $ochiq, array $holatlar,
                           array $oldindan = []): string {
    global $USER;

    // Ism oldindan o'qilgan ro'yxatdan; bo'lmasa (yakka chaqiruv) bazadan
    $ism = function (int $userid) use ($oldindan): string {
        if (!$userid) {
            return '—';
        }
        $u = $oldindan['ismlar'][$userid] ?? null;
        return $u ? fullname($u) : murojaat::ism($userid);
    };

    $t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);
    $yopiq = $m->holat === 'Yopildi';
    [$mkod, $mkun] = murojaat::muddat($m);
    $qisqa = fn($v) => userdate($v, get_string('strftimedatetimeshort'));

    // --- sarlavha yorliqlari
    //
    // DIZAYN QOIDASI: qatorda 3-4 tadan ortiq yorliq bo'lsa ko'z ularni
    // o'qimay, shunchaki «rangli shovqin» deb o'tkazib yuboradi. Shuning
    // uchun: tur + yo'nalish + holat doimiy, shoshilinchlikdan esa FAQAT
    // BITTASI — eng muhimi (kechikish ko'rilmaganlikdan ustun, chunki
    // kechikkan murojaat allaqachon ko'rilgan bo'lishi ham mumkin).
    // Qolgan signal chap chekkadagi rangli chiziqda.
    $pill = fn(string $matn, string $cls = '') => html_writer::tag('span', s($matn),
        ['class' => 'tz-mur-pill' . ($cls ? ' ' . $cls : '')]);

    $chips = $pill($m->turi) . $pill($m->yonalish);
    $chips .= $pill($m->holat, $yopiq ? 'tz-mur-p-ok' : '');

    if ($mkod === 'kechikdi') {
        $chips .= $pill($t('mur_overdue', $mkun), 'tz-mur-p-bad');
    } else if (!$m->korildivaqti) {
        $chips .= $pill($t('mur_notseen'), 'tz-mur-p-warn');
    } else if ($mkod === 'bugun') {
        $chips .= $pill($t('mur_duetoday'), 'tz-mur-p-warn');
    }
    if ($m->muhimlik === 'Yuqori' && !$yopiq) {
        $chips .= $pill($m->muhimlik, 'tz-mur-p-bad');
    }
    // Mas'ul — nazoratning asosiy savoli, lekin bo'g'iq ko'rinishda
    $chips .= $pill(
        $m->masuluserid ? $ism((int) $m->masuluserid) : $t('mur_noowner'),
        $m->masuluserid ? '' : 'tz-mur-p-warn');

    $sarlavha = html_writer::tag('summary',
        html_writer::div(s($m->studentname ?: $m->payername ?: '—')
            . ($m->sinf ? html_writer::tag('span', ' · ' . s($m->sinf), ['class' => 'tz-dir-sub']) : ''),
            'tz-mur-ism')
        . html_writer::div(s(shorten_text($m->matn, 160)), 'tz-mur-qisqa')
        . html_writer::div($chips, 'tz-mur-pills'),
        ['class' => 'tz-mur-sum']);

    // --- ota-ona gapi
    $out = html_writer::div(
        html_writer::div($t('mur_said'), 'tz-dir-lab')
        . html_writer::div(format_text($m->matn, FORMAT_PLAIN), 'tz-mur-quote'),
        'tz-mur-sec');

    // --- kim murojaat qildi (+ qarz konteksti)
    $meta = [
        $t('mur_who') => s($m->payername ?: '—') . ($m->payerphone ? ' · ' . s($m->payerphone) : ''),
        $t('mur_manager') => s($m->managername ?: '—'),
        $t('mur_talked') => $m->suhbatvaqti ? $qisqa($m->suhbatvaqti) : '—',
        $t('mur_cat') => s($m->kategoriya ?: $t('mur_nocat')),
        $t('mur_source') => s($m->manba ?: '—'),
        $t('mur_owner') => $m->masuluserid ? s($ism((int) $m->masuluserid)) : $t('mur_noowner'),
    ];
    // Qarz — rahbar qaror qabul qilishdan oldin ko'rishi kerak: chegirma yoki
    // bo'lib to'lash haqidagi qaror oilaning qarzi bilan bog'liq
    if ($m->qarzsumma > 0) {
        $meta[$t('mur_debt')] = html_writer::tag('b', number_format((float) $m->qarzsumma, 0, '.', ' '),
            ['class' => 'tz-dir-t-neg'])
            . ($m->qarzkechikish ? ' <span class="tz-dir-sub">' . s($m->qarzkechikish) . '</span>' : '')
            . ($m->qarzholat ? '<div class="tz-dir-sub">' . s($m->qarzholat) . '</div>' : '');
    } else {
        $meta[$t('mur_debt')] = html_writer::tag('span', $t('mur_nodebt'), ['class' => 'tz-dir-t-pos']);
    }
    if ($m->korildivaqti) {
        $meta[$t('mur_seen')] = s($ism((int) $m->korildikim)) . ' · ' . $qisqa($m->korildivaqti);
    }
    $metahtml = '';
    foreach ($meta as $lab => $val) {
        $metahtml .= html_writer::div(
            html_writer::tag('span', $lab, ['class' => 'tz-dir-lab']) . $val, 'tz-mur-metaitem');
    }
    $out .= html_writer::div($metahtml, 'tz-mur-meta');

    // O'quvchining Moodle akkaunti yo'q bo'lsa javob ilovaga YETMAYDI —
    // buni rahbar qaror yozishdan OLDIN bilishi kerak.
    if (!$m->studentuserid) {
        $out .= html_writer::div($t('mur_noaccount'), 'tz-mur-ogoh');
    }

    // --- qaror yoki qaror formasi
    if ($m->qarorturi) {
        $javob = html_writer::div($t('mur_answer') . ': ' . s($m->qarorturi), 'tz-dir-lab')
            . html_writer::div(format_text((string) $m->qaror, FORMAT_PLAIN), 'tz-mur-yechim')
            . html_writer::div(s($ism((int) $m->qarorkim)) . ' · '
                . $qisqa($m->qarorvaqti), 'tz-dir-sub')
            . html_writer::div($m->otaonakordi
                ? $t('mur_parentseen') . ' · ' . $qisqa($m->otaonakordi)
                : $t('mur_parentnotseen'),
                'tz-dir-sub ' . ($m->otaonakordi ? 'tz-dir-t-pos' : 'tz-dir-t-neg'));
        $out .= html_writer::div($javob, 'tz-mur-sec');
    } else {
        $out .= tz_murojaat_qaror_form($m, $holatlar);
    }

    // --- izohlar
    $ihtml = '';
    foreach (($oldindan['izohlar'][(int) $m->id] ?? murojaat::izohlar((int) $m->id)) as $i) {
        $ihtml .= html_writer::div(
            html_writer::div(s($i->ism) . ' · ' . $qisqa($i->timecreated)
                . ' · ' . s($i->turi), 'tz-dir-sub')
            . html_writer::div(format_text($i->body, FORMAT_PLAIN), 'tz-fb-text'),
            'tz-fb-item tz-fb-' . ($i->turi === 'qaror' ? 'rahmat' : 'tavsiya'));
    }
    $out .= html_writer::div(
        html_writer::div($t('mur_comments'), 'tz-dir-lab') . $ihtml
        . tz_murojaat_izoh_form($m, $holatlar),
        'tz-mur-sec');

    // --- sotuv bilan aloqa tarixi: "kim qachon gaplashdi"
    $out .= tz_murojaat_suhbatlar($m, $oldindan['suhbatlar'][(int) $m->id] ?? null);

    // --- shu o'quvchining boshqa murojaatlari
    $out .= tz_murojaat_boshqalari($m, $holatlar,
        $oldindan['boshqalari'][(int) $m->id] ?? null);

    // --- qo'shimcha amallar
    if (!$yopiq) {
        $amallar = '';
        if ((int) $m->masuluserid !== (int) $USER->id) {
            $amallar .= tz_murojaat_tugma($m, 'olish', $t('mur_takeit'), $holatlar);
        }
        // Uzatish HAR DOIM ochiq: masala boshqa rahbarga tegishli ekani
        // o'qib ko'rgandan keyin ma'lum bo'ladi, ya'ni AI noto'g'ri
        // yo'naltirgan holatni faqat shu tugma tuzatadi.
        $boshqa = $m->yonalish === 'CEO' ? 'Direktor' : 'CEO';
        $amallar .= tz_murojaat_tugma($m, 'uzat', $t('mur_forwardto', $boshqa),
            $holatlar, ['yonalish' => $boshqa]);
        $out .= html_writer::div($amallar, 'tz-dir-actions');
    }

    // Chap chiziq — shoshilinchlik: kechikkan qizil, ko'rilmagan sariq,
    // yopilgan yashil. Rangni butun kartaga yoyish ro'yxatni «hammasi
    // shoshilinch» qilib ko'rsatardi.
    $chiziq = $yopiq ? ' tz-mur-b-ok'
        : ($mkod === 'kechikdi' ? ' tz-mur-b-bad'
            : (!$m->korildivaqti ? ' tz-mur-b-warn' : ''));

    return html_writer::tag('details', $sarlavha . $out,
        ['class' => 'tz-mur-blok' . $chiziq]
        + ((int) $ochiq === (int) $m->id ? ['open' => 'open'] : []));
}

/** Sotuv bo'limi bilan aloqa tarixi (Frappe'dan ko'chirilgan nusxa). */
function tz_murojaat_suhbatlar(stdClass $m, ?array $oldindan = null): string {
    $t = fn(string $k) => get_string($k, 'local_targetzenit');
    $rows = $oldindan ?? murojaat::suhbatlar((int) $m->id);
    if (!$rows) {
        return '';
    }
    $qatorlar = '';
    foreach ($rows as $r) {
        $qatorlar .= html_writer::tag('tr',
            html_writer::tag('td', $r->vaqt
                ? userdate($r->vaqt, get_string('strftimedatetimeshort')) : '—')
            . html_writer::tag('td', s($r->menejer ?: '—'))
            . html_writer::tag('td', s($r->kanal ?: '') . ' · ' . s($r->natija ?: '')
                . ($r->suhbatnatija ? ' → <b>' . s($r->suhbatnatija) . '</b>' : ''))
            . html_writer::tag('td', s($r->izoh ?: '')));
    }
    return html_writer::div(
        html_writer::div($t('mur_salestalks'), 'tz-dir-lab')
        . html_writer::div($t('mur_salestalkshint'), 'tz-dir-sub')
        . html_writer::div(html_writer::tag('table',
            html_writer::tag('thead', html_writer::tag('tr',
                html_writer::tag('th', $t('mur_time'))
                . html_writer::tag('th', $t('mur_manager'))
                . html_writer::tag('th', $t('mur_result'))
                . html_writer::tag('th', $t('mur_comment'))))
            . html_writer::tag('tbody', $qatorlar),
            ['class' => 'tz-dir-table']), 'tz-dir-tablewrap'),
        'tz-mur-sec');
}

/** Shu o'quvchining boshqa murojaatlari — takrorlanayotgan masalami? */
function tz_murojaat_boshqalari(stdClass $m, array $holatlar,
                                 ?array $oldindan = null): string {
    $t = fn(string $k) => get_string($k, 'local_targetzenit');
    $rows = $oldindan ?? murojaat::boshqalari($m);
    if (!$rows) {
        return '';
    }
    $ichi = '';
    foreach ($rows as $r) {
        $ichi .= html_writer::link(
            new moodle_url('/local/targetzenit/murojaat.php',
                array_merge($holatlar, ['holat' => '', 'ochiq' => $r->id])),
            html_writer::tag('span', userdate($r->ochilgan, get_string('strftimedateshort')),
                ['class' => 'tz-dir-sub'])
            . html_writer::tag('span', s($r->holat), ['class' => 'tz-mur-pill'])
            . html_writer::tag('span', s($r->kategoriya ?: $r->turi))
            . html_writer::tag('span', s(shorten_text($r->matn, 70)), ['class' => 'tz-dir-sub'])
            . ($r->qarorturi
                ? html_writer::tag('span', s($r->qarorturi), ['class' => 'tz-mur-pill tz-mur-p-ok'])
                : ''),
            ['class' => 'tz-mur-boshqa']);
    }
    return html_writer::div(
        html_writer::div($t('mur_others'), 'tz-dir-lab')
        . html_writer::div($t('mur_othershint'), 'tz-dir-sub') . $ichi,
        'tz-mur-sec');
}

/** Qaror formasi — ota-ona SHU matnni o'qiydi, shuning uchun eslatma bor. */
function tz_murojaat_qaror_form(stdClass $m, array $holatlar): string {
    $t = fn(string $k) => get_string($k, 'local_targetzenit');
    $opts = '';
    foreach (murojaat::QARORLAR as $q) {
        $opts .= html_writer::tag('option', s($q), ['value' => s($q)]);
    }
    $form = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-form']);
    $form .= tz_murojaat_hidden($m, 'qaror', $holatlar);
    $form .= html_writer::div($t('mur_decisionhint'), 'tz-dir-sub');
    $form .= html_writer::tag('select', $opts, ['name' => 'qarorturi', 'class' => 'tz-dir-chip']);
    $form .= html_writer::tag('textarea', '', ['name' => 'matn', 'rows' => 3,
        'class' => 'tz-fb-ctext', 'required' => 'required',
        'placeholder' => $t('mur_decision')]);
    $form .= html_writer::div($t('mur_irrelevanthint'), 'tz-dir-sub');
    $form .= html_writer::tag('button', $t('mur_decide'),
        ['type' => 'submit', 'class' => 'tz-fb-csend']);
    $form .= html_writer::end_tag('form');
    return html_writer::div(
        html_writer::div($t('mur_decide'), 'tz-dir-lab') . $form, 'tz-mur-sec');
}

function tz_murojaat_izoh_form(stdClass $m, array $holatlar): string {
    $t = fn(string $k) => get_string($k, 'local_targetzenit');
    $form = html_writer::start_tag('form', ['method' => 'post', 'class' => 'tz-fb-cform']);
    $form .= tz_murojaat_hidden($m, 'izoh', $holatlar);
    $form .= html_writer::tag('textarea', '', ['name' => 'matn', 'rows' => 2,
        'class' => 'tz-fb-ctext', 'required' => 'required',
        'placeholder' => $t('mur_commenthint')]);
    $form .= html_writer::tag('button', $t('mur_addcomment'),
        ['type' => 'submit', 'class' => 'tz-fb-csend']);
    $form .= html_writer::end_tag('form');
    return $form;
}

function tz_murojaat_tugma(stdClass $m, string $amal, string $lab, array $holatlar,
                            array $extra = []): string {
    $form = html_writer::start_tag('form', ['method' => 'post', 'style' => 'display:inline']);
    $form .= tz_murojaat_hidden($m, $amal, $holatlar);
    foreach ($extra as $k => $v) {
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
    }
    $form .= html_writer::tag('button', $lab, ['type' => 'submit', 'class' => 'tz-dir-chip']);
    $form .= html_writer::end_tag('form');
    return $form;
}

/** Amaldan keyin ro'yxat AYNAN o'sha filtrlar bilan qaytishi uchun. */
function tz_murojaat_hidden(stdClass $m, string $amal, array $holatlar): string {
    $h = '';
    $maydonlar = array_merge($holatlar,
        ['sesskey' => sesskey(), 'amal' => $amal, 'id' => $m->id]);
    foreach ($maydonlar as $k => $v) {
        $h .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => $k, 'value' => $v]);
    }
    return $h;
}
