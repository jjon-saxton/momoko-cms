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
<div class="panel panel-warning">
<div class="panel-heading">
<h3 class="panel-title">Warning, Pre-release software!</h3>
</div>
<div class="panel-body">
<p>You are about to upgrade to a pre-release version of MomoKO ({$version}). Pre-release versions are unstable with the most unstable versions being those ending an 'a' (known as alpha releases) and the most stable versions ending in 'b' (known as beta releases). It is only recommended that developers use alpha releases while users who wish to help us debug MomoKO may use the beta releases. Regardless, we do NOT recommened either for production enviornments. Do you wish to upgrade to this pre-release software?</p>
</div>
<div class="panel-footer installer">
<div class="half center">
<button class="btn btn-success" onclick="window.location='?override=y'">Yes</button>
</div>
<div class="half center">
<button class="btn btn-danger" onclick="window.location='./README.md'">No</button>
</div>
</div>
HTML;
}
elseif ($db_major == $major)
{
 $update=db_upgrade('minor',$GLOBALS['SET']['version']);
 $body=<<<HTML
<div class="panel panel-success">
<div class="panel-heading">
<h3 class="panel-title">Update Finished</h3>
</div>
<div class="panel-body">
<p>The update appears to have been completed successfully. If something does not appear correct, please have a look at your logs.</p>
</div>
<div class="panel-footer center">
<button class="btn btn-primary"onclick="window.location='./'">Continue</button>
</div>
</div>
HTML;
}
else
{
 $update=db_upgrade('major',$GLOBALS['SET']['version']);
 $body=<<<HTML
div class="panel panel-success">
<div class="panel-heading">
<h3 class="panel-title">Update Finished</h3>
</div>
<div class="panel-body">
<p>The update appears to have been completed successfully. If something does not appear correct, please have a look at your logs.</p>
</div>
<div class="panel-footer center">
<button class="btn btn-primary"onclick="window.location='./'">Continue</button>
</div>
</div>
HTML;
}
?>
<html>
<head>
<title>MomoKO Guided Updater: web</title>
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
<h1 id="HEADER" class="body center">MomoKO Guided Updater</h1>
<div id="BODY" class="body"><?php print $body ?></div>
</body>
</html>
