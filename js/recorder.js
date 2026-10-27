/*
 * Dars audio yozuvi — brauzer tomoni.
 *
 * MediaRecorder har 15 soniyada bo'lak beradi, bo'lak DARHOL serverga ketadi.
 * Shuning uchun brauzer yopilsa, telefon uxlasa yoki internet uzilsa ham
 * yozilgan qism serverda qoladi — faqat oxirgi 15 soniya yo'qoladi.
 *
 * Yuklash NAVBAT bilan: bo'laklar tartibi buzilmasligi kerak, chunki server
 * ularni tartib raqami bo'yicha bayt-ba-bayt birlashtiradi.
 */
(function () {
    'use strict';

    var SLICE_MS = 15000;      // bo'lak uzunligi
    var BPS = 24000;           // 24 kbps mono opus ~ 8 MB / 45 daqiqa
    var RETRY = 3;

    function qs(id) { return document.getElementById(id); }

    function fmtVaqt(s) {
        var m = Math.floor(s / 60), q = s % 60;
        return m + ':' + (q < 10 ? '0' : '') + q;
    }

    function TzRecorder(cfg) {
        this.cfg = cfg;
        this.seq = 0;
        this.navbat = [];
        this.yuklanmoqda = false;
        this.boshlandi = 0;
        this.timer = null;
        this.stream = null;
        this.rec = null;
    }

    TzRecorder.prototype.url = function (action, extra) {
        var u = this.cfg.ajaxurl + '?action=' + action +
            '&lessonid=' + this.cfg.lessonid + '&sesskey=' + this.cfg.sesskey;
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

    TzRecorder.prototype.navbatniYur = function () {
        var self = this;
        if (this.yuklanmoqda || !this.navbat.length) { return; }
        this.yuklanmoqda = true;

        var ish = this.navbat[0];
        fetch(this.url('chunk', '&seq=' + ish.seq), {
            method: 'POST',
            body: ish.blob,
            headers: {'Content-Type': 'application/octet-stream'}
        }).then(function (r) { return r.json(); })
          .then(function (j) {
              if (!j.ok) { throw new Error(j.error || 'xato'); }
              self.navbat.shift();
              self.yuklanmoqda = false;
              self.tarmoq = true;
              self.navbatniYur();
          })
          .catch(function () {
              ish.urinish = (ish.urinish || 0) + 1;
              self.yuklanmoqda = false;
              self.tarmoq = false;
              // BO'LAK HECH QACHON TASHLANMAYDI — navbatda qoladi va internet
              // qaytganda yuboriladi. Yozuvning o'zi esa davom etaveradi,
              // shuning uchun uzilish paytidagi ovoz YO'QOLMAYDI.
              setTimeout(function () { self.navbatniYur(); }, 3000);
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
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            self.holat("Brauzer ovoz yozishni qo'llab-quvvatlamaydi", 'text-danger');
            return;
        }

        var sel = qs('tz-audio-device');
        var cheklov = {audio: true};
        if (sel && sel.value) {
            cheklov = {audio: {deviceId: {exact: sel.value}}};
        }

        navigator.mediaDevices.getUserMedia(cheklov).then(function (stream) {
            self.stream = stream;

            var mime = '';
            ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']
                .some(function (m) {
                    if (MediaRecorder.isTypeSupported(m)) { mime = m; return true; }
                    return false;
                });

            return fetch(self.url('start', '&mime=' + encodeURIComponent(mime.split(';')[0])), {
                method: 'POST'
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { throw new Error(j.error || 'boshlab bo\'lmadi'); }

                self.mime = mime;
                self.tarmoq = true;

                // Bo'lak kelganda — navbatga. `qayta()` ham shu ishlovchini
                // ishlatadi, shuning uchun alohida saqlaymiz.
                self.dataHandler = function (e) {
                    if (e.data && e.data.size > 0) {
                        self.seq++;
                        self.navbat.push({seq: self.seq, blob: e.data});
                        self.navbatniYur();
                    }
                };

                self.rec = new MediaRecorder(stream,
                    mime ? {mimeType: mime, audioBitsPerSecond: BPS} : undefined);
                self.rec.ondataavailable = self.dataHandler;
                self.rec.onerror = function () { setTimeout(function () { self.qayta(); }, 2000); };
                self.rec.onstop = function () {
                    // Foydalanuvchi to'xtatmagan bo'lsa — o'zi qayta boshlaydi
                    if (!self.yakunlanmoqda) { setTimeout(function () { self.qayta(); }, 2000); }
                };

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
                var bp = qs('tz-audio-pause');
                if (bp) { bp.classList.remove('hidden'); }

                self.timer = setInterval(function () { self.yangila(); }, 1000);

                // Internet qaytishi bilan navbatni darhol bo'shatamiz
                self.onlineHandler = function () {
                    self.tarmoq = true;
                    self.navbatniYur();
                };
                window.addEventListener('online', self.onlineHandler);

                window.addEventListener('beforeunload', self.ogohlantir);
            });
        }).catch(function (e) {
            self.holat('Mikrofonga ruxsat berilmadi: ' + e.message, 'text-danger');
        });
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
        this.yangila();

        clearTimeout(this.pauzaTimer);
        this.pauzaTimer = setTimeout(function () {
            self.davom(true);
        }, 30000);
    };

    TzRecorder.prototype.davom = function (avto) {
        if (!this.rec || this.rec.state !== 'paused') { return; }
        clearTimeout(this.pauzaTimer);
        this.rec.pause ? this.rec.resume() : null;
        this.pauzada = false;
        this.avtoDavom = !!avto;
        this.yangila();
    };

    /**
     * Yozuv kutilmaganda to'xtab qolsa (qurilma uzildi, brauzer to'xtatdi) —
     * O'ZI qayta boshlaydi. Sessiya o'sha-o'sha, bo'lak raqami davom etadi,
     * shuning uchun serverda hamma narsa ketma-ket birikadi.
     */
    TzRecorder.prototype.qayta = function () {
        var self = this;
        if (self.yakunlanmoqda) { return; }

        self.holat('Yozuv uzildi — qayta ulanmoqda...', 'text-warning');

        var yangi = function (stream) {
            self.stream = stream;
            try {
                self.rec = new MediaRecorder(stream,
                    self.mime ? {mimeType: self.mime, audioBitsPerSecond: BPS} : undefined);
            } catch (e) {
                self.rec = new MediaRecorder(stream);
            }
            self.rec.ondataavailable = self.dataHandler;
            self.rec.onerror = function () { setTimeout(function () { self.qayta(); }, 2000); };
            self.rec.onstop = function () {
                if (!self.yakunlanmoqda) { setTimeout(function () { self.qayta(); }, 2000); }
            };
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
        var s = Math.floor((Date.now() - this.boshlandi) / 1000) - (this.pauzaJami || 0);
        var matn, sinf;

        if (this.pauzada) {
            matn = '⏸ Pauza — 30 soniyada o\'zi davom etadi';
            sinf = 'text-warning';
        } else {
            matn = '● Yozilmoqda — ' + fmtVaqt(s);
            sinf = 'text-danger';
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

    TzRecorder.prototype.stop = function () {
        var self = this;
        if (!this.rec) { return; }

        // Bu bayroq avto-tiklanishni to'xtatadi: endi yozuv ATAYLAB tugatilyapti
        this.yakunlanmoqda = true;
        clearTimeout(this.pauzaTimer);

        window.removeEventListener('beforeunload', this.ogohlantir);
        window.removeEventListener('online', this.onlineHandler);
        clearInterval(this.timer);
        qs('tz-audio-stop').disabled = true;
        this.holat('Saqlanmoqda, kutib turing...', 'text-info');

        if (this.rec.state === 'paused') {
            this.rec.resume();
        }
        var davomiylik = Math.floor((Date.now() - this.boshlandi) / 1000);

        this.rec.onstop = function () {
            if (self.stream) {
                self.stream.getTracks().forEach(function (t) { t.stop(); });
            }
            // Navbat bo'shaguncha kutamiz — hamma bo'lak yetib borishi shart
            var kut = function () {
                if (self.navbat.length) {
                    self.holat('Yuklanmoqda... (' + self.navbat.length + ' bo\'lak qoldi)', 'text-info');
                    setTimeout(kut, 1000);
                    return;
                }
                fetch(self.url('finish', '&duration=' + davomiylik), {method: 'POST'})
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (!j.ok) { throw new Error(j.error || 'saqlab bo\'lmadi'); }
                        var mb = (j.filesize / 1048576).toFixed(1);
                        self.holat('✔ Saqlandi — ' + fmtVaqt(davomiylik) + ', ' + mb + ' MB', 'text-success');
                        if (self.cfg.popup) {
                            // Alohida oynada: sahifani yangilamaymiz, chunki
                            // asosiy oynada o'qituvchi ishlayotgan bo'lishi mumkin
                            var b = qs('tz-audio-start');
                            if (b) { b.classList.remove('hidden'); b.textContent = '🎙 Qaytadan yozish'; }
                            var b2 = qs('tz-audio-stop');
                            if (b2) { b2.classList.add('hidden'); b2.disabled = false; }
                        } else {
                            setTimeout(function () { window.location.reload(); }, 1200);
                        }
                    })
                    .catch(function (e) {
                        self.holat('Xato: ' + e.message, 'text-danger');
                        qs('tz-audio-stop').disabled = false;
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
