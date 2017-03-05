<?php
/* Fetcher and installer for Unified Store API */

class UniItem
{
 private $info=array();

 public function __construct($repo,$id=null,$name=null)
 {
  if (empty($id) && empty($name))
  {
   trigger_error("Either a name or an id is required to set an UniItem!",E_USER_ERROR);
  }
 }

 public function __get($key)
 {
  return $this->info[$key];
 }

 public function fetch($storage=null)
 {
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
  $info=new UniInfo('core',$_GET['target']);
  if ($pkg=$info->fetch())
  {
   if ($_GET['install'])
   {
    if ($info->install($pkg))
    {
     //TODO tell users they are about to run a database update and redirect to mk-update.php
    }
    else
    {
     //TODO error trying to install update!
    }
   }
   else
   {
    //TODO set update reminder and let users know the package is ready for them.
   }
  }
  else
  {
   //TODO error downloading package!
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
   else
   {
    $html.="<a href=\"{$item['href']}\" class=\"btn btn-{$item['type']}\" data-target=\"#modal\">{$item['title']}</a>";
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
