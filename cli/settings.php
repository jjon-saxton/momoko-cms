#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';
require_once "./mk-core/common.inc.php";

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

if (!empty($args['of']))
{
 create_ini($args['of']);
}
elseif (!empty($args['if']))
{
 load_ini($args['if']);
}
else
{
 fwrite(STDOUT,"Expect either an if (input file), or of (output file) to either create an ini file based on current settings or upload new settings via an existing input file. None given!\n");
 exit();
}

function create_ini($to)
{
 $config=new MomokoSiteConfig();

 file_put_contents($to,$config->getSettings('ini'));
}

function load_ini($from)
{
 $config=new MomokoSiteConfig();
 $settings=parse_ini_file($from);

 foreach ($settings as $key=>$val)
 {
  $config->$key=$val; //changes settings in site's temp settings array
 }

 fwrite(STDOUT,"Saving settings from '{$from}' to Database...");
 if ($config->saveTemp())
 {
  fwrite(STDOUT," saved!\n");
 }
 else
 {
  fwrite(STDOUT," failed!\n");
 }

 fwrite(STDOUT,"Delete '{$from}'? ");
 $delete=strtolower(trim(fgets(STDIN),"\n\r"));

 if ($delete == 'y' || $delete == 'yes')
 {
  unlink($from);
 }
 else
 {
  fwrite(STDOUT,"Keeping '{$from}'\n");
 }

 return true;
}
