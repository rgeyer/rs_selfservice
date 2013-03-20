[![Build Status](https://travis-ci.org/rgeyer/rs_selfservice.png)](https://travis-ci.org/rgeyer/rs_selfservice)

# Installation instructions

## For a LAMP All-in-One or PHP App server
1. Download and install Composer:

        curl -s http://getcomposer.org/installer | php

2. Install the composer managed dependencies:

        php composer.phar install

3. Make sure that /path/to/rsss/logs exists, and is writable by apache2/httpd

4. Copy config/autoload/local.php.dist to config/autoload/local.php, and enter your values.

5. Run the following commands which will create the schema in the DB specified in config/autoload/local.php and populate the 3 "standard" products.

        vendor/bin/doctrine-module orm:schema-tool:create
        php public/index.php product add

6. Enjoy.. Hopefully!

## For Zend Server (Temporarily deprecated/untested)
1. Download and install Composer:

        curl -s http://getcomposer.org/installer | php

2. Install the composer managed dependencies:

        php composer.phar install

3. Create a Zend Deployment package

        zdpack --src-dir=. pack

4. Deploy using Zend Server/Cluster mechanism of choice

5. Change the vhost for your application to allow overrides, either manually or by changing the templates at /usr/local/zend/share/

TODO:
* Soft deletes for products, provisioned products, perhaps others?
* Fully async provisioning operations. Main page should make ajax call to provision, which should spawn a new process which can be checked in on later, perhaps providing step by step log lines visible to the user.
* Handle "cloud not supported" errors when provisioning servers or arrays
  * Select default InstanceType when not specified
  * Select default Datacenter when not specified
* Tokenize all paths
* Accept (meta, not dashboard) inputs for products.  Customized form during provisioning.  Also self referencing variables
  * Multiple Choice (dropdown)
  * If/Else options, like MySQL 5.1 or 5.5 with logic (use one ST over another depending upon selection)
  * Inputs "2.0" type functionality.  When a particular cloud is chosen, ask for required inputs specific to that cloud etc.
* Accept dashboard inputs for products.
  * As a named credential
  * As a named credential (only in absence of a same named credential)
* Shared sessions - Horizontal Scalability
* Cache commonly read data (user profiles/oauth uris, etc)
  * Frequently query the RS API and cache status for provisioned product show screen(s)
* Allow launching of servers once deployment is created
  * Create launch stages (tiers), launch all LB first, then DB, then App
  * Allow execution of scripts on servers once they become operational
  * Enforce HA best practices by putting multiples of the same server type in different datacenters
* DNS integration to generate DB records etc.
* Support multiple AWS/RS accounts
* In the interest of database normalization, have only 1 record for "deployment_name" product metadata input
* Create a "Product Persister" which persists things in the correct order. SSH Keys -> Security Groups -> Security Group Rules -> Servers -> Arrays Etc.
* Add Authorization functionality (admin, read_all, act_all, read_mine, act_mine, etc)
* Filter cloud menu based on product ST support?
* Make sure ServerTemplate is done importing before starting servers
* Make the windows product work by properly passing inputs into the server or deployment.
* Cleaner handling of failure while provisioning.  Make sure that successfully provisioned stuff gets persisted so that it can be destroyed.
  * Cleaner handling of failure while destroying, make sure that destruction can be re-run until everything is gone.
* Add vendor dependency downloads with composer in pre_activate.php for Zend Server
* Create a meta input for instance type which is filtered/updated based on cloud selection.
* Instrument *as though* it will consume CF
  * Provisioning action(s) hit a controller with json metadata
  * ProvisioningHelper hits controllers with json metadata on success to indicate completion, allowing the RSSS to delete DB records

Icon Pack
http://findicons.com/pack/42/basic - Symbols, basic stuff
http://findicons.com/pack/2580/android_icons - Server Icons
http://findicons.com/pack/1689/splashy - More symbols, candidate for replacing the first icon pack

Handy useful queries..
select
  server_templates.id,
  nickmeta.default_value as nickname,
  vermeta.default_value as version,
  pubmeta.default_value as publication_id
from server_templates
	join product_meta_inputs as nickmeta on (server_templates.nickname_id = nickmeta.id)
	join product_meta_inputs as vermeta on (server_templates.version_id = vermeta.id)
	join product_meta_inputs as pubmeta on (server_templates.publication_id_id = pubmeta.id);