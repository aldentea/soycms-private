<?php
include_once("../common/common.inc.php");
include_once('webapp/config.inc.php');
include_once('webapp/config.ext.php');

try{
	SOY2PageController::run();
}catch(Exception $e){
	$exception = $e;
	include_once(SOY2::RootDir() . "error/admin.php");
		
}
