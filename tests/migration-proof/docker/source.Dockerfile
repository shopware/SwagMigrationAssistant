ARG VERSION=5.7.14
FROM dockware/dev:${VERSION}

ARG CONNECTOR_URL=https://github.com/shopware/SwagMigrationConnector/archive/refs/tags/2.2.0.zip

RUN curl -fsSL "$CONNECTOR_URL" -o /tmp/connector.zip \
    && unzip -q /tmp/connector.zip -d /tmp/connector \
    && mkdir -p /var/www/html/custom/plugins \
    && PLUGIN_DIR=$(find /tmp/connector -mindepth 1 -maxdepth 1 -type d | head -1) \
    && mv "$PLUGIN_DIR" /var/www/html/custom/plugins/SwagMigrationConnector \
    && rm -rf /tmp/connector.zip /tmp/connector

COPY --chmod=755 migration-proof/docker/source-init.sh /source-init.sh
COPY _fixtures/database/shopware55.sql /fixtures/shopware55.sql
COPY migration-proof/docker/reduce-database-for-proof.sql /fixtures/reduce-database-for-proof.sql

ENTRYPOINT ["/source-init.sh"]
