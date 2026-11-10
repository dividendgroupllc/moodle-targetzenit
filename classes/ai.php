<?php
// Dars yozuvini AI bilan tahlil qilish (Gemini).
//
// Frappe'dagi `telefoniya/stt.py` yondashuvi ko'chirildi: audio to'g'ridan-to'g'ri
// modelga beriladi (transkript va tahlil BIR so'rovda), kvota tugasa zaxira
// modelga o'tiladi, 503 (band) da qayta uriniladi.
//
// NEGA MOODLE TOMONIDA: audio shu yerda yotibdi (har dars ~8 MB), tahlil esa
// darsning yonida ko'rinishi kerak. Frappe moliya/ma'muriyat bo'lib qoladi.
//
// NATIJA QAT'IY JSON: transkript + xulosa + mavzuga muvofiqlik + dars tuzilishi
// + nutq ulushi. Audio 15 kundan keyin o'chadi, bu natija esa qoladi.

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class ai {

    const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';
    const URINISH = 3;
    /** Inline audio uchun amaliy chegara (base64 ~1.37x shishiradi). */
    const MAX_BYTES = 15728640;   // 15 MB

    /**
     * Kategoriya nomi foydalanuvchining tilida.
     *
     * `CATEGORIES` dagi `nom` o'zbekcha qotib yozilgan va u faqat zaxira:
     * til satri topilmasa o'sha ishlatiladi.
     */
    public static function cat_name(string $kod): string {
        $kalit = 'ai_cat_' . $kod;
        $manager = get_string_manager();
        if ($manager->string_exists($kalit, 'local_targetzenit')) {
            return get_string($kalit, 'local_targetzenit');
        }
        return self::CATEGORIES[$kod]['nom'] ?? $kod;
    }

    /** Kategoriya guruhining nomi foydalanuvchining tilida. */
    public static function grp_name(string $kod): string {
        $kalit = 'ai_grp_' . $kod;
        $manager = get_string_manager();
        if ($manager->string_exists($kalit, 'local_targetzenit')) {
            return get_string($kalit, 'local_targetzenit');
        }
        return $kod;
    }

    /**
     * BAHOLASH RAMKASI: GENERIC O'ZAK + FAN MODULI.
     *
     * NEGA SHUNDAY TUZILGAN: Charalambous va Praetorius (2018) generic'dan
     * fanga-xos tomon KONTINUUM taklif qiladi va tadqiqotlar bitta xulosaga
     * keladi — sof generic instrument o'qituvchiga ANIQ maslahat bera olmaydi
     * ("savol beringiz" degan gap matematika va adabiyot uchun boshqa-boshqa
     * narsani bildiradi), sof fanga-xos instrument esa maktab bo'ylab
     * taqqoslanmaydi. Ishlagan yechim — INTEGRATSIYA:
     *
     *   1) 23 UNIVERSAL kategoriya — hamma darsda bir xil, jami 100 ball.
     *      Maktab darajasida taqqoslash va reyting shunga tayanadi.
     *   2) FAN MODULI — fan oilasiga qarab 3-4 qo'shimcha kategoriya
     *      (`vazn = 0`, ya'ni 100 ballga KIRMAYDI). O'qituvchi uchun eng
     *      qimmatli qism aynan shu, lekin u reytingni buzmaydi.
     *   3) VAZN PROFILI — universal 100 ball ichida fanga va DARS TURIGA
     *      qarab qayta taqsimlanadi (`vaznlar()`).
     *
     * MANBALAR (har kategoriya yonida qavs ichida):
     *   GTT  — Great Teaching Toolkit Evidence Review (Coe va h., 2020,
     *          Cambridge + Durham): 100 dan ortiq ramkani bitta modelga
     *          jamlagan, 4 o'lcham / 17 element. Asosiy skelet shundan.
     *   CLASS — Pianta, Virginia universiteti (emotsional qatlam).
     *   ICALT — xalqaro taqqoslash uchun tasdiqlangan kuzatuv vositasi.
     *   TEACH — Jahon banki (kuzatiladigan xulq-atvor sifatida yozish usuli).
     *   MQI  — Mathematical Quality of Instruction (Michigan + Harvard).
     *   PLATO — Protocol for Language Arts Teaching Observation (Stanford).
     *   TRU  — Teaching for Robust Understanding (Schoenfeld, Berkeley).
     *
     * `min_n` — SHU KATEGORIYAGA ISHONISH UCHUN KERAK DARSLAR SONI.
     * Praetorius va h. (2014) generalizability tahlili: sinf boshqaruvi va
     * qo'llab-quvvatlash bitta darsda .70 ishonchlilikka yetadi, KOGNITIV
     * FAOLLASHTIRISH esa darsdan darsga shunchalik tebranadi ki, barqaror
     * baho uchun ~9 dars kerak. Shuning uchun `min_n = 9` bo'lgan kategoriya
     * bitta dars sahifasida HECH QACHON kuchli/kuchsiz tomon sifatida
     * ko'rsatilmaydi — faqat chorak agregatida.
     *
     * `hisob` — ball MODELDAN olinmaydi, `metrics` raqamlaridan BIZNING
     * kodimizda hisoblanadi (`indikator_ball()`). Sababi: LLM'dan to'g'ridan
     * to'g'ri abstrakt CLASS bali so'ralganda inson koderlari bilan
     * korrelyatsiya r = .04 chiqqan (arXiv 2310.01132) — ya'ni nol. Shuning
     * uchun o'lchovli narsani modeldan SANAB berishni so'raymiz, ballni
     * o'zimiz chiqaramiz: takrorlanuvchi va tushuntiriladigan bo'ladi.
     *
     * `teskari` — kam bo'lgani yaxshi (salbiy holat, matematik xato).
     * `talab` — AI promptiga shu kategoriya uchun tushadigan ko'rsatma.
     *           Prompt SHU JOYDAN quriladi: kategoriya va u haqidagi
     *           ko'rsatma hech qachon bir-biridan ajralib ketmaydi.
     * `eski` — iste'moldan chiqqan kod. Promptga tushmaydi va 100 ballga
     *          kirmaydi, lekin eski tahlillar nomsiz qolmasligi uchun
     *          jadvalda saqlanadi.
     */
    const CATEGORIES = [

        // ================= A. DARS TASHKILI — 18 ball (GTT 3-o'lcham)
        'time_on_task' => [
            'nom' => "Vaqtdan samarali foydalanish", 'vazn' => 7, 'norm' => 70,
            'guruh' => 'organization', 'min_n' => 1, 'hisob' => true,
            'talab' => "darsning necha foizi haqiqiy o'quv ishiga ketdi: boshlanish "
                . "kechikishi, o'tishlardagi bo'sh vaqt, tashkiliy gaplar (GTT 3.1)",
        ],
        'management' => [
            'nom' => "Xulq-atvorni boshqarish", 'vazn' => 6, 'norm' => 67,
            'guruh' => 'organization', 'min_n' => 1,
            'talab' => "tartib qanday ushlanadi: ALDINI OLISH va rutina orqalimi "
                . "(yuqori ball) yoki jazo va ovoz ko'tarish orqalimi (past ball); "
                . "ko'rsatmalar aniqmi (GTT 3.2-3.3)",
        ],
        'structuring' => [
            'nom' => "Dars strukturasi", 'vazn' => 5, 'norm' => 60,
            'guruh' => 'organization', 'min_n' => 2,
            'talab' => "dars maqsadi aytildimi, oldingi bilim faollashtirildimi, "
                . "bosqichlar ketma-ketligi mantiqlimi, oxirida xulosa bo'ldimi (GTT 4.1)",
        ],

        // ================= B. MAZMUN VA TUSHUNTIRISH — 24 ball (GTT 4.2)
        'clarity' => [
            'nom' => "Tushuntirish aniqligi", 'vazn' => 10, 'norm' => 65,
            'guruh' => 'content', 'min_n' => 2,
            'talab' => "bosqichma-bosqich tushuntirish, MISOL va QARSHI-MISOL, "
                . "yangi g'oyani oldingi bilimga ulash, namuna yechim ko'rsatish, "
                . "sodda til; chalkash yoki o'zini tuzatish epizodlari (GTT 4.2)",
        ],
        'topic_match' => [
            'nom' => "Mavzuga muvofiqlik", 'vazn' => 7, 'norm' => 80,
            'guruh' => 'content', 'min_n' => 1,
            'talab' => "jurnalga yozilgan mavzu darsda qanchalik yoritildi; "
                . "`actual_topic` ga darsda ASLIDA nima o'tilganini yoz",
        ],
        'language_modeling' => [
            'nom' => "Nutq namunasi va til boyligi", 'vazn' => 4, 'norm' => 41,
            'guruh' => 'content', 'min_n' => 2,
            'talab' => "o'qituvchi nutqi aniq va boymi, atamalar to'g'ri "
                . "ishlatiladimi, yangi so'z izohlanadimi, o'quvchi javobini "
                . "to'liq gapga kengaytiradimi (CLASS)",
        ],
        'relevance' => [
            'nom' => "Hayot bilan bog'liqlik", 'vazn' => 3, 'norm' => 45,
            'guruh' => 'content', 'min_n' => 3,
            'talab' => "mavzu real hayot yoki o'quvchiga tanish holat bilan "
                . "bog'landimi, \"bu bizga nega kerak\" savoliga javob berildimi "
                . "(GTT: authentic contexts, effekt .160; DTS talabi)",
        ],

        // ================= C. FIKRLASHNI FAOLLASHTIRISH — 26 ball (GTT 4-o'lcham)
        'questioning' => [
            'nom' => "Savol berish sifati", 'vazn' => 8, 'norm' => 35,
            'guruh' => 'thinking', 'min_n' => 4, 'hisob' => true,
            'talab' => "savollarni SANA: yopiq (bir so'zli javob) va ochiq "
                . "(fikrlash talab qiladigan) nisbati; \"nega / qanday / taqqosla "
                . "/ isbotla\" turidagi savollar soni; savol berib KUTISH VAQTI; "
                . "savollar nechta TURLI o'quvchiga yo'naltirildi (GTT 4.3)",
        ],
        'activating' => [
            'nom' => "O'quvchilar faolligi", 'vazn' => 5, 'norm' => 50,
            'guruh' => 'thinking', 'min_n' => 2,
            'talab' => "o'quvchi O'Z TASHABBUSI bilan savol berdimi yoki fikr "
                . "aytdimi, juftlik/guruh ishi bo'ldimi, mustaqil topshiriq "
                . "berildimi (ICALT)",
        ],
        'participation' => [
            'nom' => "Qatnashuv taqsimoti", 'vazn' => 4, 'norm' => 55,
            'guruh' => 'thinking', 'min_n' => 1, 'hisob' => true,
            'talab' => "nutq ulushi (o'qituvchi/o'quvchilar), nechta TURLI o'quvchi "
                . "gapirdi, o'quvchi gaplarining qanchasi eng faol 3 o'quvchiga "
                . "to'g'ri keldi. Bu sinfda kim OVOZGA EGA ekanini o'lchaydi "
                . "(TRU: equitable access; TEACH)",
        ],
        'embedding' => [
            'nom' => "Mashq va takrorlash", 'vazn' => 4, 'norm' => 55,
            'guruh' => 'thinking', 'min_n' => 3,
            'talab' => "mustaqil mashq uchun qancha vaqt berildi, uy vazifa "
                . "tekshirildimi, OLDIN o'tilgan mavzuga qaytildimi (GTT 4.5: "
                . "unutishga qarshi takroriy qaytish)",
        ],
        'concept_development' => [
            'nom' => "Tushunchani chuqurlashtirish", 'vazn' => 2, 'norm' => 30,
            'guruh' => 'thinking', 'min_n' => 9,
            'talab' => "sabab-oqibat zanjirlari, umumlashtirish, tushunchalar "
                . "o'rtasidagi bog'liqlik — YODLASH emas, TUSHUNISH (CLASS)",
        ],
        'strategies' => [
            'nom' => "O'quv strategiyalarini o'rgatish", 'vazn' => 2, 'norm' => 40,
            'guruh' => 'thinking', 'min_n' => 9,
            'talab' => "qanday o'rganishni o'rgatish: mulohazani ovoz chiqarib "
                . "modellashtirish, o'z-o'zini tekshirish, rejalashtirish "
                . "(GTT 4.6; metakognitiv effekt .244 — ro'yxatda eng yuqori)",
        ],
        'differentiation' => [
            'nom' => "Tabaqalashtirilgan yondashuv", 'vazn' => 1, 'norm' => 35,
            'guruh' => 'thinking', 'min_n' => 9,
            'talab' => "turli darajadagi o'quvchiga moslangan topshiriq, alohida "
                . "yordam epizodlari (ICALT). AUDIO ORQALI KAM KO'RINADI — dalil "
                . "bo'lmasa `low`",
        ],

        // ================= D. MUHIT VA MUNOSABAT — 20 ball (GTT 2-o'lcham + CLASS)
        'negative_climate' => [
            'nom' => "Salbiy holatlarning yo'qligi", 'vazn' => 5, 'norm' => 85,
            'guruh' => 'climate', 'min_n' => 1, 'hisob' => true, 'teskari' => true,
            'talab' => "TESKARI o'lchov: baqirish, kinoya, kamsitish, qo'rqitish, "
                . "masxara epizodlarini SANA. Faqat ovozda ANIQ eshitilgani "
                . "sanaladi, taxmin qilinmaydi (CLASS)",
        ],
        'positive_climate' => [
            'nom' => "Ijobiy muhit", 'vazn' => 4, 'norm' => 70,
            'guruh' => 'climate', 'min_n' => 1,
            'talab' => "iliqlik, ism bilan murojaat, rag'bat, o'rinli hazil, "
                . "o'quvchilar o'rtasidagi do'stona ohang (CLASS)",
        ],
        'student_attitude' => [
            'nom' => "O'quvchilarning o'qituvchiga munosabati", 'vazn' => 4, 'norm' => 65,
            'guruh' => 'climate', 'min_n' => 1,
            'talab' => "SINFNING o'qituvchiga munosabati (o'qituvchining xatti-"
                . "harakati EMAS): o'z xohishi bilan savol berishadimi, ko'rsatmaga "
                . "darhol javob beradimi, murojaat ohangi hurmatlimi; e'tiborsizlik, "
                . "norozilik, chetdan gaplar, kinoya bormi (GTT 2.1: hurmat "
                . "QAYTARILISHI kerak)",
        ],
        'teacher_sensitivity' => [
            'nom' => "O'qituvchining sezgirligi", 'vazn' => 3, 'norm' => 65,
            'guruh' => 'climate', 'min_n' => 2,
            'talab' => "qiynalayotgan o'quvchini payqadimi, savolga javob berdimi, "
                . "tushunmaganini sezib QAYTA tushuntirdimi (CLASS)",
        ],
        'high_expectations' => [
            'nom' => "Yuqori talab muhiti", 'vazn' => 2, 'norm' => 50,
            'guruh' => 'climate', 'min_n' => 2,
            'talab' => "qiyin savolni tashlab qo'ymaslik, o'quvchidan to'liq javob "
                . "talab qilish, \"sen qila olasan\" signallari; PASHT talab "
                . "signallari (\"bu sizga qiyin\", javobni o'zi aytib qo'yish) "
                . "ballni tushiradi (GTT 2.4)",
        ],
        'error_climate' => [
            'nom' => "Xatoga munosabat", 'vazn' => 2, 'norm' => 45,
            'guruh' => 'climate', 'min_n' => 3,
            'talab' => "o'quvchi xato qilganda nima bo'ldi: xato TAHLIL qilindimi "
                . "va o'qish imkoniyatiga aylandimi, yoki o'quvchi uyaltirildimi "
                . "/ javob shunchaki boshqaga o'tdimi (GTT 2.4)",
        ],
        'student_perspective' => [
            'nom' => "O'quvchi fikriga e'tibor", 'vazn' => 0, 'norm' => 45,
            'guruh' => 'climate', 'min_n' => 9, 'eski' => true,
            'talab' => '',
        ],

        // ================= E. NATIJA — 12 ball
        'feedback_quality' => [
            'nom' => "Fikr-mulohaza sifati", 'vazn' => 5, 'norm' => 32,
            'guruh' => 'outcome', 'min_n' => 4,
            'talab' => "\"to'g'ri/noto'g'ri\" dan TASHQARI nima berildi: nega to'g'ri, "
                . "qayerda xato, qanday tuzatish kerak, keyingi qadam nima (GTT 4.4)",
        ],
        'student_understanding' => [
            'nom' => "O'quvchilar o'zlashtirish darajasi", 'vazn' => 4, 'norm' => 60,
            'guruh' => 'outcome', 'min_n' => 2,
            'talab' => "o'quvchilar javoblari to'g'ri va chuqurmi yoki yuzakimi, "
                . "tushunmovchilik va xato tasavvurlar qoldimi. Bu o'qituvchini "
                . "emas, SINFNING natijasini baholaydi",
        ],
        'learning_evidence' => [
            'nom' => "O'quvchi nima o'rgandi", 'vazn' => 3, 'norm' => 50,
            'guruh' => 'outcome', 'min_n' => 2,
            'talab' => "DARS NATIJASI: o'quvchi dars oxirida O'Z SO'ZI bilan nimani "
                . "aytib bera oldi, qaysi yangi atama yoki ko'nikma aniq egallandi, "
                . "dars maqsadi bajarildimi. `learned` maydoniga o'quvchilar "
                . "egallagan narsalarni ro'yxat qilib yoz. Transkriptda DALIL "
                . "bo'lmasa — past ball, taxmin EMAS",
        ],

        // ================= FAN MODULLARI (vazn = 0, 100 ballga kirmaydi)

        // --- Aniq fanlar: matematika, algebra, geometriya, fizika, kimyo (MQI)
        'math_precision' => [
            'nom' => "Matematik til va yozuvning aniqligi", 'vazn' => 0, 'norm' => 70,
            'guruh' => 'subject', 'min_n' => 2, 'teskari' => true, 'fan' => ['aniq'],
            'talab' => "TESKARI: o'qituvchining noto'g'ri atamasi, noaniq ta'rifi, "
                . "hisob xatosi, \"shunchaki ko'chiramiz\" tipidagi noaniqlik "
                . "epizodlarini sana (MQI: Errors and Imprecision)",
        ],
        'procedure_meaning' => [
            'nom' => "Algoritm ortidagi ma'no", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['aniq'],
            'talab' => "\"shunchaki shunday qiling\" bilan \"nega shunday ishlaydi\" "
                . "nisbati: qoida/formulaning ma'nosi ochildimi (MQI: Explanations)",
        ],
        'reasoning' => [
            'nom' => "Asoslash va isbot talab qilish", 'vazn' => 0, 'norm' => 25,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['aniq'],
            'talab' => "\"nega shunday?\", \"qanday bildingiz?\" savollari va "
                . "o'quvchi O'ZI asoslab bergan epizodlar (MQI: Student Reasoning)",
        ],
        'representations' => [
            'nom' => "Bir g'oyani bir necha ko'rinishda", 'vazn' => 0, 'norm' => 35,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['aniq'],
            'talab' => "formula <-> grafik <-> so'z <-> chizma <-> jadval o'tishlari "
                . "(MQI: Linking; GTT da representation formats effekti .385 — "
                . "ro'yxatdagi eng yuqori)",
        ],

        // --- Til darslari: ona tili, ingliz, rus va boshqa chet tillari (PLATO)
        'target_language_use' => [
            'nom' => "O'rganilayotgan tilda gapirish ulushi", 'vazn' => 0, 'norm' => 60,
            'guruh' => 'subject', 'min_n' => 1, 'fan' => ['til'],
            'talab' => "dars QAYSI tilda o'tdi: o'rganilayotgan tildagi nutq ulushi "
                . "va boshqa tilga o'tish (code-switching) epizodlari. CHET TILI "
                . "darsi uchun hal qiluvchi ko'rsatkich",
        ],
        'student_talk_length' => [
            'nom' => "O'quvchi nutqining uzunligi", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 2, 'fan' => ['til'],
            'talab' => "o'quvchi javoblari BIR SO'ZLImi yoki to'liq gap/abzatsmi; "
                . "bir so'zli javoblar ulushini yoz (PLATO: classroom discourse)",
        ],
        'text_based' => [
            'nom' => "Matnga tayangan muhokama", 'vazn' => 0, 'norm' => 40,
            'guruh' => 'subject', 'min_n' => 3, 'fan' => ['til'],
            'talab' => "matndan dalil keltirish, sitata bilan asoslash, matn ustida "
                . "ishlash (PLATO: Text-Based Instruction)",
        ],
        'strategy_modeling' => [
            'nom' => "O'qish/yozish strategiyasini modellashtirish", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['til'],
            'talab' => "o'qituvchi o'z fikrlashini ovoz chiqarib ko'rsatdimi "
                . "(\"men bu gapni o'qiyotganda shuni o'ylayman...\") "
                . "(PLATO: Strategy Use and Instruction, Modeling)",
        ],

        // --- Gumanitar: tarix, adabiyot, huquq, geografiya, jamiyat
        'evidence_argument' => [
            'nom' => "Manba va dalil bilan asoslash", 'vazn' => 0, 'norm' => 35,
            'guruh' => 'subject', 'min_n' => 3, 'fan' => ['gumanitar'],
            'talab' => "\"qaysi manbada?\", \"nimaga asoslanib aytasan?\" savollari "
                . "va o'quvchi dalil keltirgan epizodlar",
        ],
        'multiple_perspectives' => [
            'nom' => "Bir necha nuqtai nazar", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['gumanitar'],
            'talab' => "bir hodisa/qahramonga turli qarashlar taqqoslandimi, "
                . "qarama-qarshi fikr muhokama qilindimi",
        ],
        'discussion_depth' => [
            'nom' => "Muhokama chuqurligi", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 3, 'fan' => ['gumanitar'],
            'talab' => "o'quvchi-o'quvchi dialogi: bir-birining fikriga javob "
                . "berish, rozilik/e'tiroz bildirish (faqat o'qituvchi-o'quvchi "
                . "ketma-ketligi emas)",
        ],
        'value_discussion' => [
            'nom' => "Qadriyat va axloqiy muhokama", 'vazn' => 0, 'norm' => 40,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['gumanitar'],
            'talab' => "qahramon tanlovi, tarixiy qaror yoki huquqiy holatning "
                . "axloqiy tomoni muhokama qilindimi",
        ],

        // --- Tabiiy fanlar: biologiya, tabiatshunoslik, ekologiya, astronomiya
        'inquiry' => [
            'nom' => "Kuzatish, gipoteza, tajriba", 'vazn' => 0, 'norm' => 25,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['tabiiy'],
            'talab' => "\"nima bo'lishini taxmin qil\", tajriba/kuzatish bosqichlari, "
                . "natijadan xulosa chiqarish (ilmiy izlanish qatlami)",
        ],
        'misconception_check' => [
            'nom' => "Xato tasavvurni ochish", 'vazn' => 0, 'norm' => 30,
            'guruh' => 'subject', 'min_n' => 4, 'fan' => ['tabiiy'],
            'talab' => "fandagi TIPIK noto'g'ri tasavvur ataylab yuzaga chiqarilib "
                . "tuzatildimi (GTT 1.4: sticking points)",
        ],
        'everyday_science' => [
            'nom' => "Kundalik hodisa bilan bog'lash", 'vazn' => 0, 'norm' => 50,
            'guruh' => 'subject', 'min_n' => 3, 'fan' => ['tabiiy'],
            'talab' => "tanish kundalik hodisadan ilmiy tushunchaga o'tish misollari",
        ],

        // --- Boshlang'ich sinf (1-4): CLASS K-3
        'routines' => [
            'nom' => "Rutinalar va aniq ko'rsatmalar", 'vazn' => 0, 'norm' => 70,
            'guruh' => 'subject', 'min_n' => 1, 'fan' => ['boshlangich'],
            'talab' => "ko'rsatma bir marta tushunarli berildimi yoki qayta-qayta "
                . "so'raldimi; o'tishlarda chalkashlik bo'ldimi",
        ],
        'vocabulary_building' => [
            'nom' => "Lug'at boyitish", 'vazn' => 0, 'norm' => 45,
            'guruh' => 'subject', 'min_n' => 2, 'fan' => ['boshlangich'],
            'talab' => "yangi so'z kiritildi + izohlandi + darsda QAYTA ishlatildimi",
        ],
        'play_engagement' => [
            'nom' => "O'yin va harakat orqali jalb qilish", 'vazn' => 0, 'norm' => 55,
            'guruh' => 'subject', 'min_n' => 2, 'fan' => ['boshlangich'],
            'talab' => "qo'shiq, o'yin, harakatli topshiriq epizodlari (yosh "
                . "xususiyatiga mos faollik)",
        ],

        // --- Amaliy fanlar: jismoniy tarbiya, mehnat, san'at, musiqa
        'demonstration' => [
            'nom' => "Namoyish va texnika tuzatish", 'vazn' => 0, 'norm' => 60,
            'guruh' => 'subject', 'min_n' => 2, 'fan' => ['amaliy'],
            'talab' => "harakat/usul ko'rsatib berildimi, individual tuzatish "
                . "epizodlari bo'ldimi",
        ],
        'practice_time' => [
            'nom' => "Amaliy mashq vaqti ulushi", 'vazn' => 0, 'norm' => 60,
            'guruh' => 'subject', 'min_n' => 1, 'fan' => ['amaliy'],
            'talab' => "gapirish va AMALIY BAJARISH nisbati: o'quvchilar qancha "
                . "vaqt haqiqatda bajardi",
        ],
        'safety' => [
            'nom' => "Xavfsizlik qoidalari", 'vazn' => 0, 'norm' => 70,
            'guruh' => 'subject', 'min_n' => 1, 'fan' => ['amaliy'],
            'talab' => "xavfsizlik ko'rsatmalari berildimi (asbob, jihoz, "
                . "jismoniy mashq oldidan)",
        ],
    ];

    /**
     * Fan oilalari — fan nomidan aniqlanadi.
     *
     * TARTIB MUHIM: ro'yxat yuqoridan pastga tekshiriladi. `amaliy` `gumanitar`
     * dan OLDIN turadi, aks holda "jismoniy tarbiya" dagi "tarbiya" so'zi uni
     * gumanitarga tortib ketardi.
     *
     * Kalit so'zlar uch tilda: Moodle kurs nomi o'zbek, rus yoki ingliz
     * tilida kelishi mumkin.
     */
    const FAN_OILALARI = [
        'amaliy' => ['jismoniy', 'sport', 'mehnat', 'texnologiya', "san'at", 'sanat',
            'musiqa', 'rasm', 'chizmachilik', 'физкультур', 'физическая', 'труд',
            'музык', 'искусств', 'рисован', 'technolog', 'physical', 'music', 'art',
            'craft', 'pe '],
        'aniq' => ['matematika', 'algebra', 'geometriya', 'fizika', 'kimyo',
            'informatika', 'dasturlash', 'математик', 'алгебр', 'геометр', 'физик',
            'хими', 'информатик', 'math', 'algebra', 'geometry', 'physics',
            'chemistry', 'computer', 'coding', 'ict', 'robot', 'muhandislik',
            'engineering', 'stem', 'робот'],
        'til' => ['ona tili', "o'zbek tili", 'uzbek tili', 'ingliz', 'rus tili',
            'nemis', 'fransuz', 'arab tili', 'koreys', 'xitoy', 'til va adabiyot',
            'узбекский язык', 'русский язык', 'английск', 'немецк', 'французск',
            'english', 'language', 'esl', 'efl', 'literacy', 'reading', 'writing'],
        'tabiiy' => ['biologiya', 'tabiatshunoslik', 'ekologiya', 'astronomiya',
            'botanika', 'zoologiya', 'anatomiya', 'биолог', 'природовед',
            'эколог', 'астроном', 'biology', 'science', 'natural'],
        'gumanitar' => ['tarix', 'adabiyot', 'huquq', 'geografiya', 'jamiyat',
            'iqtisod', 'falsafa', 'psixologiya', 'din', 'tarbiya', 'истори',
            'литератур', 'прав', 'географ', 'общество', 'эконом', 'философ',
            'history', 'literature', 'geography', 'economics', 'social', 'civic',
            'law', 'philosophy'],
    ];

    /**
     * VAZN PROFILI — fan oilasiga qarab universal 100 ball ichida qayta
     * taqsimlash koeffitsientlari.
     *
     * NEGA KERAK: aniq fanlarga qo'yilgan talab gumanitar fanlarga
     * to'g'ri kelmaydi. Til darsida o'qituvchi 80% gapirsa o'quvchi tilni
     * O'RGANMAYDI — demak `participation` og'irroq. Gumanitarda "to'g'ri
     * javob" yo'q, dars faqat savol bilan yashaydi — demak `questioning`
     * og'irroq. Matematikada esa qo'rquv aynan xato jazolanganda tug'iladi —
     * demak `error_climate` og'irroq.
     *
     * Ro'yxatda yo'q kod uchun koeffitsient 1.0.
     */
    const PROFIL = [
        'aniq' => [
            'clarity' => 1.4, 'questioning' => 1.2, 'participation' => 0.9,
            'embedding' => 1.5, 'language_modeling' => 0.8, 'relevance' => 1.2,
            'error_climate' => 1.6, 'high_expectations' => 1.4,
            'positive_climate' => 0.9, 'negative_climate' => 0.9,
            'student_attitude' => 0.9, 'teacher_sensitivity' => 0.9,
        ],
        'til' => [
            'clarity' => 0.9, 'questioning' => 1.1, 'participation' => 1.6,
            'embedding' => 1.2, 'language_modeling' => 1.8, 'relevance' => 1.0,
            'error_climate' => 1.3, 'high_expectations' => 1.1,
            'positive_climate' => 1.1, 'negative_climate' => 1.1,
            'student_attitude' => 1.1, 'teacher_sensitivity' => 1.1,
        ],
        'gumanitar' => [
            'clarity' => 1.0, 'questioning' => 1.5, 'participation' => 1.4,
            'embedding' => 0.7, 'language_modeling' => 1.3, 'relevance' => 1.2,
            'error_climate' => 0.9, 'high_expectations' => 1.0,
            'positive_climate' => 1.1, 'negative_climate' => 1.1,
            'student_attitude' => 1.1, 'teacher_sensitivity' => 1.1,
            'time_on_task' => 0.9,
        ],
        'tabiiy' => [
            'clarity' => 1.2, 'questioning' => 1.3, 'participation' => 1.0,
            'embedding' => 0.9, 'language_modeling' => 0.9, 'relevance' => 1.5,
            'error_climate' => 1.0, 'high_expectations' => 1.1,
        ],
        'boshlangich' => [
            'clarity' => 1.1, 'questioning' => 1.0, 'participation' => 1.1,
            'embedding' => 1.3, 'language_modeling' => 1.5, 'relevance' => 1.1,
            'error_climate' => 1.4, 'high_expectations' => 0.9,
            'time_on_task' => 1.3,
            'positive_climate' => 1.5, 'negative_climate' => 1.5,
            'student_attitude' => 1.5, 'teacher_sensitivity' => 1.5,
            'concept_development' => 0.7, 'strategies' => 0.7,
        ],
        'amaliy' => [
            'clarity' => 0.8, 'questioning' => 0.7, 'participation' => 1.0,
            'embedding' => 1.4, 'language_modeling' => 0.6, 'relevance' => 1.0,
            'error_climate' => 1.1, 'time_on_task' => 1.3,
            'positive_climate' => 1.2, 'negative_climate' => 1.2,
            'student_attitude' => 1.2, 'teacher_sensitivity' => 1.2,
            'concept_development' => 0.6, 'strategies' => 0.8,
        ],
        'umumiy' => [],
    ];

    /**
     * DARS TURLARI — har turga alohida yondashuv.
     *
     * NEGA KERAK: nazorat ishida o'qituvchi tushuntirmaydi va savol bermaydi —
     * shu darsni `clarity` yoki `questioning` bo'yicha baholash o'qituvchini
     * HAQSIZ jazolaydi. Takrorlash darsida yangi tushuncha kiritilmaydi, demak
     * `concept_development` kutilmaydi.
     *
     *   `kutilmaydi` — bu turdagi darsda kategoriya BAHOLANMAYDI (majburan
     *                  `confidence = low`, ya'ni 100 ballga kirmaydi).
     *   `vazn`       — shu turda kuchaytiriladigan/pasaytiriladigan kodlar.
     *
     * Dars turini MODEL o'zi aniqlaydi (`lesson_type`) — jadvalda bunday
     * maydon yo'q va o'qituvchidan qo'shimcha ish talab qilmaymiz.
     */
    const DARS_TURLARI = [
        'yangi' => [
            'nom' => "Yangi mavzu",
            'kutilmaydi' => [],
            'vazn' => ['clarity' => 1.3, 'structuring' => 1.2,
                'concept_development' => 1.3, 'embedding' => 0.7],
        ],
        'mustahkamlash' => [
            'nom' => "Mustahkamlash / mashq",
            'kutilmaydi' => ['concept_development'],
            'vazn' => ['embedding' => 1.6, 'feedback_quality' => 1.3,
                'clarity' => 0.9, 'differentiation' => 1.3],
        ],
        'takrorlash' => [
            'nom' => "Takrorlash",
            'kutilmaydi' => ['concept_development'],
            'vazn' => ['embedding' => 1.5, 'questioning' => 1.3, 'clarity' => 0.8],
        ],
        'nazorat' => [
            'nom' => "Nazorat / test",
            'kutilmaydi' => ['clarity', 'questioning', 'activating', 'participation',
                'concept_development', 'strategies', 'differentiation', 'embedding',
                'relevance', 'language_modeling', 'feedback_quality',
                'learning_evidence'],
            'vazn' => ['management' => 1.5, 'time_on_task' => 1.5,
                'structuring' => 1.3, 'negative_climate' => 1.3,
                'error_climate' => 1.2],
        ],
        'amaliy' => [
            'nom' => "Amaliy / laboratoriya",
            'kutilmaydi' => [],
            'vazn' => ['participation' => 1.3, 'embedding' => 1.4,
                'clarity' => 0.9, 'language_modeling' => 0.8],
        ],
        'muhokama' => [
            'nom' => "Muhokama / seminar",
            'kutilmaydi' => [],
            'vazn' => ['questioning' => 1.5, 'participation' => 1.5,
                'activating' => 1.3, 'clarity' => 0.7],
        ],
    ];

    /** Dars turi kodini tozalaydi; noma'lum qiymat `yangi` ga tushadi. */
    public static function dars_turi(?string $tur): string {
        $t = strtolower(trim((string) $tur));
        return isset(self::DARS_TURLARI[$t]) ? $t : 'yangi';
    }

    /** Dars turining foydalanuvchi tilidagi nomi. */
    public static function tur_nomi(string $tur): string {
        $tur = self::dars_turi($tur);
        $kalit = 'ai_tur_' . $tur;
        if (get_string_manager()->string_exists($kalit, 'local_targetzenit')) {
            return get_string($kalit, 'local_targetzenit');
        }
        return self::DARS_TURLARI[$tur]['nom'];
    }

    /** Guruh (sinf) nomidan sinf raqami: "7-A" -> 7, "11-B" -> 11. */
    public static function sinf_raqami(string $guruh): ?int {
        return preg_match('/(\d{1,2})/', $guruh, $m) ? (int) $m[1] : null;
    }

    /**
     * Fan oilasi: fan nomi va sinfga qarab.
     *
     * 1-4 sinf HAMMA fanda `boshlangich` bo'ladi — bu yoshda yondashuv
     * fandan ko'ra yoshga ko'proq bog'liq (CLASS K-3 shu mantiqda qurilgan).
     */
    public static function fan_oila(string $fan, string $guruh = ''): string {
        $sinf = $guruh !== '' ? self::sinf_raqami($guruh) : null;
        if ($sinf !== null && $sinf >= 1 && $sinf <= 4) {
            return 'boshlangich';
        }

        $n = \core_text::strtolower(' ' . trim($fan) . ' ');
        foreach (self::FAN_OILALARI as $oila => $kalitlar) {
            foreach ($kalitlar as $k) {
                if (strpos($n, $k) !== false) {
                    return $oila;
                }
            }
        }
        return 'umumiy';
    }

    /**
     * Shu darsda baholanadigan kodlar: universal o'zak + fan moduli.
     *
     * `eski` kodlar va boshqa fan oilasining modullari tushib qoladi.
     */
    public static function kodlar(string $oila = 'umumiy'): array {
        $out = [];
        foreach (self::CATEGORIES as $kod => $k) {
            if (!empty($k['eski'])) {
                continue;
            }
            if (isset($k['fan']) && !in_array($oila, $k['fan'], true)) {
                continue;
            }
            $out[] = $kod;
        }
        return $out;
    }

    /**
     * EFFEKTIV VAZNLAR: bazaviy vazn x fan profili x dars turi profili,
     * keyin 100 ga qayta normallash.
     *
     * Fan modullari (`vazn = 0`) bu yerga tushmaydi — ular reytingga ta'sir
     * qilmaydi, faqat o'qituvchiga ko'rsatiladi.
     *
     * @return array [kod => vazn (int, yig'indisi ~100)]
     */
    public static function vaznlar(string $oila = 'umumiy', string $tur = ''): array {
        $fan_profil = self::PROFIL[$oila] ?? [];
        $tur = $tur !== '' ? self::dars_turi($tur) : '';
        $tur_profil = $tur !== '' ? (self::DARS_TURLARI[$tur]['vazn'] ?? []) : [];
        $kutilmaydi = $tur !== '' ? (self::DARS_TURLARI[$tur]['kutilmaydi'] ?? []) : [];

        $xom = [];
        foreach (self::CATEGORIES as $kod => $k) {
            if ($k['vazn'] <= 0 || !empty($k['eski'])) {
                continue;   // fan moduli va eski kodlar 100 ballga kirmaydi
            }
            if (in_array($kod, $kutilmaydi, true)) {
                continue;   // bu turdagi darsda baholanmaydi
            }
            // Ikki koeffitsient KO'PAYTIRILADI, lekin ko'paytma cheklanadi:
            // 1.4 (fan) x 1.3 (dars turi) = 1.82 bo'lib, bitta mezon 18% ga
            // chiqib ketardi. Hech bir mezon umumiy ballni yolg'iz
            // belgilamasligi kerak — 23 kategoriyaning ma'nosi shunda.
            $kf = ($fan_profil[$kod] ?? 1.0) * ($tur_profil[$kod] ?? 1.0);
            $xom[$kod] = $k['vazn'] * max(0.5, min(1.6, $kf));
        }

        $jami = array_sum($xom);
        if ($jami <= 0) {
            return [];
        }

        // 100 ga normallash. Yaxlitlash qoldig'i eng katta vaznga qo'shiladi,
        // shunda yig'indi aniq 100 bo'ladi va ekranda "99%" ko'rinmaydi.
        $out = [];
        foreach ($xom as $kod => $v) {
            $out[$kod] = (int) round($v / $jami * 100);
        }
        $farq = 100 - array_sum($out);
        if ($farq !== 0 && $out) {
            arsort($out);
            $eng = array_key_first($out);
            $out[$eng] += $farq;
        }
        return $out;
    }

    /** Kategoriyaga ishonish uchun kerak darslar soni (Praetorius 2014). */
    public static function min_n(string $kod): int {
        return (int) (self::CATEGORIES[$kod]['min_n'] ?? 1);
    }

    /**
     * Vaznli xalqaro o'rtacha — "odatdagi dars" necha foiz oladi.
     *
     * Argumentsiz chaqirilsa bazaviy vaznlar bilan hisoblaydi (eski
     * chaqiruvlar shuning uchun o'zgarmay ishlaydi).
     */
    public static function norm_overall(string $oila = 'umumiy', string $tur = ''): int {
        $vaznlar = self::vaznlar($oila, $tur);
        $y = 0;
        $v = 0;
        foreach ($vaznlar as $kod => $vazn) {
            $y += self::CATEGORIES[$kod]['norm'] * $vazn;
            $v += $vazn;
        }
        return $v ? (int) round($y / $v) : 0;
    }

    /**
     * INDIKATORDAN BALL: `hisob => true` bo'lgan kategoriyalar bali
     * modeldan OLINMAYDI, `metrics` raqamlaridan shu yerda hisoblanadi.
     *
     * NEGA: LLM'dan to'g'ridan-to'g'ri abstrakt ball so'ralganda inson
     * koderlari bilan korrelyatsiya r = .04 chiqqan (arXiv 2310.01132).
     * Raqam sanash esa modelga yaxshi topshiriq. Shunda ball
     * takrorlanuvchi bo'ladi va "nega menga shu ball qo'yildi" degan
     * savolga aniq javob bor.
     *
     * @return int|null null = metrics'da kerakli raqam yo'q (modelning
     *                  o'z bali ishlatiladi)
     */
    public static function indikator_ball(string $kod, array $m): ?int {
        $son = function ($kalit) use ($m) {
            return isset($m[$kalit]) && is_numeric($m[$kalit]) ? (float) $m[$kalit] : null;
        };
        $chegara = fn($v) => (int) max(0, min(100, round($v)));

        switch ($kod) {
            case 'time_on_task':
                // To'g'ridan-to'g'ri: darsning necha foizi o'quv ishida o'tdi
                $v = $son('on_task_pct');
                return $v === null ? null : $chegara($v);

            case 'participation':
                // Ikki narsa: nutq o'quvchilarga qanchalik berilgan va u
                // nechta boshga TAQSIMLANGAN. Faqat ulush yetarli emas —
                // 30% nutq bitta o'quvchida bo'lsa, sinf jim o'tirgan.
                $oquvchi = $son('student_talk_pct');
                $turli = $son('students_addressed');
                if ($oquvchi === null && $turli === null) {
                    return null;
                }
                // 30% o'quvchi nutqi = 100 ball (TeachFX: o'qituvchi odatda
                // 70-80% gapiradi, ya'ni 20-30% xalqaro amaliy maksimum)
                $a = $oquvchi === null ? null : min(100, $oquvchi / 30 * 100);
                // 12 turli o'quvchi = 100 ball
                $b = $turli === null ? null : min(100, $turli / 12 * 100);
                $qiymatlar = array_filter([$a, $b], fn($x) => $x !== null);
                return $chegara(array_sum($qiymatlar) / count($qiymatlar));

            case 'questioning':
                // Uch komponent: ochiq savol ulushi, kutish vaqti, qamrov
                $ochiq = $son('open_questions');
                $jami = $son('questions_total');
                $kutish = $son('wait_time_avg_sec');
                $turli = $son('students_addressed');
                $qism = [];
                if ($jami !== null && $jami > 0 && $ochiq !== null) {
                    // 40% ochiq savol = 100 ball (GTT normasi ~35%)
                    $qism[] = min(100, $ochiq / $jami / 0.40 * 100);
                }
                if ($kutish !== null) {
                    // 3 sekund = 100 ball (tadqiqotlardagi tavsiya)
                    $qism[] = min(100, $kutish / 3 * 100);
                }
                if ($turli !== null) {
                    $qism[] = min(100, $turli / 12 * 100);
                }
                return $qism ? $chegara(array_sum($qism) / count($qism)) : null;

            case 'negative_climate':
                // TESKARI: har epizod 20 ball oladi. 0 epizod = 100.
                $n = $son('negative_episodes');
                return $n === null ? null : $chegara(100 - $n * 20);
        }
        return null;
    }
    public static function enabled(): bool {
        return (bool) get_config('local_targetzenit', 'aienabled')
            && trim((string) get_config('local_targetzenit', 'geminikey')) !== '';
    }

    /**
     * Tahlil kutayotgan yozuvlar (audio bor, tahlil yo'q).
     *
     * Xato bergan yozuvga DARHOL qayta urinilmaydi — 1 soat kutiladi.
     * Aks holda doimiy xato (masalan fayl buzuq) har 15 daqiqada kvotani
     * behuda yeb ketardi.
     */
    public static function pending(int $limit): array {
        global $DB;
        return $DB->get_records_select('local_targetzenit_audio',
            "status = 'ready' AND (analysis IS NULL OR analysis = '') AND filesize > 0
             AND (aierror IS NULL OR aierror = '' OR timemodified < :chegara)",
            ['chegara' => time() - HOURSECS],
            'timecreated ASC', '*', 0, $limit);
    }

    /** Ishlatilgan model (so'rovdan keyin to'ldiriladi). */
    protected static $ishlatilgan = '';

    protected static function models(): array {
        $asosiy = trim((string) get_config('local_targetzenit', 'geminimodel')) ?: 'gemini-3.8-flash';
        $zaxira = trim((string) get_config('local_targetzenit', 'geminifallback'));
        return array_values(array_unique(array_filter([$asosiy, $zaxira])));
    }

    /** Model ro'yxati bo'yicha so'rov: biri yiqilsa keyingisi sinaladi. */
    protected static function so_rov(string $prompt, ?string $b64 = null,
                                     ?string $mime = null, bool $json = true) {
        $oxirgi = '';
        foreach (self::models() as $m) {
            try {
                $natija = self::call($m, $prompt, $b64, $mime, $json);
                self::$ishlatilgan = $m;
                return $natija;
            } catch (\Throwable $e) {
                $oxirgi = $e->getMessage();
                continue;
            }
        }
        throw new \RuntimeException($oxirgi ?: 'Model javob bermadi');
    }

    /**
     * Bitta yozuvni tahlil qiladi. IKKI BOSQICHDA:
     *   1) audio 5 daqiqalik bo'laklarga bo'linib, har biri alohida matnga o'giriladi
     *   2) to'liq matn (audiosiz) bo'yicha tahlil qilinadi
     *
     * NEGA SHUNDAY: 45 daqiqalik darsni bitta so'rovda so'rasak, modelning javob
     * uzunligi chegarasiga urilib, transkript O'RTASIDAN KESILADI — matn chala
     * qoladi va tahlil ham shunga yarasha noto'g'ri chiqadi. Bo'lak-bo'lak
     * so'rasak, har bir javob kichik bo'ladi va hech narsa tushib qolmaydi.
     */
    public static function analyse(\stdClass $audio): bool {
        global $DB;

        $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $audio->lessonid]);
        if (!$dars) {
            return self::fail($audio, 'Dars topilmadi');
        }
        $context = \context_course::instance($dars->courseid, IGNORE_MISSING);
        if (!$context) {
            return self::fail($audio, 'Kurs topilmadi');
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, audio::COMPONENT, audio::FILEAREA,
            $audio->lessonid, 'itemid', false);
        $file = $files ? reset($files) : null;
        if (!$file) {
            return self::fail($audio, 'Audio fayl topilmadi');
        }

        $mime = self::audio_mime($audio, $file);
        $course = $DB->get_record('course', ['id' => $dars->courseid], 'id, fullname, idnumber');

        // Faylni vaqtinchalik papkaga chiqaramiz (ffmpeg fayl bilan ishlaydi)
        $ish = make_temp_directory('local_targetzenit/ai/' . $audio->lessonid);
        $asl = $ish . '/asl.' . pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        $file->copy_content_to($asl);

        try {
            // ---------- 1-bosqich: bo'lak-bo'lak transkripsiya
            $bolaklar = self::split($asl, $ish);
            $daq = (int) (get_config('local_targetzenit', 'aisegmentminutes') ?: 5);

            $qismlar = [];
            foreach ($bolaklar as $i => $yol) {
                if (filesize($yol) > self::MAX_BYTES) {
                    continue;   // g'ayrioddiy katta bo'lak — o'tkazamiz
                }
                $boshlanish = $i * $daq * 60;
                $matn = self::so_rov(
                    self::prompt_transcribe($boshlanish, $daq),
                    base64_encode(file_get_contents($yol)),
                    $mime,
                    false
                );
                $belgi = sprintf('[%02d:%02d]', intdiv($boshlanish, 60), $boshlanish % 60);
                $qismlar[] = $belgi . "\n" . trim($matn);
            }

            if (!$qismlar) {
                return self::fail($audio, 'Transkript olinmadi');
            }
            $transkript = implode("\n\n", $qismlar);

            // ---------- 2-bosqich: to'liq matn bo'yicha tahlil (audiosiz)
            $javob = self::so_rov(self::prompt_analyse($dars, $course, $transkript));
            $javob['transcript'] = $transkript;

            // Fan oilasi: promptga ham, vazn profiliga ham shu kerak
            $oila = 'umumiy';
            if ($course) {
                $q = explode('::', (string) $course->idnumber);
                $oila = self::fan_oila($q[2] ?? $course->fullname, $q[1] ?? '');
            }
            return self::save($audio, $javob, self::$ishlatilgan, $oila);

        } catch (\Throwable $e) {
            return self::fail($audio, $e->getMessage());
        } finally {
            foreach (glob($ish . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($ish);
        }
    }

    /** ffmpeg yo'li (sozlamadan yoki tizimdan). */
    protected static function ffmpeg(): ?string {
        $yol = trim((string) get_config('local_targetzenit', 'ffmpegpath'));
        if ($yol && is_executable($yol)) {
            return $yol;
        }
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $n) {
            if (is_executable($n)) {
                return $n;
            }
        }
        return null;
    }

    /**
     * Audioni N daqiqalik bo'laklarga ajratadi.
     * ffmpeg bo'lmasa — bitta bo'lak qaytaradi (eski xatti-harakat).
     */
    protected static function split(string $asl, string $ish): array {
        $ffmpeg = self::ffmpeg();
        $daq = (int) (get_config('local_targetzenit', 'aisegmentminutes') ?: 5);

        if (!$ffmpeg || $daq < 1) {
            return [$asl];
        }

        $ext = pathinfo($asl, PATHINFO_EXTENSION) ?: 'webm';
        $namuna = $ish . '/qism_%03d.' . $ext;

        // -c copy: qayta kodlash yo'q — tez va sifat yo'qolmaydi
        $cmd = escapeshellcmd($ffmpeg) . ' -hide_banner -loglevel error -y'
            . ' -i ' . escapeshellarg($asl)
            . ' -f segment -segment_time ' . ($daq * 60)
            . ' -c copy ' . escapeshellarg($namuna) . ' 2>&1';
        @exec($cmd, $chiqish, $kod);

        $bolaklar = glob($ish . '/qism_*.' . $ext) ?: [];
        sort($bolaklar, SORT_STRING);

        if ($kod !== 0 || !$bolaklar) {
            debugging('local_targetzenit: ffmpeg bo\'lolmadi — bitta bo\'lak bilan davom etamiz. '
                . implode(' ', (array) $chiqish), DEBUG_DEVELOPER);
            return [$asl];
        }
        return $bolaklar;
    }

    /**
     * Audio MIME turi.
     *
     * DIQQAT: `$file->get_mimetype()` ISHLATILMAYDI — Moodle `.webm` ni
     * **video/webm** deb belgilaydi (filetypes.php), biz esa uni `audio` turi
     * sifatida yuboramiz va Gemini "invalid argument" (HTTP 400) qaytaradi.
     * Shuning uchun brauzer bergan turni (audio::start da saqlangan) olamiz,
     * u bo'lmasa kengaytmadan aniqlaymiz.
     */
    protected static function audio_mime(\stdClass $audio, \stored_file $file): string {
        $ruxsat = [
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp3', 'audio/wav',
            'audio/aac', 'audio/flac', 'audio/m4a', 'audio/opus', 'audio/aiff',
        ];

        $m = strtolower(trim((string) ($audio->mimetype ?? '')));
        if (in_array($m, $ruxsat, true)) {
            return $m;
        }

        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $xarita = [
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg',
            'm4a' => 'audio/m4a',
            'mp4' => 'audio/m4a',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
        ];
        return $xarita[$ext] ?? 'audio/webm';
    }

    /**
     * Transkripsiya so'rovi — FAQAT matn, tahlil yo'q.
     *
     * Javob kichik bo'lgani uchun model uni oxirigacha yozadi; bitta uzun
     * so'rovda esa javob chegarasiga urilib, matn o'rtasidan kesilardi.
     */
    protected static function prompt_transcribe(int $boshlanish, int $daqiqa): string {
        $dan = sprintf('%02d:%02d', intdiv($boshlanish, 60), $boshlanish % 60);

        return "Bu — O'ZBEK maktabidagi darsning {$dan} dan boshlanadigan "
            . "{$daqiqa} daqiqalik bo'lagi.\n\n"
            . "Vazifa: audioni SO'ZMA-SO'Z matnga o'girib ber. Boshqa hech narsa yozma — "
            . "na izoh, na tahlil, na sarlavha.\n\n"
            . "Qoidalar:\n"
            . "- Har gapni kim aytganini belgila: \"O'qituvchi:\" yoki \"O'quvchi:\" "
            . "(ismi eshitilsa \"O'quvchi (Aziz):\")\n"
            . "- HECH NARSANI tushirib qoldirma va qisqartirma — qisqa javoblar, "
            . "takroriy so'zlar, to'xtalishlar ham yozilsin\n"
            . "- Eshitilmagan joyni [eshitilmadi] deb belgila, taxmin qilma\n"
            . "- Ingliz tilida aytilgan joylarni ingliz tilida qoldir\n"
            . "- Bo'lak oxirigacha yoz, o'rtada to'xtama";
    }

    /**
     * Tahlil so'rovi — AUDIOSIZ, tayyor matn bo'yicha. Natija QAT'IY JSON.
     *
     * Prompt `CATEGORIES` dan QURILADI: kategoriya qo'shilsa yoki uning
     * `talab` matni o'zgarsa, prompt o'zi yangilanadi. Avval ro'yxat promptda
     * qo'lda yozilgan edi va jadval bilan ajralib ketish xavfi bor edi.
     */
    protected static function prompt_analyse(\stdClass $dars, ?\stdClass $course,
                                             string $transkript): string {
        $mavzu = trim((string) $dars->topic);
        $kurs = $course ? $course->fullname : '';

        // Kurs idnumber: "<yil>::<guruh>::<fan>" — fan va sinfni shundan ajratamiz
        $guruh = '';
        $fan = '';
        if ($course && !empty($course->idnumber)) {
            $qism = explode('::', $course->idnumber);
            $guruh = $qism[1] ?? '';
            $fan = $qism[2] ?? '';
        }
        if ($fan === '' && $kurs !== '') {
            $fan = $kurs;   // idnumber bo'lmasa kurs nomidan urinamiz
        }
        $oila = self::fan_oila($fan, $guruh);
        $sinf = $guruh !== '' ? self::sinf_raqami($guruh) : null;
        $kodlar = self::kodlar($oila);

        // --- Kategoriya ko'rsatmalari guruhlar bo'yicha
        $guruh_nomi = [
            'organization' => "DARS TASHKILI",
            'content' => "MAZMUN VA TUSHUNTIRISH",
            'thinking' => "FIKRLASHNI FAOLLASHTIRISH",
            'climate' => "MUHIT VA MUNOSABAT",
            'outcome' => "NATIJA — O'QUVCHI NIMA O'RGANDI",
            'subject' => "FAN MAHORATI (shu fanga xos — " . strtoupper($oila) . ")",
        ];
        $royxat = '';
        $joriy = '';
        foreach ($kodlar as $kod) {
            $k = self::CATEGORIES[$kod];
            if ($k['guruh'] !== $joriy) {
                $joriy = $k['guruh'];
                $royxat .= "\n" . ($guruh_nomi[$joriy] ?? strtoupper($joriy)) . ":\n";
            }
            $royxat .= "- {$kod} ({$k['nom']}): {$k['talab']}\n";
        }

        // --- Dars turlari: model o'zi aniqlaydi
        $turlar = '';
        foreach (self::DARS_TURLARI as $t => $d) {
            $turlar .= "- \"{$t}\" — {$d['nom']}\n";
        }

        $fan_izoh = [
            'aniq' => "ANIQ FAN (matematika/fizika/kimyo tipida): tushuntirish aniqligi, "
                . "mashq va XATOGA MUNOSABAT hal qiluvchi. Bu fanlarda qo'rquv aynan xato "
                . "jazolanganda tug'iladi — xato bilan qanday ishlanganiga alohida e'tibor ber.",
            'til' => "TIL DARSI: o'quvchi nutqi ko'p bo'lishi SHART. O'qituvchi 80% gapirsa, "
                . "o'quvchi tilni o'rganmaydi. Qaysi tilda gapirilgani va o'quvchi "
                . "javoblarining UZUNLIGI eng muhim ko'rsatkich.",
            'gumanitar' => "GUMANITAR FAN (tarix/adabiyot tipida): bu fanlarda yagona "
                . "\"to'g'ri javob\" yo'q — dars savol va muhokama bilan yashaydi. Dalil "
                . "talab qilinganiga, turli qarashlar taqqoslanganiga e'tibor ber.",
            'tabiiy' => "TABIIY FAN: kundalik hodisa bilan bog'lash eng kuchli vosita. "
                . "Kuzatish, taxmin va xato tasavvurlar bilan ishlashga e'tibor ber.",
            'boshlangich' => "BOSHLANG'ICH SINF (1-4): emotsional muhit, rutina va lug'at "
                . "mazmun chuqurligidan USTUN. Ko'rsatmaning qisqa va takroriy bo'lishi "
                . "NORMAL, buni kamchilik deb hisoblama. Chuqur tahlil kutilmaydi.",
            'amaliy' => "AMALIY FAN (jismoniy tarbiya/mehnat/san'at/musiqa): bu darsda KO'P "
                . "GAPIRISH KUTILMAYDI. O'lchov — o'quvchilar qancha vaqt haqiqatda "
                . "BAJARDI. Jimlik bu yerda kamchilik emas. Xavfsizlik ko'rsatmasi muhim.",
            'umumiy' => "Fan aniqlanmadi: mezonlarni transkriptdan ko'rinib turgan fan "
                . "tabiatiga qarab talqin qil.",
        ];

        return "Sen dars kuzatuvi bo'yicha xalqaro metodist-ekspertsan. Senga O'ZBEKISTONdagi "
            . "xalqaro maktabda o'tilgan darsning TO'LIQ TRANSKRIPTI beriladi "
            . "(audio bo'lak-bo'lak matnga o'girilgan, [MM:SS] belgilari vaqtni ko'rsatadi).\n"
            . "Dars: {$kurs}. Sinf: " . ($guruh ?: "noma'lum")
            . ($sinf !== null ? " ({$sinf}-sinf)" : '') . ". "
            . "Fan: " . ($fan ?: "noma'lum") . " [oila: {$oila}]. "
            . "Sana: {$dars->lessondate}, {$dars->lessonno}-dars. "
            . "O'qituvchi jurnalga yozgan mavzu: \"" . ($mavzu ?: '(yozilmagan)') . "\".\n\n"

            . "MAQSAD: o'qituvchini tanqid qilish EMAS — unga YORDAMCHI bo'lish. "
            . "Sen uning shaxsiy metodisti rolidasan: nima yaxshi chiqqanini ham, nimani "
            . "yaxshilash mumkinligini ham ayt. Ohang xolis va qo'llab-quvvatlovchi. "
            . "Ayblov so'zlarini ishlatma.\n\n"

            . "=== 1-VAZIFA: DARS TURINI ANIQLA ===\n"
            . "Transkriptdan darsning turini aniqlab `lesson_type` ga yoz:\n{$turlar}"
            . "Bu MUHIM: nazorat ishida o'qituvchi tushuntirmaydi va savol bermaydi — "
            . "bunday darsni tushuntirish bo'yicha baholash haqsizlik bo'ladi. Tizim "
            . "turga qarab mezonlarni o'zi sozlaydi.\n\n"

            . "=== 2-VAZIFA: FANGA MOS TALQIN ===\n"
            . ($fan_izoh[$oila] ?? $fan_izoh['umumiy']) . "\n\n"
            . "Har fanning o'z tabiati bor: aniq fanlarga qo'yilgan talab gumanitar "
            . "fanlarga to'g'ri kelmaydi. Mezonlarni SHU fan, SHU sinf va SHU dars turiga "
            . "qarab talqin qil.\n\n"

            . "=== 3-VAZIFA: KATEGORIYALARNI BAHOLA ===\n"
            . "Baholash ramkasi: Great Teaching Toolkit (Coe va h., 2020 — 100 dan ortiq "
            . "ramkaning jamlanmasi), CLASS (Pianta), ICALT, Jahon banki TEACH, "
            . "matematika uchun MQI, til uchun PLATO.\n"
            . "Har kategoriya 0-100 FOIZDA baholanadi (ha/yo'q EMAS):\n"
            . $royxat . "\n"

            . "=== HALOLLIK QOIDASI ===\n"
            . "Senda FAQAT audio transkripti bor. Imo-ishora, doskadagi ish, sinf "
            . "joylashuvi va yozma ishlar KO'RINMAYDI. Har kategoriyaga `confidence` qo'y: "
            . "\"high\" (transkriptda aniq dalil bor), \"medium\" (qisman), \"low\" "
            . "(audio orqali baholab bo'lmaydi). Dalil bo'lmasa \"low\" qil — taxmin "
            . "qilib ball QO'YMA. `low` kategoriya umumiy ballga kiritilmaydi, ya'ni "
            . "halol \"bilmadim\" o'qituvchiga zarar qilmaydi.\n\n"

            . "TAQIQ: o'qituvchining ichki hissiy holati, charchoqligi, stressi yoki "
            . "shaxsiyati haqida XULOSA CHIQARMA. Faqat eshitilgan XATTI-HARAKATNI "
            . "tasvirla. `wellbeing_notes` ga faqat kuzatilgan faktni yoz (masalan: "
            . "\"uch marta ovoz ko'tarildi\") — tashxis yoki taxmin emas.\n\n"

            . "=== DALIL QOIDASI ===\n"
            . "Har kategoriyaning `evidence` maydoni ANIQ va RAQAMLI bo'lsin, iloji "
            . "bo'lsa [MM:SS] bilan:\n"
            . "  yaxshi: \"12 savoldan 3 tasi ochiq; [12:30] «Nega minus chiqdi?» — 4 sekund kutdi.\"\n"
            . "  yaxshi: \"Mavzuning 3 qismidan 2 tasi yoritildi, masala yechish qismi o'tilmadi.\"\n"
            . "  yomon: \"O'qituvchi yaxshi tushuntirdi.\" (raqam ham, aniq fakt ham yo'q)\n\n"
            . "`advice` — BITTA amaliy, bajarilishi mumkin tavsiya ("
            . "\"kutish vaqtini 3 sekundga chiqaring\" kabi), umumiy nasihat emas.\n\n"

            . "=== METRICS: RAQAMLARNI SANA ===\n"
            . "Bu raqamlardan ba'zi ballarni TIZIM O'ZI hisoblaydi, shuning uchun ular "
            . "iloji boricha aniq bo'lsin. Transkriptdan hisoblab yoz:\n"
            . "- teacher_talk_pct / student_talk_pct: nutq ulushi (yig'indisi ~100)\n"
            . "- questions_total: o'qituvchi bergan savollar soni\n"
            . "- open_questions: ulardan ochiq (fikrlash talab qiladigan) savollar soni\n"
            . "- wait_time_avg_sec: savol berib javobni kutish o'rtacha vaqti "
            . "([MM:SS] belgilariga qarab taxminla; darhol o'zi javob bersa 0)\n"
            . "- students_addressed: nechta TURLI o'quvchi gapirdi\n"
            . "- student_questions: o'quvchilar bergan savollar soni\n"
            . "- on_task_pct: darsning necha foizi haqiqiy o'quv ishida o'tdi\n"
            . "- negative_episodes: baqirish/kinoya/kamsitish epizodlari soni (aniq "
            . "eshitilgani; yo'q bo'lsa 0)\n"
            . "- language: darsda ishlatilgan tillar ulushi\n\n"

            . "FAQAT JSON qaytar, boshqa hech qanday matn yo'q:\n"
            . '{'
            . '"lesson_type":"yangi",'
            . '"summary":"3-5 jumlali xulosa",'
            . '"categories":['
            . '{"code":"topic_match","score":70,"confidence":"high",'
            . '"actual_topic":"darsda aslida nima o\'tildi",'
            . '"evidence":"aniq raqamli dalil","advice":"bitta amaliy tavsiya"}'
            . '],'
            . '"learned":["o\'quvchilar aniq egallagan narsa 1","narsa 2"],'
            . '"metrics":{"teacher_talk_pct":70,"student_talk_pct":30,"questions_total":14,'
            . '"open_questions":5,"wait_time_avg_sec":2,"students_addressed":9,'
            . '"student_questions":3,"on_task_pct":85,"negative_episodes":0,'
            . '"language":{"uz":60,"en":40}},'
            . '"wellbeing_notes":["kuzatilgan fakt, tashxis emas"],'
            . '"recommendations":["tavsiya 1","tavsiya 2","tavsiya 3"]'
            . "}\n\n"

            . "categories massivida YUQORIDAGI " . count($kodlar) . " kodning HAMMASI "
            . "bo'lsin: " . implode(', ', $kodlar) . ". "
            . "score butun son (0-100). Umumiy foizni O'ZING hisoblama — tizim hisoblaydi. "
            . "Transkriptni QAYTARMA — u allaqachon bizda.\n\n"
            . "=== DARS TRANSKRIPTI ===\n" . $transkript;
    }
    /** Bitta modelga so'rov; vaqtinchalik xatolarda qayta urinadi. */
    protected static function call(string $model, string $prompt, ?string $b64 = null,
                                   ?string $mime = null, bool $json = true) {
        $kalit = trim((string) get_config('local_targetzenit', 'geminikey'));

        $input = [['type' => 'text', 'text' => $prompt]];
        if ($b64 !== null) {
            $input[] = ['type' => 'audio', 'data' => $b64, 'mime_type' => $mime];
        }
        $body = json_encode(['model' => $model, 'input' => $input]);

        for ($i = 0; $i < self::URINISH; $i++) {
            $curl = new \curl();
            $curl->setHeader(['x-goog-api-key: ' . $kalit, 'Content-Type: application/json']);
            $javob = $curl->post(self::ENDPOINT, $body, [
                'CURLOPT_TIMEOUT' => 900,
                'CURLOPT_CONNECTTIMEOUT' => 30,
            ]);
            $kod = (int) ($curl->get_info()['http_code'] ?? 0);

            if ($kod === 200) {
                return $json ? self::parse($javob) : self::matn($javob);
            }
            if ($kod === 429) {
                // Kvota tugagan — qayta urinish befoyda, zaxira modelga o'tiladi
                throw new \RuntimeException('Kvota tugagan (' . $model . ')');
            }
            if ($kod >= 500 || $kod === 0) {
                sleep(5 * ($i + 1));     // band — kutib qayta urinamiz
                continue;
            }
            // Sababni keyin qidirmaslik uchun kontekstni ham yozamiz
            throw new \RuntimeException(sprintf(
                'Gemini HTTP %d [model=%s, mime=%s, hajm=%.1fMB]: %s',
                $kod, $model, (string) $mime, strlen((string) $b64) / 1048576,
                \core_text::substr((string) $javob, 0, 250)
            ));
        }
        throw new \RuntimeException('Gemini javob bermadi (' . $model . ')');
    }

    /** Javobdan sof matnni ajratib olish (transkripsiya bosqichi uchun). */
    protected static function matn(string $raw): string {
        $j = json_decode($raw, true);
        $matn = '';
        if (is_array($j)) {
            $qidir = function ($a) use (&$qidir, &$matn) {
                foreach ($a as $k => $v) {
                    if (is_array($v)) {
                        $qidir($v);
                    } else if ($k === 'text' && is_string($v) && strlen($v) > strlen($matn)) {
                        $matn = $v;
                    }
                }
            };
            $qidir($j);
        }
        return trim($matn !== '' ? $matn : $raw);
    }

    /** Javobdan JSON ajratib olish (model matn ichiga o'rab yuborishi mumkin). */
    protected static function parse(string $raw): array {
        $j = json_decode($raw, true);

        // Javob tuzilishidan matnni qazib olamiz
        $matn = '';
        if (is_array($j)) {
            $qidir = function ($a) use (&$qidir, &$matn) {
                foreach ($a as $k => $v) {
                    if (is_array($v)) {
                        $qidir($v);
                    } else if ($k === 'text' && is_string($v) && strlen($v) > strlen($matn)) {
                        $matn = $v;
                    }
                }
            };
            $qidir($j);
        }
        if ($matn === '') {
            $matn = $raw;
        }

        // ```json ... ``` ichidan olish
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $matn, $m)) {
            $matn = $m[1];
        }
        $boshi = strpos($matn, '{');
        $oxiri = strrpos($matn, '}');
        if ($boshi !== false && $oxiri !== false && $oxiri > $boshi) {
            $matn = substr($matn, $boshi, $oxiri - $boshi + 1);
        }

        $natija = json_decode($matn, true);

        // DIQQAT: `transcript` TALAB QILINMAYDI — 0.13.0 dan beri transkript
        // alohida bosqichda olinadi va modelga "qaytarma" deyilgan. Avval shu
        // yerda `transcript` talab qilingani uchun TO'G'RI javob ham rad etilardi.
        if (!is_array($natija) || (!isset($natija['categories']) && !isset($natija['summary']))) {
            throw new \RuntimeException('Javobni JSON sifatida o\'qib bo\'lmadi. Model javobi: '
                . \core_text::substr(trim($matn), 0, 300));
        }
        return $natija;
    }

    /**
     * Umumiy foiz — vaznli o'rtacha. MODEL hisoblamaydi, kod hisoblaydi:
     * shunda hisob shaffof bo'ladi va modeldan modelga o'zgarib ketmaydi.
     *
     * `confidence = low` bo'lgan kategoriya hisobga OLINMAYDI va vaznlar
     * qolganlari bo'yicha qayta taqsimlanadi — audio ko'rmagan narsa
     * o'qituvchining umumiy bahosini pasaytirmasligi kerak. Shu qoida
     * tufayli dars turiga mos kelmaydigan kategoriya (nazorat ishidagi
     * "tushuntirish aniqligi") ham ballni tushirmaydi.
     *
     * @param array $vaznlar `vaznlar($oila, $tur)` natijasi. Bo'sh bo'lsa
     *                       bazaviy vaznlar ishlatiladi (fan va dars turi
     *                       hisobga olinmaydi).
     */
    public static function overall(array $categories, array $vaznlar = []): array {
        $yigindi = 0.0;
        $vazn_jami = 0;
        $hisobda = [];
        $chetda = [];

        foreach ($categories as $c) {
            $kod = $c['code'] ?? '';
            if (!isset(self::CATEGORIES[$kod])) {
                continue;
            }
            // Vazn ro'yxati berilgan bo'lsa — faqat undagi kodlar hisobda.
            // Fan modullari (`vazn = 0`) va dars turiga mos kelmaydigan
            // kodlar shu yerda tushib qoladi.
            $bazaviy = (int) self::CATEGORIES[$kod]['vazn'];
            $vazn = $vaznlar ? ($vaznlar[$kod] ?? 0) : $bazaviy;
            if ($vazn <= 0) {
                // Universal mezon bo'lib vazni nolga tushgan bo'lsa — demak
                // shu DARS TURIDA kutilmaydi, buni foydalanuvchiga aytamiz.
                // Fan modullari (bazaviy vazni ham 0) esa reytingga
                // umuman tegishli emas, ro'yxatda ko'rinmaydi.
                if ($bazaviy > 0) {
                    $chetda[] = $kod;
                }
                continue;
            }
            if (($c['confidence'] ?? '') === 'low') {
                $chetda[] = $kod;
                continue;
            }
            $yigindi += ((int) ($c['score'] ?? 0)) * $vazn;
            $vazn_jami += $vazn;
            $hisobda[] = $kod;
        }

        return [
            'score' => $vazn_jami ? (int) round($yigindi / $vazn_jami) : null,
            'hisobda' => $hisobda,
            'chetda' => $chetda,
        ];
    }

    /**
     * Natijani saqlash.
     *
     * Shu yerda uch narsa qilinadi, va hammasi ATAYLAB modelda emas, kodda:
     *   1) dars turi tozalanadi va shu turda kutilmaydigan kategoriyalar
     *      `low` ga o'tkaziladi (nazorat ishi "tushuntirish aniqligi"
     *      bo'yicha baholanmasligi kerak);
     *   2) `hisob => true` kategoriyalar bali `metrics` raqamlaridan qayta
     *      hisoblanadi (modelning abstrakt bali ishlatilmaydi);
     *   3) effektiv vazn (fan profili x dars turi) va umumiy foiz hisoblanadi.
     */
    protected static function save(\stdClass $audio, array $javob, string $model,
                                   string $oila = 'umumiy'): bool {
        global $DB;

        $transkript = (string) ($javob['transcript'] ?? '');
        unset($javob['transcript']);   // tahlil JSON'ida takrorlanmasin

        $tur = self::dars_turi($javob['lesson_type'] ?? '');
        $javob['lesson_type'] = $tur;
        $javob['subject_family'] = $oila;

        $vaznlar = self::vaznlar($oila, $tur);
        $kutilmaydi = self::DARS_TURLARI[$tur]['kutilmaydi'] ?? [];
        $metrics = is_array($javob['metrics'] ?? null) ? $javob['metrics'] : [];

        if (!empty($javob['categories']) && is_array($javob['categories'])) {
            foreach ($javob['categories'] as &$c) {
                $kod = $c['code'] ?? '';
                if (!isset(self::CATEGORIES[$kod])) {
                    continue;
                }
                $k = self::CATEGORIES[$kod];

                // Nom ATAYLAB saqlanmaydi: JSON'ga yozilsa tahlil qilingan
                // paytdagi til qotib qoladi va foydalanuvchi tilni
                // o'zgartirganda ham o'zbekcha bo'lib qolaverardi. Ko'rsatish
                // vaqtida `cat_name()` tarjima qiladi.
                $c['name'] = self::cat_name($kod);
                $c['score'] = max(0, min(100, (int) ($c['score'] ?? 0)));
                $c['weight'] = $vaznlar[$kod] ?? 0;
                $c['min_n'] = (int) $k['min_n'];

                // Ball indikatorlardan: takrorlanuvchi va tushuntiriladigan
                if (!empty($k['hisob'])) {
                    $hisoblangan = self::indikator_ball($kod, $metrics);
                    if ($hisoblangan !== null) {
                        $c['model_score'] = $c['score'];   // taqqoslash uchun qoladi
                        $c['score'] = $hisoblangan;
                        $c['score_source'] = 'metrics';
                    }
                }

                // Dars turiga mos kelmaydigan kategoriya baholanmaydi
                if (in_array($kod, $kutilmaydi, true)) {
                    $c['confidence'] = 'low';
                    $c['note'] = self::tur_nomi($tur) . " turidagi darsda bu mezon "
                        . "baholanmaydi";
                }
            }
            unset($c);
            $javob['overall'] = self::overall($javob['categories'], $vaznlar);
        }

        $audio->transcript = $transkript;
        $audio->analysis = json_encode($javob, JSON_UNESCAPED_UNICODE);
        $audio->aimodel = \core_text::substr($model, 0, 64);
        $audio->aierror = null;
        $audio->analysedat = time();
        $audio->status = 'transcribed';
        $audio->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $audio);

        // Direktor paneli uchun: ballarni SQL bilan yig'ib bo'ladigan
        // ko'rinishga ham yozamiz va faktni darhol yangilaymiz.
        //
        // try/catch MAJBURIY: indeks yoki fakt muammosi TAHLIL
        // SAQLANISHINI to'smasligi kerak — asosiy natija yuqorida
        // allaqachon saqlangan (gradebook bilan bir xil himoya usuli).
        try {
            if (!empty($javob['categories']) && is_array($javob['categories'])) {
                aicat::store((int) $audio->lessonid, $javob['categories']);
            }
            fact::touch_lesson((int) $audio->lessonid);
        } catch (\Throwable $e) {
            debugging('local_targetzenit: tahlil indeksi yozilmadi — '
                . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return true;
    }

    protected static function fail(\stdClass $audio, string $xato): bool {
        global $DB;
        $audio->aierror = \core_text::substr($xato, 0, 255);
        $audio->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $audio);
        return false;
    }

    /** Darsning tahlili (dekodlangan). */
    public static function for_lesson(int $lessonid): ?array {
        $a = audio::get($lessonid);
        if (!$a || empty($a->analysis)) {
            return null;
        }
        $j = json_decode($a->analysis, true);
        return is_array($j) ? ['audio' => $a, 'data' => $j] : null;
    }
}
