<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (isset($_GET['action']) && $_GET['action'] == 'new')
{
	$formname="Register";
}
else
{
	$formname="Login";
}
$props['link']=$GLOBALS['SET']['baseuri']."/?action=".strtolower($formname);
$props['recovery']=$GLOBALS['SET']['baseuri'].ADDINROOT.'passreset/';

$form=new MomokoPage($formname,$props);

$tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
print $tpl->toHTML($form);