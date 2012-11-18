<?php

class VictoriqueMemberCP implements MomokoModuleInterface
{
 private $options=array();
 private $user;

 public function __construct($user,$options)
 {
  $this->user=$user;
  parse_str($options,$this->options);
 }

 public function getModule($format='html')
 {
  $cp=$this->getCPArray();
  switch ($format)
  {
   case 'html':
   default:
   switch ($this->options['display'])
   {
    case 'list':
    $start="<ul id=\"VictoriqueCPList\" class=\"victorique cp list\">\n<li><strong>Welcome {$this->user->name}</strong></li>";
    $item_tpl="<li><a href=\"~{itemlink}\">~{itemname}</a></li>";
    $end="</ul>";
    break;
    case 'line':
    $start="<span id=\"VictoriqueCPInline\" class=\"victorique cp\"><strong>Weclome {$this->user->name}</strong>";
    $item_tpl=" | <a href=\"~{itemlink}\">~{itemname}</a>";
    $end="</span>";
    break;
   }
   $items=null;
   foreach ($cp as $item)
   {
    $ch=new MomokoCommentHandler($item);
    $items.=$ch->replace($item_tpl);
   }
   return $start."\n".$items."\n".$end;
  }
 }

 private function getCPArray()
 {
  $array=array();
  if ($this->user->inGroup('nobody'))
  {
   $array[]=array('itemlink'=>BBROOT.'/?action=login','itemname'=>'Login');
   $array[]=array('itemlink'=>BBROOT.'/?action=register','itemname'=>"Register");
  }
  else
  {
   $array[]=array('itemlink'=>BBROOT.'/?action=viewprofile&u='.$this->user->num,'itemname'=>'View Profile');
   $array[]=array('itemlink'=>BBROOT.'/?action=notifications','itemname'=>'Notifications ('.$this->getNotificationCount().')');
   $array[]=array('itemlink'=>BBROOT.'/?action=pm','itemname'=>'Private Messages ('.$this->getPrivateMsgCount().')');
   if ($this->user->inGroup('admin'))
   {
    $array[]=array('itemlink'=>BBROOT.'/?action=admin','itemname'=>'AdminCP');
   }
   if ($this->user->inGroup('moderator') || $this->user->inGroup('admin'))
   {
    $array[]=array('itemlink'=>BBROOT.'/?action=mod','itemname'=>'ModCP');
   }
   $array[]=array('itemlink'=>BBROOT.'/?action=settings','itemname'=>'Your Settings');
   $array[]=array('itemlink'=>BBROOT.'/?action=logout','itemname'=>'Logout');
  }

  return $array;
 }

 private function getNotificationCount()
 {
  $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_notifications',DAL_DB_DEFAULT);
  $data=$tbl->getData('num','to='.$this->user->num);
  $c=0;
  while ($check=$data->next())
  {
   $c++;
  }
  return $c;
 }

 private function getPrivateMsgCount()
 {
  $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_messages',DAL_DB_DEFAULT);
  $data=$tbl->getData('num',array('to='.$this->user->num,'status=u'));
  $c=0;
  while ($check=$data->next())
  {
   $c++;
  }
  return $c;
 }
}

class VictoriqueModeratorCP implements MomokoModuleInterface
{
 private $options=array();
 public $user;
 
 public function __construct($user,$options)
 {
  $this->user=$user;
  parse_str($options,$this->options);
 }
 
 public function getModule($format='html')
 {
  if ($format == 'html')
  {
   if ($this->options['section'] == 'head')
   {
	return "<div class=\"modcontrols\" style=\"text-align:right\">".$this->actionList().$this->actionButtons()."</div>";
   }
   elseif ($this->options['section'] == 'foot')
   {
	return "<div class=\"modcontrols\">\n<div class=\"modactions\" style=\"float:left;width:60%\">".$this->actionList()."</div><div class=\"modbuttons\" style=\"float:right;text-align:right\">".$this->actionButtons()."</div>\n</div>";
   }
  }
 }
 
 public function actionList()
 {
  if ($this->options['display'] == 'all' && ($this->user->inGroup('moderators') || $this->user->inGroup('admin')))
  {
   //TODO: add html for action dropdown for moderators!
  }
  else
  {
   return null;
  }
 }
 
 public function actionButtons()
 {
  if (array_key_exists('topic',$this->options) && array_key_exists('forum',$this->options))
  {
   $attr['nr']=" disabled=disabled";
   $attr['nt']=$attr['nr'];
   $html=null;
   
   if ($this->checkPermissions('topic','w'))
   {
	$attr['nr']=null;
   }
   if ($this->checkPermissions('forum','w'))
   {
	$attr['nt']=null;
   }
   
   return "<button onclick=\"window.location='?action=post&what=topic'\"{$attr['nt']}>New Topic</button> <button onclick=\"window.location='?action=post&what=reply'\"{$attr['nr']}>Add Reply</button>";
  }
  elseif (array_key_exists('forum',$this->options))
  {
   $attr=" disabled=disabled";
   if ($this->checkPermissions('forum','w'))
   {
	$attr=null;
   }
   return "<button onclick=\"window.location='?action=post&what=topic'\"{$attr}>New Topic</button>";
  }
 }
 
 private function checkPermissions($what,$p)
 {
  if ($what == 'forum')
  {
   $ftbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
   $forum=$ftbl->getData(array('permissions','groups'),'name~'.$this->options['forum'],null,1);
   $forum=$forum->first();
   list($admin,$groups,$other)=explode(":",$forum->permissions);
   $okay=(preg_match("/{$p}/",$other) > 0);
   if ($this->user->inGroup('admin') && preg_match("/{$p}/",$admin) >0)
   {
	$okay=true;
   }
   if (preg_match("/{$p}/",$groups) > 0)
   {
    $glist=explode(",",$forum->groups);
    foreach ($glist as $group)
    {
     if ($this->user->inGroup($group))
     {
      $okay=true;
     }
    }
   }
   return $okay;
  }
  elseif ($what == 'topic')
  {
   $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_threads',DAL_DB_DEFAULT);
   $topic=$tbl->getData('closed','subject~'.$this->options['topic'],null,1);
   $topic=$topic->first();
   $okay=false;
   if ($this->checkPermissions('forum','w') && $topic->closed == 'n')
   {
	$okay=true;
   }
   
   return $okay;
  }
 }
}