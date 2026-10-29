<?php
// Baholarni Moodle'ning STANDART gradebook'iga yozish.
//
// TAMOYIL (foydalanuvchi talabi): asosiy ish Moodle'da yuradi, bizning
// sahifalarimiz faqat qulaylik qatlami. Shuning uchun jurnalda qo'yilgan baho
// bir vaqtda gradebook'ga ham tushadi va o'quvchi uni Moodle'ning o'z
// "Grades" sahifasida, mobil ilovasida va hisobotlarida ko'radi.
//
// TUZILMA:
//   Kurs gradebook'i
//     └── Kategoriya: "1-chorak"      <- o'rtachani MOODLE hisoblaydi
//           ├── 01.09 · 1-dars · Mavzu
//           ├── 03.09 · 2-dars · Mavzu
//           └── ...
//
// Yo'qlama (k/s/kech) gradebook'ga YOZILMAYDI — Moodle yadrosida davomat
// tushunchasi yo'q. U bizning jadvalimizda qoladi va jurnalda ko'rinadi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
require_once($GLOBALS['CFG']->dirroot . '/lib/grade/grade_item.php');
require_once($GLOBALS['CFG']->dirroot . '/lib/grade/grade_category.php');

class gradebook {

    const SOURCE = 'local_targetzenit';

    /**
     * Gradebook xatosini KO'RINADIGAN qilib yozib qo'yadi.
     *
     * NEGA: baho saqlanishini gradebook muammosi to'smasligi kerak, shuning
     * uchun chaqiruvchi joylarda try/catch bor. Ammo avval xato faqat
     * `debugging()` ga ketardi va hech kim ko'rmasdi — baholar gradebook'ga
     * tushmay turgani bilinmay qoldi. Endi oxirgi xato saqlanadi va admin
     * sahifasida qizil ogohlantirish bo'lib chiqadi.
     */
    public static function xato_yoz(\Throwable $e, string $kontekst = ''): void {
        set_config('gradebook_xato', ($kontekst ? "$kontekst: " : '')
            . get_class($e) . ' — ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'local_targetzenit');
        set_config('gradebook_xato_vaqt', time(), 'local_targetzenit');
        set_config('gradebook_xato_soni',
            (int) get_config('local_targetzenit', 'gradebook_xato_soni') + 1,
            'local_targetzenit');
        debugging('local_targetzenit: gradebook xatosi — ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    /** Oxirgi gradebook xatosi (bo'lmasa null). */
    public static function oxirgi_xato(): ?array {
        $xato = get_config('local_targetzenit', 'gradebook_xato');
        if (!$xato) {
            return null;
        }
        return [
            'matn' => $xato,
            'vaqt' => (int) get_config('local_targetzenit', 'gradebook_xato_vaqt'),
            'soni' => (int) get_config('local_targetzenit', 'gradebook_xato_soni'),
        ];
    }

    /** Xato jurnalini tozalash. */
    public static function xato_tozala(): void {
        unset_config('gradebook_xato', 'local_targetzenit');
        unset_config('gradebook_xato_vaqt', 'local_targetzenit');
        unset_config('gradebook_xato_soni', 'local_targetzenit');
    }

    /**
     * Kurs baholarini qayta hisoblash.
     *
     * NEGA: `update_final_grade()` baho ustunini `needsupdate` holatiga
     * qo'yadi. Shu holatda kategoriya/kurs yakuniy bahosi hisoblanmaydi va
     * mobil ilova ishlatadigan `gradereport_user_*` web-servislari bu
     * ustunlarni O'TKAZIB YUBORADI — ya'ni ilovada baho ko'rinmaydi.
     * Jurnalda bir saqlashda yuzlab katak o'tadi, shuning uchun bu kurs
     * bo'yicha BIR MARTA, saqlash oxirida chaqiriladi.
     */
    public static function regrade(int $courseid): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        grade_regrade_final_grades($courseid);
    }

    /** Shu chorak uchun baho kategoriyasi (bo'lmasa yaratadi). */
    public static function quarter_category(int $courseid, string $termname): ?int {
        global $DB;

        $mavjud = $DB->get_record('grade_categories',
            ['courseid' => $courseid, 'fullname' => $termname]);
        if ($mavjud) {
            return (int) $mavjud->id;
        }

        $cat = new \grade_category(['courseid' => $courseid, 'fullname' => $termname], false);
        // O'rtacha baho — oddiy o'rtacha (chorak bahosi shundan chiqadi)
        $cat->aggregation = GRADE_AGGREGATE_MEAN;
        $id = $cat->insert(self::SOURCE);
        return $id ? (int) $id : null;
    }

    /**
     * Shu dars uchun gradebook ustuni (bo'lmasa yaratadi, nomi o'zgargan
     * bo'lsa yangilaydi).
     */
    public static function lesson_item(\stdClass $dars, ?string $termname = null): ?\grade_item {
        global $DB;

        $nom = userdate(strtotime($dars->lessondate), '%d.%m') . ' · ' . $dars->lessonno . '-dars';
        if (!empty($dars->topic)) {
            $nom .= ' · ' . \core_text::substr($dars->topic, 0, 80);
        }

        $item = null;
        if (!empty($dars->gradeitemid)) {
            $item = \grade_item::fetch(['id' => $dars->gradeitemid]);
        }
        if (!$item) {
            $item = \grade_item::fetch([
                'courseid' => $dars->courseid,
                'itemtype' => 'manual',
                'idnumber' => 'tzlesson-' . $dars->id,
            ]);
        }

        if (!$item) {
            $params = [
                'courseid' => $dars->courseid,
                'itemtype' => 'manual',
                'itemname' => $nom,
                'idnumber' => 'tzlesson-' . $dars->id,
                'gradetype' => GRADE_TYPE_VALUE,
                'grademax' => lesson::MAX_GRADE,
                'grademin' => 0,
            ];
            if ($termname && ($catid = self::quarter_category((int) $dars->courseid, $termname))) {
                $params['categoryid'] = $catid;
            }
            $item = new \grade_item($params, false);
            if (!$item->insert(self::SOURCE)) {
                return null;
            }
            $DB->set_field('local_targetzenit_lesson', 'gradeitemid', $item->id, ['id' => $dars->id]);
        } else if ($item->itemname !== $nom) {
            $item->itemname = $nom;
            $item->update(self::SOURCE);
        }

        return $item;
    }

    /** Shu sana qaysi chorakka tushadi (baho kategoriyasi nomi uchun). */
    public static function term_name_for(string $date): ?string {
        $year = timetable::current_year();
        if (!$year) {
            return null;
        }
        foreach (timetable::terms($year) as $t) {
            if ($date >= $t->startdate && $date <= $t->enddate) {
                return $t->name;
            }
        }
        return null;
    }

    /**
     * Bitta bahoni gradebook'ga yozadi. $grade null bo'lsa baho o'chiriladi
     * (masalan o'qituvchi katakni bo'shatdi yoki "k" qo'ydi).
     */
    public static function push(\stdClass $dars, int $userid, $grade, ?string $termname = null): void {
        $termname = $termname ?? self::term_name_for($dars->lessondate);
        $item = self::lesson_item($dars, $termname);
        if (!$item) {
            return;
        }
        $item->update_final_grade($userid,
            ($grade === null || $grade === '') ? null : (float) $grade,
            self::SOURCE);
    }

    /**
     * Mavjud baholarni gradebook'ga ko'chirish (backfill).
     *
     * NEGA KERAK: `lesson::save_record()` gradebook'ga FAQAT baho o'zgarganda
     * yuboradi. Gradebook ulanishi qo'shilgunga qadar (0.8.0) kiritilgan
     * baholar esa u yerga hech qachon tushmagan. Bu vosita ularni bir martada
     * ko'chiradi va keyin ham istalgan vaqtda qayta yurgizish mumkin
     * (idempotent — bor bahoni qayta yozadi, dublikat yaratmaydi).
     *
     * @param int $courseid 0 = hamma kurs
     * @param callable|null $xabar har qadamda chaqiriladi (CLI uchun)
     */
    public static function backfill(int $courseid = 0, ?callable $xabar = null): array {
        global $DB;

        $shart = 'r.grade IS NOT NULL';
        $params = [];
        if ($courseid) {
            $shart .= ' AND l.courseid = :cid';
            $params['cid'] = $courseid;
        }

        $rows = $DB->get_records_sql("
            SELECT r.id, r.userid, r.grade, l.id AS lessonid
              FROM {local_targetzenit_record} r
              JOIN {local_targetzenit_lesson} l ON l.id = r.lessonid
             WHERE $shart
          ORDER BY l.courseid, l.lessondate, l.lessonno", $params);

        $natija = ['jami' => count($rows), 'ok' => 0, 'xato' => 0, 'xabarlar' => []];
        $darslar = [];

        foreach ($rows as $r) {
            if (!isset($darslar[$r->lessonid])) {
                $darslar[$r->lessonid] = $DB->get_record('local_targetzenit_lesson',
                    ['id' => $r->lessonid]);
            }
            $dars = $darslar[$r->lessonid];
            if (!$dars) {
                $natija['xato']++;
                continue;
            }

            try {
                self::push($dars, (int) $r->userid, $r->grade);
                $natija['ok']++;
            } catch (\Throwable $e) {
                $natija['xato']++;
                if (count($natija['xabarlar']) < 20) {
                    $natija['xabarlar'][] = "dars {$r->lessonid}, user {$r->userid}: "
                        . $e->getMessage();
                }
            }

            if ($xabar && ($natija['ok'] + $natija['xato']) % 100 === 0) {
                $xabar("  {$natija['ok']} / {$natija['jami']}");
            }
        }

        // Yakuniy baholar hisoblanmasa ilovada ko'rinmaydi — har kursni
        // bir marta qayta hisoblaymiz.
        $kurslar = [];
        foreach ($darslar as $d) {
            if ($d) {
                $kurslar[(int) $d->courseid] = true;
            }
        }
        foreach (array_keys($kurslar) as $cid) {
            try {
                self::regrade($cid);
            } catch (\Throwable $e) {
                self::xato_yoz($e, "regrade kurs $cid");
            }
        }
        $natija['kurslar'] = count($kurslar);

        return $natija;
    }

    /** Gradebook bilan holat: nechta ustun va baho bor. */
    public static function holat(): array {
        global $DB;
        return [
            'bizda_baho' => $DB->count_records_select('local_targetzenit_record',
                'grade IS NOT NULL'),
            'gradebook_ustun' => $DB->count_records_select('grade_items',
                $DB->sql_like('idnumber', ':p'), ['p' => 'tzlesson-%']),
            'gradebook_baho' => $DB->count_records_sql("
                SELECT COUNT(1) FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE " . $DB->sql_like('gi.idnumber', ':p') . "
                   AND gg.finalgrade IS NOT NULL", ['p' => 'tzlesson-%']),
        ];
    }

    /** Dars o'chirilsa yoki bekor qilinsa — ustunni ham olib tashlaymiz. */
    public static function remove_item(\stdClass $dars): void {
        global $DB;
        if (empty($dars->gradeitemid)) {
            return;
        }
        $item = \grade_item::fetch(['id' => $dars->gradeitemid]);
        if ($item) {
            $item->delete(self::SOURCE);
        }
        $DB->set_field('local_targetzenit_lesson', 'gradeitemid', null, ['id' => $dars->id]);
    }
}
