<?php
define ('REPPROPATH',REPCENTERPATH.'/profiles/');

class RepProfilePage implements RepPageObject
{
 public $rep;

 public function __construct($rep=null)
 {
  $this->rep=pathinfo($rep,PATHINFO_FILENAME);
 }

 public function fetchPage($action,$data=null)
 {
  $info=array();

  switch ($action)
  {
   case 'show':
   default:
   $info['title']="Rep Information: ".$this->rep;
   $info['inner_body']=file_get_contents(REPPROPATH.$this->rep.".htm");
  }

  return $info;
 }
}
