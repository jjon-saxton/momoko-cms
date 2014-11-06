<?php
if (!file_exists(dirname(__FILE__)."/database.ini")) //database.ini does not exist! go to mk_install.php to create it.
{
 header("Location: http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].dirname($_SERVER['SCRIPT_NAME'])."/mk-install.php");
 exit();
}

require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (is_writable($GLOBALS['SET']['basedir']))
{
 trigger_error("Security Notice: MomoKO's base directory is writable!",E_USER_NOTICE);
}
if (!is_writable($GLOBALS['SET']['filedir']))
{
 trigger_error("MomoKO's content storage directory is not writable!",E_USER_NOTICE);
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
  $path_parts=array_splice($path_parts,1); //TODO this seems to execute even when no query string is given!
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
    if ($_GET['p'])
    {
     $child->fetchByID($_GET['p']);
    }
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
    if ($_GET['p'])
    {
     $child->fetchByID($_GET['p']);
    }
    $child->drop();
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
 $path_parts=explode("/",@$_GET['q']);
 
 if (!$path_parts[0] && $_GET['content'])
 {
  $type=$_GET['content'];
 }
 else
 {
  $type=$path_parts[0];
 }
 
 if (isset($_GET['p']))
 {
  $id=$_GET['p'];
 }
 else
 {
  $id=$_GET['id'];
 }
 
 switch($type)
 {
  case "addin":
  case "post":
  case "attachment":
  case "page":
  $run="do_".$type;
  $path_parts=array_splice($path_parts,1);
  break;
  default:
  $run="do_page";
 }

 $run(implode("/",$path_parts),$id);
}
?>