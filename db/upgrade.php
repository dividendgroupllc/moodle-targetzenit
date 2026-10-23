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

    if ($oldversion < 2026102302) {

        // --- O'tkazilgan dars: mavzu va uy vazifa ---
        $table = new xmldb_table('local_targetzenit_lesson');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lessondate', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lessonno', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('topic', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('homework', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('course_date_lesson', XMLDB_INDEX_UNIQUE, ['courseid', 'lessondate', 'lessonno']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // --- O'quvchining davomati va bahosi ---
        $table = new xmldb_table('local_targetzenit_record');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attendance', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'present');
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('comment', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('lesson_user', XMLDB_INDEX_UNIQUE, ['lessonid', 'userid']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102302, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102400) {

        // --- Dars audio yozuvi ---
        $table = new xmldb_table('local_targetzenit_audio');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'recording');
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mimetype', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('lessonid', XMLDB_INDEX_UNIQUE, ['lessonid']);
        $table->add_index('status_time', XMLDB_INDEX_NOTUNIQUE, ['status', 'timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102400, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102500) {

        // Baholar endi Moodle gradebook'iga ham yoziladi, uy vazifa esa
        // mod_assign bo'lib yaratiladi — ikkalasining havolasi shu yerda.
        $table = new xmldb_table('local_targetzenit_lesson');

        $field = new xmldb_field('gradeitemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'teacherid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('assigncmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'gradeitemid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026102500, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102600) {

        // AI tahlili natijalari
        $table = new xmldb_table('local_targetzenit_audio');
        foreach ([
            ['analysis', XMLDB_TYPE_TEXT, null, 'transcript'],
            ['aimodel', XMLDB_TYPE_CHAR, '64', 'analysis'],
            ['aierror', XMLDB_TYPE_CHAR, '255', 'aimodel'],
        ] as [$nom, $turi, $uz, $oldin]) {
            $field = new xmldb_field($nom, $turi, $uz, null, null, null, null, $oldin);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        $field = new xmldb_field('analysedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'aierror');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026102600, 'local', 'targetzenit');
    }

    return true;
}
