Travis-CI Build Status [![Build Status](https://travis-ci.org/rgeyer/rs_selfservice.png)](https://travis-ci.org/rgeyer/rs_selfservice)

# Installation instructions

## For a LAMP All-in-One or PHP App server
1. Download and install Composer:

```curl -s http://getcomposer.org/installer | php```

2. Install the composer managed dependencies:

```php composer.phar install```

3. Make sure that /path/to/rsss/logs exists, and is writable by apache2/httpd

4. Copy config/autoload/local.php.dist to config/autoload/local.php, and enter your values.

5. Run the following commands which will create the schema in the DB specified in config/autoload/local.php and populate the 3 "standard" products.

```vendor/bin/doctrine-module odm:schema:create```
```php public/index.php product add```

6. Enjoy.. Hopefully!

## For Zend Server (Temporarily deprecated/untested)
1. Download and install Composer:

```curl -s http://getcomposer.org/installer | php```

2. Install the composer managed dependencies:

```php composer.phar install```

3. Create a Zend Deployment package

```zdpack --src-dir=. pack```

4. Deploy using Zend Server/Cluster mechanism of choice

5. Change the vhost for your application to allow overrides, either manually or by changing the templates at /usr/local/zend/share/

# Usage

## Commandline options

List users

```php /public/index.php users list```

Authorize a user

```php /public/index.php users authorize foo@bar.baz```

(De)Authorize a user

```php /public/index.php users deauthorize foo@bar.baz```


Update Memcached data for RightScale Resources (ServerTemplates, Clouds, InstanceTypes, etc)

```php /public/index.php cache update rightscale```

Add products from definition files (TODO: Document the description file format) found in /module/SelfService/src/SelfService/Product

```php /public/index.php product add php3tier```

# Developers

## Dependencies

A lot of the json that gets exchanged with (or through) the vending machine has json-schema.org
schema definitions.  Unfortunately the PHP validation tools don't support anywhere near all of
the schema options used in these schemas.

As a result, I wrote a simple python wrapper that is in the bin/ directory.  It accepts two
params.  The first is the schema file you want to validate against and the second is the file
you want to validate.

On OSX you can easily install thusly

```
easy_install pip
sudo pip install jsonschema
```

A TODO item here is to include the use of this validation in unit tests, and make sure that
Travis CI can run python in the PHP test VM.

## Testing

```
cd module/SelfService/test
phpunit
```

# Extending

The RightScale SelfService Vending Machine can be extended in three key ways.

1. Custom products.  Using a well defined JSON schema, new products can be added
2. Custom provisioners. While RSSS can provision products, that responsibility can also be delegated to other tools or systems.
3. Callbacks (Not yet implemented). Througout key lifecycle events of a product, calls can be made from the RSSS to external systems which can alter the results of that lifecycle event.

## Products

## Provisioners

RSSS has an abstract PHP class found at
./module/SelfService/src/Provisioners/AbstractProvisioner which defines the
interface for a provisioner.

When a product is selected in the RSSS UI, a JSON representation of the desired
product is generated.  That JSON representation is passed into the provision()
method of the provisioner.

RSSS provides two provisioner implementations.

1. RsApiProvisioner - This provisioner uses the RightScale API 1.5 to provision
all desired resources for a product.
2. CloudFlowProvisioner - (Not yet implemented) This provisioner delegates the
provisioning to a CloudFlow process which is designed to injest the JSON
representation of the desired product.

To create your own provisioner, create a PHP class which extends
SelfService\Provisioner\AbstractProvisioner and change the rsss/provisioner config
option in your applications ./confi/local.php config file.

# TODO
* Soft deletes for products, provisioned products, perhaps others?
* Fully async provisioning operations. Main page should make ajax call to provision, which should spawn a new process which can be checked in on later, perhaps providing step by step log lines visible to the user.
  * All ajax calls are actually ajax with a progress bar.  Above still needs more love. (Done)
* Accept product inputs which can customize the form during provisioning.
  * Inputs "2.0" type functionality.  When a particular cloud is chosen, ask for required inputs specific to that cloud etc.
* Accept dashboard inputs for products.
  * As a named credential
  * As a named credential (only in absence of a same named credential)
* Shared sessions - Horizontal Scalability
  * Sorta implemented, the authentication bits are stored in memcached, but other session data still stored in "sessions"
  * Refactoring is in order here.  The session management and authentication is scattered across several classes and methods.  Need to create a single service which aggregates and simplifies
* Cache commonly read data (user profiles/oauth uris, etc)
  * Implemented caching for all RS API GET and HEAD calls, but should create a service which smartly (as in can be invalidated when new records are imported etc) caches
    * Clouds (Done, could use an "as index" option)
    * Instance Types (Done)
    * DataCenters (Done)
    * ServerTemplates (Done)
    * MultiCloudImages & Settings?
  * Frequently query the RS API and cache status for provisioned product show screen(s)
  * Create a cache controller to show;
    * Stats
    * Namespaces
    * Objects/keys
    * Clear/invalidate namespaces (Done, in CacheController)
* Allow launching of servers once deployment is created
  * Create launch stages (tiers), launch all LB first, then DB, then App
  * Allow execution of scripts on servers once they become operational
* DNS integration to generate DB records etc.
* Support multiple AWS/RS accounts
* Create a "Product Persister" which persists things in the correct order. SSH Keys -> Security Groups -> Security Group Rules -> Servers -> Arrays Etc.
* Add Authorization functionality (admin, read_all, act_all, read_mine, act_mine, etc)
  * Partly done, users are now either authorized or not, no roles yet (Done)
  * http://opauth.org/
* Filter cloud menu based on product ST support?
* Make sure ServerTemplate is done importing before starting servers
* (Re)add a windows product
* Cleaner handling of failure while provisioning.  Make sure that successfully provisioned stuff gets persisted so that it can be destroyed.
  * Cleaner handling of failure while destroying, make sure that destruction can be re-run until everything is gone.
* Add vendor dependency downloads with composer in pre_activate.php for Zend Server
* Instrument *as though* it will consume CF
  * Provisioning action(s) hit a controller with json metadata
  * ProvisioningHelper hits controllers with json metadata on success to indicate completion, allowing the RSSS to delete DB records
* Importing templates can be tough, and time consuming, possibly batch this when the provisionable product is added to the catalog rather than making the end user wait.
* Allow an Opt-In phone home to allow the tracking of multiple vending machines by a single person/organization
* Integrate with PlanForCloud so that each product can be analyzed for cost forecasting! Brilliant
* Create scheduled reports to show run rates of provisioned products in the RSSS
* CF for generating json from running/created deployment
* CF for performing callbacks to RSSS (async)
* On Products Admin, allow changes to ServerTemplate and revision, which metainputs are requested and their default values.
* Support Queue based arrays
* Support schedules on arrays - Dependency upon rs_guzzle_client being able to make the call with the correct params.
* Support datacenter policy on sarrays - Dependency upon rs_guzzle_client being able to make the call with the correct params.
* Support optimized - Dependency upon rs_guzzle_client to consider this a valid param.
* Implement "subnet" product input
* "API First" Approach
  * API authentication (probably 2-legged OAuth)
  * All views should be calling API methods for form submits etc.
* RsApiProvisioner
  * Support deployment server_tag_scope property
* Refactor
  * MetaInput -> ProductInput
  * All of the odm -> stdClass -> json gyrations should not be tied to the document models.

# Misc Useful stuff
## Icon Pack
http://findicons.com/pack/42/basic - Symbols, basic stuff  
http://findicons.com/pack/2580/android_icons - Server Icons  
http://findicons.com/pack/1689/splashy - More symbols, candidate for replacing the first icon pack

## Queries
select  
  server_templates.id,  
  nickmeta.default_value as nickname,  
  vermeta.default_value as version,  
  pubmeta.default_value as publication_id  
from server_templates  
	join product_meta_inputs as nickmeta on (server_templates.nickname_id = nickmeta.id)  
	join product_meta_inputs as vermeta on (server_templates.version_id = vermeta.id)  
	join product_meta_inputs as pubmeta on (server_templates.publication_id_id = pubmeta.id);
