# Psixolog moduli — arxitektura

**Holat:** 1-bosqich LOKALDA qurildi · prod qolgan · 2026-11-21
**Qaror qabul qilgan:** foydalanuvchi, 2026-11-21

Maktab psixologi butun maktab (1–11-sinf o'quvchilari **va** xodimlar) bilan
ishlaydi: test/so'rovnoma o'tkazadi va tekshiradi, o'quvchi muammolarini
yuritadi (case), individual suhbat qaydlarini yozadi.

## 0. Foydalanuvchi qarorlari (2026-11-21)

| Savol | Qaror |
|---|---|
| Suhbat/case yozuvlarini kim ko'radi? | **Faqat psixolog.** Rahbariyat (direktor/zavuch) faqat STATISTIKANI ko'radi — matnsiz (nechta suhbat, qaysi sinf, qaysi toifa). |
| Test turi | **Ikkalasi:** nomma-nom test = `mod_quiz`, anonim so'rovnoma = `mod_feedback` (ikkisi ham yadroda). |
| Xodimlar | **1-bosqichda birga:** markaziy kursga xodimlar ham «Xodimlar» guruhi bilan yoziladi. |
| Qamrov | 1–11-sinf hammasi, **biriktirish Frappe'dan** (sinxron). |

## 1. Umumiy tuzilma — ikki qavat (koordinator naqshi)

Koordinator rolidagi mantiq bilan bir xil: ruxsat Moodle'da yuqoridan pastga
oqadi, bizning sahifalar TIZIM kontekstida, yadro ishlari esa KURS kontekstida.

| Qavat | Nima | Qayerda |
|---|---|---|
| `tzpsixolog` roli (tizim) | Bizning panel: suhbat, case, statistika, 360 karta | `local/targetzenit:psixolog` capability |
| `editingteacher` (markaziy kursda) | Yadro: quiz/feedback yaratish, savol banki, natijalar | kursga yozilish — **Frappe sinxoni** qiladi |

Ikkinchi qavat uchun ALOHIDA rol kerak emas — psixolog markaziy kursning
oddiy o'qituvchisi. Shu bilan `imtihon`/`topshiriq` sahifalari ham unga o'zi
ishlab ketadi (teach ruxsati editingteacher arxetipida bor).

## 2. Markaziy kurs: `PSIXOLOGIYA::<yil>`

Baholash modulidagi «markaziy kurs» g'oyasi (BAHOLASH::<yil>) bo'yicha, lekin
bu kurs **ko'rinadigan** (visible=1): o'quvchi testni aynan shu kurs orqali
yechadi (sayt + mobil ilova — standart Moodle).

- `idnumber = PSIXOLOGIYA::<yil>`, nomi «Psixologiya xizmati», `<yil>`
  (TZ-AY-<yil>) kategoriyasida.
- **Guruhlar = sinflar**: «1 A(full)» … «11 B(hybrid)» + «Xodimlar».
  Guruh `idnumber = PSIX::<yil>::<guruh nomi>`.
- Test butun maktabga ham, bitta sinfga ham berilishi mumkin — faoliyatning
  guruh cheklovi (restrict access → group) bilan.
- Kursda `groupmode` ataylab majburlanmaydi — feedback anonim rejimi va quiz
  guruh hisobotlari standartda ishlayveradi.

## 3. Frappe sinxoni — `integrations/moodle_psixologiya.py`

Cron **07:15** (foydalanuvchi 07:00 va kurs 07:10 sinxronidan KEYIN: Moodle
user id va kategoriya allaqachon kerak).

1. Markaziy kursni ta'minlaydi (`core_course_get_courses_by_field` →
   `core_course_create_courses`).
2. Sinf guruhlarini ta'minlaydi (`core_group_*`). **Sinf qoidasi:** Student
   Group `group_based_on='Activity'` VA nomi raqam bilan boshlanadi
   («7 A(full)») — CHOICE/daraja guruhlari chetda qoladi.
3. Barcha faol o'quvchini student rolida yozadi + o'z sinf guruhiga qo'shadi;
   faol xodimlarni student rolida + «Xodimlar» guruhiga.
4. **Psixologni** (Moodle Settings → `psixolog` Link Employee) editingteacher
   rolida yozadi.
5. Chiqib ketganlarni suspend qiladi (kurs sinxoni bilan bir xil:
   HECH QACHON o'chirilmaydi, javoblari qoladi).
6. Guruh a'zoligi diff bilan (har yurishda delete+insert YO'Q — tezlik
   qoidasi); yozilish esa idempotent `enrol_manual_enrol_users`.

Yangi yadro WS funksiyalari `classes/servis.php::YADRO` ga qo'shildi — deploy
o'zi Frappe servisiga biriktiradi (`servis_funksiyalari.php` saboqlari).

## 4. Ruxsat va MAXFIYLIK

```
local/targetzenit:psixolog   CONTEXT_SYSTEM · write · RISK_PERSONAL · archetypes []
```

- `archetypes` ATAYLAB BO'SH — rol `cli/psixolog_rol.php` bilan yaratiladi
  (koordinator/direktor bilan bir xil sabab). `manager`ga bog'lasak direktor
  suhbat matnlarini ko'rar edi — bu esa 0-bo'limdagi qarorga zid.
- **Suhbat/case MATNI faqat `psixolog` ruxsati borga.** Sayt admini texnik
  jihatdan hamma narsani ko'radi (Moodle'da bu chetlab o'tilmaydi) — bu
  foydalanuvchiga aytilgan chegara.
- Rahbariyat (`viewteachers`) panelda faqat **Statistika** ko'rinishini
  ochadi: oy/sinf/tur kesimida SONLAR, matn YO'Q, o'quvchi ismi YO'Q.
- Psixologlar bir-birining yozuvini ko'radi (almashganda tarix yo'qolmasin);
  tahrir/o'chirish faqat o'z yozuvida.

## 5. Ma'lumot modeli (2 jadval)

```
local_targetzenit_psix_case          o'quvchi/xodim muammosi (ish varaqasi)
  id · userid · persontype(student|staff) · psixologid
  sarlavha · tavsif · manba(kuzatuv|oqituvchi|otaona|murojaat|test)
  muhimlik(past|orta|yuqori) · holat(ochiq|kuzatuvda|yopildi)
  xulosa · yopilganvaqti · timecreated · timemodified

local_targetzenit_psix_suhbat        individual suhbat qaydi
  id · userid · persontype · caseid(0=erkin) · psixologid
  sana · turi(individual|guruhiy|otaona|kuzatuv)
  matn · tavsiya · timecreated · timemodified
```

Suhbat case'ga bog'lanishi ixtiyoriy: muammo ustida ishlash — case ichidagi
suhbatlar zanjiri; profilaktik suhbat — erkin qayd.

Testlar uchun jadval YO'Q — quiz/feedback natijalari yadroda yashaydi
(qayta ixtiro qilinmaydi, imtihon moduli tamoyili).

## 6. Panel — `psixolog.php` (shell qobig'i, `psix_menu`)

| Ko'rinish | Nima | Kim |
|---|---|---|
| `oquvchilar` | sinf tanlash → o'quvchi ro'yxati (oxirgi suhbat, ochiq case), qidiruv; amallar: suhbat qaydi, case ochish, 360 karta | psixolog |
| `suhbatlar` | qaydlar lentasi + filtr (sinf/tur/odam), yangi qayd formasi | psixolog |
| `caselar` | ochiq/kuzatuvda/yopildi; case kartasi (details) ichida bog'langan suhbatlar, holat o'zgartirish, xulosa bilan yopish | psixolog |
| `testlar` | markaziy kursdagi quiz+feedback ro'yxati (holat, nechta javob), «natija» havolalari; yangi quiz/feedback yaratish tugmalari (standart modedit) | psixolog |
| `stat` | oy/sinf/tur kesimida sonlar, ochiq caselar — MATNSIZ | psixolog + rahbariyat |

- Yozish amallari POST + sesskey (murojaat naqshi).
- `oquvchi360.php` ruxsatiga psixolog qo'shildi (butun maktab kartalari).
- Yuqori menyuga «Psixolog paneli» (`primary_extend`, cap borga).
- UI `tz-mur-*` / `tz-dir-*` mavjud uslub sinflarida — yangi CSS minimal.

## 7. Testlar oqimi

1. Psixolog panelda «Yangi test (quiz)» / «Yangi so'rovnoma (feedback)» —
   markaziy kursda standart modedit ochiladi (psixolog u yerda
   editingteacher, hamma yadro imkoniyati bor).
2. Qamrov: butun maktab (cheklovsiz) yoki sinf(lar) — restrict access → group.
3. O'quvchi testni standart kurs sahifasi / mobil ilovada yechadi.
4. Tekshirish: quiz — avto + insholar grader'da; feedback — «Analysis»
   (anonimda shaxs ko'rinmaydi — bu feedbackning o'z kafolati).
5. Panel «Testlar» ro'yxati holat va javob sonini ko'rsatib, yadro
   hisobotlariga olib boradi.

## 8. Fayllar

| Fayl | Vazifa |
|---|---|
| `db/access.php` | `local/targetzenit:psixolog` |
| `db/upgrade.php` + `db/install.xml` | `_psix_case`, `_psix_suhbat` |
| `classes/psixolog.php` | ma'lumot qatlami (kurs, guruh, suhbat, case, stat, testlar) |
| `psixolog.php` | panel (5 ko'rinish) |
| `classes/output/shell.php` | `psix_menu()` |
| `classes/hook_callbacks.php` | yuqori menyu bandi |
| `oquvchi360.php` | psixolog ruxsati |
| `cli/psixolog_rol.php` | rol yaratish/berish/tekshirish |
| `classes/servis.php` | yangi yadro WS funksiyalari |
| Frappe: `integrations/moodle_psixologiya.py` | kurs+guruh+yozilish sinxoni |
| Frappe: `Moodle Settings` | `sync_psixologiya` (Check), `psixolog` (Link Employee) |
| Frappe: `hooks.py` | cron 07:15 |

## 9. Ishga tushirish tartibi (prod)

1. Plagin deploy (CI) — jadval + capability + sahifalar.
2. Frappe deploy + `bench migrate` (Moodle Settings maydonlari).
3. Moodle Settings'da `sync_psixologiya` yoqish + `psixolog` xodimini tanlash.
4. `sudo php local/targetzenit/cli/psixolog_rol.php --yarat` keyin
   `--ber=<Employee ID>`.
5. Sinxron: `bench execute target_zenit.integrations.moodle_psixologiya.sync_now`
   (yoki 07:15 cronni kutish).

## 10. Keyingi bosqichlar (qurilmagan)

- Murojaat moduli → psixologga yo'naltirish (case'ga avtomatik aylanishi).
- Test natijasi → case ochish tavsiyasi (xavf belgilari bo'yicha).
- Mobil ilova paneli (psixolog uchun).
- Standart psixologik metodikalar banki (savol banki import).
