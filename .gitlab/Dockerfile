#syntax=docker/dockerfile:1.4

ARG BASE_IMAGE
FROM ${BASE_IMAGE} as base-image

FROM ghcr.io/friendsofshopware/shopware-cli:latest-php-8.2 as build
ARG CI_JOB_TOKEN

COPY --from=base-image /var/www/html /src
WORKDIR /src

COPY . /src/custom/plugins/SwagMigrationAssistant

RUN git clone --depth 1 https://gitlab-ci-token:${CI_JOB_TOKEN}@gitlab.shopware.com/shopware/6/services/testdata.git /src/custom/plugins/SwagMigrationAssistant/tests/testData

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN /src/vendor/bin/composer -d /src/custom/plugins/SwagMigrationAssistant require --no-update 'shopware/core:*' \
    && /src/vendor/bin/composer require --no-plugins --no-scripts -o swag/migration-assistant \
    && rm -Rf /src/var/cache/* \
    && /usr/local/bin/entrypoint.sh shopware-cli project admin-build /src \
    && /usr/local/bin/entrypoint.sh shopware-cli project storefront-build --skip-theme-compile /src \
    && find . -name 'node_modules' -type d -prune -exec rm -rf '{}' + \
    && find . -name '.git' -type d -prune -exec rm -rf '{}' + \
    && rm -Rf /src/public/bundles || true

FROM base-image

COPY --from=build --chown=www-data --link /src /var/www/html

USER root
RUN apk add --no-cache mysql-client

USER www-data

ADD .gitlab/post_install_migration_assistant.sh /usr/local/shopware/post_install.d/99_post_install_migration_assistant.sh
