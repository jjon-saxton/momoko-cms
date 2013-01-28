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
  fwrite(STDOUT,"A pre 1.2 database has been detected. MomoKO 1.2 requires new database table and settings. These will have to be created now in order to ensure proper functionality. If you chose not to upgrade now your site WILL NOT work!\n");
  fwrite(STDOUT,"Apply update now? [Y]es or [N]o ");
  $update=trim("\n",fputs(STDIN));
}

if (strtolower($update) == 'y')
{
  require '../assets/core/install.inc.php'; //Update function resides in this file for global interface use
  if (db_upgrade($version))
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
