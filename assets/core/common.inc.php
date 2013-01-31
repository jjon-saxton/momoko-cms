<?php
#This holds configuration
$GLOBALS['CFG']=new MomokoConfiguration();
#Set Constants
if ($GLOBALS['CFG']->rewrite)
{
 define("ADMINROOT","/admin/");
 define("PAGEROOT","/page/");
 define("FILEROOT","/file/");
 define("HELPROOT","/help/");
 define("ADDINROOT","/addin/");
 define("NEWSROOT","/news/");
}
else
{
 define("ADMINROOT","/admin.php/");
 define("PAGEROOT","/index.php/");
 define("FILEROOT","/file.php/");
 define("HELPROOT","/help.php/");
 define("ADDINROOT","/addin.php/");
 define("NEWSROOT","/news.php/");
}

define("MOMOKOVERSION",trim(file_get_contents($GLOBALS['CFG']->basedir.'/assets/etc/version.nfo.txt'),"\n"));
require $GLOBALS['CFG']->basedir.'/assets/dal/load.inc.php';

require_once $GLOBALS['CFG']->basedir.'/assets/core/user.inc.php';
if (!defined("INCLI") &&  basename($_SERVER['PHP_SELF']) != 'install.php')
{
 #user session now added here as part of MomoKO merge

 session_name($GLOBALS['CFG']->session);
 session_start();

 if (@$_SESSION['data'])
 {
  $GLOBALS['USR']=unserialize($_SESSION['data']);
 }
 else
 {
  $GLOBALS['USR']=new MomokoSession();
  $_SESSION['data']=serialize($GLOBALS['USR']);
 }
}

interface MomokoModuleInterface
{
  public function __construct($user,$options);
  public function getModule($format='html');
}

interface MomokoPageObject
{
  public function toHTML($child=null);
}

interface MomokoObject
{
 public function __construct($path);
 public function __get($var);
 public function __set($key,$value);
 public function get();
}

class MomokoConfiguration
{
  protected $cfg=array();

  public function __construct($file=null)
  {
    if (@!$file)
    {
      $file='./assets/etc/main.conf.txt';
    }
    $txt=file_get_contents($file);
    if (preg_match_all("/#{(?P<key>.*?):(?P<value>.*?)}/",$txt,$properties) > 0)
    {
      $i=0;
      foreach ($properties['key'] as $key)
      {
	$configuration[$key]=$properties['value'][$i];
	++$i;
      }
    }

    if(is_array(@$configuration))
    {
      if ((!array_key_exists('domain',$configuration) || empty($configuration['domain'])) && !defined("INCLI"))
      {
       $configuration['domain']=$_SERVER['SERVER_NAME']; //guess the domain everytime if it is not supplied in configuration. Useful if you're domain changes a lot for whatever reason =^.~=
      }
      $this->cfg=$configuration;
    }
  }

  public function __get($key)
  {
    if (array_key_exists($key,$this->cfg))
    {
      return $this->cfg[$key];
    }
    else
    {
      return false;
    }
  }

  public function __set($key,$value)
  {
    if (array_key_exists($key,$this->cfg))
    {
      $this->cfg[$key]=$value;
      return true;
    }
    else
    {
      return false;
    }
  }
}

class MomokoVariableHandler
{
  private $varlist=array();

  public function __construct(array $varlist)
  {
	  $this->varlist=$varlist;
  }

  public function __get($key)
  {
	  if (array_key_exists($key,$this->varlist))
	  {
		  return $this->varlist[$key];
	  }
    else
    {
      return false;
    }
  }

  private function loadMod($item,$argstr=null)
  {
    switch ($item)
    {
      case 'usercontrols':
      if (@$GLOBALS['USR'] instanceof MomokoSession)
      {
        $mod=new MomokoUCPModule($GLOBALS['USR'],$argstr);
        return $mod->getModule('html');
      }
      else
      {
        return null;
      }
      break;
      case 'pagecontrols':
      if (@$GLOBALS['USR'] instanceof MomokoSession)
      {
        $mod=new MomokoPCModule($GLOBALS['USR'],$argstr);
        return $mod->getModule('html');
      }
      else
      {
        return null;
      }
      break;
      case 'nav':
      $mod=new MomokoNavigation(null,$argstr);
      return $mod->getModule('html');
      break;
      case 'news':
      $mod=new MomokoNews(null,$argstr);
      return $mod->getModule('html');
      break;
      default: //TODO: add code to look for plugin modules
      return "<!-- Module '{$item}' not found -->";
      break;
    }
  }

  private function loadAddin($addin,$mod,$argstr)
  {
   $tbl=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
   $data=$tbl->getData('dir','shortname~'.$addin,null,1);
   $data=$data->first();
   if ($data->dir)
   {
    $xml=simplexml_load_string(file_get_contents($GLOBALS['CFG']->basedir.'/assets/addins/'.$data->dir.'/manifest.xml'));
   }
   elseif (file_exists($GLOBALS['CFG']->basedir.'/assets/'.$addin.'/manifest.xml'))
   {
    $xml=simplexml_load_string(file_get_contents($GLOBALS['CFG']->basedir.'/assets/'.$addin.'/manifest.xml'));
   }
   else
   {
    return "<!-- Addin '{$addin}' not found -->";
   }

   $nav=new MomokoNavigation(null,'display=none');
   $nav->convertXmlObjToArr($xml,$manifest);
   foreach ($manifest as $node)
   {
    if ($node['@name'] == 'dirroot')
    {
     $dirroot=$GLOBALS['CFG']->basedir.$node['@text'];
    }
    elseif ($node['@name'] == 'module' && $node['@text'] == strtolower($mod))
    {
     $include=$node['@attributes']['file'];
     $class=$node['@attributes']['class'];
    }
   }

   require_once $dirroot.$include;
   $mod=new $class(@$GLOBALS['USR'],$argstr);
   return $mod->getModule('html');
  }

  public function replace($text)
  {
    //Replace variables
    if (preg_match_all("/~{(?P<var>.*?)}/",$text,$list))
    {
      foreach ($list['var'] as $var)
      {
	     if (@$this->$var)
	     {
	      $text=preg_replace("/~{".$var."}/",$this->$var,$text);
	     }
	     else
	     {
	      $text=preg_replace("/~{".$var."}/","",$text);
	     }
      }
    }

    if (preg_match_all("/<!-- VAR:(?P<var>.*?) -->/",$text,$list))
    {
      foreach ($list['var'] as $var)
      {
	     if (@$this->$var)
	     {
	      $text=preg_replace("/<!-- VAR:".$var." -->/",$this->$var,$text);
	     }
	     else
	     {
	      $text=preg_replace("/<!-- VAR:".$var." -->/","<!-- Notice: variable '".$var."' not set or empty -->",$text);
	     }
      }
    } 

    if (preg_match_all("/<var>(?P<var>.*?)<\/var>/",$text,$list))
    {
      foreach ($list['var'] as $var)
      {
	     if (@$this->$var)
	     {
	      $text=preg_replace("/<var>".$var."<\/var>/",$this->$var,$text);
	     }
	     else
	     {
	      $text=preg_replace("/<var>".$var."<\/var>/","<!-- Notice: variable '".$var."' not set or empty -->",$text);
	     }
      }
    }

    //Replace DataBase Blocks
    if (preg_match_all("/<!-- DATABASE:(?P<base_query>.*?)\/\/ -->(?P<rows>.*?)<!-- \/\/DATABASE -->/smU",$text,$list))
    {
      $tracker=0;
      $rows=null;
      foreach ($list['base_query'] as $query)
      {
       list($table,$arg_str)=explode(":",$query);
       parse_str($arg_str,$args);
       if (!@$args['database'])
       {
         $args['database']=DAL_DB_DEFAULT;
       }
       $db=new DataBaseTable($table,$args['database']);
       $data=$db->getData(@$args['cols'],@$args['where'],@$args['sort'],@$args['limit'],@$args['offset']);
       $template=$list['rows'][$tracker];
       while ($row=$data->next())
       {
	$row_html=$template;
        if (preg_match_all("/<!-- DATA:(?P<col>.*?) -->/",$row_html,$list) > 0)
        {
         foreach ($list['col'] as $column)
         {
          if ($row->$column !== NULL)
          {
           $row_html=preg_replace("/<!-- DATA:".preg_quote($column)." -->/",$row->$column,$row_html);
          }
         }
        }

        if (preg_match_all("/@{(?P<col>.*?)}/",$row_html,$list) > 0)
        {
         foreach ($list['col'] as $column)
         {
          if ($row->$column !== NULL)
          {
           $row_html=preg_replace("/@{".preg_quote($column)."}",$row->$column,$row_html);
          }
         }
        }
	$rows.=$row_html;
       }
       $text=preg_replace("/<!-- DATABASE:".preg_quote($query)."\/\/ -->(.*?)<!-- \/\/DATABASE -->/smU",$rows,$text);
      }
     }

    //Replace module blocks
    if (preg_match_all("/<!-- MODULE:(?P<item>.*?) -->/",$text,$list))
    {
      foreach ($list['item'] as $item)
      {
        list($name,$options,)=explode(":",$item);
        $text=preg_replace("/<!-- MODULE:".preg_quote($item,"/")." -->/",$this->loadMod($name,$options),$text);
      }
    }

    //Replace addin module blocks
    if (preg_match_all("/<!-- ADDIN:(?P<item>.*?) -->/",$text,$list))
    {
      foreach ($list['item'] as $item)
      {
        list($addin,$name,$options,)=explode(":",$item);
        $text=preg_replace("/<!-- ADDIN:".preg_quote($item)." -->/",$this->loadAddin($addin,$name,$options),$text);
      }
    }

    return $text;
  }
}

class SimpleXMLExtended extends SimpleXMLElement
{   
 public function addCData($cdata_text)
 {
  $node= dom_import_simplexml($this);   
  $no = $node->ownerDocument;   
  $node->appendChild($no->createCDATASection($cdata_text));   
 }   
} 

#Misc functions
function strrtrim($message, $strip)
{
  // break message apart by strip string
  $lines = explode($strip, $message);
  $last  = '';
  // pop off empty strings at the end
  do
  {
    $last = array_pop($lines);
  }
  while (empty($last) && (count($lines)));
  // re-assemble what remains
  return implode($strip, array_merge($lines, array($last)));
}

function build_sorter($key)
{
 return function ($a, $b) use ($key)
	{
  return strnatcmp($a[$key], $b[$key]);
 };
}

function xmltoarray($file)
{
 $xml=simplexml_load_string(file_get_contents($file));
 $nav=new MomokoNavigation(null,'display=none');
 $nav->convertXmlObjToArr($xml,$array);

 return $array;
}

function parse_page($data)
{
 $array=array();
 if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$data,$match) > 0) //Find page title in $data
 {
  if (@$match['title'] && ($match['title'] != "[Blank]" && $match['title'] != "Blank")) //accept titles other than Blank and [Blank]
  {
   $array['title']=$match['title'];
  }
 }
 if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$data,$match) > 0) // Find page body in $data
 {
  $array['inner_body']=trim($match['body'],"\n\r"); //Replace the $body variable with just the page body found triming out the fat
 }
 return $array;
}

function join_dom(DOMDocument $DOMParent, DOMDocument $DOMChild, $tag = null)
{
 $node = $DOMChild->documentElement;
 $node = $DOMParent->importNode($node, true);

 if ($tag !== null)
 {
  $tag = $DOMParent->getElementsByTagName($tag)->item(0);
  $tag->appendChild($node);
 }
 else
 {
  $DOMParent->documentElement->appendChild($node);
 }

 return $DOMParent;
}
