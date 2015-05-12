
<div class="export_function_block">
<fieldset>
	<legend>記事&rArr;ファイル</legend>
	<form method="post">
		<h5>ラベルを選択してください</h5>
		<input type="radio" name="label" id="label_all" value="" checked="checked" />
		<label for="label_all">全て</label><br />
		<?php
		$labels = SOY2DAOFactory::create("cms.LabelDAO")->get();
		
		foreach($labels as $label){
			echo '<input type="radio" name="label" id="label_' . $label->getId() .'" value="'.htmlspecialchars($label->getId()).'" />';
			echo '<label for="label_' . $label->getId() .'">'.htmlspecialchars($label->getCaption()).'</label><br/>';
		}
		?>
		<input type="submit" name="export" value="ファイルに書き出す" />
	</form>
</fieldset>
</div>
	
<?php
$targetDir = UserInfoUtil::getSiteDirectory().$this->getTargetDir();
$template_overwrited = array();
$output_date = max($this->output_date,$this->sync_date);
$target_file = "";

$files = (file_exists($targetDir) && is_dir($targetDir)) ? scandir($targetDir) : array();

foreach($files as $file){
	if($file[0] == ".")continue;
	
	if($output_date < filemtime($targetDir . "/" . $file)){
		$template_overwrited[] = $file;
		continue;
	}
}

?>
<div class="export_function_block">
	
<form method="post">
<fieldset>
	<legend>ファイル&rArr;記事</legend>
	<?php
	if(!empty($template_overwrited)){
		echo "以下のファイルが書き出し後に編集されています。<br/>";
		foreach($template_overwrited as $key => $file){
			echo '<input type="checkbox" name="imports[]" id="import_file_' . $key .'" value="'.htmlspecialchars($file).'" checked="checked"/>';
			echo '<label for="import_file_' . $key .'">'.htmlspecialchars($file).'</label><br/>';
		}
	}else{
		echo "編集されたファイルはありません。";
	}
	?>
	
	<input type="submit" name="import" value="ファイルから読み込む" <?php if(empty($template_overwrited)) echo 'disabled="disabled"';?> />
</filedset>
</form>
</div>

<h6 style="clear:both;">書き出し先ディレクトリの変更</h6>

<form method="post">
	
	<div class="section">
		<p class="sub">書き出し先ディレクトリ</p>
		<?php echo UserInfoUtil::getSiteDirectory(); ?><input class="text" name="targetDir" value="<?php echo $this->getTargetDir(); ?>" /> <input type="submit" name="save" value="保存" />
	</div>
	
	
</form>

<br style="clear:both" />