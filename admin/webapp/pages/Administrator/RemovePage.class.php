<?php

class RemovePage extends CMSUpdatePageBase{

	private $adminId;
	private $failed = false;
		var $id;

    function doPost() {

    	$action = SOY2ActionFactory::createInstance("Administrator.DeleteAction",array(
    		"id" => $this->adminId
    	));
    	$result = $action->run();

    	if($result->success()){
    		$this->addMessage("REMOVE_SUCCESS");
	    	$this->jump("Administrator");
    	}else{
    		$this->addMessage("REMOVE_FAILED");
	    	$this->reload();
    	}

    	$this->jump("Administrator");
    }

    function RemovePage($arg){
		if(!UserInfoUtil::isDefaultUser() || count($arg)<1){
    		SOY2PageController::jump("Administrator");
    	}

    	$this->adminId = @$arg[0];

    	$result = $this->run("Administrator.DetailAction",array("adminId"=>$this->adminId));
    	$admin = $result->getAttribute("admin");

		if($result->success()){
			//
		}else{
			$this->jump("Administrator");
		}

    	WebPage::WebPage();

    	$this->outputMessage();

    	$this->createAdd("userId","HTMLLabel",array("text"=>$admin->getUserId()));

		$this->createAdd("name_text","HTMLLabel",array(
			"text"=>(strlen($admin->getName()) == 0 )? CMSMessageManager::get("ADMIN_NO_SETTING") : $admin->getName(),
		));

    	$this->createAdd("email_text","HTMLLabel",array(
			"text"=>(strlen($admin->getEmail()) == 0 )? CMSMessageManager::get("ADMIN_NO_SETTING") : $admin->getEmail(),
		));

    	$this->addForm("removeForm");

    	$this->createAdd("error","HTMLModel",array(
    		"visible" => $this->failed
    	));

    }

    function outputMessage(){
    	$messages = CMSMessageManager::getMessages();
    	$this->createAdd("message","HTMLLabel",array(
    		"text" => implode("\n",$messages),
    		"visible" => !empty($messages)
    	));
    }

}
