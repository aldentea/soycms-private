<?php

class DetailPage extends CMSUpdatePageBase{

	private $adminId;
	private $failed = false;

	function doPost(){
		if(UserInfoUtil::getUserId() != $this->adminId && !UserInfoUtil::isDefaultUser()){
    		$this->jump("Administrator");
    		exit;
    	}

		if($this->updateAdministrator()){
    		$this->addMessage("UPDATE_SUCCESS");
			$this->jump("Administrator.Detail.".$this->adminId);
			exit;
		}else{
			$this->failed = true;
		}
	}

    function DetailPage($arg) {
    	$adminID = @$arg[0];
    	if(!UserInfoUtil::isDefaultUser() OR strlen($adminID)<1) $adminID = UserInfoUtil::getUserId();

    	$result = $this->run("Administrator.DetailAction",array("adminId"=>$adminID));
    	$admin = $result->getAttribute("admin");
    	$this->adminId = $adminID;

    	WebPage::WebPage();

		$showInputForm = UserInfoUtil::isDefaultUser() || $this->adminId == UserInfoUtil::getUserId();

    	$this->createAdd("userId","HTMLInput",array(
			"name" => "userId",
			"value"=>$admin->getUserId(),
			"visible"=> $showInputForm
		));
    	$this->createAdd("name","HTMLInput",array(
			"name" => "name",
			"value"=>$admin->getName(),
			"visible"=> $showInputForm
		));
    	$this->createAdd("email","HTMLInput",array(
			"name" => "email",
			"value"=>$admin->getEmail(),
			"visible"=> $showInputForm
		));

		$this->createAdd("userId_text","HTMLLabel",array(
			"text"=>(strlen($admin->getUserId()) == 0 )? CMSMessageManager::get("ADMIN_NO_SETTING") : $admin->getUserId(),
			"visible"=> !$showInputForm
		));
		$this->createAdd("name_text","HTMLLabel",array(
			"text"=>(strlen($admin->getName()) == 0 )? CMSMessageManager::get("ADMIN_NO_SETTING") : $admin->getName(),
			"visible"=> !$showInputForm
		));
    	$this->createAdd("email_text","HTMLLabel",array(
			"text"=>(strlen($admin->getEmail()) == 0 )? CMSMessageManager::get("ADMIN_NO_SETTING") : $admin->getEmail(),
			"visible"=> !$showInputForm
		));

		$this->createAdd("show_userid_input","HTMLModel",array(
			"attr:class" => $showInputForm ? "" : "no_example"
		));
		$this->createAdd("show_userid_input_example","HTMLModel",array(
			"visible" => $showInputForm
		));

		$this->createAdd("button_toggle","HTMLModel",array(
			"visible"=> $showInputForm
		));


    	$this->addForm("detailForm");


    	$this->createAdd("error","HTMLModel",array(
    		"visible" => $this->failed
    	));

    	$messages = CMSMessageManager::getMessages();
    	$this->createAdd("message","HTMLLabel",array(
    		"text" => implode("\n",$messages),
    		"visible" => !empty($messages)
    	));

    }

    function setAdminId($adminId) {
    	$this->adminId = $adminId;
    }

    function updateAdministrator(){
		$result = $this->run("Administrator.UpdateAction",array("adminId"=>$this->adminId));
    	return $result->success();
    }
}
?>