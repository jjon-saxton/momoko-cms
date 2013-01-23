<?php
define("INCLI",TRUE);
define("UNAUTHORIZED",4);
define("BADREQUEST",3);

$cwd=dirname(__FILE__);
chdir('../');
require './assets/core/common.inc.php';
require './assets/core/user.inc.php';
chdir($cwd);
