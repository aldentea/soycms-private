<?php

class SOYShopLoginCheckCommon{
	
	/**
	 * SOY Shopがインストールされているか？
	 * @return boolen
	 */
	public static function checkSOYShopInstall(){
		$soyshopRoot = dirname(SOY2::RootDir()) . "/soyshop/"; 
		
		return (file_exists($soyshopRoot));
	}
	
	/**
	 * SOY CMSの管理側のDSNと切り替え
	 */
	public static function switchDsn(){
		$old["dsn"] = SOY2DAOConfig::Dsn();
		$old["user"] = SOY2DAOConfig::user();
		$old["pass"] = SOY2DAOConfig::pass();
		SOY2DAOConfig::Dsn(ADMIN_DB_DSN);
		SOY2DAOConfig::user(ADMIN_DB_USER);
		SOY2DAOConfig::pass(ADMIN_DB_PASS);
		
		return $old;
	}
	
	/**
	 * SOY CMSの管理側の切り替えのリセット
	 */
	public static function resetDsn($old){
		SOY2DAOConfig::Dsn($old["dsn"]);
		SOY2DAOConfig::user($old["user"]);
		SOY2DAOConfig::pass($old["pass"]);
	}
	
	/**
	 * 指定したSOY ShopサイトとのDSNの切り替え
	 */
	public static function switchShopDsn($siteId){
		$old["root"] = SOY2::RootDir();
		$old["dao"] = SOY2DAOConfig::DaoDir();
		$old["entity"] = SOY2DAOConfig::EntityDir();
		$old["cache"] = SOY2DAOConfig::DaoCacheDir();
		$old["dsn"] = SOY2DAOConfig::Dsn();
		$old["user"] = SOY2DAOConfig::user();
		$old["pass"] = SOY2DAOConfig::pass();
		
		$soyshopRoot = dirname($old["root"]) . "/soyshop/"; 
		
		SOY2::RootDir($soyshopRoot . "webapp/src/");
		SOY2DAOConfig::DaoDir(SOY2::RootDir() . "domain/");
		SOY2DAOConfig::EntityDir(SOY2::RootDir() . "domain/");
		SOY2DAOConfig::DaoCacheDir($soyshopRoot . "cache/");
		
		include_once($soyshopRoot . "webapp/conf/shop/" . $siteId . ".conf.php");
		
		SOY2DAOConfig::Dsn(SOYSHOP_SITE_DSN);
		SOY2DAOConfig::user(SOYSHOP_SITE_USER);
		SOY2DAOConfig::pass(SOYSHOP_SITE_PASS);
		
		return $old;
	}
	
	/**
	 * 指定したSOY ShopサイトとのDSNの切り替えのリセット
	 */
	public static function resetShopDsn($old){
		SOY2::RootDir($old["root"]);
		SOY2DAOConfig::DaoDir($old["dao"]);
		SOY2DAOConfig::EntityDir($old["entity"]);
		SOY2DAOConfig::DaoCacheDir($old["cache"]);
		SOY2DAOConfig::Dsn($old["dsn"]);
		SOY2DAOConfig::user($old["user"]);
		SOY2DAOConfig::pass($old["pass"]);
	}
}
?>