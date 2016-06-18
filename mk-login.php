<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

if (!empty($_GET['action']))
{
    switch ($_GET['action'])
    {
        case 'force-modern':
          $_SESSION['modern']='full';
          setcookie('ss',$_SESSION['modern'],time()+60*60*24*365);
          header("Location: ".$config->siteroot);
        break;
        case 'keep-classic':
          $_SESSION['classic']=true;
          setcookie('ss','classic',time()+60*60*24*365);
          header("Location: ".$config->siteroot);
        break;
        case 'new':
	    $formname="Register";
        break;
        case 'reset':
        /** Get Mailer Ready! **/
        require_once dirname(__FILE__)."/mk-core/phpmailer/PHPMailerAutoload.php";
        $mail=new PHPMailer();
        $email['type']=$config->email_mta;
        $email['from']['name']=$config->owner;
        $email['from']['address']=$config->support_email;
        parse_str($config->email_server,$email['server']);
        switch ($email['type'])
        {
            case 'smtp':
            $mail->isSMTP();
            $mail->SMTPAuth=$email['server']['auth'];
            if ($email['server']['auth'] == TRUE)
            {
                $mail->SMTPSecure=$email['server']['security'];
            }
            $mail->Host=$email['server']['host'];
            if (!empty($email['server']['port']))
            {
                $mail->Port=$email['server']['port'];
            }
            $mail->Username=$email['server']['user'];
            $mail->Password=$email['server']['password'];
            break;
            case 'sendmail':
            if ($email['server']['host'] != 'localhost')
            {
                trigger_error("Sendmail will send e-mail from the local server. If you need mail sent from a remote SMTP server, please choose SMTP as your MTA",E_USER_WARNING);
                $email['server']['host']="localhost";
            }
            $mail->isSendmail();
            break;
            case 'phpmail':
            default:
            $mail->isMail();
            if (empty($email['server']['host']))
            {
                $email['server']['host']="localhost";
            }
            elseif ($email['server']['host'] != 'localhost')
            {
                trigger_error("You have opted to use PHP's default Mail Transport Auhtority, but have set a remote server as your mail server. The default MTA does not support remote servers, so the local one will be used instead. If you need to set a remote mail server, please use the SMTP MTA!",E_USER_WARNING);
                $email['server']['host']="localhost";
            }
        }
        //Set message headers, the message will be set during the approperiate stages
        $mail->From=$email['from']['address'];
        $mail->FromName=$email['from']['name'];
        $mail->IsHTML(true);
        /** E-Mail Ready **/

        $usr=new MomokoUser;
        if (!empty($_POST['pass2'])) //presence of $_POST['pass2'] means user has supplied a new password
        {
            $info=$usr->getBySID($_POST['sid']);
            if ($_POST['pass1'] == $_POST['pass2'])
            {
                $mail->AddAddress($info->email);
                $mail->Subject=$GLOBALS['SET']['name'].": Your Password has been reset!";
                $mail->Body=<<<HTML
Hello {$info->name},

This is a friendly e-mail confirming that you have reset your password. We are sorry for any incovience this may have caused you and hope you will never have to see this message again. To assist with that please keep your password in a secure place, and remember not to share it with anyone!

If you believe you are recieving this e-mail in error, for example, you did not request your password to be reset, please contact the site administration immediately at {$GLOBALS['SET']['support_email']} so they can investigate the matter further. They may instruct you to reset your password again. For your reference, we have included the reset link at the end of this e-mail.

In case you need to reset again, the link is: {$GLOBALS['SET']['baseuri']}/mk-login.php?action=reset

Sorry for any inconvience!
HTML;
                $mail->AltBody=<<<TXT
Hello {$info->name},

This is a friendly e-mail confirming that you have reset your password. We are sorry for any incovience this may have caused you and hope you will never have to see this message again. To assist with that please keep your password in a secure place, and remember not to share it with anyone!

If you believe you are recieving this e-mail in error, for example, you did not request your password to be reset, please contact the site administration immediately at {$GLOBALS['SET']['support_email']} so they can investigate the matter further. They may instruct you to reset your password again. For your reference, we have included the reset link at the end of this e-mail.

In case you need to reset again, the link is: {$GLOBALS['SET']['baseuri']}/mk-login.php?action=reset

Sorry for any inconvience!
TXT;
                $data['password']=crypt($_POST['pass2'],$GLOBALS['SET']['salt']);
                if ($usr->updateByID($info->num,$data))
                {
                    $mail->Send();
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
                if ($sid=$usr->putSID($data,crypt(time(),$GLOBALS['SET']['salt'])))
                {
                    $link="http://".$GLOBALS['SET']['baseuri']."/mk-login.php?action=reset&sid=".$sid;
                    $mail->AddAddress($info->email);
                    $mail->Subject=$GLOBALS['SET']['name'].": Your Password Reset Instructions";
                    $mail->Body=<<<HTML
Hello {$info->name},
Per your request we are sending you instructions on how to reset your password. If you did <strong>not</strong> make this request, you may ignore this e-mail. Your password has not been reset yet!

If you <em>did</em> make this request then you may start the reset process by clicking this <a href="{$link}">reset</a> link. If the link does not work you may copy and paste the following URL "{$link}".

Once you have done so you will be presented with a form. Using this form, pick a new password and then type it a second time. Upon submitting this form, by clicking the button at the end, your new password should be set. If this is the case you will be presented with a confirmation page and recieve a confirmation e-mail.

Please remember, keep your password safe. It is important that you remember it so you do not have to see this e-mail again, but do keep it secure. <strong>NEVER</strong> give out your password to anyone, including this site's administration. <strong>ALWAYS</strong> use this process to reset a password in case your forget!

Thank you!
HTML;
                    $mail->AltBody=<<<TXT
Hello {$info->name},
Per your request we are sending you instructions on how to reset your password. If you did **not** make this request, you may ignore this e-mail. Your password has not been reset yet!

If you *did* make this request then you may start the reset process by copying and pasting the following URL into your browser's address bar "{$link}".

Once you have done so you will be presented with a form. Using this form, pick a new password and then type it a second time. Upon submitting this form, by clicking the button at the end, your new password should be set. If this is the case you will be presented with a confirmation page and recieve a confirmation e-mail.

Please remember, keep your password safe. It is important that you remember it so you do not have to see this e-mail again, but do keep it secure. **NEVER** give out your password to anyone, including this site's administration. **ALWAYS** use this process to reset a password in case your forget!

Thank you!
TXT;
                    if ($mail->Send())
                    {
                        $formname="Password Reset Instructions";
                    }
                    else
                    {
                        trigger_error("Password reset instructions for {$info->name} could not be sent to {$info->email}! ".$mail->ErrorInfo,E_USER_WARNING);
                        $formname="500 Internal Server Error";
                    }
                }
                else
                {
                    $formname="500 Internal Server Error";
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
$props['link']=$config->baseuri."/?action=".strtolower($formname);
$props['recovery']=$config->baseuri."/mk-login.php?action=reset";

$form=new MomokoPage($formname,$auth,$props);

$tpl=new MomokoTemplate($auth,$config);
print $tpl->toHTML($form);
