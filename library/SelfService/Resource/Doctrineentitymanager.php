<?php
/*
Copyright (c) 2011 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class Doctrineentitymanager extends \Zend_Application_Resource_ResourceAbstract
{
	// Default array of options that can be overridden using the application.ini file.
	// e.g., resources.entityManager.autoGenerateProxyClasses = false
	protected $_options = array(
			'connection' => array(
					'driver' 		=> 'pdo_mysql',
					'host' 			=> 'localhost',
					'dbname' 		=> '',
					'user' 			=> 'root',
					'password' 	=> ''),
			'modelDir' => '/models',
			'proxyDir' => '/proxies',
			'proxyNamespace' => 'Proxies',
			'autoGenerateProxyClasses' => true,
			'key' => null
	);

	public function init()
	{
		$options = $this->getOptions();
		
		\SelfService\Cryptographer::$key = $options['key'];

		$config = new \Doctrine\ORM\Configuration;
		$cache = new \Doctrine\Common\Cache\ArrayCache;
		$driverImpl = $config->newDefaultAnnotationDriver($options['modelDir']);

		$config->setMetadataCacheImpl($cache);
		$config->setQueryCacheImpl($cache);
		$config->setProxyDir($options['proxyDir']);
		$config->setProxyNamespace($options['proxyNamespace']);
		$config->setAutoGenerateProxyClasses($options['autoGenerateProxyClasses']);
		$config->setMetadataDriverImpl($driverImpl);

		$em = \Doctrine\ORM\EntityManager::create($options['connection'], $config);		
		
		// Add the encrypted string type for entities and schema generation
		#Doctrine\DBAL\Types\Type::addType('encryptedstring', 'SelfService\Doctrine\DBAL\Types\EncryptedString');
		#$conn = $em->getConnection();
		#$conn->getDatabasePlatform()->registerDoctrineTypeMapping('db_mytype', 'mytype');

		return $em;
	}
}