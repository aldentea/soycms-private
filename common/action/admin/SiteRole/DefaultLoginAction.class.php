<?php

/**
 * ログインできるサイトまたはアプリが一つしかないときはそのサイト、アプリにログインする
 */
class DefaultLoginAction extends SOY2Action{

	private $redirect;

	function execute($req,$form,$res) {
    	//転送先
    	$this->redirect = $req->getParameter('r');

    	$userId = $this->getUserSession()->getAttribute('userid');

		//基本的には全てfalse
		$isSiteAdministrator = false;
		$isEntryAdministrator = false;
		$isEntryPublisher = false;


		if(! UserInfoUtil::isDefaultUser()){//初期管理者は自動ログインしない
			$siteRoleDao = SOY2DAOFactory::create("admin.SiteRoleDAO");
			try{
				$siteRoles = $siteRoleDao->getByUserId($userId);
			}catch(Exception $e){
				$siteRoles = array();
			}

			$appRoleDao = SOY2DAOFactory::create("admin.AppRoleDAO");
			try{
				$appRoles = $appRoleDao->getByUserId($userId);
			}catch(Exception $e){
				$appRoles = array();
			}

			if(count($siteRoles) == 1 && count($appRoles) == 0){
				//ログインできるのがサイト１個のみなので、それにログイン
				$siteRole = $siteRoles[0];

				/*
				 * 管理画面に移動
				 */
				$this->redirectToCMS($siteRole);

				return SOY2Action::SUCCESS;
			}elseif(count($siteRoles) == 0 && count($appRoles) == 1){
				$res = $this->checkAppRoles($appRoles);

				if($res){
					//ログインできるのがApp１個のみなので、それにログイン
					$appRole = array_shift($appRoles);//@index appIdがかかってるので$appRoles[0]ではだめ

					/*
					 * 管理画面に移動
					 */
					if( $this->redirectToApp($appRole->getAppId()) ){
						return SOY2Action::SUCCESS;
					}

				}
			}elseif(count($siteRoles) == 1 && count($appRoles) == 1){
				//ShopはSiteにも登録されているのでこれが必要

				$siteRole = array_shift($siteRoles);
				$appRole  = array_shift($appRoles);//@index appIdがかかってるので$appRoles[0]ではだめ

				//SOY Shopだけに権限があるとき
				if("shop" == $appRole->getAppId()){
					//サイト情報を取得
					$site = $this->getSiteById($siteRole->getSiteId());
					if( !$site ){
						return SOY2Action::FAILED;
					}

					//サイトがショップのサイトなら管理画面に移動
					if( Site::TYPE_SOY_SHOP == $site->getSiteType() ){
						$this->redirectToApp($appRole->getAppId());
						return SOY2Action::SUCCESS;
					}
				}
			}
		}

		return SOY2Action::FAILED;
    }

	/**
	 * SOY CMSのサイト管理画面に移動
	 * 転送先が指定されている場合はそこへ
	 */
    function redirectToCMS($siteRole){
		//ここは1つのサイトの権限を持っている人のみ
		$this->getUserSession()->setAttribute("hasOnlyOneRole",true);
		$this->getUserSession()->setAttribute("onlyOneSiteAdministor",true);

		//権限周りの値
		$this->getUserSession()->setAttribute("isSiteAdministrator",$siteRole->isSiteAdministrator());
		$this->getUserSession()->setAttribute("isEntryAdministor",$siteRole->isEntryAdministrator());
		$this->getUserSession()->setAttribute("isEntryPublisher",$siteRole->isEntryPublisher());

		//ログイン先のサイトの情報
		$site = $this->getSiteById($siteRole->getSiteId());
		if( !$site ){
			return false;
		}
		$this->getUserSession()->setAttribute("Site",$site);

		if(strlen($this->redirect) >0 && CMSAdminPageController::isAllowedPath($this->redirect, "../soycms/")){
			SOY2PageController::redirect($this->redirect);
		}else{
			SOY2PageController::redirect("../soycms/");
		}

		return true;
    }

	/**
	 * SOY Appの管理画面に移動
	 * 転送先が指定されている場合はそこへ
	 */
    function redirectToApp($appId){
		//ここは1つのAppの権限を持っている人のみ
		$this->getUserSession()->setAttribute("hasOnlyOneRole",true);
		$this->getUserSession()->setAttribute("onlyOneAppAdministor",true);

		if(strlen($this->redirect) >0 && CMSAdminPageController::isAllowedPath($this->redirect, "../app/index.php/" . $appId)){
			SOY2PageController::redirect($this->redirect);
		}else{
			SOY2PageController::redirect("../app/index.php/" . $appId);
		}
    }

    /**
     * App権限でApp操作者の場合はAppのログインを表示しない
     * shopの場合はApp管理画面にログインされると都合が悪いが、
     * 他のAppの場合では管理画面にログインできる必要があるかもしれないので、
     * この仕様は要検討課題
     */
    function checkAppRoles($appRoles){
    	$res = false;

    	foreach($appRoles as $role){
    		if($role->getAppRole() == AppRole::APP_USER)$res = true;
    	}

    	return $res;
    }

    function getSiteById($siteId){
		try{
			$dao = SOY2DAOFactory::create("admin.SiteDAO");
			return $dao->getById($siteId);
		}catch(Exception $e){
			return false;
		}
    }
}
