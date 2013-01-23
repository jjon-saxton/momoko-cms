<?php
require_once dirname(__FILE__).'/assets/php/common.inc.php';
require_once $GLOBALS['CFG']->basedir.'/assets/php/content.inc.php';
require_once $GLOBALS['CFG']->basedir.'/assets/php/markdown.inc.php';

class MomokoDoc implements MomokoObject
{
 public $doc;
 public $txt;
 private $info=array();
 
 public function __construct($path)
 {
	$split=pathinfo($path);
	if (@$split['filename'])
	{
	 $this->doc=$split['dirname'].$split['filename'].'.text';
	 $this->txt=file_get_contents($GLOBALS['CFG']->basedir."/assets/docs".$this->doc);
	 if ($split['extension'] == 'htm' || $split['extension'] == 'html')
	 {
	  $this->info=$this->readInfo();
	 }
	 elseif ($split['extension'] == 'text' || $split['extension'] == 'txt' || $split['extension'] == 'md')
	 {
	  $this->info['inner_body']=$this->get();
	 }
	}
	else
	{
	 $this->doc='/getting_started.text';
	 $this->txt=file_get_contents($GLOBALS['CFG']->basedir.'/assets/docs'.$this->doc);
	 $this->info=$this->readInfo();
	}
 }
 
 public function __get($key)
 {
	if (array_key_exists($key,$this->info))
	{
	 return $this->info[$key];
	}
	else
	{
	 return null;
	}
 }
 
 public function __set($key,$value)
 {
	return null;
 }
 
 public function get()
 {
	return $this->txt;
 }
 
 public function readInfo()
 {
	$info['inner_body']=Markdown($this->txt);
	$info['title']="MomoKO Docs";
	if (preg_match("/<h1>(?P<title>.*?)<\/h1>/",$info['inner_body'],$match) > 0)
	{
	 $info['title'].=" - ".$match['title'];
	 $info['inner_body']=trim(preg_replace("/<h1>".preg_quote($match['title'])."<\/h1>/",'',$info['inner_body']),"\n\r");
	}
	//TODO $info['title']={whatever is in the first <h1> tag}
	
	return $info;
 }
}

if (@$_SERVER['PATH_INFO'])
{
 $child=new MomokoDoc($_SERVER['PATH_INFO']);
}
else
{
 header("Location: ".HELPROOT);
 exit();
}

$tpl=new MomokoTemplate('/assets/docs/main.tpl.htm');
print $tpl->toHTML($child);

?>