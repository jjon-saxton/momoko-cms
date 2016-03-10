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
        $usr=new MomokoUser;
        if (!empty($_POST['pass2'])) //presence of $_POST['pass2'] means user has supplied a new password
        {
            $info=$usr->getBySID($_POST['sid']);
            if ($_POST['pass1'] == $_POST['pass2'])
            {
                $data['password']=crypt($_POST['pass2'],$GLOBALS['SET']['salt']);
                if ($usr->updateByID($info->num,$data))
                {
                    $formname="Password Reset Confirmation";
                }
                else
                {
                    trigger_error("Could not update user's password!'",E_USER_ERROR);
                }
            }
        }
        elseif (!empty($_GET['sid'])) //presence of $_GET['sid'] means user clicked the link in their e-mail
        {
            $formname="Set New Password";
            $props['sid']=$_GET['sid'];
        }
        elseif (!empty($_POST['email'])) //presence of $_POST['email'] means user has submitted an e-mail to locate their user and send a reset lnk to
        {
            if ($info=$usr->getByEmail($_POST['email']))
            {
                $data[0]=$info->num;
                $data[1]=$info->name;
                $data[2]=$info->email;
                if ($file=$usr->putSID(md5(time())))
                {
                    //TODO send full reset instructions to $info->email
                    $formname="Password Reset Instructions";
                }
            }
            else
            {
                $formname="User Not Found";
            }
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
