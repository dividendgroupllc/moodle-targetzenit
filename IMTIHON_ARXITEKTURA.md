# Imtihon moduli (o'qituvchi portali) — arxitektura

**Holat:** 4 ekran + nizom-ulanishi QURILDI (lokalda) · prod qolgan · 2026-11-21
**Qaror qabul qilgan:** foydalanuvchi, 2026-11-20..21

> ## 0-QURILGAN HOLAT (2026-11-21)
>
> Modul lokalda to'liq ishlaydi; prodga chiqarilmagan. Fayllar:
>
> | Ekran | Fayl | Vazifa |
> |---|---|---|
> | A · Ro'yxat | `imtihonlar.php` | quiz+assign bitta jadvalda (holat, sanoq, nizom tegi) |
> | B · Yaratish | `imtihon_yangi.php` + `imtihon::yarat()` | sehrgar → `create_module` bilan haqiqiy quiz/assign |
> | C · Savollar | `imtihon_savol.php` | `structure` API ro'yxat; qo'shish `addquestion.php` (17 tur) ga delegatsiya |
> | E · Baholash | `imtihon_baho.php` | o'quvchi ro'yxati; grader (rubrika/editpdf) ga; test uchun hisobotlar |
> | Backend | `classes/imtihon.php` | royxat/holat/amal/stat + yarat + nizom_yarat + import_ballar |
> | Jadval | `local_targetzenit_imtihon` | cmid ↔ _exam ↔ sitting bog'lanishi |
>
> **Hal qilingan qarorlar:**
> - Kirish: alohida «Imtihonlar» nav tab (maket). «Topshiriqlar» sub-tab
>   Imtihonlarga **birlashtirildi** (portal o'qituvchisi topshiriqlar.php dan
>   redirect bo'ladi; direktor Edulytics'da qoladi).
> - Xavfsizlik: parol + vaqt + IP ishlaydi; SEB = `requiresafeexambrowser=4`
>   (admin ruxsati kerak, yo'q bo'lsa jim o'tadi); offline = `allowofflineattempts`.
> - **Nizom:** o'qituvchi O'Z kursida `_exam` + sitting yaratadi (`nizom_yarat`,
>   koordinator shartini teach-ruxsat bilan xavfsiz chetlab o'tadi); ball oqimi
>   = **IMPORT tugmasi** (`import_ballar` → `result_save` → `term_score_save`),
>   avtomatik observer EMAS. `quizcmid` avto-sinxroni hali ham yo'q — bu ataylab.
> - `create_module` tuzog'i: `introeditor` (editor massivi) MAJBURIY.
>
> **Prodga chiqishdan oldin tekshirish:** eski assign'lar idnumber (yil::guruh::fan)
> bilan bog'langanmi; SEB admin ruxsati; `enablemobilewebservice` (offline uchun).

---


O'qituvchi **imtihon/topshiriq**ni portalning o'z oynasidan yaratadi, savol
qo'shadi, o'tkazadi va baholaydi — Moodle kurs sahifasiga **umuman
chiqmasdan**. Orqada esa Moodle yadrosi (`mod_quiz`, `mod_assign`, savol banki,
baholash dvigateli) ishlaydi.

Bu hujjat `BAHOLASH_ARXITEKTURA.md` (koordinator tomoni: FB/BSB/CHSB, nizom,
chorak bahosi) ni **to'ldiradi** — u *qachon/qancha* baho degan savolga javob
beradi, bu hujjat esa *imtihon qanday olinadi va baholanadi* degan savolga.

---

## 0. Tamoyillar (foydalanuvchi, 2026-11-20)

1. **Yadro ustida — qayta ixtiro YO'Q.** Imtihon mexanikasini (urinish,
   avto-baho, xavfsizlik, mobil ilova, savol banki) yozmaymiz — Moodle'ni
   **o'raymiz**. Biz faqat UI qatlami va portal mantig'ini qo'shamiz.
2. **BARCHA imkoniyat saqlanadi.** Soddalashtirish ≠ yashirish. Qulay ekran
   eng ko'p ishlatiladigan sozlamalarni oldinga chiqaradi, qolganlari
   **«Qo'shimcha sozlamalar»** yoyilmasida to'liq turadi — o'qituvchi hech
   qanday Moodle imkoniyatidan mahrum bo'lmaydi.
3. **Professional, yagona UI.** Eski ekranlar (`topshiriqlar.php`,
   `baholash.php` ning o'qituvchi ko'rinishi) tashlanadi — hammasi portal
   maketida (yashil top-bar, tab/sub-tab) yangidan quriladi.
4. **Yagona ID bilan bog'lanish.** Kurs = `yil::guruh::fan` (`idnumber`),
   imtihon = Moodle `course_modules.id` (cmid). Ism/email bilan hech qachon.

---

## 1. Ikki oila — nima uchun qaysi modul

Imtihonning ikki tabiati bor; Moodle'da ikki modul:

| Oila | Modul | Nima uchun | Baholash |
|---|---|---|---|
| **TEST** | `mod_quiz` | Savol-javob, variant, raqam, moslashtirish, formula | **Avto** (insho qo'lda) |
| **YOZMA / ISH** | `mod_assign` | Fayl yuklash, onlayn matn, insho, loyiha, uy ishi | **Qo'lda** (rubrika bilan) |

Maktab holatiga moslik (nizom turlari `BAHOLASH_ARXITEKTURA.md` da):

| Maktab imtihoni | Portal oilasi | Moodle moduli |
|---|---|---|
| CHSB / BSB — kompyuterda test | TEST | `mod_quiz` |
| BSB — yozma ish / nazorat | YOZMA | `mod_assign` (fayl + rubrika) |
| LI — loyiha ishi | YOZMA | `mod_assign` (fayl/matn, muddatli) |
| FB — darsdagi joriy | *imtihon emas* | dars kokpiti (mavjud) |
| Og'zaki / qog'oz imtihon | TEST (offline) yoki YOZMA (offline worksheet) | ball qo'lda |

> **Qaror:** portal «Imtihon yaratish» sehrgari birinchi qadamda aynan shu
> ikki oilani (Test / Yozma ish) so'raydi — qolgan hamma narsa shundan kelib
> chiqadi.

---

## 2. Imkoniyatlar ↔ portal elementi (TO'LIQ SAQLASH kontrakti)

Bu bo'lim — 0.2-tamoyilning amaliy ro'yxati. Har bir Moodle imkoniyati
portalda **qayerda** turishini belgilaydi. «Asosiy» = sehrgarning ko'rinadigan
qadami; «Qo'shimcha» = yoyiladigan panel.

### 2.1 TEST (`mod_quiz`)

| Moodle imkoniyati | Portaldagi joy | Daraja |
|---|---|---|
| Savol turlari (17 ta): multichoice, truefalse, shortanswer, numerical, matching, **calculated**, **Cloze/multianswer**, gapselect, **ddwtos/ddmarker/ddimageortext**, ordering, randomsamatch, **essay** | Savol muharriri (4-qadam) | Asosiy |
| Savol banki (kategoriya, qayta ishlatish) | «Bankdan tanlash» ichki oyna | Asosiy |
| Tasodifiy savol (random) | «Bankdan N ta tasodifiy» | Asosiy |
| Import: GIFT / Aiken / Moodle XML | «Fayldan yuklash» | Qo'shimcha |
| Vaqt chegarasi, ochilish/yopilish | Asosiy (sana+davomiylik) | Asosiy |
| Urinishlar soni | Asosiy | Asosiy |
| Baholash usuli (yuqori/o'rtacha/birinchi/oxirgi) | Qo'shimcha | Qo'shimcha |
| Savol xatti-harakati (deferred/immediate/adaptive/**CBM**/interactive) | Qo'shimcha → «Baholash rejimi» | Qo'shimcha |
| Ko'rib chiqish matritsasi (qachon: jarayonda/darhol/yopilgach × nima: ball, javob, izoh, to'g'ri javob) | Qo'shimcha → «O'quvchi nimani ko'radi» (soddalashtirilgan 3 tayyor profil + to'liq matritsa) | Qo'shimcha |
| Har sahifada savol soni, navigatsiya | Qo'shimcha | Qo'shimcha |
| Aralash tartib (shuffle) | Asosiy (bitta belgi) | Asosiy |
| **Xavfsizlik:** parol, **Safe Exam Browser (SEB)**, IP cheklov, xavfsiz oyna, urinishlar orasida kechikish | Qo'shimcha → «Imtihon rejimi (xavfsizlik)» | Qo'shimcha |
| **Offline ilova** (internetsiz mobil test) | Qo'shimcha belgi | Qo'shimcha |
| Umumiy fikr (ball oralig'iga xabar) | Qo'shimcha | Qo'shimcha |
| Hisobot: overview, responses, **statistics (item analysis)** | Natijalar ekrani | Asosiy |

### 2.2 YOZMA ISH (`mod_assign`)

| Moodle imkoniyati | Portaldagi joy | Daraja |
|---|---|---|
| Topshirish: **fayl yuklash** (tur/hajm/son), onlayn matn | Asosiy | Asosiy |
| Muddatlar: boshlanish, topshirish, qattiq muddat, baholash muddati | Asosiy | Asosiy |
| Qoralama rejimi, qayta topshirish (urinish soni) | Qo'shimcha | Qo'shimcha |
| Guruhli topshiriq (team) | Qo'shimcha | Qo'shimcha |
| **Anonim baholash** (blind marking) | Qo'shimcha | Qo'shimcha |
| Baholash bosqichlari (workflow) + baholovchi biriktirish | Qo'shimcha | Qo'shimcha |
| Baholash: ball / shkala | Asosiy | Asosiy |
| **Ilg'or baholash: rubrika, marking guide** | «Baholash usuli» → rubrika quruvchi | Asosiy |
| Fikr: izoh, **editpdf (PDF ustiga belgi)**, fikr-fayli, **offline worksheet (Excel)** | Baholash ekranida | Asosiy |

> **Dizayn qoidasi:** har «Qo'shimcha» panel yopiq holda ham o'zining joriy
> qiymatini bitta qatorda ko'rsatadi (masalan «Xavfsizlik: yoqilmagan»,
> «Baholash: rubrika») — o'qituvchi yoymasdan ham nima o'rnatilganini biladi.

---

## 3. Ekran arxitekturasi (portal → Moodle)

Portalda **bitta «Imtihonlar» bo'limi** (nav tab yoki «Mening darslarim»
ichidagi tugma — maket bosqichida qaror qilinadi). Ichida 5 ekran:

| # | Ekran | Vazifa | Orqadagi Moodle |
|---|---|---|---|
| A | **Imtihonlar ro'yxati** | Barcha test+yozma ish bitta jadvalda: guruh, tur, sana, holat, topshirgan/baholangan | `mod_quiz` + `mod_assign` instansiyalari (hozirgi `topshiriqlar.php` mantig'i kengaytiriladi) |
| B | **Yaratish sehrgari** | Yangi imtihon (5-bo'lim) | `/course/modedit.php` o'rniga — bizning forma → `quiz`/`assign` yaratadi |
| C | **Savol muharriri** | Savol qo'shish/bankdan tanlash/import | savol banki API (`question/`) |
| D | **O'tkazish / kuzatish** | Jonli urinishlar, kim boshladi/topshirdi, override (qo'shimcha vaqt) | `quiz` attempts, user overrides |
| E | **Baholash va natijalar** | Avto ballar ko'rinadi; insho/yozma qo'lda (rubrika); statistika | `quiz report/grading`, `assign grading`, `quiz report/statistics` |

---

## 4. Yaratish sehrgari (B) — bosqichma-bosqich

Professional, bosqichli (wizard). Har qadam — Moodle forma elementiga mos.

**1-qadam · Tur.** Test / Yozma ish. (Keyin o'zgarmaydi — modul tanlovi.)

**2-qadam · Asosiy.**
- Guruh(lar) — bir yoki bir nechta o'z kursidan (ko'p guruhga bitta imtihon =
  har kursda instansiya; `idnumber` orqali).
- Nom (avto-taklif: «BSB-2 · 7A · Matematika» — `baholash.php` taklif_nom
  mantig'i).
- Sana/vaqt (boshlanish + davomiylik yoki muddat).
- **Nizom bog'lanishi (ixtiyoriy):** bu imtihon BSB/CHSB/LI ga tegishlimi →
  `baholash` moduli bilan ulanadi (chorak balliga 50%/40% kiradi).

**3-qadam · Sozlamalar.** «Asosiy» ko'rinadi, «Qo'shimcha sozlamalar»
yoyiladi (2-bo'lim kontrakti). Test uchun: baholash rejimi, ko'rib chiqish
profili, xavfsizlik. Yozma uchun: topshirish turi, baholash usuli
(ball/rubrika).

**4-qadam · Mazmun.**
- Test → **savol muharriri** (C ekran): yangi savol, bankdan tanlash, import,
  tasodifiy. Har savol turi — Moodle `question/type` formasi (bizning qobiqda).
- Yozma → topshirish ko'rsatmasi + biriktiriladigan fayl (namuna/shart).

**5-qadam · E'lon va tekshirish.** Oldindan ko'rish, o'quvchiga/ota-onaga
bildirishnoma (ixtiyoriy), «Faollashtirish». Faollashgach — o'quvchida oddiy
Moodle quiz/assign ochiladi (o'quvchi tomoni standart Moodle).

---

## 5. Savol banki (C)

- Yadro savol banki (`qbank`) ishlatiladi — har kurs/kategoriya ichida.
- O'qituvchi savollari **qayta ishlatiladi** (keyingi chorak, boshqa guruh).
- Import: **GIFT** (oddiy matn), **Aiken** (variantli), **Moodle XML**.
- Tasodifiy: «bankdan N ta tasodifiy» — har o'quvchiga boshqa variant.
- **Calculated** savol: formula + diapazon → har o'quvchiga boshqa son
  (ko'chirishga qarshi).

---

## 6. Baholash oqimi (E)

| Savol/ish turi | Baholash | Qayerda |
|---|---|---|
| Test — obyektiv savollar | **Avtomatik**, darhol | gradebook'ga tushadi |
| Test — insho (essay) | **Qo'lda** | `quiz report/grading` qobig'ida |
| Yozma ish (assign) | **Qo'lda** (ball/rubrika/guide) | `assign grading` qobig'ida, editpdf bilan |

- Natija **gradebook**'da yashaydi (yadro) — biz ko'chirmaymiz.
- Agar imtihon BSB/CHSB/LI ga ulangan bo'lsa → `baholash` moduli ballni
  `_term_score` ga chorak hisobiga oladi (`BAHOLASH_ARXITEKTURA.md` 5.1).
- **Statistika:** `quiz report/statistics` — har savolning qiyinligi va
  diskriminatsiyasi; o'qituvchiga «qaysi savol yomon ishladi» ko'rinadi.

---

## 7. Xavfsizlik / imtihon rejimi

Uchta tayyor profil + to'liq nazorat (hammasi yadro `accessrule`):

| Profil | Nima yoqiladi |
|---|---|
| **Oddiy** | cheklovsiz (uy testi) |
| **Sinf imtihoni** | parol + vaqt chegarasi + bitta urinish + aralash tartib |
| **Qattiq (SEB)** | yuqoridagilar + Safe Exam Browser (brauzer qulflanadi) + IP/sinf tarmog'i |

Offline (internetsiz) — mobil ilovada test (`offlineattempts`), ball keyin
sinxronlanadi.

### 7.1 Real-hayot cheklovlari (koddan tasdiqlangan, 2026-11-20)

Hammasi yadroda **tayyor** (`accessrule/seb`, `offlineattempts`, `password`,
`ipaddress`, `timelimit`, `numattempts` — hammasi o'rnatilgan). Lekin ikkitasi
tashqi shartga bog'liq:

| Himoya | Holat | Shart |
|---|---|---|
| Parol, vaqt, urinish, ochilish-yopilish | Shartsiz ishlaydi | — |
| IP cheklov | Tarmoq darajasida | Imtihon maktab Wi-Fi/LAN'ida |
| **SEB** (config-key bilan haqiqiy qulf) | Kuchli | SEB ilovasi o'quvchi qurilmasida; faqat **Windows/macOS/iOS**; Android/Chromebook ❌; admin yoqadi |
| **Offline** | Ishlaydi | Faqat **Moodle Mobile ilovasi**; SEB bilan birga EMAS |
| ~~securewindow~~ | Zaif, eski | Ishlatilmaydi — SEB almashtirgan |

**Maktab qarori (foydalanuvchi, 2026-11-20):** imtihon asosan **maktab Windows
kompyuter sinfida** → SEB. Lekin ba'zan (psixologik testlar, kompyuter
yetmaganda) **telefon**dan ham ruxsat berish kerak bo'ladi.

**Yechim — profil har imtihonda alohida + override:**
- Kompyuter imtihoni → «Qattiq (SEB)».
- Telefon ruxsat etilsa → «Sinf imtihoni» (parol+vaqt+aralash) — hamma
  qurilmada ishlaydi (SEBsiz).
- SEB imtihonida kompyuter yetmay qolsa → yadro **quiz override** bilan ayrim
  o'quvchi/guruhga qoida yumshatiladi (`mod/quiz:manageoverrides`), qolganlar
  SEB'da qoladi.

UI qoidasi: «Qattiq (SEB)» tanlanganda o'qituvchiga **qurilma
ogohlantirishi** ko'rsatiladi (Android telefon kira olmaydi).

---

## 8. Ma'lumot modeli (ingichka qatlam)

Yadro hamma narsani saqlaydi (quiz, assign, attempts, grades, questions).
Plagin **ingichka ko'rsatkich** jadvali qo'shadi (ixtiyoriy, kerak bo'lsa):

```
local_targetzenit_imtihon
  id
  cmid            -> course_modules.id (quiz yoki assign)
  moduli          'quiz' | 'assign'
  oila            'test' | 'yozma'
  examid          -> local_targetzenit_exam.id  (BSB/CHSB bog'lanishi; NULL = erkin)
  egauserid       yaratgan o'qituvchi
  timecreated
```

> Nega kerak: portal ro'yxati (A) va nizom bog'lanishini tez olish uchun.
> Agar `baholash` moduli (`_exam`) allaqachon cmid saqlasa — **takrorlanmaydi**,
> shu jadval ishlatiladi. Qaror kod bosqichida.

Yagona ID: kurs `idnumber = yil::guruh::fan`, imtihon = `cmid`. O'quvchi =
`user.id` ↔ Moodle. Ism/email bilan bog'lanmaydi (`unic-id-qoidasi`).

---

## 9. Ruxsatlar (yadro)

O'qituvchi o'z kursida `editingteacher` — demak yadro ruxsatlari **bor**:
`mod/quiz:addinstance`, `mod/quiz:manage`, `mod/quiz:grade`,
`mod/quiz:manageoverrides`, `mod/assign:addinstance`, `mod/assign:grade`,
`moodle/question:add` va h.k. Yangi capability **shart emas** — portal faqat
o'qituvchi allaqachon qila oladigan ishni qulay ko'rsatadi.

Qamrov: o'qituvchi faqat **o'z kurslarida** (`topshiriq::mening_kurslarim`
mantig'i). Koordinator (`manageexams`) hammasini ko'radi.

---

## 10. Nega yadro (qayta ixtiro qilmaslik sabablari)

- Urinish dvigateli, avto-baho, savol turlari, savol banki, SEB, mobil ilova,
  statistik tahlil — Moodle'da **yillar davomida sinalgan**, biz tenglasha
  olmaymiz.
- Yadro yangilansa — imkoniyat **bepul** keladi.
- O'quvchi/ota-ona tomoni standart Moodle bo'lib qoladi (push, ilova, kundalik).
- Biz faqat **o'qituvchi qulayligi** qatlamini qo'shamiz — qiymat shunda.

---

## 11. Keyingi qadam — maketga chiziladigan ekranlar

`~/Documents/oqituvchi-portal-ui.html` ga yangidan:

1. **Imtihonlar ro'yxati** (A) — jadval: guruh · tur · sana · holat ·
   topshirgan/baholangan · amal.
2. **Yaratish sehrgari** (B) — 5 qadam, «Qo'shimcha sozlamalar» yoyilmalari
   bilan (barcha imkoniyat ko'rinadigan, lekin bosqichli).
3. **Savol muharriri** (C) — savol turi tanlash + bank + import.
4. **Baholash ekrani** (E) — avto ballar jadvali + insho/yozma qo'lda +
   rubrika + statistika.

Har ekran yuqoridagi kontraktga (2-bo'lim) amal qiladi: **birorta Moodle
imkoniyati yo'qolmaydi**, faqat qulay joylashtiriladi.

---

## 12. Ochiq savollar (maket oldidan hal qilinadi)

1. ~~«Imtihon» portalda alohida nav tab mi yoki tugma mi?~~ — **HAL: alohida
   «Imtihonlar» nav tab** (foydalanuvchi, 2026-11-20).
2. `_imtihon` jadvali kerakmi yoki `baholash._exam` yetadimi? — kod bosqichi.
3. Ko'p guruhga bitta imtihon: har kursda instansiya (standart) yoki bitta
   umumiy — standart ma'qul (yadro shunday ishlaydi).
