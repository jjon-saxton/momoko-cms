<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (!empty($_GET['action']))
{
    switch ($_GET['action'])
    {
        case 'new':
	    $formname="Register";
        break;
        case 'reset':
        if (!empty($_POST['pass2'])) //presence of $_POST['pass2'] means user has supplied a new password
        {
            //TODO retrieve user id from ~{sid}.txt using $_POST['sid'], set new password, and remove ~{sid}.txt
            $formname="Password Reset Confirmation";
        }
        elseif (!empty($_GET['sid'])) //presence of $_GET['sid'] means user clicked the link in their e-mail
        {
            $formname="Set New Password";
            $props['sid']=$_GET['sid'];
        }
        elseif (!empty($_POST['email'])) //presence of $_POST['email'] means user has submitted an e-mail to locate their user and send a reset lnk to
        {
            //TODO find the requester's user, create a ~{sid} and a ~{sid}.txt (~{sid}.txt should just contain the user id returned from the aforementioned query) and send them an e-mail with a reset link.
            $formname="Password Reset Instructions";
        }
        else
        {
            $formname="Request Password Reset";
        }
    }
}
else
{
	$formname="Login";
}
$props['link']=$GLOBALS['SET']['baseuri']."/?action=".strtolower($formname);
$props['recovery']=$GLOBALS['SET']['baseuri']."/mk-login.php?action=reset";

$form=new MomokoPage($formname,$props);

$tpl=new MomokoTemplate(pathinfo("/",PATHINFO_DIRNAME));
print $tpl->toHTML($form);
