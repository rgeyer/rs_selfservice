#!/bin/bash -e

APPLICATION_ENV=development

doctrine orm:schema-tool:drop --force
doctrine orm:schema-tool:create

/Applications/XAMPP/xamppfiles/bin/php addZendSPProduct.php
/Applications/XAMPP/xamppfiles/bin/php addPhp3TierProduct.php
/Applications/XAMPP/xamppfiles/bin/php addBaseLinuxProduct.php
