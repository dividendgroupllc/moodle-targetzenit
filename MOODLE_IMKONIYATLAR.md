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
