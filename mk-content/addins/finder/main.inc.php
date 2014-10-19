<?php

class FinderPage implements MomokoObject
{
 public $connector;
 private $info=array();

 public function __construct($connector=null)
 {
  if (@$connector)
  {
   $this->connector=$connector;
  }
  else
  {
   $this->connector='/scripts/elfinder/php/connector.php';
  }
  $this->setInfo();
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
  return false;
 }

 public function get()
 {
  return file_get_contents(FINDERPATH.'/templates/window.tpl.htm');
 }

 public function setInfo()
 {
  if ($data=$this->get())
  {
   $info=parse_page($data);
   $varlist['finderroot']='//'.$GLOBALS['SET']['baseuri'].'/scripts/elfinder';
   $varlist['connectoruri']='//'.$GLOBALS['SET']['baseuri'].$this->connector;
   $ch=new MomokoVariableHandler($varlist);
   $info['inner_body']=$ch->replace($info['inner_body']);
  }
  else
  {
   trigger_error("Cannot open finder window! Check permissions on ".FINDERPATH."/templates/window.tpl.htm",E_USER_ERROR);
  }
  $this->info=$info;
 }
}
