# Direktor paneli — mantiq, ma'lumotlar arxitekturasi va dizayn rejasi

> Sana: 2026-10-29. Holat: **loyiha hujjati, kod yozilmagan.**
> Maqsad: direktor har bir o'qituvchining dars jarayonini — jadvaldan AI tahlilgacha —
> bitta joyda kuzatishi va fidbek berishi.

Hujjat uch qismdan iborat va ataylab shu tartibda: avval **mantiq** (savolga qanday
javob topiladi), keyin **ma'lumotlar arxitekturasi** (javobni tez va barqaror olish
uchun nima saqlanadi), oxirida **dizayn** (shu javob qanday ko'rsatiladi).

---

## 1. Direktor nima so'raydi

Topshiriqdagi savollarni aniq ro'yxatga aylantiramiz — arxitektura shunga qarab
quriladi:

| № | Savol | Javob manbasi |
|---|---|---|
| 1 | Bugun qaysi o'qituvchining qaysi darsi bor edi? | jadval × kalendar |
| 2 | Shu darsni haqiqatda o'tdimi (jurnalga yozdimi)? | `_lesson.topic` |
| 3 | Ovoz yozib oldimi? | `_audio.status` |
| 4 | Yozuv matnga o'girildimi, tahlil bo'ldimi? | `_audio.transcript`, `.analysis` |
| 5 | Yo'qlama qildimi? | `_record` qatorlari |
| 6 | Baho qo'ydimi? | `_record.grade` |
| 7 | Uy vazifa berdimi? | `_lesson.homework` |
| 8 | Kim qaysi dars uchun yozdi, kim yozmadi? | reja ↔ fakt taqqoslash |
| 9 | O'qituvchining umumiy holati qanday? | davr bo'yicha yig'indi |
| 10 | Direktor qayerda fidbek yozadi? | **hozir yo'q — yaratiladi** |

**Markaziy kuzatuv:** 1–7 savollarning ma'lumoti allaqachon bazada bor. Yetishmayotgani
— **"bo'lishi kerak edi" bilan "bo'ldi" ni taqqoslaydigan qatlam** va **fidbek**.
Qolgan hamma narsa — ko'rsatish.

---

## 2. Hozirgi mantiq: nima bor, nima yo'q

### 2.1 Bor narsalar

```
Frappe (target_zenit)                   Moodle (local_targetzenit)
─────────────────────                   ──────────────────────────
Student Group, Instructor   ──07:00──►  user, course, enrolment (standart WS)
Dars jadvali + kalendar     ──07:20──►  _timetable, _calendar
Oylik tabel + Face ID       ──push───►  _payroll (kunlarjson: kirish/chiqish)

                                        O'qituvchi dars kokpitida:
                                        _lesson   (mavzu, uy vazifa)
                                        _record   (yo'qlama, baho) ──► gradebook
                                        _audio    (yozuv) ──► ffmpeg ──► Gemini
                                                  ──► transcript + analysis (JSON)
```

- **Dars o'rni** `_timetable` da faqat "kun raqami + dars raqami" ko'rinishida turadi
  (`dayno` 1–5, `lessonno`). Aniq sanalar saqlanmaydi.
- **Aniq sana** `journal::lessons_in_term()` da har so'rovda PHP da hisoblanadi:
  chorak oralig'i (`_calendar.entrytype='term'`) ichidagi kunlarni aylanib chiqib,
  bayramlarni (`entrytype='holiday'`) va dam olish kunlarini chiqarib tashlaydi.
- **Bitta dars** = `(courseid, lessondate, lessonno)` — `_lesson` dagi unikal indeks.
  Qator o'qituvchi dars kokpitini ochganda yaratiladi (`lesson::get_or_create`).
- **AI tahlili** 15 kategoriya bo'yicha 0–100 foizda (ICALT + CLASS + TEACH),
  har biriga `confidence` va xalqaro o'rtacha (`norm`). Umumiy foizni **kod**
  hisoblaydi — `confidence='low'` kategoriyalar chiqarib tashlanadi va vaznlar
  qolganlari bo'yicha qayta taqsimlanadi.
- **Ruxsatlar**: `viewanalysis` va `viewallpayroll` allaqachon `manager`
  arxetipida — ya'ni direktor uchun poydevor qo'yilgan.

### 2.2 Yo'q narsalar — to'rtta bo'shliq

**(a) "Bo'lishi kerak edi" degan tushuncha saqlanmaydi.**
Dars o'rni har so'rovda hisoblanadi. Bitta kurs uchun bu arzon. Direktor paneli uchun
emas: 307 kurs × ~70 dars = **chorakda ~21 000 dars o'rni**. Ularni PHP da aylanib
chiqib, keyin saralash, filtrlash va foiz hisoblash — sahifa ochilishini sekundlarga
cho'zadi va SQL da umuman yig'ib bo'lmaydi. Buni **materiallashtirish** kerak.

**(b) O'qituvchi iki xil kalit bilan yuradi.**

| Joy | Kalit |
|---|---|
| `_timetable.teacherkey` | Frappe `Employee.name` (`HR-EMP-00012`) = Moodle `user.idnumber` |
| `_lesson.teacherid`, `_audio.teacherid` | Moodle `user.id` |

Ya'ni "rejadagi o'qituvchi" va "haqiqatda yozgan odam" **turli tipdagi kalitlar**.
Har so'rovda satr bo'yicha bog'lash mo'rt — `timetable::kalit_nomuvofiqmi()` dagi
izoh aynan shu tuzoqni hujjatlashtirgan: `Instructor` ga `Employee` bog'langach kalit
`INSTR-<ism>` dan `HR-EMP-xxxxx` ga o'zgargan va jadval jim qolib ketgan. Direktor
panelida bu "o'qituvchining hamma darsi g'oyib bo'ldi" ga aylanadi. Kerak:
**yagona bog'lovchi jadval** va nomuvofiqlikni **ko'rinadigan xato** qilish.

**(c) Tahlil natijasi JSON ichida qulflangan.**
`_audio.analysis` — matn ustun. "Bu o'qituvchining `feedback_quality` bo'yicha chorak
trendi" yoki "maktabda eng kuchsiz domen qaysi" degan savolga JSON ustunidan SQL
bilan javob olinmaydi (MySQL/Postgres farqlari ham bor). Raqamlarni **yassi jadvalga
chiqarish** kerak.

**(d) Fidbek uchun joy yo'q.** Birorta jadval ham, bildirishnoma ham yo'q.

---

## 3. Mantiq: javob qanday hisoblanadi

### 3.1 Dars o'rni (slot) — rejaning birligi

```
slot = (o'quv yili, sana, dars raqami, kurs)
```

Hosil qilish qoidasi:

```
har bir _timetable qatori uchun:
    chorak oralig'idagi har bir kun uchun:
        agar date.dayno == qator.dayno
           va date bayram emas
           va date chorak ichida   ──►  slot yaratiladi
```

**MUHIM QOIDA — o'tmish qotib qoladi.** Slotlar faqat **bugun va keyingi** sanalar
uchun qayta qurildi. Sababi: jadval o'quv yili davomida o'zgaradi (o'qituvchi
almashadi, dars ko'chadi). Agar o'tgan sanalarni ham qayta qursak, **o'tgan oyning
hisoboti o'z-o'zidan o'zgarib ketadi** — direktor ko'rgan 78% ertaga 71% bo'lib
qoladi va hech kim sababini topmaydi. Kuzatuv tarixi o'zgarmas bo'lishi shart.

### 3.2 To'rtta mustaqil vazifa — bitta holat emas

Dars uchun "bajarildi/bajarilmadi" degan yagona holat **noto'g'ri model**: o'qituvchi
mavzuni yozib, yo'qlamani qilib, baho qo'ymasligi mumkin va bu **normal**. Shuning
uchun har dars **to'rtta mustaqil o'lchov** bilan baholanadi:

| Vazifa | Aniqlash | Kutilish darajasi |
|---|---|---|
| **Mavzu** | `_lesson.topic != ''` | har dars — 100% |
| **Yo'qlama** | `_record` qatorlari ≥ sinf ro'yxatining X% | har dars — 100% |
| **Baho** | `_record.grade IS NOT NULL` sanog'i | **Frappe'dan keladi** (`_norm` jadvali) |
| **Yozuv** | `_audio.status IN ('ready','transcribed')` | **har dars — 100% (majburiy)** |

**Yozuv har darsda majburiy** (qaror: 2026-10-29). Bu panelning eng ko'p signal
beradigan o'lchovi: hozirgi holatda darslarning ~60% i yozilyapti, ya'ni kuniga
~70 ta "yozuv yo'q" hodisasi chiqadi. Diqqat navbati bunday hajmni qator-qator
ko'rsatsa, u ishlatib bo'lmaydigan ro'yxatga aylanadi — shuning uchun **yozuv yo'qligi
dars bo'yicha emas, o'qituvchi bo'yicha guruhlanadi** (5.3 ga qarang).

**Baho normasi Frappe'dan keladi.** Zavuch baholash sozlamalari allaqachon Frappe
tomonda (`target_zenit`) yuritiladi — Moodle'da ikkinchi nusxa yaratish ikki manba
muammosini keltiradi. Norma `_norm` jadvaliga push qilinadi, Moodle hech narsa
hisoblamaydi va o'zgartirmaydi (oylik tabeli bilan bir xil tamoyil).

### 3.3 Qo'shimcha signallar (ma'lumot bor, hisoblanmayapti)

| Signal | Qanday hisoblanadi | Nega qimmatli |
|---|---|---|
| **Jurnal kechikishi** | `_lesson.timecreated` − dars tugash vaqti | mavzu 3 kundan keyin yozilgan = eslash bo'yicha yozilgan |
| **Yozuv qamrovi** | `_audio.duration` / 45 daqiqa | 7 daqiqalik "yozuv" — yozuv emas |
| **O'rnini bosish** | `slot.teacherkey` ≠ `_lesson.teacherid` | kim kimning darsiga kirgan |
| **Tahlil muvaffaqiyati** | `_audio.aierror` | yozuv bor, tahlil yiqilgan — o'qituvchining aybi emas |
| **Kelish vaqti** | `_payroll.kunlarjson` (Face ID turniket) | 1-darsi bor, maktabga 8:50 da kelgan |

Oxirgi qator alohida diqqatga sazovor: turniket ma'lumoti **allaqachon Moodle'da**,
`_payroll.kunlarjson` ichida. 2-bosqich uchun bepul signal.

### 3.4 Hisobot kimga yoziladi — attribution qoidasi

Qaror (2026-10-29): **darsni o'tgan odamga**. Rejadagi o'qituvchi kasal bo'lib, darsni
boshqasi o'tgan bo'lsa — hisobot o'tganiga yoziladi.

```
attributeduserid = COALESCE(_fact.writeruserid, _slot.teacheruserid)
```

Ya'ni:

| Holat | Hisobot kimga |
|---|---|
| Darsni kimdir yozdi | **yozgan odamga** (`_lesson.teacherid`) |
| Dars umuman yozilmagan | **rejadagi o'qituvchiga** — aks holda "yozmadi" hodisasi hech kimga tegmay qoladi |

Shu sababli `attributeduserid` `_fact` da **alohida ustun bo'lib saqlanadi**, har
so'rovda `COALESCE` qilinmaydi: panelning hamma yig'indisi shu ustun bo'yicha
guruhlanadi va u indekslanishi kerak.

Rejadagi o'qituvchi kartasida dars **"o'rnini bosildi"** belgisi bilan ko'rinadi,
lekin uning ko'rsatkichiga kirmaydi — direktor yuklama o'zgarganini ko'rishi kerak,
boshqa odamning ishi uchun ball esa yozilmasligi kerak.

---

## 4. Ma'lumotlar arxitekturasi

### 4.1 Qarorlar va sabablari

| № | Qaror | Sabab |
|---|---|---|
| **A1** | Dars o'rni (`_slot`) materiallashtiriladi | 21 000 qatorni PHP da hisoblab bo'lmaydi; SQL da saralash/filtrlash/yig'ish kerak |
| **A2** | O'tgan sanalar slotlari qotib qoladi | tarix o'zgarmasin (3.1) |
| **A3** | O'qituvchi uchun alohida o'lchov jadvali (`_staff`) | ikki xil kalitni bir joyda bog'lash; nomuvofiqlikni ko'rinadigan qilish |
| **A4** | Reja ↔ fakt taqqoslash alohida 1:1 jadvalda (`_fact`) | slot — o'lchov (dimension), fakt — qayta hisoblanadigan natija; aralashtirmaslik kerak |
| **A5** | AI kategoriyalari JSON'dan yassi jadvalga chiqariladi (`_aicat`) | SQL bilan trend va domen tahlili |
| **A6** | Fidbek — o'z jadvali, zanjirli (thread) | direktor ↔ o'qituvchi muloqoti; Moodle'da bunga mos vosita yo'q |
| **A7** | Fakt **sana oralig'i bo'yicha idempotent** qayta hisoblanadi | "dirty flag" lar eskirib qoladi va jim yiqiladi; qayta hisoblash har safar bir xil natija beradi |
| **A8** | Foizlar saqlanmaydi — **yig'indi + sanoq** saqlanadi | `sum/count` istalgan davrga qo'shiladi, `avg` esa qo'shilmaydi (klassik xato) |
| **A9** | Kunlik rollup 2-bosqichga qoldiriladi | chorakda 21 000 qator — indeksli `SUM()` uchun arzon; oldindan optimizatsiya qilmaymiz |

### 4.2 Jadvallar

Nomlash `db/install.xml` dagi uslubga mos: `local_targetzenit_*`.

#### `local_targetzenit_staff` — o'qituvchi o'lchovi va kalit ko'prigi

| Maydon | Tip | Izoh |
|---|---|---|
| `id` | int(10) PK | |
| `academicyear` | char(50) | |
| `teacherkey` | char(100) | Frappe `Employee.name` — **yagona haqiqiy kalit** |
| `userid` | int(10) null | `user.idnumber = teacherkey` orqali topilgan |
| `teachername` | char(255) | **faqat ko'rsatish va diagnostika uchun** |
| `holat` | char(20) | `ok` \| `userless` (Moodle'da bunday idnumber yo'q) \| `legacykey` (`INSTR-*`) |
| `lessonsweek` | int(4) | haftalik yuklama (rejadan) |
| `subjects` | char(255) | filtr uchun fanlar ro'yxati |
| `timemodified` | int(10) | |

`UNIQUE(academicyear, teacherkey)` · `INDEX(userid)` · `INDEX(holat)`

> **Qoida (loyiha bo'ylab amal qiladi):** bog'lanish faqat o'zgarmas unikal ID bilan.
> `teachername` hech qachon bog'lash uchun ishlatilmaydi — u faqat `legacykey`
> ogohlantirishini chiqarish uchun bor, xuddi `timetable::kalit_nomuvofiqmi()` da.

#### `local_targetzenit_slot` — rejalashtirilgan dars o'rni

| Maydon | Tip | Izoh |
|---|---|---|
| `id` | int(10) PK | |
| `academicyear` | char(50) | |
| `lessondate` | char(10) | `YYYY-MM-DD` (mavjud jadvallar bilan bir xil format) |
| `dayno` | int(2) | |
| `lessonno` | int(3) | |
| `termname` | char(255) | qaysi chorak (`_calendar` dan) |
| `groupkey` / `groupname` | char(255) | |
| `subjectkey` / `subjectname` | char(100)/(255) | |
| `teacherkey` | char(100) | **rejadagi** o'qituvchi |
| `teacheruserid` | int(10) null | `_staff` dan ko'chirilgan |
| `courseidnumber` | char(255) | |
| `courseid` | int(10) null | Moodle kurs id (hal qilingan) |
| `starttime` / `endtime` | char(10) | |
| `room` | char(100) | |
| `frozen` | int(1) | 1 = o'tmish, qayta qurilmaydi (A2) |
| `timemodified` | int(10) | |

`UNIQUE(academicyear, lessondate, lessonno, courseidnumber)`
`INDEX(lessondate, teacheruserid)` · `INDEX(teacheruserid, lessondate)` · `INDEX(courseid, lessondate)` · `INDEX(termname, teacheruserid)`

Hajm: ~21 000 qator/chorak, ~84 000/yil. Kichik.

#### `local_targetzenit_fact` — reja ↔ fakt (1:1 slot bilan)

| Maydon | Tip | Izoh |
|---|---|---|
| `id` | int(10) PK | |
| `slotid` | int(10) | **UNIQUE** |
| `lessonid` | int(10) null | `_lesson.id`, agar yozilgan bo'lsa |
| `writeruserid` | int(10) null | haqiqatda yozgan odam (o'rnini bosishni aniqlash) |
| `attributeduserid` | int(10) null | **hisobot kimga yoziladi** (3.4) — hamma yig'indi shu ustun bo'yicha |
| `substituted` | int(1) | 1 = rejadagi o'qituvchi emas, boshqasi o'tgan |
| `hastopic` / `hashomework` | int(1) | |
| `studentcount` / `attendancecount` | int(4) | yo'qlama qamrovi — foizni UI hisoblaydi |
| `gradecount` | int(4) | |
| `absentcount` / `latecount` | int(4) | sinf natijasi signali |
| `audiostatus` | char(20) | `none` \| `ready` \| `transcribed` \| `deleted` \| `failed` |
| `audioseconds` | int(10) | qamrov hisoblash uchun |
| `analysestatus` | char(20) | `none` \| `pending` \| `ok` \| `failed` |
| `overallscore` | int(4) null | AI vaznli foiz |
| `normdelta` | int(4) null | `overallscore − ai::norm_overall()` — saralash uchun |
| `lowconfcount` | int(3) | hisobga olinmagan kategoriyalar soni |
| `firstwriteat` | int(10) null | mavzu birinchi saqlangan vaqt (kechikish) |
| `feedbackcount` | int(3) | |
| `timemodified` | int(10) | |

`UNIQUE(slotid)` · `INDEX(lessonid)` · `INDEX(analysestatus)`

`normdelta` ataylab denormallashtirilgan: direktor "normadan eng ko'p pastda" bo'yicha
saralashni so'raydi, buni har safar PHP da hisoblash sahifani sekinlashtiradi.

#### `local_targetzenit_aicat` — kategoriya ballari (yassi)

| Maydon | Tip |
|---|---|
| `id` | int(10) PK |
| `lessonid` | int(10) |
| `code` | char(40) — `clarity`, `activating`, … |
| `score` | int(4) |
| `confidence` | char(10) |
| `weight` | int(3) |
| `timemodified` | int(10) |

`UNIQUE(lessonid, code)` · `INDEX(code)`

`ai::save()` da JSON yozilayotgan paytda **bir vaqtda** shu qatorlar ham yoziladi
(JSON yagona manba bo'lib qoladi — bu faqat indeks). Mavjud tahlillar uchun
`cli/aicat_backfill.php`.

#### `local_targetzenit_feedback` — fidbek va muloqot

| Maydon | Tip | Izoh |
|---|---|---|
| `id` | int(10) PK | |
| `scope` | char(20) | `lesson` \| `teacher` (davr bo'yicha umumiy) |
| `slotid` / `lessonid` | int(10) null | darsga bog'langanda |
| `teacheruserid` | int(10) | **kim haqida** |
| `authoruserid` | int(10) | **kim yozdi** |
| `parentid` | int(10) null | zanjir (o'qituvchi javobi) |
| `academicyear` / `termname` | char(50)/(255) | davr fidbegi uchun |
| `kind` | char(20) | `tavsiya` \| `ogohlantirish` \| `rahmat` |
| `category` | char(40) null | shablon tegi: `mavzu_yoq`, `yozuv_yoq`, `savol_kam`, … |
| `body` | text | |
| `visibility` | char(20) | `teacher` (o'qituvchi ko'radi) \| `private` (faqat ma'muriyat) |
| `requiresreply` | int(1) | |
| `duedate` | int(10) null | |
| `status` | char(20) | `new` \| `seen` \| `answered` \| `closed` |
| `seenat` | int(10) | |
| `timecreated` / `timemodified` | int(10) | |

`INDEX(teacheruserid, timecreated)` · `INDEX(lessonid)` · `INDEX(status)` · `INDEX(parentid)`

Bildirishnoma — **standart Moodle message API** (`db/messages.php` da message
provider). Yangi kanal yozilmaydi: o'qituvchi fidbekni pochtada, mobil ilovada va
qo'ng'iroqchada ko'radi.

#### `local_targetzenit_daily` — 2-BOSQICH, hozir qurilmaydi

Agar chorak oralig'idagi `SUM()` 300 ms dan oshsa yoki ko'p yillik trend kerak bo'lsa:
`(teacheruserid, lessondate)` bo'yicha bitta qator, ichida **yig'indi va sanoq**
(A8 — hech qachon o'rtacha emas). Hozircha kerak emas; shart yuzaga chiqqanda
qo'shiladi.

### 4.3 Ma'lumot oqimi va vazifalar

```
Frappe push_timetable ──┐
                        ├──►  task: slot_build         (push'dan keyin + har kuni 04:00)
_calendar o'zgarishi ───┘      • faqat bugun va keyin
                               • o'tgan slotlar frozen=1, tegilmaydi

o'qituvchi dars kokpitida ishlaydi (_lesson, _record, _audio)
                        │
                        ▼
                   task: fact_refresh
                   • har 15 daqiqada: [bugun−7 … bugun+1]
                   • har kuni 04:30: butun joriy chorak
                   • idempotent: sana oralig'i bo'yicha qayta hisoblanadi (A7)

ai::analyse() ──► _audio.analysis (JSON)
              └─► _aicat qatorlari + _fact.overallscore/normdelta
```

`fact_refresh` ni **oraliq bo'yicha** qurish qarori — eng muhim barqarorlik qarori:
bayroqlar (dirty flag) yo'qoladi, navbatlar tiqiladi, qayta hisoblash esa har safar
bir xil natija beradi va istalgan vaqtda qo'lda yurgizilishi mumkin
(`cli/fact_refresh.php --from=2026-09-01 --to=2026-10-29`).

### 4.4 Ruxsatlar va direktor logini

Yangi ruxsatlar (`db/access.php`):

| Ruxsat | Tip | Kontekst | Arxetip |
|---|---|---|---|
| `local/targetzenit:viewteachers` | read | SYSTEM | `manager` |
| `local/targetzenit:givefeedback` | write | SYSTEM | `manager` |
| `local/targetzenit:viewprivatefeedback` | read | SYSTEM | `manager` |

`viewanalysis` (mavjud) tahlil va transkript uchun qayta ishlatiladi — yangi ruxsat
yaratilmaydi.

**Login:** alohida autentifikatsiya **qurilmaydi**. Direktor — oddiy Moodle
foydalanuvchisi, unga tizim kontekstida `Direktor` roli beriladi.

> **Qoida:** foydalanuvchi **Frappe'da** `Employee` sifatida yaratiladi va 07:00
> sinxronida Moodle'ga tushadi (`idnumber = Employee.name`). Moodle'da qo'lda
> yaratilgan hisob keyinchalik hech qanday ID bilan bog'lanmaydi va hisobotlarda
> g'oyib bo'ladi.

Rol o'zi: `cli/direktor_rol.php` (bir martalik, `manager` arxetipidan nusxa + yuqoridagi
uchta ruxsat). Plagin **avtomatik rol yaratmaydi** — Moodle'da rol yaratish
ma'murning qarori, va avtomatik yaratish migratsiyada jim yiqilishi mumkin.

### 4.5 Maxfiylik — bajarilishi shart bo'lgan ish

`classes/privacy/provider.php` hozir "plagin shaxsiy ma'lumot saqlamaydi" deb turadi.
Bu allaqachon to'liq to'g'ri emas (`_record` da o'quvchi bahosi, `_audio.transcript`
da o'quvchilar nutqi), fidbek jadvali qo'shilgach esa umuman noto'g'ri bo'ladi:
direktorning nomma-nom o'qituvchi haqidagi bahosi — eng nozik toifadagi ma'lumot.

Kerak:
1. Haqiqiy `metadata` (har jadval, har maydon, maqsadi bilan).
2. `core_userlist_provider` + eksport/o'chirish amalga oshirilishi.
3. Transkript uchun ham saqlash muddati — audio 15 kunda o'chadi, transkript esa
   **abadiy** qolyapti va unda voyaga yetmaganlarning nutqi bor.

> Transkriptda o'quvchi ismlarini pseudonimlashtirish (`O'quvchi (Aziz)` → `O'quvchi 3`)
> texnik jihatdan oson va direktor paneli uchun hech narsa yo'qotmaydi. 6-bo'limdagi
> ochiq savol.

---

## 5. Dizayn rejasi (UI/UX)

### 5.1 Tamoyillar — bu mahsulot uchun maxsus

Dars kuzatuvi paneli — **oddiy dashboard emas**. U odamlar haqida baho chiqaradi,
shuning uchun dizaynning o'zi etik qoidalarni ushlab turishi kerak. Mavjud kod bu
qoidalarni allaqachon o'zida tutgan (xalqaro norma, `confidence`, "ayblov so'zlarini
ishlatma") — panel ularni buzmasligi shart.

1. **Normaga nisbatan, mutlaq emas.** 59% — o'rtacha dars. Uni qizil ko'rsatish
   yolg'on. Har ball o'z kategoriyasining xalqaro o'rtachasiga nisbatan rangalanadi.
2. **Namuna hajmi ko'rinadi.** 2 ta darsdan chiqqan "64%" — fakt emas. Har foiz
   yonida `n=` turadi, `n < 5` bo'lsa raqam susaytiriladi.
3. **Raqam dalilga olib boradi.** Har ko'rsatkich bosiladi va oxir-oqibat
   transkriptdagi aniq joyga tushadi. "Dalilsiz raqam yo'q" — mavjud
   `prompt_analyse()` dagi qoidaning UI'dagi davomi.
4. **Reyting emas, ro'yxat.** O'qituvchilar standart holda **ism bo'yicha**
   saralanadi. "Diqqat tartibida" saralash — ataylab bosiladigan tanlov.
5. **Ishonch darajasi yashirilmaydi.** `confidence='low'` — "audio orqali
   baholanmadi", bo'sh joy yoki nol emas.
6. **Oyna ikki tomonga qaraydi.** O'qituvchi direktor ko'rgan narsani ko'radi
   (`private` qaydlardan tashqari). Yashirin baho ishonchni buzadi.

### 5.2 Ma'lumot arxitekturasi — to'rt qatlam

```
Bugun  ──►  O'qituvchilar  ──►  O'qituvchi kartasi  ──►  Dars kartasi
(maktab)      (37 qator)         (chorak × fan)          (bitta dars)
   │                                                           │
   └──────────────── Fidbek paneli (har qatlamdan) ────────────┘
```

Sahifalar:

| Fayl | Ko'rinish |
|---|---|
| `direktor.php?view=bugun` | kunlik pulse (standart) |
| `direktor.php?view=oqituvchilar` | ro'yxat + ko'rsatkichlar |
| `direktor.php?view=oqituvchi&staffid=N` | o'qituvchi kartasi |
| `direktor.php?view=dars&slotid=N` | dars kartasi (faqat o'qish) |
| `direktor.php?view=fidbek` | fidbek navbati |
| `fidbeklarim.php` | **o'qituvchi tomoni** — o'ziga kelgan fidbek |

### 5.3 Ekran 1 — «Bugun»

Eng muhim UX qarori: direktor **qidirmaydi, unga ko'rsatiladi**. Shuning uchun
sahifaning asosiy elementi — setka emas, **diqqat navbati**.

```
┌────────────────────────────────────────────────────────────────────────┐
│ Direktor paneli         Bugun │ O'qituvchilar │ Tahlil │ Fidbek        │
│ 29-oktabr, chorshanba · 1-chorak · 14:35 da yangilangan   [o'lcham ⇕] │
├────────────────────────────────────────────────────────────────────────┤
│   186          142           78%            61%           +4%          │
│   bugungi      o'tdi         jurnal         yozuv         normaga      │
│   dars         (44 qoldi)    to'ldirildi    olindi        nisbatan     │
│                              ▁▂▄▃▅▆▅        ▁▁▂▂▃▂▃       ▃▄▃▅▄▅▆     │
├────────────────────────────────────────────────────────────────────────┤
│ ⚠  DIQQAT TALAB QILADI (12)                      [hammasini ko'rish →] │
│                                                                         │
│  ▌ 10:20 · 3-dars · Matematika · 7-A · Karimov A.                      │
│  ▌ mavzu yozilmagan · yo'qlama yo'q                    [Fidbek] [Dars →]│
│                                                                         │
│  ▌ 09:30 · 2-dars · Ingliz tili · 5-B · Yusupova M.                    │
│  ▌ 4 dars ketma-ket yozuv yo'q                         [Fidbek] [Dars →]│
│                                                                         │
│  ▌ 08:40 · 1-dars · Fizika · 9-A · Rahimov B.          normadan −18%   │
│  ▌ tahlil: ochiq savollar 14 dan 1 ta                  [Fidbek] [Dars →]│
├────────────────────────────────────────────────────────────────────────┤
│ KUN SETKASI                        1   2   3   4   5   6   7            │
│ Karimov A.        Matematika       ■   ■   ◪   ◻   ·   ·   ·            │
│ Yusupova M.       Ingliz tili      ■   ◻   ·   ■   ■   ·   ·            │
│ Rahimov B.        Fizika           ◪   ·   ■   ■   ·   ·   ·            │
│                                                                         │
│ ■ 4/4 vazifa   ◪ qisman   ◻ bo'sh   · darsi yo'q      [jadval ko'rinish]│
└────────────────────────────────────────────────────────────────────────┘
```

**Diqqat navbati tartibi** (yuqoridan pastga, qat'iy ustuvorlik):

| Daraja | Shart | Holat rangi |
|---|---|---|
| 1 | O'tgan dars, mavzu ham yo'qlama ham yo'q | `critical` |
| 2 | N dars ketma-ket yozuv yo'q (sozlamadagi normadan past) | `serious` |
| 3 | `normdelta ≤ −15` **va** `lowconfcount` kichik (ishonchli) | `serious` |
| 4 | Jurnal 48 soatdan ko'p kechikib to'ldirilgan | `warning` |
| 5 | Yozuv bor, tahlil yiqilgan (`analysestatus='failed'`) | `warning` — **tizim muammosi, o'qituvchining aybi emas** |
| 6 | 30 kundan beri hech qanday fidbek olmagan o'qituvchi | `warning` |

5-darajani alohida belgilash muhim: aralashtirilsa o'qituvchi texnik xato uchun
javob beradi.

### 5.4 Ekran 2 — «O'qituvchilar»

7 dan ko'p ma'noli ko'rsatkich bor → forma **jadval**, grafik emas (grafik ichida
jadval emas). Har ustun — o'z o'rnidagi mikro-vizualizatsiya.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [1-chorak ▾] [Hamma sinf ▾] [Hamma fan ▾] [qidirish...]   Saralash: ism ▾    │
├──────────────────────────────────────────────────────────────────────────────┤
│ O'qituvchi      Yuklama  Mavzu   Yo'qlama  Baho    Yozuv   Tahlil     Trend  │
│                 d/hafta                                    (n=)              │
├──────────────────────────────────────────────────────────────────────────────┤
│ Karimov A.         24   ████▏96% ███▉ 92%  ██▌58%  ██▏44%  +6%  n=11 ▁▃▄▅▆▅ │
│ Yusupova M.        18   ███▎ 78% ████ 98%  ███ 71%  ▊ 12%  −14% n=3  ▂▂▁▂   │
│ Rahimov B.         22   ████▎99% ████▏97%  ██▏46%  ███▏66% +1%  n=14 ▄▄▅▄▅▆ │
└──────────────────────────────────────────────────────────────────────────────┘
```

- **Mavzu / Yo'qlama / Baho / Yozuv** — bir xil o'lchov yo'lidagi **meter**lar
  (bir hue, "ko'proq = to'qroq"), nisbat chegaraga nisbatan o'qiladi. Pie emas.
- **Tahlil** — normaga nisbatan **diverging** ko'rsatkich (+ ko'k / − qizil), yonida
  `n=` — namuna hajmi. `n < 5` bo'lsa raqam susaytiriladi (2-tamoyil).
- **Trend** — sparkline, bitta chiziq, raqamsiz. Stat tile qoidasi.
- Standart saralash — **ism**. "Diqqat tartibida" alohida tanlanadi (4-tamoyil).

### 5.5 Ekran 3 — «O'qituvchi kartasi»

```
┌──────────────────────────────────────────────────────────────────────────┐
│ ← O'qituvchilar                                                          │
│                                                                           │
│  ┌────┐  Karimov Alisher            Matematika, Geometriya                │
│  │ AK │  HR-EMP-00012               7-A, 7-B, 8-A · 24 dars/hafta         │
│  └────┘  1-chorak: 263 dars rejada · 251 o'tilgan · 11 tahlil qilingan    │
│                                                                           │
│  Darslar │ Tahlil │ Fidbek (3) │ Yozuvlar                                 │
├──────────────────────────────────────────────────────────────────────────┤
│  CHORAK XARITASI            sentabr            oktabr                     │
│                        1  4  5  6  7 ...  1  2  5  6  7  8 ...            │
│   7-A Matematika       ▓  ▓  ▒  ▓  █      █  ▓  ░  ▓  ▓  █                │
│   7-B Matematika       ▓  █  ▓  ▓  ▒      ▓  ▓  ▓  ░  █  ▓                │
│   8-A Geometriya       █  ▓  ▓  ░  ▓      ▓  █  ▓  ▓  ▒  ▓                │
│                                                                           │
│   Bajarilgan vazifa:  ░ 0-1   ▒ 2   ▓ 3   █ 4            [jadval ⇄]      │
└──────────────────────────────────────────────────────────────────────────┘
```

**«Tahlil» yorlig'i** — asosiy vizualizatsiya. Forma tanlovi muhim: 15 kategoriyani
*radar* bilan ko'rsatish keng tarqalgan xato (burchaklar maydonni buzadi, taqqoslash
mumkin emas). Ma'lumotning vazifasi — **"normadan yuqorimi yoki pastmi"**, ya'ni
polyarlik. To'g'ri forma — **nolga (normaga) markazlashgan diverging bar**:

```
TA'LIM SIFATI                       normadan past ◄── norma ──► normadan yuqori
  Tushuntirish aniqligi (65%)                    │███████▶  +12
  O'quvchilar faolligi (50%)              ◀████──│          −9
  Mavzuga muvofiqlik (80%)                       │██▶        +4
  Tushunchani chuqurlashtirish (30%)             │█████████▶ +15
  Tabaqalashtirilgan yondashuv (35%)      ░░░░░░░│  audio orqali baholanmadi

EMOTSIONAL QO'LLAB-QUVVATLASH
  Ijobiy muhit (70%)                             │████▶      +7
  ...
```

Har qatorning nol nuqtasi — **o'sha kategoriyaning o'z xalqaro o'rtachasi**
(`ai::CATEGORIES[...]['norm']`), hamma uchun umumiy 100 emas. Mavjud `dars.php` dagi
`tz-norm` belgisining mantiqiy davomi, lekin taqqoslash osonlashadi: hamma nol
nuqta bitta vertikal chiziqda turadi.

Yonida — chorak bo'yicha **umumiy ball trendi**: bitta chiziq (emphasis formasi),
fon sifatida xalqaro o'rtacha gorizontal bazaviy chiziq. Ikkinchi o'q **yo'q**.

### 5.6 Ekran 4 — «Dars kartasi» va fidbek

Dars kartasi mavjud `dars.php` dagi tahlil blokini **faqat o'qish rejimida** qayta
ishlatadi (kod takrorlanmaydi — umumiy renderer `classes/output/analysis.php` ga
ko'chiriladi). Qo'shimcha:

- audio pleyer (muddati o'tmagan bo'lsa) + transkript, ichidan qidirish bilan
- jurnal holati: mavzu, uy vazifa, yo'qlama taqsimoti, baholar
- **fidbek paneli** — o'ng tomondan chiqadigan drawer

```
┌─────────────────────────────────┐
│ Fidbek yozish              [×]  │
│                                  │
│ Dars: 29.10 · 3-dars · 7-A      │
│ O'qituvchi: Karimov A.          │
│                                  │
│ Turi:  (•) Tavsiya               │
│        ( ) Ogohlantirish         │
│        ( ) Rahmat                │
│                                  │
│ Tez tanlov:                      │
│ [mavzu yozilmagan]               │
│ [yozuv yo'q] [ochiq savol kam]   │
│ [ajoyib dars]                    │
│                                  │
│ ┌──────────────────────────────┐ │
│ │                              │ │
│ └──────────────────────────────┘ │
│                                  │
│ ☑ O'qituvchi ko'radi             │
│ ☐ Javob kutiladi  [sana]         │
│                                  │
│        [Bekor]  [Yuborish]       │
└─────────────────────────────────┘
```

Standart holat: **`tavsiya`** turi va **o'qituvchi ko'radi** belgisi yoqilgan
(6-tamoyil). `private` qayd — ataylab tanlanadigan istisno, standart emas.

O'qituvchi tomoni: `fidbeklarim.php` + dars kokpitidagi belgi (badge) + Moodle
bildirishnomasi. Javob yozish mumkin — zanjir (`parentid`).

### 5.7 Dizayn tizimi

Mavjud `styles.css` da ranglar qotib yozilgan (`#fff`, `#6a737d`). Moodle 5.3
Boost'da **qorong'i rejim** bor — yangi sahifa faqat **token** bilan yoziladi:

```css
.tz-dir {
  color-scheme: light;
  --tz-surface:      #fcfcfb;
  --tz-ink:          #0b0b0b;
  --tz-ink-2:        #52514e;

  /* Holat — o'zgarmas, hech qachon seriya rangi sifatida ishlatilmaydi */
  --tz-good:         #0ca30c;
  --tz-warning:      #fab219;
  --tz-serious:      #ec835a;
  --tz-critical:     #d03b3b;

  /* Ketma-ket (miqdor): bitta hue, ko'proq = to'qroq */
  --tz-seq-100:      #cde2fb;
  --tz-seq-250:      #86b6ef;
  --tz-seq-400:      #3987e5;
  --tz-seq-550:      #1c5cab;
  --tz-seq-700:      #0d366b;

  /* Diverging (polyarlik): ko'k ◄ kulrang ► qizil */
  --tz-div-pos:      #2a78d6;
  --tz-div-zero:     #f0efec;
  --tz-div-neg:      #d03b3b;

  /* Seriya (4 ta vazifa trendi) */
  --tz-s1: #2a78d6;  --tz-s2: #eb6834;
  --tz-s3: #1baf7a;  --tz-s4: #eda100;
}
@media (prefers-color-scheme: dark) {
  :root:where(:not([data-theme="light"])) .tz-dir { /* qorong'i qadamlar */ }
}
:root[data-theme="dark"] .tz-dir { /* o'sha qadamlar — toggle ikki tomonga yutadi */ }
```

4 ta seriya rangi **tekshirilgan** (validator, OKLab ×100):

| Rejim | CVD ΔE (eng yomon qo'shni) | Oddiy ko'rish ΔE | Kontrast |
|---|---|---|---|
| yorug' | 9.1 (`#eda100`↔`#1baf7a`) | 22.9 | 2 ta rang 3:1 dan past → **to'g'ridan-to'g'ri yorliq yoki jadval ko'rinishi shart** |
| qorong'i | 8.4 | 19.8 | hammasi ≥ 3:1 |

Shuning uchun 4 chiziqli trend grafigida **har chiziq o'z yorlig'i bilan** belgilanadi
va har grafik yonida `[jadval ⇄]` tugmasi turadi.

Holat belgilari **hech qachon faqat rang bilan** ifodalanmaydi: `■ ◪ ◻ ·` shakl +
matnli yorliq + `title`. Rangni ajratmaydigan odam ham, qora-oq chop etishda ham
o'qiladi.

Boshqa qoidalar:
- **Tashqi kutubxona yo'q.** Grafiklar — qo'lda yozilgan inline SVG va CSS.
  Moodle'da CDN'ga tayanish ham CSP, ham oflayn ishlash bo'yicha noto'g'ri.
- **Ikkinchi o'q yo'q.** Hech bir grafikda ikki o'lchov bitta o'qda emas.
- **Pie yo'q.** Nisbat — meter, part-to-whole — gorizontal stacked bar.
- Filtrlar **bitta qatorda**, grafiklardan yuqorida; holat URL'da saqlanadi
  (havolani ulashish mumkin).
- Har markda hover tooltip; tegish maydoni markdan katta.
- Mavjud `lib.php: local_targetzenit_zoom_panel()` qayta ishlatiladi — bu
  foydalanuvchilar zich jadvalni yaxshi ko'radi, o'lcham tanlovi allaqachon bor.
- Mobil: «Bugun» faqat diqqat navbatiga yig'iladi, ro'yxat kartaga aylanadi,
  xarita gorizontal skrol + yopishgan ism ustuni (`styles.css` dagi mavjud
  `position: sticky` namunasi).

---

## 6. Bosqichlar

| № | Ish | Natija |
|---|---|---|
| 0 | 3 ta ruxsat, `Direktor` roli (CLI), navigatsiya | direktor kira oladi |
| 1 | `_staff`, `_slot`, `_fact` + `slot_build`, `fact_refresh` + CLI | reja ↔ fakt tayyor |
| 2 | «Bugun» ekrani: hero, diqqat navbati, kun setkasi | kundalik nazorat ishlaydi |
| 3 | «O'qituvchilar» + «O'qituvchi kartasi» (xarita, trend) | davriy nazorat |
| 4 | `_aicat` + backfill + kategoriya profili | metodik tahlil |
| 5 | `_feedback` + drawer + `fidbeklarim.php` + message provider | muloqot yopiladi |
| 6 | `privacy/provider.php`, transkript muddati, hujjat | **majburiy**, 5 dan keyin kechikmaydi |
| 7 | ixtiyoriy: `_daily` rollup, Face ID kelish signali, KPI'ni Frappe'ga qaytarish | |

Har bosqich o'zi yakka holda foydali va deployga tayyor bo'ladi.

**Qamrovdan tashqarida:** yangi autentifikatsiya tizimi (Moodle roli ishlatiladi),
o'qituvchini reyting bo'yicha avtomatik jazolash/bonuslash (oylik Frappe'da,
`_payroll` faqat nusxa), AI'ning o'qituvchi haqida hissiy/shaxsiy xulosa chiqarishi
(`prompt_analyse()` da ataylab taqiqlangan — panel bu chegarani buzmaydi).

---

## 7. Hal qilinishi kerak bo'lgan savollar

Bular javobiga qarab **arxitektura o'zgaradi**, shuning uchun kod boshlanishidan
oldin aniqlanishi kerak:

1. **Yozuv majburiymi?** Har darsda yoki tanlab (masalan haftada 2 ta)? Bu "yozmadi"
   belgisining ma'nosini va diqqat navbatining 2-darajasini butunlay o'zgartiradi.
2. **Baho normasi qancha?** Darslarning necha foizida baho bo'lishi kutiladi? Frappe
   tomonda zavuch baholash sozlamalari allaqachon bor — norma shu yerdan kelishi
   kerakmi yoki Moodle sozlamasida turadimi?
3. **Direktordan tashqari kim ko'radi?** Zavuchlar sinf/fan bo'yicha cheklangan
   ko'rinish oladimi? Agar ha — `_slot` ga filtr o'lchovi (sinf darajasi) qo'shiladi
   va ruxsat kurs kategoriyasi kontekstida tekshiriladi.
4. **O'qituvchi o'zi haqidagi hamma raqamni ko'radimi?** 6-tamoyil shuni taklif
   qiladi (`private` qaydlardan tashqari), lekin bu boshqaruv qarori.
5. **Transkriptda o'quvchi ismlari qolsinmi?** Pseudonimlashtirish panel uchun hech
   narsa yo'qotmaydi, maxfiylik yuki esa keskin kamayadi.
6. **O'rnini bosish qanday hisoblanadi?** Rejadagi o'qituvchi kasal, darsni boshqasi
   o'tdi — hisobot kimga yozilsin? (Taklif: ikkisiga ham ko'rinsin, rejadagiga
   "o'rnini bosildi" belgisi bilan — shuning uchun `_fact.writeruserid` bor.)
