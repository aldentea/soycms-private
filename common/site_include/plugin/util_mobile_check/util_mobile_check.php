<?php
/*
 * Created on 2009/12/02
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

UtilMobileCheckPlugin::register();

class UtilMobileCheckPlugin{

	const PLUGIN_ID = "UtilMobileCheckPlugin";

	//スマートフォンの転送先設定用定数
	const CONFIG_SP_REDIRECT_PC = 0;//PCサイト
	const CONFIG_SP_REDIRECT_SP = 1;//スマートフォンサイト
	const CONFIG_SP_REDIRECT_MB = 2;//ケータイサイト

	const REDIRECT_PC = 0;//PCサイト表示（何もしない）
	const REDIRECT_SP = 1;//スマートフォンサイト転送
	const REDIRECT_MB = 2;//ケータイサイト転送

	/**
	 * 設定
	 */
	public $prefix      = "m";//モバイルのURIプレフィックス
	public $smartPrefix = "i";//スマートフォンのURIプレフィックス
	public $redirect = false;//自動転送機能を有効にするかどうか
	public $message = "Go to mobile page.";//転送文言
	public $redirectIphone = self::CONFIG_SP_REDIRECT_SP;//スマートフォンでの転送先
	public $redirectIpad = self::CONFIG_SP_REDIRECT_SP;//iPadでの転送先

	private $config;


	function getId(){
		return self::PLUGIN_ID;
	}

	function init(){
		CMSPlugin::addPluginMenu(self::PLUGIN_ID,array(
			"name"=>"携帯自動振り分けプラグイン",
			"description"=>"携帯電話やスマートフォンでのアクセス時に対応したページに転送します。",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.7"
		));
		CMSPlugin::addPluginConfigPage(self::PLUGIN_ID,array(
			$this,"config_page"
		));

		//二回目以降の動作
		if(CMSPlugin::activeCheck($this->getId())){

			//公開側へのアクセス時に必要に応じてリダイレクトする
			//出力前にセッションIDをURLに仕込むための宣言をしておく
			CMSPlugin::setEvent('onSiteAccess',$this->getId(),array($this,"onSiteAccess"));

/*
			//公開側へのアクセス時に必要に応じてリダイレクトする
			CMSPlugin::setEvent('onSiteAccess',$this->getId(),array($this,"redirect"));

			//出力前にセッションIDをURLに仕込むための宣言をしておく
			CMSPlugin::setEvent('onOutput',$this->getId(),array($this,"addSessionVar"));
*/
		//プラグインの初回動作
		}else{

		}
	}

	public static function register(){
		include_once(dirname(__FILE__)."/config.php");

		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new UtilMobileCheckPlugin();
		}

		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));
	}

	/**
	 *
	 * @return $html
	 */
	function config_page($message){
		$form = SOY2HTMLFactory::createInstance("UtilMobileCheckPluginConfigFormPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}

	/**
	 * サイトアクセス時の動作
	 */
	function onSiteAccess($obj){
		$this->redirect($obj);
		$this->addSessionVar();
	}

	/**
	 * 公開側の出力
	 */
	function redirect(){
		$config = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		$this->config = $config;

		$redirect = self::REDIRECT_PC;
		$isMobile = false;
		$isSmartPhone = false;

		//ケータイ
		if($this->isMobile()){
			$redirect = self::REDIRECT_MB;
			$isMobile = true;
		}

		//iPad
		if($this->isIpad()){
			if($config->redirectIpad == self::CONFIG_SP_REDIRECT_SP){
				$redirect = self::REDIRECT_SP;
				$isSmartPhone = true;
			}else{
				//PC
				$redirect = self::REDIRECT_PC;
			}
		}

		//スマートフォン
		if(defined("SOYCMS_IS_IPAD") && $this->isSmartPhone()){
			if($config->redirectIphone == self::CONFIG_SP_REDIRECT_SP){
				$redirect = self::REDIRECT_SP;
				$isSmartPhone = true;
			}elseif($config->redirectIphone == self::CONFIG_SP_REDIRECT_MB){
				//ケータイ
				$redirect = self::REDIRECT_MB;
				$isMobile = true;
			}else{
				//PC
				$redirect = self::REDIRECT_PC;
			}
		}

		//ここで定義を開始する
		if(!defined("SOYCMS_IS_MOBILE")){
			define("SOYCMS_IS_MOBILE",$isMobile);
		}

		if(!defined("SOYCMS_IS_SMARTPHONE")){
			define("SOYCMS_IS_SMARTPHONE",$isSmartPhone);
		}
		
		if($redirect != self::REDIRECT_PC){
			//prefixの決定
			if($redirect == self::REDIRECT_MB && strlen($config->prefix)){
				$prefix = $config->prefix;
			}
			if($redirect == self::REDIRECT_SP && strlen($config->smartPrefix)){
				$prefix = $config->smartPrefix;
			}

			//リダイレクト先の絶対パス
			$path = $this->getRedirectPath($prefix);

			if($path){

				//リダイレクト
				if($config->redirect){
					//if do not work Location header
					//CMSPageController::redirect()の中でexitをしているので、あらかじめ出力バッファーに入れておく必要がある。
					ob_start();
					echo "<a href=\"".htmlspecialchars($path,ENT_QUOTES,"UTF-8")."\">".htmlspecialchars($config->message,ENT_QUOTES,"UTF-8")."</a>";

					CMSPageController::redirect($path);
					exit;
				}

			}
		}
	}

	/**
	 * output_add_rewrite_varを使ってリンクのURLにセッションIDを付ける
	 */
	function addSessionVar(){
		if(
			$this->isMobile() && SOYCMS_MOBILE_CARRIER == "DoCoMo"
			&&
			(isset($_GET[session_name()])||isset($_POST[session_name()]))&&!isset($_COOKIE[session_name()])
		){
			session_regenerate_id(true);
			output_add_rewrite_var(session_name(), session_id());
		}
		return null;
	}

	/**
	 * ケータイからのアクセスかどうか
	 */
	function isMobile(){
		$carrier = "pc";
		$isMobile = false;

		$agent = @$_SERVER['HTTP_USER_AGENT'];

		if(preg_match("/^DoCoMo\/1.0/i", $agent)){
			//DoCoMo MOVA
			$isMobile = true;
			$carrier = "DoCoMo";
		}else if(preg_match("/^DoCoMo\/2.0/i", $agent)){
			//DoCoMo FOMA
			$isMobile = true;
			$carrier = "DoCoMo";
			if(strpos($agent,"c500")){
				//i-modeブラウザ2.0
				$carrier = "i-mode2.0";
			}
		}else if(preg_match("/^(J-PHONE|Vodafone|MOT-[CV]|SoftBank)/i", $agent)){
			//SoftBank
			$isMobile = true;
			$carrier = "SoftBank";
		}else if(preg_match("/^KDDI-/i", $agent) || preg_match("/UP\.Browser/i", $agent)){
			//au
			$isMobile = true;
			$carrier = "KDDI";
		}

		//別キャリアを見ている場合は一旦PCにとばす
		if($isMobile==false){
			$this->checkCarrier($this->config->prefix);
		}

		if(!defined("SOYCMS_MOBILE_CARRIER")){
			define("SOYCMS_MOBILE_CARRIER",$carrier);
		}

		return $isMobile;
	}

	/**
	 * iPadからのアクセスかどうか
	 */
	function isIpad(){
		$isIpad = false;

		$agent = @$_SERVER['HTTP_USER_AGENT'];

		if(preg_match("/iPad/",$agent)){
			$isIpad = true;
		}

		if(!defined("SOYCMS_IS_IPAD")){
			define("SOYCMS_IS_IPAD",$isIpad);
		}

		//iPadだった場合、スマホの設定を見ないことにする
		if($isIpad==true){
			if($this->config->redirectIpad == self::CONFIG_SP_REDIRECT_SP){
				$isSmartPhone = true;
			}else{
				$isSmartPhone = false;
			}

			//別キャリアを見ている場合は一旦PCにとばす
			if($isSmartPhone==false){
				$this->checkCarrier($this->config->smartPrefix);
			}
		}

		return $isIpad;
	}

	/**
	 * スマートフォンからのアクセスかどうか
	 */
	function isSmartPhone(){
		$isSmartPhone = false;

		if($this->config->redirectIphone ==self::CONFIG_SP_REDIRECT_SP){
			$agent = @$_SERVER['HTTP_USER_AGENT'];

			if(preg_match("/iPhone/i", $agent)){
				$isSmartPhone = true;
			}elseif(preg_match("/Mobile/", $agent) && preg_match("/Safari/", $agent)){
				$isSmartPhone = true;
			}elseif(preg_match("/Android/i", $agent)){
				$isSmartPhone = true;
			}elseif(preg_match("/Windows Phone/i", $agent)){
				$isSmartPhone = true;
			}

			//別キャリアを見ている場合は一旦PCにとばす
			if($isSmartPhone==false){
				$this->checkCarrier($this->config->smartPrefix);
			}
		}

		return $isSmartPhone;
	}
	
	//多言語化プラグインがすでに実行されているか調べる
	private function checkMultiLanguage($path){
		$reg = "/" . $this->smartPrefix . "/";
		include_once(dirname(dirname(__FILE__)) . "/util_multi_language/util_multi_language.php");
		$obj = CMSPlugin::loadPluginConfig("UtilMultiLanguagePlugin");
		if(is_null($obj)){
			$obj = new UtilMultiLanguagePlugin();
		}
		$config = $obj->getConfig();
		if(isset($config["check_browser_language_config"])) unset($config["check_browser_language_config"]);
		foreach($config as $conf){
			if(!isset($conf["prefix"]) || strlen($conf["prefix"]) === 0) continue;
			if(strpos($path, $reg . $conf["prefix"]) === 0) return true;
		}
		
		return false;
	}

	/**
	 * キャリア判定でパスとキャリアが間違っている時、
	 * 一旦PCサイトにリダイレクトさせてから、サイドキャリアに対応したサイトにリダイレクト
	 */
	function checkCarrier($prefix){
		$pathInfo = @$_SERVER['PATH_INFO'];
		if($pathInfo === "/".$prefix || strpos($pathInfo,"/".$prefix."/") === 0){
			$path = $this->getRedirectPcPath($prefix);
			SOY2PageController::redirect($path);
			exit;
		}
	}

	/**
	 * URLにプレフィックスを付けた絶対パスを返す
	 */
	function getRedirectPath($prefix){
		//REQUEST_URI
		$requestUri = $_SERVER['REQUEST_URI'];
		//$_GETの値（QUERY_STRING）を削除しておく
		if(strpos($requestUri,"?") !== false){
			$requestUri = substr($requestUri, 0, strpos($requestUri, "?"));
		}
		
		//スマホのプレフィックスと多言語のプレフィックスが付与されている場合はfalseを返す
		if(self::checkMultiLanguage($requestUri)){
			return false;
		}

		//PATH_INFO
		$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "" ;
		//先頭はスラッシュ
		if(strlen($pathInfo) && $pathInfo[0]!=="/"){
			$pathInfo = "/".$pathInfo;
		}

		//無限ループになるときはfalseを返す
		if( $pathInfo === "/".$prefix || strpos($pathInfo,"/".$prefix."/")===0 ){
			return false;
		}

		//サイトID：最初と最後に/を付けておく
		$siteDir = strlen($pathInfo) ? strtr(rawurldecode($requestUri),array($pathInfo => "")) : $requestUri ;//strtrのキーは空文字列であってはいけない
		//最初と最後に/を付けておく
		if(strpos($siteDir,"/")!==0){
			$siteDir = "/".$siteDir;
		}
		if(substr($siteDir, -1) !== "/"){
			$siteDir = $siteDir. "/";
		}

		//URLエンコードされたPATH_INFOを取るために、REQUEST_URIから作り直す
		$pathInfo = "/".substr($requestUri,strlen($siteDir));

		//prefixを付ける
		$path = $siteDir. $prefix. $pathInfo;

		//絶対パスにQuery Stringを追加する
		if(isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0){

			//セッションIDが入っている場合にregenerateされている可能性があるので
			if(strpos($_SERVER["QUERY_STRING"],session_name())!==false){
				$queries = explode("&",$_SERVER["QUERY_STRING"]);
				foreach($queries as $id => $item){
					if(strpos($item, session_name())===0){
						$queries[$id] = session_name()."=".session_id();
						break;
					}
				}
				$querystring = implode("&",$queries);
			}else{
				$querystring = $_SERVER["QUERY_STRING"];
			}


			$path .= "?" . $_SERVER["QUERY_STRING"];
		}

		return $path;
	}

	/**
	 * 各キャリアのprefixを除いたパスを返す
	 */
	function getRedirectPcPath($prefix){
		//REQUEST_URI
		$requestUri = rawurldecode($_SERVER['REQUEST_URI']);

		//getの値を取り除く
		if(strpos($requestUri,"?")!==false){
			$requestUri = substr($requestUri,0,strpos($requestUri,"?"));
		}

		//PATH_INFO
		$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "" ;
		//先頭はスラッシュ
		if(strlen($pathInfo) && $pathInfo[0]!=="/"){
			$pathInfo = "/".$pathInfo;
		}




		//サイトID：最初と最後に/を付けておく
		$path = strlen($pathInfo) ? strtr($requestUri,array($pathInfo => "")) : $requestUri ;//strtrのキーは空文字列であってはいけない
		$path = $requestUri;
		if($path[0]!== "/"){
			$path = "/".$path;
		}
		if(substr($path, -1) !== "/"){
			$path = $path. "/";
		}

		//各キャリアのprefixを除いたものを返す
		if(strrpos($path,"/".$prefix)==strlen($path)-strlen($prefix)-1){
			$path = str_replace("/".$prefix,"",$path);
		}else{
			$path = str_replace("/".$prefix."/","/",$path);
		}

		//絶対パスにQuery Stringを追加する
		if(isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0){
			$path = rtrim($path,"/");
			$path .= "?" . $_SERVER["QUERY_STRING"];
		}

		return $path;
	}
	
	function getSmartPrefix(){
		return $this->smartPrefix;
	}
	function setSmartPrefix($smartPrefix){
		$this->smartPrefix = $smartPrefix;
	}
	function setPrefix($prefix){
		$this->prefix = $prefix;
	}
	function setRedirect($redirect){
		$this->redirect = $redirect;
	}
	function setMessage($message){
		$this->message = $message;
	}
	function setRedirectIphone($redirectIphone){
		$this->redirect_iphone = $redirectIphone;
	}
	function setRedirectIpad($redirectIpad){
		$this->redirectIpad = $redirectIpad;
	}
}
?>