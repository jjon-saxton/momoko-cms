#! /usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';
$usr=new MomokoUser('guest');
$guest=$usr->get();
unset($usr);

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
$data['shortdateformat']=$guest->shortdateformat;
$data['longdateformat']=$guest->longdateformat;
$data['rowspertable']=$guest->rowspertable;
$usr=new MomokoUser($data['name']);
if($usr->put($data))
{
 exit(0);
}
else
{
 fwrite(STDOUT,"User not added! An error occured!\n");
 exit(2);
}

?>
