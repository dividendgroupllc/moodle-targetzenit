# Moodle ilovasi (mobil) — arxitektura va bosqichli reja

Maqsad: `local_targetzenit` da qurilgan narsalar Moodle'ning rasmiy mobil
ilovasida ham ishlasin.

---

## 1. Asosiy haqiqat: ilova bizning sahifalarimizni chizmaydi

Moodle ilovasi — veb-sayt emas, **Ionic/Angular ilovasi**. U HTML sahifani
ochmaydi, balki web-servis orqali **ma'lumot** oladi va uni o'z komponentlari
bilan chizadi.

Shuning uchun `oylik.php`, `index.php`, `jurnal.php` kabi sahifalar ilovada
o'z-o'zidan paydo bo'lmaydi. Har bir panel uchun **ikkinchi, ilovaga mo'ljallangan
qatlam** yoziladi:

```
Brauzer:   oylik.php      -> html_writer -> HTML
Ilova:     mobile::payroll -> mustache   -> Ionic shabloni -> ilova chizadi
```

Ikkalasi ham **bitta manbadan** oziqlanadi (`classes/payroll.php`,
`classes/timetable.php`, `classes/lesson.php`). Ma'lumot mantig'i
takrorlanmaydi — faqat ko'rinish qatlami ikkita.

---

## 2. Yarmi ALLAQACHON ishlaydi — qayta yozilmaydi

Bu rejadagi eng muhim qaror. Quyidagilar ilovada hozir ham ko'rinadi, chunki
biz ularni Moodle'ning **o'z** tizimlariga yozganmiz:

| Narsa | Ilovada qayerda | Nega ishlaydi |
|---|---|---|
| Baholar | Grades | `gradebook::push()` baholarni Moodle gradebook'iga yozadi |
| Uy vazifa | Assignments, Timeline, Calendar | `homework::ensure()` `mod_assign` yaratadi |
| Kurslar, guruhlar | My courses | Frappe sinxroni kursga yozadi |
| Chorak o'rtachasi | Grades | `grade_category` (GRADE_AGGREGATE_MEAN) |

Ya'ni o'quvchi va ota-ona uchun eng kerakli narsa — baho va uy vazifa —
**bugun ham ilovada bor**. Bu tasodif emas: "hamma narsa Moodle'ning o'zida
tursin" tamoyili aynan shu natijani bergan.

Ilova uchun alohida yoziladigan narsa — faqat Moodle'da ekvivalenti **yo'q**
bo'lganlar:

| Narsa | Moodle ekvivalenti | Xulosa |
|---|---|---|
| Dars jadvali | yo'q | ilova qatlami kerak |
| Mening oyligim | yo'q | ilova qatlami kerak |
| Dars kokpiti | yo'q | ilova qatlami kerak |
| Chorak jurnali | qisman (gradebook) | ehtimol veb'da qoladi |
| Dars audiosi | yo'q | texnik cheklov, 6-bosqichda hal qilinadi |
| AI tahlili | yo'q | ilova qatlami kerak |

---

## 3. Texnik tuzilma

### 3.1 Fayllar

```
db/mobile.php                      — ilovaga panellarni tanishtirish
classes/output/mobile.php          — har panel uchun bitta metod
templates/mobile_timetable.mustache
templates/mobile_payroll.mustache
templates/mobile_lesson.mustache
mobileapp/styles.css               — ilova uchun uslublar (ixtiyoriy)
```

### 3.2 `db/mobile.php` tuzilishi

```php
$addons = [
    'local_targetzenit' => [
        'handlers' => [
            'timetable' => [
                'delegate' => 'CoreMainMenuDelegate',   // asosiy menyuda element
                'method' => 'timetable_view',           // mobile.php dagi metod
                'displaydata' => [
                    'title' => 'timetable',             // til kaliti
                    'icon' => 'calendar',
                ],
            ],
        ],
        'lang' => [
            ['timetable', 'local_targetzenit'],         // ilovaga ko'chadigan satrlar
        ],
    ],
];
```

### 3.3 Metod nima qaytaradi

```php
return [
    'templates' => [['id' => 'main', 'html' => $OUTPUT->render_from_template(...)]],
    'javascript' => '',
    'otherdata' => ['hafta' => 1],      // shablonda <ion-select [(ngModel)]="hafta">
    'files' => [],
];
```

### 3.4 Muhim nuqtalar

- **Web-servis ro'yxatiga qo'shish SHART EMAS.** Ilova panellarni
  `tool_mobile_get_content` orqali oladi, u esa `db/mobile.php` ni o'zi
  o'qiydi. Qo'lda qo'shish faqat **qo'shimcha** funksiyalar uchun kerak
  (masalan yo'qlamani saqlash) — ular `db/services.php` da
  `'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE]` bilan e'lon qilinadi.
- **HTML emas, Ionic.** Shablonda `<table>` emas, `<ion-list>`, `<ion-item>`,
  `<ion-grid>` ishlatiladi. `html_writer` bu yerda ishlamaydi.
- **Til.** Shablonda `{{ 'plugin.local_targetzenit.mypayroll' | translate }}`.
  Satrlar `db/mobile.php` dagi `lang` ro'yxatiga kiritilgani uchun ilova
  ularni o'zi yuklab oladi — `lang/tools/strings.py` dagi uchta til shundoq
  ishlaydi, qayta tarjima qilinmaydi.
- **Kesh.** Ilova javobni keshlaydi; o'zgarish ko'rinmasa
  *Site administration → Development → Purge caches* va ilovada tortib
  yangilash (pull to refresh).

### 3.5 Old shart (bir marta)

- `enablemobilewebservice` yoqilgan bo'lishi kerak
  (*Site administration → Mobile app → Mobile settings*).
- `moodle_mobile_app` servisi yoqilgan bo'lishi kerak.
- Ilova `lms.target.erpcontrol.uz` manzilini qabul qiladi (HTTPS bor).

---

## 4. Bosqichlar

Tartib ataylab shunday: eng sodda va eng ko'p ishlatiladigandan boshlanadi,
har bosqich mustaqil ishlaydi va alohida deploy qilinadi.

### 0-bosqich — Poydevor

**Nima:** `db/mobile.php`, `classes/output/mobile.php`, birinchi bo'sh shablon.
Ilovada bitta menyu elementi paydo bo'ladi va "ishladi" deb yozadi.

**Nega alohida:** ilova qatlamining ishlashini eng kichik kod bilan
tasdiqlaymiz. Agar `enablemobilewebservice`, kesh yoki servis sozlamasida
muammo bo'lsa — u shu yerda chiqadi, murakkab shablon ichida emas.

**Tekshirish:** telefonda ilovaga kirib asosiy menyuda elementni ko'rish.

**Xavf:** past.

---

### 1-bosqich — Dars jadvali

**Nima:** `timetable_view` metodi + `mobile_timetable.mustache`.
Hafta / Bugun ko'rinishlari, o'qituvchiga o'z darslari, o'quvchiga o'z sinfi.

**Manba:** `classes/timetable.php` — yangi so'rov yozilmaydi.

**Ilova tomoni:** `<ion-list>` ichida kun bo'yicha guruhlangan
`<ion-item>` lar: dars raqami, vaqt, fan, xona, o'qituvchi.

**Qarorlar:**
- "Umumiy jadval" ilovaga **kiritilmaydi** — 934 qator telefonda noqulay,
  u veb'da qoladi.
- Kun tanlash `otherdata` orqali, sahifa qayta yuklanmaydi.

**Tekshirish:** o'qituvchi hisobi bilan — faqat o'z darslari; o'quvchi bilan —
o'z sinfi.

**Xavf:** past. Faqat o'qish.

---

### 2-bosqich — Mening oyligim

**Nima:** `payroll_view` + `mobile_payroll.mustache`.
Uchta karta (qo'lga tegadigan, ishlagan kun, bonus), hisob tafsiloti va
oylar kesimidagi hisob-kitob.

**Manba:** `classes/payroll.php`.

**Qarorlar:**
- **Kunma-kun davomat jadvali ilovaga kiritilmaydi** — 31 ustunli jadval
  telefon ekraniga sig'maydi. O'rniga qisqa satr: "22 kundan 19 tasida
  kelgan". To'liq jadval veb'da qoladi va unga havola beriladi.
- Maxfiylik veb'dagi bilan bir xil: `useridnumber` so'rovdan olinmaydi,
  faqat kirgan foydalanuvchining `idnumber` idan. Boshqa odamnikini ko'rish
  ilovada umuman bo'lmaydi.

**Tekshirish:** o'qituvchi hisobi bilan sonlar veb bilan bir xilmi.

**Xavf:** past. Faqat o'qish, maxfiy ma'lumot — ruxsat mantig'i qayta
tekshiriladi.

---

### 3-bosqich — Dars kokpiti (o'qituvchi uchun yozish)

**Nima:** mavzu yozish, yo'qlama, baho qo'yish — telefondan.

**Bu bosqich oldingilaridan tubdan farq qiladi:** bu yerda ilova **yozadi**.
Shuning uchun qo'shimcha narsalar kerak:

- `db/services.php` da yangi funksiya (`mobile_save_lesson`) va u
  `MOODLE_OFFICIAL_MOBILE_SERVICE` ga qo'shiladi.
- `offlinefunctions` — internet yo'qligida saqlab turish. Maktabda WiFi
  beqaror bo'lgani uchun bu haqiqiy ehtiyoj.
- Konflikt qoidasi: bir vaqtda veb va ilovadan baho qo'yilsa qaysi biri
  ustun — **oxirgi yozuv**, lekin `timemodified` solishtiriladi va eskisi
  yangisini bosib ketmaydi.

**Qarorlar:**
- Baho qo'yish gradebook'ga ham tushishi SHART (veb'dagi bilan bir xil yo'l,
  `gradebook::push`), aks holda ikki kanal ikki xil natija beradi.

**Tekshirish:** samolyot rejimida yo'qlama qilib, internet qaytgach
sinxronlanishini ko'rish.

**Xavf:** o'rtacha-yuqori. Yozish + oflayn + konflikt.

---

### 4-bosqich — AI tahlilini ko'rish

**Nima:** yozib olingan darsning tahlili — foizlar, kategoriyalar, xulosa.

**Manba:** `classes/ai.php` natijasi (`local_targetzenit_audio.analysis`).

**Ilova tomoni:** `<ion-card>` ichida foiz chiziqlari, kategoriya bo'yicha
ochiladigan ro'yxat.

**Qarorlar:**
- Faqat **ko'rish**. Qayta tahlil tugmasi ilovaga kiritilmaydi (u admin
  amali).
- Audio eshitish — `pluginfile` orqali, ruxsat veb'dagi bilan bir xil.

**Xavf:** past.

---

### 5-bosqich — Chorak jurnali

**Nima:** o'quvchilar × darslar katakli jadvali.

**Hozircha qaror: ILOVAGA KIRITILMAYDI.** Sabab: jadval 30 o'quvchi × 60 dars
bo'lishi mumkin, telefonda bu ishlamaydi. O'qituvchi jurnalni kompyuterda
to'ldiradi, telefonda esa kunlik ish (kokpit, 3-bosqich) qilinadi.

Agar baribir kerak bo'lsa — alohida tadqiqot bilan qaytamiz: ehtimol
"bitta dars → hamma o'quvchi" ko'rinishi (kokpitning o'zi) yetarli.

---

### 6-bosqich — Dars audiosini yozish

**Nima:** telefondan dars yozib olish.

**Texnik cheklov:** veb versiyasi `MediaRecorder` + bo'lak-bo'lak yuborish
bilan ishlaydi. Ilova ichidagi shablonda bu JavaScript **ishlamaydi** —
Ionic muhitida brauzer API lari cheklangan.

**Yo'llar (6-bosqichda aniqlanadi):**
1. Ilovaning o'z fayl yuklovchisi (`CoreFileUploader`) — telefon diktofonida
   yozib, keyin yuklash. Ikki qadam, lekin ishonchli.
2. Ilova ichidagi brauzer oynasida veb sahifani ochish — bitta tugma, lekin
   ilova tajribasidan chiqib ketadi.
3. Yozish telefon brauzerida qoladi, ilovada faqat natija ko'rinadi.

**Tavsiya:** 3-yo'l bilan boshlash (eng kam xavf), 1-yo'lni keyin qo'shish.

**Xavf:** yuqori. Shuning uchun oxirgi bosqich.

---

## 5. Umumiy qoidalar

1. **Ma'lumot mantig'i takrorlanmaydi.** Ilova metodlari `classes/*.php`
   dagi o'sha funksiyalarni chaqiradi. Yangi SQL yozilsa, veb va ilova
   sonlari bir-biridan uziladi — bu xato topilishi eng qiyin turdagi nosozlik.
2. **Ruxsat ikki joyda ham tekshiriladi.** Ilova metodi `require_capability`
   dan ozod emas: web-servis to'g'ridan-to'g'ri chaqirilishi mumkin.
3. **Har bosqich alohida deploy.** Plagin versiyasi oshiriladi, keyingi
   bosqich oldingisi ishlaganidan keyin boshlanadi.
4. **Telefonda sinaladi.** Emulyator yetarli emas — ilova keshi va oflayn
   xatti-harakati haqiqiy qurilmada boshqacha.
