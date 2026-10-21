#!/usr/bin/env bash
# local_targetzenit production deploy — idempotent, qulflangan, rollback'li.
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
HEALTH_URL="${HEALTH_URL:-https://moodle.erpcontrol.uz/}"
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

rollback() {
    trap - ERR
    echo "!!! XATO — kod eski holatga qaytarilmoqda: $PREV_COMMIT"
    cd "$PLUGIN_DIR"
    git reset --hard "$PREV_COMMIT"
    apply || true
    maintenance disable
    echo "=== Deploy MUVAFFAQIYATSIZ: $(date -Is) ==="
    exit 1
}
trap rollback ERR

# --- Yangilash (pull EMAS — serverdagi tasodifiy o'zgarish konflikt bermasin) ---
git reset --hard "$REMOTE/$BRANCH"

maintenance enable
apply
maintenance disable

# --- Health check: o'tmasa — bu ham xato, rollback ---
trap - ERR
sleep 3
for i in 1 2 3; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$HEALTH_URL" || echo 000)
    if [ "$code" = "200" ] || [ "$code" = "303" ] || [ "$code" = "302" ]; then
        echo "Health check OK (HTTP $code)"
        echo "=== Deploy MUVAFFAQIYATLI: $(date -Is) — $NEW_COMMIT ==="
        exit 0
    fi
    echo "Health check $i-urinish: HTTP $code, 10s kutilmoqda..."
    sleep 10
done
echo "!!! Moodle javob bermayapti"
rollback

}

main </dev/null
