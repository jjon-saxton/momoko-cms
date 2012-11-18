<?php
define("INCLI",TRUE);
define("UNAUTHORIZED",4);
define("BADREQUEST",3);
error_reporting(E_ERROR);

$cwd=getcwd();
chdir('../../../');
require './assets/php/common.inc.php';
require './assets/php/user.inc.php';
chdir($cwd);

$GLOBALS['USR']=new MomokoSession();
$GLOBALS['USR']->loginAs('root');

