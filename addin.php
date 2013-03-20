<?php
require dirname(__FILE__)."/assets/core/common.inc.php";
require dirname(__FILE__)."/assets/core/content.inc.php";

class MomokoAddin implements MomokoObject
{
 public $path;
 public $isEnabled;
 private $table;
 private $info=array();

 public function __construct($path=null)
 {
  $this->table=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
  if (!empty($path))
  {
   $manifest=xmltoarray($path.'/manifest.xml'); //Load manifest
   $this->info=$this->parseManifest($manifest);
   
   $db=new DataBaseTable(DAL_TABLE_PRE."addins",DAL_DB_DEFAULT);
   $query=$db->getData("dir:'".basename($path)."'",array('enabled','num','dir'),null,1);
   $data=$query->first();
   if ($data->enabled == 'y')
   {
    $this->isEnabled=true;
   }
   else
   {
    $this->isEnabled=false;
   }
  }
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
  return $this->info;
 }
 
 public function put($data=null)
 {
  if (empty($data['archive']))
  {
    return new MomokoAddinForm('add');
  }
  else
  {
    $destination=ADDINDIR.'/'.$data['dir'];
    if (mkdir($destination))
    {
      $zip=new ZipArchive;
      $zip->open($data['archive']);
      $zip->extractTo($destination);
      unlink($data['archive']);
      if ($num=$this->table->putData($data))
      {
	$new=$this->table->getData("'num:= ".$num."'",null,1);
	$info=$new->toArray();
	if (is_array($info[0]))
	{
	  return $info[0];
	}
	else
	{
	  return $info;
	}
      }
    }
  }
 }
 
 public function update($data=null)
 {
  if (empty($data['archive']))
  {
    return new MomokoAddinForm('update');
  }
  else
  {
    //TODO open archive, update files in addindir, use manifest to update database
    return true;
  }
 }
 
 public function upload($file)
 {
  if (!empty($file) && $file['error'] == UPLOAD_ERR_OK)
  {
    $result=false;
    $filename=$GLOBALS['CFG']->tmpdir.'/'.$file['name'];
    if (move_uploaded_file($file['tmp_name'],$filename))
    {
      $info=$this->getArchiveInfo($filename);
      $result=true;
    }
    elseif ($file['error'] == UPLOAD_ERR_INI_SIZE)
    {
      $error="File was too large to upload! Check your upload_max_filesize directive in your php.ini file!";
    }
    else
    {
      $error="File did not upload or could not be moved!";
    }
    
    if ($result == TRUE)
    {
      $script_body=<<<TXT
$('span#msg',pDoc).html('File upload complete...continuing');
$('div#file',pDoc).replaceWith("<div id="file"><label for="file">File:</label> <input id=addin type=hidden name="file" value="{$filename}">{$filename}<input id="addin-dir" type="hidden" name="dir" value="{$info['dir']}"></div>");
$('input#addin-name',pDoc).value('{$info['shortname']}').removeAttr('disabled');
$('input#addin-title',pDoc).value('{$info['longname']}').removeAttr('disabled');
$('input#addin-description',pDoc).value('{$info['description']}').removeAttr('disabled');
TXT;
    }
    else
    {
      $script_body=<<<TXT
$('span#msg',pDoc).html('{$error}');
$('input#file',pDoc).removeAttr('disabled');
TXT;
    }
    
    print <<<HTML
<html>
<head>
<title>File Upload</title>
<script language=javascript src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<body>
<script language="javascript" type="text/javascript">
var pDoc=window.parent.document;

{$script_body}
</script>
<p>Processing complete. Check above for further debugging.</p>
</body>
</html>
HTML;
  }
 }
 
 public function drop()
 {
  $table=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
  $query=$table->getData("dir:'".basename($this->info['dirroot'])."'",array('num','dir'),null,1);
  $data=$query->first();
  
  $ddata['num']=$data->num;
  if ($table->removeData($ddata) && rmdirr($this->info['dirroot']))
  {
    return true;
  }
  else
  {
    trigger_error("Unable to remove addin!",E_USER_ERROR);
  }
 }
 
 private function getArchiveInfo($archive)
 {
  $destination=pathinfo($archive,PATHINFO_DIRNAME).pathinfo($archive,PATHINFO_FILENAME);
  if (mkdir($destination))
  {
    $zip=new ZipArchive;
    $zip->open($archive);
    $zip->extractTo($destination,'manifest.xml');
    $zip->close();
    if (file_exists($destination.'/manifest.xml'))
    {
      $manifest=xmltoarray($destination.'/manifest.xml');
      return $this->parseManifest($manifest);
    }
  }
 }
 
 public function setPathByID($id)
 {
  $query=$this->table->getData("num:'= ".$id."'",array('num','dir','enabled'),null,1);
  $data=$query->first();
  $path=$GLOBALS['CFG']->basedir."/assets/addins/".$data->dir."/";
  
  $manifest=xmltoarray($path.'/manifest.xml'); //Load manifest
  $this->info=$this->parseManifest($manifest);
   
  if ($data->enabled == 'y')
  {
   $this->isEnabled=true;
  }
  else
  {
   $this->isEnabled=false;
  }
  
  return $this->path=$path;
 }
 
 public function toggleEnabled()
 {
  $query=$this->table->getData("dir:'".basename($this->info['dirroot']['value'])."'",array('num','enabled'),null,1);
  $data=$query->first();
  
  $ndata['num']=$data->num;
  if ($data->enabled == 'y')
  {
    $ndata['enabled']='n';
  }
  else
  {
    $ndata['enabled']='y';
  }
  
  if ($update=$this->table->updateData($ndata))
  {
    return $ndata['enabled'];
  }
  else
  {
    trigger_error("Unable to toggle enabled/disabled state of addin ".basename($this->info['dirroot']['value'])."!",E_USER_WARNING);
  }
 }

 public function hasAuthority()
 {
  $priority=array_reverse($this->authority);
  foreach ($priority as $name=>$list)
  {
   foreach ($list as $item)
   {
    if ($item == 'ALL' || $GLOBALS['USR']->inGroup($item))
    {
     $auth=true;
    }
    elseif ($item == 'NONE')
    {
     $auth=false;
    }
   }

   if ($name == 'blacklist')
   {
    $auth=!$auth;
   }
  }

  return $auth;
 }

 private function parseManifest(array $manifest)
 {
  foreach ($manifest as $node)
  {
   if (!empty($node['@text']))
   {
    $array[$node['@name']]['value']=$node['@text'];
   }
   else
   {
    if (is_array($node['@children']) && !empty ($node['@children'][0]))
    {
     $array[$node['@name']]['value']=$this->parseManifest($node['@children']);
    }
    else
    {
     $array[$node['@name']]['value']=null;
    }
   }

   $array[$node['@name']]['attr']=$node['@attributes'];
  }

  if (array_key_exists('authority',$array))
  {
   $authority=$array['authority']['value'];
   unset($array['authority']);
   $lists=array();
   foreach ($authority as $name=>$list)
   {
    $lists[$name]=explode(",",$list['value']);
   }
   $array['authority']=$lists;
  }

  return $array;
 }
}

if (@$_GET['action'] == 'login' || @$_GET['action'] == 'logout' || @$_GET['action'] == 'register')
{
 header("Location: //".$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location."?action=".$_GET['action']);
}
elseif (@$_SERVER['PATH_INFO'])
{
 list(,$addindir,)=explode("/",$_SERVER['PATH_INFO']);
 if (empty($addindir))
 {
  $path=null;
 }
 else
 {
  $path=$GLOBALS['CFG']->basedir."/assets/addins/".$addindir."/";
 }
 $GLOBALS['LOADED_ADDIN']=new MomokoAddin($path);
 if (!empty($_GET['num']))
 {
  $GLOBALS['LOADED_ADDIN']->setPathByID($_GET['num']);
 }

 switch (@$_GET['action'])
 {
  case 'add':
  $child=$GLOBALS['LOADED_ADDIN']->put($_POST);
  if (!empty($_GET['ajax']) && $_GET['ajax'] == 1)
  {
    echo json_encode($child);
  }
  break;
  case 'update':
  $child=$GLOBALS['LOADED_ADDIN']->update($_POST);
  break;
  case 'enable':
  case 'disable':
  echo $newstate=$GLOBALS['LOADED_ADDIN']->toggleEnabled();
  break;
  case 'remove';
  $child=$GLOBALS['LOADED_ADDIN']->drop();
  break;
  case 'upload':
  $child=$GLOBALS['LOADED_ADDIN']->upload($_FILES['addin']);
  break;
  case 'list':
  if ($GLOBALS['USR']->inGroup('admin'))
  {
    $child=new MomokoAddinForm('list');
  }
  else
  {
    $child=MomokoError("Forbidden");
  }
  break;
  default:
  if ($GLOBALS['LOADED_ADDIN']->hasAuthority() && $GLOBALS['LOADED_ADDIN']->isEnabled) //User must have authority, and the addin must be enabled!
  {
   include $GLOBALS['CFG']->basedir."/assets/addins/".$addindir."/load.inc.php"; //hand control over to the addin
  }
  elseif (!$GLOBALS['LOADED_ADDIN']->isEnabled)
  {
    $child=new MomokoError('Disabled');
  }
  else
  {
   $child=new MomokoError('Forbidden');
  }
 }
 if (array_key_exists('ajax',$_GET) && $_GET['ajax'] == 1)
 {
  echo $child->inner_body;
 }
 else
 {
  $tpl=new MomokoTemplate('/'); //forces load of default template to show error message
  echo $tpl->toHTML($child);
 }
}
