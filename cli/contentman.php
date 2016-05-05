#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';
require_once "./mk-core/common.inc.php";
$tbl=new DataBaseTable('content');

$args=array();
foreach ($argv as $key=>$value) //create basic arg pairs
{
 list($ak,$av)=explode("=",$value);
 if ($key == 0)
 {
  if (pathinfo($argv[0],PATHINFO_EXTENSION) != 'php') //remove the file name from the args
  {
   $args[$ak]=$av; //reformat $value for $args array
  }
 }
 else
 {
  $args[$ak]=$av;
 }
}

if (array_key_exists('action',$args))
{
 if (array_key_exists('id',$args))
 {
  $item=$args['id'];
 }
 else
 {
  $item=null;
 }

 switch ($args['action'])
 {
  case 'add':
  case 'new':
  add_content($tbl,$args['file']);
  break;
  case 'view':
  show_content($tbl,$item);
  break;
  case 'edit':
  edit_content($tbl,$item);
  break;
  case 'delete':
  case 'del':
  del_content($tbl,$item);
  break;
  case 'list':
  default:
  list_content($tbl);
 }
}
else
{
 fwrite(STDOUT,"Welcome to MomoKO's Content Manager! Please choose and action from the list below. You can skip this step next time by specifying action next to the script name. i.e. ./contentman.php action=list.\n");
 $actions=<<<TXT
1. view - Views an item.
2. add/upload - Adds an item to MomoKO.
3. edit - Edits an item.
4. remove - Removes an item.
5. list - Lists items by category.
TXT;
 fwrite(STDOUT,$actions);
 fwrite(STDOUT,"Choose the number of the action you wish to perform from the list above [5]: ");
 $action=trim(fgets(STDIN),"\n\r");
 switch($action)
 {
  case 1:
  show_content($tbl);
  break;
  case 2:
  add_content($tbl);
  break;
  case 3:
  edit_content($tbl);
  break;
  case 4:
  del_content($tbl);
  break;
  case 5:
  default:
  list_content($tbl);
 }
}

function list_content($content)
{
 $cols=array('num','title','type','status','mime_type');
 $q=$content->getData(null,$cols);
 $head=null;
 foreach ($cols as $header)
 {
  $head.=$header."|";
 }
 fwrite(STDOUT,rtrim($head,"|"));
 while($row=$q->fetch(PDO::FETCH_ASSOC))
 {
  $txt=null;
  foreach ($row as $item)
  {
   $txt.=$item."|";
  }
  fwrite(STDOUT,rtrim($txt,"|")."\n");
 }
 fwrite(STDOUT,"List complete! You may run this script again to choose an action and an item number to manage a specific item.\n");
 exit();
}
function show_content($content,$id=null)
{
 if (empty($id))
 {
  fwrite(STDOUT,"Choose and item number (hint: select 'list' as your action to see all items and their numbers): ");
  $id=rtrim(fgets(STDIN),"\n\r");
 }

 $q=$content->getData("num:`= {$id}`");
 $details=$q->fetch(PDO::FETCH_ASSOC);
 foreach ($details as $key=>$val)
 {
  if ($key == 'text')
  {
   fwrite(STDOUT,$val."\n");
  }
  else
  {
   fwrite(STDOUT,$key."=".$val."\n");
  }
 }
 exit();
}
function edit_content($content,$id=null)
{
 fwrite(STDOUT,"Editing is not currently available in this alpha release!\n");
}
function del_content($content,$id=null)
{
 if (empty($id))
 {
  fwrite(STDOUT,"Choose and item number (hint: select 'list' as your action to see all items and their numbers): ");
  $id=rtrim(fgets(STDIN),"\n\r");
 }

 $data['num']=$id;
 fwrite(STDOUT,"Are you sure you want to delete item number {$id}? ");
 $confirm=strtolower(rtrim(fgets(STDIN),"\n\r"));

 if ($confirm == 'y' || $confirm == "yes")
 {
  fwrite(STDOUT,"Deleting... ");
  if ($content->deleteData($data))
  {
   fwrite(STDOUT,"succeeded!\n");
  }
  else
  {
   fwrite(STDOUT,"failed!\n");
  }
 }
 else
 {
  fwrite(STDOUT,"User aborted!\n");
 }
 exit();
}
function add_content($content,$file=null)
{
 if (empty($file))
 {
  fwrite(STDOUT,"Absolute path to file you wish to add: ");
  $file=trim(fgets(STDIN),"\n\r");
 }
 $ext=pathinfo($file,PATHINFO_EXTENSION);

 fwrite(STDOUT,"Gathering information from '{$file}'...\n");
 switch ($ext)
 {
  case "txt":
  $ko['mime_type']="text/plain";
  fwrite(STDOUT,"Is this plain text file markdown formatted? ");
  $markdown=strtolower(trim(fgets(STDIN),"\n\r"));
  if ($markdown == "yes" || $markdown == "y")
  {
   fwrite(STDOUT,"Do wish to add this file as a page instead of an attachment? ");
   $page=strtolower(trim(fgets(STDIN),"\n\r"));
   if ($page == "yes" || $page == "y")
   {
    include ("./mk-core/markdown.inc.php");
    $ko['mime_type']="text/html";
    $ko['type']="page";
    $ko['link']=null;
    $ko['text']=Markdown(file_get_contents($file));
    $ko['title']=locate_title($ko['text']);
   }
   else
   {
    $ko['title']=basename($file);
    $ko['type']="attachment";
    $ko['link']=""; //TODO set link to the new location of the file.
   }
  }
  break;
  case "md":
  $ko['mime_type']="text/markdown";
  fwrite(STDOUT,"Do you wish to add this markdown file as a page instead of an attachment? ");
  $page=strtolower(trim(fgets(STDIN),"\n\r"));
  if ($page == "yes" || $page == 'y')
  {
   include ("./mk-core/markdown.inc.php");
   $ko['mime_type']="text/html";
   $ko['type']="page";
   $ko['link']=null;
   $ko['text']=Markdown(file_get_contents($file));
   $ko['title']=locate_title($ko['text']);
  }
  else
  {
   $ko['title']=basename($file);
   $ko['type']="attachment";
   $ko['link']=""; //TODO set link like above
  }
  break;
  case "htm":
  case "html":
  $ko['mime_type']="text/html";
  $ko['type']="page";
  $ko['link']=null;
  $info=parse_page(file_get_contents($file));
  $ko['title']=$info['title'];
  $ko['text']=$info['inner_body'];
  break;
  default:
  $ko['title']=basename($file);
  $ko['mime_type']=""; //TODO grab mime type from file
  $ko['type']="attachment";
  $ko['link']=""; //TODO grab link
 }

 fwrite(STDOUT,"Do you wish to keep this file? ");
 $keepsrc=strtolower(trim(fgets(STDIN),"\n\r"));
 if ($keepsrc == "yes" || $keepsrc == "y")
 {
  if ($ko['type'] == "attachment")
  {
   copy($file,$ko['link']);
  }
 }
 else
 {
  if ($ko['type'] == "attachment")
  {
   rename($file,$ko['link']);
  }
  else
  {
   unlink($file);
  }
 }

 fwrite(STDOUT,"Now adding '{$ko['title']}' to database as a(n) {$ko['type']}...");
 $ko['author']=1;
 $ko['date_created']=date("Y-m-d H:i");
 $ko['status']="cloaked";
 $ko['parent']=0;
 if ($nid=$content->putData($ko))
 {
  fwrite(STDOUT," succeeded! item #{$nid} added to database.\n");
 }
 else
 {
  fwrite(STDOUT," failed!\n");
 }
 exit();
}
