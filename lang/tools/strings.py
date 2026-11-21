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
    "au_warning": (
        "Recording issue: {$a}",
        "Yozuvda nuqson: {$a}",
        "Дефект записи: {$a}",
    ),
    "rc_resume": (
        "An unfinished recording was found for this lesson: {$a->vaqt} "
        "({$a->soni} chunks). Choose below \u2014 continue it, or start over. "
        "If you start over, that fragment is set aside, not merged into the "
        "new recording.",
        "Bu darsda tugallanmagan yozuv topildi: {$a->vaqt} ({$a->soni} bo'lak). "
        "Pastda tanlang \u2014 davom ettirasizmi yoki boshidan yozasizmi. "
        "Boshidan boshlasangiz, o'sha parcha yangi yozuvga QO'SHILMAYDI, "
        "chetga olinadi.",
        "\u0414\u043b\u044f \u044d\u0442\u043e\u0433\u043e \u0443\u0440\u043e\u043a\u0430 \u043d\u0430\u0439\u0434\u0435\u043d\u0430 \u043d\u0435\u0437\u0430\u0432\u0435\u0440\u0448\u0451\u043d\u043d\u0430\u044f \u0437\u0430\u043f\u0438\u0441\u044c: {$a->vaqt} ({$a->soni} \u0444\u0440\u0430\u0433\u043c.). "
        "\u0412\u044b\u0431\u0435\u0440\u0438\u0442\u0435 \u043d\u0438\u0436\u0435 \u2014 \u043f\u0440\u043e\u0434\u043e\u043b\u0436\u0438\u0442\u044c \u0438\u043b\u0438 \u0437\u0430\u043f\u0438\u0441\u0430\u0442\u044c \u0441 \u043d\u0430\u0447\u0430\u043b\u0430. "
        "\u041f\u0440\u0438 \u0437\u0430\u043f\u0438\u0441\u0438 \u0441 \u043d\u0430\u0447\u0430\u043b\u0430 \u0442\u043e\u0442 \u0444\u0440\u0430\u0433\u043c\u0435\u043d\u0442 \u041d\u0415 \u043f\u0440\u0438\u043a\u043b\u0435\u0438\u0432\u0430\u0435\u0442\u0441\u044f, \u0430 \u043e\u0442\u043a\u043b\u0430\u0434\u044b\u0432\u0430\u0435\u0442\u0441\u044f \u0432 \u0441\u0442\u043e\u0440\u043e\u043d\u0443.",
    ),
    "rc_continue": (
        "Continue the recording (+{$a} already recorded)",
        "Yozuvni davom ettirish ({$a} allaqachon yozilgan)",
        "\u041f\u0440\u043e\u0434\u043e\u043b\u0436\u0438\u0442\u044c \u0437\u0430\u043f\u0438\u0441\u044c ({$a} \u0443\u0436\u0435 \u0437\u0430\u043f\u0438\u0441\u0430\u043d\u043e)",
    ),
    "rc_fresh": (
        "Record from the beginning (set the fragment aside)",
        "Boshidan yozish (parcha chetga olinadi)",
        "\u0417\u0430\u043f\u0438\u0441\u0430\u0442\u044c \u0441 \u043d\u0430\u0447\u0430\u043b\u0430 (\u0444\u0440\u0430\u0433\u043c\u0435\u043d\u0442 \u0432 \u0441\u0442\u043e\u0440\u043e\u043d\u0443)",
    ),
    # ---------------------------------------- doskadagi material (AI ko'rishi uchun)
    "ai_lang_note": (
        "This analysis was written in {$a} \u2014 the language the teacher "
        "uses on the platform. Category names are shown in your own language.",
        "Bu tahlil {$a} yozilgan \u2014 o'qituvchi platformada shu tilda "
        "ishlaydi. Kategoriya nomlari sizning tilingizda ko'rsatiladi.",
        "\u0410\u043d\u0430\u043b\u0438\u0437 \u043d\u0430\u043f\u0438\u0441\u0430\u043d \u043d\u0430 {$a} \u2014 \u044d\u0442\u043e \u044f\u0437\u044b\u043a, \u043d\u0430 \u043a\u043e\u0442\u043e\u0440\u043e\u043c \u0440\u0430\u0431\u043e\u0442\u0430\u0435\u0442 \u0443\u0447\u0438\u0442\u0435\u043b\u044c. "
        "\u041d\u0430\u0437\u0432\u0430\u043d\u0438\u044f \u043a\u0430\u0442\u0435\u0433\u043e\u0440\u0438\u0439 \u043f\u043e\u043a\u0430\u0437\u0430\u043d\u044b \u043d\u0430 \u0432\u0430\u0448\u0435\u043c \u044f\u0437\u044b\u043a\u0435.",
    ),
    "mt_title": (
        "Board material (the AI sees it in the analysis)",
        "Doskadagi material (AI tahlilda ko'radi)",
        "Материал с доски (ИИ учитывает его в анализе)",
    ),
    "mt_hint": (
        "Attach what you showed on the board: a slide, a photo of the board, "
        "a PDF. The analysis only HEARS the lesson \u2014 it cannot see the "
        "board, so without this file it cannot credit your visual aids. "
        "Images and PDF are read in full; PPT/DOC are counted as present but "
        "not read. One file, up to {$a} MB.",
        "Darsda doskada ko'rsatgan narsangizni biriktiring: slayd, doskaning "
        "surati, PDF. Tahlil darsni faqat ESHITADI \u2014 doskani ko'rmaydi, "
        "shuning uchun bu fayl bo'lmasa ko'rgazmali ishingizni hisobga olib "
        "bo'lmaydi. Rasm va PDF to'liq o'qiladi; PPT/DOC borligi hisobga "
        "olinadi, lekin mazmuni o'qilmaydi. Bitta fayl, {$a} MB gacha.",
        "Прикрепите то, что показывали на доске: слайд, фото доски, PDF. "
        "Анализ только СЛЫШИТ урок \u2014 доску он не видит, поэтому без "
        "этого файла засчитать наглядную работу невозможно. Изображения и PDF "
        "читаются полностью; PPT/DOC учитываются как наличие, но не читаются. "
        "Один файл, до {$a} МБ.",
    ),
    "mt_upload": ("Attach file", "Faylni biriktirish", "Прикрепить файл"),
    "mt_replace": ("Replace file", "Faylni almashtirish", "Заменить файл"),
    "mt_delete": ("Delete", "O'chirish", "Удалить"),
    "mt_delete_q": (
        "Delete the board material? The analysis will no longer see it.",
        "Doskadagi material o'chiriladi. Tahlil endi uni ko'rmaydi. Davom etamizmi?",
        "Материал с доски будет удалён. Анализ его больше не увидит. Продолжить?",
    ),
    "mt_saved": (
        "Board material attached \u2014 the next analysis will take it into account",
        "Doskadagi material biriktirildi \u2014 keyingi tahlil uni hisobga oladi",
        "Материал с доски прикреплён \u2014 следующий анализ учтёт его",
    ),
    "mt_deleted": ("Board material deleted", "Doskadagi material o'chirildi",
                   "Материал с доски удалён"),
    "mt_aisees": (
        "The AI reads the content of this file and uses it as evidence.",
        "AI bu faylning mazmunini o'qiydi va dalil sifatida ishlatadi.",
        "ИИ читает содержимое этого файла и использует его как доказательство.",
    ),
    "mt_notread": (
        "The AI cannot read this format \u2014 it only counts the file as "
        "present. For full credit attach an image or a PDF.",
        "AI bu turni o'qiy olmaydi \u2014 faqat fayl borligini hisobga oladi. "
        "To'liq hisobga olinishi uchun rasm yoki PDF biriktiring.",
        "ИИ не читает этот формат \u2014 учитывается только наличие файла. "
        "Для полного зачёта прикрепите изображение или PDF.",
    ),
    "mt_err_empty": ("No file selected.", "Fayl tanlanmadi.", "Файл не выбран."),
    "mt_err_big": (
        "The file is too large (limit {$a} MB).",
        "Fayl juda katta ({$a} MB chegarasi).",
        "Файл слишком большой (ограничение {$a} МБ).",
    ),
    "mt_err_upload": (
        "Upload failed. Please try again.",
        "Yuklab bo'lmadi. Qaytadan urinib ko'ring.",
        "Не удалось загрузить. Попробуйте снова.",
    ),
    "mt_err_type": (
        "This file type is not allowed. Allowed: {$a}.",
        "Bu fayl turiga ruxsat yo'q. Ruxsat etilgan: {$a}.",
        "Этот тип файла не разрешён. Разрешено: {$a}.",
    ),
    "mt_err_virus": (
        "The file did not pass the virus check.",
        "Fayl virus tekshiruvidan o'tmadi.",
        "Файл не прошёл проверку на вирусы.",
    ),
    "mt_aiseen": (
        "The AI saw the board material",
        "AI doskadagi materialni ko'rdi",
        "ИИ видел материал с доски",
    ),
    "audio_finalize": (
        "Finalise abandoned lesson recordings",
        "Tashlab ketilgan dars yozuvlarini yakunlash",
        "Завершить брошенные записи уроков",
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

    # --- 0.42.0 ramkasi: GTT (Coe 2020) + CLASS + ICALT + TEACH + MQI/PLATO.
    # Guruhlar qayta tuzildi (quality/emotional/feedback -> content/thinking/
    # climate/outcome), shuning uchun yuqoridagi uchtasi endi ishlatilmaydi,
    # lekin eski tahlillar uchun jadvalda qolgan.
    "ai_grp_content": ('Content and explanation', 'Mazmun va tushuntirish', 'Содержание и объяснение'),
    "ai_grp_thinking": ('Activating hard thinking', 'Fikrlashni faollashtirish', 'Активизация мышления'),
    "ai_grp_climate": ('Climate and relationships', 'Muhit va munosabat', 'Климат и отношения'),
    "ai_grp_outcome": ('Learning outcome', 'Natija', 'Результат'),
    "ai_grp_subject": ('Subject-specific craft', 'Fan mahorati', 'Предметное мастерство'),

    # universal o'zakka qo'shilgan kategoriyalar
    "ai_cat_structuring": ('Lesson structure', 'Dars strukturasi', 'Структура урока'),
    "ai_cat_relevance": ('Connection to real life', "Hayot bilan bog'liqlik", 'Связь с жизнью'),
    "ai_cat_questioning": ('Quality of questioning', 'Savol berish sifati', 'Качество вопросов'),
    "ai_cat_participation": ('Distribution of participation', 'Qatnashuv taqsimoti', 'Распределение участия'),
    "ai_cat_embedding": ('Practice and review', 'Mashq va takrorlash', 'Отработка и повторение'),
    "ai_cat_student_attitude": (
        'Students\' attitude towards the teacher',
        "O'quvchilarning o'qituvchiga munosabati",
        'Отношение учеников к учителю',
    ),
    "ai_cat_high_expectations": ('Climate of high expectations', 'Yuqori talab muhiti', 'Атмосфера высоких требований'),
    "ai_cat_error_climate": ('Response to mistakes', 'Xatoga munosabat', 'Отношение к ошибкам'),
    "ai_cat_learning_evidence": ('What students learned', "O'quvchi nima o'rgandi", 'Что ученик усвоил'),

    # fan moduli — aniq fanlar (MQI)
    "ai_cat_math_precision": ('Precision of mathematical language', 'Matematik til va yozuvning aniqligi', 'Точность математического языка'),
    "ai_cat_procedure_meaning": ('Meaning behind the procedure', "Algoritm ortidagi ma'no", 'Смысл за алгоритмом'),
    "ai_cat_reasoning": ('Demanding reasoning and proof', 'Asoslash va isbot talab qilish', 'Требование обоснования и доказательства'),
    "ai_cat_representations": ('Multiple representations of one idea', "Bir g'oyani bir necha ko'rinishda", 'Несколько представлений одной идеи'),

    # fan moduli — til darslari (PLATO)
    "ai_cat_target_language_use": ('Use of the target language', "O'rganilayotgan tilda gapirish ulushi", 'Доля речи на изучаемом языке'),
    "ai_cat_student_talk_length": ('Length of student talk', "O'quvchi nutqining uzunligi", 'Длина речи ученика'),
    "ai_cat_text_based": ('Text-based discussion', 'Matnga tayangan muhokama', 'Обсуждение с опорой на текст'),
    "ai_cat_strategy_modeling": ('Modelling reading/writing strategies', "O'qish/yozish strategiyasini modellashtirish", 'Моделирование стратегий чтения и письма'),

    # fan moduli — gumanitar fanlar
    "ai_cat_evidence_argument": ('Argument from sources and evidence', 'Manba va dalil bilan asoslash', 'Обоснование источниками и доводами'),
    "ai_cat_multiple_perspectives": ('Multiple perspectives', 'Bir necha nuqtai nazar', 'Несколько точек зрения'),
    "ai_cat_discussion_depth": ('Depth of discussion', 'Muhokama chuqurligi', 'Глубина обсуждения'),
    "ai_cat_value_discussion": ('Discussion of values and ethics', 'Qadriyat va axloqiy muhokama', 'Обсуждение ценностей и этики'),

    # fan moduli — tabiiy fanlar
    "ai_cat_inquiry": ('Observation, hypothesis, experiment', 'Kuzatish, gipoteza, tajriba', 'Наблюдение, гипотеза, опыт'),
    "ai_cat_misconception_check": ('Surfacing misconceptions', 'Xato tasavvurni ochish', 'Выявление ошибочных представлений'),
    "ai_cat_everyday_science": ('Linking to everyday phenomena', 'Kundalik hodisa bilan bog\'lash', 'Связь с повседневными явлениями'),

    # fan moduli — boshlang'ich sinf (CLASS K-3)
    "ai_cat_routines": ('Routines and clear instructions', "Rutinalar va aniq ko'rsatmalar", 'Рутины и ясные инструкции'),
    "ai_cat_vocabulary_building": ('Vocabulary building', "Lug'at boyitish", 'Обогащение словаря'),
    "ai_cat_play_engagement": ('Engagement through play and movement', "O'yin va harakat orqali jalb qilish", 'Вовлечение через игру и движение'),

    # fan moduli — amaliy fanlar
    "ai_cat_demonstration": ('Demonstration and correcting technique', 'Namoyish va texnika tuzatish', 'Демонстрация и исправление техники'),
    "ai_cat_practice_time": ('Share of hands-on practice time', 'Amaliy mashq vaqti ulushi', 'Доля времени практики'),
    "ai_cat_safety": ('Safety rules', 'Xavfsizlik qoidalari', 'Правила безопасности'),

    # dars turlari — AI transkriptdan o'zi aniqlaydi
    "ai_tur_yangi": ('New topic', 'Yangi mavzu', 'Новая тема'),
    "ai_tur_mustahkamlash": ('Consolidation / practice', 'Mustahkamlash / mashq', 'Закрепление / отработка'),
    "ai_tur_takrorlash": ('Revision', 'Takrorlash', 'Повторение'),
    "ai_tur_nazorat": ('Assessment / test', 'Nazorat / test', 'Контрольная / тест'),
    "ai_tur_amaliy": ('Practical / lab', 'Amaliy / laboratoriya', 'Практическая / лабораторная'),
    "ai_tur_muhokama": ('Discussion / seminar', 'Muhokama / seminar', 'Обсуждение / семинар'),
    "ai_dars_turi": ('Lesson type', 'Dars turi', 'Тип урока'),
    "ai_fan_oila": ('Subject family', 'Fan oilasi', 'Группа предметов'),
    "ai_min_n_ogoh": (
        'This criterion needs at least {$a} lessons for a reliable judgement, so it is shown only in the termly profile.',
        "Bu mezon ishonchli bo'lishi uchun kamida {$a} dars kerak, shuning uchun u faqat chorak profilida ko'rsatiladi.",
        'Для надёжной оценки этому критерию нужно не менее {$a} уроков, поэтому он показывается только в профиле за четверть.',
    ),

    # ------------------------------------------------- yozuv oynasi (rec.php)
    "rc_mic": ("Microphone:", "Mikrofon:", "Микрофон:"),
    "rc_stop": (
        "\u25a0 Stop and save",
        "\u25a0 To'xtatish va saqlash",
        "\u25a0 Остановить и сохранить",
    ),
    "rc_hint": (
        "<b>Do not close this window and do not minimise it.</b> Keep it "
        "visible \u2014 side by side with the main window. If the window is "
        "hidden or the laptop goes to sleep, the browser stops the recorder "
        "and only the first few minutes are saved.<br><br>"
        "While it is visible you can take attendance, enter grades and open "
        "the journal in the main window \u2014 the recording continues.<br><br>"
        "<b>If the internet drops, recording does NOT stop</b> \u2014 chunks "
        "are queued and sent automatically when the connection returns; no "
        "audio is lost.<br><br>"
        "<b>Pause</b> resumes by itself after 30 seconds \u2014 so a "
        "forgotten pause will not cost you the lesson.<br><br>"
        "Audio is kept for {$a} days.",
        "<b>Bu oynani yopmang va KICHRAYTIRMANG.</b> Uni ko'rinib turadigan "
        "joyda qoldiring \u2014 asosiy oyna bilan yonma-yon. Oyna yashirilsa "
        "yoki noutbuk uxlab qolsa, brauzer yozuvni to'xtatadi va faqat "
        "birinchi bir necha daqiqa saqlanadi.<br><br>"
        "Oyna ko'rinib turganda asosiy oynada yo'qlama qilish, baho qo'yish "
        "va jurnalni ochish mumkin \u2014 yozuv davom etaveradi.<br><br>"
        "<b>Internet uzilsa yozuv TO'XTAMAYDI</b> \u2014 bo'laklar navbatda "
        "turadi va aloqa qaytishi bilan o'zi yuboriladi, ovoz "
        "yo'qolmaydi.<br><br>"
        "<b>Pauza</b> 30 soniyadan keyin o'zi davom etadi \u2014 unutib "
        "qo'yilsa ham dars yozilmay qolmaydi.<br><br>"
        "Audio {$a} kun saqlanadi.",
        "<b>Не закрывайте и не сворачивайте это окно.</b> Держите его на "
        "виду \u2014 рядом с основным окном. Если окно скрыто или ноутбук "
        "уснёт, браузер остановит запись и сохранятся только первые "
        "минуты.<br><br>"
        "Пока окно видно, в основном окне можно отмечать посещаемость, "
        "ставить оценки и открывать журнал \u2014 запись продолжается.<br><br>"
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
    # Panjarada qidiruv KATAK matni bo'yicha ishlaydi (fan + o'qituvchi +
    # xona). Sinf endi ustun sarlavhasi, katak ichida emas — shuning uchun
    # matn "sinf" ni va'da qilmaydi.
    "tt_search": (
        "Search by teacher or subject...",
        "O'qituvchi yoki fan bo'yicha qidirish...",
        "Поиск по учителю или предмету...",
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

    # ------------------------------------------------------------ murojaatlar
    # Ota-ona shikoyat/taklifi: rahbar paneli + ota-ona ko'rinishi
    "targetzenit:murojaatceo": (
        "View and resolve parent issues addressed to the CEO",
        "CEO ga yo'naltirilgan ota-ona murojaatlarini ko'rish va hal qilish",
        "Просмотр и решение обращений родителей, адресованных CEO",
    ),
    "targetzenit:murojaatdirektor": (
        "View and resolve parent issues addressed to the principal",
        "Direktorga yo'naltirilgan ota-ona murojaatlarini ko'rish va hal qilish",
        "Просмотр и решение обращений родителей, адресованных директору",
    ),
    "mur_panel": ("Parent issues", "Ota-ona murojaatlari", "Обращения родителей"),
    "mur_nav": ("Issues", "Murojaatlar", "Обращения"),
    "mur_mine": ("My issue", "Murojaatim", "Моё обращение"),
    "mur_notyours": (
        "This issue is not addressed to you",
        "Bu murojaat sizga yo'naltirilmagan",
        "Это обращение адресовано не вам",
    ),
    "mur_noaccess": (
        "You have no access to parent issues. The CEO or principal role is required.",
        "Ota-ona murojaatlariga ruxsatingiz yo'q. CEO yoki direktor roli kerak.",
        "У вас нет доступа к обращениям родителей. Нужна роль CEO или директора.",
    ),
    "mur_empty": (
        "No issues in this filter",
        "Bu filtrda murojaat yo'q",
        "В этом фильтре обращений нет",
    ),
    "mur_emptymine": (
        "You have no issues yet. When you speak to a manager and raise a concern, "
        "it appears here together with the management reply.",
        "Hozircha murojaatingiz yo'q. Menejer bilan gaplashib biror masalani "
        "aytsangiz, u rahbariyat javobi bilan birga shu yerda ko'rinadi.",
        "Пока обращений нет. Если вы расскажете менеджеру о проблеме, она появится "
        "здесь вместе с ответом руководства.",
    ),
    "mur_said": ("What the parent said", "Ota-ona nima dedi", "Что сказал родитель"),
    "mur_answer": ("Management reply", "Rahbariyat javobi", "Ответ руководства"),
    "mur_waiting": (
        "Being reviewed by management",
        "Rahbariyat ko'rib chiqmoqda",
        "Рассматривается руководством",
    ),
    "mur_who": ("Who raised it", "Kim murojaat qildi", "Кто обратился"),
    "mur_manager": ("Spoke to", "Gaplashgan menejer", "Говорил менеджер"),
    "mur_talked": ("Conversation", "Suhbat", "Разговор"),
    "mur_source": ("Source", "Manba", "Источник"),
    "mur_cat": ("Category", "Kategoriya", "Категория"),
    "mur_nocat": ("No category", "Kategoriya yo'q", "Без категории"),
    "mur_owner": ("Assigned to", "Mas'ul rahbar", "Ответственный"),
    "mur_noowner": ("Unassigned", "Biriktirilmagan", "Не назначен"),
    "mur_takeit": ("Assign to me", "O'zimga biriktirish", "Назначить себе"),
    "mur_tookit": ("Assigned to self", "O'ziga biriktirdi", "Назначил себе"),
    "mur_seen": ("Seen by", "Ko'rdi", "Просмотрел"),
    "mur_notseen": ("Not reviewed yet", "Hali ko'rilmagan", "Ещё не просмотрено"),
    "mur_parentseen": (
        "Parent has read the reply",
        "Ota-ona javobni o'qidi",
        "Родитель прочитал ответ",
    ),
    "mur_parentnotseen": (
        "Parent has not opened the reply yet",
        "Ota-ona javobni hali ochmagan",
        "Родитель ещё не открыл ответ",
    ),
    "mur_comment": ("Comment", "Izoh", "Комментарий"),
    "mur_comments": ("Comments", "Izohlar", "Комментарии"),
    "mur_addcomment": ("Add comment", "Izoh qo'shish", "Добавить комментарий"),
    "mur_commenthint": (
        "Internal note — the parent does not see it",
        "Ichki izoh — ota-ona ko'rmaydi",
        "Внутренняя заметка — родитель её не видит",
    ),
    "mur_decision": ("Decision", "Qaror", "Решение"),
    "mur_decide": ("Make a decision", "Qaror qabul qilish", "Принять решение"),
    "mur_decisionhint": (
        "Write clearly what was done, who does it and when — the parent reads this text.",
        "Aniq yozilsin: nima qilindi, kim bajaradi, qachon — ota-ona SHU matnni o'qiydi.",
        "Напишите конкретно: что сделано, кто и когда выполнит — родитель читает ИМЕННО этот текст.",
    ),
    "mur_needtext": (
        "The decision text is required — without it the issue cannot be closed.",
        "Qaror matni majburiy — u bo'lmasa murojaat yopilmaydi.",
        "Текст решения обязателен — без него обращение не закрыть.",
    ),
    "mur_q_solved": ("Solved", "Hal qilindi", "Решено"),
    "mur_q_accepted": ("Accepted", "Inobatga olindi", "Принято к сведению"),
    "mur_q_later": ("Later", "Keyinroq ko'riladi", "Рассмотрим позже"),
    "mur_q_rejected": ("Rejected", "Rad etildi", "Отклонено"),
    "mur_q_irrelevant": ("Not relevant", "Ahamiyatsiz", "Не актуально"),
    "mur_irrelevanthint": (
        "«Not relevant» closes the issue without notifying the parent — use it when "
        "the AI picked up something that was not actually a complaint.",
        "«Ahamiyatsiz» — ota-onaga xabar YUBORMASDAN yopadi: AI shikoyat bo'lmagan "
        "gapni shikoyat deb olgan hollar uchun.",
        "«Не актуально» закрывает обращение БЕЗ уведомления родителя — для случаев, "
        "когда ИИ принял за жалобу обычную фразу.",
    ),
    "mur_forward": ("Forward", "Uzatish", "Переадресовать"),
    "mur_forwardto": ("Forward to {$a}", "{$a} ga uzatish", "Переадресовать: {$a}"),
    "mur_fromsales": (
        "Received from the sales team ({$a})",
        "Sotuv bo'limidan keldi ({$a})",
        "Поступило из отдела продаж ({$a})",
    ),
    "mur_system": ("System", "Tizim", "Система"),
    "mur_noaccount": (
        "No Moodle account for this student — the parent will not receive the reply "
        "in the app.",
        "Bu o'quvchining Moodle akkaunti yo'q — ota-ona javobni ilovada olmaydi.",
        "У этого ученика нет аккаунта Moodle — родитель не получит ответ в приложении.",
    ),
    "mur_due": ("Due", "Muddat", "Срок"),
    "mur_duetoday": ("Reply due today", "Javob bugun", "Ответ сегодня"),
    "mur_duein": ("{$a} days left", "{$a} kun qoldi", "Осталось {$a} дн."),
    "mur_overdue": ("{$a} days overdue", "{$a} kun kechikdi", "Просрочено на {$a} дн."),
    "mur_f_open": ("Open", "Ochiq", "Открытые"),
    "mur_f_new": ("New", "Yangi", "Новые"),
    "mur_f_overdue": ("Overdue", "Muddati o'tdi", "Просроченные"),
    "mur_f_mine": ("Mine", "Menda", "Мои"),
    "mur_f_closed": ("Closed", "Yopilgan", "Закрытые"),
    "mur_f_all": ("All", "Hammasi", "Все"),
    "mur_k_open": ("Open issues", "Ochiq murojaat", "Открытых обращений"),
    "mur_k_unseen": ("Not reviewed", "Ko'rilmagan", "Не просмотрено"),
    "mur_k_overdue": ("Overdue", "Muddati o'tdi", "Просрочено"),
    "mur_k_mine": ("Assigned to me", "Menda", "На мне"),
    "mur_k_closedmonth": ("Closed this month", "Bu oy yopildi", "Закрыто за месяц"),
    "mur_repeat": (
        "Which issue repeats most often",
        "Qaysi masala ko'p takrorlanyapti",
        "Какая проблема повторяется чаще",
    ),
    "mur_search": (
        "Student, parent, text",
        "O'quvchi, ota-ona, matn",
        "Ученик, родитель, текст",
    ),
    "mur_saved": ("Saved", "Saqlandi", "Сохранено"),
    "mur_openweb": ("Open in browser", "Brauzerda ochish", "Открыть в браузере"),
    "mur_msgsubject": (
        "Reply to your issue",
        "Murojaatingizga javob",
        "Ответ на ваше обращение",
    ),
    "mur_msgbody": (
        "Your issue: {$a->murojaat}\n\nManagement reply: {$a->qaror}",
        "Murojaatingiz: {$a->murojaat}\n\nRahbariyat javobi: {$a->qaror}",
        "Ваше обращение: {$a->murojaat}\n\nОтвет руководства: {$a->qaror}",
    ),
    "messageprovider:murojaat": (
        "Reply to a parent issue",
        "Ota-ona murojaatiga javob",
        "Ответ на обращение родителя",
    ),
    "mur_k_unassigned": ("Unassigned", "Biriktirilmagan", "Не назначено"),
    "mur_k_avgdays": ("Avg. resolution", "O'rtacha hal qilish", "Среднее решение"),
    "mur_days": ("days", "kun", "дн."),
    "mur_f_alltypes": ("Type: all", "Turi: hammasi", "Тип: все"),
    "mur_f_allcats": ("Category: all", "Kategoriya: hammasi", "Категория: все"),
    "mur_f_allowners": ("Assigned: all", "Kim biriktirilgan: hammasi", "Ответственный: все"),
    "mur_apply": ("Apply", "Qo'llash", "Применить"),
    "mur_clear": ("Clear", "Tozalash", "Сбросить"),
    "mur_debt": ("Outstanding debt", "Qarz", "Задолженность"),
    "mur_nodebt": ("No open debt", "Qarz yo'q", "Долга нет"),
    "mur_salestalks": (
        "Conversations with the sales team",
        "Sotuv bo'limi bilan aloqa tarixi",
        "История общения с отделом продаж",
    ),
    "mur_salestalkshint": (
        "So that the parent is not told the same thing twice",
        "Ota-onaga ikkinchi marta bir xil javob bermaslik uchun",
        "Чтобы родителю не ответили дважды одно и то же",
    ),
    "mur_time": ("Time", "Vaqt", "Время"),
    "mur_result": ("Result", "Natija", "Результат"),
    "mur_others": (
        "Other issues from this student",
        "Shu o'quvchi bo'yicha boshqa murojaatlar",
        "Другие обращения по этому ученику",
    ),
    "mur_othershint": (
        "Is this a repeating issue?",
        "Takrorlanayotgan masalami?",
        "Повторяющаяся проблема?",
    ),

    # ------------------------------------------------- o'quvchi sharhi (sharh.php)
    "sh_nav": ("Student reviews", "O'quvchilar sharhi", "Отзывы об учениках"),
    "sh_navall": ("All student reviews", "Barcha sharhlar", "Все отзывы"),
    "sh_mine": ("Teachers' reviews", "O'qituvchilar sharhi", "Отзывы учителей"),
    "sh_hint": (
        "A short written review of each student — the student and the parent see it",
        "Har bir o'quvchi haqida qisqa yozma sharh — o'quvchi va ota-ona ko'radi",
        "Краткий письменный отзыв о каждом ученике — его видят ученик и родитель",
    ),
    "sh_minehint": (
        "What the teachers wrote about your studies",
        "O'qituvchilar o'qishingiz haqida nima deb yozgan",
        "Что учителя написали о вашей учёбе",
    ),
    "sh_allhint": (
        "Who wrote what about which student, and whether the student has read it",
        "Kim qaysi o'quvchiga nima deb yozdi va o'quvchi o'qidimi",
        "Кто что написал о каком ученике и прочитал ли ученик",
    ),
    "sh_student": ("Student", "O'quvchi", "Ученик"),
    "sh_students": ("Students", "O'quvchilar", "Ученики"),
    "sh_teacher": ("Teacher", "O'qituvchi", "Учитель"),
    "sh_teachers": ("Teachers", "O'qituvchilar", "Учителя"),
    "sh_group": ("Group", "Guruh", "Группа"),
    "sh_groupsubject": ("Group and subject", "Guruh va fan", "Группа и предмет"),
    "sh_count": ("Reviews", "Sharhlar", "Отзывы"),
    "sh_last": ("Latest review and new entry", "Oxirgi sharh va yangi yozuv",
                "Последний отзыв и новая запись"),
    "sh_text": ("Review", "Sharh", "Отзыв"),
    "sh_when": ("When", "Qachon", "Когда"),
    "sh_state": ("Status", "Holat", "Состояние"),
    "sh_total": ("Reviews", "Sharhlar", "Отзывы"),
    "sh_written": ("Reviewed", "Sharh yozilgan", "С отзывом"),
    "sh_ofstudents": ("of {$a} students", "{$a} o'quvchidan", "из {$a} учеников"),
    "sh_missing": ("Not yet reviewed", "Hali yozilmagan", "Пока без отзыва"),
    "sh_missinghint": (
        "No review written for these students yet",
        "Bu o'quvchilar haqida hali sharh yo'q",
        "Об этих учениках отзыва ещё нет",
    ),
    "sh_covered": ("Students covered", "Qamrab olingan o'quvchi", "Охвачено учеников"),
    "sh_coveredhint": (
        "Distinct students mentioned in this period",
        "Shu davrda sharh yozilgan turli o'quvchilar",
        "Разные ученики с отзывом за период",
    ),
    "sh_unseen": ("Not read yet", "O'qilmagan", "Не прочитано"),
    "sh_unseenhint": (
        "The student has not opened the review",
        "O'quvchi sharhni ochmagan",
        "Ученик не открыл отзыв",
    ),
    "sh_privates": ("Internal notes", "Maxfiy qaydlar", "Служебные заметки"),
    "sh_privateshint": (
        "Not sent to the student",
        "O'quvchiga yuborilmaydi",
        "Ученику не отправляются",
    ),
    "sh_none": ("No review yet", "Hali sharh yo'q", "Отзыва пока нет"),
    "sh_emptyall": (
        "No reviews match these filters",
        "Bu filtrlarga mos sharh topilmadi",
        "По этим фильтрам отзывов не найдено",
    ),
    "sh_emptymine": (
        "No reviews from teachers yet",
        "O'qituvchilardan hali sharh yo'q",
        "Отзывов от учителей пока нет",
    ),
    "sh_nocourses": (
        "You have no courses where you can write reviews. Reviews are written "
        "in a course you teach — check that you are enrolled as a teacher.",
        "Sharh yozadigan kursingiz yo'q. Sharh siz dars o'tadigan kursda "
        "yoziladi — o'qituvchi sifatida yozilganingizni tekshirib ko'ring.",
        "У вас нет курсов, где можно писать отзывы. Отзыв пишется в курсе, "
        "где вы преподаёте — проверьте, записаны ли вы как учитель.",
    ),
    "sh_noenrolled": (
        "No students enrolled in this course",
        "Bu kursga o'quvchi yozilmagan",
        "В этот курс ученики не записаны",
    ),
    "sh_nomatch": ("No student matches the search", "Qidiruvga mos o'quvchi yo'q",
                   "По поиску ученик не найден"),
    "sh_notstudent": (
        "This person is not a student of this course",
        "Bu odam shu kursning o'quvchisi emas",
        "Этот человек не является учеником данного курса",
    ),
    "sh_about": ("Reviews about {$a}", "{$a} haqida sharhlar", "Отзывы о {$a}"),
    "sh_countof": ("{$a} review(s)", "{$a} ta sharh", "Отзывов: {$a}"),
    "sh_placeholder": (
        "How is the student doing: what they manage, what to work on, what to "
        "tell the parent",
        "O'quvchi qanday o'qiyapti: nimani uddalaydi, nimani mustahkamlash "
        "kerak, ota-onaga nima aytiladi",
        "Как учится ученик: что получается, что подтянуть, что сказать родителю",
    ),
    "sh_addshort": ("Short review…", "Qisqa sharh…", "Короткий отзыв…"),
    "sh_send": ("Save the review", "Sharhni saqlash", "Сохранить отзыв"),
    "sh_saved": ("The review has been saved", "Sharh saqlandi", "Отзыв сохранён"),
    "sh_count_title": ("Number of reviews", "Sharhlar soni", "Количество отзывов"),
    "sh_openrow": ("Open", "Ochish", "Открыть"),
    "sh_openfull": ("Full text", "To'liq matn", "Полный текст"),
    "sh_close": ("Close", "Yopish", "Закрыть"),
    "sh_search": ("Search", "Qidirish", "Искать"),
    "sh_searchph": ("First or last name", "Ism yoki familiya", "Имя или фамилия"),
    "sh_clear": ("Clear", "Tozalash", "Очистить"),
    "sh_allteachers": ("All teachers", "Hamma o'qituvchi", "Все учителя"),
    "sh_allgroups": ("All groups", "Hamma guruh", "Все группы"),
    "sh_onlyunseen": ("Only unread", "Faqat o'qilmaganlar", "Только непрочитанные"),
    "sh_seen": ("Read", "O'qildi", "Прочитано"),
    "sh_notseen": ("Not read", "O'qilmagan", "Не прочитано"),
    "sh_private": ("Internal", "Maxfiy", "Служебная"),
    "sh_privatelab": (
        "Internal note — the student does not see it",
        "Maxfiy qayd — o'quvchi ko'rmaydi",
        "Служебная заметка — ученик не видит",
    ),
    "sh_privatehelp": (
        "An internal note is visible only to staff and no notification is sent. "
        "By default a review goes to the student and the parent.",
        "Maxfiy qaydni faqat xodim ko'radi va bildirishnoma yuborilmaydi. "
        "Standart holda sharh o'quvchi va ota-onaga boradi.",
        "Служебную заметку видят только сотрудники, уведомление не отправляется. "
        "По умолчанию отзыв идёт ученику и родителю.",
    ),
    "sh_msgsubject": (
        "A new teacher review: {$a}",
        "O'qituvchidan yangi sharh: {$a}",
        "Новый отзыв учителя: {$a}",
    ),
    "messageprovider:sharh": (
        "Teacher review about a student",
        "O'qituvchining o'quvchi haqidagi sharhi",
        "Отзыв учителя об ученике",
    ),

    # ------------------------------------------- baholash (FB/BSB/LI/CHSB)
    "targetzenit:manageexams": (
        "Organise summative assessments (BSB / CHSB / project work)",
        "Summativ baholashni tashkil qilish (BSB / CHSB / loyiha ishi)",
        "Организовывать суммативное оценивание (BSB / CHSB / проектная работа)",
    ),
    "targetzenit:gradeexam": (
        "Enter summative assessment scores in own course",
        "O'z kursida summativ baholash ballini kiritish",
        "Вводить баллы суммативного оценивания в своём курсе",
    ),
    "targetzenit:viewexamresults": (
        "View summative assessment results across the school",
        "Butun maktab bo'yicha summativ baholash natijalarini ko'rish",
        "Просматривать результаты суммативного оценивания по всей школе",
    ),

    "bh_nav": ("Assessment", "Baholash", "Оценивание"),
    "bh_navall": ("All assessments", "Barcha baholashlar", "Все оценивания"),
    "bh_turi_BSB": ("BSB (unit)", "BSB (bob)", "BSB (раздел)"),
    "bh_turi_CHSB": ("CHSB (quarter)", "CHSB (chorak)", "CHSB (четверть)"),
    "bh_turi_LI": ("Project work", "Loyiha ishi", "Проектная работа"),
    "bh_shakli_onlayn": ("Online", "Onlayn", "Онлайн"),
    "bh_shakli_qogoz": ("On paper", "Qog'ozda", "На бумаге"),
    "bh_holat_ishladi": ("Sat the test", "Ishladi", "Выполнял"),
    "bh_holat_uzrli": ("Absent, excused", "Uzrli sababli yo'q", "Отсутствовал по уважительной причине"),
    "bh_holat_uzrsiz": ("Absent, unexcused", "Uzrsiz yo'q", "Отсутствовал без причины"),
    "bh_holat_chetlashtirildi": ("Removed for misconduct", "Tartibbuzarlik uchun chetlashtirildi",
                                 "Отстранён за нарушение"),
    "bh_holat_ozod": ("Exempt (health)", "Ozod etilgan (salomatlik)", "Освобождён (здоровье)"),
    "bh_holat_imtiyoz": ("Exempt (olympiad/certificate)", "Imtiyoz (olimpiada/sertifikat)",
                         "Льгота (олимпиада/сертификат)"),
    "bh_status_qoralama": ("Draft", "Qoralama", "Черновик"),
    "bh_status_tasdiqlangan": ("Approved", "Tasdiqlangan", "Утверждено"),
    "bh_status_otkazildi": ("Held", "O'tkazildi", "Проведено"),
    "bh_status_yopildi": ("Closed", "Yopildi", "Закрыто"),

    "bh_baddate": (
        "The date must be in YYYY-MM-DD format",
        "Sana YYYY-MM-DD ko'rinishida bo'lishi kerak",
        "Дата должна быть в формате ГГГГ-ММ-ДД",
    ),
    "bh_noexam": (
        "The assessment was not found",
        "Baholash topilmadi",
        "Оценивание не найдено",
    ),
    "bh_notstudent": (
        "This person is not a student of this course",
        "Bu odam shu kursning o'quvchisi emas",
        "Этот человек не является учеником данного курса",
    ),
    "bh_reasonrequired": (
        "More than {$a} hours have passed since the score was entered, so a "
        "reason for the correction is required",
        "Ball kiritilganiga {$a} soatdan ko'p vaqt o'tgan, shuning uchun "
        "tuzatish sababi ko'rsatilishi shart",
        "С момента ввода балла прошло более {$a} часов, поэтому требуется "
        "указать причину исправления",
    ),
    "bh_windowclosed": (
        "The {$a}-hour correction window has closed. Only the coordinator may "
        "correct the score now, and a reason must be given.",
        "{$a} soatlik tuzatish oynasi yopilgan. Endi ballni faqat koordinator "
        "tuzatadi va sabab ko'rsatilishi shart.",
        "{$a}-часовое окно исправления закрыто. Теперь балл может исправить "
        "только координатор, с указанием причины.",
    ),
    "bh_clash_subjects": (
        "{$a->soni} subjects have assessments on this day for this class — the "
        "limit is {$a->chegara}",
        "Shu sinfda shu kunda {$a->soni} fandan baholash bor — chegara {$a->chegara}",
        "В этом классе в этот день оценивание по {$a->soni} предметам — предел {$a->chegara}",
    ),
    "bh_clash_hours": (
        "Total assessment time on this day is {$a->daqiqa} minutes — the limit "
        "is {$a->chegara}",
        "Shu kundagi baholashlarning umumiy vaqti {$a->daqiqa} daqiqa — chegara "
        "{$a->chegara} daqiqa",
        "Общее время оцениваний в этот день {$a->daqiqa} минут — предел {$a->chegara}",
    ),
    "bh_clash_lesson": (
        "Overlaps with a lesson: {$a->dars}. {$a->fan} at {$a->vaqt}",
        "Dars bilan ustma-ust tushadi: {$a->dars}-dars · {$a->fan} · {$a->vaqt}",
        "Накладывается на урок: {$a->dars}-й урок · {$a->fan} · {$a->vaqt}",
    ),
    "bh_capped": (
        "Capped at 30% under the regulation",
        "Nizom bo'yicha 30% bilan chegaralangan",
        "Ограничено 30% согласно положению",
    ),
    "bh_parts_hint": (
        "Counted: {$a}",
        "Hisobga olindi: {$a}",
        "Учтено: {$a}",
    ),
    "bh_frozen": ("The quarter is closed", "Chorak yopilgan", "Четверть закрыта"),

    # --- baholash paneli (baholash.php)
    "bh_hint": (
        "Summative assessments: schedule, questions, scores",
        "Summativ baholashlar: jadval, savollar, ballar",
        "Суммативные оценивания: расписание, вопросы, баллы",
    ),
    "bh_new": ("New assessment", "Yangi baholash", "Новое оценивание"),
    "bh_edit": ("Edit", "Tahrirlash", "Редактировать"),
    "bh_choose": ("— choose —", "— tanlang —", "— выберите —"),
    "bh_term": ("Quarter", "Chorak", "Четверть"),
    "bh_allterms": ("All quarters", "Hamma chorak", "Все четверти"),
    "bh_type": ("Type", "Turi", "Тип"),
    "bh_alltypes": ("All types", "Hamma turi", "Все типы"),
    "bh_allsubjects": ("All subjects", "Hamma fan", "Все предметы"),
    "bh_count": ("Assessments", "Baholashlar", "Оценивания"),
    "bh_withresults": ("With scores", "Ballari bor", "С баллами"),
    "bh_withresultshint": (
        "At least one score entered",
        "Kamida bitta ball kiritilgan",
        "Введён хотя бы один балл",
    ),
    "bh_retro": ("Past period", "O'tgan davr", "Прошлый период"),
    "bh_retrohint": (
        "Entered retrospectively, on paper",
        "Keyin kiritilgan, qog'oz shaklda",
        "Внесено позже, на бумаге",
    ),
    "bh_empty": (
        "No assessments match these filters",
        "Bu filtrlarga mos baholash topilmadi",
        "По этим фильтрам оцениваний не найдено",
    ),
    "bh_date": ("Date", "Sana", "Дата"),
    "bh_time": ("Time", "Vaqt", "Время"),
    "bh_name": ("Name", "Nomi", "Название"),
    "bh_form": ("Form", "Shakli", "Форма"),
    "bh_groups": ("Groups", "Guruhlar", "Группы"),
    "bh_group": ("Group", "Guruh", "Группа"),
    "bh_scores": ("Scores", "Ballar", "Баллы"),
    "bh_status": ("Status", "Holat", "Состояние"),
    "bh_level": (
        "Scope: class level or level group",
        "Qamrov: sinf darajasi yoki daraja guruhi",
        "Охват: уровень класса или уровневая группа",
    ),
    "bh_class": ("Grade {$a}", "{$a}-sinf", "{$a} класс"),
    "bh_nogroupsfound": (
        "The assessment was created, but no group matched: this subject has "
        "nothing under \"{$a}\". Check the scope, or pick a class level instead "
        "of a level group.",
        "Baholash yaratildi, lekin birorta guruh mos kelmadi: bu fanda "
        "\"{$a}\" bo'yicha guruh yo'q. Qamrovni tekshiring yoki daraja guruhi "
        "o'rniga sinf darajasini tanlang.",
        "Оценивание создано, но ни одна группа не подошла: у этого предмета "
        "нет групп по \"{$a}\". Проверьте охват или выберите уровень класса "
        "вместо уровневой группы.",
    ),
    "bh_blockgroup": (
        "Level groups {$a} (High / Middle / Low)",
        "{$a} daraja guruhlari (High / Middle / Low)",
        "Уровневые группы {$a} (High / Middle / Low)",
    ),
    "bh_room": ("Room", "Xona", "Кабинет"),
    "bh_duration": ("Duration, minutes", "Davomiyligi, daqiqa", "Длительность, минут"),
    "bh_maxscore": ("Maximum score", "Maksimal ball", "Максимальный балл"),
    "bh_random": ("Random questions", "Tasodifiy savol soni", "Случайных вопросов"),

    "bh_typehint": (
        "CHSB is held once, at the end of the quarter; BSB up to three times "
        "during it",
        "CHSB chorak oxirida bir marta, BSB chorak davomida uch martagacha",
        "CHSB проводится один раз в конце четверти, BSB — до трёх раз в течение",
    ),
    "bh_levelhint": (
        "A class level covers all its classes: 7 means 7 A, 7 B, 7 C. A level "
        "group covers the ability groups of those classes: 8-9 means ENGLISH "
        "High 8-9, Middle 8-9, Low 8-9 - each with its own students. Remove "
        "the groups that did not take the test.",
        "Sinf darajasi barcha sinflarni qamraydi: 7 degani 7 A, 7 B, 7 C. "
        "Daraja guruhi esa o'sha sinflarning bilim darajasi bo'yicha "
        "guruhlarini qamraydi: 8-9 degani ENGLISH High 8-9, Middle 8-9, "
        "Low 8-9 - har biri o'z o'quvchilari bilan. Test olinmagan guruhni "
        "keyin olib tashlaysiz.",
        "Уровень класса охватывает все классы: 7 значит 7 A, 7 B, 7 C. "
        "Уровневая группа охватывает группы по уровню знаний: 8-9 значит "
        "ENGLISH High 8-9, Middle 8-9, Low 8-9 - у каждой свои ученики. "
        "Группу, которая не писала тест, потом удалите.",
    ),
    "bh_formhint": (
        "Online means Moodle marks it automatically; on paper the score is "
        "entered by hand",
        "Onlayn — Moodle o'zi baholaydi; qog'ozda ball qo'lda kiritiladi",
        "Онлайн — Moodle оценивает сам; на бумаге балл вводится вручную",
    ),
    "bh_datehint": (
        "A past date is allowed: that is how a quarter the system missed is "
        "entered. The form is then forced to paper.",
        "O'tgan sana ham mumkin: tizim o'tkazib yuborgan chorak shunday "
        "kiritiladi. Unda shakl majburan qog'oz bo'ladi.",
        "Прошедшая дата разрешена: так вносится четверть, которую система "
        "пропустила. Форма тогда принудительно бумажная.",
    ),
    "bh_timehint": (
        "The default for every group — it can be shifted per group afterwards",
        "Hamma guruh uchun standart — keyin har guruhga alohida siljitiladi",
        "По умолчанию для всех групп — потом можно сдвинуть для каждой",
    ),
    "bh_roomhint": (
        "The default room; it can be changed per group",
        "Standart xona; har guruhga o'zgartirish mumkin",
        "Кабинет по умолчанию; можно изменить для каждой группы",
    ),
    "bh_maxscorehint": (
        "Scores go into the journal out of 100 under the regulation",
        "Nizom bo'yicha natija jurnalga 100 ballik tizimda kiritiladi",
        "Согласно положению результат вносится в журнал по 100-балльной системе",
    ),
    "bh_randomhint": (
        "0 means every question in the bank. Online only.",
        "0 — bankdagi hamma savol. Faqat onlayn shaklda.",
        "0 — все вопросы банка. Только для онлайн-формы.",
    ),
    "bh_sit_bekor": ("removed", "chiqarilgan", "исключена"),
    "bh_sitremove": ("Remove group", "Guruhni chiqarish", "Исключить группу"),
    "bh_sitrestore": ("Restore", "Tiklash", "Восстановить"),
    "bh_sitremoveq": (
        "Remove this group from the assessment?",
        "Shu guruh baholashdan chiqarilsinmi?",
        "Исключить эту группу из оценивания?",
    ),
    "bh_sitremovewarn": (
        "<b>{$a->results} entered score(s)</b> for this group will be deleted and "
        "the term grades of those students recalculated without them. The group "
        "stays in the list as «removed» and will not come back when you refresh "
        "groups. You can restore it later, but the scores will not return.",
        "Bu guruhning <b>{$a->results} ta kiritilgan balli</b> o'chiriladi va "
        "o'sha o'quvchilarning chorak bahosi ularsiz qayta hisoblanadi. Guruh "
        "ro'yxatda «chiqarilgan» bo'lib qoladi va «Guruhlarni qayta biriktirish» "
        "bosilganda qaytib kelmaydi. Keyin tiklash mumkin, lekin ballar qaytmaydi.",
        "<b>{$a->results} введённых баллов</b> этой группы будут удалены, а "
        "четвертные оценки этих учеников пересчитаны без них. Группа останется "
        "в списке как «исключена» и не вернётся при обновлении групп. Её можно "
        "восстановить, но баллы не вернутся.",
    ),
    "bh_sitremoveempty": (
        "No scores have been entered for this group. It will stay in the list as "
        "«removed» and will not come back when you refresh groups.",
        "Bu guruhga hali ball kiritilmagan. U ro'yxatda «chiqarilgan» bo'lib "
        "qoladi va «Guruhlarni qayta biriktirish» bosilganda qaytib kelmaydi.",
        "Баллы для этой группы ещё не вводились. Она останется в списке как "
        "«исключена» и не вернётся при обновлении групп.",
    ),
    "bh_sitremoved": (
        "Group removed from the assessment: {$a->results} score(s) deleted, "
        "{$a->recalc} term grade(s) recalculated.",
        "Guruh baholashdan chiqarildi: {$a->results} ta ball o'chirildi, "
        "{$a->recalc} ta chorak bahosi qayta hisoblandi.",
        "Группа исключена из оценивания: удалено баллов — {$a->results}, "
        "пересчитано четвертных оценок — {$a->recalc}.",
    ),
    "bh_sitcancelled": (
        "This group has been removed from the assessment, so scores cannot be "
        "entered. Restore it first.",
        "Bu guruh baholashdan chiqarilgan, ball kiritib bo'lmaydi. Avval uni "
        "tiklang.",
        "Эта группа исключена из оценивания, баллы вводить нельзя. Сначала "
        "восстановите её.",
    ),

    "bh_actions": ("Actions", "Amallar", "Действия"),
    "bh_delete": ("Delete", "O'chirish", "Удалить"),
    "bh_cancel": ("Cancel", "Bekor qilish", "Отмена"),
    "bh_deleteq": (
        "Delete this assessment?",
        "Shu baholash o'chirilsinmi?",
        "Удалить это оценивание?",
    ),
    "bh_deletewarn": (
        "This will permanently delete {$a->sittings} group sitting(s) and "
        "<b>{$a->results} entered score(s)</b>. Term grades of the affected "
        "students will be recalculated without this assessment. This cannot "
        "be undone.",
        "Bu {$a->sittings} ta guruh o'tkazmasi va <b>{$a->results} ta "
        "kiritilgan ball</b>ni butunlay o'chiradi. Ta'sir ko'rgan "
        "o'quvchilarning chorak bahosi shu baholashsiz qayta hisoblanadi. "
        "Buni qaytarib bo'lmaydi.",
        "Это безвозвратно удалит {$a->sittings} групповых сессий и "
        "<b>{$a->results} введённых баллов</b>. Четвертные оценки "
        "затронутых учеников будут пересчитаны без этого оценивания. "
        "Отменить нельзя.",
    ),
    "bh_deleteempty": (
        "No scores have been entered yet ({$a->sittings} group sitting(s) "
        "will be removed). This cannot be undone.",
        "Hali hech qanday ball kiritilmagan ({$a->sittings} ta guruh "
        "o'tkazmasi o'chadi). Buni qaytarib bo'lmaydi.",
        "Баллы ещё не вводились (будет удалено {$a->sittings} групповых "
        "сессий). Отменить нельзя.",
    ),
    "bh_deleted": (
        "Assessment deleted: {$a->sittings} sitting(s), {$a->results} score(s); "
        "{$a->recalc} term grade(s) recalculated.",
        "Baholash o'chirildi: {$a->sittings} o'tkazma, {$a->results} ball; "
        "{$a->recalc} ta chorak bahosi qayta hisoblandi.",
        "Оценивание удалено: {$a->sittings} сессий, {$a->results} баллов; "
        "пересчитано четвертных оценок: {$a->recalc}.",
    ),

    "bh_nameph": (
        "e.g. October exam (leave empty for an automatic name)",
        "masalan: Oktabr imtixon (bo'sh qoldirsangiz avtomatik nomlanadi)",
        "например: Октябрьский экзамен (оставьте пустым для автоназвания)",
    ),
    "bh_namehint": (
        "Leave empty and the system will compose it from the quarter, subject "
        "and class",
        "Bo'sh qoldirsangiz tizim chorak, fan va sinfdan o'zi yasaydi",
        "Оставьте пустым — система составит из четверти, предмета и класса",
    ),
    "bh_savenext": (
        "Save and set the timetable",
        "Saqlash va jadvalni belgilash",
        "Сохранить и задать расписание",
    ),
    "bh_save": ("Save", "Saqlash", "Сохранить"),
    "bh_saved": ("Saved", "Saqlandi", "Сохранено"),
    "bh_fillall": (
        "Quarter, subject and class level are required",
        "Chorak, fan va sinf darajasi to'ldirilishi shart",
        "Четверть, предмет и уровень класса обязательны",
    ),
    "bh_groupsadded": (
        "{$a->kurslar} groups found, {$a->qoshildi} added",
        "{$a->kurslar} guruh topildi, {$a->qoshildi} tasi qo'shildi",
        "Найдено групп: {$a->kurslar}, добавлено: {$a->qoshildi}",
    ),
    "bh_nogroups": (
        "No group matched. Check that the subject and class level are right, "
        "and that courses have been synced.",
        "Mos guruh topilmadi. Fan va sinf darajasi to'g'rimi, kurslar "
        "sinxroni o'tganmi — tekshirib ko'ring.",
        "Подходящих групп нет. Проверьте предмет и уровень класса, а также "
        "синхронизацию курсов.",
    ),
    "bh_savetimetable": ("Save the timetable", "Jadvalni saqlash", "Сохранить расписание"),
    "bh_inherit": ("Inherited: {$a}", "Merosga olinadi: {$a}", "Наследуется: {$a}"),
    "bh_inherithint": (
        "An empty field takes the assessment default",
        "Bo'sh maydon baholashning standart qiymatini oladi",
        "Пустое поле берёт значение по умолчанию",
    ),
    "bh_refreshgroups": ("Refresh groups", "Guruhlarni yangilash", "Обновить группы"),
    "bh_refreshhint": (
        "If a new group has appeared since",
        "Shundan keyin yangi guruh paydo bo'lgan bo'lsa",
        "Если после этого появилась новая группа",
    ),
    "bh_retronotice": (
        "This is a past period: no online test is created and no notification "
        "is sent. Enter the scores by hand.",
        "Bu o'tgan davr: onlayn test yaratilmaydi va bildirishnoma "
        "yuborilmaydi. Ballarni qo'lda kiritasiz.",
        "Это прошлый период: онлайн-тест не создаётся и уведомление не "
        "отправляется. Баллы вводятся вручную.",
    ),
    "bh_clash_future": (
        "Clashes found — they must be resolved before the test is created",
        "To'qnashuv topildi — test yaratilishidan oldin hal qilinishi kerak",
        "Найдены накладки — их нужно устранить до создания теста",
    ),
    "bh_clash_retro": (
        "Clashes found. This is a past period, so they are only a note.",
        "To'qnashuv topildi. Bu o'tgan davr, shuning uchun faqat qayd.",
        "Найдены накладки. Это прошлый период, поэтому только отметка.",
    ),
    "bh_sit_kutilmoqda": ("Scheduled", "Kutilmoqda", "Ожидается"),
    "bh_sit_otkazildi": ("Held", "O'tkazildi", "Проведено"),
    "bh_sit_yopildi": ("Closed", "Yopildi", "Закрыто"),
    "bh_sit_xato": ("Error", "Xato", "Ошибка"),

    # --- ball kiritish setkasi (output/ballsetka.php)
    # ----------------------------- Excel'dan ball yuklash (exam_import.php)
    "bh_imp_title": (
        "Upload scores from Excel",
        "Excel'dan ballarni yuklash",
        "Загрузить баллы из Excel",
    ),
    "bh_imp_hint": (
        "The file is only read — nothing is saved until you check the grid "
        "and press Save",
        "Fayl faqat O'QILADI — setkani tekshirib «Saqlash» bosmaguningizcha "
        "hech narsa saqlanmaydi",
        "Файл только читается — ничего не сохраняется, пока вы не проверите "
        "таблицу и не нажмёте «Сохранить»",
    ),
    "bh_imp_read": ("Read the file", "Faylni o'qish", "Прочитать файл"),
    "bh_imp_formats": (
        "xlsx, xls, csv. The file needs a name column and either a score "
        "column or one column per question (0/1). A student ID column makes "
        "matching exact.",
        "xlsx, xls, csv. Faylda ism ustuni va yo ball ustuni, yoki har savolga "
        "bitta ustun (0/1) bo'lishi kerak. O'quvchi ID ustuni bo'lsa moslik "
        "aniq bo'ladi.",
        "xlsx, xls, csv. В файле нужен столбец с именем и либо столбец с "
        "баллом, либо по столбцу на каждый вопрос (0/1). Столбец с ID ученика "
        "делает сопоставление точным.",
    ),
    "bh_imp_nofile": ("No file was chosen", "Fayl tanlanmadi", "Файл не выбран"),
    "bh_imp_failed": (
        "The file could not be read: {$a}",
        "Faylni o'qib bo'lmadi: {$a}",
        "Не удалось прочитать файл: {$a}",
    ),
    "bh_imp_unreadable": (
        "The file could not be opened",
        "Faylni ochib bo'lmadi",
        "Файл не удалось открыть",
    ),
    "bh_imp_nohead": (
        "A name column was not found. The file needs a header row with a cell "
        "such as F.I.Sh., Student or Name.",
        "Ism ustuni topilmadi. Faylda sarlavha qatori bo'lishi va unda "
        "«F.I.Sh.», «O'quvchi» yoki «Name» kabi katak turishi kerak.",
        "Столбец с именем не найден. В файле нужна строка заголовков с ячейкой "
        "вида «ФИО», «Ученик» или «Name».",
    ),
    "bh_imp_noscore": (
        "A score column was not found, and the columns do not look like "
        "per-question marks either. Add a column such as Ball, Natija or Score.",
        "Ball ustuni topilmadi, ustunlar savol-savol belgilarga ham o'xshamaydi. "
        "«Ball», «Natija» yoki «Score» kabi ustun qo'shing.",
        "Столбец с баллом не найден, и столбцы не похожи на отметки по вопросам. "
        "Добавьте столбец «Балл», «Результат» или «Score».",
    ),
    "bh_imp_mode_ball": ("one score column", "bitta ball ustuni", "один столбец с баллом"),
    "bh_imp_mode_matritsa": (
        "one column per question, summed",
        "har savolga bitta ustun, yig'indi olindi",
        "по столбцу на вопрос, суммировано",
    ),
    "bh_imp_result": (
        "{$a->topildi} students matched, {$a->xato} rows could not be used "
        "({$a->rejim})",
        "{$a->topildi} o'quvchi topildi, {$a->xato} qator ishlatilmadi "
        "({$a->rejim})",
        "Сопоставлено учеников: {$a->topildi}, не использовано строк: "
        "{$a->xato} ({$a->rejim})",
    ),
    "bh_imp_qsame": (
        "Questions in the file: {$a} — matches the maximum score",
        "Fayldagi savollar soni: {$a} — maksimal ball bilan bir xil",
        "Вопросов в файле: {$a} — совпадает с максимальным баллом",
    ),
    "bh_imp_setmax": (
        "Set the maximum score to {$a->savollar} (now {$a->hozir})",
        "Maksimal ballni {$a->savollar} ga tenglashtirish (hozir {$a->hozir})",
        "Установить максимальный балл {$a->savollar} (сейчас {$a->hozir})",
    ),
    "bh_imp_setmaxhint": (
        "The maximum score belongs to the assessment, so it applies to every "
        "group and changes the percentages already stored",
        "Maksimal ball BAHOLASHGA tegishli, ya'ni hamma guruhga qo'llanadi va "
        "saqlangan ballarning foizini o'zgartiradi",
        "Максимальный балл относится к оцениванию, то есть применяется ко всем "
        "группам и меняет проценты уже сохранённых баллов",
    ),
    "bh_maxclosed": (
        "The assessment is closed, so the maximum score cannot be changed",
        "Baholash yopilgan, maksimal ballni o'zgartirib bo'lmaydi",
        "Оценивание закрыто, максимальный балл изменить нельзя",
    ),
    "bh_maxtoolow": (
        "The maximum score cannot be set to {$a->yangi}: a stored score of "
        "{$a->eng} is higher and would be silently cut. Correct that score first.",
        "Maksimal ballni {$a->yangi} qilib bo'lmaydi: saqlangan {$a->eng} ball "
        "undan katta va jimgina kesilib ketardi. Avval o'sha ballni tuzating.",
        "Нельзя установить максимум {$a->yangi}: сохранённый балл {$a->eng} "
        "больше и был бы молча урезан. Сначала исправьте этот балл.",
    ),
    "bh_imp_empty": (
        "{$a} rows had no score in the file — those students are left untouched",
        "{$a} qatorda fayldagi ball bo'sh — o'sha o'quvchilarga hech narsa yozilmaydi",
        "В {$a} строках балл в файле пуст — этим ученикам ничего не записывается",
    ),
    "bh_imp_h_bosh": ("no score in the file", "faylda ball yo'q", "в файле нет балла"),
    "bh_imp_source": (
        "Taken from sheet «{$a->varaq}», subject «{$a->fan}»",
        "«{$a->varaq}» varag'idan, «{$a->fan}» fani bo'yicha olindi",
        "Взято с листа «{$a->varaq}», предмет «{$a->fan}»",
    ),
    "bh_imp_pick": (
        "The file holds several subjects and none matched «{$a->fan}» "
        "automatically (sheet «{$a->varaq}»). Choose the subject yourself.",
        "Faylda bir nechta fan bor va «{$a->fan}» ga avtomatik moslashtirib "
        "bo'lmadi («{$a->varaq}» varag'i). Fanni o'zingiz tanlang.",
        "В файле несколько предметов, и ни один не сопоставился с «{$a->fan}» "
        "автоматически (лист «{$a->varaq}»). Выберите предмет сами.",
    ),
    "bh_imp_pickhint": (
        "The number in brackets is the question count in that block. The file "
        "is kept, so there is no need to upload it again.",
        "Qavs ichidagi son — o'sha blokdagi savollar soni. Fayl saqlanib "
        "turadi, qayta yuklash kerak emas.",
        "Число в скобках — количество вопросов в этом блоке. Файл сохранён, "
        "загружать заново не нужно.",
    ),
    "bh_imp_row": ("Row", "Qator", "Строка"),
    "bh_imp_file": ("In the file", "Faylda", "В файле"),
    "bh_imp_matched": ("Matched student", "Topilgan o'quvchi", "Найденный ученик"),
    "bh_imp_how": ("How", "Qanday", "Как"),
    "bh_imp_h_id": ("by student ID", "ID bo'yicha", "по ID"),
    "bh_imp_h_ism": ("by full name", "ism bo'yicha", "по ФИО"),
    "bh_imp_h_qisman": (
        "by partial name — check it",
        "ism qisman mos — tekshiring",
        "частичное совпадение ФИО — проверьте",
    ),
    "bh_imp_h_topilmadi": ("not found", "topilmadi", "не найден"),
    "bh_imp_h_ikkixil": (
        "matches two students",
        "ikki o'quvchiga mos keldi",
        "подходит двум ученикам",
    ),
    "bh_imp_h_takror": (
        "this student is already in the file",
        "bu o'quvchi faylda takrorlangan",
        "этот ученик в файле повторяется",
    ),
    "bh_imp_h_ballyoq": ("no numeric score", "ball son emas", "балл не число"),
    "bh_imp_confirm": (
        "Check the grid below and press Save. Rows that were not matched are "
        "left untouched.",
        "Pastdagi setkani tekshirib «Saqlash» bosing. Topilmagan qatorlarga "
        "hech narsa yozilmaydi.",
        "Проверьте таблицу ниже и нажмите «Сохранить». По несопоставленным "
        "строкам ничего не записывается.",
    ),

    "bh_student": ("Student", "O'quvchi", "Ученик"),
    "bh_students": ("Students", "O'quvchilar", "Ученики"),
    "bh_score": ("Score", "Ball", "Балл"),
    "bh_state": ("Status", "Holat", "Статус"),
    "bh_retake": ("Retake", "Qayta topshirish", "Повторная сдача"),
    "bh_counted": ("Counted score", "Hisoblangan ball", "Зачтённый балл"),
    "bh_termscore": ("Quarter score", "Chorak bali", "Балл за четверть"),
    "bh_entered": ("Entered", "Kiritilgan", "Введено"),
    "bh_ofstudents": ("of {$a} students", "{$a} o'quvchidan", "из {$a} учеников"),
    "bh_missing": ("Still missing", "Qolgan", "Осталось"),
    "bh_missinghint": (
        "No score entered for these students yet",
        "Bu o'quvchilarga hali ball qo'yilmagan",
        "Этим ученикам балл ещё не выставлен",
    ),
    "bh_scorehint": (
        "Score from 0 to {$a}. A decimal comma is accepted too: 22,3 = 22.3",
        "0 dan {$a} gacha ball. Vergul ham qabul qilinadi: 22,3 = 22.3",
        "Балл от 0 до {$a}. Запятая тоже принимается: 22,3 = 22.3",
    ),
    "bh_scoresbad": (
        "{$a->soni} student(s) were NOT saved - the score could not be read: "
        "{$a->ismlar}. A score must be a number (for example 22 or 22,3). "
        "Saved: {$a->saqlandi}.",
        "{$a->soni} ta o'quvchining bali O'QILMADI va SAQLANMADI: "
        "{$a->ismlar}. Ball raqam bo'lishi kerak (masalan 22 yoki 22,3). "
        "Saqlangani: {$a->saqlandi} ta.",
        "У {$a->soni} учеников балл НЕ прочитан и НЕ сохранён: "
        "{$a->ismlar}. Балл должен быть числом (например 22 или 22,3). "
        "Сохранено: {$a->saqlandi}.",
    ),
    "bh_scoresnone": (
        "Nothing was saved - no value changed.",
        "Hech narsa saqlanmadi - birorta qiymat o'zgarmadi.",
        "Ничего не сохранено - ни одно значение не изменилось.",
    ),
    "bh_nostudents": (
        "No students are enrolled in this group, so scores cannot be entered",
        "Bu guruhga o'quvchi yozilmagan — ball kiritib bo'lmaydi",
        "В эту группу не записаны ученики — балл внести нельзя",
    ),
    "bh_nostudents_hint": (
        "Course membership comes from the Student Group in Frappe. Add the "
        "students to the group \"{$a}\" there and run the course sync - the "
        "list will appear here.",
        "Kursdagi a'zolik Frappe'dagi Student Group'dan keladi. O'quvchilarni "
        "o'sha yerda \"{$a}\" guruhiga qo'shing va kurs sinxronini ishga "
        "tushiring - ro'yxat shu yerda paydo bo'ladi.",
        "Состав курса берётся из Student Group во Frappe. Добавьте учеников в "
        "группу \"{$a}\" там и запустите синхронизацию курсов - список "
        "появится здесь.",
    ),
    "bh_nostudents_block": (
        "\"{$a}\" is a level group. Such groups usually have no lessons of their "
        "own in the timetable, so their membership is filled in Frappe by hand "
        "(Student Group -> members) or with the group assignment tool. After "
        "that run the course sync and the list will appear here.",
        "\"{$a}\" - daraja guruhi. Bunday guruhlarning jadvalda o'z darsi "
        "bo'lmaydi, shuning uchun a'zoligi Frappe'da qo'lda (Student Group -> "
        "a'zolar) yoki guruhga biriktirish vositasi bilan to'ldiriladi. "
        "Keyin kurs sinxronini ishga tushiring - ro'yxat shu yerda chiqadi.",
        "\"{$a}\" - уровневая группа. У таких групп обычно нет собственных "
        "уроков в расписании, поэтому состав заполняется во Frappe вручную "
        "(Student Group -> участники) или инструментом привязки. Затем "
        "запустите синхронизацию курсов - список появится здесь.",
    ),
    "bh_nocourse": (
        "The course of this sitting was not found",
        "Bu o'tkazilishning kursi topilmadi",
        "Курс этого проведения не найден",
    ),
    "bh_enterscores": ("Enter scores", "Ball kiritish", "Ввести баллы"),
    "bh_savescores": ("Save the scores", "Ballarni saqlash", "Сохранить баллы"),
    "bh_savehint": (
        "Only the rows you changed are saved",
        "Faqat o'zgartirilgan qatorlar saqlanadi",
        "Сохраняются только изменённые строки",
    ),
    "bh_scoressaved": (
        "{$a->saqlandi} scores saved, {$a->chorak} quarter results recalculated",
        "{$a->saqlandi} ball saqlandi, {$a->chorak} chorak natijasi qayta hisoblandi",
        "Сохранено баллов: {$a->saqlandi}, пересчитано итогов четверти: {$a->chorak}",
    ),
    "bh_timetableback": ("Back to the timetable", "Jadvalga qaytish", "К расписанию"),
    "bh_cappedhint": (
        "Under the regulation the score after a retake may not exceed {$a}",
        "Nizom bo'yicha qayta topshirishdagi ball {$a} dan oshmaydi",
        "Согласно положению балл при повторной сдаче не превышает {$a}",
    ),
    "bh_late": (
        "Corrected after the window",
        "Oynadan keyin tuzatilgan",
        "Исправлено после окна",
    ),
    "bh_changes": (
        "{$a} changes",
        "{$a} marta o'zgargan",
        "Изменений: {$a}",
    ),
    "bh_reason": ("Reason for the correction", "Tuzatish sababi", "Причина исправления"),
    "bh_reasonph": (
        "For example: the paper was re-marked by the working group",
        "Masalan: ish ishchi guruh tomonidan qayta tekshirildi",
        "Например: работа перепроверена рабочей группой",
    ),
    "bh_reasonneeded": (
        "REQUIRED: more than {$a} hours have passed since some scores were "
        "entered, so the correction must be explained",
        "SHART: ba'zi ballar kiritilganiga {$a} soatdan ko'p vaqt o'tgan, "
        "shuning uchun tuzatish izohlanishi kerak",
        "ОБЯЗАТЕЛЬНО: с момента ввода некоторых баллов прошло более {$a} "
        "часов, поэтому исправление нужно пояснить",
    ),
    "bh_reasonopt": (
        "Optional. It becomes required once {$a} hours have passed since the "
        "score was entered.",
        "Ixtiyoriy. Ball kiritilganiga {$a} soat o'tgach majburiy bo'ladi.",
        "Необязательно. Становится обязательным через {$a} часов после ввода.",
    ),
    "bh_onlinewarn": (
        "This is an online test: scores are collected from Moodle "
        "automatically. A score entered by hand may be overwritten by the "
        "next collection run.",
        "Bu onlayn test: ballar Moodle'dan avtomatik yig'iladi. Qo'lda "
        "kiritilgan ball keyingi yig'ishda ustiga yozilishi mumkin.",
        "Это онлайн-тест: баллы собираются из Moodle автоматически. "
        "Введённый вручную балл может быть перезаписан при сборе.",
    ),
    "bh_part_fb": ("formative", "formativ", "формативное"),
    "bh_part_bsb": ("BSB and projects", "BSB va loyihalar", "BSB и проекты"),
    "bh_part_chsb": ("CHSB", "CHSB", "CHSB"),

    # ---------------------------- sharhning 5 kategoriyasi (2026-11-10)
    "sh_kat_akademik": (
        "Academic Performance",
        "Akademik bilim",
        "Академические знания",
    ),
    "sh_kat_faollik": (
        "Classroom Participation",
        "Darsdagi faollik",
        "Активность на уроке",
    ),
    "sh_kat_ijtimoiy": (
        "Social and Emotional Skills",
        "Ijtimoiy va emotsional ko'nikmalar",
        "Социальные и эмоциональные навыки",
    ),
    "sh_kat_masuliyat": (
        "Responsibility and Independence",
        "Mas'uliyat va mustaqillik",
        "Ответственность и самостоятельность",
    ),
    "sh_kat_tanqidiy": (
        "Critical Thinking and Creativity",
        "Tanqidiy fikrlash va ijodkorlik",
        "Критическое мышление и креативность",
    ),
    "sh_kat_umumiy": (
        "General (earlier review)",
        "Umumiy (oldingi sharh)",
        "Общий (ранний отзыв)",
    ),

    # Ko'rsatmalar — o'qituvchi NIMA yozishini bilishi uchun. Qisqa, chunki
    # beshta maydon ustida uzun matn o'qilmaydi.
    "sh_hint_akademik": (
        "Subject knowledge, grasp of the material, progress",
        "Fan bilimi, mavzuni o'zlashtirishi, o'sishi",
        "Знание предмета, усвоение материала, прогресс",
    ),
    "sh_hint_faollik": (
        "Taking part in the lesson, asking and answering, group work",
        "Darsda qatnashishi, savol-javob, guruhda ishlashi",
        "Участие в уроке, вопросы и ответы, работа в группе",
    ),
    "sh_hint_ijtimoiy": (
        "Relations with classmates, self-control, empathy",
        "Sinfdoshlari bilan munosabati, o'zini tutishi, hamdardligi",
        "Отношения с одноклассниками, самоконтроль, эмпатия",
    ),
    "sh_hint_masuliyat": (
        "Homework, keeping to deadlines, working without being told",
        "Uy vazifasi, muddatga yetishi, aytmasdan ishlashi",
        "Домашние задания, соблюдение сроков, работа без напоминаний",
    ),
    "sh_hint_tanqidiy": (
        "Asking why, finding their own solutions, new ideas",
        "Sabab so'rashi, o'z yechimini topishi, yangi g'oyalari",
        "Задаёт вопросы «почему», находит свои решения, новые идеи",
    ),

    # Namuna matnlar (placeholder) — bo'sh maydon qo'rqitadi, namuna esa
    # ohangni ham ko'rsatadi: ayblov emas, kuzatish.
    "sh_ph_akademik": (
        "For example: has a solid grasp of fractions, needs more practice with word problems",
        "Masalan: kasrlarni yaxshi o'zlashtirgan, masalalar yechishda mashq kerak",
        "Например: хорошо усвоил дроби, нужна практика в текстовых задачах",
    ),
    "sh_ph_faollik": (
        "For example: answers readily, but rarely asks questions of their own",
        "Masalan: javob berishga shoshiladi, lekin o'zi savol kam beradi",
        "Например: охотно отвечает, но редко задаёт свои вопросы",
    ),
    "sh_ph_ijtimoiy": (
        "For example: helps classmates, finds it hard to wait their turn",
        "Masalan: sinfdoshlariga yordam beradi, navbat kutishi qiyin",
        "Например: помогает одноклассникам, трудно даётся ждать очереди",
    ),
    "sh_ph_masuliyat": (
        "For example: brings homework on time, needs reminding about materials",
        "Masalan: uy vazifasini vaqtida olib keladi, jihozlarni eslatish kerak",
        "Например: вовремя приносит домашнее задание, про материалы нужно напоминать",
    ),
    "sh_ph_tanqidiy": (
        "For example: looks for a second way to solve it, enjoys open questions",
        "Masalan: ikkinchi yechim yo'lini izlaydi, ochiq savollarni yaxshi ko'radi",
        "Например: ищет второй способ решения, любит открытые вопросы",
    ),

    "sh_allhint": (
        "All five categories must be filled in — the review is a complete "
        "picture of the student, not a single remark",
        "Beshta kategoriya ham to'ldirilishi shart — sharh o'quvchining "
        "to'liq manzarasi, bir og'iz izoh emas",
        "Все пять категорий обязательны — отзыв это полная картина ученика, "
        "а не одно замечание",
    ),
    "sh_allrequired": (
        "These categories are still empty: {$a}. All five must be filled in.",
        "Bu kategoriyalar bo'sh qoldi: {$a}. Beshtasi ham to'ldirilishi shart.",
        "Эти категории не заполнены: {$a}. Все пять обязательны.",
    ),
    "sh_archived": ("not in the timetable", "jadvalda yo'q", "нет в расписании"),
    "sh_archivednotice": (
        "This group is not in your current timetable, but because you have "
        "written reviews for it you can still read and write here.",
        "Bu guruh sizning joriy jadvalingizda yo'q, lekin unga sharh "
        "yozganingiz uchun bu yerda o'qish ham, yozish ham ochiq.",
        "Этой группы нет в вашем текущем расписании, но так как вы писали "
        "для неё отзывы, чтение и запись здесь по-прежнему доступны.",
    ),
    "sh_nocourseaccess": (
        "You neither teach this group nor have written reviews for it",
        "Siz bu guruhga dars bermaysiz va unga sharh ham yozmagansiz",
        "Вы не преподаёте в этой группе и не писали для неё отзывов",
    ),
    "sh_edit": ("Edit", "Tahrirlash", "Редактировать"),
    "sh_cancel": ("Cancel editing", "Tahrirni bekor qilish", "Отменить редактирование"),
    "sh_savechanges": ("Save the changes", "O'zgarishni saqlash", "Сохранить изменения"),
    "sh_edithint": (
        "You are editing a review you wrote. All five categories stay "
        "required; the text is replaced, not added alongside.",
        "O'zingiz yozgan sharhni tahrirlayapsiz. Beshta kategoriya baribir "
        "majburiy; matn yangisi bilan ALMASHADI, yoniga qo'shilmaydi.",
        "Вы редактируете свой отзыв. Все пять категорий по-прежнему "
        "обязательны; текст ЗАМЕНЯЕТСЯ, а не добавляется рядом.",
    ),
    "sh_edited": ("edited {$a}", "tahrirlangan {$a}", "изменено {$a}"),
    "sh_noedit": (
        "Only the teacher who wrote a review can edit it",
        "Sharhni faqat uni yozgan o'qituvchi tahrirlaydi",
        "Отзыв может редактировать только написавший его учитель",
    ),
    "sh_nobatch": (
        "That review was not found",
        "O'sha sharh topilmadi",
        "Этот отзыв не найден",
    ),
    "sh_delete": ("Delete the review", "Sharhni o'chirish", "Удалить отзыв"),
    "sh_delete_q": (
        "Delete this review? All five categories go together and this cannot "
        "be undone.",
        "Shu sharh o'chirilsinmi? Beshta kategoriya birga ketadi va qaytarib "
        "bo'lmaydi.",
        "Удалить этот отзыв? Все пять категорий удаляются вместе, отменить "
        "нельзя.",
    ),
    "sh_deleted": (
        "Deleted ({$a} entries)",
        "O'chirildi ({$a} yozuv)",
        "Удалено (записей: {$a})",
    ),
    "sh_write": ("Write a review", "Sharh yozish", "Написать отзыв"),
    "sh_katcount": (
        "{$a} categories",
        "{$a} kategoriya",
        "Категорий: {$a}",
    ),

    "app_murojaat": ("Parent issues", "Murojaatlar", "Обращения"),
    "app_murojaatim": ("My issues", "Murojaatlarim", "Мои обращения"),

    # ---------------------------------------------------- topshiriqlar (mod_assign)
    # DIQQAT: bu MOODLE faoliyatlari (assign/quiz), bizning summativ
    # baholash EMAS. Nomlar o'xshash bo'lgani uchun foydalanuvchi o'zining
    # CHSB imtihonlarini shu sahifada izlab adashdi — shuning uchun nomda
    # «Moodle» so'zi turadi.
    "tp_nav": ("Moodle activities", "Moodle faoliyatlari", "Элементы Moodle"),
    "tp_hint": (
        "Moodle assignments and quizzes across your groups: who submitted, who "
        "is graded. Your own CHSB/BSB exams are under All assessments.",
        "Guruhlardagi Moodle topshiriq va testlari: kim topshirdi, kim "
        "baholandi. O'zingiz yaratgan CHSB/BSB imtihonlari «Baholashlar» "
        "bo'limida.",
        "Задания и тесты Moodle по вашим группам: кто сдал, кто оценён. "
        "Ваши экзамены CHSB/BSB — в разделе «Все оценивания».",
    ),
    "tp_empty": (
        "No assignments yet. They appear here as soon as a teacher creates one "
        "in a course, or writes homework in the lesson cockpit.",
        "Hozircha topshiriq yo'q. O'qituvchi kursda topshiriq yaratsa yoki dars "
        "kokpitida uy vazifa yozsa, shu yerda paydo bo'ladi.",
        "Заданий пока нет. Они появятся здесь, как только преподаватель создаст "
        "задание в курсе или запишет домашнее задание в кокпите урока.",
    ),
    "tp_total": ("Assignments", "Topshiriq", "Заданий"),
    "tp_submitted": ("Submitted", "Topshirgan", "Сдали"),
    "tp_graded": ("Graded", "Baholangan", "Оценено"),
    "tp_draft": ("In draft", "Qoralamada", "В черновике"),
    "tp_drafthint": (
        "The student uploaded work but did not press \"Submit assignment\". "
        "To remove this step: Settings -> Submission settings -> "
        "\"Require students to click submit button\" = No.",
        "O'quvchi ishini yuklagan, lekin \"Submit assignment\" tugmasini "
        "bosmagan. Bu qadamni olib tashlash: Settings -> Submission settings -> "
        "\"Require students to click submit button\" = No.",
        "Ученик загрузил работу, но не нажал \"Submit assignment\". Убрать этот "
        "шаг: Settings -> Submission settings -> "
        "\"Require students to click submit button\" = No.",
    ),
    "tp_diag": (
        "Moodle currently holds {$a->jami} assignment(s) and {$a->quiz} quiz(zes), "
        "across {$a->kurslar} course(s) with assignments. If you still see nothing, "
        "the activity may be in a course you cannot access.",
        "Moodle'da hozir {$a->jami} ta topshiriq (Assignment) va {$a->quiz} ta test "
        "(Quiz) bor; topshiriqli kurslar soni {$a->kurslar}. Agar hamon hech nima "
        "ko'rinmasa, faoliyat siz kira olmaydigan kursda bo'lishi mumkin.",
        "В Moodle сейчас {$a->jami} заданий (Assignment) и {$a->quiz} тестов (Quiz); "
        "курсов с заданиями: {$a->kurslar}. Если всё ещё ничего не видно, элемент "
        "может быть в курсе, к которому у вас нет доступа.",
    ),

    "tp_visible": ("Student sees grade", "O'quvchi ko'radi", "Ученик видит оценку"),
    "tp_notvisible": ("Grades not visible", "Baho ko'rinmaydi", "Оценки не видны"),
    "tp_notvisiblehint": (
        "Assignments where a grade exists but the student cannot see it. "
        "The reason is shown in the \"Student sees grade\" column.",
        "Baho qo'yilgan, lekin o'quvchi ko'ra olmaydigan ishlar. "
        "Sababi \"O'quvchi ko'radi\" ustunida yozilgan.",
        "Задания, где оценка выставлена, но ученик её не видит. "
        "Причина указана в столбце \"Ученик видит оценку\".",
    ),
    "tp_vis_ok_s": ("yes", "ha", "да"),
    "tp_vis_ok": (
        "The grade is visible to the student in Grades.",
        "Baho o'quvchiga «Baholar» sahifasida ko'rinadi.",
        "Оценка видна ученику в разделе «Оценки».",
    ),
    "tp_vis_nomarks_s": ("no grades", "baho yo'q", "нет оценок"),
    "tp_vis_nomarks": (
        "Nothing is graded yet, so there is nothing for the student to see.",
        "Hali hech kim baholanmagan, ya'ni o'quvchi ko'radigan narsa yo'q.",
        "Пока никто не оценён, ученику нечего видеть.",
    ),
    "tp_vis_nograde_s": ("not graded", "baholanmaydi", "без оценки"),
    "tp_vis_nograde": (
        "This activity has no grade type set (Grade = None), so it never "
        "produces a grade. Settings -> Grade.",
        "Bu faoliyatda baho turi qo'yilmagan (Grade = None), ya'ni u umuman "
        "baho bermaydi. Settings -> Grade.",
        "У этого элемента не задан тип оценки (Grade = None), оценка не "
        "выставляется вообще. Settings -> Grade.",
    ),
    "tp_vis_notreleased_s": ("not released", "e'lon qilinmagan", "не опубликовано"),
    "tp_vis_notreleased": (
        "Marking workflow is ON: a grade stays hidden until you set its state "
        "to \"Released\". Open Grade, set Marking workflow state = Released "
        "(or select all students in Submissions -> With selected... -> Set "
        "marking workflow state). The number shows released/graded.",
        "Marking workflow YOQILGAN: baho holati \"Released\" bo'lmaguncha "
        "o'quvchi uni ko'rmaydi. Baholash ekranida Marking workflow state = "
        "Released qo'ying (yoki Submissions -> hammasini belgilab -> With "
        "selected... -> Set marking workflow state). Raqam: e'lon qilingan/baholangan.",
        "Marking workflow ВКЛЮЧЁН: оценка скрыта, пока её состояние не станет "
        "\"Released\". Поставьте Marking workflow state = Released (или "
        "Submissions -> выбрать всех -> With selected... -> Set marking workflow "
        "state). Число: опубликовано/оценено.",
    ),
    "tp_vis_itemhidden_s": ("hidden in gradebook", "gradebook'da yashirin", "скрыто в журнале"),
    "tp_vis_itemhidden": (
        "This column is hidden in the gradebook, so the student does not see the "
        "grade. Course -> Grades -> Gradebook setup -> the item menu -> Show.",
        "Bu ustun gradebook'da yashirilgan, shuning uchun o'quvchi bahoni "
        "ko'rmaydi. Kurs -> Grades -> Gradebook setup -> ustun menyusi -> Show.",
        "Этот столбец скрыт в журнале оценок, поэтому ученик не видит оценку. "
        "Курс -> Grades -> Gradebook setup -> меню элемента -> Show.",
    ),
    "tp_vis_nocourse_s": ("grades off", "kursda o'chirilgan", "выключено в курсе"),
    "tp_vis_nocourse": (
        "Grades are switched off for the whole course, so no grade reaches any "
        "student. Course settings -> Appearance -> Show gradebook to students = Yes.",
        "Butun kursda baholar o'chirilgan, ya'ni hech bir baho o'quvchiga "
        "bormaydi. Kurs sozlamalari -> Appearance -> Show gradebook to students = Yes.",
        "Оценки отключены для всего курса, ни одна оценка не доходит до ученика. "
        "Настройки курса -> Appearance -> Show gradebook to students = Yes.",
    ),

    "set_bhheading": ("Assessment (CHSB/BSB)", "Baholash (CHSB/BSB)",
        "Оценивание (CHSB/BSB)"),
    "set_oynasoat": ("Grade editing window (hours)",
        "Baho tahrirlash oynasi (soat)", "Окно редактирования оценки (часы)"),
    "tp_newlabel": ("New activity:", "Yangi topshiriq:", "Новый элемент:"),
    "tp_newbtn": ("Create", "Yaratish", "Создать"),
    "tp_kind": ("Type", "Turi", "Тип"),
    "tp_kind_assign": ("Assignment", "Topshiriq", "Задание"),
    "tp_kind_quiz": ("Quiz", "Test", "Тест"),
    "tp_open": ("Open", "Ochish", "Открыть"),
    "tp_results": ("Results", "Natijalar", "Результаты"),
    "tp_delete": ("Delete", "O'chirish", "Удалить"),
    "tp_deletehint": (
        "Opens Moodle's confirmation page - nothing is deleted until you confirm.",
        "Moodle'ning tasdiqlash sahifasini ochadi - tasdiqlamaguningizcha "
        "hech nima o'chmaydi.",
        "Откроет страницу подтверждения Moodle - ничего не удалится, пока вы "
        "не подтвердите.",
    ),

    "tp_group": ("Group", "Guruh", "Группа"),
    "tp_name": ("Assignment", "Topshiriq", "Задание"),
    "tp_due": ("Due", "Muddat", "Срок"),
    "tp_nodue": ("no deadline", "muddat yo'q", "без срока"),
    "tp_progress": ("Status", "Holat", "Статус"),
    "tp_actions": ("Actions", "Amallar", "Действия"),
    "tp_subshort": ("subm", "topsh", "сдали"),
    "tp_draftshort": ("draft", "qoralama", "черновик"),
    "tp_gradeshort": ("graded", "baho", "оценено"),
    "tp_grade": ("Grade", "Baholash", "Оценить"),
    "tp_settings": ("Settings", "Sozlamalar", "Настройки"),
    "tp_hidden": ("hidden", "yashirin", "скрыто"),
    "tp_hiddenhint": (
        "Hidden from students - they do not see this assignment at all. "
        "Course page -> the activity menu -> Availability -> Show on course page.",
        "O'quvchidan yashirilgan - ular bu topshiriqni umuman ko'rmaydi. "
        "Kurs sahifasi -> faoliyat menyusi -> Availability -> Show on course page.",
        "Скрыто от учеников - они вообще не видят это задание. "
        "Страница курса -> меню элемента -> Availability -> Show on course page.",
    ),
    "tp_hw": ("homework", "uy vazifa", "домашнее задание"),
    "tp_hwhint": (
        "Created from the lesson cockpit homework field. Changing the due date "
        "in Moodle will NOT stick - saving the lesson again overwrites it. "
        "Change the lesson date or the homework in the cockpit instead.",
        "Dars kokpitidagi uy vazifa maydonidan yaratilgan. Muddatni Moodle'da "
        "o'zgartirish SAQLANMAYDI - darsni qayta saqlash uni qaytadan yozadi. "
        "Buning o'rniga dars sanasini yoki kokpitdagi uy vazifani o'zgartiring.",
        "Создано из поля домашнего задания в кокпите урока. Изменение срока в "
        "Moodle НЕ сохранится - повторное сохранение урока перезапишет его. "
        "Вместо этого измените дату урока или домашнее задание в кокпите.",
    ),

    # ------------------------------------------- Ota-ona hisoboti (hisobot.php)
    "hr_nav": ("Student report", "O'quvchi hisoboti", "Отчёт об ученике"),
    "hr_title": (
        "Student progress report",
        "O'quvchining rivojlanish hisoboti",
        "Отчёт о развитии ученика",
    ),
    "hr_subtitle": (
        "For the parent meeting - test results by subject and the teachers' reviews",
        "Ota-onalar majlisi uchun - fanlar bo'yicha test natijalari va "
        "o'qituvchilar sharhi",
        "Для родительского собрания - результаты тестов по предметам и "
        "отзывы учителей",
    ),
    "hr_year": ("Academic year", "O'quv yili", "Учебный год"),
    "hr_printed": ("Printed", "Chop etildi", "Напечатано"),
    "hr_group": ("Class", "Sinf", "Класс"),
    "hr_tutor": ("Class tutor", "Sinf rahbari", "Классный руководитель"),
    "hr_studentid": ("Student ID", "O'quvchi ID", "ID ученика"),
    "hr_period": ("Period", "Davr", "Период"),
    "hr_notest": ("no test yet", "test topshirilmagan", "тест не сдавался"),
    "hr_notaken": (
        "Did not sit",
        "Qatnashmagan",
        "Не писал(а)",
    ),
    "hr_noscore": (
        "Score not entered",
        "Ball kiritilmagan",
        "Балл не внесён",
    ),
    "hr_notests": (
        "No test result has been entered for this student yet.",
        "Bu o'quvchi uchun hali test natijasi kiritilmagan.",
        "Для этого ученика ещё не внесены результаты тестов.",
    ),

    # Daraja - maktabning O'Z shkalasi (test natijalari jadvalidagi Low/Middle/High)
    "hr_lvl_yuqori": ("High", "Yuqori", "Высокий"),
    "hr_lvl_orta": ("Middle", "O'rta", "Средний"),
    "hr_lvl_past": ("Needs support", "Past", "Низкий"),

    "hr_kpi_result": ("Overall result", "Umumiy natija", "Общий результат"),
    "hr_kpi_best": ("Strongest subject", "Eng kuchli fan", "Самый сильный предмет"),
    "hr_kpi_weak": ("Needs attention", "E'tibor kerak fan", "Требует внимания"),
    "hr_kpi_vsclass": ("Compared to the class", "Sinfga nisbatan", "По сравнению с классом"),
    "hr_kpi_vsclass_hint": (
        "class average {$a}",
        "sinf o'rtachasi {$a}",
        "средний балл класса {$a}",
    ),
    "hr_kpi_tests": ("Tests taken", "Topshirilgan test", "Сдано тестов"),
    "hr_kpi_tests_hint": (
        "in {$a} subjects",
        "{$a} fan bo'yicha",
        "по {$a} предметам",
    ),

    "hr_sum_title": ("In short", "Qisqacha", "Кратко"),
    "hr_sum_none": (
        "No test result has been entered for this period yet, so the subject "
        "chart is empty. The teachers' reviews below still describe the student.",
        "Bu davr uchun test natijasi hali kiritilmagan, shuning uchun fanlar "
        "diagrammasi bo'sh. Quyidagi o'qituvchilar sharhi o'quvchi haqida baribir "
        "to'liq gapiradi.",
        "За этот период результаты тестов ещё не внесены, поэтому диаграмма по "
        "предметам пуста. Отзывы учителей ниже всё равно рассказывают о ученике.",
    ),
    "hr_sum_s1": (
        "{$a->ism} took {$a->testlar} test(s) in {$a->fan} subject(s); the overall "
        "result is {$a->foiz} - {$a->daraja} level.",
        "{$a->ism} {$a->fan} fandan {$a->testlar} ta test topshirdi, umumiy natija "
        "{$a->foiz} - {$a->daraja} daraja.",
        "{$a->ism} сдал(а) {$a->testlar} тест(ов) по {$a->fan} предметам, общий "
        "результат {$a->foiz} - {$a->daraja} уровень.",
    ),
    "hr_sum_s2": (
        "The strongest subject is {$a->kuchli} ({$a->kfoiz}), the lowest is "
        "{$a->sust} ({$a->sfoiz}).",
        "Eng yuqori natija - {$a->kuchli} ({$a->kfoiz}), eng past natija - "
        "{$a->sust} ({$a->sfoiz}).",
        "Самый высокий результат - {$a->kuchli} ({$a->kfoiz}), самый низкий - "
        "{$a->sust} ({$a->sfoiz}).",
    ),
    "hr_sum_s3": (
        "In {$a->n} subject(s) out of {$a->fan} the result is above the class "
        "average ({$a->sinf}).",
        "{$a->fan} fandan {$a->n} tasida natija sinf o'rtachasidan ({$a->sinf}) "
        "yuqori.",
        "В {$a->n} предметах из {$a->fan} результат выше среднего по классу "
        "({$a->sinf}).",
    ),
    "hr_sum_s4": (
        "{$a} subject teacher(s) have written a review about the student.",
        "{$a} fan o'qituvchisi o'quvchi haqida fikr bildirgan.",
        "{$a} учитель(-я) написали отзыв о ученике.",
    ),

    "hr_strong": ("Strengths", "Kuchli tomonlar", "Сильные стороны"),
    "hr_strong_hint": (
        "Subjects the student is doing well in - worth praising.",
        "O'quvchi yaxshi o'zlashtirgan fanlar - maqtashga arziydi.",
        "Предметы, которые ученик усвоил хорошо - стоит похвалить.",
    ),
    "hr_weak": ("Needs support", "Qo'llab-quvvatlash kerak", "Нужна поддержка"),
    "hr_weak_hint": (
        "Subjects to work on together with the teacher at home.",
        "Uyda va o'qituvchi bilan birga ishlash kerak bo'lgan fanlar.",
        "Предметы, над которыми нужно поработать дома и с учителем.",
    ),
    "hr_allstrong": (
        "There is no subject below the middle level - the result is even.",
        "O'rta darajadan past fan yo'q - natija bir tekis.",
        "Нет предметов ниже среднего уровня - результат ровный.",
    ),

    "hr_chart_title": (
        "Test result by subject",
        "Fanlar bo'yicha test natijasi",
        "Результат теста по предметам",
    ),
    "hr_chart_hint": (
        "The bar is your child's result, the triangle mark is the class average "
        "in the same test. The order of subjects is the same on every report.",
        "Ustun - farzandingiz natijasi, uchburchak belgi - shu testdagi sinf "
        "o'rtachasi. Fanlar ketma-ketligi hamma hisobotda bir xil.",
        "Столбец - результат вашего ученика, треугольник - средний балл класса "
        "в том же тесте. Порядок предметов одинаков во всех отчётах.",
    ),
    "hr_chart_hint_nocls": (
        "The bar shows the percentage of correct answers. The order of subjects "
        "is the same on every report.",
        "Ustun to'g'ri javoblar foizini ko'rsatadi. Fanlar ketma-ketligi hamma "
        "hisobotda bir xil.",
        "Столбец показывает процент правильных ответов. Порядок предметов "
        "одинаков во всех отчётах.",
    ),
    "hr_radar_title": (
        "Balance across subjects",
        "Fanlar muvozanati",
        "Баланс по предметам",
    ),
    "hr_radar_hint": (
        "The wider and more even the shape, the more evenly the student performs.",
        "Shakl qanchalik keng va tekis bo'lsa, o'quvchi shunchalik bir tekis o'zlashtirgan.",
        "Чем шире и ровнее фигура, тем равномернее успевает ученик.",
    ),
    "hr_spread": (
        "Gap between the highest and the lowest subject",
        "Eng yuqori va eng past fan orasidagi farq",
        "Разрыв между самым высоким и самым низким предметом",
    ),
    "hr_spread_tekis": (
        "the student performs evenly across subjects",
        "o'quvchi fanlar bo'yicha bir tekis o'qiyapti",
        "ученик успевает ровно по всем предметам",
    ),
    "hr_spread_orta": (
        "there is a noticeable difference between subjects",
        "fanlar orasida sezilarli farq bor",
        "между предметами есть заметная разница",
    ),
    "hr_spread_katta": (
        "the difference is large - the weaker subjects need support",
        "farq katta - orqada qolgan fanlarga yordam kerak",
        "разница большая - отстающим предметам нужна поддержка",
    ),
    "hr_gotoreview": (
        "click to read the teacher's review",
        "o'qituvchi sharhini o'qish uchun bosing",
        "нажмите, чтобы прочитать отзыв учителя",
    ),
    "hr_classavg": ("Class average", "Sinf o'rtachasi", "Средний балл класса"),
    "hr_scale_hint": (
        "The result is the percentage of correct answers in the test. The levels "
        "are the school's own scale, the same one used in the results sheet.",
        "Natija - testdagi to'g'ri javoblar foizi. Darajalar maktabning o'z "
        "shkalasi bo'yicha, natijalar jadvalidagi bilan bir xil.",
        "Результат - это процент правильных ответов в тесте. Уровни - по "
        "собственной шкале школы, той же, что и в таблице результатов.",
    ),

    "hr_table_title": ("Test detail", "Testlar tafsiloti", "Детали тестов"),
    "hr_table_hint": (
        "Every test the student took in this period.",
        "O'quvchi shu davrda topshirgan har bir test.",
        "Каждый тест, который ученик сдал в этот период.",
    ),
    "hr_col_subject": ("Subject / teacher", "Fan / o'qituvchi", "Предмет / учитель"),
    "hr_col_exam": ("Test", "Test", "Тест"),
    "hr_col_score": ("Correct", "To'g'ri javob", "Правильных"),
    "hr_col_percent": ("Result", "Natija", "Результат"),
    "hr_col_level": ("Percent / level", "Foiz / daraja", "Процент / уровень"),
    "hr_col_class": ("Class", "Sinf", "Класс"),

    "hr_reviews_title": (
        "Teachers' view",
        "O'qituvchilar fikri",
        "Мнение учителей",
    ),
    "hr_reviews_hint": (
        "Each subject teacher writes on five areas. These reviews come from "
        "watching the student in lessons all term - they are NOT derived from the "
        "test score. The most recent review for each subject is shown.",
        "Har bir fan o'qituvchisi besh yo'nalish bo'yicha yozadi. Sharh test "
        "baliga qarab emas, o'qituvchining chorak davomida darsdagi kuzatuviga "
        "asoslangan. Har fandan eng oxirgi sharh ko'rsatilgan.",
        "Каждый учитель-предметник пишет по пяти направлениям. Отзыв основан на "
        "наблюдении за учеником на уроках в течение четверти, а НЕ на балле "
        "теста. Показан последний отзыв по каждому предмету.",
    ),
    "hr_teacher_of": (
        "teacher of {$a}",
        "{$a} fani o'qituvchisi",
        "учитель предмета {$a}",
    ),
    "hr_noreviews": (
        "No teacher review has been published for this student yet.",
        "Bu o'quvchi uchun hali o'qituvchi sharhi e'lon qilinmagan.",
        "Для этого ученика пока нет опубликованного отзыва учителя.",
    ),

    "hr_footnote": (
        "The result is the percentage of correct answers in the tests of this "
        "period; the level follows the school's scale (80% and above - High, "
        "50-79% - Middle, below 50% - Needs support). A low result in one test "
        "is not a verdict about the student: read it together with the teachers' "
        "reviews. If you have a question, please speak to the class tutor or "
        "write through the school feedback page.",
        "Natija - shu davrdagi testlarda to'g'ri javoblar foizi; daraja maktab "
        "shkalasi bo'yicha (80% va undan yuqori - Yuqori, 50-79% - O'rta, 50% dan "
        "past - Past). Bitta testdagi past natija o'quvchi haqidagi hukm emas: uni "
        "o'qituvchilar sharhi bilan birga o'qing. Savolingiz bo'lsa, sinf "
        "rahbariga murojaat qiling yoki maktabning murojaat sahifasi orqali yozing.",
        "Результат - это процент правильных ответов в тестах данного периода; "
        "уровень - по школьной шкале (80% и выше - Высокий, 50-79% - Средний, "
        "ниже 50% - Низкий). Низкий результат одного теста - не приговор ученику: "
        "читайте его вместе с отзывами учителей. При вопросах обратитесь к "
        "классному руководителю или напишите через страницу обращений школы.",
    ),
    "hr_sig_tutor": ("Class tutor", "Sinf rahbari", "Классный руководитель"),
    "hr_sig_parent": (
        "Parent (signature, date)",
        "Ota-ona (imzo, sana)",
        "Родитель (подпись, дата)",
    ),

    "hr_print": ("Print / save as PDF", "Chop etish / PDF", "Печать / PDF"),
    "hr_printgroup": (
        "Print the whole class ({$a})",
        "Butun sinfni chop etish ({$a})",
        "Напечатать весь класс ({$a})",
    ),
    "hr_printhint": (
        "In Chrome: Print -> Destination \"Save as PDF\" -> Paper A4. "
        "Turn on \"Background graphics\" so the colours of the charts come out.",
        "Chrome'da: Chop etish -> Manzil \"PDF sifatida saqlash\" -> Qog'oz A4. "
        "Diagramma ranglari chiqishi uchun \"Fon rasmlari\" (Background graphics) "
        "belgisini yoqing.",
        "В Chrome: Печать -> Принтер \"Сохранить как PDF\" -> Бумага A4. "
        "Включите \"Фоновую графику\", чтобы цвета диаграмм пропечатались.",
    ),
    "hr_pickgroup": ("Choose a class", "Sinfni tanlang", "Выберите класс"),
    "hr_pickhint": (
        "The reports of every student in the class open one after another, "
        "ready to print in one go.",
        "Sinfdagi har bir o'quvchining hisoboti ketma-ket ochiladi va bir "
        "marta bosib chop etiladi.",
        "Отчёты всех учеников класса откроются подряд - печатаются за один раз.",
    ),
    "hr_noaccess": (
        "You do not have access to this student's report.",
        "Bu o'quvchining hisobotiga ruxsatingiz yo'q.",
        "У вас нет доступа к отчёту этого ученика.",
    ),
    "hr_nostudents": (
        "No student found for this report.",
        "Hisobot uchun o'quvchi topilmadi.",
        "Ученик для отчёта не найден.",
    ),
    "hr_openreport": (
        "Report for the parent meeting",
        "Ota-onalar majlisi uchun hisobot",
        "Отчёт для родительского собрания",
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

    # ------------------------------------------- psixolog (PSIXOLOG_ARXITEKTURA.md)
    "targetzenit:psixolog": (
        "School psychologist: counselling notes, cases, tests",
        "Maktab psixologi: suhbat qaydlari, muammolar (case), testlar",
        "Школьный психолог: записи бесед, кейсы, тесты",
    ),
    "psix_panel": ("Psychologist panel", "Psixolog paneli", "Панель психолога"),
    "psix_nav": ("Psychology", "Psixologiya", "Психология"),
    "psix_noaccess": (
        "This page is for the school psychologist and administration only.",
        "Bu sahifa faqat maktab psixologi va rahbariyat uchun.",
        "Эта страница только для школьного психолога и руководства.",
    ),
    "psix_nav_people": ("Students & staff", "O'quvchilar va xodimlar", "Ученики и сотрудники"),
    "psix_nav_talks": ("Counselling notes", "Suhbat qaydlari", "Записи бесед"),
    "psix_nav_cases": ("Cases", "Muammolar (case)", "Кейсы"),
    "psix_nav_tests": ("Tests & surveys", "Test va so'rovnomalar", "Тесты и опросы"),
    "psix_nav_stat": ("Psychology statistics", "Psixologiya statistikasi", "Статистика психолога"),
    "psix_nokurs": (
        "Central course PSIXOLOGIYA::<year> not found. Enable the Psychology "
        "sync in Frappe (Moodle Settings) and run it — it creates the course, "
        "class groups and enrolments.",
        "Markaziy kurs (PSIXOLOGIYA::<yil>) topilmadi. Frappe'da (Moodle "
        "Settings) psixologiya sinxronini yoqing va yurgizing — u kurs, sinf "
        "guruhlari va yozilishlarni yaratadi.",
        "Центральный курс PSIXOLOGIYA::<год> не найден. Включите синхронизацию "
        "психологии во Frappe (Moodle Settings) и запустите её — она создаст "
        "курс, группы классов и записи.",
    ),
    "psix_nokurs_short": (
        "Central course not synced yet",
        "Markaziy kurs hali sinxron qilinmagan",
        "Центральный курс ещё не синхронизирован",
    ),
    "psix_allgroups": ("All classes", "Barcha sinflar", "Все классы"),
    "psix_search": ("Search by name…", "Ism bo'yicha qidirish…", "Поиск по имени…"),
    "psix_pickgroup": (
        "Pick a class or type a name to see the list.",
        "Ro'yxat uchun sinfni tanlang yoki ism yozing.",
        "Выберите класс или введите имя, чтобы увидеть список.",
    ),
    "psix_none": ("Nothing found", "Hech narsa topilmadi", "Ничего не найдено"),
    "psix_person": ("Person", "Kim", "Кто"),
    "psix_group": ("Class / group", "Sinf / guruh", "Класс / группа"),
    "psix_lasttalk": ("Last note", "Oxirgi suhbat", "Последняя беседа"),
    "psix_opencases": ("Open cases", "Ochiq caselar", "Открытые кейсы"),
    "psix_talk_add": ("New note", "Suhbat qaydi", "Запись беседы"),
    "psix_talk_hint": (
        "To add a note, pick the person from the list first:",
        "Qayd qo'shish uchun avval odamni ro'yxatdan tanlang:",
        "Чтобы добавить запись, сначала выберите человека из списка:",
    ),
    "psix_talk_type": ("Type", "Turi", "Тип"),
    "psix_talk_text": (
        "What was discussed, observations…",
        "Nima muhokama qilindi, kuzatuvlar…",
        "Что обсуждалось, наблюдения…",
    ),
    "psix_talk_advice": ("Recommendation", "Tavsiya", "Рекомендация"),
    "psix_t_individual": ("Individual session", "Individual suhbat", "Индивидуальная беседа"),
    "psix_t_guruhiy": ("Group session", "Guruhiy suhbat", "Групповая беседа"),
    "psix_t_otaona": ("Talk with parents", "Ota-ona bilan suhbat", "Беседа с родителями"),
    "psix_t_kuzatuv": ("Observation", "Kuzatuv", "Наблюдение"),
    "psix_confidential": (
        "Confidential: visible to the psychologist only. Administration sees "
        "numbers only, without texts.",
        "Maxfiy: faqat psixolog ko'radi. Rahbariyat matnsiz, faqat sonlarni ko'radi.",
        "Конфиденциально: видит только психолог. Руководство видит только "
        "цифры, без текстов.",
    ),
    "psix_save": ("Save", "Saqlash", "Сохранить"),
    "psix_delete": ("Delete", "O'chirish", "Удалить"),
    "psix_all": ("All", "Hammasi", "Все"),
    "psix_more": ("Show more", "Yana ko'rsatish", "Показать ещё"),
    "psix_card360": ("Student 360", "O'quvchi 360", "Ученик 360"),
    "psix_case_add": ("Open a case", "Case ochish", "Открыть кейс"),
    "psix_case_title": ("Issue title", "Muammo sarlavhasi", "Название проблемы"),
    "psix_case_desc": (
        "Description: what is happening, since when…",
        "Tavsif: nima bo'layapti, qachondan beri…",
        "Описание: что происходит, с какого времени…",
    ),
    "psix_src_kuzatuv": ("Own observation", "O'z kuzatuvim", "Собственное наблюдение"),
    "psix_src_oqituvchi": ("Teacher referral", "O'qituvchi yo'llanmasi", "Направление учителя"),
    "psix_src_otaona": ("Parent request", "Ota-ona murojaati", "Обращение родителей"),
    "psix_src_murojaat": ("Complaint module", "Murojaat moduli", "Модуль обращений"),
    "psix_src_test": ("Test result", "Test natijasi", "Результат теста"),
    "psix_imp_past": ("Low", "Past", "Низкий"),
    "psix_imp_orta": ("Medium", "O'rta", "Средний"),
    "psix_imp_yuqori": ("High", "Yuqori", "Высокий"),
    "psix_st_ochiq": ("Open", "Ochiq", "Открыт"),
    "psix_st_kuzatuvda": ("Monitoring", "Kuzatuvda", "На наблюдении"),
    "psix_st_yopildi": ("Closed", "Yopildi", "Закрыт"),
    "psix_talks_n": ("{$a} note(s)", "{$a} ta qayd", "{$a} записей"),
    "psix_case_concl": ("Conclusion", "Xulosa", "Заключение"),
    "psix_case_close": ("Close with conclusion", "Xulosa bilan yopish", "Закрыть с заключением"),
    "psix_case_talks": ("Notes in this case", "Case qaydlari", "Записи кейса"),
    "psix_tests_new_quiz": ("New test (quiz)", "Yangi test (quiz)", "Новый тест (quiz)"),
    "psix_tests_new_fb": (
        "New survey (feedback)", "Yangi so'rovnoma (feedback)", "Новый опрос (feedback)",
    ),
    "psix_tests_course": ("Open the course", "Kursni ochish", "Открыть курс"),
    "psix_tests_hint": (
        "Scope: the whole school by default; to limit to a class, add a group "
        "restriction (Restrict access → Group) in the activity settings. "
        "Anonymous mode is a feedback setting.",
        "Qamrov: standartda butun maktab; bitta sinfga cheklash uchun faoliyat "
        "sozlamalarida guruh cheklovini qo'ying (Restrict access → Group). "
        "Anonimlik — feedback'ning o'z sozlamasi.",
        "Охват: по умолчанию вся школа; чтобы ограничить классом, добавьте "
        "ограничение по группе (Restrict access → Group) в настройках. "
        "Анонимность — настройка feedback.",
    ),
    "psix_tests_none": (
        "No tests or surveys yet", "Hali test/so'rovnoma yo'q", "Тестов и опросов пока нет",
    ),
    "psix_tests_name": ("Name", "Nomi", "Название"),
    "psix_tests_open": ("Opens", "Ochilishi", "Открытие"),
    "psix_tests_close": ("Closes", "Yopilishi", "Закрытие"),
    "psix_tests_done": ("Responses", "Javoblar", "Ответы"),
    "psix_tests_results": ("Results", "Natijalar", "Результаты"),
    "psix_tests_edit": ("Settings", "Sozlash", "Настройки"),
    "psix_hidden": ("Hidden", "Yashirin", "Скрыт"),
    "psix_kind_quiz": ("Test", "Test", "Тест"),
    "psix_kind_fb": ("Survey", "So'rovnoma", "Опрос"),
    "psix_stat_talks": ("Notes (year)", "Suhbatlar (yil)", "Беседы (год)"),
    "psix_stat_month": ("Notes by month", "Suhbatlar oy kesimida", "Беседы по месяцам"),
    "psix_stat_bygroup": ("Notes by class", "Suhbatlar sinf kesimida", "Беседы по классам"),
    "psix_stat_bytype": ("Notes by type", "Suhbatlar tur kesimida", "Беседы по типам"),
    "psix_stat_note": (
        "Statistics show numbers only. Note and case texts are visible to the "
        "psychologist only.",
        "Statistika faqat sonlarni ko'rsatadi. Suhbat va case matnlari faqat "
        "psixologga ko'rinadi.",
        "Статистика показывает только цифры. Тексты бесед и кейсов видит "
        "только психолог.",
    ),

    # --- tashqi (qog'oz) test natijalari
    "psix_ext_title": (
        "Paper / external tests",
        "Tashqi (qog'oz) testlar",
        "Бумажные / внешние тесты",
    ),
    "psix_ext_hint": (
        "A test taken outside Moodle (on paper): record it here and enter the "
        "results class by class.",
        "Moodle'dan tashqarida (qog'ozda) olingan test: shu yerda ro'yxatga "
        "oling va natijalarini sinf-sinf kiriting.",
        "Тест, проведённый вне Moodle (на бумаге): зарегистрируйте его здесь "
        "и внесите результаты по классам.",
    ),
    "psix_ext_new": ("Register a test", "Test ro'yxatga olish", "Зарегистрировать тест"),
    "psix_ext_name": (
        "Test name (methodology)",
        "Test nomi (metodika)",
        "Название теста (методика)",
    ),
    "psix_ext_date": ("Taken on", "O'tkazilgan sana", "Дата проведения"),
    "psix_ext_note": (
        "Note: scope, scale, what the score means…",
        "Izoh: qamrov, shkala, ball nimani bildiradi…",
        "Примечание: охват, шкала, что означает балл…",
    ),
    "psix_ext_enter": ("Enter results", "Natija kiritish", "Внести результаты"),
    "psix_online_title": (
        "Online (Moodle) tests & surveys",
        "Onlayn (Moodle) test va so'rovnomalar",
        "Онлайн (Moodle) тесты и опросы",
    ),
    "psix_res_saved": ("Results saved.", "Natijalar saqlandi.", "Результаты сохранены."),
    "psix_res_pick": (
        "Pick a class — the grid is filled class by class.",
        "Sinfni tanlang — setka sinf-sinf to'ldiriladi.",
        "Выберите класс — таблица заполняется по классам.",
    ),
    "psix_res_ball": ("Score / level", "Ball / daraja", "Балл / уровень"),
    "psix_res_izoh": ("Comment", "Izoh", "Комментарий"),
    "psix_res_hint": (
        "Leaving both fields empty removes that student's result. "
        "Confidential: visible to the psychologist only.",
        "Ikkala maydon ham bo'sh qoldirilsa o'sha o'quvchining natijasi "
        "o'chiriladi. Maxfiy: faqat psixolog ko'radi.",
        "Если оба поля пустые, результат ученика удаляется. "
        "Конфиденциально: видит только психолог.",
    ),

    # ===== TIKLANGAN sessiya string'lari (efee394 dan, 2026-11-21) =====
    'au_card': ('Lesson recording', 'Dars yozuvi', 'Запись урока'),
    'au_none': ('This lesson has not been recorded yet — press "Start recording" above.', 'Bu dars hali yozib olinmagan — yuqoridagi «Yozuvni boshlash» tugmasini bosing.', 'Этот урок ещё не записан — нажмите «Начать запись» вверху.'),
    'bosh_ktp': ('Plan: {$a}', 'KTP: {$a}', 'КТП: {$a}'),
    'bosh_lessons': ('Lessons of the day', 'Kun darslari', 'Уроки дня'),
    'bosh_m_lessons': ('Lessons this term', 'Shu chorakdagi darslar', 'Уроков в этой четверти'),
    'bosh_m_panel': ('My full panel →', "To'liq panelim →", 'Моя полная панель →'),
    'bosh_m_tahlil': ('Lessons with AI analysis', 'AI tahlil qilingan darslar', 'Уроков с AI-анализом'),
    'bosh_metrics': ('My metrics', "Mening ko'rsatkichim", 'Мои показатели'),
    'bosh_more': ('{$a} more exams', 'yana {$a} ta imtihon', 'ещё {$a} экзаменов'),
    'bosh_nav': ('Dashboard', 'Ish stoli', 'Рабочий стол'),
    'bosh_nextday': ('Next teaching day: {$a} →', 'Keyingi dars kuni: {$a} →', 'Следующий учебный день: {$a} →'),
    'bosh_nokey': ('Your teacher key was not found in the current academic year timetable — the timetable has not been pushed yet, or the key does not match. Contact the administrator.', "Joriy o'quv yili jadvalida sizning o'qituvchi kalitingiz topilmadi — jadval hali yuklanmagan yoki kalit mos emas. Administratorga murojaat qiling.", 'В расписании текущего учебного года ваш ключ преподавателя не найден — расписание ещё не загружено или ключ не совпадает. Обратитесь к администратору.'),
    'bosh_noterm': ('No term is running right now — metrics will be calculated when the term starts.', "Hozir chorak davom etmayapti — ko'rsatkichlar chorak boshlanganda hisoblanadi.", 'Сейчас четверть не идёт — показатели будут считаться с началом четверти.'),
    'bosh_now': ('NOW', 'HOZIR', 'СЕЙЧАС'),
    'bosh_sub': ('You have {$a->jami} lessons today · {$a->otdi} finished', "Bugun {$a->jami} ta darsingiz bor · {$a->otdi} tasi o'tdi", 'Сегодня у вас {$a->jami} уроков · прошло: {$a->otdi}'),
    'bosh_sub_now': (' · now teaching {$a}', ' · hozir {$a} bilan dars', ' · сейчас урок с {$a}'),
    'bosh_sub_other': ('{$a} lessons on this day', 'Bu kunda {$a} ta dars', 'В этот день {$a} уроков'),
    'bosh_t_empty': ('All good — nothing is waiting for you.', "Hammasi joyida — kutilayotgan ish yo'q.", 'Всё в порядке — ожидающих дел нет.'),
    'bosh_t_fb': ('Unread feedback from leadership', "O'qilmagan rahbariyat fikri", 'Непрочитанный отзыв руководства'),
    'bosh_t_fb2': ('Becomes "seen" when you open the lesson page', "Dars sahifasida ochilganda «ko'rildi» bo'ladi", 'Станет «просмотрено» при открытии страницы урока'),
    'bosh_t_nuqson': ('Incomplete lessons', "To'ldirilmagan darslar", 'Незаполненные уроки'),
    'bosh_t_nuqson2': ('Topic, attendance or recording is missing', 'Mavzu, davomat yoki yozuv yetishmaydi', 'Не хватает темы, посещаемости или записи'),
    'bosh_t_sub': ('Ungraded submissions', 'Tekshirilmagan topshiriqlar', 'Непроверенные задания'),
    'bosh_t_sub2': ('File submitted, no grade yet', "Fayl topshirilgan, baho qo'yilmagan", 'Файл сдан, оценка не выставлена'),
    'bosh_today': ('today', 'bugun', 'сегодня'),
    'bosh_todo': ('To do', 'Kutilayotgan ishlar', 'Ожидающие дела'),
    'bosh_week': ('My week →', 'Mening haftam →', 'Моя неделя →'),
    'chat_nav': ('Messages', 'Muloqot', 'Сообщения'),
    'chat_sub': ('Chats with parents and colleagues · you open and manage group chats', 'Ota-onalar va hamkasblar bilan yozishmalar · guruh chatlarini siz ochasiz va boshqarasiz', 'Переписка с родителями и коллегами · групповые чаты открываете и ведёте вы'),
    'chat_default': ('Group chat', 'Guruh chati', 'Групповой чат'),
    'chat_parents': ('parents', 'ota-onalar', 'родители'),
    'chat_newperson': ('— Direct message...', '— Shaxsiy xabar...', '— Личное сообщение...'),
    'chat_newgroup': ('+ New group chat', '+ Yangi guruh chati', '+ Новый групповой чат'),
    'chat_created': ('Group chat created', 'Guruh chati ochildi', 'Групповой чат создан'),
    'chat_err_send': ('Message not sent — this user is not accepting messages right now.', 'Xabar yuborilmadi — bu foydalanuvchi hozircha xabar qabul qilmaydi.', 'Сообщение не отправлено — этот пользователь пока не принимает сообщения.'),
    'chat_err_group': ('You cannot select this group.', "Bu guruhni tanlash huquqingiz yo'q.", 'Нет права выбрать эту группу.'),
    'chat_empty': ('No chats yet.', "Hali chat yo'q.", 'Чатов пока нет.'),
    'chat_pick': ('Pick a chat on the left or open a new group.', 'Chapdan chat tanlang yoki yangi guruh oching.', 'Выберите чат слева или откройте новый.'),
    'chat_members': ('members', "a'zo", 'участн.'),
    'chat_write': ('Write a message...', 'Xabar yozing...', 'Напишите сообщение...'),
    'chat_members_settings': ('Members and settings', "A'zolar va sozlamalar", 'Участники и настройки'),
    'chat_settings': ('Chat settings', 'Chat sozlamalari', 'Настройки чата'),
    'chat_attached_group': ('Attached group', 'Biriktirilgan guruh', 'Привязанная группа'),
    'chat_autoinvite': ('auto-invite', 'avto-taklif', 'автоприглашение'),
    'chat_who_write': ('Who can write', 'Kim yoza oladi', 'Кто может писать'),
    'chat_write_all': ('Everyone', 'Hamma', 'Все'),
    'chat_write_owner': ('Only me (announcement mode)', "Faqat men (e'lon rejimi)", 'Только я (режим объявлений)'),
    'chat_who_see': ('Who can see', "Kim ko'ra oladi", 'Кто видит'),
    'chat_see_val': ('Members + head teacher/director', "A'zolar + zavuch/direktor", 'Участники + завуч/директор'),
    'chat_add_member': ('Add member', "A'zo qo'shish", 'Добавить участника'),
    'chat_memberlist': ('Members', "A'zolar", 'Участники'),
    'chat_remove_member': ('remove', 'chiqarish', 'удалить'),
    'chat_only_owner_write': ('Only the owner can write (announcement mode).', "Faqat boshqaruvchi yoza oladi (e'lon rejimi).", 'Писать может только владелец (режим объявлений).'),
    'uchr_taklif': ('Meeting proposal', 'Uchrashuv taklifi', 'Предложение встречи'),
    'uchr_taklif_qil': ('Propose', 'Taklif qilish', 'Предложить'),
    'uchr_joy': ('Room / address', 'Xona / manzil', 'Кабинет / адрес'),
    'uchr_izoh': ('Note (optional)', 'Izoh (ixtiyoriy)', 'Примечание (необязательно)'),
    'uchr_holat': ('status', 'holat', 'статус'),
    'uchr_kutilmoqda': ('awaiting confirmation', 'tasdiq kutilmoqda', 'ожидает подтверждения'),
    'uchr_tasdiq': ('confirmed', 'tasdiqlandi', 'подтверждено'),
    'uchr_bekor': ('cancelled', 'bekor qilindi', 'отменено'),
    'uchr_btn': ('📅 Meeting', '📅 Uchrashuv', '📅 Встреча'),
    'chat_send': ('Send', 'Yuborish', 'Отправить'),
    'ck_ai_full': ('AI lesson analysis', 'AI dars tahlili', 'AI-анализ урока'),
    'ck_btn_doska': ('For AI: board file + note', 'AI uchun: doska fayli + izoh', 'Для AI: файл с доски + комментарий'),
    'ck_btn_res': ('Lesson materials', 'Dars materiallari', 'Материалы урока'),
    'ck_yopish': ('Close', 'Yopish', 'Закрыть'),
    'ck_ai_open': ('Open the full analysis ▾', "To'liq tahlilni ochish ▾", 'Открыть полный анализ ▾'),
    'ck_ainote': ('Note about the lesson — the AI analysis will take it into account', 'Dars haqida izoh — AI tahlil shuni inobatga oladi', 'Комментарий об уроке — AI-анализ учтёт его'),
    'ck_ainote_btn': ('Save the note', 'Izohni saqlash', 'Сохранить комментарий'),
    'ck_ainote_ph': ('For example: a practice lesson — I speak less, students solve problems in their notebooks', "Masalan: amaliy dars — men kamroq gapiraman, o'quvchilar daftarda masala ishlaydi", 'Например: практический урок — я говорю меньше, ученики решают задачи в тетрадях'),
    'ck_ainote_saved': ('Note saved — the next AI analysis will take it into account.', 'Izoh saqlandi — keyingi AI tahlil uni inobatga oladi.', 'Комментарий сохранён — следующий AI-анализ учтёт его.'),
    'ck_pickcourse': ('Start typing — groups are filtered; pick one to open its cockpit', "Yozing — guruhlar filtrlanadi, tanlang — o'sha guruh kokpiti ochiladi", 'Начните печатать — группы фильтруются; выберите — откроется кокпит той группы'),
    'ck_hwcol': ('HW status', 'UV holati', 'ДЗ: статус'),
    'ck_hwfile': ('HW file', 'UV fayl', 'ДЗ: файл'),
    'ck_hwfrom': ('Homework being checked ({$a->sana}): {$a->matn}', 'Tekshirilayotgan uy vazifa ({$a->sana}): {$a->matn}', 'Проверяемое домашнее задание ({$a->sana}): {$a->matn}'),
    'ck_hwgrade': ('HW grade', 'UV baho', 'ДЗ: оценка'),
    'ck_latemin': ('Late (min)', 'Kech (daq)', 'Опозд. (мин)'),
    'ck_latemin_hint': ('Minutes late — saved only when "Late" is selected', 'Kechikish daqiqasi — faqat «Kechikdi» tanlanganda saqlanadi', 'Минуты опоздания — сохраняются только при статусе «Опоздал»'),
    'ck_opensub': ('Open submitted files and grade →', 'Topshirilgan fayllarni ochish va baholash →', 'Открыть сданные файлы и оценить →'),
    'ck_sub_draft': ('draft', 'qoralama', 'черновик'),
    'ck_transcript': ('Lesson transcript', 'Dars transkripti', 'Транскрипт урока'),
    'ck_sub_ok': ('submitted', 'topshirgan', 'сдал'),
    'ds_latemin': ('{$a} min late', '{$a} daqiqa kechikdi', 'опоздал на {$a} мин'),
    'ds_myresult': ('My result', 'Mening natijam', 'Мой результат'),
    'ds_nohomework': ('No homework was set for this lesson.', 'Bu darsga uy vazifa berilmagan.', 'К этому уроку домашнее задание не задано.'),
    'ds_norecord': ('There is no record for this lesson yet.', "Bu dars uchun hali yozuv yo'q.", 'По этому уроку записи пока нет.'),
    'ds_noresources': ('No materials have been added to this lesson.', "Bu darsga material qo'shilmagan.", 'К этому уроку материалы не добавлены.'),
    'ds_notopic': ('The topic has not been written yet', 'Mavzu hali yozilmagan', 'Тема ещё не записана'),
    'ds_open': ('Open the lesson page', 'Dars sahifasini ochish', 'Открыть страницу урока'),
    'ds_resources': ('Lesson materials', 'Dars materiallari', 'Материалы урока'),
    'ds_submit': ('Submit the assignment (file)', 'Vazifani topshirish (fayl)', 'Сдать задание (файл)'),
    'ds_title': ('Lesson', 'Dars', 'Урок'),
    'hw_bajardi': ('Done', 'Bajardi', 'Выполнил'),
    'hw_bajarmadi': ('Not done', 'Bajarmadi', 'Не выполнил'),
    'hw_qisman': ('Partially', 'Qisman', 'Частично'),
    'elon_nav': ('Announcements', "E'lonlar", 'Объявления'),
    'elonim_nav': ('My announcements', "E'lonlarim", 'Мои объявления'),
    'elon_sub': ('Write an announcement to your groups — students and parents get a notification', "Guruhlaringizga e'lon yozing — o'quvchi va ota-onalarga bildirishnoma boradi", 'Напишите объявление группам — ученикам и родителям придёт уведомление'),
    'elon_new': ('New announcement', "Yangi e'lon", 'Новое объявление'),
    'elon_mine': ('My announcements', "Mening e'lonlarim", 'Мои объявления'),
    'elon_none': ('No announcements yet.', "Hali e'lon yo'q.", 'Объявлений пока нет.'),
    'elonim_none': ('No announcements for you.', "Sizga e'lon yo'q.", 'Для вас объявлений нет.'),
    'elon_school': ('🏫 Whole school', '🏫 Butun maktab', '🏫 Вся школа'),
    'elon_turi_oddiy': ('Normal', 'Oddiy', 'Обычное'),
    'elon_turi_muhim': ('Important', 'Muhim', 'Важное'),
    'elon_turi_majlis': ('Meeting', 'Majlis', 'Собрание'),
    'elon_titleph': ('Title...', 'Sarlavha...', 'Заголовок...'),
    'elon_textph': ('Text... (goes to student and parent)', "Matn... (o'quvchi va ota-onaga boradi)", 'Текст... (придёт ученику и родителю)'),
    'elon_room': ('Room', 'Xona', 'Кабинет'),
    'elon_publish': ('Publish', "E'lon qilish", 'Опубликовать'),
    'elon_cancel': ('Cancel', 'Bekor qilish', 'Отменить'),
    'elon_cancel_q': ('Cancel the announcement? Students will no longer see it.', "E'lon bekor qilinsinmi? O'quvchilar uni boshqa ko'rmaydi.", 'Отменить объявление? Ученики больше его не увидят.'),
    'elon_cancelled': ('Announcement cancelled', "E'lon bekor qilindi", 'Объявление отменено'),
    'elon_school_tag': ('SCHOOL', 'MAKTAB', 'ШКОЛА'),
    'elon_group_tag': ('GROUP', 'GURUH', 'ГРУППА'),
    'elon_read': ('Read:', "O'qidi:", 'Прочитали:'),
    'elon_sent': ('Published — a notification was sent to students.', "E'lon qilindi — o'quvchilarga bildirishnoma yuborildi.", 'Опубликовано — ученикам отправлено уведомление.'),
    'elon_seencount': ('Read: {$a}', "O'qidi: {$a}", 'Прочитали: {$a}'),
    'elon_err_title': ('The title must not be empty.', "Sarlavha bo'sh bo'lmasin.", 'Заголовок не должен быть пустым.'),
    'elon_err_scope': ('Only leadership can post a school-wide announcement.', "Maktab e'lonini faqat rahbariyat yoza oladi.", 'Школьное объявление может писать только руководство.'),
    'elon_err_group': ("You don't have permission to post to this group.", "Bu guruhga e'lon yozish huquqingiz yo'q.", 'У вас нет права писать объявление этой группе.'),
    'jr_examlegend': ('· <b>purple column</b> = exam (scores are entered in the score grid) · <b>Term</b> = proposed grade, ✓ frozen', '· <b>binafsha ustun</b> = imtihon (ball setkada kiritiladi) · <b>Chorak</b> = taklif baho, ✓ muzlatilgan', '· <b>фиолетовый столбец</b> = экзамен (баллы вводятся в сетке баллов) · <b>Четверть</b> = предлагаемая оценка, ✓ заморожена'),
    'jr_exammeta': ('max. {$a->max} points · {$a->shakli}', 'maks. {$a->max} ball · {$a->shakli}', 'макс. {$a->max} баллов · {$a->shakli}'),
    'jr_examrow': ('Exam · {$a->turi}: {$a->name}', 'Imtihon · {$a->turi}: {$a->name}', 'Экзамен · {$a->turi}: {$a->name}'),
    'jr_examtitle': ('{$a->name} · {$a->sana} · max. {$a->max} points', '{$a->name} · {$a->sana} · maks. {$a->max} ball', '{$a->name} · {$a->sana} · макс. {$a->max} баллов'),
    'jr_ktp': ('Curriculum coverage: {$a->otildi}/{$a->jami} topics taught — <b>{$a->foiz}%</b>', "KTP qamrovi: {$a->otildi}/{$a->jami} mavzu o'tildi — <b>{$a->foiz}%</b>", 'Охват КТП: пройдено {$a->otildi}/{$a->jami} тем — <b>{$a->foiz}%</b>'),
    'jr_nomycourses': ('No groups you teach were found — the journal is empty.', "Siz dars o'tadigan guruh topilmadi — jurnal bo'sh.", 'Групп, в которых вы преподаёте, не найдено — журнал пуст.'),
    'jr_rejatitle': ('Curriculum plan: {$a}', 'KTP rejasi: {$a}', 'План КТП: {$a}'),
    'jr_termcol': ('Term', 'Chorak', 'Четверть'),
    'jr_termtitle': ('Proposed term grade: total/100 → grade (FA 10% + unit tests 50% + term test 40%). ✓ — the term is closed and frozen.', 'Taklif chorak bahosi: jami/100 → baho (FB 10% + BSB/LI 50% + CHSB 40%). ✓ — chorak yopilib muzlatilgan.', 'Предлагаемая оценка за четверть: сумма/100 → оценка (ФО 10% + БСБ/ПР 50% + ЧСБ 40%). ✓ — четверть закрыта и заморожена.'),
    'jr_view_grid': ('Grades grid', 'Baholar setkasi', 'Сетка оценок'),
    'jr_view_topics': ('All topics & homework', 'Barcha mavzular va UV', 'Все темы и ДЗ'),
    'jr_m_status': ('Status', 'Holat', 'Статус'),
    'jr_m_filled': ('Journal', 'Jurnal', 'Журнал'),
    'jr_m_done': ('TAUGHT', "O'TILDI", 'ПРОЙДЕНО'),
    'jr_m_today': ('TODAY', 'BUGUN', 'СЕГОДНЯ'),
    'jr_m_future': ('UPCOMING', 'KELGUSI', 'ПРЕДСТОИТ'),
    'jr_m_exam': ('EXAM', 'IMTIHON', 'ЭКЗАМЕН'),
    'jr_m_grades': ('{$a} grades', '{$a} baho', '{$a} оценок'),
    'sub_darslar': ('Lessons', 'Darslar', 'Уроки'),
    'sub_topshiriq': ('Assignments', 'Topshiriqlar', 'Задания'),
    'res_add': ('Add a file', "Fayl qo'shish", 'Добавить файл'),
    'res_addlink': ('Add a link', "Havola qo'shish", 'Добавить ссылку'),
    'res_delete': ('Delete', "O'chirish", 'Удалить'),
    'res_delete_q': ('Delete this material? Students will no longer see it.', "Resurs o'chirilsinmi? O'quvchilar uni boshqa ko'rmaydi.", 'Удалить материал? Ученики больше его не увидят.'),
    'res_deleted': ('Material deleted', "Material o'chirildi", 'Материал удалён'),
    'res_empty': ('No materials have been added yet.', "Hali material qo'shilmagan.", 'Материалы пока не добавлены.'),
    'res_err_limit': ('At most {$a} materials per lesson.', "Bitta darsga ko'pi bilan {$a} ta material.", 'К одному уроку не более {$a} материалов.'),
    'res_err_type': ('This file type is not accepted. Allowed: {$a}', 'Bu fayl turi qabul qilinmaydi. Mumkin: {$a}', 'Этот тип файла не принимается. Допустимо: {$a}'),
    'res_err_url': ('Invalid link — it must start with http(s)://.', "Havola noto'g'ri — http(s):// bilan boshlanishi kerak.", 'Неверная ссылка — должна начинаться с http(s)://.'),
    'res_hint': ('At most {$a->limit} materials, each file ≤ {$a->mb} MB. Students see them on the "Lesson" page; the board material (above) is for AI analysis only.', "Ko'pi bilan {$a->limit} ta material, fayl ≤ {$a->mb} MB. Bular o'quvchiga «Dars» sahifasida ko'rinadi; doska materiali (yuqorida) esa faqat AI tahlili uchun.", 'Не более {$a->limit} материалов, файл ≤ {$a->mb} МБ. Их ученик видит на странице «Урок»; материал с доски (выше) — только для AI-анализа.'),
    'res_link': ('LINK', 'HAVOLA', 'ССЫЛКА'),
    'res_saved': ('Material added', "Material qo'shildi", 'Материал добавлен'),
    'res_title': ('Lesson materials — visible to students (slides, video, links)', "Dars materiallari — o'quvchiga ko'rinadi (slayd, video, havola)", 'Материалы урока — видны ученику (слайды, видео, ссылки)'),
    'res_titleph': ('Title (optional)', 'Nomi (ixtiyoriy)', 'Название (необязательно)'),
    'res_urlph': ('https://... (YouTube, interactive guide)', "https://... (YouTube, interaktiv qo'llanma)", 'https://... (YouTube, интерактивное пособие)'),
    's360_title': ('Student card', "O'quvchi kartasi", 'Карточка ученика'),
    's360_strong': ('Strong:', 'Kuchli:', 'Сильные:'),
    's360_weak': ('Needs attention:', "E'tibor talab:", 'Требуют внимания:'),
    's360_subjects': ('All subjects', 'Barcha fanlar', 'Все предметы'),
    's360_termscore': ('Term grade', 'Chorak bahosi', 'Оценка за четверть'),
    's360_exams': ('Exams', 'Imtihonlar', 'Экзамены'),
    's360_noexams': ('No exam results.', "Imtihon natijalari yo'q.", 'Результатов экзаменов нет.'),
    's360_nodata': ('No grades this term.', "Bu chorakda baho yo'q.", 'За эту четверть оценок нет.'),
    's360_attendance': ('Attendance', 'Davomat', 'Посещаемость'),
    's360_att_total': ('Total lessons', 'Jami darslar', 'Всего уроков'),
    's360_att_absent': ('Absent', 'Kelmagan', 'Пропущено'),
    's360_att_excused': ('Excused', 'Sababli', 'По уважительной'),
    's360_att_late': ('Late', 'Kechikkan', 'Опозданий'),
    's360_fb': ('FA avg.', "FB o'rt.", 'ФО сред.'),
    's360_total': ('Total/100', 'Jami/100', 'Всего/100'),
    's360_att_inlessons': ('In lessons', 'Darslarda', 'На уроках'),
    's360_lessons': ('lessons', 'dars', 'уроков'),
    's360_times': ('times', 'marta', 'раз'),
    's360_hw': ('Homework discipline', 'UV intizomi', 'Дисциплина ДЗ'),
    's360_hw_done': ('Completed this term', "Chorak bo'yicha bajarilgan", 'Выполнено за четверть'),
    's360_hw_count': ('Done / assigned', 'Bajarilgan / berilgan', 'Выполнено / задано'),
    's360_hw_none': ('No homework checks.', "UV tekshiruvi yo'q.", 'Проверок ДЗ нет.'),
    's360_seen': ('parent read ✓', "ota-ona o'qidi ✓", 'родитель прочитал ✓'),
    's360_open': ('Open card: {\\$a}', '{\\$a} — kartasini ochish', 'Открыть карточку: {\\$a}'),
    's360_reviews': ("Teachers' reviews", "O'qituvchilar sharhi", 'Отзывы учителей'),
    's360_noreviews': ('No reviews yet.', "Hali sharh yo'q.", 'Отзывов пока нет.'),
    's360_ex_name': ('Exam', 'Imtihon', 'Экзамен'),
    's360_ex_score': ('Score', 'Ball', 'Балл'),
    's360_ex_status': ('Status', 'Holat', 'Статус'),
    'shell_sub': ('teacher portal', "o'qituvchi portali", 'портал учителя'),
    'set_oynasoat_desc': ('How long a teacher may change an entered score without approval, counted from the first entry. <b>0 = no limit</b> (teachers can always edit) — this is the default. The regulation asks for 72; set 72 when you want to close editing. After the window, only a coordinator (manageexams) can change a score and must give a reason. Every change is logged either way.', "O'qituvchi kiritilgan ballni tasdiqsiz qancha vaqt o'zgartira oladi (birinchi kiritishdan boshlab). <b>0 = cheklov yo'q</b> (o'qituvchi doim tahrirlay oladi) — standart shu. Nizom 72 soatni talab qiladi; tahrirni yopmoqchi bo'lsangiz 72 qo'ying. Oyna yopilgandan keyin ballni faqat koordinator (manageexams) o'zgartira oladi va sabab yozishi shart. Har qanday o'zgarish baribir jurnalga tushadi.", 'Сколько времени учитель может менять введённый балл без подтверждения (с момента первого ввода). <b>0 = без ограничения</b> (учитель всегда может править) — это значение по умолчанию. Регламент требует 72; поставьте 72, когда захотите закрыть правку. После окна балл может изменить только координатор (manageexams) и обязан указать причину. Любое изменение в любом случае попадает в журнал.'),
    'bh_examclosed': ('This assessment is closed. Only the academic coordinator can change scores now.', "Bu baholash yopilgan. Endi ballni faqat o'quv koordinatori o'zgartira oladi.", 'Это оценивание закрыто. Теперь балл может изменить только учебный координатор.'),
    'im_nav': ('Exams', 'Imtihonlar', 'Экзамены'),
    'im_sub': ('Tests and written work · create, run and grade in one place · powered by Moodle', "Test va yozma ishlar · bitta joyda yaratasiz, o'tkazasiz va baholaysiz · orqada Moodle", 'Тесты и письменные работы · создаёте, проводите и оцениваете в одном месте · на базе Moodle'),
    'im_new': ('+ New exam', '+ Yangi imtihon', '+ Новый экзамен'),
    'im_type_test': ('TEST', 'TEST', 'ТЕСТ'),
    'im_type_yoz': ('WRITTEN', 'YOZMA', 'ПИСЬМ.'),
    'im_st_draft': ('DRAFT', 'QORALAMA', 'ЧЕРНОВИК'),
    'im_st_plan': ('SCHEDULED', 'REJALI', 'ЗАПЛАНИРОВАН'),
    'im_st_live': ('LIVE', 'FAOL', 'АКТИВЕН'),
    'im_st_grade': ('GRADING', 'BAHOLANMOQDA', 'НА ОЦЕНКЕ'),
    'im_st_closed': ('CLOSED', 'TUGAGAN', 'ЗАВЕРШЁН'),
    'im_amal_edit': ('Edit', 'Tahrirlash', 'Изменить'),
    'im_amal_open': ('Open', 'Ochish', 'Открыть'),
    'im_amal_watch': ('Monitor', 'Kuzatish', 'Наблюдать'),
    'im_amal_grade': ('Grade', 'Baholash', 'Оценить'),
    'im_amal_results': ('Results', 'Natijalar', 'Результаты'),
    'im_stat_live': ('Live exams', 'Faol imtihon', 'Активных'),
    'im_stat_grade': ('Awaiting grading', 'Baholanishi kutilmoqda', 'Ожидают оценки'),
    'im_stat_week': ('This week', 'Bu hafta rejada', 'На этой неделе'),
    'im_col_exam': ('Exam', 'Imtihon', 'Экзамен'),
    'im_col_group': ('Group', 'Guruh', 'Группа'),
    'im_col_type': ('Type', 'Tur', 'Тип'),
    'im_col_date': ('Date', 'Sana', 'Дата'),
    'im_col_status': ('Status', 'Holat', 'Статус'),
    'im_col_progress': ('Submitted / Graded', 'Topshirdi / Baholandi', 'Сдали / Оценено'),
    'im_prog': ('{$a->topshirgan} / {$a->jami} submitted · {$a->baholangan} graded', '{$a->topshirgan} / {$a->jami} topshirdi · {$a->baholangan} baholandi', '{$a->topshirgan} / {$a->jami} сдали · {$a->baholangan} оценено'),
    'im_muddat': ('due', 'muddat', 'срок'),
    'im_empty': ('No exams yet. Pick a group and type above to create one.', "Hali imtihon yo'q. Yuqoridan guruh va tur tanlab, yangi imtihon yarating.", 'Пока нет экзаменов. Выберите группу и тип выше, чтобы создать.'),
    'oqp_analyses': ('Lesson analyses (AI + head-teacher feedback)', 'Dars tahlillari (AI + zavuch fikri)', 'Анализы уроков (ИИ + отзыв завуча)'),
    'oqp_t_total': ('Planned lessons total', "Reja bo'yicha jami dars", 'Всего уроков по плану'),
    'oqp_t_done': ('Fully completed', "To'liq bajarilgan", 'Полностью выполнено'),
    'oqp_t_gap': ('Has gaps / remaining', 'Kamchilik bor / qolgan', 'Есть недочёты'),
    'oqp_t_ai': ('AI lesson analysis', 'AI dars tahlili', 'ИИ-анализ урока'),
    'oqp_abovenorm': ('above norm', "me'yordan yuqori", 'выше нормы'),
    'oqp_belownorm': ('below norm', "me'yordan past", 'ниже нормы'),
    'oqp_meters': ('Completion by criteria', "Mezonlar bo'yicha bajarilish", 'Выполнение по критериям'),
    'oqp_ai_overall': ('Overall AI analysis', 'AI umumiy tahlili', 'Общий ИИ-анализ'),
    'oqp_bygroup': ('By group', "Guruhlar bo'yicha", 'По группам'),
    'oqp_period': ('Period summary', 'Davr xulosasi', 'Итог периода'),
    'oqp_todo': ('To do', 'Bajarilishi kerak', 'Нужно выполнить'),
    'oqp_today_meet': ('Meeting TODAY', 'BUGUN uchrashuv bor', 'СЕГОДНЯ встреча'),
    'oqp_open_chat': ('open chat', 'suhbatni ochish', 'открыть чат'),
    'oqp_todo_gap': ('Lessons not given / with gaps', "O'tilmagan / kamchilikli darslar", 'Непроведённые / с недочётами уроки'),
    'oqp_todo_rec': ('Lessons without recording (audio off)', 'Yozuvsiz (audio yoqilmagan) darslar', 'Уроки без записи (аудио не включено)'),
    'oqp_todo_soon': ('Upcoming lessons', 'Kelgusi darslar', 'Предстоящие уроки'),
    'oqp_ai_norec': ('No analysis — audio was not recorded', "Audio yoqilmagani uchun tahlil yo'q", 'Нет анализа — аудио не включено'),
    'oqp_subst': ('Substituted lessons', "O'rnini bosgan darslar", 'Замещённые уроки'),
    'oqp_period_lbl': ('Period', 'Davr', 'Период'),
    'oqp_group_lbl': ('Group', 'Guruh', 'Группа'),
    'oqp_allgroups': ('All my groups', 'Barcha guruhlarim', 'Все мои группы'),
    'oqp_s_done': ('completed', 'bajarildi', 'выполнено'),
    'oqp_s_part': ('partial', 'qisman', 'частично'),
    'oqp_s_miss': ('missing', 'qolgan', 'не проведён'),
    'oqp_s_future': ('upcoming', 'kelgusi', 'предстоит'),
    'oqp_meet_next': ('Upcoming meeting', 'Kelayotgan uchrashuv', 'Предстоящая встреча'),
    'oqp_meet_req': ('New meeting request', "Yangi uchrashuv so'rovi", 'Новый запрос на встречу'),
    'oqp_an_sub': ('Lessons with audio · latest first · {$a} total · click a row', 'Audio yozib olingan darslar · eng oxirgi yozuv birinchi · jami {$a} ta · qatorni bosing', 'Уроки с аудиозаписью · последняя запись первой · всего {$a} · нажмите на строку'),
    'oqp_an_empty': ('No audio recordings yet. Start recording in the lesson cockpit.', "Hali audio yozuv yo'q. Dars kokpitida yozuvni boshlang.", 'Пока нет аудиозаписей. Начните запись в кабинете урока.'),
    'oqp_an_date': ('Date', 'Sana', 'Дата'),
    'oqp_an_group': ('Group · Lesson', 'Guruh · Dars', 'Группа · Урок'),
    'oqp_an_rec': ('Recording', 'Yozuv', 'Запись'),
    'oqp_an_score': ('AI score', 'AI baho', 'Оценка ИИ'),
    'oqp_an_sum': ('AI summary', 'AI qisqa xulosa', 'Краткое резюме ИИ'),
    'oqp_an_fb': ('Head-teacher feedback', 'Zavuch fikri', 'Отзыв завуча'),
    'oqp_an_view': ('Open', "Ko'rish", 'Открыть'),
    'oqp_an_close': ('Close', 'Yopish', 'Закрыть'),
    'oqp_an_replied': ('replied ✓', 'javob berilgan ✓', 'есть ответ ✓'),
    'oqp_an_await': ('awaiting reply', 'javob kutilmoqda', 'ждёт ответа'),
    'oqp_an_nofb': ('none yet', "hali yo'q", 'пока нет'),
    'oqp_an_cockpit': ('Go to lesson cockpit', "Dars kokpitiga o'tish", 'Перейти в кабинет урока'),
    'im_w_sub': ('Convenient wizard · all Moodle features kept · creates quiz/assign behind the scenes', 'Qulay sehrgar · barcha Moodle imkoniyati saqlanadi · orqada quiz/assign yaratiladi', 'Удобный мастер · все возможности Moodle · на фоне создаётся quiz/assign'),
    'im_w_type': ('Exam type', 'Imtihon turi', 'Тип экзамена'),
    'im_w_main': ('Basics', "Asosiy ma'lumot", 'Основное'),
    'im_w_settings': ('Settings', 'Sozlamalar', 'Настройки'),
    'im_type_test_f': ('Test (quiz)', 'Test (quiz)', 'Тест (quiz)'),
    'im_type_yoz_f': ('Written work (assignment)', 'Yozma ish (topshiriq)', 'Письменная работа (задание)'),
    'im_w_test_d': ('Choice, number, matching, formula — auto-graded. Essay manual.', "Variant, raqam, moslashtirish, formula… — avtomatik baholanadi. Insho qo'lda.", 'Варианты, число, сопоставление, формула — автооценка. Эссе вручную.'),
    'im_w_yoz_d': ('File upload or online text — teacher grades with a rubric.', "Fayl yuklash yoki onlayn matn — o'qituvchi rubrika bilan baholaydi.", 'Загрузка файла или онлайн-текст — учитель оценивает по рубрике.'),
    'im_w_name': ('Name', 'Nom', 'Название'),
    'im_w_name_ph': ('e.g. BSB-2 · Systems of equations', 'masalan: BSB-2 · Tenglamalar sistemasi', 'например: BSB-2 · Системы уравнений'),
    'im_w_start': ('Start', 'Boshlanish', 'Начало'),
    'im_w_duration': ('Duration (min)', 'Davomiylik (daqiqa)', 'Длительность (мин)'),
    'im_w_due': ('Due date', 'Topshirish muddati', 'Срок сдачи'),
    'im_w_maxscore': ('Maximum score', 'Maksimal ball', 'Максимальный балл'),
    'im_w_attempts': ('Attempts', 'Urinishlar soni', 'Число попыток'),
    'im_w_unlimited': ('Unlimited', 'Cheklanmagan', 'Без ограничения'),
    'im_w_shuffle': ('Shuffle questions', 'Savollarni aralash tartibda', 'Перемешивать вопросы'),
    'im_w_review': ('What the student sees', "O'quvchi nimani ko'radi", 'Что видит ученик'),
    'im_w_rev_closed': ('Only after close (standard exam)', 'Faqat yopilgach (standart imtihon)', 'Только после закрытия (стандарт)'),
    'im_w_rev_during': ('Open during (practice)', 'Jarayonda ochiq (mashq)', 'Открыто в процессе (тренировка)'),
    'im_w_rev_marks': ('Marks only (answers hidden)', 'Faqat ball (javoblar yashirin)', 'Только балл (ответы скрыты)'),
    'im_w_security': ('Exam mode (security)', 'Imtihon rejimi (xavfsizlik)', 'Режим экзамена (безопасность)'),
    'im_w_sec_simple': ('Simple (no limits)', 'Oddiy (cheklovsiz)', 'Обычный (без ограничений)'),
    'im_w_sec_class': ('Class exam (password + time)', 'Sinf imtihoni (parol + vaqt)', 'Классный экзамен (пароль + время)'),
    'im_w_sec_strict': ('Strict (password + Safe Exam Browser)', 'Qattiq (parol + Safe Exam Browser)', 'Строгий (пароль + Safe Exam Browser)'),
    'im_w_password': ('Access password (optional)', 'Kirish paroli (ixtiyoriy)', 'Пароль доступа (необязательно)'),
    'im_w_sec_note': ("SEB works only on Windows/iPad (Android phones can't enter). IP restriction works on the school network. Offline is app-only and NOT together with SEB.", "SEB faqat Windows/iPad'da ishlaydi (Android telefon kira olmaydi). IP cheklov maktab tarmog'ida ishlaydi. Offline faqat Moodle ilovasida va SEB bilan birga EMAS.", 'SEB работает только на Windows/iPad (Android-телефон не войдёт). IP-ограничение действует в школьной сети. Офлайн — только в приложении Moodle и НЕ вместе с SEB.'),
    'im_w_subfile': ('File upload (PDF/image/document)', 'Fayl yuklash (PDF/rasm/hujjat)', 'Загрузка файла (PDF/изображение/документ)'),
    'im_w_subtext': ('Online text', 'Onlayn matn', 'Онлайн-текст'),
    'im_w_yoz_note': ('Grading: rubric and PDF annotation (editpdf) on the grading screen.', 'Baholash: rubrika va PDF ustiga belgi (editpdf) baholash ekranida.', 'Оценивание: рубрика и пометки на PDF (editpdf) на экране оценки.'),
    'im_w_create': ('Create exam', 'Imtihonni yaratish', 'Создать экзамен'),
    'im_w_created': ('Exam created.', 'Imtihon yaratildi.', 'Экзамен создан.'),
    'im_w_created_q': ('Test created — now add questions.', "Test yaratildi — endi savol qo'shing.", 'Тест создан — добавьте вопросы.'),
    'im_w_err': ('Exam was not created.', 'Imtihon yaratilmadi.', 'Экзамен не создан.'),
    'im_w_nocourse': ('No groups found where you can create an exam.', 'Siz imtihon yarata oladigan guruh topilmadi.', 'Не найдено групп, где вы можете создать экзамен.'),
    'im_w_subnet': ('Only from class IP/network (optional)', "Faqat sinf IP/tarmog'idan (ixtiyoriy)", 'Только из IP/сети класса (необязательно)'),
    'im_w_offline': ('Offline in app (no internet, Moodle app)', 'Offline ilovada (internetsiz, Moodle ilovasi)', 'Офлайн в приложении (без интернета, приложение Moodle)'),
    'im_s_title': ('Questions', 'Savollar', 'Вопросы'),
    'im_s_sub': ('Adding/editing a question opens the core editor; here is the list and summary', "Savol qo'shish/tahrirlash yadro muharririga olib boradi; bu yerda ro'yxat va xulosa", 'Добавление/правка вопроса ведёт в редактор ядра; здесь список и итог'),
    'im_s_step': ('Questions', 'Savollar', 'Вопросы'),
    'im_s_publish': ('Publish', "E'lon", 'Публикация'),
    'im_s_add': ('Add question', "Savol qo'shish", 'Добавить вопрос'),
    'im_s_new': ('New question', 'Yangi savol', 'Новый вопрос'),
    'im_s_bank': ('From bank / reorder', 'Bankdan / tartiblash', 'Из банка / порядок'),
    'im_s_import': ('Import (GIFT/Aiken/XML)', 'Import (GIFT/Aiken/XML)', 'Импорт (GIFT/Aiken/XML)'),
    'im_s_types': ('Moodle offers 17 types: multiple choice, true/false, short answer, numerical, matching, calculated, Cloze, gap-select, drag-and-drop, essay…', "Moodle 17 savol turini taklif qiladi: variant, to'g'ri/noto'g'ri, qisqa javob, raqamli, moslashtirish, formulali, Cloze, bo'sh joy, sudrab, insho…", 'Moodle предлагает 17 типов: выбор, верно/неверно, короткий ответ, числовой, сопоставление, формула, Cloze, пропуск, перетаскивание, эссе…'),
    'im_s_empty': ('No questions yet — add via “New question”.', "Hali savol yo'q — «Yangi savol» bilan qo'shing.", 'Пока нет вопросов — добавьте через «Новый вопрос».'),
    'im_s_ball': ('pts', 'ball', 'балл'),
    'im_s_edit': ('edit', 'tahrir', 'правка'),
    'im_s_summary': ('Summary', 'Xulosa', 'Итог'),
    'im_s_qcount': ('Questions', 'Savol', 'Вопросов'),
    'im_s_total': ('Total marks', 'Jami ball', 'Всего баллов'),
    'im_s_ready': ('Ready?', 'Tayyormi?', 'Готово?'),
    'im_s_ready_note': ('Once questions are added the exam is ready. Activate it in the quiz settings.', "Savollar qo'shilgach imtihon tayyor. Faollashtirish quiz sozlamasida.", 'После добавления вопросов экзамен готов. Активация — в настройках теста.'),
    'im_s_preview': ('Preview', "Ko'rib chiqish", 'Просмотр'),
    'im_s_done': ('To exams', 'Imtihonlarga', 'К экзаменам'),
    'im_b_title': ('Grading', 'Baholash', 'Оценивание'),
    'im_b_sub': ('{$a->topshirdi} submitted · {$a->baholandi} graded · {$a->jami} students', "{$a->topshirdi} topshirdi · {$a->baholandi} baholandi · {$a->jami} o'quvchi", 'Сдали: {$a->topshirdi} · оценено: {$a->baholandi} · учеников: {$a->jami}'),
    'im_b_gradeall': ('Grade all (core)', 'Hammasini baholash (yadro)', 'Оценить всех (ядро)'),
    'im_b_empty': ('No students found.', "O'quvchi topilmadi.", 'Ученики не найдены.'),
    'im_b_tograde': ('to grade', 'baholanmagan', 'не оценено'),
    'im_b_draft': ('draft', 'qoralama', 'черновик'),
    'im_b_nosub': ('not submitted', 'topshirmagan', 'не сдал'),
    'im_b_rubric_note': ('“Grade” opens the core grader with rubric, PDF annotation (editpdf) and feedback.', "«Baholash» rubrika, PDF ustiga belgi (editpdf) va fikr bilan yadro grader'ida ochiladi.", '«Оценить» открывает ядровой grader с рубрикой, пометками на PDF (editpdf) и отзывом.'),
    'im_b_quiz_sub': ('The test is graded automatically. Essays manual; results and analysis below.', "Test avtomatik baholanadi. Insho qo'lda; natija va tahlil quyida.", 'Тест оценивается автоматически. Эссе вручную; результаты и анализ ниже.'),
    'im_b_r_grades': ('Grades table', 'Ballar jadvali', 'Таблица баллов'),
    'im_b_r_grades_s': ("each student's final mark (gradebook)", "har o'quvchi yakuniy bali (gradebook)", 'итоговый балл каждого (gradebook)'),
    'im_b_r_manual': ('Manual grading (essay)', "Qo'lda baholash (insho)", 'Ручная оценка (эссе)'),
    'im_b_r_manual_s': ('marks for non-auto questions', 'avto baholanmaydigan savollarga ball', 'баллы за неавтоматические вопросы'),
    'im_b_r_stats': ('Statistics', 'Statistika', 'Статистика'),
    'im_b_r_stats_s': ("each question's difficulty and discrimination", 'har savol qiyinligi va diskriminatsiyasi', 'сложность и дискриминация вопросов'),
    'im_b_r_resp': ('Responses', 'Javoblar', 'Ответы'),
    'im_b_r_resp_s': ('who marked what', 'kim nima belgiladi', 'кто что отметил'),
    'im_b_quiz_note': ('Objective questions are graded instantly; only essays are manual.', "Obyektiv savollar darhol baholanadi; faqat insho qo'lda.", 'Объективные вопросы оцениваются сразу; вручную только эссе.'),
    'im_w_nizom': ('Regulation link (optional)', "Nizom bog'lanishi (ixtiyoriy)", 'Привязка к нормативу (необязательно)'),
    'im_w_nizom_none': ('— not linked (free exam) —', "— bog'lanmagan (erkin imtihon) —", '— без привязки (свободный экзамен) —'),
    'im_w_nizom_bsb': ('BSB (50% of term grade)', 'BSB (chorak balining 50%iga)', 'BSB (50% четвертного балла)'),
    'im_w_nizom_chsb': ('CHSB (40%)', 'CHSB (40%iga)', 'CHSB (40%)'),
    'im_w_nizom_li': ('LI — project (50%)', 'LI — loyiha ishi (50%iga)', 'LI — проект (50%)'),
    'im_w_term': ('Term', 'Chorak', 'Четверть'),
    'im_w_nizom_note': ('If linked, after grading in Moodle the «Import» button on the grading screen moves marks into the term calculation.', "Bog'lansa, Moodle'da baholagach «Baholash» ekranidagi «Import» tugmasi ballarni chorak hisobiga ko'chiradi.", 'При привязке кнопка «Импорт» на экране оценивания перенесёт баллы в четвертной расчёт.'),
    'im_b_import': ('Import marks to term', 'Ballarni chorakka import', 'Импорт баллов в четверть'),
    'im_b_import_note': ('Moves Moodle grades into the regulation (BSB/CHSB/LI).', "Moodle baholarini nizom hisobiga (BSB/CHSB/LI) ko'chiradi.", 'Переносит оценки Moodle в норматив (BSB/CHSB/LI).'),
    'im_b_imported': ('{$a->n} / {$a->jami} marks imported into the term.', '{$a->n} / {$a->jami} ball chorak hisobiga import qilindi.', '{$a->n} / {$a->jami} баллов импортировано в четверть.'),
    'im_s_saved': ('Saved. Closing…', 'Saqlandi. Yopilmoqda…', 'Сохранено. Закрытие…'),
    'im_w_summary': ('Exam summary', 'Imtihon xulosasi', 'Сводка экзамена'),
    'im_w_behind': ('Behind: Moodle quiz module — attempts, auto-grading, SEB, mobile app and statistics from the core.', 'Orqada: Moodle quiz moduli — urinish, avto-baho, SEB, mobil ilova va statistika yadrodan keladi.', 'На фоне: модуль Moodle quiz — попытки, автооценка, SEB, моб. приложение и статистика из ядра.'),
    'im_w_behind_yoz': ('Behind: Moodle assignment — file upload, rubric, editpdf and feedback from the core.', 'Orqada: Moodle topshiriq (assign) — fayl yuklash, rubrika, editpdf va fikr yadrodan keladi.', 'На фоне: Moodle задание (assign) — загрузка файла, рубрика, editpdf и отзыв из ядра.'),
    'im_w_nextsteps': ('Next steps', 'Keyingi qadamlar', 'Следующие шаги'),
    'im_w_ns_q': ('new · from bank · import (GIFT/XML)', 'yangi · bankdan · import (GIFT/XML)', 'новый · из банка · импорт (GIFT/XML)'),
    'im_w_ns_pub': ('preview + notify students', "oldindan ko'rish + o'quvchiga bildirishnoma", 'просмотр + уведомление ученику'),
}
