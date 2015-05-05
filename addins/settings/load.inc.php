<?php
$dirroot=$GLOBALS['LOADED_ADDIN']->dirroot['value'];

define ('USSURI',$GLOBALS['SET']['baseuri'].ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('USSPATH',$GLOBALS['SET']['basedir'].$dirroot); //sets script base using the same info

if (!empty($_POST['send']))
{
  require USSPATH."/apply.inc.php";
  if ($ok=apply_settings($_POST))
  {
    //header("Location: //".$GLOBALS['SET']['baseuri'].ADDINROOT.basename($dirroot));
    var_dump($ok);
    exit();
  }
  else
  {
    trigger_error("Unable to change settings!",E_USER_ERROR);
  }
}
else
{
  require USSPATH."/form.inc.php";
  $child=new SettingsForm();
}
