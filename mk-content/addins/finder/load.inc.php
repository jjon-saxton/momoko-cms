<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
}
	 
define ('FINDERURI',$GLOBALS['SET']['baseuri'].ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('FINDERPATH',$GLOBALS['SET']['basedir'].$dirroot); //sets script base using the same info

require FINDERPATH.'/main.inc.php';

$child=new FinderPage();
