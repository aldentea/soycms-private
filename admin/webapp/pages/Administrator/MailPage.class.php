<?php

class MailPage extends WebPage{

	var $errorMessage = "";

	private $logic;

	function doPost(){
		
		if(!soy2_check_token()){
			SOY2PageController::jump("Administrator.Mail");
		}
		
		//テスト送信
		if(isset($_POST["test_mail_address"])){
			try{
				$this->testSend($_POST["test_mail_address"]);
				SOY2PageController::jump("Administrator.Mail?sended");
			}catch(Exception $e){
				SOY2PageController::jump("Administrator.Mail?failed_to_send");
			}
		
		//設定更新
		}else{
			try{
				$serverConfig = SOY2::cast("SOY2Mail_ServerConfig",(object)$_POST);
				$this->logic->save($serverConfig);
				SOY2PageController::jump("Administrator.Mail?updated");
			}catch(Exception $e){
				SOY2PageController::jump("Administrator.Mail?failed");
			}
		}

	}

	function MailPage() {
		//初期管理者のみ
		if(!UserInfoUtil::isDefaultUser()){
			SOY2PageController::jump("");
		}

		$this->logic = SOY2LogicContainer::get("logic.mail.MailConfigLogic");

		WebPage::WebPage();

		$this->buildForm();
		$this->buildTestSendForm();

		$this->addLabel("error_message", array(
			"text" => $this->errorMessage,
			"visible" => (strlen($this->errorMessage)>0)
		));

		DisplayPlugin::toggle("updated", isset($_GET["updated"]));
		DisplayPlugin::toggle("failed", isset($_GET["failed"]));
		DisplayPlugin::toggle("sended", isset($_GET["sended"]));
		DisplayPlugin::toggle("failed_to_send", isset($_GET["failed_to_send"]));

		$serverConfig = $this->logic->get();
		$hasMailConfig = strlen($serverConfig->getFromMailAddress()) > 0;
		$hasMailAddress = SOY2Logic::createInstance("logic.admin.Administrator.AdministratorLogic")->hasMailaddress();
		
		DisplayPlugin::toggle("no_mail_config", !$hasMailConfig);
		DisplayPlugin::toggle("no_mail_address", !$hasMailAddress);
		DisplayPlugin::toggle("valid", $hasMailConfig && $hasMailAddress);

	}

	function buildForm(){

		$this->createAdd("form","HTMLForm");

		$serverConfig = $this->logic->get();

		$this->createAdd("send_server_type_sendmail","HTMLCheckBox",array(
			"elementId" => "send_server_type_sendmail",
			"name" => "sendServerType",
			"value" => SOY2Mail_ServerConfig::SERVER_TYPE_SENDMAIL,
			"selected" => ($serverConfig->getSendServerType() == SOY2Mail_ServerConfig::SERVER_TYPE_SENDMAIL),
			"onclick" => 'toggleSMTP()'
		));
		$this->createAdd("send_server_type_smtp","HTMLCheckBox",array(
			"elementId" => "send_server_type_smtp",
			"name" => "sendServerType",
			"value" => SOY2Mail_ServerConfig::SERVER_TYPE_SMTP,
			"selected" => ($serverConfig->getSendServerType() == SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
			"onclick" => 'toggleSMTP()'
		));


		$this->createAdd("is_use_pop_before_smtp","HTMLCheckBox",array(
			"elementId" => "is_use_pop_before_smtp",
			"name" => "isUsePopBeforeSMTP",
			"value" => 1,
			"selected" => $serverConfig->getIsUsePopBeforeSMTP(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
			"onclick" => 'togglePOPIMAPSetting();'
		));

		$this->createAdd("is_use_smtp_auth","HTMLCheckBox",array(
			"elementId" => "is_use_smtp_auth",
			"name" => "isUseSMTPAuth",
			"value" => 1,
			"selected" => $serverConfig->getIsUseSMTPAuth(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
			"onclick" => 'toggleSMTPAUTHSetting();'
		));

		$this->createAdd("send_server_address","HTMLInput",array(
			"id" => "send_server_address",
			"name" => "sendServerAddress",
			"value" => $serverConfig->getSendServerAddress(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
		));
		$this->createAdd("send_server_port","HTMLInput",array(
			"id" => "send_server_port",
			"name" => "sendServerPort",
			"value" => $serverConfig->getSendServerPort(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
		));


		$this->createAdd("send_server_user","HTMLInput",array(
			"id" => "send_server_user",
			"name" => "sendServerUser",
			"value" => $serverConfig->getSendServerUser(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUseSMTPAuth(),
		));
		$this->createAdd("send_server_password","HTMLInput",array(
			"id" => "send_server_password",
			"name" => "sendServerPassword",
			"value" => $serverConfig->getSendServerPassword(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUseSMTPAuth(),
		));

		$this->createAdd("is_use_ssl_send_server","HTMLCheckBox",array(
			"elementId" => "is_use_ssl_send_server",
			"name" => "isUseSSLSendServer",
			"value" => 1,
			"selected" => $this->isSSLEnabled() && $serverConfig->getIsUseSSLSendServer(),
			"disabled" => !$this->isSSLEnabled() OR ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
			"onclick" => 'changeSendPort();'
		));

		/* 受信設定 */
		$this->createAdd("receive_server_type_pop","HTMLCheckBox",array(
			"elementId" => "receive_server_type_pop",
			"name" => "receiveServerType",
			"value" => SOY2Mail_ServerConfig::RECEIVE_SERVER_TYPE_POP,
			"selected" => ($serverConfig->getReceiveServerType() == SOY2Mail_ServerConfig::RECEIVE_SERVER_TYPE_POP),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP(),
			"onclick" => 'changeReceivePort();'
		));

		$this->createAdd("receive_server_type_imap","HTMLCheckBox",array(
			"elementId" => "receive_server_type_imap",
			"name" => "receiveServerType",
			"value" => SOY2Mail_ServerConfig::RECEIVE_SERVER_TYPE_IMAP,
			"selected" => ($serverConfig->getReceiveServerType() == SOY2Mail_ServerConfig::RECEIVE_SERVER_TYPE_IMAP),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP() OR !$this->isIMAPEnabled(),
			"onclick" => 'changeReceivePort();'
		));

		$this->createAdd("receive_server_address","HTMLInput",array(
			"id" => "receive_server_address",
			"name" => "receiveServerAddress",
			"value" => $serverConfig->getReceiveServerAddress(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP(),
		));

		$this->createAdd("receive_server_user","HTMLInput",array(
			"id" => "receive_server_user",
			"name" => "receiveServerUser",
			"value" => $serverConfig->getReceiveServerUser(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP(),
		));

		$this->createAdd("receive_server_password","HTMLInput",array(
			"id" => "receive_server_password",
			"name" => "receiveServerPassword",
			"value" => $serverConfig->getReceiveServerPassword(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP(),
		));

		$this->createAdd("receive_server_port","HTMLInput",array(
			"id" => "receive_server_port",
			"name" => "receiveServerPort",
			"value" => $serverConfig->getReceiveServerPort(),
			"disabled" => ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP) OR !$serverConfig->getIsUsePopBeforeSMTP(),
		));

		$this->createAdd("is_use_ssl_receive_server","HTMLCheckBox",array(
			"elementId" => "is_use_ssl_receive_server",
			"name" => "isUseSSLReceiveServer",
			"value" => 1,
			"selected" => $this->isSSLEnabled() && $serverConfig->getIsUseSSLReceiveServer(),
			"disabled" => !$this->isSSLEnabled() OR ($serverConfig->getSendServerType() != SOY2Mail_ServerConfig::SERVER_TYPE_SMTP),
			"onclick" => 'changeReceivePort();'
		));

		/* SSL */
		$this->createAdd("is_ssl_enabled", "HTMLHidden", array(
			"id"    => "is_ssl_enabled",
			"value" => (int) $this->isSSLEnabled()
		));
		$this->createAdd("ssl_disabled", "HTMLModel", array(
			"visible" => !$this->isSSLEnabled()
		));
		/* IMAP */
		$this->createAdd("is_imap_enabled", "HTMLHidden", array(
			"id"    => "is_imap_enabled",
			"value" => (int) $this->isIMAPEnabled()
		));
		$this->createAdd("imap_disabled", "HTMLModel", array(
			"visible" => !$this->isIMAPEnabled()
		));

		/* 送信者設定 */
		$this->createAdd("from_address","HTMLInput",array(
			"name" => "fromMailAddress",
			"value" => $serverConfig->getFromMailAddress()
		));
		$this->createAdd("from_name","HTMLInput",array(
			"name" => "fromMailAddressName",
			"value" => $serverConfig->getFromMailAddressName()
		));
		$this->createAdd("return_address","HTMLInput",array(
			"name" => "returnMailAddress",
			"value" => $serverConfig->getReturnMailAddress()
		));
		
		$this->createAdd("return_name","HTMLInput",array(
			"name" => "returnMailAddressName",
			"value" => $serverConfig->getReturnMailAddressName()
		));

		/* 文字コード設定 */
		$this->createAdd("encoding_select","HTMLSelect",array(
			"name" => "encoding",
			"options" => array("UTF-8","ISO-2022-JP"),
			"selected" => $serverConfig->getEncoding()
		));

	}

	function buildTestSendForm(){
		$this->createAdd("test_form","HTMLForm");

		$this->createAdd("test_mail_address","HTMLLabel",array(
			"name" => "test_mail_address",
			"value" => "",
		));

	}

	function testSend($to){

		$title = "SOY CMS テストメール ".date("Y-m-d H:i:s");
		$content = "これはSOY CMSから送信したテストメールです。";

		$logic = SOY2LogicContainer::get("logic.mail.MailLogic");

		$logic->sendTestMail($to);

	}

	/**
	 * SSLが使用可能かを返す
	 * @return Boolean
	 */
	function isSSLEnabled(){
		return function_exists("openssl_open");
	}

	/**
	 * IMAPが使用可能かを返す
	 */
	function isIMAPEnabled(){
		return function_exists("imap_open");
	}
}
?>