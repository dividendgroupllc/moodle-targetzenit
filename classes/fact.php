<?php
// REJA ↔ FAKT: har bir dars o'rni uchun "nima bo'lishi kerak edi" va
// "nima bo'ldi" ni taqqoslaydi.
//
// IDEMPOTENT QAYTA HISOBLASH: hamma narsa SANA ORALIG'I bo'yicha qayta
// hisoblanadi, "o'zgardi" bayroqlari (dirty flag) ishlatilmaydi.
//
// NEGA SHUNDAY: bayroq eskirsa yoki navbat tiqilib qolsa, hisobot JIM
// noto'g'ri bo'lib qoladi va buni hech kim sezmaydi. Oraliq bo'yicha qayta
// hisoblash esa har safar bir xil natija beradi va istalgan vaqtda qo'lda
// yurgizilishi mumkin:
//
//     php cli/fact_refresh.php --from=2026-09-01 --to=2026-10-29
//
// To'rtta mustaqil vazifa o'lchanadi — bitta "bajarildi" holati emas: mavzu,
// yo'qlama, baho, yozuv. O'qituvchi mavzuni yozib, baho qo'ymasligi mumkin
// va bu normal (baho normasi Frappe'dan keladi, `norm` sinfiga qarang).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class fact {

    /** Bitta qadamda shuncha kun (xotira va so'rov hajmi uchun). */
    const BLOK_KUN = 14;

    /** Faktga ko'chiriladigan maydonlar — yangilashda taqqoslash uchun. */
    const MAYDONLAR = ['lessonid', 'writeruserid', 'attributeduserid', 'substituted',
        'hastopic', 'hashomework', 'studentcount', 'attendancecount', 'gradecount',
        'absentcount', 'latecount', 'audiostatus', 'audioseconds', 'analysestatus',
        'analysedat', 'overallscore', 'normdelta', 'lowconfcount', 'lastwriteat',
        'latehours'];

    /**
     * Oraliqdagi faktlarni qayta hisoblaydi.
     *
     * @param string $from YYYY-MM-DD
     * @param string $to YYYY-MM-DD
     */
    public static function refresh(string $from, string $to, ?callable $xabar = null): array {
        global $DB;

        $natija = ['slotlar' => 0, 'qoshildi' => 0, 'yangilandi' => 0, 'tegilmadi' => 0];
        if ($from > $to) {
            return $natija;
        }

        $norma = ai::norm_overall();

        $blok_boshi = $from;
        while ($blok_boshi <= $to) {
            $blok_oxiri = min($to, date('Y-m-d',
                strtotime('+' . (self::BLOK_KUN - 1) . ' days', strtotime($blok_boshi))));

            $slotlar = slot::in_range($blok_boshi, $blok_oxiri);
            if ($slotlar) {
                $natija['slotlar'] += count($slotlar);
                self::blok($slotlar, $blok_boshi, $blok_oxiri, $norma, $natija);
            }

            if ($xabar) {
                $xabar("  {$blok_boshi} … {$blok_oxiri}: {$natija['slotlar']} slot, "
                    . "+{$natija['qoshildi']} / ~{$natija['yangilandi']}");
            }
            $blok_boshi = date('Y-m-d', strtotime('+1 day', strtotime($blok_oxiri)));
        }

        return $natija;
    }

    /** Bitta blokni ishlaydi. */
    protected static function blok(array $slotlar, string $from, string $to,
                                    int $norma, array &$natija): void {
        global $DB;

        // --- Shu oraliqdagi HAQIQATDA yozilgan darslar.
        // `_lesson` da sana indeksi bor (2026102920 yangilanishi) — aks holda
        // bu so'rov butun jadvalni skanerlardi.
        $darslar = [];
        $rows = $DB->get_records_select('local_targetzenit_lesson',
            'lessondate >= :a AND lessondate <= :b', ['a' => $from, 'b' => $to]);
        foreach ($rows as $l) {
            $darslar[$l->courseid . '#' . $l->lessondate . '#' . (int) $l->lessonno] = $l;
        }

        $lessonids = array_map(fn($l) => (int) $l->id, $rows);

        // --- Yo'qlama va baho yig'indilari (bitta so'rovda)
        $yoqlama = [];
        if ($lessonids) {
            list($in, $params) = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
            $agg = $DB->get_records_sql("
                SELECT lessonid,
                       COUNT(1) AS soni,
                       COUNT(grade) AS baholar,
                       SUM(CASE WHEN attendance = 'absent' THEN 1 ELSE 0 END) AS yoq,
                       SUM(CASE WHEN attendance = 'late' THEN 1 ELSE 0 END) AS kech
                  FROM {local_targetzenit_record}
                 WHERE lessonid $in
              GROUP BY lessonid", $params);
            foreach ($agg as $a) {
                $yoqlama[(int) $a->lessonid] = $a;
            }
        }

        // --- Audio yozuvlari
        $audiolar = [];
        if ($lessonids) {
            list($in, $params) = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
            $arows = $DB->get_records_select('local_targetzenit_audio', "lessonid $in", $params);
            foreach ($arows as $a) {
                $audiolar[(int) $a->lessonid] = $a;
            }
        }

        // --- Sinf ro'yxati hajmi (kurs bo'yicha)
        $courseids = [];
        foreach ($slotlar as $s) {
            if ($s->courseid) {
                $courseids[(int) $s->courseid] = true;
            }
        }
        $oquvchilar = self::student_counts(array_keys($courseids));

        // --- Mavjud fakt qatorlari
        $slotids = array_map(fn($s) => (int) $s->id, $slotlar);
        $mavjud = [];
        if ($slotids) {
            list($in, $params) = $DB->get_in_or_equal($slotids, SQL_PARAMS_NAMED);
            $frows = $DB->get_records_select('local_targetzenit_fact', "slotid $in", $params);
            foreach ($frows as $f) {
                $mavjud[(int) $f->slotid] = $f;
            }
        }

        $now = time();
        $qoshiladi = [];

        foreach ($slotlar as $s) {
            $slotid = (int) $s->id;
            $eski = $mavjud[$slotid] ?? null;

            $dars = null;
            if ($s->courseid) {
                $kalit = $s->courseid . '#' . $s->lessondate . '#' . (int) $s->lessonno;
                $dars = $darslar[$kalit] ?? null;
            }

            $f = (object) [
                'slotid' => $slotid,
                'lessonid' => null,
                'writeruserid' => null,
                'attributeduserid' => $s->teacheruserid ? (int) $s->teacheruserid : null,
                'substituted' => 0,
                'hastopic' => 0,
                'hashomework' => 0,
                'studentcount' => (int) ($oquvchilar[(int) $s->courseid] ?? 0),
                'attendancecount' => 0,
                'gradecount' => 0,
                'absentcount' => 0,
                'latecount' => 0,
                'audiostatus' => 'none',
                'audioseconds' => 0,
                'analysestatus' => 'none',
                'analysedat' => 0,
                'overallscore' => null,
                'normdelta' => null,
                'lowconfcount' => 0,
                'lastwriteat' => null,
                'latehours' => null,
                'feedbackcount' => $eski ? (int) $eski->feedbackcount : 0,
                'timemodified' => $now,
            ];

            if ($dars) {
                $lessonid = (int) $dars->id;
                $f->lessonid = $lessonid;
                $f->hastopic = trim((string) $dars->topic) !== '' ? 1 : 0;
                $f->hashomework = trim((string) $dars->homework) !== '' ? 1 : 0;
                $f->lastwriteat = (int) $dars->timemodified ?: null;
                $f->latehours = self::latehours($s, $f->lastwriteat);

                // HISOBOT KIMGA (qaror 2026-10-29): darsni O'TGANGA.
                // Yozgan odam yo'q bo'lsa — rejadagi o'qituvchiga, aks holda
                // "yozmadi" hodisasi hech kimga tegmay qoladi.
                $writer = (int) ($dars->teacherid ?? 0);
                if ($writer) {
                    $f->writeruserid = $writer;
                    $f->attributeduserid = $writer;
                    if ($s->teacheruserid && (int) $s->teacheruserid !== $writer) {
                        $f->substituted = 1;
                    }
                }

                if (isset($yoqlama[$lessonid])) {
                    $y = $yoqlama[$lessonid];
                    $f->attendancecount = (int) $y->soni;
                    $f->gradecount = (int) $y->baholar;
                    $f->absentcount = (int) $y->yoq;
                    $f->latecount = (int) $y->kech;
                }

                if (isset($audiolar[$lessonid])) {
                    $a = $audiolar[$lessonid];
                    $f->audiostatus = (string) $a->status;
                    $f->audioseconds = (int) $a->duration;
                    $f->analysedat = (int) $a->analysedat;

                    if (!empty($a->analysis)) {
                        $f->analysestatus = 'ok';
                        // JSON FAQAT KERAK BO'LGANDA o'qiladi: tahlil yangilangan
                        // yoki fakt hali ballni ko'rmagan bo'lsa. Aks holda har
                        // qayta hisoblashda 21 000 JSON dekod qilinardi.
                        if (!$eski || (int) $eski->analysedat !== (int) $a->analysedat
                                || $eski->overallscore === null) {
                            $ball = self::score_from_json((string) $a->analysis);
                            $f->overallscore = $ball['score'];
                            $f->lowconfcount = $ball['lowconf'];
                        } else {
                            $f->overallscore = $eski->overallscore === null
                                ? null : (int) $eski->overallscore;
                            $f->lowconfcount = (int) $eski->lowconfcount;
                        }
                        if ($f->overallscore !== null) {
                            $f->normdelta = $f->overallscore - $norma;
                        }
                    } else if (!empty($a->aierror)) {
                        // Yozuv bor, tahlil yiqilgan — TIZIM muammosi.
                        // Panelda alohida daraja: o'qituvchi buning uchun
                        // javob bermasligi kerak.
                        $f->analysestatus = 'failed';
                    } else if (in_array($a->status, audio::FAYLLI, true)) {
                        $f->analysestatus = 'pending';
                    }
                }
            }

            if (!$eski) {
                $qoshiladi[] = $f;
                continue;
            }

            $ozgardi = false;
            foreach (self::MAYDONLAR as $maydon) {
                if ((string) ($eski->$maydon ?? '') !== (string) ($f->$maydon ?? '')) {
                    $ozgardi = true;
                    break;
                }
            }
            if ($ozgardi) {
                $f->id = $eski->id;
                $DB->update_record('local_targetzenit_fact', $f);
                $natija['yangilandi']++;
            } else {
                $natija['tegilmadi']++;
            }
        }

        if ($qoshiladi) {
            $DB->insert_records('local_targetzenit_fact', $qoshiladi);
            $natija['qoshildi'] += count($qoshiladi);
        }
    }

    /**
     * Jurnal dars tugagandan keyin qancha soat o'tib to'ldirildi.
     *
     * DIQQAT: `_lesson.timecreated` dars KOKPITI ochilgan vaqt (qator shu
     * paytda yaratiladi), mavzu yozilgan vaqt emas. Shuning uchun oxirgi
     * yozilgan vaqt (`timemodified`) olinadi — bu "jurnal qachon to'ldirildi"
     * savoliga eng yaqin halol javob.
     */
    protected static function latehours(\stdClass $slot, ?int $yozilgan): ?int {
        if (!$yozilgan) {
            return null;
        }
        $tugash = $slot->endtime
            ? strtotime($slot->lessondate . ' ' . $slot->endtime)
            : strtotime($slot->lessondate . ' 23:59');
        if (!$tugash) {
            return null;
        }
        return max(0, (int) floor(($yozilgan - $tugash) / HOURSECS));
    }

    /** Tahlil JSON'idan umumiy ball va hisobga olinmagan kategoriyalar soni. */
    protected static function score_from_json(string $json): array {
        $d = json_decode($json, true);
        if (!is_array($d) || !isset($d['overall']) || !is_array($d['overall'])) {
            return ['score' => null, 'lowconf' => 0];
        }
        $ball = $d['overall']['score'] ?? null;
        return [
            'score' => ($ball === null || $ball === '') ? null : (int) $ball,
            'lowconf' => isset($d['overall']['chetda']) && is_array($d['overall']['chetda'])
                ? count($d['overall']['chetda']) : 0,
        ];
    }

    /**
     * Kurslardagi o'quvchilar soni — yo'qlama qamrovini hisoblash uchun.
     * Bitta so'rovda olinadi; `lesson::students()` ni kurs-kurs chaqirish
     * 21 000 slot uchun yaramaydi.
     */
    protected static function student_counts(array $courseids): array {
        global $DB;

        if (!$courseids) {
            return [];
        }
        list($in, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
        $params['ctxlevel'] = CONTEXT_COURSE;
        $params['student'] = 'student';
        $params['aktiv'] = 0;

        $rows = $DB->get_records_sql("
            SELECT c.id AS courseid, COUNT(DISTINCT ue.userid) AS soni
              FROM {course} c
              JOIN {enrol} e ON e.courseid = c.id
              JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = :aktiv
              JOIN {context} ctx ON ctx.contextlevel = :ctxlevel AND ctx.instanceid = c.id
              JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
              JOIN {role} r ON r.id = ra.roleid AND r.shortname = :student
             WHERE c.id $in
          GROUP BY c.id", $params);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->courseid] = (int) $r->soni;
        }
        return $out;
    }

    /**
     * Bitta darsning faktini darhol yangilash.
     *
     * Tahlil tugaganda chaqiriladi — direktor panelida ball 15 daqiqa kutmasin.
     * Slot topilmasa jim qaytadi (jadvaldan tashqari dars bo'lishi mumkin).
     */
    public static function touch_lesson(int $lessonid): void {
        global $DB;

        $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid],
            'id, lessondate');
        if (!$dars) {
            return;
        }
        self::refresh($dars->lessondate, $dars->lessondate);
    }
}
