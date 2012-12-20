<?php

function create_tables()
{
  $def['addins'][0]="`num` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['addins'][1]="`dir` VARCHAR(75) NOT NULL";
  $def['addins'][2]="`incp` CHAR(1) NOT NULL";
  $def['addins'][3]="`shortname` VARCHAR(72) NOT NULL";
  $def['addins'][4]="`longname` VARCHAR(125) NOT NULL";
  $def['addins'][5]="`description` TEXT";
  
  $def['merchants'][0]="`num` INT(255) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['merchants'][1]="`vpos` VARCHAR(15) NOT NULL";
  $def['merchants'][2]="`id` TEXT";
  $def['merchants'][3]="`key` TEXT";
  $def['merchants'][4]="`description` TEXT";
  
  $def['users'][0]="`num` INT(255) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['users'][1]="`name` VARCHAR(125) NOT NULL";
  $def['users'][2]="`password` TEXT";
  $def['users'][3]="`email` TEXT";
  $def['users'][4]="`groups` TEXT";
  $def['users'][5]="`shortdateformat` TEXT";
  $def['users'][6]="`longdateformat` TEXT";
  $def['users'][7]="`rowspertable` TEXT";
  
  $okay=0;
  $tottables=0;
  $db=new DataBaseStructure(DAL_DB_DEFAULT);
  foreach ($def as $table=>$cols)
  {
    if ($db->addTable(DAL_TABLE_PRE.$table,$cols))
    {
      $okay++;
    }
    $tottables++;
  }
  
  if ($okay == $tottables)
  {
    return true;
  }
  else
  {
    trigger_error("Only ".$okay." of ".$tottables." were created! Please empty the database and try again!",E_USER_WARNING);
    return false;
  }
}

function fill_tables(array $admin,array $defaults=null)
{
  if (empty($defaults['sdf']))
  {
    $defaults['sdf']="m/d/Y";
  }
  if (empty($defaults['ldf']))
  {
    $defaults['ldf']="I F j, Y";
  }
  if (empty($defaults['rpt']))
  {
    $defaults['rpt']=20;
  }
  
  $admin['password']=crypt($admin['password'],$GLOBALS['CFG']->salt);
  $admin['groups']="admin,users";
  $admin['shortdateformat']=$defaults['sdf'];
  $admin['longdateformat']=$defaults['ldf'];
  $admin['rowspertable']=$defaults['rpt'];
  
  $rows['addins'][]=array('dir'=>'finder','incp'=>'y','shortname'=>'elFinder','longname'=>'elFinder','description'=>"A simple file management addin, which simply loads elFinder in a page");
  $rows['addins'][]=array('dir'=>'usermanager','incp'=>'y','shortname'=>'User Manager','longname'=>"User Manager",'description'=>"Addin providing rudementary user management functions.");
  
  $rows['merchants'][]=array('vpos'=>'google','id'=>"148034693076512",'key'=>"PA99wKfO-z5RsClusjP6hA",'description'=>"Default Google Merchant Sandbox");
  
  $rows['users'][]=array('name'=>'root','password'=>'root','email'=>$admin['email'],'groups'=>"admin,cli",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=array('name'=>'guest','password'=>'guest','email'=>$admin['email'],'groups'=>"nobody",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=$admin;
  
  $okay=0;
  $tottbls=0;
  foreach ($rows as $table=>$dbrows)
  {
    $dbtbl=new DataBaseTable(DAL_TABLE_PRE.$table,DAL_DB_DEFAULT);
    $numrows=0;
    $addedrows=0;
    foreach ($dbrows as $data)
    {
      if ($dbtbl->putData($data))
      {
	$addedrows++;
      }
      $numrows++;
    }
    
    if ($addedrows == $numrows)
    {
      $okay++;
    }
    $tottbls++;
  }
  
  if ($okay == $tottbls)
  {
    return true;
  }
  else
  {
    trigger_error("Only ".$okay." of ".$tottbls." tables were populated! Please empty the tables and try again.",E_USER_WARNING);
    return false;
  }
}
