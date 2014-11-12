#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';
require dirname(__FILE__).'/../assets/core/install.inc.php';

$version=MOMOKOVERSION;

fwrite(STDOUT,"Ready to install MomoKO v".$version."? [Y]es or [N]o\n");
if (strtolower(trim(fgets(STDIN),"\n\r")) == 'y')
{
  fwrite(STDOUT,"This script is designed to prepare your database to run MomoKO. This is the final step in the install procedure. You must first configure your installation in /assets/etc/main.conf.txt and configure your database in /assets/etc/dal.conf.txt\n");
  fwrite(STDOUT,"This script will fail if the files mentioned above are not available for read or have not been edited. Please see the README for more details.\n");
  fwrite(STDOUT,"Have you configured MomoKO v".$version." as described in the README? [Y]es or [N]o\n");
  if (strtolower(trim(fgets(STDIN),"\n\r")) == 'y')
  {
    fwrite(STDOUT,"This script is now creating the required database tables. Please stand by...\n");
    if (create_tables())
    {
     fwrite(STDOUT,"The required tables are in place. The script will now fill the tables with default data.\n");
     fwrite(STDOUT,"Please provide a name for your site: ");
     $setting['name']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please provide a user name for your CMS administrator: ");
     $admin['name']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please provide a password for your administrator: ");
     $admin['password']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please provide a contact e-mail for your administrator: ");
     $admin['email']=trim(fgets(STDIN),"\n\r");
     
     fwrite(STDOUT,"You may now provide optional default user settings. Leave an option blank to use the default setting (presented in bracets [])\n");
     fwrite(STDOUT,"Short date format [m/d/Y]: ");
     $defaults['sdf']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Long date format [I F j, Y]: ");
     $defaults['ldf']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Number of rows in a table on any given page [20]: ");
     $defaults['rpt']=trim(fgets(STDIN),"\n\r");
     
     if (fill_tables($setting, $admin,$defaults))
     {
      fwrite(STDOUT,"System ready, you may access your content online at the URL you configured. We recommend using the admininstrator user name and password you just set to login and click 'change settings' to customize your site further.\n");
     }
     else
     {
      fwrite(STDOUT,"An error occuried while attempting to fill tables with default data!\n");
     }
    }
    else
    {
      fwrite(STDOUT,"Unable to create the required tables. Please ensure the configured database exists and that it is writable!\n");
    }
  }
  else
  {
    fwrite(STDOUT,"Please review the README and follow the configuration instructions included within, then return to this script to finalize your install\n");
  }
}

?>
