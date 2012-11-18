<?php

class OCPBasicForm implements MomokoLITEObject
{
 private $info=array();

 public function __construct($action=null)
 {
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
   $info['title']="Find Order by Number";
   $info['inner_body']=<<<HTML
<h2>Find Order by Number</h2>
<form method=get>
<input type=hidden name="action" value="view">
<p>Please enter a customer number below to find a single record. You will be able to make changes if a record is found.</p>
<div><label for="order">Customer Number:</label> <input type=number id="order" name="o"></div>
<div><input type=submit value="Find"></div>
</form>
<p><a href="?action=search">Search by Keywords</a></p>
HTML;
  }
  else
  {
   header("Location: ".CPROOT."?action=login");
   exit();
  }

  return $info;
 }
}

class OCPUser implements MomokoLITEObject
{
 private $info=array();

 public function __construct($action=null)
 {
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
   header("Location: ".CPROOT);
   exit();
  }
  else
  {
   $info['title']="Login";
   $info['inner_body']=<<<HTML
<h2>Login</h2>
<form method=post>
<p>You must login to edit or view orders!</p>
<div><label for="name">Name:</label> <input type=text id="name" name="name"></div>
<div><label for="password">Passowrd:</label> <input type=password name="password" id="password"></div>
<div><input type=submit value="Login"></div>
</form>
HTML;
  }

  return $info;
 }
}
