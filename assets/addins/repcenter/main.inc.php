<?php

interface RepPageObject
{
 public function __construct($data=null);
 public function fetchPage($action,$data=null);
}

class RepCenterPage implements MomokoLITEObject
{
 public $path;
 protected $query;
 protected $data;
 private $info=array();

 public function __construct($path=null)
 {
  $this->path=$path;
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
  if (pathinfo($this->path,PATHINFO_EXTENSION) == 'htm' || pathinfo($this->path,PATHINFO_EXTENSION) == 'mpr')
  {
   include REPCENTERPATH.'/profile.inc.php';
   $profile=new RepProfilePage($this->path,PATHINFO_FILENAME);
   $this->info=$profile->fetchPage($this->query['action'],$this->data);
  }
  elseif (pathinfo($this->path,PATHINFO_EXTENSION) == '' && pathinfo($this->path,PATHINFO_BASENAME) != 'repcenter')
  {
   include REPCENTERPATH.'/'.pathinfo($this->path,PATHINFO_BASENAME).'.inc.php';
   $page=new RepControlPage();
   $this->info=$profile->fetchPage($this->query['action'],$this->data);
  }
  elseif ($GLOBALS['USR']->inGroup('rep') || $GLOBALS['USR']->inGroup('admin') || $GLOBALS['USR']->inGroup('editor'))
  {
   $rcuri=REPCENTERURI;
   $info['title']="Welcome to the SaxtonSolutions Rep Center!";
   $info['inner_body']=<<<HTML
<h2>{$info['title']}</h2>
<p>The SaxtonSolutions LLC representive center is your very own portal on SaxtonSolutions. Use it to tell your customers about you, to advertise your services, and to direct customers to your own site. Below we have a few actions you can perform:</p>
<table width=100% cellspacing=0 cellpadding=2>
<tr>
<th>Personal Actions</th><th>Professional Actions</th>
</tr>
<tr>
<td><a href="{$rcuri}/{$GLOBALS['USR']->name}.mpr?action=show">Show Profile</a></td><td><a href="{$rcuri}/orders?action=show">Show/Modify Processed Orders</a></td>
</tr>
<tr>
<td><a href="{$rcuri}/{$GLOBALS['USR']->name}.mpr?action=edit">Edit Profile</a></td><td><a href="{$rcuri}/orders?action=new">Place a new Order</a></td>
</tr>
<tr>
<td><a href="{$rcuri}/repinfo?action=edit">Edit Personal/Payment Information</a></td><td><a href="{$rcuri}/settings?action=edit">Change Password and Site Settings</a></td>
</tr>
</table>
HTML;
   $this->info=$info;
  }
  return;
 }

 public function showPage($query,$data=null)
 {
  $this->query=$query;
  $this->data=$data;

  $this->get();
 }
}
