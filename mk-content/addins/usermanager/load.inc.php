<?php
$dirroot=$GLOBALS['LOADED_ADDIN']->dirroot['value'];

define ('UMURI',$GLOBALS['SET']['baseuri'].ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('UMPATH',$GLOBALS['SET']['basedir'].$dirroot); //sets script base using the same info

require UMPATH.'/main.inc.php';

$child=new UserManager(@$_GET['action']);

if (@$_GET['dialog'])
{
 include UMPATH.'/dialogs.inc.php';
 $child=new UMDialog($_GET['dialog']);
 $child->build('dialog-'.$_GET['dialog']);
}
elseif (@$_GET['ajax'])
{
 header("Content-type: application/json");
 $child->getJSON();
}
else
{
 $child->setInfo();
}
