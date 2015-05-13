<?php 

class LanguageListComponent extends HTMLList{
	
	private $config;
	private $smartPrefix;
	
	function populateItem($entity, $lang){

		$this->addLabel("language_type", array(
			"text" => $entity
		));
		
		$prefix = self::getPrefixString($lang);
		$this->addInput("prefix_input", array(
			"name" => "Config[" . $lang . "][prefix]",
			"value" => $prefix
		));
		
		$this->addLabel("prefix_text", array(
			"text" => (strlen($prefix)) ? "/" . $prefix : $prefix
		));
		
		$this->addLabel("domain", array(
			"text" => $_SERVER["HTTP_HOST"]
		));
		
		$this->addModel("display_smartphone_config", array(
			"visible" => ($lang != "jp" && isset($this->smartPrefix))
		));
		
		$this->addLabel("smart_prefix", array(
			"text" => $this->smartPrefix
		));
	}
	
	private function getPrefixString($lang){
		if($lang !== "jp"){
			$text = (isset($this->config[$lang]["prefix"])) ? $this->config[$lang]["prefix"] : $lang;
		}else{
			$text = (isset($this->config[$lang]["prefix"])) ? $this->config[$lang]["prefix"] : "";
		}
		return $text;
	}
	
	function setConfig($config){
		$this->config = $config;
	}
	
	function setSmartPrefix($smartPrefix){
		$this->smartPrefix = $smartPrefix;
	}
}
?>