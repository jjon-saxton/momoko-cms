<?php
require dirname(__FILE__)."/core/common.inc.php";
require dirname(__FILE__)."/core/content.inc.php";

if(isset($_GET['action']) && !empty($_GET['action']))
{
 if (isset ($_GET['q']) && !empty($_GET['q']))
 {
   $path_parts($explode("/",$_GET['q']));
   if ($path_parts[0] == 'addin')
   {
    $path_parts=array_splice($path_parts,1);
    do_addin(implode("/",$path_parts),$_GET['action']);
    exit();
   }
  }
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
     $child=new MomokoError('Unauthorized');
    }
   }
   else
   {
    header("Location: //".$GLOBALS['SET']['domain'].$GLOBALS['SET']['location']."/mk_login.php");
	exit();
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location: //".$GLOBALS['SET']['domain'].$GLOBALS['SET']['location']."/mk_login.php");
     exit();
    }
   }
   else
   {
    header("Location: //".$GLOBALS['SET']['domain'].$GLOBALS['SET']['location']."/mk_login.php?action=create");
	exit();
   }
   break;
   case 'logout':
   if ($GLOBALS['USR']->logout())
   {
    $_SESSION['data']=serialize($GLOBALS['USR']);
    header("Location: ?loggedin=0");
   }
   break;
  }
  $tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
  print $tpl->toHTML($child);
}
else
{
	include dirname(__FILE__)."/core/deliver.php";
	@$str=$_GET['q'];
	$path_parts=explode("/",$str);
	switch($path_parts[0])
	{
		case "addin":
		case "news":
		case "help":
		case "file":
		case "page":
		$run="do_".$path_parts[0];
		$path_parts=array_splice($path_parts,1);
		break;
		default:
		$run="do_page";
	}

	$run(implode("/",$path_parts));
}
?>
