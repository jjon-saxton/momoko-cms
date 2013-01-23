#!/usr/bin/php
<?php

require dirname(__FILE__).'/interface.inc.php';

fwrite(STDOUT,"Checking database version...\n");
if ($settings=new DataBaseTable(DAL_TABLE_PRE.'settings',DAL_DB_DEFAULT))
{
  $query=$settings->getData("key:'version'",array('key','value'),null,1);
  $settings=$query->first();
  if ($settings->value > 0)
  {
    fwrite(STDOUT,$settings->value." Detected! Checking for required database updates...\n");
    if ($settings->value >= 1.2)
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
  require '../assets/core/install.inc.php';
  if (db_upgrade($version))
  {
    fwrite(STDOUT,"Database update applied!\n");
  }
  else
  {
    fwrite(STDOUT,"Could not applie database changes! Please check your permission!\n");
  }
}
elseif(strtolower($update) == 'n')
{
  fwrite(STDOUT,"User aborted!\n");
}

exit(0);

?>
