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
      $data['type']="security";
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
    $data['type']="security";
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

  public function getByEmail($email)
  {
    $data=$this->db->getData("email:'{$email}'",null,null,1);
    if ($data->numRows > 0)
    {
        while ($row=$data->fetch(PDO::FETCH_OBJ))
        {
            if ($row->name != 'root' && $row->name != 'guest') //prevents info from guest or root from being returned
            {
                return $row;
            }
        }
    }
    else
    {
        return false;
    }
  }

  public function getBySID($sid) //gets user information based on a session ID created during password resets
  {
   $res=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'].$sid.".txt";
   list($num,$name,$email)=explode(",",file_get_contents($res));
   unlink($res) //the sid storing text file is not required any longer, best to clean it up

   return $this->getByID($num);
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

  public function putSID($data,$sid)
  {
    $dir=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'];
    $name=$sid.".txt";

    if (is_writable($dir))
    {
     $txt=null;
     foreach ($data as $info)
     {
       $txt.=$info.",";
     }
     $txt=rtrim($txt,",");

     file_put_contents($dir.$name,$text) or trigger_error("Cannot write user info to session ID text file",E_USER_ERROR);
     momoko_changes($GLOBALS['USR'],"requests",$this);
     return $dir.$name;
    }
    else
    {
     trigger_error($dir." not writable! Cannot write required session ID text file!",E_USER_ERROR);
     return false;
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
