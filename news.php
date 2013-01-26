<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

class MomokoNewsPage implements MomokoObject
{
  private $path;
  private $id;
  private $info;

  public function __construct($path)
  {
    $this->path=$path;
    $this->id=pathinfo($path,PATHINFO_FILENAME);
    $this->info=$this->fillProperties();
    $this->info['inner_body']=$this->get();
    $this->info['title']=$this->info['headline'];
    $this->info['full_html']=<<<HTML
<head>
<html>
<title>{$this->info['title']}</title>
</head>
<body>
{$this->info['inner_body']}
</body>
</html>
HTML;
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
    $type=pathinfo($this->path,PATHINFO_EXTENSION);
    switch ($type)
    {
      case 'htm':
      case 'html':
      $this->info['type']='html';
      return $this->toHTML();
      break;
      case 'xml':
      default:
      $this->info['type']='xml';
      return $this->toXML();
      break;
    }
  }
	
  public function toHTML()
  {
    $fdate=date($GLOBALS['USR']->longdateformat,$this->date);
    $self=pathinfo($this->path,PATHINFO_BASENAME);
    $html=<<<HTML
<h2 class="headline">{$this->headline}</h2>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<div class="date">{$fdate}</div>
<div class="article">{$this->article}</div>
<div class="fb-comments" data-href="//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/news.php/{$self}" data-num-posts="2" data-width="470"></div>
HTML;
    return $html;
  }

  public function toXML()
  {
    $dom=new DOMDocument('1.0', 'UTF-8');
    $feed=$dom->appendChild(new DOMElement('feed',null,'http://www.w3.org/2005/Atom'));
    $title=$feed->appendChild(new DOMElement('title',$this->headline));
    $update=$feed->appendChild(new DOMElement('updated',gmdate('Y-m-d\TH:i:s\Z',$this->update)));
    $summary=$feed->appendChild(new DOMElement('summary',$this->summary));
	
    return $dom->saveXML();
  }

  private function fillProperties()
  {
    $news_reel=new MomokoNews($GLOBALS['USR'],'sort=recent');
    $list=$news_reel->getModule('array');
    return $list[$this->id];
  }
}

class MomokoNewsManager implements MomokoObject
{
 public $path;
 private $info=array();

 public function __construct($path)
 {
  $this->path=$path;
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
  if (empty($this->path) || $_GET['action'] == 'new')
  {
   $info['headline']="New Article";
   $info['ndate']=date("d M Y");
   $info['time']=null;
   $info['summary']="";
   $info['article']="<p><br></p>";
  }
  else
  {
   $news_reel=new MomokoNews($GLOBALS['USR'],'sort=recent');
   $list=$news_reel->getModule('array');
   $info=$list[pathinfo($this->path,PATHINFO_FILENAME)];
   $info['ndate']=date('d M Y',$info['date']);
   $info['time']=date('H:i:s',$info['date']);
  }
  return $info;
 }

 public function getPage($action)
 {
  switch ($action)
  {
   case 'delete':
   $news_reel=new MomokoNews($GLOBALS['USR'],'sort=recent');
   $data=$news_reel->put($_POST);
   if ($news_reel->write($data))
   {
    header ("Location: ?success=true");
    exit();
   }
   else
   {
    $page=new MomokoError('Server_Error');
    $info['title']=$page->title;
    $info['inner_body']=$page->inner_body;
   }
   break;
   case 'new':
   case 'edit':
   default:
   if (empty($_POST['headline']))
   {
    $article=$this->get();
    $editorroot='//'.$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.'/assets/scripts/elrte';
    $finderroot='//'.$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.'/assets/scripts/elfinder';
    $info['title']=ucwords($action)." Article";
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
			$('#article').elrte(opts);

			$('#date').datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: "dd M yy"
			});

			if ($("input#time").val() == ''){
				writeTime();
			}
		});

function writeTime()
{

 var currtime = new Date(); //creates representation of current time on user's local computer
 var currhour = currtime.getHours(); //gets current hour (in 24-hour format)
 var currminute = currtime.getMinutes(); //gets current minute
 var currsecond = currtime.getSeconds(); //gets current time

 if (currminute < 10) //if current minute is one digit number
	currminute = "0" + currminute; //place zero in front of it

 if (currsecond < 10) //if current second is one digit number
	currsecond = "0" + currsecond; //place zero in front of it

 $("input#time").val(currhour + ":" + currminute + ":" + currsecond); //update text field displaying time
var id=setTimeout("writeTime()",1000); //set function to run again, 1 second from now (1000 milliseconds)
 $("input#time").focus(function(){
  clearTimeout(id);
 });
}
	</script>

<h2>{$info['title']}</h2>
<form method=post>
<ul class="nobullet noindent">
<li><label for="title">Headline:</label> <input type=text id="title" size=30 name="headline" value="{$article['headline']}"></li>
<li><label for="date">Date:</label> <input type=text id="date" size=10 name="date" value="{$article['ndate']}"> <label for="time">Time:</label> <input type=text id="time" size=8 name="time" value="{$article['time']}"></li>
<li><label for="summary">Summary (do not include HTML!):</label><br>
<textarea name="summary" cols=45 rows=5 id="summary">{$article['summary']}</textarea>
<li><label for="eltrte">Article:</label>
<div id="article">{$article['article']}</div>
</ul>
</form>
HTML;
   }
   else
   {
    $news_reel=new MomokoNews($GLOBALS['USR'],'sort=recent');
    if (@$_GET['action'] == 'new')
    {
     $data=$news_reel->put($_POST);
    }
    elseif (@$_GET['action'] == 'edit')
    {
     $data=$news_reel->update($_POST,pathinfo($this->path,PATHINFO_FILENAME));
    }

    if ($news_reel->write($data))
    {
     header ("Location: ?success=true");
     exit();
    }
    else
    {
     $page=new MomokoError('Server_Error');
     $info['title']=$page->title;
     $info['inner_body']=$page->inner_body;
    }
   }
  }
  $info['type']='html';
  $this->info=$info;
  return;
 }
}

if (@$_SERVER['PATH_INFO'])
{
 $path=$_SERVER['PATH_INFO'];
}
else
{
 $path=null;
}

switch (@$_GET['action'])
{
 case 'login':
 if (@!empty($_POST['password']))
 {
  if ($GLOBALS['USR']->login($_POST['name'],$_POST['password']))
  {
   $_SESSION['data']=serialize($GLOBALS['USR']);
   if (@!empty($_GET['re']))
   {
    header("Location: ?action=".$_GET['re']);
   }
   else
   {
    header("Location: ?loggedin=1");
   }
   exit();
  }
  else
  {
   $child=new MomokoError('Unauthorized');
  }
 }
 else
 {
  $child=new MomokoForm('login');
 }
 break;
 case 'register':
 if (@$_POST['first'])
 {
  $usr=new MomokoUser($_POST['name']);
  if ($usr->put($_POST))
  {
   header("Location:/?action=login");
   exit();
  }
 }
 else
 {
  $child=new MomokoForm('register');
 }
 break;
 case 'logout':
 if ($GLOBALS['USR']->logout())
 {
  $_SESSION['data']=serialize($GLOBALS['USR']);
  header("Location: ?loggedin=0");
 }
 break;
 case 'new':
 case 'edit':
 case 'delete':
 $child=new MomokoNewsManager($path);
 $child->getPage($_GET['action']);
 break;
 default:
 $child=new MomokoNewsPage($path);
}

if ($child->type == 'html')
{
 $tpl=new MomokoTemplate(pathinfo($path,PATHINFO_DIRNAME));
 print $tpl->toHTML($child);
}
else
{
 header("Content-type: text/xml");
 print $child->inner_body;
}

?>
