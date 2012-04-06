<?php

function loadModelsFromDir($dir, $recursive=false) {
	foreach(scandir($dir) as $modelFile) {
		$fullpath = $dir . '/' . $modelFile;
		if(is_file($fullpath) && preg_match("/\.php$/", $fullpath)) {
			require_once $fullpath;
		}
		
		if($recursive && !preg_match("/^\.{1,2}/", $modelFile) && is_dir($fullpath)) {
			loadModelsFromDir($fullpath, $recursive);
		}
	}
}

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../library'),
		get_include_path(),
)));

require_once 'guzzle/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$classLoader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$classLoader->registerNamespaces(array(
		'Guzzle' => realpath(APPLICATION_PATH . '/../library/guzzle/src'),
		'Doctrine' => realpath(APPLICATION_PATH . '/../library/Doctrine/lib'),
		'Doctrine\DBAL' => realpath(APPLICATION_PATH . '/../library/Doctrine/lib/vendor/doctrine-dbal/lib'),
		'Doctrine\Common' => realpath(APPLICATION_PATH . '/../library/Doctrine/lib/vendor/doctrine-common/lib'),
		'SelfService' => realpath(APPLICATION_PATH . '/../library')
));
$classLoader->register();

/** Smarty */
require_once 'SelfService/SmartyViews/SmartyRsSelfService.php';

/** Zend_Application */
require_once 'Zend/Application.php';
require_once 'Zend/Loader/Autoloader.php';

Zend_Loader_Autoloader::getInstance();

# preload all my models
$modulesDir = APPLICATION_PATH . '/modules';
foreach(scandir($modulesDir) as $module) {
	$moduleDir = $modulesDir . '/' . $module;
	if(preg_match("/^\.{1,2}/", $module) || !is_dir($moduleDir)) { continue; }
	
	$modelsDir = $moduleDir . '/models';
	loadModelsFromDir($modelsDir, true);
}

$modelsDir = APPLICATION_PATH . '/models';
loadModelsFromDir($modelsDir, true);

$final_config = new Zend_Config(array(), true);
$application_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
$final_config->merge($application_config);

if(file_exists(APPLICATION_PATH . '/configs/db.ini')) {
	$db_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/db.ini', 'production');
	$final_config->merge($db_config);
}

if(file_exists(APPLICATION_PATH . '/configs/cloud_creds.ini')) {
	$creds_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/cloud_creds.ini', 'production');
	$final_config->merge($creds_config);
}

$application = new Zend_Application(
		APPLICATION_ENV,
		$final_config
);