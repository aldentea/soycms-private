<?php
	$old_dsn = SOY2DAOConfig::Dsn();
	$old_user = SOY2DAOConfig::user();
	$old_pass = SOY2DAOConfig::pass();
	SOY2DAOConfig::Dsn(ADMIN_DB_DSN);
	SOY2DAOConfig::user(ADMIN_DB_USER);
	SOY2DAOConfig::pass(ADMIN_DB_PASS);

	$dao = SOY2DAOFactory::create("admin.SiteDAO");
	
	try{
		$sites = $dao->getBySiteType(Site::TYPE_SOY_SHOP);
	}catch(Exception $e){
		$sites = array();
	}
?>

<form method="post">

<h4>呼び出すショップのID</h4>
<select name="siteId">
	<?php
		foreach($sites as $site){
			
			if($site->getSiteId()===$this->siteId){
				echo "<option value=\"".$site->getSiteId()."\" selected=\"selected\">".$site->getSiteId()."</option>\n";
			}else{
				echo "<option value=\"".$site->getSiteId()."\">".$site->getSiteId()."</option>\n";				
			}
			
		}
	?>
	
</select>


<input type="submit" name="save" value="保存" />

</form>

<?php
	SOY2DAOConfig::Dsn($old_dsn);
	SOY2DAOConfig::user($old_user);
	SOY2DAOConfig::pass($old_pass);
?>
