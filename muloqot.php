<?php
// MULOQOT — ota-ona va hamkasblar bilan yozishmalar (maket «Muloqot»).
// 2 panel: chap — chatlar ro'yxati, o'ng — tanlangan suhbat. Guruh chatlarini
// o'qituvchi ochadi va boshqaradi. Xabarlar Moodle message conversations'da.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\chat;
use local_targetzenit\journal;
use local_targetzenit\output\maket;
use local_targetzenit\timetable;

require_login();
$context = context_system::instance();
$year = timetable::current_year();
$convid = optional_param('conv', 0, PARAM_INT);

$mening = journal::my_courses((int) $USER->id, $year);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/muloqot.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('chat_nav', 'local_targetzenit'));
$PAGE->requires->css('/local/targetzenit/styles.css');

// ---------------------------------------------------- YANGI GURUH CHAT
if (optional_param('yangi', 0, PARAM_INT) && confirm_sesskey()) {
    $courseid = optional_param('ncourseid', 0, PARAM_INT);
    $nom = trim(optional_param('nnom', '', PARAM_TEXT));
    if (isset($mening[$courseid])) {
        if ($nom === '') {
            $nom = format_string((string) $mening[$courseid]) . ' — '
                . get_string('chat_parents', 'local_targetzenit');
        }
        $cid = chat::guruh_yarat($nom, [], $courseid, 'sinf_otaona');
        $ch = $DB->get_record('local_targetzenit_chat', ['id' => $cid]);
        if ($ch) {
            chat::sinf_sinxron($ch);
            redirect(new moodle_url('/local/targetzenit/muloqot.php',
                ['conv' => (int) $ch->conversationid]),
                get_string('chat_created', 'local_targetzenit'));
        }
    }
    redirect($PAGE->url, get_string('chat_err_group', 'local_targetzenit'), null,
        \core\output\notification::NOTIFY_ERROR);
}

// ---------------------------------------------------- SHAXSIY CHAT BOSHLASH
if (optional_param('shaxsiy', 0, PARAM_INT) && confirm_sesskey()) {
    $otherid = optional_param('otherid', 0, PARAM_INT);
    if ($otherid) {
        $cv = chat::shaxsiy($otherid);
        if ($cv) {
            redirect(new moodle_url('/local/targetzenit/muloqot.php', ['conv' => $cv]));
        }
    }
    redirect($PAGE->url);
}

// ---------------------------------------------------- XABAR YUBORISH
if (optional_param('yubor', 0, PARAM_INT) && confirm_sesskey() && $convid) {
    $matn = optional_param('matn', '', PARAM_TEXT);
    $jonatildi = true;
    if (chat::azomi((int) $USER->id, $convid)) {
        $jonatildi = chat::yubor((int) $USER->id, $convid, $matn);
    }
    if ($jonatildi) {
        redirect(new moodle_url('/local/targetzenit/muloqot.php', ['conv' => $convid]));
    }
    redirect(new moodle_url('/local/targetzenit/muloqot.php', ['conv' => $convid]),
        get_string('chat_err_send', 'local_targetzenit'), null,
        \core\output\notification::NOTIFY_ERROR);
}

// --------------------- GURUH BOSHQARUVI (faqat ega): a'zo / siyosat / uchrashuv
$qaytar = new moodle_url('/local/targetzenit/muloqot.php', ['conv' => $convid]);

// A'zo qo'shish
if (optional_param('azoqosh', 0, PARAM_INT) && confirm_sesskey() && $convid
        && chat::ega_mi($convid, (int) $USER->id)) {
    $qoshid = optional_param('qoshid', 0, PARAM_INT);
    if ($qoshid) {
        chat::azo_qosh($convid, [$qoshid]);
    }
    redirect($qaytar);
}

// A'zo chiqarish
if (optional_param('azochiqar', 0, PARAM_INT) && confirm_sesskey() && $convid
        && chat::ega_mi($convid, (int) $USER->id)) {
    $chiqid = optional_param('chiqid', 0, PARAM_INT);
    if ($chiqid && $chiqid !== (int) $USER->id) {
        chat::azo_chiqar($convid, [$chiqid]);
    }
    redirect($qaytar);
}

// Yozish siyosati
if (optional_param('yozishsozla', 0, PARAM_INT) && confirm_sesskey() && $convid
        && chat::ega_mi($convid, (int) $USER->id)) {
    chat::yozish_sozla($convid, optional_param('yozish', 'hamma', PARAM_ALPHA));
    redirect($qaytar);
}

// Uchrashuv taklif qilish
if (optional_param('uchrashuv', 0, PARAM_INT) && confirm_sesskey() && $convid
        && chat::azomi((int) $USER->id, $convid)) {
    $sana = optional_param('usana', '', PARAM_TEXT);   // YYYY-MM-DDTHH:MM
    $joy = optional_param('ujoy', '', PARAM_TEXT);
    $izoh = optional_param('uizoh', '', PARAM_TEXT);
    $ts = $sana !== '' ? strtotime($sana) : 0;
    if ($ts) {
        chat::uchrashuv_yarat($convid, (int) $USER->id, $ts, $joy, $izoh);
    }
    redirect($qaytar);
}

// Uchrashuv holati (ega: tasdiq/bekor)
if (optional_param('uchrholat', 0, PARAM_INT) && confirm_sesskey() && $convid
        && chat::ega_mi($convid, (int) $USER->id)) {
    chat::uchrashuv_holat(optional_param('uchrholat', 0, PARAM_INT),
        optional_param('holat', '', PARAM_ALPHA));
    redirect($qaytar);
}

$chatlar = chat::mening_chatlarim((int) $USER->id);

// Suhbat nomi: guruhda — o'z nomi; shaxsiyda — boshqa a'zoning ismi.
// Yadro get_conversations a'zolarni ->id va ->fullname (satr) bilan beradi
// (firstname/lastname YO'Q), shuning uchun to'g'ridan-to'g'ri ->fullname olamiz.
$suhbatnomi = function($c) {
    global $USER;
    $nom = trim((string) ($c->name ?? ''));
    if ($nom === '' && !empty($c->members)) {
        foreach ($c->members as $m) {
            if ((int) $m->id !== (int) $USER->id && trim((string) ($m->fullname ?? '')) !== '') {
                return trim((string) $m->fullname);
            }
        }
        $first = reset($c->members);
        if ($first && trim((string) ($first->fullname ?? '')) !== '') {
            return trim((string) $first->fullname);
        }
    }
    return $nom !== '' ? $nom : get_string('chat_nav', 'local_targetzenit');
};

maket::boshla('muloqot');
echo html_writer::start_div('tz-ishstoli-wrap');

echo html_writer::tag('div', get_string('chat_nav', 'local_targetzenit'), ['class' => 'tzp-h1']);
echo html_writer::tag('div', get_string('chat_sub', 'local_targetzenit'), ['class' => 'tzp-sub']);

echo html_writer::start_div('tz-chatwrap');

// ================================================ CHAP: RO'YXAT
echo html_writer::start_div('tz-clist');

// Shaxsiy xabar boshlash (odam qidirib tanlash — filtrlanadigan ro'yxat)
$odamlar = chat::odamlar((int) $USER->id, $year);
if ($odamlar) {
    echo html_writer::start_div('tz-cnew-dm');
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false),
        'id' => 'tz-dm-form']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'shaxsiy', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'otherid', 'id' => 'tz-dm-id', 'value' => '0']);
    echo html_writer::end_tag('form');
    echo html_writer::empty_tag('input', ['type' => 'search', 'id' => 'tz-dm-qidir',
        'class' => 'form-control form-control-sm', 'autocomplete' => 'off',
        'placeholder' => '🔍 ' . get_string('chat_newperson', 'local_targetzenit')]);
    echo html_writer::start_div('tz-dm-results', ['id' => 'tz-dm-res', 'hidden' => 'hidden']);
    foreach ($odamlar as $uid => $nom) {
        echo html_writer::tag('button', s((string) $nom),
            ['type' => 'button', 'class' => 'tz-dm-opt', 'data-id' => (int) $uid]);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    $PAGE->requires->js_amd_inline("
        require([], function() {
            var q = document.getElementById('tz-dm-qidir');
            var res = document.getElementById('tz-dm-res');
            var idf = document.getElementById('tz-dm-id');
            var form = document.getElementById('tz-dm-form');
            if (!q || !res) { return; }
            var opts = res.querySelectorAll('.tz-dm-opt');
            q.addEventListener('input', function() {
                var v = q.value.trim().toLowerCase();
                if (v === '') { res.hidden = true; return; }
                res.hidden = false;
                opts.forEach(function(o) {
                    o.hidden = o.textContent.toLowerCase().indexOf(v) === -1;
                });
            });
            q.addEventListener('focus', function() {
                if (q.value.trim() !== '') { res.hidden = false; }
            });
            opts.forEach(function(o) {
                o.addEventListener('click', function() {
                    idf.value = o.getAttribute('data-id');
                    form.submit();
                });
            });
            document.addEventListener('click', function(e) {
                if (!res.contains(e.target) && e.target !== q) { res.hidden = true; }
            });
        });
    ");
}

// Yangi guruh chat (o'z sinflari)
if ($mening) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false),
        'class' => 'tz-cnew']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yangi', 'value' => 1]);
    echo html_writer::start_tag('select', ['name' => 'ncourseid', 'class' => 'form-control form-control-sm']);
    echo html_writer::tag('option', get_string('chat_newgroup', 'local_targetzenit'), ['value' => '0']);
    foreach ($mening as $cid => $nom) {
        echo html_writer::tag('option', format_string((string) $nom), ['value' => $cid]);
    }
    echo html_writer::end_tag('select');
    echo html_writer::tag('button', '+', ['type' => 'submit', 'class' => 'btn btn-sm btn-primary',
        'title' => get_string('chat_newgroup', 'local_targetzenit')]);
    echo html_writer::end_tag('form');
}

if (!$chatlar) {
    echo html_writer::div(get_string('chat_empty', 'local_targetzenit'), 'tzp-empty m-2');
} else {
    foreach ($chatlar as $c) {
        $nom = $suhbatnomi($c);
        $oxirgi = '';
        if (!empty($c->messages)) {
            $last = end($c->messages);
            $oxirgi = \core_text::substr(trim((string) ($last->text ?? '')), 0, 48);
            $oxirgi = strip_tags($oxirgi);
        }
        $unread = (int) ($c->unreadcount ?? 0);
        $faol = ((int) $c->id === $convid);
        echo html_writer::link(
            new moodle_url('/local/targetzenit/muloqot.php', ['conv' => (int) $c->id]),
            html_writer::tag('span', s(\core_text::substr($nom, 0, 2)), ['class' => 'tz-cava'])
            . html_writer::div(
                html_writer::tag('b', s($nom))
                . html_writer::tag('small', s($oxirgi)), 'tz-cmeta')
            . ($unread ? html_writer::tag('span', $unread, ['class' => 'tz-cunread']) : ''),
            ['class' => 'tz-citem' . ($faol ? ' tz-on' : '')]);
    }
}
echo html_writer::end_div();   // tz-clist

// ================================================ O'NG: SUHBAT OYNASI
echo html_writer::start_div('tz-cwin');
if (!$convid || !chat::azomi((int) $USER->id, $convid)) {
    echo html_writer::div(get_string('chat_pick', 'local_targetzenit'), 'tzp-empty m-3');
} else {
    $joriy = null;
    foreach ($chatlar as $c) {
        if ((int) $c->id === $convid) {
            $joriy = $c;
            break;
        }
    }
    $sarlavha = $joriy ? $suhbatnomi($joriy) : get_string('chat_nav', 'local_targetzenit');
    $achat = chat::by_conversation($convid);
    // _chat yozuvi faqat guruh chatlarida bor (shaxsiyda yo'q).
    $guruhmi = (bool) $achat;
    $egami = $guruhmi && chat::ega_mi($convid, (int) $USER->id);
    $yozaoladi = !($guruhmi && $achat->yozish === 'faqat_ega' && !$egami);

    echo html_writer::div(
        html_writer::tag('b', s($sarlavha))
        . ($achat && (int) $achat->courseid
            ? html_writer::tag('span',
                ' · ' . ($joriy->membercount ?? 0) . ' '
                . get_string('chat_members', 'local_targetzenit'),
                ['class' => 'text-muted small']) : ''),
        'tz-chead');

    echo html_writer::start_div('tz-cmsgs');
    foreach (chat::xabarlar((int) $USER->id, $convid) as $m) {
        $meniki = ((int) $m->useridfrom === (int) $USER->id);
        $vaqt = userdate((int) $m->timecreated, '%H:%M');
        $sndnom = '';
        if (!$meniki && $joriy && !empty($joriy->members)) {
            foreach ($joriy->members as $mem) {
                if ((int) $mem->id === (int) $m->useridfrom) {
                    $sndnom = trim((string) ($mem->fullname ?? ''));
                    break;
                }
            }
        }
        echo html_writer::div(
            ($sndnom !== '' ? html_writer::tag('span', s($sndnom), ['class' => 'tz-bub-snd']) : '')
            . s(strip_tags((string) $m->text))
            . html_writer::tag('span', $vaqt, ['class' => 'tz-bub-tm']),
            'tz-bub' . ($meniki ? ' tz-bub-me' : ''));
    }

    // Uchrashuv kartalari (chat ipiga bog'langan)
    $holatmap = [
        'kutilmoqda' => get_string('uchr_kutilmoqda', 'local_targetzenit'),
        'tasdiq' => get_string('uchr_tasdiq', 'local_targetzenit'),
        'bekor' => get_string('uchr_bekor', 'local_targetzenit'),
    ];
    foreach (chat::uchrashuvlar($convid) as $u) {
        $uvaqt = userdate((int) $u->sana, get_string('strftimedaydatetime', 'langconfig'));
        $ichi = html_writer::tag('b', '📅 ' . get_string('uchr_taklif', 'local_targetzenit'))
            . html_writer::div(s($uvaqt)
                . ((string) $u->joy !== '' ? ' · ' . s($u->joy) : ''))
            . ((string) $u->izoh !== ''
                ? html_writer::div(s($u->izoh), 'text-muted small') : '')
            . html_writer::div(get_string('uchr_holat', 'local_targetzenit') . ': '
                . s($holatmap[$u->holat] ?? $u->holat),
                'tz-meet-holat tz-meet-' . $u->holat);
        if ($egami && $u->holat === 'kutilmoqda') {
            $akt = '';
            foreach (['tasdiq', 'bekor'] as $h) {
                $akt .= html_writer::start_tag('form', ['method' => 'post',
                    'action' => $PAGE->url->out(false), 'style' => 'display:inline']);
                $akt .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                $akt .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
                $akt .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'uchrholat', 'value' => (int) $u->id]);
                $akt .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'holat', 'value' => $h]);
                $akt .= html_writer::tag('button', $holatmap[$h], ['type' => 'submit',
                    'class' => 'btn btn-sm ' . ($h === 'tasdiq' ? 'btn-success' : 'btn-outline-secondary')]);
                $akt .= html_writer::end_tag('form');
            }
            $ichi .= html_writer::div($akt, 'tz-meet-act');
        }
        echo html_writer::div($ichi, 'tz-meetcard');
    }
    echo html_writer::end_div();   // tz-cmsgs

    // ---- A'zolar va sozlamalar (faqat guruh chat)
    if ($guruhmi) {
        $azolar = chat::azolar_royxati($convid);
        echo html_writer::start_tag('details', ['class' => 'tz-memb']);
        echo html_writer::tag('summary', get_string('chat_members_settings', 'local_targetzenit')
            . ' (' . count($azolar) . ') ▾');
        echo html_writer::start_div('tz-mbody');

        // --- Chap ustun: sozlamalar
        echo html_writer::start_div('tz-mcol');
        echo html_writer::tag('h5', get_string('chat_settings', 'local_targetzenit'));
        $guruhnomi = isset($mening[(int) $achat->courseid])
            ? format_string((string) $mening[(int) $achat->courseid]) : '—';
        echo html_writer::div(
            html_writer::span(get_string('chat_attached_group', 'local_targetzenit'))
            . html_writer::tag('b', s($guruhnomi) . ' ('
                . get_string('chat_autoinvite', 'local_targetzenit') . ')'), 'tz-mrow');

        // Kim yoza oladi — ega uchun tanlagich, boshqalarga matn
        $yz = $achat->yozish === 'faqat_ega' ? 'faqat_ega' : 'hamma';
        if ($egami) {
            $sel = html_writer::start_tag('form', ['method' => 'post',
                'action' => $PAGE->url->out(false), 'style' => 'display:inline']);
            $sel .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            $sel .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
            $sel .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yozishsozla', 'value' => 1]);
            $sel .= html_writer::start_tag('select', ['name' => 'yozish',
                'class' => 'form-control form-control-sm', 'onchange' => 'this.form.submit()']);
            $sel .= html_writer::tag('option', get_string('chat_write_all', 'local_targetzenit'),
                ['value' => 'hamma'] + ($yz === 'hamma' ? ['selected' => 'selected'] : []));
            $sel .= html_writer::tag('option', get_string('chat_write_owner', 'local_targetzenit'),
                ['value' => 'faqat_ega'] + ($yz === 'faqat_ega' ? ['selected' => 'selected'] : []));
            $sel .= html_writer::end_tag('select');
            $sel .= html_writer::end_tag('form');
        } else {
            $sel = html_writer::tag('b', $yz === 'faqat_ega'
                ? get_string('chat_write_owner', 'local_targetzenit')
                : get_string('chat_write_all', 'local_targetzenit'));
        }
        echo html_writer::div(html_writer::span(get_string('chat_who_write', 'local_targetzenit'))
            . $sel, 'tz-mrow');
        echo html_writer::div(html_writer::span(get_string('chat_who_see', 'local_targetzenit'))
            . html_writer::tag('b', get_string('chat_see_val', 'local_targetzenit')), 'tz-mrow');

        // A'zo qo'shish (ega) — a'zo bo'lmaganlardan tanlash
        if ($egami) {
            $qoshish = array_diff_key($odamlar, $azolar);
            unset($qoshish[(int) $USER->id]);
            if ($qoshish) {
                $f = html_writer::start_tag('form', ['method' => 'post',
                    'action' => $PAGE->url->out(false), 'class' => 'tz-addmemb']);
                $f .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                $f .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
                $f .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'azoqosh', 'value' => 1]);
                $f .= html_writer::start_tag('select', ['name' => 'qoshid',
                    'class' => 'form-control form-control-sm']);
                $f .= html_writer::tag('option', get_string('chat_add_member', 'local_targetzenit'), ['value' => '0']);
                foreach ($qoshish as $uid => $nm) {
                    $f .= html_writer::tag('option', s($nm), ['value' => $uid]);
                }
                $f .= html_writer::end_tag('select');
                $f .= html_writer::tag('button', '→', ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']);
                $f .= html_writer::end_tag('form');
                echo html_writer::div(html_writer::span(get_string('chat_add_member', 'local_targetzenit'))
                    . $f, 'tz-mrow tz-mrow-add');
            }
        }
        echo html_writer::end_div();   // tz-mcol

        // --- O'ng ustun: a'zolar ro'yxati
        echo html_writer::start_div('tz-mcol');
        echo html_writer::tag('h5', get_string('chat_memberlist', 'local_targetzenit')
            . ' (' . count($azolar) . ')');
        foreach ($azolar as $uid => $nm) {
            $chiq = '';
            if ($egami) {
                $chiq = html_writer::start_tag('form', ['method' => 'post',
                    'action' => $PAGE->url->out(false), 'style' => 'display:inline']);
                $chiq .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                $chiq .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
                $chiq .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'azochiqar', 'value' => 1]);
                $chiq .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'chiqid', 'value' => $uid]);
                $chiq .= html_writer::tag('button', get_string('chat_remove_member', 'local_targetzenit'),
                    ['type' => 'submit', 'class' => 'tz-chiq']);
                $chiq .= html_writer::end_tag('form');
            }
            echo html_writer::div(html_writer::span(s($nm)) . $chiq, 'tz-mrow');
        }
        echo html_writer::end_div();   // tz-mcol

        echo html_writer::end_div();   // tz-mbody
        echo html_writer::end_tag('details');
    }

    // ---- Uchrashuv taklif formasi (yashirin, 📅 tugma bilan ochiladi)
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false),
        'class' => 'tz-meetform', 'id' => 'tz-meetform', 'hidden' => 'hidden']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'uchrashuv', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'datetime-local', 'name' => 'usana',
        'class' => 'form-control form-control-sm', 'required' => 'required']);
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'ujoy',
        'class' => 'form-control form-control-sm', 'placeholder' => get_string('uchr_joy', 'local_targetzenit')]);
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'uizoh',
        'class' => 'form-control form-control-sm', 'placeholder' => get_string('uchr_izoh', 'local_targetzenit')]);
    echo html_writer::tag('button', get_string('uchr_taklif_qil', 'local_targetzenit'),
        ['type' => 'submit', 'class' => 'btn btn-sm btn-primary']);
    echo html_writer::end_tag('form');

    // Yozish formasi
    if (!$yozaoladi) {
        echo html_writer::div(get_string('chat_only_owner_write', 'local_targetzenit'),
            'tz-cinput-note');
    } else {
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false),
            'class' => 'tz-cinput']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yubor', 'value' => 1]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'conv', 'value' => $convid]);
        echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'matn',
            'class' => 'form-control', 'autocomplete' => 'off', 'required' => 'required',
            'placeholder' => get_string('chat_write', 'local_targetzenit')]);
        // 📅 Uchrashuv — xabar formasini yubormasligi uchun type=button + JS toggle.
        echo html_writer::tag('button', get_string('uchr_btn', 'local_targetzenit'),
            ['type' => 'button', 'class' => 'btn btn-outline-secondary', 'id' => 'tz-meet-toggle',
             'title' => get_string('uchr_taklif', 'local_targetzenit')]);
        echo html_writer::tag('button', get_string('chat_send', 'local_targetzenit'),
            ['type' => 'submit', 'class' => 'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var t = document.getElementById('tz-meet-toggle');
            var f = document.getElementById('tz-meetform');
            if (t && f) { t.addEventListener('click', function() { f.hidden = !f.hidden; }); }
        });
    ");
}
echo html_writer::end_div();   // tz-cwin

echo html_writer::end_div();   // tz-chatwrap

// Xabarlar oxiriga avtoskroll.
$PAGE->requires->js_amd_inline("
    require([], function() {
        var m = document.querySelector('.tz-cmsgs');
        if (m) { m.scrollTop = m.scrollHeight; }
    });
");

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
