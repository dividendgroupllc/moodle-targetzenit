<?php
// Mavjud o'rnatmada jadvallarni yaratish/yangilash.
// install.xml faqat TOZA o'rnatishda ishlaydi — plagin allaqachon o'rnatilgan
// bo'lsa (bizda shunday), jadval shu fayl orqali qo'shiladi.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_targetzenit_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026102200) {

        // --- Dars jadvali ---
        $table = new xmldb_table('local_targetzenit_timetable');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subjectkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subjectname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('teacherkey', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('teachername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('dayno', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('dayname', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('lessonno', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('starttime', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('endtime', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('room', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('courseidnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('year_group', XMLDB_INDEX_NOTUNIQUE, ['academicyear', 'groupkey']);
        $table->add_index('year_teacher', XMLDB_INDEX_NOTUNIQUE, ['academicyear', 'teacherkey']);
        $table->add_index('dayno', XMLDB_INDEX_NOTUNIQUE, ['dayno']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // --- O'quv kalendari (chorak va bayramlar) ---
        $table = new xmldb_table('local_targetzenit_calendar');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entrytype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('startdate', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('year_type', XMLDB_INDEX_NOTUNIQUE, ['academicyear', 'entrytype']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102200, 'local', 'targetzenit');
    }

    return true;
}
