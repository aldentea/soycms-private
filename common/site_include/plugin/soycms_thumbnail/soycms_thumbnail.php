<?php

SOYCMSThumbnailPlugin::register();
class SOYCMSThumbnailPlugin{

	const PLUGIN_ID = "soycms_thumbnail";
	
	const UPLOAD_IMAGE = "soycms_thumbnail_plugin_upload";
	const TRIMMING_IMAGE = "soycms_thumbnail_plugin_trimming";
	const RESIZE_IMAGE = "soycms_thumbnail_plugin_resize";
	const PREFIX_IMAGE = "soycms_thumbnail_plugin_";

	private $entryAttributeDao;
	
	private $ratio_w = 4;
	private $ratio_h = 3;
	
	private $resize_w = 120;
	private $resize_h = 90;

	function getId(){
		return self::PLUGIN_ID;
	}
	
	function init(){
		CMSPlugin::addPluginMenu($this->getId(),array(
			"name"=>"サムネイルプラグイン",
			"description"=>"サムネイル画像を生成します",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.5"
		));

		if(CMSPlugin::activeCheck($this->getId())){
			
			$this->entryAttributeDao = SOY2DAOFactory::create("cms.EntryAttributeDAO");
			
			//管理側
			if(!defined("_SITE_ROOT_")){
				CMSPlugin::addPluginConfigPage($this->getId(),array(
					$this,"config_page"
				));
				
				CMSPlugin::setEvent('onEntryCreate', $this->getId(), array($this, "onEntryUpdate"));
				CMSPlugin::setEvent('onEntryUpdate', $this->getId(), array($this, "onEntryUpdate"));
				CMSPlugin::setEvent('onEntryCopy', $this->getId(), array($this, "onEntryCopy"));
				CMSPlugin::setEvent('onEntryRemove', $this->getId(), array($this, "onEntryRemove"));
				
				CMSPlugin::addCustomFieldFunction($this->getId(), "Entry.Detail", array($this, "onCallCustomField"));
				CMSPlugin::addCustomFieldFunction($this->getId(), "Blog.Entry", array($this, "onCallCustomField_inBlog"));
			//公開側
			}else{
				CMSPlugin::setEvent('onEntryOutput', $this->getId(), array($this, "onEntryOutput"));
			}
		}
	}
	
	function onEntryOutput($arg){
		$entryId = $arg["entryId"];
		$htmlObj = $arg["SOY2HTMLObject"];
		
		try{
			$obj = $this->entryAttributeDao->get($entryId, self::RESIZE_IMAGE);
		}catch(Exception $e){
			$obj = new EntryAttribute();
		}
		
		$htmlObj->addImage("thumbnail", array(
			"soy2prefix" => "cms",
			"src" => $obj->getValue()
		));
	}
	
	/**
	 * 記事作成時、記事更新時
	 */
	function onEntryUpdate($arg){

		$entry = $arg["entry"];
		$entryId = $entry->getId();
		
		try{
			$this->entryAttributeDao->delete($entryId, self::UPLOAD_IMAGE);
			$this->entryAttributeDao->delete($entryId, self::TRIMMING_IMAGE);
			$this->entryAttributeDao->delete($entryId, self::RESIZE_IMAGE);
		}catch(Exception $e){
			//
		}
		
		$images = array();
		$images[self::UPLOAD_IMAGE] = (isset($_POST["jcrop_upload_field"])) ? $_POST["jcrop_upload_field"] : null;
		$images[self::TRIMMING_IMAGE] = (isset($_POST["jcrop_trimming_field"])) ? $_POST["jcrop_trimming_field"] : null;
		
		$resizeFlag = false;
		$imageFilePath = null;
		
		//リサイズ
		if(!isset($_POST["jcrop_resize_field"]) || strlen($_POST["jcrop_resize_field"]) === 0){
			if(isset($_POST["jcrop_trimming_field"]) && strlen($_POST["jcrop_trimming_field"]) > 0){
				$resizeFlag = true;
				$imageFilePath = trim($_POST["jcrop_trimming_field"]);
			}else if(isset($_POST["jcrop_upload_field"]) && strlen($_POST["jcrop_upload_field"]) > 0){
				$resizeFlag = true;
				$imageFilePath = trim($_POST["jcrop_upload_field"]);
			}
		}
		
		if($resizeFlag && $imageFilePath){
			$dir = str_replace("/" . UserInfoUtil::getSite()->getSiteId() . "/", "", UserInfoUtil::getSiteDirectory());
			$path = $dir . $imageFilePath;
			
			$imageInfoArray = (getimagesize($path));
			$w = (int)$imageInfoArray[0];
			$h = (int)$imageInfoArray[1];
			
			//アスペクト比の維持
			if($w > (int)$_POST["resize_w"]){
				$ratio = $w / $h;
				$w = (int)$_POST["resize_w"];
				$h = $w / $ratio;
			}
			
			//Siteのアップロードディレクトリを調べる
			$siteConfig = SOY2DAOFactory::create("cms.SiteConfigDAO")->get();
			
			$resizeDir = str_replace("//" , "/" , UserInfoUtil::getSiteDirectory() . $siteConfig->getDefaultUploadDirectory()) . "/resize"; 
			if(!file_exists($resizeDir)) mkdir($resizeDir);
			
			$imageFileName = substr($imageFilePath, strrpos($imageFilePath, "/") + 1);
			$resizePath = $resizeDir . "/" . $imageFileName;
			soy2_resizeimage($path, $resizePath, $w, $h);
			$images[self::RESIZE_IMAGE] = "/" . UserInfoUtil::getSite()->getSiteId() . $siteConfig->getDefaultUploadDirectory() . "/resize/" . $imageFileName;

		}else{
			$images[self::RESIZE_IMAGE] = trim($_POST["jcrop_resize_field"]);
		}
		
		$obj = new EntryAttribute();
		$obj->setEntryId($entryId);
		
		foreach($images as $key => $image){
			$obj->setFieldId($key);
			$obj->setValue($image);
			try{
				$this->entryAttributeDao->insert($obj);
			}catch(Exception $e){
				//
			}
		}
	}
	
	function onEntryCopy($args){
		list($old, $new) = $args;
		$objects = $this->getImageObjects($old);
		
		foreach($objects as $key => $object){
			try{
				$obj = new EntryAttribute();
				$obj->setEntryId($new);
				$obj->setFieldId(self::PREFIX_IMAGE . $key);
				$obj->setValue($object->getValue());
				$obj->setExtraValuesArray(null);
				$this->entryAttributeDao->insert($obj);
			}catch(Exception $e){
				//
			}
		}

		return true;
	}
	
	function onEntryRemove($args){
		foreach($args as $entryId){
			try{
				$this->entryAttributeDao->deleteByEntryId($entryId);
			}catch(Exception $e){
				//
			}
		}

		return true;
	}
	
	function onCallCustomField(){
		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[0])) ? (int)$arg[0] : null;
		
		$htmls = array();
		
		$htmls[] = "<div class=\"section\">";
		$htmls[] = $this->buildFormHtml($entryId);
		$htmls[] = "</div>";
		
		return implode("\n", $htmls);
	}
	
	function onCallCustomField_inBlog(){
		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[1])) ? (int)$arg[1] : null;
		
		$htmls = array();
		
		$htmls[] = "<div class=\"section\">";
		$htmls[] = $this->buildFormHtml($entryId);
		$htmls[] = "</div>";
		
		return implode("\n", $htmls);
	}
	
	function buildFormHtml($entryId){
		$objects = $this->getImageObjects($entryId);
		
		$action = SOY2ActionFactory::createInstance("SiteConfig.DetailAction");
		$result = $action->run();
		$entity = $result->getAttribute("entity");
				
		$htmls = array();
		$htmls[] = "<p class=\"sub\">";
		$htmls[] = "<label for=\"custom_field_img\">サムネイルの生成</label>";
		$htmls[] = "</p>";
		$htmls[] = "<table>";
		$htmls[] = "<tr>";
		$htmls[] = "<th style=\"width:15%;\">";
		$htmls[] = "アップロード";
		$htmls[] = "<img src=\"" . SOY2PageController::createLink("") . "image/icon/help.gif\" class=\"help_icon\" onMouseOver=\"this.style.cursor='pointer'\" onMouseOut=\"this.style.cursor='auto'\" onclick=\"common_show_message_popup(this,'サムネイルの生成用の画像を選択します。')\" />";
		$htmls[] = "</th>";
		$htmls[] = "<td><input type=\"text\" class=\"jcrop_field_input\" style=\"width:70%\" id=\"jcrop_upload_field\" name=\"jcrop_upload_field\" value=\"" . $objects["upload"]->getValue() . "\" readonly=\"readonly\" />";
		$htmls[] = "<input type=\"button\" onclick=\"open_jcrop_filemanager($('#jcrop_upload_field'));\" value=\"ファイルを指定する\">";
		if(strlen($objects["upload"]->getValue()) > 0){
			$htmls[] = "<a href=\"#\" onclick=\"return preview_thumbnail_plugin(\$('#jcrop_upload_field'));\">Preview</a>";
		}
		$htmls[] = "</td>";
		$htmls[] = "</tr>";
		
		$htmls[] = "<tr>";
		$htmls[] = "<th>トリミング</th>";
		$htmls[] = "<td><input type=\"text\" style=\"width:70%;\" id=\"jcrop_trimming_field\" name=\"jcrop_trimming_field\" value=\"" . $objects["trimming"]->getValue() . "\" readonly=\"readonly\">";
		$htmls[] = "<input type=\"button\" onclick=\"open_jcrop_trimming($('#jcrop_trimming_field'));\" value=\"トリミング\">";
		if(strlen($objects["trimming"]->getValue()) > 0){
			$htmls[] = "<a href=\"#\" onclick=\"return preview_thumbnail_plugin(\$('#jcrop_trimming_field'));\">Preview</a>";
		}
		$htmls[] = "<br />アスペクト比:width:<input type=\"number\" id=\"ratio_w\" value=\"". (int)$this->ratio_w . "\">&nbsp;";
		$htmls[] = "height:<input type=\"number\" id=\"ratio_h\" value=\"" . (int)$this->ratio_h . "\">";
		$htmls[] = "</td>";
		$htmls[] = "</tr>";
				
		$htmls[] = "<tr>";
		$htmls[] = "<th>";
		$htmls[] = "サムネイルの<br>リサイズ";
		$htmls[] = "<img src=\"" . SOY2PageController::createLink("") . "image/icon/help.gif\" class=\"help_icon\" onMouseOver=\"this.style.cursor='pointer'\" onMouseOut=\"this.style.cursor='auto'\" onclick=\"common_show_message_popup(this,'トリミングの画像があればリサイズして、トリミング画像がなければアップロードの画像をリサイズしてサムネイルを生成します')\" />";
		$htmls[] = "</th>";
		$htmls[] = "<td>";
		$htmls[] = "<input type=\"text\" style=\"width:70%;\" id=\"jcrop_resize_field\" name=\"jcrop_resize_field\" value=\"" . $objects["resize"]->getValue() . "\" readonly=\"readonly\">";
		if(strlen($objects["resize"]->getValue()) > 0){
			$htmls[] = "<input type=\"button\" onclick=\"clearResizeForm();\" value=\"クリア\">";
		}
		if(strlen($objects["resize"]->getValue()) > 0){
			$htmls[] = "<a href=\"#\" onclick=\"return preview_thumbnail_plugin(\$('#jcrop_resize_field'));\">Preview</a>";
		}
		$htmls[] = "<br />リサイズ:width:<input type=\"number\" name=\"resize_w\" value=\"" . $this->resize_w . "\">&nbsp;";
		$htmls[] = "height:<input type=\"number\" name=\"resize_h\" value=\"" . $this->resize_h . "\">&nbsp;";
		$htmls[] = "</td>";
		$htmls[] = "</tr>";
		$htmls[] = "</table>";

		$htmls[] = "<script type=\"text/javascript\">";
		$htmls[] = "function open_jcrop_filemanager(\$form){";
		$htmls[] = "	common_to_layer(\"" . SOY2PageController::createLink("Page.Editor.FileUpload?jcrop_upload_field") . "\");";
		$htmls[] = "}";
		$htmls[] = "function open_jcrop_trimming(\$form){";
		$htmls[] = "	if(\$(\"#jcrop_upload_field\").val().length > 0){";
		$htmls[] = "		common_to_layer(\"" . SOY2PageController::createLink("Plugin.Editor.Trimming") . "?jcrop_trimming_field&path=\" + \$(\"#jcrop_upload_field\").val() + \"&w=\" + \$(\"#ratio_w\").val() + \"&h=\" + \$(\"#ratio_h\").val())";
		$htmls[] = "	}else{";
		$htmls[] = "		alert(\"画像が選択されていません。\")";
		$htmls[] = "	}";
		$htmls[] = "}";
		
		$htmls[] = "function preview_thumbnail_plugin(\$form){";
		$htmls[] = "	var publicURL = \"". $entity->getConfigValue("url") . "\";";
		$htmls[] = "	var siteURL = \"" . UserInfoUtil::getSiteUrl() . "\";";
		$htmls[] = "";
		$htmls[] = "	var url = \"\";";
		$htmls[] = "	var href = \$form.val();";
		$htmls[] = "";
		$htmls[] = "	if(href && href.indexOf(\"/\") == 0){";
		$htmls[] = "		url = publicURL + href.substring(1, href.length);";
		$htmls[] = "	}else{";
		$htmls[] = "		url = siteURL + href;";
		$htmls[] = "	}";
		$htmls[] = "";
		$htmls[] = "	var temp = new Image();";
		$htmls[] = "	temp.src = url;";
		$htmls[] = "	temp.onload = function(e){";
		$htmls[] = "		common_element_to_layer(url, {";
		$htmls[] = "			height : Math.min(600, Math.max(400, temp.height + 20)),";
		$htmls[] = "			width  : Math.min(800, Math.max(400, temp.width + 20))";
		$htmls[] = "		});";
		$htmls[] = "	};";
		$htmls[] = "	temp.onerror = function(e){";
		$htmls[] = "		alert(url + \"が見つかりません。\");";
		$htmls[] = "	}";
		$htmls[] = "	return false;";
		$htmls[] = "}";
		$htmls[] = "";
		$htmls[] = "function clearResizeForm(){";
		$htmls[] = "	\$(\"#jcrop_resize_field\").val(\"\");";	
		$htmls[] = "}";
		$htmls[] = "</script>";
		
		return implode("\n", $htmls);
	}
	
	function getImageObjects($entryId){
		try{
			$uploadImageObj = $this->entryAttributeDao->get($entryId, self::UPLOAD_IMAGE);
			$trimmingImageObj = $this->entryAttributeDao->get($entryId, self::TRIMMING_IMAGE);
			$resizeImageObj = $this->entryAttributeDao->get($entryId, self::RESIZE_IMAGE);
		}catch(Exception $e){
			$uploadImageObj = new EntryAttribute();
			$trimmingImageObj = new EntryAttribute();
			$resizeImageObj = new EntryAttribute();
		}
		
		return array("upload" => $uploadImageObj, "trimming" => $trimmingImageObj, "resize" => $resizeImageObj);
	}

	/**
	 * 設定画面
	 */
	function config_page($message){
		include_once(dirname(__FILE__) . "/config/SOYCMSThumbnailConfigPage.class.php");
		$form = SOY2HTMLFactory::createInstance("SOYCMSThumbnailConfigPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}
	
	function getRatioW(){
		return $this->ratio_w;
	}
	function setRatioW($ratio_w){
		$this->ratio_w = $ratio_w;
	}

	function getRatioH(){
		return $this->ratio_h;
	}
	function setRatioH($ratio_h){
		$this->ratio_h = $ratio_h;
	}
	
	function getResizeW(){
		return $this->resize_w;
	}
	function setResizeW($resize_w){
		$this->resize_w = $resize_w;
	}
	
	function getResizeH(){
		return $this->resize_h;
	}
	function setResizeH($resize_h){
		$this->resize_h = $resize_h;
	}
	
	public static function register(){
		
		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(!$obj){
			$obj = new SOYCMSThumbnailPlugin();
		}
			
		CMSPlugin::addPlugin(self::PLUGIN_ID, array($obj, "init"));
	}	
}
?>