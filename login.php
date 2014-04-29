<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

if (isset($_GET['action']) && $_GET['action'] == 'create')
{
	$formname="register";
}
else
{
	$formname="login";
}
$props['link']=$GLOBALS['SET']['domain'].$GLOBALS['SET']['location']."/?action=".$formname;

$form=new Momokopage("/forms/{$formname}.htm",$props);

$tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
print $tpl->toHTML($form);
