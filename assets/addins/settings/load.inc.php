<?php
$dirroot=$GLOBALS['LOADED_ADDIN']->dirroot['value'];

define ('USURI',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('USPATH',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info

if (!empty($_POST['send']))
{
  require USPATH."/apply.inc.php";
  if (apply_settings($_POST))
  {
    header("Location: //".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.basename($dirroot));
    exit();
  }
  else
  {
    trigger_error("Unable to change settings!",E_USER_ERROR);
  }
}
else
{
  require USPATH."/form.inc.php";
  $child=new SettingsForm();
}

$tpl=new MomokoTemplate($dirroot.'/templates/main.tpl.htm');
echo ($tpl->toHTML($child));