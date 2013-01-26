<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

class MomokoAddin implements MomokoLITEObject
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
