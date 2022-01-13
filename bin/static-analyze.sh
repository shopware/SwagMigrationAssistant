#!/usr/bin/env bash

php "`dirname \"$0\"`"/phpstan-config-generator.php
composer dump-autoload
php ../../../dev-ops/analyze/vendor/bin/phpstan analyze --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php bin Command Controller Core DependencyInjection Exception Migration Profile Resources Test

# Return if phpstan returns with error
if [ $? -eq 1 ]
then
  exit 1
fi

php ../../../dev-ops/analyze/vendor-bin/psalm/vendor/vimeo/psalm/psalm --config=psalm.xml --threads=$(nproc) --diff --show-info=false
