<?php
class MomokoMediaboxModule extends MomokoModule implements MomokoModuleInterface
{
 public $info;
 public $opt_keys=array();
 private $actions=array();
 private $settings=array();

 public function __construct()
 {
  $this->info=$this->getInfoFromDB();
  $this->opt_keys=array('type'=>array('type'=>'select','options'=>array('image','video','audio','object')),'width'=>array('type'=>'number'),'height'=>array('type'=>'number'),'link1'=>array('type'=>'link'),'link2'=>array('type'=>'link'));
  parse_str($this->info->settings,$this->settings); 
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->settings))
  {
   return $this->settings[$key];
  }
  else
  {
   return false;
  }
 }

 public function getModule($format='html')
 {
  $src[0]=$this->settings['link1'];
  $src[1]=$this->settings['link2'];
  if ($this->settings['width'])
  {
   $obj['width']=" width=\"".$this->settings['width']."px\"";
  }
  else
  {
   $obj['width']=" width=\"100%\"";
  }
  if ($this->settings['height'])
  {
   $obj['height']=" height=\"".$this->settings['height']."px\"";
  }
  else
  {
   $obj['height']=null;
  }
  
  if (!empty($src))
  {
   switch ($this->settings['type'])
   {
    case 'audio':
    case 'video':
    $tag=$this->settings['type'];
    $media="<{$tag}{$obj['width']}{$obj['height']} controls>\n";
    foreach ($src as $i)
    {
     $media.="<source src=\"{$i}\">\n";
    }
    $media.="</{$tag}>";
    break;
    case 'object':
    $media="<object{$obj['width']}{$obj['height']} data=\"{$src[0]}\">Your browser does not support this content!</object>";
    break;
    case 'image':
    default:
    $media="<img{$obj['width']}{$obj['height']} src=\"{$src[0]}\">";
   }

   return <<<HTML
<div id="MediaBox" class="box">
{$media}
</div>
HTML;
  }
  else
  {
   return "<!-- No media set -->";
  }
 }
		
	public function getInfoFromDB()
	{
	 $table=new DataBaseTable("addins");
	 $query=$table->getData("dir:'".basename(dirname(__FILE__))."'",null,null,1);
	 return $query->fetch(PDO::FETCH_OBJ);
	}
}
