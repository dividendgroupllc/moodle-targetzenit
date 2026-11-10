# AI dars tahlili — baholash ramkasi

**Holat:** kod yozilgan (0.42.0), lokalda mantiq testi o'tdi · 2026-11-10
**Yagona manba:** `classes/ai.php` → `ai::CATEGORIES`, `ai::PROFIL`, `ai::DARS_TURLARI`
**Vazifa:** AI har o'qituvchiga **yordamchi metodist** bo'lishi; nazorat vositasi emas.

---

## 1. Nega bu ramka — tadqiqot asosi

| Qisqartma | Nima | Nimaga olindi |
|---|---|---|
| **GTT** | Great Teaching Toolkit Evidence Review (Coe va h., 2020 — Cambridge + Durham) | **100 dan ortiq** dars baholash ramkasi va meta-tahlilni bitta modelga jamlagan: 4 o'lcham, 17 element. Skeletning asosi |
| **CLASS** | Classroom Assessment Scoring System (Pianta, Virginia u-ti) | Emotsional qatlam va o'zaro munosabatlar |
| **ICALT** | International Comparative Analysis of Learning and Teaching | Xalqaro **taqqoslash** uchun tasdiqlangan |
| **TEACH** | Jahon banki (Primary/Secondary) | Har ballni **kuzatiladigan xulq** bilan bog'lash usuli |
| **MQI** | Mathematical Quality of Instruction (Michigan + Harvard) | Aniq fanlar moduli |
| **PLATO** | Protocol for Language Arts Teaching Observation (Stanford) | Til darslari moduli |
| **TRU** | Teaching for Robust Understanding (Schoenfeld, Berkeley) | «Teng imkoniyat» — nutq kimda taqsimlangani |

Metodologik asos:
- **Gates MET (2013, 3 yil, 5 instrument)** — barcha instrument o'quvchi natijasi bilan ijobiy bog'langan, lekin **bitta kuzatuv yetarli emas**. Bizning ustunligimiz: biz *hamma* darsni yozamiz.
- **Ofsted, «Six models of lesson observation» (2018)** — hisobdorlik uchun ishlatilgan kuzatuv rivojlanish vositasi bo'lishdan to'xtaydi. Shuning uchun o'qituvchi panelida ball emas, **bitta kuchli tomon + bitta keyingi qadam** ustun turadi.
- **Praetorius va h. (2014), «One lesson is all you need?»** — `min_n` ning asosi (3-bo'lim).
- **arXiv 2310.01132** — LLM dan to'g'ridan-to'g'ri abstrakt CLASS bali so'ralganda inson koderlari bilan korrelyatsiya **r = .04**. Shuning uchun `hisob` mexanizmi (4-bo'lim).

---

## 2. Arxitektura: generic o'zak + fan moduli

Charalambous & Praetorius (2018) generic↔fanga-xos **kontinuum** taklif qiladi. Tadqiqotlar xulosasi: sof generic instrument o'qituvchiga **aniq** maslahat bera olmaydi («savol beringiz» matematika va adabiyot uchun boshqa-boshqa narsa), sof fanga-xos instrument esa maktab bo'ylab taqqoslanmaydi. Ishlagan yechim — **integratsiya**:

```
23 UNIVERSAL mezon  →  jami 100 ball  →  reyting, direktor paneli, taqqoslash
      +
3–4 FAN MEZONI      →  vazn = 0        →  faqat o'qituvchiga, reytingni buzmaydi
      ×
VAZN PROFILI        →  fan oilasi × dars turi, keyin 100 ga qayta normallash
```

Fan oilalari: `aniq` · `til` · `gumanitar` · `tabiiy` · `boshlangich` · `amaliy` · `umumiy` (aniqlanmadi).
Oila **kurs nomidan** aniqlanadi (`ai::FAN_OILALARI`, kalit so'zlar uz/ru/en). **1–4 sinf** hamma fanda `boshlangich` bo'ladi — bu yoshda yondashuv fandan ko'ra yoshga bog'liq (CLASS K-3 shu mantiqda qurilgan).

---

## 3. `min_n` — qancha darsdan keyin ishonish mumkin

Praetorius va h. (2014), 38 o'qituvchi × 5 dars, generalizability tahlili:

| Dimensiya | .70 ishonchlilik uchun |
|---|---|
| Sinf boshqaruvi, shaxsiy qo'llab-quvvatlash | **1 dars** |
| Kognitiv faollashtirish | **~9 dars** |

Shuning uchun har mezonda `min_n` bor va u **uch joyda** ishlatiladi:

| Joy | Qoida |
|---|---|
| `dashboard::recordings()` — bitta dars kartasi | `min_n ≤ 2` bo'lgan mezon kuchli/kuchsiz tomon deb atalishi mumkin |
| `tahlil::render()` — dars tahlili | `min_n ≥ 4` mezon yonida «faqat chorak profilida ishonchli» izohi |
| `teacher::kuchli_kuchsiz()`, `reyting::*` | **faqat** `n ≥ min_n` mezonlar; yetarli bo'lmasa hech narsa aytilmaydi |

Bu eng muhim qoida: aks holda 2 darsdan keyin «Tushunchani chuqurlashtirish — kuchsiz tomoningiz» deb aytardik, holbuki shu mezon uchun barqaror baho ~9 dars talab qiladi. Tasodifni xulosaga aylantirish o'qituvchini **noto'g'ri** yo'naltiradi.

---

## 4. `hisob` — ball qaydan kelgani

`hisob => true` bo'lgan 4 mezonning bali **modeldan olinmaydi**, `metrics` raqamlaridan `ai::indikator_ball()` da hisoblanadi:

| Mezon | Formula | Asos |
|---|---|---|
| `time_on_task` | `on_task_pct` | to'g'ridan-to'g'ri |
| `participation` | o'quvchi nutqi (30% = 100 ball) va turli o'quvchi soni (12 = 100 ball) o'rtachasi | TeachFX: o'qituvchi odatda **70–80%** gapiradi, ya'ni 20–30% amaliy maksimum |
| `questioning` | ochiq savol ulushi (40% = 100), kutish vaqti (3 s = 100), qamrov (12 o'quvchi = 100) o'rtachasi | GTT 4.3 |
| `negative_climate` | `100 − epizod × 20` (teskari) | CLASS |

Natija: ball **takrorlanuvchi** va tushuntiriladigan. «Nega menga shu ball qo'yildi?» degan savolga javob modelning fikri emas, transkriptdan sanalgan raqam bo'ladi. Ekranda bu alohida aytiladi.

---

## 5. Universal o'zak — 23 mezon, 100 ball

Ustunlar: kod · nom · vazn · xalqaro norma · `min_n` · belgilar.

### A. Dars tashkili — 18 ball (GTT 3-o'lcham)

| Kod | Nom | Vazn | Norma | min_n | Belgi |
|---|---|---|---|---|---|
| `time_on_task` | Vaqtdan samarali foydalanish | 7 | 70 | 1 | hisob |
| `management` | Xulq-atvorni boshqarish | 6 | 67 | 1 | |
| `structuring` | Dars strukturasi | 5 | 60 | 2 | |

### B. Mazmun va tushuntirish — 24 ball (GTT 4.2)

| Kod | Nom | Vazn | Norma | min_n | Belgi |
|---|---|---|---|---|---|
| `clarity` | Tushuntirish aniqligi | 10 | 65 | 2 | |
| `topic_match` | Mavzuga muvofiqlik | 7 | 80 | 1 | |
| `language_modeling` | Nutq namunasi va til boyligi | 4 | 41 | 2 | |
| `relevance` | Hayot bilan bog'liqlik | 3 | 45 | 3 | |

### C. Fikrlashni faollashtirish — 26 ball (GTT 4-o'lcham)

| Kod | Nom | Vazn | Norma | min_n | Belgi |
|---|---|---|---|---|---|
| `questioning` | Savol berish sifati | 8 | 35 | 4 | hisob |
| `activating` | O'quvchilar faolligi | 5 | 50 | 2 | |
| `participation` | Qatnashuv taqsimoti | 4 | 55 | 1 | hisob |
| `embedding` | Mashq va takrorlash | 4 | 55 | 3 | |
| `concept_development` | Tushunchani chuqurlashtirish | 2 | 30 | 9 | |
| `strategies` | O'quv strategiyalarini o'rgatish | 2 | 40 | 9 | |
| `differentiation` | Tabaqalashtirilgan yondashuv | 1 | 35 | 9 | |

### D. Muhit va munosabat — 20 ball (GTT 2-o'lcham + CLASS)

| Kod | Nom | Vazn | Norma | min_n | Belgi |
|---|---|---|---|---|---|
| `negative_climate` | Salbiy holatlarning yo'qligi | 5 | 85 | 1 | hisob · teskari |
| `positive_climate` | Ijobiy muhit | 4 | 70 | 1 | |
| `student_attitude` | **O'quvchilarning o'qituvchiga munosabati** | 4 | 65 | 1 | |
| `teacher_sensitivity` | O'qituvchining sezgirligi | 3 | 65 | 2 | |
| `high_expectations` | Yuqori talab muhiti | 2 | 50 | 2 | |
| `error_climate` | Xatoga munosabat | 2 | 45 | 3 | |

### E. Natija — 12 ball

| Kod | Nom | Vazn | Norma | min_n | Belgi |
|---|---|---|---|---|---|
| `feedback_quality` | Fikr-mulohaza sifati | 5 | 32 | 4 | |
| `student_understanding` | O'quvchilar o'zlashtirish darajasi | 4 | 60 | 2 | |
| `learning_evidence` | **O'quvchi nima o'rgandi** | 3 | 50 | 2 | |

> `student_perspective` — iste'moldan chiqqan (`eski => true`). Promptga tushmaydi va 100 ballga kirmaydi, lekin eski tahlillar nomsiz qolmasligi uchun jadvalda qoladi.

**Vazn mantig'i:** har vazn ikki narsani birga hisobga oladi — (a) tadqiqotdagi effekt o'lchami, (b) **audio orqali qanchalik ishonchli o'lchanadi**. Shuning uchun `concept_development` (effekti yuqori, lekin ovozda noaniq va 9 dars kerak) vazni kichik, `clarity` (ovozda juda yaxshi eshitiladi) vazni eng katta.

---

## 6. Fan modullari — 21 mezon (`vazn = 0`)

| Kod | Nom | Norma | min_n | Oila | Belgi |
|---|---|---|---|---|---|
| `math_precision` | Matematik til va yozuvning aniqligi | 70 | 2 | aniq | teskari |
| `procedure_meaning` | Algoritm ortidagi ma'no | 30 | 4 | aniq | |
| `reasoning` | Asoslash va isbot talab qilish | 25 | 4 | aniq | |
| `representations` | Bir g'oyani bir necha ko'rinishda | 35 | 4 | aniq | GTT effekti **.385** |
| `target_language_use` | O'rganilayotgan tilda gapirish ulushi | 60 | 1 | til | |
| `student_talk_length` | O'quvchi nutqining uzunligi | 30 | 2 | til | |
| `text_based` | Matnga tayangan muhokama | 40 | 3 | til | |
| `strategy_modeling` | O'qish/yozish strategiyasini modellashtirish | 30 | 4 | til | |
| `evidence_argument` | Manba va dalil bilan asoslash | 35 | 3 | gumanitar | |
| `multiple_perspectives` | Bir necha nuqtai nazar | 30 | 4 | gumanitar | |
| `discussion_depth` | Muhokama chuqurligi | 30 | 3 | gumanitar | |
| `value_discussion` | Qadriyat va axloqiy muhokama | 40 | 4 | gumanitar | |
| `inquiry` | Kuzatish, gipoteza, tajriba | 25 | 4 | tabiiy | |
| `misconception_check` | Xato tasavvurni ochish | 30 | 4 | tabiiy | |
| `everyday_science` | Kundalik hodisa bilan bog'lash | 50 | 3 | tabiiy | |
| `routines` | Rutinalar va aniq ko'rsatmalar | 70 | 1 | boshlangich | |
| `vocabulary_building` | Lug'at boyitish | 45 | 2 | boshlangich | |
| `play_engagement` | O'yin va harakat orqali jalb qilish | 55 | 2 | boshlangich | |
| `demonstration` | Namoyish va texnika tuzatish | 60 | 2 | amaliy | |
| `practice_time` | Amaliy mashq vaqti ulushi | 60 | 1 | amaliy | |
| `safety` | Xavfsizlik qoidalari | 70 | 1 | amaliy | |

---

## 7. Vazn profili — qaysi fanda nimaga ko'proq e'tibor

`ai::PROFIL` koeffitsientlari. Ro'yxatda yo'q kod uchun 1.0.

| Mezon | aniq | til | gumanitar | tabiiy | boshlangich | amaliy |
|---|---|---|---|---|---|---|
| `clarity` | **1.4** | 0.9 | 1.0 | 1.2 | 1.1 | 0.8 |
| `questioning` | 1.2 | 1.1 | **1.5** | 1.3 | 1.0 | 0.7 |
| `participation` | 0.9 | **1.6** | **1.4** | 1.0 | 1.1 | 1.0 |
| `embedding` | **1.5** | 1.2 | 0.7 | 0.9 | 1.3 | **1.4** |
| `language_modeling` | 0.8 | **1.8** | 1.3 | 0.9 | **1.5** | 0.6 |
| `relevance` | 1.2 | 1.0 | 1.2 | **1.5** | 1.1 | 1.0 |
| `error_climate` | **1.6** | 1.3 | 0.9 | 1.0 | **1.4** | 1.1 |
| `high_expectations` | **1.4** | 1.1 | 1.0 | 1.1 | 0.9 | 1.0 |
| `time_on_task` | 1.0 | 1.0 | 0.9 | 1.0 | **1.3** | **1.3** |
| `climate` guruhi | 0.9 | 1.1 | 1.1 | 1.0 | **1.5** | 1.2 |
| `concept_development`, `strategies` | 1.0 | 1.0 | 1.0 | 1.0 | 0.7 | 0.6–0.8 |

Mantiq:
- **Aniq fanlar** — tushuntirish, mashq va **xatoga munosabat** hal qiluvchi: matematika qo'rquvi aynan xato jazolanganda tug'iladi.
- **Til** — nutq ulushi va nutq namunasi hamma narsadan muhim: o'qituvchi 80% gapirsa, o'quvchi tilni o'rganmaydi.
- **Gumanitar** — yagona «to'g'ri javob» yo'q, dars savol va muhokama bilan yashaydi.
- **Tabiiy** — kundalik hodisa bilan bog'lash eng kuchli vosita.
- **Boshlang'ich** — emotsional muhit, rutina va lug'at mazmun chuqurligidan ustun.
- **Amaliy** — gapirish emas, **bajarish** vaqti o'lchanadi; jimlik bu yerda kamchilik emas.

**Cheklov:** ikki koeffitsient ko'paytiriladi, lekin ko'paytma `[0.5 … 1.6]` oralig'iga qisiladi. Aks holda 1.4 × 1.3 = 1.82 bo'lib, bitta mezon 18% ga chiqib ketardi — 23 mezonning ma'nosi yo'qoladi.

---

## 8. Dars turiga individual yondashuv

Dars turini **AI o'zi** aniqlaydi (`lesson_type`) — jadvalda bunday maydon yo'q va o'qituvchidan qo'shimcha ish talab qilmaymiz.

| Tur | Baholanmaydi | Kuchaytiriladi |
|---|---|---|
| `yangi` — Yangi mavzu | — | `clarity` 1.3 · `structuring` 1.2 · `concept_development` 1.3 · `embedding` 0.7 |
| `mustahkamlash` — Mustahkamlash / mashq | `concept_development` | `embedding` 1.6 · `feedback_quality` 1.3 · `differentiation` 1.3 |
| `takrorlash` — Takrorlash | `concept_development` | `embedding` 1.5 · `questioning` 1.3 |
| `nazorat` — Nazorat / test | **12 mezon**: `clarity`, `questioning`, `activating`, `participation`, `concept_development`, `strategies`, `differentiation`, `embedding`, `relevance`, `language_modeling`, `feedback_quality`, `learning_evidence` | `management` 1.5 · `time_on_task` 1.5 · `structuring` 1.3 · `negative_climate` 1.3 |
| `amaliy` — Amaliy / laboratoriya | — | `participation` 1.3 · `embedding` 1.4 |
| `muhokama` — Muhokama / seminar | — | `questioning` 1.5 · `participation` 1.5 · `activating` 1.3 |

**Nega kerak:** nazorat ishida o'qituvchi tushuntirmaydi va savol bermaydi — shu darsni `clarity` yoki `questioning` bo'yicha baholash **haqsizlik**. Baholanmaydigan mezon `confidence = low` ga o'tadi, 100 ballga kirmaydi, va ekranda sababi yoziladi («Nazorat / test turidagi darsda bu mezon baholanmaydi»).

Shu sababli **norma ham dars turiga qarab o'zgaradi** (`ai::norm_overall($oila, $tur)`): yangi mavzu darsida ~57%, nazorat ishida ~68%.

---

## 9. AI promptining tuzilishi

Prompt `CATEGORIES` dan **quriladi** (`ai::prompt_analyse`) — mezon qo'shilsa yoki uning `talab` matni o'zgarsa, prompt o'zi yangilanadi. Avval ro'yxat promptda qo'lda yozilgan edi va jadval bilan ajralib ketish xavfi bor edi.

Promptga uzatiladigan kontekst:
```
Dars: <kurs>. Sinf: 7-A (7-sinf). Fan: Matematika [oila: aniq].
Sana: 2026-11-10, 3-dars. Jurnaldagi mavzu: "..."
```

Uch vazifa ketma-ket:
1. **Dars turini aniqla** (`lesson_type`).
2. **Fanga mos talqin qil** — har oila uchun alohida izoh (`$fan_izoh`): amaliy fanda jimlik kamchilik emas, til darsida o'quvchi nutqi shart, boshlang'ichda takroriy ko'rsatma normal.
3. **Mezonlarni bahola** — 23 universal + shu oilaning fan moduli.

Uch himoya qoidasi promptda:
- **Halollik** — faqat audio bor; imo-ishora, doska, yozma ish ko'rinmaydi. Dalil bo'lmasa `confidence = low`, va `low` umumiy ballga kirmaydi, ya'ni halol «bilmadim» o'qituvchiga **zarar qilmaydi**.
- **Taqiq** — o'qituvchining hissiy holati, charchoqligi, shaxsiyati haqida xulosa chiqarilmaydi; faqat eshitilgan xatti-harakat.
- **Dalil** — `evidence` raqamli va `[MM:SS]` bilan; `advice` — bitta bajarilishi mumkin qadam, umumiy nasihat emas.

---

## 10. Kod xaritasi

| Fayl | Nima o'zgardi |
|---|---|
| `classes/ai.php` | `CATEGORIES` (23+21+1), `FAN_OILALARI`, `PROFIL`, `DARS_TURLARI`, `fan_oila()`, `kodlar()`, `vaznlar()`, `min_n()`, `indikator_ball()`, `tur_nomi()`, `norm_overall($oila,$tur)`, qayta yozilgan `prompt_analyse()`, vaznli `overall()`, dars turi gatingi bilan `save()` |
| `classes/aicat.php` | O'zgarmadi — `weight` endi **effektiv** vazn bo'lib keladi |
| `classes/teacher.php` | `profile()`: fan modullari faqat ma'lumot bo'lsa; `min_n`/`ishonchli`. `kuchli_kuchsiz()`: faqat ishonchli mezonlar |
| `classes/reyting.php` | `min_n` bajarilmagan mezon kuchli/kuchsiz ro'yxatiga kirmaydi |
| `classes/dashboard.php` | Bitta dars kartasida `min_n ≤ 2` chegarasi |
| `classes/output/tahlil.php` | Dars turi va fan oilasi belgisi, «O'quvchi nima o'rgandi» bloki, `min_n` izohi, «ball o'lchovlardan hisoblangan» izohi, yangi `metrics` (o'quvchi savollari, salbiy epizodlar) |
| `lang/tools/strings.py` | 30 yangi kalit × 3 til (`gen.py` bilan generatsiya) |

**Migratsiya kerak emas:** `local_targetzenit_aicat` jadvali o'zgarmadi, eski kodlar saqlanib qoldi. Eski tahlillarda `lesson_type` va `subject_family` yo'q — ko'rsatish qatlami `umumiy` / bo'sh qiymat bilan ishlaydi. Xohlasa `php cli/direktor.php` ichidagi backfill bilan indeks qayta quriladi.

---

## 11. Keyingi bosqich (hali qilinmagan)

1. **O'qituvchi paneli** (`oqituvchi.php`) — Ofsted xulosasiga ko'ra: ball emas, **bitta kuchli tomon (dalili bilan) + bitta keyingi qadam** ustun turishi kerak; taqqoslash boshqa o'qituvchi bilan emas, **o'zining 3 oy oldingi holati** bilan.
2. **Fan mahorati bloki** — fan modullari hozir umumiy ro'yxatda, alohida kartaga chiqarilishi kerak.
3. **Prodda sinash** — birinchi 20 ta tahlildan keyin `metrics` raqamlarining realligini tekshirish (ayniqsa `wait_time_avg_sec`: transkript vaqt belgilari 5 daqiqalik bo'lak boshida qo'yilgani uchun kutish vaqti taxminiy chiqishi mumkin).
4. **Dars turi aniqligini tekshirish** — AI `lesson_type` ni to'g'ri topayotganini qo'lda 20 darsda solishtirish.
