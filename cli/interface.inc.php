<?php
define("INCLI",TRUE);
define("UNAUTHORIZED",4);
define("BADREQUEST",3);

$cwd=dirname(__FILE__);
chdir('../');
require './assets/core/common.inc.php';
chdir($cwd);

if ($GLOBALS['SET']['error_logging'] > 0)
{
  set_error_handler("momoko_cli_errors");
}