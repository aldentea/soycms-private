<?php
SOY2::import("domain.admin.Site");
class IndexPage extends CMSWebPageBase{

	function doPost(){

    	if(soy2_check_token()){

			if(isset($_POST["file_db_update"])){

				SOY2::import("util.CMSFileManager");

				CMSFileManager::deleteAll();

				set_time_limit(0);

				$sites = $this->getSiteList();
				foreach($sites as $site){
					CMSFileManager::setSiteInformation($site->getId(), $site->getUrl(), $site->getPath());
					CMSFileManager::insertAll($site->getPath());
				}
				$this->jump("?file_db_updated");
				exit;

			}

			if(isset($_POST["cache_clear"])){
				set_time_limit(0);

				$root = dirname(SOY2::RootDir());
				CMSUtil::unlinkAllIn($root . "/admin/cache/");
				CMSUtil::unlinkAllIn($root . "/soycms/cache/");
				CMSUtil::unlinkAllIn($root . "/app/cache/", true);

				$sites = $this->getSiteList();
				foreach($sites as $site){
					CMSUtil::unlinkAllIn($site->getPath().".cache/", true);
				}

				$this->jump("?cache_cleared");
				exit;
			}

			$this->jump("");
    	}
	}

	function IndexPage($arg){
		WebPage::WebPage();

		/*
		 * データベースのバージョンチェック
		 * ここまででDataSetsを呼び出していないこと ← そのうち破綻する気がする
		 * @TODO 初期管理者以外ではバージョンアップを促す文言を出すとか
		 */
		$this->run("Database.CheckVersionAction");

		//ユーザに割り当てられたサイト/Appが１つのときは、そのサイトにログイン(redirect)するようにする。
		$this->run("SiteRole.DefaultLoginAction");

		$this->createAdd("file_db_massage", "HTMLModel", array(
			"visible" => (isset($_GET["file_db_updated"]))
		));

		$this->createAdd("cache_clear_massage", "HTMLModel", array(
			"visible" => (isset($_GET["cache_cleared"]))
		));


		//ファイルDB更新、キャッシュの削除
		$this->createAdd("file_form", "HTMLForm");
		$this->createAdd("cache_form", "HTMLForm");

		//現在のユーザーがログイン可能なサイトのみを表示する
		$loginableSiteList = $this->getLoginableSiteList();
		$this->createAdd("list", "SiteList", array(
			"list" => $loginableSiteList
		));

		$this->createAdd("no_site","HTMLModel",array(
			"visible" => (count($loginableSiteList)<1)
		));

		if(!UserInfoUtil::isDefaultUser()){
			DisplayPlugin::hide("only_default_user");
		}

		$this->createAdd("create_link","HTMLLink",array("link"=>SOY2PageController::createLink("Site.Create")));
		$this->createAdd("addAdministrator","HTMLLink",array("link"=>SOY2PageController::createLink("Administrator.Create")));

		//アプリケーション
		$applications = $this->getLoginiableApplicationLists();
		$this->createAdd("application_list","ApplicationList",array(
			"list" => $applications
		));

		$this->createAdd("application_list_wrapper","HTMLModel",array(
			"visible" => (count($applications)>0)
		));

		$this->createAdd("allow_php","HTMLModel",array(
			"visible" => (defined("SOYCMS_ALLOW_PHP_SCRIPT") && SOYCMS_ALLOW_PHP_SCRIPT)
		));
	}

	/**
	 * サイト一覧
	 */
	function getSiteList(){
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		return $SiteLogic->getSiteList();
	}

	/**
	 * 現在のユーザIDからログイン可能なサイトオブジェクトのリストを取得する
	 */
	function getLoginableSiteList(){
		$SiteLogic = SOY2Logic::createInstance("logic.admin.Site.SiteLogic");
		return $SiteLogic->getSiteByUserId(UserInfoUtil::getUserId());
	}

	/**
	 * 2008-07-24 ログイン可能なアプリケーションを読み込む
	 */
	function getLoginiableApplicationLists(){
		$appLogic = SOY2Logic::createInstance("logic.admin.Application.ApplicationLogic");
		if(UserInfoUtil::isDefaultUser()){
			return $appLogic->getApplications();
		}else{
			return $appLogic->getLoginableApplications(UserInfoUtil::getUserId());
		}
	}


}

class SiteList extends HTMLList{

	function replaceTooLongHost($url){


		$array = parse_url($url);


		$host = $array["host"];
		if(isset($array["port"]))$host .=   ":" . $array["port"];

		if(strlen($host)>30){
			$host = mb_strimwidth($host,0,30,"...");
		}

		$url = $array["scheme"] . "://" . $host . $array["path"];

		return $url;

	}

	protected function populateItem($entity){

		$siteName = $entity->getSiteName();
		if($entity->getIsDomainRoot()){
			$siteName = "*" . $siteName;
		}

		$this->add("site_name",SOY2HTMLFactory::createInstance("HTMLLabel",array(
			"text" => $siteName
		)));

		/**
		 * ログイン後の転送先（$_GET["r"]）があれば再度$_GET["r"]に入れておく
		 */
		$param = array();
		if(isset($_GET["r"]) && strlen($_GET["r"]) && strpos($_GET["r"],"/soycms/")) $param["r"] = $_GET["r"];
		$this->createAdd("login_link","HTMLLink",array(
			"link" => $entity->getLoginLink($param)
		));

		$this->createAdd("site_link","HTMLLink",array(
			"link" => $entity->getUrl(),
			"text" => $this->replaceTooLongHost($entity->getUrl())
		));

		$rootLink = UserInfoUtil::getSiteURLBySiteId("");
		$this->createAdd("domain_root_site_url","HTMLLink",array(
			"link" => $rootLink,
			"text" => $this->replaceTooLongHost($rootLink),
			"visible" => $entity->getIsDomainRoot()
		));

		$this->createAdd("auth_link","HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.SiteRole.".$entity->getId())
		));

		$this->createAdd("root_site_link","HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.SiteRoot.".$entity->getId())
		));

		$link = SOY2HTMLFactory::createInstance("HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.Remove.".$entity->getId())
		));

		$link->setAttribute("onclick",'javascript:return confirm("'.CMSMessageManager::get("SOYCMS_CONFIRM_DELETE").'");');
		$this->add("remove_link",$link);

	}

}


class ApplicationList extends HTMLList{
	protected function populateItem($entity,$key){
		$this->createAdd("name","HTMLLabel",array(
			"text" => $entity["title"]
		));

		/**
		 * ログイン後の転送先（$_GET["r"]）があれば再度$_GET["r"]に入れておく
		 */
		$param = array();
		if(isset($_GET["r"]) && strlen($_GET["r"]) && strpos($_GET["r"],"/app/index.php/".$key)) $param["r"] = $_GET["r"];
		$this->createAdd("login_link","HTMLLink",array(
			"link" => SOY2PageController::createRelativeLink("../app/index.php/" . $key).( count($param) ? "?".http_build_query($param) : "" )
		));

		$this->createAdd("description","HTMLLabel",array(
			"text" => $entity["description"]
		));

		$this->createAdd("version","HTMLLabel",array(
			"text" => (isset($entity["version"])) ? "ver. " . $entity["version"] : "",
			"visible" => (isset($entity["version"]))
		));
	}
}
?>