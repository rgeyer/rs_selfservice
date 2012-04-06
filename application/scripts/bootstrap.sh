#!/bin/bash -e
APPLICATION_ENV=development doctrine orm:schema-tool:drop --force
APPLICATION_ENV=development doctrine orm:schema-tool:create
/Applications/XAMPP/xamppfiles/bin/php addZendSPProduct.php