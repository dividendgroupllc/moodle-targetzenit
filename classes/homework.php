<?php
// Uy vazifa -> Moodle'ning STANDART "Assignment" (mod_assign) faoliyati.
//
// TAMOYIL: o'quvchi uy vazifani bizning sahifamizdan emas, Moodle'ning o'z
// joylaridan ko'radi — kurs sahifasi, boshqaruv panelidagi "Timeline",
// kalendar, mobil ilova va eslatmalar. Topshirish va baholash ham standart.
//
// HAJM CHEGARASI: topshiriq FAQAT o'qituvchi haqiqatan uy vazifa yozgan
// darslar uchun yaratiladi. Bo'sh darslar uchun hech narsa yaratilmaydi —
// aks holda 307 kurs x ~70 dars = 20 000 dan ortiq faoliyat paydo bo'lardi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class homework {

    /**
     * Shu dars uchun topshiriqni ta'minlaydi: bo'lmasa yaratadi, bo'lsa
     * matnini yangilaydi. Uy vazifa bo'sh bo'lsa hech narsa qilmaydi.
     */
    public static function ensure(\stdClass $dars): ?int {
        global $CFG, $DB;

        $vazifa = trim((string) ($dars->homework ?? ''));
        if ($vazifa === '') {
            return null;
        }

        $nom = 'Uy vazifa — ' . userdate(strtotime($dars->lessondate), '%d.%m');
        if (!empty($dars->topic)) {
            $nom .= ' · ' . \core_text::substr($dars->topic, 0, 60);
        }
        $muddat = self::due_date($dars);

        // --- Mavjud bo'lsa yangilaymiz
        if (!empty($dars->assigncmid)) {
            $cm = $DB->get_record('course_modules', ['id' => $dars->assigncmid]);
            if ($cm) {
                $DB->update_record('assign', (object) [
                    'id' => $cm->instance,
                    'name' => $nom,
                    'intro' => text_to_html($vazifa),
                    'duedate' => $muddat,
                    'timemodified' => time(),
                ]);
                rebuild_course_cache((int) $dars->courseid, true);
                return (int) $dars->assigncmid;
            }
        }

        // --- Yangisini yaratamiz
        require_once($CFG->dirroot . '/course/modlib.php');

        $info = new \stdClass();
        $info->modulename = 'assign';
        $info->course = (int) $dars->courseid;
        $info->section = 0;
        $info->visible = 1;
        $info->name = $nom;
        $info->intro = text_to_html($vazifa);
        $info->introformat = FORMAT_HTML;

        // mod_assign talab qiladigan maydonlar
        $info->duedate = $muddat;
        $info->allowsubmissionsfromdate = strtotime($dars->lessondate . ' 00:00');
        $info->cutoffdate = 0;
        $info->gradingduedate = 0;
        $info->timelimit = 0;
        $info->grade = lesson::MAX_GRADE;
        $info->submissiondrafts = 0;
        $info->requiresubmissionstatement = 0;
        $info->sendnotifications = 0;
        $info->sendlatenotifications = 0;
        $info->sendstudentnotifications = 1;
        $info->teamsubmission = 0;
        $info->requireallteammemberssubmit = 0;
        $info->teamsubmissiongroupingid = 0;
        $info->blindmarking = 0;
        $info->hidegrader = 0;
        $info->markingworkflow = 0;
        $info->markingallocation = 0;
        $info->markinganonymous = 0;
        $info->attemptreopenmethod = 'none';
        $info->maxattempts = -1;
        $info->completionsubmit = 0;
        $info->preventsubmissionnotingroup = 0;
        $info->activityeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0];

        // Topshirish usullari: matn va fayl
        $info->assignsubmission_onlinetext_enabled = 1;
        $info->assignsubmission_onlinetext_wordlimitenabled = 0;
        $info->assignsubmission_onlinetext_wordlimit = 0;
        $info->assignsubmission_file_enabled = 1;
        $info->assignsubmission_file_maxfiles = 3;
        $info->assignsubmission_file_maxsizebytes = 0;
        $info->assignsubmission_file_filetypes = '';
        $info->assignsubmission_comments_enabled = 0;
        $info->assignfeedback_comments_enabled = 1;
        $info->assignfeedback_comments_commentinline = 0;
        $info->assignfeedback_file_enabled = 0;
        $info->assignfeedback_editpdf_enabled = 0;

        try {
            $yaratildi = create_module($info);
        } catch (\Throwable $e) {
            // Topshiriq yaratilmasa ham dars saqlanishi kerak — faqat qayd qilamiz
            debugging('local_targetzenit: topshiriq yaratilmadi — ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }

        $DB->set_field('local_targetzenit_lesson', 'assigncmid', $yaratildi->coursemodule,
            ['id' => $dars->id]);
        return (int) $yaratildi->coursemodule;
    }

    /**
     * Topshirish muddati — KEYINGI dars kuni (uy vazifa shunga beriladi).
     * Jadvaldan topilmasa +7 kun.
     */
    protected static function due_date(\stdClass $dars): int {
        global $DB;

        $kurs = $DB->get_record('course', ['id' => $dars->courseid], 'id, idnumber');
        if ($kurs && $kurs->idnumber) {
            $kunlar = $DB->get_fieldset_select('local_targetzenit_timetable', 'DISTINCT dayno',
                'courseidnumber = :c', ['c' => $kurs->idnumber]);
            if ($kunlar) {
                $kunlar = array_map('intval', $kunlar);
                $sana = strtotime($dars->lessondate);
                for ($i = 1; $i <= 14; $i++) {
                    $keyingi = strtotime("+{$i} days", $sana);
                    if (in_array((int) date('N', $keyingi), $kunlar, true)) {
                        return strtotime(date('Y-m-d', $keyingi) . ' 23:59');
                    }
                }
            }
        }
        return strtotime($dars->lessondate . ' +7 days 23:59');
    }
}
