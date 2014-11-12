<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

if (@$_SERVER['PATH_INFO'] && (pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'htm' || pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'html'))
{
  $path=$_SERVER['PATH_INFO'];
}
elseif (@$_SERVER['PATH_INFO'] && pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'php')
{
 $path=$_SERVER['PATH_INFO'];
 include $GLOBALS['CFG']->basedir.$_SERVER['PATH_INFO'];
 $child=new MomokoCustom($_GET);
}
elseif (@$_SERVER['PATH_INFO'] && pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'txt')
{
 if (pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME) == 'sitemap')
	{
		$nav=new MomokoNavigation(null,'display=list');
		header("Content-type: text/plain");
		echo ($nav->getModule('plain'));
	}
	else
	{
		echo(file_get_contents($GLOBALS['CFG']->basedir.$_SERVER['PATH_INFO']));
	}
	exit();
}
elseif (@$_SERVER['PATH_INFO'] && pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'xml')
{
 switch(pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME))
	{
		case 'feed':
		case 'rss':
		$format='rss';
		header("Content-type: application/rss+xml");
		$mod=new MomokoNews(null,'type=recent');
		echo ($mod->getModule('rss'));
		break;
		case  'atom':
		$format='atom';
		header("Content-type: application/atom-xml");
		$nav=new MomokoNews(null,'type=recent');
		echo ($nav->getModule($format));
	 break;
		default:
		echo(file_get_contents($GLOBALS['CFG']->basedir.$_SERVER['PATH_INFO']));
	}
	exit();
}
elseif (@$_SERVER['PATH_INFO'])
{
 $nav=new MomokoNavigation(null,'display=none');
 $path=$nav->getIndex($_SERVER['PATH_INFO']);
}
else
{
  $nav=new MomokoNavigation(null,'display=none');
  $path=$nav->getIndex();
}

if (@$path && !@$child)
{
 $child=new MomokoPage($path);
 if (@!empty($_GET['action']))
 {
  switch ($_GET['action'])
  {
   case 'new':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child=new MomokoPage(pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_DIRNAME).'/new_page.htm');
    $child->put($_POST);
   }
   else
   {
    header("Location: https://".CURURI."?action=login&re=new");
    exit();
   }
   case 'edit':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child->put($_POST);
   }
   else
   {
    header("Location: https://".CURURI."?action=login&re=edit");
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
    else
    {
     trigger_error("Cannot remove page, please check permissions!",E_USER_ERROR);
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
      header("Location: http://".CURURI."?action=".$_GET['re']);
     }
     else
     {
      header("Location: http://".CURURI."?loggedin=1");
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
    header("Location://".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."/login.php");
     exit();
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location://".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."/login.php");
     exit();
    }
   }
   else
   {
    header ("Location://".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."/login.php?action=create");
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
 }
}

$tpl=new MomokoTemplate(pathinfo($path,PATHINFO_DIRNAME));
print $tpl->toHTML($child);

?>
