<?php

switch (@$_GET['action'])
{
 case 'login':
 if (@!empty($_POST['password']))
 {
  if ($GLOBALS['USR']->login($_POST['name'],$_POST['password']))
  {
   $_SESSION['data']=serialize($GLOBALS['USR']);
   if (@!empty($_GET['re']))
   {
    header("Location: ?action=".$_GET['re']);
   }
   else
   {
    header("Location: ?loggedin=1");
   }
   exit();
  }
  else
  {
   $child=new MomokoError('Unauthorized');
  }
 }
 else
 {
  $child=new MomokoForm('login');
 }
 break;
 case 'register':
 if (@$_POST['first'])
 {
  $usr=new MomokoUser($_POST['name']);
  if ($usr->put($_POST))
  {
   header("Location:/?action=login");
   exit();
  }
 }
 else
 {
  $child=new MomokoForm('register');
 }
 break;
 case 'logout':
 if ($GLOBALS['USR']->logout())
 {
  $_SESSION['data']=serialize($GLOBALS['USR']);
  header("Location: ?loggedin=0");
 }
 break;
 case 'new':
 case 'edit':
 case 'delete':
 $child=new MomokoNewsManager($path);
 $child->getPage($_GET['action']);
 break;
 default:
 $child=new MomokoNewsPage($path);
}

if ($child->type == 'html')
{
 $tpl=new MomokoTemplate(pathinfo($path,PATHINFO_DIRNAME));
 print $tpl->toHTML($child);
}
else
{
 header("Content-type: text/xml");
 print $child->inner_body;
}

