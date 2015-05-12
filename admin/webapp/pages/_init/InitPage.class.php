<?php

class InitPage extends CMSHTMLPageBase{

	private $_message = null;

	function doPost(){

		$logic = SOY2Logic::createInstance("logic.init.InitializeLogic");

    	$userId = $_POST['userId'];
    	$password = $_POST['password'];
    	$password_confirm = $_POST['password_confirm'];

    	$this->_message = array();

    	if(strlen($userId) < 4){
    		$this->_message["userId"] = CMSMessageManager::get("ADMIN_SUPERUSER_ID_IS_TOO_SHORT");
    	}elseif(strlen($userId) > 255){
    		$this->_message["userId"] = CMSMessageManager::get("ADMIN_SUPERUSER_ID_IS_TOO_LONG");
    	}
    	if(strlen($password) < 6){
    		$this->_message["password"] = CMSMessageManager::get("ADMIN_PASSWORD_IS_TOO_SHORT");
    	}elseif(strlen($password) > 255){
    		$this->_message["password"] = CMSMessageManager::get("ADMIN_PASSWORD_IS_TOO_LONG");
    	}
    	if($password != $password_confirm){
    		$this->_message["password_confirm"] = CMSMessageManager::get("ADMIN_PASSWORDS_NOT_SAME");
    	}

    	if(count($this->_message) >0){
    		return;
    	}

    	if($logic->initialize($userId,$password)){
    		SOY2PageController::redirect("./index.php/Redirect?userId=".$userId);
    	}

		$this->_message["init"] = "[initialize] Failed to initialize cms";
	}

	function InitPage(){
		$loginable = $this->checkLoginable();

		//初期管理者が作成済み
		if($loginable && ADMIN_DB_EXISTS && $this->hasDefaultUser()){
    		SOY2PageController::redirect("./index.php");
		}

		WebPage::WebPage();


		$this->createAdd("initform","HTMLForm");
		$this->createAdd("userId", "HTMLInput", array(
			"name"  => "userId",
			"value" => @$_POST["userId"],
			"disabled" => !$loginable,
    		"attr:autocomplete" => "off"
		));
		$this->createAdd("password", "HTMLInput", array(
			"type"  => "password",
			"name"  => "password",
			"value" => @$_POST["password"],
			"disabled" => !$loginable,
    		"attr:autocomplete" => "off"
		));
		$this->createAdd("password_confirm", "HTMLInput", array(
			"type"  => "password",
			"name"  => "password_confirm",
			"value" => "",
			"disabled" => !$loginable,
    		"attr:autocomplete" => "off"
		));
		$this->createAdd("submit_button", "HTMLInput", array(
			"type"  => "submit",
			"name"  => "login",
			"value" => CMSMessageManager::get("ADMIN_SET"),
			"disabled" => !$loginable
		));

		$this->add("message_db",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => @$this->_message["db"],
			"visible" => !$loginable
		)));
		$this->add("message_userId",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => @$this->_message["userId"],
		)));
		$this->add("message_password",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => @$this->_message["password"],
		)));
		$this->add("message_password_confirm",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => @$this->_message["password_confirm"],
		)));

		$this->createAdd("biglogo","HTMLModel",array(
    		"src"=>SOY2PageController::createRelativeLink("css/img/logo_big.gif")
    	));

	}

	/**
	 * データベースに接続できるかをチェックする
	 * @return Boolean
	 */
	function checkLoginable(){

		if(!is_writable(SOY2::RootDir() . "db")){
			$this->_message["db"] = CMSMessageManager::get("ADMIN_DB_CONNECT_FAILURE")." ".CMSMessageManager::get("ADMIN_DB_NO_ROLE")." ".realpath(SOY2::RootDir() . "db");

		}else if(SOYCMS_DB_TYPE != "sqlite"){
			try{
				$con = SOY2DAO::_getDataSource();
			}catch(Exception $e){
				$this->_message["db"] = CMSMessageManager::get("ADMIN_DB_CONNECT_FAILURE")." (".$e->getMessage().")";
			}
		}

		return (strlen(@$this->_message["db"])==0);
	}

	/**
	 * すでに初期管理者がいるかどうかをチェックする
	 * @return Boolean
	 */
	function hasDefaultUser(){
		$logic = SOY2Logic::createInstance("logic.admin.Administrator.AdministratorLogic");
		return $logic->hasDefaultUser();
	}

}

?>