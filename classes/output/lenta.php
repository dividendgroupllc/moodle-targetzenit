<?php
// DARS YOZUVLARI LENTASI — YAGONA chizuvchi.
//
// Xuddi shu lenta ikki joyda ko'rinadi:
//   yozuvlar.php    direktor/zavuch — BUTUN maktab, o'qituvchi filtri bilan
//   oqituvchi.php   o'qituvchi — FAQAT O'ZINIKI, filtr yo'q
//
// Kod nusxalansa, lenta ko'rinishi o'zgarganda (allaqachon ikki marta
// o'zgargan: ustunlar, keyin ochiladigan karta) ikki sahifa boshqa-boshqa
// narsa ko'rsatadi — `tahlil.php` da aynan shu xato bir marta bo'lgan.
//
// MAXFIYLIK: lenta QATORLARNI TANLAMAYDI. Qaysi yozuvlar ko'rinishini
// faqat `dashboard::recordings()` ga uzatilgan filtr hal qiladi va uni
// SAHIFA beradi. O'qituvchi sahifasida `teacher` filtri serverda qotib
// yoziladi (so'rov parametridan OLINMAYDI), shuning uchun bu yerda
// boshqa odamning yozuvi paydo bo'lishi mumkin emas.

namespace local_targetzenit\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_targetzenit\ai;
use local_targetzenit\audio;
use local_targetzenit\dashboard;
use moodle_url;

class lenta {

    /** Bir sahifadagi yozuv soni. */
    const QATOR = 20;

    /** Qisqa xulosa necha belgida kesiladi. */
    const XULOSA = 150;

    /** Holat chipi — rang + MATN (rang yolg'iz ma'no tashlamaydi). */
    protected static function chip(string $status, string $matn, string $title = ''): string {
        return html_writer::tag('span', s($matn),
            ['class' => 'tz-dir-chip tz-dir-' . $status, 'title' => $title]);
    }

    /**
     * Lentani chizadi.
     *
     * @param array $rows `dashboard::recordings()` dan kelgan qatorlar
     * @param array $o sozlamalar:
     *   'url'       callable(array): moodle_url — havola quruvchi (MAJBURIY)
     *   'page'      int joriy sahifa
     *   'ism'       bool o'qituvchi ustuni ko'rsatilsinmi (o'z lentasida — yo'q)
     *   'ochirish'  bool o'chirish tugmalari
     *   'fidbek'    bool fidbek bloki
     */
    public static function render(array $rows, array $o): string {
        $url = $o['url'];
        $page = (int) ($o['page'] ?? 0);
        $ism = (bool) ($o['ism'] ?? true);
        $ochirish = (bool) ($o['ochirish'] ?? false);
        $fidbekbor = (bool) ($o['fidbek'] ?? false);

        $t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);
        $norma = ai::norm_overall();
        $syscontext = \context_system::instance();

        // Qatordagi qisqa fidbek uchun oxirgi xabarlar — HAMMA qator uchun
        // bitta so'rovda, aks holda sahifada 20 ta alohida so'rov bo'lardi.
        $fidbeklar = $fidbekbor
            ? \local_targetzenit\feedback::latest(
                array_map(fn($r) => (int) $r->lessonid, $rows),
                has_capability('local/targetzenit:viewprivatefeedback', $syscontext))
            : [];
        $koranaliz = has_capability('local/targetzenit:viewanalysis', $syscontext);

        // Kurs konteksti qator-qator tekshiriladi, lekin KESHLANADI: bir
        // sahifada bitta kurs bir necha marta uchraydi.
        $kesh = [];
        $teachable = function(?int $courseid) use (&$kesh): bool {
            if (!$courseid) {
                return false;
            }
            if (!array_key_exists($courseid, $kesh)) {
                $ctx = \context_course::instance($courseid, IGNORE_MISSING);
                $kesh[$courseid] = $ctx
                    && has_capability('local/targetzenit:teach', $ctx);
            }
            return $kesh[$courseid];
        };

        if (!$rows) {
            return html_writer::div(
                html_writer::tag('b', $t('rec_none'))
                . html_writer::div($t('rec_none_help'), 'tz-dir-sub'),
                'tz-dir-card');
        }

        $ustun = $ism
            ? ['dir_colteacher', 'rec_coltopic', 'rec_colwhen', 'rec_collength',
               'rec_colai', 'rec_colsummary']
            : ['dir_colgroup', 'rec_coltopic', 'rec_colwhen', 'rec_collength',
               'rec_colai', 'rec_colsummary'];
        // Fidbek ustuni: kartani OCHMASDAN ham yozish/javob berish uchun.
        // `colspan` quyida `count($ustun)` dan olinadi — o'zi to'g'rilanadi.
        if ($fidbekbor) {
            $ustun[] = 'fb_title';
        }

        $h = html_writer::start_div('tz-dir-card');
        $h .= html_writer::start_div('tz-dir-tablewrap');
        $h .= html_writer::start_tag('table', ['class' => 'tz-dir-table tz-rec']);

        $h .= html_writer::start_tag('thead') . html_writer::start_tag('tr');
        foreach ($ustun as $k) {
            $h .= html_writer::tag('th', $t($k));
        }
        $h .= html_writer::end_tag('tr') . html_writer::end_tag('thead');

        $h .= html_writer::start_tag('tbody');
        foreach ($rows as $r) {
            $id = 'tzrec' . (int) $r->audioid;
            $fanguruh = trim(($r->subjectname ?: '') . ' · ' . ($r->groupname ?: ''), ' ·');

            // ------------------------------------------------ master qator
            $h .= html_writer::start_tag('tr', ['class' => 'tz-rec-row', 'data-tzrec' => $id,
                'tabindex' => '0', 'role' => 'button', 'title' => $t('rec_expand')]);

            // Birinchi ustun: direktorda — kim, o'z lentasida — qayerda.
            // O'z lentasida ismni takrorlash joy yeydi va ma'no bermaydi.
            $h .= html_writer::tag('td', html_writer::div(
                html_writer::tag('span', '▸', ['class' => 'tz-rec-caret'])
                . html_writer::div($ism
                    ? html_writer::div(s($r->ism), 'tz-dir-chipname')
                        . html_writer::div(s($fanguruh), 'tz-dir-sub')
                    : html_writer::div(s((string) ($r->groupname ?: '—')), 'tz-dir-chipname')
                        . html_writer::div(s((string) ($r->subjectname ?: '')), 'tz-dir-sub')),
                'tz-dir-cellname'));

            $h .= html_writer::tag('td',
                s(\core_text::substr(trim((string) $r->topic), 0, 70) ?: '—')
                . ($r->vacancy ? ' ' . self::chip('warning', $t('rec_vacancy')) : ''));

            $h .= html_writer::tag('td',
                userdate(strtotime($r->lessondate), '%d.%m.%Y')
                . html_writer::div(userdate((int) $r->timecreated, '%H:%M'), 'tz-dir-sub'));

            $daq = (int) round(((int) $r->duration) / 60);
            $h .= html_writer::tag('td', $r->duration
                ? html_writer::tag('b', $daq . ' ' . $t('rec_min'))
                : html_writer::tag('span', '—', ['class' => 'tz-dir-sub']));

            // AI — NORMAGA NISBATAN (59%), mutlaq shkalada emas
            if ($r->ball === null) {
                $h .= html_writer::tag('td', $r->aierror
                    ? self::chip('warning', $t('dir_failed'), (string) $r->aierror)
                    : self::chip('none', $t('rec_pending')));
            } else {
                $h .= html_writer::tag('td',
                    self::chip($r->band['status'], $r->band['nom'], $t('dir_normis', $norma))
                    . html_writer::div($r->ball . '% · '
                        . ($r->band['delta'] >= 0 ? '+' : '') . $r->band['delta'] . '%',
                        'tz-dir-sub'));
            }

            $qisqa = \core_text::substr($r->xulosa, 0, self::XULOSA);
            $h .= html_writer::tag('td', html_writer::div(
                s($qisqa . (\core_text::strlen($r->xulosa) > self::XULOSA ? '…' : '')) ?: '—',
                'tz-rec-xulosa'));

            // Fidbek: oldingi xabar KICHRAYTIRILGAN holatda + bir qatorli
            // yozish maydoni. Kartadagi to'liq suhbat bilan BITTA zanjir.
            if ($fidbekbor) {
                $h .= html_writer::tag('td', fidbek::compactcell((int) $r->lessonid,
                    (int) ($r->teacheruserid ?? 0), $fidbeklar[(int) $r->lessonid] ?? null,
                    $url(['page' => $page])->out(false)), ['class' => 'tz-fb-td']);
            }

            $h .= html_writer::end_tag('tr');

            // ------------------------------------------- ochiladigan karta
            $h .= html_writer::start_tag('tr',
                ['class' => 'tz-rec-drill', 'id' => $id, 'hidden' => 'hidden']);
            $h .= html_writer::start_tag('td', ['colspan' => count($ustun)]);
            $h .= html_writer::start_div('tz-rec-card');

            // --- chap: ovoz
            $h .= html_writer::start_div('tz-rec-left');
            $ctx = $r->courseid
                ? \context_course::instance((int) $r->courseid, IGNORE_MISSING) : null;
            $aurl = $ctx ? audio::file_url((int) $r->lessonid, $ctx) : null;
            if ($aurl) {
                // preload="none" — qator ochilmaguncha audio yuklanmaydi
                $h .= '<audio class="tz-rec-audio" controls preload="none" src="'
                    . $aurl->out(false) . '"></audio>';
                $h .= html_writer::div(
                    ($r->filesize ? number_format($r->filesize / 1048576, 1) . ' MB' : '')
                    . ($r->hasmatn ? ' · ' . $t('rec_hastext') : ''), 'tz-dir-sub mt-1');
            } else {
                $h .= html_writer::div($r->audiostatus === 'deleted'
                    ? $t('dir_audiogone') : $t('dir_audionone'), 'tz-dir-sub');
            }
            $h .= html_writer::end_div();

            // --- o'ng: qisqa tahlil
            $h .= html_writer::start_div('tz-rec-right');
            if ($r->xulosa !== '') {
                $h .= html_writer::tag('p', s($r->xulosa), ['class' => 'tz-rec-full']);
            }
            foreach ([['rec_strong', $r->kuchli, 'good'], ['rec_weak', $r->kuchsiz, 'serious']]
                     as [$yorliq, $k, $st]) {
                if (!$k) {
                    continue;
                }
                $h .= html_writer::div(
                    self::chip($st, $t($yorliq))
                    . ' ' . html_writer::tag('b', s($k['nom']))
                    . html_writer::tag('span', ' ' . $k['ball'] . '% ('
                        . ($k['delta'] >= 0 ? '+' : '') . $k['delta'] . '%)',
                        ['class' => 'tz-dir-sub']), 'tz-rec-cat');
            }
            foreach ($r->tavsiyalar as $tav) {
                $h .= html_writer::div('→ ' . s($tav), 'tz-rec-tavsiya');
            }
            if ($r->xulosa === '' && !$r->kuchli) {
                $h .= html_writer::div($r->aierror
                    ? s((string) $r->aierror) : $t('rec_pending'), 'tz-dir-sub');
            }
            $h .= html_writer::end_div();
            $h .= html_writer::end_div();

            // --- amal tugmalari
            //
            // Darsga o'tish manzili RUXSATGA qarab tanlanadi: o'qituvchida
            // `viewanalysis` yo'q, ya'ni `dars_tahlil.php` unga 403 beradi —
            // u o'z kursidagi kokpitga o'tadi (baho, audio, AI tahlili bir
            // joyda). Ikkisi ham bo'lmasa tugma QO'YILMAYDI: kursga
            // yozilmagan odam uchun havola boshi berk yo'l.
            $h .= html_writer::start_div('tz-rec-actions');
            if ($teachable($r->courseid === null ? null : (int) $r->courseid)) {
                $h .= html_writer::link(
                    new moodle_url('/local/targetzenit/dars.php', [
                        'courseid' => (int) $r->courseid,
                        'date' => $r->lessondate,
                        'lessonno' => (int) $r->lessonno,
                    ]), $t('dir_opencockpit'), ['class' => 'btn btn-sm btn-primary']);
            } else if ($koranaliz) {
                $h .= html_writer::link(
                    new moodle_url('/local/targetzenit/dars_tahlil.php',
                        ['lessonid' => (int) $r->lessonid]),
                    $t('rec_openlesson'), ['class' => 'btn btn-sm btn-primary']);
            }
            if ($ochirish) {
                if ($aurl) {
                    $h .= html_writer::link($url(['ochir' => $r->audioid, 'turi' => 'audio',
                        'sesskey' => sesskey()]), $t('rec_delaudio'),
                        ['class' => 'btn btn-sm btn-outline-warning',
                         'onclick' => 'return confirm('
                             . json_encode($t('rec_delaudio_q')) . ')']);
                }
                $h .= html_writer::link($url(['ochir' => $r->audioid, 'turi' => 'hammasi',
                    'sesskey' => sesskey()]), $t('rec_delall'),
                    ['class' => 'btn btn-sm btn-outline-danger',
                     'onclick' => 'return confirm(' . json_encode($t('rec_delall_q')) . ')']);
            }
            $h .= html_writer::end_div();

            // --- fidbek (ro'yxat + ruxsat bo'lsa yozish formasi)
            if ($fidbekbor) {
                $h .= fidbek::render((int) $r->lessonid, (int) ($r->teacheruserid ?? 0),
                    $url(['page' => $page])->out(false), false);
            }

            $h .= html_writer::end_tag('td') . html_writer::end_tag('tr');
        }
        $h .= html_writer::end_tag('tbody') . html_writer::end_tag('table');
        $h .= html_writer::end_div();
        $h .= html_writer::end_div();

        return $h;
    }

    /**
     * Qatorni ochish/yopish — kichik vanilla JS, tashqi kutubxonasiz (CSP).
     *
     * Yopilganda audio to'xtatiladi: bir vaqtda ikki yozuv jaranglamasin.
     */
    public static function js(\moodle_page $page): void {
        $page->requires->js_amd_inline("
require([], function() {
    document.querySelectorAll('.tz-rec-row').forEach(function (qator) {
        var ochish = function () {
            var d = document.getElementById(qator.dataset.tzrec);
            if (!d) { return; }
            d.hidden = !d.hidden;
            qator.classList.toggle('tz-on', !d.hidden);
            if (d.hidden) {
                var a = d.querySelector('audio');
                if (a) { a.pause(); }
            }
        };
        // Fidbek katagi ichida yozayotganda karta ochilib ketmasligi kerak
        qator.addEventListener('click', function (e) {
            if (e.target.closest('[data-tzstop]')) { return; }
            ochish();
        });
        qator.addEventListener('keydown', function (e) {
            if (e.target.closest('[data-tzstop]')) { return; }
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ochish(); }
        });
    });
});
        ");
    }
}
