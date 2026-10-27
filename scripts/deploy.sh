#!/usr/bin/env bash
# local_targetzenit production deploy — idempotent va qulflangan (rollback YO'Q: plagin baza yangilanishi qaytmaydi).
#
# DIQQAT: bu skript "bash -s < deploy.sh" orqali yuboriladi — bash uni stdin'dan
# o'qiydi. Shuning uchun butun mantiq main() ichida: bash avval hammasini o'qib
# oladi, keyin "main </dev/null" bajaradi (ichki buyruqlar stdin'ni "yemasin").
set -Eeuo pipefail

main() {

PLUGIN_DIR="${PLUGIN_DIR:-/opt/moodle/public/local/targetzenit}"
MOODLE_DIR="${MOODLE_DIR:-/opt/moodle}"
PHP="${PHP:-/usr/bin/php8.3}"
BRANCH="${DEPLOY_BRANCH:-main}"
HEALTH_URL="${HEALTH_URL:-https://lms.target.erpcontrol.uz/}"
LOCK_FILE="/tmp/deploy-local_targetzenit.lock"

echo "=== Moodle plagin deploy: $(date -Is) | branch=$BRANCH ==="

# --- Oldindan tekshiruvlar ---
[ -d "$PLUGIN_DIR/.git" ] || { echo "XATO: $PLUGIN_DIR git repo emas. Birinchi o'rnatish README bo'yicha qo'lda qilinadi."; exit 1; }
[ -f "$MOODLE_DIR/public/version.php" ] || { echo "XATO: Moodle topilmadi: $MOODLE_DIR"; exit 1; }
command -v "$PHP" >/dev/null || { echo "XATO: $PHP topilmadi"; exit 1; }

# --- Qulf: parallel deploy bloklanadi ---
exec 9>"$LOCK_FILE"
flock -n 9 || { echo "XATO: boshqa deploy hozir ketyapti"; exit 1; }

cd "$PLUGIN_DIR"
PREV_COMMIT=$(git rev-parse HEAD)
REMOTE=$(git remote | head -1)
git fetch "$REMOTE" "$BRANCH"
NEW_COMMIT=$(git rev-parse "$REMOTE/$BRANCH")

if [ "$PREV_COMMIT" = "$NEW_COMMIT" ]; then
    echo "Yangi commit yo'q ($PREV_COMMIT). Deploy shart emas."
    exit 0
fi
echo "Yangilanish: $PREV_COMMIT -> $NEW_COMMIT"

maintenance() {  # on | off
    sudo -u www-data "$PHP" "$MOODLE_DIR/admin/cli/maintenance.php" --"$1" >/dev/null 2>&1 || true
}

apply() {  # kodni joyiga qo'yib, Moodle'ni yangilash
    chown -R root:www-data "$PLUGIN_DIR"
    find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
    find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
    chmod 755 "$PLUGIN_DIR/scripts/deploy.sh" 2>/dev/null || true
    sudo -u www-data "$PHP" "$MOODLE_DIR/admin/cli/upgrade.php" --non-interactive
    sudo -u www-data "$PHP" "$MOODLE_DIR/admin/cli/purge_caches.php"
}

# DIQQAT: kod ORQAGA QAYTARILMAYDI.
# Sabab (2026-10-23 da qoqildik): Moodle plaginining baza yangilanishi
# qaytarilmaydi. Kodni eski commitga tushirsak, bazada versiya yuqori bo'lib
# qoladi va Moodle "Cannot downgrade" deb butunlay ishlamay qoladi.
# Shuning uchun xatoda faqat ogohlantiramiz — tuzatish OLDINGA qarab qilinadi.
fail() {
    trap - ERR
    echo
    echo "!!! DEPLOY XATO BERDI."
    echo "!!! Kod ATAYLAB orqaga qaytarilmadi: plagin baza yangilanishi qaytmaydi,"
    echo "!!! qaytarsak 'Cannot downgrade' bo'lib sayt ishlamay qoladi."
    echo "!!! Joriy commit: $(git -C "$PLUGIN_DIR" rev-parse --short HEAD)"
    echo "!!! Oldingi commit: ${PREV_COMMIT:0:7}"
    echo "!!!"
    echo "!!! Nima qilish kerak:"
    echo "!!!   1) Sayt ochilyaptimi tekshir: $HEALTH_URL"
    echo "!!!   2) nginx/php holati: nginx -t ; systemctl status php8.3-fpm"
    echo "!!!   3) Moodle xatosi: sudo -u www-data $PHP $MOODLE_DIR/admin/cli/upgrade.php --non-interactive"
    echo "!!!   4) Kod aybdor bo'lsa — TUZATIB, versiyani OSHIRIB, qaytadan deploy qil."
    maintenance disable
    echo "=== Deploy MUVAFFAQIYATSIZ: $(date -Is) ==="
    exit 1
}
trap fail ERR

# --- Yangilash (pull EMAS — serverdagi tasodifiy o'zgarish konflikt bermasin) ---
git reset --hard "$REMOTE/$BRANCH"

maintenance enable
apply
maintenance disable

# --- Health check: o'tmasa xato beramiz, lekin kodni qaytarmaymiz ---
# DIQQAT: purge_caches dan keyin BIRINCHI so'rov butun keshni qaytadan quradi —
# 2 vCPU li serverda bu 20-30 soniya olishi mumkin. Shuning uchun kutish uzun:
# qisqa chegara qo'yilsa, aslida sog'lom sayt "o'lgan" deb hisoblanadi
# (2026-10-27 da shunday bo'ldi).
trap - ERR
sleep 10
for i in 1 2 3 4 5; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$HEALTH_URL" || echo 000)
    if [ "$code" = "200" ] || [ "$code" = "303" ] || [ "$code" = "302" ]; then
        echo "Health check OK (HTTP $code)"
        echo "=== Deploy MUVAFFAQIYATLI: $(date -Is) — $NEW_COMMIT ==="
        exit 0
    fi
    echo "Health check $i-urinish: HTTP $code, 15s kutilmoqda..."
    sleep 15
done
echo "!!! Moodle javob bermayapti (kod yangi holatda qoldirildi)"
fail

}

main </dev/null
