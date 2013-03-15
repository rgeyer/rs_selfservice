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

spl_autoload_register(function($class) {
  if(0 === strpos($class, 'SelfService')) {
    $path = implode('/', array_slice(explode('\\', $class), 1)) . '.php';
    require_once APPLICATION_PATH . '/../library/SelfService/' . $path;
    return true;
  }
});

require_once APPLICATION_PATH . '/../vendor/autoload.php';

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

if(file_exists(APPLICATION_PATH . '/configs/rsss.ini')) {
	$rsss_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/rsss.ini', 'production');
	$final_config->merge($rsss_config);
}

# This is added to compensate for a broken curl implementation in Zend Studio when debugging or running PHP unit
RGeyer\Guzzle\Rs\Common\ClientFactory::setAdditionalParams(array('curl.CURLOPT_SSL_VERIFYPEER' => false));

date_default_timezone_set('UTC');

$application = new Zend_Application(
		APPLICATION_ENV,
		$final_config
);
