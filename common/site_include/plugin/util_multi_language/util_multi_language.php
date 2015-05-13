<?php
UtilMultiLanguagePlugin::register();

class UtilMultiLanguagePlugin{

	const PLUGIN_ID = "UtilMultiLanguagePlugin";

	private $config;
	private $check_browser_language;


	function getId(){
		return self::PLUGIN_ID;
	}

	function init(){
		CMSPlugin::addPluginMenu(self::PLUGIN_ID,array(
			"name"=>"多言語サイトプラグイン",
			"description"=>"サイトの言語設定を確認し、指定したURLへリダイレクトします。",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.5"
		));
		CMSPlugin::addPluginConfigPage(self::PLUGIN_ID,array(
			$this,"config_page"
		));

		//二回目以降の動作
		if(CMSPlugin::activeCheck($this->getId())){

			//公開側へのアクセス時に必要に応じてリダイレクトする
			//出力前にセッションIDをURLに仕込むための宣言をしておく
			CMSPlugin::setEvent('onSiteAccess', $this->getId(), array($this, "onSiteAccess"));
			CMSPlugin::setEvent('onPageOutput', self::PLUGIN_ID, array($this, "onPageOutput"));

		//プラグインの初回動作
		}else{
			//
		}
	}

	/**
	 *
	 * @return $html
	 */
	function config_page($message){
		include_once(dirname(__FILE__) . "/config/UtilMultiLanguageConfigFormPage.class.php");
		$form = SOY2HTMLFactory::createInstance("UtilMultiLanguageConfigFormPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}

	/**
	 * サイトアクセス時の動作
	 */
	function onSiteAccess($obj){
		$this->redirect($obj);
	}

	/**
	 * 公開側の出力
	 */
	function redirect(){
		
		//端末振り分けプラグインの方とのバッティングを避けるため、モバイルもしくはスマホのリダイレクトの状態ならば処理を止める
		if((defined("SOYCMS_IS_MOBILE") && SOYCMS_IS_MOBILE) || (defined("SOYCMS_IS_SMARTPHONE") && SOYCMS_IS_SMARTPHONE)) return;
		
		$config = $this->getConfig();
		
		//ブラウザの言語設定を確認するモード
		if($this->check_browser_language){
			$language = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
			
			if(preg_match('/^en/i', $language)) {
				$languageConfig = "en";
			}else{
				$languageConfig = "jp";
			}
		//言語切替ボタンを使うモード
		}else{
			$userSession = SOY2ActionSession::getUserSession();
			
			//言語切替ボタンを押したとき
			if(isset($_GET["language"])){
				$languageConfig = trim($_GET["language"]);
				$userSession->setAttribute("soycms_publish_language", $languageConfig);
			//押してないとき
			}else{
				$languageConfig = $userSession->getAttribute("soycms_publish_language");
				if(is_null($languageConfig)){
					//SOY Shopの方の言語設定も確認する
					$languageConfig = $userSession->getAttribute("soyshop_publish_language");
					
					if(is_null($languageConfig)){
						$languageConfig = "jp";
						$userSession->setAttribute("soycms_publish_language", $languageConfig);
					}
				}
			}
		}
		
		if(!defined("SOYCMS_PUBLISH_LANGUAGE")) define("SOYCMS_PUBLISH_LANGUAGE", $languageConfig);
		
		$redirectPath = $this->getRedirectPath($config);
		
		if($this->checkRedirectPath($redirectPath)){
			CMSPageController::redirect($redirectPath);
			exit;
		}
	}

	/**
	 * URLにプレフィックスを付けた絶対パスを返す
	 */
	function getRedirectPath($config){
		//REQUEST_URI
		$requestUri = $_SERVER['REQUEST_URI'];
		//$_GETの値（QUERY_STRING）を削除しておく
		if(strpos($requestUri, "?") !== false){
			$requestUri = substr($requestUri, 0, strpos($requestUri, "?"));
		}

		//PATH_INFO
		$pathInfo = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : "" ;
		//先頭はスラッシュ
		if(strlen($pathInfo) && $pathInfo[0] !== "/"){
			$pathInfo = "/" . $pathInfo;
		}
		
		$prefix = (isset($config[SOYCMS_PUBLISH_LANGUAGE]["prefix"])) ? $config[SOYCMS_PUBLISH_LANGUAGE]["prefix"] : "";

		//無限ループになるときはfalseを返す
		if( $pathInfo === "/" . $prefix || strpos($pathInfo, "/" . $prefix . "/") === 0 ){
			return false;
		}

		//サイトID：最初と最後に/を付けておく
		$siteDir = strlen($pathInfo) ? strtr(rawurldecode($requestUri), array($pathInfo => "")) : $requestUri;//strtrのキーは空文字列であってはいけない
		//最初と最後に/を付けておく
		if(strpos($siteDir, "/") !== 0){
			$siteDir = "/" . $siteDir;
		}
		if(substr($siteDir, -1) !== "/"){
			$siteDir = $siteDir . "/";
		}
		
		//URLエンコードされたPATH_INFOを取るために、REQUEST_URIから作り直す
		$pathInfo = "/" . substr($requestUri, strlen($siteDir));
		
		//prefixが0文字の場合はpathInfoの値から他のprefixがないかを調べる
		if(strlen($prefix) === 0){
			$pathInfo = $this->removeInsertPrefix($pathInfo, $config);
		}

		//prefixを付ける
		$path = $siteDir. $prefix. $pathInfo;
		
		//prefixを付けると//になることがあるので、ここで//の場合は/にする様に処理
		$path = str_replace("//", "/", $path);
		
		//絶対パスにQuery Stringを追加する
		if(isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0){

			//セッションIDが入っている場合にregenerateされている可能性があるので
			if(strpos($_SERVER["QUERY_STRING"], session_name()) !== false){
				$queries = explode("&", $_SERVER["QUERY_STRING"]);
				foreach($queries as $id => $item){
					if(strpos($item, session_name()) === 0){
						$queries[$id] = session_name() . "=" . session_id();
						break;
					}
				}
				$querystring = implode("&", $queries);
			}else{
				$querystring = $_SERVER["QUERY_STRING"];
			}

			$path .= "?" . $_SERVER["QUERY_STRING"];
		}

		return $path;
	}
	
	function onPageOutput($obj){
	
		$languages = array("jp", "en");
		
		foreach($languages as $language){
			$obj->addLink("language_" . $language . "_link", array(
				"soy2prefix" => "cms",
				"link" => "?language=" . $language
			));
		}
	}
	
	function removeInsertPrefix($path, $config){
		foreach($config as $conf){
			if(!isset($conf["prefix"])) continue;
			if(preg_match('/\/' . $conf["prefix"] . '\//', $path) || $path == "/" . $conf["prefix"]){
				$path = str_replace("/" . $conf["prefix"], "", $path);
				break;
			}
		}
		return $path;
	}
	
	//リダイレクトを行う必要があるか調べる
	function checkRedirectPath($path){
		if($path === false) return false;
		
		if(strpos($path, "/") !== 0){
			$path = "/" . $path;
		}
		
		$requestUri = $_SERVER["REQUEST_URI"];
		if(strpos($requestUri, "/") !== 0){
			$requestUri = "/" . $requestUri;
		}
		
		return ($path !== $requestUri);
	}

	function getConfig(){
		if(strlen($this->config)){
			return soy2_unserialize($this->config);
		}else{
			return $this->config;
		}
	}
	
	function setConfig($config){
		$this->config = soy2_serialize($config);
	}
	
	function getCheckBrowserLanguage(){
		return $this->check_browser_language;
	}
	
	function setCheckBrowserLanguage($check_browser_language){
		$this->check_browser_language = $check_browser_language;
	}
	
	public static function register(){
		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new UtilMultiLanguagePlugin();
		}

		CMSPlugin::addPlugin(self::PLUGIN_ID, array($obj, "init"));
	}
}
?>