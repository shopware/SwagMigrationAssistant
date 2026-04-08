#!/bin/bash
set -e

SOURCE_API_USERNAME=${MIGRATION_PROOF_SOURCE_API_USERNAME:-migration-proof-api}
SOURCE_API_PASSWORD=${MIGRATION_PROOF_SOURCE_API_PASSWORD:-migration-proof-api-password}
SOURCE_API_KEY=${MIGRATION_PROOF_SOURCE_API_KEY:-migration-proof-api-key-1234567890abcdef}

/bin/bash /entrypoint.sh &
DOCKWARE_PID=$!

echo "[source-init] Waiting for MySQL..."
until mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1; do
    echo "[source-init] --- MySQL not ready yet"
    sleep 2
done
echo "[source-init] ==> MySQL is ready"

cd /var/www/html

echo "[source-init] Waiting for Shopware database bootstrap..."
until mysql -uroot -proot shopware -e "SELECT 1 FROM s_core_shops LIMIT 1" >/dev/null 2>&1; do
    echo "[source-init] --- Shopware database bootstrap still running"
    sleep 2
done
echo "[source-init] ==> Shopware database bootstrap finished"

echo "[source-init] Importing shopware55.sql into shopware..."
mysql -uroot -proot -e "DROP DATABASE IF EXISTS shopware; CREATE DATABASE shopware CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
sed \
    -e '/^CREATE DATABASE IF NOT EXISTS shopware55;/d' \
    -e '/^USE shopware55;/d' \
    -e '/^SET SQL_REQUIRE_PRIMARY_KEY=off;$/d' \
    /fixtures/shopware55.sql | mysql -uroot -proot shopware
echo "[source-init] shopware55.sql imported"

echo "[source-init] Deactivating plugins not present on filesystem..."
PLUGIN_NAMES=$(
    find /var/www/html/engine/Shopware/Plugins/Default -mindepth 2 -maxdepth 2 -type d 2>/dev/null | xargs -I{} basename {}
    find /var/www/html/custom/plugins -mindepth 1 -maxdepth 1 -type d 2>/dev/null | xargs -I{} basename {}
)
IN_CLAUSE=$(echo "$PLUGIN_NAMES" | sort -u | awk '{printf "'"'"'%s'"'"',",$0}' | sed 's/,$//')

mysql -uroot -proot shopware -e "UPDATE s_core_plugins SET active=0, installation_date=NULL WHERE name NOT IN ($IN_CLAUSE);" 2>/dev/null
mysql -uroot -proot shopware -e "DELETE FROM s_core_subscribes WHERE pluginID NOT IN (SELECT id FROM s_core_plugins WHERE active=1);" 2>/dev/null
echo "[source-init] Filesystem-absent plugins deactivated"

echo "[source-init] Setting up SwagMigrationConnector..."

echo "[source-init] Running sw:plugin:refresh"
php bin/console sw:plugin:refresh
echo "[source-init] sw:plugin:refresh finished"

echo "[source-init] Running sw:plugin:activate SwagMigrationConnector"
ACTIVATE_OUTPUT=$(php bin/console sw:plugin:activate SwagMigrationConnector 2>&1) || ACTIVATE_EXIT=$?

printf '%s\n' "$ACTIVATE_OUTPUT"

if [ "${ACTIVATE_EXIT:-0}" -ne 0 ] && ! printf '%s\n' "$ACTIVATE_OUTPUT" | grep -q 'already activated'; then
    exit "${ACTIVATE_EXIT}"
fi
echo "[source-init] sw:plugin:activate finished"

echo "[source-init] Running sw:cache:clear"
php bin/console sw:cache:clear
echo "[source-init] sw:cache:clear finished"

echo "[source-init] SwagMigrationConnector ready"

echo "[source-init] Reducing source database for proof..."
mysql -uroot -proot shopware </fixtures/reduce-database-for-proof.sql
echo "[source-init] Source database reduced for proof"

echo "[source-init] Clearing cache after database reduction..."
php bin/console sw:cache:clear
echo "[source-init] Cache cleared"

echo "[source-init] Bootstrapping API credentials..."
mysql -uroot -proot shopware <<SQL
INSERT INTO s_core_auth (roleID, localeID, username, password, encoder, apiKey, name, email, active)
VALUES (
    (SELECT id FROM s_core_auth_roles ORDER BY id LIMIT 1),
    (SELECT id FROM s_core_locales ORDER BY id LIMIT 1),
    '$SOURCE_API_USERNAME',
    MD5('$SOURCE_API_PASSWORD'),
    'LegacyBackendMd5',
    '$SOURCE_API_KEY',
    'Migration Proof',
    'migration-proof@example.com',
    1
);
SQL

touch /tmp/source-ready
echo "[source-init] Source setup complete"

wait $DOCKWARE_PID
