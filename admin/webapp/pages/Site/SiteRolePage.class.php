<?php

class SiteRolePage extends CMSUpdatePageBase{

	private $siteId;

	function doPost(){
		$action = SOY2ActionFactory::createInstance("SiteRole.UpdateAction");
    	$result = $action->run();
    	
    	if($result->success()){
			$this->addMessage("UPDATE_SUCCESS");
    		$this->jump("Site.SiteRole.".$this->siteId);
    	}else{
    		$this->jump("Site.SiteRole.".$this->siteId);
    	}
	}

    function SiteRolePage($arg) {
    	
    	$siteId = @$arg[0];
    	if(is_null($siteId)){
    		SOY2PageController::jump("Site");
    	}
    	$this->siteId = $siteId;
    	
    	if(!UserInfoUtil::isDefaultUser()){
    		SOY2PageController::jump("Site");
    	}
    	
    	WebPage::WebPage();
    	
    	$action = SOY2ActionFactory::createInstance("SiteRole.ListAction",array(
    		"siteId" => $arg[0]
    	));
    	$result = $action->run();
    	
    	if($result == SOY2Action::FAILED){
    		SOY2PageController::jump("Site");
    	}
    	
    	$siteRole = $result->getAttribute("siteRole");
    	$userName = $result->getAttribute("adminName");
    	
    	$list = SOY2HTMLFactory::createInstance("SiteRoleList",array(
    		"user" => $userName,
    		"siteId" => $this->siteId
    	));
    	$list->setList($siteRole);
    	
    	$this->add("siterole_block",$list);
    	
    	$this->addForm("siteRoleForm");
    	
    	$siteInfo = $result->getAttribute("siteTitle");
    	$this->createAdd("site_title","HTMLLabel",array(
    		"text" => $siteInfo->getSiteId().CMSMessageManager::get("ADMIN_MASSAGE_ADMIN_LIST")
    	));
    	
    	$this->createAdd("modify_button","HTMLInput",array(
    		"type" => "submit",
    		"value" => CMSMessageManager::get("ADMIN_CHANGE"),
    		"visible" => (count($siteRole)>0)
    	));

		$messages = CMSMessageManager::getMessages();
    	$this->createAdd("message","HTMLLabel",array(
			"text" => implode($messages),
			"visible" => (count($messages)>0)
		));
    	
    }
    
}

class SiteRoleList extends HTMLList{
	
	private $user;
	private $siteId;
	
	function setUser($user){
		$this->user = $user;
	}
	
	function setSiteId($siteId){
		$this->siteId = $siteId;
	}
	
	
	protected function populateItem($entity,$key){
		$this->createAdd("user_name","HTMLLabel",array(
			"text" => $this->user[$key]
		));
		
		$this->createAdd("site_role","HTMLSelect",array(
			"options" => SiteRole::getSiteRoleLists(),
			"name" => "siteRole[".$key."][".$this->siteId."]",
			"indexOrder" => true,
			"selected" => (int)$entity
		));
	}
	
}



?>