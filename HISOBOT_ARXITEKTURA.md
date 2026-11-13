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
| o'quvchi karti | ism, sinf, sinf rahbari, ID |
| **4 ko'rsatkich** | umumiy natija · eng kuchli fan · e'tibor kerak fan · sinfga nisbatan |
| **«Qisqacha»** | raqamlar GAP shaklida — diagrammani hamma o'qimaydi, gapni hamma o'qiydi |
| **Kuchli / Qo'llab-quvvatlash kerak** | majlisdan eslab qolinadigan ikki ro'yxat |
| ustun diagramma | fanlar **kuchlisidan sustigacha** tartiblangan + sinf o'rtachasi belgisi |
| radar + shkala | muvozanat va «80% nima degani» |
| testlar jadvali | har bir test: ball, foiz, daraja, sinf o'rtachasi |
| **o'qituvchilar sharhi** | 5 kategoriya, yonida shu fanning foizi |
| izoh + imzo | shkala izohi, «past natija hukm emas», imzo chiziqlari |

Natija umuman yo'q bo'lsa diagramma va shkala **chizilmaydi** — bo'sh
o'qlar varaqni to'ldiradi, lekin hech narsa aytmaydi.

## 5. Uchta qat'iy qoida

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
