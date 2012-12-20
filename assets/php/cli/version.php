#!/usr/bin/php
<?php
require dirname(__FILE__).'/../root.inc.php';

$version=file_get_contents($GLOBALS['CFG']->basedir.'/assets/etc/version.nfo.txt');
fwrite(STDOUT,$version);
fwrite(STDOUT,"Use update.php if you wish to check for updates.\n");
exit(0);

?>
