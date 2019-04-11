#!/usr/bin/env bash

composer dump-autoload
./../../../vendor/shopware/platform/bin/phpstan.phar analyze --level 5 --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php .
