<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (isset($_GET['action']) && $_GET['action'] == 'new')
{
	$formname="register";
}
else
{
	$formname="login";
}
$props['link']=$GLOBALS['SET']['baseuri']."/?action=".$formname;
$props['recovery']=$GLOBALS['SET']['baseuri'].ADDINROOT.'passreset/';

$form=new MomokoPage("/forms/{$formname}.htm",$props);

$tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
print $tpl->toHTML($form);