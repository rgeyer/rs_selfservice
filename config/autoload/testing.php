<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in ZendSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */

return array(
  // Whether or not to enable a configuration cache.
  // If enabled, the merged configuration will be cached and used in
  // subsequent requests.
  //'config_cache_enabled' => false,
  // The key used to create the configuration cache file name.
  //'config_cache_key' => 'module_config_cache',
  // The path in which to cache merged configuration.
  //'cache_dir' =>  './data/cache',
  // ...
  'doctrine' => array(
    'connection' => array(
      'orm_default' => array(
        'driverClass' => 'Doctrine\DBAL\Driver\PDOSqlite\Driver',
        'params' => array(
          'dbname'   => 'rs_selfservice',
        )
      )
    )
  ),
  'rsss' => array(
    'hostname' => '33.33.33.10',
    'cloud_credentials' => array(
      'rightscale' => array(
        'email'       => 'foo@bar.baz',
        'password'    => 'password',
        'account_id'  => '12345'
      ),
      'owners' => array(
        '1875'  => 'Datapipe',
        '1998'  => 'Datapipe',
        '1874'  => 'Datapipe',
        '1999'  => 'Datapipe',
        '2175'  => 'Datapipe',
        '1'     => 'AWS',
        '2'     => 'AWS',
        '3'     => 'AWS',
        '4'     => 'AWS',
        '5'     => 'AWS',
        '6'     => 'AWS',
        '7'     => 'AWS',
        '8'     => 'AWS',
      ),
    ),
  ),
);
