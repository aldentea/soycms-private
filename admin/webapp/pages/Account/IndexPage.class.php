<?php

class IndexPage extends CMSUpdatePageBase{
	
	var $userinfoChanged;
	var $passwordChanged;
	
	function setUserInfoChanged($flag){
		$this->userinfoChanged = $flag;
	}
	
	function setPasswordChanged($flag){
		$this->passwordChanged = $flag;	
	}
	
	function doPost(){
		
		if(isset($_POST["changeuser"])){
			SOY2PageController::jump("Account.ChangeUserInfo");
		}
		
		if(isset($_POST["changepassword"])){
			SOY2PageController::jump("Account.ChangePassword");
		}
			
	}
	
    function IndexPage() {
    	WebPage::WebPage();
    	
    	$userId = UserInfoUtil::getUserId();
    	
    	$result = $this->run("Administrator.DetailAction",array("adminId"=>$userId));
    	
    	$userInfo = $result->getAttribute("admin");
    	
    	$name =  $userInfo->getName();
    	
    	$this->createAdd("name","HTMLLabel",array(
    		"text" => strlen($name)>0 ? $name : CMSMessageManager::get("ADMIN_NO_SETTING")						
    	));
    	
    	$email = $userInfo->getEmail();
    	
    	$this->createAdd("email","HTMLLabel",array(
    		"text" => strlen($email)>0 ? $email : CMSMessageManager::get("ADMIN_NO_SETTING")
    	));
    	
    	//フォームの追加
    	$this->addForm("changeuser_form");
    	$this->addForm("changepassword_form");
    	
    	//メッセージ
    	$this->createAdd("changepassword_message","HTMLModel",array("visible"=>$this->passwordChanged));
    	$this->createAdd("changeuserinfo_message","HTMLModel",array("visible"=>$this->userinfoChanged));
   		
	}
}
?>