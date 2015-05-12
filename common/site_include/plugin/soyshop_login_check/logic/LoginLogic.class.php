<?php

class LoginLogic extends SOY2LogicBase {
	
	private $siteId;
	
	function LoginkLogic(){
		SOY2::import("site_include.plugin.soyshop_login_check.common.SOYShopLoginCheckCommon");
	}
	
	function getLoginPageUrl(){
		
		//SOY Shopがインストールされていない場合は空文字を返す
		if(!SOYShopLoginCheckCommon::checkSOYShopInstall()) return "";
		
		$old = SOYShopLoginCheckCommon::switchShopDsn($this->siteId);
		
		SOY2::import("domain.config.SOYShop_DataSets");
		include_once(SOY2::RootDir() . "base/func/common.php");
		
		$loginPageUrl = soyshop_get_mypage_url() . "/login";
		
		SOYShopLoginCheckCommon::resetShopDsn($old);
		
		return $loginPageUrl;
	}
	
	function setSiteId($siteId){
		$this->siteId = $siteId;
	}
}
?>