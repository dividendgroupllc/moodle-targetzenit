<?php
// Til satrlari. Moodle har bir plagindan kamida 'pluginname' ni talab qiladi.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Target Zenit integration';
$string['privacy:metadata'] = 'The Target Zenit integration plugin does not store any personal data. '
    . 'User records are created and updated through Moodle core web services by the Frappe ERP system.';

// Ruxsatlar
$string['targetzenit:push'] = 'Push timetable and calendar data from Frappe';
$string['targetzenit:viewalltimetable'] = 'View the whole-school timetable';

// Dars jadvali sahifasi
$string['timetable'] = 'Dars jadvali';
$string['timetable_my'] = 'Mening jadvalim';
$string['timetable_today'] = 'Bugun';
$string['timetable_week'] = 'Hafta';
$string['timetable_all'] = 'Umumiy jadval';
$string['timetable_empty'] = 'Jadval topilmadi.';
$string['timetable_nolessons'] = 'Bu kuni dars yo\'q.';
$string['lesson'] = 'Dars';
$string['room'] = 'Xona';
$string['teacher'] = 'O\'qituvchi';
$string['group'] = 'Guruh';
$string['subject'] = 'Fan';

// Dars kokpiti
$string['targetzenit:teach'] = 'Record topic, attendance and grades for a lesson';
$string['topic'] = 'Dars mavzusi';
$string['homework'] = 'Uy vazifa';
$string['student'] = "O'quvchi";
$string['attendance'] = "Yo'qlama";
$string['grade'] = 'Baho';
$string['comment'] = 'Izoh';
$string['journal'] = 'Jurnal — oldingi darslar';
$string['average'] = "O'rtacha";
$string['openlesson'] = 'Darsni ochish';

// Dars audiosi
$string['audio_settings'] = 'Dars audio yozuvi';
$string['audio_enabled'] = 'Audio yozuvni yoqish';
$string['audio_enabled_desc'] = "O'qituvchi dars sahifasida yozishni boshlay oladi.";
$string['audio_retention'] = 'Saqlash muddati (kun)';
$string['audio_retention_desc'] = "Shu kundan oshgan audio AVTOMATIK o'chiriladi — transkript qilingan yoki qilinmaganidan qat'i nazar. Transkript matni o'chmaydi. Sabab: haftasiga ~7.5 GB audio to'planadi, disk to'lib qolmasligi kerak.";
$string['audio_maxmb'] = 'Bitta yozuv uchun chegara (MB)';
$string['audio_maxmb_desc'] = "Shu hajmdan oshsa yozish to'xtaydi. 24 kbps da 120 MB ~ 11 soat.";
$string['audio_record'] = 'Darsni yozib olish';
$string['audio_stop'] = "To'xtatish va saqlash";
$string['audio_recording'] = 'Yozilmoqda';
$string['audio_ready'] = 'Yozuv saqlandi';
$string['audio_cleanup'] = "Muddati o'tgan dars audiolarini o'chirish";

// O'quvchi ko'rinishi
$string['mygrades'] = 'Baholarim';
