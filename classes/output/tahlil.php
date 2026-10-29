<?php
// AI dars tahlilini chizish — YAGONA joy.
//
// NEGA ALOHIDA SINF: xuddi shu tahlil ikki joyda ko'rinadi —
//   dars.php          o'qituvchining dars kokpitida (tahrir bilan)
//   dars_tahlil.php   direktor/zavuch uchun FAQAT O'QISH rejimida
// Kod nusxalansa, tahlil ramkasi o'zgarganda (allaqachon ikki marta
// o'zgargan: ha/yo'q -> foiz -> 15 kategoriya) ikki joyni yangilash
// esdan chiqadi va ikki sahifa boshqa-boshqa narsa ko'rsatadi.

namespace local_targetzenit\output;

defined('MOODLE_INTERNAL') || die();

class tahlil {

    /**
     * Tahlil kartasini qaytaradi (HTML).
     *
     * Tahlil bo'lmasa — holatiga qarab "kutilmoqda" yoki "hozir tahlil qilish"
     * kartasi; umuman audio bo'lmasa bo'sh satr.
     *
     * @param \stdClass $dars `_lesson` yozuvi
     * @param bool $tugmalar amal tugmalari ko'rsatilsinmi (faqat-o'qish sahifasida false)
     */
    public static function render(\stdClass $dars, bool $tugmalar = true): string {
        global $PAGE, $OUTPUT;

        $h = '';

    // ---------------------------------------------------------- AI DARS TAHLILI
    $tahlil = \local_targetzenit\ai::for_lesson((int) $dars->id);
    if ($tahlil) {
        $d = $tahlil['data'];
        $h .= \html_writer::start_div('card mb-3 tz-ai-card');
        $h .= \html_writer::start_div('card-body');

        // --- Umumiy foiz
        $um = $d['overall']['score'] ?? null;
        $norma = \local_targetzenit\ai::norm_overall();
        $sarlavha = '🤖 ' . get_string('ai_analyse', 'local_targetzenit');
        if ($um !== null) {
            // Rang XALQARO O'RTACHAGA nisbatan: 59% o'rtacha dars demakdir,
            // uni "qizil" deb ko'rsatish noto'g'ri bo'lardi.
            $rang = $um >= $norma + 10 ? 'success' : ($um >= $norma - 10 ? 'warning' : 'danger');
            $sarlavha .= ' ' . \html_writer::tag('span', $um . '%',
                ['class' => 'badge badge-' . $rang . ' tz-ai-umumiy']);
        }
        $h .= \html_writer::tag('h5', $sarlavha);

        if ($um !== null) {
            $farq = $um - $norma;
            $h .= \html_writer::div(
                "Xalqaro o'rtacha: <b>{$norma}%</b> (CLASS va ICALT tadqiqotlari) · " .
                "bu dars: <b>{$um}%</b> — o'rtachadan " .
                ($farq >= 0 ? "<span class='text-success'>+{$farq}%</span>"
                            : "<span class='text-danger'>{$farq}%</span>"),
                'text-muted small mb-3');
        }

        if (!empty($d['summary'])) {
            $h .= \html_writer::tag('p', s($d['summary']), ['class' => 'mb-3']);
        }

        // --- Mavzuga muvofiqlik
        if (!empty($d['topic_match'])) {
            $tm = $d['topic_match'];
            $ok = !empty($tm['covered']);
            $h .= \html_writer::div(
                \html_writer::tag('span', $ok ? "✔ Mavzu o'tildi" : "✖ Mavzu o'tilmadi",
                    ['class' => 'badge ' . ($ok ? 'badge-success' : 'badge-danger')]) . ' ' .
                (!empty($tm['actual_topic'])
                    ? \html_writer::tag('span', 'Darsda: ' . s($tm['actual_topic']), ['class' => 'ml-2'])
                    : '') .
                (!empty($tm['note']) ? \html_writer::div(s($tm['note']), 'text-muted small mt-1') : ''),
                'mb-3'
            );
        }

        // --- Kategoriyalar (ICALT) — foizli chiziqlar
        if (!empty($d['categories']) && is_array($d['categories'])) {
            $h .= \html_writer::tag('div',
                "Baholash ramkasi: <b>ICALT</b> + <b>CLASS</b> + Jahon banki <b>TEACH</b>. " .
                "Chiziqdagi <span class='tz-norm-namuna'></span> belgisi — shu mezon bo'yicha " .
                "<b>xalqaro o'rtacha</b>. Undan o'ngda bo'lsangiz — o'rtachadan yuqorisiz.",
                ['class' => 'text-muted small mb-2']);

            // Guruhlar bo'yicha tartiblaymiz (CATEGORIES dagi tartib bo'yicha)
            $tartib = array_keys(\local_targetzenit\ai::CATEGORIES);
            usort($d['categories'], function($a, $b) use ($tartib) {
                return array_search($a['code'] ?? '', $tartib) <=> array_search($b['code'] ?? '', $tartib);
            });
            $joriy_guruh = '';

            foreach ($d['categories'] as $c) {
                $guruh = \local_targetzenit\ai::CATEGORIES[$c['code'] ?? '']['guruh'] ?? '';
                if ($guruh && $guruh !== $joriy_guruh) {
                    $h .= \html_writer::tag('div', s($guruh), ['class' => 'tz-cat-guruh']);
                    $joriy_guruh = $guruh;
                }
                $ball = (int) ($c['score'] ?? 0);
                $ishonch = $c['confidence'] ?? 'medium';
                $past = ($ishonch === 'low');
                $rang = $past ? 'secondary' : ($ball >= 75 ? 'success' : ($ball >= 55 ? 'warning' : 'danger'));

                $h .= \html_writer::start_div('tz-cat');
                $kat_norma = \local_targetzenit\ai::CATEGORIES[$c['code'] ?? '']['norm'] ?? null;
                // Rang normaga nisbatan — masalan 35% "Tushunchani chuqurlashtirish"
                // xalqaro o'rtachadan (30%) YUQORI, uni qizil qilish noto'g'ri
                if (!$past && $kat_norma !== null) {
                    $rang = $ball >= $kat_norma + 10 ? 'success'
                        : ($ball >= $kat_norma - 10 ? 'warning' : 'danger');
                }

                $h .= \html_writer::div(
                    \html_writer::tag('span', s($c['name'] ?? $c['code'] ?? ''), ['class' => 'tz-cat-nom']) .
                    \html_writer::tag('span', ($c['weight'] ?? 0) . '%', ['class' => 'tz-cat-vazn']) .
                    \html_writer::tag('span', $past ? 'audio orqali baholanmadi' : $ball . '%',
                        ['class' => 'tz-cat-ball text-' . $rang]),
                    'tz-cat-bosh');

                $chiziq = \html_writer::div('', 'tz-bar-ichi bg-' . $rang,
                    ['style' => 'width:' . ($past ? 0 : $ball) . '%']);
                if ($kat_norma !== null) {
                    $chiziq .= \html_writer::div('', 'tz-norm',
                        ['style' => 'left:' . $kat_norma . '%',
                         'title' => "Xalqaro o'rtacha: {$kat_norma}%"]);
                }
                $h .= \html_writer::div($chiziq, 'tz-bar');
                if (!empty($c['actual_topic'])) {
                    $h .= \html_writer::div('Darsda: ' . s($c['actual_topic']), 'tz-cat-dalil');
                }
                if (!empty($c['evidence'])) {
                    $h .= \html_writer::div(s($c['evidence']), 'tz-cat-dalil');
                }
                if (!empty($c['advice'])) {
                    $h .= \html_writer::div('→ ' . s($c['advice']), 'tz-cat-tavsiya');
                }
                $h .= \html_writer::end_div();
            }

            if (!empty($d['overall']['chetda'])) {
                $h .= \html_writer::div(
                    "Hisobga olinmadi (audio orqali baholab bo'lmaydi): " .
                    s(implode(', ', $d['overall']['chetda'])) .
                    ". Umumiy foiz qolgan kategoriyalar bo'yicha hisoblangan.",
                    'text-muted small mt-2');
            }
        } else if (!empty($d['structure']) && is_array($d['structure'])) {
            // Eski ko'rinishdagi tahlil (kategoriyalar joriy etilgunga qadar)
            $h .= $OUTPUT->notification(
                "Bu tahlil ESKI ramkada qilingan (ha/yo'q). Foizli baholash uchun " .
                "pastdagi \"Qayta tahlil qilish\" tugmasini bosing.", 'warn');
            $t = new \html_table();
            $t->head = ['Bosqich', "Bo'ldimi", 'Daqiqa', 'Izoh'];
            $t->attributes['class'] = 'generaltable tz-ai-struct';
            foreach ($d['structure'] as $b) {
                $bor = !empty($b['present']);
                $t->data[] = [
                    s($b['stage'] ?? ''),
                    \html_writer::tag('span', $bor ? 'ha' : "yo'q",
                        ['class' => $bor ? 'text-success' : 'text-danger']),
                    (int) ($b['minutes'] ?? 0),
                    \html_writer::tag('span', s($b['note'] ?? ''), ['class' => 'small text-muted']),
                ];
            }
            $h .= \html_writer::table($t);
        }

        // --- O'lchovlar (audio orqali ishonchli o'lchanadiganlar)
        $m = $d['metrics'] ?? $d['speech'] ?? [];
        if ($m) {
            $q = [];
            if (isset($m['teacher_talk_pct'])) {
                $q[] = "O'qituvchi " . (int) $m['teacher_talk_pct'] . '% · o\'quvchilar '
                    . (int) ($m['student_talk_pct'] ?? 0) . '%';
            }
            if (isset($m['questions_total'])) {
                $q[] = 'Savollar: ' . (int) $m['questions_total'] .
                    (isset($m['open_questions']) ? ' (ochiq: ' . (int) $m['open_questions'] . ')' : '');
            }
            if (isset($m['wait_time_avg_sec'])) {
                $q[] = "Kutish vaqti: " . (int) $m['wait_time_avg_sec'] . ' s';
            }
            if (isset($m['students_addressed'])) {
                $q[] = 'Murojaat qilingan o\'quvchi: ' . (int) $m['students_addressed'];
            }
            if (isset($m['on_task_pct'])) {
                $q[] = "O'quv ishida: " . (int) $m['on_task_pct'] . '%';
            }
            if (!empty($m['language']) && is_array($m['language'])) {
                $til = [];
                foreach ($m['language'] as $k => $v) {
                    $til[] = strtoupper(s($k)) . ' ' . (int) $v . '%';
                }
                $q[] = 'Til: ' . implode(' · ', $til);
            }
            if ($q) {
                $h .= \html_writer::div(implode('  |  ', $q), 'tz-ai-speech mt-3');
            }
        }

        // --- Kuzatuv izohlari (BALLGA TA'SIR QILMAYDI)
        if (!empty($d['wellbeing_notes']) && is_array($d['wellbeing_notes'])) {
            $li = '';
            foreach ($d['wellbeing_notes'] as $w) {
                $li .= \html_writer::tag('li', s($w));
            }
            $h .= \html_writer::start_div('tz-wellbeing mt-3');
            $h .= \html_writer::tag('b', "E'tibor uchun (ballga ta'sir qilmaydi)");
            $h .= \html_writer::tag('ul', $li);
            $h .= \html_writer::tag('div',
                "Bu — ovozda eshitilgan faktlar, xulosa emas. AI o'qituvchining hissiy " .
                "holati yoki charchog'i haqida hukm chiqarmaydi: ovozdan bunday xulosa " .
                "chiqarish ishonchli emas va ta'lim muassasalarida qabul qilinmagan amaliyot.",
                ['class' => 'text-muted small']);
            $h .= \html_writer::end_div();
        }

        // --- Tavsiyalar
        if (!empty($d['recommendations']) && is_array($d['recommendations'])) {
            $li = '';
            foreach ($d['recommendations'] as $r) {
                $li .= \html_writer::tag('li', s($r));
            }
            $h .= \html_writer::tag('div',
                \html_writer::tag('b', 'Tavsiyalar:') . \html_writer::tag('ul', $li),
                ['class' => 'mt-3']);
        }

        // --- Transkript
        if (!empty($tahlil['audio']->transcript)) {
            $h .= \html_writer::start_tag('details', ['class' => 'mt-3']);
            $h .= \html_writer::tag('summary', "To'liq matn (transkript)", ['class' => 'tz-ai-summary']);
            $h .= \html_writer::tag('pre', s($tahlil['audio']->transcript), ['class' => 'tz-transcript']);
            $h .= \html_writer::end_tag('details');
        }

        $h .= \html_writer::div('Model: ' . s($tahlil['audio']->aimodel ?? '') . ' · ' .
            userdate($tahlil['audio']->analysedat), 'text-muted small mt-2');

        // Qayta tahlil — FAQAT MA'MUR uchun.
        // O'qituvchi o'z darsining tahlilini qayta ishga tushira olmaydi: aks holda
        // natija yoqmaganda uni qayta-qayta "aylantirish" mumkin bo'lib qolardi.
        if ($tugmalar
                && in_array($tahlil['audio']->status, \local_targetzenit\audio::FAYLLI, true)
                && \local_targetzenit\ai::enabled()
                && has_capability('local/targetzenit:viewanalysis', \context_system::instance())) {
            $h .= \html_writer::tag('button', '🔄 Qayta tahlil qilish',
                ['type' => 'button', 'id' => 'tz-ai-again', 'class' => 'btn btn-sm btn-outline-primary mt-2']);
            $h .= \html_writer::tag('span', '', ['id' => 'tz-ai-again-holat', 'class' => 'ml-2 small']);

            $PAGE->requires->js_amd_inline("
                require([], function() {
                    var b = document.getElementById('tz-ai-again');
                    var h = document.getElementById('tz-ai-again-holat');
                    if (!b) { return; }
                    var baza = '" . (new \moodle_url('/local/targetzenit/ajax.php'))->out(false) . "' +
                        '?lessonid=" . (int) $dars->id . "&sesskey=" . sesskey() . "';
                    b.addEventListener('click', function () {
                        if (!confirm('Mavjud tahlil o\'chiriladi va yangi ramkada qaytadan qilinadi. Davom etamizmi?')) {
                            return;
                        }
                        b.disabled = true;
                        h.textContent = 'Navbatga qo\'yildi...';
                        fetch(baza + '&action=reanalyse', {method: 'POST'})
                            .then(function (r) { return r.json(); })
                            .then(function (j) {
                                if (!j.ok) {
                                    h.textContent = 'Xato: ' + (j.error || '');
                                    b.disabled = false;
                                    return;
                                }
                                h.textContent = 'Tahlil qilinmoqda — uzun darsda 2-5 daqiqa...';
                                // Tahlil tugaganini kutamiz (har 5 soniyada)
                                var n = 90;
                                var kut = function () {
                                    if (n-- <= 0) {
                                        h.textContent = 'Hali tugamadi — sahifani keyinroq yangilang.';
                                        b.disabled = false;
                                        return;
                                    }
                                    fetch(baza + '&action=status', {method: 'POST'})
                                        .then(function (r) { return r.json(); })
                                        .then(function (j2) {
                                            if (j2.analysed) { window.location.reload(); return; }
                                            if (j2.aierror) {
                                                h.textContent = 'Xato: ' + j2.aierror;
                                                b.disabled = false;
                                                return;
                                            }
                                            setTimeout(kut, 5000);
                                        })
                                        .catch(function () { setTimeout(kut, 5000); });
                                };
                                setTimeout(kut, 5000);
                            })
                            .catch(function () {
                                h.textContent = 'Fonda davom etmoqda — 2-5 daqiqadan keyin sahifani yangilang.';
                            });
                    });
                });
            ");
        }

        $h .= \html_writer::end_div();
        $h .= \html_writer::end_div();
    } else if (\local_targetzenit\ai::enabled()) {
        $a = \local_targetzenit\audio::get((int) $dars->id);
        if ($a && $a->status === 'ready') {
            $h .= \html_writer::start_div('card mb-3 tz-ai-card');
            $h .= \html_writer::start_div('card-body');
            $h .= \html_writer::tag('div',
                !empty($a->aierror)
                    ? 'Tahlil qilinmadi: ' . s($a->aierror)
                    : get_string('ai_pending', 'local_targetzenit') .
                      " — yozuv tugagach avtomatik boshlanadi (~1 daqiqa).",
                ['class' => !empty($a->aierror) ? 'text-danger mb-2' : 'text-muted mb-2']);

            if (!$tugmalar) {
                $h .= \html_writer::end_div();
                $h .= \html_writer::end_div();
                return $h;
            }

            $h .= \html_writer::tag('button', '🤖 Hozir tahlil qilish',
                ['type' => 'button', 'id' => 'tz-ai-now', 'class' => 'btn btn-primary']);
            $h .= \html_writer::tag('span', '', ['id' => 'tz-ai-holat', 'class' => 'ml-3 font-weight-bold']);
            $h .= \html_writer::end_div();
            $h .= \html_writer::end_div();

            $PAGE->requires->js_amd_inline("
                require([], function() {
                    var b = document.getElementById('tz-ai-now');
                    var h = document.getElementById('tz-ai-holat');
                    if (!b) { return; }
                    var baza = '" . (new \moodle_url('/local/targetzenit/ajax.php'))->out(false) . "' +
                        '?lessonid=" . (int) $dars->id . "&sesskey=" . sesskey() . "';

                    // Uzun so'rov nginx tomonidan uzilsa ham PHP tahlilni tugatadi —
                    // shuning uchun xatoda ham holatni so'rab turamiz.
                    var kuzat = function (qoldi) {
                        if (qoldi <= 0) {
                            h.textContent = 'Hali tugamadi — sahifani keyinroq yangilang.';
                            b.disabled = false;
                            return;
                        }
                        fetch(baza + '&action=status', {method: 'POST'})
                            .then(function (r) { return r.json(); })
                            .then(function (j) {
                                if (j.analysed) { window.location.reload(); return; }
                                if (j.aierror) {
                                    h.textContent = 'Xato: ' + j.aierror;
                                    b.disabled = false;
                                    return;
                                }
                                setTimeout(function () { kuzat(qoldi - 1); }, 5000);
                            })
                            .catch(function () { setTimeout(function () { kuzat(qoldi - 1); }, 5000); });
                    };

                    b.addEventListener('click', function () {
                        b.disabled = true;
                        h.textContent = 'Navbatga qo\'yildi, tahlil fonda boshlanadi...';
                        fetch(baza + '&action=analyse', {method: 'POST'})
                            .then(function (r) { return r.json(); })
                            .then(function (j) {
                                if (j.ok) {
                                    h.textContent = 'Tahlil qilinmoqda — uzun darsda 2-5 daqiqa.';
                                    kuzat(90);     // ~7 daqiqagacha kuzatamiz
                                } else {
                                    h.textContent = 'Xato: ' + (j.error || 'noma\'lum');
                                    b.disabled = false;
                                }
                            })
                            .catch(function () {
                                h.textContent = 'Fonda davom etmoqda, kutilmoqda...';
                                kuzat(90);
                            });
                    });
                });
            ");
        }
    }

        return $h;
    }
}
