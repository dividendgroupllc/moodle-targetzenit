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

    $ADMIN->add('localplugins', $settings);
}
