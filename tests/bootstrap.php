<?php

require_once __DIR__ . '/../application/scripts/bootstrapEnvironment.php';

\RGeyer\Guzzle\Rs\Common\ClientFactory::setCredentials('123', 'foo@bar.baz', 'password');

ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 'On');