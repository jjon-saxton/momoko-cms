<?php

class OCPView implements MomokoLITEObject
{
 private $tbl;
 private $data;
 private $info=array();

 public function __construct($num=null)
 {
  require_once $GLOBALS['CFG']->basedir.'/assets/php/dal/load.inc.php';
  $this->tbl=new DataBaseTable(DAL_TABLE_PRE."orders",DAL_DB_DEFAULT);
  $query=$this->tbl->getData(null,'num='.$num,null,1);
  $this->data=$query->first();
  $this->info=$this->setInfo();
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
  return null;
 }

 public function get()
 {
  return $this->setInfo();
 }

 public function setInfo()
 {
  if ($GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('support'))
  {
   $num=$this->data->num;
   $info['title']="View Order #".$num;
   $info['inner_body']=<<<HTML
<h2>View Order #{$num}</h2>
<form action="?action=edit&o={$num}" method=post>
<ul class="nobullet">
<li><strong>Name</strong>: {$this->data->name}</li>
<li><strong>E-mail</strong>: <a href="mailto:{$this->data->email}?subject=Your SextonSolutions Order">{$this->data->email}</a></li>
<li><strong>Product/Package</strong>: {$this->data->product}</li>
<li><strong>Start-up Fee</strong>: {$this->data->start_price}</li>
<li><strong>Service Fee</strong>: {$this->data->renewal_price}</li>
<li><strong>Serice Duration</strong>: {$this->data->renewal_duration}</li>
<li><strong>Status:</strong> {$this->showStatus()}</li>
</ul>
<input type=submit name="send" value="Update Status"> <a href="?action=edit&o={$num}">Unlock Editing</a>
</form>
HTML;
  }
  else
  {
   header("Location: ".CPROOT."?action=login");
   exit();
  }

  return $info;
 }

 private function showStatus()
 {
  $html="<select name=\"status\">\n";
  $statuses=array("canceled","unverified","contacted","verified","contracting","active","inactive");

  foreach ($statuses as $status)
  {
   if ($status == $this->data->status)
   {
    $attr=" selected=selected";
   }
   else
   {
    $attr=null;
   }

   $html.="<option value=\"".$status."\"".$attr.">".ucwords($status)."</option>\n";
  }

  $html.="</select>";
  return $html;
 }
}
