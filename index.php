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

if ($config->version < sprintf("%.1f",MOMOKOVERSION)) // It is possible the database does not match the script version
{
 //header("Location: http://".$config->baseuri."/mk-update.php");
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
   $child=new MomokoContent($path,$auth);
   break;
  }
  switch ($_GET['action'])
  {
   case 'new':
   if ($auth->inGroup('admin') || $auth->inGroup('editor'))
   {
    if (!empty($_GET['file']))
    {
     $html=file_get_contents($config->basedir.$_GET['file']);
     $data=parse_page($html);
     unset($html);
     $data['text']=$data['inner_body'];
     $data['link']=$config->siteroot.$_GET['file'];
     unlink($config->basedir.$_GET['file']);
    }
    else
    {
     $data['title']="New...";
     $data['author']=$user->num;
     $data['date_created']=date("Y-m-d H:i:s");
     $data['status']="cloaked";
     $data['parent']=0;
     $data['text']=file_get_contents($config->basedir.$config->filedir."forms/new.htm");
     
     if (!empty($_GET['content']))
     {
      $data['type']=$_GET['content'];
     }
     else
     {
      $data['type']="unknown";
     }
    }
    
    $data['mime_type']="text/html";
    $num=$child->fetchByLink($data['link']);
    if (!empty($num))
    {
     if ($child->update($data,$num))
     {
      header("Location: ".$config->siteroot."?p=".$num);
      exit();
     }
    }
    if ($num=$child->putTemp($data))
    {
     header("Location: ".$config->siteroot."?p=".$num);
     exit();
    }
   }
   else
   {
    header("Location: https://".$config->baseuri."?action=login&re=new");
    exit();
   }
   break;
   case 'edit':
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
    header("Location: //".$config->baseuri."/mk-login.php");
	exit();
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location: //".$config->baseuri."/mk-login.php");
     exit();
    }
   }
   else
   {
    header("Location: //".$config->baseuri."/mk-login.php?action=new");
    exit();
   }
   case 'passreset':
   header("Location: //".$config->baseuri."/mk-login.php?action=reset");
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
  $run="do_addin";
  break;
  case "attachment":
  case "page":
  case "post":
  default:
  $run="do_content";
 }
 
 if (is_array($path_parts))
 {
  $path_parts=array_splice($path_parts,1);
 }

 $child=$run(implode("/",$path_parts),$auth,$id);
 if ($child instanceof MomokoContent || $child instanceof MomokoFeed)
 {
  $tpl=new MomokoTemplate($auth,$config);
  print $tpl->toHTML($child);
 }
}
?>