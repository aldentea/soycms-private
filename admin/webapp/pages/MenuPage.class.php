<?php

class MenuPage extends CMSWebPageBase{
	
	var $type = SOY2HTML::SOY_BODY;
	
	function MenuPage(){
		WebPage::WebPage();
	}
	
	function execute(){
				
		$this->add("administratorlink",SOY2HTMLFactory::createInstance("HTMLLink",array(
			"link" => SOY2PageController::createLink("Administrator.List")
		)));
		
		$this->add("sitelink",SOY2HTMLFactory::createInstance("HTMLLink",array(
			"link" => SOY2PageController::createLink("Site.List")
		)));
		
		$this->add("siterolelink",SOY2HTMLFactory::createInstance("HTMLLink",array(
			"link" => SOY2PageController::createLink("SiteRole.List")
		)));
	}
	    
}
?>