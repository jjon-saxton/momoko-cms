#!/usr/bin/php
<?php
include "../core/database.inc.php";

fwrite(STDOUT,"Running Tests.\n");
$tbl=new DataBaseTable("settings",null,"../database.ini");
$data['key']="test";
$data['value']="true";
$id=$tbl->putData($data);
fwrite(STDOUT,"Row '{$id}' inserted!\n");
