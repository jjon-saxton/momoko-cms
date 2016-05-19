<?php
if (@$_GET['step'] < 2 && file_exists(dirname(__FILE__)."/database.ini"))
{
 header("Location: //".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])); //someone added database.ini, for security reasons, do NOT allow mk_install to be used!
 exit();
}

define ("INSTALLER",TRUE);
require './mk-core/install.inc.php';
require './mk-core/database.inc.php';
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
<div class="panel panel-info">
<div class="panel-heading">
<h3 class="panel-title">Ready to begin?</h3>
</div>
<div class="panel-body">
<p>This script is designed to configure MomoKO to connect to a database server and prepare your database to handle MomoKO's data. Please ensure that your database is up and that you have created a user and schema for MomoKO. Are you ready to confgiure MomoKO version {$version}?</p>
</div>
<div class="panel-footer installer">
<div class="half center"><button class="btn btn-success" onclick="window.location='?step=1'">Yes</button></div>
<div class="half center"><button class="btn btn-danger" onclick="window.location='./README.md'">No</button></div>
</div>
</div>
HTML;
 break;
}
?>
<html>
<head>
<title>MomoKO Guided Installer: web</title>
<link href="./mk-core/styles/momoko.css" rel="stylesheet" type="text/css">
<style type=text/css>
body {background-color: Window; color: WindowText}
div.box {border: 1px solid WindowText; border-radius: 5px; background-color: white; color: black}
div.body {width:90%; margin: auto}
.title {text-align:center}
.panel-footer.installer {height:55px}
</style>
</head>
<body>
<h1 id="HEADER" class="body title">MomoKO Guided Installer</h1>
<div id="BODY" class="body"><?php print $body ?></div>
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
<div class="panel-group">
<form role="form" action="?step=2" method=post>
<h2 class="title">Database Settings</h2>
<div class="panel panel-default">
<div class="panel-heading">
<h3 id="s1" class="panel-title">Server</h3>
</div>
<div class="panel-body">
<div class="form-group">
<label for="driver">Database Type:</label>
<select class="form-control" id="driver" name="driver">
{$driver_opts}</select>
</div>
<div class="form-group">
<label for="host">Host or File:</label>
<input class="form-control"type=text id="host" name="host" value="localhost">
</div>
<div class="form-group">
<label for="port">Port:</label>
<input class="form-control" type=number id="port" name="port">
</div>
</div>
</div>
<div class="panel panel-default">
<div class="panel-heading">
<h3 id="s2" class="panel-title">Schema</h3>
</div>
<div class="panel-body">
<div class="form-group">
<label for="name">Schema Name:</label>
<input class="form-control" type=text id="name" name="name">
</div>
<div class="form-group">
<label for="user">User Name:</label>
<input class="form-control" type=text id="user" name="user">
</div>
<div class="form-group">
<label for="password">Password:</label>
<input class="form-control" type=password id="password" name="password">
</div>
<div class="form-group">
<label for="prefix">Table Prefix:</label>
<input class="form-control" type=text id="prefix" name="tableprefix" value="mk2_">
</div>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading">
<h3 class="panel-title">Continue</h3>
</div>
<div class="panel-footer center">
<button class="btn btn-primary"type=submit">Next <span class="glyphicon glyphicon-triangle-right"></buton>
</div>
</div>
</form>
</div>
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
<div class="panel panel-success">
<div class="panel-heading">
<h3 class="panel-title">Database Configured!</h3>
</div>
<div class="panel-body">
<p>This database is now configured. We will now test your settings by creating the required tables. If we cannot create these tables you will be directed back to the first step to check your settings!</p>
</div>
<div class="panel-footer center">
<button class="btn btn-success" onclick="window.location='?step=3'">Continue <span class="glyphicon glyphicon-triangle-right"></button>
</div>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<div class="panel panel-danger">
<div class="panel panel-heading">
<h3 class="panel-title">Could Not Write Configuration!</h3>
</div>
<div class="panel-body">
<p>We were unable to write '{$basedir}/database.ini'! PLease go back and try again. If see this error again, check your permissions or consult your operating system's manuals.</p>
</div>
<div class="panel-footer center"><button class="btn btn-danger" onclick="history.back()"><span class="glyphicon glyphicon-triangle-left"> Previous</button>
</div>
</div>
HTML;
  }
 }
 else
 {
  return <<<HTML
<div class="panel panel-warning">
<div class="panel-heading">
<h3 class="panel-title">{$basedir} Not Writable!</h3>
</div>
<div class="panel-body">
<p>We were unable to write '{$basedir}/database.ini'! This web server does not have permissions to write to {$basedir}. Please change permissions, go back, and try again!</p>
</div>
<div class="panel-footer center">
<button class="btn btn-warning" onclick="history.back()"><span class="glypicon glyphicon-triangle-left" Previous</button>
</div>
</div>
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
<div class="panel panel-success">
<div class="panel-heading">
<h3 class="panel-title">Congratulations!</h3>
</div>
<div class="panel-body">
<p>Your site is now set up and ready to go! After clicking the button below you will be able to login as the administrator you just set up, add content, and add users to your site.</p>
<p>For your security you should change the permissions of your base directory so it is read-only to the server. Please ensure that your content directory, however, remains writable so MomoKO can store attachments there.</p>
</div>
<div class="panel-footer center">
<button class="btn btn-primary"onclick="window.location='./'">Go to your site <span class="glyphicon glyphicon-forward"></button>
</div>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<div class="panel panel-danger">
<div class="panel-heading">
<h3 class="panel-title">Could Not Fill Tables!</h3>
</div>
<div class="panel-body">
<p>We could not fill the tables with the data you entered. Ensure that you gave the user you set permission to insert data and try again. If the problem persists contact your server administrator.
</div>
<div class="panel-footer center">
<button class="btn btn-danger" onclick="history.back()"><spane class="glyphicon glyphicon-triangle-left"> Previous</button>
</div>
</div>
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
<div class="panel panel-danger">
<div class="panel-heading">
<h3 class="panel-title">Could Not Create Tables!</h3>
</div>
<div class="panel-body">
<p>We could not create the tables MomoKO needs in the database you set up. This error is fetal. Please go back to the first step and reconfigure your database!</p>
<p class="error message">{$message}</p>
</div>
<div class="panel-footer center">
<button class="btn btn-danger" onclick="window.location='?step=1'"><span class="glyphicon glyphicon-backward"> Start Over</button>
</div>
</div>
HTML;
  }

  $basedir=dirname(__FILE__);
  return <<<HTML
<div class="panel-group">
<form role="form" action="?step=3" method=post>
<h2 class="title">Settings</h2>
<div class="panel panel-default">
<div class="panel-heading">
<h3 id="s1" class="panel-title">Site Settings</h3>
</div>
<div class="panel-body">
<div class="form-group">
<label for="name">Site Name:</label>
<input class="form-control" type=text id="name" name="settings[name]">
<input type=hidden name="settings[rewrite]" value="">
</div>
<div class="form-group">
<label for="session">Simple name for Sessions:</label>
<input class="form-control" type=text id="session" name="settings[session]" value="MK2">
</div>
<div class="form-group">
<label for="baseuri">URI Where MomoKO lives:</label>
<input class="form-control" type=text id="baseuri" name="settings[baseuri]" placeholder="Autodetect">
</div>
<div class="form-group">
<label for="basedir">Absolute path where MomoKO lives:</label>
<input class="form-control" type=text id="basedir" name="settings[basedir]" value="{$basedir}">
</div>
<div class="form-group">
<label for="filedir">Absolute path where attachments and other content will be uploaded:</label>
<input class="form-control" type=text id="filedir" name="settings[filedir]" value="{$basedir}/mk-content/">
</div>
</div>
</div>
<div class="panel panel-default">
<div class="panel-heading">
<h3 id="s2" class="panel-title">Administrator Settings</h3>
</div>
<div class="panel-body">
<div class="form-group">
<label for="admin">User Name:</label>
<input class="form-control" type=text id="admin" name="admin[name]">
</div>
<div class="form-group">
<label for="password">Password:</label>
<input class="form-control" type=password id="password" name="admin[password]">
</div>
<div class="form-group">
<label for="email">E-Mail Address:</label>
<input class="form-control" type=email id="email" name="admin[email]">
</div>
</div>
</div>
<div class="panel panel-default">
<div class="panel-heading">
<h3 id="s3" class="panel-title">Default User Settings</h3>
</div>
<div class="panel-body">
<div class="form-group">
<label for="shortdate">Short Date Format:</label>
<input class="form-control" type=text id="shortdate" name="defaults[sdf]" value="m/d/Y">
</div>
<div class="form-group">
<label for="longdate">Long Date Format:</label>
<input class="form-control" type=text id="longdate" name="defaults[ldf]" value="F j, Y">
</div>
<div class="form-group">
<label for="numrows">Number of Rows in a Table (dashboard and addins):</label>
<input class="form-control" type=number id="numrows" name="defaults[rpt]" value="50">
</div>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading">
<h3 class="panel-title">Finalize</h3>
</div>
<div class="panel-footer center">
<button class="btn btn-primary" type=submit">Next <span class="glyphicon glyphicon-triangle-right"></buton>
</div>
</form>
HTML;
 }
}

?>
