<?php
#Load settings from database
require_once dirname(__FILE__).'/database.inc.php';
$config=new MomokoSiteConfig();
$config->sys_groups=array('nobody','users','suspended','editor','cli','admin');

//Set Constants
if ($config->rewrite)
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
define("TEMPLATEROOT","/mk-content/addins/");

session_name($config->sessionname);
session_start();

require_once $config->basedir.'/mk-core/user.inc.php';

if (@$_SESSION['data'])
{
 $auth=unserialize($_SESSION['data']);
}
else
{
 $auth=new MomokoSession();
 $_SESSION['data']=serialize($auth);
}

$_SESSION['modern']=false;
if (!empty($_COOKIE['ss']))
{
 $_SESSION['modern']=$_COOKIE['ss'];
}

if (!defined("MOMOKOVERSION"))
{
 define("MOMOKOVERSION",trim(file_get_contents($config->basedir.'/version.nfo.txt'),"\n"));
}
if ($_SESSION['modern'] == 'full') //For browsers supporting cookies, javascript, and css
{
 define ("TEMPLATEPATH",TEMPLATEROOT.$config->template.'/'.$config->template.'.tpl.htm');
}
else //legacy browser fallback
{
 define ("TEMPLATEPATH",TEMPLATEROOT."1997/1997.tpl.htm");
}
 
if ($config->error_logging > 0)
{
 set_error_handler("momoko_html_errors"); //TODO need to set cli handler if we are running in cli mode
}

class MomokoSiteConfig
{
 private $temp=array();
 private $table;

 public function __construct()
 {
   $this->table=new DataBaseTable('settings');

   if (empty($this->baseuri) && (!defined("INCLI") || INCLI)) //Set some defaults
   {
    $this->baseuri=$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']);
   }
   if ($this->use_ssl == "strict") //ADD protocol
   {
    $this->sec_protocol="https://";
    $this->siteroot="https://".$this->baseuri;
   }
   else
   {
    if ($this->use_ssl == "yes")
    {
     $this->sec_protocol="https://";
    }
    else
    {
     $this->sec_protocol="http://";
    }
    $this->siteroot="//".$this->baseuri;
   }

   return $this->table;
 }

 public function __get($key)
 {
  $query=$this->table->getData("key:`{$key}`",array('value'),null,1);
  $set=$query->fetch(PDO::FETCH_ASSOC);

  if (array_key_exists($key,$this->temp))
  {
   return $this->temp[$key];
  }
  elseif (!empty($set['value']))
  {
   return $set['value'];
  }
  else
  {
   return false;
  }
 }

 public function __set($key,$value)
 {
  return $this->temp[$key]=$value;
 }

 public function getSettings($as='array')
 {
  $query=$this->table->getData();
  $settings=array();
  while ($set=$query->fetch(PDO::FETCH_ASSOC))
  {
   $settings[$set['key']]=$set['value'];
  }
  $settings=array_merge($settings,$this->temp);

  switch ($as)
  {
   case 'ini':
   $ini=null;
   foreach ($settings as $key=>$value)
   {
    $ini.=$key." = \"".$value."\"\n";
   }
   return $ini;
   break;
   case 'string':
   return http_build_query($settings);
   break;
   case 'json':
   return json_encode($settings);
   break;
   case 'array':
   default:
   return $settings;
  }
 }

 public function saveTemp()
 {
  $status=array();
  foreach ($this->temp as $key=>$value)
  {
   $data['key']=$key;
   $data['value']=$value;
   if ($query=$this->table->getData("key:`{$key}`",null,null,1))
   {
    $set=$query->fetch(PDO::FETCH_ASSOC);
    if (!empty($set['key']))
    {
     $status[$key]=$this->table->updateData($data) or die(trigger_error("Setting {$key} could not be updated!",E_USER_ERROR));
    }
    else
    {
     $status[$key]=$this->table->putData($data) or die(trigger_error("Setting {$key} could not be updated!",E_USER_ERROR));
    }
   }
   else
   {
    $status[$key]=$this->table->putData($data);
   }
  }

  $this->temp=array(); //empty temp array
  return $status;
 }
}

class MomokoModule
{
	public function settingsToHTML(array $values)
	{
	 $html="<ul id=\"settings\" class=\"noindent nobullet\">\n";
	 foreach ($this->opt_keys as $key=>$value)
	 {
	  $item="<li><label for=\"{$this->info->dir}-{$key}\">{$key}: </label>";
	  switch ($value['type'])
	  {
	   case 'text':
	   case 'number':
	   $item.="<input id=\"{$this->info->dir}-{$key}\" type={$value['type']} size=10 name=\"{$this->info->num}[{$key}]\" value=\"{$values[$key]}\"></li>\n";
	   break;
	   case 'link':
	   $item.="<input id=\"{$this->info->dir}-{$key}\" type=\"text\" size=5 name=\"{$this->info->num}[{$key}]\" value=\"{$values[$key]}\"><button id=\"{$key}\" class=\"linkbrowse btn btn-info btn-sm\">Browse...</button></li>\n";
       break;
	   case 'select':
	   $item.="<select id=\"{$this->info->dir}-{$key}\" name=\"{$this->info->num}[{$key}]\">\n";
	   foreach ($value['options'] as $option)
	   {
	    if ($option == $values[$key])
	    {
	     $item.="<option selected=selected>{$option}</option>\n";
	    }
	    else
	    {
	     $item.="<option>{$option}</option>\n";
	    }
	   }
	   $item.="</select>";
	   break;
	  }
	  $html.=$item;
	 }
	 return $html.="</ul>";
	}
}

interface MomokoModuleInterface
{
  public function __construct(MomokoSession $user);
  public function getModule($format='html');
  public function getInfoFromDB();
}

interface MomokoPageAddinInterface
{
  public function __construct($settings, MomokoSession $user);
  public function getPage();
  public function getForm();
}

interface MomokoPageObject
{
  public function toHTML($child=null);
}

interface MomokoObject
{
 public function __get($var);
 public function __set($key,$value);
}

class MomokoVariableHandler
{
  private $varlist=array();
  private $user;
  private $config;

  public function __construct(array $varlist,MomokoSession $user)
  {
   $this->varlist=$varlist;
   $this->user=$user;
   $this->config=new MomokoSiteConfig();
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
      case 'pagecontrols':
      if (@$this->user instanceof MomokoSession)
      {
        $mod=new MomokoPCModule($this->user,$argstr);
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
      default: //TODO: add code to look for plugin modules
      return "<!-- Module '{$item}' not found -->";
      break;
    }
  }

  private function loadModsByZone($q)
  {
   parse_str($q,$info);
   $table=new DataBaseTable("addins");
   $query=$table->getData("zone:'= ".$info['id']."'",array('num','dir','type'),'order');
   $text=null;

   if($query->rowCount() > 0)
   {
    while ($module=$query->fetch(PDO::FETCH_ASSOC))
    {
     if ($module['type'] == 'module') //Sanity check!
     {
      require_once $this->config->basedir."/".$this->config->filedir."addins/{$module['dir']}/".$module['type'].".php";
      $class="Momoko".ucwords($module['dir'])."Module";
      $mod=new $class($this->user);
      $text.=$mod->getModule('html');
     }
    }
   }
   else
   {
    $text.="<!-- No Modules Set for Zone: {$info['id']} -->";
   }
   
   return $text;
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

    //Replace Navigation comment
    if (preg_match("/<!-- NAVIGATION:(?P<arguments>.*?) -->/",$text,$list))
    {
     $text=preg_replace("/<!-- NAVIGATION:".preg_quote($list['arguments'],"/")." -->/",$this->loadMod('nav',$list['arguments']),$text);
    }

    //Replace module zone blocks
    if (preg_match_all("/<!-- MODULEZONE:(?P<arguments>.*?) -->/",$text,$list))
    {
      foreach ($list['arguments'] as $query)
      {
        $text=preg_replace("/<!-- MODULEZONE:".preg_quote($query)." -->/",$this->loadModsByZone($query),$text);
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

function file_url($url){
  $parts = parse_url($url);
  $path_parts = array_map('rawurldecode', explode('/', $parts['path']));

  return $parts['scheme'].'://'.$parts['host'].implode('/', array_map('rawurlencode', $path_parts));
}

#Error handlers
function momoko_html_errors($num,$str,$file,$line,$context)
{
  $cfg=new MomokoSiteConfig();
  if (($num != E_USER_NOTICE && $num != E_NOTICE) || ($cfg->error_logging > 1))
  {
   $text="PHP Error (".$num."; ".$str.") on line ".$line." of ".$file."!\n";
   try
   {
    $table=new DataBaseTable('log');
   }
   catch (Exception $err)
   {
    echo "Unable to open database connection. ".$err->getMessage()." This error cannot be recorded!\n";
    echo $text;
    if ($num == E_USER_ERROR || $num == E_ERROR)
    {
     exit();
    }
   }
   
   switch ($num)
   {
    case E_USER_NOTICE:
    case E_NOTICE:
    $msg_type="notice";
    break;
    case E_USER_WARNING:
    case E_WARNING:
    $msg_type="warning";
    break;
    case E_USER_ERROR:
    case E_ERROR:
    $msg_type="cerror";
    break;
    default:
    $msg_type="uerror";
   }
   
   $error['time']=date("Y-m-d H:i:s");
   $error['type']=$msg_type;
   $error['action']="error caught";
   $error['message']=$text;
   
   if (@$table instanceof DataBaseTable)
   {
    try
    {
     $log_id=$table->putData($error);
    }
    catch (Exception $err)
    {
     echo "Error could not be recorded! ".$err->getMessage();
    }
   }
  }
  
  if ($num == E_USER_ERROR)
  {
    $info['error_type']=$num;
    if (class_exists('MomokoError'))
    {
     $child=new MomokoError('Server_Error',$str,$info);

     $tpl=new MomokoTemplate(pathinfo(@$path,PATHINFO_DIRNAME));
     print $tpl->toHTML($child);
    }
    else
    {
     http_response_code(500);
     print <<<HTML
<!doctype html>
<html>
<head>
<title>500 Internal Server Error</title>
</head>
<body>
<div class="message error box">
<h2>Internal Server Error</h2>
<p>The server encountered the following error. Additionally the class MomokoError was not found so this page could not be properly displayed.</p>
<p class="error message">{$str}</p>
</div>
</body>
</html>
HTML;
    }
    die();
  }
}

function momoko_cli_errors($num,$str,$file,$line,$context)
{
  $cfg=new MomokoSiteConfig();
  if (($num != E_USER_NOTICE && $num != E_NOTICE) || ($cfg->error_logging > 1))
  {
    if (file_exists($cfg->logdir.'/error.log'))
    {
      $log=fopen($cfg->logdir.'/error.log','a') or die("Error log could not be open for write!");
      fwrite($log,"[".date("Y-m-d H:i:s")."] PHP Error (".$num."; ".$str.") in ".$line." of ".$file."!\n");
    }
    else
    {
      die("Error log does not exist at configured location: ".$cfg->logdir."!");
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
  $cfg=new MomokoSiteConfig();
  if ($cfg->security_logging > 0)
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
  $cfg=new MomokoSiteConfig();
  if ($cfg->security_logging > 0)
  {
   if (!empty($message))
   {
    $message=": ".$message;
   }
   $log=new DataBaseTable('log');
   $change['time']=date("Y-m-d H:i:s");
   $change['type']="change";
   $change['action']=$action;
   $change['message']=$user->name."({$user->num}:{$_SERVER['REMOTE_ADDR']}) changed ".$target.$message;
   if($row=$log->putData($change))
   {
    return $row;
   }
   else
   {
    trigger_error("Could not write message ({$message}) to database!",E_USER_WARNING);
    return false;
   }
  }
}

#Misc functions
function paginate($total,MomokoSession $user,$offset=0,$perpage=null)
{
 if (empty($perpage))
 {
  $perpage=$user->rowspertable;
 }
 $total_pp=ceil($total/$perpage);
 for($c=1;$c<=$total_pp;$c++)
 {
  $offset_c=(($c-1)*$user->rowspertable);
  $pages[]=array('offset'=>$offset_c,'number'=>$c);
 }

 return $pages;
}

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
 $config=new MomokoSiteConfig();
 require_once $config->basedir."/mk-core/mk-xml.class.php";

 $xml=new MomokoXMLHandler();
 $xml->read($file);
 return $xml->getArray();
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

function locate_title($txt,$tag='h1') //Locates a title for a page without a head section, useful for pages from markdown sources
{
 $title="Untitled";
 if (preg_match("/<{$tag}>(?P<title>.*?)<\/{$tag}>/smU",$txt,$match) > 0) //Find page title in $data
 {
  if (@$match['title']) //accept titles other than Blank and [Blank]
  {
   $title=$match['title'];
  }
 }

 return $title;
}

function get_author($num)
{
 $auth_table=new DataBaseTable('users');
 $auth_query=$auth_table->getData("num:'= {$num}'",null,null,1);
 $author=$auth_query->fetch(PDO::FETCH_OBJ);
 
 return $author;
}

function list_content_by_tag()
{
  $tag=new DataBaseTable('tags');
  $content=new DataBaseTable('content');
  $assoc=new DataBaseTable('tcassoc');
  
  $list=func_get_args(); //in case of a list of tags
  if (is_array($list[0])) //in case of an array of tags
  {
    $list=$list[0];
  }
  foreach ($list as $name) //get all tag ids and put them into an array
  {
    $q=$tag->getData("name:`{$name}`",array('num'));
    $info=$q->fetch(PDO::FETCH_ASSOC);
    $tids[]=$info['num'];
  }
  
  foreach ($tids as $tnum) //find all content ids and put them into an array
  {
    $q=$assoc->getData("tag_num:`= {$tnum}`",array('con_num'));
    $ko=$q->fetch(PDO::FETCH_ASSOC);
    $cids[]=$ko['num'];
  }
  
  $where="WHERE ";
  foreach ($cids as $num)
  {
    if (!empty($num))
    {
     $where.="num={$num} OR ";
    }
  }
  if ($where != "WHERE ")
  {
   return $content->getByQuery($where);
  }
  else
  {
    return false;
  }
}

function ftp_get_contents($url,$user=null,$password=null)
{
 $location=parse_url($url);
 $conn=ftp_connect($location['host']);
 if (empty($user))
 {
  $user=$location['user'];
 }

 if (empty($password) && !empty($location['password']))
 {
  $password=$location['password'];
 }

 ftp_login($conn,$user,$password);
 ob_start();
 ftp_get($conn,"php://output",$location['path'],FTP_ASCII);
 $str=ob_get_contents();
 ob_end_clean();

 if (!empty($str))
 {
  return $str;
 }
 else
 {
  return false;
 }
}

function compile_head()
{
 $html=null;
 $table=new DataBaseTable('addins');
 $list=$table->getData("enabled:'y'",array('headtags'));
 while ($addin=$list->fetch(PDO::FETCH_OBJ))
 {
  if ($addin->headtags)
  {
   $html.=$addin->headtags."\n";
  }
 }
 
 return $html;
}

function fetch_files($dir,$limitto=null)
{
 $cfg=new MomokoSiteConfig();
 $set=$cfg->getSettings();
 $dir=str_replace(" ","+",$dir);
 $files=array();
 switch ($limitto)
 {
  case 'styles':
   $name="/*.css";
   break;
  default:
   $name="/*";
   break;
 }
 foreach (glob($set['basedir'].$set['filedir'].$dir.$name) as $file)
 {
  switch ($limitto)
  {
   case 'styles':
    if (preg_match("/((?:[a-z][a-z]+))(-)((?:[a-z][a-z\\.\\d_]+)\\.(?:[a-z\\d]{3}))(?![\\w\\.])/",$file,$matches) == 0)
    {
     $files[]=basename($file);
    }
    break;
   default:
    break;
  }
 }
 
 return $files;

}