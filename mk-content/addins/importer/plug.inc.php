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
   case 'import':
   //TODO read/parse and move the data
   if (import_data($_SESSION['importer']['data']))
   {
    unset($_SESSION['importer']);
    $info['inner_body']=<<<HTML
<h2>Content Imported</h2>
<p>The data you selected has been added to MomoKO 2.1's database. You can now edit and view the content using the dashboard sidebar. If content seems to be missing, check the logs for any errors.</p>
<div align="center"><button onclick="window.location='//{$GLOBALS['SET']['baseuri']}'">Return to Site</button></div>
HTML;
   }
   break;
   case 'upload':
   if ($a=ready_data($_FILES['data']))
   {
    $_SESSION['importer']['data']=$a['name'];
    unset($a['name']);
    $checks="<input type=\"hidden\" name=\"type\" value=\"{$_POST['type']}\">\n";
    foreach ($a as $name=>$val)
    {
        $checks.="<input type=\"checkbox\" id=\"{$name}\" name=\"{$name}\" value=\"{$val}\"><label for=\"{$name}\"> ".ucwords($name)."</label>\n";
    }
    $info['inner_body']=<<<HTML
<h2>Choose What to Import</h2>
<p>We have gone through the data you uploaded and found that we can import the following. Please check the box next to <strong>all</strong> the data you wish to import.
<form role="form" action="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=switchboard&plug=importer&action=import" method="post">
{$checks}<div align=center><button type="submit" class="btn btn-primary" name="status" value="importing">Import Selected Data</button></div> 
</form>
HTML;
   }
   break;
   case 'form':
   default:
   $info['inner_body']=<<<HTML
<h2>Content Importer</h2>
<p>The purpose of this switchboard plug is to provide a means through which you can import content into MomoKO 2.x from another platform including MomoKO 1.x. To begin you will need to select the type of data you have (i.e. MomoKO 1.x data, XML data from Wordpress or another platform). Once we know this you can then upload the data. Finally you will decide what you would like to import.</p>
<form role="form"a ction="//{$GLOBALS['SET']['baseuri']}/mk-dash.php?section=switchboard&plug=importer&action=upload" enctype="multipart/form-data" method="post">
<label for="select">To begin select the type of data below:</label>
<select id="select" class="form-control" name="type">
<option value="mkr">MomoKO v2.2+</option>
<option value="mk1">MomoKO v1.0-1.6</option>
<option value="wxr">WordPress XML</option>
<option value="rss">Generic RSS</option>
<option value="sql">Generic SQL</option>
</select>
<br>
<label for="file">Now choose the the file that contains this data, it could be an XML file or a zip archive. If you are moving from MomoKO v1.0-1.5, this will be a zip file you created when following the instructions on our wiki. Choose the file:</label>
<input id="file" class="form-control" name="data" type="file">
<p>Now hit the button and wait for the upload:<br>
<button class="btn btn-primary" type="submit" name="status" value="sending">Upload Data</button></p>
</form>
HTML;
  }

  return $info;
 }
}
?>
