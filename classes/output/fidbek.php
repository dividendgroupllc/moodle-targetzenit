<?php
// FIDBEK BLOKI — YAGONA chizuvchi.
//
// Xuddi shu blok uch joyda ko'rinadi:
//   yozuvlar.php      lentada, ochilgan kartaning ichida (direktor yozadi)
//   dars_tahlil.php   dars kartasida (direktor yozadi)
//   dars.php          o'qituvchining dars kokpitida (FAQAT O'QISH)
//
// Kod nusxalansa, ohang yoki ko'rinish qoidasi o'zgarganda uch joyni
// yangilash esdan chiqadi va bir sahifa boshqasidan farq qila boshlaydi.

namespace local_targetzenit\output;

use local_targetzenit\feedback;

defined('MOODLE_INTERNAL') || die();

class fidbek {

    /**
     * Fidbek ro'yxati va (ruxsat bo'lsa) yozish formasi.
     *
     * @param int $lessonid dars
     * @param int $teacheruserid kim haqida — forma shu odamga yozadi
     * @param string $action forma qayerga yuborilsin (bo'sh = shu sahifa)
     * @param bool $karta kartaga o'ralsinmi (dars.php da alohida karta kerak)
     */
    public static function render(int $lessonid, int $teacheruserid,
                                   string $action = '', bool $karta = true): string {
        global $USER;

        $ctx = \context_system::instance();
        $yozaoladi = $teacheruserid
            && has_capability('local/targetzenit:givefeedback', $ctx);
        // `private` qaydni faqat ma'muriyat ko'radi — o'qituvchi uni ko'rmaydi
        $maxfiyham = has_capability('local/targetzenit:viewprivatefeedback', $ctx);

        $royxat = feedback::for_lesson($lessonid, $maxfiyham);

        // O'qituvchi O'Z fidbegini ochdi — "ko'rildi" deb belgilaymiz
        if ($royxat && !$yozaoladi && (int) $USER->id === $teacheruserid) {
            feedback::korildi($lessonid, $teacheruserid);
        }

        $t = fn(string $k) => get_string($k, 'local_targetzenit');
        $h = '';

        if ($karta) {
            $h .= \html_writer::start_div('tz-dir-card tz-fb');
            $h .= \html_writer::tag('h4',
                $t('fb_title') . ($royxat
                    ? \html_writer::tag('span', ' (' . count($royxat) . ')',
                        ['class' => 'tz-dir-count']) : ''),
                ['class' => 'tz-dir-cardtitle']);
        } else {
            $h .= \html_writer::start_div('tz-fb');
            $h .= \html_writer::div($t('fb_title')
                . ($royxat ? ' (' . count($royxat) . ')' : ''), 'tz-fb-head');
        }

        // ------------------------------------------------------ ro'yxat
        if (!$royxat) {
            $h .= \html_writer::div($t('fb_none'), 'tz-dir-sub');
        } else {
            foreach ($royxat as $f) {
                $ism = ($f->firstname || $f->lastname)
                    ? \local_targetzenit\dashboard::ism($f->firstname, $f->lastname) : '—';
                $status = ['tavsiya' => 'good', 'ogohlantirish' => 'serious',
                           'rahmat' => 'good'][$f->kind] ?? 'none';

                $h .= \html_writer::start_div('tz-fb-item tz-fb-' . s($f->kind));
                $h .= \html_writer::div(
                    \html_writer::tag('span', $t('fb_kind_' . $f->kind),
                        ['class' => 'tz-dir-chip tz-dir-' . $status])
                    . ' ' . \html_writer::tag('b', s($ism))
                    . \html_writer::tag('span', ' · ' . userdate($f->timecreated, '%d.%m.%Y %H:%M'),
                        ['class' => 'tz-dir-sub'])
                    . ($f->visibility === 'private'
                        ? ' ' . \html_writer::tag('span', $t('fb_private'),
                            ['class' => 'tz-dir-chip tz-dir-warning']) : '')
                    . ($f->status === 'seen'
                        ? ' ' . \html_writer::tag('span', $t('fb_seen'),
                            ['class' => 'tz-dir-sub']) : ''),
                    'tz-fb-meta');
                $h .= \html_writer::tag('p', nl2br(s($f->body)), ['class' => 'tz-fb-body']);
                $h .= \html_writer::end_div();
            }
        }

        // ------------------------------------------------------- forma
        if ($yozaoladi) {
            $h .= \html_writer::start_tag('form', ['method' => 'post',
                'action' => $action, 'class' => 'tz-fb-form']);
            $h .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            $h .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'fblesson', 'value' => $lessonid]);
            $h .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'fbteacher', 'value' => $teacheruserid]);

            $h .= \html_writer::tag('textarea', '', [
                'name' => 'fbbody', 'rows' => 3, 'class' => 'tz-fb-text',
                'maxlength' => feedback::MAX,
                'placeholder' => $t('fb_placeholder'),
            ]);

            $h .= \html_writer::start_div('tz-fb-tools');
            // Ohang: STANDART "tavsiya" — panel maqsadi ayblash emas, rivojlantirish
            $h .= \html_writer::select([
                'tavsiya' => $t('fb_kind_tavsiya'),
                'ogohlantirish' => $t('fb_kind_ogohlantirish'),
                'rahmat' => $t('fb_kind_rahmat'),
            ], 'fbkind', 'tavsiya', false);

            $h .= \html_writer::tag('label',
                \html_writer::checkbox('fbprivate', 1, false, '') . ' ' . $t('fb_privatelab'),
                ['class' => 'tz-fb-check', 'title' => $t('fb_privatehelp')]);

            $h .= \html_writer::empty_tag('input', ['type' => 'submit',
                'class' => 'btn btn-sm btn-primary', 'value' => $t('fb_send')]);
            $h .= \html_writer::end_div();
            $h .= \html_writer::end_tag('form');
        }

        $h .= \html_writer::end_div();
        return $h;
    }

    /**
     * Yuborilgan formani qabul qiladi. Sahifa boshida, chiqarishdan OLDIN
     * chaqiriladi.
     *
     * @return int saqlangan fidbek id si (0 = yuborilmagan)
     */
    public static function handle(): int {
        $lessonid = optional_param('fblesson', 0, PARAM_INT);
        if (!$lessonid) {
            return 0;
        }
        require_sesskey();
        require_capability('local/targetzenit:givefeedback', \context_system::instance());

        $teacher = optional_param('fbteacher', 0, PARAM_INT);
        $body = optional_param('fbbody', '', PARAM_TEXT);
        $kind = optional_param('fbkind', 'tavsiya', PARAM_ALPHA);
        $private = optional_param('fbprivate', 0, PARAM_INT);

        return feedback::add($lessonid, $teacher, $body, $kind,
            $private ? 'private' : 'teacher');
    }
}
