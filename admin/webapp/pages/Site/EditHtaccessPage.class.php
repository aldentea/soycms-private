<?php

class EditHtaccessPage extends CMSUpdatePageBase {

	const FILENAME = ".htaccess";
	var $id;
	
	function doPost(){
		
		if($this->id == $_POST["site_id"] && $this->saveFile($_POST["contents"])){
			$this->addMessage("UPDATE_SUCCESS");
		}
		
		$this->reload();
		exit;
	}

    function EditHtaccessPage($args) {
    	if(!UserInfoUtil::isDefaultUser()){
    		$this->jump("Site");
    	}
    	$id = $args[0];
    	$this->id = $id;
    	
		WebPage::WebPage();
		
    	HTMLHead::addLink("site.edit.css",array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./css/site/edit.css")."?".SOYCMS_BUILD_TIME
		));

		$site = $this->getSite();
		
		$this->createAdd("site_name","HTMLLabel",array(
			"text" => $site->getSiteName()
		));
		
		$this->createAdd("detail_link","HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.Detail.".$this->id),
		));

		$this->addForm("update_site_form",array(
			"disabled" => !is_writable($site->getPath() . self::FILENAME)
		));
		
		$this->createAdd("site_id","HTMLInput",array(
			"type"  => "hidden",
			"name"  => "site_id",
			"value" => $this->id 
		));
		
		$this->createAdd("contents","HTMLTextArea",array(
			"name"  => "contents",
			"value" => @file_get_contents($site->getPath() . self::FILENAME),
			"disabled" => !is_writable($site->getPath() . self::FILENAME)
		));
		
		$this->createAdd("button","HTMLInput",array(
			"value"     => CMSMessageManager::get("SOYCMS_SAVE"),
			"disabled" => !is_writable($site->getPath() . self::FILENAME)
		));
    	
		if(!is_writable($site->getPath() . self::FILENAME)){
			$this->addErrorMessage("SOYCMS_NOT_WRITABLE");
		}
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
    
    function saveFile($contents){
    	$site = $this->getSite();
    	return file_put_contents($site->getPath() . self::FILENAME, $contents);
    }
    
    function getSite(){
		try{
			$site = SOY2DAOFactory::create("admin.SiteDAO")->getById($this->id);
		}catch(Exception $e){
			SOY2PageController::jump("Site");
		}
		
		return $site;
    }
    
}
