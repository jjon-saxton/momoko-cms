<?php
class MomokoMetalinksModule extends MomokoModule implements MomokoModuleInterface
{
 protected $settings=array();
 private $actions=array();

 public function __construct(MomokoSession $user,$extset=null)
 {
  $cfg=new MomokoSiteConfig();
  $this->info=$this->getInfoFromDB();
  $this->opt_keys=array('display'=>array('type'=>'select','options'=>array('line','box')));
  if (empty($extset))
  {
    parse_str($this->info->settings,$this->settings);
  }
  else
  {
    parse_str($extset,$this->settings);
  }
  
  if ($user->inGroup('editor') || $user->inGroup('admin'))
  {
    if (empty($_GET['p']))
    {
      $base=$cfg->siteroot."/?";
    }
    else
    {
      $base=$cfg->siteroot."/?p={$_GET['p']}&";
    }
    $actions[]=array('href'=>$base."action=new",'title'=>"New...");
    if (basename($_SERVER['PHP_SELF']) != 'mk-dash.php' && empty($_GET['action']))
    {
      $actions[]=array('href'=>$base."action=edit",'title'=>"Edit This");
      $actions[]=array('href'=>$base."action=delete",'title'=>"Delete This");
    }
  }
  
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
  $protocol=$this->cfg->sec_protcol;
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
<li>Welcome <a href="#MLList" class="dropdown-toggle" data-toggle="dropdown"><strong>{$this->user->name}<span class="caret"></span></strong></a></li>
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
<li><a href="{$this->cfg->siteroot}/?content=rss">Post Feed: RSS</a></li>
<li><a href="{$this->cfg->siteroot}/?content=atom">Post Feed: ATOM</a></li>
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
{$userinfo} | <a href="//{$this->cfg->baseuri}/?content=rss">Post Feed: RSS</a> | <a href="//{$this->cfg->baseuri}/?content=atom">Post Feed: ATOM</a></span>
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
  if (is_array($this->actions))
  {
    foreach ($this->actions as $action)
    {
      $attr_str=null;
      foreach ($action as $attr=>$val)
      {
        $attr_str.=$attr." =\"{$val}\" ";
      }
      $html.=preg_replace("/__LINK__/","<a {$attr_str}>".$action['title']."</a>",$wrapper);
    }
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
