<?php
class VictoriqueSettings
{
 public $user;
 public $settings=array();

 public function __construct(MomokoSession $user)
 {
  $tbl=new DataBaseTable('ss_bb_settings',DAL_DB_DEFAULT);
  if ($user->inGroup('users'))
  {
   $data=$tbl->getData(null,'name='.$user->name,null,1);
   $data=$data->toArray();
   if (@$data[0]['name'])
   {
    $this->settings=$data[0];
   }
   else
   {
    $data[0]['name']=$user->name;
    $data[0]['lastlogin']=time();
    $tbl->putData($data[0]);
    unset($data);
    $data=$tbl->getData(null,'name='.$user->name,null,1);
    $data=$data->toArray();
    $this->settings=$data[0];
   }
  }
  else
  {
   $data=$tbl->getData(null,'name=guest',null,1);
   $data=$data->toArray();
   $this->settings=$data[0];
  }
  $this->user=$user;
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->settings))
  {
   return $this->settings[$key];
  }
  else
  {
   return $this->user->$key;
  }
 }

 public function __set($key,$value)
 {
  return $this->settings[$key]=$value;
 }
}

if (@$_SESSION['data'] && @$_SESSION['bbdata'])
{
 $GLOBALS['USR']=unserialize($_SESSION['data']);
 $GLOBALS['BBUSR']=unserialize($_SESSION['bbdata']);
}
elseif (@$_SESSION['data'] && @!$_SESSION['bbdata'])
{
 $GLOBALS['USR']=unserialize($_SESSION['data']);
 $GLOBALS['BBUSR']=new VictoriqueSettings($GLOBALS['USR']);
 $_SESSION['bbdata']=serialize($GLOBALS['BBUSR']);
}
else
{
 $GLOBALS['USR']=new MomokoSession();
 $GLOBALS['BBUSR']=new VictoriqueSettings($GLOBALS['USR']);
 $_SESSION['data']=serialize($GLOBALS['USR']);
 $_SESSION['bbdata']=serialize($GLOBALS['BBUSR']);
}

class VictoriqueAction implements MomokoLITEObject
{
 private $info=array();

 public function __construct($action=null)
 {
  switch ($action)
  {
   case 'viewprofile':
   $this->info=$this->showProfile($_GET['u']);
   break;
			case 'editprofile':
			$this->info=$this->editProfile($_GET['u'],$_POST);
			break;
   case 'login':
   case 'logout':
   unset ($_SESSION['bbdata']);
   case 'register':
   header("Location: ".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."?action=".$_GET['action']);
   break;
   case 'admin':
   require BBBASE.'/admin.inc.php';
   $acp=new VictoriqueACP($_GET);
   $this->info=$acp->get();
   break;
   case 'mod':
   case 'settings':
   case 'pm':
   case 'notifications':
   $page=new VictoriqueNotification();
   if (empty($_POST['do']))
   {
    $this->info=$page->showNotifications();
   }
   else
   {
    switch ($_POST['do'])
    {
     case 'drop':
     if ($page->drop($_POST['num']))
     {
      echo ("Success!");
     }
     else
     {
      echo ("Failed!");
     }
     exit();
     break;
    }
   }
   break;
   default:
   $this->info=$this->showMain();
  }
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
  return <<<HTML
<p>Test</p>
HTML;
 }

 private function showProfile($user)
 {
		$xml=simplexml_load_string(file_get_contents(BBBASE.'/profile.def.xml'));
		$nav=new MomokoLITENavigation(null,'display=none');
		$nav->convertXmlObjToArr($xml,$form);
		$tbl=new DataBaseTable(DAL_TABLE_PRE.$form[0]['@attributes']['table'],DAL_DB_DEFAULT);
		$fields=$form[0]['@children'];
		
		$data=$tbl->getData(null,'uid='.$user,null,1);
		$data=$data->first();
		
		$html=file_get_contents(BBBASE.'/templates/profile.tpl.htm');
		$vars['profile']=null;
		if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->num == $user)
		{
			$vars['profile']="<a href=\"".BBROOT."/?action=editprofile&amp;u={$user}\">Edit this Profile</a>";
		}
		
		if ($data->uid)
		{		
		 $vars['sectiontitle']="View Profile: ".$data->user;
		 
		 $user=new MomokoUser($data->user);
		 $user=$user->get();
		 $settings=new DataBaseTable(DAL_TABLE_PRE.'bb_settings',DAL_DB_DEFAULT);
		 $settings=$settings->getData('display_name','name~'.$data->user,null,1);
		 $settings=$settings->first();
		 
		 $vars['profile'].="<table width=96% border=0 cellspacing=1 cellpadding=1>\n<tr><td rowspan=5 width=150><img src=\"".BBROOT."/?action=fetch_avi&u=".$user->num."\"></td><th colspan=2>{$settings->display_name}</th></tr>\n";
		 $c=0;
		 foreach ($fields as $field)
		 {
		  $vars['profile'].="<tr>";
		  if ($c < 5-1)
		  {
		   $vars['profile'].="<td align=right>{$field['@attributes']['label']}:</td>";
		  }
		  else
		  {
		   $vars['profile'].="<td align=right colspan=2>{$field['@attributes']['label']}:</td>";
		  }
		  if ($field['@attributes']['type'] == 'radio' || $field['@attributes']['type'] == 'selectbox')
		  {
		   foreach ($field['@children'] as $options)
		   {
			if ($data->$field['@attributes']['name'] == $options['@attributes']['value'])
			{
			 $vars['profile'].="<td>".$options['@text']."</td>";
			}
		   }
		  }
		  else
		  {
		   if ($data->$field['@attributes']['name'])
		   {
			$val=$data->$field['@attributes']['name'];
		   }
		   else
		   {
			$val="&nbsp;";
		   }
		   $vars['profile'].="<td>".$val."</td>";
		  }
		  $vars['profile'].="</tr>\n";
		  $c++;
		 }
		 $vars['profile'].="</table>";
		}
		else
		{
			$vars['sectiontitle']="User Profile is Empty";
		}

		$vh=new VictoriqueDataHandler($vars,$html);
		return $vh->setInfo();
 }
	
	private function editProfile($user,$data)
	{
		$xml=simplexml_load_string(file_get_contents(BBBASE.'/profile.def.xml'));
		$nav=new MomokoLITENavigation(null,'display=none');
		$nav->convertXmlObjToArr($xml,$form);
		$tbl=new DataBaseTable(DAL_TABLE_PRE.$form[0]['@attributes']['table'],DAL_DB_DEFAULT);
		$fields=$form[0]['@children'];
		
		if (@$data['send'])
		{
			$data['user']=$GLOBALS['USR']->name;
			$check=$tbl->getData('num','uid='.$user,null,1);
			$check=$check->first();
			if (@$check->num)
			{
				$data['num']=$check->num;
				$tbl->updateData($data);
			}
			else
			{
			 $data['uid']=$user;
				$tbl->putData($data);
			}
			header("Location: ".BBROOT."?action=viewprofile&u=".$user);
			exit();
		}
		else
		{
			$check=$tbl->getData(null,'uid='.$user,null,1);
			$data=$check->first();
			$vars['sectiontitle']="Edit Profile";
			$html=file_get_contents(BBBASE.'/templates/profile.tpl.htm');
			$vars['profile']="<div id=\"VictoriqueEditProfile\">\n<form action=\"#edit\" method=post>\n";
			foreach ($fields as $row)
			{
				$vars['profile'].="<div><label for=\"{$row['@attributes']['name']}\">{$row['@attributes']['label']}:</label> ";
				$col=$row['@attributes']['name'];
				switch ($row['@attributes']['type'])
				{
					case 'radio':
					foreach ($row['@children'] as $option)
					{
						if ((!@$data->$col && @$option['@attributes']['default'] == 'true') || (@$data->$col && $option['@attributes']['value'] == $data->$col))
						{
							$attr=" checked=checked value=".$option['@attributes']['value'];
						}
						else
						{
							$attr=" value=".$option['@attributes']['value'];
						}
						$vars['profile'].="<input type=radio id=\"{$row['@attributes']['name']}-{$option['@attributes']['value']}\" name=\"{$row['@attributes']['name']}\"{$attr}><label for=\"{$row['@attributes']['name']}-{$option['@attributes']['value']}\">{$option['@text']}</label> ";
					}
					break;
					case 'text':
					$vars['profile'].="<br>\n<textarea id=\"{$row['@attributes']['name']}\" name=\"{$row['@attributes']['name']}\">{$data->$col}</textarea>";
					break;
					case 'string':
					default:
					if (@$row['@attributes']['len'])
					{
						$attr=" maxlength=".$row['@attributes']['len'];
					}
					else
					{
						$attr=null;
					}
					$vars['profile'].="<input type=text{$attr} id=\"{$row['@attributes']['name']}\" name=\"{$row['@attributes']['name']}\" value=\"{$data->$col}\">";
				}
				$vars['profile'].="</div>\n";
			}
			$vars['profile'].="<div id=\"Main\"><input type=submit name=\"send\" value=\"Edit\"></div>\n</form>\n</div>";
			$vh=new VictoriqueDataHandler($vars,$html);
			return $vh->setInfo();
		}
	}

 private function showLogin()
 {
  $html=file_get_contents(BBBASE.'/templates/login.tpl.htm');
  $vars['sectiontitle']="Login";
  $vars['bbroot']=BBROOT;
  $vh=new VictoriqueDataHandler($vars,$html);
  return $vh->setInfo();
 }

 private function showMain()
 {
  $html=file_get_contents(BBBASE.'/templates/frontpage.tpl.htm');
  $vars['sectiontitle']="Bullitin Board";
  $vars['bbroot']=BBROOT;
  $vh=new VictoriqueDataHandler($vars,$html);

  if (preg_match_all("/<!-- SECTION:(?P<name>.*)\/\/ -->(?P<body>.*)<!-- \/\/SECTION -->/smU",$html,$matches) > 0) //Find Sections
  {
   $c=0;
   foreach($matches['name'] as $section)
   {
    $vh->parse($section,$matches['body'][$c]);
    $c++;
   } 
  }

  return $vh->setInfo();
 }
}

class VictoriqueDataHandler
{
 public $html;
 private $varlist=array();

 public function __construct(array $varlist,$html)
 {
  $this->html=$html;
  $this->varlist=$varlist;
 }

 public function parse($section,$source,$parent=0)
 {
  $rows=null;

  switch ($section)
  {
   case 'subforums':
   $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
   $data=$tbl->getData(null,'parent='.$parent,'order>ascending');
   if (preg_match("/<!-- ROW:forums\/\/ -->(?P<row>.*)<!-- \/\/ROW -->/smU",$source,$match) > 0) //Find row template
   {
    $row_tpl=$match['row'];
    while ($item=$data->next())
    {
     $rows.=$this->rowParse($item,$row_tpl);
    }
    $body=preg_replace("/<!-- ROW:forums\/\/ -->(.*)<!-- \/\/ROW -->/smU",$rows,$source);
   }
   break;
   case 'topics':
   $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_threads',DAL_DB_DEFAULT);
   $data=$tbl->getData(null,'parent='.$parent);
   if (preg_match("/<!-- ROW:topics\/\/ -->(?P<row>.*)<!-- \/\/ROW -->/smU",$source,$match) > 0) //Find row template
   {
    $row_tpl=$match['row'];
    while ($item=$data->next())
    {
     $row=$row_tpl;
     $rows.=$this->rowParse($item,$row_tpl);
    }
    $body=preg_replace("/<!-- ROW:topics\/\/ -->(.*)<!-- \/\/ROW -->/smU",trim($rows,"\n\r"),$source);
   }
   break;
   case 'post':
   $tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_posts',DAL_DB_DEFAULT);
   $data=$tbl->getData(null,'parent='.$parent);
   if (preg_match("/<!-- ROW:post\/\/ -->(?P<row>.*)<!-- \/\/ROW -->/smU",$source,$match) > 0) //Find row template
   {
    $row_tpl=$match['row'];
    while ($item=$data->next())
    {
     $row=$row_tpl;
     $rows.=$this->rowParse($item,$row_tpl);
    }
    $body=preg_replace("/<!-- ROW:post\/\/ -->(.*)<!-- \/\/ROW -->/smU",trim($rows,"\n\r"),$source);
   }
  }

  if (!@$rows)
  {
   $this->html=preg_replace("/<!-- SECTION:{$section}\/\/ -->(.*)<!-- \/\/SECTION -->/smU",'',$this->html);
  }
  else
  {
   $this->html=preg_replace("/<!-- SECTION:{$section}\/\/ -->(.*)<!-- \/\/SECTION -->/smU",$body,$this->html);
  }
 }

 public function setInfo()
 {
  $ch=new MomokoCommentHandler($this->varlist);
  $data=$ch->replace($this->html);

  
  if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$data,$match) > 0) //Find page title in $data
  {
   if (@$match['title'] && ($match['title'] != "[Blank]" && $match['title'] != "Blank")) //accept titles other than Blank and [Blank]
   {
    $info['title']=$match['title'];
   }
  }
  if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$data,$match) > 0) // Find page body in $data
  {
   $info['inner_body']=trim($match['body'],"\n\r"); //Replace the $body variable with just the page body found triming out the fat
  }
  $info['full_html']=$data;
  $info['type']='addon:victorique';
 
  return $info;
 }

 private function rowParse($item,$row_tpl)
 {
  $row=$row_tpl;
  if (preg_match_all("/<!-- ROW_VAR:(?P<col>.*?) -->/",$row_tpl,$matches) > 0)
  {
   foreach ($matches['col'] as $col)
   {
		if (isset($this->varlist['bbc_level']) && ($col == 'post' || $col == 'message'))
		{
		 $bbcode=new VictoriqueBBCode($this->varlist['bbc_level']);
		 $value=$bbcode->get($item->$col);
		}
		else
		{
		 $value=$item->$col;
		}
    $row=preg_replace("/<!-- ROW_VAR:{$col} -->/",$value,$row);
   }
  }
  if (preg_match_all("/\*{(?P<col>.*?)}/",$row_tpl,$matches) > 0)
  {
   foreach ($matches['col'] as $col)
   {
    $row=preg_replace("/\*{{$col}}/",strtolower(urlencode($item->$col)),$row);
   }
  }

  return $row;
 }
}

@require BBBASE.'/bbparser/stringparser_bbcode.class.php';

class VictoriqueBBCode
{
 public $level;
 private $parser;
 
 public function __construct($level)
 {
	$this->level=$level;
	$this->parser=new StringParser_BBCode();
	$this->runSetup();
 }
 
 public function get($text)
 {
	return $this->parser->parse($text);
 }
 
 private function runSetup()
 {
	$level=$this->level;
	if ($level != 'n')
	{
   $this->parser->addFilter (STRINGPARSER_FILTER_PRE, array(&$this,'universalBreaks'));

   $this->parser->addParser (array ('block', 'inline', 'link', 'listitem'), 'htmlspecialchars');
   $this->parser->addParser (array ('block', 'inline', 'link', 'listitem'), 'nl2br');
   $this->parser->addParser ('list', array(&$this,'strip'));

   $this->parser->addCode ('b', 'simple_replace', null, array ('start_tag' => '<strong>', 'end_tag' => '</strong>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->addCode ('i', 'simple_replace', null, array ('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->addCode ('u', 'simple_replace', null, array ('start_tag' => '<span style="text-decoration:underline">', 'end_tag' => '</span>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->addCode ('s', 'simple_replace', null, array ('start_tag' => '<span style="text-decoration:line-through">', 'end_tag' => '</span>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->addCode ('url', 'usecontent?', array(&$this,'url'), array ('usecontent_param' => 'default'), 'link', array ('listitem', 'block', 'inline'), array ('link'));
   $this->parser->addCode ('link', 'callback_replace_single', array(&$this,'url'), array (), 'link', array ('listitem', 'block', 'inline'), array ('link'));
   $this->parser->addCode ('img', 'usecontent', array(&$this,'image'), array (), 'image', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->addCode ('image', 'usecontent', array(&$this,'image'), array (), 'image', array ('listitem', 'block', 'inline', 'link'), array ());
   $this->parser->setOccurrenceType ('img', 'image');
   $this->parser->setOccurrenceType ('image', 'image');
   $this->parser->setMaxOccurrences ('image', 5);
   $this->parser->addCode ('list', 'callback_replace', array(&$this,'lists'), array(), 'list', array ('block', 'listitem'), array ());
   $this->parser->addCode ('*', 'simple_replace', null, array ('start_tag' => '<li>', 'end_tag' => '</li>'), 'listitem', array ('list'), array ());
   $this->parser->setCodeFlag ('*', 'closetag', BBCODE_CLOSETAG_OPTIONAL);
   $this->parser->setCodeFlag ('*', 'paragraphs', false);
   $this->parser->setCodeFlag ('list', 'paragraph_type', BBCODE_PARAGRAPH_BLOCK_ELEMENT);
   $this->parser->setCodeFlag ('list', 'opentag.before.newline', BBCODE_NEWLINE_DROP);
   $this->parser->setCodeFlag ('list', 'closetag.before.newline', BBCODE_NEWLINE_DROP);
   $this->parser->setRootParagraphHandling (true);
	 
	 if ($level == 'f')
	 {
		$this->parser->addCode('code','simple_replace',null,array('start_tag'=>"<pre><code>",'end_tag'=>"</code></pre>"),'block',array('block','inline'),array('quote'));
                $this->parser->addCode('size','callback_replace',array(&$this,'size'),array(),'inline',array('block','listitem','inline','link'),array());
                $this->parser->addCode('color','callback_replace',array(&$this,'color'),array(),'inline',array('block','listitem','inline','link'),array());
		
		$code_table=new DataBaseTable(DAL_TABLE_PRE.'bb_bbcodes',DAL_DB_DEFAULT);
		$codes=$code_table->getData();
		while ($code=$codes->next())
		{
		 $allowed['yes']=explode(',',$code->allowed_in);
		 $allowed['no']=explode(',',$code->not_allowed_in);
     $this->parser->addCode ($code->bb_tag, 'simple_replace', null, array ('start_tag' => $code->start_tag, 'end_tag' => $code->end_tag), $code->type, $allowed['yes'], $allowed['no']);
		}
	 }
	}
 }
 
 public function universalBreaks($text)
 {
	return preg_replace ("/\015\012|\015|\012/", "\n", $text);
 }
 
 public function strip($text)
 {
	return preg_replace ("/[^\n]/", '', $text);
 }
 
 public function lists($action,$attributes,$content,$node_object)
 {
	if ($action == 'validate')
	{
	 return true;
	}
	else
	{
	 if (isset($attributes['default']))
	 {
		return "<ol type=".$attributes['default'].">".$content."</ol>";
	 }
	 else
	 {
		return "<ul>".$content."</ul>";
	 }
	}
 }
 
 public function size($action,$attributes,$content,$node_object)
 {
	if ($action == 'validate')
	{
	 return true;
	}
	else
	{
	 if (isset($attributes['default']))
	 {
		return "<span style=\"font-size:".$attributes['default']."em\">".$content."</span>";
	 }
	 else
	 {
		return $content;
	 }
	}
 }
 
 public function color($action,$attributes,$content,$node_object)
 {
	if ($action == 'validate')
	{
	 return true;
	}
	else
	{
	 if (isset($attributes['default']))
	 {
		return "<span style=\"color:".$attributes['default']."\">".$content."</span>";
	 }
	 else
	 {
		return $content;
	 }
	}
 }
 
 public function url($action, $attributes, $content, $params, $node_object)
 {
	if (!isset ($attributes['default']))
	{
   $url = $content;
   $text = htmlspecialchars ($content);
  }
	else
	{
   $url = $attributes['default'];
   $text = $content;
  }
  if ($action == 'validate')
	{
   if (substr ($url, 0, 5) == 'data:' || substr ($url, 0, 5) == 'file:' || substr ($url, 0, 11) == 'javascript:' || substr ($url, 0, 4) == 'jar:')
	 {
    return false;
   }
   return true;
  }
  return '<a href="'.htmlspecialchars ($url).'">'.$text.'</a>';
 }
 
 public function image($action, $attributes, $content, $params, $node_object)
 {
	if ($action == 'validate')
	{
   if (substr ($content, 0, 5) == 'file:' || substr ($content, 0, 11) == 'javascript:' || substr ($content, 0, 4) == 'jar:')
	 {
    return false;
   }
   return true;
  }
	
	$attr="border=0";
	if (isset($attributes['w']))
	{
	 $attr.=" width=".$attributes['w'];
	}
	if (isset($attributes['h']))
	{
	 $attr.=" height=".$attributes['h'];
	}
	if (isset($attributes['title']))
	{
	 $attr.=" alt=".$attributes['title']." title=". $attributes['title'];
	}
	else
	{
	 $name=pathinfo($content,PATHINFO_BASENAME);
	 $attr.=" alt=".$name;
	}
  return '<img src="'.htmlspecialchars($content).'" {$attr}">';
 }
}

class VictoriqueNotification implements MomokoLITEObject
{
 public $path;
 public $data;
 private $table;
 private $info=array();
 private $new_data=array();

 public function __construct($path=null)
 {
  $this->table=new DataBaseTable(DAL_TABLE_PRE.'bb_notifications',DAL_DB_DEFAULT);
  if (!empty($path)) //If a notification is selected return it
  {
   $this->path=$path;
   $this->data=$this->table->getData(null,'num='.$path,null,1);
  }
  else //return a list
  {
   $this->data=$this->table->getData(null,'to='.$GLOBALS['USR']->num);
  }

  $this->readInfo();
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  elseif (array_key_exists($key,$this->new_data))
  {
   return $this->new_data[$key];
  }
  else
  {
   return $this->data->$key;
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
   return $this->new_data[$key]=$value;
  }
 }

 public function get()
 {
  return $this->data;
 }

 public function put(array $sendto,$from,$type,$link)
 {
  $sendnums=array();
  $data['from']=$from;
  $data['type']=$type;
  $data['link']=$link;

  foreach ($sendto as $to)
  {
   if ($to != $from)
   {
    $data['to']=$to;
    $sendnums[]=$this->table->putData($data);
   }
  }

  return $sendnums;
 }

 public function drop($num)
 {
  if (is_array($num))
  {
   foreach ($num as $id)
   {
    $data['num']=$id;
    $okay=$this->table->removeData($data);
   }
  }
  else
  {
   $data['num']=$num;
   $okay=$this->table->removeData($data);
  }

  return $okay;
 }

 public function showNotifications()
 {
  return $this->info;
 }

 private function readInfo()
 {
  $info=array();
  if (!empty($this->path))
  {
   $info['title']="Notifications: Show Details";
  }
  else
  {
   $info['title']="Notifications";
   $list="<div id=\"notifications\">";
   while ($note=$this->data->next())
   {
    $r=new MomokoUser();
    $user=$r->getByID($note->from);
    $user_link="<a href=\"?action=viewporfile&u=".$user->num."\">".$user->name."</a>";
    switch ($note->type)
    {
     case 'forum-reply':
     $message=$user_link." has replied to a topic you subscribed to!";
    }
    $list.="<div id=\"".$note->num."Message\" class=\"".$note->type."\">".$message."<div class=\"note_link\"><a href=\"".$note->link."\">View</a></div><div id=\"".$note->num."\" class=\"remove\"><a href=\"#remove\">Remove</a></div></div>\n";
   }
   $list.="</div>";
   $bbroot=BBROOT;
   $info['inner_body']=<<<HTML
<script language="javascript" type="text/javascript">
$(function(){
	$(".remove a").button().click(function(event){
		event.preventDefault();
		var id=$(this).parent().attr('id');
		$.post("{$bbroot}?action=notifications",{ 'do': "drop", num: id },function(data){
			if (data == "Success!"){
				$("div#"+id+"Message").remove();
			}
			else{
				alert(data);
			}
		});
	});
});
</script>
<h2>Notifications</h2>
{$list}
HTML;
  }

  $this->info=$info;
  return true;
 }
}

class VictoriquePM implements MomokoLITEObject
{
 public $path;
 public $data;
 private $table;
 private $info=array();
 private $new_data=array();

 public function __construct($path)
 {
  $this->path=$path;
  $this->table=new DataBaseTable(DAL_TABLE_PRE.'bb_messages',DAL_DB_DEFAULT);
  $this->data=$this->table->getData(null,'num='.$path,null,1);
  $this->info=$this->readInfo();
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  elseif (array_key_exists($key,$this->new_data))
  {
   return $this->new_data[$key];
  }
  else
  {
   return $this->data->$key;
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
   return $this->new_data[$key]=$value;
  }
 }

 public function get()
 {
  return $this->data;
 }

 public function put(array $sendto,$from,$subject,$message)
 {
  $sendnums=array();
  $data['from']=$from;
  $data['subject']=$subject;
  $data['message']=$message;

  foreach ($sendto as $to)
  {
   if ($to != $from)
   {
    $data['to']=$to;
    $sendnums[]=$this->table->putData($data);
   }
  }

  return $sendnums;
 }

 private function readInfo()
 {
  $info=array();

  return $info;
 }
}
