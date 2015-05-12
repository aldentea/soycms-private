<?php
/**
 * 通常ログインとASPログインを振り分けて処理
 */
class LoginAction extends SOY2Action{

    function execute($req,$form,$res){
    	$auth = $req->getParameter('Auth');
    	$redirect = $req->getParameter('r');
    	
    	if(defined("SOYCMS_ASP_MODE")){
    		return $this->aspLogin($auth);
    	}else{
    		return $this->normalLogin($auth, $redirect);
    	}
    }
    
    /**
     * 通常のログインを行う
     */
    function normalLogin($auth, $redirect){
    	$logic = SOY2Logic::createInstance("logic.admin.Administrator.AdministratorLogic");
    	
    	if($logic->login($auth['name'],$auth['password'])){
    		$this->getUserSession()->setAuthenticated(true);
    		$this->getUserSession()->setAttribute('userid',$logic->getId());
    		
    		$name = $logic->getName();
    		if(!$name)$name = $logic->getUserId();
    		
    		$this->getUserSession()->setAttribute('loginid',$logic->getUserId());
    		$this->getUserSession()->setAttribute('username',$name);
    		$this->getUserSession()->setAttribute('email',$logic->getEmail());
    		$this->getUserSession()->setAttribute('isdefault',(int)$logic->getIsDefaultUser());

			SOY2ActionSession::regenerateSessionId();
			
			/**
			 * 転送先が指定されている場合はそこへ遷移する
			 */
			if(strlen($redirect) >0 && CMSAdminPageController::isAllowedPath($redirect)){
				SOY2PageController::redirect($redirect);
			}
    
    		return SOY2Action::SUCCESS;
    	}
    	
    	$this->setErrorMessage('loginfailed','ログインに失敗しました。');
    	$this->setAttribute('username',$auth['name']);
    	return SOY2Action::FAILED;
    }
    
    /**
     * ASP版のログインを行う
     */
    function aspLogin($auth){
    	
    	$name = $auth['name'];
    	$pass = $auth['password'];
    	
    	$dao = SOY2DAOFactory::create("asp.ASPUserDAO");
    	
    	try{
    		$user = $dao->login($name,crypt($pass,$name));
    		
    		$userSiteDao = SOY2DAOFactory::create("asp.UserSiteDAO");
    		$userSite = $userSiteDao->getByUserId($user->getId());
    		
    		$siteDao = SOY2DAOFactory::create("admin.SiteDAO");
    		$site = $siteDao->getById($userSite->getSiteId());
   		
    		$this->getUserSession()->setAuthenticated(true);
	    	$this->getUserSession()->setAttribute("Site",$site);
			$this->getUserSession()->setAttribute("isSiteAdministrator",true);
			$this->getUserSession()->setAttribute("isEntryAdministor",false);
			$this->getUserSession()->setAttribute("userid",$user->getId());
			$this->getUserSession()->setAttribute('username',$user->getNickName());
			
			$user->setLastLoginDate(time());
			$dao->update($user);
					
    	}catch(Exception $e){
    		
    		$this->setErrorMessage('loginfailed','ログインに失敗しました。');
	    	$this->setAttribute('username',$auth['name']);
	    	return SOY2Action::FAILED;
    	}
    	
		
    	return SOY2Action::SUCCESS;
    }
    
}
?>