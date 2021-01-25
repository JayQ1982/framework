<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

use framework\autoloader\Autoloader;
use framework\autoloader\AutoloaderPathModel;
use framework\core\Core;

// Make sure we display all errors that occur during initialization
error_reporting(E_ALL);
@ini_set('display_errors', '1');

// Default timezone patch
$defaultTimezone = 'Greenwich';
$iniTimezone = ini_get('date.timezone');

if (is_string($iniTimezone) && strlen($iniTimezone) > 0) {
	$defaultTimezone = $iniTimezone;
}

date_default_timezone_set($defaultTimezone);

define('REQUEST_TIME', $_SERVER['REQUEST_TIME'] + strtotime(microtime()));

// Use directory separator from system in documentRoot
$documentRoot = str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

// Make sure there is only one trailing slash
$documentRoot = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $documentRoot);

// Framework specific paths
$fwRoot = $documentRoot . 'framework' . DIRECTORY_SEPARATOR;
$siteRoot = $documentRoot . 'site' . DIRECTORY_SEPARATOR;

// Initialize autoloader for classes and interfaces
/** @noinspection PhpIncludeInspection */
require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'Autoloader.php');
$autoloader = new Autoloader($siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'cache.autoload');
$autoloader->register();
/** @noinspection PhpIncludeInspection */
require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'AutoloaderPathModel.php');
$autoloader->addPath(new AutoloaderPathModel(
	'fw-logic',
	$documentRoot,
	AutoloaderPathModel::MODE_NAMESPACE,
	['.class.php', '.php', '.interface.php']
));

$core = new Core($documentRoot, $fwRoot, $siteRoot, $autoloader);
$core->sendResponse();