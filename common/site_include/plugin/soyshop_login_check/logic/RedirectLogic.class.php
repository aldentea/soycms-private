<?php

class RedirectLogic extends SOY2LogicBase{
	
	private $loginPageUrl;
	private $configPerBlog;
	
	function RedirectLogic(){}
	
	function redirectLoginForm($page, $mode){
		
		$redirectFlag = false;
		
		//念のため、ログインフォームのURLが取得できているかを確認
		if(isset($this->loginPageUrl) && strlen($this->loginPageUrl) > 0){
			$pageType = $page->getPageType();
				
			//ブログページの場合
			if($pageType == Page::PAGE_TYPE_BLOG){
				//ブログのタイプ毎に設定内容を調べる
				if($this->configPerBlog[$page->getId()][$mode]){
					$redirectFlag = true;
				}
			//ブログ以外のページはtrue
			}else{
				$redirectFlag = true;
			}
			
			if($redirectFlag){
				$url = rawurldecode($_SERVER["REQUEST_URI"]);
				$this->execRedirect($url);
			}
		}
	}
	
	function execRedirect($url){
		header("Location:" . $this->loginPageUrl . "?r=" . $url);
	}
		
	function setLoginPageUrl($loginPageUrl){
		$this->loginPageUrl = $loginPageUrl;
	}
	
	function setConfigPerBlog($configPerBlog){
		$this->configPerBlog = $configPerBlog;
	}
}
?>