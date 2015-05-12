<?php
/*
 * Created on 2010/07/24
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
SOYShopLoginCheckPlugin::register();

class SOYShopLoginCheckPlugin{
	
	const PLUGIN_ID = "SOYShopLoginCheck";
	
	private $siteId = "shop";
	private $isLoggedIn;
	private $loginPageUrl;
	
	function getId(){
		return self::PLUGIN_ID;	
	}
	
	function init(){
		CMSPlugin::addPluginMenu(self::PLUGIN_ID,array(
			"name"=>"SOYShopログインチェックプラグイン",
			"description"=>"SOY Shopサイトでのログインの有無をチェックする",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com",
			"mail"=>"info@n-i-agroinformatics.com",
			"version"=>"0.5"
		));
		
		if(CMSPlugin::activeCheck($this->getId())){
		
			CMSPlugin::addPluginConfigPage(self::PLUGIN_ID, array(
				$this,"config_page"	
			));
		
			//公開画面側
			if(defined("_SITE_ROOT_")){
			
				//ここでログインチェックをしてしまう。
				$checkLogic = SOY2Logic::createInstance("site_include.plugin.soyshop_login_check.logic.LoginCheckLogic", array("siteId" => $this->siteId));
				$this->isLoggedIn = $checkLogic->isLoggedIn();
				
				//ログインページのURLもここで取得する
				$loginLogic = SOY2Logic::createInstance("site_include.plugin.soyshop_login_check.logic.LoginLogic", array("siteId" => $this->siteId));
				$this->loginPageUrl = $loginLogic->getLoginPageUrl();
				
				CMSPlugin::setEvent('onEntryOutput',self::PLUGIN_ID, array($this, "onEntryOutput"));
				CMSPlugin::setEvent('onPageOutput', self::PLUGIN_ID, array($this, "onPageOutput"));
			}
		}
	}
	
	function onEntryOutput($arg){
		
		$htmlObj = $arg["SOY2HTMLObject"];
		
		$htmlObj->addModel("is_login", array(
			"soy2prefix" => "cms",
			"visible" => ($this->isLoggedIn)
		));
		
		$htmlObj->addModel("no_login", array(
			"soy2prefix" => "cms",
			"visible" => (!$this->isLoggedIn)
		));
				
		//ログインリンク
		$htmlObj->addLink("login_link", array(
			"soy2prefix" => "cms",
			"link" => $this->loginPageUrl . "?r=" . rawurldecode($_SERVER["REQUEST_URI"])
		));
		
		/** ここから下は詳細ページでしか動作しません **/
		if(isset($htmlObj->entryPageUri) && strpos($_SERVER["REQUEST_URI"], $htmlObj->entryPageUri) !== false){
			
			$htmlObj->addForm("login_form", array(
				"soy2prefix" => "cms",
				"action" => $this->loginPageUrl . "?r=" . rawurldecode($_SERVER["REQUEST_URI"]),
				"method" => "post"
			));
			
			$htmlObj->addInput("login_email", array(
				"soy2prefix" => "cms",
				"type" => "email",
				"name" => "mail",
				"value" => ""
			));
			
			$htmlObj->addInput("login_password", array(
				"soy2prefix" => "cms",
				"type" => "password",				
				"name" => "password",
				"value" => ""
			));
			
			$htmlObj->addInput("login_submit", array(
				"soy2prefix" => "cms",
				"type" => "submit", 
				"name" => "login"
			));
			
			$htmlObj->addInput("auto_login", array(
				"soy2prefix" => "cms",
				"type" => "checkbox", 
				"name" => "login_memory"
			));
		}
	}
	
	function onPageOutput($obj){
		
		$obj->addModel("is_login", array(
			"soy2prefix" => "s_block",
			"visible" => ($this->isLoggedIn)
		));
		
		$obj->addModel("no_login", array(
			"soy2prefix" => "s_block",
			"visible" => (!$this->isLoggedIn)
		));
		
		$obj->addForm("login_form", array(
			"soy2prefix" => "s_block",
			"action" => $this->loginPageUrl . "?r=" . rawurldecode($_SERVER["REQUEST_URI"]),
			"method" => "post"
		));
			
		$obj->addInput("login_email", array(
			"soy2prefix" => "s_block",
			"type" => "email",
			"name" => "mail",
			"value" => ""
		));
			
		$obj->addInput("login_password", array(
			"soy2prefix" => "s_block",
			"type" => "password",
			"name" => "password",
			"value" => ""
		));
		
		$obj->addInput("login_submit", array(
			"soy2prefix" => "s_block",
			"type" => "submit", 
			"name" => "login"
		));
		
		$obj->addInput("auto_login", array(
			"soy2prefix" => "s_block",
			"type" => "checkbox", 
			"name" => "login_memory"
		));
	}
	
	function config_page(){	
		include_once(dirname(__FILE__)."/config/SOYShopLoginCheckConfigFormPage.class.php");
		$form = SOY2HTMLFactory::createInstance("SOYShopLoginCheckConfigFormPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}
	
	public static function register(){
		
		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(!$obj){
			$obj = new SOYShopLoginCheckPlugin();
		}
			
		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));
	}
	
	function getSiteId(){
		return $this->siteId;
	}
	function setSiteId($siteId){
		$this->siteId = $siteId;
	}
}
?>