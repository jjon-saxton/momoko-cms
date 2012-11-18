<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
}
	 
define ('BBROOT',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('BBBASE',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info
require BBBASE."/main.inc.php";

@list(,,$forum,$subject,$post)=explode('/',$_SERVER['PATH_INFO']);

if (@$_GET['action'] == 'post' || @$_GET['action'] == 'edit') // TODO: NEEDS Security check!!
{
 require BBBASE.'/post.inc.php';
 $ptbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
 $pdata=$ptbl->getData(array('num','name','bbc','html'),'name~'.urldecode($forum),null,1);
 $pdata=$pdata->first();
 $child=new VictoriqueThread($subject);
 $notify=explode(",",$child->subscribers);
 switch ($_GET['what'])
 {
  case 'topic':
  if (@$_POST['subject'])
  {
   $data=$_POST;
   if ($_POST['subscribe'] == 'y')
   {
    $data['subscribers']=$GLOBALS['USR']->num;
   }
   $child->parent=$pdata->num;
   if ($child->put($data,$notify))
   {
	  header("Location: ".BBROOT.$_SERVER['PATH_INFO']);
   }
   else
   {
    $error=new MomokoLITEError('Server_Error');
	  $child->title=$error->title;
	  $child->inner_body=$error->inner_body;
   }
  }
  else
  {
   $info=$child->editForm(array('bbc_level'=>$pdata->bbc,'allow_html'=>$pdata->html));
   $child->title=$info['title'];
   $child->inner_body=$info['inner_body'];
  }
  break;
  case 'reply':
  $dptbl=new DataBaseTable(DAL_TABLE_PRE.'bb_threads',DAL_DB_DEFAULT);
  $dpdata=$dptbl->getData(array('num','subject'),'subject~'.urldecode($subject),null,1);
  $dpdata=$dpdata->first();
  $obj=new VictoriqueSinglePost($post);
  $ptopic=$child->get();
  if (@$_POST['subject'])
  {
   $obj->parent=$ptopic->num;
   $obj->author=$GLOBALS['USR']->num;
   $obj->added=date('Y-m-d H:i:s');
   $obj->modified=date('Y-m-d H:i:s');
   if ($reply=$obj->put($_POST,$notify))
   {
    $thread['num']=$ptopic->num;
    $thread['last_reply']=$reply;
    $thread['replies']=($ptopic->replies)+1;
    if ($_POST['subscribe'] == 'y')
    {
     $thread['subscribers']=trim($pdata->subscribers.','.$GLOBALS['USR']->num,",");
    }
    if ($child->put($thread))
    {
     header("Location: ".BBROOT.$_SERVER['PATH_INFO']);
     exit();
    }
   }
   else
   {
    $error=new MomokoLITEError('Server_Error');
	  $child->title=$error->title;
	  $child->inner_body=$error->inner_body;
   }
  }
  else
  {
   if (!$obj->subject)
   {
	  $obj->subject="RE: ".$ptopic->subject;
   }
   $info=$obj->editForm(array('bbc_level'=>$pdata->bbc,'allow_html'=>$pdata->html));
   $child->title=$info['title'];
   $child->inner_body=$info['inner_body'];
  }
  break;
 }
}
elseif (@$forum && @$subject)
{
 require BBBASE."/post.inc.php";

 $child=new VictoriqueThread($subject);
 if (@$post)
 {
  //$child->getPost($post);
 }
}
elseif (@$forum && !@$subject)
{
 require BBBASE."/forum.inc.php";

 $child=new VictoriqueForum($forum);
}
else
{
 $child=new VictoriqueAction(@$_GET['action']);
}

if (isset($_SERVER['PATH_INFO']) && pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) == 'xml')
{
 require BBBASE.'/feed.inc.php';

 $tpl=new VictoriqueFeed();
 switch(pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME))
 {
  case 'feed':
  case 'rss':
  $type='rss';
  break;
  case 'atom':
  default:
  $type='atom';
 }
 echo ($tpl->getFeed($child,$type));
}
else
{
 $tpl=new MomokoLITETemplate($dirroot.'/templates/main.tpl.htm');
 echo ($tpl->toHTML($child));
}
