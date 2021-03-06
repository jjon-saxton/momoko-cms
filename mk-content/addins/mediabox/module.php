<?php
class MomokoMediaboxModule extends MomokoModule implements MomokoModuleInterface
{
 public $info;
 public $opt_keys=array();
 protected $settings=array();
 private $actions=array();

 public function __construct(MomokoSession $user,$extset=null)
 {
  $this->info=$this->getInfoFromDB();
  $this->opt_keys=array('type'=>array('type'=>'select','options'=>array('image','video','audio','object')),'width'=>array('type'=>'number'),'height'=>array('type'=>'number'),'link1'=>array('type'=>'link'),'link2'=>array('type'=>'link'));
  if (empty($extset))
  {
    parse_str($this->info->settings,$this->settings); 
  }
  else
  {
    parse_str($extset,$this->settings);
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
<script language="javascript" type="text/javascript">
$(function(){
  $("#MediaBox img").css("cursor","pointer").click(function(){
    var src=$(this).attr('src');
    $("#modal .modal-title").html("Image Viewer");
    $("#modal .modal-body").html("<a href=\""+src+"\" title=\"Open full image in new tab\" target=\"_new\"><img width=\"100%\" src=\""+src+"\"></a>");
    $("#modal").modal('show');
  });
});
</script>
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
