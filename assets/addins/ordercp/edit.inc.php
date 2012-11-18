<?php

class OCPEdit implements MomokoLITEObject
{
 private $tbl;
 private $data;
 private $info=array();

 public function __construct($num=null)
 {
  require_once $GLOBALS['CFG']->basedir.'/assets/php/dal/load.inc.php';
  $this->tbl=new DataBaseTable(DAL_TABLE_PRE."orders",DAL_DB_DEFAULT);
	if (@$_POST['send'])
	{
	 $data=$_POST;
	 $data['num']=$num;
	 $this->put($data);
	}
	else
	{
   $query=$this->tbl->getData(null,'num='.$num,null,1);
   $this->data=$query->first();
   $this->info=$this->setInfo();
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
	return $this->setInfo();
 }
 
 public function put($data)
 {
	if ($this->tbl->updateData($data))
	{
	 header("Location: //".CPROOT);
	}
	else
	{
	 return false;
	}
 }
 
 public function setInfo()
 {
	$info['title']="Edit Order #".$this->data->num;
	$info['inner_body']=<<<HTML
<h2>Edit Order #{$this->data->num}</h2>
<form method=post>
<ul class="nobullet">
<li><label for="n">Name:</label> <input type=text name="name" id="n" value="{$this->data->name}"></li>
<li><label for="e">E-Mail:</label> <input type=email name="email" id="e" value="{$this->data->email}"></li>
<li><label for="p">Product/Package:</label> <input type=text name="product" id="p" value="{$this->data->product}"></li>
<li><label for="sf">Start-up Fee:</label> <input type=number name="start_price" id="sf" value="{$this->data->start_price}"></li>
<li><label for="rf">Service Fee:</label> <input type=number name="renewal_price" id="rf" value="{$this->data->renewal_price}"></li>
<li><label for="sd">Service Duration:</label> <input type=text name="renewal_duration" id="sd" value="{$this->data->renewal_duration}"></li>
</ul>
<input type=submit name="send" value="Edit Order">
</form>
HTML;

  return $info;
 }
}