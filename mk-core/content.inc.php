<?php
require_once $GLOBALS['SET']['basedir'].'/core/Array2XML.class.php';
require_once $GLOBALS['SET']['basedir'].'/core/XML2Array.class.php';

class MomokoNavigation implements MomokoModuleInterface
{
 public $user;
 public $options=array();
 public $map=array();

 public function __construct($user,$options)
 {
  $this->user=$user;
  parse_str($options,$this->options);
  $xml=simplexml_load_file($GLOBALS['SET']['pagedir'].'/map.xml');
  $this->convertXmlObjToArr($xml,$this->map);
 }
	
public function convertXmlObjToArr($obj, &$arr)
 {
  $children = $obj->children();
  foreach ($children as $elementName => $node)
  {
   $nextIdx = count($arr);
   $arr[$nextIdx] = array();
   $arr[$nextIdx]['@name'] = strtolower((string)$elementName);
   $arr[$nextIdx]['@attributes'] = array();
   $attributes = $node->attributes();
   foreach ($attributes as $attributeName => $attributeValue)
   {
    $attribName = strtolower(trim((string)$attributeName));
    $attribVal = trim((string)$attributeValue);
    $arr[$nextIdx]['@attributes'][$attribName] = $attribVal;
   }
   $text = (string)$node;
   $text = trim($text);
   if (strlen($text) > 0)
   {
    $arr[$nextIdx]['@text'] = $text;
   }
   $arr[$nextIdx]['@children'] = array();
   $this->convertXmlObjToArr($node, $arr[$nextIdx]['@children']);
  }
  return;
 } 

 public function convertArrToXmlObj($arr, &$obj)
 {
  foreach ($arr as $item)
  {
   $subnode=$obj->addChild($item['@name'],$item['@text']);
   if (is_array($item['@attributes']))
   {
    foreach ($item['@attributes'] as $attr=>$value)
    {
     $subnode->addAttribute($attr,$value);
    }
   }
   if (is_array($item['@children']))
   {
    $this->convertArrToXmlObj($item['@children'],$subnode);
   }
  }
  return;
 }

 public function getModule($format='html',$map=null,$name=null,$ppath=null)
 {
  if (!@$map)
  {
   $map=$this->map;
  }

  switch ($format)
  {
   case 'plain':
   return $this->getTextNav($this->map);
   break;
   case 'html':
   default:
   if ((@$GLOBALS['USR'] instanceof MomokoSession) && ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor')))
   {
    $edit=<<<HTML
<style>
div#Editor{
	display:inline;
	float:right
}
ul.subnav { display:none }
</style>
<script language="javascript" type="text/javascript">
$(function(){
	$("div#Editor a")
		.button({
			text:false,
			icons:{ primary: 'ui-icon-gear' }
		})
		.click(function(event){
			event.preventDefault();
			$("div#NavEditDialog").load("//{$GLOBALS['SET']['domain']}{$GLOBALS['SET']['location']}/ajax.php?include=navhelper&action=build&dialog=map").dialog({
				autoLoad:false,
				modal:true,
				title:'Edit Site Map',
				minWidth: '400',
				minHeight: '250',
				buttons:{
					"Save":function(){
						//Perform ajax save and site nav update
						$.post("//{$GLOBALS['SET']['domain']}{$GLOBALS['SET']['location']}/ajax.php?include=navhelper&action=post",{ 'raw_dom':$("div#MapList").html() },function(data){
							location.reload(true);
						});
						$(this).dialog('close');
					},
					"Cancel":function(){
						$(this).dialog('close');
					}
				}
			});
		});
});
</script>
<div id="Editor"><a href="#edit-nav">Edit</a>
<div id="NavEditDialog" style="display:none">
<p>Loading...</p>
</div>
HTML;
   }
   else
   {
    $edit=null;
   }
   switch ($this->options['display'])
   {
    case 'menu':
    $html="<ul id=\"NavList\" class=\"topnav\">\n".$this->getLinkList($map,$ppath).$edit."\n</ul>";
    break;
    case 'list':
    if (!@$name)
    {
     $name="Site Map";
    }
    $html="<h2>{$name}</h2>\n<ul id=\"MapList\" class=\"sitemap\">\n".$this->getLinkList($map,$ppath)."\n</ul>";
    break;
    case'simple':
    default:
    $html=$this->getLinkList($map,$ppath);
   }
   return $html;
   break;
  }
 }

 public function getIndex($path='/',$mapnode=null,$fullpath=null)
 {
  if (!@$mapnode)
  {
   $mapnode['@children']=$this->map;
  }

  $dir=explode('/',$path);
  if (!@$dir[1])
  {
   $children=$mapnode['@children'];

   if (!@$fullpath)
   {
    $fullpath='/';
   }

   foreach ($children as $child)
   {
    if (file_exists($GLOBALS['SET']['pagedir'].$fullpath.'home.htm'))
    {
     return $fullpath.'home.htm';
    }
   }
   return $fullpath.'map.mmk';
  }
  else
  {
   $name=$dir[1].'/';
   foreach ($mapnode['@children'] as $child)
   {
    if ($child['@name'] == 'site' && $child['@attributes']['dir'] == $name)
    {
     $mapnode=$child;
    }
   }

   if (!@$fullpath)
   {
    $fullpath=$path;
   }

   unset ($dir[0]);
   $npath=implode('/',$dir);

   return $this->getIndex($npath,$mapnode,$fullpath);
  }
 }
 
 private function getLinkList(array $map)
 {
  $html=null;
  $rpath=rtrim('//'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'],'/');

		foreach($map as $node)
		{
			if ($node['@name'] == 'site')
			{
				if (!isset($node['@attributes']['file']))
				{
					$node['@attributes']['file']='/';
				}
				$id=$node['@text'];
				$class='subnav';
				$html.="<li type=\"{$node['@name']}\" class=\"site\"><a href=\"{$rpath}{$node['@attributes']['file']}\">{$node['@text']}</a>\n";
				$html.="<ul id=\"{$id}\" class=\"{$class}\">\n".$this->getLinkList($node['@children'])."\n</ul></li>\n";
			}
			elseif ($node['@name'] == 'page')
			{
				if(!empty($node['@attributes']['uri']))
				{
					$html.="<li type=\"{$node['@name']}\" class=\"page external\"><a href=\"{$node['@attributes']['uri']}\">{$node['@text']}</a></li>\n";
				}
				elseif (!empty($node['@attributes']['file']))
				{
					$html.="<li type=\"{$node['@name']}\" class=\"page\"><a href=\"{$rpath}{$node['@attributes']['file']}\">{$node['@text']}</a></li>\n";
				}
			}
		}

  return trim($html,"\n\r");
 }
	
	private function getTextNav($map)
	{
		$text=null;
  $rpath="http://{$GLOBALS['SET']['domain']}{$GLOBALS['SET']['location']}";
		
		if ($this->options['display'] == 'list')
		{
			$sep="\n";
		}
		else
		{
			$sep=" | ";
		}

		foreach($map as $node)
		{
			if ($node['@name'] == 'site')
			{
				$id=$node['@text'];
				$class='subnav';
				//$cpath=$node['@attributes']['dir'];
				$text.=$rpath.$node['@attributes']['file'].$sep;
				$text.=$this->getTextNav($node['@children']).$sep;
			}
			elseif ($node['@name'] == 'page')
			{
				if (!empty($node['@attributes']['file']))
				{
					$text.=$rpath.$node['@attributes']['file'].$sep;
				}
			}
		}
		
		return trim($text,"\n\r");
	}
 public function HTMLArraytoMap(array $array)
 {
  foreach ($array as $node)
  {
   if ($node['tag'] == 'ul')
   {
    foreach ($node['childNodes'] as $child)
    {
     $attrs=array();
     unset($this->map);
     if ($child['tag'] == 'li')
     {
      foreach ($child['childNodes'] as $grandchild)
      {
       if ($grandchild['tag'] == 'a')
       {
        if (preg_match("/".preg_quote($GLOBALS['SET']['domain'].$GLOBALS['SET']['location'],"/")."/",$grandchild['attributes']['href']) > 0)
        {
         $attrs['file']=preg_replace("/".preg_quote($GLOBALS['SET']['domain'].$GLOBALS['SET']['location'],"/")."/","",$grandchild['attributes']['href']);
	 $attrs['file']=preg_replace("/http:/",'',$attrs['file']);
	 $attrs['file']="/".trim($attrs['file'],"/"); //Since removing extra slashes (/) never seemed to work above we will trim them off here and readd a single slash to the front.
        }
	else
	{
	 $attrs['uri']=$grandchild['attributes']['href'];
        }

	/*if (!empty($grandchild['attributes']['index']))
        {
         $attrs['index']='index';
        }*/

	$title=$grandchild['innerHTML'];
       }
      }
      $new['@name']=$child['attributes']['type'];
      $new['@attributes']=$attrs;
      $new['@text']=$title;
      if ($child['attributes']['type'] == 'site')
      {
       if (!empty($new['@attributes']['file']))
       {
        unset($new['@attributes']['file']);
        $new['@attributes']['file']=$attrs['file'];
       }
       $new['@children']=$this->HTMLArraytoMap($child['childNodes']);
      }
      else
      {
       $new['@children']=array();
      }
      $map[]=$new;
     }
    }
   }
  }
  return $this->map=$map;
 }

 public function writeMap()
 {
  $xmlstr=<<<XML
<?xml version="1.0"?>
<site dir="/">
</site>
XML;
  $xmlobj=new SimpleXMLElement($xmlstr);
  $arr=$this->map;
  $this->convertArrToXmlObj($arr,$xmlobj);
  $dom=new DOMDocument('1.0'); //Folowing lines are used to process XML in easier to read format, for anyone who cares
  $dom->preserveWhiteSpace=false;
  $dom->formatOutput=true;
  $dom->loadXML($xmlobj->asXML());
  $data=$dom->saveXML();
  if (file_put_contents($GLOBALS['SET']['pagedir'].'/map.xml',$data))
  {
   momoko_changes($GLOBALS['USR'],'updated',$this,"Changes were written to {$GLOBALS['SET']['pagedir']}/map.xml");
   return true;
  }
  else
  {
   return false;
  }
 }
}

class MomokoNews implements MomokoModuleInterface
{
	public $user;
	public $news_list;
	public $options;
	
	public function __construct($user,$options)
	{
  $this->user=$user;
  parse_str($options,$this->options);
  $xml=simplexml_load_file($GLOBALS['SET']['pagedir'].'/news.xml');
		$nav=new MomokoNavigation(null,'display=none');
  $nav->convertXmlObjToArr($xml,$this->news_list);
	}
	
	public function getModule ($format='html')
	{
		$data=array();
		
		if (is_array($this->news_list))
                {
		 foreach ($this->news_list as $news_item)
		 {
			if ($news_item['@name'] == 'item')
			{
				foreach ($news_item['@children'] as $node)
				{
					$item[$node['@name']]=$node['@text'];
				}
			}
			$item['date']=strtotime($item['update']);
			$data[$item['date']]=$item;
		 }
  		}
		
		switch ($format)
		{
			case 'array':
			return $data;
			case 'rss':
			$uri_root='http://'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].'/';
   $dom=new DOMDocument('1.0', 'UTF-8');
			$rss=$dom->appendChild(new DOMElement('rss'));
			$rss_version=$rss->setAttribute('version','2.0');
			$channel=$rss->appendChild(new DOMElement('channel'));
			$ftitle=$channel->appendChild(new DOMElement('title',$GLOBALS['SET']['name'].' News Feed'));
			$flink=$channel->appendChild(new DOMElement('link',$uri_root));
			$fdes=$channel->appendChild(new DOMElement('description',$GLOBALS['SET']['name']." Atom Feed for news items"));
			foreach ($data as $news)
			{
				$item=$channel->appendChild(new DOMElement('item'));
				$title=$item->appendChild(new DOMElement('title',$news['headline']));
				$link=$item->appendChild(new DOMElement('link',$uri_root.'news.php/'.$news['date'].'.htm'));
				$pubdate=$item->appendChild(new DOMElement('pubDate',gmdate('Y-m-d\TH:i:s\Z',$news['date'])));
				$guid=$item->appendChild(new DOMElement('guid',$this->generateUUID(null,$news['date'])));
				$des=$item->appendChild(new DOMElement('description',$news['summary']));
			}
			$xml=$dom->saveXML();
			return $xml;
			break;
			case 'atom':
			$uri_root='//'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].'/';
   $dom=new DOMDocument('1.0', 'UTF-8');
   $feed=$dom->appendChild(new DOMElement('feed',null,'http://www.w3.org/2005/Atom'));
			$ftitle=$feed->appendChild(new DOMElement('title',$GLOBALS['SET']['name'].' News Feed','http://www.w3.org/2005/Atom'));
			$fstitle=$feed->appendChild(new DOMElement('subtitle',$GLOBALS['SET']['name']." Atom Feed for news items"));
			$flink_self=$feed->appendChild(new DOMElement('link'));
			$flink_self_href=$flink_self->setAttribute('href',$uri_root."index.php/atom.xml");
			$flink_self_rel=$flink_self->setAttribute('rel','self');
			$flink_site=$feed->appendChild(new DOMElement('link'));
			$flink_self_href=$flink_site->setAttribute('href',$uri_root."index.php");
			foreach ($data as $news)
			{
				$entry=$feed->appendChild(new DOMElement('entry',null,'http://www.w3.org/2005/Atom'));
				$title=$entry->appendChild(new DOMElement('title',$news['headline']));
				$link=$entry->appendChild(new DOMElement('link'));
				$link_href=$link->setAttribute('href',$uri_root.'news.php/'.$news['date']);
				$link_alt=$entry->appendChild(new DOMElement('link'));
				$link_alt_rel=$link_alt->setAttribute('rel','alternate');
				$link_alt_type=$link_alt->setAttribute('type','text/html');
				$link_alt_href=$link_alt->setAttribute('href',$uri_root.'news.php/'.$news['date'].'.htm');
				$uuid=$entry->appendChild(new DOMElement('id',$this->generateUUID("urn:uuid:",$news['date'])));
				$date=$entry->appendChild(new DOMElement('updated',gmdate('Y-m-d\TH:i:s\Z',$news['date'])));
				$summary=$entry->appendChild(new DOMElement('summary',$news['txt_summary']));
			}
   $xml=$dom->saveXML();
			return $xml;
			break;
			case 'html':
			default:
		 $html="<div id=\"NewsList\" class=\"news box\">\n";
		
		 if (isset($this->options['sort']))
		 {
			 switch ($this->options['sort'])
			 {
				 case 'recent':
				 break;
				 case 'oldest':
				 usort($data,build_sorter('date'));
				 break;
			 }
		 }
		 $max=$this->options['num'];
		
		 $c=1;
		 foreach($data as $news)
		 {
			 $news['file']=$news['date'].'.htm';
			 $news['date']=date($GLOBALS['USR']->shortdateformat,$news['date']);
			 if ($max > 0 && $c<=$max)
			 {
			  if (strlen($news['summary']) > $this->options['length'])
                          {
                           $matches = array();
  			   preg_match("/^(.{1,".$this->options['length']."})[\s]/i", $news['summary'], $matches);
                           $text=$matches[0].'... <a href="//'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].NEWSROOT.$news['file'].'">more</a>';
                          }
			  else
                          {
                           $text=$news['summary'].' <a href="//'.$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].NEWSROOT.$news['file'].'">view/comment on article</a>';
                          }
			  $html.=<<<HTML
<div id="{$news['date']}" class="news item">
<h4 class="headline">{$news['headline']}</h4>
<div class="date">{$news['date']}</div>
<div class="summary">
{$text}
</div>
</div>
HTML;
			 }
			 $c++;
		 }
		
		 $html.="</div>";
		 return $html;
		}
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

class MomokoPage implements MomokoObject
{
 private $cur_path;
 private $info=array();
 
 public function __construct($path, array $additional_vars=null)
 {
  $this->cur_path=$path;
  $this->info=$this->readInfo();

  $body=$this->inner_body;
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
 
 public function listAll()
 {
 }
 
 public function getChildren()
 {
  return false; //This object should have no children
 }
 
 public function put($data)
 {
  if (@$data['pagebody'])
  {
   if (@$data['private'])
   {
    $extra="<private>".$data['private']."</private>";
   }
   else
   {
    $extra="<public />";
   }
   $full_html=<<<HTML
<html>
<head>
<title>{$data['pagetitle']}</title>
{$extra}
</head>
<body>
{$data['pagebody']}
</body>
</html>
HTML;
   if (file_put_contents($GLOBALS['SET']['pagedir'].$this->path,$full_html))
   {
    $dir=pathinfo($this->path,PATHINFO_DIRNAME);
    if (pathinfo($data['pagename'],PATHINFO_EXTENSION) != 'htm' && pathinfo($data['pagename'],PATHINFO_EXTENSION) != 'html')
    {
     $data['pagename'].=".htm";
    }

    if ((pathinfo($this->path,PATHINFO_BASENAME) != $data['pagename']) && (rename($GLOBALS['SET']['pagedir'].$this->path,$GLOBALS['SET']['pagedir'].$dir.'/'.$data['pagename'])))
    {
     momoko_changes($GLOBALS['USR'],'updated',$this,"Additionally the page was renamed from ".basename($this->path)." to ".$data['pagename']."!");
     $dir=ltrim($dir,"/");
     header("Location: //".$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].PAGEROOT.$dir.'/'.$data['pagename']);
     exit();
    }
    else
    {
     momoko_changes($GLOBALS['USR'],'updated',$this);
     $file=ltrim($this->path,"/");
     header("Location: //".$GLOBALS['SET']['domain'].$GLOBALS['SET']['location'].PAGEROOT.$file);
     exit();
    }
   }
   else
   {
    trigger_error("Unable to write to page!",E_USER_ERROR);
   }
  }
  else
  {
   $editorroot='//'.$GLOBALS['SET']['baseuri'].'/scripts/elrte';
   $finderroot='//'.$GLOBALS['SET']['baseuri'].'/scripts/elfinder';
   $page['data']=$this->get();
   $page['name']=pathinfo($this->path,PATHINFO_BASENAME);
   if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$page['data'],$match) > 0) //Find page title in $data
   {
    if (@$match['title'] && ($match['title'] != "[Blank]" && $match['title'] != "Blank")) //accept titles other than Blank and [Blank]
    {
     $page['title']=$match['title'];
    }
   }
   if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$page['data'],$match) > 0) // Find page body in $data
   {
    $page['body']=trim($match['body'],"\n\r"); //Replace the $body variable with just the page body found triming out the fat
   }
   $info['title']="Edit Page: ".$this->path;
   $info['inner_body']=<<<HTML
	<!-- elFinder -->
	<script src="{$finderroot}/js/elfinder.min.js" type="text/javascript" charset="utf-8"></script>
	<link rel="stylesheet" href="{$finderroot}/css/elfinder.min.css" type="text/css" media="screen" charset="utf-8">
	<!-- elRTE -->
	<script src="{$editorroot}/js/elrte.min.js" type="text/javascript" charset="utf-8"></script>
	<link rel="stylesheet" href="{$editorroot}/css/elrte.min.css" type="text/css" media="screen" charset="utf-8">
	<script type="text/javascript" charset="utf-8">
		$().ready(function() {
			var opts = {
				cssClass : 'el-rte',
				fmOpen : function(callback) {
				$('<div />').dialogelfinder({
      						url: '{$finderroot}/php/connector.php',
      						commandsOptions: {
        						getfile: {
          							oncomplete: 'destroy' // destroy elFinder after file selection
        						}
      						},
      						getFileCallback: callback // pass callback to file manager
    					});
  				},
				// lang     : 'ru',
				height   : 375,
				toolbar  : 'maxi',
				cssfiles : ['{$editorroot}/css/elrte-inner.css']
			}
			$('#pagebody').elrte(opts);

			$('#access').click(function(){
				$('input#private').attr('disabled',!this.checked);
			});
		});
	</script>
<h2>Edit Page: {$this->path}</h2>
<form method=post>
<ul class="noindent nobullet">
<li><label for="name">Filename:</label> <input type=text name="pagename" id="name" value="{$page['name']}"></li>
<li><label for="title">Page Title:</label> <input type=text name="pagetitle" id="title" value="{$page['title']}"></li>
<li><input type=checkbox id="access"><label for="access">Limit access to this page?</label></li>
<li><label for="private">Groups that have access:</label> <input type=text name="private" id="private" disabled=disabled value="editor,members"></li>
<li><div id="pagebody">
{$page['body']}
</div></li>
</ul>
</form>
HTML;
   $this->info=$info;
   return true;
  }
 }

 public function get()
 {
  $authorized=true;
  if (file_exists($GLOBALS['SET']['pagedir'].pathinfo($this->cur_path,PATHINFO_DIRNAME).'/private.txt'))
  {
   $authorized=false;
   $private=explode(",",file_get_contents($GLOBALS['SET']['pagedir'].pathinfo($this->cur_path,PATHINFO_DIRNAME).'/private.txt'));
   $authorized=$this->hasAccess($private);
  }

  if ($authorized && file_exists($GLOBALS['SET']['pagedir'].$this->cur_path))
  {
   return file_get_contents($GLOBALS['SET']['pagedir'].$this->cur_path);
  }
  elseif (pathinfo($this->cur_path,PATHINFO_FILENAME) == 'new_page')
  {
   if (file_exists($GLOBALS['SET']['pagedir'].$this->cur_path))
   {
    return unlink($GLOBALS['SET']['pagedir'].$this->cur_path);
   }
   else
   {
    return file_get_contents($GLOBALS['SET']['basedir'].'/assets/templates/new_page.tpl.htm');
   }
  }
  elseif (!$authorized)
  {
   $page=new MomokoError("Forbidden");
   return $page->full_html;
  }
  else
  {
   $page=new MomokoError("Not_Found");
   return $page->full_html;
  }
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

 private function readInfo()
 {
  if (pathinfo($this->cur_path,PATHINFO_BASENAME) == 'map.mmk')
  {
   $info['title']='Site Map';
   $nav=new MomokoNavigation(null,'display=list');
   $dir=explode('/',$this->cur_path);
   $file=array_pop($dir);
   $map['@children']=$nav->map;
   foreach ($dir as $section)
   {
    foreach ($map['@children'] as $node)
    {
     if ($node['@name'] == 'site' && rtrim($node['@attributes']['dir'],'/') == $section)
     {
      $map=$node;
     }
    }
   }
   $info['inner_body']=$nav->getModule('html',$map['@children'],$map['@text'],$map['@attributes']['dir']);
  }
  else
  {
   $data=$this->get();
   if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$data,$match) > 0) //Find page title in $data
   {
    if (@$match['title'] && ($match['title'] != "[Blank]" && $match['title'] != "Blank")) //accept titles other than Blank and [Blank]
    {
     $info['title']=$match['title'];
    }
   }
   if (preg_match("/<private>(?P<private>.*?)<\/private>/smU",$data,$match) > 0) //Find page private in $data
   {
    if (@$match['private'])
    {
     $info['private']=explode(",",$match['private']);
    }
   }
   if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$data,$match) > 0) // Find page body in $data
   {
    $info['inner_body']=trim($match['body'],"\n\r"); //Replace the $body variable with just the page body found triming out the fat
   }
   if ((isset($info['private']) && is_array($info['private'])) && !$this->hasAccess($info['private']))
   {
    $page=new MomokoError("Forbidden");
    $info['title']=$page->title;
    $info['inner_body']=$page->inner_body;
    $info['full_html']=$page->full_html;
   }
   else
   {
    $info['full_html']=$data;
    $info['name']=pathinfo($this->cur_path,PATHINFO_BASENAME);
    $info['path']=$this->cur_path;
    $info['type']='page';
   }
  }

  return $info;
 }
 
 private function hasAccess(array $grouplist)
 {
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   return true;
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

 public function __construct($name,$msg=null,array $additional_vars=null)
 {
  $this->page=new MomokoPage('/error/'.$name.'.htm');
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
  $info=array('owner' => 'admin'); //TODO: replace with actual info parser
  
  return $info;
 }

 public function toHTML($child=null)
 {
  $html=$this->get();
  $vars['siteroot']=$GLOBALS['SET']['baseuri'];
  $vars['sitename']=$GLOBALS['SET']['name'];
  $vars['pagetitle']="Untitled";
  $vars['corestyles']=$vars['siteroot'].'/assets/core/styles/';
  $vars['templatedir']=$vars['siteroot'].dirname($this->template);
  $vars['pagedir']=$vars['siteroot'].PAGEROOT;
  
  if (@$child && (is_object($child)) && ($child instanceof MomokoObject))
  {
   $page=$child;
  }
  else
  {
   $page=new MomokoError('Tea_Time');
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

class MomokoPCModule implements MomokoModuleInterface
{
 public $opts=array();
 private $user;

 public function __construct($user,$opts)
 {
  parse_str($opts,$this->opts);
  $this->user=$user;
 }

 public function getModule($format='html')
 {
  $ext=pathinfo(@$_SERVER['PATH_INFO'],PATHINFO_EXTENSION);
  if ((empty($_SERVER['PATH_INFO']) || $ext == 'htm' || $ext == 'html') && (@$_GET['action'] != 'edit' && @$_GET['action'] != 'new'))
  {
    if ($this->opts['nouser'] == 'hidden' && ($this->user->inGroup('admin') || $this->user->inGroup('editor')))
    {
      return $this->buildControls($format);
    }
    elseif (@empty($this->opts['nouser']) || @$this->opts['nouser'] == 'visible')
    {
      return $this->buildControls($format);
    }
  }
  else
  {
   return null;
  }
 }

 private function buildControls($format)
 {
  switch ($this->opts['display'])
  {
   //TODO add icon and text cases for icons only and text only mode
   case 'toolbar':
   if (empty($this->opts['text']) || !$this->opts['text'] || $this->opts['text'] == 'none')
   {
    $text="false";
   }
   else
   {
    $text="true";
   }
   return <<<HTML
<style type="text/css">
.toolbar {
	float:left;
	width:100%;
}
</style>
<script language=javascript type="text/javascript">
$(function(){
	$("a#np").button({
		text:{$text},
		icons:{
			primary:'ui-icon-document'
		}
	});
	$("a#ep").button({
		text:{$text},
		icons:{
			primary:'ui-icon-pencil'
		}
	});
	$("a#rp").button({
		text:{$text},
		icons:{
			primary:'ui-icon-trash'
		}
	});
});
</script>
<span class="toolbar ui-widget-header ui-corner-all"><a id="np" href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=new">New Page</a><a id="ep" href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=edit">Edit Page</a><a id="rp" href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=delete">Delete Page</a></span>
HTML;
   break;
   case "icontext":
   default:
   return <<<HTML
<span class="pagecontrols"><a href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=new"><i class="controls c1"></i>New Page</a> <a href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=edit"><i class="controls pc2"></i>Edit This Page</a> <a href="//{$GLOBALS['SET']['baseuri']}/?q={$_GET['q']}&action=delete"><i class="controls pc3"></i>Delete This Page</a><span>
HTML;
   break;
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
    return file_get_contents($GLOBALS['SET']['pagedir']."/forms/addin".$this->form.".htm");
  }
  
  private function parse()
  {
    $info=parse_page($this->get());
    $vars['sitedir']=$GLOBALS['SET']['baseuri'];
    
    switch($this->form)
    {
      case 'remove':
      $vars['num']=$this->info['addin']->num;
      $vars['name']=$this->info['addin']->shortname;
      $vars['dir']=$this->info['addin']->dir;
      break;
      default:
      $table=new DataBaseTable('addins');
      $cols=$table->getFields();
      $vars['addin_cols']=null;
      foreach ($cols as $properties)
      {
	if ($properties->Field != 'num')
	{
	  if ($properties->Field == "incp")
	  {
	    $properties->Field="AdminCP Module";
	  }
	  $vars['addin_cols'].="<th class=\"ui-state-default\">".ucwords($properties->Field)."</th>";
	}
      }
      $vars['addin_cols'].="<th class=\"ui-state-default\">Actions</th>";
      unset($name,$properties);
      
      $data=$table->getData();
      $vars['addin_list']=null;
      while ($row=$data->fetch())
      {
	$vars['addin_list'].="<tr id=\"".$row['num']."\">\n";
	foreach ($cols as $name=>$properties)
	{
	  if ($name != 'num')
	  {
	    $vars['addin_list'].="<td id=\"".$name."\" class=\"ui-widget-content\">".$row[$name]."</td>";
	  }
	}
	$vars['addin_list'].="<td class=\"ui-widget-content\"><a class=\"ui-icon ui-icon-check\" style=\"display:inline-block\" onclick=\"toggleEnabled('".$row['num']."',event)\" title=\"Enable/Disable\" href=\"#toggleEnabled\"></a><a class=\"ui-icon ui-icon-arrowthickstop-1-n\" style=\"display:inline-block\" onclick=\"showUpdate('".$row['num']."',event)\" title=\"Update\" href=\"#update\"></a><a class=\"ui-icon ui-icon-trash\" style=\"display:inline-block\" onclick=\"showRemove('".$row['num']."',event)\" title=\"Delete\" href=\"#delete\"></a></td>\n</tr>\n";
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
    $filename=$GLOBALS['CFG']->tempdir.'/'.$file['name'];
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
$('span#msg',pDoc).html('{$error}');
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

