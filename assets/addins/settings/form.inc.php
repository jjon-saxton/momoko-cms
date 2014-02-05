<?php

class SettingsForm implements MomokoObject
{
  private $user;
  private $site;
  private $info=array();
  
  public function __construct($path=null)
  {
    $usr=new MomokoUser($GLOBALS['USR']->name);
    if ($GLOBALS['USR']->inGroup('admin'))
    {
     $this->site=$GLOBALS['SET'];
    }
    $this->user=$usr->get();
    $this->info=$this->get();
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
    $data=file_get_contents(USSPATH.'/form.htm');
    $dates=file_get_contents(USSPATH.'/commondates.json');
    $mtas=file_get_contents(USSPATH.'/mtas.json');
    $dates=json_decode($dates,true);
    $mtas=json_decode($mtas,true);
    if (preg_match("/<title>(?P<title>.*?)<\/title>/smU",$data,$match) > 0) //Find page title in $data
    {
      if (@$match['title'] && ($match['title'] != "[Blank]" && $match['title'] != "Blank")) //accept titles other than Blank and [Blank]
      {
	$info['title']=$match['title'];
      }
    }
    if (preg_match("/<body>(?P<body>.*?)<\/body>/smU",$data,$match) > 0) // Find page body in $data
    {
      $vars['shortdateformat']=$this->user->shortdateformat;
      $vars['longdateformat']=$this->user->longdateformat;
      $vars['rowspertable']=$this->user->rowspertable;

      if (!empty($this->site['version']))
      {
       $vars['support_email']=$this->site['support_email'];
       if ($this->site['security_logging'] == 1)
       {
        $vars['security_logging_radio']="<input type=radio name=\"security_logging\" id=\"sl1\" checked=checked value=1><label for=\"sl1\"> Enabled</label> <input name=\"security_logging\" type=radio id=\"sl0\" value=0><label for=\"sl0\"> Disabled</label>";
       }
       else
       {
        $vars['security_logging_radio']="<input type=radio name=\"security_logging\" id=\"sl1\" value=1><label for=\"sl1\"> Enabled</label> <input type=radio checked=checked name=\"security_logging\" id=\"sl0\" value=0><label for=\"sl0\"> Disabled</label>";
       }
       if ($this->site['error_logging'] == 1)
       {
        $vars['error_logging_radio']="<input type=radio name=\"error_logging\" id=\"el1\" checked=checked value=1><label for=\"el1\"> Enabled</label> <input name=\"error_logging\" type=radio id=\"el0\" value=0><label for=\"el0\"> Disabled</label>";
       }
       else
       {
        $vars['error_logging_radio']="<input type=radio name=\"error_logging\" id=\"el1\" value=1><label for=\"el1\"> Enabled</label> <input type=radio checked=checked name=\"error_logging\" id=\"el0\" value=0><label for=\"el0\"> Disabled</label>";
       }

       $vars['mta_options']="";
       foreach ($mtas as $mta)
       {
        if ($this->site['email_protocol'] == $mta)
        {
         $vars['mta_options'].="<option selected=selected>{$mta}</option>";
        }
        else
        {
         $vars['mta_options'].="<option>{$mta}</option>";
        }
       }

       parse_str($this->site['email_server'],$email['server']);
       $vars['email_host']=$email['server']['host'];
       $vars['email_port']=$email['server']['port'];
       $vars['email_username']=$email['server']['username'];
       $vars['email_password']=$email['server']['password'];
       $vars['email_security_radio']="<input type=radio name=\"server[security]\" id=\"esec0\" value=\"\"><label for=\"esec0\"> None</label> <input type=radio name=\"server[security]\" id=\"esec1\" value=\"ssl\"><label for=\"esec1\"> SSL</label> <input type=radio name=\"server[security]\" id=\"esec2\" value=\"tls\"><label for=\"esec2\"> TLS</label>";
       parse_str($this->site['email_from'],$email['from']);
       $vars['email_from_address']=$email['from']['address'];
       $vars['email_from_name']=$email['from']['name'];
      }
      
      $vars['sdfopts']="<option value=\"\">Use Text Field:</option>";
      $vars['ldfopts']=$vars['sdfopts'];
      foreach ($dates['short'] as $date)
      {
	$vars['sdfopts'].="<option value=\"".$date."\">".date($date)."</option>";
      }
      foreach ($dates['long'] as $date)
      {
	$vars['ldfopts'].="<option value=\"".$date."\">".date($date)."</option>";
      }
      
      $varhandler=new MomokoVariableHandler($vars);
    
      $info['inner_body']=$varhandler->replace(trim($match['body'],"\n\r"));
    }
    
    
    return $info;
  }
}
