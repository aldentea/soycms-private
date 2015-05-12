<?php
class ChangePasswordPage extends CMSUpdatePageBase{
	
	var $error_str;
	
	function setError_str($str){
		$this->error_str = $str;
	}
	
	function doPost(){
		$result = SOY2ActionFactory::createInstance("Administrator.ChangePasswordAction")->run();
		
		$form = $result->getAttribute("form");
		
		if($result->success()){
			$this->jump("Account",array("passwordChanged"=>true));
		}else{
			
			if($form->hasError()){
				$str = CMSMessageManager::get("ADMIN_NEW_PASSWORD_FORMAT_WRONG");
			}else if($form->newPassword != $form->newPasswordConfirm){
				$str = CMSMessageManager::get("ADMIN_NEW_PASSWORDS_NOT_SAME");
			}else{
				$str = CMSMessageManager::get("ADMIN_OLD_PASSWORD_WRONG");
			}
			
			$this->jump("Account.ChangePassword",array("error_str"=>$str));
		}
		
	}
	
    function ChangePasswordPage() {
    	WebPage::WebPage();
    	
    	$this->addForm("changeform");
    	$this->buildForm();    	
    }
    
    function buildForm(){
    	
    	$this->createAdd("password","HTMLInput",array(
    		"name" => "newPassword",
    		"value" => "",
    		"type" => "password"    	
    	));
    	
    	$this->createAdd("password_confirm","HTMLInput",array(
    		"name" => "newPasswordConfirm",
    		"value" => "",
    		"type" => "password"    	
    	));
    	
    	$this->createAdd("old_password","HTMLInput",array(
    		"name"=>"oldPassword",
    		"value"=>"",
    		"type"=>"password"
    	));
    	
    	$this->createAdd("error_msg","HTMLLabel",array(
	    	"text"=>$this->error_str,
	    	"visible"=>strlen($this->error_str)>0
	    ));
    	
    	
    }
}
?>