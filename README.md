Travis-CI Build Status [![Build Status](https://travis-ci.org/rgeyer/rs_selfservice.png)](https://travis-ci.org/rgeyer/rs_selfservice)

# Installation instructions

## For a LAMP All-in-One or PHP App server
1. Download and install Composer:

```curl -s http://getcomposer.org/installer | php```

2. Install the composer managed dependencies:

```php composer.phar install```

3. Make sure that /path/to/rsss/logs exists, and is writable by apache2/httpd

4. Copy config/autoload/local.php.dist to config/autoload/local.php, and enter your values.

5. Run the following commands which will create the schema in the DB specified in config/autoload/local.php and populate the 2 "standard" products.

```vendor/bin/doctrine-module odm:schema:create```
```php public/index.php product add baselinux```
```php public/index.php product add php3tier```

6. Enjoy.. Hopefully!

# Usage

## Commandline options

List users

```php /public/index.php users list```

Authorize a user

```php /public/index.php users authorize foo@bar.baz```

(De)Authorize a user

```php /public/index.php users deauthorize foo@bar.baz```


Update Memcached data for RightScale Resources (ServerTemplates, Clouds, InstanceTypes, Datacenters etc)

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

A TODO item here is to include the use of this validation in unit tests, and the actual app

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

# Changelog

## Input Schema

* [46913d1] Bumped version from 1.0 to 1.1.  Removed subnets and added "depends" to prudct inputs. This *would* be a
breaking change for anyone who was consuming 1.0 because all references to "subnet" were removed.  However since I'm
the only consumer at this point I chose to increment the minor version rather than the major.
* [bece7ab] Bumped version from 1.1 to 1.2.  Added a "required_cloud_capability" property for text and select product
inputs.  This allows those to be dependent upon a cloud input which is set to a cloud which supports the cloud
capability which is required for that input.  Think of things like asking for volume size only when the cloud supports
attachable volumes.
* [cf4433c] Bumped version from 1.2 to 1.3.  Added "none" as an option for matching on the "required_cloud_capability" property.
This allows those to appear only when the cloud input does not have the cloud capabilities specified. Think of things
like setting up ROS on clouds which don't support volumes.

# TODO

https://github.com/rgeyer/rs_selfservice/issues

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
