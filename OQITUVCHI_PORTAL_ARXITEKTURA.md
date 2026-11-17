# O'QITUVCHI PORTALI — TO'LIQ ARXITEKTURA

Sana: 2026-11-16. Holat: **arxitektura** (tasdiqlangandan keyin bosqichma-bosqich quriladi).
Manba: 5 jahon platformasi tadqiqoti (PowerSchool/Schoology, Canvas, Google Classroom, Toddle, Kundelik/Dnevnik.ru) + `local_targetzenit` 0.65.0 to'liq inventarizatsiyasi.

Bog'liq hujjatlar: `DIREKTOR_PANELI.md`, `BAHOLASH_ARXITEKTURA.md`, `SHARH_ARXITEKTURA.md`,
`MUROJAAT_ARXITEKTURA.md`, `HISOBOT_ARXITEKTURA.md`, `AI_TAHLIL_KATEGORIYALARI.md`,
`MOBIL_ILOVA_ARXITEKTURA.md`, `MOODLE_IMKONIYATLAR.md`,
Frappe tomonda: `OQITUVCHI_ISH_STOLI_ARXITEKTURA.md`, `OQUV_REJA_ARXITEKTURA.md`, `DARS_JADVALI_ARXITEKTURA.md`.

---

## 1. JAHON TADQIQOTI XULOSASI

### 1.1 Beshta platforma taqqoslash (o'qituvchi tomoni)

Belgilar: ✓ to'liq, ◐ qisman, ✗ yo'q.
PS = PowerSchool+Schoology · CV = Canvas · GC = Google Classroom · TD = Toddle · KD = Kundelik/Dnevnik.

| # | Imkoniyat | PS | CV | GC | TD | KD | Etalon |
|---|---|---|---|---|---|---|---|
| 1 | Kirganda bugungi dars jadvali | ✓ | ◐ | ✗ | ✓ | ✓ | KD: jadval = bosh ekran |
| 2 | Yagona to-do ("tekshirilmagan N ta ish") | ◐ | ✓ | ◐ | ◐ | ✗ | CV: To Do → SpeedGrader |
| 3 | "Davomat qilinmadi" eslatmasi | ◐ | ✗ | ✗ | ✓ | ◐ | TD: avtomatik eslatma |
| 4 | Dars sahifasi: mavzu+UV+davomat+baho bitta joyda | ◐ | ◐ | ◐ | ✓ | ✓ | KD: dars sahifasi = jurnal katagi |
| 5 | Chorak setkasi: o'quvchilar × dars sanalari | ◐ | ◐ | ◐ | ◐ | ✓ | KD: G'arb LMS'larida umuman yo'q |
| 6 | Imtihon = jurnalda alohida turdagi ustun | ✓ | ✓ | ◐ | ◐ | ✓ | KD: СОР/СОЧ naqshi |
| 7 | O'rtacha + taklif qilingan chorak bahosi | ✓ | ✓ | ◐ | ◐ | ✓ | KD + PS formulalar |
| 8 | Topshiriq: kim topshirdi/topshirmadi, fayl, baho | ✓ | ✓ | ✓ | ✓ | ◐ | CV: SpeedGrader aylanasi |
| 9 | CBT: savol banki, avtotekshiruv, moslashuvlar | ✓ | ✓ | ◐ | ◐ | ◐ | CV: New Quizzes + Item Banks |
| 10 | E'lon → ota-onaga yetadi, o'qilganlik belgisi | ✓ | ✓ | ◐ | ✓ | ✓ | TD: oila emailigacha + read receipt |
| 11 | Ota-ona uchrashuvini band qilish (slotlar) | ◐ | ✓ | ◐ | ◐ | ✗ | CV Scheduler / ManageBac 3-way |
| 12 | O'quvchi 360: barcha fanlardan baholar, davomat | ✓ | ◐ | ◐ | ✓ | ◐ | PS: Quick Lookup |
| 13 | Xulq qaydnomasi ota-onaga, o'qilganlik bilan | ◐ | ✗ | ✗ | ✓ | ✓ | KD: замечание |
| 14 | Butun maktab jadvalini ko'rish (o'qituvchi) | ✗ | ✗ | ✗ | ◐ | ✗ | **Hech kimda yo'q — bizda bor** |
| 15 | KTP: yillik mavzu rejasi darslarni avto-to'ldiradi | ◐ | ◐ | ✗ | ✓ | ✓ | KD: КТП + Excel import |
| 16 | O'qituvchining o'z tahlili/reytingi | ✗ | ◐ | ◐ | ◐ | ✗ | **Bizda bor (norm-nisbiy)** |
| 17 | Oylik ma'lumoti portalda | ✗ | ✗ | ✗ | ✗ | ✗ | **Faqat bizda (Mening oyligim)** |
| 18 | Dars yozuvini AI tahlil qilish | ✗ | ✗ | ✗ | ✗ | ✗ | **Faqat bizda** |

**Asosiy xulosa.** Jahon standarti = to'rt etalon yig'indisi:
- **Dnevnik/Kundelik** — jurnal mexanikasi (dars sahifasi, chorak setkasi, KTP). Bizning madaniyatdagi "jurnal" tushunchasini faqat shu beradi; biz uni `_slot`/`_lesson`/`_record` qatlamida to'g'ri qurganmiz.
- **Canvas** — baholash ergonomikasi (To Do, SpeedGrader aylanasi, "topshirmaganlarga xabar", imtihonda individual qo'shimcha vaqt = Moodle'dagi user override).
- **PowerSchool** — o'quvchi 360 (Quick Lookup: barcha fanlar baholari + davomat + xulq qaydlari bitta kartada).
- **Toddle** — ota-ona aloqasi + AI (e'lonlar o'qilganlik belgisi bilan, dalilga tayangan AI xarakteristika).

14, 16, 17, 18-qatorlar — bizning farqlovchi ustunliklarimiz, jahonda analogi yo'q. Ularni saqlaymiz va chuqurlashtiramiz.

### 1.2 Moodle yadrosi chegarasi (nimani qurmaymiz)

| Soha | Yechim | Izoh |
|---|---|---|
| Topshiriq oqimi (fayl, holat, annotatsiya, rubrika) | **core `mod_assign`** | `homework.php` allaqachon yaratadi; SpeedGrader ≈ assign grading sahifasi |
| CBT (savol turlari, bank, avtotekshiruv) | **core `mod_quiz` + question bank** | Kategoriya kontekstidagi bank = Canvas Item Banks; user/group override = Accommodations |
| Baholar agregatsiyasi | **core gradebook** | `gradebook.php` allaqachon kurs→chorak kategoriyasi→dars elementi quradi |
| Sharh matni | **core `{comments}`** | `SHARH_ARXITEKTURA.md` qarori, o'zgarmaydi |
| Xabarnomalar | **core message providers** | `feedback`/`sharh`/`murojaat` providerlar ishlaydi; yangilari qo'shiladi |
| Video uchrashuv | **core BigBlueButton** (4.0+ yadroda) | Schoology/Canvas ham aynan shu dvijokni ishlatadi |
| Fayllar | **core file storage + pluginfile** | `audio`/`material` naqshi davom etadi |

Moodle'da tuzilmaviy **yo'q** narsalar (shuning uchun custom): rejalashtirilgan dars tushunchasi (sana×para), o'quvchilar×sanalar jurnali, chorak, KTP, davomat↔jadval bog'i, ota-ona qatlami, turniket, oylik, AI tahlil. Bularning barchasi plugin qatlamida — to'g'ri qaror, davom etadi.

---

## 2. TALABLAR XARITASI (foydalanuvchi talabi → holat → yechim)

| # | Talab | Holat | Qayerda / nima yetishmaydi |
|---|---|---|---|
| 1 | Kirganda bugungi jadval + kutilayotgan ishlar | **QISMAN** | `index.php?view=today` + `oqituvchi.php?filter=nuqson` bor, lekin yagona ish stoli ekrani yo'q → **5A** |
| 2 | Darsga kirish, ovozli yozuv tugmasi | **BOR** | `dars.php` → `rec.php` (alohida oyna), chunked upload, Gemini tahlil |
| 3 | O'quvchilar ro'yxati, baholash, yo'qlama | **BOR** | `dars.php` + `_record` (present/absent/late/excused, 1–10 baho, izoh) |
| 4 | Bugungi mavzu, uyga vazifa | **BOR** | `_lesson.topic/homework`; mavzu KTP'dan (`_slot.mavzu`) keladi |
| 5 | Slayd/video/interaktiv qo'llanma yuklash | **QISMAN** | `material.php` faqat **1 fayl ≤10MB** (AI uchun doska materiali) → ko'p faylli dars resurslari kerak → **5B** |
| 6 | Hammasi o'quvchiga ko'rinishi (baho, fayl, mavzu, UV) | **QISMAN** | `baholarim.php` baho/mavzu/UV ni ko'rsatadi; materiallar ko'rinmaydi → **5B** |
| 7 | Chorak tepadan ko'rinishi: barcha baholar + mavzular + UV, o'tilgan/o'tiladigan | **QISMAN** | `jurnal.php` setkasi bor; imtihon ustunlari va KTP qamrov paneli yo'q → **5C** |
| 8 | Imtihon: kun belgilash, computer-based, turlari, baholash, jurnalda "imtihon" | **QISMAN** | `baholash.php` (jadval tuzish, ball setka, Excel import, 30% qoplama) bor; **CBT yo'q** (ustunlar rezerv), **jurnalda imtihon ustuni yo'q** (`exam::journal_columns()` o'lik kod) → **5C + 5D** |
| 9 | Butun maktab jadvalini ko'rish, begona darsga kira olmaslik | **BOR** | `index.php?view=all` (`viewalltimetable`) + `teach` kurs kontekstida — begona kursda yozolmaydi |
| 10 | O'quvchiga comment yozish | **BOR** | `sharh.php` (5 kategoriya, ochiq/maxfiy, o'qilganlik) |
| 11 | Moodle'da guruh ochish | **QAROR** | Akademik guruhlar **faqat Frappe'da** tug'iladi (unikal ID qoidasi). Kurs ichida tabaqalash uchun core groups → **7.1** |
| 12 | Ota-onalar majlisi e'loni: bitta yozuv → hamma biladi | **YO'Q** | → **5E (E'lonlar)** |
| 13 | Ota-ona uchrashuv so'rovi, direktor/zavuch xabardor, vaqt kelishiladi | **YO'Q** | → **5F (Uchrashuvlar)** |
| 14 | Guruhga yangilik/ma'lumot yuborish | **YO'Q** | → **5E** |
| 15 | Turniket kirdi-chiqdi: o'qituvchi va o'quvchi | **QISMAN** | Xodimniki `_payroll.kunlarjson` ichida bor; **o'quvchiniki Moodle'ga umuman kelmaydi** → **5G** |
| 16 | O'qituvchi oyligi (Frappe'dan, tafsilotlari bilan) | **BOR** | `oylik.php` + `_payroll`/`_settle`, Face-ID vaqtlari bilan |
| 17 | O'quvchi holati: boshqa fanlardagi baholari | **QISMAN** | `hisobot.php`/`baholarim.php` bor, lekin o'qituvchi uchun tezkor 360 karta yo'q → **5H** |
| 18 | O'zining maktabdagi reytingi | **BOR** | `oqituvchi.php` (norm-nisbiy ball, trend, kategoriya profili) |
| 19 | Yozib olingan darslar tahlili: ovoz, matn, xulosa | **BOR** | `_audio` (transcript, analysis JSON, i18n), `views/oq_yozuvlar.php` |
| 20 | UV: kim bajardi/bajarmadi, fayllari, har birini baholash | **QISMAN** | `topshiriqlar.php` (faqat ko'rish, chuqur havola); kokpitda o'quvchi-o'quvchi UV tekshiruvi yo'q (ISH_STOLI qarori qurilmagan) → **5I** |

**Natija: 8 ta BOR, 8 ta QISMAN, 3 ta YO'Q, 1 ta qaror.** Yangi qurilish 9 modulga jamlanadi (5A–5I).

---

## 3. UMUMIY ARXITEKTURA CHIZMASI

```
┌─────────────────────── FRAPPE (manba haqiqat) ───────────────────────┐
│ Student/Employee/Instructor   Jadval Versiyasi   Oquv Reja (KTP)     │
│ Student Group (guruhlar)      Academic Term/Holiday                  │
│ Oylik Tabel + Terminal Checkin (Face-ID 6x Hikvision)   Murojaat     │
└───┬──────────┬───────────┬──────────┬──────────┬───────────┬─────────┘
    │users     │courses    │timetable │mavzular  │payroll    │checkin(YANGI)
    │07:00     │07:10      │07:20     │07:25     │07:30      │07:35     murojaat */15
    ▼          ▼           ▼          ▼          ▼           ▼
┌────────────────── MOODLE WS: "Frappe Sync" (:push) ──────────────────┐
│ core_user_* core_course_* enrol_manual │ local_targetzenit_push_*    │
└──────────────────────────────┬────────────────────────────────────────┘
                               ▼
┌────────────── PLUGIN MA'LUMOT QATLAMI (30 jadval: 24+6) ─────────────┐
│ REJA:  _timetable _calendar _mavzu ──slot_build 04:00──► _slot       │
│ FAKT:  _lesson _record(+hw) _lessonres* _audio _aicat                │
│ TAHLIL:_staff _fact _norm _tutor _feedback  (fact_refresh */15)      │
│ BAHO:  _exam _exam_sitting _exam_result _term_score _grade_log       │
│ ALOQA: _sharh _murojaat(3x) _elon* _elon_seen* _chat* _uchrashuv*    │
│ TASHQI:_payroll _settle _checkin*                   (* = yangi)      │
└───────┬──────────────────────────────────────────────────────────────┘
        │                    MOODLE YADRO (qurmaymiz, ishlatamiz)
        │  gradebook ◄─gradebook.php    mod_assign ◄─homework.php
        │  mod_quiz+qbank ◄─exam.php(5D)  {comments} files messages BBB
        ▼
┌────────────── O'QITUVCHI SAHIFALARI ─────────┬─── BOSHQA ROLLAR ─────┐
│ bosh.php*(ish stoli: bugun+to-do)            │ O'quvchi/ota-ona:     │
│ index.php(jadval) dars.php(kokpit)           │  baholarim darsim*    │
│ rec.php(yozuv) jurnal.php(+imtihon ustuni)   │  sharhlarim murojaatim│
│ baholash.php(+CBT) topshiriqlar.php          │  elonlarim* hisobot   │
│ sharh.php elon.php* muloqot.php*             │ Direktor/zavuch:      │
│ oquvchi360.php* oylik.php oqituvchi.php      │  direktor.php sharhlar│
└──────────────────────┬───────────────────────┴───────────────────────┘
                       ▼
            MOBIL ILOVA (Ionic panellar): 7 mavjud + elon/uchrashuv
```

Oqim qoidalari (o'zgarmaydi):
- **Frappe → Moodle faqat push**; Moodle Frappe'ni chaqirmaydi (token faqat Frappe tomonda). Yagona istisno — murojaat `pull` modeli (Frappe o'zi `get_murojaat` ni chaqiradi).
- **Bog'lanish faqat o'zgarmas ID** orqali: `Student.name`/`Employee ID` → `user.idnumber`, `yil::guruh::fan` → `course.idnumber` (unikal ID qoidasi).
- **Egalik taqsimoti**: reja (jadval, KTP, guruhlar, oylik) — Frappe; bajarilish (dars, baho, davomat, yozuv, aloqa) — Moodle.

---

## 4. YANGI MA'LUMOTLAR SXEMASI

Barcha jadvallar `local_targetzenit_` prefiksi bilan, `db/install.xml` + `upgrade.php` orqali.

### 4.1 `_lessonres` — dars resurslari (5B)

Doska materiali (`lessonboard`, 1 fayl, AI o'qiydi) **saqlanadi**, bu alohida tushuncha:
dars uchun tayyorlangan slayd/video/havolalar, o'quvchiga ko'rinadi.

| Ustun | Turi | Izoh |
|---|---|---|
| id | PK | |
| lessonid | FK → _lesson | indeks |
| turi | char(10) | `fayl` \| `havola` |
| url | char(1333) null | havola uchun (YouTube, interaktiv qo'llanma) |
| title | char(255) | ko'rsatiladigan nom |
| tartib | int | saralash |
| koringan | int(1) | 1 = o'quvchiga ochiq (qoralama holat uchun 0) |
| userid, timecreated | | kim, qachon |

Fayllar: filearea `lessonres`, itemid = lessonres.id, kurs konteksti. Limitlar: dars boshiga ≤10 obyekt,
fayl ≤100MB (video uchun), jami ≤300MB. MIME: pdf, pptx/ppt, docx, png/jpg/webp, mp4/webm, mp3. 
Serving: `local_targetzenit_pluginfile` — enrol tekshiruvi (o'z kursining o'quvchisi yoki `teach`/`viewanalysis`).
AI bog'i: pdf/rasm turlari xohishga ko'ra `ai::material_tavsifi()` ga doska materiali qatori qo'shimcha dalil bo'lib beriladi (2-bosqichda).

### 4.2 `_record` kengaytmasi — uy vazifa tekshiruvi (5I)

`OQITUVCHI_ISH_STOLI_ARXITEKTURA.md` dagi tasdiqlangan qaror nihoyat quriladi:

| Yangi ustun | Turi | Izoh |
|---|---|---|
| hwstatus | char(10) null | `bajardi` \| `qisman` \| `bajarmadi` — **o'tgan darsda berilgan** UV bo'yicha |
| hwgrade | int null | 1–10, UV bahosi (kunlik bahodan alohida) |
| latemin | int null | kechikish daqiqasi (ISH_STOLI qarori: N kechikish = 1 yo'qlik — hisob `_fact` da) |

mod_assign bog'i: dars uchun `assigncmid` mavjud bo'lsa, kokpit `assign_submission` holatini o'qiydi va
`hwstatus` ni avtomatik taklif qiladi (topshirdi→`bajardi`); o'qituvchi qo'lda o'zgartira oladi.
Fayl topshiriqlarni baholash core assign sahifasida qoladi (chuqur havola), baho assign'ning o'z grade itemiga tushadi.

### 4.3 `_elon` + `_elon_seen` — e'lonlar (5E)

| `_elon` ustuni | Turi | Izoh |
|---|---|---|
| id, muallifuserid, timecreated | | |
| scope | char(10) | `maktab` \| `guruh` \| `kurs` |
| scopekey | char(100) | guruh kaliti yoki courseid; maktab uchun bo'sh |
| auditoriya | char(10) | `oquvchi` \| `otaona` \| `hamma` (ota-ona = farzand akkaunti, 7.2) |
| turi | char(10) | `oddiy` \| `muhim` \| `majlis` |
| sarlavha, matn | char(255), text | |
| sana, vaqt, xona | null | `majlis` turi uchun: qachon, qayerda |
| uchrashuvid | FK null | majlis e'loni `_uchrashuv` yozuviga bog'lanadi |
| holat | char(10) | `qoralama` \| `chiqdi` \| `bekor` |

`_elon_seen`: (elonid, userid, seenat) — Toddle naqshi: muallif "N tadan M tasi o'qidi" ni ko'radi.
Yetkazish: yangi message provider `elon` (push + web) auditoriyaning har bir useriga; mobil panel `tzelon`.
Huquq: o'qituvchi — o'zi dars beradigan guruhlarga; zavuch/direktor (`viewteachers`) — maktab bo'ylab.

### 4.4 Muloqot (chat) + uchrashuvlar (5F) — REVIZIYA 2026-11-17

Dastlabki loyihadagi "uchrashuv-yozishma" modeli to'laqonli **chat moduliga** kengaytirildi (foydalanuvchi qarori):
o'qituvchi ota-onalar va hamkasblar bilan messenjer ko'rinishida yozishadi, guruh chatlari ochadi va boshqaradi.

**Poydevor qarori: Moodle yadrosining message conversations API'si** (`\core_message\api`, individual + group
suhbatlar) ustiga quriladi — xabar saqlash, o'qilganlik, push va **mobil ilovaning tayyor messenjer ekrani**
bepul keladi; custom xabar jadvali yozilmaydi. Plagin faqat boshqaruv qatlamini qo'shadi:

| `_chat` ustuni | Turi | Izoh |
|---|---|---|
| id, egauserid, timecreated | | ega = chatni ochgan o'qituvchi (boshqaruvchi) |
| conversationid | FK core | yadro suhbatiga 1:1 bog' |
| turi | char(12) | `sinf_otaona` \| `kafedra` \| `erkin` \| `shaxsiy` |
| groupkey | char(100) null | biriktirilgan sinf/guruh: yangi o'quvchi qo'shilsa ota-onasi (farzand akkaunti) **avto-taklif**, chiqsa avto-chiqariladi |
| yozish | char(10) | `hamma` \| `faqat_ega` (e'lon rejimi) |
| korinish | char(12) | `azolar` \| `azolar_rahbariyat` (zavuch/direktor o'qiy oladi) |

A'zolikni ega boshqaradi: qo'shish/chiqarish (yadro suhbat a'zoligi orqali), faqat ega sozlamalarni o'zgartiradi.

`_uchrashuv` saqlanadi, lekin **chat ipiga bog'lanadi** (`conversationid` ustuni qo'shiladi); `_uchrashuv_izoh`
BEKOR — vaqt kelishuvi endi chatning o'zida. Uchrashuv chatda **karta** ko'rinishida yashaydi:

| `_uchrashuv` ustuni | Turi | Izoh |
|---|---|---|
| id, timecreated, timemodified | | |
| conversationid | FK core | qaysi chat ichida |
| turi | char(12) | `majlis` (guruh yig'ilishi) \| `individual` |
| tashabbus | char(10) | `oqituvchi` \| `otaona` (farzand akkaunti orqali) \| `rahbariyat` |
| teacheruserid, studentuserid, groupkey | | kim bilan / qaysi farzand / qaysi guruh |
| maqsad | text | nima haqida |
| sana, vaqt, xona | null | taklif → tasdiqlangach yakuniy |
| holat | char(12) | `soralgan` → `taklif` → `kelishildi` → `otkazildi` / `bekor` |

Oqimlar:
- **Ota-ona → individual**: farzand akkauntidan shaxsiy chatda yozadi yoki "uchrashuv so'rash" bosadi →
  o'qituvchi **va** zavuch/direktorga push (talab #13) → o'qituvchi chat ichida 📅 karta bilan vaqt taklif qiladi →
  ota-ona tasdiqlaydi → `kelishildi`: ikkala tomonga push + o'qituvchi core kalendariga event.
- **O'qituvchi → majlis**: `majlis` turidagi `_elon` (5E) chiqaradi → `_uchrashuv` yoziladi → sinf chatida
  karta paydo bo'ladi. Bitta yozuv — hamma biladi (talab #12).
- Rahbariyat barcha uchrashuvlarni lenta ko'rinishida, `korinish=azolar_rahbariyat` chatlarni o'qish rejimida ko'radi.

E'lon (5E) alohida qoladi: e'lon — bir tomonlama rasmiy xabar (o'qilganlik statistikasi bilan), chat — ikki
tomonlama suhbat. Vaqt: Asia/Tashkent. Slot-bron (Canvas Scheduler) — ota-ona akkaunti paydo bo'lgandagi bosqich.

### 4.5 `_checkin` — turniket kunlik xulosasi (5G)

| Ustun | Turi | Izoh |
|---|---|---|
| id | PK | |
| useridnumber | char(50) | `EDU-STU-*` yoki `HR-EMP-*` — **shaxs turidan qat'i nazar bitta jadval** |
| sana | int (date) | unikal kalit (useridnumber, sana) |
| birinchikirish, oxirgichiqish | int null | timestamp, Asia/Tashkent |
| soni | int | kun davomidagi hodisalar soni |
| manba | char(20) | `terminal` (kelgusida boshqa manbalar uchun) |

Frappe tomonda yangi `integrations/moodle_checkin.py`: `Terminal Checkin` dan kecha+bugun oralig'ini
agregatlab push qiladi (xom hodisalar emas — kunlik xulosa; hajm 6 qurilma × ~700 kishi uchun arzon).
WS: `local_targetzenit_push_checkin` (`:push`). Cron: `35 7 * * *` to'liq kecha + `*/30 8-18 * * 1-5` bugungi jonli yangilanish.
Bog'lanish: Student uchun `Student.name`, xodim uchun `Employee ID` ↔ `attendance_device_id` ko'prigi Frappe'da hal qilinadi (moodle_oylik'dagi `_turniket_vaqtlari` mantig'i qayta ishlatiladi).
Ko'rsatish joylari: `oquvchi360.php`, `baholarim.php` (o'quvchi o'zi/ota-ona), kokpitda nomuvofiqlik belgisi
(darsda `absent`, lekin turniketda binoda bor → ⚠ "binoda edi"), `oylik.php` (xodim — mavjud `kunlarjson` bilan birlashtiriladi, uzoq muddatda `kunlarjson` o'rnini `_checkin` oladi).

### 4.6 O'zgaradigan mavjud kod (yangi jadvalsiz)

| Modul | O'zgarish |
|---|---|
| `jurnal.php` | `exam::journal_columns()` (hozir o'lik kod) ulanadi: chorak setkasidan keyin ajralib turuvchi BSB/CHSB/LI ustunlari, sana katagida "Imtihon: BSB-1" mavzusi; oxirida `_term_score` dan taklif chorak bahosi ustuni |
| `jurnal.php` | KTP qamrov paneli: `_slot.mavzu` + `_mavzu` reja → o'tilgan ✓ / bugungi ● / kelgusi ○ ro'yxati, "reja bo'yicha N% da" ko'rsatkichi (talab #7 "tepadan qarash") |
| `exam.php` | CBT: 4.7 ga qarang — rezerv ustunlar (`qbankcmid`, `qcategoryid`, `quizcmid`, `randomcount`) nihoyat ishlaydi |
| `dars.php` | UV tekshiruv bloki (hwstatus/hwgrade/latemin + assign holati), resurslar bloki (`_lessonres`), o'quvchi fotosi (ISH_STOLI qarori) |
| `teacher.php` | `counts()` kengayadi: tekshirilmagan topshiriqlar soni (`topshiriq.php` so'rovi), kutilayotgan imtihonlar, o'qilmagan feedback — bosh.php uchun |

### 4.7 CBT arxitekturasi (5D) — core `mod_quiz` ustida

Sxema allaqachon tayyor (rezerv ustunlar), faqat mantiq yoziladi:

1. **Savol banki**: koordinator/o'qituvchi savollarni **kurs kategoriyasi kontekstidagi** bankda tuzadi
   (`yil → guruh` kategoriya daraxtining fan darajasida) — parallel sinflar o'rtasida umumiy (Canvas Item Banks naqshi).
   `_exam.qcategoryid` shu bank kategoriyasiga ishora qiladi.
2. **Quiz yaratish**: imtihon `tasdiqlangan` holatga o'tganda `exam::quiz_yarat()` har bir sitting uchun
   o'z kursida (kurs = guruh×fan, demak auditoriya tabiiy cheklangan) yashirin `mod_quiz` yaratadi:
   `timeopen/timeclose` = sitting sana+vaqt±, `timelimit` = duration, bankdan `randomcount` ta tasodifiy savol,
   bitta urinish, deferred feedback. `_exam_sitting.quizcmid` ga yoziladi.
3. **Moslashuvlar**: alohida o'quvchiga qo'shimcha vaqt — core **user override** (Canvas Accommodations analogi), kod yozilmaydi.
4. **Yig'ish**: yangi task `exam_harvest` (imtihon kuni */10 min): `quiz_grades` → maxscore'ga masshtab →
   `_exam_result.rawscore` → mavjud 30% qoplama mantig'i → `score`. O'qituvchi `ballsetka` da ko'rib,
   qo'lda tuzatishi mumkin (`enteredby` saqlanadi) → `yopildi` da muzlaydi.
5. **Qog'oz shakli o'zgarishsiz** (ball setka + Excel import ishlayveradi). Lockdown (SEB) — kelgusi bosqich, arxitekturaga kirmaydi.

---

## 5. SAHIFALAR VA MODULLAR (yangi qurilish)

### 5A. `bosh.php` — o'qituvchi ish stoli
Kirganda birinchi ekran (talab #1). Tarkibi (barchasi mavjud ma'lumotdan, yangi jadval YO'Q):
- **Bugungi darslar** kartalari (`_slot` bugun + o'qituvchi): vaqt, guruh, fan, xona, KTP mavzusi,
  holat chiplari (mavzu ✓/✗, davomat ✓/✗, yozuv ✓/✗, UV ✓/✗), tugmalar: ▶ Yozuv (`rec.php`), Kokpit (`dars.php`).
- **Kutilayotgan ishlar** (yagona to-do, Canvas naqshi):
  to'ldirilmagan o'tgan darslar (`_fact`: attendancecount=0 yoki hastopic=0) → kokpitga havola;
  tekshirilmagan topshiriqlar (assign/quiz submitted−graded) → baholash sahifasiga;
  yaqin 7 kundagi imtihonlar (`_exam_sitting`); o'qilmagan direktor feedbacki; kelishuv kutayotgan uchrashuvlar.
- **Maktab e'lonlari** lentasi (`_elon` scope=maktab) — PowerTeacher Daily Bulletin naqshi.
- Hafta jadvali (mavjud `index.php?view=week` ga havola).
Kirish nuqtasi: primary nav'da birinchi band + `\core\event\user_loggedin` observer — `teacher::has_any()` bo'lsa
bosh.php ga redirect (loop-guard bilan). Ma'lumot qatlami: `classes/teacher.php` kengayadi (yangi klass emas).

### 5B. Dars resurslari — `dars.php` bloki + `darsim.php` (o'quvchi)
O'qituvchi kokpitda slayd/video/havola qo'shadi (`_lessonres`, 4.1). O'quvchi tomonda yangi `darsim.php`:
sana bo'yicha o'z dars sahifasi — mavzu, UV, resurslar, o'z bahosi/davomati ("o'quvchiga ham ko'rinadi" talabi #6
to'liq yopiladi; `baholarim.php` jadval ko'rinishida qoladi, darsim — bitta dars tafsiloti). Mobil: `tzdarsim` panel.

### 5C. Jurnal to'liq ko'rinishi — `jurnal.php` kengaytmasi
4.6 dagi ikki o'zgarish: imtihon ustunlari (Kundelik СОР/СОЧ naqshi — ajralib turadigan rangda, mavzu qatorida
"Imtihon") va KTP qamrov paneli. Shu bilan talab #7 ("butun chorakni tepadan kuzatish") yopiladi:
setka (baholar+davomat) + mavzular/UV + imtihonlar + reja-fakt foizi bitta sahifada.

### 5D. CBT — `baholash.php` ichida
4.7 bo'yicha. UI o'zgarishi minimal: imtihon formasida `shakli=onlayn` tanlanganda bank kategoriyasi tanlagichi
va "Quiz yaratish/holati" ustuni sitting jadvalida; ballsetka'da "quizdan keldi" belgisi.

### 5E. E'lonlar — `elon.php` (yozish) + `elonlarim.php` (o'qish)
4.3 bo'yicha. O'qituvchi o'z guruhlariga, rahbariyat maktabga. O'qilganlik statistikasi muallifga ko'rinadi.
Majlis turi avtomatik `_uchrashuv` yaratadi. Talablar #12, #14 yopiladi.

### 5F. Muloqot (chat) — `muloqot.php` (barcha rollar uchun bitta sahifa)
4.4 bo'yicha. Chap ustun — chatlar ro'yxati (guruh chatlari + shaxsiy), o'ng — suhbat oynasi.
O'qituvchi: guruh chati ochadi (sinfga biriktiradi — a'zolik avto-sinxron), a'zolarni/yozish siyosatini/ko'rinishni
boshqaradi, hamkasblar bilan ham yozishadi (kafedra chati). Ota-ona (farzand akkaunti): o'z chatlari + "uchrashuv
so'rash". Uchrashuv rejalashtirish chat ichida 📅 karta: taklif → tasdiq → push + kalendar. Xabarlar yadroda
(message conversations) bo'lgani uchun mobil ilovaning o'z messenjer ekrani qo'shimcha kodsiz ishlaydi.
Talablar #13 va "o'qituvchilar bilan muloqot" yopiladi.

### 5G. Turniket — `_checkin` + ko'rsatish nuqtalari
4.5 bo'yicha. Talab #15 yopiladi (o'quvchi qismi). Diqqat: turniket **ma'lumot**, davomat **hujjat** bo'lib qoladi —
o'qituvchi yo'qlamasi manba haqiqat, turniket faqat signal (nomuvofiqlik belgisi). Oylik koeffitsiyenti ham
avvalgidek zavuch qarori (mavjud qaror o'zgarmaydi).

### 5H. O'quvchi 360 — `oquvchi360.php`
PowerSchool Quick Lookup naqshi (talab #17). Bloklar: sarlavha (foto, guruh, tutor); joriy chorak barcha fanlar —
FB o'rtacha + `_term_score` (fan × FB/BSB/CHSB/jami/baho jadvali); davomat xulosasi (`_record` agregat +
`_checkin` haftalik); UV intizomi (hwstatus agregat); imtihon natijalari; ochiq sharhlar; (faqat rahbariyatga) murojaatlar.
Kirish: yangi `viewstudent360` qobiliyati (teacher arxetipi) + runtime tekshiruv: **shu o'quvchiga dars beradigan**
o'qituvchigina ochadi (enrol kesishmasi); tutor va rahbariyat — to'liq. Kirish nuqtalari: jurnal/kokpitdagi ism,
sharh sahifasi, qidiruv. `hisobot.php` (ota-ona varianti) o'zgarishsiz qoladi — ikkitasi bir ma'lumot qatlamidan o'qiydi.

### 5I. UV tekshiruvi — `dars.php` bloki
4.2 bo'yicha. Kokpitda har o'quvchi qatorida: UV holati (bajardi/qisman/bajarmadi) + UV bahosi + assign fayl holati
(topshirdi/yo'q, havola bilan). `topshiriqlar.php` saqlanib qoladi (fanlar kesimi umumiy ko'rinish). Talab #20 yopiladi.

---

## 6. RUXSATLAR MODELI (qo'shimchalar)

Mavjud model o'zgarmaydi (`teach` kurs kontekstida = faqat o'z darsi; `viewteachers` = rahbariyat; va h.k.). Yangi:

| Qobiliyat | Kontekst | Arxetip | Izoh |
|---|---|---|---|
| `local/targetzenit:elon` | kurs | editingteacher, manager | o'z guruhiga e'lon |
| `local/targetzenit:elonall` | tizim | bo'sh (qo'lda) | maktab e'loni — zavuch/direktor rollariga |
| `local/targetzenit:chat` | tizim | editingteacher, manager | guruh chati ochish/boshqarish, uchrashuv rejalashtirish |
| `local/targetzenit:viewstudent360` | tizim | editingteacher, manager | + runtime "shu o'quvchining o'qituvchisimi" tekshiruvi |
| `local/targetzenit:viewcheckin` | tizim | manager | boshqalarning turniketi; o'quvchinikini uning o'qituvchilari 360 orqali ko'radi |

Qoida saqlanadi: o'qituvchi **hamma narsani ko'radi, faqat o'zinikini yozadi**; jurnal/baho yozish — faqat o'z kursida;
maktab jadvali — o'qish uchun ochiq (jahon platformalaridan ustunligimiz); boshqa jurnallarni ochish — yo'q (jahon standarti ham shu).

---

## 7. ARXITEKTURAVIY QARORLAR

### 7.1 Guruh ochish (talab #11)
Akademik guruh (sinf, daraja guruhi, to'garak) **faqat Frappe'da** yaratiladi (`Student Group`) — unikal ID qoidasi
va sinxron yaxlitligi buziladi aks holda. O'qituvchiga Frappe'da guruh so'rash tartibi: zavuchga murojaat.
Kurs **ichida** tabaqalash (UVni bir qism o'quvchiga berish, Kundelik naqshi) — Moodle core groups, sinxronlanmaydi,
o'qituvchi o'zi tuzadi. Bu ikki daraja hujjatda aniq ajratiladi.

### 7.2 Ota-ona akkaunti
Hozirgi model saqlanadi: ota-ona farzand akkaunti bilan kiradi (barcha mavjud modullar shunga qurilgan).
E'lon `auditoriya=otaona` — texnik jihatdan o'sha akkauntga boradi, matnda "Hurmatli ota-ona" deb ajratiladi.
Alohida ota-ona akkaunti (Moodle mentee roli) — alohida kelgusi loyiha; bu arxitektura unga to'siq qo'ymaydi
(`_elon.auditoriya`, `_uchrashuv.tashabbus` allaqachon farqlaydi).

### 7.3 Davomat ↔ turniket
Turniket hech qachon davomatni avtomatik yozmaydi: dars davomati pedagogik hujjat (uzrli/uzrsiz farqi bor),
turniket — binoga kirish fakti. Faqat nomuvofiqlik signali ko'rsatiladi.

### 7.4 Ovoz yozish mobilda
O'zgarmaydi: yozuv faqat web (`MOBIL_ILOVA_ARXITEKTURA.md` — Ionic'da MediaRecorder'ga ishonchli kirish yo'q),
ilova webga yo'naltiradi.

### 7.5 Jurnal setkasi — E-maktab naqshi (2026-11-17 qarori)
`jurnal.php` ko'rinishi E-maktab uslubida: chapda o'quvchilar (sticky), o'rtada butun chorak kunlik baholar
setkasi, **o'ngda tanlangan kunning mavzusi va UVsi** (tahrirlanadigan). Sana ustuni bosilganda ustun to'qroq
rang bilan ajraladi va o'ng panel o'sha kunga o'tadi; baholar setkaning o'zida katak ichida tahrirlanadi
(`_grade_log` 72-soat qoidasi amal qiladi). Mavzuni o'qituvchi biriktiradi/tahrirlaydi (KTP taklif beradi,
yakuniy so'z o'qituvchida — reja≠fakt prinsipi saqlanadi).

### 7.5a Sana tanlash va blok fanlar ko'rinishi (2026-11-17 qarori)
Ish stoli va dars jadvali (hafta + butun maktab) sahifalarida **sana tanlagich**: standart — bugungi kun,
o'qituvchi istalgan o'tgan/kelgusi sanani tanlay oladi (‹ › tugmalar + kalendar + «Bugun»). O'tmish `_slot`
muzlatilgan qatlamdan (frozen=1), kelajak rejadan o'qiladi. Butun maktab jadvalida **blok fanlar**
(Matematika, Ingliz tili — 3 daraja guruhi) Frappe'dagidek ko'rsatiladi: sinf qatorida «blok: 3 daraja
guruhi ↓», blok qatorining bitta katagida uchchala guruh nomi va o'qituvchisi (High/Middle/Low) — ma'lumot
manbasi `_timetable.memberclasses`.

### 7.6 Uch til qoidasi (QAT'IY)
Har bir sahifa, tugma, yorliq, xabarnoma — **uz / ru / en** uchchala tilda. Texnik yo'l: plagin allaqachon
`lang/uz`, `lang/ru`, `lang/en` bilan keladi — barcha yangi satrlar faqat `get_string()` orqali yoziladi,
hardcode matn taqiqlanadi. Moodle'da foydalanuvchi tilini almashtirsa butun portal (yangi modullar ham)
avtomatik o'sha tilga o'tadi. AI tahlil tili allaqachon shu qoidada (`analysis_i18n`, foydalanuvchi `lang` i).

### 7.7 Nimani qurmaymiz (ongli rad)
- `mod_attendance` — bizning davomat jadval-markazli (`_record`), kurs-markazli plaginga ko'chirish regressiya.
- `mod_scheduler` (slot-bron) — ota-ona akkauntisiz slot modeli ishlamaydi; kelishuv-yozishma modeli (5F) yetarli.
- O'tirish sxemasi (seating chart), logstore tahlili, SEB lockdown — kelgusi g'oyalar ro'yxatida, bu bosqichda yo'q.

---

## 8. QURILISH BOSQICHLARI

Har bosqich — alohida versiya, alohida deploy (version bump qoidasi!), lokal test → prod.

| Bosqich | Tarkib | Hajm | Bog'liqlik |
|---|---|---|---|
| **1. Jurnal to'liq** | 5C: imtihon ustunlari (o'lik kodni ulash) + KTP qamrov paneli | kichik | yo'q — darhol boshlasa bo'ladi |
| **2. Kokpit to'liq** | 5I (UV tekshiruvi, `_record` ustunlari) + 5B (`_lessonres`, darsim.php) + foto | o'rta | yo'q |
| **3. Ish stoli** | 5A (`bosh.php`, to-do, login redirect) | o'rta | 2-bosqichdagi countlar foydali |
| **4. CBT** | 5D (`exam::quiz_yarat`, `exam_harvest` task) | o'rta | 1-bosqich (jurnal ustunlari) |
| **5. Aloqa qatlami** | 5E (e'lonlar) + 5F (muloqot-chat, uchrashuv kartalari) + mobil panellar | katta | yo'q |
| **6. O'quvchi 360 + turniket** | 5H + 5G (`moodle_checkin.py`, `_checkin`) | o'rta | Frappe tomonda ham ish bor |

Tavsiya tartibi: 1 → 2 → 3 (o'qituvchining kundalik tajribasi birinchi), keyin 4 (chorak imtihonlariga ulgurish),
keyin 5–6. Har bosqichda `BAHOLASH_ARXITEKTURA.md` dagi kabi ushbu hujjat holat belgilari bilan yangilanadi.

---

## 9. OCHIQ SAVOLLAR (foydalanuvchi tasdig'i kerak)

1. Login redirect: har kirishda `bosh.php` ga majburiy yo'naltirishmi, yoki faqat nav'dagi birinchi bandmi?
2. `_lessonres` video limiti 100MB yetadimi (yoki YouTube-havola asosiy yo'l deb belgilaymizmi)?
3. E'lonni o'qituvchi chiqarishi uchun zavuch tasdig'i kerakmi, yoki to'g'ridan-to'g'ri chiqadimi?
4. O'quvchi 360 da fan o'qituvchisi boshqa fanlar baholarini **to'liq** ko'radimi (talab shunday edi) — maxfiylik
   nuqtai nazaridan yakuniy tasdiq (jahon amaliyotida bu tutor/rahbariyatga cheklanadi, bizda ochiq bo'ladi).
5. CBT birinchi sinovi qaysi fan/imtihon turida (LI?) o'tkaziladi?
