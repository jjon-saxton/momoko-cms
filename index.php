<?php
if (!file_exists(dirname(__FILE__)."/database.ini")) //database.ini does not exist! go to mk_install.php to create it.
{
 header("Location: http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].dirname($_SERVER['SCRIPT_NAME'])."/mk-install.php");
 exit();
}

require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (is_writable($config->basedir))
{
 trigger_error("Security Notice: MomoKO's base directory is writable!",E_USER_NOTICE);
}
if (!is_writable($config->basedir.$config->filedir))
{
 trigger_error("MomoKO's content storage directory is not writable!",E_USER_NOTICE);
}

if ($config->version < preg_replace("/[^0-9,.]/","",MOMOKOVERSION)) // It is possible the database does not match the script version
{
 header("Location: http://".$config->basedir."/mk-update.php");
 exit();
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
  if ((empty($_GET['content']) || empty($_GET['p'])) && is_array($path_parts))
  {
   $path_parts=array_splice($path_parts,1); //TODO this seems to execute whenever ?content=post!?
   $path=implode("/",$path_parts);
  }
  switch ($_GET['content'])
  {
   case 'attachment':
   $child=new MomokoAttachment($path);
   break;
   case 'post':
   case 'page':
   default:
   $child=new MomokoPage($path,$auth);
   break;
  }
  switch ($_GET['action'])
  {
   case 'new':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child=new MomokoPage("New...",$auth);
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
    elseif ($_GET['link'])
    {
     $child->fetchByLink($_GET['link']);
    }
    $child->put($_POST);
   }
   else
   {
    header("Location: https://".$config->baseuri."?action=login&re=edit");
    exit();
   }
   break;
   case 'delete':
   if ($auth->inGroup('admin') || $auth->inGroup('editor'))
   {
    if ($_GET['p'])
    {
     $child->fetchByID($_GET['p']);
    }
    elseif ($_GET['link'])
    {
     $child->fetchByLink($_GET['link']);
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
    if ($auth->login($_POST['name'],$_POST['password']))
    {
     $_SESSION['data']=serialize($auth);
     if (@!empty($_GET['re']))
     {
      header("Location: http://".$config->baseuri."?action=".$_GET['re']);
     }
     else
     {
      header("Location: http://".$config->baseuri."#logged-in");
     }
     exit();
    }
    else
    {
     $child=new MomokoError('401 Unauthorized',$auth);
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
     header("Location: //".$GLOBALS['SET']['baseuri']."/mk-login.php");
     exit();
    }
   }
   else
   {
    header("Location: //".$GLOBALS['SET']['baseuri']."/mk-login.php?action=new");
    exit();
   }
   case 'passreset':
   header("Location: //".$GLOBALS['SET']['baseuri']."/mk-login.php?action=reset");
   break;
   break;
   case 'logout':
   if ($auth->logout())
   {
    $_SESSION['data']=serialize($auth);
    header("Location: http://".$config->baseuri."#logged-out");
    exit();
   }
   break;
  }

  $tpl=new MomokoTemplate($auth,$config);
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
  case "feed":
  case "rss":
  case "atom":
  $run="do_feed";
  $path="/";
  $id=$type;
  break;
  case "addin":
  case "attachment":
  case "page":
  $run="do_".$type;
  case "post":
  $run="do_page";
  $path_parts=array_splice($path_parts,1);
  break;
  default:
  $run="do_page";
 }

 $child=$run(implode("/",$path_parts),$auth,$id);
 if ($child instanceof MomokoPage || $child instanceof MomokoFeed)
 {
  $tpl=new MomokoTemplate($auth,$config);
  print $tpl->toHTML($child);
 }
}
?>
