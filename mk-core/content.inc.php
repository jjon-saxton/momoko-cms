<?php
require_once $GLOBALS['SET']['basedir'].'/mk-core/simple_html_dom.php';

class MomokoNavigation
{
 public $user;
 public $options=array();
 public $map=array();
 private $table;

 public function __construct($user,$options)
 {
  $this->user=$user;
  parse_str($options,$this->options);
  $this->table=new DataBaseTable('content');
  $this->map=$this->scanContent();
 }
 
 public function getModule($format='html')
 {
  switch ($format)
  {
   //Other format cases?
   //break;
   case 'html':
   switch ($this->options['display'])
   {
    case 'menu':
    $text="<ul id=\"NavList\" class=\"topnav nav navbar-nav\">".$this->getListItems($this->map)."\n</ul>";
    break;
    case 'list':
    $text="<ul id=\"MapList\" class=\"sitemap\">\n".$this->getListItems($this->map)."\n</ul>";
    break;
    case'simple':
    default:
    $text=$this->getListItems($this->map);
   }
  }
  
  return $text;
 }
 
 private function scanContent($parent=0)
 {
  $pinfo=$this->table->getData("num:'= {$parent}'",array('num','title'),null,1);
  $has_info=$pinfo->rowCount();
  if ($has_info)
  {
   $parent=$pinfo->fetch(PDO::FETCH_ASSOC);
   $query=$this->table->getData("parent:'= {$parent['num']}'",null,"order");
   $pfolder=urlencode($parent['title'])."/";
  }
  else
  {
   $query=$this->table->getData("parent:'= {$parent}'",null,"order");
   $pfolder=null;
  }
  $content=array();
  while ($data=$query->fetch(PDO::FETCH_ASSOC))
  {
   $is_parent=$this->table->getData("parent:'= {$data['num']}'",array('num'), null, 1);
   $has_child=$is_parent->rowCount();
   if ($has_child)
   {
    $content[]=array('id'=>$data['num'],'title'=>$data['title'],'href'=>"/".$pfolder.urlencode($data['title']).".htm",'children'=>$this->scanContent($data['num']));
   }
   elseif ($data['type'] == "page" && ($data['status'] != "cloaked" && $data['status'] != "locked"))
   {
    $content[]=array('id'=>$data['num'],'title'=>$data['title'],'href'=>"/".$pfolder.urlencode($data['title']).".htm");
   }
  }
  
  return $content;
 }
 
 private function getListItems(array $map)
 {
  $text=null;
  foreach ($map as $item)
  {
   if ($GLOBALS['SET']['rewrite'] == true)
   {
    $href="//".$GLOALS['SET']['baseuri']."/".$item['href'];
   }
   else
   {
    $href="//".$GLOBALS['SET']['baseuri']."/?p=".$item['id'];
   }
   if (is_array($item['children']))
   {
    $text.="<li id=\"{$item['id']}\" class=\"category dropdown\"><a href=\"{$href}\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\">{$item['title']}</a>\n";
    $text.="<ul id=\"{$item['id']}\" class=\"subnav dropdown-menu\" role=\"menu\">\n".$this->getListItems($item['children'])."\n</ul>\n</li>\n";
   }
   else
   {
    $text.="<li id=\"{$item['id']}\"><a href=\"{$href}\">{$item['title']}</a></li>\n";
   }
  }

  return $text;
 }
 
 public function put($map=null)
 {
  if (!$map)
  {
   $map=$this->map;
  }
  
  //TODO parse map and save to database
  
  return $map;
 }
 
 public function reOrderByHTML($raw_map)
 {
  $html=str_get_html($raw_map);
  foreach ($html->find('ul') as $map)
  {
   $data['order']=1;
   foreach ($map->find('li') as $item)
   {
    $data['num']=$item->id;
    try
    {
     $update=$this->table->updateData($data);
    }
    catch (Exception $err)
    {
     trigger_error("Caught exception '".$err->getMessage()."' while attempting to re-order site map.",E_USER_WARNING);
    }
    $data['order']++;
   }
  }
 }
}

class MomokoNews implements MomokoObject
{
	public $user;
	public $news_list;
	public $info;
	private $table;
	
	public function __construct($user)
	{
  $this->user=$user;
  parse_str($options,$this->options);
  $this->table=new DataBaseTable('content');
  $query=$this->table->getData("type:'post'");
  $this->news_list=$query->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function __get($key)
	{
	 return $this->info[$key];
	}
	
	public function __set($key,$var)
	{
	 //TODO Set a new key in $this->info
	}
	
	public function getPostByHeadline($title)
	{
	 $query=$this->table->getData("title:'".$title."'",array('num'),null,1);
	 $data=$query->fetch(PDO::FETCH_ASSOC);
	 $this->getPostByID($data['num']);
	}
	
	public function getPostByID($num)
	{
	 $query=$this->table->getData("num:'".$num."'",null,null,1);
	 $info=$query->fetch(PDO::FETCH_ASSOC);
	 $post_created=strtotime($info['date_created']);
	 if ($info['date_modified'])
	 {
	  $post_modified=strtotime($info['date_modified']);
	 }
	 $date=date($GLOBALS['USR']->longdateformat,$post_created);
	 $info['inner_body']=<<<HTML
<h2>{$info['title']}</h2>
<div class="date">{$date}</div>
<artcile class="box">{$info['text']}</article>
HTML;
  $info['full_html']="<html>\n<body>\n{$this->info['inner_body']}\n</body>\n</html>";
  $this->info=$info;
	}
	
	public function get()
	{
	 //TODO Get something?
	}

 public function put($data,$prepend=true)
 {
  $key=strtotime($data['date'].$data['time']);
  $data['update']=date("d M Y H:i:s",$key);
  unset($data['date'],$data['time']);
  $array[$key]=$data;
  momoko_changes($GLOBALS['USR'],'added',$this); //Log changes based on settings
  if ($prepend == TRUE)
  {
   return array_merge($array,$this->getModule('array'));
  }
  else
  {
   return array_merge($this->getModule('array'),$array);
  }
 }

 public function update($data,$key)
 {
  $ndate=strtotime($data['date'].$data['time']);
  $data['update']=date("d M Y H:i:s",$ndate);
  unset($data['date'],$data['time']);
  $array=$this->getModule('array');
  $array[$key]=$data;
  momoko_changes($GLOBALS['USR'],'updated',$this); //Log the change
  return $array;
 }

 public function drop($key)
 {
  $array=$this->getModule('array');
  unset($array[$key]);
  momoko_changes($GLOBALS['USR'],'deleted',$this);
  return $array;
 }

 public function convertNewsToXMLObj($arr, &$obj)
 {
  foreach ($arr as $item)
  {
   $node=$obj->addChild('item');
   foreach($item as $name=>$text)
   {
    if ($name == 'article')
    {
     $subnode=$node->addChild($name);
     $subnode->addCData($text);
    }
    elseif ($name != 'date')
    {
     $subnode=$node->addChild($name,$text);
    }
   }
  }
  return;
 }

 public function write(array $arr)
 {
  $xmlstr=<<<XML
<?xml version="1.0"?>
<reel>
</reel>
XML;
  $xmlobj=new SimpleXMLExtended($xmlstr);
  $this->convertNewsToXmlObj($arr,$xmlobj);
  $dom=new DOMDocument('1.0'); //Folowing lines are used to process XML in easier to read format, for anyone who cares
  $dom->preserveWhiteSpace=false;
  $dom->formatOutput=true;
  $dom->loadXML($xmlobj->asXML());
  $data=$dom->saveXML();
  if (file_put_contents($GLOBALS['SET']['pagedir'].'/news.xml',$data)) // Replace with actual path!
  {
   momoko_changes($GLOBALS['USR'],'updated',$this,"Changes were written to {$GLOBALS['SET']->pagedir}/news.xml!");
   return true;
  }
  else
  {
   trigger_error("Could not write to news.xml, check permissions!",E_USER_WARNING);
   return false;
  }
 }
	
 private function generateUUID($prefix=null,$chars)
 {
  $chars=md5($chars);
  $uuid=substr($chars,0,8) . '-';
  $uuid.=substr($chars,8,4) . '-';
  $uuid.=substr($chars,12,4) . '-';
  $uuid.=substr($chars,16,4) . '-';
  $uuid.=substr($chars,20,12);
  return $prefix.$uuid;
 }
}

class MomokoAttachment implements MomokoObject
{
 private $table;
 private $page;
 private $info=array();
 
 public function __construct($path, array $additional_vars=null)
 {
  $table=new DataBaseTable('content');
  
  $title=basename($path);
  $category_tree=trim(dirname($path),"./");
  $parent=basename($category_tree);
  
  if ($title == NULL)
  {
   $where="status:'public'";
  }
  else
  {
   $where="title:'{$title}'";
  }
  if ($parent != NULL)
  {
   $pq=$table->getData("title:'{$parent}'",array('num'),null,1);
   $pinfo=$pg->fetch(PDO::FETCH_ASSOC);
   $where.=",parent:'{$pinfo['num']}'";
  }
  $query=$table->getData($where,null,null,1);
  $this->table=$table;
  $this->info=$query->fetch();
 }
 
 public function __get($var)
 {
  if (array_key_exists($var,$this->info))
  {
   return $this->info[$var];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  $this->info[$key]=$value;
  return true;
 }
 
 public function fetchByID($num)
 {
  $query=$this->table->getData("num:'{$num}'",null,null,1);
  $this->info=$query->fetch(PDO::FETCH_ASSOC);
 }
 
 public function fetchByLink($uri)
 {
  $query=$this->table->getData("link:'{$uri}'",null,null,1);
  $this->info=$query->fetch(PDO::FETCH_ASSOC);
 }
 
 public function put($data)
 {
  if (@$data['file'])
  {
   $file=$_FILES['file'];
   //TODO need to work with uploaded file before adding or updating attachment information in database
  }
  else
  {
   $findparents=$this->table->getData("type:'page'",array('num','title'));
   if ($this->info['parent'] == 0)
   {
    $parent_opts="<option selected=selected value=0>-- Top Level --</option>";
   }
   else
   {
    $parent_opts="<option value=0>-- Top Level --</option>";
   }
   while ($parent=$findparents->fetch(PDO::FETCH_ASSOC))
   {
    if ($parent['num'] != $this->info['num'])
    {
     if ($parent['num'] == $this->info['parent'])
     {
      $parent_opts.="<option selected=selected value={$parent['num']}>{$parent['title']}</option>";
     }
     else
     {
      $parent_opts.="<option value={$parent['num']}>{$parent['title']}</option>";
     }
    }
   }
   
   $now=date("Y-m-d H:i:s");
   if ($_GET['action'] == 'new')
   {
    $hiddenvals=<<<HTML
<input type=hidden name="type" value="attachment">
<input type=hidden name="date_created" value="{$now}">
<input type=hidden name="author" value="{$GLOBALS['USR']->num}">
HTML;
   }
   else
   {
    $hiddenvals=<<<HTML
<input type=hidden name="num" value="{$this->info['num']}">
<input type=hidden name="date_modified" value="{$now}">
HTML;
   }
   
   $info['title']="Edit Attachment: ".$this->title;
   $info['inner_body']=/*TODO add image resampling fields -->*/<<<HTML
<script language="javascript">
$(function(){
 if ($("select#status").val() == "private"){
  $("input#private").removeAttr('disabled');
 }
 
 $("select#status").change(function(){
  if ($("select#status").val() == "private"){
   $("input#private").removeAttr('disabled');
  }
  else{
   $("input#private").attr('disabled','disabled');
  }
 });
});
</script>
<form method=post>
{$hiddenvals}
<h2>Edit Attachment: <input type=text name="title" placeholder="Filename" id="title" value="{$this->title}"></h2>
<div id="PageEditor">
<div id="PageProps">
<ul class="noindent nobullet">
<li><label for="parent">Parent Page:</label> <select id="parent" name="parent">{$parent_opts}</select></li>
<li><label for="status">Attachment Status:</label> <select id="status" name="status">{$status_opts}</select></li>
<li><label for="private">Groups that have access:</label> <input type=text id="private" name="has_access" disabled=disabled value="editor,members"></li>
</ul>
</div>
<div id="PageSave" align=center>
<button type=submit name="save" value="1">Save</button>
</div>
</div>
</form>
HTML;
   $this->info=$info;
   return true;
  }
 }

 public function get()
 {
  $authorized=$this->hasAccess();

  if ($authorized && $this->text)
  {
   return $this->text;
  }
  elseif (!$authorized)
  {
   $page=new MomokoError("403 Forbidden");
   return $page->full_html;
  }
  else
  {
   $page=new MomokoError("404 Not Found");
   return $page->full_html;
  }
 }
 
 public function drop()
 {
  $info['title']="Delete Attachment: ".$this->info['title'];
  if ($_POST['drop'])
  {
   $data['num']=$this->info['num'];
   $file=$GLOBALS['SET']['basedir'].'/'.preg_replace("%http://".$GLOBALS['SET']['baseuri']."/%",'',$this->link);
   if (unlink($file))
   {
    try
    {
     $delete=$this->table->deleteData($data);
    }
    catch (Exception $err)
    {
     trigger_error("Caught exception '{$err->getMessage()}' while attempting to remove a page or post.",E_USER_WARNING);
    }
   }
   else
   {
    trigger_error("Could not delete attachment at '{$file}'",E_USER_WARNING);
   }
   
   if ($delete)
   {
    $info['inner_body']=<<<HTML
<div id="DeleteAttachment" class="message box">
<h3 class="message title">Attachment Gone</h3>
<p>The attachment you selected was deleted! You may now <a href="//{$GLOBALS['SET']['baseuri']}/">return</a> to your home page!</p>
</div>
HTML;
   }
   else
   {
    $info['inner_body']=<<<HTML
<div id="DeleteAttachment" class="message error box">
<h3 class="error title">Attachment Still there</h3>
<p>Could not delete the selected attachment! Please contact your site administrator!</p>
</div>
HTML;
   }
  }
  else
  {
   $info['inner_body']=<<<HTML
<form method=post>
<div id="DeletePage" class="message box">
<h3 class="message confirmation title">Do you wish to delete this attachment?</h3>
<p>You are about to delete an attachment. Content in MomoKO cannot be retrieved once it is deleted. If you would like to simply make the attachment private without removing it, there are several options for you in the attachment's edit page under 'status'.</p>
<p class="confirmation question">Are you sure you want to delete '{$this->info['title']}'?</p>
<div class="confirmation buttons"><button class="answer" type=submit name="drop" id="true" value="1">Yes</button> <button class="answer" id="false">No</button></div>
</div>
</form>
HTML;
  }
  
  $this->info=$info;
  return true;
 }
}

class MomokoPage implements MomokoObject
{
 private $table;
 private $page;
 private $info=array();
 
 public function __construct($path, array $additional_vars=null)
 {
  $table=new DataBaseTable('content');
  
  $title=basename($path);
  $category_tree=trim(dirname($path),"./");
  $parent=basename($category_tree);
  
  if ($title == NULL)
  {
   $where="status:'public'";
  }
  else
  {
   $where="title:'{$title}'";
  }
  if ($parent != NULL)
  {
   $pq=$table->getData("title:'{$parent}'",array('num'),null,1);
   $pinfo=$pg->fetch(PDO::FETCH_ASSOC);
   $where.=",parent:'{$pinfo['num']}'";
  }
  $query=$table->getData($where,null,null,1);
  $this->table=$table;
  $this->info=$query->fetch();

  $body=$this->get();
  $vars=$this->setVars($additional_vars);
  $ch=new MomokoVariableHandler($vars);
  $this->inner_body=$ch->replace($body);
 }
 
 public function __get($var)
 {
  if (array_key_exists($var,$this->info))
  {
   return $this->info[$var];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  $this->info[$key]=$value;
  return true;
 }
 
 public function fetchByID($num)
 {
  $query=$this->table->getData("num:'{$num}'",null,null,1);
  $this->info=$query->fetch(PDO::FETCH_ASSOC);

  $body=$this->get();
  $vars=$this->setVars($additional_vars);
  $ch=new MomokoVariableHandler($vars);
  $this->inner_body=$ch->replace($body);
 }
 
 public function put($data)
 {
  if (@$data['text'])
  {
   if (($_GET['action'] == 'edit' && $data['num']) && $update=$this->table->updateData($data))
   {
    header("Location: http://{$GLOBALS['SET']['baseuri']}/?p={$data['num']}");
    exit();
   }
   elseif ($_GET['action'] == 'new' && $new=$this->table->putData($data))
   {
    header("Location: http://{$GLOBALS['SET']['baseuri']}/?p={$new}");
    exit();
   }
   else
   {
    //TODO on failure
   }
  }
  else
  {
   $findparents=$this->table->getData("type:'page'",array('num','title'));
   if ($this->info['parent'] == 0)
   {
    $parent_opts="<option selected=selected value=0>-- Top Level --</option>";
   }
   else
   {
    $parent_opts="<option value=0>-- Top Level --</option>";
   }
   while ($parent=$findparents->fetch(PDO::FETCH_ASSOC))
   {
    if ($parent['num'] != $this->info['num'])
    {
     if ($parent['num'] == $this->info['parent'])
     {
      $parent_opts.="<option selected=selected value={$parent['num']}>{$parent['title']}</option>";
     }
     else
     {
      $parent_opts.="<option value={$parent['num']}>{$parent['title']}</option>";
     }
    }
   }
   $statuses=array('public'=>"Public",'cloaked'=>"Hidden From Navigation",'private'=>"Private",'locked'=>"In Production");
   $status_opts=null;
   foreach($statuses as $value=>$name)
   {
    if ($value == $this->status)
    {
     $status_opts.="<option selected=selected value=\"{$value}\">{$name}</option>\n";
    }
    else
    {
     $status_opts.="<option value=\"{$value}\">{$name}</option>\n";
    }
   }
   
   if ($_GET['content'])
   {
    $type=$_GET['content'];
   }
   else
   {
    $type="page";
   }
   
   $now=date("Y-m-d H:i:s");
   if ($_GET['action'] == 'new')
   {
    $hiddenvals=<<<HTML
<input type=hidden name="type" value="{$type}">
<input type=hidden name="date_created" value="{$now}">
<input type=hidden name="author" value="{$GLOBALS['USR']->num}">
<input type=hidden name="mime_type" value="text/html">
HTML;
   }
   else
   {
    $hiddenvals=<<<HTML
<input type=hidden name="num" value="{$this->info['num']}">
<input type=hidden name="date_modified" value="{$now}">
HTML;
   }
   $type_links=null;
   if ($type == 'page')
   {
    $formats='["p","Normal"], ["h2","Header 2"], ["h3","Header 3"], ["h4","Header 4"], ["pre","Preformatted"]';
    if ($_GET['action'] == 'new')
    {
     $type_links="<div id=\"type_select\"><input type=\"radio\" id=\"page\" checked=checked name=\"type\" value=\"page\"><label for=\"page\">Page</label> <input type=\"radio\" id=\"post\" name=\"type\" value=\"post\"><label for=\"post\">Post</label></div>";
    }
   }
   else
   {
    $formats='["p","Normal"], ["h3","Header 3"], ["h4","Header 4"]';
    if ($_GET['action'] == 'new')
    {
     $type_links="<div id=\"type_select\"><input type=\"radio\" id=\"t1\" name=\"type\" value=\"page\"><label for=\"t1\">Page</label> <input type=\"radio\" id=\"t2\" checked=checked name=\"type\" value=\"post\"><label for=\"t2\">Post</label></div>";
    }
   }
   $type=ucwords($type);
   $chooser=null;
   if ($_GET['action'] == "new")
   {
    $chooser=<<<TXT
	$("div#modal").load("//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&action=gethref&ajax=1&origin=new",function(){
	 $("#vtabs").tabs().addClass('ui-tabs-vertical ui-helper-clearfix');
	 }).on('mouseenter',"div.selectable",function(){
		 $(this).addClass("ui-state-hover");
		 }).on('mouseleave',"div.selectable",function(){
			$(this).removeClass("ui-state-hover");
		 }).on('click',"div.selectable",function(){
		   var location=$(this).find("a#location").attr('href');
           $.get(location,function(html){
		    $("#pagebody").html(html);
            $("div.jqte_editor").html(html);
           })
		   $("div#modal").dialog('close');
	    });
	$("div#modal").dialog({
		 height: 500,
		 width: 800,
		 modal: true,
		 title: "New From...",
         close: function(){
            $(this).empty(); //empty the dialog box so it may be filled by ajax again later.
            $(this).find('*').addBack().off(); //destroy all even handlers so they may be re-used with new data later.
         }
	});
TXT;
   }
   
   $info['title']="Edit {$type}: ".$this->title;
   $info['inner_body']=<<<HTML
<script language="javascript">
$(function(){
 {$chooser}
 if ($("select#status").val() == "private"){
  $("input#private").removeAttr('disabled');
 }
  
 $("textarea").jqte({
  dashuri:"//{$GLOBALS['SET']['baseuri']}/mk-dash.php",
  color:false,
  strike:false,
  formats:[{$formats}],
  fsize:false,
  placeholder: "Page body..."
 });
 $("div#PageEditor").tabs();
 
 $("#type_select input:radio").change(function(){
  window.location="?action=new&content="+$(this).val();
 });
 $("#type_select").css("text-align","center").buttonset();
 $("#type_select input:radio").change(function(){
  console.debug($(this).val());
  //window.location="?action=new&content="+$(this).val();
 });
 
 $("select#status").change(function(){
  if ($("select#status").val() == "private"){
   $("input#private").removeAttr('disabled');
  }
  else{
   $("input#private").attr('disabled','disabled');
  }
 });
});
</script>
<form method=post>
{$hiddenvals}
<h2>Edit {$type}: <input type=text name="title" placeholder="{$type} Title" id="title" value="{$this->title}"></h2>
{$type_links}
<div id="PageEditor">
<ul id="tabs">
<li><a href="#PageBody">Body</a></li>
<li><a href="#PageProps">Properties</a></li>
</ul>
<div id="PageBody">
<textarea id="pagebody" name="text">
{$this->inner_body}
</textarea>
</div>
HTML;
  if ($type == 'Page')
  {
   $info['inner_body'].=<<<HTML
<div id="PageProps">
<ul class="noindent nobullet">
<li><label for="parent">Parent Page:</label> <select id="parent" name="parent">{$parent_opts}</select></li>
<li><label for="status">Page Status:</label> <select id="status" name="status">{$status_opts}</select></li>
<li><label for="private">Groups that have access:</label> <input type=text id="private" name="has_access" disabled=disabled value="editor,members"></li>
</ul>
</div>
HTML;
   }
   elseif ($type == 'Post')
   {
    $now_h=date($GLOBALS['USR']->shortdateformat);
    unset($statuses['cloaked'],$statuses['private']);
    $status_opts=null;
    foreach($statuses as $value=>$name)
    {
     if ($value == $this->status)
     {
      $status_opts.="<option selected=selected value=\"{$value}\">{$name}</option>\n";
     }
     else
     {
      $status_opts.="<option value=\"{$value}\">{$name}</option>\n";
     }
    }
    $info['inner_body'].=<<<HTML
<div id="PageProps">
<input type=hidden name="parent" value="0">
<ul class="noindent nobullet">
<li>Post Date: {$now_h}</li>
<li>Post Author: {$GLOBALS['USR']->name}</li>
<li><label for="status">Post Status</lable> <select id="status" name="status">{$status_opts}</select>
</ul>
</div>
HTML;
   }
   $info['inner_body'].=<<<HTML
<div id="PageSave" align=center>
<button type=submit name="save" value="1">Save</button>
</div>
</div>
</form>
HTML;
   $this->info=$info;
   return true;
  }
 }

 public function get()
 {
  $authorized=$this->hasAccess();

  if ($authorized && $this->text)
  {
   return $this->text;
  }
  elseif (!$authorized)
  {
   $page=new MomokoError("403 Forbidden");
   return $page->inner_body;
  }
  else
  {
   $page=new MomokoError("404 Not Found");
   return $page->inner_body;
  }
 }
 
 public function drop()
 {
  $info['title']="Delete Page: ".$this->info['title'];
  if ($_POST['drop'])
  {
   $data['num']=$this->info['num'];
   try
   {
    $delete=$this->table->deleteData($data);
   }
   catch (Exception $err)
   {
    trigger_error("Caught exception '{$err->getMessage()}' while attempting to remove a page or post.",E_USER_WARNING);
   }
   
   if ($delete)
   {
    $info['inner_body']=<<<HTML
<div id="DeletePage" class="message box">
<h3 class="message title">Page Gone</h3>
<p>The page you selected was removed! You may now <a href="//{$GLOBALS['SET']['baseuri']}/">return</a> to your home page!</p>
</div>
HTML;
   }
   else
   {
    $info['inner_body']=<<<HTML
<div id="DeletePage" class="message error box">
<h3 class="error title">Page Still there</h3>
<p>Could not delete the selected page! Please contact your site administrator!</p>
</div>
HTML;
   }
  }
  else
  {
   $info['inner_body']=<<<HTML
<form method=post>
<div id="DeletePage" class="message box">
<h3 class="message confirmation title">Do you wish to delete this page?</h3>
<p>You are about to delete a page or post. Content in MomoKO cannot be retrieved once it is deleted. If you would like to hide a page from navigation without removing it, there are several options for you in the page's properties tab under 'status'.</p>
<p class="confirmation question">Are you sure you want to delete '{$this->info['title']}'?</p>
<div class="confirmation buttons"><button class="answer" type=submit name="drop" id="true" value="1">Yes</button> <button class="answer" id="false">No</button></div>
</div>
</form>
HTML;
  }
  
  $this->info=$info;
  return true;
 }
 
 private function setVars(array $vars=null)
 {
   if (empty($vars))
   {
    $vars=array();
   }
   
   $vars['siteroot']=$GLOBALS['SET']['baseuri'];
   
   return $vars;
 }
 
 private function hasAccess()
 {
  $grouplist=explode(",",$this->has_access);
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   return true;
  }
  elseif ($this->status != 'private')
  {
   switch ($this->status)
   {
    case 'locked':
    if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
    {
     return true;
    }
    else
    {
     return false;
    }
    break;
    case 'cloaked':
    case 'public':
    default:
    return true;
   }
  }
  else
  {
   foreach ($grouplist as $group)
   {
    if ($GLOBALS['USR']->inGroup($group))
    {
     return true;
    }
   }
  }
  return false;
 }
}

class MomokoError implements MomokoObject
{
 public $name;
 private $page;
 private $inner_body;
 private $error_msg;

 public function __construct($title,$msg=null,array $additional_vars=null)
 {
  $this->page=new MomokoPage($title);
  $this->error_msg=$msg;
  header("Status: ".$this->page->title);
  header("HTTP/1.0 ".$this->page->title);

  $body=$this->page->inner_body;
  $vars=$this->setVars($additional_vars);
  $ch=new MomokoVariableHandler($vars);
  $this->inner_body=$ch->replace($body);
 }

 public function __get($key)
 {
  if (@$this->$key)
  {
   return $this->$key;
  }
  elseif ($this->page->$key)
  {
   return $this->page->$key;
  }
  else
  {
   return false;
  }
 }

 public function __set($key,$value)
 {
  return $this->page->$key=$value;
 }
 
 public function get()
 {
  return $this->page->get();
 }

 private function setVars($vars)
 {
  $vars['error_msg']=$this->error_msg;
  $vars['admin_email']=$GLOBALS['SET']['support_email'];
  $vars['forgot_password']='http://'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].ADDINROOT.'passreset/';
  return $vars;
 }
}

class MomokoTemplate implements MomokoObject, MomokoPageObject
{
 private $cur_path;
 private $template;
 private $info=array();
 
 public function __construct($path)
 {
  $this->cur_path=$path;
  $this->info=$this->readInfo();
 }
 
 public function __get($var)
 {
  if (array_key_exists($var,$this->info))
  {
   return $this->info[$var];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  $this->info[$key]=$value;
  return true;
 }
 
 public function get()
 {
  if ((pathinfo($this->cur_path,PATHINFO_EXTENSION) == 'html' || pathinfo($this->cur_path,PATHINFO_EXTENSION) == 'htm') && file_exists($GLOBALS['SET']->basedir.$this->cur_path))
  {
   $this->template=$this->cur_path;
  }
  elseif (file_exists($GLOBALS['SET']['basedir'].$this->cur_path.'/default.tpl.html'))
  {
   $this->template=$this->cur_path.'/default.tpl.html';
  }
  else
  {
   $this->template=TEMPLATEPATH;
  }
  
  return file_get_contents($GLOBALS['SET']['basedir'].$this->template);
 }

 private function readInfo()
 {
  $raw=$this->get();
  preg_match("/<head>(?P<head>.*?)<\/head>/smU",$raw,$match);
  $split['head']=$match['head'];
  unset($match);
  preg_match("/<body>(?P<body>.*?)<\/body>/smU",$raw,$match);
  $split['body']=$match['body'];
  unset($match);
  
  if (!$GLOBALS['USR']->inGroup('users'))
  {
   $umopts="<li><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-login.php\">Login</a></li>";
   $rockout=null;
  }
  else
  {
   $umopts="<li><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=settings\">Settings</a></li>";
   $rockout="\n<li><a href=\"//{$GLOBALS['SET']['baseuri']}/?action=logout\">Logout</a></li>\n";
  }
  $contentlists=null;
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   $umopts.="\n<li><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=list\">Manage</a></li>\n<li><a href=\"//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=user&action=new\">Register</a></li>";
  }
  if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
  {
   if($_SERVER['QUERY_STRING'])
   {
    $qstr="?".$_SERVER['QUERY_STRING']."&";
   }
   else
   {
    $qstr="?";
   }
   if($_GET['content'] == "addin" || basename($_SERVER['PHP_SELF']) != "mk-dash.php")
   {
    if (isset($_GET['content']))
    {
     $type=ucwords($_GET['content']);
    }
    else
    {
     $type="Page";
    }
    $curconlinks.=<<<HTML
<li><a href="{$qstr}action=edit">Edit This {$type}</a></li>
<li><a href="{$qstr}action=delete">Delete This {$type}</a></li>
HTML;
   }
   else
   {
    $curcontlinks=null;
   }
   $contentlists.=<<<HTML
<h4>Content</h4>
<ul id="ContentPlugs" class="plug list">
<li><a href="//{$GLOBALS['SET']['baseuri']}/?action=new">New</a></li>{$curconlinks}
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list=pages">All Pages</a></li>
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list=posts">All Posts</a></li>
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=content&list=attachments">Attachments</a></li>
</ul>
HTML;
  }
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   $contentlists.=<<<HTML
<h4>Site</h4>
<ul id="SitePlugs" class="plug list">
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&list=logs">Logs</a></li>
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=settings">Settings</a></li>
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&action=appearance">Appearance</a></li>
<li><a href="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=site&list=addins">Addins</a></li>
</ul>
HTML;
  }
  
  $metatags="<!-- Meta Tags? -->";
  if ($_GET['action'] == 'edit' || $_GET['action'] == 'new')
  {
   $editor=<<<HTML
<link rel="stylesheet" href="//{$GLOBALS['SET']['baseuri']}/mk-core/styles/editor.css" type=text/css>
<script type="text/javascript" src="//{$GLOBALS['SET']['baseuri']}/mk-core/scripts/editor.js"></script>
HTML;
  }
  else
  {
   $editor=null;
   $editor=null;
  }
  $addin_tags=compile_head();
  $split['head']=<<<HTML
<title>~{sitename} - ~{pagetitle}</title>
<!-- Meta Tags? -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
{$editor}<script src="//{$GLOBALS['SET']['baseuri']}/mk-core/scripts/dash.js" type="text/javascript"></script>
<link rel="stylesheet" href="//{$GLOBALS['SET']['baseuri']}/mk-core/styles/momoko.css" type="text/css">
{$addin_tags}
{$split['head']}
HTML;
  if ($GLOBALS['USR']->inGroup('users'))
  {
   $dashup="<div id=\"dashOpen\"><button id=\"sidebarOpen\" onclick=\"toggleSidebar()\">My Dashboard</button></div>";
  }
  else
  {
   $dashup="<div id=\"dashOpen\"><button id=\"sidebarLogin\" onclick=\"window.location='//{$GLOBALS['SET']['baseuri']}/mk-login.php'\">Login</button></div>";
  }
  $split['body']=<<<HTML
{$dashup}
<div id="modal" title="Loading Awesomeness!" style="display:none">
<p>Becoming 20% more awesome...</p>
</div>
<div id="overlay" class="ui-widget-overlay" style="display:none" onclick="toggleSidebar();">&nbsp;</div>
<div id="dashboard" class="sidebar">
<button style="float:right" id="sidebarClose" onclick="toggleSidebar()">Close Dashboard</button>
<h1>{$GLOBALS['SET']['name']}</h1>
<h4>User</h4>
<ul id="UserPlugs" class="plug list">
{$umopts}
</ul>
{$contentlists}
<h4>Exit</h4>
<ul id="ExitPlugs" class="plug list">
<li><a href="javascript:void();" onclick="toggleSidebar();">Close Dashboard</a></li>{$rockout}
</ul>
</div>
{$split['body']}
HTML;
  
  $split['full']=<<<HTML
<!doctype html>
<!--[if lt IE 7]> <html class="ie6 oldie"> <![endif]-->
<!--[if IE 7]>    <html class="ie7 oldie"> <![endif]-->
<!--[if IE 8]>    <html class="ie8 oldie"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="">
<!--<![endif]-->
<head>
{$split['head']}
</head>
<body>
{$split['body']}
</body>
</html>
HTML;

  return $split;
 }

 public function toHTML($child=null)
 {
 if (@$_GET['ajax']) //skips rendering the template if ajax is set
 {
   return $child->inner_body;
 }
 else
 {
  $html=$this->info['full'];
  $vars['siteroot']=$GLOBALS['SET']['baseuri'];
  $vars['sitename']=$GLOBALS['SET']['name'];
  $vars['pagetitle']="Untitled";
  $vars['templatedir']=$vars['siteroot'].dirname($this->template);
  $vars['pagedir']=$vars['siteroot'].PAGEROOT;
  
  if (@$child && (is_object($child)) && ($child instanceof MomokoObject))
  {
   $page=$child;
  }
  $vars['pagetitle']=@$page->title;
  $vars['softwareversion']=MOMOKOVERSION;
  $vars['body']=preg_replace("/([\$])([A-za-z0-9])/","&#36;\\2",@$page->inner_body); //preg_replace here works around PHP $ (dollar sign) problems)

  if (@!$vars['body'] && @$page->full_html) // just in case the above didn't work, note: this is not elegant as it could result in invalid code, but will prevent links from appearing not to work. >.>
  {
   $vars['body']=$page->full_html;
  }

  if (@!$vars['body']) //just in case $body is still blank!
  {
   $page=new MomokoError(null); //browsers handle 204 errors differently, but this is the correct error code, so be warned
   $vars['body']=$page->inner_body;
  }

  $ch=new MomokoVariableHandler($vars);
  $html=$ch->replace($html);

  return $html;
 }
 }
}

class MomokoAddinForm implements MomokoObject
{
  public $form;
  private $info=array();
  
  public function __construct($form=null)
  {
    if (!empty($form))
    {
      $this->form=$form;
      $this->info=$this->parse();
    }
  }
  
  public function __get($var)
  {
    if (array_key_exists($var,$this->info))
    {
      return $this->info[$var];
    }
    else
    {
      return null;
    }
  }
  
  public function __set($var,$value)
  {
    return $this->info[$var]=$value;
  }
  
  public function get()
  {
    return file_get_contents($GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir']."forms/addin".$this->form.".htm");
  }
  
  private function parse()
  {
    $info=parse_page($this->get());
    $vars['sitedir']=$GLOBALS['SET']['baseuri'];
    $table=new DataBaseTable('addins');
    
    switch($this->form)
    {
      case 'remove':
      $q=$table->getData("num:'{$_GET['num']}'",array('num','longname','dir'));
      $vars=$q->fetch(PDO::FETCH_ASSOC);
      break;
      default:
      $cols=array('num','shortname','longname','type');
      $vars['addin_cols']=null;
      foreach ($cols as $col)
      {
	if ($col != 'num')
	{
	  $vars['addin_cols'].="<th class=\"ui-state-default\">".ucwords($col)."</th>";
	}
      }
      $vars['addin_cols'].="<th class=\"ui-state-default\">&nbsp;</th>";
      unset($name,$properties);
      
      $data=$table->getData();
      $vars['addin_list']=null;
      while ($row=$data->fetch())
      {
	$vars['addin_list'].="<tr id=\"".$row['num']."\">\n";
	foreach ($cols as $name)
	{
	  if ($name != 'num')
	  {
	    $vars['addin_list'].="<td id=\"".$name."\" class=\"ui-widget-content\">".$row[$name]."</td>";
	  }
	}
	$vars['addin_list'].="<td class=\"ui-widget-content\"><a class=\"ui-icon ui-icon-trash\" style=\"display:inline-block\" onclick=\"showRemove('".$row['num']."',event)\" title=\"Delete\" href=\"javascript:void()\"></a></td>\n</tr>\n";
      }
    }
    $vars['site_location']=$GLOBALS['SET']['baseuri'];
      
    $vh=new MomokoVariableHandler($vars);
    $info['inner_body']=$vh->replace($info['inner_body']);
    return $info;
  }
}

class MomokoAddin implements MomokoObject
{
 public $path;
 public $isEnabled;
 private $table;
 private $info=array();

 public function __construct($path=null)
 {
  $this->table=new DataBaseTable('addins');
  if (!empty($path))
  {
   $manifest=xmltoarray($GLOBALS['SET']['basedir'].$path.'/manifest.xml'); //Load manifest
   $this->info=$this->parseManifest($manifest);
   
   $db=new DataBaseTable("addins");
   $query=$db->getData("dir:'".basename($path)."'",array('enabled','num','dir'),null,1);
   $data=$query->fetch();
   if ($data['enabled'] == 'y')
   {
    $this->isEnabled=true;
   }
   else
   {
    $this->isEnabled=false;
   }
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
   return null;
  }
 }

 public function __set($key,$value)
 {
  return $this->info[$key]=$value;
 }

 public function get()
 {
  return $this->info;
 }
 
 public function put($data=null)
 {
  if (empty($data['archive']))
  {
    return new MomokoAddinForm('add');
  }
  else
  {
    $destination=$GLOBALS['CFG']->basedir.$data['dir'];
    if (mkdir($destination))
    {
      $zip=new ZipArchive;
      $zip->open($data['archive']);
      $zip->extractTo($destination);
      unlink($data['archive']);
      $data['dir']=pathinfo($data['dir'],PATHINFO_BASENAME);
      if ($num=$this->table->putData($data))
      {
	$new=$this->table->getData("num:'= ".$num."'",null,null,1);
	$info=$new->toArray();
	momoko_changes($GLOBALS['USR'],'added',$this);
	if (is_array($info[0]))
	{
	  return $info[0];
	}
	else
	{
	  return $info;
	}
      }
    }
    else
    {
      unlink($data['archive']);
      $info['error']="Could not make directory '".$destination."'!";
      return $info;
    }
  }
 }
 
 public function update($data=null)
 {
  if (empty($data['archive']))
  {
    return new MomokoAddinForm('add');
  }
  else
  {
    $destination=$GLOBALS['CFG']->basedir.$data['dir'];
    if (!file_exists($destination))
    {
      unlink($data['archive']);
      $info['error']="Cannot update non-existent addin '".$data['dir']."'! Please go back and select 'add addin'.";
      return $info;
    }
    if (is_writable($destination))
    {
      $zip=new ZipArchive;
      $zip->open($data['archive']);
      rmdirr($destination,true); //should empty the addin folder WITHOUT removing it
      $zip->extractTo($destination); //places the new files in the intact addin folder
      unlink($data['archive']);
      $data['dir']=pathinfo($data['dir'],PATHINFO_BASENAME);
      $old=$this->table->getData("dir:'".$data['dir']."'",array('num'),null,1);
      $old=$old->first();
      $data['num']=$old->num;
      if ($num=$this->table->updateData($data))
      {
	$new=$this->table->getData("num:'= ".$num."'",null,null,1);
	$info=$new->toArray();
	momoko_changes($GLOBALS['USR'],'updated',$this);
	if (is_array($info[0]))
	{
	  return $info[0];
	}
	else
	{
	  return $info;
	}
      }
    }
    else
    {
      unlink($data['archive']);
      $info['error']="Addin folder '".$destination."' not writable, cannot update addin!";
      return $info;
    }
    return true;
  }
 }
 
 public function upload($file)
 {
  if (!empty($file))
  {
    $result=false;
    $filename=$GLOBALS['SET']['tempdir'].'/'.$file['name'];
    if ($file['error'] == UPLOAD_ERR_OK && move_uploaded_file($file['tmp_name'],$filename))
    {
      $result=true;
    }
    elseif ($file['error'] == UPLOAD_ERR_INI_SIZE)
    {
      $error="File was too large to upload! Check your upload_max_filesize directive in your php.ini file!";
    }
    else
    {
      $error="File did not upload or could not be moved!";
    }
    
    if ($result == TRUE)
    {
      $info=$this->getArchiveInfo($filename);
      $basename=basename($filename);
      $script_body=<<<TXT
$('span#msg',pDoc).html('File upload complete...continuing');
$('li#file',pDoc).replaceWith("<li id=\"file\"><label for=\"file\">File:</label> <input id=addin type=hidden name=\"file\" value=\"{$filename}\">{$basename}<input id=\"addin-dir\" type=hidden name=\"dir\" value=\"{$info['dirroot']['value']}\"><input id=\"addin-incp\" type=hidden name=\"incp\" value=\"{$info['incp']['value']}\"></div>");
$('input#addin-name',pDoc).val('{$info['shortname']['value']}').removeAttr('disabled');
$('input#addin-title',pDoc).val('{$info['longname']['value']}').removeAttr('disabled');
$('textarea#addin-description',pDoc).val('{$info['description']['value']}').removeAttr('disabled');
TXT;
    }
    else
    {
      $script_body=<<<TXT
$('span#msg',pDoc).html("{$error}");
$('input#file',pDoc).removeAttr('disabled');
TXT;
    }
    
    print <<<HTML
<html>
<head>
<title>File Upload</title>
<script language=javascript src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<body>
<script language="javascript" type="text/javascript">
var pDoc=window.parent.document;

{$script_body}
</script>
<p>Processing complete. Check above for further debugging.</p>
</body>
</html>
HTML;
  }
 }
 
 public function drop()
 {
  $table=new DataBaseTable('addins');
  $query=$table->getData("dir:'".basename($this->info['dirroot']['value'])."'",array('num','dir','shortname'),null,1);
  $data=$query->fetch(PDO::FETCH_OBJ);
  
  if ($_POST['send'] == "Yes")
  {
    $ddata['num']=$data->num;
    if ($table->removeData($ddata) && rmdirr($GLOBALS['SET']['basedir'].'/addins/'.$data->dir))
    {
      momoko_changes($GLOBALS['USR'],'dropped',$this);
      $ddata['succeed']=true;
    }
    else
    {
      trigger_error("Unable to remove addin!",E_USER_NOTICE);
      $ddata['suceed']=false;
      $ddata['error']="MySQL Error: ".$table->error();
    }
   }
   else
   {
    $form=new MomokoAddinForm('remove');
    return $form;
   }
  
  return $ddata;
 }
 
 private function getArchiveInfo($archive)
 {
  $destination=pathinfo($archive,PATHINFO_DIRNAME).'/'.pathinfo($archive,PATHINFO_FILENAME);
  if (mkdir($destination))
  {
    $zip=new ZipArchive;
    $zip->open($archive);
    $zip->extractTo($destination,'manifest.xml');
    $zip->close();
    if (file_exists($destination.'/manifest.xml'))
    {
      $manifest=xmltoarray($destination.'/manifest.xml');
      unlink ($destination.'/manifest.xml');
      rmdir ($destination);
      return $this->parseManifest($manifest);
    }
  }
  else
  {
    echo "Could not make folder '".$destination."'!";
  }
 }
 
 public function setPathByID($id)
 {
  $query=$this->table->getData("num:'= ".$id."'",array('`num`','`dir`','`enabled`'),null,1);
  $data=$query->fetch(PDO::FETCH_OBJ);
  $path=$GLOBALS['SET']['basedir']."/addins/".$data->dir."/";
  
  $manifest=xmltoarray($path.'/manifest.xml'); //Load manifest
  $this->info=$this->parseManifest($manifest);
   
  if ($data->enabled == 'y')
  {
   $this->isEnabled=true;
  }
  else
  {
   $this->isEnabled=false;
  }
  
  return $this->path=$path;
 }
 
 public function toggleEnabled()
 {
  $query=$this->table->getData("dir:'".basename($this->info['dirroot']['value'])."'",array('num','enabled'),null,1);
  $data=$query->first();
  
  $ndata['num']=$data->num;
  if ($data->enabled == 'y')
  {
    $ndata['enabled']='n';
  }
  else
  {
    $ndata['enabled']='y';
  }
  
  if ($update=$this->table->updateData($ndata))
  {
    momoko_changes($GLOBALS['USR'],'toggled',$this,"Addin is enabled is now set to=".$ndata['enabled']);
    return $ndata['enabled'];
  }
  else
  {
    trigger_error("Unable to toggle enabled/disabled state of addin ".basename($this->info['dirroot']['value'])."!",E_USER_WARNING);
  }
 }

 public function hasAuthority()
 {
  $priority=array_reverse($this->authority);
  foreach ($priority as $name=>$list)
  {
   foreach ($list as $item)
   {
    if ($item == 'ALL' || $GLOBALS['USR']->inGroup($item))
    {
     $auth=true;
    }
    elseif ($item == 'NONE')
    {
     $auth=false;
    }
   }

   if ($name == 'blacklist')
   {
    $auth=!$auth;
   }
  }

  return $auth;
 }

 private function parseManifest(array $manifest)
 {
  foreach ($manifest as $node)
  {
   if (!empty($node['@text']))
   {
    $array[$node['@name']]['value']=$node['@text'];
   }
   else
   {
    if (is_array($node['@children']) && !empty ($node['@children'][0]))
    {
     $array[$node['@name']]['value']=$this->parseManifest($node['@children']);
    }
    else
    {
     $array[$node['@name']]['value']=null;
    }
   }

   $array[$node['@name']]['attr']=$node['@attributes'];
  }

  if (array_key_exists('authority',$array))
  {
   $authority=$array['authority']['value'];
   unset($array['authority']);
   $lists=array();
   foreach ($authority as $name=>$list)
   {
    $lists[$name]=explode(",",$list['value']);
   }
   $array['authority']=$lists;
  }

  return $array;
 }
}

