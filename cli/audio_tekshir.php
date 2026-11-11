<?php
/**
 * Saqlangan dars yozuvlarini tekshirish: KESILGANlarini topish va belgilash.
 *
 *   php local/targetzenit/cli/audio_tekshir.php              # faqat ko'rsatadi
 *   php local/targetzenit/cli/audio_tekshir.php --apply      # bazaga yozadi
 *   php local/targetzenit/cli/audio_tekshir.php --apply --tahlilni-bekor
 *
 * NEGA KERAK (2026-11-11): yozuv oynasi fonda ishlashdan to'xtaganda
 * MediaRecorder bo'lak bermay qo'yadi, brauzer soati esa yuraveradi. Natijada
 * 3 daqiqalik ovoz bazaga "33 daqiqa" bo'lib tushadi. AI esa faylning jim
 * qismlariga DARS MATNINI O'YLAB TOPADI (prodda: "7-sinf algebra",
 * "8-sinf fizika Om qonuni" — ikkisi ham yo'q darslar) va zavuch soxta
 * bahoni o'qiydi.
 *
 * Bu vosita har faylni o'lchaydi:
 *   - `audiblesec` — HAQIQATAN bor ovoz (dekodlangan PCM hajmidan);
 *   - `wallsec`    — tugma bosilgan turgan vaqt (eski `duration` shu edi);
 *   - `duration`   — endi haqiqiy ovoz uzunligi bo'ladi;
 *   - `warning`    — kesilgani haqida ogohlantirish.
 *
 * `--tahlilni-bekor` kesilgan yozuvlarning TAHLILINI o'chiradi (transkript
 * qoladi, odam o'zi ko'rib chiqishi uchun). Baho soxta bo'lgani uchun
 * panelda turmasligi kerak.
 *
 * YOZISHDAN OLDIN HECH NARSA O'ZGARMAYDI: `--apply` bo'lmasa faqat jadval
 * chiqaradi.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_targetzenit\audio;

list($opt) = cli_get_params(
    ['help' => false, 'apply' => false, 'tahlilni-bekor' => false, 'jurnal' => '',
     'remux' => false],
    ['h' => 'help']
);

if ($opt['help']) {
    cli_writeln("Dars yozuvlarini tekshirish (kesilganlarini topish).\n");
    cli_writeln("  --apply            bazaga yozadi (aks holda faqat ko'rsatadi)");
    cli_writeln("  --tahlilni-bekor   kesilganlarning soxta tahlilini o'chiradi");
    cli_writeln("  --jurnal=<dars>    QORA QUTI: yozuv nega jim qolganini ko'rsatadi");
    cli_writeln("  --remux            saqlangan fayllarni QIDIRILADIGAN holga keltiradi");
    cli_writeln("                     (pleyerda vaqt chizig'i paydo bo'ladi; --apply kerak)");
    exit(0);
}

// ===================== QORA QUTI
//
// Brauzer yozuv davomida hodisalarni qayd etib boradi va ularni serverga
// yuboradi. Shu yerda o'qiymiz: oyna yashirilganmi, internet uzilganmi,
// mikrofon o'chganmi, qorovul yozuvni tiklaganmi.
if ($opt['jurnal'] !== '') {
    $a = $DB->get_record('local_targetzenit_audio', ['lessonid' => (int) $opt['jurnal']]);
    if (!$a) {
        cli_error('Bu darsda yozuv yo\'q.');
    }
    cli_writeln("Dars {$a->lessonid}: ovoz {$a->audiblesec}s / yozilgan {$a->wallsec}s"
        . ', bo\'lak-qism ' . $a->segments . ', holat ' . $a->status);
    if ($a->warning) {
        cli_writeln('OGOHLANTIRISH: ' . $a->warning);
    }
    if (empty($a->diag)) {
        cli_writeln("\nQora quti BO'SH — yozuv 0.46.0 dan oldin qilingan "
            . "yoki jurnal yetib kelmagan.");
        exit(0);
    }
    $d = json_decode($a->diag, true);
    if (!is_array($d) || empty($d['jurnal'])) {
        cli_writeln("\nQora qutini o'qib bo'lmadi.");
        exit(0);
    }
    cli_writeln("\nQurilma: " . ($d['qurilma'] ?? '?'));
    cli_writeln('Bo\'lak: ' . ($d['bolak'] ?? '?') . ', o\'zi tiklangan: '
        . ($d['uzilish'] ?? 0) . " marta\n");

    // Bo'laklar oqimini qisqartiramiz — muhimi HODISALAR va BO'SHLIQLAR
    $oxirgi_bolak = null;
    $oxirgi_vaqt = 0;
    foreach ($d['jurnal'] as $q) {
        list($t, $hodisa, $izoh) = array_pad((array) $q, 3, '');
        if ($hodisa === 'bolak') {
            // Bo'laklar orasida 60 soniyadan katta tanaffus bo'lsa — SHU MUHIM
            if ($oxirgi_bolak !== null && $t - $oxirgi_bolak > 60) {
                cli_writeln(sprintf('  %s  >>> %d SONIYA BO\'LAK KELMAGAN (yozuv to\'xtagan) <<<',
                    gmdate('i:s', $oxirgi_bolak), $t - $oxirgi_bolak));
            }
            $oxirgi_bolak = $t;
            continue;
        }
        cli_writeln(sprintf('  %s  %-16s %s', gmdate('i:s', (int) $t), $hodisa, $izoh));
        $oxirgi_vaqt = $t;
    }
    if ($oxirgi_bolak !== null) {
        cli_writeln(sprintf("\n  Oxirgi bo'lak: %s", gmdate('i:s', $oxirgi_bolak)));
    }
    exit(0);
}

$yozuvlar = $DB->get_records_select('local_targetzenit_audio',
    "status IN ('ready', 'transcribed') AND filesize > 0", [], 'id');

if (!$yozuvlar) {
    cli_writeln('Tekshiriladigan yozuv yo\'q.');
    exit(0);
}

$fs = get_file_storage();
$kesilgan = 0;
$tekshirildi = 0;
$bekor = 0;
$remux = 0;

cli_writeln(sprintf('%-5s %-7s %-11s %9s %9s %9s  %s',
    'id', 'dars', 'sana', 'ovoz', 'yozilgan', 'tahlil', 'xulosa'));
cli_writeln(str_repeat('-', 78));

foreach ($yozuvlar as $a) {
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid],
        'id, courseid, lessondate');
    if (!$dars) {
        continue;
    }
    $ctx = \context_course::instance($dars->courseid, IGNORE_MISSING);
    if (!$ctx) {
        continue;
    }

    $fayllar = $fs->get_area_files($ctx->id, audio::COMPONENT, audio::FILEAREA,
        $a->lessonid, 'id', false);
    $f = $fayllar ? reset($fayllar) : null;
    if (!$f) {
        continue;
    }

    // Faylni vaqtinchalik joyga chiqaramiz — ffmpeg fayl bilan ishlaydi
    $vaqtinchalik = make_temp_directory('local_targetzenit/tekshir') . '/a-' . $a->id . '.'
        . (pathinfo($f->get_filename(), PATHINFO_EXTENSION) ?: 'webm');
    $f->copy_content_to($vaqtinchalik);

    $ovoz = audio::audio_seconds($vaqtinchalik);
    $konteyner = audio::probe_duration($vaqtinchalik);
    $tekshirildi++;

    // ============ REMUX: pleyerda vaqt chizig'i paydo bo'lishi uchun
    //
    // MediaRecorder bergan oqimda davomiylik yo'q, shuning uchun brauzer
    // `duration = Infinity` deb ko'radi va surib qo'yishga ruxsat bermaydi.
    // `ffmpeg -c copy` sarlavhani qaytadan yozadi (qayta kodlash YO'Q).
    $remuxlandi = false;
    if ($opt['remux'] && $opt['apply'] && $konteyner === null) {
        $yangiyol = $vaqtinchalik . '.remux.'
            . (pathinfo($f->get_filename(), PATHINFO_EXTENSION) ?: 'webm');
        if (audio::remux($vaqtinchalik, $yangiyol)) {
            // XAVFSIZ TARTIB: avval YANGI faylni yaratamiz, keyin eskisini
            // o'chiramiz, so'ng nomini qaytaramiz. Teskarisi bo'lsa, yaratish
            // xato bersa dars butunlay yo'qolardi.
            $asliy = $f->get_filename();
            $yangifile = $fs->create_file_from_pathname([
                'contextid' => $ctx->id,
                'component' => audio::COMPONENT,
                'filearea' => audio::FILEAREA,
                'itemid' => $a->lessonid,
                'filepath' => '/',
                'filename' => 'remux-' . $asliy,
            ], $yangiyol);
            $f->delete();
            $yangifile->rename('/', $asliy);
            $a->filesize = filesize($yangiyol);
            $remuxlandi = true;
            $remux++;
        }
        @unlink($yangiyol);
    }
    @unlink($vaqtinchalik);

    if ($ovoz === null) {
        cli_writeln(sprintf('%-5s %-7s %-11s %9s %9s %9s  %s',
            $a->id, $a->lessonid, $dars->lessondate, '?', $a->duration . 's',
            $a->analysis ? 'bor' : '-', 'o\'lchab bo\'lmadi'));
        continue;
    }

    // Eski yozuvlarda `duration` — brauzer soati, ya'ni `wallsec`.
    $devorsoat = (int) ($a->wallsec ?: $a->duration);
    $ovozsek = (int) round($ovoz);
    $bu_kesilgan = $devorsoat > 120 && $ovozsek < $devorsoat * 0.8;

    cli_writeln(sprintf('%-5s %-7s %-11s %9s %9s %9s  %s',
        $a->id, $a->lessonid, $dars->lessondate,
        gmdate('i:s', $ovozsek), gmdate('i:s', $devorsoat),
        $a->analysis ? 'bor' : '-',
        ($bu_kesilgan ? 'KESILGAN' . ($a->analysis ? ' + SOXTA TAHLIL' : '') : 'sog\'lom')
        . ($remuxlandi ? ' [remux qilindi]' : ($konteyner === null ? ' [qidirilmaydi]' : ''))));

    if ($bu_kesilgan) {
        $kesilgan++;
    }

    if (!$opt['apply']) {
        continue;
    }

    $a->audiblesec = $ovozsek;
    $a->wallsec = $devorsoat;
    $a->duration = $ovozsek;
    if ($bu_kesilgan) {
        $a->warning = \core_text::substr(sprintf(
            'KESILGAN: %s ovoz bor, %s yozilgan — yozuv oynasi ishlashdan to\'xtagan',
            gmdate('i:s', $ovozsek), gmdate('i:s', $devorsoat)), 0, 255);

        if ($opt['tahlilni-bekor'] && $a->analysis) {
            // Tahlil chala yozuvga qo'yilgan baho — panelda turmasligi kerak.
            // Transkript QOLADI: eshitilgan qismi haqiqiy, odam ko'rib chiqsin.
            $a->analysis = null;
            $a->analysedat = 0;
            $a->aierror = 'Yozuv kesilgan — tahlil ishonchsiz deb bekor qilindi';
            $a->status = 'ready';
            $bekor++;
        }
    }
    $a->timemodified = time();
    $DB->update_record('local_targetzenit_audio', $a);
}

cli_writeln(str_repeat('-', 78));
cli_writeln(sprintf('Tekshirildi: %d, kesilgan: %d%s%s', $tekshirildi, $kesilgan,
    $bekor ? ", tahlili bekor qilindi: {$bekor}" : '',
    $remux ? ", remux qilindi: {$remux}" : ''));
if (!$opt['apply']) {
    cli_writeln('(bazaga YOZILMADI — yozish uchun --apply qo\'shing)');
}
