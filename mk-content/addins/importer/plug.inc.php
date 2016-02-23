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
  $info['title']="Switchboard: ".ucwords($_GET['plug']);
  switch ($_GET['action'])
  {
   case 'import':
   //TODO read/parse and move the data
   $info['inner_body']=<<<HTML
<h2>Content Imported</h2>
<p>The data you selected has been added to MomoKO 2.1's database. You can now edit and view the content using the dashboard sidebar. If content seems to be missing, check the logs for any errors.</p>
<div align="center"><button onclick="window.location='//{$GLOBALS['SET']['baseuri']}'">Return to Site</button></div>
HTML;
   break;
   case 'upload':
   //TODO figure out what to do with the data once it is uploaded
   $info['inner_body']=<<<HTML
<h2>Choose What to Import</h2>
<p>We have gone through the data you uploaded and found that we can import the following. Please check the box next to <strong>all</strong> the data you wish to import.
<form action="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=switchboard&plug=importer&action=import" method="post">
<!-- TODO create checkboxes -->
<div align=center><button type="submit" name="status" value="importing">Import Selected Data</button></div> 
</form>
HTML;
   break;
   case 'form':
   default:
   $info['inner_body']=<<<HTML
<h2>Content Importer</h2>
<p>The purpose of this switchboard plug is to provide a means through which you can import content into MomoKO 2.x from another platform including MomoKO 1.x. To begin you will need to select the type of data you have (i.e. MomoKO 1.x data, XML data from Wordpress or another platform). Once we know this you can then upload the data. Finally you will decide what you would like to update.</p>
<form action="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=switchboard&plug=importer&action=upload" enctype="multipart/form-data" method="post">
<p>To begin select the type of data below:<br>
<select name="type">
<option value="mk1">MomoKO v1.0-1.5</option>
<option value="wp">WordPress XML</option>
<option value="xml">Generic XML</option>
<option value="sql">Generic SQL</option>
<option value="cdd">Comma-delinated Document</option>
</select></p>
<p>Now choose the the file that contains this data, it could be an XML file or a zip archive. If you are moving from MomoKO v1.0-1.5, this will be a zip file you created when following the instructions on our wiki. Choose the file:<br>
<input name="data" type="file"></p>
<p>Now hit the button and wait for the upload:<br>
<button type="submit" name="status" value="sending">Upload Data</button></p>
</form>
HTML;
  }

  return $info;
 }
}
?>
