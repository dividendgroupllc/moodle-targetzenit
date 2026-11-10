<?php
// Tashlab ketilgan dars yozuvlarini yakunlash — HAR 10 DAQIQADA.
//
// NEGA ALOHIDA VA TEZ-TEZ: o'qituvchi "To'xtatish" ni bosmay oynani yopsa
// (yoki noutbuk uxlasa, brauzer yiqilsa), bo'laklar serverda qolib ketadi.
// Avval buni faqat kunlik `cleanup_audio` (03:30) yig'ardi — ya'ni dars
// ertalab yozilsa, yozuv kechgacha «recording» holatda turardi: fayl yo'q,
// AI tahlili yo'q, zavuch hech narsa ko'rmaydi. Endi eng ko'p ~40 daqiqada
// yakunlanadi.
//
// XAVFSIZLIK: 30 daqiqadan beri bo'lak kelmagan yozuv «tashlab ketilgan»
// deb hisoblanadi. Internet shunchadan uzoq yo'q bo'lsa brauzer hali
// yozayotgan bo'lishi mumkin — shunda `audio::chunk()` yozuvni qayta ochadi
// va kech kelgan bo'laklar tayyor fayl ustiga qo'shiladi (audio.php ga qara).

namespace local_targetzenit\task;

defined('MOODLE_INTERNAL') || die();

class finalize_audio extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('audio_finalize', 'local_targetzenit');
    }

    public function execute(): void {
        $y = \local_targetzenit\audio::finalize_stale();
        if ($y['yakunlandi'] || $y['tashlandi']) {
            mtrace("local_targetzenit: tashlab ketilgan yozuv — {$y['yakunlandi']} yakunlandi, "
                . "{$y['tashlandi']} bo'sh sessiya tozalandi");
        }
    }
}
