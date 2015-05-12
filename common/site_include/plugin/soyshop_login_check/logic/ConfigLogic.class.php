<?php

class ConfigLogic extends SOY2LogicBase{
	
	function ConfigLogic(){
		SOY2::import("site_include.plugin.soyshop_login_check.common.SOYShopLoginCheckCommon");
	}
	
	function getList(){
		$shops = $this->getShopList();
		
		$list = array();
		
		foreach($shops as $shop){
			$list[] = $shop->getSiteId();
		}
		
		return $list;
	}
	
	function getShopList(){
		
		$old = SOYShopLoginCheckCommon::switchDsn();

		$dao = SOY2DAOFactory::create("admin.SiteDAO");
		
		try{
			$sites = $dao->getBySiteType(Site::TYPE_SOY_SHOP);
		}catch(Exception $e){
			$sites = array();
		}
		
		SOYShopLoginCheckCommon::resetDsn($old);
		
		return $sites;
	}
	
}