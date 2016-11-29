#!/usr/bin/php
<?php

require dirname(__FILE__).'/interface.inc.php';

if ($argv[0] && pathinfo($argv[0],PATHINFO_EXTENSION) != 'php')
{
 $argstr=$argv[0];
}
elseif (@$argv[1])
{
 $argstr=$argv[1];
}

if (!empty($argstr))
{
 $confirm=strtolower($argstr);
}
else
{
 fwrite(STDOUT,"You are about to drop all database tables from your MomoKO installation. This action CANNOT be undone. Do you wish to delete MomoKO?");
 $confirm=strtolower(trim(fgets(STDIN),"\n\r"));
}

if ($confirm == "y")
{
 $tables=array("addins","content","log","settings","users");
 foreach ($tables as $name)
 {
  $tbl=new DataBaseTable($name);
  fwrite(STDOUT,"Dropping table '{$name}'. . .");
  if ($tbl->drop())
  {
   fwrite(STDOUT,"dropped!\n");
  }
  else
  {
   fwrite(STDOUT,"failed!\n");
  }
 }

 $ini="./database.ini";
 if (file_exists($ini))
 {
  rename($ini,$ini.".old~");
 }
 exit();
}
else
{
 fwrite(STDOUT,"User aborted!\n");
 exit();
}
