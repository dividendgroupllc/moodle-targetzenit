<?php
// AI KATEGORIYA BALLARI — JSON'dan chiqarilgan yassi indeks.
//
// MUAMMO: tahlil `_audio.analysis` da matn ustun bo'lib yotadi. "Bu
// o'qituvchining `feedback_quality` bo'yicha chorak trendi" yoki "maktabda
// eng kuchsiz domen qaysi" degan savolga JSON ustunidan SQL bilan javob
// olinmaydi (MySQL va Postgres JSON funksiyalari ham bir xil emas).
//
// YECHIM: tahlil saqlanayotgan paytda 15 ball shu jadvalga ham yoziladi.
// JSON YAGONA MANBA bo'lib qoladi — bu faqat indeks, shuning uchun istalgan
// vaqtda JSON'dan qayta qurilishi mumkin:
//
//     php cli/aicat_backfill.php

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class aicat {

    /**
     * Darsning kategoriya ballarini saqlaydi (bor bo'lsa almashtiradi).
     *
     * @param array $categories `ai::save()` dagi ko'rinish: code/score/confidence/weight
     * @return int yozilgan qatorlar soni
     */
    public static function store(int $lessonid, array $categories): int {
        global $DB;

        $qatorlar = [];
        $now = time();

        foreach ($categories as $c) {
            $kod = (string) ($c['code'] ?? '');
            // Noma'lum kodni saqlamaymiz: `ai::CATEGORIES` ramkasi o'zgarsa
            // jadvalda qayerdan kelgani tushunarsiz qatorlar qolib ketardi.
            if ($kod === '' || !isset(ai::CATEGORIES[$kod])) {
                continue;
            }
            $qatorlar[$kod] = (object) [
                'lessonid' => $lessonid,
                'code' => $kod,
                'score' => max(0, min(100, (int) ($c['score'] ?? 0))),
                'confidence' => \core_text::substr((string) ($c['confidence'] ?? ''), 0, 10),
                'weight' => (int) ($c['weight'] ?? ai::CATEGORIES[$kod]['vazn'] ?? 0),
                'timemodified' => $now,
            ];
        }

        // Almashtirish: qayta tahlil qilinganda eski ballar qolmasligi kerak
        $DB->delete_records('local_targetzenit_aicat', ['lessonid' => $lessonid]);
        if ($qatorlar) {
            $DB->insert_records('local_targetzenit_aicat', array_values($qatorlar));
        }
        return count($qatorlar);
    }

    /**
     * Mavjud tahlillardan indeksni qayta quradi.
     *
     * Idempotent: qayta yurgizilsa dublikat yaratmaydi (har dars uchun
     * avval o'chiriladi).
     */
    public static function backfill(?callable $xabar = null): array {
        global $DB;

        $rows = $DB->get_records_select('local_targetzenit_audio',
            "analysis IS NOT NULL AND analysis <> ''", [], 'lessonid ASC',
            'id, lessonid, analysis');

        $natija = ['darslar' => count($rows), 'ok' => 0, 'bosh' => 0, 'ballar' => 0];

        foreach ($rows as $r) {
            $d = json_decode((string) $r->analysis, true);
            if (!is_array($d) || empty($d['categories']) || !is_array($d['categories'])) {
                $natija['bosh']++;
                continue;
            }
            $natija['ballar'] += self::store((int) $r->lessonid, $d['categories']);
            $natija['ok']++;

            if ($xabar && $natija['ok'] % 50 === 0) {
                $xabar("  {$natija['ok']} / {$natija['darslar']}");
            }
        }

        return $natija;
    }

    /** Bitta darsning ballari: [code => record] */
    public static function for_lesson(int $lessonid): array {
        global $DB;
        $rows = $DB->get_records('local_targetzenit_aicat', ['lessonid' => $lessonid]);
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->code] = $r;
        }
        return $out;
    }

    /**
     * O'qituvchining kategoriya profili: har kod bo'yicha o'rtacha va namuna
     * hajmi.
     *
     * `confidence = low` ballar HISOBGA OLINMAYDI — audio orqali baholanmagan
     * narsa o'rtachani pasaytirmasligi kerak (`ai::overall()` bilan bir xil
     * qoida).
     *
     * @return array [code => ['avg' => float, 'n' => int]]
     */
    public static function profile(int $attributeduserid, string $from, string $to): array {
        global $DB;

        $rows = $DB->get_records_sql("
            SELECT ac.code,
                   AVG(ac.score) AS ortacha,
                   COUNT(1) AS soni
              FROM {local_targetzenit_aicat} ac
              JOIN {local_targetzenit_fact} f ON f.lessonid = ac.lessonid
              JOIN {local_targetzenit_slot} s ON s.id = f.slotid
             WHERE f.attributeduserid = :uid
               AND s.lessondate >= :a AND s.lessondate <= :b
               AND (ac.confidence IS NULL OR ac.confidence <> :low)
          GROUP BY ac.code", [
            'uid' => $attributeduserid, 'a' => $from, 'b' => $to, 'low' => 'low',
        ]);

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->code] = [
                'avg' => round((float) $r->ortacha, 1),
                'n' => (int) $r->soni,
            ];
        }
        return $out;
    }
}
