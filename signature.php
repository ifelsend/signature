<?php
/*

**************************************************************************

Plugin Name:  博客签名档插件
Plugin URI:   http://ifelsend.com
Description:  输出一个含有博客最新文章的签名图片.博客签名档，可以放置在论坛、贴吧，或是其它可以引用网上图片的位置，签名档会随你的文章同步更新。
Version:      1.0
Author:       Naodai
Author URI:   http://ifelsend.com/

*************************************************************************
*采用输出图片的方式来做.
*
*左侧显示网站的二维码
*右侧显示最新文章
*底下显示博客地址
*
**/

define('IFELSEND_SIGNATURE_AP', ABSPATH . 'wp-content/plugins/signature/');
define('IFELSEND_SIGNATURE_URL', get_option('siteurl').'/wp-content/plugins/signature/');
define('IFELSEND_SIGNATURE_RP', '../wp-content/plugins/signature/');

function ifelsend_signature(){	
}
function ifelsend_signature_admin_menu(){
	add_options_page('博客签名档','博客签名档',  9,__FILE__,'menu');
}

function ifelsend_signature_options(){
	$defaultOptions = array(
		'is_show_date' => 1,
		'date_type' => 'm-d',
		'skin' => '1',
		'siteurl' => get_option('siteurl'),
	);
	$getOptions = get_option('ifelsend_signature');
	$options = array();
	if(false === $getOptions){
		update_option('ifelsend_signature', $defaultOptions);
		$options = $defaultOptions;
	}
	else{
		$options = $getOptions;
	}
	return $options;
}

function ifelsend_createSignature($review = 0){
	global $wpdb;
	
	$options = ifelsend_signature_options();
	
	$tmp = 0;
	if(is_array($review)){
		foreach ($review as $k => $v){
			$options[$k] = $v;
		}
		$tmp = $review['tmp'];
	}
	
	$sql = "SELECT ID, post_title, post_date, comment_count ";
	$sql .= " FROM " . $wpdb->posts;
	$sql .= " WHERE post_status = 'publish' AND post_type = 'post' AND post_password = ''";
	$sql .= " ORDER BY id DESC LIMIT 3";
	
	$posts = $wpdb->get_results($sql);
	$output = '';
	
	$pngDir = IFELSEND_SIGNATURE_AP;
	$pngNum = 1;
	$imageFile = '';
	$imageFile = $pngDir.$options['skin'].'.png';
	
	$image = imagecreatefrompng($imageFile);
	$color = imagecolorallocate($image,"136","136","136");
	
	$siteUrlQrcodeFile = ifelsend_signature_qrcode();
	$qrcodeFileSize = getimagesize($siteUrlQrcodeFile);
	$qrcodeIm = imagecreatefrompng($siteUrlQrcodeFile);
	
	imagecopy($image, $qrcodeIm, 10, 10, 0, 0, $qrcodeFileSize[0], $qrcodeFileSize[1]);
	imagedestroy($qrcodeIm);
	
	$i=0;
	foreach ($posts as $post) {
		$post_title = '' . stripslashes($post->post_title);
		$titleNum = 17;
		$postDate = '';		
		if($options['is_show_date']){
			switch ($options['date_type']){
				case 'm-d':
				case 'd-m': $titleNum = 13;break;
				case 'Y-m-d':
				case 'd-m-Y': $titleNum = 11;break;
				default: $titleNum = 11;
			}
			
			$postDate = " [".date($options['date_type'],strtotime($post->post_date))."]";
		}
		//$output =substr($post_title, 0,$titleNum);
		$output =cut_str($post_title, $titleNum);
		$output .= $postDate;

		$i=$i+11+10;
		imagettftext ( $image, 11, 0, 110, 10+$i, $color, IFELSEND_SIGNATURE_AP . 'weiruanvistafangsong.ttf', $output ); //字体文件路径 输出的内容
	}
	$blogUrl = get_option('siteurl');
	$blogUrlLength = strlen($blogUrl) * 7 + 20;
	$blogUrlStart = 0;
	if($blogUrlLength < 380){
		$blogUrlStart = 380 - $blogUrlLength;
	}
	imagettftext ( $image, 11, 0, $blogUrlStart, 100, $color, IFELSEND_SIGNATURE_AP . 'weiruanvistafangsong.ttf', $blogUrl ); //字体文件路径 输出的内容
	$signatureImage = IFELSEND_SIGNATURE_AP . "title.png";
	if($tmp){
		$signatureImage = IFELSEND_SIGNATURE_AP . "title_tmp.png";
	}
	imagepng($image, $signatureImage);
	imagedestroy($image);	
}

function ifelsend_signature_qrcode(){
	include_once IFELSEND_SIGNATURE_AP . "phpqrcode/qrlib.php";
	$blogUrl = get_option('siteurl');
	$getOptions = ifelsend_signature_options();
	
	$siteUrlQrcodeFile = IFELSEND_SIGNATURE_AP . "siteurlqrcode.png";
	if($blogUrl == $getOptions['siteurl'] && file_exists($siteUrlQrcodeFile)){
		
	}
	else{
		$getOptions['siteurl'] = $blogUrl;
		update_option('ifelsend_signature', $getOptions);
		QRcode::png($blogUrl, $siteUrlQrcodeFile, 'H', 2, 2);
	}
	return $siteUrlQrcodeFile;
}

function menu(){
	$getOptions = ifelsend_signature_options();
	if($_POST){
		$postOptions = array();
		$is_show_date = intval(trim($_POST['is_show_date']));
		$date_type = trim($_POST['date_type']);
		$skin = intval(trim($_POST['skin']));
		if(1 == $is_show_date){
			$postOptions['is_show_date'] = $is_show_date;
		}
		else{
			$postOptions['is_show_date'] = 0;
		}
		$postOptions['date_type'] = $date_type;
		$postOptions['skin'] = $skin;
		$postOptions['siteurl'] = get_option('siteurl');
		
		update_option('ifelsend_signature', $postOptions);
		$getOptions = $postOptions;
		ifelsend_createSignature();
	}
?>
<div class="wrap">
<h2>博客签名档&nbsp;&nbsp;配置选项</h2>
<hr>
<p>博客签名档，可以放置在论坛、贴吧，或是其它可以引用网上图片的位置，签名档会随你的文章同步更新。</p>
<form name="form1" method="post" action="">
<table width="80%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>皮肤主题：
      <label for="skin"></label>
      <input type="hidden" name="skin" id="skin" value="<?php echo $getOptions['skin']?>"></td>
    <td>效果预览：</td>
  </tr>
  <tr>
    <td width="300" class="border">
    	<p id="template_skins">
    		<a href="#" class="<?php if(1 == $getOptions['skin']){echo 'on';}?>"><span class="skin1"><em></em></span></a> 
    		<a href="#" class="<?php if(2 == $getOptions['skin']){echo 'on';}?>"><span class="skin2"><em></em></span></a> 
    		<a href="#" class="<?php if(3 == $getOptions['skin']){echo 'on';}?>"><span class="skin3"><em></em></span></a> 
    		<a href="#" class="<?php if(4 == $getOptions['skin']){echo 'on';}?>"><span class="skin4"><em></em></span></a> 
    		<a href="#" class="<?php if(5 == $getOptions['skin']){echo 'on';}?>"><span class="skin5"><em></em></span></a> 
    		<!-- <a href="#" class=""><span class="skin_pic skin6"><em></em></span></a> 
    		<a href="#" class=""><span class="skin_pic skin7"><em></em></span></a> 
    		<a href="#" class=""><span class="skin_pic skin8"><em></em></span></a> 
    		<a href="#" class=""><span class="skin_pic skin9"><em></em></span></a> 
    		<a href="#" class=""><span class="skin_pic skin10"><em></em></span></a> --> 
    	</p>
    </td>
    <td class="border"><img id="review" src="<?php echo IFELSEND_SIGNATURE_RP?>title.png?<?php echo time();?>" width="390" height="110"></td>
  </tr>
  <tr>
    <td>是否显示发表时间：</td>
    <td>获取代码：</td>
  </tr>
  <tr>
    <td class="border">
      <p>
        <label>
          <input name="is_show_date" type="radio" id="is_show_date_0" value="1" <?php if($getOptions['is_show_date']) echo 'checked';?>>
          是</label>
        <label for="date_type">时间格式</label>
        <select name="date_type" id="date_type" <?php if(!$getOptions['is_show_date']) echo 'disabled';?>>
          <option value="m-d" <?php if('m-d' == $getOptions['date_type']) echo 'selected';?>>月-日</option>
          <option value="Y-m-d" <?php if('Y-m-d' == $getOptions['date_type']) echo 'selected';?>>年-月-日</option>
          <option value="d-m" <?php if('d-m' == $getOptions['date_type']) echo 'selected';?>>日-月</option>
          <option value="d-m-Y" <?php if('d-m-Y' == $getOptions['date_type']) echo 'selected';?>>日-月-年</option>
        </select>      
<br>
        <label>
          <input type="radio" name="is_show_date" value="0" id="is_show_date_1" <?php if(!$getOptions['is_show_date']) echo 'checked';?>>
          否</label>
        <br>
      </p>
    </td>
    <td class="border"><textarea rows="4" cols="60" id="code" name="code"><?php echo IFELSEND_SIGNATURE_URL,'title.png'?></textarea></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><input type="submit" name="submit" id="submit" class="button-primary" value="保存更改"></td>
    <td><a href="http://ifelsend.com/blog/2012/10/26/wordpress%E5%8D%9A%E5%AE%A2%E7%AD%BE%E5%90%8D%E6%A1%A3%E6%8F%92%E4%BB%B6.html">获取更多帮助信息</a>  <a href="http://ifelsend.com/blog/2012/10/26/wordpress%E5%8D%9A%E5%AE%A2%E7%AD%BE%E5%90%8D%E6%A1%A3%E6%8F%92%E4%BB%B6.html">使用反馈</a></td>
  </tr>
</table>
</form>
<style type="text/css">
td{padding:10px;}
#template_skins {padding:4px 0 11px;*padding:3px 0 29px;overflow:hidden;}
#template_skins p{padding-left:5px;}
#template_skins a{display:inline-block;margin:0 0 18px 11px;padding:1px;border:1px solid #c6c6c6;}
#template_skins a:hover,#template_skins  a.on{position:relative;top:2px;margin:-4px -2px 18px 9px;border:3px solid #a8de86;}
#template_skins a span{position:relative;display:block;width:29px;height:29px;background:#e4e4e4;cursor:hand;}
#template_skins a em{position:absolute;right:-1px;bottom:-1px;width:12px;height:12px;background:url(<?php echo IFELSEND_SIGNATURE_RP?>ico_slted.gif);visibility:hidden;}
#template_skins a.on em{visibility:visible;}
#template_skins a:hover{text-decoration:none;}
#template_skins .skin_pic{background-image:url(<?php echo IFELSEND_SIGNATURE_RP?>skins_signature.gif);}
#template_skins .skin1{background:#e4e4e4;}
#template_skins .skin2{background:#ff5f00;}
#template_skins .skin3{background:#62bb5d;}
#template_skins .skin4{background:#699fff;}
#template_skins .skin5{background:#e6c619;}
#template_skins .skin6{background-position:0 0;}
#template_skins .skin7{background-position:-30px 0;}
#template_skins .skin8{background-position:-60px 0;}
#template_skins .skin9{background-position:-90px 0;}
#template_skins .skin10{background-position:-120px 0;}
.border{border-bottom:1px #ccc dashed;}
</style>
<script type="text/javascript">
jQuery('#template_skins a').click(function(){
	jQuery('#template_skins .on').removeClass('on');
	jQuery(this).addClass('on');
	var className = jQuery(this).children('span').attr('class');
	var skin = 1;
	if(className != undefined){
		className = className.replace('skin_pic ','');
		skin = className.replace('skin','');
	}
	jQuery('#skin').val(skin);
	review();
});
jQuery('input:radio').change(function(){
	var val = jQuery(this).val();
	if(val>0){
		jQuery('#date_type').removeAttr('disabled');
	}
	else{
		jQuery('#date_type').attr('disabled','disabled');
	}
	review();
});
jQuery('#date_type').change(function(){
	if(jQuery('input:radio:checked').val()>0){
		review();
	}
});
function review(){
	var skin = jQuery('#skin').val();
	var is_show_date = jQuery('input:radio:checked').val();
	var date_type = jQuery('#date_type').val();
	jQuery.ajax({
        type: "POST",
        url: "<?php echo IFELSEND_SIGNATURE_RP?>index.php?t="+Math.random(),
        data:{'review':1,'skin':skin,'is_show_date':is_show_date,'date_type':date_type},
        success: function(html) {		
            jQuery('#review').attr('src','<?php echo IFELSEND_SIGNATURE_RP?>title_tmp.png?'+Math.random());
        }
    });
}
</script>
<?php 
}

function cut_str($sourcestr,$cutlength)
{
	$returnstr='';
	$i=0;
	$n=0;
	$str_length=strlen($sourcestr);//字符串的字节数
	while (($n<$cutlength) and ($i<=$str_length))
	{
		$temp_str=substr($sourcestr,$i,1);
		$ascnum=Ord($temp_str);//得到字符串中第$i位字符的ascii码
		if ($ascnum>=224)    //如果ASCII位高与224，
		{
			$returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
			$i=$i+3;            //实际Byte计为3
			$n++;            //字串长度计1
		}
		elseif ($ascnum>=192) //如果ASCII位高与192，
		{
			$returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
			$i=$i+2;            //实际Byte计为2
			$n++;            //字串长度计1
		}
		elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
		{
			$returnstr=$returnstr.substr($sourcestr,$i,1);
			$i=$i+1;            //实际的Byte数仍计1个
			$n++;            //但考虑整体美观，大写字母计成一个高位字符
		}
		else                //其他情况下，包括小写字母和半角标点符号，
		{
			$returnstr=$returnstr.substr($sourcestr,$i,1);
			$i=$i+1;            //实际的Byte数计1个
			$n=$n+0.5;        //小写字母和半角标点等与半个高位字符宽...
		}
	}
// 	if ($str_length>$cutlength){
// 		$returnstr = $returnstr . "...";//超过长度时在尾处加上省略号
// 	}
	return $returnstr;

}
//add_action('init', 'ifelsend_createSignature');
//发表文章时执行的函数
add_action('publish_post','ifelsend_createSignature',1); 
add_action('admin_menu', 'ifelsend_signature_admin_menu');

//插件页面显示设置连接
function signature_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/signature.php' ) ) {
		$links[] = '<a href="admin.php?page=signature/signature.php">'.__('Settings').'</a>';
	}

	return $links;
}
add_filter( 'plugin_action_links', 'signature_plugin_action_links', 10, 2 );