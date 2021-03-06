#!/usr/bin/php
<?php
define ("INSTALLER",TRUE);

require dirname(__FILE__).'/interface.inc.php';
require dirname(__FILE__).'/../mk-core/install.inc.php';

$version=MOMOKOVERSION;

fwrite(STDOUT,"Ready to install MomoKO v".$version."? [Y]es or [N]o\n");
if (strtolower(trim(fgets(STDIN),"\n\r")) == 'y')
{
  fwrite(STDOUT,"This script is designed to prepare your database to run MomoKO.\n");
  fwrite(STDOUT,"This script will fail if the base directory is not writable or if a file called 'database.ini' already exists there. Please see the README for more details.\n");
  fwrite(STDOUT,"Have you configured MomoKO v".$version." as described in the README? [Y]es or [N]o\n");
  if (strtolower(trim(fgets(STDIN),"\n\r")) == 'y')
  {
    if (!is_writable("../") || file_exists("../database.ini"))
    {
     fwrite(STDOUT,"Database cannot be configured because I can't write to my base folder or a configuration already exists. Please correct the issue and try again!\n");
     exit();
    }
    fwrite(STDOUT, "Welcome to MomoKO!\n");
    fwrite(STDOUT,"I need to know a few things about the database server I will be connecting to.\n");
    $pdo_drivers=PDO::getAvailableDrivers();
    $pdo_drivers=implode(", ",$pdo_drivers);
    trim ($pdo_drivers);
    fwrite(STDOUT,"What type of database server am I connecting to? (select one of the following: {$pdo_drivers})\n");
    $dserver['driver']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"Where is this database server? (for MySQL and similar servers provide the hostname, for SQLite provide the .sql file location)\n");
    $dserver['host']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"What port number, if applicable, will I use to open a database connection?\n");
    $dserver['port']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"Okay, thank you! Now I will need to know a bit about the particular database schema I will be using to build tables and store data. Please ensure that this schema is already created and that you have a user other than root for me to use to connect to it if applicable.\n");
    fwrite(STDOUT,"What is the name of the database schema you want me to use?\n");
    $dschema['name']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"What user name will I need to access this schema, note: A user is not required to access SQLite databases?\n");
    $dschema['user']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"What is the password, if needed, for the user above?\n");
    $dschema['password']=trim(fgets(STDIN),"\n\r");
    fwrite(STDOUT,"To help me defirentiate myself from other MomoKOs and other data I can add a prefix to my table. What prefix would like me to use? (i.e. mk_)\n");
    $dschema['tableprefix']=trim(fgets(STDIN),"\n\r");

    $ini=<<<TXT
[database]
driver = {$dserver['driver']}
host = {$dserver['host']}
port = {$dserver['port']}

[schema]
name = "{$dschema['name']}"
username = "{$dschema['user']}"
password = "${dschema['password']}"
tableprefix = "${dschema['tableprefix']}"
TXT;

    file_put_contents('./database.ini',$ini);
    
    fwrite(STDOUT,"This script is now creating the required database tables. Please stand by...\n");
    if (create_tables('./database.ini'))
    {
     fwrite(STDOUT,"The required tables are in place. The script will now fill the tables with default data.\n");

     $basedir=getcwd();

     fwrite(STDOUT,"Please provide a name for your site: ");
     $setting['name']=trim(fgets(STDIN),"\n\r");
     $setting['rewrite']=false;
     fwrite(STDOUT,"Please provide a simple name for session cookies [MK]: ");
     $setting['session']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please provide the document root for your site [AUTODETECT]: ");
     $setting['baseuri']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Please remind me what folder I'm installed in [$basedir]: ");
     $setting['pagedir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store all other files? [$basedir/mk-content/]: ");
     $setting['filedir']=trim(fgets(STDIN),"\n\r");
     fwrite(STDOUT,"Where should I store temporary files? [~{filedir}/temp/]: ");
     $setting['tempdir']=trim(fgets(STDIN),"\n\r");

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
     fwrite(STDOUT,"Time format [H:i:s]");
     $defaults['tf']=trim(fgets(STDIN),"\n\r");
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
