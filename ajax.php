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
require_capability('local/targetzenit:teach', $context);

if (in_array($action, ['start', 'chunk', 'finish', 'cancel'], true)
        && !get_config('local_targetzenit', 'audioenabled')) {
    throw new moodle_exception('Audio yozuv o\'chirilgan.');
}

echo $OUTPUT->header();   // AJAX_SCRIPT rejimida JSON sarlavhalarini qo'yadi

try {
    switch ($action) {
        case 'start':
            $mime = optional_param('mime', 'audio/webm', PARAM_RAW_TRIMMED);
            $rec = audio::start($lessonid, $mime, (int) $USER->id);
            echo json_encode(['ok' => true, 'status' => $rec->status]);
            break;

        case 'chunk':
            $seq = required_param('seq', PARAM_INT);
            $data = file_get_contents('php://input');
            if ($data === false || $data === '') {
                throw new moodle_exception('Bo\'sh bo\'lak');
            }
            $hajm = audio::chunk($lessonid, $seq, $data);
            echo json_encode(['ok' => true, 'bytes' => $hajm]);
            break;

        case 'finish':
            $duration = optional_param('duration', 0, PARAM_INT);
            $rec = audio::finish($lessonid, $context, $duration);
            echo json_encode([
                'ok' => true,
                'status' => $rec->status,
                'filesize' => (int) $rec->filesize,
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
            // Eski ramkadagi tahlilni yangisi bilan almashtirish.
            if (!\local_targetzenit\ai::enabled()) {
                throw new moodle_exception('AI tahlil yoqilmagan yoki kalit yo\'q.');
            }
            $rec = audio::get($lessonid);
            if (!$rec || !in_array($rec->status, audio::FAYLLI, true)) {
                throw new moodle_exception('Audio yo\'q — qayta tahlil qilib bo\'lmaydi.');
            }
            $rec->analysis = null;
            $rec->aierror = null;
            $rec->status = 'ready';
            $rec->timemodified = time();
            $DB->update_record('local_targetzenit_audio', $rec);

            \core_php_time_limit::raise(900);
            $ok = \local_targetzenit\ai::analyse(audio::get($lessonid));
            $yangi = audio::get($lessonid);
            echo json_encode([
                'ok' => $ok,
                'error' => $ok ? null : ($yangi->aierror ?? 'noma\'lum xato'),
            ]);
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
            \core_php_time_limit::raise(900);
            $ok = \local_targetzenit\ai::analyse($rec);
            $yangi = audio::get($lessonid);
            echo json_encode([
                'ok' => $ok,
                'error' => $ok ? null : ($yangi->aierror ?? 'noma\'lum xato'),
            ]);
            break;

        default:
            throw new moodle_exception('Noma\'lum amal');
    }
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
