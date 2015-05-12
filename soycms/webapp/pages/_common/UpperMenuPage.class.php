<?php

class UpperMenuPage extends CMSHTMLPageBase{

    function UpperMenuPage() {
    	HTMLPage::HTMLPage();
    }
    
    function execute(){
    	
    	//sitePath
		$this->createAdd("sitepath","HTMLLink",array(
			"text" => "/" . UserInfoUtil::getSite()->getSiteId(),
			"link" => UserInfoUtil::getSiteUrl(),
			"style" => "text-decoration:none;color:black;"
		));
		
		$this->createAdd("sitename","HTMLLabel",array(
			"text" => UserInfoUtil::getSite()->getSiteName()
		));
		
		//管理者名
		$this->createAdd("adminname","HTMLLabel",array(
			"text" => UserInfoUtil::getUserName(),
			"width" => 18,
			"title" => UserInfoUtil::getUserName(),
		));
		
		//popup
		$messages = CMSMessageManager::getMessages();
		$error  = CMSMessageManager::getErrorMessages();
				
		$this->createAdd("message","HTMLLabel",array(
			"html" => implode("",$error) . implode("",$messages)
		));
		
		$this->createAdd("popup","HTMLModel",array(
			"style" => (count($error)>0 || count($messages)>0) ? "" : "display:none;"
		));
		
		//SOY InquiryかSOY Mailのデータベースがサイト側に存在している場合、新しいinlineを表示する
		$inquiryUseSiteDb = SOYAppUtil::checkAppAuth("inquiry");
		$mailUseSiteDb = SOYAppUtil::checkAppAuth("mail");
		
		$this->addModel("display_app_link", array(
			"visible" => ($inquiryUseSiteDb || $mailUseSiteDb)
		));
		
		$this->addModel("display_inquiry_link", array(
			"visible" => ($inquiryUseSiteDb)
		));
		
		$this->addModel("display_mail_link", array(
			"visible" => ($mailUseSiteDb)
		));
		
		//SOY Inquiryのデータベースがサイト側に存在する場合に表示するリンク
		$this->addLink("inquiry_link", array(
			"link" => SOYAppUtil::createAppLink("inquiry")
		));
		
		//SOY Mailのデータベースがサイト側に存在する場合に表示するリンク
		$this->addLink("mail_link", array(
			"link" => SOYAppUtil::createAppLink("mail")
		));
		
		$this->createAdd("account_link","HTMLLink",array(
			"link" => (defined("SOYCMS_ASP_MODE")) ? 
						 SOY2PageController::createLink("Login.UserInfo")
						:SOY2PageController::createRelativeLink("../admin/index.php/Account")
		));
    }
}
?>