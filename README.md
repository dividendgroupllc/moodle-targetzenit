# local_targetzenit

Target xususiy maktabi uchun Moodle **local plugin**. Frappe (`target_zenit`) bilan
integratsiyaga tegishli maxsus kod shu yerda yashaydi.

Qoida: **Moodle core kodiga hech qachon tegilmaydi.** Hamma o'zgarish shu papka
ichida bo'ladi — shunda Moodle'ni yangilash (git pull) xavfsiz qoladi.

| | |
|---|---|
| GitHub repo | `dividendgroupllc/moodle-targetzenit` |
| Frankenstyle nomi | `local_targetzenit` |
| Serverdagi joyi | `/opt/moodle/public/local/targetzenit` |
| Moodle | 5.3+ (`$plugin->requires = 2026100500`) |
| Sayt | https://moodle.erpcontrol.uz |

## Nima uchun kerak

Hozir integratsiya Moodle'ning **standart** web service funksiyalari bilan ishlaydi
(`core_user_create_users`, `core_user_update_users`, `core_user_get_users_by_field`) —
Frappe tomonda `target_zenit/integrations/moodle.py`.

Bu plagin kelgusidagi ishlar uchun uy:

- o'z web service funksiyalarimiz (bitta chaqiruvda guruh+kurs+yozilish)
- `db/services.php` orqali "Frappe Sync" servisini **kod bilan** e'lon qilish
  (hozir u qo'lda yaratilgan)
- bahoni Frappe'ga qaytarish uchun hook/task
- maxsus hisobotlar va sozlama sahifalari

## Serverga birinchi o'rnatish (bir marta, root nomidan)

> **Papka nomiga diqqat:** Moodle plagin nomini repo nomidan emas, **clone qilingan
> papka nomidan** oladi. Papka `targetzenit` bo'lishi shart — `version.php` dagi
> `$plugin->component = 'local_targetzenit'` shunga mos kelishi kerak, aks holda
> Moodle "plugin directory name mismatch" xatosini beradi.

```bash
git clone https://github.com/dividendgroupllc/moodle-targetzenit.git \
  /opt/moodle/public/local/targetzenit
chown -R root:www-data /opt/moodle/public/local/targetzenit
find /opt/moodle/public/local/targetzenit -type d -exec chmod 755 {} \;
find /opt/moodle/public/local/targetzenit -type f -exec chmod 644 {} \;

sudo -u www-data php8.3 /opt/moodle/admin/cli/upgrade.php --non-interactive
sudo -u www-data php8.3 /opt/moodle/admin/cli/purge_caches.php
```

Tekshirish: `Site administration → Plugins → Plugins overview` da
**Target Zenit integration** ko'rinadi.

## Keyingi deploylar

`main` ga push qilinganda GitHub Actions o'zi bajaradi:
`lint` (php -l + bash -n + version.php tekshiruvi) → `deploy` (SSH →
`scripts/deploy.sh`).

Skript: qulf oladi → `git reset --hard origin/main` → ruxsatlarni tiklaydi →
maintenance rejimi → `upgrade.php` → `purge_caches.php` → maintenance off →
health check. Xato bo'lsa avtomatik **rollback**.

**GitHub secrets** (repo → Settings → Environments → production):
`SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS`.

> `SSH_USER` — **root** (yoki sudo'li user). `/opt/moodle` root'niki, Frappe'ning
> `frappe` useri unga tegmaydi: ikki tizim alohida OS userda yuradi, bu ataylab
> shunday (ikkita serverdek ishlash qoidasi).

## Versiya raqami

Kod o'zgarganda `version.php` dagi `$plugin->version` ni **oshirish shart**
(`YYYYMMDDXX`, masalan `2026102101`). Oshirilmasa Moodle yangilanishni sezmaydi
va eski kod keshda qolib ketadi.

## Lokal ishlash

Lokal Moodle ham `/opt/moodle` da. Repoyni Moodle ichiga ulash:

```bash
sudo ln -s /home/user/moodle-plugins/targetzenit /opt/moodle/public/local/targetzenit
sudo -u www-data php8.3 /opt/moodle/admin/cli/upgrade.php --non-interactive
```

Symlink bo'lgani uchun tahrir qilganing darhol ko'rinadi; `version.php` ni
oshirganda `upgrade.php` ni qayta ishga tushirasan.
