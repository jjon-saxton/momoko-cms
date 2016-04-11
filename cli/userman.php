#! /usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';

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
 fwrite (STDOUT,"Enter Search Query: ");
 $data['q']=trim(fgets(STDIN),"\n\r");
}

if (empty($data['q']) && !empty($data['name']))
{
  $data['q']="name:'".$data['name']."'";
}

get_user($data['q']);
exit();

function get_user($q)
{
 if (class_exists("DataBaseTable"))
 {
  $table=new DataBaseTable("users");
  $count=$table->getData($q,array('num'));
  $c=$count->fetchAll(PDO::FETCH_ASSOC);
  $rows=count($c);
  unset($c);
  $query=$table->getData($q,array('num','name','email','groups'));
  if ($rows > 1)
  {
    fwrite (STDOUT,"The following users match your query\n");
    while ($user=$query->fetch(PDO::FETCH_OBJ))
    {
      fwrite (STDOUT,"Num: ".$user->num.", Name: ".$user->name.", E-mail: ".$user->email."\n");
    }
    fwrite (STDOUT,"Enter a number for more information [1]: ");
    $num=trim(fgets(STDIN),"\n\r");
    if (empty($num))
    {
      $nq="num:'= 1'";
    }
    else
    {
      $nq="num:'= ".$num."'";
    }
    get_user($nq);
  }
  elseif ($rows == 1)
  {
    $user=$query->fetch(PDO::FETCH_OBJ);
    fwrite (STDOUT,"Num: ".$user->num."\nName: ".$user->name."\nE-Mail: ".$user->email."\nGroups: ".$user->groups."\n");
    fwrite (STDOUT,"What would you like to do next? [E]dit, or [D]elete the user? E[x]it? ");
    $next=strtolower(trim(fgets(STDIN),"\n\r"));
    switch ($next)
    {
      case 'e':
      case 'edit':
      fwrite(STDOUT,"Enter new Information, leave blank to keep current value\n");
      fwrite(STDOUT,"Name: ");
      $new['name']=trim(fgets(STDIN),"\n\r");
      fwrite(STDOUT,"Password: ");
      $new['password']=trim(fgets(STDIN),"\n\r");
      if (!empty($new['password']))
      {
	$new['password']=crypt($new['password'],$GLOBALS['CFG']->salt);
      }
      fwrite(STDOUT,"E-Mail: ");
      $new['email']=trim(fgets(STDIN),"\n\r");
      fwrite(STDOUT,"Groups (seperated by commas): ");
      $new['groups']=trim(fgets(STDIN),"\n\r");
      foreach ($new as $key=>$value)
      {
	if (!empty($value))
	{
	  $data[$key]=$value;
	}
      }
      unset($new);
      $data['num']=$user->num;
      if ($table->updateData($data))
      {
	fwrite(STDOUT,"User updated!\n");
      }
      else
      {
	fwrite(STDOUT,"User not updated! An error occuried!\n");
	exit(2);
      }
      break;
      case 'd':
      case 'delete':
      fwrite(STDOUT,"Are you sure? [Y]es, or [N]o ");
      $confirm=strtolower(trim(fgets(STDIN),"\n\r"));
      $data['num']=$user->num;
      if (($confirm == 'y' || $confirm == 'yes') && $table->removeData($data))
      {
	fwrite(STDOUT,"User removed!\n");
      }
      else
      {
	fwrite(STDOUT,"User not removed!\n");
      }
      break;
      case 'x':
      case 'exit':
      default:
      return true;
    }
  }
 }
 else
 {
  fwrite(STDOUT,"User manager cannot detect a database connection, please ensure that MomoKO is fully installed by running install.php");
 }
}
