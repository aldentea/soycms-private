<?php
class IndexPage extends CMSWebPageBase{
	
	
	function IndexPage(){
		WebPage::WebPage();

		if(!UserInfoUtil::isDefaultUser()){
    		$this->jump("");
		}		


		include(SOY2::RootDir() . "error/error.func.php");
		
		$this->createAdd("server_info", "HTMLTextArea", array(
			"text" => get_soycms_report() ."\n\n". get_soycms_options() ."\n\n". get_environment_report(),
			"style" => "width:100%;height:1000px;",
			"readonly" => "readonly"
		));
		$this->createAdd("php_info", "HTMLModel", array(
			"src" => SOY2PageController::createLink("Server.PHPInfo"),
			"style" => "width:100%;height:1000px;border: 1px inset #ECE9D8;",
		));
	}


}