#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';

$version=file_get_contents("version.nfo.txt");
$settings=new DataBaseTable("settings");
$dbquery=$settings->getData("key: 'version'", array('value'), NULL, 1);
$dbrow=$dbquery->fetch(PDO::FETCH_OBJ);
$dbversion=$dbrow->value;
fwrite(STDOUT,"Momoko v".$version); //simply returns the current version number as stored in version.nfo.txt
fwrite(STDOUT,"Running with database v".$dbversion."\n"); //shows the current version of the database.
exit(0);

?>
