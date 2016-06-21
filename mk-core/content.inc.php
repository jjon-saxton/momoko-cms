<?php
require_once $config->basedir.'/mk-core/simple_html_dom.php';

class MomokoNavigation
{
 public $user;
 public $options=array();
 public $map=array();
 private $config;
 private $table;

 public function __construct($user,$options)
 {
  $this->user=$user;
  $this->config=new MomokoSiteConfig();
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
    case 'flat':
    $text=$this->getTopMap($this->map,$this->options['style']);
    break;
    case 'simple':
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
   if ($this->config->rewrite == true)
   {
    $href=$this->config->siteroot."/".$item['href'];
   }
   else
   {
    $href=$this->config->siteroot."/?p=".$item['id'];
   }
   if (is_array($item['children']))
   {
    $text.="<li id=\"{$item['id']}\" class=\"category dropdown\"><a href=\"{$href}\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\">{$item['title']}<div style=\"display:inline-block;height:100%\"> <b class=\"caret\"></b> </div></a>\n";
    $text.="<ul id=\"{$item['id']}\" class=\"subnav dropdown-menu\" role=\"menu\">\n".$this->getListItems($item['children'])."\n</ul>\n</li>\n";
   }
   else
   {
    $text.="<li id=\"{$item['id']}\"><a href=\"{$href}\">{$item['title']}</a></li>\n";
   }
  }

  return $text;
 }

 public function getTopMap($map,$display='line')
 {
  if ($display == "list")
  {
   $text="<ol type=\"I\" id=\"NavList\">\n";
  }
  else
  {
   $text="<div id=\"NavLine\">\n";
  }

  foreach ($map as $item)
  {
   if ($this->config->rewrite == true)
   {
    $href="//".$this->config->baseuri."/".$item['href'];
   }
   else
   {
    $href="//".$this->config->baseuri."/?p=".$item['id'];
   }

   if ($display == "list")
   {
    $text.="<li id=\"{$item['id']}\" class=\"nav-item\"><a href=\"{$href}\">{$item['title']}</a></li>\n";
   }
   else
   {
    $text.="| <a href=\"{$href}\">{$item['title']}</a> |";
   }
  }

  if ($display == "list")
  {
   $text.="</ol>";
  }
  else
  {
   $text.="</div>";
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

class MomokoFeed implements MomokoObject
{
 private $table;
 private $config;
 private $options=array();
 private $info=array();

 public function __construct($path, array $additional_vars=null)
 {
  $this->table=new DataBaseTable('content');
  $this->config=new MomokoSiteConfig();
  $this->options['where_str']="status: 'public'";
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
    return $this->info[$key];
  }
  else
  {
    return $this->options[$key];
  }
 }

 public function __set($key,$val)
 {
  return $this->options[$key]=$val;
 }

 public function put($data)
 {
  trigger_error("User attempted to put data using MomokoFeed class, method not supported!",E_USER_NOTICE);
  $page=new MomokoError("405 Method Not Allowed");
  return $page->full_html;
 }

 public function get()
 {
  $name=htmlspecialchars($this->config->name,ENT_XML1,'UTF-8');
  $uri=htmlspecialchars("//".$this->config->baseuri."/",ENT_XML1,'UTF-8');
  $query=$this->table->getData("type:'post'");
  $dom=new DOMDocument('1.0','UTF-8');

  switch ($this->options['type'])
  {
   case 'atom':
   case 'feed':
   header("content-type:application/atom+xml");
   $atom=$dom->appendChild(new DOMElement('feed',null,'http://www.w3.org/2005/Atom'));
   $atom_name=$atom->appendChild(new DOMElement('title',$name." | Post Feed | ATOM","http://www.w3.org/2005/Atom"));
   $atom_des=$atom->appendChild(new DOMElement('subtitle',$name."'s ATOM feed for posts"));
   $self=$atom->appendChild(new DOMElement('link'));
   $self_href=$self->setAttribute('href',$uri."?content=atom");
   $self_rel=$self->setAttribute('rel','self');
   $site=$atom->appendChild(new DOMElement('link'));
   $site_href=$site->setAttribute('href',$uri);
   while ($post=$query->fetch(PDO::FETCH_ASSOC))
   {
    $entry=$atom->appendChild(new DOMElement('entry',null,'http://www.w3.org/2005/Atom'));
    $title=$entry->appendChild(new DOMElement('title',htmlspecialchars($post['title'],ENT_XML1,"UTF-8")));
    $link=$entry->appendChild(new DOMElement('link'));
    $link_href=$link->setAttribute('href',$uri."?content=post&p=".$post['num']);
    $alt=$entry->appendChild(new DOMElement('link'));
    $alt_rel=$alt->setAttribute('rel','alternate');
    $alt_href=$alt->setAttribute('href',"http:".$uri."?content=post&p=".$post['num']);
    $alt_type=$alt->setAttribute('type',"text/html");
    if (empty($post['date_modified']))
    {
     $uuid=$entry->appendChild(new DOMElement('id',$this->generateUUID("urn:uuid:",$post['date_created'])));
     $date=$entry->appendChild(new DOMElement('updated',gmdate('Y-m-d\TH:i:s\Z',strtotime($post['date_created']))));
    }
    else
    {
     $uuid=$entry->appendChild(new DOMElement('id',$this->generateUUID("urn:uuid:",$post['date_modified'])));
     $date=$entry->appendChild(new DOMElement('updated',gmdate('Y-m-d\TH:i:s\Z',strtotime($post['date_modified']))));
    }
    $summary=$entry->appendChild(new DOMElement('summary',$this->generateSummary($post['text'])));
   }
   break;
   case 'rss':
   default:
   header("content-type:application/rss+xml");
   $rss=$dom->appendChild(new DOMElement('rss'));
   $rss_version=$rss->setAttribute('version','2.0');
   $channel=$rss->appendChild(new DOMElement('channel'));
   $feed_name=$channel->appendChild(new DOMElement('title',$name." | Post Feed | RSS"));
   $feed_link=$channel->appendChild(new DOMElement('link',$uri));
   $feed_des=$channel->appendChild(new DOMElement('description',$name."'s RSS Feed for posts"));
   while ($post=$query->fetch(PDO::FETCH_ASSOC))
   {
    $link=$uri."/?content=post&amp;p=".$post['num'];
    $item=$channel->appendChild(new DOMElement('item'));
    $title=$item->appendChild(new DOMElement('title',$post['title']));
    $link=$item->appendChild(new DOMElement('link',$link));
    $pubdate=$item->appendChild(new DOMElement('pubDate',gmdate('Y-m-d\TH:i:s\Z',strtotime($post['date_created']))));
    if (empty($post['date_modified']))
    {
     $guid=$item->appendChild(new DOMElement('guid',$this->generateUUID(null,$post['date_created'])));
    }
    else
    {
     $guid=$item->appendChild(new DOMElement('guid',$this->generateUUID(null,$post['date_modified'])));
    }
    $des=$item->appendChild(new DOMElement('description',$this->generateSummary($post['text'])));
   }
  }

  $full_xml=$dom->saveXML();
  $this->full_html=$full_xml;
  return $full_xml;
 }

 private function generateSummary($text,$len=125)
 {
  $page=parse_page($text); //Seperates full post into parts if needed
  $text=$page['inner_body']; //Resets text to just the important part of the post
  $text=strip_tags($text,"<strong><b><em><i>"); //Gets rid of HTML tags
  if (strlen($text) > $len)
  {
   $matches = array();
   preg_match("/^(.{1,".$len."})[\s]/i", $text, $matches); //Shortens the summary intelligently
   $text=$matches[0].'...';
  }

  return $text;
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
 private $config;
 private $page;
 private $info=array();
 
 public function __construct($path, array $additional_vars=null)
 {
  $table=new DataBaseTable('content');
  $this->config=new MomokoSiteConfig();
  
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
  $query=$this->table->getData("num:'= {$num}'",null,null,1);
  $this->info=$query->fetch(PDO::FETCH_ASSOC);
 }
 
 public function fetchByLink($uri)
 {
  $query=$this->table->getData("link:`{$uri}`",null,null,1);
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
<input type=hidden name="author" value="{$this->user->num}">
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
<form class="form-inline" role="form" method=post>
{$hiddenvals}
<h2>Edit Attachment: <input type=text name="title" placeholder="Filename" id="title" value="{$this->title}"></h2>
<div id="PageEditor">
<div id="PageProps">
<div class="form-group">
 <label for="parent">Parent Page:</label>
 <select class="form-control" id="parent" name="parent">{$parent_opts}</select>
</div>
<div class="form-group">
 <label for="status">Attachment Status:</label>
 <select id="status" name="status">{$status_opts}</select>
</div>
<div class="form-group">
 <label for="private">Groups that have access:</label>
 <input type=text id="private" name="has_access" disabled=disabled value="editor,members">
</div>
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
   $file=$this->config->basedir.'/'.preg_replace("%http://".$this->config->baseuri."/%",'',$this->link);
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
<p>The attachment you selected was deleted! You may now <a href="//{$this->config->baseuri}/">return</a> to your home page!</p>
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
<div class="confirmation buttons"><button class="answer" class="btn btn-success" type=submit name="drop" id="true" value="1">Yes</button> <button class="btn btn-danger" class="answer" id="false">No</button></div>
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
 private $user;
 private $config;
 private $info=array();
 
 public function __construct($path,MomokoSession $user, array $additional_vars=null)
 {
  $this->user=$user;
  $this->config=new MomokoSiteConfig();

  $table=new DataBaseTable('content');
  
  $title=basename($path);
  $category_tree=trim(dirname($path),"./");
  $parent=basename($category_tree);
  
  if ($title == NULL)
  {
   $where="status:`public` type:`page`";
  }
  else
  {
   $where="title:`{$title}`";
  }
  if ($parent != NULL)
  {
   $pq=$table->getData("title:`{$parent}`",array('num'),null,1);
   $pinfo=$pg->fetch(PDO::FETCH_ASSOC);
   if ($pinfo['num'] > 0) //Ensures that there was actually a parent
   {
    $where.=" parent:`= {$pinfo['num']}`";
   }
   else
   {
    $where.=" parent:`= 0`";
   }
  }
  else
  {
   $where.=" parent:`= 0`";
  }
  $query=$table->getData($where,null,"order",1);
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
    header("Location: http://{$this->config->baseuri}/?p={$data['num']}");
    exit();
   }
   elseif ($_GET['action'] == 'new' && $new=$this->table->putData($data))
   {
    header("Location: http://{$this->config->baseuri}/?p={$new}");
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
   $statuses=array('public'=>"Public",'cloaked'=>"Hidden From Navigation",'private'=>"Private",'locked'=>"Draft");
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
<input type=hidden name="author" value="{$this->user->num}">
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
     $type_links="<div id=\"type_select\"><input type=\"radio\" id=\"t1\" checked=checked name=\"type\" value=\"page\"><label for=\"t1\">Page</label> <input type=\"radio\" id=\"t2\" name=\"type\" value=\"post\"><label for=\"t2\">Post</label></div>";
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
    $("#modal .modal-title").html("New From...")
	$("#modal .modal-body").load("//{$this->config->baseuri}/mk-dash.php?section=content&action=gethref&ajax=1&origin=new",function(){
     $(this).on('click','div.selectable',function(){
      var location=$(this).find("a#location").attr('href');
      $.get(location,function(html){
       $(".jqte_editor").html(html);
      });
     });
     $(".selectable").attr("data-dismiss",'modal');
	});
    $("#modal").modal('show');
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
  dashuri:"//{$this->config->baseuri}/mk-dash.php",
  color:false,
  strike:false,
  formats:[{$formats}],
  fsize:false,
  placeholder: "Page body..."
 });
 
 $("#type_select").css("text-align","center");
 $("#type_select input:radio").change(function(){
  window.location="?action=new&content="+$("#type_select input:radio:checked").val();
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
<form role="form" method=post>
{$hiddenvals}
<h2>Edit {$type}: <input type=text name="title" placeholder="{$type} Title" id="title" value="{$this->title}"></h2>
{$type_links}
<div id="PageEditor">
<ul class="nav nav-tabs">
<li class="active"><a data-toggle="tab" href="#PageBody">Body</a></li>
<li><a data-toggle="tab" href="#PageProps">Properties</a></li>
</ul>
<div class="tab-content">
<div id="PageBody" class="tab-pane fade in active">
<textarea class="form-control" id="pagebody" name="text">
{$this->inner_body}
</textarea>
</div>
HTML;
  if ($type == 'Page')
  {
   $info['inner_body'].=<<<HTML
<div id="PageProps" class="tab-pane fade">
<div class="form-group">
 <label for="parent">Parent Page:</label>
 <select class="form-control" id="parent" name="parent">{$parent_opts}</select>
</div>
<div class="form-group">
 <label for="status">Page Status:</label>
 <select class="form-control" id="status" name="status">{$status_opts}</select>
</div>
<div class="form-group">
 <label for="private">Groups that have access:</label>
 <input class="form-control" type="text" id="private" name="has_access" disabled=disabled value="editor,members">
</div>
</div>
HTML;
   }
   elseif ($type == 'Post')
   {
    $now_h=date($this->user->shortdateformat);
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
<div id="PageProps" class="tab-pane fade">
<input type=hidden name="parent" value="0">
<ul class="noindent nobullet">
<li>Post Date: {$now_h}</li>
<li>Post Author: {$this->user->name}</li>
<li><label for="status">Post Status:</label> <select id="status" name="status">{$status_opts}</select>
</ul>
</div>
HTML;
   }
   $info['inner_body'].=<<<HTML
</div>
<div id="PageSave" align=center>
<button type=submit name="save" class="btn btn-primary" value="1">Save</button>
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
  if (empty($this->user) || !($this->user instanceof MomokoSession))
  {
   $user=new MomokoSession(); //create a guest session in case usere is empty to prevent cascading errors
  }
  else
  {
   $user=$this->user; //copy the current session if it is okay.
  }

  if ($authorized && $this->text)
  {
   return $this->text;
  }
  elseif (!$authorized)
  {
   $page=new MomokoError("403 Forbidden",$user);
   return $page->inner_body;
  }
  else
  {
   $page=new MomokoError("404 Not Found",$user);
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
<p>The page you selected was removed! You may now <a href="//{$this->config->baseuri}/">return</a> to your home page!</p>
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
<div class="confirmation buttons"><button class="answer btn btn-success" type=submit name="drop" id="true" value="1">Yes</button> <button class="answer btn btn-danger" id="false">No</button></div>
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
   
   $vars['siteroot']=$this->config->siteroot;
   
   return $vars;
 }
 
 private function hasAccess()
 {
  $grouplist=explode(",",$this->has_access);
  if (empty($this->user))
  {
   trigger_error("No user passed when attempting to check for access rights. Defaulting to no access. This is a programing error.",E_USER_NOTICE);
   return false;
  }
  elseif ($this->user->inGroup('admin'))
  {
   return true;
  }
  elseif ($this->status != 'private')
  {
   switch ($this->status)
   {
    case 'locked':
    if ($this->user->inGroup('admin') || $this->user->inGroup('editor'))
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
    if ($this->user->inGroup($group))
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
 private $config;
 private $inner_body;
 private $error_msg;

 public function __construct($title,MomokoSession $user,$msg=null,array $additional_vars=null)
 {
  $this->page=new MomokoPage($title,$user);
  $this->config=new MomokoSiteConfig();
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
  $vars['admin_email']=$this->config->support_email;
  $vars['forgot_password']=$this->config->sec_protocol.$this->config->baseuri."/mk-login.php?action=reset";
  return $vars;
 }
}

class MomokoTemplate implements MomokoObject, MomokoPageObject
{
 private $user;
 private $template;
 private $conf;
 private $info=array();
 
 public function __construct(MomokoSession $user,MomokoSiteConfig $conf)
 {
  $this->user=$user;
  $this->conf=$conf;
  $this->template=$conf->basedir.TEMPLATEPATH;
  $this->info=$this->get();
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
  $raw=file_get_contents($this->template);
  preg_match("/<head>(?P<head>.*?)<\/head>/smU",$raw,$match);
  $split['head']=$match['head'];
  unset($match);
  preg_match("/<body(?P<body_props>.*)>(?P<body>.*?)<\/body>/smU",$raw,$match);
  $split['body']=$match['body'];
  $body_tag="<body".$match['body_props'].">";
  unset($match);
  
  if (!$this->user->inGroup('users'))
  {
   $umopts="<li><a href=\"{$this->conf->sec_protocol}{$this->conf->baseuri}/mk-login.php\">Login</a></li>";
   $rockout=null;
  }
  else
  {
   $umopts="<li><a href=\"{$this->conf->siteroot}/mk-dash.php?section=user&action=settings\">Settings</a></li>";
   $rockout="\n<li><a href=\"{$this->conf->sec_protocol}{$this->conf->baseuri}/?action=logout\">Logout</a></li>\n";
  }
  $contentlists=null;
  if ($this->user->inGroup('admin'))
  {
   $umopts.="\n<li><a href=\"{$this->conf->siteroot}/mk-dash.php?section=user&action=list\">Manage</a></li>\n<li><a href=\"{$this->conf->siteroot}/mk-dash.php?section=user&action=new\">Register</a></li>";
  }
  if ((basename($_SERVER['PHP_SELF']) == "mk-dash.php" || empty($_GET['action'])) && ($this->user->inGroup('admin') || $this->user->inGroup('editor')))
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
<li><a href="{$this->conf->siteroot}/?action=new">New</a></li>{$curconlinks}
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=pages">All Pages</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=posts">All Posts</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=attachments">Attachments</a></li>
</ul>
HTML;
  }
  elseif ($this->user->inGroup('admin') || $this->user->inGroup('editor'))
  {
    $contentlists.=<<<HTML
<h4>Content</h4>
<ul id="ContentPlugs" class="plug list">
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=pages">All Pages</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=posts">All Posts</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=content&list=attachments">Attachments</a></li>
</ul>
HTML;
  }
  if ($this->user->inGroup('admin'))
  {
   $sb_tbl=new DataBaseTable('addins');
   $sb_q=$sb_tbl->getData("type:'switchboard'",array('dir','shortname'));
   $switchboards=null;
   if ($sb_q->rowCount() > 0)
   {
    while ($sb_row=$sb_q->fetch())
    {
        $switchboards.="<li><a href=\"{$this->conf->siteroot}/mk-dash.php?section=switchboard&plug={$sb_row['dir']}\">{$sb_row['shortname']}</a></li>\n";
    }
   }
   $contentlists.=<<<HTML
<h4>Site</h4>
<ul id="SitePlugs" class="plug list">
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=site&list=logs">Logs</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=site&action=settings">Settings</a></li>
<li><a href="{$this->conf->siteroot}/mk-dash.php?section=site&action=appearance">Appearance</a></li>
</ul>
<h4>Addins</h4>
<ul id="SwitchPlugs" class="plug list">
{$switchboards}<li><a href="{$this->conf->siteroot}/mk-dash.php?section=site&list=addins">All Addins</a></li>
</ul>
HTML;
  }
  
  $metatags="<!-- Meta Tags? -->";
  if ($_GET['action'] == 'edit' || $_GET['action'] == 'new')
  {
   $editor=<<<HTML
<link rel="stylesheet" href="//{$this->conf->baseuri}/mk-core/styles/editor.css" type=text/css>
<script type="text/javascript" src="//{$this->conf->baseuri}/mk-core/scripts/editor.js"></script>
HTML;
  }
  else
  {
   $editor=null;
  }
  $addin_tags=compile_head();
  $split['head']=<<<HTML
<title>~{sitename} - ~{pagetitle}</title>
<!-- Meta Tags? -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//{$this->conf->baseuri}/mk-core/scripts/bootstrap.js"></script>
{$editor}<script src="//{$this->conf->baseuri}/mk-core/scripts/dash.js" type="text/javascript"></script>
<link rel="stylesheet" href="//{$this->conf->baseuri}/mk-core/styles/momoko.css" type="text/css">

<link rel="alternate" type="application/rss+xml" title="Post Feed: RSS" href="{$this->conf->siteroot}/?content=rss">
<link rel="alternate" type="application/atom+xml" title="Post Feed: ATOM" href="{$this->conf->siteroot}/?content=atom">
{$addin_tags}
{$split['head']}
HTML;

 if ($_SESSION['modern'] == 'full')
 {
  $split['body']=<<<HTML
<div id="modal" class="modal fade" role="dialog">
 <div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
     <button type="button" class="close" data-dismiss="modal">&times;</button>
     <h4 class="modal-title">Preparing awesome stuff...</h4>
    </div>
    <div class="modal-body">
     <p>Becoming 20% more awesome...</p>
    </div>
  </div>
 </div>
</div>
<div id="sidebar" class="modal fade left" role="dialog">
 <div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
     <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div id="dashboard" class="modal-body">
<h1>{$this->conf->name}</h1>
<h4>User</h4>
<ul id="UserPlugs" class="plug list">
{$umopts}
{$rockout}
</ul>
{$contentlists}
<h4>Exit</h4>
<ul id="ExitPlugs" class="plug list">
<li><a href="#sidebar" data-dismiss="modal">Close Dashboard</a></li>
</ul>
</div>
    </div>
  </div>
 </div>
</div>
{$split['body']}
HTML;
 }
 elseif ($_SESSION['modern'] == 'partial' && !$_SESSION['classic'])
 {
   $split['body']=<<<HTML
<script language="javascript" type="text/javascript">
  $(document).ready(function(){
    $('#modal').modal('show');
  });
</script>
<div id="modal" class="modal fade" role="dialog">
 <div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
     <h4 class="modal-title">JavaScript support detected!</h4>
    </div>
    <div class="modal-body">
     <p>Your browser does support javascript. If you'd like you can switch to a better supported, feature-rich layout by informing MomoKO that JavaScript is enabled. You will only have to do this once. Do you wish to switch to a better layout?</p>
    </div>
    <div class="modal-footer">
     <a href="{$this->config->siteroot}mk-login.php?action=force-modern" class="btn btn-success">Yes</a>
     <a href="{$this->config->siteroot}mk-login.php?action=keep-classic" class="btn btn-danger">No</a>
    </div>
  </div>
 </div>
</div>
<noscript>
<div class="alert alert-warning">
<p>Your browser does not support JavaScript! To use a better theme, please enable JavaScript, if available.
</div>
</noscript>
{$split['body']}
HTML;
 }
  
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
{$body_tag}
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
  $vars['siteroot']=$this->conf->siteroot;
  $vars['sitename']=$this->conf->name;
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

  $ch=new MomokoVariableHandler($vars,$this->user);
  $html=$ch->replace($html);

  return $html;
 }
 }
}

class MomokoAddinForm implements MomokoObject
{
  public $form;
  private $config;
  private $info=array();
  
  public function __construct($form=null)
  {
    $this->config=new MomokoSiteConfig();
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
    return file_get_contents($this->config->basedir.$this->config->filedir."forms/addin".$this->form.".htm");
  }
  
  private function parse()
  {
    $info=parse_page($this->get());
    $vars['sitedir']=$this->config->baseuri;
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
	    $vars['addin_list'].="<td id=\"".$name."\">".$row[$name]."</td>";
	  }
	}
	$vars['addin_list'].="<td><a class=\"glyphicon glyphicon-remove\" onclick=\"showRemove('".$row['num']."',event)\" title=\"Delete\" href=\"javascript:void()\"></a></td>\n</tr>\n";
      }
    }
    $vars['site_location']=$this->config->siteroot;
      
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
 private $config;
 private $info=array();

 public function __construct($path=null)
 {
  $this->table=new DataBaseTable('addins');
  $this->config=new MomokoSiteConfig();
  if (!empty($path))
  {
   $manifest=xmltoarray($this->config->basedir.$path.'/manifest.xml'); //Load manifest
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
    $destination=$this->config->basedir.$data['dir'];
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
	momoko_changes($this->user,'added',$this);
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
    $destination=$this->config->basedir.$data['dir'];
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
	momoko_changes($this->user,'updated',$this);
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
    $filename=$this->config->tempdir.'/'.$file['name'];
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
    if ($table->removeData($ddata) && rmdirr($this->config->basedir.'/addins/'.$data->dir))
    {
      momoko_changes($this->user,'dropped',$this);
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
  $path=$this->config->basedir."/addins/".$data->dir."/";
  
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
    momoko_changes($this->user,'toggled',$this,"Addin is enabled is now set to=".$ndata['enabled']);
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
    if ($item == 'ALL' || $this->user->inGroup($item))
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

