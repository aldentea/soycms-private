<?php

error_reporting(0); // Set E_ALL for debuging

//GETの値がなければelfinderを実行させない
if(!isset($_GET["site_id"])) exit;

//ログインしていなければelfinderを実行させない
include_once("../../../../common/common.inc.php");
SOY2::import("util.UserInfoUtil");
if(!UserInfoUtil::isLoggined()) exit;

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';

// Required for MySQL storage connector
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeMySQL.class.php';
// Required for FTP connector support
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeFTP.class.php';


/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
		:  null;                                    // else elFinder decide it itself
}

//SOY CMSとの接続:サイトのパスを取得
SOY2::import("domain.admin.Site");
SOY2::import("domain.admin.SiteDAO");

include_once(SOY2::RootDir() . "/config/db/" . SOYCMS_DB_TYPE . ".php");
SOY2DAOConfig::Dsn(ADMIN_DB_DSN);
SOY2DAOConfig::user(ADMIN_DB_USER);
SOY2DAOConfig::pass(ADMIN_DB_PASS);
$siteDAO = SOY2DAOFactory::create("admin.SiteDAO");
try{
	$site = $siteDAO->getBySiteId($_GET["site_id"]);
}catch(Exception $e){
	exit;
}

$opts = array(
	// 'debug' => true,
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'			=> $site->getPath(),
			'URL'           => $site->getUrl(),
			'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

