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
        // Direktor fidbek yozadi; O'QITUVCHI esa o'z darsiga JAVOB yozadi —
        // suhbat bir tomonlama bo'lmasligi kerak.
        $direktor = has_capability('local/targetzenit:givefeedback', $ctx);
        $ozi = $teacheruserid && (int) $USER->id === $teacheruserid;
        $yozaoladi = $teacheruserid && ($direktor || $ozi);
        // `private` qaydni faqat ma'muriyat ko'radi — o'qituvchi uni ko'rmaydi
        $maxfiyham = has_capability('local/targetzenit:viewprivatefeedback', $ctx);

        $royxat = feedback::for_lesson($lessonid, $maxfiyham);

        // O'qituvchi O'Z fidbegini ochdi — "ko'rildi" deb belgilaymiz
        if ($royxat && $ozi) {
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

                $h .= \html_writer::start_div('tz-fb-item tz-fb-' . s($f->kind)
                    . ($f->parentid ? ' tz-fb-javob' : ''));
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
            // Zanjirning oxirgi xabari — yangi yozuv unga javob bo'ladi
            $oxirgi = $royxat ? end($royxat) : null;
            $h .= \html_writer::empty_tag('input', ['type' => 'hidden',
                'name' => 'fbparent', 'value' => $oxirgi ? (int) $oxirgi->id : 0]);

            $h .= \html_writer::tag('textarea', '', [
                'name' => 'fbbody', 'rows' => 3, 'class' => 'tz-fb-text',
                'maxlength' => feedback::MAX,
                'placeholder' => $ozi ? $t('fb_replyph') : $t('fb_placeholder'),
            ]);

            $h .= \html_writer::start_div('tz-fb-tools');
            // Ohang va maxfiylik — FAQAT direktorga. O'qituvchi o'ziga
            // ogohlantirish yozmaydi va javobini o'zidan yashira olmaydi.
            if ($direktor && !$ozi) {
                // STANDART "tavsiya": panel maqsadi ayblash emas, rivojlantirish
                $h .= \html_writer::select([
                    'tavsiya' => $t('fb_kind_tavsiya'),
                    'ogohlantirish' => $t('fb_kind_ogohlantirish'),
                    'rahmat' => $t('fb_kind_rahmat'),
                ], 'fbkind', 'tavsiya', false);

                $h .= \html_writer::tag('label',
                    \html_writer::checkbox('fbprivate', 1, false, '') . ' ' . $t('fb_privatelab'),
                    ['class' => 'tz-fb-check', 'title' => $t('fb_privatehelp')]);
            }

            $h .= \html_writer::empty_tag('input', ['type' => 'submit',
                'class' => 'btn btn-sm btn-primary',
                'value' => $ozi ? $t('fb_reply') : $t('fb_send')]);
            $h .= \html_writer::end_div();
            $h .= \html_writer::end_tag('form');
        }

        $h .= \html_writer::end_div();
        return $h;
    }

    /**
     * QISQA KO'RINISH — lenta qatorining o'z katagida.
     *
     * Kartani ochmasdan ham fidbek yozish mumkin bo'lishi kerak: direktor
     * 20 ta darsni ko'zdan kechirib, ba'zisiga bir og'iz izoh qoldiradi.
     * Oldingi xabar KICHRAYTIRILGAN holatda ko'rinadi (oxirgisi + soni),
     * to'liq suhbat esa kartada.
     *
     * Ikkalasi BITTA zanjir: bu yerda yozilgani kartada ham, kartada
     * yozilgani bu yerda ham chiqadi.
     *
     * @param array|null $oxirgi `feedback::latest()` dan kelgan qator
     */
    public static function compactcell(int $lessonid, int $teacheruserid,
                                        ?array $oxirgi, string $action): string {
        global $USER;

        $ctx = \context_system::instance();
        $direktor = has_capability('local/targetzenit:givefeedback', $ctx);
        $ozi = $teacheruserid && (int) $USER->id === $teacheruserid;
        if (!$teacheruserid || (!$direktor && !$ozi)) {
            return '';
        }
        $t = fn(string $k) => get_string($k, 'local_targetzenit');

        // Katak ichidagi bosish qatorni OCHMASLIGI kerak — `data-tzstop`
        // ni JS tekshiradi, aks holda matn yozayotganda karta ochilib ketardi.
        $h = \html_writer::start_div('tz-fb-cell', ['data-tzstop' => '1']);

        $soni = (int) ($oxirgi['soni'] ?? 0);
        $son = $oxirgi['oxirgi'] ?? null;

        if ($son) {
            $ism = ($son->firstname || $son->lastname)
                ? \local_targetzenit\dashboard::ism($son->firstname, $son->lastname) : '—';
            $status = ['tavsiya' => 'good', 'ogohlantirish' => 'serious',
                       'rahmat' => 'good'][$son->kind] ?? 'none';
            $matn = \core_text::substr(trim((string) $son->body), 0, 90);

            $h .= \html_writer::div(
                \html_writer::tag('span', $t('fb_kind_' . $son->kind),
                    ['class' => 'tz-dir-chip tz-dir-' . $status])
                . ($soni > 1 ? ' ' . \html_writer::tag('span', (string) $soni,
                    ['class' => 'tz-fb-count', 'title' => $t('fb_count')]) : ''),
                'tz-fb-cmeta');
            $h .= \html_writer::div(
                \html_writer::tag('b', s($ism)) . ': '
                . s($matn) . (\core_text::strlen((string) $son->body) > 90 ? '…' : ''),
                'tz-fb-ctext', ['title' => s((string) $son->body)]);
        }

        // --- bir qatorli yozish formasi
        $h .= \html_writer::start_tag('form', ['method' => 'post', 'action' => $action,
            'class' => 'tz-fb-cform']);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'fblesson', 'value' => $lessonid]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'fbteacher', 'value' => $teacheruserid]);
        $h .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'fbparent', 'value' => $son ? (int) $son->id : 0]);
        $h .= \html_writer::empty_tag('input', ['type' => 'text', 'name' => 'fbbody',
            'maxlength' => feedback::MAX,
            'placeholder' => $son ? $t('fb_replyshort') : $t('fb_addshort')]);
        $h .= \html_writer::tag('button', '➤', ['type' => 'submit',
            'class' => 'tz-fb-csend', 'title' => $son ? $t('fb_reply') : $t('fb_send')]);
        $h .= \html_writer::end_tag('form');

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

        $teacher = optional_param('fbteacher', 0, PARAM_INT);
        // Direktor fidbek yozadi, o'qituvchi O'Z darsiga javob yozadi.
        // Boshqa hech kim — tugmani yashirish yetarli emas, so'rovni
        // to'g'ridan-to'g'ri yuborish mumkin.
        global $USER;
        if ((int) $USER->id !== $teacher) {
            require_capability('local/targetzenit:givefeedback', \context_system::instance());
        } else if (!feedback::javob_mumkinmi($lessonid, (int) $USER->id)) {
            // O'z id sini qo'yib BEGONA darsga javob yozishning oldi olinadi —
            // tugmani yashirish yetarli emas, so'rovni qo'lda yuborish mumkin.
            throw new \moodle_exception('nopermissions', 'error', '',
                get_string('fb_noreply', 'local_targetzenit'));
        }
        $body = optional_param('fbbody', '', PARAM_TEXT);
        $kind = optional_param('fbkind', 'tavsiya', PARAM_ALPHA);
        $private = optional_param('fbprivate', 0, PARAM_INT);
        $parent = optional_param('fbparent', 0, PARAM_INT);

        return feedback::add($lessonid, $teacher, $body, $kind,
            $private ? 'private' : 'teacher', $parent);
    }
}
