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

if (array_key_exists('upload',$args))
{
 if (pathinfo($args['upload'],PATHINFO_EXTENSION) == "md")
 {
  make_page($tbl,$args['upload'],'markdown');
 }
 elseif (pathinfo($args['upload'],PATHINFO_EXTENSION) == "html" || pathinfo($args['upload'],PATHINFO_EXTENSION) == "htm")
 {
  make_page($tbl,$args['upload'],'html');
 }
 else
 {
  make_attachment($tbl,$args['upload']);
 }
}
elseif (array_key_exists('action',$args))
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
  fwrite(STDOUT,"Please provide the absolute path to the file you wish to add: ");
  $upload=trim(fgets(STDIN),"\n\r");
  var_dump($upload); //TODO as we did above, choose wish make function to use and how to use it.
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
   fwrite(STDOUT,$val);
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
function make_page($content,$file=null,$type='html')
{
 //TODO process html and/or markdown page and add it to database
}
function make_attachment($content,$file=null)
{
 //TODO add attachment to database with default data
}
