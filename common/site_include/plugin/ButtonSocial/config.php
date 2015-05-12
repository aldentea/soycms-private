<?php
/*
 * Created on 2009/06/12
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

?>
<form method="post">
<table class="list" style="width:80%;">
	<tr>
		<th>Facebookのapp_idとadminsの設定</th>
	</tr>
	<tr>
		<td>
				app_id：<input type="text" name="app_id" value="<?php echo htmlspecialchars($this->app_id,ENT_QUOTES); ?>" style="text-align:right;ime-mode:inactive;" />
				admins：<input type="text" name="admins" value="<?php echo htmlspecialchars($this->admins,ENT_QUOTES); ?>" style="text-align:right;ime-mode:inactive;" /><br />
				descripions：<input type="text" name="description" value="<?php echo htmlspecialchars($this->description,ENT_QUOTES); ?>" style="width:80%;" /><br />
				サムネイルのパス：<input type="text" name="image" value="<?php echo htmlspecialchars($this->image,ENT_QUOTES); ?>" style="width:50%;" />
		</td>
	</tr>
	<tr>
		<th>mixiイイネの設定</th>
	</tr>
	<tr>
		<td>
			key：<input type="text" name="mixi_like_key" value="<?php echo htmlspecialchars($this->mixi_like_key,ENT_QUOTES); ?>" style="text-align:right;ime-mode:inactive;" />
		</td>
	</tr>
	<tr>
		<th>mixiチェックの設定</th>
	</tr>
	<tr>
		<td>
			key：<input type="text" name="mixi_check_key" value="<?php echo htmlspecialchars($this->mixi_check_key,ENT_QUOTES); ?>" style="text-align:right;ime-mode:inactive;" />
		</td>
	</tr>
	<tr>
		<td>
				<input type="submit" name="save" value="保存" />
		</td>
	</tr>
</table>
</form>

<h3>テンプレートへの記述例</h3>
<pre style="border:1px solid #000000;padding:5px 20px;margin:0 35px;">
&lt;!-- ヘッダ内 --&gt;
<strong>&lt;!-- sns:id="og_meta" /--&gt;</strong>
<strong>&lt;!-- sns:id="facebook_meta" /--&gt;</strong>
&lt;!-- ページに対するボタンの設置 --&gt;
<strong>&lt;!-- sns:id="facebook_like_button" /--&gt;</strong>
<strong>&lt;!-- sns:id="twitter_button" /--&gt;</strong>
<strong>&lt;!-- sns:id="hatena_button" /--&gt;</strong>
<strong>&lt;!-- sns:id="google_plus_button" /--&gt;</strong>
&lt;!-- 詳細ページに対するボタンの設置 b_block:id="entry_list"内 --&gt;
<strong>&lt;!-- cms:id="facebook_like_button" /--&gt;</strong>
<strong>&lt;!-- cms:id="twitter_button" /--&gt;</strong>
<strong>&lt;!-- cms:id="hatena_button" /--&gt;</strong>
<strong>&lt;!-- cms:id="google_plus_button" /--&gt;</strong>
</pre>

<h3>出力されるメタタグ</h3>
<pre style="border:1px solid #000000;padding:5px 20px;margin:0 35px;">
&lt;meta property="og:title" content="**************" /&gt;
&lt;meta property="og:site_name" content="**************" /&gt;
&lt;meta property="og:url" content="**************" /&gt;
&lt;meta property="og:type" content="****" /&gt;
&lt;meta property="og:image" content="**************" /&gt;
&lt;meta property="og:description" content="**************" /&gt;
&lt;meta property="fb:app_id" content="**************" /&gt;
&lt;meta property="fb:admins" content="**************" /&gt;
</pre>
<p style="padding:3px 40px">※****の部分には自動で値が入ります</p>