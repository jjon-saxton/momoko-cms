<?php

class VictoriqueACP implements MomokoLITEObject
{
 public $section;
 public $cmd;
 public $stage;
 private $info=array();
 
 public function __construct($data=null)
 {
   if (is_array($data))
   {
	if (isset($data['section']))
	{
	 $this->section=$data['section'];
	}
	if (isset($data['cmd']))
	{
	 $this->cmd=$data['cmd'];
	}
	if (isset($data['stage']))
	{
	 $this->stage=$data['stage'];
	}
   }
   $this->info=$this->get();
 }
 
 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key]=$value;
  }
  else
  {
   return false;
  }
 }
 
 public function get()
 {
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   $info=array();
   switch ($this->section)
   {
    case 'forums';
    $info['title']="Manage Forums";
	if (@$_GET['cmd'] == 'reorder')
	{
	 $order=explode(",",$_POST['order']);
	 $data['order']=0;
	 $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
	 $okay=false;
	 foreach ($order as $data['num'])
	 {
	  if ($update=$tbl->updateData($data))
	  {
	   $okay=true;
	  }
	  $data['order']++;
	 }
	 if ($okay)
	 {
	  print "Updates applied successfully";
	 }
	 else
	 {
	  print "Updates could not be applied!";
	 }
	 exit();
	}
    elseif (@$_POST['name'])
    {
	 require_once BBBASE.'/forum.inc.php';
	 $forum=new VictoriqueForum($_POST['name']);
	 if (@$_POST['delete'] && !@$_POST['confirm'])
	 {
	   $info['inner_body']=<<<HTML
<h2>Really Delete this forum?</h2>
Are you sure you want to delete this forum? If you continue the forum named '{$forum->data->name}' will be removed along with all child forums. In addition all threads of this forum and its decendents will be orphaned. Do you wish to continue?
<form action="#remove" method=post>
<div id="MainOption" class="oneline"><input type=hidden name="name" value="{$forum->data->name}"> <input type=submit name="confirm" value="Yes"></div>
<div id="Cancel" class="oneline"><input type=submit name="noconfirm" value="No"></div>
</form>
HTML;
	 }
	 elseif ($_POST['confirm'] && $delf=$forum->drop())
	 {
	  header ("Location: ".BBROOT."?action=admin&section=forums");
	  exit();
	 }
	 elseif ($newf=$forum->put($_POST))
	 {
	  header ("Location: ".BBROOT."?action=admin&section=forums&forum=".urlencode($_POST['name']));
	  exit();
	 }
    }
	else
	{
	 if(@$_GET['forum'])
	 {
	  require_once BBBASE.'/forum.inc.php';
	  $forum=urldecode($_GET['forum']);
	  $forum=new VictoriqueForum($forum);
	  $html=$forum->editForm();
	  
	  $list=$forum->tbl->getData(null,'parent='.$forum->data->num,'order>ascending');
	  $html.="<h3>Children</h3>\n<div id=\"response\" style=\"display:none\"> </div>\n<ul id=\"Forum{$forum->data->name}List\" class=\"box dragcontainer\">\n";
	  while ($row=$list->next())
	  {
	   $nameurl=urlencode($row->name);
	   $html.="<li id=\"{$row->num}\" class=\"draggable box\"><a href=\"?action=admin&section=forums&forum={$nameurl}\">{$row->name}</a></li>\n";
	  }
	  $html.=<<<HTML
<form action="#new" method=post>
<li id="NewForum" class="box"><label for="name">Name:</label> <input type=text name="name" id="name"> <input type=hidden name="parent" value="{$forum->data->num}"> <input type=submit value="Add Forum"></li>
</form>
</ul>
HTML;
      $info['inner_body']=$html;
	 }
	 else
	 {
	  $list=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
	  $forum=$list->getData(null,'parent=0','order>ascending');
	  $html=<<<HTML
<h2>{$info['title']}</h2>
<div id="response" style="display:none"> </div>
<ul id="ForumList" class="box dragcontainer">
HTML;
      while ($row=$forum->next())
	  {
	   $nameurl=urlencode($row->name);
	   $html.="<li id=\"{$row->num}\" class=\"draggable box\"><a href=\"?action=admin&section=forums&forum={$nameurl}\">{$row->name}</a></li>\n";
	  }
	  $html.=<<<HTML
<form action="#new" method=post>
<li id="NewForum" class="box"><label for="name">Name:</label> <input type=text name="name" id="name"> <input type=hidden name="parent" value="0"> <input type=submit value="Add Forum"></li>
</form>
</ul>
HTML;
      $info['inner_body']=$html;
	 }
	}
    break;
	case 'members':
	if (@$_POST['name'])
	{
	 $member=new MomokoUser($_POST['name']);
	 if (@$_POST['delete'] && !@$_POST['confirm'])
	 {
	   $data=$member->get();
	   $info['inner_body']=<<<HTML
<h2>Really remove this member?</h2>
Are you sure you want to remove this member? If you continue the member named '{$data->name}' will be removed along and they will no longer have access to this bb <em>or</em> this site! Do you wish to continue?
<form action="#remove" method=post>
<div id="MainOption" class="oneline"><input type=hidden name="name" value="{$data->name}"> <input type=submit name="confirm" value="Yes"></div>
<div id="Cancel" class="oneline"><input type=submit name="noconfirm" value="No"></div>
</form>
HTML;
	 }
	 elseif ($_POST['confirm'] && $delf=$member->drop())
	 {
	  header ("Location: ".BBROOT."?action=admin&section=members");
	  exit();
	 }
	 elseif ($newf=$member->put($_POST))
	 {
	  header ("Location: ".BBROOT."?action=admin&section=members&member=".urlencode($_POST['name']));
	  exit();
	 }
	}
	elseif (@$_GET['member'])
	{
	 $member=new MomokoUser(urldecode($_GET['member']));
	 $data=$member->get();
	 $info['title']="Manage Member: ".$data->name;
	 $info['inner_body']=$member->editForm();
	}
	else
	{
	 $info['title']="Manage Members";
	 $list=new DataBaseTable(DAL_TABLE_PRE.'users',DAL_DB_DEFAULT);
	 $members=$list->getData(array('num','name'));
	 $info['inner_body']=<<<HTML
<h2>{$info['title']}</h2>
<ul id="MemberList" class="nobullet">
HTML;
     while ($user=$members->next())
	 {
	  if ($user->name != 'root' && $user->name != 'guest')
	  {
	   $info['inner_body'].="<li id=\"".$user->num."\"><a href=\"".BBROOT."?action=admin&section=members&member=".urlencode($user->name)."\">".$user->name."</a></li>\n";
	  }
	 }
	 $info['inner_body'].=<<<HTML
<form action="#edit" method="post">
<li id="New"><label for="name">Name:</label> <input type=text id="name" name="name"> <label for="pass">Password:</label> <input type=text id="pass" name="Password"> <input type=submit name="send" value="Add User"></li>
</form>
</ul>
HTML;
	}
	break;
	//TODO: Add new cases for other section
    default:
    $root=BBROOT;
    $info['title']="Victorique Admin Control Panel";
    $info['inner_body']=<<<HTML
<h2>{$info['title']}</h2>
<div id="ACP" class="box"><h3>Dashboard</h3>
<div id="Tile1" class="box tile"><a href="{$root}/?action=admin&section=forums"><i class="acp a1"></i><span class="text tile">Manage Forums</span>
<div class="subtext tile">Add, remove, and edit your forums and subforums.</div></a>
</div>
<div id="Tile2" class="box tile"><a href="{$root}/?action=admin&section=members"><i class="acp a2"></i><span class="text tile">Manage Members</spane>
<div class="subtext tile">Add and managed members of these forums. This may also affect general site users.</div></a>
</div>
<div id="Tile3" class="box tile"><a href="{$root}/?action=admin&section=apperance"><i class="acp a3"></i><span class="text tile">Change Apperance and Templates</span>
<div class="subtext tile">Change various templates and css used by these forums.</div></a>
</div>
<div id="Title4" class="box tile"><a href="{$root}/?action=admin&section=settings"><i class="acp a4"></i><span class="text tile">Settings</span>
<div class="subtext tile">Change board settings including default user settings</div></a>
</div>
</div>
HTML;
   }
  }
  else
  {
   $err=new MomokoLITEError('Forbidden');
   $info['title']=$err->title;
   $info['inner_body']=$err->inner_body;
  }
  return $info;
 }
}
   
