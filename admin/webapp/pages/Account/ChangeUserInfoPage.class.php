<?php
class ChangeUserInfoPage extends CMSUpdatePageBase{
	
	var $account;
	
	function doPost(){
		$result = $this->run("Administrator.UpdateAction",array("adminId"=>UserInfoUtil::getUserId()));
		
		if($result->success()){
			$this->jump("Account",array("userinfoChanged"=>true));
		}else{
			
			$this->jump("Account.ChangeUserInfo");
		}
		
	}
	
    function ChangeUserInfoPage() {
    	WebPage::WebPage();
    	
    	
    	$result = $this->run("Administrator.DetailAction",array("adminId"=>UserInfoUtil::getUserId()));
    	
    	$userInfo = $result->getAttribute("admin");
    	
    	
    	$this->buildForm($userInfo);    	
    }
    
    function buildForm($userInfo){
    	//hiddenで渡す
    	$this->createAdd("user_id","HTMLInput",array(
    		"name" => "userId",
    		"value" => $userInfo->getUserId()
    	));
    	
    	$this->createAdd("name","HTMLInput",array(
    		"name"=>"name",
    		"value"=>$userInfo->getName()
    	));
    	
    	$this->createAdd("email","HTMLInput",array(
    		"name"=>"email",
			"value"=>$userInfo->getEmail()
    	));
    	
    	$this->addForm("changeform");
    	
    	
    }
}
?>