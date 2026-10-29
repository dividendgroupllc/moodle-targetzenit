<?php
// Oylik ma'lumotini O'QISH qatlami.
//
// MAXFIYLIK QOIDASI: har bir o'qish faqat bitta `useridnumber` ga bog'lanadi.
// Xodim o'zining `user.idnumber` idan boshqa qiymat bilan hech qachon so'rov
// yubora olmaydi — sahifa `idnumber` ni URL dan OLMAYDI, balki tizimga kirgan
// foydalanuvchi profilidan oladi. Boshqa odamning oyligini ko'rish uchun
// `local/targetzenit:viewallpayroll` ruxsati talab qilinadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class payroll {

    /** Foydalanuvchining Frappe'dagi kaliti (Employee.name). */
    public static function ref_for(int $userid): string {
        global $DB;
        return (string) $DB->get_field('user', 'idnumber', ['id' => $userid, 'deleted' => 0]);
    }

    /**
     * Shu foydalanuvchida umuman oylik ma'lumoti bormi.
     *
     * O'Z oyligini ko'rish huquqi ANA SHU bilan boshqariladi — alohida ruxsat
     * bilan emas. Sabab: o'qituvchining roli kurs kontekstida, oylik sahifasi
     * esa tizim kontekstida; tizim darajasidagi ruxsat hech kimda yo'q edi.
     * O'quvchi va ota-onada oylik qatori bo'lmaydi, shuning uchun bu tekshiruv
     * maxfiylik jihatidan ham yetarli.
     */
    public static function has_any(int $userid): bool {
        global $DB;
        $ref = self::ref_for($userid);
        if ($ref === '') {
            return false;
        }
        return $DB->record_exists('local_targetzenit_payroll', ['useridnumber' => $ref]);
    }

    /** Shu xodim uchun ma'lumot bor oylar, yangisidan boshlab. */
    public static function months(string $ref): array {
        global $DB;
        if ($ref === '') {
            return [];
        }
        return $DB->get_records('local_targetzenit_payroll', ['useridnumber' => $ref],
            'yil DESC, oy DESC', 'id, yil, oy, holat, jami');
    }

    /** Bitta oy. $yil/$oy bo'sh bo'lsa — eng yangisi. */
    public static function month(string $ref, ?int $yil = null, ?int $oy = null): ?\stdClass {
        global $DB;
        if ($ref === '') {
            return null;
        }
        if ($yil && $oy) {
            $rec = $DB->get_record('local_targetzenit_payroll',
                ['useridnumber' => $ref, 'yil' => $yil, 'oy' => $oy]);
        } else {
            $rec = $DB->get_record_sql(
                "SELECT * FROM {local_targetzenit_payroll}
                  WHERE useridnumber = :r ORDER BY yil DESC, oy DESC",
                ['r' => $ref], IGNORE_MULTIPLE);
        }
        return $rec ?: null;
    }

    /** Kunlar ro'yxati: [{kun, koef, kirish, chiqish}] */
    public static function days(\stdClass $rec): array {
        if (empty($rec->kunlarjson)) {
            return [];
        }
        $d = json_decode($rec->kunlarjson, true);
        return is_array($d) ? $d : [];
    }

    /** Oy nomi — foydalanuvchining Moodle tilida. */
    public static function oy_nomi(int $oy): string {
        if ($oy < 1 || $oy > 12) {
            return (string) $oy;
        }
        return get_string('month_' . $oy, 'local_targetzenit');
    }

    /**
     * Summani o'qiladigan ko'rinishga keltiradi: 4 250 000 so'm
     *
     * UZS uchun belgi tarjima qilinadi ("so'm" / "сум" / "UZS"), boshqa
     * valyutada kod o'zi ko'rsatiladi.
     */
    public static function pul($summa, ?string $valyuta = null): string {
        $belgi = ($valyuta === null || $valyuta === 'UZS')
            ? get_string('pr_currency_uzs', 'local_targetzenit')
            : $valyuta;
        return number_format((float) $summa, 0, ',', ' ') . ' ' . $belgi;
    }

    /**
     * Barcha xodimlar ro'yxati (faqat `viewallpayroll` ruxsati bilan).
     * Moodle foydalanuvchisi bilan bog'langanini ham ko'rsatadi.
     */
    public static function all_for_month(int $yil, int $oy): array {
        global $DB;
        return $DB->get_records_sql("
            SELECT p.*, u.id AS moodleuserid
              FROM {local_targetzenit_payroll} p
         LEFT JOIN {user} u ON u.idnumber = p.useridnumber AND u.deleted = 0
             WHERE p.yil = :y AND p.oy = :o
          ORDER BY p.employeename", ['y' => $yil, 'o' => $oy]);
    }

    /** Ma'lumot bor barcha oylar (admin tanlovi uchun). */
    public static function all_months(): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT DISTINCT yil, oy FROM {local_targetzenit_payroll}
              ORDER BY yil DESC, oy DESC");
    }
}
