<?php

class UpperMenuPage extends CMSWebPageBase{

	/**
	 * 有効なタブとURLのパターン
	 */
	private $activeTabRules = array(
		'Index' => 'top',
		'^Site'=> 'site',
		'^Administrator'=> 'administrator',
		'^Application' => 'application'
	);

	private $activeTab;

    function UpperMenuPage() {
    	WebPage::WebPage();

    	//リクエストされたパスからActiveなパスを取得
    	$requestPath = SOY2PageController::getRequestPath();

    	foreach($this->activeTabRules as $rule => $tab){
    		if(preg_match("/".$rule."/",$requestPath)){
    			$this->activeTab = $tab;
    			break;
    		}
    	}
    }

    function execute(){
		$this->add("update_link",SOY2HTMLFactory::createInstance("HTMLLink",array(
			"link" => SOY2PageController::createLink("Administrator.Detail.".UserInfoUtil::getUserId())
		)));

    	$this->createAdd("adminname","HTMLLabel",array(
			"text" => UserInfoUtil::getUserName(),
			"width" => 30,
			"title" => UserInfoUtil::getUserName(),
		));

    	$this->createAdd("biglogo","HTMLModel",array(
    		"src"=>SOY2PageController::createRelativeLink("css/img/logo_big.gif")
    	));

    	/* タブの状態を設定 */
    	$this->createAdd("top","HTMLTab",array(
    		"class" => $this->getMenuStatus("top")
    	));

    	$this->createAdd("site","HTMLTab",array(
    		"class" => $this->getMenuStatus("site")
    	));

    	$this->createAdd("administrator","HTMLTab",array(
    		"class" => $this->getMenuStatus("administrator")
    	));

    	$this->createAdd("application","HTMLTab",array(
    		"class" => $this->getMenuStatus("application")
    	));

    	/* アプリケーションタブの表示 */
    	$logic = SOY2Logic::createInstance("logic.admin.Application.ApplicationLogic");
    	$this->createAdd("is_application_installed","HTMLModel",array(
    		"visible" => $logic->checkIsInstalledApplication()
    	));
    }

    /**
     * メニューの状態を設定
     */
    function getMenuStatus($tabName){

    	if($tabName == $this->activeTab){
    		return "tab_active";
    	}else{
    		return "tab_inactive";
    	}
    }
}

class HTMLTab extends SOY2HTML{

	const SOY_TYPE = SOY2HTML::HTML_BODY;

	function execute(){
		//do nothing
	}

	function getObject(){
		return "";
	}

}
?>