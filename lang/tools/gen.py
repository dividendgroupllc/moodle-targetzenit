#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""strings.py dan lang/{en,uz,ru}/local_targetzenit.php generatsiya qiladi."""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from strings import S                                        # noqa: E402

# gen.py: lang/tools/gen.py -> plagin ildizi uch pog'ona yuqorida
ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
TILLAR = {"en": 0, "uz": 1, "ru": 2}

SARLAVHA = """<?php
// AVTOMATIK GENERATSIYA QILINGAN — QO'LDA TAHRIRLAMANG.
// Manba: lang/tools/strings.py  |  Qayta yozish: python3 lang/tools/gen.py
//
// Uchta til bitta jadvalda yuritiladi, aks holda ular bir-biridan ajralib
// ketadi: kalit bittasiga qo'shilib, boshqasida esdan chiqadi.
//
// `en` — Moodle uchun ZAXIRA til: tarjimasi yo'q kalit shundan olinadi.

defined('MOODLE_INTERNAL') || die();

"""


def tekshir() -> None:
    """Satrlarni yozishdan OLDIN tekshiradi.

    NEGA: emoji'ni `\\ud83c\\udf99` ko'rinishida yozish Python'da yolg'iz
    surrogat hosil qiladi va fayl yozilayotganda tushunarsiz
    `UnicodeEncodeError` beradi — qaysi kalit aybdor ekani ko'rinmaydi.
    Emoji'ni o'zini yozish kerak. Bu tekshiruv kalit nomini aytadi.
    """
    xato = []
    bosh = []
    for kalit, qiymatlar in S.items():
        if len(qiymatlar) != 3:
            xato.append(f"{kalit}: uchta til bo'lishi kerak")
            continue
        for til, q in zip(("en", "uz", "ru"), qiymatlar):
            # Bo'sh qiymat XATO EMAS: ba'zi sozlama tavsiflari ataylab
            # bo'sh qoldirilgan. Faqat bittasi bo'sh, boshqasi to'la
            # bo'lsa — bu tushib qolgan tarjima, shuni aytamiz.
            if not q:
                if any(qiymatlar):
                    bosh.append(f"{kalit} [{til}]")
                continue
            try:
                q.encode("utf-8")
            except UnicodeEncodeError:
                xato.append(
                    f"{kalit} [{til}]: yolg'iz surrogat \u2014 emoji'ni "
                    "\\uXXXX bilan emas, o'zini yozing"
                )
    if bosh:
        print("OGOHLANTIRISH \u2014 tarjimasi tushib qolgan: "
              + ", ".join(bosh))
    if xato:
        raise SystemExit("Til jadvalida xato:\n  " + "\n  ".join(xato))


def php_qator(qiymat: str) -> str:
    """PHP bir qavatli qo'shtirnoq uchun ekranlash."""
    return qiymat.replace("\\", "\\\\").replace("'", "\\'")


def yoz(til: str, idx: int) -> int:
    papka = os.path.join(ROOT, "lang", til)
    os.makedirs(papka, exist_ok=True)
    qatorlar = [SARLAVHA]
    for kalit in sorted(S):
        qatorlar.append(f"$string['{kalit}'] = '{php_qator(S[kalit][idx])}';\n")
    with open(os.path.join(papka, "local_targetzenit.php"), "w", encoding="utf-8") as f:
        f.write("".join(qatorlar))
    return len(S)


if __name__ == "__main__":
    tekshir()
    for til, idx in TILLAR.items():
        n = yoz(til, idx)
        print(f"lang/{til}/local_targetzenit.php — {n} satr")
