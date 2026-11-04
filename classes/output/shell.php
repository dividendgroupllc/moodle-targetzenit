<?php
// DIREKTOR PANELI QOBIG'I — yon panel + asosiy ustun.
//
// NEGA ALOHIDA SINF: qobiq to'rtta sahifada bir xil (darslar, reyting,
// yozuvlar, tutorlar). Har birida nusxalansa, menyuga yangi band qo'shilganda
// uchtasida esdan chiqadi va foydalanuvchi sahifadan sahifaga o'tganda
// navigatsiya o'zgarib turadi.
//
// `embedded` LAYOUT bilan ishlaydi: Moodle sarlavhasi, navigatsiyasi va
// drawer'lari chizilmaydi, maket butun `<body>` ni egallaydi. Shuning uchun
// Moodle'ga qaytish havolasini O'ZIMIZ beramiz — aks holda foydalanuvchi
// sahifada qamalib qoladi.

namespace local_targetzenit\output;

defined('MOODLE_INTERNAL') || die();

class shell {

    /**
     * Sahifani `embedded` rejimiga qo'yadi. `$OUTPUT->header()` dan OLDIN
     * chaqirilishi shart.
     */
    public static function setup(\moodle_page $page, string $title): void {
        $page->set_pagelayout('embedded');
        $page->set_title($title);
        $page->set_heading('');
    }

    /**
     * Yon panel + asosiy ustunni ochadi.
     *
     * @param string $active qaysi band faol: darslar|reyting|yozuvlar|tutorlar
     * @param string $title sarlavha (direktor panelida — salomlashuv)
     * @param string $sub sarlavha ostidagi izoh
     * @param string $tools o'ng tomondagi vositalar (tayyor HTML)
     */
    public static function start(string $active, string $title,
                                  string $sub = '', string $tools = ''): void {
        global $SITE, $USER;

        $ctx = \context_system::instance();
        $t = fn(string $k) => get_string($k, 'local_targetzenit');

        echo \html_writer::start_div('tz-dir');
        echo \html_writer::start_div('tz-dir-shell');

        // ---------------------------------------------------- yon panel
        echo \html_writer::start_div('tz-dir-side');
        echo \html_writer::div(\html_writer::tag('span', '◧') . 'Edulytics', 'tz-dir-logo');
        echo \html_writer::link(new \moodle_url('/my/'),
            '← ' . format_string($SITE->shortname ?: $SITE->fullname),
            ['class' => 'tz-dir-back']);

        echo \html_writer::div($t('dir_navmain'), 'tz-dir-navlab');
        self::item($active, 'darslar', $t('dir_navlessons'),
            new \moodle_url('/local/targetzenit/direktor.php', ['view' => 'darslar']));
        self::item($active, 'reyting', $t('dir_navranks'),
            new \moodle_url('/local/targetzenit/direktor.php', ['view' => 'reyting']));
        self::item($active, 'yozuvlar', $t('recordings'),
            new \moodle_url('/local/targetzenit/yozuvlar.php'));
        // O'qituvchi paneli — reyting "kim pastda" ga javob beradi, bu esa
        // "NEGA" ga: dars-dars ro'yxati va kategoriya profili.
        self::item($active, 'oqituvchi', $t('oq_mypanel'),
            new \moodle_url('/local/targetzenit/oqituvchi.php'));

        echo \html_writer::div($t('dir_navmanage'), 'tz-dir-navlab');
        self::item($active, 'tutorlar', $t('dir_tutors'),
            new \moodle_url('/local/targetzenit/tutorlar.php'));
        self::item($active, 'jadval', $t('timetable'),
            new \moodle_url('/local/targetzenit/index.php'));
        if (has_capability('moodle/site:config', $ctx)) {
            self::item($active, 'sozlama', $t('dir_navsettings'),
                new \moodle_url('/admin/settings.php', ['section' => 'local_targetzenit']));
        }

        echo \html_writer::div(
            \html_writer::div(s(fullname($USER)), 'tz-dir-username')
            . \html_writer::link(new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]),
                get_string('logout'), ['class' => 'tz-dir-logout']),
            'tz-dir-user');
        echo \html_writer::end_div();

        // ------------------------------------------------ asosiy ustun
        echo \html_writer::start_div('tz-dir-main');

        echo \html_writer::start_div('tz-dir-top');
        echo \html_writer::div(
            \html_writer::div(format_string($SITE->fullname), 'tz-dir-school')
            . \html_writer::tag('h2', $title, ['class' => 'tz-dir-hello'])
            . ($sub !== '' ? \html_writer::div($sub, 'tz-dir-sub') : ''));
        if ($tools !== '') {
            echo \html_writer::div($tools, 'tz-dir-tools');
        }
        echo \html_writer::end_div();
    }

    protected static function item(string $active, string $kod, string $nom,
                                    \moodle_url $url): void {
        echo \html_writer::link($url, $nom,
            ['class' => 'tz-dir-navitem' . ($active === $kod ? ' tz-on' : '')]);
    }

    /** Asosiy ustun va qobiqni yopadi. */
    public static function end(): void {
        echo \html_writer::end_div();   // .tz-dir-main
        echo \html_writer::end_div();   // .tz-dir-shell
        echo \html_writer::end_div();   // .tz-dir
    }
}
