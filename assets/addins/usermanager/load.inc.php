<?php
$dirroot=$GLOBALS['LOADED_ADDIN']->dirroot['value'];

define ('UMURI',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('UMPATH',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info

require UMPATH.'/main.inc.php';

$child=new UserManager(@$_GET['action']);

if (@$_GET['dialog'])
{
 include UMPATH.'/dialogs.inc.php';
 $child=new UMDialog($_GET['dialog']);
 echo ($child->build('dialog-'.$_GET['dialog']));
}
elseif (@$_GET['ajax'])
{
 header("Content-type: application/json");
 echo(json_encode($child->get()));
}
else
{
 $child->setInfo();
 $tpl=new MomokoLITETemplate($dirroot.'/templates/main.tpl.htm');
 echo ($tpl->toHTML($child));
}
