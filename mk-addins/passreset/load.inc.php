<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
 if ($node['@name'] == 'dbtableprefix')
 {
  define('ADDIN_TABLE_PRE',$node['@text']);
 }
 else
 {
  define('ADDIN_TABLE_PRE',DAL_TABLE_PRE);
 }
 if ($node['@name'] == 'dbtable')
 {
  $tables=explode(",",trim($node['@text']));
 }
}

foreach($tables as $tablename)
{
 $GLOBALS['ADDIN']['db-tables'][$tablename]=new DataBaseTable($tablename);
}
	 
define ('RESETURI',$GLOBALS['SET']['baseuri'].ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('RESETPATH',$GLOBALS['SET']['basedir'].$dirroot); //sets script base using the same info

require $GLOBALS['SET']['basedir']."/core/phpmailer/class.phpmailer.php";
require RESETPATH.'/main.inc.php';

$child=new ResetPage($_GET['q']);
$child->showPage(@$_GET['sid'],$_POST);

