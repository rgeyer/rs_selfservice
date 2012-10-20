<?php
/* The script pre_activate.php should contain code that should make the changes in the server
 * environment so that the application is fully functional. For example, this may include
 * changing symbolic links to "data" directories from previous to current versions,
 * upgrading an existing DB schema, or setting up a "Down for Maintenance"
 * message on the live version of the application
 * The following environment variables are accessable to the script:
 * 
 * - ZS_RUN_ONCE_NODE - a Boolean flag stating whether the current node is
 *   flagged to handle "Run Once" actions. In a cluster, this flag will only be set when
 *   the script is executed on once cluster member, which will allow users to write
 *   code that is only executed once per cluster for all different hook scripts. One example
 *   for such code is setting up the database schema or modifying it. In a
 *   single-server setup, this flag will always be set.
 * - ZS_WEBSERVER_TYPE - will contain a code representing the web server type
 *   ("IIS" or "APACHE")
 * - ZS_WEBSERVER_VERSION - will contain the web server version
 * - ZS_WEBSERVER_UID - will contain the web server user id
 * - ZS_WEBSERVER_GID - will contain the web server user group id
 * - ZS_PHP_VERSION - will contain the PHP version Zend Server uses
 * - ZS_APPLICATION_BASE_DIR - will contain the directory to which the deployed
 *   application is staged.
 * - ZS_CURRENT_APP_VERSION - will contain the version number of the application
 *   being installed, as it is specified in the package descriptor file
 * - ZS_PREVIOUS_APP_VERSION - will contain the previous version of the application
 *   being updated, if any. If this is a new installation, this variable will be
 *   empty. This is useful to detect update scenarios and handle upgrades / downgrades
 *   in hook scripts
 * - ZS_<PARAMNAME> - will contain value of parameter defined in deployment.xml, as specified by
 *   user during deployment.
 */  

$log = '';

$log .= print_r($_SERVER, true);

$zend_app_dir	= $_SERVER['ZS_APPLICATION_BASE_DIR'] . DIRECTORY_SEPARATOR . 'application';

$log .= $zend_app_dir . "\n";

$config_dir		= $zend_app_dir . DIRECTORY_SEPARATOR . 'configs';
$scripts_dir 	= $zend_app_dir . DIRECTORY_SEPARATOR . 'scripts';
$log_dir 			= $_SERVER['ZS_APPLICATION_BASE_DIR'] . DIRECTORY_SEPARATOR . 'logs';
$proxies_dir	= $zend_app_dir . DIRECTORY_SEPARATOR . 'proxies';

# Generate the DB config file
$db_template_file = $config_dir . DIRECTORY_SEPARATOR . 'db.ini.erb';
$db_config_file = $config_dir . DIRECTORY_SEPARATOR . 'db.ini';
$db_config_str = file_get_contents($db_template_file);

$db_config_str = str_replace('<%= @db_host %>', $_SERVER['ZS_DB_HOST'], $db_config_str);
$db_config_str = str_replace('<%= @db_name %>', $_SERVER['ZS_DB_NAME'], $db_config_str);
$db_config_str = str_replace('<%= @db_user %>', $_SERVER['ZS_DB_USER'], $db_config_str);
$db_config_str = str_replace('<%= @db_pass %>', $_SERVER['ZS_DB_PASS'], $db_config_str);

file_put_contents($db_config_file, $db_config_str);

# Generated the cloud_creds config file
$cloud_template_file = $config_dir . DIRECTORY_SEPARATOR . 'cloud_creds.ini.erb';
$cloud_config_file = $config_dir . DIRECTORY_SEPARATOR . 'cloud_creds.ini';
$cloud_config_str = file_get_contents($cloud_template_file);

$cloud_config_str = str_replace('<%= @rs_email %>', $_SERVER['ZS_RS_EMAIL'], $cloud_config_str);
$cloud_config_str = str_replace('<%= @rs_pass %>', $_SERVER['ZS_RS_PASS'], $cloud_config_str);
$cloud_config_str = str_replace('<%= @rs_acct_num %>', $_SERVER['ZS_RS_ACCTNUM'], $cloud_config_str);
$cloud_config_str = str_replace('<%= @aws_access_key %>', $_SERVER['ZS_AWS_KEY_ID'], $cloud_config_str);
$cloud_config_str = str_replace('<%= @aws_secret_access_key %>', $_SERVER['ZS_AWS_KEY_SECRET'], $cloud_config_str);
$cloud_config_str = str_replace('<%= @datapipe_owner %>', $_SERVER['ZS_DP_OWNER'], $cloud_config_str);

file_put_contents($cloud_config_file, $cloud_config_str);

# Generated the rsss config file
$rsss_template_file = $config_dir . DIRECTORY_SEPARATOR . 'rsss.ini.erb';
$rsss_config_file = $config_dir . DIRECTORY_SEPARATOR . 'rsss.ini';
$rsss_config_str = file_get_contents($rsss_template_file);

$rsss_config_str = str_replace('<%= @hostname %>', $_SERVER['ZS_FQDN'], $rsss_config_str);

file_put_contents($rsss_config_file, $rsss_config_str);

# Allow everybody to write the log
exec("chmod 777 $log_dir");

# Allow everybod to write the proxies
exec("chmod -R 777 $proxies_dir");

# Nuke the schema
$cmdout = '';
$log .= exec("cd $scripts_dir && chmod +x zap_schema.sh && export PATH=\$PATH:/usr/local/zend/bin && ./zap_schema.sh", $cmdout);
$log .= print_r($cmdout, true);

file_put_contents('/tmp/pre_activate.log', $log);