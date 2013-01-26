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
   $this->connector='/assets/scripts/elfinder/php/connector.php';
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
   $varlist['finderroot']='//'.$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.'/assets/scripts/elfinder';
   $varlist['connectoruri']='//'.$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.$this->connector;
   $ch=new MomokoCommentHandler($varlist);
   $info['inner_body']=$ch->replace($info['inner_body']);
  }
  else
  {
   $page=new MomokoLITEError('Server_Error');
   $info['full_html']=$page->full_html;
   $info['title']=$page->title;
   $info['inner_body']=$page->inner_body;
  }
  $this->info=$info;
 }
}
