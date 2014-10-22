<?php
class MomokoSession
{
  public $name;
  private $user;
  private $groups=array();

  public function __construct()
  {
    $this->name='guest';
    $r=new MomokoUser($this->name);
    $this->user=$r->get();
    $this->groups=$this->updateGroups();
  }

  public function __get($var)
  {
    if (@$this->user->$var)
    {
      return $this->user->$var;
    }
  }

  public function login($name,$password)
  {
    $r=new MomokoUser($name);
    $user=$r->get();
    
    $log=new DataBaseTable('log');
    
    if (crypt($password,$user->password) == $user->password)
    {
      $this->name=$name;
      $this->user=$user;
      $this->groups=$this->updateGroups();
      
      $data['time']=date('Y-m-d H:i:s');
      $data['action']="logged in";
      $data['message']="Session started for user ".$name." at ".$_SERVER['REMOTE_ADDR'];
      $log->putData($data);
      return true;
    }
    else
    {
      $data['time']=date('Y-m-d H:i:s');
      $data['action']="login failed";
      $data['message']="Authentication failed for user ".$name;
      $log->putData($data);
      return false;
    }
  }

  public function updateSession()
  {
   $curname=$this->name;
   if ($this->loginAs($this->name))
   {
    $this->name=$curname;
    return true;
   }
   else
   {
    return false;
   }
  }

  public function loginAs($name)
  {
   if (defined('INCLI') || $this->inGroup('admin'))
   {
    $r=new MomokoUser($name);
    $user=$r->get();
    $this->name=$this->name." as ".$name;
    $this->user=$user;
    $this->groups=$this->updateGroups();
    
    if ($this->inGroup('admin'))
    {
      $log=new DataBaseTable('log');
      $data['time']=date('Y-m-d H:i:s');
      $data['action']="logged in";
      $data['message']="An administrator logged in as ".$name." (".$this->name.")";
      $log->putData($data);
    }
   
    return true;
   }
   else
   {
    return false;
   }
  }

  public function logout()
  {    
    $log=new DataBaseTable('log');
    $data['time']=date('Y-m-d H:i:s');
    $data['action']="logged out";
    $data['message']="Session ended for user ".$this->name;
    $log->putData($data);

    $this->name='guest';
    $r=new MomokoUser($this->name);
    $this->user=$r->get();
    $this->groups=$this->updateGroups();
    
    return true;
  }

  private function updateGroups()
  {
    return explode (",",$this->user->groups);
  }

  public function inGroup($group)
  {
    if (in_array($group,$this->groups))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
  
		public function canRead($object)
		{
			if ($this->inGroup('admin') || $this->inGroup('root'))
			{
				return true;
			}
			else
			{
				list($author,$group,$other)=explode(":",$object->permissions);
				if ((preg_match('/r/',$other) > 0))
				{
					return true;
				}
				if ((preg_match('/r/',$group) >0) && $this->inGroup($object->group))
				{
					return true;
				}
				if ((preg_match('/r/',$author) > 0) && $object->author == $this->name)
				{
					return true;
				}
			}
			return false;
		}
		
		public function canWrite($object)
		{
			if ($this->inGroup('admin') || $this->inGroup('root'))
			{
				return true;
			}
			else
			{
				list($author,$group,$other)=explode(":",$object->permissions);
				if ((preg_match('/w/',$other) > 0))
				{
					return true;
				}
				if ((preg_match('/w/',$group) >0) && $this->inGroup($object->group))
				{
					return true;
				}
				if ((preg_match('/w/',$author) > 0) && $object->author == $this->name)
				{
					return true;
				}
			}		
		}
}

class MomokoUser
{
  private $db;
  private $path;
  private $info=array();

  public function __construct($path=null)
  {
    $this->db=new DataBaseTable('users');
    $this->path=$path;
  }

  public function __get($var)
  {
    if (array_key_exists($var,$this->info))
    {
      return $this->info[$var];
    }
    else
    {
      return false;
    }
  }

  public function get()
  {
    $name=pathinfo($this->path,PATHINFO_BASENAME);
    $data=$this->db->getData("name:'".$name."'",array('num'),null,1);
    $row=$data->fetch(PDO::FETCH_OBJ);
    return $this->getByID($row->num);
  }

  public function getByID($num)
  {
    $data=$this->db->getData("num:'= ".$num."'",null,null,1);
    return $data->fetch(PDO::FETCH_OBJ);
  }
  
  public function put($data=null)
  {
    if (!is_array($data))
    {
      return false;
    }
    if (isset($data['name']))
    {
      $ud=$this->db->getData("name:'".$data['name']."'",array('num'),null,1);
      $check=$ud->fetch(PDO::FETCH_OBJ);
      if($check->num !== FALSE)
      {
        return $this->updateByID($check->num,$data);
      }
      else
      {
        $data['added']=date('Y-m-d H:i:s');
	$data['password']=crypt($data['password'],$GLOBALS['SET']['salt']);
	momoko_changes($GLOBALS['USR'],'added',$this);
        return $this->db->putData($data);
      }
    }
  }
  
  public function updateByName($name,array $data)
  {
    $resource=$this->db->getData("name:'".$name."'",array('num'),null,1);
    $user=$resource->fetch(PDO::FETCH_OBJ);
    momoko_changes($GLOBALS['USR'],'updated',$this);
    return $this->updateByID($user->num,$data);
  }
  
  public function updateByID($num,array $data)
  {
    $data['num']=$num;
    momoko_changes($GLOBALS['USR'],'updated',$this);
    return $this->db->updateData($data);
  }

  public function update($newname)
  {
    $oldname=pathinfo($this->path,PATHINFO_BASENAME);
    $data['name']=$newname;

    return $this->updateByName($oldname,$data);
  }

  public function drop()
  {
    $name=pathinfo($this->path,PATHINFO_BASENAME);
    $data=$this->db->getData("name:'".$name."'",array('num'),null,1);
    $row=$data->fetch(PDO::FETCH_OBJ);
    $del['num']=$row->num;
    momoko_changes($GLOBALS['USR'],'deleted',$this);
    return $this->db->removeData($del);
  }
}

class MomokoUCPModule implements MomokoModuleInterface
{
 public $opts;
 private $usr;

 public function __construct($usr,$opts)
 {
  $this->usr=$usr;
  parse_str($opts,$this->opts);
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
   if ($this->opts['display'] == 'box')
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
<span id="LoginLine" class="ucp"><a href="{$protocol}://mk-login.php">Login</a> | <a href="{$protocol}://mk-login.php?action=new">New Account?</a></span>
HTML;
  }
 }
 else
 {
  if ($this->opts['display'] == 'box')
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
   $userlink=$this->listUserActions();
   return <<<HTML
<span id="UCPLine" class="ucp">Welcome <strong>{$this->usr->name}</strong> | <a href="?action=logout">Logout</a></span>
HTML;
  }
 }
 }

 private function listUserActions($wrapper="__LINK__")
 {
  if ($GLOBALS['USR']->inGroup('admin'))
  {
   $atitle="AdminCP";
   if (!empty($this->opts['admin_title']))
   {
    $atitle=$this->opts['admin_title'];
   }
   $actions[]=array('href'=>'//'.$GLOBALS['SET']['baseuri'].ADMINROOT,'title'=>'AdminCP');
   $actions[]=array('href'=>'//'.$GLOBALS['SET']['baseuri'].ADDINROOT.QUERYSTARTER."action=list",'title'=>'Manage Addins');
  }
  if (is_array($this->opts['custom_links']))
  {
   foreach ($this->opts['custom_links'] as $group=>$link)
   {
    if ($this->usr->inGroup($group))
    {
     @list($link,$text,)=explode(">",$link);
     $link=str_replace("\\","/",$link);
     if (preg_match("/\/\//",$link) <= 0)
     {
      $link='//'.$GLOBALS['SET']['baseuri'].$link;
     }
     if (empty($text))
     {
      $text=$link;
     }
     $actions[]=array('href'=>$link,'title'=>$text);
    }
   }
  }
  $actions[]=array('href'=>'//'.$GLOBALS['SET']['baseuri'].ADDINROOT.'settings','title'=>'Change Settings');
  $actions[]=array('href'=>'?action=logout','title'=>'Logout');
  $html=null;

  foreach ($actions as $action)
  {
   $html.=preg_replace("/__LINK__/","<a href=\"".$action['href']."\" title=\"".$action['title']."\">".$action['title']."</a>",$wrapper);
  }

  return $html;
 }
}

class MomokoMiniCP implements MomokoModuleInterface
{
 public $user;
 public $dbtable;
 private $opts=array();

 public function __construct($user,$opts)
 {
  $this->user=$user;
  parse_str($opts,$this->opts);
  $this->dbtable=new DataBaseTable('addins');
 }

 public function getModule($format="html")
 {
  switch ($this->opts['display'])
  {
   case 'list':
   $link_tpl="<li class=\"module\">__LINK__</li>";
   return "<ul id=\"modules list\">".$this->listMods($link_tpl)."</ul>";
   case 'plugs':
   case 'grid':
   default:
   $link_tpl="<div class=\"module box addin plug\">__LINK__</div>";
   return "<div id=\"modules list box\">".$this->listMods($link_tpl)."</div>";
  }
 }

 public function listMods($wrapper="__LINK__")
 {
  $html=null;
  $query=$this->dbtable->getData("incp:'y'");
  while ($data=$query->fetch(PDO::FETCH_OBJ))
  {
   $html.=preg_replace("/__LINK__/","<a href=\"//".$GLOBALS['SET']['baseuri'].ADDINROOT.$data->dir."\" title=\"".$data->longname."\">".$data->shortname."</a>",$wrapper);
  }
  return $html;
 }
}