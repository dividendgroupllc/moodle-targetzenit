# Ota-ona uchun o'quvchi hisoboti (chop etiladigan varaq)

**Versiya:** 0.59.0 (2026-11-13) · `local_targetzenit`
**Sahifa:** `/local/targetzenit/hisobot.php`

Ota-onalar majlisida qo'lga beriladigan 1–3 betli A4 hujjat.

---

## 1. Hisobot qaysi savolga javob beradi

> **«Farzandim qaysi fanda yaxshi, qaysi fanda sust? Maktabdagi umumiy
> holati qanday?»**

Boshqa savolni ko'tarmaydi. Shuning uchun varaqda:

| Bor | Yo'q (ATAYLAB) |
|---|---|
| fanlar kesimida **test natijasi** (foiz) | chorak bahosi — maktabda hali yo'q |
| **sinf o'rtachasi** bilan qiyos | davomat / yo'qlama — hali yuritilmaydi |
| **kuchli / sust** fanlar ro'yxati | FB · BSB · CHSB tarkibi — test bilan bog'liq emas |
| o'qituvchilarning **5 kategoriyali sharhi** | o'quvchining sinfdagi O'RNI (reyting) |

**Reyting ataylab yo'q:** majlisda bolani sinfdoshlari bilan taqqoslash
uchun asos bo'lib qolardi. Qiyos faqat **sinf o'rtachasi** bilan.

## 2. Ma'lumot manbalari

| Nima | Qayerdan |
|---|---|
| test ballari | `_exam_result` + `_exam` (`exam::for_student()`) |
| sinf o'rtachasi | `_exam_result` bo'yicha `AVG(score)` — **sitting** kesimida, 2 tadan kam natija bo'lsa qaytarilmaydi |
| o'qituvchi sharhi | `{comments}` + `_sharh` (`sharh::mine()` — faqat **ochiq**) |
| o'qituvchi ismi | `_slot` (haqiqatda dars o'tgan) → `_timetable` (reja) |
| sinf va tutor | kurs `idnumber` + `tutor::map()` |

Test natijalari Excel'dan `exam_import` orqali kiritiladi
(`1Quarter...Test_natijalari_sinflar.xlsx` formati).

## 3. Daraja shkalasi

Maktabning **o'z** shkalasi (natijalar jadvalidagi `Low/Middle/High`):

| Foiz | Daraja |
|---|---|
| 80–100 | Yuqori |
| 50–79 | O'rta |
| 0–49 | Past |

Nizomning 86/66/30 bandlari BU YERDA ishlatilmaydi — ular chorak bahosi
uchun. Ikki shkalani bir varaqqa qo'yish ota-onani chalg'itadi.
Chegaralar: `hisobot::DARAJA_YUQORI` / `DARAJA_ORTA`.

## 4. Varaq tuzilishi

| Blok | Nima uchun |
|---|---|
| sarlavha (logotip, davr) | hujjat kimniki ekani |
| **HERO** — bitta katta SVG | o'quvchi + halqali ko'rsatkich (umumiy natija) + uchta raqam: eng kuchli fan · e'tibor kerak fan · sinfga nisbatan |
| **«Qisqacha»** | raqamlar GAP shaklida — diagrammani hamma o'qimaydi, gapni hamma o'qiydi |
| **Kuchli / Qo'llab-quvvatlash kerak** | majlisdan eslab qolinadigan ikki ro'yxat |
| ustun diagramma | **faqat testi bor** fanlar, kuchlisidan sustigacha tartiblangan + sinf o'rtachasi belgisi |
| radar + tarqoqlik | muvozanat: shakl va «eng yuqori va eng past fan orasidagi farq» |
| testlar jadvali | har bir test: ball, foiz, daraja, sinf o'rtachasi |
| **o'qituvchilar fikri** | 5 kategoriya; foiz KO'RSATILMAYDI — sharh test baliga emas, dars davomidagi kuzatuvga asoslanadi |
| izoh + imzo | shkala izohi, «past natija hukm emas», imzo chiziqlari |

Natija umuman yo'q bo'lsa diagramma va shkala **chizilmaydi** — bo'sh
o'qlar varaqni to'ldiradi, lekin hech narsa aytmaydi.

## 4.1 Fan belgilari (`output\hisobot_ikona`)

Har fanning **o'z chizma belgisi va rangi** bor. Ro'yxat maktabning
HAQIQIY jadvalidan olingan (`Umumiy_dars_jadvali` dagi 24 ta nom), ya'ni
belgisiz fan qolmagan:

| Fan | Belgi | Fan | Belgi |
|---|---|---|---|
| MATH | π | MENTAL.M | abak |
| ENGLISH · CHINESE | globus | RUS · UZBEK | ochiq kitob |
| IT | monitor + kod | AI | mikrosxema |
| ROBOTICS | robot | CHESS | shaxmat piyodasi |
| HOMEWORK | daftar | EXTRA | soat |
| FINANCE | tangalar | ECO | o'suvchi grafik |
| BUS | portfel | PER DEV | odam + o'sish |
| G PRES | doska | MOR EDU | yurak |
| P.E / GYM | to'p | PHYSICS | atom |
| SCIENCE | kolba | BIO | barg |
| HIST.U / HIST.W | qum soati | GEO | xarita |
| MUSIC | nota | ART | palitra |

**Moslik IKKI BOSQICHLI** (`hisobot_ikona::fan()`):
1. **butun so'z** bo'yicha (`kod`) — jadvaldagi qisqartmalar uchun;
   qism-satr qidiruvi bu yerda xato berardi: `art` «start» ichida,
   `ai` «main» ichida, `it` «british» ichida bor;
2. **qism-satr** bo'yicha (`sozlar`) — to'liq nomlar uchun
   («Ona tili va adabiyoti», «Физкультура»).

Aniq kod har doim ustun turadi. Topilmasa — neytral kitob.

Belgi **varaq bo'ylab bir xil joylarda** takrorlanadi: diagramma qatori,
kuchli/sust ro'yxati, testlar jadvali, sharh bloki sarlavhasi va
**o'qituvchi ismi oldida** — kim qaysi fandan dars berishi ismning
o'zidan ko'rinib tursin. Besh kategoriya belgisi (qalpoq, pufak, ikki
odam, ro'yxat, lampochka) shu fanning rangida chiziladi.

**NEGA EMOJI EMAS:** emoji har tizimda boshqacha chiziladi (ba'zan
kvadrat bo'lib qoladi), bosmada rastr bo'lib loyqalanadi va hujjat
palitrasiga bo'ysunmaydi. SVG chizma belgi vektorda bosiladi, rangi
bizniki.

**RANG QOIDASI:** fan rangi FAQAT belgida. Ustun rangi — DARAJA
(yuqori/o'rta/past). Ikkisi aralashsa rang ikki narsani anglatib
qolardi: «yashil» — yaxshimi yoki matematikami?

Moslik fan nomidagi **kalit so'zlar** bo'yicha, uch tilda (`English`,
`Ona tili va adabiyoti`, `Математика`) — Frappe'dan nom erkin matn
bo'lib keladi.

## 4.2 Diagrammadan sharhga o'tish

Diagrammadagi fan qatori — **havola**: bosilsa shu fanning o'qituvchi
sharhiga tushadi (`<a href="#hr3-fan-412">`), blok bir zumga ajralib
turadi. Ota-ona past natijani ko'rib, darhol o'qituvchi nima
yozganini o'qiy oladi. Bosmada havola zararsiz.

### 4.3 O'quvchining ismi hech qachon qisqartirilmaydi

Hero'dagi ism `…` bilan kesilmaydi — **shrift kichrayadi**
(`hisobot_varaq::olcham()`), ism esa butun qoladi. Sabab maket emas:
hujjat ota-onaga beriladi va farzandining ismi to'liq turishi hurmat
belgisi (talab 2026-11-13). Xuddi shu qoida sinf rahbarining ismiga ham
tegishli. SVG matni o'ralmagani uchun kenglik bahosi ehtiyotkor
(0.62 em): 37 harfli qo'sh familiya ham halqaga tegmaydi.

## 5. Uchta qat'iy qoida

### 5.0.1 Varaq YORUG' — bu did emas, bosma talabi
Hero bloki dastlab to'q ko'k fon + oq matn edi. Ikki bosqichda
ochartirildi va oxirida umuman yorug' qilindi (2026-11-13):

- to'q maydon lazer/siyoh printerda ko'p bo'yoq yeydi va yo'l-yo'l
  (banding) bo'lib chiqadi;
- **oq matn to'q fonda** bosilganda harf chetida bo'yoq yoyilib, mayda
  yozuv «semiradi»; to'q matn oq fonda esa doim tiniqroq.

Shuning uchun: fon oqqa yaqin, matn to'q ko'k (`HERO_INK`), rang esa
faqat ma'no tashiydigan joyda (daraja, fan belgisi) va to'yingan holda.
Brend urg'usi — chap chetdagi qizil tasma va logotip. Soyalar ham
ataylab juda yengil (`flood-opacity 0.16`): kuchli soya ustun atrofida
kulrang halqa bo'lib, chekkani loyqalatadi.

### 5.0 Hajm (3D) ham SVG ichida
Gradient, soya va yorug'lik chizig'i — `defs()` dagi
`linearGradient` / `feDropShadow` orqali. CSS `box-shadow` va
`background: linear-gradient` qog'ozga **tushmaydi**, SVG esa rasm.
Gradient `id` lari har hujjatda prefiksli (`hr3-bar-yuqori`): guruh
rejimida bitta sahifada 30 hujjat bo'ladi va bir xil `id` lar
bir-birini bosib ketardi.

### 5.1 Rang CSS fonida emas, SVG ichida
Brauzerda «Print background graphics» standart holatda **o'chiq** —
`background` bilan chizilgan diagramma printerdan **oq** chiqadi. Har bir
ma'lumot tashiydigan rang SVG `fill` yoki CSS `border` da.
`print-color-adjust: exact` bor, lekin unga **tayanilmaydi**.

### 5.2 PDF ni brauzer yasaydi, server emas
TCPDF tanlanmadi: o'zbek tipografiyasi, SVG va matn oqimi bilan yomon
ishlaydi. `@page` qoidasi **sahifaning o'zida** (`styles.css` da emas):
uni selektor bilan chegaralab bo'lmaydi, umumiy faylga yozilsa butun
saytning bosma chetlari o'zgarardi.

### 5.3 Maket qat'iy emas
Fan soni ham, sharh uzunligi ham oldindan noma'lum. Majburiy bet
uzilishi **faqat o'quvchilar orasida**; jadval va sharh bloki bet
oxirida bo'linadi (qator va kategoriya bus-butun ko'chadi). Aks holda
har betning pastida 4–5 sm bo'sh joy qolardi — 30 nusxada o'nlab varaq.

## 6. Maxfiylik

`maxfiy` sharhlar hisobotga **hech qachon** tushmaydi: manba
`sharh::mine()`, u so'rovning o'zida `visibility = 'ochiq'` ni talab
qiladi. Filtrlash chizishda emas, **manbada**.

## 7. Ruxsatlar va kirish nuqtalari

| Kim | Nima ko'radi | Qayerdan |
|---|---|---|
| O'quvchi / ota-ona | o'zinikini | `baholarim.php`, `sharhlarim.php` tugmasi |
| Fan o'qituvchisi | o'z o'quvchilarini | `sharh.php` qatoridagi «O'quvchi hisoboti» |
| Sinf rahbari (tutor) | o'z sinfini, **butun sinfni bir bosishda** | menyu → sinf tanlagich |
| Direktor / zavuch | hammasini | menyu → sinf tanlagich |

Tekshiruv: `hisobot::korish_mumkinmi()` / `guruh_mumkinmi()`; menyu
sharti `hisobot::tutormi()` — ayni mantiq, aks holda havola boshi berk
yo'lga olib borardi. Bir urinishda eng ko'pi **60** o'quvchi.

## 8. Fayllar

| Fayl | Vazifa |
|---|---|
| `hisobot.php` | sahifa: ruxsat, davr tanlagich, sinf tanlagich, `@page` |
| `classes/hisobot.php` | ma'lumot yig'ish (so'rovlar shu yerda), daraja shkalasi |
| `classes/output/hisobot_varaq.php` | chizish (so'rov YO'Q): HTML + SVG |
| `styles.css` → `tz-hr-*` | qog'oz maketi, `@media print` |
| `pix/logo.png` | maktab logotipi |

## 8.1 Ekrandagi ko'rinish

Varaq avval ekranda ko'riladi, keyin bosiladi. `@media screen` da:
ustunlar chapdan o'ngga ochiladi (`clip-path` animatsiyasi), kartaga
sichqoncha tekkanda ko'tariladi, diagramma qatorida qalqib chiquvchi
izoh bor (SVG `<title>`: fan, foiz, daraja, sinf o'rtachasi), vositalar
paneli yopishib turadi. `@media print` da bularning hammasi
**o'chiriladi** — qog'ozda harakat ham, soya ham kerak emas.
`prefers-reduced-motion` hurmat qilinadi.

## 9. Chop etish

Chrome → **Chop etish** → «PDF sifatida saqlash» → **A4** →
«Fon rasmlari» (Background graphics) **yoqilgan**. Fon o'chiq bo'lsa ham
diagrammalar chiqadi (5.1), lekin plita va band ranglari oqaradi.

## 10. Dizaynni Moodle'siz tekshirish

Lokalda Moodle runtime yo'q (config.php o'qilmaydi). Shuning uchun
maket soxta ma'lumot bilan tekshiriladi: Moodle funksiyalarini taqlid
qiladigan kichik harness → `hisobot_varaq::chiz()` → HTML →
`google-chrome --headless --print-to-pdf` → `pdftoppm -png` → rasmga
qarash. Shu yo'l bilan bet soni, uzilish joylari va ranglar haqiqatda
ko'riladi.
