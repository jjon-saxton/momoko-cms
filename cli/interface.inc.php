<?php
define("INCLI",TRUE);
define("UNAUTHORIZED",4);
define("BADREQUEST",3);

chdir('../');
if (@INSTALLER)
{
 require './mk-core/database.inc.php';
 define("MOMOKOVERSION",trim(file_get_contents('./version.nfo.txt'),"\n"));
}
else
{
 require './mk-core/common.inc.php';
 include './mk-core/users.inc.php';
 if ($GLOBALS['SET']['error_logging'] > 0)
 {
   set_error_handler("momoko_cli_errors");
 }
}

