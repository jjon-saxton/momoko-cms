#! /usr/bin/php
<?php
require dirname(__FILE__).'/root.inc.php';

$data=array();
fwrite (STDOUT,"User you wish to change: ");
$data['name']=trim(fgets(STDIN),"\n\r");
if ($data['name'] != 'root' && $data['name'] != 'guest')
{
 fwrite(STDOUT,"New Password: ");
 $data['password']=crypt(trim(fgets(STDIN),"\n\r"),$GLOBALS['CFG']->salt);
 $usr=new MomokoUser($data['name']);
 $usr->put($data);
 exit(0);
}
else
{
 fwrite(STDOUT,"You cannot change the password for system user '{$data['name']}'!\n");
 exit(BADREQUEST);
}

?>

