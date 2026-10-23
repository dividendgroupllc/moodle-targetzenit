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
              self.navbatniYur();
          })
          .catch(function () {
              ish.urinish = (ish.urinish || 0) + 1;
              self.yuklanmoqda = false;
              if (ish.urinish >= RETRY) {
                  // Tashlab ketmaymiz — navbatda qoldiramiz va keyin yana urinamiz
                  self.holat("Tarmoq uzilgan — qayta urinilmoqda...", 'text-warning');
              }
              setTimeout(function () { self.navbatniYur(); }, 3000);
          });
    };

    TzRecorder.prototype.start = function () {
        var self = this;
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            self.holat("Brauzer ovoz yozishni qo'llab-quvvatlamaydi", 'text-danger');
            return;
        }

        navigator.mediaDevices.getUserMedia({audio: true}).then(function (stream) {
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

                self.rec = new MediaRecorder(stream,
                    mime ? {mimeType: mime, audioBitsPerSecond: BPS} : undefined);

                self.rec.ondataavailable = function (e) {
                    if (e.data && e.data.size > 0) {
                        self.seq++;
                        self.navbat.push({seq: self.seq, blob: e.data});
                        self.navbatniYur();
                    }
                };
                self.rec.start(SLICE_MS);
                self.boshlandi = Date.now();

                qs('tz-audio-start').classList.add('hidden');
                qs('tz-audio-stop').classList.remove('hidden');

                self.timer = setInterval(function () {
                    var s = Math.floor((Date.now() - self.boshlandi) / 1000);
                    self.holat('● Yozilmoqda — ' + fmtVaqt(s) +
                        (self.navbat.length ? '  (yuklanmoqda: ' + self.navbat.length + ')' : ''),
                        'text-danger');
                }, 1000);

                window.addEventListener('beforeunload', self.ogohlantir);
            });
        }).catch(function (e) {
            self.holat('Mikrofonga ruxsat berilmadi: ' + e.message, 'text-danger');
        });
    };

    TzRecorder.prototype.ogohlantir = function (e) {
        e.preventDefault();
        e.returnValue = '';
    };

    TzRecorder.prototype.stop = function () {
        var self = this;
        if (!this.rec) { return; }

        window.removeEventListener('beforeunload', this.ogohlantir);
        clearInterval(this.timer);
        qs('tz-audio-stop').disabled = true;
        this.holat('Saqlanmoqda, kutib turing...', 'text-info');

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
        var b1 = qs('tz-audio-start'), b2 = qs('tz-audio-stop');
        if (b1) { b1.addEventListener('click', function () { r.start(); }); }
        if (b2) { b2.addEventListener('click', function () { r.stop(); }); }
    };
}());
