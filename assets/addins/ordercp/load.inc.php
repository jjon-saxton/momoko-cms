<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
}
	 
define ('CPROOT',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('CPBASE',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info

require CPBASE.'/main.inc.php';

switch (@$_GET['action'])
{
 case 'login':
 if (@$_POST['password'])
 {
  if ($GLOBALS['USR']->login($_POST['name'],$_POST['password']))
  {
   $_SESSION['data']=serialize($GLOBALS['USR']);
   header("Location: ".CPROOT);
   exit();
  }
 }
 else
 {
  $child=new OCPUser($GLOBALS['USR']);
 }
 break;
 case 'logout':
 if ($GLOBALS['USR']->logout())
 {
  $_SESSION['data']=serialize($GLOBALS['USR']);
  header("Location: ".CPROOT);
  exit();
 }
 break;
 case 'display':
 case 'show':
 case 'view':
 require_once CPBASE.'/view.inc.php';
 $child=new OCPView($_GET['o']);
 break;
 case 'edit':
 require_once CPBASE.'/edit.inc.php';
 $child=new OCPEdit($_GET['o']);
 break;
 case 'find':
 case 'search':
 require_once CPBASE.'/find.inc.php';
 $child=new OCPFind(@$_GET['q']);
 break;
 default:
 $child=new OCPBasicForm();
 break;
}

$tpl=new MomokoLITETemplate($dirroot.'/templates/main.tpl.htm');
echo ($tpl->toHTML($child));
