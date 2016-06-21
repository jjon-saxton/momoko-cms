<?php
class MomokoMetalinksModule extends MomokoModule implements MomokoModuleInterface
{
 public $info;
 public $opt_keys=array();
 private $user;
 private $cfg;
 private $actions=array();
 private $settings=array();

 public function __construct(MomokoSession $user)
 {
  $cfg=new MomokoSiteConfig();
  $this->info=$this->getInfoFromDB();
  $this->opt_keys=array('display'=>array('type'=>'select','options'=>array('line','box')));
  parse_str($this->info->settings,$this->settings);
  
  if (!$user->inGroup('nobody'))
  {
   $actions[]=array('href'=>'#sidebar','data-toggle'=>"modal", "title"=>"Dashboard");
   $actions[]=array('href'=>$cfg->siteroot."/?action=logout","title"=>"Logout");
  }
  $this->user=$user;
  $this->cfg=$cfg;
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
  $userlinks=$this->listUserActions("<li>__LINK__</li>\n");
  if ($this->settings['display'] == 'box')
  {
   if ($this->user->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<li><a href="{$protocol}//{$this->cfg->baseuri}/mk-login.php">Login</a></li>
HTML;
   }
   else
   {
    $userinfo=<<<HTML
<li>Welcome <a href="#MLList" class="dropdown-toggle" data-toggle="dropdown"><strong>{$this->user->name}<span class="caret"></span></strong></a>!</li>
HTML;
   }
   return <<<HTML
<h4 class="module">Meta</h4>
<div id="MLBox" class="metalinks box">
<ul class="nobullet noindent">
<li>{$userinfo}</li>
<li id="MLList" class="dropdown"><ul class="dropdown-menu" role="menu">
{$userlinks}
</ul></li>
<li><a href="">Post Feed: RSS</a></li>
<li><a href="">Post Feed: ATOM</a></li>
</ul>
</div>
HTML;
  }
  else
  {
   if ($this->user->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<a href="{$this->cfg->sec_protcol}//{$this->cfg->baseuri}/mk-login.php">Login</a>
HTML;
   }
   else
   {
    $userinfo=<<<HTML
Welcome <a href="#MLine" class="dropdown-toggle" data-toggle="dropdown"><strong>{$this->user->name}<span class="caret"></strong></a>
HTML;
   }
   return <<<HTML
<div id="MLLine" class="dropdown metalinks">
{$userinfo} | <a href="//{$this->cfg->baseuri}/?content=rss">Post Feed: RSS</a> | <a href="//{$this->cfg->baseuri}/?content=rss">Post Feed: ATOM</a></span>
<ul role="menu" class="dropdown-menu">
{$userlinks}
</ul>
</div>
HTML;
  }
 }

 private function listUserActions($wrapper="__LINK__")
 {
  $html=null;
  foreach ($this->actions as $action)
  {
   $attr_str=null;
   foreach ($action as $attr=>$val)
   {
    $attr_str.=$attr." =\"{$val}\" ";
   }
   $html.=preg_replace("/__LINK__/","<a {$attr_str}>".$action['title']."</a>",$wrapper);
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
