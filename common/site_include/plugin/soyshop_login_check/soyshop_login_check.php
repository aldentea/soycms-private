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
	private $logoutPageUrl;
	
	//フォームへリダイレクトするページ
	//Array<ページID => 0 | 1> リダイレクトしないページが1
	public $config_per_page = array();
	//Array<ページID => Array<ページタイプ => 0 | 1>> リダイレクトしないページが1
	public $config_per_blog = array();
	
	//コメント投稿者へのポイント付与設定
	private $point = 0;
	
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
			"version"=>"0.7"
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

				$loginLogic = SOY2Logic::createInstance("site_include.plugin.soyshop_login_check.logic.LoginLogic", array("siteId" => $this->siteId));
				
				//ログインページのURLもここで取得する
				if(!$this->isLoggedIn){
					$this->loginPageUrl = $loginLogic->getLoginPageUrl();
				
				//ログアウトページのURLをここで取得する
				}else{
					$this->logoutPageUrl = $loginLogic->getLogoutPageUrl();
				}
				
				CMSPlugin::setEvent('onEntryOutput',self::PLUGIN_ID, array($this, "onEntryOutput"));
				CMSPlugin::setEvent('onPageOutput', self::PLUGIN_ID, array($this, "onPageOutput"));
				
				//コメント時のポイント付与
				if($this->isLoggedIn && (is_numeric($this->point) && $this->point > 0)){
					CMSPlugin::setEvent('onSubmitComment',self::PLUGIN_ID, array($this, "onSubmitComment"));
				}
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
		
		//ログアウトリンク
		$htmlObj->addLink("logout_link", array(
			"soy2prefix" => "cms",
			"link" => $this->logoutPageUrl
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
		
		//リダイレクトの対象ページか調べる。
		if($this->checkRedirect($obj->page->getId())){
			$redirectLogic = SOY2Logic::createInstance("site_include.plugin.soyshop_login_check.logic.RedirectLogic", array("loginPageUrl" => $this->loginPageUrl, "configPerBlog" => $this->config_per_blog));
			$mode = isset($obj->mode) ? $obj->mode : null;
			$redirectLogic->redirectLoginForm($obj->page, $mode);
		}
		
		/** ここからフォーム **/
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
		
		$obj->addLink("logout_link", array(
			"soy2prefix" => "s_block",
			"link" => $this->logoutPageUrl
		));
	}
	
	//コメント投稿時のポイント付与
	function onSubmitComment($args){
		$entry = $args["page"]->entry;
		$entryComment = $args["entryComment"];
		
		//コメント文章があるかを念の為にチェック
		if(is_null($entryComment->getBody()) || strlen($entryComment->getBody()) === 0) return;
		
		//ポイント付与
		$pointLogic = SOY2Logic::createInstance("site_include.plugin.soyshop_login_check.logic.PointLogic", array("siteId" => $this->siteId, "point" => $this->point, "entry" => $entry));
		$pointLogic->addPoint();
	}
	
	function config_page(){	
		include_once(dirname(__FILE__)."/config/SOYShopLoginCheckConfigFormPage.class.php");
		$form = SOY2HTMLFactory::createInstance("SOYShopLoginCheckConfigFormPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}
	
	function checkRedirect($pageId){
		//既にログインしている場合はリダイレクトをしないを返す
		if($this->isLoggedIn) return 0;
		
		//プラグインのページ毎のリダイレクト設定を確認する
		return (isset($this->config_per_page[$pageId])) ? (int)$this->config_per_page[$pageId] : 0;
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
	
	function getPoint(){
		return $this->point;
	}
	function setPoint($point){
		$this->point = $point;
	}
}
?>