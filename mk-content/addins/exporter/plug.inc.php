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
  if (!empty($_POST['type']))
  {
    require_once $GLOBALS['SET']['basedir']."/mk-content/addins/".$_GET['plug']."/includes/".$_POST['type'].".inc.php";
  }

  $info['title']="Switchboard: ".ucwords($_GET['plug']);
  switch ($_GET['action'])
  {
   case 'export':
   if (export_data($_SESSION['exporter']['data']))
   {
    unset($_SESSION['importer']);
    $info['inner_body']=<<<HTML
<h2>Content Exported</h2>
<p>Your data has been exported.</p>
<div align="center"><button onclick="window.location='//{$GLOBALS['SET']['baseuri']}'">Return to Site</button></div>
HTML;
   }
   break;
   case 'form':
   default:
   $info['inner_body']=<<<HTML
<h2>Content Exporter</h2>
<p>The purpose of this switchboard plug is to provide a means through which you can export content from MomoKO 2.x  as a backup or to move to another instance or platform</p>
<form role="form"a ction="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=switchboard&plug=importer&action=upload" enctype="multipart/form-data" method="post">
<label for="select">To begin select the type of data below:</label>
<select id="select" class="form-control" name="type">
<option value="mk1">MomoKO v1.0-1.6</option>
<option value="wp">WordPress XML</option>
<option value="xml">Generic XML</option>
<option value="sql">Generic SQL</option>
<option value="cdd">Comma-delinated Document</option>
</select>
<p>Now hit the button and wait for the download:<br>
<button class="btn btn-primary" type="submit" name="status" value="getting">Download Data</button></p>
</form>
HTML;
  }

  return $info;
 }
}
?>
