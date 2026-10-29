<?php
// O'qituvchi o'lchovi va KALIT KO'PRIGI.
//
// MUAMMO: o'qituvchi tizimda ikki xil kalit bilan yuradi —
//   `_timetable.teacherkey`  = Frappe Employee.name  (= Moodle user.idnumber)
//   `_lesson.teacherid`      = Moodle user.id
// Ya'ni "rejadagi o'qituvchi" va "haqiqatda yozgan odam" turli tipdagi
// kalitlar. Har so'rovda satr bo'yicha bog'lash sekin va mo'rt.
//
// YANA BIR MUAMMO: kalit mos kelmasligi JIM yiqiladi. Frappe'da `Instructor` ga
// `Employee` bog'langach kalit `INSTR-<ism>` dan `HR-EMP-xxxxx` ga o'zgaradi,
// jadval qatorlarida esa eski kalit qoladi — natijada o'qituvchining hamma
// darsi hisobotdan g'oyib bo'ladi va hech qanday xato chiqmaydi
// (`timetable::kalit_nomuvofiqmi()` dagi izohga qarang).
//
// Shu jadval ikkisini ham hal qiladi: bog'lanish BIR MARTA hisoblanadi va
// nomuvofiqlik `holat` ustunida KO'RINADIGAN xato bo'lib qoladi.
//
// QOIDA: bog'lanish faqat o'zgarmas unikal ID bilan. `teachername` hech qachon
// bog'lash uchun ishlatilmaydi — u faqat `legacykey` ogohlantirishini chiqarish
// uchun saqlanadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class staff {

    /** Moodle'da shu idnumber bilan foydalanuvchi topildi. */
    const OK = 'ok';
    /** Jadvalda kalit bor, Moodle'da bunday idnumber yo'q. */
    const USERLESS = 'userless';
    /**
     * `INSTR-<ism>` ko'rinishidagi kalit: Frappe'da `Instructor` ga `Employee`
     * bog'lanmagan.
     *
     * IKKI XIL SABAB bo'lishi mumkin va ularni ajratish MUHIM:
     *   1) VAKANSIYA — "Chess vakant", "TUTOR" kabi. O'qituvchi hali
     *      tayinlanmagan. Bu xato EMAS, lekin bu darslar hech kimning
     *      hisobotiga tushmaydi va panelda "o'qituvchi tayinlanmagan"
     *      bo'lib ko'rinishi kerak.
     *   2) TUZATISH KERAK — haqiqiy o'qituvchi, lekin `Employee` bog'lanmagan
     *      yoki jadval eski kalit bilan yuborilgan.
     *
     * Tizim ikkisini farqlay olmaydi (ikkisida ham bog'lanadigan ID yo'q),
     * shuning uchun ro'yxat ISM bilan ko'rsatiladi — qarorni odam qabul qiladi.
     */
    const LEGACY = 'legacykey';

    /** Eski kalitlar shu bilan boshlanadi. */
    const LEGACY_PREFIX = 'INSTR-';

    /**
     * Jadvaldan o'qituvchilar ro'yxatini qayta quradi.
     *
     * Jadval o'zgarganda qayta yurgiziladi (push'dan keyin yoki kechasi).
     * Idempotent: ikki marta yurgizilsa natija bir xil.
     */
    public static function rebuild(string $year): array {
        global $DB;

        if ($year === '') {
            return ['jami' => 0, 'ok' => 0, 'userless' => 0, 'legacykey' => 0, 'ochirildi' => 0];
        }

        // Jadvaldagi o'qituvchilar: kalit -> [ism, dars soni, fanlar]
        $rows = $DB->get_records_sql("
            SELECT teacherkey, teachername, subjectname, COUNT(1) AS soni
              FROM {local_targetzenit_timetable}
             WHERE academicyear = :y AND teacherkey IS NOT NULL AND teacherkey <> ''
          GROUP BY teacherkey, teachername, subjectname", ['y' => $year]);

        $kalitlar = [];
        foreach ($rows as $r) {
            $k = (string) $r->teacherkey;
            if (!isset($kalitlar[$k])) {
                $kalitlar[$k] = ['ism' => (string) $r->teachername, 'soni' => 0, 'fanlar' => []];
            }
            $kalitlar[$k]['soni'] += (int) $r->soni;
            if ($r->subjectname !== null && $r->subjectname !== '') {
                $kalitlar[$k]['fanlar'][(string) $r->subjectname] = true;
            }
            // Ism bo'sh kelgan qator bo'lsa, to'ldirilganini olamiz
            if ($kalitlar[$k]['ism'] === '' && $r->teachername) {
                $kalitlar[$k]['ism'] = (string) $r->teachername;
            }
        }

        if (!$kalitlar) {
            $ochirildi = $DB->count_records('local_targetzenit_staff', ['academicyear' => $year]);
            $DB->delete_records('local_targetzenit_staff', ['academicyear' => $year]);
            return ['jami' => 0, 'ok' => 0, 'userless' => 0, 'legacykey' => 0,
                    'ochirildi' => $ochirildi];
        }

        // Kalitlarni Moodle foydalanuvchilariga bog'laymiz — BITTA so'rovda.
        list($in, $params) = $DB->get_in_or_equal(array_keys($kalitlar), SQL_PARAMS_NAMED, 'idn');
        $params['del'] = 0;
        $userrows = $DB->get_records_select('user', "idnumber $in AND deleted = :del", $params,
            '', 'id, idnumber');
        $userlar = [];
        foreach ($userrows as $u) {
            $userlar[(string) $u->idnumber] = (int) $u->id;
        }

        $mavjud = $DB->get_records('local_targetzenit_staff', ['academicyear' => $year],
            '', 'id, teacherkey, userid, teachername, holat, lessonsweek, subjects');
        $mavjud_kalit = [];
        foreach ($mavjud as $m) {
            $mavjud_kalit[(string) $m->teacherkey] = $m;
        }

        $now = time();
        $hisob = [self::OK => 0, self::USERLESS => 0, self::LEGACY => 0];
        $yangilar = [];

        foreach ($kalitlar as $kalit => $info) {
            $userid = $userlar[$kalit] ?? null;
            if ($userid) {
                $holat = self::OK;
            } else if (strpos($kalit, self::LEGACY_PREFIX) === 0) {
                $holat = self::LEGACY;
            } else {
                $holat = self::USERLESS;
            }
            $hisob[$holat]++;

            $fanlar = \core_text::substr(implode(', ', array_keys($info['fanlar'])), 0, 255);
            $qator = (object) [
                'academicyear' => $year,
                'teacherkey' => $kalit,
                'userid' => $userid,
                'teachername' => \core_text::substr($info['ism'], 0, 255),
                'holat' => $holat,
                'lessonsweek' => $info['soni'],
                'subjects' => $fanlar,
                'timemodified' => $now,
            ];

            if (isset($mavjud_kalit[$kalit])) {
                $qator->id = $mavjud_kalit[$kalit]->id;
                $DB->update_record('local_targetzenit_staff', $qator);
                unset($mavjud_kalit[$kalit]);
            } else {
                $yangilar[] = $qator;
            }
        }

        if ($yangilar) {
            $DB->insert_records('local_targetzenit_staff', $yangilar);
        }

        // Jadvalda qolmagan kalitlarni olib tashlaymiz
        $ochirildi = 0;
        foreach ($mavjud_kalit as $eski) {
            $DB->delete_records('local_targetzenit_staff', ['id' => $eski->id]);
            $ochirildi++;
        }

        return [
            'jami' => count($kalitlar),
            'ok' => $hisob[self::OK],
            'userless' => $hisob[self::USERLESS],
            'legacykey' => $hisob[self::LEGACY],
            'ochirildi' => $ochirildi,
        ];
    }

    /** `teacherkey => userid` xaritasi (slot qurishda ishlatiladi). */
    public static function key_to_user(string $year): array {
        global $DB;
        $rows = $DB->get_records_select('local_targetzenit_staff',
            'academicyear = :y AND userid IS NOT NULL', ['y' => $year], '', 'teacherkey, userid');
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->teacherkey] = (int) $r->userid;
        }
        return $out;
    }

    /** Hamma o'qituvchi, ism bo'yicha. */
    public static function all(string $year): array {
        global $DB;
        return $DB->get_records('local_targetzenit_staff', ['academicyear' => $year],
            'teachername ASC');
    }

    /** Shu Moodle foydalanuvchisining o'qituvchi yozuvi. */
    public static function by_user(int $userid, string $year): ?\stdClass {
        global $DB;
        $rec = $DB->get_record('local_targetzenit_staff',
            ['academicyear' => $year, 'userid' => $userid]);
        return $rec ?: null;
    }

    /**
     * Bog'lanmagan kalitlar — panelda ma'lumot sog'lig'i ogohlantirishi.
     *
     * Bo'sh bo'lmasa, direktor panelining yuqorisida ko'rinadi: shu kalitlarning
     * darslari hech kimning hisobotiga tushmaydi.
     */
    public static function problems(string $year): array {
        global $DB;
        return $DB->get_records_select('local_targetzenit_staff',
            'academicyear = :y AND holat <> :ok', ['y' => $year, 'ok' => self::OK],
            'holat ASC, teachername ASC');
    }
}
