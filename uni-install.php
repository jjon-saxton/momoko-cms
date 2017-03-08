<?php
/* Fetcher and installer for Unified Store API */

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
   var_dump($pkg);
   if ($_GET['install'])
   {
    if ($info->install($pkg))
    {
     $page['body']="Update package downloaded and installed. You will need to perform a database update before your site is ready again.";
     $buttons[0]['href']="./mk-update/";
     $buttons[0]['type']="success";
     $buttons[0]['self']=false;
     $buttons[0]['title']="Finish Upgrading";
    }
    else
    {
     //TODO error trying to install update!
    }
   }
   else
   {
    //TODO set update reminder.
    $page['body']="Update package downloaded and ready. A message will appear the next time you log in reminding you to install this package.";
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
  $page['body']="You are about to upgrade MomoKO to {$_GET['target']}. During the upgrade, your site will <strong>not</strong> be available. You may choose to upgrade your system as soon as you download the update package or hold off until you have notified your users of a short site outage. How do you wish to proceed?";
  $buttons[0]['action']="cancel";
  $buttons[1]['href']="./uni-install.php?method={$_GET['method']}&target={$_GET['target']}&modal={$_GET['modal']}&install=0";
  $buttons[1]['type']="info";
  $buttons[1]['title']="Download Update Only";
  $buttons[2]['href']="./uni-install.php?method={$_GET['method']}&target={$_GET['target']}&modal={$_GET['modal']}&install=1";
  $buttons[2]['type']="primary";
  $buttons[2]['title']="Download and Install Now!";
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
    if ($self !== false)
    {
     $target=" data-target=\"#self\"";
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
