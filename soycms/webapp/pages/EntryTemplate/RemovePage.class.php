<?php
class RemovePage extends CMSWebPageBase{

	function RemovePage($arg) {
    	if(soy2_check_token()){
	    	WebPage::WebPage();
	    	$id = @$arg[0];
	    	if(is_null($id)){
	    		$this->jump("EntryTemplate");
	    		exit;
	    	}
	    	$result = SOY2ActionFactory::createInstance("EntryTemplate.TemplateRemoveAction",array("id"=>$id))->run();
	    	if($result->success()){
	    		$this->addMessage("ENTRY_TEMPLATE_REMOVE_SUCCESS");
	    	}else{
	    		$this->addMessage("ENTRY_TEMPLATE_REMOVE_FAILED");
	    	}
    	}
    	$this->jump("EntryTemplate");
    	
    }
}
?>