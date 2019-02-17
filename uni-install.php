<?php
/* Fetcher and installer for Unified Store API */
require_once dirname(__FILE__)."/mk-core/common.inc.php";

define ("UNI_ERROR_NO_ARCHIVE",6011);
define ("UNI_ERROR_INVALID_PKG",6012);
define ("UNI_ERROR_PKG_MISMATCH",6020);
define ("UNI_ERROR_NO_VERSION",6021);
define ("UNI_ERROR_CANNOT_MOVE",6022);

class UniItem
{
 public $repo;
 public $errorMsg;
 private $info=array();

 public function __construct($repo,$id=null,$name=null)
 {
  $this->repo=$repo;
  if (empty($id) && empty($name))
  {
   trigger_error("Either a name or an id is required to set an UniItem!",E_USER_ERROR);
  }

  switch ($repo)
  {
   case 'addins':
   $raw=file_get_contents("http://cem:1slmlpFS!@dev.tower21studios.com/store.momoko/mk-uni.php?repo=addins&q=id:`= {$id}` short_name:`{$name}`");
   break;
   case 'channels':
   $raw=file_get_contents("http://cem:1slmlpFS!@dev.tower21studios.com/store.momoko/mk-uni.php?repo=channels&q=id:`= {$id}` name:`{$name}`");
   break;
   case 'core':
   $raw=file_get_contents("http://cem:1slmlpFS!@dev.tower21studios.com/store.momoko/mk-uni.php?repo=core&q=version:`{$name}`");
   break;
  }
  $temp=json_decode($raw,true);

  return $this->info=$temp;
 }

 public function __get($key)
 {
  return $this->info[$key];
 }

 public function fetch($storage=null)
 {
  $ftp_server="momokocms.org";
  $ftp_user="api@momokocms.org";
  $ftp_pass="tRy{LfAGdIuT";
  $conn=ftp_connect($ftp_server);
  $login=ftp_login($conn,$ftp_user,$ftp_pass);

  if (empty($storage))
  {
   $storage=dirname(__FILE__)."/mk-content/";
  }

  if (ftp_get($conn,$storage.$this->pkg_name,"/{$this->repo}/".$this->pkg_name,FTP_BINARY))
  {
   return $storage.$this->pkg_name;
  }
  else
  {
   return false;
  }
 }

 public function install($pkg)
 {
  if (class_exists("finfo"))
  {
   $pkg_info=new finfo(FILEINFO_MIME);
   $mime=$pkg_info->file($pkg);
   list($mime,$charset)=explode(";",$mime); //charset added to file when downloaded via FTP which could cause problems with the check below. Remove it!
   if ($mime == "application/zip")
   {
    $arch=new ZipArchive;
    $status=$arch->open($pkg);
    if ($status === TRUE)
    {
     $storage=dirname($pkg);
     $name=basename($pkg,".zip");
     $arch->extractTo($storage);
     unlink($pkg);
     if (file_exists($storage.'/'.$name."/version.nfo.txt"))
     {
      $version=file_get_contents($storage.'/'.$name."/version.nfo.txt");
      if (trim($version) == $this->long_version)
      {
       $cfg=new MomokoSiteConfig();
       $new=$cfg->basedir;
       $backup=rtrim($cfg->basedir,"/")."-bak/";
       if(rename($cfg->basedir,$backup))
       {
        mkdir($new,0755);
        if(rename($backup."mk-content/".$name,$new))
        {
          return true; //TODO further testing during beta
        }
        else
        {
          trigger_error("Cannot complete installation! New package files could not be moved into place. Check permissions.",E_USER_NOTICE);
          return 6022;
        }
       }
       else
       {
         trigger_error("Cannot complete installation! A backup of your current install could not be created. It is not recommended to complete installation automatically without a backup!",E_USER_NOTICE);
         return 6022;
       }
      }
      else
      {
       trigger_error("Package mismatch! The version of MomoKO provided by the new package is {$version} expecting {$this->long_version}.",E_USER_NOTICE);
       rmdirr($storage.'/'.$name);
       return 6020;
      }
     }
     else
     {
      trigger_error("Unexpected package format! 'version.nfo.txt' does not exist in the new package's root directory!");

      rmdirr($storage.'/'.$name);
      return 6021;
     }
     return true;
    }
    else
    {
     trigger_error("Could not open {$pkg} as a ZIP archive: {$status}",E_USER_NOTICE);
     return 6011;
    }
   }
   else
   {
    trigger_error("{$pkg} is not a valid ZIP archive",E_USER_NOTICE);
    return 6012;
   }
  }
 }
}

switch ($_GET['method'])
{
 //TODO need addon methods!
 case 'sys-upgrade':
 if (isset($_GET['install']))
 {
  $page['title']="Upgrading System...";
  $info=new UniItem('core',null,$_GET['target']);
  if ($pkg=$info->fetch())
  {
   if ($_GET['install'])
   {
    $status=$info->install($pkg);
    if ($status === TRUE)
    {
     $page['body']="Update package downloaded and installed. You will need to perform a database update before your site is ready again. A backup of your old installation has been created, if you notice problems, please delete the current installation and rename the backup. You will need to delete the backup manually if you need to save space!";
     $buttons[0]['href']="./mk-update.php";
     $buttons[0]['type']="success";
     $buttons[0]['self']=false;
     $buttons[0]['title']="Finish Upgrading";
    }
    else
    {
     $page['body']="Update package downloaded, but <strong>not</strong> installed!";
     switch ($status)
     {
      case UNI_ERROR_PKG_MISMATCH:
      case UNI_ERROR_NO_VERSION:
      $page['body'].="The update package was opened, but there was a problem with the version of MomoKO it provides. Either no version information was not found (malformed package?) or the version provided was not what was expected. The update package was removed, so you may try again. If the problem continues please <a href=\"http://store.momokocms.org/report?repo=core&version={$_GET['target']}\">report it</a> to the MomoKO Store staff.";
      case UNI_ERROR_NO_ARCHIVE:
      $page['body'].=" The update package could not be opened as an archive.";
      break;
      case UNI_ERROR_INVALID_PKG:
      $page['body'].=" We fetched a package that is not valid. This may be problem with the <a href=\"http://store.momokocms.org\">Addin Store</a>.";
      break;
         case UNI_ERROR_CANNOT_MOVE:
             $page['body'].="The package was downloaded and extracted but could not be moved! Please check permissions. Please note some servers may not allow permissions to be set correctly. In cases where you cannot set permissions to allow MomoKO to overwrite her own files, you will have to complete the installation manually. The files reside in MomoKO's 'mk-content' folder.";
             break;
      default:
      $page['body'].=" An unknown error occured.";
     }
     $buttons[0]['href']="./mk-dash.php?section=site&list=logs";
     $buttons[0]['type']="warning";
     $buttons[0]['self']=false;
     $buttons[0]['title']="Check the logs";
     $buttons[1]['action']="close";
     $buttons[1]['type']="danger";
     $buttons[1]['title']="Close";
    }
   }
   else
   {
    $page['body']="Update package downloaded and ready. The package will be detected next time you attempt to update.";
    $buttons[0]['action']="close";
    $buttons[0]['type']="primary";
   }
  }
  else
  {
   $page['body']="<span class=\"alert alert-danager\">Your update package could not be downloaded!</span>";
  }
 }
 else
 {
  $page['title']="Perform System Upgrade?";
  $page['body']="You are about to upgrade MomoKO to {$_GET['target']}. During the upgrade, your site will <strong>not</strong> be available.";
  $buttons[0]['action']="cancel";
  $files=glob(dirname(__FILE__)."/mk-content/momoko-cms-*.zip");
  if (count($files) > 0)
  {
    $page['body'].="You have a package ready for install already. You can install it whenever you're ready.";
    $buttons[1]['href']="./uni-install.php?method={$_GET['method']}&target={$_GET['target']}&modal={$_GET['modal']}&install=1";
    $buttons[1]['type']="primary";
    $buttons[1]['title']="Install Now!";
  } 
  else
  {
    $page['body'].="You may choose to upgrade your system as soon as you download the update package or hold off until you have notified your users of a short site outage. How do you wish to proceed?";
    $buttons[1]['href']="./uni-install.php?method={$_GET['method']}&target={$_GET['target']}&modal={$_GET['modal']}&install=0";
    $buttons[1]['type']="info";
    $buttons[1]['title']="Download Update Only";
    $buttons[2]['href']="./uni-install.php?method={$_GET['method']}&target={$_GET['target']}&modal={$_GET['modal']}&install=1";
    $buttons[2]['type']="primary";
    $buttons[2]['title']="Download and Install Now!";
  }
 }
 break;
}

if ($_GET['modal'])
{
 $html=<<<HTML
<script language="javascript">
 $("#modal a[data-target='#self']").click(function(e){
        e.preventDefault();
        var loc=$(this).attr("href");
        $("div.modal.in .modal-content").load(loc);
    });
</script>
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal">&times;</button>
<h4 class="modal-title">{$page['title']}</h4>
</div>
<div class="modal-body">{$page['body']}</div>
HTML;

 if (is_array($buttons))
 {
  $html.="<div class=\"modal-footer\">\n";
  foreach ($buttons as $item)
  {
   if ($item['action'] == 'cancel')
   {
    $html.="<button type=\"button\" class=\"btn btn-danger\" data-dismiss=\"modal\">Cancel</button>";
   }
   elseif ($item['action'] == 'close')
   {
    $html.="<button type=\"button\" class=\"btn btn-{$item['type']}\" data-dismiss=\"modal\">Close</button>";
   }
   else
   {
    if ($item['self'] !== FALSE)
    {
     $target=" data-target=\"#self\"";
    }
    else
    {
     $target=null;
    }
    $html.="<a href=\"{$item['href']}\" class=\"btn btn-{$item['type']}\"$target>{$item['title']}</a>";
   }
  }
  $html.="</div>\n";
 }
}
else
{
 $html=""; //TODO Full page HTML
}

echo $html;
