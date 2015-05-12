<?php

class DetailPage extends CMSUpdatePageBase{
	
	var $id;
	
	function doPost(){
		
		try{
			$siteDAO = SOY2DAOFactory::create("admin.SiteDAO");
			$site = $siteDAO->getById($this->id);
			$site->setUrl(@$_POST["siteUrl"]);
			
			$siteDAO->update($site);
			
			//ファイルDBの更新
			$this->updateFileDB();
			
			//キャッシュ削除
			CMSUtil::unlinkAllIn($site->getPath().".cache/");
			
			$this->addMessage("UPDATE_SUCCESS");
			
		}catch(Exception $e){
			
		}
		
		$this->jump("Site.Detail." . $this->id);
		
	}
	
	function DetailPage($args) {
    		
    	if(!UserInfoUtil::isDefaultUser() || count($args)<1){
    		//デフォルトユーザのみ変更可能
    		$this->jump("Site");
    		exit;
    	}
    	
    	$this->id = $args[0];
    	
    	WebPage::WebPage();
		
		$this->addForm("update_site_form");
		
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		$site = $SiteLogic->getById($this->id);
		
		if(!$site){
			$this->jump("Site");
		}
		
		$this->createAdd("site_name_title","HTMLLabel",array(
			"text" => $site->getSiteName()
		));
		
		$this->createAdd("site_name","HTMLLabel",array(
			"text" => $site->getSiteName()
		));
		
		$this->createAdd("site_id","HTMLLabel",array(
			"text" => $site->getSiteId()
		));
		
		//SOY CMS
		$this->createAdd("site_url_soycms","HTMLInput",array(
			"value" => $site->getUrl(),
			"name" => "siteUrl"
		));
		
		$this->createAdd("display_soycms", "HTMLModel", array(
			"visible" => ($site->getSiteType() == Site::TYPE_SOY_CMS)
		));
		
		
		//SOY Shop
		$this->createAdd("site_url_shop","HTMLInput",array(
			"value" => $site->getUrl(),
			"name" => "siteUrl",
			"disabled" => "disabled"
		));

		$this->createAdd("display_soyshop", "HTMLModel", array(
			"visible" => ($site->getSiteType() == Site::TYPE_SOY_SHOP)
		));

		
		$this->createAdd("default_url","HTMLLabel",array(
			"text" => UserInfoUtil::getSiteURLBySiteId($site->getSiteId())
		));
		
		$messages = CMSMessageManager::getMessages();
    	$this->createAdd("message","HTMLLabel",array(
			"text" => implode($messages),
			"visible" => (count($messages)>0)
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
		
		$this->createAdd("regenerate_link","HTMLActionLink",array(
			"link"    => SOY2PageController::createLink("Site.CreateController.".$this->id),
			"visible" => UserInfoUtil::isDefaultUser()
		));
	
		$this->createAdd("edit_indexphp","HTMLLink",array(
			"link"    => SOY2PageController::createLink("Site.EditController.".$this->id),
			"visible" => UserInfoUtil::isDefaultUser()
		));

		$this->createAdd("edit_htaccess","HTMLLink",array(
			"link"    => SOY2PageController::createLink("Site.EditHtaccess.".$this->id),
			"visible" => UserInfoUtil::isDefaultUser()
		));
	
	}
	
	function updateFileDB(){
		SOY2::import("util.CMSFileManager");
		
		CMSFileManager::deleteAll();
		
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		$sites = $SiteLogic->getSiteList();
		
		foreach($sites as $site){
			$url = (UserInfoUtil::getSiteURLBySiteId($site->getId()) != $site->getUrl() ) ? $site->getUrl() : null;
			CMSFileManager::setSiteInformation($site->getId(), $url, $site->getPath());
			CMSFileManager::insertAll($site->getPath());
		}
		
	}
}

?>