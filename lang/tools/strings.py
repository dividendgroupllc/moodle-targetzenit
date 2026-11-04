# -*- coding: utf-8 -*-
"""Til satrlarining YAGONA MANBASI — bundan lang/{en,uz,ru} generatsiya qilinadi.

NEGA SHUNDAY: uchta faylni qo'lda yuritganda ular bir-biridan ajralib ketadi —
kalit bittasiga qo'shilib, boshqasida esdan chiqadi. Bu jadvalda har kalit
uchta tilda yonma-yon turadi, ya'ni tushib qolgani darhol ko'rinadi.

Ishlatish:
    python3 lang/tools/gen.py        # uchta lang faylini qayta yozadi

Moodle qoidasi: `en` — ZAXIRA til. Tarjimasi yo'q kalit `en` dan olinadi,
shuning uchun `en` to'liq va INGLIZ tilida bo'lishi shart.
"""

# kalit: (en, uz, ru)
S = {
    # ---------------------------------------------------------------- umumiy
    "pluginname": (
        "Target Zenit integration",
        "Target Zenit integratsiyasi",
        "Интеграция Target Zenit",
    ),
    "privacy:metadata": (
        "The Target Zenit integration plugin does not store any personal data. "
        "User records are created and updated through Moodle core web services "
        "by the Frappe ERP system.",
        "Target Zenit integratsiya plagini shaxsiy ma'lumot saqlamaydi. "
        "Foydalanuvchi yozuvlari Frappe ERP tizimi tomonidan Moodle yadrosining "
        "web-servislari orqali yaratiladi va yangilanadi.",
        "Плагин интеграции Target Zenit не хранит персональных данных. "
        "Записи пользователей создаются и обновляются системой Frappe ERP "
        "через веб-сервисы ядра Moodle.",
    ),
    "error": ("{$a}", "{$a}", "{$a}"),

    # ------------------------------------------------------------- ruxsatlar
    "targetzenit:push": (
        "Push timetable and calendar data from Frappe",
        "Frappe'dan jadval va kalendarni yuborish",
        "Отправлять расписание и календарь из Frappe",
    ),
    "targetzenit:teach": (
        "Record topic, attendance and grades for a lesson",
        "Dars mavzusi, yo'qlama va baho qo'yish",
        "Записывать тему урока, посещаемость и оценки",
    ),
    "targetzenit:viewanalysis": (
        "View AI analysis of any lesson recording",
        "Har qanday dars yozuvining AI tahlilini ko'rish",
        "Просматривать ИИ-анализ любой записи урока",
    ),
    "targetzenit:viewalltimetable": (
        "View the whole-school timetable",
        "Butun maktab jadvalini ko'rish",
        "Просматривать расписание всей школы",
    ),
    "targetzenit:viewallpayroll": (
        "View payroll of all staff",
        "Barcha xodimlarning oyligini ko'rish",
        "Просматривать зарплату всех сотрудников",
    ),
    "targetzenit:viewteachers": (
        "View the teaching activity of all teachers (director panel)",
        "Barcha o'qituvchilarning dars jarayonini ko'rish (direktor paneli)",
        "Просматривать учебную деятельность всех учителей (панель директора)",
    ),
    "targetzenit:givefeedback": (
        "Write feedback on a lesson or a teacher",
        "Dars yoki o'qituvchi haqida fidbek yozish",
        "Писать обратную связь по уроку или учителю",
    ),
    "targetzenit:viewprivatefeedback": (
        "View administrative notes hidden from the teacher",
        "O'qituvchiga ko'rinmaydigan ma'muriy qaydni ko'rish",
        "Просматривать административные заметки, скрытые от учителя",
    ),

    # -------------------------------------------------------- dars jadvali
    "timetable": ("Timetable", "Dars jadvali", "Расписание"),
    "timetable_my": ("My timetable", "Mening jadvalim", "Моё расписание"),
    "timetable_week": ("Week", "Hafta", "Неделя"),
    "timetable_all": ("Whole school", "Umumiy jadval", "Общее расписание"),
    "timetable_empty": ("No timetable found.", "Jadval topilmadi.", "Расписание не найдено."),
    "timetable_nolessons": ("No lessons on this day.", "Bu kuni dars yo'q.", "В этот день уроков нет."),
    "lesson": ("Lesson", "Dars", "Урок"),
    "room": ("Room", "Xona", "Аудитория"),
    "teacher": ("Teacher", "O'qituvchi", "Учитель"),
    "group": ("Group", "Guruh", "Группа"),
    "subject": ("Subject", "Fan", "Предмет"),

    # -------------------------------------------------------- dars kokpiti
    "topic": ("Lesson topic", "Dars mavzusi", "Тема урока"),
    "homework": ("Homework", "Uy vazifa", "Домашнее задание"),
    "student": ("Student", "O'quvchi", "Ученик"),
    "attendance": ("Attendance", "Yo'qlama", "Посещаемость"),
    "grade": ("Grade", "Baho", "Оценка"),
    "comment": ("Comment", "Izoh", "Комментарий"),
    "journal": ("Journal — previous lessons", "Jurnal — oldingi darslar", "Журнал — предыдущие уроки"),
    "average": ("Average", "O'rtacha", "Средний балл"),
    "openlesson": ("Open lesson", "Darsni ochish", "Открыть урок"),
    "mygrades": ("My grades", "Baholarim", "Мои оценки"),

    # ---------------------------------------------------------- dars audiosi
    "audio_settings": ("Lesson audio recording", "Dars audio yozuvi", "Аудиозапись урока"),
    "audio_enabled": ("Enable audio recording", "Audio yozuvni yoqish", "Включить аудиозапись"),
    "audio_enabled_desc": (
        "Teachers can start recording from the lesson page.",
        "O'qituvchi dars sahifasida yozishni boshlay oladi.",
        "Учитель может начать запись на странице урока.",
    ),
    "audio_retention": ("Retention period (days)", "Saqlash muddati (kun)", "Срок хранения (дней)"),
    "audio_retention_desc": (
        "Audio older than this is deleted AUTOMATICALLY, whether or not it has "
        "been transcribed. The transcript text is kept. Reason: about 7.5 GB of "
        "audio accumulates per week and the disk must not fill up.",
        "Shu kundan oshgan audio AVTOMATIK o'chiriladi — transkript qilingan "
        "yoki qilinmaganidan qat'i nazar. Transkript matni o'chmaydi. Sabab: "
        "haftasiga ~7.5 GB audio to'planadi, disk to'lib qolmasligi kerak.",
        "Аудио старше указанного срока удаляется АВТОМАТИЧЕСКИ — независимо от "
        "того, расшифровано оно или нет. Текст расшифровки сохраняется. "
        "Причина: за неделю накапливается около 7,5 ГБ аудио, диск не должен "
        "переполниться.",
    ),
    "audio_maxmb": ("Limit per recording (MB)", "Bitta yozuv uchun chegara (MB)", "Лимит на одну запись (МБ)"),
    "audio_maxmb_desc": (
        "Recording stops above this size. At 24 kbps, 120 MB is about 11 hours.",
        "Shu hajmdan oshsa yozish to'xtaydi. 24 kbps da 120 MB ~ 11 soat.",
        "При превышении этого размера запись останавливается. При 24 кбит/с "
        "120 МБ — около 11 часов.",
    ),
    "audio_record": ("Record the lesson", "Darsni yozib olish", "Записать урок"),
    "audio_stop": ("Stop and save", "To'xtatish va saqlash", "Остановить и сохранить"),
    "audio_recording": ("Recording", "Yozilmoqda", "Идёт запись"),
    "audio_ready": ("Recording saved", "Yozuv saqlandi", "Запись сохранена"),
    "audio_cleanup": (
        "Delete expired lesson audio",
        "Muddati o'tgan dars audiolarini o'chirish",
        "Удалить устаревшие аудиозаписи уроков",
    ),
    "recordings": ("Lesson recordings", "Dars yozuvlari", "Записи уроков"),

    # ------------------------------------------------------------ AI tahlili
    "ai_settings": ("AI lesson analysis", "AI dars tahlili", "ИИ-анализ урока"),
    "ai_enabled": ("Enable AI analysis", "AI tahlilni yoqish", "Включить ИИ-анализ"),
    "ai_enabled_desc": (
        "Recorded lessons are transcribed and analysed automatically.",
        "Yozib olingan dars avtomatik transkript qilinadi va tahlil qilinadi.",
        "Записанный урок автоматически расшифровывается и анализируется.",
    ),
    "ai_key": ("Gemini API key", "Gemini API kalit", "Ключ Gemini API"),
    "ai_key_desc": (
        "Key obtained from Google AI Studio.",
        "Google AI Studio dan olingan kalit.",
        "Ключ, полученный в Google AI Studio.",
    ),
    "ai_model": ("Model", "Model", "Модель"),
    "ai_model_desc": ("For example: gemini-3.8-flash", "Masalan: gemini-3.8-flash", "Например: gemini-3.8-flash"),
    "ai_fallback": ("Fallback model", "Zaxira model", "Резервная модель"),
    "ai_fallback_desc": (
        "Used when the main model runs out of quota. Leave empty to disable.",
        "Asosiy modelning kvotasi tugasa shunga o'tiladi. Bo'sh qoldirilsa "
        "zaxira ishlatilmaydi.",
        "Используется, когда у основной модели закончилась квота. Оставьте "
        "пустым, чтобы отключить.",
    ),
    "ai_perrun": ("Lessons per run", "Bir yurishda nechta dars", "Уроков за один запуск"),
    "ai_perrun_desc": (
        "This many recordings are analysed every 15 minutes. A high value "
        "exhausts the quota quickly.",
        "Har 15 daqiqada shuncha yozuv tahlil qilinadi. Ko'p qo'yilsa kvota "
        "tez tugaydi.",
        "Столько записей анализируется каждые 15 минут. При большом значении "
        "квота расходуется быстро.",
    ),
    "ai_segment": (
        "Transcription segment (minutes)",
        "Transkripsiya bo'lagi (daqiqa)",
        "Фрагмент расшифровки (минут)",
    ),
    "ai_segment_desc": (
        "A long lesson is split into segments of this length and each is "
        "transcribed separately. Asked for a 45-minute lesson in one request, "
        "the model hits its output limit and cuts the text in the middle. "
        "5 minutes is a tested value. Set 0 to disable splitting.",
        "Uzun dars shu uzunlikdagi bo'laklarga ajratilib, har biri alohida "
        "matnga o'giriladi. Bitta so'rovda 45 daqiqalik dars so'ralsa, model "
        "javob uzunligi chegarasiga urilib matnni o'rtasidan kesadi. "
        "5 daqiqa — sinalgan qiymat. 0 qo'yilsa bo'linmaydi.",
        "Длинный урок разбивается на фрагменты указанной длины, каждый "
        "расшифровывается отдельно. Если запросить 45-минутный урок одним "
        "запросом, модель упирается в предел длины ответа и обрывает текст на "
        "середине. 5 минут — проверенное значение. 0 — не разбивать.",
    ),
    "ai_ffmpeg": ("Path to ffmpeg", "ffmpeg yo'li", "Путь к ffmpeg"),
    "ai_ffmpeg_desc": (
        "Needed to split audio into segments (apt install ffmpeg). If not "
        "found, audio is not split and long lessons may be transcribed only "
        "partially.",
        "Audioni bo'laklarga ajratish uchun kerak (apt install ffmpeg). "
        "Topilmasa bo'linmaydi va uzun darslarning matni chala qolishi mumkin.",
        "Нужен для разбиения аудио на фрагменты (apt install ffmpeg). Если не "
        "найден, аудио не разбивается и длинные уроки могут быть расшифрованы "
        "не полностью.",
    ),
    "ai_analyse": ("Lesson analysis", "Dars tahlili", "Анализ урока"),
    "ai_pending": ("Analysis queued", "Tahlil navbatda", "Анализ в очереди"),
    "ai_running": ("Analysis in progress", "Tahlil qilinmoqda", "Анализ выполняется"),
    "ai_task": (
        "Analyse lesson recordings with AI",
        "Dars yozuvlarini AI bilan tahlil qilish",
        "Анализировать записи уроков с помощью ИИ",
    ),

    # --------------------------------------------------------------- oylik
    "mypayroll": ("My payroll", "Mening oyligim", "Моя зарплата"),
    "pr_noidnumber": (
        "Your profile has no ID number, so payroll data could not be linked. "
        "Please contact the administrator.",
        "Profilingizda ID raqami yo'q, shuning uchun oylik ma'lumoti "
        "bog'lanmadi. Administratorga murojaat qiling.",
        "В вашем профиле нет идентификационного номера, поэтому данные о "
        "зарплате не связаны. Обратитесь к администратору.",
    ),
    "pr_nodata": (
        "No payroll data yet. Data is refreshed every morning.",
        "Hali oylik ma'lumoti kelmagan. Ma'lumot har kuni ertalab yangilanadi.",
        "Данных о зарплате пока нет. Данные обновляются каждое утро.",
    ),
    "pr_nomonth": (
        "No data for this month.",
        "Bu oy uchun ma'lumot yo'q.",
        "За этот месяц данных нет.",
    ),
    "pr_month": ("Month", "Oy", "Месяц"),
    "pr_inprogress": ("in progress", "davom etmoqda", "продолжается"),
    "pr_open_notice": (
        "The month is not closed yet — the figures may change until the end of "
        "the month. Last update: {$a}",
        "Oy hali yopilmagan — sonlar oy oxirigacha o'zgarishi mumkin. "
        "Oxirgi yangilanish: {$a}",
        "Месяц ещё не закрыт — суммы могут измениться до конца месяца. "
        "Последнее обновление: {$a}",
    ),
    "pr_takehome": ("Take-home pay", "Qo'lga tegadigan summa", "Сумма на руки"),
    "pr_workeddays": ("Days worked", "Ishlagan kun", "Отработано дней"),
    "pr_workedhours": ("Hours worked", "Ishlagan soat", "Отработано часов"),
    "pr_worked": ("Worked", "Ishlagan", "Отработано"),
    "pr_bonus": ("Bonus", "Bonus", "Премия"),
    "pr_bonus_penalty": ("Bonus / penalty", "Bonus / jarima", "Премия / штраф"),
    "pr_breakdown": ("Calculation details", "Hisob tafsiloti", "Детали расчёта"),
    "pr_indicator": ("Item", "Ko'rsatkich", "Показатель"),
    "pr_value": ("Value", "Qiymat", "Значение"),
    "pr_salary": ("Contract salary", "Shartnoma oyligi", "Оклад по договору"),
    "pr_hourrate": ("Hourly rate", "1 soat narxi", "Стоимость часа"),
    "pr_normdays": ("Standard working days this month", "Shu oydagi norma ish kuni", "Норма рабочих дней за месяц"),
    "pr_dayrate": ("Daily rate", "Bir kunlik narx", "Стоимость одного дня"),
    "pr_attended": ("Days attended", "Kelgan kunlar", "Дней присутствия"),
    "pr_gross": ("Calculated pay", "Hisoblangan ish haqi", "Начисленная оплата"),
    "pr_tax": ("Tax withheld", "Soliq ushlovi", "Удержание налога"),
    "pr_meals": ("Meals", "Ovqat (pitaniya)", "Питание"),
    "pr_days": ("Day-by-day attendance", "Kunma-kun davomat", "Посещаемость по дням"),
    "pr_days_hint_source": (
        "Entry and exit times come from the Face ID turnstile.",
        "Kirish va chiqish vaqti Face ID turniketidan olingan.",
        "Время входа и выхода берётся с турникета Face ID.",
    ),
    "pr_days_hint_hours": ("Column: hours on that day.", "Ustun: shu kundagi soat.", "Столбец: часы за этот день."),
    "pr_days_hint_koef": (
        "Column: coefficient for that day (1 = full day, 0.5 = half day, "
        "0 = absent).",
        "Ustun: shu kundagi koeffitsient (1 = to'liq kun, 0.5 = yarim kun, "
        "0 = kelmagan).",
        "Столбец: коэффициент за этот день (1 = полный день, 0,5 = полдня, "
        "0 = отсутствовал).",
    ),
    "pr_day": ("Day", "Kun", "День"),
    "pr_weekday": ("Weekday", "Hafta kuni", "День недели"),
    "pr_hours": ("Hours", "Soat", "Часы"),
    "pr_koef": ("Coef.", "Koef", "Коэф."),
    "pr_in": ("In", "Kirish", "Вход"),
    "pr_out": ("Out", "Chiqish", "Выход"),
    "pr_unit_day": ("d", "kun", "дн."),
    "pr_unit_hour": ("h", "soat", "ч."),
    "pr_footnote": (
        "This data is a copy from the Frappe (accounting) system. If you have "
        "questions, please contact the accounting department.",
        "Bu ma'lumot Frappe (buxgalteriya) tizimidan olingan nusxa. Savol "
        "bo'lsa buxgalteriyaga murojaat qiling.",
        "Эти данные — копия из системы Frappe (бухгалтерия). По вопросам "
        "обращайтесь в бухгалтерию.",
    ),
    "pr_allstaff": ("All staff — {$a}", "Barcha xodimlar — {$a}", "Все сотрудники — {$a}"),
    "pr_employee": ("Employee", "Xodim", "Сотрудник"),
    "pr_position": ("Position", "Lavozim", "Должность"),
    "pr_linked": ("In Moodle", "Moodle'da", "В Moodle"),
    "pr_notlinked": ("not linked", "bog'lanmagan", "не связан"),
    "pr_currency_uzs": ("UZS", "so'm", "сум"),

    # ----------------------------------------------------- mobil ilova
    "app_title": ("Target Zenit", "Target Zenit", "Target Zenit"),
    "app_welcome": (
        "Target Zenit panels",
        "Target Zenit panellari",
        "Панели Target Zenit",
    ),
    "app_ok": (
        "The app connection is working.",
        "Ilova bilan bog'lanish ishlayapti.",
        "Связь с приложением работает.",
    ),
    "app_soon": ("Coming soon", "Tez orada", "Скоро"),
    "app_openweb": ("Open in browser", "Brauzerda ochish", "Открыть в браузере"),
    "app_nolink": (
        "Your account is not linked to a staff or student record, so some "
        "panels will be empty.",
        "Hisobingiz xodim yoki o'quvchi yozuviga bog'lanmagan, shuning uchun "
        "ba'zi panellar bo'sh bo'ladi.",
        "Ваша учётная запись не связана с записью сотрудника или ученика, "
        "поэтому некоторые панели будут пустыми.",
    ),

    # --------------------------------------------- yo'qlama belgilari
    "att_present": ("Present", "Keldi", "Присутствовал"),
    "att_absent": ("Absent", "Kelmadi", "Отсутствовал"),
    "att_late": ("Late", "Kechikdi", "Опоздал"),
    "att_excused": ("Excused", "Sababli", "По уважительной"),

    # ------------------------------------------ dars kokpiti (veb sahifa)
    "ck_topicph": (
        "Today's lesson topic",
        "Bugungi dars mavzusi",
        "Тема сегодняшнего урока",
    ),
    "ck_homeworkph": (
        "Homework for the next lesson",
        "Keyingi darsga uy vazifa",
        "Домашнее задание к следующему уроку",
    ),
    "ck_nostudents": (
        "No students are enrolled in this course.",
        "Bu kursga o'quvchilar yozilmagan.",
        "На этот курс не записаны ученики.",
    ),
    "ck_saved": ("Saved", "Saqlandi", "Сохранено"),
    "ck_lessonno": ("Lesson {$a}", "{$a}-dars", "Урок {$a}"),
    # Jurnal setkasidagi ustun sarlavhasi — joy tor, qisqa shakl.
    "ck_lessonshort": ("L{$a}", "{$a}-d", "У{$a}"),
    "ck_save": ("Save", "Saqlash", "Сохранить"),

    # -------------------------------------------------- chorak jurnali (veb)
    "jr_noterms": (
        "Term dates not found \u2014 the timetable and calendar have not been "
        "sent from Frappe.",
        "Chorak sanalari topilmadi \u2014 Frappe'dan jadval va kalendar "
        "yuborilmagan.",
        "Даты четвертей не найдены \u2014 расписание и календарь не "
        "отправлены из Frappe.",
    ),
    "jr_nolessons": (
        "No lessons found for this course in this term (not in the timetable, "
        "or the term is empty).",
        "Bu chorakda bu kurs uchun dars topilmadi (jadvalda yo'q yoki chorak "
        "bo'sh).",
        "За эту четверть уроков по этому курсу не найдено (нет в расписании "
        "или четверть пуста).",
    ),
    "jr_saved": ("Saved", "Saqlandi", "Сохранено"),
    "jr_savedskipped": (
        "Saved. {$a} cells were skipped because they could not be understood.",
        "Saqlandi. {$a} ta katak tushunarsiz bo'lgani uchun o'tkazib yuborildi.",
        "Сохранено. {$a} ячеек пропущено, так как их не удалось распознать.",
    ),
    "jr_lessonshort": ("#{$a}", "{$a}-d", "№{$a}"),
    "jr_offschedule": (
        "Not in the timetable, but a journal record exists.",
        "Jadvalda yo'q, lekin jurnalda yozuv bor.",
        "Нет в расписании, но запись в журнале есть.",
    ),
    "jr_nofile": ("No file was selected.", "Fayl tanlanmadi.", "Файл не выбран."),
    "jr_studentgrades": ("{$a} \u2014 grades", "{$a} \u2014 baholari", "{$a} \u2014 оценки"),
    "jr_topics": ("Topics \u2014 {$a}", "Mavzular \u2014 {$a}", "Темы \u2014 {$a}"),
    "jr_date": ("Date", "Sana", "Дата"),
    "jr_topichw": ("Topic / homework", "Mavzu / uy vazifa", "Тема / домашнее задание"),
    "jr_topicph": ("Topic", "Mavzu", "Тема"),
    "jr_hwph": ("Homework", "Uy vazifa", "Домашнее задание"),
    "jr_upload": (
        "Upload the term plan (Excel or CSV)",
        "Chorak rejasini yuklash (Excel yoki CSV)",
        "Загрузить план четверти (Excel или CSV)",
    ),
    "jr_uploadhint": (
        "Columns: <b>No. | Topic | Homework</b>. Dates are not written \u2014 "
        "topics are placed one after another onto this term's lessons "
        "(currently {$a} lessons).",
        "Ustunlar: <b>\u2116 | Mavzu | Uy vazifa</b>. Sana yozilmaydi \u2014 "
        "mavzular shu chorakdagi darslarga ketma-ket joylashadi (hozir {$a} "
        "ta dars bor).",
        "Столбцы: <b>\u2116 | Тема | Домашнее задание</b>. Дата не "
        "записывается \u2014 темы раскладываются подряд по урокам этой "
        "четверти (сейчас уроков: {$a}).",
    ),
    "jr_overwrite": (
        " Overwrite topics that are already filled in (left untouched by default)",
        " To'ldirilgan mavzularning ustiga yozilsin (standart holatda tegilmaydi)",
        " Перезаписывать уже заполненные темы (по умолчанию не трогаются)",
    ),
    "jr_uploadbtn": ("Upload", "Yuklash", "Загрузить"),
    "jr_planresult": (
        "{$a->reja} topics in the plan, {$a->darslar} lessons in the term. "
        "Written: {$a->yozildi}{$a->otkazildi}",
        "Rejada {$a->reja} mavzu, chorakda {$a->darslar} dars. "
        "Yozildi: {$a->yozildi}{$a->otkazildi}",
        "В плане тем: {$a->reja}, уроков в четверти: {$a->darslar}. "
        "Записано: {$a->yozildi}{$a->otkazildi}",
    ),
    "jr_planskipped": (
        ", left untouched (already filled in): {$a}",
        ", tegilmadi (allaqachon to'ldirilgan): {$a}",
        ", не тронуто (уже заполнено): {$a}",
    ),
    "jr_filefailed": (
        "The file could not be read: {$a}",
        "Faylni o'qib bo'lmadi: {$a}",
        "Не удалось прочитать файл: {$a}",
    ),
    "jr_celllegend": (
        "Cell: <b>empty</b> = present \u00b7 <b>number</b> = grade (1\u2013{$a}) "
        "\u00b7 <b>k</b> = absent \u00b7 <b>s</b> = excused \u00b7 "
        "<b>kech</b> = late",
        "Katak: <b>bo'sh</b> = keldi \u00b7 <b>raqam</b> = baho (1\u2013{$a}) "
        "\u00b7 <b>k</b> = kelmadi \u00b7 <b>s</b> = sababli \u00b7 "
        "<b>kech</b> = kechikdi",
        "Ячейка: <b>пусто</b> = присутствовал \u00b7 <b>число</b> = оценка "
        "(1\u2013{$a}) \u00b7 <b>k</b> = отсутствовал \u00b7 "
        "<b>s</b> = по уважительной \u00b7 <b>kech</b> = опоздал",
    ),


    # ------------------------------- AI tahlili: kategoriya va guruh nomlari
    "ai_cat_clarity": ('Clarity of explanation', 'Tushuntirish aniqligi', 'Ясность объяснения'),
    "ai_cat_activating": ('Student engagement', "O'quvchilar faolligi", 'Активность учеников'),
    "ai_cat_topic_match": ('Match with the topic', 'Mavzuga muvofiqlik', 'Соответствие теме'),
    "ai_cat_student_understanding": ('Level of student understanding', "O'quvchilar o'zlashtirish darajasi", 'Уровень усвоения учениками'),
    "ai_cat_concept_development": ('Deepening of concepts', 'Tushunchani chuqurlashtirish', 'Углубление понятий'),
    "ai_cat_strategies": ('Teaching learning strategies', "O'quv strategiyalarini o'rgatish", 'Обучение учебным стратегиям'),
    "ai_cat_differentiation": ('Differentiated approach', 'Tabaqalashtirilgan yondashuv', 'Дифференцированный подход'),
    "ai_cat_positive_climate": ('Positive climate', 'Ijobiy muhit', 'Позитивный климат'),
    "ai_cat_negative_climate": ('Absence of negative climate', "Salbiy holatlarning yo'qligi", 'Отсутствие негатива'),
    "ai_cat_teacher_sensitivity": ('Teacher sensitivity', "O'qituvchining sezgirligi", 'Чуткость учителя'),
    "ai_cat_student_perspective": ('Regard for student perspectives', "O'quvchi fikriga e'tibor", 'Внимание к мнению учеников'),
    "ai_cat_management": ('Behaviour management', 'Xulq-atvorni boshqarish', 'Управление поведением'),
    "ai_cat_time_on_task": ('Productive use of time', 'Vaqtdan samarali foydalanish', 'Эффективное использование времени'),
    "ai_cat_feedback_quality": ('Quality of feedback', 'Fikr-mulohaza sifati', 'Качество обратной связи'),
    "ai_cat_language_modeling": ('Language modelling', 'Nutq namunasi va til boyligi', 'Речевой образец и богатство языка'),
    "ai_grp_quality": ('Instructional quality', "Ta'lim sifati", 'Качество обучения'),
    "ai_grp_emotional": ('Emotional support', "Emotsional qo'llab-quvvatlash", 'Эмоциональная поддержка'),
    "ai_grp_organization": ('Classroom organization', 'Sinfni tashkil etish', 'Организация класса'),
    "ai_grp_feedback": ('Feedback and language', 'Fikr-mulohaza va til', 'Обратная связь и язык'),

    # ------------------------------------------------- yozuv oynasi (rec.php)
    "rc_mic": ("Microphone:", "Mikrofon:", "Микрофон:"),
    "rc_stop": (
        "\u25a0 Stop and save",
        "\u25a0 To'xtatish va saqlash",
        "\u25a0 Остановить и сохранить",
    ),
    "rc_hint": (
        "<b>Do not close this window.</b> While it is open you can take "
        "attendance, enter grades and open the journal in the main window "
        "\u2014 the recording continues.<br><br>"
        "<b>If the internet drops, recording does NOT stop</b> \u2014 chunks "
        "are queued and sent automatically when the connection returns; no "
        "audio is lost.<br><br>"
        "<b>Pause</b> resumes by itself after 30 seconds \u2014 so a "
        "forgotten pause will not cost you the lesson.<br><br>"
        "Audio is kept for {$a} days.",
        "<b>Bu oynani yopmang.</b> U ochiq turganda asosiy oynada yo'qlama "
        "qilish, baho qo'yish va jurnalni ochish mumkin \u2014 yozuv davom "
        "etaveradi.<br><br>"
        "<b>Internet uzilsa yozuv TO'XTAMAYDI</b> \u2014 bo'laklar navbatda "
        "turadi va aloqa qaytishi bilan o'zi yuboriladi, ovoz "
        "yo'qolmaydi.<br><br>"
        "<b>Pauza</b> 30 soniyadan keyin o'zi davom etadi \u2014 unutib "
        "qo'yilsa ham dars yozilmay qolmaydi.<br><br>"
        "Audio {$a} kun saqlanadi.",
        "<b>Не закрывайте это окно.</b> Пока оно открыто, в основном окне "
        "можно отмечать посещаемость, ставить оценки и открывать журнал "
        "\u2014 запись продолжается.<br><br>"
        "<b>Если интернет пропадёт, запись НЕ остановится</b> \u2014 "
        "фрагменты встанут в очередь и отправятся сами при возвращении "
        "связи, звук не потеряется.<br><br>"
        "<b>Пауза</b> сама возобновляется через 30 секунд \u2014 забытая "
        "пауза не будет стоить вам урока.<br><br>"
        "Аудио хранится {$a} дней.",
    ),
    "rc_exists": (
        "\u2714 This lesson already has a recording ({$a} MB). Recording "
        "again replaces it.",
        "\u2714 Bu darsda yozuv bor ({$a} MB). Qaytadan yozsangiz eskisi "
        "almashadi.",
        "\u2714 У этого урока уже есть запись ({$a} МБ). Повторная запись "
        "заменит её.",
    ),

    # ---------------------------------------------------- dars audiosi (veb)
    "au_open": (
        "\u25cf Recording is open \u2014 it continues in a separate window.",
        "\u25cf Yozuv ochiq \u2014 alohida oynada davom etmoqda.",
        "\u25cf Запись идёт \u2014 она продолжается в отдельном окне.",
    ),
    "au_record": (
        "🎙 Record the lesson",
        "🎙 Darsni yozib olish",
        "🎙 Записать урок",
    ),
    "au_rerecord": (
        "🎙 Record again",
        "🎙 Qaytadan yozish",
        "🎙 Записать заново",
    ),
    "au_deleted": (
        "🎙 The audio was deleted because it expired "
        "(the analysis text remains).",
        "🎙 Audio muddati o'tgani uchun o'chirilgan "
        "(tahlil matni qoldi).",
        "🎙 Аудио удалено по истечении срока хранения "
        "(текст анализа сохранён).",
    ),
    "au_windowhint": (
        "Recording opens in a <b>separate window</b>. While it is open you can "
        "take attendance, enter grades and go to the journal on this page "
        "without interrupting the recording. Audio is kept for {$a} days.",
        "Yozuv <b>alohida oynada</b> ochiladi \u2014 u ochiq turganda bu "
        "sahifada yo'qlama qilish, baho qo'yish va jurnalga o'tish mumkin, "
        "yozuv uzilmaydi. Audio {$a} kun saqlanadi.",
        "Запись открывается в <b>отдельном окне</b>. Пока оно открыто, на "
        "этой странице можно отмечать посещаемость, ставить оценки и "
        "переходить в журнал \u2014 запись не прервётся. Аудио хранится "
        "{$a} дней.",
    ),
    "au_analysed": (
        "Analysed. The audio is kept for <b>{$a} more days</b> so that the "
        "analysis can be compared against the original recording.",
        "Tahlil qilingan. Ovoz yana <b>{$a} kun</b> saqlanadi \u2014 tahlil "
        "qanchalik to'g'ri chiqqanini asl yozuv bilan solishtirish uchun.",
        "Проанализировано. Аудио хранится ещё <b>{$a} дней</b>, чтобы можно "
        "было сверить анализ с оригинальной записью.",
    ),

    # ------------------------------------------- ilovadagi dars kokpiti
    "app_pastdays": (
        "Earlier days are listed below \u2014 tap one to grade a lesson you "
        "missed.",
        "Quyida oldingi kunlar ham bor \u2014 unutilgan darsni baholash uchun "
        "kerakli kunni bosing.",
        "Ниже перечислены предыдущие дни \u2014 нажмите на нужный, чтобы "
        "выставить оценки за пропущенный урок.",
    ),
    "app_savelesson": (
        "Save topic and homework",
        "Mavzu va uy vazifani saqlash",
        "Сохранить тему и домашнее задание",
    ),
    "app_recordweb": (
        "\U0001F399 Record the lesson",
        "\U0001F399 Darsni yozib olish",
        "\U0001F399 Записать урок",
    ),
    "app_recordhint": (
        "Recording opens in the browser: the app cannot access the microphone "
        "from inside a plugin page. Keep that window open \u2014 attendance "
        "and grades here are not affected.",
        "Yozuv brauzerda ochiladi: ilova plagin sahifasi ichidan mikrofonga "
        "kira olmaydi. O'sha oynani ochiq qoldiring \u2014 bu yerdagi "
        "yo'qlama va bahoga ta'sir qilmaydi.",
        "Запись открывается в браузере: приложение не может обратиться к "
        "микрофону со страницы плагина. Оставьте то окно открытым \u2014 на "
        "посещаемость и оценки здесь это не влияет.",
    ),
    "app_hasaudio": (
        "\u2714 This lesson has a recording ({$a} MB)",
        "\u2714 Bu darsda yozuv bor ({$a} MB)",
        "\u2714 У этого урока есть запись ({$a} МБ)",
    ),
    "app_openweb_lesson": (
        "Open the full lesson page",
        "To'liq dars sahifasini ochish",
        "Открыть полную страницу урока",
    ),
    "app_lesson": ("Lesson", "Dars", "Урок"),
    "app_todaylessons": ("Today's lessons", "Bugungi darslar", "Сегодняшние уроки"),
    "app_register": (
        "Attendance & grades",
        "Davomat va baho",
        "Посещаемость и оценки",
    ),
    "app_nolessontoday": (
        "You have no lessons today.",
        "Bugun darsingiz yo'q.",
        "Сегодня у вас нет уроков.",
    ),
    "app_students": ("Students", "O'quvchilar", "Ученики"),
    "app_nostudents": (
        "No students are enrolled in this course.",
        "Bu kursga o'quvchi yozilmagan.",
        "На этот курс не записаны ученики.",
    ),
    "app_savegrade": ("Save grade", "Bahoni saqlash", "Сохранить оценку"),
    "app_saved": ("Saved", "Saqlandi", "Сохранено"),
    "app_gradehint": (
        "Grade from 1 to {$a}. Leave empty to remove the grade.",
        "Baho 1 dan {$a} gacha. Bahoni olib tashlash uchun bo'sh qoldiring.",
        "Оценка от 1 до {$a}. Оставьте пустым, чтобы удалить оценку.",
    ),
    "app_attendancehint": (
        "Tap a status — it is saved immediately.",
        "Belgini bosing — darhol saqlanadi.",
        "Нажмите на статус — сохранится сразу.",
    ),
    "app_lessonsaved": (
        "The grade is also written to the Moodle gradebook.",
        "Baho Moodle gradebook'iga ham yoziladi.",
        "Оценка также записывается в журнал оценок Moodle.",
    ),
    "app_noteach": (
        "You do not have permission to teach this course.",
        "Bu kursda dars o'tish huquqingiz yo'q.",
        "У вас нет прав вести занятия по этому курсу.",
    ),

    # ------------------------------------- ilovadagi choraklik baholar
    "app_termgrades": ("Term grades", "Choraklik baholar", "Оценки по четвертям"),
    "app_nocourses": (
        "You are not teaching any courses.",
        "Sizga biriktirilgan kurs yo'q.",
        "За вами не закреплены курсы.",
    ),
    "app_nogradesyet": (
        "No grades in this course yet.",
        "Bu kursda hali baho qo'yilmagan.",
        "В этом курсе оценок пока нет.",
    ),
    "app_gradecount": ("{$a} grades", "{$a} ta baho", "оценок: {$a}"),
    "app_absences": ("{$a} absences", "{$a} ta qoldirgan", "пропусков: {$a}"),
    "app_termaverages": (
        "Average grade per term. Tap a lesson in Today's lessons to enter grades.",
        "Chorak bo'yicha o'rtacha baho. Baho qo'yish uchun \"Bugungi darslar\" "
        "dan darsni oching.",
        "Средний балл по четвертям. Чтобы выставить оценки, откройте урок в "
        "разделе \"Сегодняшние уроки\".",
    ),

    # ------------------------------------ ilovadagi chorak jurnali
    "app_journal": ("Term journal", "Chorak jurnali", "Журнал четверти"),
    "app_journalhint": (
        "The whole class in one grid: rows are students, columns are lessons. "
        "Scroll sideways to see more lessons. k = absent, s = excused, "
        "kech = late.",
        "Butun sinf bitta setkada: qatorlar \u2014 o'quvchilar, ustunlar "
        "\u2014 darslar. Qolgan darslarni ko'rish uchun yon tomonga suring. "
        "k \u2014 kelmadi, s \u2014 sababli, kech \u2014 kechikdi.",
        "Весь класс в одной сетке: строки \u2014 ученики, столбцы \u2014 "
        "уроки. Прокрутите вбок, чтобы увидеть остальные уроки. "
        "k \u2014 отсутствовал, s \u2014 по уважительной, kech \u2014 опоздал.",
    ),
    "app_journalterm": ("Term: {$a}", "Chorak: {$a}", "Четверть: {$a}"),
    "app_journallimit": (
        "The last {$a} lessons are shown.",
        "Oxirgi {$a} ta dars ko'rsatilgan.",
        "Показаны последние {$a} уроков.",
    ),
    "app_noterm_now": (
        "Today is outside any term, so there is no journal to show.",
        "Bugun chorak oralig'ida emas, shuning uchun jurnal yo'q.",
        "Сегодня вне учебной четверти, поэтому журнала нет.",
    ),

    "tt_weekend": ("Weekend", "Dam olish kuni", "Выходной"),
    "tt_outofterm": (
        "Outside the term (holiday)",
        "Chorakdan tashqari (ta'til)",
        "Вне четверти (каникулы)",
    ),

    # --------------------------------------------------- jadval sahifasi
    "tt_noclass": (
        "Your class could not be determined — you are not enrolled in any "
        "course yet.",
        "Sizning sinfingiz aniqlanmadi — hali birorta kursga yozilmagansiz.",
        "Ваш класс не определён — вы ещё не зачислены ни на один курс.",
    ),
    "tt_search": (
        "Search by class or teacher...",
        "Sinf yoki o'qituvchi bo'yicha qidirish...",
        "Поиск по классу или учителю...",
    ),
    "tt_classcount": ("{$a} classes", "{$a} sinf", "классов: {$a}"),
    "tt_prevweek": ("\u2190 Previous week", "\u2190 oldingi hafta", "\u2190 Предыдущая неделя"),
    "tt_thisweek": ("This week", "shu hafta", "Текущая неделя"),
    "tt_nextweek": ("Next week \u2192", "keyingi hafta \u2192", "Следующая неделя \u2192"),
    "tt_keymismatch": (
        "Your timetable could not be matched to your account, so you are "
        "seeing the whole class timetable instead of your own lessons. The "
        "timetable needs to be re-sent from Frappe. Please tell the "
        "administrator.",
        "Jadval sizning hisobingizga bog'lanmadi, shuning uchun o'z "
        "darslaringiz o'rniga butun sinf jadvalini ko'rib turasiz. Jadvalni "
        "Frappe'dan qayta yuborish kerak. Administratorga aytib qo'ying.",
        "Расписание не удалось сопоставить с вашей учётной записью, поэтому "
        "вы видите расписание всего класса, а не свои уроки. Расписание нужно "
        "заново отправить из Frappe. Сообщите администратору.",
    ),
    "tt_today_suffix": (" (today)", " (bugun)", " (сегодня)"),

    # ------------------------------------------------- baholarim sahifasi
    "mg_noterms": (
        "Term dates not found.",
        "Chorak sanalari topilmadi.",
        "Даты учебных четвертей не найдены.",
    ),
    "mg_term": ("Term", "Chorak", "Четверть"),
    "mg_nogrades": (
        "No grades have been given this term yet.",
        "Bu chorakda hali baho qo'yilmagan.",
        "В этой четверти оценок пока нет.",
    ),
    "mg_gradebooklink": (
        "📊 Moodle grade report (all courses)",
        "📊 Moodle baholar hisoboti (barcha kurslar)",
        "📊 Отчёт по оценкам Moodle (все курсы)",
    ),
    "mg_gradebookhint": (
        "Grades are stored in the Moodle gradebook — they also appear in the "
        "mobile app and in the standard reports. This page is for convenience: "
        "grades, attendance and homework in one place.",
        "Baholar Moodle gradebook'ida saqlanadi — mobil ilovada va standart "
        "hisobotlarda ham ko'rinadi. Bu sahifa qulaylik uchun: baho, davomat "
        "va uy vazifa bir joyda.",
        "Оценки хранятся в журнале оценок Moodle — они видны и в мобильном "
        "приложении, и в стандартных отчётах. Эта страница — для удобства: "
        "оценки, посещаемость и домашние задания в одном месте.",
    ),
    "mg_nograde": ("no grade", "baho yo'q", "нет оценки"),
    "mg_full": ("full attendance", "to'liq", "без пропусков"),
    "mg_absent": ("absent: {$a}", "kelmadi: {$a}", "пропустил: {$a}"),
    "mg_excused": ("excused: {$a}", "sababli: {$a}", "по уважительной: {$a}"),
    "mg_late": ("late: {$a}", "kechikdi: {$a}", "опоздал: {$a}"),
    "mg_hint": (
        "Hover over a grade to see its date and the lesson topic. "
        "<b>k</b> — absent, <b>s</b> — excused, <b>kech</b> — late.",
        "Bahoga sichqonchani olib borsangiz, sanasi va dars mavzusi ko'rinadi. "
        "<b>k</b> — kelmadi, <b>s</b> — sababli, <b>kech</b> — kechikdi.",
        "Наведите курсор на оценку, чтобы увидеть дату и тему урока. "
        "<b>k</b> — пропуск, <b>s</b> — по уважительной, <b>kech</b> — опоздание.",
    ),
    "mg_homework": ("Homework", "Uy vazifalar", "Домашние задания"),
    "mg_nohomework": (
        "No homework has been set for the coming days.",
        "Yaqin kunlarda uy vazifa yozilmagan.",
        "На ближайшие дни домашнее задание не задано.",
    ),
    "mg_homeworkhint": (
        "Every homework item is also created in Moodle as an <b>assignment</b> "
        "— it appears on the course page, in the Timeline on the dashboard and "
        "in the calendar, and is submitted from there.",
        "Har uy vazifa Moodle'da <b>topshiriq</b> sifatida ham ochiladi — "
        "kurs sahifasida, boshqaruv panelidagi \"Timeline\" da va kalendarda "
        "ko'rinadi, o'sha yerdan topshiriladi.",
        "Каждое домашнее задание создаётся в Moodle и как <b>задание</b> — оно "
        "видно на странице курса, в разделе \"Timeline\" на главной и в "
        "календаре, и сдаётся оттуда.",
    ),
    "mg_date": ("Date", "Sana", "Дата"),
    "mg_grades": ("Grades", "Baholar", "Оценки"),
    "mg_attendance": ("Attendance", "Davomat", "Посещаемость"),

    # ------------------------------------------- xodim bilan hisob-kitob
    "st_title": ("Settlement by month", "Oylar kesimida hisob-kitob", "Расчёты по месяцам"),
    "st_hint": (
        "Accrued and paid amounts come from the accounting system. "
        "\u00abRemaining\u00bb is what is still owed for that month; a plus sign "
        "means it was overpaid.",
        "Hisoblangan va to'langan summalar buxgalteriya tizimidan olingan. "
        "\u00abQoldiq\u00bb \u2014 shu oy uchun to'lanmagan qism; oldidagi "
        "ortiqcha belgisi ortiq to'langanini bildiradi.",
        "Начисленные и выплаченные суммы берутся из бухгалтерской системы. "
        "\u00abОстаток\u00bb \u2014 невыплаченная часть за этот месяц; знак "
        "плюс означает переплату.",
    ),
    "st_month": ("Month", "Oy", "Месяц"),
    "st_salary": ("Monthly pay", "Oylik ish haqi", "Оклад"),
    "st_accrued": ("Accrued", "Nachisleniya", "Начислено"),
    "st_paid": ("Paid", "To'langan", "Выплачено"),
    "st_remaining": ("Remaining", "Qoldiq", "Остаток"),
    "st_status": ("Status", "Holat", "Статус"),
    "st_total": ("TOTAL", "JAMI", "ИТОГО"),
    "st_average": ("average", "o'rtacha", "в среднем"),
    "st_unlinked": (
        "of which unallocated: {$a}",
        "shundan bog'lanmagan: {$a}",
        "из них не привязано: {$a}",
    ),
    "st_s_paid": ("Paid", "To'langan", "Выплачено"),
    "st_s_over": ("Overpaid", "Ortiq to'langan", "Переплата"),
    "st_s_partial": ("Partial", "Qisman", "Частично"),
    "st_s_unpaid": ("Unpaid", "To'lanmagan", "Не выплачено"),
    "st_s_advance": ("Advance", "Avans", "Аванс"),
    "st_none": (
        "No accruals or payments found.",
        "Nachisleniya yoki to'lov topilmadi.",
        "Начислений или выплат не найдено.",
    ),
    "st_currentmissing": (
        "{$a} is not in this table yet: the accrual is posted when the month "
        "is closed in the accounting system. The figures above are the "
        "calculation for the current month.",
        "{$a} bu jadvalda hali yo'q: nachisleniya buxgalteriyada oy "
        "yopilganda yoziladi. Yuqoridagi sonlar — joriy oyning hisobi.",
        "{$a} в этой таблице ещё нет: начисление проводится при закрытии "
        "месяца в бухгалтерии. Суммы выше — расчёт за текущий месяц.",
    ),
    "st_nosalary": (
        "No pay set for this month in the timesheet",
        "Oylik tabelda bu oy uchun ish haqi kiritilmagan",
        "В табеле за этот месяц оклад не указан",
    ),

    # ---------------------------------------------------------- direktor paneli
    "dir_panel": ("Director panel", "Direktor paneli", "Панель директора"),
    "dir_today": ("Today", "Bugun", "Сегодня"),
    "dir_since": (
        "monitoring since {$a}",
        "kuzatuv {$a} dan",
        "наблюдение с {$a}",
    ),
    "dir_lessons_today": ("lessons today", "bugungi dars", "уроков сегодня"),
    "dir_left": ("{$a} still to come", "{$a} tasi qoldi", "ещё {$a}"),
    "dir_passed": ("already finished", "o'tib bo'ldi", "уже прошло"),
    "dir_journal": ("journal filled", "jurnal to'ldirildi", "журнал заполнен"),
    "dir_recorded": ("recorded", "yozuv olindi", "запись сделана"),
    "dir_vsnorm": ("vs international average", "xalqaro o'rtachaga nisbatan",
                   "относительно мирового среднего"),
    "dir_noanalysis": ("no analysed lesson yet", "hali tahlil qilingan dars yo'q",
                       "ещё нет проанализированных уроков"),
    "dir_attention": ("Needs attention", "Diqqat talab qiladi", "Требует внимания"),
    "dir_attention_window": (
        "An action list, not a report: only lessons since {$a} are shown. "
        "The full picture is in the teacher list.",
        "Bu hisobot emas, amal ro'yxati: faqat {$a} dan keyingi darslar. "
        "To'liq manzara o'qituvchilar ro'yxatida.",
        "Это не отчёт, а список действий: только уроки с {$a}. "
        "Полная картина — в списке учителей.",
    ),
    "dir_attention_empty": (
        "Nothing needs attention — everything is filled in.",
        "Diqqat talab qiladigan narsa yo'q — hammasi to'ldirilgan.",
        "Ничего не требует внимания — всё заполнено.",
    ),
    "dir_more": (
        "{$a} such lessons in total in this window.",
        "Bu oynada jami {$a} ta shunday dars bor.",
        "Всего в этом окне {$a} таких уроков.",
    ),
    "dir_openlesson": ("Lesson", "Dars", "Урок"),
    "dir_grid": ("Day grid", "Kun setkasi", "Сетка дня"),
    "dir_grid_empty": (
        "No lessons scheduled for this day.",
        "Bu kunga dars rejalashtirilmagan.",
        "На этот день уроки не запланированы.",
    ),
    "dir_grid_legend": (
        "The number in a cell = how many of the three required duties are done "
        "(topic, attendance, recording). A grade is not expected in every lesson, "
        "so it is shown as a separate dot. A dashed cell means the lesson has not "
        "happened yet.",
        "Katakdagi raqam = uchta majburiy vazifadan nechtasi bajarilgan "
        "(mavzu, yo'qlama, yozuv). Baho har darsda kutilmaydi, shuning uchun "
        "alohida nuqta bilan ko'rsatiladi. Uzuq chiziqli katak — dars hali bo'lmagan.",
        "Число в ячейке = сколько из трёх обязательных задач выполнено "
        "(тема, посещаемость, запись). Оценка не ожидается на каждом уроке, "
        "поэтому показана отдельной точкой. Пунктирная ячейка — урок ещё не прошёл.",
    ),
    "dir_teacher": ("Teacher", "O'qituvchi", "Учитель"),
    "dir_notyet": ("has not happened yet", "hali bo'lmagan", "ещё не прошёл"),
    "dir_hasgrade": ("a grade was given", "baho qo'yilgan", "оценка выставлена"),
    "dir_unlinked": ("not linked", "bog'lanmagan", "не связан"),
    "dir_unlinked_help": (
        "This timetable key is not linked to a Moodle account, so these lessons "
        "do not count towards anyone. Either a vacancy, or Instructor has no "
        "Employee linked in Frappe.",
        "Bu jadval kaliti Moodle hisobiga bog'lanmagan, ya'ni bu darslar hech "
        "kimning hisobotiga tushmaydi. Yo vakansiya, yoki Frappe'da `Instructor` "
        "ga `Employee` bog'lanmagan.",
        "Этот ключ расписания не связан с учётной записью Moodle, поэтому эти "
        "уроки никому не зачитываются. Либо вакансия, либо в Frappe у Instructor "
        "не привязан Employee.",
    ),
    "dir_setting_start": (
        "Monitoring start date",
        "Kuzatuv boshlanish sanasi",
        "Дата начала наблюдения",
    ),
    "dir_setting_start_desc": (
        "YYYY-MM-DD. Lessons before this date stay in the plan and the term map but "
        "are not counted in the director panel. Leave empty to detect it "
        "automatically from the first journal entry (the day the system went live).",
        "YYYY-MM-DD. Bu sanadan oldingi darslar rejada va chorak xaritasida qoladi, "
        "lekin direktor panelida hisobga olinmaydi. Bo'sh qoldirilsa — jurnaldagi "
        "birinchi yozuvdan avtomatik aniqlanadi (dastur ishga tushgan kun).",
        "ГГГГ-ММ-ДД. Уроки до этой даты остаются в плане и карте четверти, но не "
        "учитываются в панели директора. Оставьте пустым — определится "
        "автоматически по первой записи журнала (день запуска системы).",
    ),

    # --------------------------------------------- dars kartasi (faqat o'qish)
    "dir_lessoncard": ("Lesson card", "Dars kartasi", "Карточка урока"),
    "dir_analysis": ("Analysis", "Tahlil", "Анализ"),
    "dir_nojournal": (
        "Nothing was written for this lesson: the teacher never opened the lesson "
        "cockpit, so there is no topic, attendance or recording.",
        "Bu dars uchun hech narsa yozilmagan: o'qituvchi dars kokpitini ochmagan, "
        "ya'ni mavzu, yo'qlama va yozuv yo'q.",
        "По этому уроку ничего не записано: учитель не открывал кокпит урока, "
        "поэтому нет ни темы, ни посещаемости, ни записи.",
    ),
    "dir_wroteby": ("filled in by {$a}", "jurnalni to'ldirgan: {$a}", "журнал заполнил: {$a}"),
    "dir_opencockpit": ("Open the lesson cockpit", "Dars kokpitida ochish",
                        "Открыть в кокпите урока"),
    "dir_journalstate": ("Journal", "Jurnal holati", "Состояние журнала"),
    "dir_topic": ("topic", "mavzu", "тема"),
    "dir_notopic": ("no topic", "mavzu yozilmagan", "тема не записана"),
    "dir_hw": ("homework", "uy vazifa", "домашнее задание"),
    "dir_nohw": ("no homework", "uy vazifa yo'q", "домашнего задания нет"),
    "dir_noatt": ("attendance not taken", "yo'qlama qilinmagan", "посещаемость не отмечена"),
    "dir_attdone": ("attendance taken", "yo'qlama qilingan", "посещаемость отмечена"),
    "dir_attn": ("present {$a->kelgan} of {$a->jami}",
                 "{$a->jami} dan {$a->kelgan} tasi keldi",
                 "пришли {$a->kelgan} из {$a->jami}"),
    "dir_nograde": ("no grades given", "baho qo'yilmagan", "оценки не выставлены"),
    "dir_recording": ("Recording", "Yozuv", "Запись"),
    "dir_audiogone": (
        "The audio has been deleted after the retention period — the transcript "
        "and the analysis remain.",
        "Audio saqlash muddati o'tgach o'chirilgan — transkript va tahlil qoldi.",
        "Аудио удалено по истечении срока хранения — расшифровка и анализ остались.",
    ),
    "dir_audiorec": ("still recording", "hozir yozilmoqda", "идёт запись"),
    "dir_audionone": ("the audio file is missing", "audio fayl yo'q", "аудиофайла нет"),
    "dir_analysisnone": (
        "There is no analysis for this lesson yet — either no recording was made, "
        "or the analysis has not run.",
        "Bu dars uchun hali tahlil yo'q — yoki yozuv olinmagan, yoki tahlil "
        "ishga tushmagan.",
        "Для этого урока анализа ещё нет — либо запись не сделана, либо анализ "
        "не запускался.",
    ),
    "dir_aioff": (
        "AI analysis is switched off in the plugin settings.",
        "AI tahlil plagin sozlamalarida o'chirilgan.",
        "ИИ-анализ отключён в настройках плагина.",
    ),

    # --------------------------------------- direktor paneli: Edulytics ko'rinishi
    "dir_morning": ("Good morning, {$a}", "Xayrli tong, {$a}", "Доброе утро, {$a}"),
    "dir_afternoon": ("Good afternoon, {$a}", "Xayrli kun, {$a}", "Добрый день, {$a}"),
    "dir_evening": ("Good evening, {$a}", "Xayrli kech, {$a}", "Добрый вечер, {$a}"),

    "dir_navmain": ("Main", "Asosiy", "Основное"),
    "dir_navmanage": ("Management", "Boshqaruv", "Управление"),
    "dir_navlessons": ("Lesson analysis", "Darslar tahlili", "Анализ уроков"),
    "dir_navranks": ("Rankings", "Reytinglar", "Рейтинги"),
    "dir_navsettings": ("Settings", "Sozlamalar", "Настройки"),

    "dir_lessonstitle": ("Today's lesson analysis", "Bugungi darslar tahlili",
                         "Анализ сегодняшних уроков"),
    "dir_lessonssub": (
        "Lesson recordings analysed by AI, and the recommendations",
        "AI yordamida tahlil qilingan dars yozuvlari va tavsiyalar",
        "Записи уроков, проанализированные ИИ, и рекомендации",
    ),
    "dir_byschedule": ("by the timetable", "dars jadvali bo'yicha", "по расписанию"),
    "dir_totalaudio": ("Total audio length", "Umumiy audio davomiyligi",
                       "Общая длительность аудио"),
    "dir_nrecordings": ("{$a} recordings", "{$a} ta yozuv", "{$a} записей"),
    "dir_quality": ("Average quality score", "O'rtacha sifat ko'rsatkichi",
                    "Средний показатель качества"),
    "dir_vsnormshort": (
        "{$a->delta} vs the international average ({$a->norma}%) · n = {$a->n}",
        "xalqaro o'rtachadan {$a->delta}% ({$a->norma}%) · n = {$a->n}",
        "{$a->delta}% к мировому среднему ({$a->norma}%) · n = {$a->n}",
    ),
    "dir_notsent": ("No recording sent", "Yozuv yuborilmagan", "Запись не отправлена"),
    "dir_sendreminder": ("needs a reminder", "o'qituvchiga eslatma kerak",
                         "нужно напомнить учителю"),

    "dir_control": ("Today's recording control", "Bugungi yozuvlar nazorati",
                    "Контроль сегодняшних записей"),
    "dir_controlsub": (
        "automatically matched against the timetable",
        "dars jadvali bilan avtomatik solishtirildi",
        "автоматически сверено с расписанием",
    ),
    "dir_fall": ("All", "Jami dars", "Всего"),
    "dir_frec": ("Recorded", "Yozdi", "Записал"),
    "dir_fmiss": ("Not recorded", "Yozmadi", "Не записал"),
    "dir_fsoon": ("Upcoming", "Hali bo'lmagan", "Ещё не прошли"),
    "dir_notrecorded": ("Not recorded", "Yozmadi", "Не записал"),
    "dir_norec": ("no recording", "yozuv yo'q", "записи нет"),
    "dir_seemissing": (
        "See all {$a} lessons without a recording",
        "Yozuvi yo'q {$a} ta darsni ko'rish",
        "Показать все {$a} уроков без записи",
    ),
    "dir_nolessons": ("No lessons found.", "Dars topilmadi.", "Уроки не найдены."),

    "dir_lessonlist": ("Lesson recordings", "Dars yozuvlari", "Записи уроков"),
    "dir_showing": ("{$a->n} of {$a->jami} shown", "{$a->jami} tadan {$a->n} tasi",
                    "показано {$a->n} из {$a->jami}"),
    "dir_search": ("Teacher, subject or class...", "O'qituvchi, fan yoki sinf...",
                   "Учитель, предмет или класс..."),
    "dir_searchbtn": ("Search", "Qidirish", "Искать"),
    "dir_clearsearch": ("clear the search", "qidiruvni tozalash", "очистить поиск"),
    "dir_colteacher": ("Teacher", "O'qituvchi", "Учитель"),
    "dir_collesson": ("Lesson", "Dars ma'lumoti", "Данные урока"),
    "dir_coltime": ("Time and audio", "Vaqt va audio", "Время и аудио"),
    "dir_colscore": ("AI score", "AI bahosi", "Оценка ИИ"),
    "dir_colstatus": ("Status", "Holati", "Статус"),
    "dir_overall": ("overall", "umumiy natija", "общий результат"),
    "dir_noanalysisshort": ("not analysed", "tahlil qilinmagan", "не проанализирован"),
    "dir_failed": ("analysis failed", "tahlil yiqilgan", "анализ не прошёл"),

    # ---- reytinglar
    "dir_rankstitle": ("Rankings and indicators", "Reytinglar va ko'rsatkichlar",
                       "Рейтинги и показатели"),
    "dir_rankssub": (
        "Performance by teacher, class and subject",
        "O'qituvchilar, sinflar va fanlar kesimida samaradorlik",
        "Эффективность по учителям, классам и предметам",
    ),
    "dir_week": ("This week", "Bu hafta", "Эта неделя"),
    "dir_month": ("This month", "Bu oy", "Этот месяц"),
    "dir_term": ("Term", "Chorak", "Четверть"),
    "dir_rankperiod": (
        "{$a->nom}: {$a->from} – {$a->to} · {$a->n} analysed lessons",
        "{$a->nom}: {$a->from} – {$a->to} · {$a->n} ta tahlil qilingan dars",
        "{$a->nom}: {$a->from} – {$a->to} · {$a->n} проанализированных уроков",
    ),
    "dir_noranks": (
        "There is not a single analysed lesson in this period.",
        "Bu davrda birorta ham tahlil qilingan dars yo'q.",
        "За этот период нет ни одного проанализированного урока.",
    ),
    "dir_noranks_help": (
        "A ranking needs recordings that have been analysed. Choose a longer "
        "period, or check that recording and AI analysis are switched on.",
        "Reyting uchun tahlil qilingan yozuvlar kerak. Uzunroq davrni tanlang "
        "yoki yozuv va AI tahlili yoqilganini tekshiring.",
        "Для рейтинга нужны проанализированные записи. Выберите более длинный "
        "период или проверьте, включены ли запись и ИИ-анализ.",
    ),
    "dir_bestteacher": ("Best teacher of {$a}", "{$a}ning eng yaxshi o'qituvchisi",
                        "Лучший учитель за {$a}"),
    "dir_bestclass": ("Highest scoring class", "Eng yuqori natijali sinf",
                      "Класс с лучшим результатом"),
    "dir_avgscore": ("School average", "Maktab o'rtachasi", "Среднее по школе"),
    "dir_nlessons": ("{$a} lessons", "{$a} ta dars", "{$a} уроков"),
    "dir_lessonword": ("lessons", "ta dars", "уроков"),
    "dir_nosample": ("not enough data yet", "hali ma'lumot yetarli emas",
                     "данных пока недостаточно"),
    "dir_byteacher": ("Teachers", "O'qituvchilar", "Учителя"),
    "dir_byclass": ("Classes", "Sinflar", "Классы"),
    "dir_bysubject": ("Subjects", "Fanlar", "Предметы"),
    "dir_colclass": ("Class", "Sinf", "Класс"),
    "dir_colsubject": ("Subject", "Fan", "Предмет"),
    "dir_collessons": ("Lessons", "Darslar", "Уроки"),
    "dir_coltrend": ("Trend", "Dinamika", "Динамика"),
    "dir_colstrong": ("Strongest area", "Kuchli tomoni", "Сильная сторона"),
    "dir_colresult": ("Result", "Natija", "Результат"),
    "dir_normis": ("International average: {$a}%", "Xalqaro o'rtacha: {$a}%",
                   "Мировое среднее: {$a}%"),
    "dir_normlegend": (
        "The mark on the bar is the international average ({$a}%):",
        "Chiziqdagi belgi — xalqaro o'rtacha ({$a}%):",
        "Отметка на полосе — мировое среднее ({$a}%):",
    ),
    "dir_strongwhy": (
        "{$a->avg}% — {$a->delta}% against this area's own international average",
        "{$a->avg}% — shu yo'nalishning o'z xalqaro o'rtachasidan {$a->delta}%",
        "{$a->avg}% — {$a->delta}% к собственному мировому среднему этого направления",
    ),
    "dir_lowsample": (
        "Not in the ranking (fewer than {$a->min} analysed lessons): {$a->ismlar}",
        "Reytingga kirmadi (tahlil qilingan dars {$a->min} tadan kam): {$a->ismlar}",
        "Не вошли в рейтинг (меньше {$a->min} проанализированных уроков): {$a->ismlar}",
    ),
    "dir_strongest": ("Strongest areas", "Kuchli yo'nalishlar", "Сильные направления"),
    "dir_todevelop": ("Areas to develop", "Rivojlantirish kerak", "Требуют развития"),
    "dir_vsownnorm": (
        "Ranked against each area's OWN international average — not against each "
        "other. An area with a low raw percentage can still be a strong one.",
        "Har yo'nalish O'Z xalqaro o'rtachasiga nisbatan tartiblangan — bir-biriga "
        "emas. Foizi past yo'nalish ham kuchli bo'lishi mumkin.",
        "Отсортировано относительно СОБСТВЕННОГО мирового среднего каждого "
        "направления, а не друг друга. Направление с низким процентом тоже может "
        "быть сильным.",
    ),

    # ------------------------------------------------------- guruh tutorlari
    "dir_tutors": ("Group tutors", "Guruh tutorlari", "Кураторы групп"),
    "dir_tutorshelp": (
        "Some subjects in the timetable have no teacher assigned yet. Those "
        "lessons are taught by the group's own tutor, so they are reported "
        "against that tutor. Without an assignment those lessons count for "
        "nobody and a missing recording never shows up.",
        "Jadvaldagi ba'zi fanlarga o'qituvchi hali tayinlanmagan. Bunday "
        "darslarni guruhning o'z tutori o'tadi, shuning uchun hisobot o'shanga "
        "yoziladi. Biriktirilmasa bu darslar hech kimga tegmaydi va yozuv "
        "yo'qligi umuman ko'rinmaydi.",
        "Некоторым предметам в расписании учитель ещё не назначен. Такие уроки "
        "ведёт куратор группы, поэтому отчёт пишется на него. Без привязки эти "
        "уроки никому не засчитываются и отсутствие записи не видно.",
    ),
    "dir_tutorsum": (
        "{$a->vakant} lessons a week have no assigned teacher; {$a->guruh} groups still have no tutor.",
        "Haftada {$a->vakant} dars o'qituvchisiz; {$a->guruh} guruhda tutor hali biriktirilmagan.",
        "{$a->vakant} уроков в неделю без учителя; в {$a->guruh} группах куратор ещё не назначен.",
    ),
    "dir_tutorsaved": ("{$a} assignments changed.", "{$a} ta biriktirish o'zgardi.",
                       "Изменено привязок: {$a}."),
    "dir_tutornone": ("— not assigned —", "— biriktirilmagan —", "— не назначен —"),
    "dir_tutornocand": (
        "There is no teacher linked to a Moodle account yet, so there is nobody to assign.",
        "Moodle hisobiga bog'langan o'qituvchi yo'q — biriktiradigan odam topilmadi.",
        "Нет ни одного учителя, связанного с учётной записью Moodle — некого назначить.",
    ),
    "dir_colgroup": ("Group", "Guruh", "Группа"),
    "dir_colvacant": ("Without a teacher", "O'qituvchisiz", "Без учителя"),
    "dir_colvacsubj": ("Subjects", "Qaysi fanlar", "Какие предметы"),
    "dir_coltutor": ("Tutor", "Tutor", "Куратор"),
    "dir_nweek": ("{$a} a week", "haftada {$a} ta", "{$a} в неделю"),

    # --------------------------------------------- baholash normalari (Moodle)
    "norm_mavzu_ulushi": ("Topic: expected share of lessons, %",
                          "Mavzu: darslarning necha foizida kutiladi, %",
                          "Тема: в каком проценте уроков ожидается, %"),
    "norm_mavzu_ulushi_desc": ("Normally 100 — a topic is written for every lesson.",
                               "Odatda 100 — mavzu har darsda yoziladi.",
                               "Обычно 100 — тема записывается на каждом уроке."),
    "norm_yoqlama_qamrovi": ("Attendance: share of the class list to be marked, %",
                             "Yo'qlama: sinf ro'yxatining necha foizi belgilanishi kerak, %",
                             "Посещаемость: какой процент списка класса отмечается, %"),
    "norm_yoqlama_qamrovi_desc": ("", "", ""),
    "norm_baho_ulushi": ("Grades: expected share of lessons, %",
                         "Baho: darslarning necha foizida kutiladi, %",
                         "Оценки: в каком проценте уроков ожидаются, %"),
    "norm_baho_ulushi_desc": (
        "A grade is not expected in every lesson. This value decides when the "
        "panel treats grading as insufficient.",
        "Baho har darsda kutilmaydi. Panel qachon \'baho kam\' deb hisoblashini "
        "shu qiymat belgilaydi.",
        "Оценка ожидается не на каждом уроке. Это значение определяет, когда "
        "панель считает оценивание недостаточным.",
    ),
    "norm_uy_vazifa_ulushi": ("Homework: expected share of lessons, %",
                              "Uy vazifa: darslarning necha foizida kutiladi, %",
                              "Домашнее задание: в каком проценте уроков ожидается, %"),
    "norm_uy_vazifa_ulushi_desc": ("", "", ""),
    "norm_yozuv_ulushi": ("Recording: expected share of lessons, %",
                          "Yozuv: darslarning necha foizida kutiladi, %",
                          "Запись: в каком проценте уроков ожидается, %"),
    "norm_yozuv_ulushi_desc": ("100 — recording every lesson is mandatory.",
                               "100 — yozuv har darsda majburiy.",
                               "100 — запись обязательна на каждом уроке."),
    "norm_jurnal_kechikish_soat": ("Journal: allowed delay, hours",
                                   "Jurnal: ruxsat etilgan kechikish, soat",
                                   "Журнал: допустимая задержка, часов"),
    "norm_jurnal_kechikish_soat_desc": (
        "If the journal is filled in later than this after the lesson ends, it "
        "appears in the attention queue.",
        "Jurnal dars tugagandan keyin shundan ko'p kechiksa — diqqat navbatiga tushadi.",
        "Если журнал заполнен позже этого срока после урока — попадает в очередь внимания.",
    ),

    # --------------------------------------------------- dars yozuvlari lentasi
    "rec_sub": (
        "{$a} recordings. Click a row to hear the lesson and read the short analysis.",
        "{$a} ta yozuv. Qatorni bosing — darsni eshitasiz va qisqa tahlilni o'qiysiz.",
        "{$a} записей. Нажмите строку — услышите урок и прочитаете краткий анализ.",
    ),
    "rec_allteachers": ("All teachers", "Barcha o'qituvchilar", "Все учителя"),
    "rec_allgroups": ("All groups", "Barcha guruhlar", "Все группы"),
    "rec_from": ("From", "Sanadan", "С даты"),
    "rec_to": ("To", "Sanagacha", "По дату"),
    "rec_apply": ("Apply", "Qo'llash", "Применить"),
    "rec_reset": ("reset", "tozalash", "сбросить"),
    "rec_none": ("No recordings for this filter.", "Bu filtrda yozuv yo'q.",
                 "По этому фильтру записей нет."),
    "rec_none_help": (
        "Widen the date range, or pick \'All teachers\' and \'All groups\'.",
        "Sana oralig'ini kengaytiring yoki «Barcha o'qituvchilar» va «Barcha guruhlar» ni tanlang.",
        "Расширьте диапазон дат или выберите «Все учителя» и «Все группы».",
    ),
    "rec_coltopic": ("Topic", "Mavzu", "Тема"),
    "rec_colwhen": ("Recorded", "Qachon yozilgan", "Когда записано"),
    "rec_collength": ("Length", "Davomiyligi", "Длительность"),
    "rec_colai": ("AI assessment", "AI bahosi", "Оценка ИИ"),
    "rec_colsummary": ("Short summary", "Qisqa xulosa", "Краткое резюме"),
    "rec_min": ("min", "daq", "мин"),
    "rec_expand": ("Click to open the recording and the analysis",
                   "Yozuv va tahlilni ochish uchun bosing",
                   "Нажмите, чтобы открыть запись и анализ"),
    "rec_pending": ("not analysed yet", "hali tahlil qilinmagan", "ещё не проанализирован"),
    "rec_vacancy": ("vacancy — taught by the tutor", "vakant — tutor o'tgan",
                    "вакансия — вёл куратор"),
    "rec_hastext": ("transcript available", "transkript bor", "есть расшифровка"),
    "rec_strong": ("strongest", "kuchli tomoni", "сильная сторона"),
    "rec_weak": ("to develop", "rivojlantirish kerak", "требует развития"),
    "rec_openlesson": ("Open the lesson in full", "Dars jarayonini to'liq ochish",
                       "Открыть урок полностью"),
    "rec_delaudio": ("Delete the audio", "Audioni o'chirish", "Удалить аудио"),
    "rec_delaudio_q": (
        "The audio will be deleted. The transcript and the analysis stay. Continue?",
        "Audio o'chiriladi. Transkript va tahlil qoladi. Davom etamizmi?",
        "Аудио будет удалено. Расшифровка и анализ останутся. Продолжить?",
    ),
    "rec_delall": ("Delete everything", "Butunlay o'chirish", "Удалить полностью"),
    "rec_delall_q": (
        "The audio, the transcript AND the analysis will be deleted. Continue?",
        "Audio, transkript VA tahlil butunlay o'chiriladi. Davom etamizmi?",
        "Аудио, расшифровка И анализ будут удалены полностью. Продолжить?",
    ),
    "rec_deleted_audio": (
        "The audio was deleted. The transcript and the analysis remain.",
        "Audio o'chirildi. Transkript va tahlil saqlanib qoldi.",
        "Аудио удалено. Расшифровка и анализ сохранены.",
    ),
    "rec_deleted_all": (
        "The recording was deleted completely (audio, transcript and analysis).",
        "Yozuv butunlay o'chirildi (audio, transkript va tahlil).",
        "Запись удалена полностью (аудио, расшифровка и анализ).",
    ),

    # ------------------------------------------------- O'QITUVCHI PANELI
    "oq_mypanel": (
        "My lessons",
        "Mening darslarim",
        "Мои уроки",
    ),
    "oq_mylessons": (
        "My lessons",
        "Mening darslarim",
        "Мои уроки",
    ),
    "oq_card": (
        "Teacher card",
        "O'qituvchi kartasi",
        "Карточка учителя",
    ),
    "oq_noaccess": (
        "You may only view your own lessons.",
        "Siz faqat o'z darslaringizni ko'rishingiz mumkin.",
        "Вы можете просматривать только свои уроки.",
    ),
    "oq_empty": (
        "No lesson is recorded for this person in the selected period. If this looks "
        "wrong, the staff key may be unlinked: the Employee ID must match the Moodle "
        "user idnumber.",
        "Tanlangan davrda bu odamga yozilgan dars yo'q. Agar bu noto'g'ri ko'rinsa, "
        "kalit bog'lanmagan bo'lishi mumkin: Employee ID Moodle'dagi user idnumber "
        "bilan mos bo'lishi shart.",
        "За выбранный период уроков за этим человеком не записано. Если это неверно, "
        "возможно не связан ключ: Employee ID должен совпадать с idnumber пользователя "
        "Moodle.",
    ),
    "oq_tryall": (
        "Show the whole monitoring period",
        "Butun kuzatuv davrini ko'rsatish",
        "Показать весь период наблюдения",
    ),
    "oq_tab_sum": ("Overview", "Umumiy hisob", "Общий итог"),
    "oq_rec_sub": (
        "Your own lesson recordings only. Click a row to open the audio and the "
        "short analysis.",
        "Faqat O'ZINGIZNING dars yozuvlaringiz. Ovoz va qisqa tahlilni ko'rish "
        "uchun qatorni bosing.",
        "Только ВАШИ записи уроков. Нажмите на строку, чтобы открыть аудио и "
        "краткий анализ.",
    ),
    "oq_navmine": ("My work", "Mening ishim", "Моя работа"),
    "oq_navschool": ("School", "Maktab", "Школа"),
    "jurnal_class": ("Class", "Sinf", "Класс"),
    "jurnal_term": ("Term", "Chorak", "Четверть"),
    "jurnal_link": (
        "Full journal",
        "To'liq jurnal",
        "Полный журнал",
    ),
    "lessons_link": ("Lessons", "Darslar", "Уроки"),
    "tt_colaction": ("Lesson", "Dars", "Урок"),
    "tt_record": ("Record", "Yozib olish", "Записать"),
    "tt_recorded": ("Recorded", "Yozilgan", "Записано"),
    "tt_missing": (
        "not yet: {$a}",
        "hali yo'q: {$a}",
        "ещё нет: {$a}",
    ),
    "oq_all": ("All", "Hammasi", "Всё"),
    "oq_periodis": (
        "{$a->nom}: {$a->from} - {$a->to}",
        "{$a->nom}: {$a->from} - {$a->to}",
        "{$a->nom}: {$a->from} - {$a->to}",
    ),
    "oq_watchfrom": (
        "Monitoring starts on {$a} - the journal was not in use before that date, "
        "so earlier lessons are not counted.",
        "Kuzatuv {$a} dan boshlanadi - undan oldin jurnal ishlatilmagan, "
        "shuning uchun avvalgi darslar hisobga olinmaydi.",
        "Наблюдение начинается с {$a} - до этой даты журнал не использовался, "
        "поэтому более ранние уроки не учитываются.",
    ),
    "oq_perweek": (
        "{$a} lessons/week",
        "{$a} dars/hafta",
        "{$a} уроков/неделя",
    ),

    # --- plitalar
    "oq_done": ("Lessons taught", "O'tilgan darslar", "Проведённые уроки"),
    "oq_ofplanned": (
        "of {$a} planned",
        "rejadagi {$a} tadan",
        "из {$a} по плану",
    ),
    "oq_complete": ("Fully completed", "To'liq bajarilgan", "Полностью выполнено"),
    "oq_complete_sub": (
        "topic + attendance + recording, all three present",
        "mavzu + yo'qlama + yozuv, uchalasi ham bor",
        "тема + посещаемость + запись, все три на месте",
    ),

    # --- majburiy vazifalar
    "oq_duties": ("Required work", "Majburiy vazifalar", "Обязательные задачи"),
    "oq_duties_sub": (
        "Share of past lessons where each item was recorded.",
        "Vaqti o'tgan darslarning qanchasida bu ish bajarilgani.",
        "Доля прошедших уроков, где это было выполнено.",
    ),
    "oq_m_topic": ("Lesson topic", "Dars mavzusi", "Тема урока"),
    "oq_m_att": ("Attendance", "Yo'qlama", "Посещаемость"),
    "oq_m_rec": ("Recording", "Dars yozuvi", "Запись урока"),
    "oq_m_hw": ("Homework", "Uy vazifa", "Домашнее задание"),
    "oq_m_grade": ("Grades", "Baholar", "Оценки"),
    "oq_notrequired": (
        "not expected in every lesson",
        "har darsda kutilmaydi",
        "не требуется на каждом уроке",
    ),
    "oq_substituted": (
        "{$a} of these lessons were taught as a substitute.",
        "Shu darslardan {$a} tasini o'rnini bosib o'tgansiz.",
        "Из этих уроков {$a} проведено в качестве замены.",
    ),

    # --- guruh x fan
    "oq_bygroup": ("By class and subject", "Sinf va fan bo'yicha", "По классам и предметам"),
    "oq_colgroup": ("Class", "Sinf", "Класс"),
    "oq_colsubject": ("Subject", "Fan", "Предмет"),
    "oq_collessons": ("Taught / planned", "O'tilgan / rejada", "Проведено / по плану"),
    "oq_colcomplete": ("Completed", "Bajarilgan", "Выполнено"),
    "oq_clearfilter": (
        "Show all classes",
        "Hamma sinfni ko'rsatish",
        "Показать все классы",
    ),

    # --- kategoriya profili
    "oq_profile": (
        "Teaching profile (15 criteria)",
        "Dars berish profili (15 mezon)",
        "Профиль преподавания (15 критериев)",
    ),
    "oq_profile_sub": (
        "Each criterion is compared with its OWN international average, not with 100%.",
        "Har mezon O'Z xalqaro o'rtachasiga nisbatan ko'rsatilgan, 100% ga emas.",
        "Каждый критерий показан относительно СВОЕГО международного среднего, а не 100%.",
    ),
    "oq_noprofile": (
        "No analysed lesson in this period yet - the profile appears once recordings "
        "have been analysed.",
        "Bu davrda tahlil qilingan dars yo'q - yozuvlar tahlil qilingach profil paydo bo'ladi.",
        "В этом периоде нет проанализированных уроков - профиль появится после анализа записей.",
    ),
    "oq_strong": ("Strongest", "Kuchli tomon", "Сильная сторона"),
    "oq_weak": ("Needs work", "Ishlash kerak", "Требует работы"),
    "oq_below": ("below average", "o'rtachadan past", "ниже среднего"),
    "oq_normzero": ("international average", "xalqaro o'rtacha", "международное среднее"),
    "oq_above": ("above average", "o'rtachadan yuqori", "выше среднего"),
    "oq_notmeasured": (
        "not measurable from audio",
        "audio orqali baholanmadi",
        "по аудио не оценивается",
    ),
    "oq_normn": (
        "average {$a->norma}% · n={$a->n}",
        "o'rtacha {$a->norma}% · n={$a->n}",
        "среднее {$a->norma}% · n={$a->n}",
    ),
    "oq_profile_note": (
        "Rows based on fewer than {$a} lessons are dimmed: the sample is too small "
        "to draw a conclusion.",
        "{$a} tadan kam darsga asoslangan qatorlar susaytirilgan: xulosa chiqarish "
        "uchun namuna kichik.",
        "Строки на основе менее {$a} уроков показаны бледнее: выборка слишком мала "
        "для вывода.",
    ),

    # --- darslar ro'yxati
    "oq_lessons": ("Lesson by lesson", "Dars-dars ro'yxat", "Урок за уроком"),
    "oq_fgap": ("needs attention", "e'tibor kerak", "требуют внимания"),
    "oq_fnorec": ("no recording", "yozuvsiz", "без записи"),
    "oq_fanalysed": ("analysed", "tahlil qilingan", "проанализировано"),
    "oq_coldate": ("Date", "Sana", "Дата"),
    "oq_coltopic": ("Topic and homework", "Mavzu va uy vazifa", "Тема и домашнее задание"),
    "oq_colchecks": ("All in place?", "Hammasi joyidami?", "Всё на месте?"),
    "oq_subst": ("substitute", "o'rnini bosdi", "замена"),
    "oq_c_topic": ("topic", "mavzu", "тема"),
    "oq_c_att": ("attendance", "yo'qlama", "посещаемость"),
    "oq_c_att_t": (
        "present {$a->kelgan} of {$a->jami} · absent {$a->yoq} · late {$a->kech}",
        "{$a->jami} dan {$a->kelgan} kelgan · kelmadi {$a->yoq} · kechikdi {$a->kech}",
        "пришли {$a->kelgan} из {$a->jami} · отсутствовали {$a->yoq} · опоздали {$a->kech}",
    ),
    "oq_c_rec": ("recording", "yozuv", "запись"),
    "oq_c_hw": ("homework", "uy vazifa", "дом. задание"),
    "oq_c_grade": ("grades", "baho", "оценки"),
    "oq_c_grade_t": (
        "{$a} grades given",
        "{$a} ta baho qo'yilgan",
        "выставлено оценок: {$a}",
    ),
    "oq_lowconf": (
        "{$a} criteria could not be assessed from the audio",
        "{$a} mezon audio orqali baholanmadi",
        "{$a} критериев не оценены по аудио",
    ),
    "oq_open": ("Open lesson", "Darsga o'tish", "Открыть урок"),
    "oq_fill": ("Fill in", "To'ldirish", "Заполнить"),
    "oq_opencard": ("Lesson card", "Dars kartasi", "Карточка урока"),

    # ------------------------------------------------------- direktor fidbegi
    "messageprovider:feedback": (
        "Feedback from the director about your lesson",
        "Darsingiz haqida direktor fidbegi",
        "Обратная связь директора о вашем уроке",
    ),
    "fb_title": ("Director feedback", "Direktor fidbegi", "Обратная связь директора"),
    "fb_noreply": (
        "You can only reply to feedback on your own lesson.",
        "Javob faqat O'Z darsingizdagi fidbekka yozilishi mumkin.",
        "Ответить можно только на отзыв по вашему собственному уроку.",
    ),
    "fb_none": ("No feedback for this lesson yet.", "Bu dars uchun hali fidbek yo'q.",
                "По этому уроку обратной связи пока нет."),
    "fb_placeholder": (
        "What went well, and what is worth trying next time...",
        "Nima yaxshi bo'ldi va keyingi safar nimani sinab ko'rish mumkin...",
        "Что получилось хорошо и что стоит попробовать в следующий раз...",
    ),
    "fb_send": ("Send", "Yuborish", "Отправить"),
    "fb_saved": ("The feedback was sent to the teacher.",
                 "Fidbek o'qituvchiga yuborildi.",
                 "Обратная связь отправлена учителю."),
    "fb_kind_tavsiya": ("advice", "tavsiya", "совет"),
    "fb_kind_ogohlantirish": ("warning", "ogohlantirish", "предупреждение"),
    "fb_kind_rahmat": ("thanks", "rahmat", "благодарность"),
    # `javob` — o'qituvchining javobi; tanlanmaydi, avtomatik qo'yiladi
    "fb_kind_javob": ("reply", "javob", "ответ"),
    "fb_privatelab": ("admin note only", "faqat ma'muriy qayd", "только админ-заметка"),
    "fb_privatehelp": (
        "The teacher will NOT see this note and gets no notification. "
        "Normally feedback is visible to the teacher — a hidden assessment "
        "destroys trust.",
        "O'qituvchi bu qaydni KO'RMAYDI va bildirishnoma olmaydi. Odatda fidbek "
        "o'qituvchiga ko'rinadi — yashirin baho ishonchni buzadi.",
        "Учитель НЕ увидит эту заметку и не получит уведомление. Обычно обратная "
        "связь видна учителю — скрытая оценка разрушает доверие.",
    ),
    "fb_reply": ("Reply", "Javob berish", "Ответить"),
    "fb_replyph": (
        "Your reply to the director...",
        "Direktorga javobingiz...",
        "Ваш ответ директору...",
    ),
    "fb_addshort": ("Write feedback...", "Fidbek yozing...", "Напишите отзыв..."),
    "fb_replyshort": ("Reply...", "Javob...", "Ответить..."),
    "fb_count": ("messages in this conversation", "shu suhbatdagi xabarlar soni",
                 "сообщений в этом разговоре"),
    "fb_private": ("admin note", "ma'muriy qayd", "админ-заметка"),
    "fb_seen": ("· read", "· o'qildi", "· прочитано"),
    "fb_msgsubject": (
        "Feedback about your lesson: {$a->kurs}, {$a->sana}",
        "Darsingiz haqida fidbek: {$a->kurs}, {$a->sana}",
        "Обратная связь о вашем уроке: {$a->kurs}, {$a->sana}",
    ),

    # ------------------------------------------------------------ oylik sahifasi
    "pr_calchint": (
        "Read left to right: this is how the amount is built up.",
        "Chapdan o'ngga o'qing: summa shu tartibda hosil bo'ladi.",
        "Читайте слева направо: так формируется сумма.",
    ),
    "pr_ofnorm": ("of {$a} norm days", "norma {$a} kundan", "из {$a} нормо-дней"),

    # ------------------------------------------------- direktor paneli vazifalari
    "task_slotbuild": (
        "Rebuild the planned lesson slots from the timetable",
        "Dars rejasini (slot) jadvaldan qayta qurish",
        "Пересобрать запланированные уроки из расписания",
    ),
    "task_factrefresh": (
        "Refresh lesson compliance data for recent days",
        "Oxirgi kunlar uchun dars bajarilish ma'lumotini yangilash",
        "Обновить данные о проведении уроков за последние дни",
    ),
    "task_factrebuild": (
        "Recompute lesson compliance data for the whole academic year",
        "Butun o'quv yili uchun dars bajarilish ma'lumotini qayta hisoblash",
        "Пересчитать данные о проведении уроков за весь учебный год",
    ),

    # Oy nomlari — sana formatiga bog'liq emas, tabel oyi uchun
    "month_1": ("January", "Yanvar", "Январь"),
    "month_2": ("February", "Fevral", "Февраль"),
    "month_3": ("March", "Mart", "Март"),
    "month_4": ("April", "Aprel", "Апрель"),
    "month_5": ("May", "May", "Май"),
    "month_6": ("June", "Iyun", "Июнь"),
    "month_7": ("July", "Iyul", "Июль"),
    "month_8": ("August", "Avgust", "Август"),
    "month_9": ("September", "Sentabr", "Сентябрь"),
    "month_10": ("October", "Oktabr", "Октябрь"),
    "month_11": ("November", "Noyabr", "Ноябрь"),
    "month_12": ("December", "Dekabr", "Декабрь"),
}
