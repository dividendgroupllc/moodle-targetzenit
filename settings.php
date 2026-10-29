<?php
// Plagin sozlamalari: Site administration -> Plugins -> Local plugins -> Target Zenit.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_targetzenit',
        get_string('pluginname', 'local_targetzenit'));

    $settings->add(new admin_setting_heading('local_targetzenit/audioheading',
        get_string('audio_settings', 'local_targetzenit'), ''));

    $settings->add(new admin_setting_configcheckbox('local_targetzenit/audioenabled',
        get_string('audio_enabled', 'local_targetzenit'),
        get_string('audio_enabled_desc', 'local_targetzenit'), 1));

    // DIQQAT: audio vaqtinchalik. 934 dars/hafta x ~8 MB = ~7.5 GB/hafta.
    // Muddat o'tgan yozuvlar transkript qilingan-qilinmaganidan qat'i nazar o'chadi.
    $settings->add(new admin_setting_configtext('local_targetzenit/audioretentiondays',
        get_string('audio_retention', 'local_targetzenit'),
        get_string('audio_retention_desc', 'local_targetzenit'), 15, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_targetzenit/audiomaxmb',
        get_string('audio_maxmb', 'local_targetzenit'),
        get_string('audio_maxmb_desc', 'local_targetzenit'), 120, PARAM_INT));

    // ---------------- Direktor paneli ----------------
    $settings->add(new admin_setting_heading('local_targetzenit/dirheading',
        get_string('dir_panel', 'local_targetzenit'), ''));

    // Bo'sh qoldirilsa — jurnaldagi birinchi yozuvdan avtomatik aniqlanadi.
    // NEGA KERAK: reja 1-sentabrdan to'lgan, jurnal esa keyinroq ishga
    // tushgan. Sana bo'lmasa o'qituvchi jurnal mavjud bo'lmagan kunlar uchun
    // ham "dars o'tmagan" bo'lib ko'rinadi.
    $settings->add(new admin_setting_configtext('local_targetzenit/kuzatuvboshi',
        get_string('dir_setting_start', 'local_targetzenit'),
        get_string('dir_setting_start_desc', 'local_targetzenit'), '', PARAM_TEXT));

    // ---------------- AI tahlili ----------------
    $settings->add(new admin_setting_heading('local_targetzenit/aiheading',
        get_string('ai_settings', 'local_targetzenit'), ''));

    $settings->add(new admin_setting_configcheckbox('local_targetzenit/aienabled',
        get_string('ai_enabled', 'local_targetzenit'),
        get_string('ai_enabled_desc', 'local_targetzenit'), 0));

    $settings->add(new admin_setting_configpasswordunmask('local_targetzenit/geminikey',
        get_string('ai_key', 'local_targetzenit'),
        get_string('ai_key_desc', 'local_targetzenit'), ''));

    $settings->add(new admin_setting_configtext('local_targetzenit/geminimodel',
        get_string('ai_model', 'local_targetzenit'),
        get_string('ai_model_desc', 'local_targetzenit'), 'gemini-3.8-flash', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_targetzenit/geminifallback',
        get_string('ai_fallback', 'local_targetzenit'),
        get_string('ai_fallback_desc', 'local_targetzenit'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_targetzenit/aisegmentminutes',
        get_string('ai_segment', 'local_targetzenit'),
        get_string('ai_segment_desc', 'local_targetzenit'), 5, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_targetzenit/ffmpegpath',
        get_string('ai_ffmpeg', 'local_targetzenit'),
        get_string('ai_ffmpeg_desc', 'local_targetzenit'), '/usr/bin/ffmpeg', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_targetzenit/aiperrun',
        get_string('ai_perrun', 'local_targetzenit'),
        get_string('ai_perrun_desc', 'local_targetzenit'), 3, PARAM_INT));

    $ADMIN->add('localplugins', $settings);

    // Gradebook integratsiyasi holati — baholar Moodle'ning o'z gradebook'iga
    // tushayotganini tekshirish va yetishmaganlarini ko'chirish.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_targetzenit_gradebook',
        'Target Zenit: gradebook',
        new moodle_url('/local/targetzenit/gradebook_admin.php'),
        'moodle/site:config'
    ));

    // Yozuvlar ro'yxati — admin panelidan ochiladigan alohida sahifa
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_targetzenit_yozuvlar',
        get_string('recordings', 'local_targetzenit'),
        new moodle_url('/local/targetzenit/yozuvlar.php'),
        'local/targetzenit:viewanalysis'
    ));
}
