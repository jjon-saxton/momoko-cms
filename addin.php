<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

class MomokoAddin implements MomokoObject
{
 public $path;
 private $info=array();

 public function __construct($path=null)
 {
  if ($path)
  {
   $manifest=xmltoarray($path.'/manifest.xml'); //Load manifest
   $this->info=$this->parseManifest($manifest);
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
 
 public function put($archive=null)
 {
  if (empty($archive))
  {
    return new MomokoAddinForm('add');
  }
  else
  {
    return true;
  }
 }
 
 public function update($archive=null)
 {
  if (empty($archive))
  {
    return new MomokoAddinForm('update');
  }
  else
  {
    //TODO open archive, update files in addindir, use manifest to update database
    return true;
  }
 }
 
 public function drop()
 {
  $table=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
  $query=$table->getData("dir: '".basename($this->info['dirroot'])."'",array('num','dir'),null,1);
  $data=$query->first();
  
  $ddata['num']=$data->num;
  if ($table->removeData($ddata) && rmdirr($this->info['dirroot']))
  {
    return true;
  }
  else
  {
    trigger_error("Unable to remove addin!",E_USER_ERROR);
  }
 }
 
 public function toggleEnabled()
 {
  $table=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
  $query=$table->getData("dir: '".basename($this->info['dirroot'])."'",array('num','enabled'),null,1);
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
  
  if ($update=$table->updateData($ndata))
  {
    return true;
  }
  else
  {
    trigger_error("Unable to toggle enabled/disabled state of addin ".basename($this->info['dirroot'])."!",E_USER_WARNING);
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

if (@$_GET['action'] == 'login' || @$_GET['action'] == 'logout' || @$_GET['action'] == 'register')
{
 header("Location: //".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."?action=".$_GET['action']);
}
elseif (@$_SERVER['PATH_INFO'])
{
 list(,$addindir,)=explode("/",$_SERVER['PATH_INFO']);
 $GLOBALS['LOADED_ADDIN']=new MomokoAddin($GLOBALS['CFG']->basedir."/assets/addins/".$addindir."/");

 switch (@$_GET['action'])
 {
  case 'add':
  case 'update':
  case 'enable':
  case 'disable':
  case 'view':
  case 'list':
  default:
  if ($GLOBALS['LOADED_ADDIN']->hasAuthority()) //User must have authority!
  {
   include $GLOBALS['CFG']->basedir."/assets/addins/".$addindir."/load.inc.php"; //hand control over to the addin
  }
  else
  {
   $child=new MomokoLITEError('Forbidden');

   $tpl=new MomokoLITETemplate('/'); //forces load of default template to show error message
   echo $tpl->toHTML($child);
  }
 }
}
