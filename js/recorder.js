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
        this.stream = null;
        this.rec = null;
    }

    TzRecorder.prototype.url = function (action, extra) {
        var u = this.cfg.ajaxurl + '?action=' + action +
            '&lessonid=' + this.cfg.lessonid + '&sesskey=' + this.cfg.sesskey;
        if (this.sid) { u += '&sid=' + encodeURIComponent(this.sid); }
        if (extra) { u += extra; }
        return u;
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
        clearTimeout(this.pauzaTimer);
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
        fetch(this.url('chunk', '&avlod=' + ish.gen + '&seq=' + ish.seq), {
            method: 'POST',
            body: ish.blob,
            headers: {'Content-Type': 'application/octet-stream'}
        }).then(function (r) { return r.json(); })
          .then(function (j) {
              if (!j.ok) {
                  var e = new Error(j.error || 'xato');
                  e.qatiy = !!j.qatiy;
                  throw e;
              }
              self.navbat.shift();
              self.yuklanmoqda = false;
              self.tarmoq = true;
              self.xatolar = 0;
              self.navbatniYur();
          })
          .catch(function (e) {
              self.yuklanmoqda = false;

              if (e && e.qatiy) {
                  // Server aytdi: bu bo'lak HECH QACHON qabul qilinmaydi.
                  self.qatiyXato(e.message);
                  return;
              }

              // Vaqtinchalik: internet yo'q, server band, javob kelmadi.
              // BO'LAK HECH QACHON TASHLANMAYDI — navbatda qoladi va aloqa
              // qaytganda yuboriladi. Yozuvning o'zi davom etaveradi,
              // shuning uchun uzilish paytidagi ovoz YO'QOLMAYDI.
              ish.urinish = (ish.urinish || 0) + 1;
              self.xatolar++;
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
            return fetch(self.url('start', '&mime=' + encodeURIComponent(mime.split(';')[0])), {
                method: 'POST'
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { throw new Error(j.error || 'boshlab bo\'lmadi'); }

                self.mime = mime;
                self.tarmoq = true;
                self.sid = j.sid || '';
                self.gen = j.avlod || 1;
                self.seq = 0;
                self.pauzaJami = 0;

                // Bo'lak kelganda — navbatga. `qayta()` ham shu ishlovchini
                // ishlatadi, shuning uchun alohida saqlaymiz.
                self.dataHandler = function (e) {
                    if (!e.data || e.data.size === 0) { return; }
                    self.seq++;
                    if (self.beaconRejimi && navigator.sendBeacon) {
                        // Oyna yopilmoqda: `fetch` bekor qilinadi, beacon esa
                        // yetib boradi. Tartib muhim emas — server bo'lakni
                        // NOMI bo'yicha joylaydi.
                        navigator.sendBeacon(
                            self.url('chunk', '&avlod=' + self.gen + '&seq=' + self.seq),
                            e.data);
                        return;
                    }
                    self.navbat.push({gen: self.gen, seq: self.seq, blob: e.data});
                    self.navbatniYur();
                };

                self.rec = self.yangiRec(stream);

                // Mikrofon uzilsa (USB sug'urildi, qurilma almashdi) — qayta ulanamiz
                stream.getTracks().forEach(function (t) {
                    t.onended = function () {
                        if (!self.yakunlanmoqda) { self.qayta(); }
                    };
                });

                self.rec.start(SLICE_MS);
                self.boshlandi = Date.now();

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

                self.timer = setInterval(function () { self.yangila(); }, 1000);

                // Internet qaytishi bilan navbatni darhol bo'shatamiz
                self.onlineHandler = function () {
                    self.tarmoq = true;
                    clearTimeout(self.navbatTimer);
                    self.navbatniYur();
                };
                window.addEventListener('online', self.onlineHandler);

                // Oyna yopilayotganda oxirgi parchani BEACON bilan jo'natamiz:
                // `fetch` bu paytda bekor qilinadi, `sendBeacon` esa brauzer
                // yopilgandan keyin ham yetib boradi. Shunda yo'qoladigan
                // vaqt 15 soniyadan ~0 ga tushadi.
                self.unloadHandler = function () { self.oxirginiJonat(); };
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
            // Navbatda qolgan bo'laklarni ham beacon bilan urinib ko'ramiz
            self.navbat.forEach(function (ish) {
                navigator.sendBeacon(
                    self.url('chunk', '&avlod=' + ish.gen + '&seq=' + ish.seq), ish.blob);
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
    TzRecorder.prototype.qayta = function () {
        var self = this;
        if (self.yakunlanmoqda || self.tamom) { return; }
        if (self.rec && self.rec.state === 'recording') { return; }   // allaqachon tirik

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

        if (this.pauzada) {
            matn = '⏸ Pauza — 30 soniyada o\'zi davom etadi';
            sinf = 'text-warning';
        } else {
            matn = '● Yozilmoqda — ' + fmtVaqt(s);
            sinf = 'text-danger';
        }
        if (this.gen > 1) {
            matn += '  ·  ' + this.gen + '-qism';
        }
        if (this.navbat.length) {
            matn += this.tarmoq === false
                ? '  ·  internet yo\'q, ' + this.navbat.length + ' bo\'lak navbatda (yo\'qolmaydi)'
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
        if (this.unloadHandler) {
            window.removeEventListener('pagehide', this.unloadHandler);
        }
        clearInterval(this.timer);
        clearTimeout(this.pauzaTimer);
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
                fetch(self.url('finish', '&duration=' + davomiylik), {method: 'POST'})
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (!j.ok) { throw new Error(j.error || 'saqlab bo\'lmadi'); }
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
