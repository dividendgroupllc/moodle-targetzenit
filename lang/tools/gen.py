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
    for til, idx in TILLAR.items():
        n = yoz(til, idx)
        print(f"lang/{til}/local_targetzenit.php — {n} satr")
