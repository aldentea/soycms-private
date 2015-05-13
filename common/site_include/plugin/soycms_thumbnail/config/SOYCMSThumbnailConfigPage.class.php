<?php

class SOYCMSThumbnailConfigPage extends WebPage{

	private $pluginObj;

	function SOYCMSThumbnailConfigPage(){
		
	}
	
	function doPost(){
		
		if(soy2_check_token() && isset($_POST["Config"])){
			
			$config = $_POST["Config"];
			$this->pluginObj->setRatioW((int)$config["ratio_w"]);
			$this->pluginObj->setRatioH((int)$config["ratio_h"]);
			
			$this->pluginObj->setResizeW((int)$config["resize_w"]);
			$this->pluginObj->setResizeH((int)$config["resize_h"]);
			
			CMSPlugin::savePluginConfig($this->pluginObj->getId(), $this->pluginObj);
			CMSPlugin::redirectConfigPage();
		}
	}
	
	function execute(){
		WebPage::WebPage();
		
		$this->addForm("form");
		
		$this->addInput("ratio_w", array(
			"name" => "Config[ratio_w]",
			"value" => $this->pluginObj->getRatioW()
		));
		
		$this->addInput("ratio_h", array(
			"name" => "Config[ratio_h]",
			"value" => $this->pluginObj->getRatioH()
		));
		
		$this->addInput("resize_w", array(
			"name" => "Config[resize_w]",
			"value" => $this->pluginObj->getResizeW()
		));
		
		$this->addInput("resize_h", array(
			"name" => "Config[resize_h]",
			"value" => $this->pluginObj->getResizeH()
		));
	}
	
	function setPluginObj($pluginObj){
		$this->pluginObj = $pluginObj;
	}
}
?>