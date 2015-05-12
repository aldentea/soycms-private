<?php

class SiteConfig {

	const CHARSET_UTF_8 = 1;
	const CHARSET_SHIFT_JIS = 2;
	const CHARSET_EUC_JP = 3;

	private $name;
    private $siteConfig;
    private $charset;
    private $description;

    function getSiteConfig(){
    	return $this->siteConfig;
    }
    function setSiteConfig($config){
    	if(is_string($config)){
    		$this->siteConfig = $config;
    	}else{
    		$this->siteConfig = serialize($config);
    	}
    }
    function getCharset() {
    	return $this->charset;
    }
    function setCharset($charset) {
    	$this->charset = $charset;
    }
    function getName() {
    	return $this->name;
    }
    function setName($name){
    	$this->name = $name;
    }
    function getDescription() {
    	return $this->description;
    }
    function setDescription($description) {
    	$this->description = $description;
    }

   	/**
   	 * 最終更新時刻を設定
   	 */
    function notifyUpdate(){
    	$config = (is_string($this->siteConfig)) ? unserialize($this->siteConfig) : array();
    	$config["udate"] = time();

    	$this->setSiteConfig($config);
    }

    /**
     * 最終更新時刻を取得
     */
    function getLastUpdateDate(){
    	$config = @unserialize($this->getSiteConfig());
    	if(is_array($config)){
    		return (isset($config["udate"])) ? $config["udate"] : null;
    	}

    	return strtotime(date("Y-m-d 00:00:00"));
    }

    /**
     * 日付毎にディレクトリを作成するかどうか
     */
    function isCreateDefaultUploadDirectory(){
    	$config = @unserialize($this->getSiteConfig());
    	if(is_array($config)){
    		return (isset($config["createUploadDirectoryByDate"])) ? (boolean)$config["createUploadDirectoryByDate"] : false;
    	}

    	return false;
    }

    /**
     * 日付毎にディレクトリを作成するかどうかのフラグを保存
     */
    function setCreateUploadDirectoryByDate($value){
    	$config = (is_string($this->siteConfig)) ? unserialize($this->siteConfig) : array();
    	$config["createUploadDirectoryByDate"] = (int)$value;

    	$this->setSiteConfig($config);
    }

    /**
     * 管理側にログインしている時のみ表示するかどうか
     */
    function isShowOnlyAdministrator(){
    	$config = @unserialize($this->getSiteConfig());
    	if(is_array($config)){
    		return (isset($config["isShowOnlyAdministrator"])) ? (boolean)$config["isShowOnlyAdministrator"] : false;
    	}

    	return false;
    }

    /**
     * 日付毎にディレクトリを作成するかどうかのフラグを保存
     */
    function setIsShowOnlyAdministrator($value){
    	$config = (is_string($this->siteConfig)) ? unserialize($this->siteConfig) : array();
    	$config["isShowOnlyAdministrator"] = (int)$value;

    	$this->setSiteConfig($config);
    }

    function getDefaultUploadDirectory(){
    	$array = is_array($this->getSiteConfig()) ? $this->getSiteConfig() : unserialize($this->getSiteConfig());
    	$dir =  isset($array["upload_directory"]) ? $array["upload_directory"] : "/files";

    	$dir = str_replace("..","",$dir);
    	if($dir[0] != '/'){
    		$dir = '/'.$dir;
    	}

    	while(substr($dir,-1) == '/'){
	    	$dir = substr($dir,0,-1);
    	}

    	return $dir;
    }

    /**
     * アップロードディレクトリを作成して取得
     */
    function getUploadDirectory(){
    	$dir = $this->getDefaultUploadDirectory();


    	//日付別ディレクトリ
    	if($this->isCreateDefaultUploadDirectory()){
			SOY2::import("util.CMSFileManager");

    		$targetDir = UserInfoUtil::getSiteDirectory() . $dir . "/" . date("Ymd");
    		$targetUrl = $dir . "/" . date("Ymd");

    		//存在しなかったら作成する
    		if(!file_exists($targetDir)){
    			$res = @mkdir($targetDir);
    			if(!$res)return $dir;	//作成に失敗したら$dir

    			@chmod($targetDir, 0777);

    			//ファイルDBに追加
    			CMSFileManager::add($targetDir);
    		}

    		//ファイルDBになかったら追加する
    		try{
    			CMSFileManager::get($targetDir,$targetDir);
    		}catch(Exception $e){
    			CMSFileManager::add($targetDir);
    		}

    		if(file_exists($targetDir) && is_writable($targetDir)){
    			return $targetUrl;
    		}
    	}

    	return $dir;
    }

    function setDefaultUploadDirectory($dir){
    	$array = is_array($this->getSiteConfig()) ? $this->getSiteConfig() : unserialize($this->getSiteConfig());
    	$array["upload_directory"] = $dir;
    	$this->setSiteConfig($array);
    }

    /**
     * 文字コード変換
     * (UTF-8→サイトの文字コード)
     */
    function convertToSiteCharset($contents){
    	switch($this->charset){
    		case SiteConfig::CHARSET_UTF_8:
    			break;
    		case SiteConfig::CHARSET_SHIFT_JIS:
    			$contents = mb_convert_encoding($contents,'SJIS-win','UTF-8');
    			break;
    		case SiteConfig::CHARSET_EUC_JP:
    			$contents = mb_convert_encoding($contents,'eucJP-win','UTF-8');
    			break;
    		default:
    			break;
    	}
    	return $contents;
    }

    function getCharsetText(){
    	switch($this->charset){
    		case SiteConfig::CHARSET_UTF_8:
    			return "UTF-8";
    			break;
    		case SiteConfig::CHARSET_SHIFT_JIS:
    			return "Shift_JIS";
    			break;
    		case SiteConfig::CHARSET_EUC_JP:
    			return "EUC-JP";
    			break;
    		default:
    			break;
    	}
    }

    /**
     * 文字コード変換
     * (サイトの文字コード→UTF8)
     */
    function convertFromSiteCharset($contents){
    	switch($this->charset){
    		case SiteConfig::CHARSET_UTF_8:
    			break;
    		case SiteConfig::CHARSET_SHIFT_JIS:
    			$contents = mb_convert_encoding($contents,'UTF-8','SJIS-win');
    			break;
    		case SiteConfig::CHARSET_EUC_JP:
    			$contents = mb_convert_encoding($contents,'UTF-8','eucJP-win');
    			break;
    		default:
    			break;
    	}
    	return $contents;
    }

    public static function getCharsetLists(){
    	return array(
    		SiteConfig::CHARSET_UTF_8     => "UTF-8",
    		SiteConfig::CHARSET_SHIFT_JIS => "Shift_JIS",
    		SiteConfig::CHARSET_EUC_JP    => "EUC-JP"
    	);
    }

}
?>