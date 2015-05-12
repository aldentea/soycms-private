<?php

class LoginCheckLogic extends SOY2LogicBase{
	
	private $siteId;
	
	function LoginCheckLogic(){
		SOY2::import("site_include.plugin.soyshop_login_check.common.SOYShopLoginCheckCommon");
	}
	
	function isLoggedIn(){
		
		static $isLoggedIn;
		
		//SOY Shopがインストールされていない場合は必ずfalse
		if(!SOYShopLoginCheckCommon::checkSOYShopInstall()) return false;

		if(is_null($isLoggedIn)){
			$old = SOYShopLoginCheckCommon::switchShopDsn($this->siteId);
				
			SOY2::import("domain.config.SOYShop_DataSets");
			include_once(SOY2::RootDir() . "base/func/common.php");
			if(!defined("SOYSHOP_CURRENT_MYPAGE_ID")) define("SOYSHOP_CURRENT_MYPAGE_ID", soyshop_get_mypage_id());
				
			SOY2::import("logic.mypage.MyPageLogic");
			$mypage = MyPageLogic::getMyPage();
				
			$isLoggedIn = $mypage->getIsLoggedin();
		
			SOYShopLoginCheckCommon::resetShopDsn($old);
		}
		
		return $isLoggedIn;
	}
	
	function setSiteId($siteId){
		$this->siteId = $siteId;
	}
}
?>