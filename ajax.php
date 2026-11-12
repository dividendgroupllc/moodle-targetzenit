<?php
// Audio yozuv uchun AJAX nuqtalari: start | chunk | finish | cancel | status.
// Har chaqiruvda sesskey va KURS kontekstidagi ruxsat tekshiriladi.

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');

use local_targetzenit\audio;

require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$lessonid = required_param('lessonid', PARAM_INT);

$dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid], '*', MUST_EXIST);
$context = context_course::instance($dars->courseid);

// YOZUV amallari — faqat shu kursda dars o'tadigan odam.
// TAHLIL amallari (status/analyse/reanalyse) — direktor/zavuch ham: ularda
// kurs kontekstidagi `teach` yo'q, tizim darajasida `viewanalysis` bor.
if (in_array($action, ['start', 'chunk', 'finish', 'cancel', 'diag'], true)) {
    require_capability('local/targetzenit:teach', $context);
} else if (!has_capability('local/targetzenit:teach', $context)) {
    require_capability('local/targetzenit:viewanalysis', context_system::instance());
}

if (in_array($action, ['start', 'chunk', 'finish', 'cancel', 'diag'], true)
        && !get_config('local_targetzenit', 'audioenabled')) {
    throw new \local_targetzenit\audio_xato('ochirilgan', 'Audio yozuv o\'chirilgan.');
}

echo $OUTPUT->header();   // AJAX_SCRIPT rejimida JSON sarlavhalarini qo'yadi

try {
    switch ($action) {
        case 'start':
            $mime = optional_param('mime', 'audio/webm', PARAM_RAW_TRIMMED);
            // `rejim`: davom | yangi — tugallanmagan yozuv topilganda nima
            // qilish kerakligini O'QITUVCHI tanlaydi (dars 201 saboqi).
            $rejim = optional_param('rejim', 'davom', PARAM_ALPHA);
            $rec = audio::start($lessonid, $mime, (int) $USER->id, $rejim);
            // `sid` — shu sessiyaning kaliti; `avlod` — brauzer qaysi raqamdan
            // yozishni boshlashi kerak (tugallanmagan yozuv davom etsa > 1).
            echo json_encode([
                'ok' => true,
                'status' => $rec->status,
                'sid' => $rec->sessionid,
                'avlod' => (int) $rec->_avlod,
                'davom' => (bool) $rec->_davom,
                'bolaklar' => (int) $rec->_bolaklar,
            ]);
            break;

        case 'chunk':
            $seq = required_param('seq', PARAM_INT);
            $avlod = optional_param('avlod', 1, PARAM_INT);
            $sid = optional_param('sid', '', PARAM_ALPHANUM);
            $data = file_get_contents('php://input');
            if ($data === false || $data === '') {
                // Bo'sh tana: ko'pincha `post_max_size` dan oshgan yoki
                // so'rov yo'lda uzilgan. Qayta urinish MA'NOLI — vaqtinchalik.
                throw new \local_targetzenit\audio_xato('bosh_tana', 'Bo\'sh bo\'lak');
            }
            $natija = audio::chunk($lessonid, $avlod, $seq, $sid, $data, $context);
            echo json_encode(['ok' => true, 'bytes' => $natija['bytes'],
                'parts' => $natija['parts']]);
            break;

        case 'diag':
            // QORA QUTI: brauzer hodisalari jurnali. `pagehide` da
            // `sendBeacon` bilan ham keladi, shuning uchun tana xom o'qiladi.
            $json = file_get_contents('php://input');
            if ($json !== false && $json !== '') {
                audio::save_diag($lessonid, $json);
            }
            echo json_encode(['ok' => true]);
            break;

        case 'finish':
            $duration = optional_param('duration', 0, PARAM_INT);
            $sid = optional_param('sid', '', PARAM_ALPHANUM);
            // Jurnal finish tanasida keladi — yakuniy va eng to'liq nusxa.
            $jurnal = file_get_contents('php://input');
            if ($jurnal !== false && $jurnal !== '') {
                audio::save_diag($lessonid, $jurnal);
            }
            $rec = audio::finish($lessonid, $context, $duration, $sid ?: null);
            echo json_encode([
                'ok' => true,
                'status' => $rec->status,
                'filesize' => (int) $rec->filesize,
                'duration' => (int) $rec->duration,
                'segments' => (int) ($rec->segments ?? 1),
                'warning' => $rec->warning ?? null,
                'url' => (string) audio::file_url($lessonid, $context),
            ]);
            break;

        case 'cancel':
            audio::cancel($lessonid);
            echo json_encode(['ok' => true]);
            break;

        case 'status':
            $rec = audio::get($lessonid);
            echo json_encode([
                'ok' => true,
                'status' => $rec->status ?? 'none',
                'staged' => audio::staged_size($lessonid),
                'filesize' => (int) ($rec->filesize ?? 0),
                'analysed' => !empty($rec->analysis),
                'aierror' => $rec->aierror ?? null,
            ]);
            break;

        case 'reanalyse':
            // FAQAT SAYT MA'MURI (qaror 2026-11-04): amal eski tahlil va
            // transkriptni ustiga yozadi, ya'ni dalilni almashtiradi.
            // Tugmani yashirish yetarli emas — so'rovni to'g'ridan-to'g'ri
            // yuborish mumkin, shuning uchun ruxsat SHU YERDA ham tekshiriladi.
            require_capability('moodle/site:config', context_system::instance());

            // Eski ramkadagi tahlilni yangisi bilan almashtirish.
            if (!\local_targetzenit\ai::enabled()) {
                throw new moodle_exception('AI tahlil yoqilmagan yoki kalit yo\'q.');
            }
            $rec = audio::get($lessonid);
            if (!$rec || !in_array($rec->status, audio::FAYLLI, true)) {
                throw new moodle_exception('Audio yo\'q — qayta tahlil qilib bo\'lmaydi.');
            }
            $rec->analysis = null;
            $rec->materialdesc = null;   // material qayta ko'rib chiqiladi
            $rec->aierror = null;
            $rec->status = 'ready';
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);

            // FONGA qo'yamiz, bu yerda BAJARMAYMIZ.
            // Sabab: tahlil endi bo'lak-bo'lak ishlaydi va bir necha daqiqa oladi.
            // PHP-FPM da atigi 4 ta ishchi bor — uzun so'rov ularni band qilsa,
            // butun Moodle javob bermay qoladi (2026-10-27 da shunday bo'ldi).
            \local_targetzenit\task\analyse_one::queue($lessonid);
            echo json_encode(['ok' => true, 'navbatda' => true]);
            break;

        case 'analyse':
            // DEMO uchun: 15 daqiqalik jadvalni kutmasdan darhol tahlil.
            if (!\local_targetzenit\ai::enabled()) {
                throw new moodle_exception('AI tahlil yoqilmagan yoki kalit yo\'q.');
            }
            $rec = audio::get($lessonid);
            if (!$rec || $rec->status !== 'ready') {
                throw new moodle_exception('Tahlil qilinadigan audio yo\'q.');
            }
            \local_targetzenit\task\analyse_one::queue($lessonid);
            echo json_encode(['ok' => true, 'navbatda' => true]);
            break;

        default:
            throw new moodle_exception('Noma\'lum amal');
    }
} catch (\Throwable $e) {
    // `kod` brauzerga qayta urinish kerakmi yoki yo'qligini aytadi.
    // Noma'lum xato (masalan baza uzilishi) VAQTINCHALIK deb qaraladi:
    // bo'lak navbatda qoladi, ovoz yo'qolmaydi.
    $kod = ($e instanceof \local_targetzenit\audio_xato) ? $e->kod : 'nomalum';
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'kod' => $kod,
        'qatiy' => \local_targetzenit\audio_xato::qatiymi($kod),
    ]);
}
