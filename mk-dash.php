<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

class MomokoDashboard implements MomokoObject
{
 public $table;
 private $info=array();

 public function __construct($section=null)
 {
  switch ($section)
  {
   case 'user':
   default:
   $this->table=new DataBaseTable('users');
   break;
  }
  
  $this->info=$this->get($_GET['list']);
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
  return $this->info[$key]=$value;
 }

 public function get($list=null)
 {
  if ($list)
  {
   $info['title']="Dashboard: ".ucwords($list);
  }
  else
  {
   $info['title']="Dashboard";
  }
  
  switch ($list)
  {
   case 'Stats':
   default:
   break;
  }
  
  return $info;
 }
 
 public function getByAction($action,$user_data)
 {
  switch ($action)
  {
   case 'new':
   break;
   case 'edit':
   $page['title']="Edit User";
   if (!$user_data['name'])
   {
    $query=$this->table->getData("num:'".$_GET['id']."'",null,null,1);
    $user=$query->fetch(PDO::FETCH_ASSOC);
    $page['body']=<<<HTML
<form id="UserForm" action="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=edit&id={$user['num']}" method=post>
<input type=hidden name="num" value="{$user['num']}">
<ul id="FormList" class="nobullet noindent">
<li><label for="name">Name:</label> <input type=text id="name" name="name" value="{$user['name']}"</li>
<li><label for="email">E-mail:</label> <input type=email id="email" name="email" value="{$user['email']}"</li>
<li><label for="groups">Groups:</label> <textarea id="groups" name="groups">{$user['groups']}</textarea></li>
</ul>
</form>
HTML;
   }
   else
   {
    if ($this->table->updateData($user_data))
    {
     header("Location: //{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list");
    }
    else
    {
     $page['body']="<p>Could not edit user '{$user_data['name']}'</p>";
    }
   }
   break;
   case 'delete':
   break;
   case 'settings':
   $page['title']="User Settings";
   if (!$user_data['send'])
   {
    $page['body']="";
   }
   break;
   case 'list':
   default:
   $page['title']="Manage Users";
   $page['body']="<div class=\"list plug box\"><table width=100% colspacing=1 cellspacing=1>\n<tr>\n";
   $columns=array('num','name','email','groups');
   foreach ($columns as $column)
   {
    if ($column != 'num')
    {
     $page['body'].="<th id=\"{$column}\">".ucwords($column)."</th>";
    }
   }
   $page['body'].="</tr>";
   $query=$this->table->getData(null,$columns);
   $row=null;
   while ($user=$query->fetch(PDO::FETCH_ASSOC))
   {
    if ($user['num'] > 2)
    {
     $row.="<tr id=\"".$user['num']."\" style=\"cursor:pointer\" onclick=\"openAJAXModal('http://{$GLOBALS['SET']['baseuri']}/mk-dash.php?ajax=1&section=user&action=edit&id={$user['num']}','Edit User #{$user['num']}')\">\n";
     foreach ($user as $col=>$value)
     {
      if ($col != num)
      {
       $row.="<td>".$value."</td>";
      }
     }
     $row.="</tr>\n";
     $c++;
    }
   }
   $page['body'].=$row;
   unset($row);
   $page['body'].="</table>\n</div>";
   break;
  }
  if (!$_GET['ajax'])
  {
   $page['body']="<h2>{$page['title']}</h2>\n".$page['body'];
  }
  $this->info['inner_body']=$page['body'];
  $this->info['title']=$this->info['title'].": ".$page['title'];
 }
}

if (@$_GET['action'] == 'login' || @$_GET['action'] == 'logout' || @$_GET['action'] == 'register')
{
 header("Location: //".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."?action=".$_GET['action']);
}
else
{
 if ($GLOBALS['USR']->inGroup('users')) //User must be logged in!
 {
  $child=new MomokoDashboard($_GET['section']);
  if ($_GET['action'] != NULL)
  {
   $child->getByAction($_GET['action'],$_POST);
  }
 }
 else
 {
  $child=new MomokoError('Forbidden');
 }
 
 if ($_GET['ajax'] == TRUE)
 {
  echo $child->inner_body;
 }
 else
 {
  $tpl=new MomokoTemplate('/');
  echo $tpl->toHTML($child);
 }
}