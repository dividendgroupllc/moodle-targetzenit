<?php
// Target xususiy maktabi — Moodle local plugin.
//
// Bu plagin Frappe (target_zenit) bilan integratsiyaga tegishli maxsus kod uchun
// uy vazifasini bajaradi. Moodle core kodiga HECH QACHON tegilmaydi — hamma
// o'zgarish shu papka ichida bo'ladi, shunda Moodle'ni yangilash xavfsiz qoladi.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_targetzenit';   // Frankenstyle nomi — o'zgarmaydi
$plugin->version   = 2026102805;            // YYYYMMDDXX — har deployda oshiriladi
$plugin->requires  = 2026100500;            // Moodle 5.3
$plugin->supported = [503, 503];
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.12.0';
