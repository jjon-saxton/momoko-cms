<?php

define ("INSTALLER",TRUE);
require './mk-core/install.inc.php';
require './mk-core/common.inc.php';
$version=MOMOKOVERSION;
list($major,$minor,$revision)=explode('.',preg_replace("/[^0-9,.]/","",$version));
list($db_major,$db_minor)=explode('.',$GLOBALS['SET']['version']);

if ($revision >= 80 && $_GET['override'] != 'y')
{
 $body=<<<HTML
<div class="message warning box"><h3 class="message title"Warning, Pre-release software!</h3>
<p>You are about to upgrade to a pre-release version of MomoKO ({$version}). Pre-release versions are unstable with the most unstable version be those ending an 'a' and the most stable versions ending in 'b'. It is only recommended that developers use 'a' releases while users who wish to help us debug our releases may use the 'b' version. Regardless, we do NOT recommened either for production enviornments. Do you wish to upgrade to this pre-release software?</p>
<div class="message buttons"><button onclick="window.location='?override=y'">Yes</button><button onclick="window.location='./README.md'">No</button></div></div>
HTML;
}
elseif ($db_major == $major)
{
 $body="Minor upgrade, should only need to check for new addins and settings.";
}
else
{
 $body="Major upgrade, may need to upgrade the entire database.";
}
?>
<html>
<head>
<title>MomoKO Guided Updater: web</title>
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
<h1 id="HEADER" class="body title">MomoKO Guided Updater</h1>
<div id="BODY" class="body box"><?php print $body ?></div>
</body>
</html>
