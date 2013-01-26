<?php

class UMDialog implements MomokoObject
{
 public $path;
 private $info=array();

 public function __construct($file=null)
 {
  if (@$file)
  {
   $this->path=UMPATH."/templates/".$file.'.tpl.htm';
   $this->get();
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
  $data=file_get_contents($this->path);
  $this->info=parse_page($data);
  return true;
 }

 public function build($eid='dialog-form')
 {
  return <<<HTML
<div id="{$eid}">
{$this->inner_body}
</div>
HTML;
 }
}
