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
    "timetable_today": ("Today", "Bugun", "Сегодня"),
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
