<?php
define("INCLI",TRUE);
define("UNAUTHORIZED",4);
define("BADREQUEST",3);

$cwd=dirname(__FILE__);
chdir('../');
if (INSTALLER)
{
 require './core/database.inc.php';
 define("MOMOKOVERSION",trim(file_get_contents('./version.nfo.txt'),"\n"));
}
else
{
 require './core/common.inc.php';
 if ($GLOBALS['SET']['error_logging'] > 0)
 {
   set_error_handler("momoko_cli_errors");
 }
}
chdir($cwd);

