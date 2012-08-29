# Installation instructions

1. Download and install Composer:

        curl -s http://getcomposer.org/installer | php

2. Install the composer managed dependencies:

        php composer.phar install

3. Install the pear/pyrus managed dependencies:

        pear channel-discover zend.googlecode.com/svn
        pear install zend/zend-1.11.11

4. Install Smarty someplace, and add it to your PHP include_path

5. Make sure that /path/to/rsss/logs exists, and is writable by apache2/httpd

6. Copy application/configs/db.ini.tpl and application/configs/cloud_creds.ini.tpl to their *.ini equivalents, and enter your values.

7. Run application/scripts/zap_schema.sh script which will create the schema in the DB specified in application/configs/db.ini and populate the 3 "standard" products.

8. Edit library/SelfService/GoogleAuthAdapter.php to use your servers hostname/ip rather than "local.rsss.com"

9. Enjoy.. Hopefully!

DEPENDENCIES:
In addition to the dependencies defined in composer.json, these don't play well with composer and will need to be installed manually

* Zend Framework 1.11.11
* Smarty 3.1.11

TODO:
* Create and verify support for SQLite for dev and test
* Soft deletes for products, provisioned products, perhaps others?
* Fully async provisioning operations. Main page should make ajax call to provision, which should spawn a new process which can be checked in on later, perhaps providing step by step log lines visible to the user.
* Handle "cloud not supported" errors when provisioning servers or arrays
  * Be smarter about picking the MCI that supports the requested cloud.
  * Initial provisioning succeeds without issue. Will not be able to launch
* Tokenize all paths
* Refactor
  * Lots of confusion of methods in controllers that are unrelated, not enough controllers, etc
  * Build services for common tasks like product provisioning and deletion, and user interactions (fetch, cache, create) 
* Accept (meta, not dashboard) inputs for products.  Customized form during provisioning.  Also self referencing variables
  * Multiple Choice (dropdown)
  * If/Else options, like MySQL 5.1 or 5.5 with logic (use one ST over another depending upon selection)
  * Inputs "2.0" type functionality.  When a particular cloud is chosen, ask for required inputs specific to that cloud etc.
* Accept dashboard inputs for products.
  * As a named credential
  * As a named credential (only in absence of a same named credential)
* Shared sessions - Horizontal Scalability
* Cache commonly read data (user profiles/oauth uris, etc)
* Use API 1.5 publishing API's for getting correct ServerTemplates
* Use API 1.5 to support additional clouds
  * In progress...
* Allow launching of servers once deployment is created
  * Create launch stages (tiers), launch all LB first, then DB, then App
  * Allow execution of scripts on servers once they become operational
* DNS integration to generate DB records etc.
* Support multiple AWS/RS accounts
* In the interest of database normalization, have only 1 record for "deployment_name" product metadata input

Icon Pack
http://findicons.com/pack/42/basic