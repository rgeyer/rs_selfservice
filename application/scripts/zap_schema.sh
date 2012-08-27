#!/bin/bash -e

APPLICATION_ENV=development

php ./doctrine.php orm:schema-tool:drop --force
php ./doctrine.php orm:schema-tool:create

php addZendSPProduct.php
php addPhp3TierProduct.php
php addBaseLinuxProduct.php
