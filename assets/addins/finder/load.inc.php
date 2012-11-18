<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
}
	 
define ('FINDERURI',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('FINDERPATH',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info

require FINDERPATH.'/main.inc.php';

$child=new FinderPage();

$tpl=new MomokoLITETemplate($dirroot.'/templates/main.tpl.htm');
echo ($tpl->toHTML($child));
