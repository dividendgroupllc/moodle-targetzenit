<?php
// Ilgak chaqiruvlari.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class hook_callbacks {

    /**
     * Asosiy (yuqori) menyuga "Dars jadvali" havolasi.
     * Mehmon va kirmagan foydalanuvchiga ko'rsatilmaydi — sahifa baribir
     * login talab qiladi, havola esa bekorga chalkashtirmasin.
     */
    public static function primary_extend(\core\hook\navigation\primary_extend $hook): void {
        // DIQQAT: `global $USER` SHART. Pastda `$USER->id` ishlatiladi va u
        // e'lon qilinmagani uchun "Mening oyligim" yuqori menyuda hech qachon
        // chiqmagan (chap menyudagi nusxasi ishlagani uchun sezilmagan).
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }
        $nav = $hook->get_primaryview();

        // DIREKTOR PANELI — birinchi o'rinda: ruxsati bor odam uchun kundalik
        // kirish nuqtasi aynan shu, jadval emas.
        //
        // NEGA CHAP MENYU EMAS: Boost temasi Dashboard sahifasida chap
        // menyuni UMUMAN ko'rsatmaydi, shuning uchun `lib.php` dagi
        // `extend_navigation` orqali qo'shilgan havola direktorga ko'rinmadi
        // (2026-10-29). Yuqori menyu esa har sahifada turadi.
        if (has_capability('local/targetzenit:viewteachers', \context_system::instance())) {
            $nav->add(
                get_string('dir_panel', 'local_targetzenit'),
                '/local/targetzenit/direktor.php',
                \navigation_node::TYPE_CUSTOM,
                null,
                'tzdirektor'
            );
        }

        // "MENING DARSLARIM" — o'qituvchining kundalik kirish nuqtasi.
        //
        // NEGA YUQORIDA: `lib.php` dagi `extend_navigation` nusxasi CHAP
        // menyuga tushadi, Boost esa chap menyuni hamma sahifada
        // ko'rsatmaydi — natijada havola faqat kurs/dars sahifasiga
        // kirgandan keyin paydo bo'lardi. Yuqori menyu har sahifada turadi.
        //
        // NEGA JADVALDAN OLDIN: Boost sig'magan elementlarni "More"
        // ro'yxatiga yashiradi; ro'yxatning boshida turgan element
        // yashirinmaydi. O'qituvchi uchun eng ko'p ochiladigani shu.
        //
        // Shart: shu odamga YOZILGAN darsi bor. O'quvchi, ota-ona va dars
        // o'tmaydigan xodimda bunday yozuv bo'lmaydi, ya'ni havola ham
        // chiqmaydi.
        if (teacher::has_any((int) $USER->id)) {
            $nav->add(
                get_string('oq_mypanel', 'local_targetzenit'),
                '/local/targetzenit/oqituvchi.php',
                \navigation_node::TYPE_CUSTOM,
                null,
                'tzoqituvchi'
            );
        }

        $nav->add(
            get_string('timetable', 'local_targetzenit'),
            '/local/targetzenit/index.php',
            \navigation_node::TYPE_CUSTOM,
            null,
            'tztimetable'
        );
        $nav->add(
            get_string('mygrades', 'local_targetzenit'),
            '/local/targetzenit/baholarim.php',
            \navigation_node::TYPE_CUSTOM,
            null,
            'tzgrades'
        );

        // "O'qituvchilar sharhi" — o'quvchining (ota-onaning) sahifasi.
        //
        // Shart: shu odam haqida OCHIQ sharh bor. Xodimda bunday qator
        // bo'lmaydi, shuning uchun havola unda chiqmaydi. Baholardan KEYIN
        // turadi: ota-ona avval bahoni ko'radi, keyin izohni o'qiydi.
        if (sharh::has_any((int) $USER->id)) {
            $nav->add(
                get_string('sh_mine', 'local_targetzenit'),
                '/local/targetzenit/sharhlarim.php',
                \navigation_node::TYPE_CUSTOM,
                null,
                'tzsharhlarim'
            );
        }

        // "Mening oyligim" — faqat oylik ma'lumoti BOR odamga. O'quvchi va
        // ota-onada bunday qator bo'lmaydi, shuning uchun havola ham chiqmaydi.
        if (payroll::has_any((int) $USER->id)) {
            $nav->add(
                get_string('mypayroll', 'local_targetzenit'),
                '/local/targetzenit/oylik.php',
                \navigation_node::TYPE_CUSTOM,
                null,
                'tzpayroll'
            );
        }
    }

    /**
     * Avatar menyusiga (yuqori o'ngda) "Mening oyligim".
     *
     * NEGA ENG MUHIM JOY: odam shaxsiy narsani — profil, baholar, sozlamalar —
     * aynan shu ro'yxatdan qidiradi. Yuqori (primary) menyuda havola Boost
     * temasining "More" ro'yxatiga yashirinib qolardi, chap menyuda esa
     * foydalanuvchi uni topa olmadi.
     *
     * Shart: oylik ma'lumoti BOR odam. O'quvchi va ota-onada bunday qator
     * bo'lmaydi, shuning uchun element ularda umuman chiqmaydi — `Moodle`ning
     * `customusermenuitems` sozlamasidan afzalligi ham shunda: u hammaga
     * ko'rinardi.
     */
    public static function extend_user_menu(\core_user\hook\extend_user_menu $hook): void {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }
        if (!payroll::has_any((int) $USER->id)) {
            return;
        }

        $hook->add_menu_item(new \core_user\output\user_action_menu\link(
            new \core\url('/local/targetzenit/oylik.php'),
            get_string('mypayroll', 'local_targetzenit'),
            null,
            new \core\output\pix_icon('i/payment', '')
        ));
    }
}
