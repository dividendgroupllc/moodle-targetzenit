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

    if ($oldversion < 2026102920) {

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
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('scope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'global');
            $table->add_field('scopekey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('value', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('norm_key', XMLDB_INDEX_UNIQUE,
                ['academicyear', 'scope', 'scopekey', 'code']);
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

        upgrade_plugin_savepoint(true, 2026102920, 'local', 'targetzenit');
    }

    return true;
}
