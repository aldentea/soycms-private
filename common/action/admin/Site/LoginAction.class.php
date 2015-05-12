<?php

class LoginAction extends SOY2Action{

	var $siteId;

	function setSiteId($id){
		$this->siteId = $id;
	}

	function execute(){

		/*
		 * サイトの権限持ってるかどうかチェック
		 */
		$userId = UserInfoUtil::getUserId();

		//基本的には全てfalse
		$isSiteAdministrator = false;
		$isEntryAdministrator = false;
		$isEntryPublisher = false;

		if(UserInfoUtil::isDefaultUser()){
			//初期管理者は全権限を持つ
			$isSiteAdministrator = true;
			$isEntryAdministrator = true;
			$isEntryPublisher = true;
		}else{
			$siteRoleDao = SOY2DAOFactory::create("admin.SiteRoleDAO");

			try{
				$siteRoles = $siteRoleDao->getByUserId($userId);
			}catch(Exception $e){
				//
			}

			$flag = false;
			foreach($siteRoles as $siteRole){

				if($siteRole->getSiteId() == $this->siteId){
					$flag = true;
					$isSiteAdministrator = $siteRole->isSiteAdministrator();
					$isEntryAdministrator = $siteRole->isEntryAdministrator();
					$isEntryPublisher = $siteRole->isEntryPublisher();
					break;
				}
			}

			if($flag != true){
				return SOY2Action::FAILED;
			}
		}

		$dao = SOY2DAOFactory::create("admin.SiteDAO");
		$site = $dao->getById($this->siteId);

		$this->getUserSession()->setAttribute("Site",$site);
		$this->getUserSession()->setAttribute("isSiteAdministrator",$isSiteAdministrator);
		$this->getUserSession()->setAttribute("isEntryAdministor",$isEntryAdministrator);
		$this->getUserSession()->setAttribute("isEntryPublisher",$isEntryPublisher);
		$this->getUserSession()->setAttribute("onlyOneSiteAdministor",false);//ここに来てる時点で複数の管理サイトの権限を持っている

		SOY2ActionSession::regenerateSessionId();

		return SOY2Action::SUCCESS;
	}

}
?>