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
   break;
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
   $text="<div id=\"Content\" class=\"box\">\n<table width=100% class=\"dashboard row-select\">\n<tr>\n";
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
   $text="<div id=\"Logs\" class=\"box\">\n<table width=100% class=\"dashboard\">\n<tr>\n";
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
   break;
   case 'addins':
   default:
   $form=new MomokoAddinForm('list');
   $info['inner_body']=$form->inner_body;
   break;
  }
  
  return $info;
 }
 
 public function getByAction($action,$user_data)
 {
  switch ($action)
  {
   case 'new':
   $page['title']="New ".ucwords($_GET['section']);
   switch ($_GET['section'])
   {
    case 'user':
    default:
    if (!$user_data['send'])
    {
     $query=$this->table->getData("name:'guest'",NULL,NULL,1);
     $default=$query->fetch(PDO::FETCH_ASSOC);
     $page['body']=<<<HTML
<form method=post>
<h3>Basic Information</h3>
<ul id="NewUserForm" class="noindent nobullet">
<li><label for="name">Name: </label><input type=text id="name" name="name"></li>
<li><label for="email">E-Mail: </label><input type=email id="email" name="email"></li>
</ul>
<h3>Password</h3>
<ul id="PasswordForm" class="noindent nobullet">
<li><label for="pass1">Password: </label><input type=password id="pass1" name="password"></li>
<li><label for="pass2">Confirm Password: </lable><input type=password id="pass2" name="password2"></li>
</ul>
<h3>Setting</h3>
<ul id="UserSettings" class="noindent nobullet">
<li><label for="groups">Groups: </label><input type=text id="groups" name="groups" value="users"></li>
<li><label for="sdf">Short Date Format: </label><input type=text id="sdf" name="shortdateform" value="{$default['shortdateformat']}"></li>
<li><label for="ldf">Long Date Format: </label><input type=text id="ldf" name="longdateform" value="{$default['longdateformat']}"></li>
<li><label for="rpt">Rows Per Table: </label><input type=number id="rpt" name="rowspertable" value="{$default['rowspertable']}"></li>
</ul>
<h3>Next</h3>
<div class="box" align=center><button type=submit name="send" value="1">Register User</div>
</form>
HTML;
    }
    else
    {
     try
     {
      $update=$this->table->putData($user_data);
     }
     catch (Exception $err)
     {
      trigger_error("Caught exception '".$err->getMessage()."' while attempting to add a new user via dashboard",E_USER_WARNING);
     }
     if ($update)
     {
      $page['body']=<<<HTML
<div id="NewUserAdded" class="message box">
<h3 class="message title">New User Added</h3>
<p>A new user was added to MomoKO. You may <a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=new">return</a> to add another user, or continue on to other actions</p>
</div>
HTML;
     }
    }
   }
   $info['inner_body']="<h2>{$page['title']}</h2>".$page['body'];
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
    switch($_GET['section'])
    {
     case 'site':
     $page['body']=<<<HTML
<form method=post>
<h3>All Settings</h3>
<ul id="SettingsForm" class="noindent nobullet">
HTML;
     $query=$this->table->getData();
     $settings=$query->fetchALL(PDO::FETCH_ASSOC);
     foreach ($settings as $setting)
     {
      $title=str_replace("_"," ",$setting['key']);
      switch ($setting['key'])
      {
       case 'sessionname':
       $title="session name";
       break;
       case 'rewrite':
       $title="human readable URLs";
       break;
      }
      $title=ucwords($title);
      $page['body'].="<li><label for=\"{$setting['key']}\">{$title}: </label>";
      switch ($setting['key'])
      {
       case 'version':
       $page['body'].="<span id=\"{$setting['key']}\">{$setting['value']}</span>";
       break;
       //TODO add special case for template
       case 'security_logging':
       case 'error_logging':
       $page['body'].="<input type=number id=\"{$setting['key']}\" name=\"{$settings['key']}\" value=\"{$setting['value']}\">";
       break;
       //TODO add special cases for e-mail settings
       case 'rewrite':
       if ($setting['key'])
       {
        $page['body'].="<span id=\"{$setting['key']}\"><input type=radio id=\"{$setting['key']}1\" name=\"{$setting['key']}\" value=\"1\"> <label for=\"{$setting['key']}1\">Yes</label> <input type=radio id=\"{$setting['key']}0\" name=\"{$setting['key']}\" checked=checked value=\"\"> <label for=\"{$setting['key']}0\">No</label></span>";
       }
       else
       {
       $page['body'].="<span id=\"{$setting['key']}\"><input type=radio id=\"{$setting['key']}1\" name=\"{$setting['key']}\" checked=checked value=\"1\"> <label for=\"{$setting['key']}1\">Yes</label> <input type=radio id=\"{$setting['key']}0\" name=\"{$setting['key']}\" value=\"\"> <label for=\"{$setting['key']}0\">No</label></span>";
       }
       break;
       default:
       $page['body'].="<input type=text id=\"{$setting['key']}\" name=\"{$settings['key']}\" value=\"{$setting['value']}\"></li>\n";
       break;
      }
      $page['body'].="</li>\n";
     }
     $page['body'].="</ul>\n<h3>Save</h3>\n<div class=\"box\" align=\"center\"><button type=submit name=\"send\" value=\"1\">Save Changes</button>\n</div>\n</form>";
     break;
     case 'user':
     $page['body']=<<<HTML
<form method=post>
<h3>Password</h3>
<ul id="PassForm" class="noindent nobullet">
<li><input type=checkbox id="pc" name="pass_change" value=1> <label for="pc">Do you wish to change your password?</label></li>
<li><label for="cpass">Current Password: </label><input type=password id="cpass" name="oldpassword"></li>
<li><label for="npass1">New Password: </label><input type=password id="npass1" name="newpassword1"></li>
<li><label for="npass2">Confirm New Password: </label><input type=password id="npass2" name="newpassword2"></li>
</ul>
<h3>Settings</h3>
<ul id="UserForm" class="noindent nobullet">
<input type=hidden name="num" value="{$GLOBALS['USR']->num}">
HTML;
     $columns=array('name','email','shortdateformat','longdateformat','rowspertable');
     $query=$this->table->getData("num:'".$GLOBALS['USR']->num."'",$columns,null,1);
     $user=$query->fetch(PDO::FETCH_ASSOC);
     foreach ($columns as $form_field)
     {
      $type="text";
      switch ($form_field)
      {
       case 'email':
       $title="E-Mail";
       break;
       case 'shortdateformat':
       $title="Short Date Format";
       break;
       case 'longdateformat':
       $title="Long Date Format";
       break;
       case 'rowspertable':
       $title="# of Rows in a Given Table";
       $type="number";
       break;
       default:
       $title=ucwords($form_field);
       break;
      }
      $page['body'].="<li><label for=\"{$form_field}\">{$title}: </label><input type=\"{$type}\" id=\"{$form_field}\" name=\"{$form_field}\" value=\"{$user[$form_field]}\"></li>\n";
     }
     $page['body'].="</ul>\n<h3>Save</h3><div class=\"box\" align=center><button type=submit name=\"send\" value=\"1\">Save Changes</button></div>\n</form>";
     break;
    }
   }
   else
   {
    switch ($_GET['section'])
    {
     case 'site':
     $update=false;
     foreach($user_data as $nkey=>$nval)
     {
      $ndata=array('key'=>$nkey,'value'=>$nval);
      try
      {
       $update=$this->table->updateData($ndata);
      }
      catch (Exception $e)
      {
       trigger_error("Caught exception '".$e->getMessage()."' while attempting to change site settings",E_USER_WARNING);
      }
     }
     break;
     case 'user':
     default:
     try
     {
      $update=$this->table->updateData($user_data);
     }
     catch (Exception $e)
     {
      trigger_error("Caught exception '".$e->getMessage()."' while attempting to update settings for user #{$user_data['num']}",E_USER_ERROR);
     }
     break;
    }
    $section=ucwords($_GET['section']);
    if ($update)
    {
     $page['body']=<<<HTML
<div id="SettingsChanged" class="message box">
<h3 class="message title">{$section} Settings Changed</h3>
<p>{$section} settings have been changed succesfully! Please feel free to <a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section={$_GET['section']}&action=settings">Return</a> to the previous page, or select another page or action.</p>
</div>
HTML;
    }
    else
    {
     $page['body']=<<<HTML
<div id="SettingsChanged" class="message error box">
<h3 class="message error title">{$section} Settings Not Changed!</h3>
<p>{$section} settings could not be changed. If you are an administrator, please review the event logs for more information. Other users, should report this error to the sie administrator</p>
</div>
HTML;
    }
   }
   break;
   case 'map':
   $page['title']="Site Map";
   break;
   case 'list':
   default:
   $page['title']="Manage Users";
   $page['body']="<div class=\"list plug box\"><table width=100% class=\"dashboard row-select\">\n<tr>\n";
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
    $page_div="<div id=\"UserPags\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list&offset={$prev}\">Previous</a></td><td align=center>";
    $pages=paginate($row_c,@$_GET['offset']);
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"currentpage\">{$page['number']}</strong>";
     }
     else
     {
      $page_div.="<a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list&offset={$page['offset']}\">{$page['number']}</a> ";
     }
    }
    $next=@$_GET['offset']+$GLOBALS['USR']->rowspertable;
    if ($next > $row_C)
    {
     $next=0;
    }
    $page_div.="</td><td align=right><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
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