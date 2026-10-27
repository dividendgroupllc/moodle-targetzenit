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

// AI tahlili
$string['targetzenit:viewanalysis'] = 'View AI analysis of any lesson recording';
$string['ai_settings'] = 'AI dars tahlili';
$string['ai_enabled'] = 'AI tahlilni yoqish';
$string['ai_enabled_desc'] = "Yozib olingan dars avtomatik transkript qilinadi va tahlil qilinadi.";
$string['ai_key'] = 'Gemini API kalit';
$string['ai_key_desc'] = 'Google AI Studio dan olingan kalit.';
$string['ai_model'] = 'Model';
$string['ai_model_desc'] = "Masalan: gemini-3.8-flash";
$string['ai_fallback'] = 'Zaxira model';
$string['ai_fallback_desc'] = "Asosiy modelning kvotasi tugasa shunga o'tiladi. Bo'sh qoldirilsa zaxira ishlatilmaydi.";
$string['ai_perrun'] = 'Bir yurishda nechta dars';
$string['ai_perrun_desc'] = "Har 15 daqiqada shuncha yozuv tahlil qilinadi. Ko'p qo'yilsa kvota tez tugaydi.";
$string['ai_analyse'] = 'Dars tahlili';
$string['ai_pending'] = 'Tahlil navbatda';
$string['ai_running'] = 'Tahlil qilinmoqda';
$string['ai_task'] = 'Dars yozuvlarini AI bilan tahlil qilish';

$string['recordings'] = 'Dars yozuvlari';

$string['ai_segment'] = "Transkripsiya bo'lagi (daqiqa)";
$string['ai_segment_desc'] = "Uzun dars shu uzunlikdagi bo'laklarga ajratilib, har biri alohida matnga o'giriladi. Bitta so'rovda 45 daqiqalik dars so'ralsa, model javob uzunligi chegarasiga urilib matnni o'rtasidan kesadi. 5 daqiqa — sinalgan qiymat. 0 qo'yilsa bo'linmaydi.";
$string['ai_ffmpeg'] = 'ffmpeg yo\'li';
$string['ai_ffmpeg_desc'] = "Audioni bo'laklarga ajratish uchun kerak (apt install ffmpeg). Topilmasa bo'linmaydi va uzun darslarning matni chala qolishi mumkin.";
