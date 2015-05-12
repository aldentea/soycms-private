<?php

class UserInfoUtil implements IUserInfoUtil{

	/**
	 * ログアウトする
	 */
	public static function logout(){
		SOY2ActionSession::getUserSession()->setAuthenticated(false);
		SOY2ActionSession::getUserSession()->clearAttributes();
	}

	/**
	 * 現在ログインしているかどうかを返す
	 * SOY2Actionを利用
	 */
    public static function isLoggined(){

		if(defined("SOYCMS_LOGIN_LIFETIME") && SOYCMS_LOGIN_LIFETIME > 0){
			//一定時間アクセスがなかったらログアウトする
			$lastAccessTime = SOY2ActionSession::getUserSession()->getAttribute("lastAccessTime");
			if($lastAccessTime + SOYCMS_LOGIN_LIFETIME < time()){
				self::logout();
			}
		}

		SOY2ActionSession::getUserSession()->setAttribute("lastAccessTime",time());

    	$isAuth = SOY2ActionSession::getUserSession()->getAuthenticated();
    	if($isAuth){
			return true;
    	}else{
    		self::logout();
    		return false;
    	}
    }

    /**
     * 現在ログインユーザがデフォルトユーザ（初期管理者）であるかどうか
     */
    public static function isDefaultUser(){
    	return SOY2ActionSession::getUserSession()->getAttribute("isdefault");
    }

    /**
     * 現在ログインしているユーザが一般管理者以上かどうか
     */
    public static function hasSiteAdminRole(){
    	$isSiteAdministrator = (boolean)SOY2ActionSession::getUserSession()->getAttribute("isSiteAdministrator");
    	$isDefaultUser = (boolean)self::isDefaultUser();
    	return ($isSiteAdministrator || $isDefaultUser);
    }

    /**
     * 現在ログインしているユーザがエントリー公開権限を持っているか
     */
    public static function hasEntryPublisherRole(){
		return (boolean)SOY2ActionSession::getUserSession()->getAttribute("isEntryPublisher");
    }

	/**
	 * 現在ログインしているユーザが自動ログインしたユーザかどうか
	 * （１つのサイト/アプリにしかログイン権限がないということ）
	 */
    public static function hasOnlyOneRole(){
    	return (boolean)SOY2ActionSession::getUserSession()->getAttribute("hasOnlyOneRole");
    }

    /**
     * 現在ログインしているユーザのIDを返す
     */
    public static function getUserId(){
    	return SOY2ActionSession::getUserSession()->getAttribute("userid");
    }

    /**
     * 現在ログインしているユーザのログインID（User.UserId）を返す
     */
    public static function getLoginId(){
    	return SOY2ActionSession::getUserSession()->getAttribute("loginid");
    }

    /**
     * 現在ログインしているユーザ名を返す
     */
     public static function getUserName(){
    	return htmlspecialchars(SOY2ActionSession::getUserSession()->getAttribute("username"));
    }

    /**
     * 現在ログインしているユーザのメールアドレスを返す
     */
     public static function getUserMailAddress(){
    	return htmlspecialchars(SOY2ActionSession::getUserSession()->getAttribute("email"));
    }

    /**
     * 現在ログインしているサイトの情報を返す
     */
    public static function getSite(){
    	return SOY2ActionSession::getUserSession()->getAttribute("Site");
    }

    /**
     * 現在ログインしているサイトのIDを返す
     */
    public static function getSiteId(){
    	return self::getSite()->getId();
    }

    /**
     * サイトの情報を更新する（セッション内部）
     */
    public static function updateSite(Site $site){
    	SOY2ActionSession::getUserSession()->setAttribute("Site",$site);
    }

    /**
     * 現在ログインしているサイトのディレクトリを返す
     *
     * @param isRealpath(=false) trueならば場所を返す
     */
    public static function getSiteDirectory($realpath = false){
    	return self::getSite()->getPath();
    }

    /**
     * サイトのURLを取得
     */
    public static function getSiteURL(){
    	return self::getSite()->getUrl();
    }

    /**
     * サイトの公開URLを取得（ルート設定ならルート設定のURLを返す）
     */
	public static function getSitePublishURL(){
    	if(self::getSiteIsDomainRoot()){
    		return self::getSiteURLBySiteId("");
    	}else{
	    	return self::getSite()->getUrl();
    	}
    }

    /**
     * サイトがルート設定されているかどうか
     */
    public static function getSiteIsDomainRoot(){
		return self::getSite()->getIsDomainRoot();
    }

    /**
     * URLからサーバーのパスを取得する
     */
    public static function url2serverpath($address){

    	$address = preg_replace('/https?:\/\/'.$_SERVER["HTTP_HOST"].'/i',"",$address);
    	$server = preg_replace('/https?:\/\/'.$_SERVER["HTTP_HOST"].'/i',"",SOY2PageController::createRelativeLink("./"));
    	$counts =explode("/",$server);

    	if($address[0] == "/"){
    		$address = substr($address,1);
    	}

    	foreach($counts as $count){
    		if(strlen($count)){
    			$address = '../' . $address;
    		}
    	}

    	return $address;
    }

    public static function getSiteConfig(){
    	return SOY2DAOFactory::create("cms.SiteConfigDAO")->get();
    }

    /**
     * サイトのIDからサイトのURLを取得
     * 　ルート設定などは無視したデフォルトのURL（$siteId = ""とすればルート設定のURLが取れる）
     * 　SOYCMS_TARGET_URLが指定されていると管理画面とは別のURLとなる
     */
    public static function getSiteURLBySiteId($siteId = null){
    	if(is_null($siteId)) $siteId = self::getSite()->getSiteId();


    	if(defined("SOYCMS_TARGET_URL")){
    		$url = SOYCMS_TARGET_URL;
    		if($url[strlen($url)-1] != "/")$url .= "/";
    	}else{
	    	$http = (isset($_SERVER["HTTPS"]) || defined("SOY2_HTTPS") && SOY2_HTTPS) ? "https" : "http";
	    	$host = $_SERVER['SERVER_NAME'];
	    	if( (!isset($_SERVER["HTTPS"]) && $_SERVER['SERVER_PORT'] != 80) OR (isset($_SERVER["HTTPS"]) && $_SERVER['SERVER_PORT'] != 443) ){
	    		$host .= ":".$_SERVER['SERVER_PORT'];
	    	}

	    	$url = $http . "://" . $host . "/";
    	}

    	if(defined("SOYCMS_TARGET_PREFIX")){
    		$siteId = SOYCMS_TARGET_PREFIX  . ((strlen($siteId) > 0) ? "/" . $siteId : "");
    	}

    	if(strlen($siteId)>0){
    		return $url . $siteId . "/";
    	}else{
    		return $url;
    	}
    }
}
?>