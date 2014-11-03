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
   case'content':
   $this->table=new DataBaseTable('content');
   break;
   case 'site':
   $this->table=new DataBaseTable('settings');
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
   case 'pages':
   case 'posts':
   case 'attachments':
   $cols=array('num','title','status','mime_type');
   $text="<div id=\"Content\" class=\"box\">\n<table width=100% cellspacing=1 cellpadding=1>\n<tr>\n";
   foreach ($cols as $th)
   {
    if ($th != "num")
    {
     $text.="<th>".ucwords(str_replace("_"," ",$th))."</th>";
    }
   }
   $text.="</tr>";
   $query=$this->table->getData("type:'".rtrim($list,"s")."'",$cols);
   $row_c=$query->rowCount();
   $pages=paginate($row_c);
   $prev=@$_GET['offset']-$GLOBALS['USR']->rowspertable;
   $next=@$_GET['offset']+$GLOBALS['USR']->rowspertable;
   if ($prev >= 0)
   {
    $prev=0;
   }
   if (count($pages) > 1)
   {
    $query=$this->table->getData("type:'".rtrim($list,"s")."'",$cols,NULL,$GLOBALS['USR']->rowspertable,@$_GET['offset']);
    $page_div="<div id=\"Page\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list={$list}&offset={$prev}\">Previous</a></td><td align=center>";
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"curpage\">{$page['number']}</strong>";
     }
     else
     {
      $page_div.="<a href=\"{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list={$list}&offest={$page['offset']}\">{$page['number']}</a>";
     }
    }
    $page_div.="</td><td align=right><a href=\"{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list={$list}&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
   }
   else
   {
    $page_div=NULL;
   }
   if ($row_c>0)
   {
    while($content=$query->fetch(PDO::FETCH_ASSOC))
    {
     $text.="<tr>\n";
     foreach ($content as $col=>$value)
     {
      if ($col != "num")
      {
       $text.="<td>{$value}</td>";
      }
     }
     $text.="</tr>\n";
    }
   }
   else
   {
    $text.="<tr><td colspan=5 align=center><span class=\"notice\">- You have no {$list} yet! -</td></tr>";
   }
   $info['inner_body']="<h2>".ucwords($list)."</h2>\n".$text."</table></div>".$page_div;
   break;
   case 'logs':
   $table=new DataBaseTable('log');
   $text="<div id=\"Logs\" class=\"box\">\n<table width=100% cellspacing=1 cellpadding=1>\n<tr>\n";
   foreach ($table->fieldlist as $th)
   {
    if ($th != "num")
    {
     $text.="<th>".ucwords($th)."</th>";
    }
   }
   $text.="</tr>";
   $query=$table->getData(@$_GET['q']);
   $row_c=$query->rowCount();

   if ($row_c > $GLOBALS['USR']->rowspertable)
   {
    unset($query);
    $query=$table->getData(@$_GET['q'],NULL,NULL,$GLOBALS['USR']->rowspertable,@$_GET['offset']);
   }

   $pages=paginate($row_c,@$_GET['offset']);
   $prev=@$_GET['offset']-$GLOBALS['USR']->rowspertable;
   $next=@$_GET['offset']+$GLOBALS['USR']->rowspertable;
   if ($prev < 0)
   {
    $prev=0;
   }
   if ($next > $row_c)
   {
    $next=0;
   }
   if (count($pages) > 1)
   {
    $page_div="<div id=\"Pages\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&list=logs&offset={$prev}\">Previous</a></td><td align=center";
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"currentpage\">{$page['number']}</strong> ";
     }
     else
     {
      $page_div.="<a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&list=logs&offset={$page['offset']}\">{$page['number']}</a> ";
     }
    }
    $page_div.="</td><td align=right><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&list=logs&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
   }
   else
   {
    $page_div=NULL;
   }

   while ($log=$query->fetch(PDO::FETCH_ASSOC))
   {
    $text.="<tr>\n";
    foreach ($log as $col=>$value)
    {
     if ($col != 'num')
     {
      $text.="<td id=\"{$col}\">{$value}</td>";
     }
    }
    $text.="</tr>\n";
   }
   $info['inner_body']="<h2>Event Logs</h2>\n".$text."</table>\n</div>".$page_div;
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
   $page['title']=ucwords($_GET['section'])." Settings";
   if (!$user_data['send'])
   {
    switch($section)
    {
     case 'site':
     $page['body']=""; //TODO write site settings form
     break;
     case 'user':
     $page['body']=""; //TODO write user settings form
     break;
    }
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
   $row_c=$query->rowCount();
   if ($row_c > $GLOBALS['USR']->rowspertable)
   {
    $query=$this->table->getData(null,$columns,null,$GLOBALS['USR']->rowspertable,@$_GET['offset']);
    $prev=@$_GET['offset']-$GLOBALS['USR']->rowspertable;
    if ($prev < 0)
    {
     $prev=0;
    }
    $page_div="<div id=\"UserPags\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=users&action=list&offset={$prev}\">Previous</a></td><td align=center>";
    $pages=paginate($row_c,@$_GET['offset']);
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"currentpage\">{$page['number']}</strong>";
     }
     else
     {
      $page_div.="<a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=users&action=list&offset={$page['offset']}\">{$page['number']}</a> ";
     }
    }
    $next=@$_GET['offset']+$GLOBALS['USR']->rowspertable;
    if ($next > $row_C)
    {
     $next=0;
    }
    $page_div.="</td><td align=right><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=users&action=list&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
   }
   else
   {
    $page_div=null;
   }
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
   $page['body'].="</table>\n</div>".$page_div;
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