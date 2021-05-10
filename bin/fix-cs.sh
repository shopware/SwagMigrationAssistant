#!/usr/bin/env bash
echo "Fix php files"
php ../../../dev-ops/analyze/vendor/bin/ecs check --fix --config=../../../vendor/shopware/platform/easy-coding-standard.php bin Command Controller Core DependencyInjection Exception Migration Profile Resources Test SwagMigrationAssistant.php
php ../../../dev-ops/analyze/vendor/bin/ecs check --fix --config=easy-coding-standard.php

echo "Fix javascript files"
../../../vendor/shopware/platform/src/Administration/Resources/app/administration/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/.eslintrc.js --ext .js,.vue --fix .
