<?php
class MomokoMediaboxModule extends MomokoModule implements MomokoModuleInterface
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
  $this->opt_keys=array('type'=>array('type'=>'select','options'=>array('image','video','audio','object')),'link'=>array('type'=>'link'));
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
  $src[1]=$this->settings['link2'];u
  if (!empty($src))
  {
   switch ($this->settings['type'])
   {
    case 'audio':
    case 'video':
    $tag=$this->settings['type'];
    $media="<{$tag} controls>\n";
    foreach ($src as $i)
    {
     $media.="<source src=\"{$i}\">\n"
    }
    $media.="</{$tag}>";
    break;
    case 'object':
    $media="<object data=\"{$src[0]}\">";
    break
    case 'image':
    default:
    $media="<img src=\"{$src[0]}\">";
   }

   return <<<HTML
<div id="MediaBox" class="box" style="max-width:100%">
{$media}
</div>
HTML;
  }
  else
  {
   return "<!-- No media set -->";
  }
 }
}
