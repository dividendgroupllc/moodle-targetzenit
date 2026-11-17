# Moodle imkoniyatlari — dars jarayoni uchun yo'l xaritasi

> Sana: 2026-10-26. Maqsad: o'quv jarayonining HAMMA qismini Moodle'ning
> standart vositalarida yuritish. Bizning plagin — faqat qulaylik qatlami
> (jadval, jurnal, audio, AI tahlil).

## Allaqachon ishlayotgani

| | Holat |
|---|---|
| Foydalanuvchilar (o'quvchi/o'qituvchi) | Frappe'dan sinxron, 07:00 |
| Kurslar va kursga yozish, rollar | Frappe jadvalidan, 07:10 |
| Dars jadvali | Frappe'dan, 07:20 + plaginda sahifa |
| Jurnal: mavzu, yo'qlama, baho | Plaginda (e-maktab uslubi) |
| **Baho -> gradebook** | ✅ standart |
| **Uy vazifa -> `mod_assign`** | ✅ standart |
| Dars audiosi + AI tahlil | Plaginda |

## Bosqichlar

Tartib bejiz emas: har bosqich oldingisiga tayanadi va o'zidan keyingisini
osonlashtiradi. Yuqoridagilari kundalik ishga ta'sir qiladi, pastdagilari
sifatni oshiradi.

### 1-bosqich. O'quv materiali (poydevor)

Moodle: `resource` (fayl), `page` (sahifa), `folder`, `url`, `book`, `label`.

Hozir kursda faqat uy vazifa bor — dars materiali yo'q. O'qituvchi mavzuga
oid taqdimot, matn, havolani kursga qo'ysa, o'quvchi uyda qaytib ko'ra oladi.

**Nima qilamiz:** jurnaldagi har mavzu uchun kurs bo'limi (section)
avtomatik ochiladi va o'qituvchi materialni o'sha mavzu ostiga qo'yadi.
Shunda kurs sahifasi dars jadvaliga mos tartibda tuziladi.

### 2-bosqich. Test va savollar banki (eng katta qiymat)

Moodle: `quiz` + `qbank`.

Bu Moodle'ning eng kuchli qismi: test avtomatik baholanadi va bahosi
**o'z-o'zidan gradebook'ga** tushadi. O'qituvchining qo'l mehnati keskin kamayadi.

**Nima qilamiz:** har fan uchun savollar banki (sinflar bo'yicha), Excel/GIFT
dan savol import qilish, nazorat ishi va chorak nazorati uchun test shabloni.
Jurnaldagi "Nazorat ishi" bahosi test natijasidan avtomatik kelishi mumkin.

### 3-bosqich. O'zlashtirishni kuzatish

Moodle: Activity/Course completion, `report/progress`, `report/participation`.

Har material va topshiriq uchun "bajarildi" belgisi. Zavuch bitta jadvalda
kim qaysi mavzuni o'zlashtirgan-o'zlashtirmaganini ko'radi.

**Nima qilamiz:** completion'ni yoqish, kurs shablonida standart qoidalar,
zavuch uchun hisobot havolalari.

### 4-bosqich. Kompetensiya (DTS/KTP bog'lash)

Moodle: `competency` (Competency frameworks).

O'quv dasturidagi talablar ro'yxati tizimga kiritiladi va har mavzu/topshiriq
shularga bog'lanadi. Natija: "o'quvchi 7-sinf matematika bo'yicha qaysi
talabni o'zlashtirdi" degan savolga aniq javob.

**Nima qilamiz:** fanlar bo'yicha kompetensiya ro'yxati, mavzularni ularga
bog'lash. Bu KTP moduli bilan bir xil maqsad — lekin Moodle'ning o'zida.

### 5-bosqich. Motivatsiya

Moodle: `badges` (nishonlar), `h5pactivity` (interaktiv mashqlar).

Nishon avtomatik beriladi (masalan "chorakda 10 ta 5 baho"), o'quvchi
profilida ko'rinadi. H5P esa boshlang'ich sinflar uchun o'yin uslubidagi mashq.

### 6-bosqich. Teskari aloqa va tahlil

Moodle: `feedback` (so'rovnoma), `report/insights` (analitika), `choice`.

- O'quvchi/ota-ona so'rovnomasi: dars sifati haqida
- Moodle analitikasi: xavf ostidagi (ortda qolayotgan) o'quvchilarni OLDINDAN
  aniqlaydi — zavuch aralashishi uchun

### 7-bosqich. Hamkorlikdagi ish

Moodle: `forum`, `wiki`, `workshop`, `glossary`, `data`.

- `forum` — e'lonlar va muhokama (har kursda standart bor)
- `workshop` — o'quvchilar bir-birini baholaydi (loyiha ishlari uchun)
- `glossary` — atamalar lug'ati (til fanlari uchun juda mos)

### 8-bosqich. Masofaviy dars (kerak bo'lsa)

Moodle: `bigbluebuttonbn`, `lti`.

Karantin yoki ob-havo sababli masofaviy kunlar uchun. Alohida BBB server
talab qiladi — shuning uchun oxirida.

## Nimadan voz kechamiz

| Modul | Nega |
|---|---|
| `scorm`, `imscp` | Eskirgan standartlar, tayyor kurs paketlari uchun — bizda kerak emas |
| `lesson` | `book` + `quiz` birikmasi bir xil natijani soddaroq beradi |
| `data`, `wiki` | Yuqori sinflarda loyiha uchun foydali, lekin kundalik darsga ortiqcha |

## Qoida

Har bosqichda **avval Moodle'ning standart vositasi**, faqat u yetmaganda
plaginga kod yoziladi. Hozirgacha plaginga yozilganlar aynan shunday paydo
bo'lgan: Moodle'da dars jadvali, o'zbekcha jurnal va davomat tushunchasi yo'q.

---

# O'QITUVCHI PORTALI → YADRO XARITASI (2026-11-17)

> Prinsip: **UI bizniki — dvijok Moodle'niki.** Portalning har bir ekrani ortida
> maksimal darajada yadro mexanizmi ishlaydi; plagin faqat o'zbek maktabi
> tushunchalarini (sana×para, chorak, KTP, blok guruh) yadroga tarjima qiladi.
> Quyidagi ro'yxat taxmin emas — 2026-11-17 kuni o'rnatmaning o'zidan olingan
> (`mod/`, `blocks/`, `question/type/`, `mod/quiz/accessrule/`, `availability/condition/`,
> `filter/`, `admin/tool/`, `grade/`, `report/` kataloglar ro'yxati).

## A. Ekran → yadro mexanizmi xaritasi

| Portal ekrani | Ortida ishlaydigan yadro | Plaginda qoladigan yupqa qatlam |
|---|---|---|
| Ish stoli (bosh) | timeline/calendar ma'lumot API'lari, `assign.count_submissions_need_grading()`, kurs hodisalari | bugungi `_slot` ro'yxati + holat chiplari |
| Dars jadvali | — (yadroda analog yo'q) | `_timetable`/`_slot` to'liq plaginda, `memberclasses` blok katagi |
| Kokpit: UV fayllari | **mod_assign**: topshirish holati, PDF annotatsiya, kechikish, muddat uzaytirish (extension), ko'r baholash | hwstatus avto-taklif + 1–10 baho yozish |
| Kokpit: UV ovozli izoh | **TinyMCE** ichidagi audio/video yozish (RecordRTC) — assign feedback'da tayyor | — (sozlash kifoya) |
| Kokpit: materiallar | fayl ombori + `pluginfile`; 1-bosqichdagi `resource/page/folder/url/book`; **H5P + content bank** (interaktiv) | `_lessonres` sana↔section bog'i |
| Jurnal: baholar | **gradebook**: kategoriya, vazn, 1–10 **custom scale**, `grade/report/history` (baho tarixi — `_grade_log` ni to'ldiradi), `singleview` (bitta o'quvchi/ustun kiritish) | sana×o'quvchi setkasi, chorak formulasi |
| Baholash/CBT | **mod_quiz + qbank**: 15 savol turi (ddimageortext, ddmarker, ddwtos, gapselect, cloze, calculated, ordering...), **accessrule'lar: seb (Safe Exam Browser), ipaddress (faqat maktab tarmog'i/xonasi), password, openclosedate, timelimit, offlineattempts**; user/group override (individual qo'shimcha vaqt); **quiz statistics** (savol sifati: yengillik/diskriminatsiya indeksi) | `_exam` jadval-ustun bog'i, 30% qoplama |
| Topshiriqlar paneli | assign/quiz hisoblagichlari (hozir ham shunday) | kesma ro'yxat |
| O'quvchi 360 | **core_notes** (o'quvchi haqida qayd — PowerSchool Log Entries analogi yadroda bor!), `report/outline`, `report/participation`, gradebook overview | fanlararo jamlama + turniket |
| E'lonlar | **forum (Announcements, majburiy obuna)** yetkazish dvijogi sifatida ishlatilishi mumkin; message providers | o'qilganlik statistikasi (`_elon_seen`) |
| Muloqot | **core_message conversations** (hal qilingan, 4.4-reviziya) | `_chat` siyosat qatlami |
| Uch til | `lang/` + `get_string` (UI) + **filter/multilang** (KONTENT: mavzu/UV/e'lon matni `{mlang uz}...{mlang}` bilan 3 tilda!) + `tool_customlang` (atamalarni moslash) | — |
| Onboarding | **tool_usertours** — yangi UI ustida birinchi kirish ekskursiyasi, 3 tilda | — |

## B. Aytilmagan, lekin o'rnatmada tayyor turgan imkoniyatlar

Baho: ✅ darhol yoqamiz · ⏳ keyingi bosqich · ✖ hozircha kerak emas.

| # | Imkoniyat (o'rnatmadagi nomi) | Nima beradi | Baho |
|---|---|---|---|
| 1 | `filter/multilang` | Mavzu, UV, e'lon **matnlari** 3 tilda — til almashganda kontent ham almashadi | ✅ (uch til qoidasining ikkinchi yarmi) |
| 2 | TinyMCE audio/video yozish | O'qituvchi UVga **ovozli izoh** yozadi (Canvas/Toddle naqshi) — kod yozilmaydi | ✅ |
| 3 | `quizaccess_seb` + `ipaddress` | CBT qattiqlashtirish: Safe Exam Browser va faqat maktab IP'sidan ishlash | ✅ (CBT bosqichi bilan) |
| 4 | Quiz **statistics** hisoboti | Har savolning sifati — koordinator uchun imtihon tahlili (MasteryConnect o'rnini bosadi) | ✅ (CBT bilan) |
| 5 | `grade/report/history` | Baho o'zgarishlari yadro tarixi — `_grade_log` bilan birga to'liq audit | ✅ |
| 6 | Rubrika + baholash yo'riqnomasi (`grade/grading/form/rubric`, `guide`) | Insho/loyiha UVlarini mezonlar bo'yicha baholash | ✅ |
| 7 | Assign **marking workflow** | Baholar «tayyor bo'lguncha» yashirin, bir vaqtda e'lon qilinadi | ⏳ |
| 8 | `availability/condition/group` + grouping | Blok daraja guruhlariga **turli material/UV** — High'ga bir topshiriq, Low'ga boshqa | ✅ (blok fanlar uchun ayni kerak) |
| 9 | Activity completion + `report/progress` | «Material ko'rildi/bajarildi» belgisi, zavuch jadvali | ⏳ (3-bosqich, o'z o'rnida) |
| 10 | `mod_choice` | Bir bosimli so'rov: majlisga kelasizmi (RSVP), sinf ovozi | ✅ (majlis e'loniga ulanadi) |
| 11 | `mod_feedback` | Anonim so'rovnoma: ota-ona/o'quvchi qoniqishi — murojaat moduli bilan juft | ⏳ |
| 12 | `mod_h5pactivity` + content bank | Interaktiv qo'llanmalar (siz so'ragan!) — natijasi avtomatik gradebook'ga | ✅ (materiallar bosqichida) |
| 13 | `core_notes` + `block_mentees` | O'quvchi haqida qayd (sayt/kurs/shaxsiy daraja); mentees — kelgusi ota-ona akkaunti poydevori | ⏳ (sharh bor; 360 ga qo'shimcha) |
| 14 | Badges (nishonlar) | «Chorakda 10 ta a'lo» kabi avtomatik rag'bat — o'quvchi motivatsiyasi | ⏳ |
| 15 | `tool_monitor` | Kodsiz hodisa-qoidalari: «muddatdan keyin topshirdi → o'qituvchiga xabar», «baho o'zgardi → zavuchga» | ✅ |
| 16 | `core_reportbuilder` | Zavuch/direktor uchun kodsiz maxsus hisobotlar + jadval bo'yicha emailga yuborish | ⏳ |
| 17 | `report/participation` | Faollik hisoboti + «qatnashmaganlarga xabar yuborish» (Canvas "Message students who" yadroda bor) | ✅ |
| 18 | `admin/tool/analytics` + `report/insights` | Ortda qolish xavfi bashorati — zavuch oldindan aralashadi | ⏳ (ma'lumot yig'ilgach) |
| 19 | `tool_usertours` | Yangi portal ustida interaktiv yo'riqnoma (3 tilda) — o'qitish xarajatini tushiradi | ✅ (har bosqich deployida) |
| 20 | `tool_policy` | Rozilik hujjatlari: dars audiosi yozilishiga ota-ona roziligi — birinchi kirishda tasdiq | ✅ (audio modul uchun muhim) |
| 21 | `tool_mfa` | Rahbariyat akkauntlariga ikki bosqichli kirish | ✅ (xavfsizlik) |
| 22 | `tool_recyclebin` | O'chirilgan topshiriq/kursni tiklash — «o'chirib yubordim» hodisalariga sug'urta | ✅ (shunchaki yoqiladi) |
| 23 | `mod_subsection` (5.x yangiligi) | Ichma-ich bo'limlar — mavzu-section tuzilmasini toza qiladi | ⏳ (1-bosqich bilan) |
| 24 | Kurs nusxalash/import | O'tgan yil kursidan material/testlarni yangi yilga ko'chirish | ✅ (yil almashinuvida) |
| 25 | `tool_lpimportcsv` (kompetensiya CSV) | KTP/DTS talablarini CSV'dan framework qilib yuklash — 4-bosqichni tezlashtiradi | ⏳ |
| 26 | `quizaccess_offlineattempts` | Mobil ilovada internetsiz test (zaxira stsenariy) | ✖ hozircha |
| 27 | `mod_lti`, `oauth2` | Tashqi vositalar (GeoGebra...), Google orqali kirish | ✖ hozircha |
| 28 | Global search (`block_globalsearch`) | Butun kontent bo'ylab qidiruv — qidiruv dvijoki sozlashni talab qiladi | ✖ hozircha |

## C. Baribir plaginda qoladiganlar (yadroda analogi yo'q — tekshirildi)

Sana×para jadvali va slot/fakt qatlami · o'quvchilar×sanalar jurnali · chorak (FB/BSB/CHSB)
formulasi va 30% qoplama · KTP↔sana biriktirish · blok guruh katagi · davomat (para darajasida) ·
turniket · oylik · murojaat · sharh indeksi · e'lon o'qilganlik statistikasi · `_chat` siyosati ·
audio+AI tahlil. Bular aynan «Moodle'da yo'q» ro'yxati — plagin shu yerda yashaydi, qolgani yadroda.

## D. Yoqish tartibi (portal bosqichlariga bog'langan)

| Portal bosqichi | Birga yoqiladigan yadro imkoniyatlari |
|---|---|
| 1 (jurnal) | grade history, custom scale 1–10, singleview havolasi |
| 2 (kokpit) | TinyMCE ovozli izoh, rubrika/guide, H5P + content bank, multilang filtr, recyclebin, policy (audio rozilik) |
| 3 (ish stoli) | tool_monitor qoidalari, report/participation havolalari, usertours |
| 4 (CBT) | seb + ipaddress + password accessrule'lar, quiz statistics, group/user override |
| 5 (aloqa) | choice (RSVP), mentees ko'rib chiqish, mfa (rahbariyat) |
| 6 (360) | core_notes integratsiyasi, reportbuilder shablonlari, completion/progress |
| keyin | kompetensiya (lpimportcsv), badges, feedback so'rovnomasi, analytics/insights |

---

# BUTUN PLATFORMA EKOTIZIMI (2026-11-17 veb-tadqiqoti)

> Lokal o'rnatmadan tashqari: yadroning yangi yo'nalishlari (4.5→5.3), rasmiy
> plaginlar katalogi (~2000), rasmiy ilova/xizmatlar. Belgi: [yadro] / [bepul] / [pullik].

## E. Yadroning biz bilmagan yo'nalishlari

| Imkoniyat | Mohiyati | Biz uchun |
|---|---|---|
| **AI quyi tizimi** (4.5+, 5.3 da yetuk) | Provider×Placement arxitekturasi. 5.2+ da **Gemini YADRO provideri** (`aiprovider_gemini`), 5.3 da Claude ham. Placementlar: TinyMCE'da matn/rasm generatsiya, kurs sahifasida Summarise/Explain. Rozilik siyosati, limitlar, loglar tayyor. | **YUQORI** — mavjud Gemini kalitimiz bilan barcha o'qituvchiga editor-AI «bepul» yoqiladi. Audio-tahlil pipeline'imiz custom qoladi; keyin uni custom placement sifatida o'rash mumkin |
| **mod_qbank / umumiy savol banklari** (5.0+) | Bank — mustaqil obyekt, kurslararo ulashiladi. 5.0 ga ko'tarilishda bank-migratsiya taski og'riqli nuqta | **YUQORI** — fan bo'yicha maktab banki (blok guruhlarga umumiy). CBT bosqichida shu modelga quramiz |
| **Yangi REST API** (5.1+ routing, 5.3 OAuth2/PAT) | Klassik WS token o'rniga zamonaviy REST | **YUQORI** (kuzatamiz) — Frappe sinxronini bosqichma-bosqich ko'chirish mumkin |
| **communication_matrix** (4.2+) | Kurs chatlari tashqi Matrix (Synapse+Element) xonalarida; HQ strategik yo'nalishi, core messaging sekin susaytirilmoqda | **QAROR 2026-11-17**: hozircha **core messaging'da qolamiz** (4.4-reviziya) — bugungi ehtiyojni infra xarajatisiz yopadi; Matrix — kelgusi nomzod, `_chat` sxemasi unga ko'chishga to'siq qo'ymaydi |
| SMS quyi tizimi (4.5+) | `smsgateway_*` plagin turi; customapi orqali Eskiz/Play Mobile ulanishi real | O'RTA — ota-ona SMS kanali keyin |
| Gradebook penalty (5.0) | Kechikkan topshiriqqa avtomatik ball ayirish qoidasi | O'RTA |
| MoodleNet | **Xizmat 2026-aprelda YOPILDI** | E'tiborsiz |

## F. Plaginlar katalogi — bizga tegishlilari

| Plagin | Nima beradi | Holat | Biz uchun |
|---|---|---|---|
| **theme_boost_union** | Boost ustki mavzusi: brending, **Smart Menus** (rol/kohortaga qarab dinamik menyu — portal navigatsiyasi kodsiz!), banner, statik sahifalar | bepul, a'lo | **1-o'rin** — birinchi o'rnatiladigan |
| **qtype_stack** | Simvolik matematika: o'quvchi algebraik javob yozadi, Maxima ekvivalentlikni tekshiradi, tasodifiylash | bepul, 5.3 gacha | **YUQORI** — MATH High/Middle/Low uchun o'yin o'zgartiruvchi |
| **block_xp (Level Up)** | Ball/daraja/peshqadamlik — K-12 motivatsiya | bepul+pullik qism | **YUQORI** |
| **tool_trigger** | Hodisa→filtr→HTTP POST: kodsiz webhook dvijoki (Moodle hodisalarini Frappe'ga oqizish) | bepul (Catalyst) | **YUQORI** — custom observer kodini qisqartiradi |
| **mod_interactivevideo** | Video ustiga savol/H5P/matn qadash + kim qancha ko'rganini hisobot | bepul, 5.3 | **YUQORI** — yozib olingan darslarning o'quvchiga «ikkinchi hayoti» |
| **message_telegram** | Moodle xabarnomalari Telegram'ga (2025 da jonlantirilgan fork) | bepul, kichik jamoa | **YUQORI** (O'zbekiston = Telegram) — forkni sinab yoki o'z `message_*` outputimizni yozamiz (API kichik) |
| **local_recompletion** | Yillik avto-reset: completion/baholarni tozalab arxivlash | bepul (D. Marsden) | **YUQORI** — o'quv yili almashinuvi |
| **mod_scheduler** | Slot e'lon qilish → bron (ota-ona majlislari) | bepul, 5.2 | YUQORI — ota-ona akkaunti bo'lgach |
| **mod_customcert** | Dizaynli sertifikat + QR tekshiruv, avto-berish | bepul, 5.3 | YUQORI — xususiy maktab uchun ko'rkam qiymat |
| **block_configurable_reports / report_customsql** | SQL va konstruktorli hisobotlar | bepul, sog'lom | YUQORI (adminlarga) |
| block_completion_progress | Sinf bo'ylab progress-setka | bepul | O'RTA |
| format_tiles | Plitka ko'rinishli kurs sahifasi (boshlang'ich sinflarga) | bepul | O'RTA |
| qtype_coderunner | Dasturlash savollari (Jobe sandbox) | bepul, a'lo | O'RTA (informatika) |
| quizaccess_proctoring | Vebkamera-snapshot nazorati | bepul, o'rtacha | PAST (SEB yetadi) |
| mod_attendance | Standart davomat (ilova qo'llab-quvvati bilan) | bepul, a'lo | **PAST** — jurnalimiz manba haqiqat; parallel haqiqat ochmaymiz (7.7 qarori tasdiqlandi) |
| **OTA-ONA NAQSHI** | Yadroda «ota-ona akkaunti» YO'Q; standart yo'l: Parent roli **bola user-kontekstida** + block_mentees + sinxron orqali biriktirish. Katalogda bundan yaxshisi yo'q — bizning sahifalar UI bo'lib qoladi | yadro | **YUQORI** — kelgusi ota-ona bosqichining poydevori (Frappe'dan rol biriktirish, unikal ID qoidasi) |

**Ishlatmaymiz:** MoodleNet (o'lik) · mod_hvp (5.x da ishlamaydi — faqat core h5pactivity!) ·
local_boostnavigation, local_webhooks (tashlandiq) · MoodleCloud (custom plagin taqiqlangan) ·
Moodle Workplace (Frappe qatlamimizni litsenziya puliga takrorlaydi; faqat ko'p-filial bo'lsak qaytamiz) ·
tijorat proktoring (imtihonlar maktabda — SEB yetadi).

## G. Rasmiy ilova va push — MUHIM OGOHLANTIRISH

- Rasmiy Moodle App bepul tarifida **push 50 ta qurilma bilan cheklangan** (butun sayt!) — bizda 420+ akkaunt,
  ya'ni limitdan allaqachon oshganmiz. Yo **App Premium** (~$550/yil, cheksiz push), yo **Telegram-asosiy kanal**.
  Tavsiya: Telegram-birinchi + Premium'ni byudjetga kiritish.
- Branded App (o'z nomimizdagi ilova) — Premium ustiga, so'rov bo'yicha narx; ota-ona bosqichida qaytamiz.
- O'qituvchi rasmiy ilovada: baholash (offlayn ham), topshiriqlar, forum/xabarlar, bizning Ionic panellar.

## H. Ekotizim bo'yicha yakuniy yoqish tartibi (ustuvorlik)

1-to'lqin (portal 2–3-bosqichi bilan): **boost_union**, core AI (Gemini provider), H5P content bank, usertours.
2-to'lqin (CBT bilan): umumiy savol banklari, **qtype_stack** (+Maxima server), SEB, quiz statistics.
3-to'lqin (aloqa bilan): **message_telegram** (yoki o'z outputimiz), push strategiyasi qarori, mod_customcert.
4-to'lqin: tool_trigger (Frappe'ga hodisa oqimi), local_recompletion (yil yakunida), block_xp, mod_interactivevideo.
Ota-ona bosqichi: Parent rol + mentees + mod_scheduler.
