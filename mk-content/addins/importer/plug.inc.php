<?php
class MomokoSwitchboard implements MomokoObject
{
 private $info=array();

 public function __construct()
 {

 }

 public function __get($key)
 {

 }

 public function __set($key,$value)
 {
 
 }

 public function get($action)
 {
  switch ($action)
  {
   case 'form'
   default:
   $this->info['inner_body']="<h2>Test</h2>"
  }
 }
}
?>
