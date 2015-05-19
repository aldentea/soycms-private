<?php

class IndexPage extends CMSWebPageBase{

	const TYPE_PHP = "php";
	const TYPE_HTML = "html";

	function IndexPage(){
		//ディレクトリの作成
		if(!self::checkHasModuleDirectory()) {
			self::createModuleDirectory();
		}
		
		WebPage::WebPage();
		
		$modules = self::getModules();
		
		$this->addModel("is_module_list", array(
			"visible" => (count($modules))
		));
		
		$this->addModel("no_module", array(
			"visible" => (count($modules) === 0)
		));
		
		$this->createAdd("module_list", "_component.Module.ModuleListComponent", array(
			"list" => $modules,
			"editorLink" => SOY2PageController::createLink("Module.Editor?moduleId=")
		));
		
		$modules = self::getModules(self::TYPE_HTML);
		
		$this->addModel("is_html_module_list", array(
			"visible" => (count($modules))
		));
		
		$this->createAdd("html_module_list", "_component.Module.ModuleListComponent", array(
			"list" => $modules,
			"editorLink" => SOY2PageController::createLink("Module.HTML.Editor?moduleId=")
		));
	}
	
	/**
	 * モジュール用のディレクトリがあるか？
	 * @return boolean
	 */
	private function checkHasModuleDirectory(){
		$dir = self::getModuleDirectory();
		return (file_exists($dir) && is_dir($dir));
	}
	
	private function createModuleDirectory(){
		mkdir(self::getModuleDirectory());
		mkdir(self::getModuleDirectory(self::TYPE_HTML));
	}
	
	private function getModules($t = self::TYPE_PHP){
		$res = array();
		$moduleDir = self::getModuleDirectory();
		
		$files = soy2_scanfiles($moduleDir);
		
		foreach($files as $file){
			$moduleId  = str_replace($moduleDir, "", $file);
			if(!preg_match('/\.php$/', $file)) continue;
			
			if($t == self::TYPE_PHP){
				if(!self::checkModuleDir($moduleId)) continue;
			}else{
				if(self::checkModuleDir($moduleId)) continue;
			}
			
			
			//一個目の/より前はカテゴリ
			$moduleId = preg_replace('/\.php$/', "", $moduleId);
			$moduleId = str_replace("/", ".", $moduleId);
			$name = $moduleId;
			
			//ini
			$iniFilePath = preg_replace('/\.php$/', ".ini", $file);
			if(file_exists($iniFilePath)){
				$array = parse_ini_file($iniFilePath);
				if(isset($array["name"])) $name = $array["name"];
			}
			
			$res[] = array(
				"name" => $name,
				"moduleId" => $moduleId,
			);	
		}
		
		return $res;
	}
	
	//モジュール群からcommonディレクトリにあるモジュールを除く
	private function checkModuleDir($dir){
		$res = true;
		
		if(preg_match("/^common./", $dir)){
			$res = false;
		}
		if(preg_match("/^html./", $dir)){
			$res = false;
		}
		
		return $res;
	}
	
	private function getModuleDirectory($t = self::TYPE_PHP){
		if(isset($t) && $t == self::TYPE_HTML){
			return UserInfoUtil::getSiteDirectory() . ".module/html/";
		}else{
			return UserInfoUtil::getSiteDirectory() . ".module/";
		}
	}
}
?>