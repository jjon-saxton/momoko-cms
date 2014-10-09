<?php

function do_file($path)
{
require_once $GLOBALS['SET']['basedir'].'/core/ximager.inc.php';
if (@$path && (pathinfo($path,PATHINFO_EXTENSION) != 'html' || pathinfo($path,PATHINFO_EXTENSTION) != 'php'))
{
 $img=new MomokoImage($GLOBALS['CFG']->datadir.$path);
 if ($img->isImage())
 {
  if (!empty($_GET['w']) && empty($_GET['h']))
  {
   $img->resizeToWidth($_GET['w']);
  }
  elseif (empty($_GET['w']) && !empty($_GET['h']))
  {
   $img->resizeToHeight($_GET['h']);
  }
  elseif (!empty($_GET['w']) && !empty($_GET['h']))
  {
   $img->resize($_GET['w'],$_GET['h']);
  }
  elseif (!empty($_GET['scale']))
  {
   $img->scale($_GET['scale']);
  }
  header("Content-type: image/png");
  if (@$_GET['download'] == TRUE)
  {
   header('Content-Disposition: attachment; filename="'.pathinfo($path,PATHINFO_FILENAME).'.png"');
  }
  else
  {
   header('Content-Disposition: inline; filename="'.pathinfo($path,PATHINFO_FILENAME).'.png"');
  }
  $data=$img->get();
  unset($img);
 }
 else
 {
  $filename=$GLOBALS['CFG']->datadir.$path;
  $finfo=new finfo(FILEINFO_MIME);
  $mime=$finfo->file($filename);
  if (@$_GET['download'] == TRUE)
  {
   header('Content-Disposition: attachment');
  }
  header("Content-type: ".$mime);
  $data=file_get_contents($filename);
 }
}

echo($data);
}

function do_page($path)
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
elseif (@$path)
{
 $nav=new MomokoNavigation(null,'display=none');
 $path=$nav->getIndex($path);
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
    $child=new MomokoPage(pathinfo(@$path,PATHINFO_DIRNAME).'/new_page.htm');
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
    $child=new MomokoForm('login');
   }
   break;
   case 'register':
   if (@$_POST['first'])
   {
    $usr=new MomokoUser($_POST['name']);
    if ($usr->put($_POST))
    {
     header("Location:?action=login");
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

$tpl=new MomokoTemplate(pathinfo($path,PATHINFO_DIRNAME));
print $tpl->toHTML($child);
}

function do_addin($path,$action=null)
{
if (@$action == 'login' || @$action == 'logout' || @$action == 'register')
{
 header("Location: //".$GLOBALS['SET']['baseuri']."?action=".$action);
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
}

?>
