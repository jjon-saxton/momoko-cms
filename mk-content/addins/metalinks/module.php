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
  
  $actions[]=array('href'=>"//".$cfg->baseuri."/?content=rss",'title'=>"Post Feeds: RSS");
  $actions[]=array('href'=>"//".$cfg->baseuri."/?content=atom",'title'=>"Post Feeds: ATOM");
  if (!$user->inGroup('nobody'))
  {
   $actions[]=array('href'=>'#sidebar','data-toggle'=>"modal",'title'=>'My Dashboard');
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
  if ($this->settings['display'] == 'box')
  {
   $userlinks=$this->listUserActions("<li>__LINK__</li>\n");
   if ($this->user->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<li><a href="{$protocol}//{$this->cfg->baseuri}/mk-login.php">Login</a></li>
HTML;
   }
   else
   {
    $userinfo=<<<HTML
<li>Welcome <strong>{$this->user->name}</strong>!</li>
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
   if ($this->user->inGroup('nobody'))
   {
    $userinfo=<<<HTML
<a href="{$this->cfg->sec_protcol}//{$this->cfg->baseuri}/mk-login.php">Login</a>
HTML;
   }
   else
   {
    $userinfo=<<<HTML
Welcome <strong>{$this->user->name}</strong>
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
