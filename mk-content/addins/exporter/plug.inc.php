<?php
class MomokoSwitchboard implements MomokoObject
{
 private $info=array();

 public function __construct()
 {
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
  $conf=new MomokoSiteConfig();
  
  $info['title']="Switchboard: ".ucwords($_GET['plug']);
  switch ($_GET['action'])
  {
   case 'download':
   if ($_POST['type'])
   {
     require_once $conf->basedir.$conf->filedir."addins/exporter/includes/".$_POST['type'].".inc.php";
     if ($archive=ready_data($_POST['name']))
     {
       $info['inner_body']=<<<HTML
<h2>Content Exported</h2>
<p>Your content has been exported in the selected format and is available for download. If you selected MomoKO Archive as your data type, please note that the file can only be imported by MomoKO 2.2 and above.</p>
<a href="{$conf->siteroot}{$conf->filedir}{$archive}" class="btn btn-default">Download Data</a>
HTML;
     }
   }
   break;
   case 'form':
   default:
   $ctime=time();
   $info['inner_body']=<<<HTML
<h2>Content Exporter</h2>
<p>The purpose of this switchboard plug is to provide a means through which you can export content from MomoKO 2.x  as a backup or to move to another instance or platform</p>
<form role="form" action="//{$conf->baseuri}/mk-dash.php?section=switchboard&plug=exporter&action=download" method="post">
<label for="select">To begin select the type of data below:</label>
<select id="select" class="form-control" name="type">
<option value="mkr">MomoKO Archive</option>
<option value="sql">Generic SQL</option>
</select>
<label for="name">Archive Name:</label>
<input type="text" id="name" name="name" class="form-control" placeholder="mk-export-{$ctime}">
<p>Now hit the button and wait for the download:<br>
<button class="btn btn-primary" type="submit" name="status" value="getting">Prepare Data</button></p>
</form>
HTML;
  }

  return $info;
 }
}
?>
