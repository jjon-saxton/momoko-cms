<?php
class MomokoMetalinksModule implements MomokoModuleInterface
{
 public $info;
 public $opt_keys=array();
 private $usr;
 private $settings=array();

 public function __construct()
 {
  $this->info=$this->getInfoFromDB();
  $this->usr=$GLOBALS['USR'];
  $this->opt_keys=array('options'=>array('line','box'));
  parse_str($this->info->settings,$this->settings);
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->opt_keys))
  {
   return $this->opt_key[$key];
  }
 }

 public function getModule($format='html')
 {
  if ($this->usr->inGroup('nobody'))
  {
   if ($GLOBALS['SET']['use_ssl'] == TRUE)
   {
    $protocol='https';
   }
   else
   {
    $protocol='http';
   }
   if ($this->settings['display'] == 'box')
   {
    return <<<HTML
<div id="LoginBox" class="ucp box">
<ul class="noindent nobullet">
<li><a href="{$protocol}://{$GLOBALS['SET']['baseuri']}/mk-login.php">Login</a></li>
<li><a href="{$protocol}://{$GLOBALS['SET']['baseuri']}/mk-login.php?action=new">New Account?</a></li>
</ul>
</form>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<span id="LoginLine" class="ucp"><a href="{$protocol}://{$GLOBALS['SET']['baseuri']}/mk-login.php">Login</a> | <a href="{$protocol}://{$GLOBALS['SET']['baseuri']}/mk-login.php?action=new">New Account?</a></span>
HTML;
  }
 }
 else
 {
  if ($this->settings['display'] == 'box')
  {
   $userlinks=$this->listUserActions("<li>__LINK__</li>\n");
   return <<<HTML
<div id="UCPBox" class="ucp box">
<ul class="nobullet noindent">
<li>Welcome <strong>{$this->usr->name}</strong>!</li>
{$userlinks}
</ul>
</div>
HTML;
  }
  else
  {
   $userlink=$this->listUserActions(" | __LINK__");
   return <<<HTML
<span id="UCPLine" class="ucp">Welcome <strong>{$this->usr->name}</strong>{$userlink}</span>
HTML;
  }
 }
 }

 private function listUserActions($wrapper="__LINK__")
 {
  $actions[]=array('href'=>'javascript:void();','onclick'=>"toggleSidebar();",'title'=>'My Dashboard');
  $actions[]=array('href'=>'?action=logout','title'=>'Logout');
  $html=null;

  foreach ($actions as $action)
  {
   if (@$action['onclick'])
   {
    $props=" onclick=\"{$action['onclick']}\"";
   }
   $html.=preg_replace("/__LINK__/","<a href=\"{$action['href']}\"{$props} title=\"".$action['title']."\">".$action['title']."</a>",$wrapper);
  }

  return $html;
 }
		
	public function getInfoFromDB()
	{
	 $table=new DataBaseTable("addins");
	 $query=$table->getData("dir:'".basename(dirname(__FILE__))."'",null,null,1);
	 return $query->fetch(PDO::FETCH_OBJ);
	}
}