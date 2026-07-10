#!/usr/bin/env bash
set -euo pipefail

DUMP_FILE='tests/_fixtures/database/shopware55.sql'
ENV_FILE='../../../.env'
SOURCE_DB='shopware55'

connectionString=''

if [[ -f "$ENV_FILE" ]]; then
    connectionString=$(grep -E '^DATABASE_URL=' "$ENV_FILE" | tail -n1 || true)
fi

connectionString="${connectionString:-DATABASE_URL=${DATABASE_URL:-}}"

url="${connectionString#DATABASE_URL=}"
url="${url%\"}"; url="${url#\"}"
url="${url%\'}"; url="${url#\'}"

if [[ -z "$url" ]]; then
    echo "Could not determine DATABASE_URL (checked $ENV_FILE and the environment)." >&2
    exit 1
fi

rest="${url#*://}"           # user:pass@host:port/dbname?query
creds="${rest%%@*}"          # user:pass
hostpart="${rest#*@}"        # host:port/dbname?query

user="${creds%%:*}"
password=''
[[ "$creds" == *:* ]] && password="${creds#*:}"

hostport="${hostpart%%/*}"   # host:port
host="${hostport%%:*}"
port=3306
[[ "$hostport" == *:* ]] && port="${hostport#*:}"

echo "MySQL host: ${host}:${port}"
echo "MySQL user: ${user}"

mysql_as() {
    local u="$1" p="$2"; shift 2

    if [[ -n "$p" ]]; then
        mysql -u"$u" -p"$p" --host "$host" --port "$port" "$@"
    else
        mysql -u"$u" --host "$host" --port "$port" "$@"
    fi
}

db_exists() {
    local out
    out=$(mysql_as "$1" "$2" -N -e \
        "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${SOURCE_DB}';" \
        2>/dev/null || true)
    [[ "$out" == "$SOURCE_DB" ]]
}

if mysql_as "$user" "$password" < "$DUMP_FILE" 2>/dev/null && db_exists "$user" "$password"; then
    echo "Imported test data into '${SOURCE_DB}' as '${user}'."
    exit 0
fi

adminUser="${MIGRATION_DB_ADMIN_USER:-root}"
adminPassword="${MIGRATION_DB_ADMIN_PASSWORD-}"

echo "User '${user}' cannot provision '${SOURCE_DB}'; using admin '${adminUser}'."

if ! mysql_as "$adminUser" "$adminPassword" -e "SELECT 1;" >/dev/null 2>&1; then
    echo "ERROR: cannot connect as admin '${adminUser}' on ${host}:${port}." >&2
    echo "       Set MIGRATION_DB_ADMIN_USER / MIGRATION_DB_ADMIN_PASSWORD to an account" >&2
    echo "       that may create databases, then re-run." >&2
    exit 1
fi

mysql_as "$adminUser" "$adminPassword" -e "CREATE DATABASE IF NOT EXISTS \`${SOURCE_DB}\`;"

if [[ "$user" != "$adminUser" ]]; then
    hosts=$(mysql_as "$adminUser" "$adminPassword" -N -e \
        "SELECT host FROM mysql.user WHERE user='${user}';")

    for h in $hosts; do
        mysql_as "$adminUser" "$adminPassword" -e \
            "GRANT ALL PRIVILEGES ON \`${SOURCE_DB}\`.* TO '${user}'@'${h}';"
    done

    mysql_as "$adminUser" "$adminPassword" -e "FLUSH PRIVILEGES;"
    echo "Granted '${user}' access to '${SOURCE_DB}'."
fi

mysql_as "$adminUser" "$adminPassword" < "$DUMP_FILE"

if ! db_exists "$user" "$password"; then
    echo "ERROR: '${SOURCE_DB}' does not exist or is not accessible by '${user}' after import." >&2
    exit 1
fi

echo "Imported test data into '${SOURCE_DB}'."
