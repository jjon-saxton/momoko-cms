#!/usr/bin/php
<?php
require '../root.inc.php';

$version=file_get_contents(BASEDIR.'/assets/docs/version.txt');
fwrite(STDOUT,$version);
fwrite(STDOUT,"Use update.php if you wish to check for updates.\n");
exit(0);

?>
