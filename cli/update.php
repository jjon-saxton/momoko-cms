#!/usr/bin/php
<?php

require dirname(__FILE__).'/interface.inc.php';

fwrite(STDOUT,"Checking database version...\n");
$db=new DataBaseStructure(DAL_DB_DEFAULT); //Before 1.2 the 'settings' table did not exist by default
$tables=$db->showTables(); // This grabs a list of the tables in the current default database
foreach ($tables as $table) //This searches the list above for the 'settings' table
{
  if (array_search(DAL_TABLE_PRE."settings",$table))
  {
    $settings=new DataBaseTable(DAL_TABLE_PRE.'settings',DAL_DB_DEFAULT); //if we find the table, we set settings to it
  }
}
if (!empty($settings)) //Sanitation check! make sure we have a dbtable set for 'settings'
{
  $query=$settings->getData("key:'version'",array('key','value'),null,1); //Get the version of the database.
  $settings=$query->first();
  if ($settings->value > 0)
  {
    fwrite(STDOUT,$settings->value." Detected! Checking for required database updates...\n");
    if ($settings->value >= 1.2) //No update is needed for 1.2 or greater yet
    {
      fwrite(STDOUT,"Database update not required.\n");
      $update="O";
    }
    else
    {
      fwrite(STDOUT,"Your database must be updated! Do you wish to update now? [Y]es, or [N]o ");
      $update=trim("\n",fputs(STDIN));
    }
    $version=$settings->value;
  }
  else
  {
    fwrite(STDOUT,"Your database is out of date. You cannot proceed beyond this point without an update! Apply update now? [Y]es or [N]o ");
    $update=trim("\n",fputs(STDIN));
    $version=1.1;
  }
}
else
{
  fwrite(STDOUT,"A pre 1.5 database has been detected. MomoKO 1.5 requires new database table and settings. These will have to be created now in order to ensure proper functionality. If you chose not to upgrade now your site WILL NOT work!\n");
  fwrite(STDOUT,"Apply update now? [Y]es or [N]o ");
  $update=trim("\n",fputs(STDIN));
}

if (strtolower($update) == 'y')
{
  require '../assets/core/install.inc.php'; //Update function resides in this file for global interface use
  if ($version == 1.1)
  {
   fwrite(STDOUT,"MomoKO 1.5 moved a few settings to the database and introduced other new setting values for better and easier customization. I will now begin asking you a series of questions so you may set these new values. Just like when you first installed MomoKO, if you see brackets [], it means I can fill in the default value presented within if you leave the answer blank. Otherwise all questions are required!\n");
   fwrite(STDOUT,"What was the name of your site as you configured it in main.conf.txt for MomoKO 1.1 or earlier?");
   $newsettings['name']=trim("\n",fputs(STDIN));
   fwrite(STDOUT,"What e-mail should we use for support inquiries?");
   $newsettings['support_email']=trim("\n",fputs(STDIN));
   fwrite(STDOUT,"Should we enable security logging? [Y]es or [N]o [Yes]");
   $newsettings['security_logging']=trim("\n",fputs(STDIN));
   fwrite(STDOUT,"Should we enable error loggin? [Y]es or [N]o [Yes]");
   $newsettings['error_logging']=trim("\n",fputs(STDIN));
   fwrite(STDOUT,"Which template should we use? [quirk]");
   $newsettings['template']=trim("\n",fputs(STDIN));
   foreach ($newsettings as $key=>$value)
   {
    if ($value == 'N' || $value == 'n')
    {
     $newsettings[$key]=0;
    }
    elseif (empty($value))
    {
     switch ($key)
     {
      case 'security_logging':
      case 'error_loggin':
      $newsettings[$key]=0;
      break;
      case 'template':
      $newsettings[$key]='quirk';
      break;
     }
    }
   }
   $newsettings['version']='1.5';
   fwrite(STDIN,"Great! You can use the new 'change settings' link in your user control panel (if you are an administrator) to access these and other settings in the future. Now we have one final question before we begin upgrading your database.");
   fwrite(STDIN,"Would you like us to create a backup of the database? [Y]es [N]o [No]");
   $backup=trim("\n",fputs(STDIN));
   $fwrite(STDIN,"Very well, we are upgrading your database now, please standby...\n");
  }
  if (db_upgrade($version,$newsettings,$backup))
  {
    fwrite(STDOUT,"Database update applied!\n");
  }
  else
  {
    fwrite(STDOUT,"Could not apply database changes! Please check your permission!\n");
  }
}
elseif(strtolower($update) == 'n')
{
  fwrite(STDOUT,"User aborted!\n");
}

exit(0);

?>
