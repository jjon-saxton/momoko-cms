<?php

class UserManager implements MomokoObject
{
 public $action;
 public $dbtable;
 private $info=array();

 public function __construct($action=null)
 {
  $this->dbtable=new DataBaseTable("users");
  $this->action=$action;
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  else
  {
   return null;
  }
 }

 public function __set($key,$value)
 {
  return false;
 }

 public function get()
 {
  switch ($this->action)
  {
   case 'get':
   if (@$_GET['columns'])
   {
    $cols=explode(",",$_GET['columns']);
   }
   else
   {
    $cols=null;
   }
   $query=$this->dbtable->getData("num:'".$_GET['u']."'",$cols,null,1);
   $rows=$query->fetch(PDO::FETCH_ASSOC);
   return $rows;
   break;
   case 'put':
   $user=new MomokoUser('guest');
   $settings=$user->get();
   $data=$_POST;
   $data['num']=@$_GET['u'];
   $data['password']=crypt($data['password'],$GLOBALS['CFG']->salt);
   $data['shortdateformat']=$settings->shortdateformat;
   $data['longdateformat']=$settings->longdateformat;
   $data['rowspertable']=$settings->rowspertable;
   return $this->put($data);
   break;
   case 'drop';
   $data['num']=$_GET['u'];
   return $this->drop($data);
   break;
   default:
   $table="<table width=100% id=\"users\" class=\"ui-widget ui-widget-content\">\n<tr class=\"ui-widget-header \">\n";

   $where=null;
   if (@$_GET['columns'])
   {
    $cols=explode(",",$_GET['columns']);
   }
   else
   {
    $cols=array('num','name','groups');
   }
   foreach ($cols as $col)
   {
    if ($col == 'num')
    {
     $table.="<th>#</th>";
    }
    else
    {
     $table.="<th>".ucwords($col)."</th>";
    }
   }
   $table.="<th>Actions</th>\n</tr>\n";

   $query=$this->dbtable->getData(@$_GET['query'],$cols,@$_GET['sort'],@$_GET['limit'],@$_GET['offset']);
   
   while ($row=$query->fetch(PDO::FETCH_OBJ))
   {
    $table.="<tr id=\"".$row->num."\">\n";
    foreach ($cols as $col)
    {
     $table.="<td>".$row->$col."</td>";
    }
    $table.="<td><a href=\"#edit\" title=\"Edit\" onClick=\"showEdit('".$row->num."', event)\" class=\"ui-icon ui-icon-pencil\" style=\"display: inline-block\">Edit</a> <a href=\"#remove\" title=\"Delete\" class=\"ui-icon ui-icon-trash\" onClick=\"showDelete('".$row->num."', event)\" style=\"display: inline-block\">Delete</a></td>\n</tr>";
   }
   $table.="</table>";
   $siteroot=$GLOBALS['SET']['baseuri'];

   $html=<<<HTML
<html>
<head>
<title>User Manager</title>
</head>
<body>
<script language="javascript" type="text/javascript" src="//{$siteroot}/addins/usermanager/scripts/umanager.js"></script>
<style>
label, input { display:block;  }
div#dialog-form label, div#dialog-form p, div#dialog-form input { font-size: 10pt; }
input.text { margin-bottom:12px; width:95%; padding: .4em; }
fieldset { padding:0; border:0; margin-top:25px; }
div#users-contain { margin: 20px 0; }
div#users-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
div#users-contain table td, div#users-contain table th { border: 1px solid #eee; padding: .6em 10px; text-align: left; }
button#create-user {font-size:11pt; }
.ui-widget {font-size: 10pt; font-weight: none}
.ui-dialog .ui-state-error { padding: .3em; }
.validateTips { border: 1px solid transparent; padding: 0.3em; }
 </style>
<h2>User Manager</h2>
<div id="dialog-fill" style="display:inline"></div>
<div id="users-contain" class="ui-widget">
<h3>Existing Users</h3>
{$table}
</div>
<button id="create-user">Add new user</button>
</body>
</html>
HTML;
   return $html;
  }
 }

 public function put($data)
 {
  $okay=false;
  $return=$data;
  if (!empty($data['num']))
  {
   if ($this->dbtable->updateData($data))
   {
    $okay=true;
   }
  }
  else
  {
   if ($return['num']=$this->dbtable->putData($data))
   {
    $okay=true;
   }
  }
  $return['actions']="<a href=\"#edit\" title=\"Edit\" onClick=\"showEdit('".$return['num']."', event)\" class=\"ui-icon ui-icon-pencil\" style=\"display: inline-block\">Edit</a> <a href=\"#remove\" title=\"Delete\" class=\"ui-icon ui-icon-trash\" onClick=\"showDelete('".$return['num']."', event)\" style=\"display: inline-block\">Delete</a></td>\n";
  if ($okay)
  {
   return $return;
  }
 }

 public function drop($data)
 {
  if ($this->dbtable->removeData($data))
  {
   return true;
  }
  else
  {
   return false;
  }
 }

 public function getJSON()
 {
  $this->info['inner_body']=json_encode($this->get());
 }

 public function setInfo()
 {
  if ($data=$this->get())
  {
   $info=parse_page($data);
   $varlist['finderroot']='//'.$GLOBALS['SET']['baseuri'].'/assets/scripts/elfinder';
   $varlist['connectoruri']='//'.$GLOBALS['SET']['baseuri'].$this->connector;
   $ch=new MomokoVariableHandler($varlist);
   $info['inner_body']=$ch->replace($info['inner_body']);
  }
  else
  {
   $page=new MomokoLITEError('Server_Error');
   $info['full_html']=$page->full_html;
   $info['title']=$page->title;
   $info['inner_body']=$page->inner_body;
  }
  $this->info=$info;
 }
}
