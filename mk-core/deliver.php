<?php

function do_feed($path,$user,$type=null)
{
   $child=new MomokoFeed($path);
   $child->type=$type;
   $child->get();

   print $child->full_html;
}

function do_content($path,$user,$id=null)
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

if (!@$child)
{
 $child=new MomokoContent($path,$user);
 if ($id)
 {
  $child->fetchByID($id);
 }
}

 return $child;
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
