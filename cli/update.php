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
    if ($settings->version >= 2.1) //Database is at or greater than script version
    {
      fwrite(STDOUT,"Database update not required.\n");
      $update="O";
    }
    else
    {
      fwrite(STDOUT,"Your database must be updated! Do you wish to update now? [Y]es, or [N]o ");
      $update=trim(fgets(STDIN),"\n");
    }
    $version=$settings->version;
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
   $level='major';
   fwrite(STDOUT,"MomoKO 1.5 moved a few settings to the database and introduced other new setting values for better and easier customization. These settings will be added with default values. You can change these values later by access the dashboard.\n");
   fwrite(STDIN,"Would you like us to create a backup of the database? [Y]es [N]o [N] ");
   $backup=trim(fgets(STDIN),"\n");
   fwrite(STDIN,"Very well, we are upgrading your database now, please standby...\n");
  }
  elseif ($version < 2)
  {
   $level='major';
  }
  else
  {
   $level='minor';
  }

  if (db_upgrade($level,$version,strtolower($backup)))
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
