<?php

class IndexPage extends CMSWebPageBase{
	
	function IndexPage(){
		
		WebPage::WebPage();

		HTMLHead::addScript("draganddrop",array(
			"script" => "var DragAndDropPageURL = '".SOY2PageController::createLink("FileManager.DragAndDrop")."';"
		));
		
		HTMLHead::addScript("css_editor",array(
			"script"=>'var css_editor_url = "'.SOY2PageController::createLink("Page.Preview.CSSEditor").'";'.
					'var siteId = "'.UserInfoUtil::getSite()->getSiteId().'";'
		));

	}
	
}

?>