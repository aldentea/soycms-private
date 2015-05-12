<?php

class RedirectPage extends CMSHTMLPageBase{

	var $userId;

	function RedirectPage(){

		//ログインIDの指定がなければただちにログイン画面へ
		if(!isset($_GET["userId"]) || strlen($_GET["userId"])<1){
			SOY2PageController::redirect("");
		}


		WebPage::WebPage();

		$this->createAdd("user_id","HTMLLabel",array(
			"text" => $_GET["userId"],
		));

		$this->createAdd("redirect_link","HTMLLabel",array(
			"text" => SOY2PageController::createLink("")
		));

		$this->createAdd("biglogo","HTMLImage",array(
			"src"=>SOY2PageController::createRelativeLink("css/img/logo_big.gif")
		));

	}

	function setUserId($userId){
		error_log(__LINE__." user id $userId");
		$this->userId = $userId;
		echo $userId;
	}

}

