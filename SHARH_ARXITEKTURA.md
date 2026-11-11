# O'quvchi sharhi (student review) — arxitektura

**Versiya:** 0.33.0 (2026-11-06) · `local_targetzenit`

O'qituvchi har bir o'quvchisi haqida qisqa yozma sharh (xarakteristika)
qoldiradi. O'quvchi va ota-ona uni o'z panelida ko'radi va bildirishnoma
oladi; direktor/zavuch butun maktab bo'ylab «kim qaysi o'quvchiga nima deb
yozdi» ni ko'radi.

## 1. Nega kerak

Tizimda o'quvchi haqida yozma gap uchun joy yo'q edi:

| Mavjud | Nimaga tegishli | Nega yetmaydi |
|---|---|---|
| `_record.comment` | bitta DARS | chorak/umumiy xarakteristika sig'maydi |
| `_feedback` | O'QITUVCHI (direktor fidbegi) | o'quvchi haqida emas |
| `_murojaat` | OTA-ONA shikoyati | teskari yo'nalish |

Ota-ona «bolam qanday o'qiyapti?» deb so'raganda baho va davomatdan boshqa
hech narsa yo'q edi.

## 2. Ma'lumot modeli — matn Moodle'ning o'zida

**Sharh matni `local_targetzenit_sharh` da SAQLANMAYDI.** U Moodle'ning
standart comment quyi tizimida, `{comments}` jadvalida:

| `{comments}` maydoni | Qiymat |
|---|---|
| `component` | `local_targetzenit` |
| `commentarea` | `studentreview` (`sharh::AREA`) |
| `itemid` | o'quvchining `user.id` — **kim haqida** |
| `contextid` | **KURS** konteksti — qaysi guruh + fan |
| `userid` | **kim yozdi** |
| `content` / `format` | matn, `FORMAT_PLAIN` |

`local_targetzenit_sharh` esa faqat **holat va indeksli filtr** uchun:
`commentid` (UNIQUE), `courseid`, `studentid`, `authorid`, `visibility`,
`status`, `seenat`, `timecreated`.

**Nega shunday:** plaginning umumiy tamoyili — «avval Moodle'ning o'z
vositasi». Comment quyi tizimi tayyor holda ruxsat tekshiruvi, hodisa
(event) va tozalashni beradi. Matnni ikki joyda saqlasak ular bir-biridan
ajralib ketadi, shuning uchun yagona manba — `{comments}`. `{comments}` ga
**INNER JOIN**: comment core vositasi bilan o'chirilsa qator ro'yxatdan
ham chiqib ketadi.

### MAJBURIY callbacklar (`lib.php`)

```php
local_targetzenit_comment_validate($params)      // -> bool
local_targetzenit_comment_permissions($params)   // -> ['post' => ?, 'view' => ?]
```

- `validate()` **bo'lmasa** `can_post()` ning o'zi `invalidcommentparam`
  bilan yiqiladi: `manager::validate()` plagin chaqiruvini standart
  qiymat `false` bilan so'raydi.
- `permissions()` **bo'lmasa** standart `['post'=>false,'view'=>false]` —
  hech kim yoza olmaydi.
- **TUZOQ:** `moodle/comment:post` kurs kontekstida **o'quvchida ham** bor
  (core arxetipi). Shuning uchun yozish `local/targetzenit:teach` ga
  qattiq bog'langan — aks holda o'quvchi o'zi haqida sharh yozib qo'yardi.

## 3. Qamrov — guruh + fan (qaror 2026-11-06)

Sharh **kursga** bog'lanadi. O'qituvchining kursi allaqachon
`<yil>::<guruh>::<fan>`, shuning uchun «guruh bo'yicha filtr» alohida
maydon talab qilmaydi — kurs tanlagichining o'zi shu.

Ochiq qolgani: **guruh tutori** ning fansiz «umumiy xarakteristika» si.
Hozir tutor o'z kurslaridan biri ostida yozadi.

## 4. Ko'rinish — ochiq / maxfiy

| `visibility` | O'quvchi ko'radimi | Bildirishnoma |
|---|---|---|
| `ochiq` (standart) | ha | ha |
| `maxfiy` | **yo'q** | yo'q |

Standart ataylab **ochiq**: sharhning maqsadi o'quvchiga va ota-onaga
qaytarma berish. «Maxfiy» — ma'muriy qayd, tanlanadigan istisno: uni shu kursda
dars o'tuvchilar va direktor/zavuch ko'radi, o'quvchi ko'rmaydi.
O'quvchi tomonida maxfiy qatorlar **so'rovning o'zida** chiqarib
tashlanadi (`sharh::mine()`) — blokni yashirish himoya emas.

## 5. Ruxsatlar

| Amal | Shart |
|---|---|
| Yozish | `local/targetzenit:teach` **kurs** kontekstida + o'quvchi shu kursga yozilgan (`is_enrolled`) |
| O'z sharhlarini o'qish | o'zi (`itemid`) |
| Boshqa o'quvchining sharhini o'qish | `viewteachers` **yoki** shu o'quvchiga dars beradigan o'qituvchi |
| Butun maktab lentasi | `viewteachers` |

Yangi capability **qo'shilmadi** — mavjud uchtasi aynan shu savollarga
javob beradi.

`viewalltimetable` ataylab ishlatilmaydi: u core arxetipi bo'yicha har bir
o'qituvchida bor va butun maktabning sharhini ochib qo'yardi.

## 6. Uch sahifa, bitta chizuvchi

| Fayl | Kim | Nima |
|---|---|---|
| `sharh.php` | o'qituvchi | guruh tanlagich + ism qidiruvi + o'quvchilar ro'yxati, qatorda yozish, ochilgan qatorda to'liq zanjir |
| `sharhlar.php` | direktor/zavuch | butun maktab lentasi, 5 filtr (o'qituvchi, guruh, o'quvchi, sana oralig'i, «faqat o'qilmaganlar») — **faqat o'qish** |
| `sharhlarim.php` | o'quvchi / ota-ona | fan bo'yicha guruhlangan ro'yxat, ochilganda «ko'rildi» — **faqat o'qish** |

Chizuvchi bitta: `classes/output/sharhblok.php`
(`thread()` / `form()` / `compactcell()` / `handle()`) — `output\fidbek`
bilan bir xil sabab: nusxalansa uch sahifa bir-biridan farq qila boshlaydi.

Direktor sharh **yozmaydi**: kuzatuvchi o'zi baho qo'yadigan dalilni
yarata olmasligi kerak (`yozuvlar.php` dagi qoida bilan bir xil). Uning
o'qituvchiga aytadigan gapi uchun FIDBEK bor.

Ota-ona **javob yozmaydi** (qaror 2026-11-06): e'tirozi bo'lsa
MUROJAATLAR moduli bor — u yerda javob egasi va muddati belgilangan.

## 7. Navigatsiya

- O'qituvchi yon menyusi (`shell::oq_menu`): Hisob · Yozuvlar · Jadval ·
  **O'quvchilar sharhi** · Mening oyligim — *oylikdan oldin*
  (foydalanuvchi talabi).
- Direktor menyusi (`shell::dir_menu`): ... Yozuvlar · **Barcha sharhlar** ·
  O'qituvchi paneli.
- O'quvchi: yuqori menyu + chap menyu + `baholarim.php` dagi tugma
  (o'qilmaganlar soni bilan) + bildirishnomadagi havola. Havola faqat
  sharhi BOR odamda chiqadi (`sharh::has_any`).

## 8. Bildirishnoma

Moodle'ning standart message API si (`db/messages.php` da `sharh`
provideri) — qo'ng'iroqcha, pochta, mobil ilova. `capability` **ataylab
yo'q**: o'quvchida maxsus ruxsat bo'lmaydi, shart qo'yilsa bildirishnoma
hech kimga yetmasdi (`murojaat` provideri bilan bir xil sabab).

Bildirishnoma yuborilmasa sharh saqlanishi to'smaydi (`try/catch`).

## 9. Hali yo'q

- **Mobil ilova paneli** (qaror: keyingi bosqich) — `db/mobile.php` +
  `output/mobile.php` + mustache.
- **Tutor umumiy sharhi** (fansiz).
- ~~O'chirish~~ — **QO'SHILDI (2026-11-11, 0.44.1):** `sharh::ochir()`,
  FAQAT `moodle/site:config` (sayt ma'muri). O'chirish **TO'PLAM** bo'yicha —
  besh kategoriya birga ketadi, aks holda «chala sharh» qolardi.
  Matn Moodle comment API si (`manager::delete()`) bilan o'chiriladi, shunda
  hodisa va tozalash ham o'zi ishlaydi; kontekst yo'qolgan bo'lsa
  `{comments}` to'g'ridan-to'g'ri tozalanadi (holat qatori yetim qolmasin).
  Tugma `thread()` da, lekin FAQAT `$ochirurl` berilganda — o'quvchi/ota-ona
  sahifasi uni bermaydi, ya'ni tugma u yerda tasodifan chiqmaydi.
  **Nega o'qituvchi va direktor o'chira olmaydi:** sharh — o'quvchi haqidagi
  yozma dalil; muallif o'zi yozganini keyin o'chira olsa ota-ona oldida hech
  narsa qolmaydi, direktor esa kuzatuvchi (`yozuvlar.php` qoidasi).
- **`privacy/provider.php`** hamon «shaxsiy ma'lumot saqlamaydi» deb
  turadi. Bu `_feedback` bilan allaqachon noto'g'ri edi, sharh bilan yana
  bir pog'ona noto'g'ri bo'ldi: jadvalda voyaga yetmaganlar haqidagi
  xodim izohlari bor. **Majburiy qarz.**

---

## 10. BESH KATEGORIYA (2026-11-10, foydalanuvchi talabi)

Erkin matnli bitta sharh o'rniga har o'qituvchi har o'quvchiga **beshta
kategoriya** bo'yicha yozadi:

| Kalit | Nomi |
|---|---|
| `akademik` | Academic Performance — Akademik bilim |
| `faollik` | Classroom Participation — Darsdagi faollik |
| `ijtimoiy` | Social and Emotional Skills |
| `masuliyat` | Responsibility and Independence |
| `tanqidiy` | Critical Thinking and Creativity |

**BESHTASI HAM MAJBURIY** (`sharh::add_batch()` tekshiradi + formada
`required`). Nega qattiq: bittasi bo'sh qolsa hisobot «akademik bilimi bor,
ijtimoiy ko'nikmasi yo'q» degan noto'g'ri xulosaga olib keladi — aslida esa
shunchaki yozilmagan. Bo'sh qoldirishga ruxsat bersak amalda faqat birinchi
maydon to'ldiriladi.

### Model — har kategoriya ALOHIDA comment

Matn baribir Moodle'ning `{comments}` jadvalida qoladi (modulning asosi
shu), `_sharh` ga ikki ustun qo'shildi:

- **`kategoriya`** (CHAR 20, standart `umumiy`) — qaysi biri;
- **`batch`** (CHAR 32, NULL) — **bitta saqlash = bitta to'plam**, besh qator
  shu kalitni bo'lishadi.

**Nega JSON emas:** matnni bitta katakka JSON qilib solsak, comment quyi
tizimining qidiruvi, hodisalari va tozalashi yo'qoladi.

**Eski yozuvlar saqlanadi:** 2026-11-10 dan oldingi erkin sharhlar
`kategoriya = umumiy` bo'lib qoladi va «Umumiy (oldingi sharh)» yorlig'i
ostida ko'rinadi. Ular **ko'chirilmaydi**: o'qituvchi o'sha matnni qaysi
kategoriya deb yozganini bilmaymiz, taxmin qilish xato bo'lardi.

### Nimalar o'zgardi

| Joy | O'zgarish |
|---|---|
| `sharh::add_batch()` | asosiy yozish yo'li; `add()` endi bitta kategoriyani yozadi |
| Bildirishnoma | TO'PLAM uchun **bitta** (avval beshta ketardi) |
| `sharh::guruhla()` | qatorlarni to'plamlarga yig'adi; eski sharh ham to'plam (`old<id>`) |
| `latest()` dagi `soni` | **to'plamlar** soni, qatorlar emas — aks holda bitta sharh «5 sharh» bo'lib ko'rinardi |
| `sharhblok::form()` | besh maydon, har biriga yorliq + ko'rsatma + namuna matn |
| `sharhblok::compactcell()` | bir qatorli yozish maydoni **olib tashlandi** (besh maydon sig'maydi), faqat xulosa |
| `sharh.php` tugmasi | sharh yo'q bo'lsa «Sharh yozish» (ko'k), bor bo'lsa «Ochish» |
| `sharhlar.php` | lenta **to'plamlar bo'yicha** guruhlanadi, aks holda besh barobar uzun bo'lardi |

Migratsiya: `2026111030`, version 0.44.0.
