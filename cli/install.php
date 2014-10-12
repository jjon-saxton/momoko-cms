#!/usr/bin/php
<?php
define ("INSTALLER",TRUE);

require dirname(__FILE__).'/interface.inc.php';
require dirname(__FILE__).'/../core/install.inc.php';

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
    if (create_tables('../database.ini'))
    {
     fwrite(STDOUT,"The required tables are in place. The script will now fill the tables with default data.\n");

     $basedir=dirname(__FILE__);
     $basedir=str_replace("cli","",$basedir);
     $basedir=rtrim($basedir,"/");

     fwrite(STDOUT,"Please provide a name for your site: ");
     $setting['name']=trim(fgets(STDIN),"\n\r");
     $setting['rewrite']=false;
     fwrite(STDOUT,"Please provide a simple name for session cookies [MK]: ");
     $setting['session']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please provide the document root for your site [AUTODETECT]: ");
     $setting['baseuri']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please remind me what folder I'm installed in [$basedir]: ");
     $setting['basedir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store pages? [$basedir/pages/]: ");
     $setting['pagedir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store temporary files? [$basedir/temp/]: ");
     $setting['tempdir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store log files? [$basedir/logs/]: ");
     $setting['logdir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store all other files? [$basedir/files/]: ");
     $setting['filedir']=trim(fgets(STDIN),"\n\r");

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
     
     if (fill_tables($setting, $admin, $defaults))
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
