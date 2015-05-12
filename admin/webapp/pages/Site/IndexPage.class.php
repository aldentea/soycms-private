<?php
SOY2::import("domain.admin.Site");

class IndexPage extends CMSWebPageBase{

	function IndexPage(){
		WebPage::WebPage();

		if(!UserInfoUtil::isDefaultUser()){
			DisplayPlugin::hide("only_default_user");
		}

		$this->createAdd("create_link","HTMLLink",array("link"=>SOY2PageController::createLink("Site.Create")));

		$loginableSiteList = $this->getLoginableSiteList();
		$this->createAdd("list", "SiteList", array(
			"list" => $loginableSiteList
		));

		$this->createAdd("no_site","HTMLModel",array(
			"visible" => (count($loginableSiteList)<1)
		));

		$messages = CMSMessageManager::getMessages();
		$errores = CMSMessageManager::getErrorMessages();
    	$this->createAdd("message","HTMLLabel",array(
			"text" => implode($messages),
			"visible" => (count($messages)>0)
		));
		$this->createAdd("error","HTMLLabel",array(
			"text" => implode($errores),
			"visible" => (count($errores)>0)
		));
	}

	/**
	 * 現在のユーザIDからログイン可能なサイトオブジェクトのリストを取得する
	 */
	function getLoginableSiteList(){
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		return $SiteLogic->getSiteByUserId(UserInfoUtil::getUserId());
	}

}

class SiteList extends HTMLList{

	var $domainRootSiteLogic;

	function getDomainRootSiteLogic(){
		if(!$this->domainRootSiteLogic){
			$this->domainRootSiteLogic = SOY2Logic::createInstance("logic.admin.Site.DomainRootSiteLogic");
		}
		return $this->domainRootSiteLogic;
	}

	function replaceTooLongHost($url){

		$array = parse_url($url);

		$host = $array["host"];
		if(isset($array["port"]))$host .=   ":" . $array["port"];

		if(strlen($host)>30){
			$host = mb_strimwidth($host,0,30,"...");
		}

		$url = $array["scheme"] . "://" . $host . $array["path"];

		return $url;

	}

	protected function populateItem($entity){

		$siteName = $entity->getSiteName();
		if($entity->getIsDomainRoot()){
			$siteName = "*" . $siteName;
		}

		$this->add("site_name",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => $siteName
		)));

		$this->createAdd("login_link","HTMLLink",array(
			"link" => $entity->getLoginLink()
		));

		$siteLink = (isset($_SERVER["HTTPS"]) ? "https://" : "http://"). $_SERVER['HTTP_HOST'] . '/' . $entity->getSiteId();
		$this->createAdd("site_link","HTMLLink",array(
			"link" => $entity->getUrl(),
			"text" => $this->replaceTooLongHost($entity->getUrl())
		));

		$rootLink = UserInfoUtil::getSiteURLBySiteId("");
		$this->createAdd("domain_root_site_url","HTMLLink",array(
			"link" => $rootLink,
			"text" => $this->replaceTooLongHost($rootLink),
			"visible" => $entity->getIsDomainRoot()
		));

		$this->createAdd("auth_link","HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.SiteRole.".$entity->getId()),
			"visible" => ($entity->getSiteType()!=Site::TYPE_SOY_SHOP)
		));

		$onclick = 'return confirm("'.CMSMessageManager::get("ADMIN_CONFIRM_DOMAIN_ROOT_SETTING").'");';
		if(file_exists(SOYCMS_TARGET_DIRECTORY."/index.php")){
    		if(true != $this->getDomainRootSiteLogic()->checkCreatedController(SOYCMS_TARGET_DIRECTORY."/index.php")){
    			$onclick = 'return confirm("'.CMSMessageManager::get("ADMIN_CONFIRM_INDEXPHP").'");';
    		}
    	}else if(file_exists(SOYCMS_TARGET_DIRECTORY."/.htaccess")){
    		if(true != $this->getDomainRootSiteLogic()->checkCreatedController(SOYCMS_TARGET_DIRECTORY."/.htaccess")){
    			$onclick = 'return confirm("'.CMSMessageManager::get("ADMIN_CONFIRM_HTACCESS").'");';
    		}
    	}

    	if($entity->getIsDomainRoot()){
    		$this->createAdd("root_site_link","HTMLActionLink",array(
				"link" => SOY2PageController::createLink("Site.SiteRootDetach.".$entity->getId()),
				"text"=>CMSMessageManager::get("ADMIN_ROOT_SETTING_OFF"),
				"onclick"=> 'return confirm("'.CMSMessageManager::get("ADMIN_CONFIRM_ROOT_SETTING_OFF").'");',
			));

    	}else{
	    	$this->createAdd("root_site_link","HTMLActionLink",array(
				"link" => SOY2PageController::createLink("Site.SiteRoot.".$entity->getId()),
				"text"=>CMSMessageManager::get("ADMIN_ROOT_SETTING"),
				"onclick"=> $onclick,
			));
    	}


		$this->createAdd("site_detail_link","HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.Detail.".$entity->getId()),
			"visible" => ($entity->getSiteType()!=Site::TYPE_SOY_SHOP)
		));

		$this->createAdd("remove_link","HTMLLink",array(
			"link"    => SOY2PageController::createLink("Site.Remove.".$entity->getId()),
			"onclick" => $entity->getIsDomainRoot() ? 'alert("'.CMSMessageManager::get("ADMIN_DETACH_ROOT_SETTING_BEFORE_DELETE_SITE").'");return false;' : "",
			"visible" => ($entity->getSiteType()!=Site::TYPE_SOY_SHOP)
		));



	}
}
?>