<?php

class ResetPage implements MomokoObject
{
  public $path;
  private $info=array();
  
  public function __construct($path=null)
  {
    $this->path=$path;
    $this->showPage();
  }
  
  public function __get($key)
  {
    if (array_key_exists($key,$this->info))
    {
      return $this->info[$key];
    }
    else
    {
      return null;
    }
  }
  
  public function __set($key,$value)
  {
    return false;
  }
  
  public function get()
  {
    $html=<<<HTML
<html>
<head>
<title>{$this->info['title']}</title>
</head>
<body>
{$this->info['inner_body']}
</body>
</html>
HTML;
    $this->info['full_html']=$html;
    
    return $html;
  }
  
  public function showPage($sid=null,array $data=null)
  {
    $info['title']="Reset Password";
    if (!$sid && !@$data['email'])
    {
      $info['inner_body']=<<<HTML
<h2>Reset your Password</h2>
<p>We are sorry you are having trouble with your password. We hope this will help. Please supply your e-mail address. If the address matches one on file you will recieve and e-mail with further instructions.</p>
<form method=post>
<input type=email name="email"><br>
<input type=submit name="send" value="Continue">
</form>
HTML;
    }
    elseif (!$sid && $data['email'])
    {
      $query=$GLOBALS['ADDIN']['db-tables']['users']->getData("email:'".$data['email']."'",array('num','email'),null,1);
      $user=$query->first();
      $num=$user->num;
      if (!$num)
      {
        $info['title'].=" - No User";
        $info['inner_body']=<<<HTML
<h2>User not found</h2>
<p>We cannot find the user that matches the e-mail you provided. Please check your e-mail and try again.</p>
HTML;
      }
      else {
      $sid=$this->generateSid($num,time());
      $location=RESETURI."?sid=".$sid;
      //Mailer start
      $mail=new PHPMailer();
      $email['type']=$GLOBALS['SET']['email_mta'];
      parse_str($GLOBALS['SET']['email_server'],$email['server']); //TODO apply %26 (&) workaround when setting values for 'email_server' and so forth, may already exist, furhter testing needed.
      parse_str($GLOBALS['SET']['email_from'],$email['from']);
      switch ($email['type'])
      {
        case 'smtp':
        $mail->IsSMTP();
        //SMTP Configuration
        $mail->SMTPSecure=$email['server']['security'];
        $mail->Host=$email['server']['host'];
        if (isset($email['server']['port']))
        {
          $mail->Port=$email['server']['port'];
        }
        if (isset($email['server']['username']) && $email['server']['username'])
        {
         $mail->SMTPAuth=true;
         $mail->Username=$email['server']['username'];
         $mail->Password=$email['server']['password'];
        }
	break;
	case 'sendmail':
	$mail->isSendmail();
	if ($email['server']['host'] != 'localhost')
        {
           trigger_error("Sendmail must be sent from this server. If you need to use an external server please configure the mailer for SMTP!",E_USER_ERROR);
        }
	break;
        case 'phpmail':
	default:
        if ($email['server']['host'] != 'localhost')
        {
          trigger_error("You opted to use PHP's default Mail Transport Authority, but the hostname or server is not a local server, please use SMTP if you need to use an external server!",E_USER_ERROR);
        }
      } 
      //Message header
      $mail->From=$email['from']['address'];
      $mail->FromName=$email['from']['name'];
      $mail->IsHTML(true);
      //Message
      $mail->AddAddress($data['email']);
      $mail->Subject="Your Password Reset Instructions";
      $mail->Body=<<<HTML
Hello,
Per your request we are sending you instructions on how to reset your password. If you did <b>not</b> make this request please ignore this e-mail and do nothing. Otherwise begin by going to <a href="http://{$location}">{$location}</a>, if you cannot click this link please copy and paste into your browser. You will then be allowed to select a new password. Please chose a secure password you can remember!

Thank you!
HTML;
      $mail->AltBody=<<<TXT
Hello,
Per your request we are sending you instructions on how to reset your password. If you did **not** make this request please ignore this e-mail and do nothing. Otherwise copy this URL: http://{$location} and paste into your browser's address bar. You will then be allowed to select a new password. Please chose a secure password you can remember.

Thank you!
TXT;
     $admincontact=$GLOBALS['SET']['support_email'];
     if ($mail->Send())
     {
      $info['title'].=" - E-Mail Sent";
      $info['inner_body']=<<<HTML
<h2>E-mail Sent</h2>
<p>We have sent an e-mail with password reset instructions. Please check your e-mail and review the e-mail we sent.</p>
HTML;
     }
     else
     {
       $info['title'].=" - E-Mail Could not be sent!";
       $info['inner_body']=<<<HTML
<h2>Unable to Send E-Mail</h2>
<p>{$mail->ErrorInfo} Please contact <a href="mailto:{$admincontact}?subject=Password reset error!">your administrator</a> for assistance!</p>
<p>Reference Number: {$sid}</p>
HTML;
     }
    }}
    else
    {
      if ($data['num']=$this->VerifySid($sid))
      {
	if (isset($data['newpass']) && $data['newpass'] == $data['newpass2'])
	{
	  $this->removeSid($sid);
	  $data['password']=crypt($data['newpass'],$GLOBALS['CFG']->salt);
	  if ($GLOBALS['ADDIN']['db-tables']['users']->updateData($data))
	  {
	    $info['title'].=" - Password Changed";
	    $info['inner_body']=<<<HTML
<h2>Eureka!</h2>
Your password as now been changed to the password you supplied. You can now login with your new password. If you continue to have problems logging in please contact <a href="mailto:{$admincontact}?subject=Login Help">your administrator</a>.
HTML;
	  }
          else
          {
            $info['title'].=" - Password Not Changed";
            $info['inner_body']=<<<HTML
<h2>Unhandled MySQL Error!</h2>
<p>Your password had <u>not</u> been changed due to a database error. Below are details we were able to gather about this error. Please report these to <a href="mailto:{$admincontact}?subject=Password reset error!">your administrator</a>.<br>
Supplied e-mail: {$name}<br>
Current user e-mail: {$user->email}<br>
Current user number: {$user->num}
HTML;
          }
         }
	 else
	 {
	   $info['title'].=" - Supply New Password";
	   $info['inner_body']=<<<HTML
<h2>Set a New Password</h2>
Please set your new password below:
<form method=post>
<label for="np1">Password</label>: <input type=password name="newpass" id="np1"><br>
<label for="np2">Re-type Password</label>: <input type=password name="newpass2" id="np2"><br>
<input type=submit name="send" value="Continue">
</form>
HTML;
	}
      }
    }
    $this->info=$info;
    return true;
  }

  private function generateSID($num,$time)
  {
    $sid=$time.'-'.md5($num);
    if (file_put_contents(RESETPATH.'/sids/'.$sid,$num))
    {
      return $sid;
    }
    else
    {
      return false;
    }
  }

  private function verifySID($sid)
  {
    if ($num=file_get_contents(RESETPATH.'/sids/'.$sid))
    {
      return $num;
    }
    else
    {
      return false;
    }
  }

  private function removeSID($sid)
  {
    return unlink(RESETPATH.'/sids/'.$sid);
  }
}
