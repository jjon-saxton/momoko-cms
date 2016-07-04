<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

class MomokoDashboard implements MomokoObject
{
 public $table;
 private $user;
 private $config;
 private $info=array();

 public function __construct($section=null,MomokoSession $user, MomokoSiteConfig $config)
 {
  $this->user=$user;
  $this->config=$config;

  switch ($section)
  {
   case 'addin':
   case 'switchboard':
   $this->table=new DataBaseTable('addins');
   break;
   case 'content':
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
   $where="type:`".rtrim($list,"s")."`";
   $cols=null; //TODO remove this from all queries
   $text="<div id=\"ContentList\" class=\"box\">\n";
   $query=$this->table->getData($where,$cols);
   $row_c=$query->rowCount();
   $pages=paginate($row_c,$this->user);
   $prev=@$_GET['offset']-$this->user->rowspertable;
   $next=@$_GET['offset']+$this->user->rowspertable;
   if ($prev >= 0)
   {
    $prev=0;
   }
   if (count($pages) > 1)
   {
    $query=$this->table->getData("type:'".rtrim($list,"s")."'",$cols,NULL,$GLOBALS['USR']->rowspertable,@$_GET['offset']);
    $page_div="<div id=\"pages\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"{$this->config->siteroot}/mk-dash.php?section=content&list={$list}&offset={$prev}\">Previous</a></td><td align=center>";
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"curpage\">{$page['number']}</strong>";
     }
     else
     {
      $page_div.="<a href=\"{$this->config->siteroot}/mk-dash.php?section=content&list={$list}&offest={$page['offset']}\">{$page['number']}</a>";
     }
    }
    $page_div.="</td><td align=right><a href=\"{$this->config->siteroot}/mk-dash.php?section=content&list={$list}&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
   }
   else
   {
    $page_div=NULL;
   }
   if ($row_c>0)
   {
    $summary_len=300;
    while($content=$query->fetch(PDO::FETCH_ASSOC))
    {
     $content['date_created']=date($this->user->shortdateformat,strtotime($content['date_created']));
     if ($content['date_modified'])
     {
      $content['date_modified']=date($this->user->shortdateformat,strtotime($content['date_modified']));
     }
     else
     {
      $content['date_modified']=date($this->user->shortdateformat,strtotime($content['date_created']));
     }
     $content['author']=get_author($content['author']); //Fetches the author object
     $content['author']=ucwords($content['author']->name); //Narrows down to just the name
     $content['text']=preg_replace("/<h2>(.*?)<\/h2>/smU",'',$content['text']); //get rid of page title, it will be styleized and placed elsewhere.
     if (strlen($content['text']) > $summary_len)
     {
      preg_match("/^(.{1,".$summary_len."})[\s]/i", $content['text'], $matches);
      $content['text']=$matches[0].'...';
     }
     
     if ($GLOBALS['SET']['rewrite'])
     {
      $content['link']=$content['type']."/".urlencode($content['title'].".htm?");
     }
     else
     {
      $content['link']="?p={$content['num']}&";
     }
     
     $text.=<<<HTML
<div id="{$content['num']}" class="page box {$content['status']}"><h4 style="display:inline-block;clear:left" class="module">{$content['title']}</h4>
<div class="actions "style="float:right"><a href="{$this->config->siteroot}/{$content['link']}action=view" class="glyphicon glyphicon-folder-open" title="Open/Download"></a> <a href="{$this->config->siteroot}/{$content['link']}action=edit" class="glyphicon glyphicon-edit" title="Edit"></a> <a href="{$this->config->siteroot}/{$content['link']}action=delete" class="glyphicon glyphicon-remove" title="Delete"></a></div>
<div class="properties">{$content['date_created']}, {$content['date_modified']}, {$content['author']}, {$content['mime_type']}</div>
<div class="summary">{$content['text']}</div>
</div>
HTML;
    }
   }
   else
   {
    $text.="<div id=\"NoContent\" class=\"page box empty\"><span class=\"empty icon\"></span>- You have no {$list} yet! -</div>";
   }
   $info['inner_body']="<h2>".ucwords($list)."</h2>\n".$text."</table></div>".$page_div;
   break;
   case 'logs':
   $table=new DataBaseTable('log');
   //set options for filters
   $filters['type']=array(array('value'=>'cerror','name'=>"Critical Errors"),array('value'=>"security",'name'=>"Security Messages"),array('value'=>"warning",'name'=>"Warnings"),array('value'=>"notice",'name'=>"Notices"));
   $filters['timeframe']=array(array('value'=>date("Y-m-d H:i",strtotime('-1 day')),'name'=>'Past Day'),array('value'=>date("Y-m-d H:i",strtotime('-1 week')),'name'=>"Past Week"),array('value'=>date("Y-m-d H:i",strtotime('-1 month')),'name'=>"Past Month"),array('value'=>date("Y-m-d H:i",strtotime('-1 year')),'name'=>"Past Year"));
   $type_opts="<option value=\"*\">- Any -</option>";
   foreach ($filters['type'] as $type)
   {
    if (!empty($_GET['filter']) && $_GET['filter']['type'] == $type['value'])
    {
     $type_opts.="<option selected value=\"{$type['value']}\">{$type['name']}</option>\n";
    }
    else
    {
     $type_opts.="<option value=\"{$type['value']}\">{$type['name']}</option>\n";
    }
   }
   $time_opts="<option value=\"*\">- Since Creation -</option>";
   foreach ($filters['timeframe'] as $option)
   {
    if (!empty($_GET['filter']) && $_GET['filter']['time'] == $option['value'])
    {
     $time_opts.="<option selected value=\"{$option['value']}\">{$option['name']}</option>\n";
    }
    else
    {
     $time_opts.="<option value=\"{$option['value']}\">{$option['name']}</option>\n";
    }
   }
   
   $text=<<<HTML
   <div id="Logs" class="box">
   <form role="form" class="form-inline" id="Filter">
   <input type=hidden name="action" value="{$_GET['action']}">
   <input type=hidden name="list" value="{$_GET['list']}">
   <table width=100% id="Filters">
   <tr valign=middle><strong>Filters:</strong>
   <div class="form-group">
    <label for="type">Type</label>
    <select class="form-control" id="type" name="filter[type]">{$type_opts}</select>
   </div>
   <div class="form-group">
    <label for="time">Timeframe:</label>
    <select class="form-control" id="time" name="filter[time]">{$time_opts}</select>
   </div>
   <button class="btn btn-default" type=submit>Apply</button></td></tr>
   </form>
   <table class="table table-striped" width=100% class="dashboard">
   <thead>
   <tr>
HTML;

   foreach ($table->fieldlist as $th)
   {
    if ($th != "num")
    {
     $text.="<th>".ucwords($th)."</th>";
    }
   }
   $text.="</tr>\n</thead>\n<tbody>";
   
   $where=null;
   if (is_array(@$_GET['filter']))
   {
    foreach($_GET['filter'] as $col=>$value)
    {
     if ($col == 'time' && $value != "*")
     {
      $value="> ".$value;
     }
     
     if ($value != "*")
     {
      $where.=$col.":'".$value."', ";
     }
    }
   }
   $where=rtrim($where,", ");
   
   $query=$table->getData($where);
   $row_c=$query->rowCount();

   if ($row_c > $this->user->rowspertable)
   {
    unset($query);
    $query=$table->getData($where,NULL,NULL,$this->user->rowspertable,@$_GET['offset']);
   }

   $query_str=http_build_query($_GET);
   $pages=paginate($row_c,$this->user,@$_GET['offset']);
   $prev=@$_GET['offset']-$this->user->rowspertable;
   $next=@$_GET['offset']+$this->user->rowspertable;
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
    $page_div="<div id=\"Pages\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"{$this->config->siteroot}/mk-dash.php?{$query_str}&offset={$prev}\">Previous</a></td><td align=center>";
    if (count($pages) >= 10)
    {
     $page_div.="<select id=\"PageList\" onchange=\"window.location='{$this->config->siteroot}/mk-dash.php?{$query_str}&offset='+$(this).val()\" name=\"page_dropdown\">\n";
     foreach ($pages as $page)
     {
      if ($page['offset'] == @$_GET['offset'])
      {
       $page_div.="<option selected=selected value=\"{$page['offset']}\">Page {$page['number']}</option>\n";
      }
      else
      {
       $page_div.="<option value=\"{$page['offset']}\">Page {$page['number']}</option>\n";
      }
     }
     $page_div.="</select>";
    }
    else
    {
     foreach ($pages as $page)
     {
      if ($page['offset'] == @$_GET['offset'])
      {
       $page_div.="<strong class=\"currentpage\">{$page['number']}</strong> ";
      }
      else
      {
       $page_div.="<a href=\"{$this->config->siteroot}/mk-dash.php?{$query_str}&offset={$page['offset']}\">{$page['number']}</a> ";
      }
     }
    }
    $page_div.="</td><td align=right><a href=\"{$this->config->siteroot}/mk-dash.php?{$query_str}&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
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
     if ($col == 'time')
     {
      $unixt=strtotime($value);
      $time=date($this->user->shortdateformat." ".$this->user->timeformat,$unixt);
      $text.="<td id=\"{$col}\">{$time}</td>";
     }
     elseif ($col != 'num')
     {
      $text.="<td id=\"{$col}\">{$value}</td>";
     }
    }
    $text.="</tr>\n";
   }
   $info['inner_body']="<h2>Event Logs</h2>\n".$text."</tbody></table>\n</div>".$page_div;
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
    case 'addin':
    if ($user_data['archive'])
    {
     $destination=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir'].'addins/'.$user_data['dir'];
     $zip=new ZipArchive();
     if ($zip->open($GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'].$user_data['archive']))
     {
      $find=$this->table->getData("dir:'{$user_data['dir']}'");
      if ($find->rowCount > 0)
      {
       $add=$this->table->updateData($user_data);
      }
      else
      {
       $add=$this->table->putData($user_data);
      }
      
      if ($add && mkdir($destination))
      {
       if ($zip->extractTo($destination))
       {
        unlink($GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'].$user_data['archive']);
        $status['num']=$add;
        $query=$this->table->getData("num:`= {$add}`",array('shortname','longname','type'));
        $status['info']=$query->fetch(PDO::FETCH_ASSOC);
        $status['code']=200;
       }
       else
       {
        $status['code']=501;
        $status['msg']="Could not extract archive, please try to extract it manually to {$destination}!";
       }
      }
      else
      {
       $status['code']=501;
       $status['msg']="There was a problem adding the addin to {$destination}! Please ensure it was added to the database and that MomoKO has permission to create folders  in the addin directory.";
      }
     }
     else
     {
      $status['code']=501;
      $status['msg']="Could not open {$user_data['archive']}! please ensure it has not been deleted and that MomoKO has permissions to read it.";
     }
     
     if ($status['code'] == 501)
     {
      trigger_error($status['msg'],E_USER_WARNING);
     }
     
     $page['body']=json_encode($status);
    }
    else
    {
     $form=new MomokoAddinForm('add');
     $page['body']=$form->inner_body;
    }
    break;
    case 'user':
    default:
    if (!$user_data['send'])
    {
     $query=$this->table->getData("name:'guest'",NULL,NULL,1);
     $default=$query->fetch(PDO::FETCH_ASSOC);
     $group_opts=null;
     $group_num=1;
     foreach ($this->config->sys_groups as $name)
     {
        if ($name != 'cli' && $name != 'nobody')
        {
            if ($name != 'users')
            {
                $group_opts.="<option>{$name}</option>\n";
            }
            else
            {
                $group_opts.="<option selected=\"selected\">{$name}</option>\n";
            }
            $group_num++;
        }
     }
     $page['body']=<<<HTML
<form role="form" method=post>
<h3>Basic Information</h3>
<div class="form-group">
 <label for="name">Name:</label>
 <input class="form-control" type=text id="name" name="name">
</div>
<div class="form-group">
 <label for="email">E-Mail:</label>
 <input class="form-control" type=email id="email" name="email">
</div>
<h3>Password</h3>
<div class="form-group">
 <label for="pass1">Password:</label>
 <input class="form-control" type=password id="pass1" name="password">
</div>
<div class="form-group">
 <label for="pass2">Confirm Password:</label>
 <input class="form-control" type=password id="pass2" name="password2">
</div>
<h3>Setting</h3>
<div class="form-group">
 <label for="groups">Groups:</label>
 <select class="form-control" onchange="$('input#group_store').val(($(this).val()))" size="{$group_num}" multiple id="groups">
{$group_opts}</select>
 <input type="hidden" id="group_store" name="groups" value="users">
</div>
<div class="form-group">
 <label for="sdf">Short Date Format:</label>
 <input class="form-control" type=text id="sdf" name="shortdateformat" value="{$default['shortdateformat']}">
</div>
<div class="form-group">
 <label for="ldf">Long Date Format:</label>
 <input class="form-control" type=text id="ldf" name="longdateformat" value="{$default['longdateformat']}">
</div>
<div class="form-group">
 <label for="rpt">Rows Per Table:</label>
 <input class="form-control" type=number id="rpt" name="rowspertable" value="{$default['rowspertable']}">
</div>
<h3>Next</h3>
<div class="box" align=center><button class="btn btn-primary" type=submit name="send" value="1">Register User</div>
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
<p>A new user was added to MomoKO. You may <a href="{$GLOBALS['SET']['siteroot']}/mk-dash.php?section=user&action=new">return</a> to add another user, or continue on to other actions</p>
</div>
HTML;
     }
    }
   }
   $info['inner_body']="<h2>{$page['title']}</h2>".$page['body'];
   break;
   case 'edit':
   switch ($_GET['section'])
   {
    case 'user':
    default:
    $page['title']="Edit User";
    if (!$user_data['name'])
    {
     $query=$this->table->getData("num:'".$_GET['id']."'",null,null,1);
     $user=$query->fetch(PDO::FETCH_ASSOC);
     $group_arry=explode(",",$user['groups']);
     $group_opts=null;
     $group_num=1;
     foreach ($this->config->sys_groups as $name)
     {
        if ($name != 'cli' && $name != 'nobody')
        {
            if (array_search($name,$group_arry) === FALSE)
            {
                $group_opts.="<option>{$name}</option>\n";
            }
            else
            {
                $group_opts.="<option selected=\"selected\">{$name}</option>\n";
            }
            $group_num++;
        }
     }
     $page['body']=<<<HTML
<form role="form" id="UserForm" action="{$GLOBALS['SET']['sec_protocol']}{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=edit&id={$user['num']}" method=post>
<input type=hidden name="num" value="{$user['num']}">
<ul id="FormList" class="nobullet noindent">
<div class="form-group">
 <label for="name">Name:</label>
 <input class="form-control" type=text id="name" name="name" value="{$user['name']}">
</div>
<div class="form-group">
 <label for="email">E-mail:</label>
 <input class="form-control" type=email id="email" name="email" value="{$user['email']}">
</div>
<div class="form-group">
 <label for="groups">Groups:</label>
 <select class="form-control" onchange="$('input#group_store').val(($(this).val()))"size="{$group_num}" multiple id="groups">
{$group_opts}</select>
 <input type="hidden" name="groups" id="group_store" value="{$user['groups']}">
</div>
<hr>
<div align="center">
 <button class="btn btn-primary" type="submit">Apply Changes</button>
</div>
</form>
HTML;
    }
    else
    {
     if ($this->table->updateData($user_data))
     {
      header("Location: {$GLOBALS['SET']['sec_protocol']}{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list");
     }
     else
     {
      $page['body']="<p>Could not edit user '{$user_data['name']}'</p>";
     }
    }
    break;
   }
   break;
   case 'delete':
   switch ($_GET['section'])
   {
    case 'addin':
    $page['title']="Remove Addin";
    if ($_REQUEST['confirm'] == "Yes")
    {
     $q=$this->table->getData("num:'= {$_GET['num']}'");
     $select=$q->fetch(PDO::FETCH_ASSOC);
     if ($delete=$this->table->deleteData($select))
     {
      if (rmdirr($GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir'].'addins/'.$select['dir']))
      {
       $status['code']=200;
       $status['num']=$select['num'];
      }
      else
      {
       $status['code']=501;
       $status['msg']="Removed addin from database, but its folder could not be removed, please delete it manually!";
      }
     }
     else
     {
      $status['code']=501;
      $status['msg']="Could not delete addin from database!";
     }
     
     if ($status['code'] == 501)
     {
      trigger_error($status['msg'],E_USER_WARNING);
     }
     
     $page['body']=json_encode($status);
    }
    else
    {
     $form=new MomokoAddinForm('remove');
     $page['body']=$form->inner_body;
    }
    break;
    case 'user':
    if ($user_data['confirm'] == 'y')
    {
     if ($this->table->deleteData($user_data))
     {
      header("Location: {$GLOBALS['SET']['sec_protocol']}{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list");
     }
     else
     {
      $page['body']="<h2>Remove User</h2>\n<p class=\"error\">Could not remove user '{$user_data['name']}'</p>";
     }
    }
    else
    {
     $page['body']=<<<HTML
<h2>Remove User?</h2>
<form id="UserForm" action="{$GLOBALS['SET']['sec_protocol']}{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=delete&id={$_GET['id']}" method=post>
<input type=hidden name="num" value="{$_GET['id']}">
<p>Do you really want to remove this user? Keep in mind that this will prevent the user from accessing this site in the future and that this action cannot be undone without the user re-registering!</p>
<p><input type="checkbox" id="cbc" name="confirm" value="y"><label for="cbc">I have read and understand the above warning.</label></p>
<hr>
<div class="box" align="center">
<div style="float:left;text-align:center;width:50%">
 <button class="btn btn-success" type="submit">Yes</buton>
</div>
<div style="float:left;text-align:center;width:50%">
 <button class="btn btn-danger" data-dismiss="modal">No</buton>
</div>
&nbsp;
</div>
</form>
HTML;
    }
    default:
    break;
   }
   break;
   case 'settings':
   $page['title']=ucwords($_GET['section'])." Settings";
   if (!$user_data['send'])
   {
    switch($_GET['section'])
    {
     case 'site':
     $page['body']=<<<HTML
<form role="form" method=post>
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
       case 'use_ssl':
       $title="use SSL";
       break;
       case 'rewrite':
       $title="human readable URLs";
       break;
       case 'email_mta':
       $title="email transport authority";
       break;
      }
      $title=ucwords($title);
      $page['body'].="<div class=\"form-group\"><label for=\"{$setting['key']}\">{$title}:</label>";
      switch ($setting['key'])
      {
       case 'version':
       $page['body'].="<input type=\"text\" disabled=\"disabled\" class=\"form-control\" id=\"{$setting['key']}\" value=\"{$setting['value']}\">";

       $raw=ftp_get_contents("ftp://ftp.momokocms.org/core/momokoversions.lst","anonymous@momokocms.org");
       $raw=explode("\n",$raw);
       $list=array();
       foreach ($raw as $row)
       {
        list($r['num'],$r['level'])=explode(",",$row);
        $list[$r['level']][]=$r['num'];
        if ($r['num'] == $setting['value'])
        {
         $path=$r['level'];
        }
       }
       $update=false;
       foreach ($list[$path] as $v)
       {
        if ($v > $setting['value'])
        {
         $update=$v;
        }
       }

       if ($update)
       {
        $page['body'].="\n<div class=\"alert alert-warning\">MomoKO {$update} is out now! <a href=\"https://github.com/jjon-saxton/momoko-cms/wiki/{$update}:-Upgrading\">more information</a></div>";
       }
       else
       {
        $page['body'].="\n<div class=\"alert alert-success\">MomoKO is up-to-date!</div>";
       }
       break;
       case 'template':
       $page['body'].="<input type=\"text\" disabled=\"disabled\" class=\"form-control\" id=\"{$setting['key']}\" value=\"{$setting['value']}\">\n<div class=\"alert alert-info\">Change in <a href=\"{$GLOBALS['SET']['siteroot']}/mk-dash.php?section=site&action=appearance\">site appearance</a></div>";
       break;
       //TODO add special cases for e-mail settings
       case 'email_mta':
       $mtas=array(array('name'=>"PHP mail()",'value'=>"phpmail"),array('name'=>'*Nix sendmail()','value'=>"sendmail",'tip'=>"Only works on Unix-like systems"),array('name'=>"SMTP",'value'=>"smtp",'tip'=>"For either local SMTP or remote SMTP (including gMail) servers.")); //TODO find a way of building this array by actually detecting supported mtas
       $page['body'].="<select class=\"form-control\" id=\"email_mta\" onchange=\"changeServerInputs()\" name=\"email_mta\">\n";
       foreach ($mtas as $auth)
       {
         if ($auth['value'] == $setting['value'])
         {
           $cur=" selected=selected";
         }
         else
         {
           $cur=null;
         }
         $page['body'].="<option{$cur} value=\"{$auth['value']}\">{$auth['name']}</option>\n";
       }
       $page['body'].="</select>";
       break;
       case 'email_server':
       case 'email_from':
       parse_str($setting['value'],$email_raw);
       $page['body'].="<ul id=\"{$setting['key']}\" class=\"nobullet\">\n";
       foreach ($email_raw as $key=>$val)
       {
         $name=ucwords($key);
         switch ($key)
         {
           case 'password':
           $type="password";
           break;
           default:
           $type="text";
         }
         $page['body'].="<li><div class=\"form-group\"><label for=\"{$setting['key']}_{$key}\">{$name}:</label> <input class=\"form-control\" onkeyup=\"serializeInputs('{$setting['key']}')\" id=\"{$setting['key']}_{$key}\" name=\"{$key}\" type=\"{$type}\" value=\"{$val}\"></div></li>\n";
       }
       $page['body'].="</ul>\n<input type=\"hidden\" name=\"{$setting['key']}\" value=\"{$setting['value']}\">";
       break;
       case 'use_ssl':
       $page['body'].="<select class=\"form-control\" id=\"${setting['key']}\" name=\"{$setting['key']}\">\n";
       $opts=array(array('value'=>'','title'=>"No"),array('value'=>'yes','title'=>"Only in senstive areas"),array('value'=>'strict','title'=>"For entire site"));
       foreach ($opts as $option)
       {
        if ($option['value'] == $setting['value'])
        {
         $page['body'].="<option selected=selected value=\"{$option['value']}\">{$option['title']}</option>\n";
        }
        else
        {
         $page['body'].="<option value=\"{$option['value']}\">{$option['title']}</option>\n";
        }
       }
       $page['body'].="</select>";
       break;
       case 'security_logging':
       case 'error_logging':
       case 'rewrite':
       if (!$setting['value'])
       {
        $page['body'].="<div id=\"{$setting['key']}\"><input type=radio id=\"{$setting['key']}1\" name=\"{$setting['key']}\" value=\"1\"> <label for=\"{$setting['key']}1\">Yes</label> <input type=radio id=\"{$setting['key']}0\" name=\"{$setting['key']}\" checked=checked value=\"\"> <label for=\"{$setting['key']}0\">No</label></div>";
       }
       else
       {
        $page['body'].="<div id=\"{$setting['key']}\"><input type=radio id=\"{$setting['key']}1\" name=\"{$setting['key']}\" checked=checked value=\"1\"> <label for=\"{$setting['key']}1\">Yes</label> <input type=radio id=\"{$setting['key']}0\" name=\"{$setting['key']}\" value=\"\"> <label for=\"{$setting['key']}0\">No</label></div>";
       }
       break;
       default:
       $page['body'].="<input class=\"form-control\" type=text id=\"{$setting['key']}\" name=\"{$setting['key']}\" value=\"{$setting['value']}\"></li>\n";
       break;
      }
      $page['body'].="</div>\n";
     }
     $page['body'].="<h3>Save</h3>\n<div class=\"box\" align=\"center\"><button class=\"btn btn-primary\" type=submit name=\"send\" value=\"1\">Save Changes</button>\n</div>\n</form>";
     break;
     case 'user':
     $dtfs['short']=array('m/d/Y','m-d-Y','m.d.Y','j M Y');
     $options['short']="<option value=\"\">Use Text Field:</option>";
     $dtfs['long']=array('F j, Y','jS F Y','m M Y');
     $options['long']=$options['short'];
     $dtfs['time']=array('H:i:s','h:i:s a','G:i','g:i a');
     $options['time']=$options['short'];
     foreach ($dtfs as $type=>$formats)
     {
      foreach ($formats as $format)
      {
       $options[$type].="<option value=\"{$format}\">".date($format,0)."</option>";
      }
     }
     $page['body']=<<<HTML
<script language="javascript">
$(function(){
 $("input#shortdateformat").before('<select class="formats form-control" id="shortdateformat">{$options['short']}</select> ');
 $("input#longdateformat").before('<select class="formats form-control" id="longdateformat">{$options['long']}</select> ');
 $("input#timeformat").before('<select class="formats form-control" id="timeformat">{$options['time']}</select> ');
 $("select.formats").change(function(){
  var id=$(this).attr('id');
  var format=$(this).val();
  $("input#"+id).focus().val(format);
 });
});
</script>
<form role="form" method=post>
<h3>Password</h3>
<div id="PassForm" class="form-group">
 <label for="pc">Do you wish to change your password?</label>
 <input type=checkbox id="pc" onclick="toggleInputState($(this),'[type=password]')" name="pass_change" value=1>
</div>
<div class="form-group">
 <label for="cpass">Current Password:</label>
 <input class="form-control" type=password id="cpass" disabled="disabled" name="oldpassword">
</div>
<div class="form-group">
 <label for="npass1">New Password:</label>
 <input class="form-control" type=password id="npass1" disabled="disabled" name="newpassword1">
</div>
<div class="form-group">
 <label for="npass2">Confirm New Password:</label>
 <input class="form-control" type=password id="npass2" disabled="disabled" name="newpassword2">
</div>
<h3>Settings</h3>
<input type=hidden name="num" value="{$this->user->num}">
HTML;
     $columns=array('name','email','shortdateformat','longdateformat','timeformat','rowspertable');
     $query=$this->table->getData("num:'".$this->user->num."'",$columns,null,1);
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
      $page['body'].="<div class=\"form-group\"><label for=\"{$form_field}\">{$title}: </label><input class=\"form-control\" type=\"{$type}\" id=\"{$form_field}\" name=\"{$form_field}\" value=\"{$user[$form_field]}\"></div>\n";
     }
     $page['body'].="<h3>Save</h3><div class=\"box\" align=center><button class=\"btn btn-primary\" type=submit name=\"send\" value=\"1\">Save Changes</button></div>\n</form>";
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
     if ($user_data['pass_change'])
     {
       $q=$this->table->getData("num:'= {$user_data['num']}'");
       $cur_info=$q->fetch(PDO::FETCH_ASSOC);
       if (($user_data['newpassword1'] == $user_data['newpassword2']) && (crypt($user_data['oldpassword'],$cur_info['password']) == $cur_info['password']))
       {
         $user_data['password']=crypt($user_data['newpassword2'],$GLOBALS['SET']['salt']);
         $pass_changed['worked']=true;
       }
       else
       {
         $pass_changed['worked']=false;
         $pass_changed['message']="You either supplied an incorrect current password or your the new passwords you supplied did not match, please go back and try again.";
       }
     }
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
<p>{$section} settings have been changed succesfully! Please feel free to <a href="{$GLOBALS['SET']['siteroot']}/mk-dash.php?section={$_GET['section']}&action=settings">Return</a> to the previous page, or select another page or action.</p>
HTML;

     if ($user_data['pass_change'] && $pass_changed['worked'])
     {
      $page['body'].="<p>Additionally we have updated your password as requested!</p>\n";
     }
     elseif ($user_data['pass_change'] && !$pass_changed['worked'])
     {
      $page['body'].="<p>Unfortunately we were not able to change your password as you requested.</p>\n<p>{$pass_changed['message']}</p>\n";
     }

     $page['body'].="</div>";
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
   case 'appearance':
   $page['title']="Site Appearance";
   if (!$user_data['raw_dom'] && !$user_data['send'])
   {
    $map=new MomokoNavigation($this->user,'display=simple');
    $maplist=$map->getModule('html');
    $templates=new DataBaseTable('addins');
    $query=$templates->getData("type:'template'",array('dir','shortname'),"shortname");
    $templatesettings="<ul class=\"nobullet noindent\">\n<div class=\"form-group\"><label for=\"layout\">Layout:</label> <select class=\"form-control\" id=\"layout\" name=\"template\">\n";
    while ($template=$query->fetch(PDO::FETCH_ASSOC))
    {
     if ($template['dir'] == $this->config->template)
     {
      $templatesettings.="<option selected=selected value=\"{$template['dir']}\">{$template['shortname']}</option>\n";
     }
     else
     {
      $templatesettings.="<option value=\"{$template['dir']}\">{$template['shortname']}</option>\n";
     }
    }
    $templatesettings.="</select> <a href=\"{$this->config->siteroot}/mk-dash.php?section=site&list=addins\" title=\"Template addins are listed here, add or remove them to change your selection.\">Manage Addins</a></div>\n<div class=\"form-group\"><label for=\"style\">Style:</label> <select class=\"form-control\" id=\"style\" name=\"style\">";
    $files=fetch_files("addins/".$this->config->template,'styles');
    $style=$templates->getData("dir:'".$this->config->template."'",array('num','headtags'));
    $style=$style->fetch(PDO::FETCH_ASSOC);
    $doc=new DOMDocument(); //Create a DOM document to parse headtags
    $doc->loadHTML($style['headtags']); //Load headtags into DOM for parsing
    $tags=$doc->getElementsByTagName('link'); //select all link tags to find styles
    $styles=array();
    foreach ($tags as $tag)
    {
        $styles[]=basename($tag->getAttribute('href')); //Read just the filename into an array to locate later
    }
    foreach ($files as $file)
    {
      $name=ucwords(pathinfo($file,PATHINFO_FILENAME));
      if (in_array($file,$styles))
      {
        $templatesettings.="<option selected=selected value=\"{$file}\">{$name}</option>\n";
      }
      else
      {
        $templatesettings.="<option value=\"{$file}\">{$name}</option>\n";
      }
    }
    $templatesettings.="</select></div>";

    $modulelayout="<div class=\"screenshot\" style=\"background-image:url('".$this->config->siteroot.$this->config->filedir."addins/".$this->config->template."/screenshot.png')\">".file_get_contents($this->config->basedir.$this->config->filedir."addins/".$this->config->template."/".$this->config->template.".pre.htm")."</div>";
    $addins=new DataBaseTable('addins');
    $dbquery=$addins->getData("type:'module'",array('num','dir','shortname','settings'),'order');
    $assoc=new DataBaseTable('mzassoc');
    $modulelist=NULL;
    while ($module=$dbquery->fetch(PDO::FETCH_ASSOC))
    {
     $zone_q=$assoc->getData("mod:`= {$module['num']}`");
     $mz=$zone_q->fetch(PDO::FETCH_ASSOC);
     $module['zone']=$mz['zone'];
     $module['settings']=$mz['settings'];
     if (!$module['zone']) //in case no zone is given, for example a new addin, set the zone to 0
     {
      $module['zone']=0;
     }
     require_once $this->config->basedir.$this->config->filedir."addins/".$module['dir']."/module.php";
     $mod_obj="Momoko".ucwords($module['dir'])."Module";
     $mod_obj=new $mod_obj($this->user,$module['settings']);
     $module['settings']=$mod_obj->settingsToHTML();
     $modulelist[$module['zone']].="<div id=\"{$module['num']}\" class=\"module panel panel-info\">\n<div class=\"panel-heading\"><h4 class=\"panel-title\">{$module['shortname']}<span data-target=\"#collapse{$module['num']}\" data-toggle=\"collapse\" class=\"right glyphicon glyphicon-plus\"></span></h4></div>\n<div id=\"collapse{$module['num']}\" class=\"panel-collapse collapse\">\n<div class=\"panel-body\">{$module['settings']}</div>\n</div>\n</div>\n";
    }
    if (preg_match_all("/<!-- MODULEPLACEHOLDER:(?P<arguments>.*?) -->/",$modulelayout,$list))
    {
      foreach ($list['arguments'] as $query)
      {
        parse_str($query,$opts);
        $modulelayout=preg_replace("/<!-- MODULEPLACEHOLDER:".preg_quote($query)." -->/",$modulelist[$opts['zone']],$modulelayout);
      }
    }
    foreach ($modulelist as $zone=>$div)
    {
      if ($zone != 0)
      {
        $modulelist[0].=$modulelist[$zone]; //Adds a copy of all modules to module source.
      }
    }
    if (preg_match_all("/<!-- MODULESOURCE -->/",$modulelayout,$list))
    {
     $modulelayout=preg_replace("/<!-- MODULESOURCE -->/",$modulelist[0],$modulelayout);
    }

    /*jQueryUI is added below as there is no better solution for drag-drop lists available at this time*/
    $page['body']=<<<HTML
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script language="javascript">
$(function(){
 $(".dialog").hide();
 $("select#layout").change(function(){
  $.getJSON("{$this->config->siteroot}/ajax.php?action=fetch_files&dir=addins/"+$(this).val()+"&limit=styles",function(j){
   $("select#style").fadeOut('fast',function(){
    var opts='';
    for (var i=0; i < j.length; i++)
    {
     opts+="<option>"+j[i]+"</option>";
    }
    $("select#style").html(opts).fadeIn('fast');
   });
  });
 });
 
 $("button#SaveMods").click(function(event){
  event.preventDefault();
  var raw=$("div.container").html();
  $("input#mods").val(raw);
  $("form#ModuleForm").submit();
 });
 
 $("button#ReOrder").click(function(){
  event.preventDefault();
  var raw=$("div#MapList").html();
  $("input#map").val(raw);
  $("form#MapForm").submit();;
 });
 $("ul.map ul").addClass("nobullet");
 
 $("#MapList .subnav").parent()
		.prepend("<span class='droparrow glyphicon glyphicon-plus'>&nbsp;</span>");
	$("#MapList span.droparrow").click(function(event){
		event.stopPropagation();
		$(this).parent().find("ul.subnav").toggle("slow");
		if ($(this).hasClass('glyphicon-plus'))
		{
			$(this).removeClass('glyphicon-plus');
			$(this).addClass('glyphicon-minus');
		}
		else
		{
			$(this).removeClass('glyphicon-minus');
			$(this).addClass('glyphicon-plus');
		}
	});
    $(".column").sortable({
      connectWith: ".column",
      handle: ".panel-heading",
      placeholder: "alert alert-success",
      receive:function(e,ui){
        ui.sender.data('copied',true);
      }
    });
    $("#0.column").sortable({
      helper:function(e,li){
        this.copyHelper=li.clone().insertAfter(li);
        $(this).data('copied',false);
        return li.clone();
      },
      stop: function(e,ui){
        var copied=$(this).data('copied');
        if (!copied){
          this.copyHelper.remove();
        }
        this.copyHelper=null;
      },
    });
    $("#MapList ul b.caret").parent().remove();
	$( "#MapList ul" ).addClass('list-group')
    		.sortable({
			placeholder: 'alert alert-success',
		})
    		.find( "li" )
        		.addClass( "list-group-item" )
            .find("ul").removeClass('dropdown-menu').hide()
			.find("a")
				.click(function(event){ event.preventDefault(); });
});
</script>
<div id="AppearancePlugs" class="box">
<div id="Templates" class="box" style="width:45%;float:left">
<h3>Template</h3>
<form method=post id="Template">
<input type=hidden name="section" value="template">
{$templatesettings}
<button class="btn btn-primary" type=submit name="send" value="1">Change Template</button>
</form>
</div>
<form method=post id="MapForm">
<div id="MapList" class="box" style="width:49%;float:left">
<h3>Navigation</h3>
<ul class="map nobullet">
{$maplist}
</ul>
<input type=hidden name="section" value="map">
<input type=hidden id="map" name="raw_dom">
<div align=right><button class="btn btn-primary" id="ReOrder">Re-Order</button></div>
</div>
</form>
<form method=post id="ModuleForm">
<div id="Modules" class="box" style="width:100%;float:left">
<h3>Modules</h3>
<div id="ModuleGrid" style="width:100%">
{$modulelayout}
</div>
<input type=hidden name="section" value="modules">
<input type=hidden id="mods" name="raw_dom">
<div align=center><button class="btn btn-primary" id="SaveMods">Update Modules</button></div>
</form>
</div>
</div>
<div id="ItemRemoveDialog" title="Remove Item" class="dialog">
<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Are you sure you want to remove this item from the site map? If the selected item is a section, all sub-pages and sections will also be removed. This action will not be saved until you select 'save'.</p>
</div>
HTML;
   }
   elseif ($user_data['section'] == 'map')
   {
    $new_map=new MomokoNavigation($GLOBALS['USR'],'display=simple');
    $new_map->reOrderbyHTML($user_data['raw_dom']);
    header("Location:http:{$this->config->siteroot}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   elseif ($user_data['section'] == 'modules')
   {
    $table=new DataBaseTable('addins');
    $assoc=new DataBaseTable('mzassoc');
    $assoc->emptyData(); //Clear old modules and settings, best way to avoid duplicates with information we have
    $html=str_get_html($user_data['raw_dom']);
    foreach ($html->find("div.column") as $node)
    {
     $mz['zone']=$node->id;
     $data['order']=1;
     foreach ($node->find("div.module") as $mod)
     {
      $data['num']=$mod->id;
      $mz['mod']=$mod->id;
      $mz['settings']=http_build_query($user_data[$data['num']]);
      try
      {
       $update=$table->updateData($data);
       if ($mz['zone'] != 0)
       {
         $mzu=$assoc->putData($mz);
       }
      }
      catch (Exception $err)
      {
       trigger_error("Caught exception '".$err->getMessage()."' while attempting to update modules");
      }
      $data['order']++;
     }
    }
    header("Location: {$this->config->siteroot}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   else
   {
    $addins=new DataBaseTable('addins');
    $list=$addins->getData("dir:'{$GLOBALS['SET']['template']}'",array('num','dir'),NULL,1);
    $cur_template=$list->fetch(PDO::FETCH_ASSOC);
    $cur_template['enabled']='n';
    $kill_template=$addins->updateData($cur_template);
    $list=$addins->getData("dir:'{$user_data['template']}'",array('num','dir'),null,1);
    $template=$list->fetch(PDO::FETCH_ASSOC);
    $template['enabled']='y';
    $template['headtags']="<link rel=\"stylesheet\" href=\"{$this->config->siteroot}{$this->config->filedir}addins/{$template['dir']}/{$user_data['style']}\" type=\"text/css\">";
    $style=$addins->updateData($template);
    
    $settings=new DataBaseTable('settings');
    $data['key']="template";
    $data['value']=$user_data['template'];
    $template=$settings->updateData($data);
    
    header("Location: {$this->config->siteroot}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   break;
   case 'fetch':
   $finfo['src']=file_url($_GET['uri']);
   $finfo['name']=rawurldecode(basename($finfo['src']));
   $finfo['author']=$this->user->num;
   $finfo['date_created']=date("Y-m-d H:i:s");
   $finfo['temp']=$this->config->basedir.$this->config->tempdir.time();
   if (copy($finfo['src'],$finfo['temp']))
   {
    $finfo['perm']=$this->config->filedir.$finfo['name'];
    if (class_exists('finfo'))
    {
       $upload_info=new finfo(FILEINFO_MIME_TYPE);
       $finfo['mime_type']=$upload_info->file($temp_file);
    }
    else
    {
       trigger_error("Could not reliably determine mime type of an uploaded file! finfo class does not exist, so mime type set by browser. Recommend updating PHP or installing the fileinfo PECL extension to avoid mime type spoofing.",E_USER_NOTICE);
       $finfo['mime_type']=$finfo['type'];
    }

    $finfo['type']='attachment'; //we do not accept page or post uploads this way yet!
    $finfo['title']=$finfo['name']; //currently we only accept attachment uploads this way so a file name is an attachment title
    if ($new_ko=$this->table->putData($finfo))
    {
     if (rename($finfo['temp'],$this->config->basedir.$finfo['perm']))
     {
      $finfo['link']=$this->config->siteroot.$finfo['perm'];
     }
     if ($_GET['ajax'] == 1)
     {
      echo ("<div class=\"page selectable box\"><a id=\"location\" href=\"{$finfo['link']}\" style=\"display:none\">[insert]</a><strong>{$finfo['title']}</strong></div>");
     }
    }
    else
    {
     unlink($finfo['temp']);
     if ($_GET['ajax'] == 1)
     {
      echo ("<div class=\"page error box\">Attachment could not be added to database!</div>");
     }
    }
   }
   else
   {
    $err=error_get_last();
    $msg="Remote file could not be copied to this server";
    $err['origin']=$finfo['src'];
    $err['dest']=$finfo['temp'];
    if ($_GET['ajax'] == 1)
    {
     echo <<<HTML
<div class="page error box"><strong>$msg</strong>
<ul class="nobullet noindent">
<li>Origin: {$err['origin']}</li>
<li>Destination: {$err['dest']}</li>
</ul>
</div>
HTML;
    }
   }
   break;
   case 'upload':
   switch ($_GET['section'])
   {
    case 'addin':
    if ($_FILES['addin']['tmp_name'])
    {
     if ($_FILES['addin']['error'] == UPLOAD_ERR_OK)
     {
      $temp=$this->config->basedir.$this->config->tempdir.time().$_FILES['addin']['name'];
      move_uploaded_file($_FILES['addin']['tmp_name'],$temp);
      $zip=new ZipArchive();
      if ($zip->open($temp) === TRUE)
      {
       if ($manifest=$zip->getFromName("MANIFEST"))
       {
        $manifest=parse_ini_string($manifest,true);
        $values=$manifest['info'];
        unset($manifest);
        $values['dir']=pathinfo($_FILES['addin']['name'],PATHINFO_FILENAME);
        $values['extractFrom']=basename($temp);
       }
       else
       {
        $finfo['error']="Possible missing MANIFEST in archive! Please ensure this is a MomoKO addin package!";
       }
      }
      else
      {
       $finfo['error']="Could not open temporary archive ('{$temp}')! Is it a .zip, or .apkg!?";
      }
     }
     else
     {
      switch ($_FILES['file']['error'])
      {
       case UPLOAD_ERR_INI_SIZE:
       $finfo['error']="Uploaded file is too large. Try increasing PHP's max upload size in 'php.ini'";
       break;
       case UPLOAD_ERR_PARTIAL:
       $finfo['error']="Uploaded file was only partially recieved!";
       break;
       case UPLOAD_ERR_NO_FILE:
       $finfo['error']="No file was received!";
       break;
       case UPLOAD_ERR_CANT_WRITE:
       $finfo['error']="Failed to write uploaded file to disk";
       break;
       default:
       $finfo['error']="Unknown error encountered while attempting to upload a file, please try again!";
       break;
      }
     }
    }
    else
    {
     $finfo['error']="Please select a file to upload!";
    }
    
    if (!$finfo['error'])
    {
     $script_body=<<<TXT
$('span#msg',pDoc).html("Uploaded!").addClass("success");
$('input#addin-temp',pDoc).val("{$values['extractFrom']}");
$('input#addin-type',pDoc).val("{$values['type']}");
$('input#addin-dir',pDoc).val("{$values['dir']}");
$('input#addin-shortname',pDoc).val("{$values['shortname']}");
$('input#addin-longname',pDoc).val("{$values['longname']}");
$('textarea#addin-description',pDoc).val("{$values['description']}");

$('li#addin-hidden',pDoc).append(" {$values['dir']}").show();
$('form',pDoc).find('input, textarea, button, select').removeAttr('disabled');

window.setTimeout(function(){
 $("span#msg",pDoc).remove();
 $('input#file',pDoc).removeAttr('disabled');
}, 1500);
TXT;
    }
    else
    {
     $script_body=<<<TXT
$('span#msg',pDoc).html('{$finfo['error']}').addClass("error");
window.setTimeout(function(){
 $('input#file',pDoc).removeAttr('disabled');
}, 1500);
TXT;
    }
     
    $page['body']=<<<HTML
<html>
<head>
<title>File Upload</title>
<script language=javascript src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<body>
<script language="javascript" type="text/javascript">
var pDoc=window.parent.document;

{$script_body}
</script>
<p>Processing complete. Check below for further debugging.</p>
</body>
</html>
HTML;
    break;
    case 'attachment':
    default:
    $page['title']="Upload a file from your computer";
    if ($_FILES['file']['tmp_name'])
    {
     if ($_FILES['file']['error'] == UPLOAD_ERR_OK)
     {
      $finfo=$_FILES['file'];
      if (class_exists("finfo"))
      {
       $upload_info=new finfo(FILEINFO_MIME_TYPE);
       $finfo['mime_type']=$upload_info->file($finfo['tmp_name']);
      }
      else
      {
       trigger_error("Could not reliably determine mime type of an uploaded file! finfo class does not exist, so mime type set by browser. Recommend updating PHP or installing the fileinfo PECL extension to avoid mime type spoofing.",E_USER_NOTICE);
       $finfo['mime_type']=$finfo['type'];
      }
      $finfo['author']=$GLOBALS['USR']->num;
      $finfo['date_created']=date("Y-m-d H:i:s");
      $finfo['temp']=$this->config->basedir.$this->config->tempdir."temp-attach-".time();
    
      if (is_writable($this->config->basedir.$this->config->tempdir))
      {
       move_uploaded_file($_FILES['file']['tmp_name'],$finfo['temp']) or die(trigger_error("Cannot move file to '{$finfo['temp']}'!"));
       if ($finfo['mime_type'] == "text/html")
       {
        $finfo['type']='page';
        if($raw=file_get_contents($finfo['temp']))
        {
         preg_match("/<title>(?P<title>.*?)<\/title>/smU",$raw,$match);
         $finfo['title']=$match['title'];
         unset($match);
         preg_match("/<body>(?P<body>.*?)<\/body>/smU",$raw,$match);
         $finfo['text']=$match['body'];
         unset($match);
         try
         {
          $new_ko=$this->table->putData($finfo);
         }
         catch (Exception $err)
         {
          trigger_error("Caught exception '".$err->getMessage()."' while attempting to add attachment to database",E_USER_ERROR);
          $finfo['error']=$err->getMessage();
         }
         if ($GLOBALS['SET']['rewrite'])
         {
          //TODO set $finfo['link'] to human readable URI
         }
         else
         {
          $finfo['link']=$this->config->siteroot."?p=".$new_ko;
          $finfo['edit']=$finfo['link']."&action=edit";
         }
        }
        else
        {
         $finfo['error']="HTML file detected, but I could not process it!";
        }
        unlink($finfo['temp']);
       }
       else
       {
        $finfo['type']='attachment';
        $finfo['title']=$finfo['name'];
        $finfo['link']=$this->config->sec_protocol.$this->config->baseuri.$this->config->filedir.$finfo['name'];
        $finfo['edit']="#";
        if(rename($finfo['temp'],$this->config->basedir.$this->config->filedir.$finfo['name']))
        {
         try
         {
          $new_ko=$this->table->putData($finfo);
         }
         catch (Exception $err)
         {
          trigger_error("Caught exception '".$err->getMessage()."' while attempting to add attachment to database",E_USER_ERROR);
          ulink($this->config->filedir.$finfo['name']);
          $finfo['error']=$err->getMessage();
         }
        }
        else
        {
         $finfo['error']="Could not move attachment to its permenant location (".$GLOBALS['SET']['basedir'].$this->config->filedir.$finfo['name'].")!";
         }
       }
      }
      else
      {
       trigger_error("Cannot write to temporary storage directory!",E_USER_WARNING);
       if (file_exists($this->config->basedir.$this->config->tempdir))
       {
        $finfo['error']="Temp folder not writable!";
       }
       else
       {
        $finfo['error']="Temp folder does not exist!";
       }
      }
     }
     else
     {
      switch ($_FILES['file']['error'])
      {
       case UPLOAD_ERR_INI_SIZE:
       $finfo['error']="Uploaded file is too large. Try increasing PHP's max upload size in 'php.ini'";
       break;
       case UPLOAD_ERR_PARTIAL:
       $finfo['error']="Uploaded file was only partially recieved!";
       break;
       case UPLOAD_ERR_NO_FILE:
       $finfo['error']="No file was received!";
       break;
       case UPLOAD_ERR_CANT_WRITE:
       $finfo['error']="Failed to write uploaded file to disk";
       break;
       default:
       $finfo['error']="Unknown error encountered while attempting to upload a file, please try again!";
       break;
      }
     }
    
     if (!$finfo['error'])
     {
      $script_body=<<<TXT
$('#ExtFile #msg',pDoc).html("Uploaded!").removeClass('alert-info').addClass('alert-success');
$('div#FileInfo',pDoc).append("<div onclick=\"window.location='{$finfo['edit']}'\" class=\"page selectable box\"><a id=\"location\" href=\"{$finfo['link']}\" style=\"display:none\">[insert]</a><strong>{$finfo['title']}</strong></div>");
window.setTimeout(function(){
 $("#ExtFile #msg",pDoc).fadeOut('slow');
 $('input#file',pDoc).val("").removeAttr('disabled');
}, 1500);
TXT;
     }
     else
     {
     $script_body=<<<TXT
$('#ExtFile #msg',pDoc).html('{$finfo['error']}').removeClass("alert-info").addClass("alert-danger");
window.setTimeout(function(){
 $('input#file',pDoc).val("").removeAttr('disabled');
}, 1500);
window.setTimeout(function(){
 $("#ExtFile #msg",pDoc).fadeOut('slow');
}, 4500);
TXT;
     }
     
     $page['body']=<<<HTML
<html>
<head>
<title>File Upload</title>
<script language=javascript src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<body>
<script language="javascript" type="text/javascript">
var pDoc=window.parent.document;

{$script_body}
</script>
<p>Processing complete. Check below for further debugging.</p>
</body>
</html>
HTML;
    }
    else
    {
     $page['body']=<<<HTML
<p>Ready for upload!</p>
HTML;
    }
    break;
   }
   break;
   case 'picker':
     $list=$this->table->getData("type:`page`");
     $addins=null;
     while ($page=$list->fetch(PDO::FETCH_OBJ))
     {
       $addins.="<div id=\"{$page->dir}\" class=\"page selectable box\"><strong>{$page->longname}</strong><p>{$page->description}</p></div>";
     }
     $page['title']="Addin Picker...";
     $page['body']=<<<HTML
<div id="AddinPages">
{$addins}
</div>
HTML;
   break;
   case 'addinform':
     $info=$this->table->getData("dir:`{$_GET['addin']}`");
     $info=$info->fetch(PDO::FETCH_OBJ);
     require_once $this->config->basedir.$this->config->filedir."addins/".$info->dir."/page.php";
     $addin=new MomokoPageAddin();
     $page['title']="Addin Form";
     $page['body']=$addin->getForm();
   break;
   case 'gethref':
   $blank=null;
   $list=$this->table->getData(null,null,'order');
   while ($content=$list->fetch(PDO::FETCH_OBJ))
   {
    $hsep="&";
    if (!$content->link)
    {
     if ($this->config->rewrite)
     {
      $href=$this->config->siteroot."/{$content->type}/".urlencode($content->title).".htm";
      $hsep="?";
     }
     else
     {
      $href=$this->config->siteroot."/?p={$content->num}";
     }
     if (@$_GET['origin'] == "new")
     {
      $href.=$hsep."ajax=1";
     }
    }
    else
    {
     $href=$content->link;
    }
    $temp="<div id=\"{$content->num}\" class=\"page selectable box\"><a id=\"location\" href=\"{$href}\" style=\"display:none\">[insert]</a><strong>{$content->title}</strong></div>";
    switch($content->type)
    {
     case 'page':
     $pages.=$temp;
     break;
     case 'post':
     $posts.=$temp;
     break;
     case 'attachment':
     $attachments.=$temp;
     break;
    }
   }

   if (@$_GET['origin'] == "new")
   {
    $blank="<div id=\"0\" class=\"page selectable box\"><a id=\"location\" href=\"{$this->config->siteroot}{$this->config->filedir}/forms/new.htm\" style=\"display:none\">[insert]</a><strong>Blank Page or Post</strong></div>";
   }

   if (ini_get('allow_url_fopen'))
   {
    $exturi_perams=" title=\"Hint: Press enter or return when finished\"";
   }
   else
   {
    $exturi_perams=" disabled=disabled title=\"Your server does not support this function!\"";
   }
   $page['title']="Link Chooser";
   $page['body']=<<<HTML
<ul class="nav nav-pills">
<li class="active"><a data-toggle="pill" href="#External">External Source</a></li>
<li><a data-toggle="pill" href="#Pages">Page</a></li>
<li><a data-toggle="pill" href="#Posts">Post</a></li>
<li><a data-toggle="pill" href="#Attachments">Attachment</a></li>
</ul>
<div class="tab-content">
<div id="External" class="tab-pane fade in active">
{$blank}
<h4 class="module">Upload</h4>
<form enctype="multipart/form-data" action="{$this->config->siteroot}/mk-dash.php?section=content&action=upload&ajax=1" method="post" target="droptarget">
<div id="ExtURI"><label for="uri">A file from the web: </label><input{$exturi_perams} type=url id="uri" name="uri" placeholder="http://" onkeypress="iFetch(event,this)"></div>
<div id="ExtFile"><label for="file">A file on your computer: </label><input type=file id="file" name="file" onchange="iUpload(this)"></div>
</form>
<div id="FileInfo"><iframe id="FileTarget" name="droptarget" style="display:none" src="{$this->config->siteroot}/mk-dash.php?section=content&action=upload&ajax=1"></iframe></div>
</div>
<div id="Pages" class="tab-pane fade">
<h4 class="module">Select a Page</h4>
{$pages}
</div>
<div id="Posts" class="tab-pane fade">
<h4 class="module">Select a Post</h4>
{$posts}
</div>
<div id="Attachments" class="tab-pane fade">
<h4 class="module">Select an Attachment</h4>
{$attachments}
 </div>
 </div>
</div>
HTML;
   break;
   case 'list':
   default:
   $page['title']="Manage Users";
   $page['body']="<div class=\"list plug box\"><table class=\"table table-striped\" width=100% class=\"dashboard row-select\">\n<thead>\n<tr>\n";
   $columns=array('num','name','email','groups');
   foreach ($columns as $column)
   {
    if ($column != 'num')
    {
     $page['body'].="<th id=\"{$column}\">".ucwords($column)."</th>";
    }
   }
   $page['body'].="<th>&nbsp;</th></thead></tr><tbody>";
   $query=$this->table->getData(null,$columns);
   $row_c=$query->rowCount();
   if ($row_c > $this->user->rowspertable)
   {
    $query=$this->table->getData(null,$columns,null,$this->user->rowspertable,@$_GET['offset']);
    $prev=@$_GET['offset']-$GLOBALS['USR']->rowspertable;
    if ($prev < 0)
    {
     $prev=0;
    }
    $page_div="<div id=\"UserPages\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"{$this->config->siteroot}/mk-dash.php?section=user&action=list&offset={$prev}\">Previous</a></td><td align=center>";
    $pages=paginate($row_c,$this->user,@$_GET['offset']);
    foreach ($pages as $page)
    {
     if ($page['offset'] == @$_GET['offset'])
     {
      $page_div.="<strong class=\"currentpage\">{$page['number']}</strong>";
     }
     else
     {
      $page_div.="<a href=\"{$this->config->siteroot}/mk-dash.php?section=user&action=list&offset={$page['offset']}\">{$page['number']}</a> ";
     }
    }
    $next=@$_GET['offset']+$GLOBALS['USR']->rowspertable;
    if ($next > $row_C)
    {
     $next=0;
    }
    $page_div.="</td><td align=right><a href=\"{$this->config->siteroot}/mk-dash.php?section=user&action=list&offset={$next}\">Next</a></td>\n</tr>\n</table></div>";
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
     $row.="<tr id=\"".$user['num']."\">\n";
     foreach ($user as $col=>$value)
     {
      if ($col != num)
      {
       $row.="<td>".$value."</td>";
      }
     }
     $row.="<td><a href=\"#modal\" data-toggle=\"modal\" class=\"glyphicon glyphicon-edit\" onclick=\"populateModal('{$this->config->siteroot}/mk-dash.php?ajax=1&section=user&action=edit&id={$user['num']}','Edit User #{$user['num']}')\" title=\"Edit User #{$user['num']}\"></a> <a href=\"#modal\" data-toggle=\"modal\" class=\"glyphicon glyphicon-remove-sign\" onclick=\"populateModal('{$this->config->siteroot}/mk-dash.php?ajax=1&section=user&action=delete&id={$user['num']}','Remove User #{$user['num']}')\" title=\"Remove User #{$user['num']}\"></span></td></tr>\n";
     $c++;
    }
   }
   $page['body'].=$row;
   unset($row);
   $page['body'].="</tbody>\n</table>\n</div>".$page_div;
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
 header("Location: ".$config->sec_protocol.$config->baseuri."?action=".$_GET['action']);
}
else
{
 if ($auth->inGroup('users')) //User must be logged in!
 {
  if ($_GET['section'] == 'switchboard')
  {
   if (!empty($_GET['plug']))
   {
    require_once $config->basedir."/mk-content/addins/{$_GET['plug']}/plug.inc.php";
    $child=new MomokoSwitchboard();
   }
   else
   {
    trigger_error("No Plug selected for the switchboard!",E_USER_ERROR);
   }
  }
  else
  {
   $child=new MomokoDashboard($_GET['section'],$auth,$config);
   if (!empty($_GET['action']))
   {
       $child->getByAction($_GET['action'],$_POST);
   }
  }
 }
 else
 {
  $child=new MomokoError('403 Forbidden',$auth);
 }
 
 if ($_GET['ajax'] == TRUE)
 {
  echo $child->inner_body;
 }
 else
 {
  $tpl=new MomokoTemplate($auth,$config);
  echo $tpl->toHTML($child);
 }
}
