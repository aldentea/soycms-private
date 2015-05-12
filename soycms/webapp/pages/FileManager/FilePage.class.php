<?php
SOY2::import("util.CMSFileManager");
class FilePage extends CMSWebPageBase{
	
	function FilePage(){
		
		WebPage::WebPage();
		
		include_once(dirname(__FILE__) ."/FileActionPage.class.php");
		
		$this->createAdd("uploaddialogform","HTMLForm",array(
			"action" => $this->createLink(FileActionPage::ACTION_UPLOAD),
			"name" => "upload_dialog_form",
			"onsubmit" => "document.upload_dialog_form.soy2_token.value = CMS_FileManager.soy2_token;"
		));
		
		$root = str_replace("\\",'/',realpath(UserInfoUtil::getSiteDirectory()));
		
		ob_start();
		$file = CMSFileManager::printTree($root);
		$buff = ob_get_contents();
		ob_end_clean();
		
		//ツリーの出力
		$this->createAdd("filetree","HTMLLabel",array(
			"html" => $buff
		));	
		
		//スクリプトの出力
		$script = file_get_contents(dirname(__FILE__)."/cms_script.js");
		$script = str_replace("__AJAX__GET_FILE_ID_PATH__",$this->createLink(FileActionPage::ACTION_GET_FILE_ID),$script);
		$script = str_replace("__AJAX__UPLOAD_PATH__",$this->createLink(FileActionPage::ACTION_UPLOAD),$script);
		$script = str_replace("__AJAX__REMOVE_PATH__",$this->createLink(FileActionPage::ACTION_REMOVE),$script);
		$script = str_replace("__AJAX__RELOAD_PATH__",$this->createLink(FileActionPage::ACTION_RELOAD),$script);
		$script = str_replace("__AJAX_SOY2_TOKEN__",soy2_get_token(),$script);
				
		$this->createAdd("cmsscript","HTMLScript",array(
			"script" => $script
		));

		$this->addLabel("connector_path", array(
			"text" => SOY2PageController::createRelativeLink("./js/elfinder/php/connector.php") . "?site_id=" . UserInfoUtil::getSite()->getSiteId()
		));
		
		CMSFileManager::debug();

	}
	
	function createLink($id){
		return SOY2PageController::createLink("FileManager.FileAction.".$id);
	}    
}

?>