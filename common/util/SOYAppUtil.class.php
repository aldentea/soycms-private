<?php

class SOYAppUtil {

	public static function checkAppAuth($appId="inquiry"){
		
		$auth = false;
		$useSiteDb = false;
		
		if($appId == "inquiry"){
			$useSiteDb = (defined("SOYINQUIRY_USE_SITE_DB") && SOYINQUIRY_USE_SITE_DB);
		}else{
			$useSiteDb = (defined("SOYMAIL_USE_SITE_DB") && SOYMAIL_USE_SITE_DB);
		}
		
		if($useSiteDb){
			$session = SOY2ActionSession::getUserSession();
			
			//ルート権限の場合、サイト側のデータベースの定数がtrueだったら絶対にtrue
			if($session->getAttribute("isdefault")) return true;
			
			$userId = $session->getAttribute("userid");
			
			$old = self::switchAdminMode();
			
			$appDao = SOY2DAOFactory::create("admin.AppRoleDAO");
			try{
				$appRoles = $appDao->getByUserId($userId);
			}catch(Exception $e){
				$appRoles = array();
			}
			
			self::resetAdminMode($old);
			
			//SOY Appで設定されている権限を調べる
			if(isset($appRoles[$appId]) && $appRoles[$appId]->getAppRole() > 0){
				$auth = true;
			}									
		}

		return $auth;	
	}

	public static function createAppLink($appId="inquiry"){
		//index.phpがある場合はindex.phpの二つ前のディレクトリまで戻る
		if(strpos($_SERVER["REQUEST_URI"], "index.php")!==false){
			$adminPath = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "/index.php"));
		}else{
			$adminPath = $_SERVER["REQUEST_URI"];
		}
		return dirname($adminPath) . "/app/index.php/" . $appId;
	}
	
	private static function switchAdminMode(){
		$old = array();
		
		$old["dsn"] = SOY2DAOConfig::Dsn();
		$old["user"] = SOY2DAOConfig::user();
		$old["pass"] = SOY2DAOConfig::pass();
		
		SOY2DAOConfig::Dsn(ADMIN_DB_DSN);
		SOY2DAOConfig::user(ADMIN_DB_USER);
		SOY2DAOConfig::pass(ADMIN_DB_PASS);
		
		return $old;
	}
	
	private static function resetAdminMode($old){
		
		SOY2DAOConfig::Dsn($old["dsn"]);
		SOY2DAOConfig::user($old["user"]);
		SOY2DAOConfig::pass($old["pass"]);
	}
}
?>