<?php
// MAKET QOBIG'I — o'qituvchi portalining to'liq UI ramkasi.
//
// QAROR (2026-11-18, foydalanuvchi): o'qituvchi Moodle interfeysini UMUMAN
// ko'rmaydi — ekranda faqat shablon (~/Documents/oqituvchi-portal-ui.html)
// ko'rinishi: yashil top-bar, oq tab-navigatsiya, kulrang fon. Moodle esa
// ORQADA ishlayveradi: sessiya, ruxsatlar, gradebook, mod_assign, fayllar.
//
// QANDAY: sahifa `embedded` layout bilan ochiladi (Moodle navbar/drawer/footer
// chiqmaydi), top-bar va tablarni shu klass chizadi. Ishlatish:
//     $PAGE->set_pagelayout('embedded');
//     ...
//     maket::boshla('kokpit');   // header o'rniga
//     ... sahifa tarkibi ...
//     maket::tugat();            // footer o'rniga

namespace local_targetzenit\output;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class maket {

    /** Tab ro'yxati: kalit => [satr kaliti, url]. Tartib — maketdagidek. */
    protected static function tablar(): array {
        global $USER;

        $t = [
            'bosh' => ['bosh_nav', '/local/targetzenit/bosh.php'],
            'jadval' => ['timetable', '/local/targetzenit/index.php'],
            'jurnal' => ['jurnal_link', '/local/targetzenit/jurnal.php'],
            'darslarim' => ['oq_mypanel', '/local/targetzenit/oqituvchi.php'],
            'topshiriqlar' => ['tp_nav', '/local/targetzenit/topshiriqlar.php'],
        ];
        if (has_capability('local/targetzenit:manageexams', \context_system::instance())
                || \local_targetzenit\exam::mening_kurslarim((int) $USER->id)) {
            $t['baholash'] = ['bh_nav', '/local/targetzenit/baholash.php'];
        }
        return $t;
    }

    /**
     * Qobiq boshи: Moodle header (embedded, yalang'och) + top-bar + tablar.
     *
     * @param string $faol qaysi tab yoqib ko'rsatiladi (tablar() kaliti)
     */
    public static function boshla(string $faol): void {
        global $OUTPUT, $PAGE, $USER;

        $PAGE->add_body_class('tz-shell');
        echo $OUTPUT->header();

        // ------- Yashil top-bar (maket .top)
        $bosh = new moodle_url('/local/targetzenit/bosh.php');
        $chiqish = new moodle_url('/login/logout.php', ['sesskey' => sesskey()]);
        $ism = trim((string) $USER->firstname . ' ' . (string) $USER->lastname);
        $bosh_harflar = \core_text::strtoupper(
            \core_text::substr((string) $USER->firstname, 0, 1)
            . \core_text::substr((string) $USER->lastname, 0, 1));

        echo html_writer::start_div('tz-top');
        // OQ logotip (pix/logo_oq.png) — to'q ko'k panel uchun maxsus variant:
        // harflar oq, belgilar qizil, fon shaffof. To'liq rangli nusxa
        // (pix/logo.png) oq qog'ozdagi hisobot varag'ida ishlatiladi.
        echo html_writer::link($bosh,
            html_writer::empty_tag('img', [
                'src' => $OUTPUT->image_url('logo_oq', 'local_targetzenit')->out(false),
                'alt' => 'Target International School', 'class' => 'tz-top-logo',
            ])
            . html_writer::tag('small',
                get_string('shell_sub', 'local_targetzenit'),
                ['class' => 'tz-top-brandtxt']),
            ['class' => 'tz-top-brand']);
        echo html_writer::tag('span', '', ['class' => 'tz-top-sp']);
        echo html_writer::tag('span',
            userdate(time(), '%A, %d-%B %Y'), ['class' => 'tz-top-date']);

        // Til almashtirgich (UZ/RU/EN) — Moodle sessiya tilini ?lang= bilan
        // almashtiradi; faqat o'rnatilgan til paketlari ko'rsatiladi.
        $tillar = get_string_manager()->get_list_of_translations();
        $joriy = current_language();
        echo html_writer::start_tag('span', ['class' => 'tz-top-lang']);
        foreach (['uz', 'ru', 'en'] as $t) {
            if (!isset($tillar[$t])) {
                continue;
            }
            echo html_writer::link(
                new moodle_url($PAGE->url, ['lang' => $t]),
                strtoupper($t),
                ['class' => 'tz-top-lang-btn'
                    . (strpos($joriy, $t) === 0 ? ' tz-on' : '')]);
        }
        echo html_writer::end_tag('span');
        echo html_writer::tag('span', s($bosh_harflar), ['class' => 'tz-top-avatar']);
        echo html_writer::tag('span', s($ism), ['class' => 'tz-top-name']);
        echo html_writer::link($chiqish,
            get_string('logout'), ['class' => 'tz-top-exit']);
        echo html_writer::end_div();

        // ------- Tab-navigatsiya (maket nav)
        echo html_writer::start_tag('nav', ['class' => 'tz-nav']);
        foreach (self::tablar() as $kalit => [$satr, $url]) {
            echo html_writer::link(new moodle_url($url),
                get_string($satr, 'local_targetzenit'),
                ['class' => 'tz-nav-tab' . ($kalit === $faol ? ' tz-on' : '')]);
        }
        // O'ngda — oylik (maketdagi shaxsiy burchak)
        echo html_writer::tag('span', '', ['class' => 'tz-top-sp']);
        echo html_writer::link(new moodle_url('/local/targetzenit/oylik.php'),
            get_string('mypayroll', 'local_targetzenit'),
            ['class' => 'tz-nav-tab' . ($faol === 'oylik' ? ' tz-on' : '')]);
        echo html_writer::end_tag('nav');

        echo html_writer::start_tag('main', ['class' => 'tz-main']);
    }

    /** Qobiq oxiri. */
    public static function tugat(): void {
        global $OUTPUT;
        echo html_writer::end_tag('main');
        echo $OUTPUT->footer();
    }
}
