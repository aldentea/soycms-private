<?php

class UtilMultiLanguageConfigFormPage extends WebPage{
	
	function UtilMultiLanguageConfigFormPage(){
		
	}
	
	function doPost(){
		if(soy2_check_token() && isset($_POST["Config"])){
			$this->pluginObj->setConfig($_POST["Config"]);
			$check = (isset($_POST["check_browser_language"])) ? (int)$_POST["check_browser_language"] : 0;
			$this->pluginObj->setCheckBrowserLanguage($check);
			CMSPlugin::savePluginConfig($this->pluginObj->getId(),$this->pluginObj);
		}
		CMSPlugin::redirectConfigPage();
	}
	
	function execute(){
		WebPage::WebPage();
		
		$config = $this->pluginObj->getConfig();
		
		$this->addForm("form");
				
		$languages = array("jp", "en");
		foreach($languages as $language){
			if($language !== "jp"){
				$text = (isset($config[$language]["prefix"])) ? $config[$language]["prefix"] : $language;
			}else{
				$text = (isset($config[$language]["prefix"])) ? $config[$language]["prefix"] : "";
			}
			
			$this->addInput($language . "_prefix", array(
				"name" => "Config[" . $language . "][prefix]",
				"value" => $text
			));
			
			$this->addLabel($language . "_prefix_text", array(
				"text" => (strlen($text)) ? "/" . $text : ""
			));
		}
				
		$this->addLabel("domain", array(
			"text" => $_SERVER["HTTP_HOST"]
		));
		
		$this->addCheckBox("confirm_browser_language", array(
			"name" => "check_browser_language",
			"value" => 1,
			"selected" => $this->pluginObj->getCheckBrowserLanguage(),
			"label" => "確認する"
		));	
	}
	
	function setPluginObj($pluginObj){
		$this->pluginObj = $pluginObj;
	}
}
?>