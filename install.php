<?php
require dirname(__FILE__)."/assets/php/common.inc.php";
require dirname(__FILE__)."/assets/php/content.inc.php";

define("INCLI",true); //fake a CLI environment to prevent session creation

class MomokoInstall implements MomokoLITEObject
{
  private $info;
  private $settings;
  
  public function __construct($stage=null)
  {
    if ($stage < 1)
    {
      $stage=1;
    }
    $this->info['stage']=$stage;
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
    return $this->info[$key]=$value;
  }
  
  public function get()
  {
    $info['title']="MomoKO Installation - Step ".$this->stage;
    switch ($this->stage)
    {
      case 1:
      $version=MOMOKOVERSION;
      
      $required_values=$this->showSettings('main','required');
      $optional_values=$this->showSettings('main','optional');
      $advanced_values=$this->showSettings('main','advanced');
      $dal_required=$this->showSettings('dal','required');
      $dal_optional=$this->showSettings('dal','optional');
      
      $info['inner_body']=<<<HTML
<h2>MomoKO Pre-Installation Checks</h2>
<p>This script is designed to prepare your database to run MomoKO. This is the final step in the install procedure. You must first configure your installation in /assets/etc/main.conf.txt and configure your database in /assets/etc/dal.conf.txt.</p>
<p>Below are your main configuration values:
<table width=100% border=0 cellspacing=1 cellpadding=1>
<tr>
<th colspan=2>Required Values</th>
</tr>
{$required_values}
<th colspan=2>Optional Values</th>
</tr>
{$optional_values}
<th colspan=2>Advanced Values</th>
</tr>
{$advanced_values}
</table>
<p>Finally here are your DAL configuration values:</p>
<table width=100% cellspacing=1 cellpadding=1>
<tr>
<tr>
<th colspan=2>Required Values</th>
</tr>
{$dal_required}
<tr>
<th colspan=2>Optional Values</th>
</tr>
{$dal_optional}
</tr>
</table>
<p>This script will fail if the files mentioned above are not available for read or have not been edited. Please see the README for more details. Have you configured MomoKO v$version as described in the README?</p>
<table width=100% border=0 cellpadding=2 cellspacing=0>
<tr>
<td align=center width=50%><button onclick="window.location='?step=2'">Yes</button></td><td align=center width="50%"><button onclick="window.reload()">No</button></td>
</tr>
</table>
HTML;
      break;
      case 2:
      require dirname(__FILE__).'/assets/php/install.inc.php';
      if (create_tables())
      {
       $sdf='m/d/Y';
       $ldf='I F j, Y';
       $dpreview['sdf']=date($sdf);
       $dpreview['ldf']=date($ldf);
       
       $info['inner_body']=<<<HTML
<h2>Fill In Database Information</h2>
<p>Your database has been prepared! Please fill out the form below to add default data to your database.</p>
<form action="?step=3" method=post>
<table width=100% border=0 cellspacing=1 cellpadding=1>
<tr>
<th colspan=2>Create an Administrator</th>
</tr>
<tr>
<td align=right><label for="name">User Name:</label> </td><td align=left><input type=text name="admin[name]" id="name"></td>
</tr>
<tr>
<td align=right><label for="pass1">Password:</label> </td><td align=left><input type=password name="pass1" id="pass1"></td>
</tr>
<td align=right><label for="pass2">Re-Type Password:</label> </td><td align=left><input type=password name="admin[password]" id="pass2"></td>
</tr>
<tr>
<td align=right><label for="email">E-Mail:</label> </td><td align=left><input type=email name="admin[email]" id="email"></td>
</tr>
<tr>
<th colspan=2>Set User Default Settings</th>
</tr>
<tr>
<td align=right><label for="sdf">Short Date Format:</label> </td><td align=left><input type=text name="defaults[sdf]" id="sdf" value="{$sdf}"> (Preview: <span id="spfpreview">{$dpreview['sdf']}</span>) <a href="">help</a></td>
</tr>
<tr>
<td align=right><label for="ldf">Long Date Format:</label> </td><td align=left><input type=text name="defaults[ldf]" id="ldf" value="{$ldf}"> (Preview: <span id="lpfpreview">{$dpreview['ldf']}</span>) <a href="">help</a></td>
</tr>
<tr>
<td align=right><label for="rpt">Number of rows in a table on any given page:</label> </td><td alig=left><input type=number size=3 name="defaults[rpt]" id="rpt" value=20></td>
</tr>
<tr>
<td colspan=2 align=center>
<input type=submit name="send" value="Set">
</tr>
</tr>
</table>
</form>
HTML;
      }
      else
      {
	$page=new MomokoLITEError('Server_Error');
	$info['title']=$page->title;
	$info['inner_body']=$page->inner_body;
      }
      break;
      case 3:
      require dirname(__FILE__).'/assets/php/install.inc.php';
      if (fill_tables($_POST['admin'],$_POST['defaults']))
      {
	$siteroot=$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location;
	$info['inner_body']=<<<HTML
<h2>Installation Finished!</h2>
<p>Congratulations! Your system is now set up and ready. You may now <a href="//{$siteroot}?action=login">login</a> with the administerator you created and begin editting your site!</p>
HTML;
      }
      else
      {
	$page=new MomokoLITEError('Server_Error');
	$info['title']=$page->title;
	$info['inner_body']=$page->inner_body;
      }
    }
    return $info;
  }
  
  private function showSettings($file,$type)
  {
    $this->settings['main']['required']=array('sitename','basedir','pagedir','datadir','tempdir');
    $this->settings['main']['optional']=array('domain','location','default_template');
    $this->settings['main']['advanced']=array('session','salt','rewrite');
    $this->settings['dal']['required']=array('type','default');
    $this->settings['dal']['optional']=array('host','file','table_pre','user','password');
    
    $html=null;
    if ($file == 'main')
    {
      $obj=$GLOBALS['CFG'];
    }
    else
    {
      $obj=new DALConfig();
    }
    
    foreach($this->settings[$file][$type] as $name)
    {
      $value=$obj->$name;
      $html.="<tr>\n<td align=right>{$name}: </td>";
      if (!empty($value))
      {
	if ($name == 'domain')
	{
	  $value.=" (May have been guessed)";
	}
	if ($name == 'password')
	{
	  $c=strlen($value);
	  $value=null;
	  for ($i=1; $i<$c; $i++)
	  {
	    $value.="*";
	  }
	}
	$html.="<td align=left>{$value}</td>";
      }
      elseif ($type == 'required')
      {
	$html.="<td align=left class=\"redline\">Empty or False!</td>";
      }
      else
      {
	$html.="<td align=left class=\"warning\">Empty or False!</td>";
      }
      $html.="</tr>";
    }
    
    return $html;
  }
}

$basedir=$GLOBALS['CFG']->basedir;
$pagedir=$GLOBALS['CFG']->pagedir;
if (empty($basedir) || empty($pagedir))
{
 echo <<<HTML
<html>
<head>
<title>MomoKO Not Configured!</title>
<style>
body {font: 12px Arial,sans; background: #ddddef; color: black}
a:link {color: navy}
a:visited {color: navy}
</style>
</head>
<body>
<h1>System not configured!</h1>
<p>MomoKO has not been configured according to the README.md file. The system must be configured before the database can be populated. Please open the README.md file for more information.
</body>
</html>
HTML;
}
else
{ 
 $child=new MomokoInstall(@$_GET['step']);
 $tpl=new MomokoLITETemplate('/'); //forces load of default template to show install page
 echo $tpl->toHTML($child);
}