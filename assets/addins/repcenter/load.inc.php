<?php
$manifest=xmltoarray(dirname(__FILE__).'/manifest.xml'); //Load manifest
foreach ($manifest as $node) //find this addins folder
{
 if ($node['@name'] == 'dirroot')
 {
  $dirroot=rtrim($node['@text'],"/");
 }
}
	 
define ('REPCENTERURI',$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADDINROOT.pathinfo($dirroot,PATHINFO_BASENAME)); //set roots based off of addins folder found from manifest
define ('REPCENTERPATH',$GLOBALS['CFG']->basedir.$dirroot); //sets script base using the same info

require REPCENTERPATH.'/main.inc.php';

$child=new RepCenterPage($_SERVER['PATH_INFO']);
$child->showPage($_GET,$_POST);

$tpl=new MomokoLITETemplate($dirroot.'/templates/main.tpl.htm');
echo ($tpl->toHTML($child));
