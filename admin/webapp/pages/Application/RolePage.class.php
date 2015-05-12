<?php

class RolePage extends CMSUpdatePageBase{

	private $appId;

	function doPost(){
		$role = $_POST["AppRole"];

		$appRoleDAO = SOY2DAOFactory::create("admin.AppRoleDAO");

		try{
			$appRoleDAO->begin();
			foreach($role as $userId => $value){

				try{
					$appRole = $appRoleDAO->getRole($this->appId, $userId);

					if($value > 0){
						$appRole->setAppRole($value);
						$appRoleDAO->update($appRole);
					}else{
						//権限なしの場合は削除
						$appRoleDAO->delete($appRole);
					}

				}catch(Exception $e){
					if($value == 0)continue;
					$appRole = new AppRole();
					$appRole->setAppId($this->appId);
					$appRole->setUserId($userId);
					$appRole->setAppRole($value);
					$appRoleDAO->insert($appRole);
				}
			}

			$appRoleDAO->commit();
			$this->addMessage("UPDATE_SUCCESS");

		}catch(Exception $e){
			$appRoleDAO->rollback();
			$this->addMessage("UPDATE_FAILED");
		}

		$this->jump("Application.Role" . "?app_id=" . $this->appId );

	}

    function RolePage($arg) {
    	$this->appId = (isset($_GET["app_id"])) ? $_GET["app_id"] : "";
		
    	if(is_null($this->appId)){
    		SOY2PageController::jump("Application");
    	}

    	if(!UserInfoUtil::isDefaultUser()){
    		SOY2PageController::jump("Application");
    	}

    	//アプリの情報を取得
    	$appLogic = SOY2Logic::createInstance("logic.admin.Application.ApplicationLogic");
    	$application = null;
    	try{
    		$application = $appLogic->getApplication($this->appId);
    	}catch(Exception $e){
			SOY2PageController::jump("Application");
    	}

    	/**
    	 * SOY Shopなどアプリ内で権限設定を持つ場合
    	 */
    	if(strlen($application["customRoleUri"])){
    		SOY2PageController::redirect("../app/index.php/" . $application["customRoleUri"]);
    	}

    	WebPage::WebPage();

    	//DAO
    	$userDAO = SOY2DAOFactory::create("admin.AdministratorDAO");
    	$appRoleDAO = SOY2DAOFactory::create("admin.AppRoleDAO");

    	$users = $userDAO->get();
    	$roles = $appRoleDAO->getByAppId($this->appId);

    	$this->createAdd("role_list","RoleList",array(
    		"list" => $users,
    		"roles" => $roles,
    		"application" => $application
    	));

    	$this->addForm("form",array(
    		"action" => SOY2PageController::createLink("Application.Role") . "?app_id=" . $this->appId
    	));

    	$this->createAdd("modify_button","HTMLInput",array(
    		"type" => "submit",
    		"value" => CMSMessageManager::get("ADMIN_CHANGE"),
    		"visible" => (count($users)>1)
    	));

    	$this->createAdd("app_name","HTMLLabel",array(
    		"text" => $application["title"]
    	));

    	$messages = CMSMessageManager::getMessages();
		$errores = CMSMessageManager::getErrorMessages();
    	$this->createAdd("message","HTMLLabel",array(
			"text" => implode($messages),
			"visible" => (count($messages)>0)
		));
		$this->createAdd("error","HTMLLabel",array(
			"text" => implode($errores),
			"visible" => (count($errores)>0)
		));
    }

}

class RoleList extends HTMLList{

	private $roles;
	private $application;

	protected function populateItem($entity,$key){

		$userId = $entity->getId();

		$this->createAdd("user_name","HTMLLabel",array(
			"text" => (strlen($entity->getName())) ? $entity->getName() . " (".$entity->getUserId().")" : $entity->getUserId()
		));


		if(isset($this->roles[$userId])){
			$role = $this->roles[$userId];
			$roleValeu = $role->getAppRole();
		}else{
			$roleValeu = 0;
		}

		$this->createAdd("role","HTMLSelect",array(
			"options" => AppRole::getRoleLists($this->application["useMultipleRole"]),
			"indexOrder" => true,
			"name" => "AppRole[".$userId."]",
			"selected" => $roleValeu,
			"visible" => !$entity->getIsDefaultUser()
		));
	}

	function getRoles() {
		return $this->roles;
	}
	function setRoles($roles) {
		$this->roles = $roles;
	}
	function getApplication() {
		return $this->application;
	}
	function setApplication($application) {
		$this->application = $application;
	}
}



?>