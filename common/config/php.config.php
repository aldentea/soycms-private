<?php

ini_set("short_open_tag","Off");

mb_language('Japanese');
ini_set("mbstring.internal_encoding","UTF-8");

ini_set("display_errors","Off");
ini_set("log_errors",1);
//error_logを指定しなければApacheのログに残る
if(is_dir(dirname(dirname(__FILE__))."/log") && is_writable(dirname(dirname(__FILE__))."/log")){
	ini_set("error_log",dirname(dirname(__FILE__))."/log/error-".date("Ym").".log");
}

