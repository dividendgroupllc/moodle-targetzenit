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
     * Yon panel + asosiy ustunni ochadi — DIREKTOR menyusi bilan.
     *
     * @param string $active qaysi band faol: darslar|reyting|yozuvlar|tutorlar
     * @param string $title sarlavha (direktor panelida — salomlashuv)
     * @param string $sub sarlavha ostidagi izoh
     * @param string $tools o'ng tomondagi vositalar (tayyor HTML)
     */
    public static function start(string $active, string $title,
                                  string $sub = '', string $tools = ''): void {
        self::start_menu($active, $title, $sub, $tools, self::dir_menu());
    }

    /**
     * Xuddi shu qobiq, lekin menyu CHAQIRUVCHIDAN keladi.
     *
     * NEGA KERAK: o'qituvchi paneli ham shu qobiqda ishlaydi (to'liq ekran,
     * ekranga moslashgan maket allaqachon shu yerda), lekin unga direktor
     * menyusini ko'rsatib bo'lmaydi — o'sha sahifalar `viewteachers` /
     * `viewanalysis` talab qiladi va havolalar boshi berk yo'l bo'lardi.
     *
     * @param array $menu [['lab' => string|null, 'items' => [[kod, nom, moodle_url], ...]], ...]
     */
    public static function start_menu(string $active, string $title, string $sub,
                                        string $tools, array $menu): void {
        global $SITE, $USER;

        echo \html_writer::start_div('tz-dir');
        echo \html_writer::start_div('tz-dir-shell');

        // ---------------------------------------------------- yon panel
        echo \html_writer::start_div('tz-dir-side');
        echo \html_writer::div(\html_writer::tag('span', '◧') . 'Edulytics', 'tz-dir-logo');
        echo \html_writer::link(new \moodle_url('/my/'),
            '← ' . format_string($SITE->shortname ?: $SITE->fullname),
            ['class' => 'tz-dir-back']);

        foreach ($menu as $guruh) {
            if (!empty($guruh['items']) && !empty($guruh['lab'])) {
                echo \html_writer::div($guruh['lab'], 'tz-dir-navlab');
            }
            foreach ($guruh['items'] ?? [] as [$kod, $nom, $url]) {
                self::item($active, $kod, $nom, $url);
            }
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

    /** Direktor/zavuch menyusi. */
    public static function dir_menu(): array {
        $ctx = \context_system::instance();
        $t = fn(string $k) => get_string($k, 'local_targetzenit');
        $u = fn(string $fayl, array $p = []) =>
            new \moodle_url('/local/targetzenit/' . $fayl, $p);

        $asosiy = [
            ['darslar', $t('dir_navlessons'), $u('direktor.php', ['view' => 'darslar'])],
            ['reyting', $t('dir_navranks'), $u('direktor.php', ['view' => 'reyting'])],
            ['yozuvlar', $t('recordings'), $u('yozuvlar.php')],
            ['oqituvchi', $t('oq_mypanel'), $u('oqituvchi.php')],
        ];
        $boshqaruv = [
            ['tutorlar', $t('dir_tutors'), $u('tutorlar.php')],
            ['jadval', $t('timetable'), $u('index.php')],
        ];
        if (has_capability('moodle/site:config', $ctx)) {
            $boshqaruv[] = ['sozlama', $t('dir_navsettings'),
                new \moodle_url('/admin/settings.php', ['section' => 'local_targetzenit'])];
        }

        return [
            ['lab' => $t('dir_navmain'), 'items' => $asosiy],
            ['lab' => $t('dir_navmanage'), 'items' => $boshqaruv],
        ];
    }

    /**
     * O'QITUVCHI menyusi.
     *
     * Faqat shu odam ochishi mumkin bo'lgan sahifalar. Maktab bo'limi esa
     * ruxsati borlarga (direktor o'z kartasini ochganda yo'l yo'qolmasligi
     * kerak) — ruxsati yo'q odamga u umuman chizilmaydi.
     *
     * @param int $userid kartasi ko'rilayotgan odam (o'zi bo'lmasa havolada saqlanadi)
     * @param bool $ozim o'z kartasimi
     */
    public static function oq_menu(int $userid, bool $ozim): array {
        $ctx = \context_system::instance();
        $t = fn(string $k) => get_string($k, 'local_targetzenit');
        $qosh = $ozim ? [] : ['userid' => $userid];
        $u = fn(string $fayl, array $p = []) =>
            new \moodle_url('/local/targetzenit/' . $fayl, $p);

        $mening = [
            ['hisob', $t('oq_tab_sum'), $u('oqituvchi.php', ['view' => 'hisob'] + $qosh)],
            ['yozuvlar', $t('recordings'), $u('oqituvchi.php', ['view' => 'yozuvlar'] + $qosh)],
        ];
        if ($ozim) {
            // Boshqa odamning kartasida bu havolalar O'ZIMIZGA olib borardi —
            // chalg'ituvchi, shuning uchun faqat o'z kartasida.
            $mening[] = ['jadval', $t('timetable'), $u('index.php')];
            if (\local_targetzenit\payroll::has_any((int) $userid)) {
                $mening[] = ['oylik', $t('mypayroll'), $u('oylik.php')];
            }
        }

        $menu = [['lab' => $t('oq_navmine'), 'items' => $mening]];

        if (has_capability('local/targetzenit:viewteachers', $ctx)) {
            $menu[] = ['lab' => $t('oq_navschool'), 'items' => [
                ['darslar', $t('dir_navlessons'), $u('direktor.php', ['view' => 'darslar'])],
                ['reyting', $t('dir_navranks'), $u('direktor.php', ['view' => 'reyting'])],
            ]];
        }
        return $menu;
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
