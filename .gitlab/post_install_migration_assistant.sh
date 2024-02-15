#! /bin/bash
# Be careful: there is no bash inside the alpine container by default, so bash syntax doesn't really work here

# PHP to the rescue
php ./custom/plugins/SwagMigrationAssistant/.gitlab/install_test_data.php
