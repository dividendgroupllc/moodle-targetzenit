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
