<?php
/**
 * @ToDo 後日、iframe周りのhttpsの読み込みの対応を行う
 */
include(dirname(__FILE__)."/common.php");
class ButtonSocialPlugin{

	const PLUGIN_ID = "ButtonSocial";
	const PLUGIN_KEY = "ogimage_field";

	private $logic;
	private $app_id;
	private $mixi_check_key;
	private $mixi_like_key;
	private $admins;
	private $description;
	private $image;
	
	private $entryAttributeDao;

	function init(){
		CMSPlugin::addPluginMenu($this->getId(),array(
			"name"=>"ソーシャルボタン設置プラグイン",
			"description"=>"ページにソーシャルボタンを設置します。",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"0.9"
		));

		$logic = new ButtonSocialCommon();
		$logic->setPluginObj($this);
		$this->logic = $logic;

		CMSPlugin::addPluginConfigPage($this->getId(),array(
			$this,"config_page"
		));

		if(CMSPlugin::activeCheck($this->getId())){
			$this->entryAttributeDao = SOY2DAOFactory::create("cms.EntryAttributeDAO");

			//公開画面側
			if(defined("_SITE_ROOT_")){
				CMSPlugin::setEvent('onEntryOutput',$this->getId(),array($this,"display"));
	
				//公開側のページを表示させたときに、メタデータを表示する
				CMSPlugin::setEvent('onPageOutput',$this->getId(),array($this,"onPageOutput"));
				CMSPlugin::setEvent('onOutput',$this->getId(),array($this,"onOutput"));
			}else{
				CMSPlugin::setEvent('onEntryUpdate', $this->getId(), array($this, "onEntryUpdate"));
				CMSPlugin::setEvent('onEntryCreate', $this->getId(), array($this, "onEntryUpdate"));
				CMSPlugin::setEvent('onEntryCopy', $this->getId(), array($this, "onEntryCopy"));
	
				CMSPlugin::addCustomFieldFunction($this->getId(), "Entry.Detail", array($this, "onCallCustomField"));
				CMSPlugin::addCustomFieldFunction($this->getId(), "Blog.Entry", array($this, "onCallCustomField_inBlog"));
			}
		}else{
			//何もしない
		}
	}

	function getId(){
		return self::PLUGIN_ID;
	}

	function display($arg){
		$logic = $this->logic;

		$entryId = $arg["entryId"];
		$htmlObj = $arg["SOY2HTMLObject"];

		list($url,$title) = $logic->getDetailUrl($htmlObj,$entryId);

		$htmlObj->addLabel("facebook_like_button", array(
			"soy2prefix" => "cms",
			"html" => $logic->getFbButton($this->app_id,$url)
		));

		$htmlObj->addLabel("twitter_button", array(
			"soy2prefix" => "cms",
			"html" => $logic->getTwitterButton($url)
		));

		$htmlObj->addLink("twitter_button_mobile", array(
			"soy2prefix" => "cms",
			"link" => $logic->getTwitterButtonMobile($url, $title)
		));

		$htmlObj->addLabel("hatena_button", array(
			"soy2prefix" => "cms",
			"html" => $logic->getHatenaButton($url)
		));

		$htmlObj->addLink("mixi_check_button", array(
			"soy2prefix" => "cms",
			"link" => "http://mixi.jp/share.pl",
			"attr:class" => "mixi-check-button",
			"attr:data-key" => $this->mixi_check_key,
			"attr:data-url" => $url
		));

		$htmlObj->addLabel("mixi_check_script", array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiCheckScript()
		));

		$htmlObj->addLabel("mixi_check_button_mobile", array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiCheckButtonMobile($url, $this->mixi_check_key, $title)
		));

		$htmlObj->addLabel("mixi_like_button", array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiLikeButton($this->mixi_like_key)
		));

		$htmlObj->addLabel("mixi_like_button_mobile", array(
			"soy2prefix" => "cms",
			"html" => $logic->getMixiLikeButtonMobile($url, $title, $this->mixi_like_key)
		));
		
		$htmlObj->addLabel("google_plus_button", array(
			"soy2prefix" => "cms",
			"html" => $logic->getGooglePlusButton()
		));
	}

	function onPageOutput($obj){
		$entryId = (get_class($obj) == "CMSBlogPage" && isset($obj->entry) && !is_null($obj->entry->getId())) ? (int)$obj->entry->getId() : null;
		
		$logic = $this->logic;

		$obj->addLabel("og_meta", array(
			"soy2prefix" => "sns",
			"html" => $logic->getOgMeta($obj, $this->description, $this->image, $entryId)
		));

		$obj->addLabel("facebook_meta", array(
			"soy2prefix" => "sns",
			"html" => $logic->getFbMeta($this->app_id, $this->admins)
		));

		$obj->addLabel("facebook_like_button", array(
			"soy2prefix" => "sns",
			"html" => $logic->getFbButton($this->app_id)
		));

		$obj->addLabel("twitter_button", array(
			"soy2prefix" => "sns",
			"html" => $logic->getTwitterButton()
		));

		$obj->addLabel("twitter_button_mobile", array(
			"soy2prefix" => "sns",
			"html" => $logic->getTwitterButton()
		));

		$obj->addLabel("hatena_button", array(
			"soy2prefix" => "sns",
			"html" => $logic->getHatenaButton()
		));
		
		$obj->addLabel("google_plus_button", array(
			"soy2prefix" => "sns",
			"html" => $logic->getGooglePlusButton()
		));

		/*
		 * 互換性のため block:id のものも置いておく
		 */
		$obj->addLabel("og_meta", array(
			"soy2prefix" => "block",
			"html" => $logic->getOgMeta($obj, $this->description, $this->image, $entryId)
		));
		$obj->addLabel("facebook_meta", array(
			"soy2prefix" => "block",
			"html" => $logic->getFbMeta($this->app_id, $this->admins)
		));
		$obj->addLabel("facebook_like_button", array(
			"soy2prefix" => "block",
			"html" => $logic->getFbButton($this->app_id)
		));
		$obj->addLabel("twitter_button", array(
			"soy2prefix" => "block",
			"html" => $logic->getTwitterButton()
		));
		$obj->addLabel("hatena_button", array(
			"soy2prefix" => "block",
			"html" => $logic->getHatenaButton()
		));
	}
	
	function onOutput($arg){
		$html = &$arg["html"];
		
		//ダイナミック編集では挿入しない
		if(defined("CMS_PREVIEW_MODE") && CMS_PREVIEW_MODE){
			return null;
		}
		
		//app_idが入力されていない場合は表示しない
		if(is_null($this->app_id) || strlen($this->app_id) ===0){
			return null;
		}
		
		$logic = $this->logic;
		
		if(stripos($html,'<body>') !== false){
			$html = str_ireplace('<body>', '<body>' . "\n" . $logic->getFbRoot($this->app_id), $html);
		}elseif(preg_match('/<body\\s[^>]+>/',$html)){
			$html = preg_replace('/(<body\\s[^>]+>)/', "\$0\n" . $logic->getFbRoot($this->app_id), $html);
		}else{
			//何もしない
		}
		
		return $html;
	}
	
	function onEntryUpdate($arg){
		
		if(isset($_POST[self::PLUGIN_KEY]) && strlen($_POST[self::PLUGIN_KEY]) > 0){
			$entry = $arg["entry"];
			
			try{
				$this->entryAttributeDao->delete($entry->getId(), self::PLUGIN_KEY);
			}catch(Exception $e){
				
			}
			
			$obj = new EntryAttribute();
			$obj->setEntryId($entry->getId());
			$obj->setFieldId(self::PLUGIN_KEY);
			$obj->setValue($_POST[self::PLUGIN_KEY]);
			
			try{
				$this->entryAttributeDao->insert($obj);
			}catch(Exception $e){
				
			}
		}
	}
	
	function onEntryCopy($args){
		list($old, $new) = $args;
		$custom = $this->getOgImageObject($old);
		
		try{
			$obj = new EntryAttribute();
			$obj->setEntryId($new);
			$obj->setFieldId($custom->getId());
			$obj->setValue($custom->getValue());
			$obj->setExtraValuesArray($custom->getExtraValues());
			$this->entryAttributeDao->insert($obj);
		}catch(Exception $e){
				
		}

		return true;
	}
	
	function onCallCustomField(){
		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[0])) ? (int)$arg[0] : null;
		return $this->buildForm($entryId);
	}
	
	function onCallCustomField_inBlog(){
		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[1])) ? (int)$arg[1] : null;
		return $this->buildForm($entryId);
	}
	
	function buildForm($entryId){
		$obj = $this->getOgImageObject($entryId);
		
		$html = array();
		$html[] = "<div class=\"section custom_field\">";
		$html[] = "<p class=\"sub\">";
		$html[] = "<label for=\"custom_field_img\">og:image ※必ず画像をアップロードしてください</label>";
		$html[] = "</p>";
		$html[] = "<div style=\"margin:-0.5ex 0px 0.5ex 1em;\">";
		$html[] = "<input type=\"text\" class=\"ogimage_field_input\" style=\"width:50%\" id=\"ogimage_field\" name=\"" . self::PLUGIN_KEY . "\" value=\"". $obj->getValue() . "\" />";
		$html[] = "<button type=\"button\" onclick=\"open_ogimage_filemanager($('#ogimage_field'));\" style=\"margin-right:10px;\">ファイルを指定する</button>";
		$html[] = "</div>";
		$html[] = "<script type=\"text/javascript\">";
		$html[] = "var \$custom_field_input = \$();";
		$html[] = "function open_ogimage_filemanager(\$form){";
		$html[] = "	\$custom_field_input = \$form;";
		$html[] = "	common_to_layer(\"/main/soycms/index.php/Page/Editor/FileUpload\");";
		$html[] = "}";
		$html[] = "</script>";
		return implode("\n", $html);
	}
	
	function getOgImageObject($entryId){
		try{
			$obj = $this->entryAttributeDao->get($entryId, self::PLUGIN_KEY);
		}catch(Exception $e){
			$obj = new EntryAttribute();
		}
		return $obj;
	}

	function config_page($message){
		if(isset($_POST["save"])){
			$this->app_id = $_POST["app_id"];
			$this->admins = $_POST["admins"];
			$this->description = $_POST["description"];
			$this->image = $_POST["image"];
			$this->mixi_check_key = $_POST["mixi_check_key"];
			$this->mixi_like_key = $_POST["mixi_like_key"];

			CMSPlugin::savePluginConfig(self::PLUGIN_ID,$this);
			CMSPlugin::redirectConfigPage();
			//CMSUtil::NotifyUpdate();

			exit;
		}

		ob_start();
		include_once(dirname(__FILE__) . "/config.php");
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public static function register(){

		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new ButtonSocialPlugin();
		}

		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));

	}
}
ButtonSocialPlugin::register();
?>