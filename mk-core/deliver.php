<?php

function do_attachment($path,$id=null)
{
 header("Location: {$_GET['link']}");
}

function do_page($path,$id=null)
{
if (@$path && (pathinfo($path,PATHINFO_EXTENSION) == 'htm' || pathinfo($path,PATHINFO_EXTENSION) == 'html'))
{
  $path=$path;
}
elseif (@$path && pathinfo(@$path,PATHINFO_EXTENSION) == 'php')
{
 $path=$path;
 include $GLOBALS['CFG']->basedir.$path;
 $child=new MomokoCustom($_GET);
}
elseif (@$path && pathinfo(@$path,PATHINFO_EXTENSION) == 'txt')
{
 if (pathinfo($path,PATHINFO_FILENAME) == 'sitemap')
	{
		$nav=new MomokoNavigation(null,'display=list');
		header("Content-type: text/plain");
		echo ($nav->getModule('plain'));
	}
	else
	{
		echo(file_get_contents($GLOBALS['CFG']->basedir.$path));
	}
	exit();
}
elseif (@$path && pathinfo(@$path,PATHINFO_EXTENSION) == 'xml')
{
 switch(pathinfo($path,PATHINFO_FILENAME))
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
		echo(file_get_contents($GLOBALS['CFG']->basedir.$path));
	}
	exit();
}
/*elseif (@$path)
{
 $nav=new MomokoNavigation(null,'display=none');
 $path=$nav->getIndex($path);
}*/

if (!@$child)
{
 $child=new MomokoPage($path);
 if ($id)
 {
  $child->fetchByID($id);
 }
}

$tpl=new MomokoTemplate(pathinfo($path,PATHINFO_DIRNAME));
print $tpl->toHTML($child);
}

function do_post($path,$id=null)
{
 $child=new MomokoNews($GLOBALS['USR']);
 if (!$id)
 {
  $headline=basename($path);
  $child->getPostByHeadline($headline);
 }
 else
 {
  $child->getPostByID($id);
 }
 
 $tpl=new MomokoTemplate(dirname($path));
 print $tpl->toHTML($child);
}

function do_addin($path,$id=null,$action=null)
{
if (@$action == 'login' || @$action == 'logout' || @$action == 'register')
{
 header("Location: //".$GLOBALS['SET']['baseuri']."?action=".$action);
 exit();
}
elseif (@$path)
{
 $addindir=trim($path,"/");
 if (empty($addindir))
 {
  $path=null;
 }
 else
 {
  $path=$GLOBALS['CFG']->basedir."/addins/".$addindir."/";
 }
 $GLOBALS['LOADED_ADDIN']=new MomokoAddin($path);
 if (!empty($_GET['num']))
 {
  $GLOBALS['LOADED_ADDIN']->setPathByID($_GET['num']);
 }

 switch (@$action)
 {
  case 'add':
  $child=$GLOBALS['LOADED_ADDIN']->put($_POST);
  if (!empty($_POST['dir']) && (!empty($_GET['ajax']) && $_GET['ajax'] == 1))
  {
    echo json_encode($child);
    exit();
  }
  break;
  case 'update':
  $child=$GLOBALS['LOADED_ADDIN']->update($_POST);
  if (!empty($_POST['dir']) && (!empty($_GET['ajax']) && $_GET['ajax'] == 1))
  {
    echo json_encode($child);
    exit();
  }
  break;
  case 'enable':
  case 'disable':
  echo $newstate=$GLOBALS['LOADED_ADDIN']->toggleEnabled();
  break;
  case 'remove';
  $child=$GLOBALS['LOADED_ADDIN']->drop();
  if (!empty($_POST['send']) && (!empty($_GET['ajax']) && $_GET['ajax'] == 1))
  {
    echo json_encode($child);
    exit();
  }
  break;
  case 'upload':
  $child=$GLOBALS['LOADED_ADDIN']->upload($_FILES['addin']);
  break;
  case 'list':
  if ($GLOBALS['USR']->inGroup('admin'))
  {
    $child=new MomokoAddinForm('list');
  }
  else
  {
    $child=MomokoError("Forbidden");
  }
  break;
  default:
  if ($GLOBALS['LOADED_ADDIN']->hasAuthority() && $GLOBALS['LOADED_ADDIN']->isEnabled) //User must have authority, and the addin must be enabled!
  {
   include $GLOBALS['SET']['basedir']."/addins/".$addindir."/load.inc.php"; //hand control over to the addin
  }
  elseif (!$GLOBALS['LOADED_ADDIN']->isEnabled)
  {
    $child=new MomokoError('Disabled');
  }
  else
  {
   $child=new MomokoError('Forbidden');
  }
 }

 if ((array_key_exists('dialog',$_GET) && $_GET['dialog']) || (array_key_exists('ajax',$_GET) && $_GET['ajax'] == 1))
 {
  echo $child->inner_body;
 }
 else
 {
  $tpl=new MomokoTemplate($GLOBALS['LOADED_ADDIN']->dirroot['value'].'/templates/main.tpl.htm');
  print $tpl->toHTML($child);
 }
}
else
{
 if (@$_GET['action'] == 'list')
 {
  if ($GLOBALS['USR']->inGroup('admin'))
  {
    $child=new MomokoAddinForm('list');
  }
  else
  {
    $child=MomokoError("Forbidden");
  }

  $tpl=new MomokoTemplate('/');
  print $tpl->toHTML($child);
 }
}
}

?>
