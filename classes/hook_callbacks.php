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
        $hook->get_primaryview()->add(
            get_string('timetable', 'local_targetzenit'),
            '/local/targetzenit/index.php',
            \navigation_node::TYPE_CUSTOM,
            null,
            'tztimetable'
        );
    }
}
