#!/usr/bin/env bash

composer dump-autoload
touch vendor/composer/InstalledVersions.php
php -d pcov.enabled=1 -d pcov.directory=./.. ./../../../vendor/bin/phpunit --configuration phpunit.xml.dist --log-junit ./../../../build/artifacts/phpunit.junit.xml --coverage-clover ./../../../build/artifacts/phpunit.clover.xml --coverage-html ./../../../build/artifacts/coverage-migration-assistant --coverage-text
