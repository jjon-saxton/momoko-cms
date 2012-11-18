<?php

class OCPFind implements MomokoLITEObject
{
 private $tbl;
 private $data;
 private $info=array();

 public function __construct($query=null)
 {
  require_once $GLOBALS['CFG']->basedir.'/assets/php/dal/load.inc.php';
  $this->tbl=new DataBaseTable(DAL_TABLE_PRE."orders",DAL_DB_DEFAULT);
  if (@$query)
	{
	 $this->data=$this->tbl->getDataFT($query);
	 $this->info=$this->showData();
	}
	else
	{
	 $this->info=$this->showBox();
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
  return null;
 }

 public function get()
 {
  return $this->setBox();
 }
 
 public function showData()
 {
	$info['title']="Order Search Results";
	$info['inner_body']=<<<HTML
<h2>Order Search Results</h2>
<table width=100% border=0 cellspacing=1 cellpadding=1>
<tr>
<th>#</th><th>Name</th><th>E-Mail</th><th>Product</th><th>Status</th>
</tr>
HTML;
  while ($row=$this->data->next())
	{
	 $info['inner_body'].="<tr id=".$row->num."><td><a href=\"".CPROOT."?action=view&o=".$row->num."\">".$row->num."</td><td>".$row->name."</td><td>".$row->email."</td><td>".$row->product."</td><td>".$row->status."</td>\n</tr>\n";
	}
  $info['inner_body'].="</table>";
  return $info;
 }
 
 public function showBox()
 {
	$info['title']="Search Orders by Keywords";
	$info['inner_body']=<<<HTML
<h2>Search Orders by Keywords</h2>
<form method=get>
<input type=hidden name="action" value="search">
<p>Enter search keywords: <input type=text name="q"> <input type=submit value="Search"></p>
</form>
HTML;
  return $info;
 }
}