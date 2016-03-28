#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';

$version=file_get_contents($GLOBALS['SET']['basedir']."/version.nfo.txt");
$settings=new DataBaseTable("settings");
$dbquery=$settings->getData("key: 'version'", array('value'), NULL, 1);
$dbrow=$dbquery->fetch(PDO::FETCH_OBJ);
$dbversion=$dbrow->key;
fwrite(STDOUT,"Momoko v".$version."\n"); //simply returns the current version number as stored in version.nfo.txt
fwrite(STDOUT,"Database v".$dbversion);
exit(0);

?>
