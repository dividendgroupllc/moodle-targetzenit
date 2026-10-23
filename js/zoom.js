/*
 * Jadval o'lchamini moslash (50% – 150%).
 *
 * Nega transform: scale() EMAS: u butun bloknи kichraytiradi, lekin skrol
 * maydoni va sticky ustunlar buziladi. Shuning uchun CSS o'zgaruvchilari
 * orqali katak kengligi va shrift o'lchami o'zgartiriladi — setka haqiqatan
 * ixchamlashadi va bitta ekranga ko'proq kun sig'adi.
 *
 * Tanlov brauzerda eslab qolinadi (localStorage), chunki har o'qituvchining
 * ekrani har xil.
 */
(function () {
    'use strict';

    var DARAJA = {
        50:  {w: 26, f: 0.70, ism: 120},
        75:  {w: 34, f: 0.82, ism: 150},
        100: {w: 46, f: 1.00, ism: 190},
        125: {w: 56, f: 1.12, ism: 220},
        150: {w: 66, f: 1.25, ism: 260}
    };

    function qoy(root, daraja) {
        var d = DARAJA[daraja] || DARAJA[100];
        root.style.setProperty('--tz-cell-w', d.w + 'px');
        root.style.setProperty('--tz-font', d.f + 'rem');
        root.style.setProperty('--tz-name-w', d.ism + 'px');
    }

    window.tzZoomInit = function (kalit) {
        var panel = document.getElementById('tz-zoom');
        if (!panel) { return; }

        var root = document.documentElement;
        var saqlangan = 100;
        try {
            saqlangan = parseInt(localStorage.getItem('tz-zoom-' + kalit), 10) || 100;
        } catch (e) { /* localStorage yopiq bo'lishi mumkin */ }

        var tugmalar = panel.querySelectorAll('[data-zoom]');

        var belgila = function (daraja) {
            qoy(root, daraja);
            Array.prototype.forEach.call(tugmalar, function (b) {
                var bu = parseInt(b.getAttribute('data-zoom'), 10) === daraja;
                b.classList.toggle('btn-primary', bu);
                b.classList.toggle('btn-outline-secondary', !bu);
            });
            try {
                localStorage.setItem('tz-zoom-' + kalit, String(daraja));
            } catch (e) { /* e'tiborsiz */ }
        };

        Array.prototype.forEach.call(tugmalar, function (b) {
            b.addEventListener('click', function () {
                belgila(parseInt(b.getAttribute('data-zoom'), 10));
            });
        });

        belgila(saqlangan);
    };
}());
