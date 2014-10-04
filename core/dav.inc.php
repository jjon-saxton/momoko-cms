<?php

require './lib/includes/common.inc.php';
require './lib/includes/content.inc.php';
require './lib/includes/asset.inc.php';

abstract class MomokoNode implements Sabre_DAV_INode
{
  protected $path;
  protected $fsp;
		protected $type;

  public function __construct($path)
  {
    @list($base,$type)=explode("/",$path);
				$this->path=$path;
				$fpath=preg_replace("/".$base."/",'',$path);
				$fpath=preg_replace("/".$type."/",'',$fpath);
				$fpath=trim($fpath,'/');
    switch ($type)
				{
					case 'Sites':
					$this->fsp=BASEDIR.$GLOBALS['CFG']->sitedir.'/'.$fpath;
					break;
					case 'Files':
					$this->fsp=BASEDIR.$GLOBALS['CFG']->filedir.'/'.$fpath;
					break;
				}
				$this->type=$type;
  }

  public function getName()
  {
    list(,$name)=Sabre_DAV_URLUtil::splitPath($this->path);
    return $name;
  }

  public function setName($name)
  {
    list(,$newname)=Sabre_DAV_URLUtil::splitPath($name);

    switch ($this->type)
    {
					case 'Sites':
					if(is_dir($this->fsp))
					{
						$dir=new MomokoSite($this->fsp);
						$dir->update($newname);
					}
					else
					{
						$file=new MomokoContent($this->fsp);
						$file->update($newname);
					}
					break;
					case 'Files':
					if(is_dir($this->fsp))
					{
						$dir=new MomokoBin($this->fsp);
						$dir->update($newname);
					}
					else
					{
						$file=new MomokoFile($this->fsp);
						$file->update($newname);
					}
					break;
    }
  }

  public function getLastModified()
  {
   return filemtime($this->fsp);
  }
}

class MomokoCollection extends MomokoNode implements Sabre_DAV_ICollection
{
  public function createFile($name,$data=null)
  {
    switch($this->type)
				{
					case 'Files':
					$file=new MomokoFile($this->fsp.'/'.$name);
					return $file->put($data);
					break;
					case 'Sites':
					$content=new MomokoContent($this->fsp.'/'.$name);
					return $content->put($data);
					break;
    }
  }

  public function createDirectory($name)
  {
    switch ($this->type)
				{
					case 'Files':
					$bin=new MomokoBin($this->fsp.'/'.$name);
					return $bin->put();
					break;
					case 'Sites':
					$site=new MomokoSite($this->fsp.'/'.$name);
					return $bin->put();
					break;
				}
  }

  public function getChild($name)
  {
    $path=$this->path.'/'.$name;
				switch ($name)
				{
					case '':
					case 'Root':
					case 'Files':
					case 'Sites':
					return new MomokoCollection($path);
					break;
					default:
    	if (!is_dir($this->fsp))
    	{
     	 return new MomokoObject($path);
    	}
    	else
    	{
      	return new MomokoCollection($path);
    	}
				}
  }

  public function getChildren()
  {
    $path=trim($this->path,"/");
    list($path,$name)=Sabre_DAV_URLUtil::splitPath($path);
    switch ($name)
    {
      case '':
      case 'Root':
      $list=array("Files","Sites");
      break;
      default:
      list(,$p)=Sabre_DAV_URLUtil::splitPath($path);
      switch($this->type)
      {
							case "Files":
							$bin=new MomokoBin();
							$list=$bin->listContent();
							break;
							case "Sites":
							$site=new MomokoSite();
							$list=$site->ListContent();
							break;
      }
    }

    $children=array();
    foreach ($list as $node)
    {
      $children[]=$this->getChild($node);
    }

    return $children;
  }

  public function childExists($name)
  {
    return is_dir($this->fsp.'/'.$name);
  }

  public function delete()
  {
    switch ($this->type)
    {
      case 'Files':
						$bin=new MomokoBin($this->fsp);
						$bin->drop();
      break;
      case 'Sites':
      $site=new MomokoSite($this->fsp);
      $site->drop();
      break;
    }
  }
}

class MomokoObject extends MomokoNode implements Sabre_DAV_IFile
{
  public function put($data)
  {
    $data=stream_get_contents($data);
    switch ($this->type)
    {
      case 'Files':
						$file=new MomokoFile($this->fsp);
						$file->put();
						break;
      case 'Sites':
      $resource=new MomokoContent($this->fsp);
						$resource->put();
      break;
    }
  }

  public function get()
  {
    $r=$this->getInfo();
				switch($this->type)
    {
			  case 'Files':
    	return fopen("data://".$r['mime'].";base64,".base64_encode($r['data']),'r');
    	case 'Sites':
    	return fopen("data://text/plain,".$r['data'],'r');
    }
  }

  public function delete()
  {
    switch ($this->type)
    {
      case 'Files':
						$file=new MomokoFile($this->fsp);
						$file->drop();
      break;
      case 'Sites':
						$resource=new MomokoContent($this->fsp);
						$resource->drop();
      break;
    }
  }

  public function getSize()
  {
				$r=$this->getInfo();
    return $r['size'];
  }

  public function getETag()
  {
    $r=$this->getInfo();
    return md5($r['data']);
  }

  public function getContentType()
  {
    $r=$this->getInfo();
				return $r['mime'];
  }

  public function getInfo()
  {
			$finfo=new finfo(FILEINFO_MIME_TYPE);
			$info['mime']=$finfo->file($this->fsp);
			$info['name']=pathinfo($this->fsp,PATHINFO_BASENAME);
			$info['extension']=pathinfo($this->fsp,PATHINFO_EXTENSION);
			$info['size']=filesize($this->fsp);
			switch ($this->type)
			{
				case 'Files':
				$file=new MomokoFile($this->fsp);
				$info['data']=$file->get();
				break;
				case 'Sites':
				$resource=new MomokoContent($this->fsp);
				$info['data']=$resource->get();
				break;
			}
  }
}
