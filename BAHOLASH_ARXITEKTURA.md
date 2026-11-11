# Baholash moduli (FB · BSB · CHSB) — arxitektura

**Holat:** reja (kod yozilmagan) · 2026-11-08
**Qaror qabul qilgan:** foydalanuvchi, 2026-11-08

O'quv koordinatori (Academic Coordinator) summativ baholashlarni o'tkazadi,
natijasini tahlil qiladi; chorak bahosi O'zbekiston Respublikasining rasmiy
nizomi bo'yicha **100 ballik** tizimda hisoblanadi.

---

## 1. Rasmiy asos — nizomdagi raqamlar

Manba: **Maktabgacha va maktab ta'limi vaziri 248-son buyrug'i ilovasi
(Nizom)** — «Umumiy o'rta ta'lim muassasalari o'quvchilari bilimini
baholash tartibi». Quyidagi raqamlar kodda **qotib yozilmaydi**, plagin
sozlamasiga chiqariladi (nizom o'zgarishi mumkin), lekin standart qiymatlar
shular:

### 1.1 Chorak bahosi — 100 ball

| Qism | Ulush | Soni | Qachon |
|---|---|---|---|
| **FB** — formativ baholash | **10%** | har darsda | chorak davomida |
| **BSB** — bob bo'yicha summativ **+ LI** — loyiha ishi | **50%** (birgalikda) | BSB **3 martagacha** | chorak davomida |
| **CHSB** — chorak bo'yicha summativ | **40%** | **1 marta** | chorak oxirida |

**BSB soni haftalik soatga bog'liq** (nizom: «haftalik soatlardan kelib
chiqib»; aniq chegaralar):

| Haftada soat | BSB soni |
|---|---|
| 1–2 | **1 ta** |
| 3–4 | **2 ta** |
| 5 va undan ortiq | **3 ta** |

**LI — loyiha ishi** uchinchi summativ tur: e-kundalik jurnalida BSB va CHSB
bilan **yonma-yon ustun** sifatida turadi va 50% ulushga BSB bilan
**birgalikda** kiradi («BSB va loyihalar — 50%»).

### 1.2 Baho shakliga o'tkazish

| Ball | Baho | Daraja |
|---|---|---|
| 86–100 | **5** | a'lo |
| 66–85 | **4** | yaxshi |
| 30–65 | **3** | qoniqarli |
| 0–29 | **2** | qoniqarsiz |

### 1.3 Formativ baholashning ichki taqsimoti (har darsda, 10 ball)

- **Darsda ishtirok — 6 ball** (6 / 5 / 4 / 3 / 2 / 1)
- **Uy vazifa — 4 ball** (4 / 3 / 2 / 1 / 0)

Jurnalga izoh rangi: **9–10 yashil · 6–8 sariq · 0–5 qizil**, har biriga
ota-onaga mo'ljallangan matn. Nizom: *bu izohlar CHSB natijasiga ta'sir
qilmaydi*.

### 1.4 Qayta topshirish

| Holat | Natija |
|---|---|
| Uzrli sabab (tibbiy ma'lumotnoma, kasallik varaqasi, yaqin qarindoshning vafoti, respublika/xalqaro olimpiada) | **olgan bali to'liq** |
| Uzrsiz qatnashmagan · tartibbuzarlik uchun chetlashtirilgan · **30%dan past** natija | **maksimal 30%** |
| Kasalliklar ro'yxati bo'yicha ozod etilgan | **aniq 30%** |

Muddat: keyingi chorakning shu fandan **birinchi summativ baholashi
kunigacha**.

**Tartibbuzarlik** (nizomda aniq sanalgan): boshqa o'quvchi bilan gaplashish,
yordam olish/berish; ruxsatsiz manba va elektron qurilma (telefon, planshet,
eshitish moslamasi, aqlli soat); nazorat materialini almashtirish. Natija —
chetlashtirish + **dalolatnoma**.

### 1.5 Jadval cheklovi (modulda MAJBURIY tekshiriladi)

- CHSB jadvali chorak boshlanishidan oldin **metodik kengash qarori** bilan
  tasdiqlanadi;
- **bitta sinfda bir o'quv kunida 3 fandan ortiq emas** va umumiy vaqti
  **5 soatdan oshmasligi** kerak.

Shuning uchun imtihon jadvali qurilayotganda to'qnashuv **avtomatik**
tekshiriladi: KELAJAK sanalar uchun buzilsa **yaratish to'xtaydi**
(ogohlantirish emas), o'tgan davrni kiritishda esa faqat **ogohlantiradi** —
o'tmishni tuzatib bo'lmaydi (3.2).

### 1.6 Imtiyoz

Milliy/xalqaro sertifikat egalari va xalqaro fan olimpiadalari sovrindorlari —
o'quv yili yakuniga qadar CHSB dan ozod; mahalliy olimpiadaning respublika
bosqichi sovrindorlari — **bir marta**.

### 1.7 Yillik baho (hozir qamrovda EMAS, yozib qo'yiladi)

- Davlat attestatsiyasi bor fanlar: 4 chorak o'rtacha arifmetigining **70%** +
  davlat imtihoni **30%** (davlat imtihonida maksimal 30 ball);
- attestatsiya yo'q fanlar: choraklik baholarning o'rtacha arifmetigi.

### 1.8 E-KUNDALIK QOIDALARI (foydalanuvchi: «to'liq amal qilamiz»)

Nizomdan tashqari elektron jurnalning o'z operatsion qoidalari bor. Ular
ham bajariladi:

**(a) Jurnal ustuni modeli.** E-kundalikda har summativ ish uchun jurnalda
**alohida ustun** yaratiladi va ustun uch narsa bilan belgilanadi:
**ish turi** (BSB / CHSB / Loyiha ishi) · **o'tkazilish sanasi** ·
**maksimal ball**. Soni, sanalari va maksimal ballari rasmiy metodik
talablarga mos bo'lishi shart.

> Bu bizning `_exam` modeliga **aynan** mos tushadi (`turi` + `examdate` +
> `maxscore`) — ya'ni model e-kundalik bilan bir tilda gaplashadi. Yagona
> qo'shimcha: `turi` ga **`LI`** (loyiha ishi) qiymati qo'shiladi.

**(b) 72 SOATLIK TUZATISH OYNASI va «O'zgarishlar tarixi».**
Kiritilgan bahoni **72 soat ichida** o'zgartirish mumkin; o'zgarishlar
**«O'zgarishlar tarixi»** bo'limida kuzatiladi. 72 soatdan keyin tuzatish
uchun tuman/shahar ta'lim bo'limiga rasmiy xat kerak bo'ladi.

**Bu bizda YANGI talab.** `_record` da hozir o'zgarishlar tarixi **yo'q** —
baho ustiga yozilib, eskisi yo'qoladi. Shuning uchun:

- yangi jadval **`_grade_log`**: qaysi baho (FB yoki summativ), kim, qachon,
  eski qiymat -> yangi qiymat, izoh;
- 72 soatdan keyin tuzatish **koordinator/direktor tasdig'i** bilan
  (bizda tuman ta'lim bo'limi o'rnini shu egallaydi) — tasdiq ham jurnalga
  yoziladi;
- tarix **o'chirilmaydi**: bu dalil (`yozuvlar.php` dagi «kuzatuvchi dalilni
  yo'q qila olmaydi» qoidasi bilan bir xil).

**(c) Mezonlar bo'yicha baholash.** Har summativ ish mezonlar bo'yicha
tekshiriladi va natija shu ustunga tushadi. Birinchi bosqichda biz faqat
**yakuniy ballni** olamiz; mezon-mezon tafsilot keyingi bosqichga qoldiriladi
(nizom: mezonlar Markaz tomonidan beriladi, bizda — koordinator).

---

## 2. Nega bu modul — va hozirgi ziddiyat

Tizimda summativ baholash tushunchasi umuman yo'q. Bor narsa — har darsga
qo'yiladigan **1–10 baho** (`_record.grade`), va `gradebook.php:99` da chorak
kategoriyasi `GRADE_AGGREGATE_MEAN`, ya'ni:

> **chorak bahosi = kundalik baholarning oddiy o'rtachasi**

Nizom bo'yicha esa kundalik baho — bu **FB** va u jami **10%**. Ya'ni hozirgi
hisob nizomga qarama-qarshi.

Yaxshi tomoni: bizdagi 1–10 shkala FB ning 10 balliga **to'g'ridan-to'g'ri**
mos tushadi (6 + 4 = 10), ya'ni ma'lumot bazasi allaqachon bor — faqat vazn,
chorak formulasi va rang chegaralari o'zgaradi.

---

## 3. Qarorlar (foydalanuvchi, 2026-11-08)

| Savol | Qaror |
|---|---|
| Imtihon shakli | **ikkisi ham** — har baholashda `onlayn` yoki `qogoz` tanlanadi |
| Vaqt | baholashda **standart sana+vaqt**, har guruhga **alohida siljitish mumkin** (quyida 3.1) |
| O'tgan davr | **majburiy**: o'tgan sanaga baholash biriktirib, ballni qo'lda kiritish (3.2) |
| Chorak bahosi | **to'liq 100 ballik model** (FB 10 + BSB/LI 50 + CHSB 40) |
| Qoidalarga munosabat | **DTS va e-kundalik qoidalariga TO'LIQ amal qilinadi** — o'z vaznimiz kiritilmaydi |
| Birinchi quriladigan | **CHSB** |
| BSB ni kim tekshiradi | **fan o'qituvchisi** (nizom bo'yicha), CHSB — koordinator |
| Savollarni kim tayyorlaydi | **faqat koordinator** |
| CHSB qachon | **chorak yakunida**, chorakda bir marta (har oy EMAS) |
| BSB qachon boshlanadi | **kelasi chorakdan** — o'tgan chorakda BSB o'tkazilmagan |
| Vazn qayta taqsimlash | faqat **o'tgan chorak uchun ISTISNO** (9.1); kelasi choraklarda vazn qat'iy 10/50/40 |

### 3.1 Imtihon jadvali — vaqt SITTING darajasida

Foydalanuvchining aniq misoli (2026-11-08):

> «imtixon kunini oldindan kordinator belgilaydi — **chorshanba kuni 7A soat 9
> da matematika** fanidan test, **9A soat 10 da ingliz tilidan** test va hokazo»

Ya'ni koordinator qiladigan ish — bitta sana tanlash emas, **imtihon jadvalini
qurish**: (guruh × fan × sana × vaqt × xona) setkasi. Bu xalqaro
Examinations Officer ishining aynan o'zi («devising the full timetable for
each exam season, to include appropriate rooming»).

Shuning uchun model shunday bo'ladi:

- `_exam` da **standart** `examdate` / `starttime` / `room` turadi — bir
  baholash (masalan «Matematika · 7-sinflar · 1-chorak CHSB») uchun odatiy vaqt;
- `_exam_sitting` da **o'sha maydonlar qayta yozilishi mumkin** (`NULL` =
  baholashdan meros oladi). 7A ni soat 9 da, 7B ni soat 11 da o'tkazish —
  bitta baholash ichida, ikki sitting.

**Nega meros + qayta yozish, ikkisidan biri emas:** hamma guruhga bitta vaqt
qo'yish halol taqqoslash uchun afzal (savol tarqalmaydi), lekin 79 guruh
bitta vaqtda joylashmaydi — xona ham, qurilma ham yetmaydi. Standart qiymat
qo'l mehnatini yo'qotadi, qayta yozish esa haqiqatni hisobga oladi.

Koordinator ekrani — **jadval quruvchi**: qatorlar = guruhlar, ustunlar =
sana/vaqt/xona, pastda to'qnashuv ogohlantirishi (1.5-bo'limdagi 3 fan /
5 soat qoidasi + dars jadvali bilan ustma-ustlik).

### 3.2 O'TGAN CHORAKNI KIRITISH (majburiy talab)

> «bizni avvalgi chorakda dastur ishlamagan. uni kiritishimiz kerak. eski
> kunlarga chorak nazorati biriktirish va baholarni kiritish imkoni bo'lsin»

Ya'ni modul **faqat oldinga ishlamaydi** — o'tgan sanaga baholash yaratish
birinchi darajali funksiya, keyin qo'shiladigan qulaylik emas.

Qoidalar:

| Holat | Xatti-harakat |
|---|---|
| `examdate` o'tmishda | ruxsat beriladi; «kelajak bo'lishi shart» tekshiruvi YO'Q |
| Shakli | majburan **`qogoz`** — o'tgan kunga onlayn quiz yaratish ma'nosiz (`timeopen` o'tib ketgan, hech kim ishlamaydi) |
| Quiz | yaratilmaydi, `quizcmid = NULL` |
| Bildirishnoma | **yuborilmaydi** — o'tgan imtihon haqida bugun xabar kelishi chalkashlik |
| Ball | koordinator setka ekranida qo'lda kiritadi (guruh → o'quvchi → ball) |
| To'qnashuv tekshiruvi | **ogohlantirish**, to'xtatmaydi — o'tmish allaqachon bo'lib o'tgan, uni «tuzatib» bo'lmaydi |
| Jurnal | ustun o'sha o'tgan sanada paydo bo'ladi (8.2) |
| Chorak bahosi | kiritilgach `term_score_rebuild` hisoblaydi, keyin koordinator chorakni **muzlatadi** (`frozen=1`) |

**Ochiq savol (foydalanuvchiga):** o'tgan chorakda kundalik baholar ham
kiritilmagan bo'lsa **FB (10%) qaydan olinadi?** Uchta yo'l bor va bu
chorak bahosini jiddiy o'zgartiradi — 12-bo'limdagi xavflar ro'yxatida.

---

## 4. Rol va ruxsatlar

### 4.1 Rol — «O'quv koordinatori»

`cli/koordinator_rol.php` (naqsh: `cli/direktor_rol.php`) rolni yaratadi va
**`<yil>` kurs kategoriyasiga** biriktiradi.

**Nega kategoriya:** kurslar `<yil>` → `<guruh>` kategoriyalariga joylangan
(`moodle_kurs.py:159`). Rolni `<yil>` ga bir marta biriktirish **79 guruhning
hammasini** qamraydi — «barcha guruhlar uchun ruxsat» talabi aynan shu bitta
amal bilan bajariladi. Kursga yozilish (enrolment) shart emas: kategoriya roli
ruxsatni pastga meros qiladi.

### 4.2 Yangi capability lar

| Capability | Kontekst | Kimga | Nima uchun |
|---|---|---|---|
| `local/targetzenit:manageexams` | SYSTEM | koordinator | baholash yaratish, savol yuklash, generatsiya, CHSB ballini kiritish va tasdiqlash |
| `local/targetzenit:gradeexam` | **COURSE** | `teacher`, `editingteacher`, `manager` | **qog'oz** BSB ballini O'Z kursida kiritish (nizom: BSB ni fan o'qituvchisi tekshiradi) |
| `local/targetzenit:viewexamresults` | SYSTEM | koordinator + `manager` | butun maktab natijalari va tahlili |

`archetypes` koordinator uchun **bo'sh**: rol CLI bilan yaratiladi va qo'lda
biriktiriladi (`murojaatceo`/`murojaatdirektor` bilan bir xil sabab — manager
arxetipiga bog'lansa direktor ham koordinator ishini qila olardi).

**`local/targetzenit:teach` koordinatorga BERILMAYDI** — u dars o'tmaydi,
mavzu/yo'qlama/kundalik baho yozmaydi (direktor bilan bir xil tamoyil).

### 4.3 Rolga kerak bo'ladigan YADRO ruxsatlari

Kategoriya darajasida beriladi (CLI skript yozadi):

- `moodle/course:view`, `moodle/course:viewhiddencourses`
- `moodle/course:manageactivities` — `create_module()` shuni talab qiladi
- `mod/quiz:addinstance`, `mod/quiz:manage`, `mod/quiz:preview`,
  `mod/quiz:viewreports`, `mod/quiz:grade`
- `mod/qbank:addinstance`, `mod/qbank:view`
- `moodle/question:add`, `:editall`, `:viewall`, `:useall`, `:managecategory`,
  `:moveall`
- `moodle/grade:viewall`

**Berilmaydi:** `moodle/grade:edit` (bahoni qo'lda tuzatish), `moodle/role:assign`,
`moodle/course:delete`.

---

## 5. Ma'lumot modeli

```
_exam                       bitta baholash hodisasi = e-kundalikdagi JURNAL USTUNI
  turi            BSB | CHSB | LI      (LI = loyiha ishi)
  academicyear    2026-2027
  termname        1-chorak
  subjectkey      fan kaliti (kurs idnumber'ining 3-bo'lagi)
  gradelevel      sinf darajasi: "7"  (guruh "7 A" ning raqamli qismi)
  shakli          onlayn | qogoz
  examdate        YYYY-MM-DD        <- STANDART (sitting qayta yozishi mumkin)
  starttime       HH:MM             <- STANDART
  room            xona              <- STANDART
  duration        daqiqa
  retro           1 = o'tgan davr uchun kiritilgan (quiz yaratilmaydi, xabar yo'q)
  maxscore        maksimal ball (standart 100)
  randomcount     0 = hamma savol; >0 = bankdan shuncha tasodifiy savol
  qbankcmid       markaziy qbank faoliyati (fan bo'yicha)
  qcategoryid     shu baholashning savol kategoriyasi
  status          qoralama | tasdiqlangan | otkazildi | yopildi
  createdby, timecreated, timemodified

_exam_sitting               har guruh/kurs uchun bitta qator = IMTIHON JADVALI qatori
  examid, courseid, groupkey, groupname
  examdate, starttime       NULL = baholashdan meros; qiymat bo'lsa SHU guruh
  room                      NULL = meros; xona (Examinations Officer ishining markazida)
  quizcmid                  onlayn bo'lsa yaratilgan quiz; qog'ozda null
  status                    kutilmoqda | otkazildi | yopildi | xato
  timegenerated

_exam_result                o'quvchi natijasi
  examid, sittingid, studentid, courseid
  score                     olingan ball (0..maxscore)
  holat                     ishladi | uzrli | uzrsiz | chetlashtirildi | ozod | imtiyoz
  retake                    0 | 1  (qayta topshirishmi)
  capped                    1 = 30% chegarasi qo'llanildi (izlanuvchan bo'lishi kerak)
  enteredby, timemodified
      ^ onlayn: vazifa Moodle quiz dan yig'adi
      ^ qogoz: koordinator (CHSB) / fan o'qituvchisi (BSB) kiritadi

_grade_log                  O'ZGARISHLAR TARIXI (e-kundalik talabi, 1.8-b)
  turi            fb | summativ
  recordid        _record.id (FB) yoki _exam_result.id (summativ)
  studentid, courseid
  oldvalue, newvalue
  userid          kim o'zgartirdi
  reason          72 soatdan keyin MAJBURIY izoh
  approvedby      72 soatdan keyingi tuzatishni kim tasdiqladi (0 = kerak emas)
  timecreated
      ^ O'CHIRILMAYDI — bu dalil

_term_score                 chorak yakuni — HISOBLANGAN, lekin SAQLANADI
  studentid, courseid, academicyear, termname
  fb        0..10     kundalik baholarning o'rtachasi
  bsb       0..50     BSB lar yig'indisi normallashtirilgan
  chsb      0..40     CHSB normallashtirilgan
  total     0..100
  baho      2 | 3 | 4 | 5
  parts     hisobga olingan qismlar: "fb,bsb,chsb" | "bsb,chsb" | "chsb"
            ^ 9.1 — vazn qayta taqsimlangani KO'RINIB turishi shart
  divisor   bo'luvchi: 100 | 90 | 40 ...  (tekshiruv va tushuntirish uchun)
  frozen    1 = chorak yopilgan, qayta hisoblanmaydi
  timemodified
```

### 5.1 Nega `_term_score` saqlanadi

Foiz saqlanmaydi, **ball saqlanadi** — va chorak yopilgach `frozen=1`.
`_slot.frozen` va `_fact` bilan bir xil sabab: aks holda kundalik bahoga
bitta tuzatish kiritilsa **o'tgan chorak bahosi o'z-o'zidan o'zgarib ketardi**.
Bu ota-ona oldida tushuntirib bo'lmaydigan holat.

### 5.2 Natija qayerda yashaydi

Onlayn baholashda matn va javoblar **Moodle'ning o'zida** (`quiz_attempts`,
`question_attempts`) — nusxalanmaydi. `_exam_result` faqat **yassi ko'rinish**:
SQL bilan guruhlararo taqqoslash va chorak hisobi uchun (`_aicat` naqshi —
«JSON yagona manba bo'lib qoladi, yassi jadval indeks»).

---

## 6. Savol banki

Markaziy kurs: `BAHOLASH::<yil>` (`idnumber`), `<yil>` kategoriyasida,
**yashirin** (`visible=0`) — o'quvchi ko'rmaydi.

Ichida **har fanga bitta `mod_qbank`** faoliyati
(`question_bank_helper::create_default_open_instance($course, $bankname,
TYPE_STANDARD)`), har baholashga esa shu bank ichida **bitta savol
kategoriyasi**: `<yil> · <chorak> · <sinf> · <turi>`.

**Nega bitta bank, 79 nusxa emas:** Moodle 5.x da savol banki
**ulashiladi** — `mod_qbank` `FEATURE_PUBLISHES_QUESTIONS` ni qo'llaydi va
`core_question_search_shared_banks` *hamma* bankni qidiradi. Ya'ni 79
guruhning quiz'i **bitta** bankka murojaat qiladi. Savollarni nusxalash
versiyalar chalkashligiga va «bir guruhda tuzatilgan savol boshqasida eski
qolishiga» olib keladi.

**Yuklash:** `qformat_gift` / `qformat_xml` / `qformat_aiken` /
`qformat_multianswer` — kod bilan ham chaqirish mumkin:

```php
$qformat = new qformat_gift();
$qformat->setCategory($category);
$qformat->setContexts(...);
$qformat->setCourse($course);
$qformat->setFilename($path);
$qformat->setMatchgrades('error');
$qformat->importpreprocess();
$qformat->importprocess();
$qformat->importpostprocess();
```

**Qog'oz shakli uchun chop etish:** `qformat_xhtml` savollarni XHTML qilib
eksport qiladi — chop etiladigan varaq shundan chiqadi. (Nizom: summativ
baholash topshiriqlarini chop etish o'quv ishlari bo'yicha direktor
o'rinbosari zimmasida; bizda — koordinator.)

---

## 7. Avtomatlashtirish zanjiri (CHSB)

Koordinator **uch amal** qiladi, qolganini tizim bajaradi:

1. **Baholash yaratadi** — turi, fan, sinf darajasi, shakli, sana+vaqt,
   davomiyligi, maksimal ball.
2. **Savol faylini yuklaydi** — markaziy bankdagi shu baholash
   kategoriyasiga tushadi.
3. **«Yaratish»** bosadi.

Tizim:

| Qadam | Nima qiladi | Xatoda |
|---|---|---|
| 1 | Mos kurslarni topadi: `<yil>::<guruh>::<fan>`, `guruh` ning raqamli qismi = `gradelevel` | kurs topilmasa — to'xtaydi, ro'yxatini ko'rsatadi |
| 2 | **To'qnashuv tekshiruvi:** shu kunda shu sinfda 3 fandan ortiq emas · umumiy 5 soatdan oshmasin · dars jadvali bilan ustma-ustlik | buzilsa — **to'xtaydi** (ogohlantirish emas) |
| 3 | `_exam_sitting` qatorlarini yozadi | — |
| 4 | Onlayn bo'lsa har kursga `quiz` yaratadi (`create_module()`) | yiqilsa shu sitting `xato` deb belgilanadi, qolganlari davom etadi |
| 5 | Bankdan savol qo'yadi: `quiz_add_quiz_question()` yoki `structure::add_random_questions()` | — |
| 6 | Kalendar hodisasi **o'zi** tug'iladi (`quiz_update_events()`) | — |
| 7 | O'quvchi va o'qituvchiga bildirishnoma (`db/messages.php` provideri) | yiqilsa baholash saqlanadi, faqat qayd |

**MUHIM — bu ish VEB-SO'ROVDA BAJARILMAYDI.** 79 kursga quiz yaratish
daqiqalar oladi; serverda PHP-FPM da atigi **4 ishchi** bor va uzun so'rov
butun Moodle'ni to'xtatadi (0.13.1 saboqi). Shuning uchun «Yaratish» faqat
**adhoc vazifani navbatga qo'yadi** (`task\exam_build`) va darhol qaytadi;
sahifa holatni har 5 soniyada so'rab turadi.

### 7.1 Quiz sozlamalari (imtihon rejimi)

| Maydon | Qiymat | Nega |
|---|---|---|
| `timeopen` / `timeclose` | sana+vaqt · +davomiylik | imtihon oynasi; kalendar shundan |
| `timelimit` | davomiylik | nizomdagi vaqt |
| `attempts` | 1 | summativ baholash bir marta |
| `navmethod` | `sequential` | orqaga qaytib javobni o'zgartirish yo'q |
| `shuffleanswers` | 1 | yonidagiga qarash foydasiz |
| `preferredbehaviour` | `deferredfeedback` | javob darhol ko'rsatilmaydi |
| `reviewcorrectness` va h.k. | faqat `timeclose` dan keyin | hamma tugatmaguncha javob ochilmaydi |
| `password` | ixtiyoriy | nazoratchi aytadi |
| `subnet` | ixtiyoriy | xona IP si — tashqaridan ishlanmaydi |
| `browsersecurity` | ixtiyoriy | nizomdagi «ruxsatsiz manba» talabiga yaqinlashish |
| `grade` | `maxscore` | 100 ballik tizim |

---

## 8. Ko'rinish — qayerda paydo bo'ladi

### 8.1 Dars jadvali (`index.php`)

Baholashning **o'z sanasi va vaqti** bor (dars slotiga bog'lanmagan), va u
sitting darajasida aniqlanadi (3.1) — ya'ni 7A soat 9 da, 7B soat 11 da.
Shuning uchun jadvalda **alohida, ajratilgan qator** bo'lib chiqadi, dars
katagiga belgi sifatida EMAS. Darsga to'g'ri kelsa qator «dars o'rniga» deb
belgilanadi.

Kun ko'rinishida qatorlar **vaqt bo'yicha** aralashtiriladi: imtihon soat 9 da
bo'lsa, u 2-dars bilan 3-dars orasiga tushadi. Shuning uchun saralash dars
raqami bo'yicha emas, `starttime` bo'yicha bo'lishi kerak.

> **Nega `_slot` ga bog'lanmaydi:** avval shunday rejalashtirilgan edi, lekin
> bitta umumiy vaqt tanlangach baholash biror guruhning dars o'rniga tushishi
> SHART emas — u mustaqil hodisa. `_slot` ga majburan bog'lash soxta slot
> yaratishni talab qilardi.

### 8.2 Chorak jurnali (`jurnal.php`)

- BSB/CHSB — **alohida ustun**, binafsha fon (jadvaldan tashqari dars uchun
  `merge_actual()` da ishlatilgan belgi bilan bir xil naqsh). Katak qiymati —
  **0..maxscore ball**, 1–10 baho emas.
- **Ustun imtihon sanasida turadi** — u dars kuni bo'lmasa ham. Jurnal
  ustunlari hozir `journal::lessons_in_term()` da jadval + chorak + bayramdan
  HISOBLANADI, ya'ni imtihon kuni ro'yxatda bo'lmasligi mumkin. Shuning uchun
  imtihon ustunlari `merge_actual()` ga o'xshash yo'l bilan **qo'shiladi**:
  **jurnal hech qachon mavjud ma'lumotni yashirmasligi kerak** (0.9.2 saboqi).
- O'tgan chorakni kiritishda ham xuddi shu yo'l ishlaydi — ustun o'sha o'tgan
  sanada paydo bo'ladi, alohida kod kerak emas.
- O'ng tomonda **chorak xulosasi** bloki: `FB / BSB / CHSB / Jami / Baho`.
- Kundalik baho rangi nizomdagi chegaralarga **o'tkaziladi**:
  **9–10 yashil · 6–8 sariq · 0–5 qizil** (hozir `>=7` / `>=5`).

### 8.3 O'quvchi (`baholarim.php`)

- **Kutilayotgan baholashlar**: sana, vaqt, fan, davomiyligi, xona
  (nizom: «chorak boshida o'quvchilarga yetkaziladi»).
- Natijalar: ball, foiz, holat (qayta topshirish / 30% chegarasi ko'rinib
  turadi — yashirilmaydi).
- Chorak xulosasi: FB / BSB / CHSB / Jami / Baho.

### 8.4 O'qituvchi

- `jurnal.php` dagi BSB ustunida **ball kiritish** (`gradeexam`, faqat o'z
  kursi, faqat `qogoz` shakli). Nizom: natija **bir hafta ichida** jurnalga —
  tizim muddatni ko'rsatadi.
- CHSB ustuni o'qituvchiga **faqat o'qish**.

### 8.5 Koordinator — `baholash.php`

Yagona qobiq (`output\shell`), o'z menyusi: Baholashlar · Yaratish ·
Savollar · Natijalar · Tahlil. Yon menyuda band `shell` ga qo'shiladi.

### 8.6 Direktor/zavuch

`direktor.php` menyusiga «Baholash» — maktab kesimida natija va tahlil
(`viewexamresults`). **Faqat o'qish**: kuzatuvchi o'zi baho qo'yadigan
dalilni o'zgartira olmaydi (`yozuvlar.php` qoidasi).

---

## 9. Chorak bahosi hisobi

`task\term_score_rebuild` (har kuni kechasi + qo'lda) `frozen=0` choraklarni
qayta hisoblaydi:

```
FB   = o'rtacha(kundalik baholar)            -> 0..10   (bizdagi 1..10 shkala)
BSB  = SUM(bsb ball) / SUM(bsb maxscore) * 50
CHSB = chsb ball / chsb maxscore * 40

# VAZN QAYTA TAQSIMLANADI — qaysi qism MAVJUD bo'lsa, faqat o'sha hisoblanadi
mavjud_ball = sum(bor bo'lgan qismlar)
mavjud_vazn = sum(bor bo'lgan qismlarning vaznlari)   # 10 | 50 | 40 dan
Jami = round(mavjud_ball / mavjud_vazn * 100)         -> 0..100
Baho = 86+ -> 5 · 66+ -> 4 · 30+ -> 3 · else 2
```

### 9.1 Vazn qayta taqsimlash — FAQAT O'TGAN CHORAK UCHUN ISTISNO

**NORMAL HOLAT (kelasi choraklardan boshlab): vazn QAT'IY 10 / 50 / 40.**
Foydalanuvchi qarori (2026-11-08): *«davlat ta'lim standarti va e-kundalik
qoidalariga to'liq amal qilamiz»* — ya'ni o'z vaznimiz kiritilmaydi, qism
yo'q bo'lsa ham formula o'zgarmaydi.

Qayta taqsimlash **faqat o'tgan chorakka** qo'llanadi, chunki u davrda
dastur ishlamagan va ma'lumotning o'zi yo'q:

| Chorakda nima bor | Bo'luvchi | Misol | Qachon |
|---|---|---|---|
| FB + BSB/LI + CHSB | 100 | 8 + 41 + 34 = **83** (baho 4) | **normal** |
| Faqat CHSB | **40** | 30/40 -> **75** (baho 4) | **o'tgan chorak (istisno)** |
| BSB + CHSB (FB yo'q) | **90** | 75/90*100 = **83** | istisno |

Istisno `_term_score.divisor` da **yozilib qoladi**, ya'ni qaysi chorak
qoidadan chetlangani hisobotda ko'rinadi va tushuntirib berish mumkin.

**Nega istisno berilyapti:** o'quvchi **mavjud bo'lmagan narsa
uchun jazolanmasligi** kerak. Bu kodda allaqachon ishlatilgan qoidaning
o'zi — `ai::overall()` da `confidence=low` kategoriya hisobga olinmaydi va
vaznlar qayta taqsimlanadi («audio ko'rmagan narsa o'qituvchini jazolamasin»).
Bu yerda ham: dastur ishlamagan chorakda FB yo'qligi o'quvchining aybi emas.

**UI DA MAJBURIY KO'RSATILADI:** chorak bahosi yonida **qaysi qismlar
hisoblangani** yoziladi (masalan «faqat CHSB bo'yicha · FB va BSB yo'q»).
Aks holda 30/40 ball nega 75 bo'lganini hech kim tushuntirib bera olmaydi —
va bu ota-ona oldida chiqadigan birinchi savol.

### 9.2 O'tgan chorak uchun amaliy holat

Qaror: o'tgan chorakka **faqat CHSB** kiritiladi (BSB o'tkazilmagan).
Ya'ni o'sha chorak uchun chorak ball = `CHSB / 40 * 100`, va jurnalda
«faqat CHSB bo'yicha» izohi turadi. Kiritish tugagach koordinator chorakni
**muzlatadi** (`frozen=1`) — shundan keyin kundalik baho qo'shilsa ham
o'tgan chorak bahosi o'zgarmaydi.

**Qayta topshirish chegarasi** `_exam_result` ga yozilganda qo'llanadi,
hisobda emas — shunda chegaralangan ball ekrani ham ko'rinadi:

```
uzrli            -> ball o'zgarmaydi
uzrsiz | chetlashtirildi | ball < 30%  ->  ball = min(ball, 0.30 * maxscore), capped=1
ozod (kasallik)  ->  ball = 0.30 * maxscore
```

**Gradebook:** `quarter_category()` dagi `GRADE_AGGREGATE_MEAN` o'rniga
chorak ball **tayyor qiymat** bo'lib yoziladi (`grademax = 100`), ya'ni
Moodle o'rtachani o'zi hisoblamaydi — formula nizomdagi vaznli formula,
oddiy o'rtacha emas. Kundalik baho item lari **qoladi** (FB manbasi va
o'quvchiga ko'rinadi), lekin chorak kategoriyasiga **qo'shilmaydi**
(`aggregationcoef = 0` / kategoriyadan chiqarilади).

---

## 10. Tahlil

Yadro **allaqachon hisoblaydi** — qayta yozilmaydi:

| Ko'rsatkich | Qayerda | Sifat chegarasi |
|---|---|---|
| Facility index (qiyinlik) | `quiz_statistics` | 30–70% foydali |
| Discrimination index | `quiz_statistics`, `qbank_statistics` | **>0.30 yaxshi · 0.10–0.30 o'rtacha · <0.10 yomon** |
| Discriminative efficiency | `quiz_statistics` | — |
| Coefficient of internal consistency (KR-20) | `quiz_statistics` | — |

Bizning tahlil sahifasi **yadroning ustiga** qo'shadi (yadro bitta quiz'ni
biladi, biz 79 guruhni):

- guruhlararo taqqoslash (bir xil savol, turli guruh);
- fan va sinf darajasi kesimi;
- **oydan oyga o'sish** (BSB → BSB → CHSB);
- o'qituvchi kesimi (ehtiyot bilan — `_fact.attributeduserid` naqshi);
- 30% chegarasi qo'llangan o'quvchilar ro'yxati (diqqat navbati).

**Faqat `onlayn` shaklda savol-savol tahlil bo'ladi.** Qog'ozda bitta umumiy
ball bor — bu cheklov UI da ochiq aytiladi, aks holda «nega tahlil yo'q»
degan savol tug'iladi.

---

## 11. Bosqichlar

| # | Nima | Natija |
|---|---|---|
| **0** | Rol + capability lar + `cli/koordinator_rol.php` | koordinator tizimga kiradi va hamma guruhni ko'radi |
| **1** | `_exam` / `_exam_sitting` / `_exam_result` jadvallari + `classes/exam.php` | ma'lumot qatlami |
| **2** | `baholash.php` — CHSB yaratish (**o'tgan sana ham**) + imtihon jadvali quruvchi | koordinator jadvalni e'lon qiladi |
| **3** | **Qo'lda ball kiritish setkasi** (guruh → o'quvchi → ball, holat, qayta topshirish) | **o'tgan chorak kiritiladi** |
| **4** | Jurnalda imtihon ustuni + `baholarim.php` da natija | o'quvchi va o'qituvchi ko'radi |
| **5** | `_term_score` + `term_score_rebuild` + muzlatish + gradebook o'zgarishi | chorak bahosi nizom bo'yicha |
| **6** | Markaziy bank + savol yuklash + `task\exam_build` (onlayn quiz) + to'qnashuv tekshiruvi | onlayn CHSB o'tkaziladi |
| **7** | `task\exam_harvest` + dars jadvalida imtihon qatori + bildirishnoma | onlayn natija avtomatik |
| **8** | Tahlil sahifasi | koordinatorning asosiy ishi |
| **9** | **BSB + LI** — `turi` maydoni orqali shu modelga qo'shiladi + o'qituvchi ball kiritishi | to'liq nizom, **kelasi chorakdan** |
| **10** | `_grade_log` — 72 soatlik oyna, o'zgarishlar tarixi, tasdiq oqimi | e-kundalik qoidasi (1.8-b) |

**Diqqat:** `_grade_log` 10-bosqichda turgani bilan **jadval 1-bosqichda
yaratiladi** va yozish 3-bosqichdan boshlab ishlaydi — aks holda o'tgan
chorakni kiritish paytidagi tuzatishlar izsiz qolardi, keyin esa tarixni
orqaga tiklab bo'lmaydi.

**Tartib ataylab shunday:** foydalanuvchining eng shoshilinch ehtiyoji —
**o'tgan chorakni kiritish** (2–5), onlayn imtihon esa undan keyin (6–7).
Avvalgi rejada onlayn generatsiya 3-bosqichda edi; o'tgan davr talabi
kelgach tartib almashtirildi — qo'lda kiritish yo'li onlayn yo'ldan
MUSTAQIL bo'lishi kerak, aks holda o'tgan chorakni kiritish uchun ham
savol banki va quiz qurish kerak bo'lardi.

---

## 12. Tuzoqlar va qarzlar

**Bu kodbazadan kelgan qoidalar:**

- **Uzoq ish veb-so'rovda bajarilmaydi** — PHP-FPM da 4 ishchi, uzun so'rov
  butun Moodle'ni to'xtatadi (0.13.1). Generatsiya va yig'ish — adhoc/cron.
- Lokalda Moodle ishga tushmaydi (`config.php` o'qilmaydi) → tekshiruv
  faqat `php -l` + deploydan keyin brauzerda.
- Forma massiv kalitlari faqat `[a-z0-9_-]` — `optional_param_array()`
  qolganini **jimgina** tashlaydi (0.9.4). Ball kiritish setkasi aynan shunday
  forma, kalit `b<userid>` bo'ladi.
- Postgres: bitta nomli parametr bir so'rovda ikki marta ishlatilmaydi;
  uzun CHAR ustunli qo'shma unikal indeks yozilmaydi (md5 kalit).
- `use` `require` orqali o'tmaydi; `output` namespace ichida `\html_writer::`.
- Til satrlari faqat `lang/tools/strings.py` + `gen.py`.
- `version.php` oshirilmasa CI lint yiqiladi.
- `create_module()` ko'p majburiy maydon talab qiladi — `classes/homework.php`
  dagi ishlaydigan namuna.

**Yangi xavflar:**

- **Nizom raqamlari sozlamaga chiqariladi** (10/50/40, 86/66/30 chegaralari,
  30% cheklovi, BSB soni). Nizom o'zgarsa kod o'zgarmasligi kerak.
- **Chorak bahosi o'zgarishi butun maktabga ta'sir qiladi.** O'tgan choraklar
  `frozen=1` bilan muzlatiladi va eski usulda qolgani ochiq yoziladi, aks
  holda sentabr bahosi dekabrda o'zgarib ketadi.
- **Imtiyoz (olimpiada) bali nizomda aniq ko'rsatilmagan** — «ozod etish»
  deyilgan, lekin ozod etilganga qancha ball yozilishi faqat kasallik
  ro'yxati uchun (30%) aytilgan. **Foydalanuvchidan so'raladi.**
- ~~FB bo'lmasa nima bo'ladi~~ — **HAL QILINDI (2026-11-08):** vazn qayta
  taqsimlanadi, 9.1-bo'lim. Qolgan xavf: bu nizomdan **chetlanish** (nizomda
  vazn qat'iy 10/50/40). Davlat tekshiruvi bo'lsa tushuntirish kerak bo'ladi,
  shuning uchun `_term_score` ga qaysi qismlar hisoblangani YOZILADI va
  hisobotda ko'rinadi.
- `privacy/provider.php` hamon null_provider — imtihon natijasi bilan bu yana
  bir pog'ona noto'g'ri bo'ladi.

---

Aloqador: [[moodle-local-plugin]], `DIREKTOR_PANELI.md`,
`SHARH_ARXITEKTURA.md`, `MUROJAAT_ARXITEKTURA.md`

---

## 13. EXCEL'DAN BALL YUKLASH (0.45.0, 2026-11-11)

Foydalanuvchi talabi: *«o'tgan testning natijalarini guruh bo'yicha bittada
yuklamoqchiman; yuklagan faylimdan savollar soni va har bir o'quvchi
natijasini olishi kerak»*.

### Asosiy qoida: import HECH NARSA YOZMAYDI

Fayl faqat **o'qiladi** va mavjud **ball setkasini to'ldiradi** — odam ko'rib,
tekshirib «Saqlash» bosadi. Shuning uchun:

- yozish yo'li **bitta** bo'lib qoladi (`exam::result_save()`), ya'ni nizomdagi
  30% chegarasi, 72 soatlik oyna va o'zgarishlar tarixi import uchun
  **qaytadan yozilmaydi**;
- ism bo'yicha topilgan moslikni **odam tasdiqlaydi** — bu majburiy, chunki
  tizimlararo bog'lanish faqat o'zgarmas ID bilan bo'lishi kerak.

Shu sabab import shoxchasi `baholash.php` da **redirect QILMAYDI** (redirect
o'qilgan ma'lumotni yo'qotardi) — bu yagona shunday amal.

### Fayl formati QOTIB BELGILANMAYDI

O'qituvchining fayli har xil bo'ladi, shuning uchun ustunlar **sarlavha
so'zlaridan** aniqlanadi (uz/ru/en): ism (`F.I.Sh.`, `O'quvchi`, `ФИО`,
`Name`...), ID (`ID`, `Kod`...), ball (`Ball`, `Natija`, `To'g'ri`, `Балл`,
`Score`...). Topilmasa — tushunarli xabar, taxmin qilinmaydi.

**Ikki rejim:**

| Rejim | Fayl ko'rinishi | Savollar soni qaydan |
|---|---|---|
| `ball` | `F.I.Sh. | Ball` | «Savollar soni: 20» katagidan |
| `matritsa` | `F.I.Sh. | 1 | 2 | 3 | ...` (0/1) | **ustunlar sonidan** |

Matritsa rejimi ataylab: savol-savol varaq foydalanuvchi so'ragan ikki
narsani (savollar soni + har o'quvchi natijasi) bir vaqtda beradi. Ball =
yig'indi. *Kelajak:* savol-savol javoblarni saqlab item analysis qilish
(hozir faqat yig'indi olinadi).

### Moslashtirish tartibi (qat'iy)

1. **`idnumber` bo'yicha aniq moslik** — o'zgarmas unikal ID qoidasi;
2. ism tokenlari **to'liq** mos kelsa (to'plam sifatida — fayldagi tartib
   «Familiya Ism» bo'lishi mumkin, Moodle'da «Ism Familiya»);
3. fayldagi tokenlar o'quvchining tokenlari **ichida** bo'lsa va kamida
   ikkitasi mos kelsa (otasining ismi fayldan tushib qolgan hol) — sariq
   «tekshiring» belgisi bilan.

**Ikki o'quvchiga mos kelgan qator YOZILMAYDI** («ikki xil» deb belgilanadi).
Taxmin qilib yozish eng qimmat xato: ball boshqa o'quvchiga tushadi va buni
keyin topish deyarli imkonsiz.

Ball sifatida qabul qilinadi: `18` · `18,5` · `18.5` · `90%` (maksimal
ballning foizi). Matn (`kelmadi`) — ball emas, hisobotda ko'rinadi.

### «Savollar soni» → maksimal ball

Avtomatik QO'YILMAYDI, **taklif qilinadi** (belgi). Sabab: `maxscore`
baholashga tegishli, ya'ni hamma guruhga umumiy va saqlangan ballarning
foizini o'zgartiradi (18/20 = 90%, lekin max 100 bo'lsa 18%).

`exam::maxscore_ozgartir()` da himoya: saqlangan ballardan birortasi yangi
maksimaldan **katta** bo'lsa o'zgartirilmaydi — aks holda ball jimgina
kesilib, o'quvchi haqsiz past natija olardi.

**TUZOQ (tuzatildi):** hisobot avval setka formasidan TASHQARIDA chizilgan
edi va undagi «savollar soni» belgisi hech qachon yuborilmasdi. Endi hisobot
setka formasining **ichida**.

### 13.1 Maktabning HAQIQIY fayli (0.46.0, tekshirilgan)

`1Quarter3grade Test_natijalari_sinflar.xlsx` ustida sinaldi. Tuzilishi:

```
Varaqlar:  Umumiy · Darajalar · 1A · 1B · 2A ... 11B      <- har sinf alohida
1-qator:   «3A sinf — test natijalari (har bir o'quvchiga 50 ta savol)»
2-qator:   N | O'quvchi ism familiyasi | Math | English | Russian | Business | JAMI
3-qator:       | ballar soni | To'g'ri javob | Foiz | Progress | Daraja | ...
4-qator+:  1 | Ravshanbekova Parizoda | 30 | 0 | ...
```

**Tizim o'zi topadi:**
- **varaqni** — sitting guruhining nomidan (`3 A` -> `3A`, bo'shliq va registr
  e'tiborsiz; `Umumiy`/`Darajalar` chetlab o'tiladi);
- **fan blokini** — baholashning fanidan (`blok_tanla()`); topilmasa
  **odam tanlaydi**, fayl saqlanib turadi va qayta yuklash kerak emas;
- **savollar sonini** — o'sha blokning «Savollar soni / ballar soni»
  ustunidan (3A: Math 30, English 25, Russian 15, Business 5);
- **jadval oxirini** — № ustuni butun son bo'lmagan birinchi qatorda
  to'xtaydi. Bu SHART: fayl ostida «darajalar bo'yicha guruhlar» jadvallari
  bor va ular o'quvchi ismlarini QAYTA sanaydi.

`JAMI` bloki **ataylab chetlab o'tiladi** — u yig'indi, fan emas.

**TUZOQ (tuzatildi):** sarlavha qatorida «har bir **o'quvchi**ga 50 ta savol»
yozilgan va u ism ustuni deb qabul qilinardi -> butun tahlil buzilardi.
`ism_ustun()` da ikki himoya: katak **60 belgidan uzun bo'lmasin** (izoh emas,
yorliq) va sarlavha qatorida **kamida ikki to'ldirilgan katak** bo'lsin
(title qatorida bitta bo'ladi).

**Ism moslashtirish sinovdan o'tdi** (16/16): tartib almashgan
(`Familiya Ism` <-> `Ism Familiya`), apostrof boshqa belgi
(`Yo'ldosheva` <-> `Yo‘ldosheva`), Moodle'da otasining ismi qo'shimcha
(`qisman` belgisi bilan).
