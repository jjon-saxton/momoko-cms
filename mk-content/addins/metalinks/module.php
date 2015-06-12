<?php
class MomokoMetalinksModule extends MomokoModule implements MomokoModuleInterface
{
 public $info;
 public $opt_keys=array();
 private $usr;
 private $actions=array();
 private $settings=array();

 public function __construct()
 {
  $this->info=$this->getInfoFromDB();
  $this->usr=$GLOBALS['USR'];
  $this->opt_keys=array('display'=>array('type'=>'select','options'=>array('line','box')));
  parse_str($this->info->settings,$this->settings);
  
  /*if ($GLOBALS['SET']['rewrite'])
  {
   $feeds="feeds.xml";
  }
  else
  {
   $feeds="?content=feeds";
  }
  $actions[]=array('href'=>GLOBAL_PROTOCOL."//".$GLOBALS['SET']['baseuri']."/".$feeds,'title'=>"RSS Feeds"); TODO reactivate this for 2.0*/
  if (!$GLOBALS['USR']->inGroup('nobody'))
  {
   $actions[]=array('href'=>'javascript:void();','onclick'=>"toggleSidebar();",'title'=>'My Dashboard');
  }
  $this->actions=$actions;
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
  $protocol=SECURE_PROTOCOL;
  if ($this->settings['display'] == 'box')
  {
   $userlinks=$this->listUserActions("<li>__LINK__</li>\n");
   if ($GLOBALS['USR']->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<li><a href="{$protocol}//{$GLOBALS['SET']['baseuri']}/mk-login.php">Login</a></li>
HTML;
   }
   else
   {
    $userinfo=<<<HTML
<li>Welcome <strong>{$this->usr->name}</strong>!</li>
HTML;
   }
   return <<<HTML
<h4 class="module">Meta</h4>
<div id="UCPBox" class="ucp box">
<ul class="nobullet noindent">
{$userinfo}
{$userlinks}
</ul>
</div>
HTML;
  }
  else
  {
   $userlink=$this->listUserActions(" | __LINK__");
   if ($GLOBALS['USR']->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<a href="{$protocol}//{$GLOBALS['SET']['baseuri']}/mk-login.php">Login</a>";
HTML;
   }
   else
   {
    $userinfo=<<<HTML
Welcome <strong>{$this->usr->name}</strong>
HTML;
   }
   return <<<HTML
<span id="UCPLine" class="ucp">{$userinfo}{$userlink}</span>
HTML;
  }
 }

 private function listUserActions($wrapper="__LINK__")
 {
  $html=null;
  foreach ($this->actions as $action)
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
