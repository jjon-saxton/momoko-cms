<?php

class SettingsForm implements MomokoObject
{
  private $user;
  private $info=array();
  
  public function __construct($path=null)
  {
    $usr=new MomokoUser($GLOBALS['USR']->name);
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
    $data=file_get_contents(USPATH.'/form.htm');
    $dates=file_get_contents(USPATH.'/commondates.json');
    $dates=json_decode($dates,true);
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