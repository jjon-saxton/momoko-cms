<?php

function create_tables($config)
{  
  $def['settings'][0]="`key` VARCHAR(30) NOT NULL PRIMARY KEY";
  $def['settings'][1]="`value` VARCHAR(255) NOT NULL";

  $def['addins'][0]="`num` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['addins'][1]="`dir` VARCHAR(75) NOT NULL";
  $def['addins'][2]="`type` VARCHAR(15) NOT NULL";
  $def['addins'][3]="`zone` INT(1)";
  $def['addins'][4]="`order` INT(11)";
  $def['addins'][5]="`enabled` CHAR(1) NOT NULL";
  $def['addins'][6]="`shortname` VARCHAR(72) NOT NULL";
  $def['addins'][7]="`longname` VARCHAR(125) NOT NULL";
  $def['addins'][8]="`settings` TEXT";
  $def['addins'][9]="`description` TEXT";
  $def['addins'][10]="`headtags` TEXT";
  
  $def['content'][0]="`num` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['content'][1]="`title` VARCHAR(100) NOT NULL";
  $def['content'][2]="`order` INT(11)";
  $def['content'][3]="`type` VARCHAR(15) NOT NULL";
  $def['content'][4]="`date_created` DATETIME NOT NULL";
  $def['content'][5]="`date_modified` DATETIME";
  $def['content'][6]="`status` VARCHAR(15) NOT NULL";
  $def['content'][7]="`author` INT(255)";
  $def['content'][8]="`has_access` VARCHAR(20)";
  $def['content'][9]="`mime_type` VARCHAR(20)";
  $def['content'][10]="`parent` TEXT";
  $def['content'][11]="`text` TEXT";
  $def['content'][12]="`link` TEXT";
  
  $def['log'][0]="`num` INT (11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
  $def['log'][1]="`time` DATETIME";
  $def['log'][2]="`type` VARCHAR(8) NOT NULL";
  $def['log'][3]="`action` VARCHAR(20) NOT NULL";
  $def['log'][4]="`message` TEXT";
  
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
  $db=new DataBaseSchema(null,$config);
  foreach ($def as $tablename=>$cols)
  {
    if ($table=$db->addTable($tablename,$cols))
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

  if (empty($site['session']))
  {
   $site['session']='MK';
  }

  if (empty($site['basedir']))
  {
   $site['basedir']=dirname(__FILE__);
   $site['basedir']=str_replace("core","",$site['basedir']);
   $site['basedir']=rtrim($site['basedir'],"/");
  }

  if (empty($site['filedir']))
  {
   $site['filedir']="/mk-content/";
  }

  if (empty($site['tempdir']))
  {
   $site['tempdir']=$site['filedir']."/temp/";
  }

  if (empty($site['use_ssl']))
  {
   $site['use_ssl']=FALSE;
  }
  
  $firstpage=<<<HTML
<html>
<body>
<h2>Hello World!</h2>
<p>Hello, I have just finished setting up my new site driven my MomoKO. In the coming days and weeks I will be adding content to this site. Please check back regularly for updates!</p>
</body>
</html>
HTML;
  $firstpost=<<<HTML
<html>
<body>
<p>As of today I have a new site running MomoKO!</p>
</body>
</html> 
HTML;
  
  $admin['password']=crypt($admin['password'],$site['session']);
  $admin['groups']="admin,users";
  $admin['shortdateformat']=$defaults['sdf'];
  $admin['longdateformat']=$defaults['ldf'];
  $admin['rowspertable']=$defaults['rpt'];
  
  $rows['content'][]=array('title'=>"Hello World!",'date_created'=>date("Y-m-d H:i:s"),'status'=>"public",'type'=>'page','order'=>1, 'parent'=>0,'author'=>1,'text'=>$firstpage,'mime_type'=>'text/html');
  $rows['content'][]=array('title'=>"Welcome!",'date_created'=>date("Y-m-d H:i:s"),'status'=>"public",'type'=>'post','parent'=>0,'author'=>1,'text'=>$firstpost,'mime_type'=>'text/html');
  foreach (glob($site['basedir']."/mk-content/error/*.htm") as $file)
  {
   $raw=file_get_contents($file);
   if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$raw,$match) > 0)
   {
    $page['title']=$match['title'];
   }
   else
   {
    $page['title']="Untitled Error";
   }
   unset($match);
   $page['date_created']=date("Y-m-d H:i:s");
   $page['status']="cloaked";
   $page['type']="error page";
   $page['parent']=0;
   $page['author']=1;
   if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$raw,$match) > 0)
   {
    $page['text']=$match['body'];
   }
   unset($match);
   $page['mime_type']="text/html";
   
   $rows['content'][]=$page;
  }
  foreach (glob($site['basedir']."/mk-content/forms/*.htm") as $file)
  {
   $raw=file_get_contents($file);
   if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$raw,$match) > 0)
   {
    $page['title']=$match['title'];
   }
   else
   {
    $page['title']="Untitled Form";
   }
   unset($match);
   $page['date_created']=date("Y-m-d H:i:s");
   $page['status']="cloaked";
   $page['type']="form";
   $page['parent']=0;
   $page['author']=1;
   if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$raw,$match) > 0)
   {
    $page['text']=$match['body'];
   }
   unset($match);
   $page['mime_type']="text/html";
   
   $rows['content'][]=$page;
  }
  
  $rows['log'][]=array('time'=>date("Y-m-d H:i:s"),'type'=>"notice",'action'=>'created','message'=>$site['name']." goes online!");
  
  $rows['users'][]=array('name'=>'root','password'=>'root','email'=>$admin['email'],'groups'=>"admin,cli",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=array('name'=>'guest','password'=>'guest','email'=>$admin['email'],'groups'=>"nobody",'shortdateformat'=>$defaults['sdf'],'longdateformat'=>$defaults['ldf'],'rowspertable'=>$defaults['rpt']);
  $rows['users'][]=$admin;
  
  $rows['settings'][]=array('key'=>'version','value'=>'2.1');
  $rows['settings'][]=array('key'=>'name','value'=>$site['name']);
  $rows['settings'][]=array('key'=>'template','value'=>'quirk');
  $rows['settings'][]=array('key'=>'support_email','value'=>$admin['email']);
  $rows['settings'][]=array('key'=>'security_logging','value'=>1);
  $rows['settings'][]=array('key'=>'error_logging','value'=>1);
  $rows['settings'][]=array('key'=>'salt','value'=>$site['session']);
  $rows['settings'][]=array('key'=>'sessionname','value'=>$site['session']);
  $rows['settings'][]=array('key'=>'baseuri','value'=>$site['baseuri']);
  $rows['settings'][]=array('key'=>'basedir','value'=>$site['basedir']);
  $rows['settings'][]=array('key'=>'filedir','value'=>$site['filedir']);
  $rows['settings'][]=array('key'=>'tempdir','value'=>$site['tempdir']);
  $rows['settings'][]=array('key'=>'use_ssl','value'=>$site['use_ssl']);
  $rows['settings'][]=array('key'=>'rewrite','value'=>0);
  
  $okay=0;
  $tottbls=0;
  foreach ($rows as $table=>$dbrows)
  {
    $dbtbl=new DataBaseTable($table,null,$site['basedir'].'/database.ini');
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

  $tottbls++;
  if (scan_addins($site))
  {
   $okay++;
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

function db_upgrade($level,$version,array $settings,$backup=null)
{
 if ($level == 'major') //add new tables for a major upgrade, in the future we may have to detect if a table exists first before adding or altering it
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
 }
 
 if ($version < 2.0) //settings to add for versions less than 2.0, in the future this section will have other lower version as well
 {
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
 }

 echo("Scanning for new core forms and error pages...\n"); //To be completed for all upgrades; update core forms and error pages in database!
 $new_content=scan_core_content($settings);
 if (is_array($new_content))
 {
    echo ("Upating content number(s)...");
    foreach ($new_content as $num)
    {
        echo ($num." ");
    }
    echo ("\n");
 }
 else
 {
    echo ("No content needs to be updated, or update failed! Hint; check error messages and logs\n");
 }
 
 echo("Scanning for new or updated addins...\n"); //This is to be completed for all upgrades in case we add new core addins
 $new_addins=scan_addins($settings);
 if (is_array($new_addins))
 {
  echo ("Updating or adding addin number(s)...");
  foreach ($new_addins as $num)
  {
   echo ($num." ");
  }
  echo ("\n");
 }
 else
 {
  echo ("No updates needed or updates failed!\n");
 }
 
 $update['key']='version';
 $update['value']=2.1;
 $table=new DataBaseTable('settings');
 $row=$table->updateData($update);
 
 return true;
}

function scan_addins($settings=null)
{
 if (empty($settings) && is_array($GLOBALS['SET']))
 {
  $settings=$GLOBALS['SET'];
 }
 $path=$settings['basedir'].$settings['filedir'].'addins';
 foreach (scandir($path) as $item)
 {
  if (!empty($item) && $item != '.' && $item != '..' && is_dir($path.'/'.$item))
  {
   $info=parse_ini_file($path.'/'.$item.'/MANIFEST');
   $addins=new DataBaseTable('addins');
   $query=$addins->getData("shortname:'{$info['shortname']}'");
   if ($query->rowCount() < 1)
   {
    $info['dir']=$item;
    $rows[]=$addins->putData($info);
   }
   else
   {
    $row=$query->fetch(PDO::FETCH_ASSOC);
    foreach ($row as $key=>$value)
    {
     if ($value != $info[$key])
     {
      $update[$key]=$value;
     }
    }
    if (!empty($update))
    {
     $update['num']=$row['num'];
     $rows[]=$addins->updateData($update);
    }
   }
  }
 }

 return $rows;
}

function scan_core_content($settings=null)
{
    $content=new DataBaseTable('content');
    if ($settings == NULL && is_array($GLOBALS['SET']))
    {
        $settings=$GLOBALS['SET'];
    }
    $types=array(array('folder'=>'forms','name'=>'form'),array('folder'=>'errors','name'=>'error page')); //saves code and allows future updates to add types and even store types elsewhere
    foreach ($types as $type)
    {
        $path=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir']."/".$type['folder'];
        foreach (scandir($path) as $item)
        {
            if (!is_dir($path."/".$item)) //folder should not carry sub-folders
            {
                //TODO determine mime type to ensure proper form/page
                $html=file_get_contents($path."/".$item);
                $page=parse_page($html);
                $page['type']=$type['name'];
                $page['text']=$page['inner_body']; unset($page['inner_body']);
                $query=$content->getData("title='{$page['title']}'");
                $old=$query->fetch(PDO::FETCH_ASSOC);
                if (!empty($page['num']))
                {
                    $page['num']=$old['num'];
                    $page['date_modified']=date("Y-m-d H:i:s");
                    $rows[]=$content->updateData($page);
                }
                else
                {
                    $page['date_created']=date("Y-m-d H:i:s");
                    $page['status']="cloaked";
                    $page['parent']=0;
                    $page['author']=1;
                    $page['mime_type']="text/html";
                    $rows[]=$content->putData($page);
                }
            }
        }
    }

    return $rows;
}
