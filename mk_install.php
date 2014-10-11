<?php
if (!file_exists(dirname(__FILE__)."/database.ini"))
{
 header("Location: //".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
 exit();
}
