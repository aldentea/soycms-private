<?php
/**
 * @ToDo 後日、iframe周りのhttpsの読み込みの対応を行う
 */
include(dirname(__FILE__)."/common.php");
class ButtonSocialPlugin{

	const PLUGIN_ID = "ButtonSocial";

	private $logic;
	private $app_id;
	private $mixi_check_key;
	private $mixi_like_key;
	private $admins;
	private $description;
	private $image;

	function init(){
		CMSPlugin::addPluginMenu($this->getId(),array(
			"name"=>"ソーシャルボタン設置プラグイン",
			"description"=>"ページにソーシャルボタンを設置します。",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.8"
		));

		$this->logic = new ButtonSocialCommon();

		CMSPlugin::addPluginConfigPage($this->getId(),array(
			$this,"config_page"
		));

		if(CMSPlugin::activeCheck($this->getId())){

			CMSPlugin::setEvent('onEntryOutput',$this->getId(),array($this,"display"));

			//公開側のページを表示させたときに、メタデータを表示する
			CMSPlugin::setEvent('onPageOutput',$this->getId(),array($this,"onPageOutput"));
			CMSPlugin::setEvent('onOutput',self::PLUGIN_ID,array($this,"onOutput"));
		}else{
			//何もしない
		}

	}

	function getId(){
		return self::PLUGIN_ID;
	}

	function display($arg){
		$logic = $this->logic;

		$entryId = $arg["entryId"];
		$htmlObj = $arg["SOY2HTMLObject"];

		list($url,$title) = $logic->getDetailUrl($htmlObj,$entryId);

		$htmlObj->createAdd("facebook_like_button","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getFbButton($this->app_id,$url)
		));

		$htmlObj->createAdd("twitter_button","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getTwitterButton($url)
		));

		$htmlObj->createAdd("twitter_button_mobile","HTMLLink",array(
			"soy2prefix" => "cms",
			"link" => $logic->getTwitterButtonMobile($url,$title)
		));

		$htmlObj->createAdd("hatena_button","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getHatenaButton($url)
		));

		$htmlObj->createAdd("mixi_check_button","HTMLLink",array(
			"soy2prefix" => "cms",
			"link" => "http://mixi.jp/share.pl",
			"attr:class" => "mixi-check-button",
			"attr:data-key" => $this->mixi_check_key,
			"attr:data-url" => $url
		));

		$htmlObj->createAdd("mixi_check_script","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiCheckScript()
		));

		$htmlObj->createAdd("mixi_check_button_mobile","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiCheckButtonMobile($url,$this->mixi_check_key,$title)
		));

		$htmlObj->createAdd("mixi_like_button","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiLikeButton($this->mixi_like_key)
		));

		$htmlObj->createAdd("mixi_like_button_mobile","HTMLLabel",array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiLikeButtonMobile($url,$title,$this->mixi_like_key)
		));
		
		$htmlObj->createAdd("google_plus_button", "HTMLLabel", array(
			"soy2prefix" => "cms",
			"html" => $logic->getGooglePlusButton()
		));

	}

	function onPageOutput($obj){
		$logic = $this->logic;

		$obj->createAdd("og_meta","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getOgMeta($obj,$this->description,$this->image)
		));

		$obj->createAdd("facebook_meta","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getFbMeta($this->app_id,$this->admins)
		));

		$obj->createAdd("facebook_like_button","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getFbButton($this->app_id)
		));

		$obj->createAdd("twitter_button","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getTwitterButton()
		));

		$obj->createAdd("twitter_button_mobile","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getTwitterButton()
		));

		$obj->createAdd("hatena_button","HTMLLabel",array(
			"soy2prefix" => "sns",
			"html" => $logic->getHatenaButton()
		));
		
		$obj->createAdd("google_plus_button", "HTMLLabel", array(
			"soy2prefix" => "sns",
			"html" => $logic->getGooglePlusButton()
		));

		/*
		 * 互換性のため block:id のものも置いておく
		 */
		$obj->createAdd("og_meta","HTMLLabel",array(
			"soy2prefix" => "block",
			"html" => $logic->getOgMeta($obj,$this->description,$this->image)
		));
		$obj->createAdd("facebook_meta","HTMLLabel",array(
			"soy2prefix" => "block",
			"html" => $logic->getFbMeta($this->app_id,$this->admins)
		));
		$obj->createAdd("facebook_like_button","HTMLLabel",array(
			"soy2prefix" => "block",
			"html" => $logic->getFbButton($this->app_id)
		));
		$obj->createAdd("twitter_button","HTMLLabel",array(
			"soy2prefix" => "block",
			"html" => $logic->getTwitterButton()
		));
		$obj->createAdd("hatena_button","HTMLLabel",array(
			"soy2prefix" => "block",
			"html" => $logic->getHatenaButton()
		));

	}
	
	function onOutput($arg){
		$html = &$arg["html"];
		
		//ダイナミック編集では挿入しない
		if(defined("CMS_PREVIEW_MODE") && CMS_PREVIEW_MODE){
			return null;
		}
		
		//app_idが入力されていない場合は表示しない
		if(is_null($this->app_id) || strlen($this->app_id) ===0){
			return null;
		}
		
		$logic = $this->logic;
		
		if(stripos($html,'<body>') !== false){
			$html = str_ireplace('<body>', '<body>' . "\n" . $logic->getFbRoot($this->app_id), $html);
		}elseif(preg_match('/<body\\s[^>]+>/',$html)){
			$html = preg_replace('/(<body\\s[^>]+>)/', "\$0\n" . $logic->getFbRoot($this->app_id), $html);
		}else{
			//何もしない
		}
		
		return $html;
	}


	function config_page($message){
		if(isset($_POST["save"])){
			$this->app_id = $_POST["app_id"];
			$this->admins = $_POST["admins"];
			$this->description = $_POST["description"];
			$this->image = $_POST["image"];
			$this->mixi_check_key = $_POST["mixi_check_key"];
			$this->mixi_like_key = $_POST["mixi_like_key"];

			CMSPlugin::savePluginConfig(self::PLUGIN_ID,$this);
			CMSPlugin::redirectConfigPage();
			//CMSUtil::NotifyUpdate();

			exit;
		}

		ob_start();
		include_once(dirname(__FILE__) . "/config.php");
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public static function register(){

		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new ButtonSocialPlugin();
		}

		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));

	}
}
ButtonSocialPlugin::register();
?>