<?php
require "./assets/php/common.inc.php";
require "./assets/php/content.inc.php";

if (@$_SERVER['PATH_INFO'] && (pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'htm' || pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'html'))
{
  $path=$_SERVER['PATH_INFO'];
}
elseif (@$_SERVER['PATH_INFO'] && pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'php')
{
 $path=$_SERVER['PATH_INFO'];
 include $GLOBALS['CFG']->basedir.$_SERVER['PATH_INFO'];
 $child=new MomokoLITECustom($_GET);
}
elseif (@$_SERVER['PATH_INFO'] && pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'txt')
{
 if (pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME) == 'sitemap')
	{
		$nav=new MomokoLITENavigation(null,'display=list');
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
		$mod=new MomokoLITENews(null,'type=recent');
		echo ($mod->getModule('rss'));
		break;
		case  'atom':
		$format='atom';
		header("Content-type: application/atom-xml");
		$nav=new MomokoLITENews(null,'type=recent');
		echo ($nav->getModule($format));
	 break;
		default:
		echo(file_get_contents($GLOBALS['CFG']->basedir.$_SERVER['PATH_INFO']));
	}
	exit();
}
elseif (@$_SERVER['PATH_INFO'])
{
 $nav=new MomokoLITENavigation(null,'display=none');
 $path=$nav->getIndex($_SERVER['PATH_INFO']);
}
else
{
  $nav=new MomokoLITENavigation(null,'display=none');
  $path=$nav->getIndex();
}

if (@$path && !@$child)
{
 $child=new MomokoLITEPage($path);
 if (@!empty($_GET['action']))
 {
  switch ($_GET['action'])
  {
   case 'new':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child=new MomokoLITEPage(pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_DIRNAME).'/new_page.htm');
    $child->put($_POST);
   }
   else
   {
    header("Location: ?action=login&re=new");
    exit();
   }
   case 'edit':
   if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
   {
    $child->put($_POST);
   }
   else
   {
    header("Location: ?action=login&re=edit");
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
      header("Location: ?action=".$_GET['re']);
     }
     else
     {
      header("Location: ?loggedin=1");
     }
     exit();
    }
    else
    {
     $child=new MomokoLITEError('Unauthorized');
    }
   }
   else
   {
    $child=new MomokoForm('login');
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location:/?action=login");
     exit();
    }
   }
   else
   {
    $child=new MomokoForm('register');
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

$tpl=new MomokoLITETemplate(pathinfo($path,PATHINFO_DIRNAME));
print $tpl->toHTML($child);

?>
