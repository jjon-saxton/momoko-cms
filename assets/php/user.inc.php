<?php
require_once $GLOBALS['CFG']->basedir.'/assets/php/dal/load.inc.php';

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
    if (crypt($password,$user->password) == $user->password)
    {
      $this->name=$name;
      $this->user=$user;
      $this->groups=$this->updateGroups();
      return true;
    }
    else
    {
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
   
    return true;
   }
   else
   {
    return false;
   }
  }

  public function logout()
  {
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

class MomokoUser implements MomokoResourceInterface
{
  private $db;
		private $path;
		private $info=array();

  public function __construct($path=null)
  {
    $this->db=new DataBaseTable(DAL_TABLE_PRE.'users',DAL_DB_DEFAULT);
				$this->path=$path;
				$this->info['epath']='indentity/';
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
   $data=$this->db->getData('num','name~'.$name,null,1);
   $row=$data->first();
   return $this->getByID($row->num);
  }

  public function getByID($num)
  {
    $data=$this->db->getData(null,'num='.$num,null,1);
    return $data->first();
  }
  
  public function put($data=null)
  {
			if (!is_array($data))
			{
				return false;
			}
    if (isset($data['name']))
    {
      $ud=$this->db->getData('num','name='.$data['name'],null,1);
      $check=$ud->first();
      if($check->num) //TODO evaluate this check!
      {
        return $this->updateByName($data['name'],$data);
      }
      else
      {
        $data['added']=date('Y-m-d H:i:s');
		$data['password']=crypt($data['password'],$GLOBALS['CFG']->salt);
        return $this->db->putData($data);
      }
    }
  }
  
  public function editForm()
  {
   $data=$this->get();
   $html=<<<HTML
<h2>Manage Member: {$data->name}</h2>
<form action="#edit" method=post>
<ul id="MemberInfo" class="nobullet">
<li><label for="name">Name:</label> <input type=text name="name" id="name" value="{$data->name}"></li>
<li><label for="pass">Password: </label> <span id="pass">Saved</span></li>
<li><label for="email">E-mail:</label> <input type=text name="email" id="email" value="{$data->email}"></li>
<li><label for="groups">Groups:</label> <input type=text name="groups" id="groups" value="{$data->groups}"></li>
<li><div id="MainOption" class="oneline"><input type=submit name="send" value="Edit Member"></div> <div id="SecondOption" class="oneline"><input type=submit name="delete" value="Delete Member"></div></li>
</ul>
</form>
HTML;
   return $html;
  }
  
  public function updateByName($name,array $data)
  {
    $resource=$this->db->getData('num','name='.$name,null,1);
    $user=$resource->first();
    return $this->updateByID($user->num,$data);
  }
  
  public function updateByID($num,array $data)
  {
    $data['num']=$num;
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
   $data=$this->db->getData('num','name~'.$name,null,1);
   $row=$data->first();
			$del['num']=$row->num;
   return $this->db->removeData($del);
		}
}

class MomokoIdentities implements MomokoStorageInterface
{
	private $path;
	private $db;
	private $info=array();
	
	public function __construct($path=null)
	{
		$this->path=$path;
		$this->info['epath']=$path;
		$this->info['dir']=true;
		$this->db=new DataBaseTable(DAL_TABLE_PRE.'users',DAL_DB_DEFAULT);
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
	
	public function getChildren()
	{
		$users=$this->listContent();
		$children=array();
		
		foreach ($users as $user)
		{
			$r=new MomokoUser($this->path.'/'.$user);
			$children[]=$r->get();
		}
		
		return $children;
	}
	
	public function listContent()
	{
		$users=array();
		
		$q=$this->db->getData(array('num','name'));
		while ($user=$q->next())
		{
			$users[]=$user->name;
		}
		
		return $users;
	}
	
	public function put()
	{
		return false;
	}
	
	public function update($newname)
	{
		return false;
	}
	
	public function drop()
	{
		return false;
	}
}

class MomokoUserCPModule implements MomokoModuleInterface
{
	private $user;
	private $options=array();
	
	public function __construct($user,$options)
	{
		$this->user=$user;
		parse_str($options,$options);
		if (!@$options['acp_name'])
		{
		 $options['acp_name']="AdminCP";
		}
		if (!@$options['ucp_name'])
		{
		 $options['ucp_name']="UserCP";
		}
		if (!@$options['login_name'])
		{
		 $options['login_name']="Login";
		}
		$this->options=$options;
	}
	
	public function __get($key)
	{
		if (array_key_exists($key,$this->options))
		{
			return $this->options[$key];
		}
		else
		{
			return false;
		}
	}
	
	public function getModule($format='html')
	{
	 if ($format == 'html')
	 {
	 	if ($this->user->inGroup('admin'))
	 	{
	 		$links=array("Welcome ".$this->user->name."!","<a href=\"//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/acp/\">".$this->acp_name."</a>","<a href=\"//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/ucp/\">".$this->ucp_name."</a>");
	 	}
	 	elseif ($this->user->inGroup('users'))
	 	{
	 		$links=array("Welcome ".$this->user->name."!","<a href=\"//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/ucp/\">".$this->ucp_name."</a>");
	 	}
	 	elseif ($this->user->inGroup('nobody'))
	 	{
	 		$links=array("<a href=\"./login.mmk\">".$this->login_name."</a>");
	 	}
	 	elseif ($this->user->inGroup('restricted') || $this->user->inGroup('banned'))
	 	{
	 		$links=array("Welcome ".$this->user->name."!","User restrictions in place!");
	 	}
	 	else
	 	{
	 		$links=array("Welcome ".$this->user->name."!");
	 	}
	 }
	 
	 if (is_array($this->custom_links))
	 {
	  foreach ($this->custom_links as $group=>$link)
	  {
	  	if ($this->user->inGroup($group))
	  	{
				@list($link,$text,)=explode("=>",$link);
				$link=str_replace("\\","/",$link);
				if (!@$text)
				{
				 $text=$link;
				}
	  	 	$links[]="<a href=\"{$link}\">{$text}</a>";
	  	}
	  }
	 }
	 
	 if (!$this->user->inGroup('nobody'))
	 {
	  $links[]="<a href=\"./logout.mmk\">Logout</a>";
	 }

		switch ($this->display)
		{
			case 'line':
			$html=null;
			foreach ($links as $item)
			{
			 $html.=$item." | ";
			}
			$html=trim($html, " | ");
			break;
			case 'list':
			$html="<ul id=\"UserCPList\">\n";
			foreach ($links as $item)
			{
				$html.="<li class=\"cp-item\">".$item."</li>\n";
			}
			$html.="</ul>";
			break;
			default:
			$html="<!-- Display missing or not recognized: '{$this->dispay}' -->";
		}
		
		if ($GLOBALS['CFG']->rewrite)
		{
		 $siteroot="/";
		}
		else
		{
		 $siteroot="/index.php/";
		}
		
		return preg_replace("/\.\//",$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.$siteroot,$html);
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
   if ($this->opts['display'] == 'box')
   {
    return <<<HTML
<div id="LoginBox" class="ucp box">
<form action="?action=login" method=post>
<ul class="noindent nobullet">
<li><input type=text name="name" placeholder="username:"></li>
<li><input type=password name="password" placeholder="password:"></li>
<li><input type=submit name="send" value="Login"></li>
<li><a href="?action=register">Want an account?</a></li>
</ul>
</form>
</div>
HTML;
  }
  else
  {
   return <<<HTML
<span id="LoginLine" class="ucp"><form action="?action=login" method=post><input type=text name="name" placeholder="username:"> <input type=password name="password" placeholder="password:"> <input type=submit name="send" value="Login"></form></span>
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
   $actions[]=array('href'=>$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.ADMINROOT,'title'=>'AdminCP');
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
      $link=$GLOBALS['CFG']->domain.$GLOBALS['CFG']->location.$link;
     }
     if (empty($text))
     {
      $text=$link;
     }
     $actions[]=array('href'=>$link,'title'=>$text);
    }
   }
  }
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
  $this->dbtable=new DataBaseTable(DAL_TABLE_PRE.'addins',DAL_DB_DEFAULT);
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
  $query=$this->dbtable->getData(null,'incp=y');
  while ($data=$query->next())
  {
   $html.=preg_replace("/__LINK__/","<a href=\"".ADDINROOT.$data->dir."\" title=\"".$data->longname."\">".$data->shortname."</a>",$wrapper);
  }
  return $html;
 }
}
