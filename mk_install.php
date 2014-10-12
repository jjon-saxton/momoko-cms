<?php
if ($_GET['step'] < 2 && file_exists(dirname(__FILE__)."/database.ini"))
{
 header("Location: //".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])); //someone added database.ini, for security reasons, do NOT allow mk_install to be used!
 exit();
}

define ("INSTALLER",TRUE);
require './core/install.inc.php';
require './core/database.inc.php';
$version=trim(file_get_contents('./version.nfo.txt'),"\n");

switch (@$_GET['step'])
{
 case "3":
 $body=enter_settings($_POST);
 break;
 case "2":
 $body=write_databaseini($_POST);
 break;
 case "1":
 $body=configure_database();
 break;
 default:
 $body=<<<HTML
<div class="message box"><h3 class="message title">Ready to begin?</h3>
<p>This script is designed to configure MomoKO to connect to a database server and prepare your database to handle MomoKO's data. Please ensure that your database is up and that you have created a user and schema for MomoKO. Are you ready to confgiure MomoKO version {$version}?</p>
<div class="message buttons"><button onclick="window.location='?step=1'">Yes</button><button onclick="window.location='./README.html'">No</button></div></div>
HTML;
 break;
}
?>
<html>
<head>
<title>MomoKO Guided Installer: web</title>
<style type=text/css>
body {background-color: Window; color: WindowText}
div.box {border: 1px solid WindowText; border-radius: 5px; background-color: white; color: black}
div.body {width:90%; margin: auto}
.title {text-align:center}
div.message {width: 60%; margin:auto; margin-top:50px; margin-bottom: 50px}
div.message .buttons {width:100%; text-align:center}
div.message .buttons button {font:14pt Arial,sans-serif; font-weight:bold; margin-left:25px; margin-right:25px}
div.form.box {border: 0; width:75%; margin:auto; margin-bottom: 10px}
div.form.button {width:75%; margin:auto; text-align:right}
div.form.button button {font:14pt Arial,sans-serif}
/* TODO: write installer styles */
</style>
</head>
<body>
<h1 id="HEADER" class="body title">MomoKO Guided Installer</h1>
<div id="BODY" class="body box"><?php print $body ?></div>
</body>
</html>
<?php

function configure_database()
{
 $pdo_drivers=PDO::getAvailableDrivers();
 $driver_opts=null;
 foreach ($pdo_drivers as $driver)
 {
  $driver_opts.="<option>".$driver."</option>\n";
 }
 return <<<HTML
<form action="?step=2" method=post>
<h2 class="form title">Database Settings</h2>
<div class="form section box"><h3 id="s1" class="form section title">Server</h3>
<table width=100% border=0 cellspacing=0 cellpadding=2>
<tr>
<td align=right><label for="driver">Database Type:</label></td><td><select id="driver" name="driver">
{$driver_opts}</select></td>
</tr>
<tr>
<td align=right><label for="host">Host or File:</label></td><td><input type=text id="host" name="host" value="localhost"></td>
</tr>
<tr>
<td align=right><label for="port">Port:</label></td><td><input type=number id="port" name="port"></td>
</tr>
<table>
</div>
<div class="form section box"><h3 id="s2" class="form section title">Schema</h3>
<table width=100% border=0 cellspacing=0 cellpadding=2>
<tr>
<td align=right><label for="name">Schema Name:</label></td><td><input type=text id="name" name="name"></td>
</tr>
<tr>
<td align=right><label for="user">User Name:</label></td><td><input type=text id="user" name="user"></td>
</tr>
<td align=right><label for="password">Password:</label></td><td><input type=password id="password" name="password"></td>
</tr>
<tr>
<td align=right><label for="prefix">Table Prefix:</label></td><td><input type=text id="prefix" name="tableprefix" value="mk_"></td>
</tr>
</table>
</div>
<div class="form next button"><button type=submit">Next -></buton></div>
</form>
HTML;
}

function write_databaseini($data)
{
 $basedir=dirname(__FILE__);
 $ini=<<<TXT
[database]
driver = {$data['driver']}
host = {$data['host']}
port = {$data['port']}

[schema]
name = "{$data['name']}"
username = "{$data['user']}"
password = "${data['password']}"
tableprefix = "${data['tableprefix']}"
TXT;

 if (is_writable($basedir))
 {
  if (file_put_contents($basedir."/database.ini",$ini))
  {
   return <<<HTML
<div class="message box"><h3 class="message title">Database Configured!</h3>
<p>This database is now configured. We will now test your settings by creating the required tables. If we cannot create these tables you will be directed back to the first step to check your settings!</p>
<div class="form next button"><button onclick="window.location='?step=3'">Continue -></button></div>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<div class="message error box"><h3 class="message error title">Could Not Write Congifuration!</h3>
<p>We were unable to write '{$basedir}/database.ini'! PLease go back and try again. If see this error again, check your permissions or consult your operating system's manuals.</p>
<div class="form back button"><button onclick="history.back()"><- Previous</button></div></div>
HTML;
  }
 }
 else
 {
  return <<<HTML
<div class="message box"><h3 class="message error title">{$basedir} Not Writable!</h3>
<p>We were unable to write '{$basedir}/database.ini'! This web server does not have permissions to write to {$basedir}. Please change permissions, go back, and try again!</p>
<div class="form back button"><button onclick="history.back()"><- Previous</button></div></div>
HTML;
 }
}

function enter_settings($data=null)
{
 if (!empty($data))
 {
  $success=extract($data);
  if ($success <= 3 && fill_tables($settings,$admin,$defaults))
  {
   return <<<HTML
<div class="message box"><h3 class="message title">Congratulations!</h3>
<p>Your site is now set up and ready to go! After clicking the button below you will be able to login as the administrator you just set up, add content, and add users to your site. Have fun and welcome to the MomoKO family!</p>
<div class="form next button"><button onclick="window.location='./'">Go to your site -></button></div>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<div class="message error box"><h3 class="message error title">Could Not Fill Tables!</h3>
<p>We could not fill the tables with the data you entered. Ensure that you gave the user you set permission to insert data and try again. If the problem persists contact your server administrator.
<div class="form back button"><button onclick="history.back()"><- Previous</button></div></div>
HTML;
  }
 }
 else
 {
  try
  {
   create_tables('./database.ini');
  }
  catch (Exception $err)
  {
   unlink ('./database.ini');
   $message=$err->getMessage();
   return <<<HTML
<div class="message error box"><h3 class="message error title">Could Not Create Tables!</h3>
<p>We could not create the tables MomoKO needs in the database you set up. This error is fetal. Please go back to the first step and reconfigure your database!</p>
<p class="error message">{$message}</p>
<div class="form back button"><button onclick="window.location='?step=1'"><<- Start Over</button></div></div>
HTML;
  }

  $basedir=dirname(__FILE__);
  return <<<HTML
<form action="?step=3" method=post>
<h2 class="form title">Settings</h2>
<div class="form section box"><h3 id="s1" class="form section title">Site Settings</h3>
<table width=100% border=0 cellspacing=0 cellpadding=2>
<tr>
<td align=right><label for="name">Site Name:</label></td><td><input type=text id="name" name="settings[name]"><input type=hidden name="settings[rewrite]" value=""></td>
</tr>
<tr>
<td align=right><label for="session">Simple name for Sessions:</label></td><td><input type=text id="session" name="settings[session]" value="MK"></td>
</tr>
<tr>
<td align=right><label for="baseuri">URI Where MomoKO lives:</label></td><td><input type=text id="baseuri" name="settings[baseuri]" placeholder="Autodetect"></td>
</tr>
<tr>
<td align=right><label for="basedir">Absolute path where MomoKO lives:</label></td><td><input type=text id="basedir" name="settings[basedir]" value="{$basedir}"></td>
</tr>
<tr>
<td align=right><label for="pagedir">Path where Pages will be stored:</label></td><td><input type=text id="pagedir" name="settings[pagedir]" value="{$basedir}/pages/"></td>
</tr>
<tr>
<td align=right><label for="tempdir">Path where Temporary Files will be stored:</label></td><td><input type=text id="tempdir" name="settings[tempdir]" value="{$basedir}/temp/"></td>
</tr>
<tr>
<td align=right><label for="logdir">Path where Log Files will be stored:</label></td><td><input type=text id="logdir" name="settings[logdir]" value="{$basedir}/logs/"></td>
</tr>
<tr>
<td align=right><label for="filedir">Path where all other Files will be stored:</label></td><td><input type=text id="filedir" name="settings[filedir]" value="{$basedir}/files/"></td>
</tr>
<table>
</div>
<div class="form section box"><h3 id="s2" class="form section title">Administrator Settings</h3>
<table width=100% border=0 cellspacing=0 cellpadding=2>
<tr>
<td align=right><label for="admin">User Name:</label></td><td><input type=text id="admin" name="admin[name]"></td>
</tr>
<tr>
<td align=right><label for="password">Password:</label></td><td><input type=password id="password" name="admin[password]"></td>
</tr>
<tr>
<td align=right><label for="email">E-Mail Address:</label></td><td><input type=email id="email" name="admin[email]"></td>
</tr>
</table>
</div>
<div class="form section box"><h3 id="s3" class="form section title">Default User Settings</h3>
<table width=100% border=0 cellspacing=0 cellpadding=2>
<tr>
<td align=right><label for="shortdate">Short Date Format:</label></td><td><input type=text id="shortdate" name="defaults[sdf]" value="m/d/Y"></td>
</tr>
<table>
</div>
<div class="form next button"><button type=submit">Next -></buton></div>
</form>
HTML;
 }
}

?>
