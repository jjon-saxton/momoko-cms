<?php
if (file_exists(dirname(__FILE__)."/database.ini"))
{
 header("Location: //".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])); //someone added database.ini, for security reasons, do NOT allow mk_install to be used!
 exit();
}

define ("INSTALLER",TRUE);
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
div.form.box {width:75%; margin:auto; margin-bottom: 10px}
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
 return <<<HTML
<form action="?step=2" method=post>
<h2 class="form title">Database Settings</h2>
<div class="form section box"><h3 id="s1" class="form section title">Server</h3>
</div>
<div class="form section box"><h3 id="s2" class="form section title">Schema</h3>
</div>
<div class="form next button"><button type=submit">Next -></buton></div>
</form>
HTML;
}

function write_databaseini($data)
{
/* TODO: use $data to write database.ini. Check that write happened before continuing! */
}

function enter_settings($data=null)
{
/* TODO: if $data is empty present settings form, otherwise write settings to database.*/
}

?>
