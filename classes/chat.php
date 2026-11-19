<?php
// MULOQOT (chat) — Moodle message conversations API ustida yupqa qatlam.
//
// QAROR: xabarlar, o'qilganlik, push, mobil ilova — YADRODAN bepul keladi
// (\core_message\api). Plagin faqat boshqaruv qatlamini qo'shadi (_chat):
// egasi, biriktirilgan sinf guruhi (avto-taklif), yozish siyosati, ko'rinish.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

use core_message\api;

class chat {

    const TURLAR = ['sinf_otaona', 'kafedra', 'erkin'];

    /**
     * Guruh chat yaratadi: core conversation + _chat yozuvi, a'zolarni qo'shadi.
     *
     * @param string $nom chat nomi
     * @param array $userids boshlang'ich a'zolar (ega ham qo'shiladi)
     * @param int $courseid biriktirilgan sinf kursi (avto-taklif); 0 = erkin
     * @return int _chat.id
     */
    public static function guruh_yarat(string $nom, array $userids, int $courseid = 0,
                                        string $turi = 'erkin'): int {
        global $DB, $USER;

        $userids = array_values(array_unique(array_map('intval',
            array_merge([(int) $USER->id], $userids))));
        $nom = \core_text::substr(trim($nom), 0, 255);
        if ($nom === '') {
            $nom = get_string('chat_default', 'local_targetzenit');
        }

        $conv = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP, $userids, $nom,
            api::MESSAGE_CONVERSATION_ENABLED,
            'local_targetzenit', 'chat', $courseid ?: null,
            \context_system::instance()->id);

        $id = $DB->insert_record('local_targetzenit_chat', (object) [
            'conversationid' => (int) $conv->id,
            'egauserid' => (int) $USER->id,
            'courseid' => $courseid,
            'turi' => in_array($turi, self::TURLAR, true) ? $turi : 'erkin',
            'yozish' => 'hamma',
            'korinish' => 'azolar',
            'timecreated' => time(),
        ]);
        return (int) $id;
    }

    /** _chat yozuvi conversation bo'yicha (bo'lmasa null). */
    public static function by_conversation(int $convid): ?\stdClass {
        global $DB;
        $r = $DB->get_record('local_targetzenit_chat', ['conversationid' => $convid]);
        return $r ?: null;
    }

    /**
     * Foydalanuvchining suhbatlari (guruh + shaxsiy), eng yangisi yuqorida.
     * Yadro get_conversations qaytaradi: id, name, members, messages, ...
     */
    public static function mening_chatlarim(int $userid, int $limit = 50): array {
        return api::get_conversations($userid, 0, $limit);
    }

    /** Suhbat xabarlari (eskisidan yangisiga). */
    public static function xabarlar(int $userid, int $convid, int $limit = 100): array {
        $r = api::get_conversation_messages($userid, $convid, 0, $limit, 'timecreated ASC');
        return $r['messages'] ?? [];
    }

    /** Xabar yuboradi (yozish siyosati tekshiriladi). */
    public static function yubor(int $userid, int $convid, string $matn): bool {
        $matn = trim($matn);
        if ($matn === '') {
            return false;
        }
        $chat = self::by_conversation($convid);
        // «faqat_ega» siyosati: faqat ega yoza oladi (e'lon rejimi).
        if ($chat && $chat->yozish === 'faqat_ega' && (int) $chat->egauserid !== $userid) {
            return false;
        }
        // Moodle maxfiylik to'sig'i (shaxsiy suhbatda qabul qiluvchi sozlamasi):
        // yubora olmasak, yadro istisno tashlab butun sahifani yiqitmasin uchun
        // oldindan tekshiramiz va tushunarli «false» qaytaramiz.
        if (!api::can_send_message_to_conversation($userid, $convid)) {
            return false;
        }
        api::send_message_to_conversation($userid, $convid, $matn, FORMAT_PLAIN);
        return true;
    }

    /** Suhbatga a'zo qo'shadi (faqat ega). */
    public static function azo_qosh(int $convid, array $userids): void {
        $userids = array_values(array_filter(array_map('intval', $userids)));
        if ($userids) {
            api::add_members_to_conversation($userids, $convid);
        }
    }

    /**
     * Sinf guruhiga biriktirilgan chatlar uchun: kursning o'quvchilarini
     * (ota-ona = farzand akkaunti) suhbatga sinxronlaydi — yangi o'quvchi
     * qo'shilsa avtomatik taklif qilinadi (maket qarori).
     */
    public static function sinf_sinxron(\stdClass $chat): int {
        global $DB;

        if ((int) $chat->courseid <= 0) {
            return 0;
        }
        $role = $DB->get_record('role', ['shortname' => 'student'], 'id');
        $ctx = \context_course::instance((int) $chat->courseid, IGNORE_MISSING);
        if (!$role || !$ctx) {
            return 0;
        }
        $kerak = array_keys(get_role_users($role->id, $ctx, false, 'u.id'));
        $bor = $DB->get_fieldset_select('message_conversation_members', 'userid',
            'conversationid = :c', ['c' => (int) $chat->conversationid]);
        $qosh = array_diff($kerak, $bor);
        if ($qosh) {
            self::azo_qosh((int) $chat->conversationid, $qosh);
        }
        return count($qosh);
    }

    /**
     * SHAXSIY (1:1) suhbat: bor bo'lsa topadi, yo'q bo'lsa yaratadi.
     * @return int conversation id
     */
    public static function shaxsiy(int $otherid): int {
        global $USER;
        $me = (int) $USER->id;
        if ($otherid === $me) {
            return 0;
        }
        $convid = api::get_conversation_between_users([$me, $otherid]);
        if (!$convid) {
            $conv = api::create_conversation(
                api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [$me, $otherid]);
            $convid = $conv->id;
        }
        // Yadro maxfiylik to'sig'i (qabul qiluvchi «faqat kontaktlar» yoki umumiy
        // kurs yo'q) — Moodle o'zi buni KONTAKT orqali ochadi. O'qituvchi yozishni
        // o'zi boshlagani uchun ikkovini kontakt qilamiz (sof core api), shunda
        // send_message_to_conversation to'g'ridan-to'g'ri yuboradi.
        if (!api::is_contact($me, $otherid)) {
            api::add_contact($me, $otherid);
        }
        return (int) $convid;
    }

    /**
     * Shaxsiy xabar uchun odamlar: o'qituvchining o'z guruhlari o'quvchilari
     * (ota-ona = farzand akkaunti) + hamkasb o'qituvchilar. [userid => nom].
     */
    public static function odamlar(int $userid, string $year): array {
        global $DB;

        $out = [];
        // 1) O'z guruhlari o'quvchilari
        foreach (journal::my_courses($userid, $year) as $cid => $kurs) {
            $ctx = \context_course::instance((int) $cid, IGNORE_MISSING);
            if (!$ctx) {
                continue;
            }
            foreach (lesson::students($ctx) as $s) {
                $out[(int) $s->id] = fullname($s) . ' · '
                    . get_string('student', 'local_targetzenit');
            }
        }
        // 2) Hamkasb o'qituvchilar (teacherkey bor foydalanuvchilar)
        $staff = $DB->get_records_sql("
            SELECT st.userid, u.firstname, u.lastname
              FROM {local_targetzenit_staff} st
              JOIN {user} u ON u.id = st.userid
             WHERE st.userid IS NOT NULL AND st.userid <> :me", ['me' => $userid]);
        foreach ($staff as $t) {
            $out[(int) $t->userid] = fullname($t) . ' · '
                . get_string('teacher', 'local_targetzenit');
        }
        asort($out);
        return $out;
    }

    /** Foydalanuvchi shu suhbat a'zosimi. */
    public static function azomi(int $userid, int $convid): bool {
        global $DB;
        return $DB->record_exists('message_conversation_members',
            ['conversationid' => $convid, 'userid' => $userid]);
    }

    /** Foydalanuvchi shu chatning egasimi (boshqaruvchi). */
    public static function ega_mi(int $convid, int $userid): bool {
        $chat = self::by_conversation($convid);
        return $chat && (int) $chat->egauserid === $userid;
    }

    /**
     * Suhbat a'zolari: [userid => fullname], o'zim tashqari, ism bo'yicha.
     */
    public static function azolar_royxati(int $convid): array {
        global $DB, $USER;
        $rows = $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname
              FROM {message_conversation_members} m
              JOIN {user} u ON u.id = m.userid
             WHERE m.conversationid = :c AND u.id <> :me AND u.deleted = 0",
            ['c' => $convid, 'me' => (int) $USER->id]);
        $out = [];
        foreach ($rows as $u) {
            $out[(int) $u->id] = fullname($u);
        }
        asort($out);
        return $out;
    }

    /** Suhbatdan a'zo chiqaradi (faqat guruh; faqat ega chaqiradi). */
    public static function azo_chiqar(int $convid, array $userids): void {
        $userids = array_values(array_filter(array_map('intval', $userids)));
        if ($userids) {
            api::remove_members_from_conversation($userids, $convid);
        }
    }

    /** Yozish siyosatini o'rnatadi (hamma|faqat_ega). */
    public static function yozish_sozla(int $convid, string $yozish): void {
        global $DB;
        $yozish = in_array($yozish, ['hamma', 'faqat_ega'], true) ? $yozish : 'hamma';
        $chat = self::by_conversation($convid);
        if ($chat) {
            $DB->set_field('local_targetzenit_chat', 'yozish', $yozish, ['id' => $chat->id]);
        }
    }

    // ============================================================ UCHRASHUV

    /**
     * Uchrashuv taklifi yaratadi: _uchrashuv yozuvi + suhbatga karta xabari
     * (push/mobil bepul). @return uchrashuv id.
     */
    public static function uchrashuv_yarat(int $convid, int $userid, int $sana,
                                           string $joy, string $izoh): int {
        global $DB;
        $id = $DB->insert_record('local_targetzenit_uchrashuv', (object) [
            'conversationid' => $convid,
            'taklifuserid' => $userid,
            'sana' => $sana,
            'joy' => \core_text::substr(trim($joy), 0, 255) ?: null,
            'izoh' => trim($izoh) !== '' ? trim($izoh) : null,
            'holat' => 'kutilmoqda',
            'timecreated' => time(),
        ]);
        // Suhbatga ko'rinadigan xabar — a'zolar va mobil ilova xabardor bo'ladi.
        $vaqt = userdate($sana, get_string('strftimedaydatetime', 'langconfig'));
        $matn = '📅 ' . get_string('uchr_taklif', 'local_targetzenit') . ': ' . $vaqt
            . ($joy !== '' ? ' · ' . $joy : '');
        if (api::can_send_message_to_conversation($userid, $convid)) {
            api::send_message_to_conversation($userid, $convid, $matn, FORMAT_PLAIN);
        }
        return (int) $id;
    }

    /** Suhbatning uchrashuvlari (yangi oldinda). */
    public static function uchrashuvlar(int $convid): array {
        global $DB;
        return $DB->get_records('local_targetzenit_uchrashuv',
            ['conversationid' => $convid], 'sana DESC');
    }

    /** Uchrashuv holatini o'zgartiradi (tasdiq|bekor|kutilmoqda). */
    public static function uchrashuv_holat(int $uchrid, string $holat): void {
        global $DB;
        if (in_array($holat, ['kutilmoqda', 'tasdiq', 'bekor'], true)) {
            $DB->set_field('local_targetzenit_uchrashuv', 'holat', $holat, ['id' => $uchrid]);
        }
    }
}
