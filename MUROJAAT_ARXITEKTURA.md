# Murojaatlar — Moodle arxitekturasi

Ota-onalarning shikoyat / taklif / muammolari: sotuv bo'limida tug'iladi,
**rahbariyat tomonidan Moodle'da hal qilinadi**, javob ota-onaga bolasining
ilovasi orqali boradi.

## 1. Egalik taqsimoti (2026-11-04 qarori)

| Qism | Egasi | Nega |
|---|---|---|
| Murojaatning TUG'ILISHI: kim qachon gaplashdi, kim shikoyat qildi, nima dedi | **Frappe** | Qo'ng'iroq yozuvi, AI tahlili va qarzdorlik ishi Frappe'da |
| Murojaatning YURITILISHI: ko'rish, komment, qaror | **Moodle** | CEO, direktor va zavuch kundalik ishni Moodle'da/ilovada qiladi |
| Ota-onaga yetkazish | **Moodle** | Moodle'ning bildirishnoma + mobil ilovasi bor, Frappe'da yo'q |
| Frappe panellarida javobni ko'rsatish | Frappe (nusxa) | Investor paneli, sotuv nazorati va qarzdorlik paneli uchun |

Ya'ni **ikki tomonlama yozish YO'Q**. Har maydonning bitta egasi bor:
Frappe manba maydonlarini yuboradi, Moodle yuritish maydonlarini o'zi yozadi.
Frappe esa yuritish maydonlarini faqat **o'qib** ko'rsatadi. Shuning uchun
"kim ustun" degan savol hech qachon tug'ilmaydi.

## 2. Ma'lumot oqimi

```
Sotuvchi / AI (Frappe)
   │  Murojaat yaratiladi (MR-YYYY-#####)
   ▼
local_targetzenit_push_murojaat          (Frappe -> Moodle, 15 daqiqada)
   │  faqat MANBA maydonlari: matn, turi, kategoriya, yo'nalish,
   │  o'quvchi, sinf, ota-ona, menejer, suhbat vaqti
   ▼
Moodle: local_targetzenit_murojaat
   │
   ├─► CEO / Direktor / Zavuch  (murojaat.php + mobil panel)
   │      ko'radi · komment yozadi · QAROR qabul qiladi
   │      └─► qaror saqlanganda: o'quvchiga Moodle bildirishnomasi (push)
   │
   ├─► O'quvchi akkaunti = OTA-ONA  (murojaatim.php + mobil panel)
   │      shikoyat matni + rahbar javobi; ko'rgani qayd etiladi
   │
   └─► local_targetzenit_get_murojaat    (Moodle -> Frappe, so'rab olish)
          Frappe nusxaga yozadi: holat, qaror, qaror kim/qachon, ko'rildi
          └─► /app/murojaatlar · sotuv nazorati · qarzdorlik paneli
```

**Nega Moodle Frappeni chaqirmaydi:** butun integratsiya bir tomonlama
ishonchga qurilgan — tokenni FAQAT Frappe ushlab turadi. Moodle'ga Frappe
kalitini berish yangi xavfsizlik yuzasi ochadi va ikki tomonlama bog'liqlik
yaratadi (biri yiqilsa ikkinchisi ham). Shuning uchun javoblarni Frappe
**o'zi so'rab oladi**.

## 3. Shaxs ko'prigi — faqat o'zgarmas ID

| Kim | Frappe | Moodle |
|---|---|---|
| O'quvchi | `Student.name` | `user.idnumber` |
| Menejer / rahbar | `Employee.name` | `user.idnumber` |
| Murojaat | `Murojaat.name` (MR-…) | `_murojaat.frappename` (unique) |

Ism yoki email bo'yicha **hech qachon** qidirilmaydi: ism o'zgaradi, ID
o'zgarmaydi. Moodle akkaunti topilmasa qator saqlanadi, lekin `studentuserid`
/ `manageruserid` 0 bo'lib qoladi — panel buni ko'rsatadi, jim qolmaydi.

## 4. Ruxsatlar

| Capability | Kimga | Nima |
|---|---|---|
| `local/targetzenit:murojaatceo` | CEO roli | Tashkiliy / to'lov murojaatlari |
| `local/targetzenit:murojaatdirektor` | Direktor va **zavuch** rollari | O'quv-tarbiya murojaatlari |

Ikkisi ham `archetypes => []` bilan e'lon qilinadi — ya'ni avtomatik hech
kimga tushmaydi, admin QO'LDA rolga biriktiradi. Sabab: `manager` arxetipiga
bog'lasak CEO ham direktor murojaatlarini ko'rar edi va yo'nalish ajratish
ma'nosini yo'qotardi. Sayt admini (`is_siteadmin`) ikkisini ham ko'radi.

Ota-ona/o'quvchi uchun ruxsat kerak emas: `murojaatim.php` faqat
`studentuserid = $USER->id` qatorlarni ko'rsatadi.

## 5. Holat zanjiri

```
Yangi ──(rahbar ochdi)──► Ko'rib chiqilmoqda ──(qaror)──► Yopildi
```

Qaror yozilmasa yopib bo'lmaydi. `qarorturi`: `Hal qilindi` ·
`Inobatga olindi` · `Keyinroq ko'riladi` · `Rad etildi` · `Ahamiyatsiz`.
`Ahamiyatsiz` — AI xato topgan holat: ota-onaga bildirishnoma YUBORILMAYDI.

## 6. Ota-ona nima ko'radi

Shikoyat matni **va** rahbar javobi (2026-11-04 qarori). Rahbarlarning
ichki kommentlari ko'rinmaydi: `_murojaat_izoh.korinish` = `rahbar`
(standart) yoki `otaona`.

O'quvchining o'zi ham shu matnni o'qiydi — ota-ona bolasining akkauntidan
kiradi, Moodle'da ota-ona akkaunti yo'q (faqat Student/Instructor/Employee
sinxronlanadi).

## 7. Fayllar

| Fayl | Vazifa |
|---|---|
| `db/install.xml`, `db/upgrade.php` | `_murojaat`, `_murojaat_izoh` jadvallari |
| `classes/murojaat.php` | Ma'lumot qatlami — veb ham, ilova ham shundan oziqlanadi |
| `_murojaat_suhbat` jadvali | Sotuv bo'limining aloqa tarixi (Frappe nusxasi) |
| `classes/external/push_murojaat.php` | Frappe -> Moodle |
| `classes/external/get_murojaat.php` | Moodle -> Frappe (so'rab olish) |
| `classes/external/mobile_murojaat.php` | Ilovadan komment / qaror / ko'rildi |
| `murojaat.php` | Rahbar sahifasi (shell navigatsiyasida) |
| `murojaatim.php` | O'quvchi / ota-ona sahifasi |
| `classes/output/mobile.php` | `murojaat_view`, `murojaatim_view` |
| `templates/mobile_murojaat*.mustache` | Ionic shablonlari |
| `db/messages.php` | `murojaat` bildirishnoma provayderi |

Frappe tomonda: `target_zenit/integrations/moodle_murojaat.py` (push + pull).

## 7.1 Frappe paneli bilan muvofiqlik

Moodle sahifasi Frappe panelining ketma-ketligini TAKRORLAYDI: ota-ona gapi →
kim murojaat qildi (qarz holati bilan) → rahbar qarori → izohlar → sotuv bilan
aloqa tarixi → shu o'quvchining boshqa murojaatlari. Filtrlar ham bir xil:
holat chiplari, turi, kategoriya, kim biriktirilgan, qidiruv. Rahbar
kompyuterda ham, ilovada ham bir xil joydan qaraydi.

**Ataylab KO'CHIRILMAGAN:** qo'ng'iroq ovozi va AI transkripti. Ular Frappe'da
qoladi — qarzdorlik paneli va sotuv nazorati uchun. Moodle'ga faqat suhbatning
NATIJASI keladi (kim, qachon, qanday kanal, natija, izoh), ya'ni rahbarga
qaror qabul qilish uchun yetarli bo'lgan qism.

**Suhbatlar JSON satr bo'lib uzatiladi:** Moodle REST so'rovni forma
maydonlari ko'rinishida oladi va ichma-ich ro'yxat bilan PHP
`max_input_vars` (5000) oshib ketardi (120 murojaat × 10 suhbat × 6 maydon =
7200). JSON bilan har murojaatga bitta maydon ketadi — real o'lchov:
120 × 21 = 2520 maydon.

## 8. Bosqichlar

1. **Rahbar + o'quvchi** (bu bosqich): jadvallar, push/pull, rahbar sahifasi
   va mobil paneli, ota-ona sahifasi va mobil paneli, bildirishnoma.
2. Sotuvchi paneli Moodle'da (hozircha sotuvchi Frappe'da ishlaydi).
3. Hisobotlar: kategoriya kesimi, hal qilish muddati, takrorlanish.
