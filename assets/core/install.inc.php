<?php

function create_tables()
{
  $def['addins'][0]="`num` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['addins'][1]="`dir` VARCHAR(75) NOT NULL";
  $def['addins'][2]="`incp` CHAR(1) NOT NULL";
  $def['addins'][3]="`enabled` CHAR(1) NOT NULL";
  $def['addins'][4]="`shortname` VARCHAR(72) NOT NULL";
  $def['addins'][5]="`longname` VARCHAR(125) NOT NULL";
  $def['addins'][6]="`description` TEXT";
  
  $def['users'][0]="`num` INT(255) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['users'][1]="`name` VARCHAR(125) NOT NULL";
  $def['users'][2]="`password` TEXT";
  $def['users'][3]="`email` TEXT";
  $def['users'][4]="`groups` TEXT";
  $def['users'][5]="`shortdateformat` TEXT";
  $def['users'][6]="`longdateformat` TEXT";
  $def['users'][7]="`rowspertable` TEXT";
  
  $def['settings'][0]="`key` VARCHAR(30) NOT NULL PRIMARY KEY";
  $def['settings'][1]="`value` VARCHAR(255) NOT NULL";
  
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

function fill_tables(array $site, array $admin,array $defaults=null)
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
  
  $rows['addins'][]=array('dir'=>'finder','incp'=>'y','enabled'=>'y','shortname'=>'elFinder','longname'=>'elFinder','description'=>"A simple file management addin, which simply loads elFinder in a page");
  $rows['addins'][]=array('dir'=>'usermanager','incp'=>'y','enabled'=>'y','shortname'=>'User Manager','longname'=>"User Manager",'description'=>"Addin providing rudementary user management functions.");
  $rows['addins'][]=array('dir'=>'settings','incp'=>'n','enabled'=>'y','shortname'=>'User Settings','longname'=>'Manage Use Settings','description'=>'Addin providing users the ability to change there settings.');
  $rows['addins'][]=array('dir'=>'passreset','incp'=>'n','enabled'=>'y','shortname'=>'Password Resetter','longname'=>'Password Resetter','description'=>'Allows users to reset their own password.');
  
  $rows['users'][]=array('name'=>'root','password'=>'root','email'=>$admin['email'],'groups'=>"admin,cli",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=array('name'=>'guest','password'=>'guest','email'=>$admin['email'],'groups'=>"nobody",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=$admin;
  
  $rows['settings'][]=array('key'=>'version','value'=>'1.2');
  $rows['settings'][]=array('key'=>'name','value'=>$site['name']);
  $rows['settings'][]=array('key'=>'template','value'=>'quirk');
  $rows['settings'][]=array('key'=>'support_email','value'=>$admin['email']);
  $rows['settings'][]=array('key'=>'security_logging','value'=>1);
  $rows['settings'][]=array('key'=>'error_logging','value'=>1);
  $rows['settings'][]=array('key'=>'email_mta','value'=>'phpmail');
  $rows['settings'][]=array('key'=>'email_server','value'=>"host=localhost");
  $rows['settings'][]=array('key'=>'email_from',"name={$admin['name']}&address={$admin['email']}");
  
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

function db_upgrade($version,array $settings,$backup=null)
{
 $db=new DataBaseStructure(DAL_DB_DEFAULT);
 if ($backup == 'y')
 {
  $db->createBackup($GLOBALS['CFG']->datadir."/momoko-db-".time().".sql") or die(trigger_error("Could not create backup!", E_USER_WARNING));
 }
 $tables['addins']=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
 echo("Altering addin table columns...\n");
 $tables['addins']->putField("enabled","char",1,"NOT NULL") or die(trigger_error("could not add 'enabled' column, you may need to manually add this column, see our release notes for more details!",E_USER_WARNING));
 echo("Dropping old/unused tables...\n");
 $db->dropTable(DAL_TABLE_PRE.'merchants',DAL_DB_DEFAULT) or die(trigger_error("Unable to drop old table '".DAL_TABLE_PRE."merchants'!",E_USER_ERROR));
 echo("Adding the new settings table...\n");
 $settings_def[0]="`key` VARCHAR(30) NOT NULL PRIMARY KEY";
 $settings_def[1]="`value` VARCHAR(255) NOT NULL";
 $db->addTable(DAL_TABLE_PRE.'settings',DAL_DB_DEFAULT) or die(trigger_error("Unable to add new settings table please try again!",E_USER_ERROR));
 $table['settings']=new DataBaseTable(DAL_TABLE_PRE.'settings',DAL_DB_DEFAULT);
 $settings['email_mta']='phpmail';
 $settings['email_server']='host=localhost';
 $settings['email_from']='name='.$settings['from']['name']."&address=".$settings['from']['address'];
 unset($settings['from']);
 foreach ($settings as $key=>$value)
 {
  $newrow['key']=$key;
  $newrow['value']=$value;
  $row=$table['settings']->putData($newrow) or die(trigger_error("Could not add setting '{$key}'"));
 }
 return true;
}
