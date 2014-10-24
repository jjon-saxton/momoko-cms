<?php
if (!file_exists(dirname(__FILE__)."/database.ini")) //database.ini does not exist! go to mk_install.php to create it.
{
 header("Location: //".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/mk-install.php");
 exit();
}

require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (is_writable($GLOBALS['SET']['basedir']))
{
 trigger_error("Security Warning: MomoKO's base directory is writable!",E_USER_WARNING);
}
if (!is_writable($GLOBALS['SET']['filedir']))
{
 trigger_error("MomoKO's content storage directory is not writable!",E_USER_WARNING);
}

if(isset($_GET['action']) && !empty($_GET['action']))
{
 if (isset ($_GET['q']) && !empty($_GET['q']))
 {
   $path_parts=(explode("/",$_GET['q']));
   if ($path_parts[0] == 'addin')
   {
    include dirname(__FILE__)."/mk-core/deliver.php";
    $path_parts=array_splice($path_parts,1);
    do_addin(implode("/",$path_parts),$_GET['action']);
    exit();
   }
  }
  $path_parts=array_splice($path_parts,1);
  $path=implode("/",$path_parts);
  $child=new MomokoPage($path);
  switch ($_GET['action'])
  {
   case 'new':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child=new MomokoPage(pathinfo(@$path,PATHINFO_DIRNAME).'/new_page.htm');
    $child->put($_POST);
   }
   else
   {
    header("Location: https://".$GLOBALS['SET']['baseuri']."?action=login&re=new");
    exit();
   }
   break;
   case 'edit':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child->put($_POST);
   }
   else
   {
    header("Location: https://".$GLOBALS['SET']['baseuri']."?action=login&re=edit");
    exit();
   }
   break;
   case 'delete':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    if ($child->drop())
    {
     header("Location: //".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location);
     exit();
    }
   }
   else
   {
    header("Location: ?action=login&re=delete");
    exit();
   }
   break;
   case 'login':
   if (@!empty($_POST['password']))
   {
    if ($GLOBALS['USR']->login($_POST['name'],$_POST['password']))
    {
     $_SESSION['data']=serialize($GLOBALS['USR']);
     if (@!empty($_GET['re']))
     {
      header("Location: http://".$GLOBALS['SET']['baseuri']."?action=".$_GET['re']);
     }
     else
     {
      header("Location: http://".$GLOBALS['SET']['baseuri']."?loggedin=1");
     }
     exit();
    }
    else
    {
     $child=new MomokoError('401 Unauthorized');
    }
   }
   else
   {
    header("Location: //".$GLOBALS['SET']['baseuri']."/mk-login.php");
	exit();
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location: //".$GLOBALS['SET']['baseuri']."/mk_login.php");
     exit();
    }
   }
   else
   {
    header("Location: //".$GLOBALS['SET']['baseuri']."/mk_login.php?action=new");
    exit();
   }
   break;
   case 'logout':
   if ($GLOBALS['USR']->logout())
   {
    $_SESSION['data']=serialize($GLOBALS['USR']);
    header("Location: ?loggedin=0");
    exit();
   }
   break;
  }
  $tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
  print $tpl->toHTML($child);
}
else
{
 include dirname(__FILE__)."/mk-core/deliver.php";
 @$str=$_GET['q'];
 $path_parts=explode("/",$str);
 switch($path_parts[0])
 {
  case "addin":
  case "post":
  case "help":
  case "file":
  case "page":
  $run="do_".$path_parts[0];
  $path_parts=array_splice($path_parts,1);
  $letter=substr($path_parts[0],0,1);
  break;
  default:
  $run="do_page";
  $letter='p';
 }

 $run(implode("/",$path_parts),$_GET[$letter]);
}
?>