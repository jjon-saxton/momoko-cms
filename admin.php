<?php
require "./assets/php/common.inc.php";
require "./assets/php/content.inc.php";

class MomokoACP implements MomokoLITEObject
{
 public $path;
 private $info=array();

 public function __construct($path=null)
 {
  $this->info=$this->get();
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
  $info['title']="Administrator Control Panel";
  $mcp=new MomokoMiniCP($GLOBALS['USR'],'display=plugs');
  $list=$mcp->getModule('html');
  $info['inner_body']=<<<HTML
<h2>Administrator Control Panel</h2>
{$list}
HTML;
  $info['full_html']=<<<HTML
<html>
<head>
<title>{$info['title']}</title>
</head>
<body>
{$info['inner_body']}
</body>
</html>
HTML;
  return $info;
 }
}

if (@$_GET['action'] == 'login' || @$_GET['action'] == 'logout' || $_GET['action'] == 'register')
{
 header("Location: ".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."?action=".$_GET['action']);
}
else
{
 if ($GLOBALS['USR']->inGroup('admin')) //User must have authority!
 {
  $child=new MomokoACP(@$_SERVER['PATH_INFO']);
 }
 else
 {
  $child=new MomokoLITEError('Forbidden');
 }
 $tpl=new MomokoLITETemplate('/');
 echo $tpl->toHTML($child);
}
