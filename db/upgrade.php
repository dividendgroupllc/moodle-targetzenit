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

    if ($oldversion < 2026102908) {

        // Frappe oylik tabeli nusxasi — o'qituvchi o'z oyligini Moodle'da ko'radi.
        $table = new xmldb_table('local_targetzenit_payroll');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('employeename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('yil', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('oy', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lavozim', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('tolovturi', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'kun');
            $table->add_field('normakun', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('kelgankun', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            foreach (['ishlagan' => '10', 'kunliknarx' => '15', 'oylik' => '15', 'bonus' => '15',
                      'nalog' => '15', 'pitaniya' => '15', 'hisob' => '15', 'jami' => '15'] as $nom => $uz) {
                $table->add_field($nom, XMLDB_TYPE_NUMBER, $uz . ', 2', null, XMLDB_NOTNULL, null, '0');
            }
            $table->add_field('valyuta', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            $table->add_field('holat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Ochiq');
            $table->add_field('kunlarjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('ref_oy', XMLDB_INDEX_UNIQUE, ['useridnumber', 'yil', 'oy']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102908, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102910) {

        // 2026102908 jadvalni `id` ustuniga ketma-ketlik (auto-increment)
        // BERMASDAN yaratgan edi — `add_field()` ning 6-parametri o'tkazib
        // yuborilgan. Natijada har insert `dmlwriteexception` bilan yiqilardi.
        // Jadval Frappe tabelining NUSXASI, shuning uchun uni o'chirib
        // yangidan qurish xavfsiz: keyingi sinxronda ma'lumot to'liq qaytadi.
        $table = new xmldb_table('local_targetzenit_payroll');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('local_targetzenit_payroll');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('employeename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('yil', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('oy', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lavozim', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('tolovturi', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'kun');
        $table->add_field('normakun', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('kelgankun', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        foreach (['ishlagan' => '10', 'kunliknarx' => '15', 'oylik' => '15', 'bonus' => '15',
                  'nalog' => '15', 'pitaniya' => '15', 'hisob' => '15', 'jami' => '15'] as $nom => $uz) {
            $table->add_field($nom, XMLDB_TYPE_NUMBER, $uz . ', 2', null, XMLDB_NOTNULL, null, '0');
        }
        $table->add_field('valyuta', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('holat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Ochiq');
        $table->add_field('kunlarjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('ref_oy', XMLDB_INDEX_UNIQUE, ['useridnumber', 'yil', 'oy']);
        $dbman->create_table($table);

        upgrade_plugin_savepoint(true, 2026102910, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102917) {

        // Xodim bilan hisob-kitob: oy kesimida nachisleniya / to'langan /
        // qoldiq. Frappe investor panelidagi bilan BIR XIL manbadan keladi.
        $table = new xmldb_table('local_targetzenit_settle');
        if (!$dbman->table_exists($table)) {
            // DIQQAT: `id` ga XMLDB_SEQUENCE berilishi SHART — berilmasa jadval
            // yaratiladi, SELECT ishlaydi, har INSERT esa dmlwriteexception
            // bilan yiqiladi (payroll jadvalida aynan shu bo'lgan).
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('oy', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
            $table->add_field('valyuta', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'UZS');
            foreach (['oylik', 'nach', 'paid', 'advance', 'debt'] as $nom) {
                $table->add_field($nom, XMLDB_TYPE_NUMBER, '15, 2', null, XMLDB_NOTNULL, null, '0');
            }
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('ref_oy_cur', XMLDB_INDEX_UNIQUE, ['useridnumber', 'oy', 'valyuta']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102917, 'local', 'targetzenit');
    }

    if ($oldversion < 2026102921) {

        // ============ DIREKTOR PANELI — 1-bosqich ============
        // Reja ↔ fakt qatlami. Batafsil: DIREKTOR_PANELI.md
        //
        // DIQQAT (payroll jadvalida yo'l qo'yilgan xato): `id` ga
        // XMLDB_SEQUENCE berilishi SHART. Berilmasa jadval yaratiladi,
        // SELECT ishlaydi, har INSERT esa dmlwriteexception bilan yiqiladi.

        // --- O'qituvchi o'lchovi va kalit ko'prigi.
        // `_timetable.teacherkey` (Employee ID = user.idnumber) bilan
        // `_lesson.teacherid` (Moodle user.id) ni bir joyda bog'laydi.
        $table = new xmldb_table('local_targetzenit_staff');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('teacherkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('teachername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('holat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'ok');
            $table->add_field('lessonsweek', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('subjects', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('year_key', XMLDB_INDEX_UNIQUE, ['academicyear', 'teacherkey']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('holat', XMLDB_INDEX_NOTUNIQUE, ['holat']);
            $dbman->create_table($table);
        }

        // --- Rejalashtirilgan dars o'rni (materiallashtirilgan).
        //
        // NEGA `slotkey` (md5) UNIKAL KALIT: tabiiy kalit
        // (yil + sana + dars raqami + guruh + fan) uzun CHAR ustunlardan
        // iborat va utf8mb4 da indeks chegarasiga yaqinlashadi. Hash qisqa,
        // aniq va PHP tomonda diff qilish uchun ham xuddi shu kalit.
        $table = new xmldb_table('local_targetzenit_slot');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('slotkey', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lessondate', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('dayno', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lessonno', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('termname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('groupkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('subjectkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subjectname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('teacherkey', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('teacheruserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('courseidnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('starttime', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            $table->add_field('endtime', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            $table->add_field('room', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('frozen', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slotkey', XMLDB_INDEX_UNIQUE, ['slotkey']);
            $table->add_index('date_teacher', XMLDB_INDEX_NOTUNIQUE, ['lessondate', 'teacheruserid']);
            $table->add_index('teacher_date', XMLDB_INDEX_NOTUNIQUE, ['teacheruserid', 'lessondate']);
            $table->add_index('course_date', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'lessondate']);
            $table->add_index('year_date', XMLDB_INDEX_NOTUNIQUE, ['academicyear', 'lessondate']);
            $dbman->create_table($table);
        }

        // --- Reja ↔ fakt (slot bilan 1:1).
        $table = new xmldb_table('local_targetzenit_fact');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('slotid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('writeruserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            // Hisobot KIMGA yoziladi (3.4): yozgan odam, u yo'q bo'lsa rejadagi.
            $table->add_field('attributeduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('substituted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('hastopic', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('hashomework', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('studentcount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('attendancecount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('gradecount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('absentcount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('latecount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('audiostatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'none');
            $table->add_field('audioseconds', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('analysestatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'none');
            $table->add_field('analysedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('overallscore', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
            $table->add_field('normdelta', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
            $table->add_field('lowconfcount', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastwriteat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('latehours', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
            $table->add_field('feedbackcount', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slotid', XMLDB_INDEX_UNIQUE, ['slotid']);
            $table->add_index('lessonid', XMLDB_INDEX_NOTUNIQUE, ['lessonid']);
            $table->add_index('attributed', XMLDB_INDEX_NOTUNIQUE, ['attributeduserid']);
            $table->add_index('analysestatus', XMLDB_INDEX_NOTUNIQUE, ['analysestatus']);
            $dbman->create_table($table);
        }

        // --- AI kategoriya ballari (JSON'dan chiqarilgan indeks).
        // Yagona manba `_audio.analysis` bo'lib qoladi; bu jadval SQL bilan
        // trend va domen tahlili qilish uchun.
        $table = new xmldb_table('local_targetzenit_aicat');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('score', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('confidence', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            $table->add_field('weight', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('lesson_code', XMLDB_INDEX_UNIQUE, ['lessonid', 'code']);
            $table->add_index('code', XMLDB_INDEX_NOTUNIQUE, ['code']);
            $dbman->create_table($table);
        }

        // --- Frappe'dan kelgan normalar.
        // Moodle bu qiymatlarni HECH QACHON hisoblamaydi va o'zgartirmaydi —
        // `_payroll` bilan bir xil tamoyil: Frappe yagona manba.
        $table = new xmldb_table('local_targetzenit_norm');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            // `normkey` = md5(yil|qamrov|kalit|kod).
            //
            // NEGA HASH: tabiiy kalit (academicyear + scope + scopekey + code)
            // 365 belgi chiqadi, Moodle'ning indeks chegarasi esa 333 belgi
            // (Postgres'da 999 bayt). Tabiiy kalit bilan jadval UMUMAN
            // yaratilmadi — 2026-10-29 da migratsiya aynan shu yerda to'xtagan.
            $table->add_field('normkey', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('scope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'global');
            $table->add_field('scopekey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('value', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('normkey', XMLDB_INDEX_UNIQUE, ['normkey']);
            $table->add_index('academicyear', XMLDB_INDEX_NOTUNIQUE, ['academicyear']);
            $dbman->create_table($table);
        }

        // --- `_lesson` ga sana indeksi.
        //
        // NEGA: mavjud unikal indeks (courseid, lessondate, lessonno) ning
        // BOSH ustuni `courseid`. Direktor paneli esa "hamma kurs bo'yicha
        // shu sana oralig'i" deb so'raydi va bu indeksdan foydalanmaydi —
        // har qayta hisoblash butun jadvalni skanerlardi.
        $table = new xmldb_table('local_targetzenit_lesson');
        $index = new xmldb_index('lessondate', XMLDB_INDEX_NOTUNIQUE, ['lessondate']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026102921, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110310) {

        // --- Guruh tutori (sinf rahbari).
        //
        // NEGA KERAK: jadvalda ba'zi fanlar VAKANT — o'qituvchi hali
        // tayinlanmagan (`INSTR-...vakant`, `INSTR-TUTOR`). Bunday darslarni
        // guruhning o'z tutori o'tadi. Tutorsiz bu darslar hech kimning
        // hisobotiga tushmasdi: haftada 82 dars g'oyib bo'lardi.
        //
        // NEGA MOODLE'DA: Frappe'da guruh<->tutor bog'lanishi umuman yo'q
        // (tutor faqat `Employee.designation` da lavozim sifatida bor).
        // Qoida: moliya ERP'da, dars jarayoni Moodle'da — biriktirish shu yerda.
        $table = new xmldb_table('local_targetzenit_tutor');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('tutorkey', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // Unikal kalit hash ustida — `groupkey` uzun CHAR (indeks chegarasi
            // 333 belgi; `_norm` da aynan shu xato migratsiyani to'xtatgan).
            $table->add_index('tutorkey', XMLDB_INDEX_UNIQUE, ['tutorkey']);
            $table->add_index('year', XMLDB_INDEX_NOTUNIQUE, ['academicyear']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $dbman->create_table($table);
        }

        // Vakant dars belgisi: rejada o'qituvchi YO'Q edi va dars guruh
        // tutoriga yozildi. `substituted` dan farqli — u "boshqa o'qituvchi
        // o'rnini bosdi" degani, bu esa "o'qituvchi umuman tayinlanmagan".
        $table = new xmldb_table('local_targetzenit_fact');
        $field = new xmldb_field('vacancy', XMLDB_TYPE_INTEGER, '1', null,
            XMLDB_NOTNULL, null, '0', 'substituted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026110310, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110410) {

        // --- Direktor fidbegi.
        //
        // Direktor darsni ko'rib, o'qituvchiga yozma izoh qoldiradi.
        // O'qituvchi uni O'Z dars kokpitida ko'radi va bildirishnoma oladi.
        //
        // `visibility`: `teacher` — o'qituvchi ko'radi (STANDART, 6-tamoyil:
        // yashirin baho ishonchni buzadi); `private` — ma'muriyat ichidagi
        // qayd, ataylab tanlanadi.
        $table = new xmldb_table('local_targetzenit_feedback');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('teacheruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('authoruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('kind', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'tavsiya');
            $table->add_field('visibility', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'teacher');
            $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'new');
            $table->add_field('seenat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('lessonid', XMLDB_INDEX_NOTUNIQUE, ['lessonid']);
            $table->add_index('teacher_time', XMLDB_INDEX_NOTUNIQUE, ['teacheruserid', 'timecreated']);
            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026110410, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110420) {

        // Fidbek zanjiri: o'qituvchi javob yoza oladi, direktor javobga javob.
        // Dars bo'yicha bitta suhbat — qator ichidagi qisqa ko'rinish ham,
        // kartadagi to'liq ko'rinish ham AYNI shu zanjirni ko'rsatadi.
        $table = new xmldb_table('local_targetzenit_feedback');
        $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0', 'lessonid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026110420, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110500) {

        // MUROJAATLAR: ota-ona shikoyat/taklifi. Manba Frappe'dan keladi,
        // yuritish (holat, qaror) Moodle'da — MUROJAAT_ARXITEKTURA.md.
        $table = new xmldb_table('local_targetzenit_murojaat');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('frappename', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('turi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Shikoyat');
            $table->add_field('kategoriya', XMLDB_TYPE_CHAR, '150', null, null, null, null);
            $table->add_field('yonalish', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'CEO');
            $table->add_field('muhimlik', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, "O'rta");
            $table->add_field('matn', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('studentuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('studentidnumber', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('studentname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('sinf', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('payername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('payerphone', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_field('manageruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('managername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('manba', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('suhbatvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('ochilgan', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('slasana', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('holat', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'Yangi');
            $table->add_field('masuluserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('korildikim', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('korildivaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('qarorturi', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_field('qaror', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('qarorkim', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('qarorvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('otaonakordi', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('yopilganvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('frappename', XMLDB_INDEX_UNIQUE, ['frappename']);
            $table->add_index('yonalish_holat', XMLDB_INDEX_NOTUNIQUE, ['yonalish', 'holat']);
            $table->add_index('studentuserid', XMLDB_INDEX_NOTUNIQUE, ['studentuserid']);
            $table->add_index('masuluserid', XMLDB_INDEX_NOTUNIQUE, ['masuluserid']);
            $table->add_index('timemodified', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_targetzenit_murojaat_izoh');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('murojaatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('turi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'komment');
            $table->add_field('korinish', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'rahbar');
            $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('murojaat_time', XMLDB_INDEX_NOTUNIQUE, ['murojaatid', 'timecreated']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026110500, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110600) {

        // Murojaat kartasiga QARZ konteksti: rahbar qaror qabul qilishdan
        // oldin "bu oilada qarz bormi" degan savolga javob ko'rishi kerak.
        $table = new xmldb_table('local_targetzenit_murojaat');
        foreach ([
            ['qarzsumma', XMLDB_TYPE_NUMBER, '18,2', '0'],
            ['qarzholat', XMLDB_TYPE_CHAR, '64', null],
            ['qarzkechikish', XMLDB_TYPE_CHAR, '32', null],
        ] as [$nom, $turi, $uzunlik, $default]) {
            $field = new xmldb_field($nom, $turi, $uzunlik, null,
                $default === null ? null : XMLDB_NOTNULL, null, $default);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Sotuv bo'limining aloqa tarixi — "kim qachon gaplashdi".
        $table = new xmldb_table('local_targetzenit_murojaat_suhbat');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('murojaatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('vaqt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('menejer', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('kanal', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_field('natija', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('suhbatnatija', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('izoh', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('murojaat_vaqt', XMLDB_INDEX_NOTUNIQUE, ['murojaatid', 'vaqt']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026110600, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110610) {

        // O'QUVCHI SHARHI — o'qituvchining o'quvchi haqidagi yozma izohi.
        //
        // MATN BU JADVALDA EMAS: u Moodle'ning standart `{comments}` jadvalida
        // yotadi (`component=local_targetzenit`, `commentarea=studentreview`,
        // `itemid` = o'quvchi `user.id`, `contextid` = kurs konteksti). Shu
        // jadval faqat holat (o'quvchi ko'rdimi, maxfiymi) va indeksli filtr
        // uchun — matn ikki joyda saqlansa ular bir-biridan ajralib ketadi.
        $table = new xmldb_table('local_targetzenit_sharh');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('commentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('authorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('visibility', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'ochiq');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'new');
            $table->add_field('seenat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // `commentid` UNIQUE: bitta commentga ikki holat qatori bo'lmaydi.
            $table->add_index('commentid', XMLDB_INDEX_UNIQUE, ['commentid']);
            $table->add_index('student_time', XMLDB_INDEX_NOTUNIQUE, ['studentid', 'timecreated']);
            $table->add_index('course_student', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'studentid']);
            $table->add_index('author_time', XMLDB_INDEX_NOTUNIQUE, ['authorid', 'timecreated']);
            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026110610, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110620) {

        // NUQSON TUZATISH (2026-11-06/07): murojaat jadvallari yaratilganda
        // `id` ga AUTO_INCREMENT belgisi qo'yilmagan edi (`XMLDB_SEQUENCE`
        // `add_field()` ning 6-argumenti). Oqibati: birinchi yozuv id=0,
        // ikkinchisi "duplicate key" — modul birinchi murojaatdayoq sinadi.
        //
        // Yaratish bloklari yuqorida tuzatildi, lekin ular ALLAQACHON o'tgan
        // saytda qayta ishlamaydi (savepoint saqlangan). Shuning uchun bu
        // yerda mavjud jadval tekshiriladi va kerak bo'lsa qayta quriladi.
        //
        // DIQQAT — `install_one_table_from_xmldb_file()` ISHLATILMAYDI:
        // u BUTUN `install.xml` ni tekshiradi va fayldagi istalgan nuqson
        // (masalan takroriy jadval nomi) yangilanishni to'xtatadi. 2026-11-07
        // da aynan shunday bo'ldi. Ta'riflar shu yerda, inline.
        foreach (['local_targetzenit_murojaat',
                  'local_targetzenit_murojaat_izoh',
                  'local_targetzenit_murojaat_suhbat'] as $nom) {
            $table = new xmldb_table($nom);

            if ($dbman->table_exists($table)) {
                $ustunlar = $DB->get_columns($nom, false);
                if (!empty($ustunlar['id']->auto_increment)) {
                    continue;                   // joyida
                }
                if ($DB->count_records($nom) > 0) {
                    // Ma'lumot bor — jimgina o'chirib bo'lmaydi
                    debugging("local_targetzenit: {$nom}.id AUTO_INCREMENT emas "
                        . "va jadvalda ma'lumot bor — qo'lda tuzatish kerak",
                        DEBUG_DEVELOPER);
                    continue;
                }
                $dbman->drop_table($table);
            }

            $dbman->create_table(local_targetzenit_murojaat_jadval($nom));
        }

        upgrade_plugin_savepoint(true, 2026110620, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110760) {

        // TEZLIK: `studentidnumber` bo'yicha qidiruv indekssiz edi —
        // "shu o'quvchining boshqa murojaatlari" va ota-ona ko'rinishi
        // har safar butun jadvalni o'qirdi (2026-11-07 tezlik tekshiruvi).
        $table = new xmldb_table('local_targetzenit_murojaat');
        $index = new xmldb_index('studentidnumber', XMLDB_INDEX_NOTUNIQUE,
            ['studentidnumber']);
        if ($dbman->table_exists($table) && !$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026110760, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110830) {

        // O'QUV REJA (KTP) mavzulari — Frappe'dan keladi, slotlarga shu
        // yerda biriktiriladi (dars sanalari faqat Moodle'da ma'lum).
        $table = new xmldb_table('local_targetzenit_mavzu');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subjectkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('chorak', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('tartib', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('bolim', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('mavzu', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('soat', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('darsturi', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('frappereja', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('yil_fan_guruh', XMLDB_INDEX_NOTUNIQUE,
                ['academicyear', 'subjectkey', 'groupkey']);
            $table->add_index('tartib', XMLDB_INDEX_NOTUNIQUE,
                ['academicyear', 'subjectkey', 'groupkey', 'chorak', 'tartib']);
            $dbman->create_table($table);
        }

        // Slotga rejadagi mavzu. `_lesson.topic` ga YOZILMAYDI: u qator
        // faqat o'qituvchi dars o'tgandan keyin paydo bo'ladi va «o'tildi»
        // ma'nosini anglatadi — rejani u yerga yozish faktni buzardi.
        $table = new xmldb_table('local_targetzenit_slot');
        foreach ([['mavzu', XMLDB_TYPE_TEXT, null, null],
                  ['mavzuid', XMLDB_TYPE_INTEGER, '10', '0']] as [$nom, $turi, $uzunlik, $dflt]) {
            $field = new xmldb_field($nom, $turi, $uzunlik, null,
                $dflt === null ? null : XMLDB_NOTNULL, null, $dflt);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026110830, 'local', 'targetzenit');
    }

    if ($oldversion < 2026110840) {

        // ============================================ BAHOLASH (FB/BSB/CHSB)
        // BAHOLASH_ARXITEKTURA.md — O'zbekiston nizomi bo'yicha 100 ballik
        // chorak: FB 10 + BSB/LI 50 + CHSB 40.

        // --- Summativ baholash = e-kundalikdagi jurnal ustuni
        $table = new xmldb_table('local_targetzenit_exam');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, true);
            $table->add_field('turi', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'CHSB');
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('termname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subjectkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('gradelevel', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('shakli', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'qogoz');
            $table->add_field('examdate', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('starttime', XMLDB_TYPE_CHAR, '5', null, null, null, null);
            $table->add_field('room', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('duration', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '45');
            $table->add_field('maxscore', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '100');
            $table->add_field('randomcount', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('qbankcmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('qcategoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('retro', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'qoralama');
            $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('year_term', XMLDB_INDEX_NOTUNIQUE, ['academicyear', 'termname']);
            $table->add_index('subject_level', XMLDB_INDEX_NOTUNIQUE, ['subjectkey', 'gradelevel']);
            $table->add_index('examdate', XMLDB_INDEX_NOTUNIQUE, ['examdate']);
            $table->add_index('turi', XMLDB_INDEX_NOTUNIQUE, ['turi']);
            $dbman->create_table($table);
        }

        // --- Imtihon jadvali qatori (har guruh uchun sana/vaqt/xona)
        $table = new xmldb_table('local_targetzenit_exam_sitting');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, true);
            $table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('groupname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('examdate', XMLDB_TYPE_CHAR, '10', null, null, null, null);
            $table->add_field('starttime', XMLDB_TYPE_CHAR, '5', null, null, null, null);
            $table->add_field('room', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('quizcmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'kutilmoqda');
            $table->add_field('xato', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timegenerated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // Bitta baholash bitta kursga BIR MARTA tushadi
            $table->add_index('exam_course', XMLDB_INDEX_UNIQUE, ['examid', 'courseid']);
            $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        // --- O'quvchi natijasi
        $table = new xmldb_table('local_targetzenit_exam_result');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, true);
            $table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sittingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('score', XMLDB_TYPE_NUMBER, '7,2', null, null, null, null);
            $table->add_field('holat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'ishladi');
            $table->add_field('retake', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('capped', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('rawscore', XMLDB_TYPE_NUMBER, '7,2', null, null, null, null);
            $table->add_field('enteredby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('exam_student', XMLDB_INDEX_UNIQUE, ['examid', 'studentid']);
            $table->add_index('sittingid', XMLDB_INDEX_NOTUNIQUE, ['sittingid']);
            $table->add_index('student_course', XMLDB_INDEX_NOTUNIQUE, ['studentid', 'courseid']);
            $dbman->create_table($table);
        }

        // --- Chorak yakuni
        //
        // TUZOQ (2026-10-29 da yiqilganmiz): Postgres'da qo'shma indeks
        // chegarasi 333 belgi / 999 bayt. Tabiiy kalit (student+course+
        // academicyear 50 + termname 255) chegaradan oshadi, shuning uchun
        // unikal kalit `termkey` = md5 hash ustiga qo'yiladi.
        $table = new xmldb_table('local_targetzenit_term_score');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, true);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('termname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('termkey', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $table->add_field('fb', XMLDB_TYPE_NUMBER, '6,2', null, null, null, null);
            $table->add_field('bsb', XMLDB_TYPE_NUMBER, '6,2', null, null, null, null);
            $table->add_field('chsb', XMLDB_TYPE_NUMBER, '6,2', null, null, null, null);
            $table->add_field('total', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
            $table->add_field('baho', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
            $table->add_field('parts', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_field('divisor', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
            $table->add_field('frozen', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('termkey', XMLDB_INDEX_UNIQUE, ['termkey']);
            $table->add_index('course_term', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'termname']);
            $table->add_index('studentid', XMLDB_INDEX_NOTUNIQUE, ['studentid']);
            $table->add_index('frozen', XMLDB_INDEX_NOTUNIQUE, ['frozen']);
            $dbman->create_table($table);
        }

        // --- Baho o'zgarishlari tarixi (e-kundalik talabi: 72 soatlik oyna)
        //
        // NEGA HOZIR, 10-bosqichda emas: o'tgan chorakni kiritish paytidagi
        // tuzatishlar izsiz qolmasligi kerak — tarixni keyin orqaga tiklab
        // bo'lmaydi.
        $table = new xmldb_table('local_targetzenit_grade_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, true);
            $table->add_field('turi', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'fb');
            $table->add_field('recordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('oldvalue', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('newvalue', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('approvedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('latehours', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('turi_record', XMLDB_INDEX_NOTUNIQUE, ['turi', 'recordid']);
            $table->add_index('student_time', XMLDB_INDEX_NOTUNIQUE, ['studentid', 'timecreated']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026110840, 'local', 'targetzenit');
    }

    return true;
}

/**
 * Murojaat jadvallarining ta'rifi (yagona manba).
 *
 * `install.xml` bilan bir xil bo'lishi SHART: yangi o'rnatish o'sha fayldan,
 * mavjud sayt esa shu funksiyadan quriladi.
 *
 * @param string $nom jadval nomi
 * @return xmldb_table
 */
function local_targetzenit_murojaat_jadval(string $nom): xmldb_table {
    $t = new xmldb_table($nom);

    switch ($nom) {
        case 'local_targetzenit_murojaat':
            $t->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $t->add_field('frappename', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $t->add_field('turi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Shikoyat');
            $t->add_field('kategoriya', XMLDB_TYPE_CHAR, '150', null, null, null, null);
            $t->add_field('yonalish', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'CEO');
            $t->add_field('muhimlik', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, "O'rta");
            $t->add_field('matn', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $t->add_field('studentuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('studentidnumber', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('studentname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $t->add_field('sinf', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('payername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $t->add_field('payerphone', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $t->add_field('manageruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('manageridnumber', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('managername', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $t->add_field('manba', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $t->add_field('qarzsumma', XMLDB_TYPE_NUMBER, '18, 2', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('qarzholat', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('qarzkechikish', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $t->add_field('suhbatvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('ochilgan', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('slasana', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('holat', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'Yangi');
            $t->add_field('masuluserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('korildikim', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('korildivaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('qarorturi', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $t->add_field('qaror', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $t->add_field('qarorkim', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('qarorvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('otaonakordi', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('yopilganvaqti', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $t->add_index('frappename', XMLDB_INDEX_UNIQUE, ['frappename']);
            $t->add_index('yonalish_holat', XMLDB_INDEX_NOTUNIQUE, ['yonalish', 'holat']);
            $t->add_index('studentuserid', XMLDB_INDEX_NOTUNIQUE, ['studentuserid']);
            $t->add_index('masuluserid', XMLDB_INDEX_NOTUNIQUE, ['masuluserid']);
            $t->add_index('timemodified', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);
            return $t;

        case 'local_targetzenit_murojaat_izoh':
            $t->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $t->add_field('murojaatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $t->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('turi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'komment');
            $t->add_field('korinish', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'rahbar');
            $t->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $t->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $t->add_index('murojaat_time', XMLDB_INDEX_NOTUNIQUE, ['murojaatid', 'timecreated']);
            return $t;

        case 'local_targetzenit_murojaat_suhbat':
            $t->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $t->add_field('murojaatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $t->add_field('vaqt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_field('menejer', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $t->add_field('kanal', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $t->add_field('natija', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('suhbatnatija', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $t->add_field('izoh', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $t->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $t->add_index('murojaat_vaqt', XMLDB_INDEX_NOTUNIQUE, ['murojaatid', 'vaqt']);
            return $t;
    }

    throw new coding_exception('Noma\'lum murojaat jadvali: ' . $nom);
}
