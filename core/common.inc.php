<?php
#Load settings from database
require_once dirname(__FILE__).'/database.inc.php';
$settings=new DataBaseTable('settings');
$settings=$settings->getData();
$settings=$settings->fetchAll();
foreach ($settings as $pairs)
{
 $GLOBALS['SET'][$pairs['key']]=$pairs['value'];
}
if (empty($GLOBALS['SET']['baseuri']))
{
 $GLOBALS['SET']['baseuri']=$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']);
}

#Set Constants
if ($GLOBALS['SET']['rewrite'])
{
 define("ADMINROOT","/mk_dash/");
 define("PAGEROOT","/page/");
 define("FILEROOT","/mk_content/");
 define("ADDINROOT","/mk_addin/");
 define("NEWSROOT","/news/");
 define("QUERYSTARTER","?");
}
else
{
 define("ADMINROOT","/mk_dash.php/");
 define("PAGEROOT","/?q=page/");
 define("FILEROOT","/?q=file/");
 define("ADDINROOT","/?q=addin/");
 define("NEWSROOT","/?q=news/");
 define("QUERYSTARTER","&");
}
define("TEMPLATEROOT","/templates/");


unset($setting,$pairs);
define("MOMOKOVERSION",trim(file_get_contents($GLOBALS['SET']['basedir'].'/version.nfo.txt'),"\n"));
define ("TEMPLATEPATH",TEMPLATEROOT.$GLOBALS['SET']['template'].'/'.$GLOBALS['SET']['template'].'.tpl.htm');

session_name($GLOBALS['SET']['sessionname']);
session_start();

require_once $GLOBALS['SET']['basedir'].'/core/user.inc.php';

if (@$_SESSION['data'])
{
 $GLOBALS['USR']=unserialize($_SESSION['data']);
}
else
{
 $GLOBALS['USR']=new MomokoSession();
 $_SESSION['data']=serialize($GLOBALS['USR']);
}
 
if ($GLOBALS['SET']['error_logging'] > 0)
{
 set_error_handler("momoko_html_errors"); //TODO need to set cli handler if we are running in cli mode
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
 public function __get($var);
 public function __set($key,$value);
 public function get();
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

  public function evalIf($exp,$true_block,$false_block=null)
  {
   if (eval("return $exp;"))
   {
    return $true_block;
   }
   else
   {
    return $false_block;
   }
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
    //Evaluate If statements to produce the best block
    if (preg_match_all("/<!-- TemplateIF:(?P<expression>.*?)\/\/ -->(?P<iftrue>.*?)<!-- \/\/EndIF -->/smU",$text,$list)) //simple if only
    {
     $tracker=0;
     foreach ($list['expression'] as $exp)
     {
      $text=preg_replace("/<!-- TemplateIF:".preg_quote($exp)."\/\/ -->(.*?)<!-- \/\/EndIF -->/smU",$this->evalIF($exp,$list['iftrue'][$tracker]),$text);
      $tracker++;
     }
    }

    if (preg_match_all("/<!-- TemplateIF:(?P<expression>.*?)\/\/ -->(<?P<iftrue>.*?)<!-- \/\/ELSE\/\/ -->(<?P<iffalse>.*?)<!-- \/\/EndIF -->/smU",$text,$list)) //if/else
    {
     $tracker=0;
     foreach ($list['expression'] as $exp)
     {
      $text=preg_replace("/<!-- TemplateIF:".preg_quote($exp)."\/\/ -->(.*?)<!-- \/\/EndIF -->/smU",$this->evalIF($exp,$list['iftrue'][$tracker],$list['iffalse'][$tracker]),$text);
      $tracker++;
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

#Error handlers
function momoko_html_errors($num,$str,$file,$line,$context)
{
  if (($num != E_USER_NOTICE && $num != E_NOTICE) || ($GLOBALS['SET']['error_logging'] > 1))
  {
    if (file_exists($GLOBALS['SET']['logdir'].'error.log'))
    {
      $log=fopen($GLOBALS['SET']['logdir'].'error.log','a') or die("Error log could not be open for write!");
      fwrite($log,"[".date("Y-m-d H:i:s")."] PHP Error (".$num."; ".$str.") in ".$line." of ".$file."!\n");
    }
    else
    {
      die("Error log does not exist at configured location: ".$GLOBALS['CFG']->logdir."!");
    }
  }
  
  if ($num == E_USER_ERROR)
  {
    $info['error_type']=$num;
    $child=new MomokoError('Server_Error',$str,$info);

    $tpl=new MomokoTemplate(pathinfo(@$path,PATHINFO_DIRNAME));
    print $tpl->toHTML($child);
    die();
  }
}

function momoko_cli_errors($num,$str,$file,$line,$context)
{
  if (($num != E_USER_NOTICE && $num != E_NOTICE) || ($GLOBALS['SET']['error_logging'] > 1))
  {
    if (file_exists($GLOBALS['CFG']->logdir.'/error.log'))
    {
      $log=fopen($GLOBALS['CFG']->logdir.'/error.log','a') or die("Error log could not be open for write!");
      fwrite($log,"[".date("Y-m-d H:i:s")."] PHP Error (".$num."; ".$str.") in ".$line." of ".$file."!\n");
    }
    else
    {
      die("Error log does not exist at configured location: ".$GLOBALS['CFG']->logdir."!");
    }
  }
  
  if ($num == E_USER_ERROR)
  {
    fwrite(STDOUT,"Fetal Error: ".$str." in line ".$line." of ".$file."!\n");
    exit(2);
  }
}

#Change logging handler
function momoko_changes($user,$action,$resource,$message=null)
{
  if ($GLOBALS['SET']['security_logging'] > 0)
  {
      switch (get_class($resource))
      {
	case 'MomokoPage':
	$target="Page ".$resource->title;
	break;
	case 'MomokoNews':
	$target="News Article ".$resource->title;
	break;
        case 'MomokoNavigation':
	$target="Site Map";
	break;
	default:
	$target="Addin Object".@$resource->path;
      }
      
    momoko_basic_changes($user,$action,$target,$message);
  }
}

function momoko_basic_changes($user,$action,$target,$message=null)
{
  if ($GLOBALS['SET']['security_logging'] > 0)
  {
    if (is_writable($GLOBALS['CFG']->logdir.'/changes.log'))
    {
      if (!empty($message))
      {
        $message=": ".$message;
      }
      $log=fopen($GLOBALS['CFG']->logdir.'/changes.log','a');
      fwrite($log,"[".date("Y-m-d H:i:s")."] ".$user->name." (".$user->num.":".$_SERVER['REMOTE_ADDR'].") ".$action." ".$target.$message."\n");
    }
    else
    {
      trigger_error("changes.log is not writable!",E_USER_WARNING);
    }
  }
}

#Misc functions
function rmdirr($dir,$empty_only=false)
{
  rtrim($dir,"/");
  if (file_exists($dir) && is_readable($dir))
  {
    $handle=opendir($dir);
    while (FALSE !== ($item=readdir($handle)))
    {
      if ($item != '.' && $item != '..')
      {
	$path=$dir.'/'.$item;
	if (is_dir($path))
	{
	  rmdirr($path);
	}
	else
	{
	  unlink($path);
	}
      }
    }
    closedir($handle);
    
    if ($empty_only == FALSE)
    {
      if (!rmdir($dir))
      {
	trigger_error("Unable to remove folder!",E_USER_ERROR);
	return false;
      }
    }
    return true;
  }
  elseif (!file_exists($dir))
  {
    trigger_error("Directory '{$dir}' does not exists!",E_USER_ERROR);
    return false;
  }
  else
  {
    trigger_error("Directory '{$dir}' could not be opened for read!",E_USER_ERROR);
    return false;
  }
}

function strrtrim($message,$strip)
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