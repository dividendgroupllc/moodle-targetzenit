#!/usr/bin/env python3
"""db/install.xml ni Moodle XMLDB qoidalari bo'yicha tekshiradi.

NEGA KERAK (2026-11-07): `install.xml` da takroriy jadval nomi turgan edi va
PHP lint buni ko'rmaydi — XML sintaktik jihatdan to'g'ri. Moodle esa faylni
yuklaganda "Some TABLES name values are incorrect" deb BUTUN yangilanishni
to'xtatadi. Nuqson oylab sezilmay turdi, chunki fayl odatda faqat yangi
o'rnatishda o'qiladi.

Moodle qoidalari (lib/xmldb/xmldb_table.php, xmldb_object::checkNameValues):
  * jadval nomi: ^[a-z][a-z0-9_]*$ va 53 belgidan uzun emas (63 - prefiks 10)
  * jadval nomlari takrorlanmasligi kerak
  * maydon/kalit/indeks nomlari: [a-z0-9_ -] va jadval ichida takrorlanmaydi
"""

import re
import sys
from collections import Counter

MAX_NOM = 63 - 10          # NAME_MAX_LENGTH - PREFIX_MAX_LENGTH
JADVAL_NOM = re.compile(r"^[a-z][a-z0-9_]*$")
ELEMENT_NOM = re.compile(r"^[a-z0-9_ -]+$", re.I)


def tekshir(yol: str) -> list[str]:
    s = open(yol, encoding="utf-8").read()
    xato = []

    nomlar = re.findall(r'<TABLE NAME="([^"]+)"', s)
    for nom, soni in Counter(nomlar).items():
        if soni > 1:
            xato.append(f"TAKRORIY jadval: {nom} ({soni} marta)")
    for nom in nomlar:
        if not JADVAL_NOM.match(nom):
            xato.append(f"jadval nomida yaroqsiz belgi: {nom}")
        if len(nom) > MAX_NOM:
            xato.append(f"jadval nomi uzun: {nom} ({len(nom)} > {MAX_NOM})")

    for blok in re.findall(r'<TABLE NAME="[^"]+".*?</TABLE>', s, re.S):
        jadval = re.search(r'NAME="([^"]+)"', blok).group(1)
        for tur in ("FIELD", "KEY", "INDEX"):
            els = re.findall(rf'<{tur} NAME="([^"]+)"', blok)
            for nom, soni in Counter(els).items():
                if soni > 1:
                    xato.append(f"{jadval}: takroriy {tur} — {nom}")
            for nom in els:
                if not ELEMENT_NOM.match(nom):
                    xato.append(f"{jadval}: {tur} nomida yaroqsiz belgi — {nom}")

    if not nomlar:
        xato.append("jadval topilmadi — fayl buzuqmi?")
    return xato


if __name__ == "__main__":
    yol = sys.argv[1] if len(sys.argv) > 1 else "db/install.xml"
    xatolar = tekshir(yol)
    if xatolar:
        for x in xatolar:
            print(f"::error::{x}")
        sys.exit(1)
    print(f"{yol}: XMLDB qoidalariga mos")
