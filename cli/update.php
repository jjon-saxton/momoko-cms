#!/usr/bin/php
<?php
define("INSTALLER",TRUE);

require dirname(__FILE__).'/interface.inc.php';
$dbs=parse_ini_file('./database.ini');

fwrite(STDOUT,"Checking database version...\n");
$db=new DataBaseSchema(); //Before 1.2 the 'settings' table did not exist by default
$tables=$db->showTables(); // This grabs a list of the tables in the current default database
foreach ($tables as $table) //This searches the list above for the 'settings' table
{
  if (array_search($dbs['tableprefix']."settings",$table))
  {
    require_once('./mk-core/common.inc.php');
    $settings=new MomokoSiteConfig();
  }
}
if (!empty($settings)) //Sanitation check! make sure we have a dbtable set for 'settings'
{
  if ($settings->version > 0)
  {
    fwrite(STDOUT,"Version ".$settings->version." Detected! Checking for required database updates...\n");
    if ($settings->value >= 2.1) //Database is at or greater than script version
    {
      fwrite(STDOUT,"Database update not required.\n");
      $update="O";
    }
    else
    {
      fwrite(STDOUT,"Your database must be updated! Do you wish to update now? [Y]es, or [N]o ");
      $update=trim(fgets(STDIN),"\n");
    }
    $version=$settings->value;
  }
  else
  {
    fwrite(STDOUT,"Your database is out of date. You cannot proceed beyond this point without an update! Apply update now? [Y]es or [N]o ");
    $update=trim(fgets(STDIN),"\n");
    $version=1.1;
  }
}
else
{
  $version=1.1;
  fwrite(STDOUT,"A pre 1.5 database has been detected. MomoKO 1.5 requires new database table and settings. These will have to be created now in order to ensure proper functionality. If you chose not to upgrade now your site WILL NOT work!\n");
  fwrite(STDOUT,"Apply update now? [Y]es or [N]o ");
  $update=trim(fgets(STDIN),"\n");
}

if (strtolower($update) == 'y')
{
  require './mk-core/install.inc.php'; //Update function resides in this file for global interface use
  if ($version == 1.1)
  {
   fwrite(STDOUT,"MomoKO 1.5 moved a few settings to the database and introduced other new setting values for better and easier customization. I will now begin asking you a series of questions so you may set these new values. Just like when you first installed MomoKO, if you see brackets [], it means I can fill in the default value presented within if you leave the answer blank. Otherwise all questions are required!\n");
   fwrite(STDOUT,"What was the name of your site as you configured it in main.conf.txt for MomoKO 1.1 or earlier? ");
   $newsettings['name']=trim(fgets(STDIN),"\n");
   fwrite(STDOUT,"What e-mail should we use for support inquiries? ");
   $newsettings['support_email']=trim(fgets(STDIN),"\n");
   fwrite(STDOUT,"What e-mail do you want to send automatic e-mail from? [{$newsettings['support_email']}] ");
   $newsettings['from']['address']=trim(fgets(STDIN),"\n");
   if (empty($newsettings['from']['address']))
   {
    $newsettings['from']['address']=$newsettings['support_email'];
   }
   fwrite(STDOUT,"What is the name you want associated with that address? ");
   $newsettings['from']['name']=trim(fgets(STDIN),"\n");
   fwrite(STDOUT,"Should we enable security logging? [Y]es or [N]o [Yes] ");
   $newsettings['security_logging']=trim(fgets(STDIN),"\n");
   fwrite(STDOUT,"Should we enable error logging? [Y]es or [N]o [Yes] ");
   $newsettings['error_logging']=trim(fgets(STDIN),"\n");
   fwrite(STDOUT,"Which template should we use? [quirk] ");
   $newsettings['template']=trim(fgets(STDIN),"\n");
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
      case 'error_logging':
      $newsettings[$key]=0;
      break;
      case 'template':
      $newsettings[$key]='fluidity';
      break;
     }
    }
   }
   $newsettings['version']='2.1';
   fwrite(STDIN,"Great! You can use the new 'change settings' link in your user control panel (if you are an administrator) to access these and other settings in the future. Now we have one final question before we begin upgrading your database.\n");
   fwrite(STDIN,"Would you like us to create a backup of the database? [Y]es [N]o [No] ");
   $backup=trim(fgets(STDIN),"\n");
   fwrite(STDIN,"Very well, we are upgrading your database now, please standby...\n");
  }
  if (db_upgrade($version,$newsettings,strtolower($backup)))
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
