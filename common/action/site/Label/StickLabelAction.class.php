<?php

class StickLabelAction extends SOY2Action{

    function execute($request,$form,$response) {
    	
    	$dao = SOY2DAOFactory::create("cms.EntryLabelDAO");
    	$dao->begin();
    	foreach($form->label as $label){
    		foreach($form->entry as $entry){
    			try{
    				$dao->getByParam($label,$entry);
    			}catch(Exception $e){
    				$obj = new EntryLabel();
    				$obj->setEntryId($entry);
    				$obj->setLabelId($label);
    				$obj->setMaxDisplayOrder();
    				$dao->insert($obj);
    			}
    		}
    	}
    	$dao->commit();
    	
    	return SOY2Action::SUCCESS;
    }
}

class StickLabelActionForm extends SOY2ActionForm{
	
	var $label = array();
	var $entry = array();
	
	function setLabel($label){
		$this->label = $label;
		if(is_null($this->label)){
			$this->label = array();
		}
	}
	
	function setEntry($entry){
		$this->entry = $entry;
		if(is_null($this->entry)){
			$this->label = array();
		}
	}
	
}

?>