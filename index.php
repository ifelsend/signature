<?php 
require_once dirname(__FILE__).'/../../../wp-load.php';
require_once dirname(__FILE__).'/../../../wp-admin/admin.php';

require_once dirname(__FILE__).'/signature.php';

$review = intval(trim($_POST['review']));
$skin = intval(trim($_POST['skin']));
$is_show_date = intval(trim($_POST['is_show_date']));
$date_type = trim($_POST['date_type']);
if($review && $skin){
	$review = array(
		'skin' => $skin,
		'is_show_date' => $is_show_date,
		'date_type' => $date_type,
		'tmp' => 'tmp'
	);
	ifelsend_createSignature($review);
	echo 'OK';
}
else{
?>
	<img src="./title.png" />
<?php 
}
?>