#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';

$version=file_get_contents($GLOBALS['CFG']->basedir.'/assets/etc/version.nfo.txt');
fwrite(STDOUT,$version); //simply returns the current version number as stored in version.nfo.txt
exit(0);

?>
