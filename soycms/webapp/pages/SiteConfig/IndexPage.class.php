<?php

class IndexPage extends CMSWebPageBase{
	
	function doPost() {
    	if(soy2_check_token()){
			
			//当サイトのファイルDBを更新
			{	
				
				SOY2::import("util.CMSFileManager");

				CMSFileManager::deleteAll();

				set_time_limit(0);

				$sites = $this->getSiteList();
				foreach($sites as $site){
					CMSFileManager::setSiteInformation($site->getId(), $site->getUrl(), $site->getPath());
					CMSFileManager::insertAll($site->getPath());
				}
			}
				
			$action = SOY2ActionFactory::createInstance("SiteConfig.UpdateAction");
			$result = $action->run();
			if($result->success()){
				$this->addMessage("SITECONFIG_UPDATE_SUCCESS");
				$this->jump("SiteConfig");
			}else{
				$this->addErrorMessage("SITECONFIG_UPDATE_FAILED");
				$this->jump("SiteConfig");
			}
    	}else{
			$this->addErrorMessage("SITECONFIG_UPDATE_FAILED");
    	}
		
	}
	
	function IndexPage(){
		WebPage::WebPage();
		
		$this->createAdd("index_form","HTMLForm",array(
			"action"=>SOY2PageController::createLink("SiteConfig")
		));
		
		$action = SOY2ActionFactory::createInstance("SiteConfig.DetailAction");
		$result = $action->run();
		$entity = $result->getAttribute("entity");
		
		$this->createAdd("name","HTMLInput",array("value"=>$entity->getName()));
		$this->createAdd("description","HTMLTextArea",array("text"=>$entity->getDescription(),"name"=>"description"));
		$this->createAdd("charset","HTMLSelect",array(
			"selected"=>$entity->getCharset(),
			"options"=>SiteConfig::getCharsetLists()
		));
		$this->createAdd("uploadpath","HTMLInput",array(
			"name"=>"defaultUploadDirectory",
			"value"=>$entity->getDefaultUploadDirectory()
		));
		
		$this->createAdd("create_by_date","HTMLCheckBox",array(
			"name" => "createUploadDirectoryByDate",
			"value" => 1,
			"type" => "checkbox",
			"selected" => $entity->isCreateDefaultUploadDirectory(),
			"label" => "日付毎にディレクトリを作成する"
		));
		
		$this->createAdd("isShowOnlyAdministrator","HTMLCheckBox",array(
			"name" => "isShowOnlyAdministrator",
			"value" => 1,
			"type" => "checkbox",
			"selected" => $entity->isShowOnlyAdministrator(),
			"label" => "管理側にログインしている時のみ表示"
		));
	}
	
	/**
	 * サイト一覧
	 */
	function getSiteList(){
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		$old = CMSUtil::switchDsn();
		$sites = $SiteLogic->getSiteList();
		CMSUtil::resetDsn($old);
		return $sites;
	}
}
?>