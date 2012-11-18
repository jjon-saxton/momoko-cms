#! /usr/bin/php
<?php
require '../root.inc.php';

$data=array();
if ($argv[0] && pathinfo($argv[0],PATHINFO_EXTENSION) != 'php')
{
 $data['name']=$argv[0];
}
elseif (@$argv[1])
{
 $data['name']=$argv[1];
}
else
{
 fwrite (STDOUT,"New User Name: ");
 $data['name']=trim(fgets(STDIN),"\n\r");
}
fwrite(STDOUT,"New Password: ");
$data['password']=crypt(trim(fgets(STDIN),"\n\r"),$GLOBALS['CFG']->salt);
fwrite(STDOUT,"E-Mail: ");
$data['email']=trim(fgets(STDIN),"\n\r");
$data['groups']='users';
$usr=new MomokoUser($data['name']);
if($usr->put($data))
{
 exit(0);
}
else
{
 fwrite(STDOUT,"MySQL Error!\n");
 exit(2);
}

?>
