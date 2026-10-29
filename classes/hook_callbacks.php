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
        if (!isloggedin() || isguestuser()) {
            return;
        }
        $nav = $hook->get_primaryview();
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
}
