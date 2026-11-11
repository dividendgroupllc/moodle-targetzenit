/*
 * Dars audio yozuvi — brauzer tomoni.
 *
 * MediaRecorder har 15 soniyada bo'lak beradi, bo'lak DARHOL serverga ketadi.
 * Shuning uchun brauzer yopilsa, telefon uxlasa yoki internet uzilsa ham
 * yozilgan qism serverda qoladi.
 *
 * Yuklash NAVBAT bilan: bo'laklar tartibi buzilmasligi kerak, chunki server
 * ularni (avlod, tartib) bo'yicha birlashtiradi.
 *
 * AVLOD (gen): bitta MediaRecorder = bitta avlod. Yozuv uzilib MediaRecorder
 * qayta yaratilsa, avlod raqami OSHADI — chunki yangi oqim yangi sarlavha
 * bilan boshlanadi va server bu qismlarni ffmpeg bilan ulashi kerak. Avlodni
 * oshirmasa, uzilishdan keyingi butun dars jimgina o'qilmas bo'lib qolardi.
 *
 * SESSIYA (sid): `start` bergan kalit. Shu dars boshqa oyna/qurilmada
 * ochilsa server yangi kalit beradi va bu oynaning bo'laklari rad etiladi —
 * ikki oqim bir faylga aralashmasligi uchun. Bunda yozuv to'xtaydi va
 * o'qituvchiga sabab ko'rsatiladi (jim davom etib, bekorga yozib
 * o'tirishdan ko'ra shu to'g'ri).
 */
(function () {
    'use strict';

    var SLICE_MS = 15000;      // bo'lak uzunligi
    var BPS = 24000;           // 24 kbps mono opus ~ 8 MB / 45 daqiqa
    var KUTISH_MIN = 3000;     // qayta urinishgacha eng kam kutish
    var KUTISH_MAX = 30000;    // ... va eng ko'p (internet uzoq yo'q bo'lsa)

    function qs(id) { return document.getElementById(id); }

    function fmtVaqt(s) {
        if (s < 0) { s = 0; }
        var m = Math.floor(s / 60), q = s % 60;
        return m + ':' + (q < 10 ? '0' : '') + q;
    }

    function TzRecorder(cfg) {
        this.cfg = cfg;
        this.gen = 1;          // avlod raqami (serverdan keladi)
        this.seq = 0;          // avlod ICHIDAGI tartib
        this.sid = '';         // sessiya kaliti
        this.navbat = [];
        this.yuklanmoqda = false;
        this.band = false;     // yozuv ketmoqdami (ikki marta boshlanmasligi uchun)
        this.tamom = false;    // qat'iy xato — urinishdan foyda yo'q
        this.boshlandi = 0;
        this.pauzaJami = 0;
        this.pauzaBoshi = 0;
        this.xatolar = 0;
        this.timer = null;
        this.qorovul = null;
        this.stream = null;
        this.rec = null;
        this.oxirgiBolak = 0;      // oxirgi bo'lak qachon keldi (qorovul uchun)
        this.bolakSoni = 0;        // jami yaratilgan bo'lak — HAQIQIY ovoz o'lchovi
        this.uzilish = 0;          // necha marta o'zi tiklandi
        this.wakeLock = null;
        this.jimlikdan = 0;        // mikrofon qachondan beri jim
        this.jurnal = [];          // qora quti
        this.diskda = false;       // IndexedDB ishlayaptimi
    }

    // =====================================================================
    // INDEXEDDB: bo'laklar DISKDA turadi, brauzer xotirasida emas.
    //
    // NEGA: avval navbat faqat `this.navbat` massivida — ya'ni sahifa
    // xotirasida — edi. Internet uzilganda bo'laklar saqlanib turardi, lekin
    // oyna yopilsa, brauzer yiqilsa yoki kompyuter o'chib qolsa YO'QOLARDI.
    // IndexedDB diskda saqlaydi: brauzer qayta ochilganda ham bo'laklar
    // joyida bo'ladi va keyingi kirishda o'zi yuboriladi.
    //
    // Xotira ham tejaladi: navbat uzun bo'lsa (uzoq internetsiz ish) bo'lak
    // xotiradan olib tashlanadi va yuborish paytida diskdan o'qiladi.
    // =====================================================================
    var IDB_NOM = 'tz-audio';
    var IDB_STORE = 'bolaklar';
    var XOTIRA_CHEGARA = 20;   // shundan ortiq navbatda bo'lsa diskka tayanamiz

    function idbQur(db) {
        if (!db.objectStoreNames.contains(IDB_STORE)) {
            var st = db.createObjectStore(IDB_STORE, {keyPath: 'kalit'});
            st.createIndex('lessonid', 'lessonid', {unique: false});
        }
    }

    function idbOch() {
        return new Promise(function (bajar, rad) {
            if (!window.indexedDB) { rad(new Error('indexedDB yo\'q')); return; }
            var s = indexedDB.open(IDB_NOM);     // versiyani MAJBURLAMAYMIZ
            s.onupgradeneeded = function () { idbQur(s.result); };
            s.onerror = function () { rad(s.error); };
            s.onsuccess = function () {
                var db = s.result;
                if (db.objectStoreNames.contains(IDB_STORE)) { bajar(db); return; }

                // Baza bor, lekin ombor yo'q (yarim yaratilgan yoki boshqa kod
                // ochib qo'ygan). Versiyani oshirib omborni QO'SHAMIZ — aks
                // holda bo'laklar hech qachon diskka tushmay qolardi.
                var yangi = db.version + 1;
                db.close();
                var s2 = indexedDB.open(IDB_NOM, yangi);
                s2.onupgradeneeded = function () { idbQur(s2.result); };
                s2.onsuccess = function () { bajar(s2.result); };
                s2.onerror = function () { rad(s2.error); };
            };
        });
    }

    function idbAmal(rejim, ishlovchi) {
        return idbOch().then(function (db) {
            return new Promise(function (bajar, rad) {
                var tx = db.transaction(IDB_STORE, rejim);
                var st = tx.objectStore(IDB_STORE);
                var natija = ishlovchi(st);
                tx.oncomplete = function () { db.close(); bajar(natija && natija.result); };
                tx.onerror = function () { db.close(); rad(tx.error); };
                tx.onabort = function () { db.close(); rad(tx.error); };
            });
        });
    }

    function idbSaqla(ish) {
        return idbAmal('readwrite', function (st) {
            return st.put({
                kalit: ish.kalit, lessonid: ish.lessonid, sid: ish.sid,
                gen: ish.gen, seq: ish.seq, blob: ish.blob, vaqt: Date.now()
            });
        });
    }

    function idbOl(kalit) {
        return idbAmal('readonly', function (st) { return st.get(kalit); })
            .then(function (r) { return r ? r.blob : null; });
    }

    function idbOchir(kalit) {
        return idbAmal('readwrite', function (st) { return st.delete(kalit); });
    }

    /** Shu darsning diskda qolgan bo'laklari (tartib bilan). */
    function idbRoyxat(lessonid) {
        return idbAmal('readonly', function (st) {
            return st.index('lessonid').getAll(lessonid);
        }).then(function (list) {
            list = list || [];
            list.sort(function (a, b) { return (a.gen - b.gen) || (a.seq - b.seq); });
            return list;
        });
    }

    function idbTozala(lessonid) {
        return idbRoyxat(lessonid).then(function (list) {
            return Promise.all((list || []).map(function (x) { return idbOchir(x.kalit); }));
        });
    }

    /**
     * QORA QUTI — hodisalar jurnali.
     *
     * "Nega jim bo'lib qoldi?" degan savolga KEYIN javob berish uchun.
     * 2026-11-11 dagi tekshiruvda bu jurnal yo'q edi va sababni nginx
     * jurnali bilan fayl ichidagi vaqt belgilaridan tiklashga to'g'ri keldi.
     * Endi hamma narsa o'zi yozilib boradi: oyna yashirildi/ko'rindi,
     * internet uzildi, mikrofon o'chdi, qorovul yozuvni tikladi.
     *
     * Jurnalda AUDIO YO'Q — faqat texnik hodisalar.
     */
    TzRecorder.prototype.qayd = function (hodisa, izoh) {
        var t = this.boshlandi ? Math.floor((Date.now() - this.boshlandi) / 1000) : 0;
        this.jurnal.push([t, hodisa, izoh || '']);
        if (this.jurnal.length > 600) { this.jurnal.splice(0, 100); }
    };

    /** Jurnalni serverga yuborish (yo'qolib ketmasin). */
    TzRecorder.prototype.jurnalYubor = function (beacon) {
        var tana = JSON.stringify({
            boshlandi: this.boshlandi,
            bolak: this.bolakSoni,
            uzilish: this.uzilish,
            qurilma: navigator.userAgent,
            jurnal: this.jurnal
        });
        var u = this.url('diag');
        if (beacon && navigator.sendBeacon) {
            try { navigator.sendBeacon(u, new Blob([tana], {type: 'application/json'})); }
            catch (e) { /* ahamiyatsiz */ }
            return;
        }
        fetch(u, {method: 'POST', body: tana,
            headers: {'Content-Type': 'application/json'}}).catch(function () {});
    };

    /**
     * DISKDA QOLGAN bo'laklarni yuborish.
     *
     * Brauzer yiqilgan, oyna yopilgan yoki kompyuter o'chib qolgan bo'lsa,
     * yuborilmagan bo'laklar IndexedDB da qoladi. Yangi sessiya SO'RASHDAN
     * OLDIN ularni jo'natamiz: bazadagi yozuv hali `recording` holatda va
     * eski `sid` turgani uchun server ularni qabul qiladi, keyin yangi
     * sessiya keyingi avlod bo'lib ustiga qo'shiladi — dars yo'qolmaydi.
     */
    TzRecorder.prototype.qoldiqniYubor = function () {
        var self = this;
        return idbRoyxat(self.cfg.lessonid).then(function (list) {
            if (!list || !list.length) { return; }
            self.qayd('qoldiq', list.length + ' bolak diskda topildi');
            self.holat('Oldingi yozuvdan ' + list.length
                + ' bo\'lak topildi — yuborilmoqda...', 'text-info');

            return list.reduce(function (zanjir, x) {
                return zanjir.then(function () {
                    if (!x.blob) { return idbOchir(x.kalit); }
                    return fetch(self.chunkUrl(x.sid, x.gen, x.seq), {
                        method: 'POST',
                        body: x.blob,
                        headers: {'Content-Type': 'application/octet-stream'}
                    }).then(function (r) { return r.json(); }).then(function () {
                        // Server qabul qilmasa ham o'chiramiz: yozuv
                        // allaqachon yakunlangan bo'lsa bu bo'lak kerak emas.
                        return idbOchir(x.kalit);
                    });
                    // DIQQAT: internet yo'q bo'lsa `catch` ishlamaydi va
                    // bo'lak DISKDA QOLADI — keyingi kirishda yuboriladi.
                });
            }, Promise.resolve());
        }).catch(function () { /* internet yo'q — keyingi kirishda */ });
    };

    /**
     * Ekranni uyg'oq ushlash.
     *
     * NEGA KERAK (2026-11-11, prodda 14 yozuvdan 9 tasi kesilgan): noutbuk
     * uxlab qolsa yoki oyna fonga o'tsa MediaRecorder bo'lak bermay qo'yadi,
     * brauzer soati esa yuraveradi — natijada 3 daqiqalik ovoz "33 daqiqa"
     * bo'lib saqlanadi. Frappe'dagi birinchi versiyada wake lock BOR edi,
     * Moodle'ga ko'chirishda tushib qolgan.
     *
     * DIQQAT: wake lock faqat sahifa KO'RINIB turganda ishlaydi, shuning
     * uchun `visibilitychange` da qaytadan olinadi.
     */
    TzRecorder.prototype.wakeLockOl = function () {
        var self = this;
        if (!navigator.wakeLock || self.wakeLock) { return; }
        navigator.wakeLock.request('screen').then(function (l) {
            self.wakeLock = l;
            self.qayd('wakelock', 'olindi');
            l.addEventListener('release', function () {
                self.wakeLock = null;
                self.qayd('wakelock', 'bo\'shadi');
            });
        }).catch(function (e) {
            self.qayd('wakelock', 'BERILMADI: ' + (e && e.name));
        });
    };

    TzRecorder.prototype.wakeLockQoyib = function () {
        if (this.wakeLock) {
            try { this.wakeLock.release(); } catch (e) { /* allaqachon bo'shagan */ }
            this.wakeLock = null;
        }
    };

    /**
     * Mikrofon darajasini o'lchash.
     *
     * Ikki foyda: (1) o'qituvchi mikrofon ishlayotganini KO'RADI — jim
     * yozuvdan ogohlantiramiz; (2) tirik `AudioContext` sahifani "audio
     * bilan ishlayapti" deb belgilaydi, bu brauzerning fon oynani
     * muzlatishini qiyinlashtiradi.
     */
    TzRecorder.prototype.olchovBoshla = function (stream) {
        var self = this;
        try {
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) { return; }
            self.ac = new AC();
            if (self.ac.state === 'suspended') { self.ac.resume(); }
            self.analyser = self.ac.createAnalyser();
            self.analyser.fftSize = 2048;
            self.ac.createMediaStreamSource(stream).connect(self.analyser);
            self.buf = new Uint8Array(self.analyser.fftSize);
        } catch (e) { self.analyser = null; }
    };

    /** Hozirgi ovoz darajasi 0..1 (analyser bo'lmasa null). */
    TzRecorder.prototype.daraja = function () {
        if (!this.analyser || !this.buf) { return null; }
        this.analyser.getByteTimeDomainData(this.buf);
        var jami = 0;
        for (var i = 0; i < this.buf.length; i++) {
            var v = (this.buf[i] - 128) / 128;
            jami += v * v;
        }
        return Math.sqrt(jami / this.buf.length);
    };

    /**
     * QOROVUL — eng muhim tuzatish.
     *
     * MediaRecorder "recording" holatida turib, bo'lak BERMAY qo'yishi mumkin
     * (qurilma uxladi, oyna muzlatildi, mikrofonni boshqa ilova oldi). Hodisa
     * ham, xato ham chiqmaydi — shuning uchun buni faqat VAQT bilan sezish
     * mumkin: bo'lak kelmayotgan bo'lsa, yozuvni majburan qayta boshlaymiz.
     * Avlod raqami oshadi, server qismlarni ffmpeg bilan to'g'ri ulaydi.
     */
    TzRecorder.prototype.tekshir = function () {
        if (!this.rec || this.pauzada || this.yakunlanmoqda || this.tamom) { return; }

        var tinch = Date.now() - this.oxirgiBolak;

        // 1-pog'ona: bo'lakni o'zimiz so'rab ko'ramiz (taymer throttle bo'lgan bo'lishi mumkin)
        if (tinch > SLICE_MS * 2.5 && !this.soraldi) {
            this.soraldi = true;
            this.qayd('qorovul', 'bolak kelmadi ' + Math.round(tinch / 1000) + 's — so\'raldi');
            try { this.rec.requestData(); } catch (e) { /* ahamiyatsiz */ }
            return;
        }

        // 2-pog'ona: hamon jim — yozuvni MAJBURAN qayta boshlaymiz
        if (tinch > SLICE_MS * 4) {
            this.uzilish++;
            this.qayd('qorovul', 'YOZUV TO\'XTAGAN ' + Math.round(tinch / 1000)
                + 's — majburan tiklanadi');
            this.soraldi = false;
            this.oxirgiBolak = Date.now();
            this.qayta(true);
        }
    };

    TzRecorder.prototype.url = function (action, extra) {
        var u = this.cfg.ajaxurl + '?action=' + action +
            '&lessonid=' + this.cfg.lessonid + '&sesskey=' + this.cfg.sesskey;
        if (this.sid) { u += '&sid=' + encodeURIComponent(this.sid); }
        if (extra) { u += extra; }
        return u;
    };

    /** Bo'lak yuklash manzili — sid/avlod/seq ATAYLAB oshkor beriladi:
     *  diskdan topilgan qoldiq ESKI sessiyaga tegishli bo'lishi mumkin. */
    TzRecorder.prototype.chunkUrl = function (sid, gen, seq) {
        return this.cfg.ajaxurl + '?action=chunk&lessonid=' + this.cfg.lessonid
            + '&sesskey=' + this.cfg.sesskey
            + '&sid=' + encodeURIComponent(sid || '')
            + '&avlod=' + gen + '&seq=' + seq;
    };

    TzRecorder.prototype.holat = function (matn, sinf) {
        var el = qs('tz-audio-holat');
        if (el) {
            el.textContent = matn;
            el.className = 'tz-audio-holat ' + (sinf || '');
        }
    };

    /**
     * Qat'iy xato: sessiya tugagan, boshqa oyna egalladi, hajm chegarasi.
     * Bunda navbatni cheksiz aylantirishdan foyda yo'q — yozuvni to'xtatamiz,
     * lekin SERVERDA yozilgan qism joyida qoladi (cron uni o'zi yakunlaydi).
     */
    TzRecorder.prototype.qatiyXato = function (matn) {
        this.tamom = true;
        this.band = false;
        clearInterval(this.timer);
        clearInterval(this.qorovul);
        clearTimeout(this.pauzaTimer);
        this.wakeLockQoyib();
        try {
            if (this.rec && this.rec.state !== 'inactive') {
                this.yakunlanmoqda = true;
                this.rec.stop();
            }
            if (this.stream) {
                this.stream.getTracks().forEach(function (t) { t.stop(); });
            }
        } catch (e) { /* allaqachon to'xtagan */ }
        window.removeEventListener('beforeunload', this.ogohlantir);
        this.holat('⛔ ' + matn + ' — yozilgan qism serverda saqlangan.', 'text-danger');
        var b = qs('tz-audio-start');
        if (b) { b.classList.remove('hidden'); b.textContent = '🎙 Qaytadan boshlash'; }
        var b2 = qs('tz-audio-stop');
        if (b2) { b2.classList.add('hidden'); }
        var b3 = qs('tz-audio-pause');
        if (b3) { b3.classList.add('hidden'); }
    };

    TzRecorder.prototype.navbatniYur = function () {
        var self = this;
        if (this.yuklanmoqda || !this.navbat.length || this.tamom) { return; }
        this.yuklanmoqda = true;

        var ish = this.navbat[0];

        // Blob xotirada bo'lmasa — DISKDAN o'qiymiz (uzoq internetsiz ishda
        // bo'laklar xotiradan olib tashlanadi, diskda esa qoladi).
        var blobni = ish.blob ? Promise.resolve(ish.blob) : idbOl(ish.kalit);

        blobni.then(function (blob) {
            if (!blob) {
                // Diskda ham yo'q — bu bo'lakni tiklab bo'lmaydi, o'tkazamiz
                // (aks holda navbat butunlay qotib qolardi).
                self.qayd('bolak-yoq', 'seq=' + ish.seq);
                self.navbat.shift();
                self.yuklanmoqda = false;
                self.navbatniYur();
                return null;
            }
            return fetch(self.chunkUrl(ish.sid, ish.gen, ish.seq), {
                method: 'POST',
                body: blob,
                headers: {'Content-Type': 'application/octet-stream'}
            });
        }).then(function (r) { return r ? r.json() : null; })
          .then(function (j) {
              if (j === null) { return; }
              if (!j.ok) {
                  var e = new Error(j.error || 'xato');
                  e.qatiy = !!j.qatiy;
                  throw e;
              }
              self.navbat.shift();
              idbOchir(ish.kalit).catch(function () {});
              self.yuklanmoqda = false;
              self.tarmoq = true;
              self.xatolar = 0;
              self.navbatniYur();
          })
          .catch(function (e) {
              self.yuklanmoqda = false;

              if (e && e.qatiy) {
                  // Server aytdi: bu bo'lak HECH QACHON qabul qilinmaydi.
                  self.qayd('qatiy-xato', e.message);
                  self.qatiyXato(e.message);
                  return;
              }

              // Vaqtinchalik: internet yo'q, server band, javob kelmadi.
              // BO'LAK HECH QACHON TASHLANMAYDI — navbatda qoladi va aloqa
              // qaytganda yuboriladi. Yozuvning o'zi davom etaveradi,
              // shuning uchun uzilish paytidagi ovoz YO'QOLMAYDI.
              ish.urinish = (ish.urinish || 0) + 1;
              self.xatolar++;
              if (self.tarmoq !== false) {
                  self.qayd('yuklash-uzildi', 'seq=' + ish.seq + ' ' + (e && e.message));
              }
              self.tarmoq = false;
              var kut = Math.min(KUTISH_MIN * ish.urinish, KUTISH_MAX);
              clearTimeout(self.navbatTimer);
              self.navbatTimer = setTimeout(function () { self.navbatniYur(); }, kut);
          });
    };

    /** Mikrofonlar ro'yxatini chiqarish (tashqi mikrofon ulansa tanlash uchun). */
    TzRecorder.prototype.qurilmalar = function () {
        var sel = qs('tz-audio-device');
        if (!sel || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return;
        }
        // Nomlarni ko'rish uchun avval ruxsat kerak
        navigator.mediaDevices.getUserMedia({audio: true})
            .then(function (s) {
                s.getTracks().forEach(function (t) { t.stop(); });
                return navigator.mediaDevices.enumerateDevices();
            })
            .then(function (list) {
                sel.innerHTML = '';
                list.filter(function (d) { return d.kind === 'audioinput'; })
                    .forEach(function (d, i) {
                        var o = document.createElement('option');
                        o.value = d.deviceId;
                        o.textContent = d.label || ('Mikrofon ' + (i + 1));
                        sel.appendChild(o);
                    });
                sel.parentNode.classList.remove('hidden');
            })
            .catch(function () { /* ruxsat berilmadi — standart mikrofon ishlatiladi */ });
    };

    TzRecorder.prototype.start = function () {
        var self = this;

        // IKKI MARTA BOSISH HIMOYASI: tugma yashirilishi `getUserMedia` dan
        // keyin bo'ladi, ya'ni shu oraliqda ikkinchi bosish ikki
        // MediaRecorder tug'dirardi va ikki oqim bir navbatga aralashardi.
        if (self.band) { return; }
        self.band = true;
        self.tamom = false;

        if (!navigator.mediaDevices || !window.MediaRecorder) {
            self.band = false;
            self.holat("Brauzer ovoz yozishni qo'llab-quvvatlamaydi", 'text-danger');
            return;
        }

        var sel = qs('tz-audio-device');
        var cheklov = {audio: true};
        if (sel && sel.value) {
            cheklov = {audio: {deviceId: {exact: sel.value}}};
        }

        self.holat('Mikrofon so\'ralmoqda...', 'text-muted');

        navigator.mediaDevices.getUserMedia(cheklov).then(function (stream) {
            self.stream = stream;

            var mime = '';
            ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']
                .some(function (m) {
                    if (MediaRecorder.isTypeSupported(m)) { mime = m; return true; }
                    return false;
                });

            self.sid = '';   // yangi sessiya so'rayapmiz
            return self.qoldiqniYubor().then(function () {
                return fetch(self.url('start', '&mime=' + encodeURIComponent(mime.split(';')[0])), {
                    method: 'POST'
                });
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { throw new Error(j.error || 'boshlab bo\'lmadi'); }

                self.mime = mime;
                self.tarmoq = true;
                self.boshlandi = Date.now();
                self.jurnal = [];
                self.qayd('start', 'mime=' + mime + (j.davom ? ' DAVOM' : '')
                    + ' avlod=' + (j.avlod || 1));
                self.sid = j.sid || '';
                self.gen = j.avlod || 1;
                self.seq = 0;
                self.pauzaJami = 0;

                // Bo'lak kelganda — navbatga. `qayta()` ham shu ishlovchini
                // ishlatadi, shuning uchun alohida saqlaymiz.
                self.dataHandler = function (e) {
                    if (!e.data || e.data.size === 0) { return; }
                    self.oxirgiBolak = Date.now();
                    self.soraldi = false;
                    self.bolakSoni++;
                    self.seq++;
                    self.qayd('bolak', 'seq=' + self.gen + '-' + self.seq
                        + ' ' + Math.round(e.data.size / 1024) + 'KB');
                    // Har 20 bo'lakda jurnalni serverga tashlab turamiz —
                    // brauzer yiqilsa ham sabab bazada qoladi.
                    if (self.bolakSoni % 20 === 0) { self.jurnalYubor(false); }
                    if (self.beaconRejimi && navigator.sendBeacon) {
                        // Oyna yopilmoqda: `fetch` bekor qilinadi, beacon esa
                        // yetib boradi. Tartib muhim emas — server bo'lakni
                        // NOMI bo'yicha joylaydi.
                        navigator.sendBeacon(
                            self.chunkUrl(self.sid, self.gen, self.seq), e.data);
                        return;
                    }
                    var ish = {
                        kalit: self.cfg.lessonid + ':' + self.sid + ':'
                            + self.gen + ':' + self.seq,
                        lessonid: self.cfg.lessonid,
                        sid: self.sid,
                        gen: self.gen,
                        seq: self.seq,
                        blob: e.data
                    };
                    self.navbat.push(ish);

                    // DISKKA YOZAMIZ: oyna yopilsa, brauzer yiqilsa yoki
                    // kompyuter o'chsa ham bo'lak yo'qolmaydi — keyingi
                    // kirishda o'zi yuboriladi.
                    idbSaqla(ish).then(function () {
                        self.diskda = true;
                        // Navbat uzun bo'lsa blobni xotiradan bo'shatamiz:
                        // yuborish paytida diskdan o'qiladi.
                        if (self.navbat.length > XOTIRA_CHEGARA) { ish.blob = null; }
                    }).catch(function (err) {
                        // IndexedDB yo'q (shaxsiy rejim, kvota) — eski
                        // xatti-harakat: bo'lak xotirada qoladi.
                        if (self.diskda !== 'xato') {
                            self.diskda = 'xato';
                            self.qayd('disk-yoq', err && err.message);
                        }
                    });

                    self.navbatniYur();
                };

                self.rec = self.yangiRec(stream);

                // Mikrofon uzilsa (USB sug'urildi, qurilma almashdi) — qayta ulanamiz
                stream.getTracks().forEach(function (t) {
                    t.onended = function () {
                        self.qayd('mikrofon', 'UZILDI (ended)');
                        if (!self.yakunlanmoqda) { self.qayta(); }
                    };
                    // `mute` — mikrofon tizim darajasida o'chirilgan yoki
                    // boshqa ilova olgan. Yozuv davom etadi, lekin JIM bo'ladi.
                    t.onmute = function () { self.qayd('mikrofon', 'O\'CHDI (mute)'); };
                    t.onunmute = function () { self.qayd('mikrofon', 'yoqildi'); };
                });

                self.rec.start(SLICE_MS);

                qs('tz-audio-start').classList.add('hidden');
                qs('tz-audio-stop').classList.remove('hidden');
                qs('tz-audio-stop').disabled = false;
                var bp = qs('tz-audio-pause');
                if (bp) { bp.classList.remove('hidden'); }

                if (j.davom && j.bolaklar) {
                    // Tugallanmagan yozuv topildi: eski bo'laklar O'CHIRILMADI,
                    // shu yozuv ularning ustiga qo'shib boriladi.
                    self.holat('● Avvalgi yozuv davom etmoqda (' + j.bolaklar
                        + ' bo\'lak saqlangan)', 'text-danger');
                }

                self.oxirgiBolak = Date.now();
                self.bolakSoni = 0;
                self.wakeLockOl();
                self.olchovBoshla(stream);

                self.timer = setInterval(function () { self.yangila(); }, 1000);
                // Qorovul ALOHIDA taymer: ko'rsatish taymeri sekinlashsa ham
                // tekshiruv davom etsin.
                self.qorovul = setInterval(function () { self.tekshir(); }, 5000);

                // Sahifa ko'rinadigan holga qaytganda: wake lock'ni qayta
                // olamiz va darhol tekshiramiz — fonda turganda bo'lak
                // kelmagan bo'lishi mumkin.
                self.visHandler = function () {
                    self.qayd('oyna', document.hidden ? 'YASHIRILDI' : 'ko\'rindi');
                    if (!document.hidden) {
                        self.wakeLockOl();
                        self.tekshir();
                    }
                };
                document.addEventListener('visibilitychange', self.visHandler);

                // Internet qaytishi bilan navbatni darhol bo'shatamiz
                self.onlineHandler = function () {
                    self.tarmoq = true;
                    self.qayd('internet', 'qaytdi');
                    clearTimeout(self.navbatTimer);
                    self.navbatniYur();
                };
                window.addEventListener('online', self.onlineHandler);

                self.offlineHandler = function () {
                    self.tarmoq = false;
                    self.qayd('internet', 'UZILDI');
                };
                window.addEventListener('offline', self.offlineHandler);

                // Oyna yopilayotganda oxirgi parchani BEACON bilan jo'natamiz:
                // `fetch` bu paytda bekor qilinadi, `sendBeacon` esa brauzer
                // yopilgandan keyin ham yetib boradi. Shunda yo'qoladigan
                // vaqt 15 soniyadan ~0 ga tushadi.
                self.unloadHandler = function () {
                    self.qayd('oyna', 'yopilmoqda');
                    self.jurnalYubor(true);
                    self.oxirginiJonat();
                };
                window.addEventListener('pagehide', self.unloadHandler);

                window.addEventListener('beforeunload', self.ogohlantir);
            });
        }).catch(function (e) {
            self.band = false;
            self.holat('Boshlab bo\'lmadi: ' + (e && e.message ? e.message : e), 'text-danger');
            var b = qs('tz-audio-start');
            if (b) { b.classList.remove('hidden'); }
        });
    };

    /** Bitta MediaRecorder yaratish (hodisalari bilan). */
    TzRecorder.prototype.yangiRec = function (stream) {
        var self = this;
        var rec;
        try {
            rec = new MediaRecorder(stream,
                self.mime ? {mimeType: self.mime, audioBitsPerSecond: BPS} : undefined);
        } catch (e) {
            rec = new MediaRecorder(stream);
        }
        rec.ondataavailable = self.dataHandler;
        rec.onerror = function () {
            if (!self.yakunlanmoqda && !self.tamom) {
                setTimeout(function () { self.qayta(); }, 2000);
            }
        };
        rec.onstop = function () {
            // Foydalanuvchi to'xtatmagan bo'lsa — o'zi qayta boshlaydi
            if (!self.yakunlanmoqda && !self.tamom) {
                setTimeout(function () { self.qayta(); }, 2000);
            }
        };
        return rec;
    };

    /**
     * Oyna yopilayotganda: yozuvdagi to'planmagan parchani so'rab olib,
     * `sendBeacon` bilan jo'natamiz. Beacon faqat navbat BO'SH bo'lsa
     * ishlatiladi — aks holda bo'laklar tartibi emas, ularning borligi
     * muhim: server ularni nom bo'yicha joylaydi, shuning uchun
     * tartibsiz yetib borishi ham xavfsiz.
     */
    TzRecorder.prototype.oxirginiJonat = function () {
        var self = this;
        if (!self.rec || self.tamom || !navigator.sendBeacon) { return; }
        try {
            if (self.rec.state === 'inactive') { return; }
            // Navbatda qolgan bo'laklarni ham beacon bilan urinib ko'ramiz.
            // Blobi xotirada YO'Q bo'lganlar (uzun navbatda diskka ko'chirilgan)
            // o'tkazib yuboriladi — ular IndexedDB da turadi va keyingi
            // kirishda `qoldiqniYubor()` ularni o'zi jo'natadi. Aks holda
            // beacon BO'SH tana yuborib, server "bo'sh bo'lak" xatosini olardi.
            self.navbat.forEach(function (ish) {
                if (!ish.blob) { return; }
                navigator.sendBeacon(self.chunkUrl(ish.sid, ish.gen, ish.seq), ish.blob);
            });
            // Hozirgi to'planayotgan parcha: `requestData` hodisani
            // ASINXRON chaqiradi, shuning uchun ishlovchini almashtirib
            // qo'yish ishlamaydi (hodisa kelgunicha o'z joyiga qaytib
            // qolardi) — bayroq qo'yamiz, `dataHandler` o'zi beaconga buradi.
            self.beaconRejimi = true;
            self.rec.requestData();
        } catch (e) { /* brauzer ruxsat bermadi — cron yakunlaydi */ }
    };

    /**
     * Qo'lda pauza. 30 soniyadan keyin O'ZI qayta yoqiladi —
     * o'qituvchi pauzani unutib qo'ysa, dars yozilmay qolmasin.
     */
    TzRecorder.prototype.pauza = function () {
        var self = this;
        if (!this.rec || this.rec.state !== 'recording') { return; }

        this.rec.pause();
        this.qayd('pauza', 'qo\'lda');
        this.pauzada = true;
        this.pauzaBoshi = Date.now();
        this.yangila();

        clearTimeout(this.pauzaTimer);
        this.pauzaTimer = setTimeout(function () {
            self.davom(true);
        }, 30000);
    };

    TzRecorder.prototype.davom = function (avto) {
        if (!this.rec || this.rec.state !== 'paused') { return; }
        clearTimeout(this.pauzaTimer);
        this.qayd('davom', avto ? 'avtomatik' : 'qo\'lda');
        this.rec.resume();
        // Pauzada o'tgan vaqt davomiylikka KIRMAYDI — aks holda holat qatori
        // va `duration` haqiqiy ovoz uzunligidan katta chiqardi.
        if (this.pauzaBoshi) {
            this.pauzaJami += Math.floor((Date.now() - this.pauzaBoshi) / 1000);
            this.pauzaBoshi = 0;
        }
        this.pauzada = false;
        this.avtoDavom = !!avto;
        this.yangila();
    };

    /**
     * Yozuv kutilmaganda to'xtab qolsa (qurilma uzildi, brauzer to'xtatdi) —
     * O'ZI qayta boshlaydi.
     *
     * AVLOD OSHADI: yangi MediaRecorder yangi sarlavha bilan boshlanadi,
     * shuning uchun server bu qismni alohida ulashi kerak. Avlod oshmasa
     * tartib raqamlari ustma-ust tushib, yozuv buzilardi.
     */
    TzRecorder.prototype.qayta = function (majburiy) {
        var self = this;
        if (self.yakunlanmoqda || self.tamom) { return; }
        if (self.rec && self.rec.state === 'recording' && !majburiy) {
            return;   // allaqachon tirik
        }

        // MAJBURIY tiklash: yozuvchi "recording" deb turib bo'lak bermayapti.
        // Eskisini to'xtatamiz (oxirgi parchasi `ondataavailable` bilan
        // keladi), lekin `onstop` yana `qayta()` chaqirmasligi kerak.
        if (majburiy && self.rec && self.rec.state !== 'inactive') {
            try {
                self.rec.onstop = null;
                self.rec.onerror = null;
                self.rec.stop();
            } catch (e) { /* allaqachon o'lgan */ }
        }

        self.holat('Yozuv uzildi — qayta ulanmoqda...', 'text-warning');

        var yangi = function (stream) {
            self.stream = stream;
            self.gen++;          // YANGI AVLOD
            self.seq = 0;
            self.rec = self.yangiRec(stream);
            stream.getTracks().forEach(function (t) {
                t.onended = function () {
                    if (!self.yakunlanmoqda) { self.qayta(); }
                };
            });
            self.rec.start(SLICE_MS);
            self.oxirgiBolak = Date.now();
            self.soraldi = false;
            self.pauzada = false;
            self.yangila();
        };

        // Oqim hali tirikmi? Tirik bo'lsa shuni ishlatamiz
        var tirik = self.stream && self.stream.getTracks().some(function (t) {
            return t.readyState === 'live';
        });
        if (tirik) {
            yangi(self.stream);
            return;
        }
        navigator.mediaDevices.getUserMedia({audio: true})
            .then(yangi)
            .catch(function () {
                self.holat('Mikrofonga ulanib bo\'lmadi — 5 soniyadan keyin qayta urinamiz',
                    'text-danger');
                setTimeout(function () { self.qayta(); }, 5000);
            });
    };

    /** Holat qatorini yangilash (taymer har soniyada chaqiradi). */
    TzRecorder.prototype.yangila = function () {
        var pauzada = this.pauzada && this.pauzaBoshi
            ? Math.floor((Date.now() - this.pauzaBoshi) / 1000) : 0;
        var s = Math.floor((Date.now() - this.boshlandi) / 1000)
            - this.pauzaJami - pauzada;
        var matn, sinf;

        // HAQIQIY ovoz: har bo'lak ~15 soniya. Devor soati bilan farq qilsa,
        // yozuv to'xtab qolgan — buni YASHIRMAYMIZ (2026-11-11 saboqi: ekranda
        // "33:00" turgan, faylda esa 3 daqiqa ovoz bor edi).
        var ovoz = Math.floor(this.bolakSoni * SLICE_MS / 1000);
        var farq = s - ovoz;

        if (this.pauzada) {
            matn = '⏸ Pauza — 30 soniyada o\'zi davom etadi';
            sinf = 'text-warning';
        } else if (farq > 60) {
            matn = '⚠ Yozuv to\'xtab qolgan! ovoz ' + fmtVaqt(ovoz)
                + ' / o\'tgan vaqt ' + fmtVaqt(s) + ' — oynani ko\'rinib turadigan '
                + 'joyda qoldiring';
            sinf = 'text-danger font-weight-bold';
        } else {
            matn = '● Yozilmoqda — ' + fmtVaqt(ovoz);
            sinf = 'text-danger';
            var d = this.daraja();
            if (d !== null) {
                if (d < 0.004) {
                    this.jimlikdan = this.jimlikdan || Date.now();
                    if (Date.now() - this.jimlikdan > 60000) {
                        matn += '  ·  ⚠ mikrofon ovoz olmayapti';
                        sinf = 'text-warning font-weight-bold';
                        if (!this.jimlikQayd) {
                            this.jimlikQayd = true;
                            this.qayd('mikrofon', 'JIM — 60s ovoz yo\'q');
                        }
                    }
                } else {
                    if (this.jimlikQayd) {
                        this.qayd('mikrofon', 'ovoz qaytdi');
                        this.jimlikQayd = false;
                    }
                    this.jimlikdan = 0;
                    matn += '  ·  ' + '▁▂▃▄▅▆▇'.charAt(
                        Math.min(6, Math.floor(d * 24))) + ' mikrofon';
                }
            }
        }
        if (this.gen > 1) {
            matn += '  ·  ' + this.gen + '-qism';
        }
        if (this.uzilish) {
            matn += '  ·  ' + this.uzilish + ' marta tiklandi';
        }
        if (this.navbat.length) {
            matn += this.tarmoq === false
                ? '  ·  internet yo\'q, ' + this.navbat.length + ' bo\'lak '
                    + (this.diskda === true ? 'DISKDA saqlandi' : 'navbatda')
                    + ' (yo\'qolmaydi)'
                : '  ·  yuklanmoqda: ' + this.navbat.length;
        }
        this.holat(matn, sinf);

        var b = qs('tz-audio-pause');
        if (b) { b.textContent = this.pauzada ? '▶ Davom ettirish' : '⏸ Pauza'; }
    };

    TzRecorder.prototype.ogohlantir = function (e) {
        e.preventDefault();
        e.returnValue = '';
    };

    TzRecorder.prototype.tozala = function () {
        window.removeEventListener('beforeunload', this.ogohlantir);
        if (this.onlineHandler) {
            window.removeEventListener('online', this.onlineHandler);
        }
        if (this.offlineHandler) {
            window.removeEventListener('offline', this.offlineHandler);
        }
        if (this.unloadHandler) {
            window.removeEventListener('pagehide', this.unloadHandler);
        }
        if (this.visHandler) {
            document.removeEventListener('visibilitychange', this.visHandler);
        }
        clearInterval(this.timer);
        clearInterval(this.qorovul);
        clearTimeout(this.pauzaTimer);
        this.wakeLockQoyib();
        if (this.ac) {
            try { this.ac.close(); } catch (e) { /* allaqachon yopilgan */ }
            this.ac = null;
            this.analyser = null;
        }
    };

    TzRecorder.prototype.stop = function () {
        var self = this;
        if (!this.rec || this.yakunlanmoqda) { return; }

        // Bu bayroq avto-tiklanishni to'xtatadi: endi yozuv ATAYLAB tugatilyapti
        this.yakunlanmoqda = true;
        this.tozala();
        qs('tz-audio-stop').disabled = true;
        var bp = qs('tz-audio-pause');
        if (bp) { bp.classList.add('hidden'); }
        this.holat('Saqlanmoqda, kutib turing...', 'text-info');

        if (this.rec.state === 'paused') {
            this.rec.resume();
            if (this.pauzaBoshi) {
                this.pauzaJami += Math.floor((Date.now() - this.pauzaBoshi) / 1000);
                this.pauzaBoshi = 0;
            }
        }
        var davomiylik = Math.floor((Date.now() - this.boshlandi) / 1000) - this.pauzaJami;

        this.rec.onstop = function () {
            if (self.stream) {
                self.stream.getTracks().forEach(function (t) { t.stop(); });
            }
            // Navbat bo'shaguncha kutamiz — hamma bo'lak yetib borishi shart.
            // Internet yo'q bo'lsa bu uzoq cho'zilishi mumkin; oynani yopib
            // ketilsa ham yozuv yo'qolmaydi — serverdagi cron (`finalize_stale`)
            // tashlab ketilgan bo'laklarni 20 daqiqadan keyin o'zi yig'adi.
            var kut = function () {
                if (self.tamom) { return; }         // qat'iy xato — xabar berilgan
                if (self.navbat.length) {
                    self.holat('Yuklanmoqda... (' + self.navbat.length + ' bo\'lak qoldi'
                        + (self.tarmoq === false ? ', internet kutilmoqda' : '')
                        + ') — oynani yopmang', 'text-info');
                    setTimeout(kut, 1000);
                    return;
                }
                self.qayd('finish', 'bolak=' + self.bolakSoni + ' vaqt=' + davomiylik + 's');
                fetch(self.url('finish', '&duration=' + davomiylik), {
                    method: 'POST',
                    // QORA QUTI yakuniy nusxasi — serverda `diag` ustuniga tushadi
                    body: JSON.stringify({
                        boshlandi: self.boshlandi,
                        bolak: self.bolakSoni,
                        uzilish: self.uzilish,
                        qurilma: navigator.userAgent,
                        jurnal: self.jurnal
                    }),
                    headers: {'Content-Type': 'application/json'}
                })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (!j.ok) { throw new Error(j.error || 'saqlab bo\'lmadi'); }
                        // Hammasi serverda — diskdagi nusxalar endi kerak emas
                        idbTozala(self.cfg.lessonid).catch(function () {});
                        var mb = (j.filesize / 1048576).toFixed(1);
                        var matn = '✔ Saqlandi — ' + fmtVaqt(j.duration || davomiylik)
                            + ', ' + mb + ' MB';
                        if (j.segments > 1) {
                            matn += ' (' + j.segments + ' qismdan ulandi)';
                        }
                        self.holat(matn, 'text-success');
                        if (j.warning) {
                            var w = document.createElement('div');
                            w.className = 'alert alert-warning mt-2 small';
                            w.textContent = 'Diqqat: ' + j.warning;
                            var h = qs('tz-audio-holat');
                            if (h && h.parentNode) { h.parentNode.insertBefore(w, h.nextSibling); }
                        }
                        self.band = false;
                        if (self.cfg.popup) {
                            // Alohida oynada: sahifani yangilamaymiz, chunki
                            // asosiy oynada o'qituvchi ishlayotgan bo'lishi mumkin
                            var b = qs('tz-audio-start');
                            if (b) { b.classList.remove('hidden'); b.textContent = '🎙 Qaytadan yozish'; }
                            var b2 = qs('tz-audio-stop');
                            if (b2) { b2.classList.add('hidden'); b2.disabled = false; }
                            self.yakunlanmoqda = false;
                        } else {
                            setTimeout(function () { window.location.reload(); }, 1200);
                        }
                    })
                    .catch(function (e) {
                        // Yakunlash xatosi — yozuv SERVERDA bor, cron uni
                        // yig'adi. Tugmani qaytaramiz: qayta urinish mumkin.
                        self.holat('Yakunlashda xato: ' + e.message
                            + ' — yozilgan qism serverda, qayta urinib ko\'ring', 'text-danger');
                        qs('tz-audio-stop').disabled = false;
                        self.yakunlanmoqda = false;
                    });
            };
            kut();
        };
        this.rec.stop();
    };

    window.tzAudioInit = function (cfg) {
        var r = new TzRecorder(cfg);
        r.qurilmalar();
        var b1 = qs('tz-audio-start'), b2 = qs('tz-audio-stop'), b3 = qs('tz-audio-pause');
        if (b1) { b1.addEventListener('click', function () { r.start(); }); }
        if (b2) { b2.addEventListener('click', function () { r.stop(); }); }
        if (b3) {
            b3.addEventListener('click', function () {
                if (r.pauzada) { r.davom(false); } else { r.pauza(); }
            });
        }
    };
}());
