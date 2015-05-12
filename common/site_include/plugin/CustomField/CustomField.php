<?php
class CustomFieldPlugin{

	const PLUGIN_ID = "CustomField";

	var $customFields = array();

	//設定
	var $displayTitle = 0;
	var $displayID = 0;


	function init(){
		CMSPlugin::addPluginMenu(CustomFieldPlugin::PLUGIN_ID, array(
			"name"=>"カスタムフィールド",
			"description"=>"エントリーにカスタムフィールドを追加します。",
			"author"=>"日本情報化農業研究所",
			"url"=>"http://www.n-i-agroinformatics.com/",
			"mail"=>"soycms@soycms.net",
			"version"=>"1.7"
		));

		CMSPlugin::addPluginConfigPage(CustomFieldPlugin::PLUGIN_ID, array(
			$this, "config_page"
		));

		if(CMSPlugin::activeCheck(CustomFieldPlugin::PLUGIN_ID)){

			CMSPlugin::setEvent('onEntryUpdate', CustomFieldPlugin::PLUGIN_ID, array($this, "onEntryUpdate"));
			CMSPlugin::setEvent('onEntryCreate', CustomFieldPlugin::PLUGIN_ID, array($this, "onEntryUpdate"));
			CMSPlugin::setEvent('onEntryCopy', CustomFieldPlugin::PLUGIN_ID, array($this, "onEntryCopy"));

			CMSPlugin::addCustomFieldFunction(CustomFieldPlugin::PLUGIN_ID, "Entry.Detail", array($this, "onCallCustomField"));
			CMSPlugin::addCustomFieldFunction(CustomFieldPlugin::PLUGIN_ID, "Blog.Entry", array($this, "onCallCustomField_inBlog"));

			CMSPlugin::setEvent('onEntryOutput', CustomFieldPlugin::PLUGIN_ID, array($this, "display"));
		}else{
			CMSPlugin::setEvent('onActive', CustomFieldPlugin::PLUGIN_ID, array($this, "createTable"));
		}
	}

	function display($arg){
		
		$entryId = $arg["entryId"];
		$htmlObj = $arg["SOY2HTMLObject"];

		$fields = $this->getCustomFields($entryId);

		foreach($fields as $field){

			//設定を取得
			$master = (isset($this->customFields[$field->getId()])) ? $this->customFields[$field->getId()] : null;

			$class = "CMSLabel";
			$attr = array(
				"html"       => $field->getValue(),
				"soy2prefix" => "cms",
			);
			
			//カスタムフィールドの設定が取れるときの動作（たとえば同じサイト内の場合）
			if($master){
				
				//タイプがリンクの場合はここで上書き
				if($master->getType() == "link"){
					$class = "HTMLLink";
					$attr["link"] = (strlen($field->getValue()) > 0) ? $field->getValue() : null;
					unset($attr["html"]);
				}

				//値が設定されていないなら初期値を使う
				if(is_null($field->getValue())){
					$field->setValue($master->getDefaultValue());
				}

				//空の時の動作
				if(strlen($field->getValue()) == 0 ){
					if($master->getHideIfEmpty()){
						//空の時は表示しない
						$attr["visible"] = false;
					}else{
						//空の時の値
						$field->setValue($master->getEmptyValue());
					}
				}

				//上で空の時の値が入るかも知れず、下でunsetされる可能性があるのでここで設定し直す。
				$attr["html"] = $field->getValue();

				//属性に出力
				if(strlen($master->getOutput()) > 0){
					
					//リンクタイプ以外でhrefを使う場合
					if($master->getOutput() == "href" && $master->getType() != "type"){
						$class = "HTMLLink";
						$attr["link"] = (strlen($field->getValue()) > 0) ? $field->getValue() : null;
					}else{
						$class = "HTMLModel";
						$attr[$master->getOutput()] = $field->getValue();
					}
					
					//追加属性を出力
					
					if(strlen($master->getExtraOutputs()) > 0){
						$extraOutputs = explode("\n", str_replace(array("\r\n", "\r"), "\n", $master->getExtraOutputs()));
						$extraValues = $field->getExtraValues();
						foreach($extraOutputs as $key => $extraOutput){
							$extraOutput = trim($extraOutput);
							$attr[$extraOutput] = is_array($extraValues) && isset($extraValues[$extraOutput]) ? $extraValues[$extraOutput] : "";
						}
					}
					/*
					if(strlen($master->getExtraOutputs()) > 0 && is_array($field->getExtraValues())){
						foreach($field->getExtraValues() as $key => $value){
							$attr["attr:" . $key] = $value;
						}
					}
					*/
					unset($attr["html"]);//HTMLModelなのでunsetしなくても出力されないはず
				}
			}

			$htmlObj->addModel($field->getId() . "_visible", array(
				"soy2prefix" => "cms",
				"visible" => (strlen($field->getValue()) > 0)
			));

			$htmlObj->addModel($field->getId() . "_is_not_empty", array(
				"soy2prefix" => "cms",
				"visible" => (strlen($field->getValue()) > 0)
			));

			$htmlObj->addModel($field->getId() . "_is_empty", array(
				"soy2prefix" => "cms",
				"visible" => (strlen($field->getValue()) === 0)
			));
			
			//SOY2HTMLのデフォルトの _visibleがあるので、$field->getId()."_visible"より後にこれをやらないと表示されなくなる
			$htmlObj->createAdd($field->getId(), $class, $attr);
		}

	}

	function config_page($message){
		//$this->importFields();
		$form = SOY2HTMLFactory::createInstance("CustomFieldPluginFormPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}

	function onEntryUpdate($arg){
		$entry = $arg["entry"];

		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[0])) ? (int)$arg[0] : null;
		$postFields = (isset($_POST["custom_field"])) ? $_POST["custom_field"] : null;
		$extraFields = (isset($_POST["custom_field_extra"])) ? $_POST["custom_field_extra"] : null;

		//各エントリーに保存する時はIDとValueのみ保存するように変更
		$saveCustomFields = array();

		foreach($this->customFields as $key => $field){

			$field = new CustomField();
			$field->setId($this->customFields[$key]->getId());

			if(isset($postFields[$field->getId()])){
				$field->setValue($postFields[$field->getId()]);
			}else{
				$field->setValue("");
			}

			if(isset($extraFields[$field->getId()]) && is_array($extraFields[$field->getId()])){
				$field->setExtraValues($extraFields[$field->getId()]);
			}else{
				$field->setExtraValues(array());
			}
			
			$saveCustomFields[] = $field;
		}

		$dao = new SOY2DAO();

		try{
			$dao->executeQuery("update Entry set custom_field = :custom where Entry.id = :id",
				array(
					":id"=>$entry->getId(),
					":custom"=>soy2_serialize($saveCustomFields)
					));
		}catch(Exception $e){
			return false;
		}

		return true;
	}

	function createTable(){
		$dao = new SOY2DAO();
		try{
			$dao->executeQuery("alter table Entry add custom_field text",array());
		}catch(Exception $e){
		}

		return;
	}

	function onEntryCopy($args){
		list($old, $new) = $args;

		try{
			$fields = $this->getCustomFields($old);

			$dao = new SOY2DAO();

			$dao->executeQuery("update Entry set custom_field = :custom where Entry.id = :id",
					array(
						":id"=>$new,
						":custom"=>soy2_serialize($fields)
						));
		}catch(Exception $e){
			return false;
		}

		return true;
	}

	function deleteField($id){
		if(isset($this->customFields[$id])){
			unset($this->customFields[$id]);
			CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID,$this);
		}
	}

	/**
	 * 通常の更新
	 *
	 * ラベルと種別のみ更新
	 */
	function update($id, $value, $type){
		if(isset($this->customFields[$id])){
			$this->customFields[$id]->setLabel($value);
			$this->customFields[$id]->setType($type);
			CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID, $this);
		}
	}

	/**
	 * 高度な設定の更新
	 */
	function updateAdvance($id,$obj){
		if(isset($this->customFields[$id])){
			SOY2::cast($this->customFields[$id], $obj);
			CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID, $this);
		}
	}

	/**
	 * 移動
	 */
	function moveField($id, $diff){
		if(isset($this->customFields[$id])){

			$keys = array_keys($this->customFields);
			$currentKey = array_search($id, $keys);
			$swap = ($diff > 0) ? $currentKey + 1 :$currentKey - 1;

			if($swap >= 0 && $swap < count($keys)){
				$tmp = $keys[$currentKey];
				$keys[$currentKey] = $keys[$swap];
				$keys[$swap] = $tmp;

				$tmpArray = array();
				foreach($keys as $index => $value){
					$field = $this->customFields[$value];
					$tmpArray[$field->getId()] = $field;
				}

				$this->customFields = $tmpArray;
			}

			CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID, $this);
		}
	}


	function insertField(CustomField $_field){
		if(isset($this->customFields[$_field->getId()])){
			return false;
		}

		$id_blacklist = array(
			"title", "content", "more", "id", "create_date",
		);

		if(in_array($_field->getId(), $id_blacklist)){
			return false;
		}

		if(preg_match('/_visible$/i', $_field->getId())){
			return false;
		}

		$this->customFields[$_field->getId()] = $_field;
		CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID, $this);
	}

	function onCallCustomField(){

		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[0])) ? (int)$arg[0] : null;
		
		$html = $this->getScripts();
		$html .= '<div class="section custom_field">';
		$db_arr = $this->getCustomFields($entryId);

		$db_values = array();
		foreach($db_arr as $field){
			$db_values[$field->getId()] = $field->getValue();
		}

		$db_extra_values = array();
		foreach($db_arr as $field){
			$db_extra_values[$field->getId()] = $field->getExtraValues();
		}
		
		foreach($this->customFields as $fieldId => $fieldObj){
			$html .= $fieldObj->getForm($this, $db_values[$fieldId], $db_extra_values[$fieldId]);
		}

		$html .= '</div>';

		return $html;
	}

	function onCallCustomField_inBlog(){
		$arg = SOY2PageController::getArguments();
		$entryId = (isset($arg[1])) ? (int)$arg[1] : null;

		$html = $this->getScripts();
		$html .= '<div class="section custom_field">';
		$db_arr = $this->getCustomFields($entryId);

		$db_values = array();
		foreach($db_arr as $field){
			$db_values[$field->getId()] = $field->getValue();
		}

		$db_extra_values = array();
		foreach($db_arr as $field){
			$db_extra_values[$field->getId()] = $field->getExtraValues();
		}
		
		foreach($this->customFields as $fieldId => $fieldObj){
			$html .= $fieldObj->getForm($this, $db_values[$fieldId], $db_extra_values[$fieldId]);
		}

		$html .= '</div>';

		return $html;
	}

	function getScripts(){

		$script = '<script type="text/javascript">';
		$script .= file_get_contents(dirname(__FILE__) . "/custom_field.js");
		$script .= '</script>';
		$script = str_replace("#FILE_UPLOAD_LINK#", SOY2PageController::createLink("Page.Editor.FileUpload"), $script);
		$script = str_replace("#PUBLIC_URL#", UserInfoUtil::getSiteURLBySiteId(""), $script);
		$script = str_replace("#SITE_URL#", UserInfoUtil::getSiteURL(), $script);
		
		return $script;
	}

	/**
	 * 特定の記事のカスタムフィールドの値を返す
	 * @param int entryId 記事のID
	 * @param 廃止（互換性のために残しておく）
	 * @return Array <CustomField>
	 */
	function getCustomFields($entryId, $checkInternal = null){
		$dao = new SOY2DAO();

		if(is_null($entryId)){
			//HTMLListの初回のダミーオブジェクトのときなど
			$result = null;
		}else{
			try{
				$result = $dao->executeQuery("select custom_field from Entry where id = :id", array(":id" => $entryId));
			}catch(Exception $e){
				$result = null;
			}
		}

		if(is_array($result) && count($result) && is_array($result[0]) && isset($result[0]['custom_field'])){
			if(strpos($result[0]['custom_field'], "\0CustomField\0") !== false){
				//ただのserializeのころのデータのための後方互換
				$db_arr = @unserialize($result[0]['custom_field']);
			}elseif(strlen($result[0]['custom_field'])){
				$db_arr = @soy2_unserialize($result[0]['custom_field']);
			}
		}else{
			$db_arr = null;
		}

		if(!is_array($db_arr)){
			$db_arr = array();
		}

		/*
		 * 注意！
		 * $this->customFieldsは連想配列（カスタムフィールドのID => カスタムフィールドのオブジェクト）
		 * $db_arryはただの配列（連番 => カスタムフィールドのオブジェクト（IDと値だけが入っている、高度な設定などは空））
		 */

		//記事にあるカスタムフィールド
		$db_fields = array();
		foreach($db_arr as $key => $field){
			if(isset($this->customFields[$field->getId()])){
				$db_fields[$field->getId()] = $key;
			}
		}

		//記事にないカスタムフィールドの設定内容を入れておく
		//（HTMLListやカスタムフィールドを追加したときの既存の記事のため）
		foreach($this->customFields as $filedId => $fieldValue){
			if(isset($db_fields[$filedId]) && $db_arr[$db_fields[$filedId]] instanceof CustomField){
				//do nothing
			}else{
				//IDと初期値だけ入れておく
				$added = new CustomField();
				$added->setId($filedId);
				$added->setValue($fieldValue->getDefaultValue());
				$db_arr[] = $added;
			}
		}

		return $db_arr;
	}

	/**
	 * custom_field.tsvをインポートする
	 */
	function importFields(){
		$tsv_file = dirname(__FILE__) . "/" . "custom_field.tsv";
		if(!file_exists($tsv_file)) return false;

		//まず削除
		$this->deleteAllFields();

		$tsv = mb_convert_encoding(file_get_contents($tsv_file), "UTF-8", "SJIS");

		$tsv = str_replace(array("\r\n", "\r"),"\n",$tsv);
		$array = explode("\n", $tsv);

		foreach($array as $item){
			$item = explode("\t", $item);
			if(count($item)<2 OR empty($item[0]) OR empty($item[1])) continue;
			if(@empty($item[2])) $item[2] = "input";
			$this->insertField(new CustomField(array(
				"id"    => @$item[0],
				"label" => @$item[1],
				"type"  => @$item[2],
				"labelId"  => @$item[3],
				"output"  => @$item[4],
				"defaultValue"  => @$item[5],
				"emptyValue"  => @$item[6],
				"hideIfEmpty"  => (boolean)@$item[7],
			)));
		}

		rename($tsv_file, dirname(__FILE__) . "/"."custom_field-imported_at_".date('Y-m-d\THis').".tsv");
		CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID,$this);
		CMSUtil::notifyUpdate();
		CMSPlugin::redirectConfigPage();

	}

	/**
	 * カスタムフィールド設定の削除
	 */
	function deleteAllFields(){
		foreach($this->customFields as $field){
			$this->deleteField($field->getId());
		}
	}

	/**
	 * 設定の保存
	 */
	function updateDisplayConfig($config){
		//表示設定
		$this->displayTitle = ( $config["display_title"] > 0 ) ? 1 : 0 ;
		$this->displayID = ( $config["display_id"] > 0 ) ? 1 : 0 ;

		CMSPlugin::savePluginConfig(CustomFieldPlugin::PLUGIN_ID,$this);
	}

	public static function register(){
		include_once(dirname(__FILE__)."/entity.php");
		include_once(dirname(__FILE__)."/form.php");

		$obj = CMSPlugin::loadPluginConfig(CustomFieldPlugin::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new CustomFieldPlugin();
		}

		CMSPlugin::addPlugin(CustomFieldPlugin::PLUGIN_ID,array($obj,"init"));

	}
}

/**
 * 互換性維持のための旧クラス
 */
class ConfigFieldPlugin extends CustomFieldPlugin{}

CustomFieldPlugin::register();
?>