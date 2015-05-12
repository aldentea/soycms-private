<?php
/*
 * 記事同期プラグイン
 */

define('$this->getId()',"sync_template");

class SyncEntryPlugin{
	
	const PLUGIN_ID = "sync_entry";
	
	var $output_date = "-";
	var $output_time = "-";
	var $sync_date = "-";
	var $sync_time = "-";
	var $targetDir = "entries";
	
	function getId(){
		return self::PLUGIN_ID;
	}
	
	function init(){
		CMSPlugin::addPluginMenu($this->getId(),array(
			"name"=>"記事同期プラグイン",
			"description"=>'記事を実ファイルと同期させます。<br />SOY CMSに格納されている記事をファイルに書き出したり、<br />書き出したファイルをSOY CMSに格納したりすることが可能です。',
			"author"=>"株式会社日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.0.2"
		));	
		
		CMSPlugin::addPluginConfigPage($this->getId(),array(
			$this,"config_page"
		));
	}
	
	function config_page($message){
		
		$res = $this->checkDir();
		if(!$res){
			return '<p>出力先のディレクトリを作成することが出来ません。</p>';
		}
		
		//export
		if(@$_POST["export"]){
			$this->export();
			exit;
		}
		
		//import
		if(@$_POST["import"]){
			$this->import();
			exit;
		}
		
		//save
		if(isset($_POST["save"])){
			$this->setTargetDir($_POST["targetDir"]);
			if($this->checkDir()){
				CMSPlugin::savePluginConfig($this->getId(),$this);
				CMSPlugin::redirectConfigPage();
			}
			
			echo "<p class=\"error\">出力先のディレクトリを作成することが出来ません</p>";
		}
		
		$html = '<style type="text/css">'.file_get_contents(dirname(__FILE__)."/style.css").'</style>';
		
		ob_start();
		include_once(dirname(__FILE__)."/config.php");
		$html .= ob_get_contents();
		ob_clean();
		
		$html.= '<p class="export_time_head">最終出力時刻</p><p class="export_time_body">' . (is_numeric($this->output_date) ? date("Y-m-d H:i:s",$this->output_date) : "-");
		$html.= '&nbsp;(' . (is_numeric($this->output_time) ? round($this->output_time,3) . " sec." : "-") . ")</p>";
		$html.= '<p class="export_time_head">最終同期時刻</p><p class="export_time_body">' . (is_numeric($this->sync_date) ? date("Y-m-d H:i:s",$this->sync_date) : "-");
		$html.= '&nbsp;(' . (is_numeric($this->sync_time) ? round($this->sync_time,3) . " sec." : "-") . ")</p>";
		$html.= '<br style="clear:both"/>';

		$html .= file_get_contents(dirname(__FILE__)."/description.html");

		return $html;
	}
	
	function checkDir(){
		$targetDir = $this->getTargetDir(true);
		
		if(!file_exists($targetDir)){
			$res = mkdir($targetDir);
			if($res){
				chmod($targetDir, 0777);
				if(is_writable($targetDir)){
					file_put_contents($targetDir."/.htaccess", "Deny from all");
					$res = true;
				}else{
					$res = false;
				}
			}
		}else{
			if(is_dir($targetDir) && is_writable($targetDir)){
				$res = true;
			}else{
				$res = false;
			}
		}
	
		return $res;

	}
	
	/**
	 * 出力
	 */
	function export(){
		
		$start = microtime(true);
		
		$this->exportEntries($_POST["label"]);
		
		$this->output_time = microtime(true) - $start;
		
		$this->output_date = time();
		CMSPlugin::savePluginConfig($this->getId(),$this);
		CMSPlugin::redirectConfigPage();	
	}
	
	/**
	 * 入力
	 */
	function import(){
		$imports = @$_POST["imports"];
		
		$start = microtime(true);
		
		$this->importEntries($imports);
		
		$this->sync_time = microtime(true) - $start;
		
		$this->sync_date = time();
		CMSPlugin::savePluginConfig($this->getId(),$this);
		CMSPlugin::redirectConfigPage();
	}
	
	function exportEntries($labelId){
		$targetDir = $this->getTargetDir(true);
		
		//いらないファイルを削除
		$files = scandir($targetDir);
		
		foreach($files as $file){
			if($file[0] == ".")continue;
			unlink($targetDir . "/" . $file);
		}
		
		if($labelId){
			$res = SOY2ActionFactory::createInstance("Entry.EntryListAction",array(
				"id" => $labelId
			))->run();
			
			$entries = $res->getAttribute("Entities");
		}else{
			$entries = SOY2DAOFactory::create("cms.EntryDAO")->get();
		}
		
		foreach($entries as $entry){
			file_put_contents($targetDir . "/" . $entry->getId()."_title.html",$entry->getTitle());
			file_put_contents($targetDir . "/" . $entry->getId()."_content.html",$entry->getContent());
			file_put_contents($targetDir . "/" . $entry->getId()."_more.html",$entry->getMore());
		}
		
	}
	
	function importEntries($imports){
		
		$targetDir = $this->getTargetDir(true);
		
		$entryDAO = SOY2DAOFactory::create("cms.EntryDAO");
		
		foreach($imports as $import){
			if($import[0] == ".")continue;
			$filepath = $targetDir ."/" . $import;
			
			if(filemtime($filepath) <= $this->output_date){
				continue;
			}
				
			if($this->sync_date && (filemtime($filepath) <= $this->sync_date)){
				continue;	
			}
			
			$id = preg_replace("/^([0-9]+).*/",'$1',$import);
			
			$isTitle = (preg_match('/_title/',$import));
			$isContent = (preg_match('/_content/',$import));
			$isMore = (preg_match('/_more/',$import));
			
			try{
				$entry = $entryDAO->getById($id);
			}catch(Exception $e){
				continue;
			}
			
			if($isTitle){
				$entry->setTitle(file_get_contents($filepath));
			}
			
			if($isContent){
				$entry->setContent(file_get_contents($filepath));
			}
			
			if($isMore){
				$entry->setMore(file_get_contents($filepath));
			}
						
			$entryDAO->update($entry);
		}
		
		
	}
	
	public static function register(){
		
		$obj = CMSPlugin::loadPluginConfig(SyncEntryPlugin::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new SyncEntryPlugin();
		}
		
		CMSPlugin::addPlugin(SyncEntryPlugin::PLUGIN_ID,array($obj,"init"));

	}
	
	function getTargetDir($flag = false) {
		if($flag){
			return UserInfoUtil::getSiteDirectory().$this->targetDir;
		}
		return $this->targetDir;
	}
	function setTargetDir($targetDir) {
		$targetDir = str_replace(array("\\","/","."),"_",$targetDir);
		$this->targetDir = $targetDir;
	}
}


SyncEntryPlugin::register();
?>
