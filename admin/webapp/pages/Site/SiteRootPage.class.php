<?php

class SiteRootPage extends CMSUpdatePageBase{

	var $id;

	function doPost(){

		if(soy2_check_token() && $this->id == $_POST["site_id"]){
			$this->updateDomainRootSite($this->id, $_POST["contents"]);

			$this->addMessage("UPDATE_SUCCESS");
		}

		$this->jump("Site");
		exit;
	}

    function SiteRootPage($args) {

    	//初期管理者のみ
    	if(!UserInfoUtil::isDefaultUser()){
    		$this->jump("Site");
    	}

    	$id = (isset($args[0])) ? $args[0] : null;
    	$this->id = $id;

    	//htaccessない場合はそのまま作る
    	if(!$this->checkHtaccessExists()){
    		if(soy2_check_token()){
	    		$this->updateDomainRootSite($id);
	    		$this->addMessage("UPDATE_SUCCESS");
    		}else{
	    		$this->addMessage("UPDATE_FAILED");
    		}
	    	$this->jump("Site");
	    	exit;
    	}

    	//htaccessがすでに存在する場合は編集画面を表示する

		WebPage::WebPage();

		try{
			$site = SOY2DAOFactory::create("admin.SiteDAO")->getById($this->id);
		}catch(Exception $e){
			SOY2PageController::jump("Site");
		}

		$this->addForm("update_site_form");

		$this->addLabel("site_name", array(
			"text" => $site->getSiteName()
		));

		$this->addInput("site_id", array(
			"type" => "hidden",
			"name" => "site_id",
			"value" => $this->id
		));

		$this->addTextArea("contents", array(
			"name" => "contents",
			"value" => $this->getHtaccess($site)
		));


    	HTMLHead::addLink("site.edit.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./css/site/edit.css") . "?" . SOYCMS_BUILD_TIME
		));
    }

    /**
     * 上書き出来ないhtaccessファイルが存在しているかどうか
     */
    function checkHtaccessExists(){

    	$filepath = SOYCMS_TARGET_DIRECTORY . ".htaccess";
    	if(!file_exists($filepath)) return false;

    	$htaccess = file($filepath);
    	if((isset($htaccess[0])) && preg_match("/@generated by SOY/", $htaccess[0])){
    		return false;
    	}

    	return true;
    }

    function getHtaccess($site){

    	$filepath = SOYCMS_TARGET_DIRECTORY . ".htaccess";
    	$htaccess = file_get_contents($filepath);

    	$logic = SOY2Logic::createInstance("logic.admin.Site.DomainRootSiteLogic");

    	//もしすでに生成されていた場合
    	if( preg_match('/\n?.*@generated by SOY.*/', $htaccess, $tmp1, PREG_OFFSET_CAPTURE)
    	 && preg_match('/.*-+SOY.*\n?/', $htaccess, $tmp2, PREG_OFFSET_CAPTURE)
    	){
    		$res = substr($htaccess, 0, $tmp1[0][1]) . substr($htaccess, $tmp2[0][1] + strlen($tmp2[0][0]));
    		$htaccess = $res;
    	}

    	return $htaccess . "\n\n" . $logic->getHtaccess($site);
    }

    function updateDomainRootSite($id, $htaccess = null){
    	$dao = SOY2DAOFactory::create("admin.SiteDAO");
    	$dao->begin();
    	$dao->resetDomainRootSite();
    	$dao->updateDomainRootSite($id);
    	$dao->commit();

    	try{
    		$logic = SOY2Logic::createInstance("logic.admin.Site.DomainRootSiteLogic");
    		$logic->create();
    		if($htaccess) $logic->createHtaccess($htaccess);

    	}catch(Exception $e){
			//
    	}

		//キャッシュ削除
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		$sites = $SiteLogic->getSiteList();
		foreach($sites as $site){
			CMSUtil::unlinkAllIn($site->getPath() . ".cache/");
		}
    }
}
?>