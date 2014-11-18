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
   $cols=null; //TODO remove this from all queries
   $text="<div id=\"ContentList\" class=\"box\">\n";
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
    $page_div="<div id=\"pages\" class=\"box\"><table width=100% cellspacing=1 cellpadding=1>\n<tr>\n<td align=left><a href=\"{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list={$list}&offset={$prev}\">Previous</a></td><td align=center>";
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
    $summary_len=300;
    while($content=$query->fetch(PDO::FETCH_ASSOC))
    {
     $content['date_created']=date($GLOBALS['USR']->shortdateformat,strtotime($content['date_created']));
     if ($content['date_modified'])
     {
      $content['date_modified']=date($GLOBALS['USR']->shortdateformat,strtotime($content['date_modified']));
     }
     else
     {
      $content['date_modified']=date($GLOBALS['USR']->shortdateformat,strtotime($content['date_created']));
     }
     $content['author']=get_author($content['author']); //Fetches the author object
     $content['author']=ucwords($content['author']->name); //Narrows down to just the name
     $content['text']=preg_replace("/<h2>(.*?)<\/h2>/smU",'',$content['text']); //get rid of page title, it will be styleized and placed elsewhere.
     if (strlen($content['text']) > $summary_len)
     {
      preg_match("/^(.{1,".$summary_len."})[\s]/i", $content['text'], $matches);
      $content['text']=$matches[0].'...';
     }
     if (!$content['link'])
     {
      if ($GLOBALS['SET']['rewrite'])
      {
       $content['link']=$content['type']."/".urlencode($content['title'].".htm?");
      }
      else
      {
       $content['link']="?content={$content['type']}&p={$content['num']}&";
      }
     }
     else
     {
      $content['link']="?content={$content['type']}&link={$content['link']}&";
     }
     
     $text.=<<<HTML
<div id="{$content['num']}" class="page box {$content['status']}"><h4 style="display:inline-block;clear:left" class="module">{$content['title']}</h4>
<div class="actions "style="float:right"><a href="//{$GLOBALS['SET']['baseuri']}/{$content['link']}" id="location" style="display:none">Open</a> <span id="view" class="ui-icon ui-icon-folder-open" style="display:inline-block" title="View"></span> <span id="edit" class="ui-icon ui-icon-pencil" style="display:inline-block" title="Edit"></span> <span id="delete" class="ui-icon ui-icon-trash" style="display:inline-block" title="Delete"></span></div>
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
       case 'use_ssl':
       $title="use SSL";
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
       $page['body'].="<span id=\"{$setting['key']}\">{$setting['value']} <em class=\"message\">changed only by update script!</em></span>";
       break;
       case 'template':
       $page['body'].="<span id=\"{$setting['key']}\">{$setting['value']} <em class=\"message\">change in <a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=appearance\">site appearance</a></em></span>";
       break;
       case 'security_logging':
       case 'error_logging':
       $page['body'].="<input type=number id=\"{$setting['key']}\" name=\"{$setting['key']}\" value=\"{$setting['value']}\">";
       break;
       //TODO add special cases for e-mail settings
       case 'use_ssl':
       $page['body'].="<select id=\"${setting['key']}\" name=\"{$setting['key']}\">\n";
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
   case 'appearance':
   $page['title']="Site Appearance";
   if (!$user_data['raw_dom'] && !$user_data['send'])
   {
    $map=new MomokoNavigation($GLOBALS['USR'],'display=simple');
    $maplist=$map->getModule('html');
    $templates=new DataBaseTable('addins');
    $query=$templates->getData("type:'template'",array('dir','shortname'),"shortname");
    $templatesettings="<ul class=\"nobullet noindent\">\n<li><label for=\"layout\">Layout:</label> <select id=\"layout\" name=\"template\">\n";
    while ($template=$query->fetch(PDO::FETCH_ASSOC))
    {
     if ($template['dir'] == $GLOBALS['SET']['template'])
     {
      $templatesettings.="<option selected=selected value=\"{$template['dir']}\">{$template['shortname']}</option>\n";
     }
     else
     {
      $templatesettings.="<option value=\"{$template['dir']}\">{$template['shortname']}</option>\n";
     }
    }
    $templatesettings.="</select></li>\n<li><label for=\"style\">Style:</label> <select id=\"style\" name=\"style\">";
    foreach (glob($GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir']."templates/".$GLOBALS['SET']['template']."/*.css") as $file)
    {
     if (preg_match("/((?:[a-z][a-z]+))(-)((?:[a-z][a-z\\.\\d_]+)\\.(?:[a-z\\d]{3}))(?![\\w\\.])/",$file,$matches) == 0)
     {
      $name=basename($file);
      $templatesettings.="<option>{$name}</option>\n";
     }
    }
    $templatesettings.="</select></li>\n</ul>";

    $modulelayout=file_get_contents($GLOBALS['SET']['filedir']."templates/".$GLOBALS['SET']['template']."/".$GLOBALS['SET']['template'].".pre.htm");
    $addins=new DataBaseTable('addins');
    $dbquery=$addins->getData("type:'module'",array('num','dir','shortname','zone','settings'),'order');
    $modulelist=NULL;
    while ($module=$dbquery->fetch(PDO::FETCH_ASSOC))
    {
     if (!$module['zone'])
     {
      $module['zone']=0;
     }
     parse_str($module['settings'],$module['settings']);
     include_once $GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir']."addins/".$module['dir']."/module.php";
     $mod_obj="Momoko".ucwords($module['dir'])."Module";
     $mod_obj=new $mod_obj();
     $module['settings']=$mod_obj->settingsToHTML($module['settings']);
     $modulelist[$module['zone']].="<div id=\"{$module['num']}\" class=\"module portlet box\">\n<div class=\"portlet-header\">{$module['shortname']}</div>\n<div class=\"portlet-content\">{$module['settings']}</div>\n</div>\n";
    }
    if (preg_match_all("/<!-- MODULEPLACEHOLDER:(?P<arguments>.*?) -->/",$modulelayout,$list))
    {
      foreach ($list['arguments'] as $query)
      {
        parse_str($query,$opts);
        $modulelayout=preg_replace("/<!-- MODULEPLACEHOLDER:".preg_quote($query)." -->/",$modulelist[$opts['zone']],$modulelayout);
      }
    }
    if (preg_match_all("/<!-- MODULESOURCE -->/",$modulelayout,$list))
    {
     $modulelayout=preg_replace("/<!-- MODULESOURCE -->/",$modulelist[0],$modulelayout);
    }

    $page['body']=<<<HTML
<script language="javascript">
$(function(){
 $(".dialog").hide();
 
 $("select#template").change(function(){
  $("select#style").disable();
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
		.prepend("<span class='droparrow ui-icon ui-icon-carat-1-se'></span>");
	$("#MapList span.droparrow").click(function(event){
		event.stopPropagation();
		$(this).parent().find("ul.subnav").toggle("slow");
		if ($(this).hasClass('ui-icon-carat-1-e'))
		{
			$(this).removeClass('ui-icon-carat-1-e');
			$(this).addClass('ui-icon-carat-1-se');
		}
		else
		{
			$(this).removeClass('ui-icon-carat-1-se');
			$(this).addClass('ui-icon-carat-1-e');
		}
	});
    $(".column").sortable({
      connectWith: ".column",
      handle: ".portlet-header",
      cancel: ".portlet-toggle",
      placeholder: "portlet-placeholder ui-corner-all"
    });
    $(".portlet")
      .addClass( "ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" )
      .find( ".portlet-header" )
        .addClass( "ui-widget-header ui-corner-all" )
        .prepend( "<span class='ui-icon ui-icon-minusthick portlet-toggle'></span>");
    $(".portlet-toggle").click(function() {
      var icon = $( this );
      icon.toggleClass( "ui-icon-minusthick ui-icon-plusthick" );
      icon.closest( ".portlet" ).find( ".portlet-content" ).toggle();
    });
	$( "#MapList ul" )
    		.sortable({
			placeholder: 'ui-state-highlight',
		})
    		.find( "li" )
        		.addClass( "ui-state-default ui-corner-all" )
			.click(function(event){
				event.stopPropagation();
				if ($(this).hasClass('ui-state-highlight')){
					$(this).removeClass('ui-state-highlight');
	 			}
				else{
					$('.ui-state-highlight').removeClass('ui-state-highlight');
					$(this).addClass('ui-state-highlight');
				}
			})
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
<button type=submit name="send" value="1">Change Template</button>
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
<div align=right><button id="ReOrder">Re-Order</button></div>
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
<div align=center><button id="SaveMods">Update Modules</button></div>
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
    header("Location:http://{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   elseif ($user_data['section'] == 'modules')
   {
    $table=new DataBaseTable('addins');
    $html=str_get_html($user_data['raw_dom']);
    foreach ($html->find("div.column") as $node)
    {
     $data['zone']=$node->id;
     $data['order']=1;
     foreach ($node->find("div.module") as $mod)
     {
      $data['num']=$mod->id;
      $data['settings']=http_build_query($user_data[$data['num']]);
      try
      {
       $update=$table->updateData($data);
      }
      catch (Exception $err)
      {
       trigger_error("Caught exception '".$err->getMessage()."' while attempting to update modules");
      }
      $data['order']++;
     }
    }
    header("Location: http://{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   else
   {
    $addins=new DataBaseTable('addins');
    $list=$addins->getData("shortname:'{$GLOBALS['SET']['template']}'",array('num','dir'),NULL,1);
    $cur_template=$list->fetch(PDO::FETCH_ASSOC);
    $cur_template['enabled']='n';
    $kill_template=$addins->updataData($cur_template);
    $list=$addins->getData("shortname:'{$user_data['template']}'",array('num','dir'),null,1);
    $template=$list->fetch(PDO::FETCH_ASSOC);
    $template['enabled']='y';
    $template['headtags']="<link rel=\"stylesheet\" href=\"".GLOBAL_PROTOCOL."//{$GLOBALS['SET']['baseuri']}{$GLOBALS['SET']['filedir']}templates/{$template['dir']}/{$user_data['style']}\" type=\"text/css\">";
    $style=$addins->updateData($template);
    
    $settings=new DataBaseTable('settings');
    $data['key']="template";
    $data['value']=$user_data['template'];
    $template=$settings->updateData($data);
    
    header("Location: http://{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=appearance");
    exit();
   }
   break;
   case 'upload':
   $page['title']="Upload a file from your computer";
   if ($_FILES['file']['tmp_name'])
   {
    $upload_info=new finfo(FILEINFO_MIME_TYPE);
    $finfo=$_FILES['file'];
    $finfo['mime_type']=$upload_info->file($finfo['tmp_name']);
    $finfo['temp']=$GLOBALS['SET']['filedir']."/temp/".crypt(time());
    
    if (is_writable($GLOBALS['SET']['filedir']."/temp"))
    {
     move_uploaded_file($_FILES['file']['tmp_name'],$finfo['temp']);
     //TODO put file in database and move to permenant location if needed.
     if ($finfo['mime_type'] == "text/html")
     {
      $finfo['type']='page';
      if($html=file_get_contents($finfo['temp']))
      {
       //TODO find title and body
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
      $finfo['link']=$GLOBALS['SET']['filedir']."/".$finfo['name'];
      if(rename($finfo['temp'],$GLOBALS['SET']['baseuri']."/".$finfo['link']))
      {
       try
       {
        $new_ko=$this->table->putData($finfo);
       }
       catch (Exception $err)
       {
        trigger_error("Caught exception '".$err->getMessage()."' while attempting to add attachment to database",E_USER_ERROR);
        ulink($finfo['link']);
        $finfo['error']=$err->getMessage();
       }
      }
      else
      {
       $finfo['error']="Could not move attachment to its permenant location!";
      }
     }
    }
    else
    {
     trigger_error("Cannot write to temporary storage directory!",E_USER_WARNING);
     if (file_exists($GLOBALS['SET']['filedir']."/temp"))
     {
      $finfo['error']="Temp folder not writable!";
     }
     else
     {
      $finfo['error']="Temp folder does not exist!";
     }
    }
    
    if (!$finfo['error'])
    {
     $script_body=<<<TXT
$('span#msg',pDoc).html("Uploaded!").addClass("success");
$('div#FileInfo',pDoc).append("<div class=\"page selectable box\"><a id=\"location\" href=\"{$finfo['link']}\" style=\"display:none\">[insert]</a><strong>{$finfo['title']}</strong></div>");
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
   }
   else
   {
    $page['body']=<<<HTML
<p>Ready for upload!</p>
HTML;
   }
   break;
   case 'gethref':
   $list=$this->table->getData(null,null,'order');
   while ($content=$list->fetch(PDO::FETCH_OBJ))
   {
    if (!$content->link)
    {
     if ($GLOBALS['SET']['rewrite'])
     {
      $href=GLOBAL_PROTOCOL."//{$GLOBALS['SET']['baseuri']}/{$content->type}/".urlencode($content->title).".htm";
     }
     elseif ($content->type == 'page')
     {
      $href=GLOBAL_PROTOCOL."//{$GLOBALS['SET']['baseuri']}/?p={$content->num}";
     }
     else
     {
      $href=GLOBAL_PROTOCOL."//{$GLOBALS['SET']['baseuri']}/?content={$content->type}&p={$content->num}";
     }
    }
    else
    {
     $href=GLOBAL_PROTOCOL."//{$GLOBALS['SET']['baseuri']}/".$content->link;
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
   $page['title']="Browse Site";
   $page['body']=<<<HTML
<div id="vtabs">
<ul>
<li><a href="#External">External Source</a></li>
<li><a href="#Pages">Page</a></li>
<li><a href="#Posts">Post</a></li>
<li><a href="#Attachments">Attachment</a></li>
</ul>
<div id="External">
<h4 class="module">Upload</h4>
<form enctype="multipart/form-data" action="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&action=upload&ajax=1" method="post" target="droptarget">
<div id="ExtURI"><label for="uri">A file from the web: </label><input type=text id="uri" name="uri" placeholder="http://"></div>
<div id="ExtFile"><label for="file">A file on your computer: </label><input type=file id="file" name="file" onchange="iUpload(this)"></div>
</form>
<div id="FileInfo"><iframe id="FileTarget" name="droptarget" style="display:none" src="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&action=upload&ajax=1"></iframe></div>
</div>
<div id="Pages">
<h4 class="module">Select a Page</h4>
{$pages}
</div>
<div id="Posts">
<h4 class="module">Select a Post</h4>
{$posts}
</div>
<div id="Attachments">
<h4 class="module">Select an Attachment</h4>
{$attachments}
 </div>
</div>
HTML;
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
