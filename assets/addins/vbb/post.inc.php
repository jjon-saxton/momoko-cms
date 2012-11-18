<?php

class VictoriqueThread implements MomokoLITEObject
{
 public $name;
 public $table;
 private $data;
 private $info=array();
 private $new_data=array();

 public function __construct($path=null)
 {
  $this->name=urldecode($path);
	$this->table=new DataBaseTable(DAL_TABLE_PRE.'bb_threads',DAL_DB_DEFAULT);
	$q=$this->table->getData(null,'subject~'.$this->name,null,1);
	$this->data=$q->first();
	if ($this->data->subject)
	{
   $this->info=$this->showPosts();
	}
 }

 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  elseif (array_key_exists($key,$this->new_data))
  {
   return $this->new_data[$key];
  }
  else
  {
   return $this->data->$key;
  }
 }

 public function __set($key,$value)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key]=$value;
  }
  else
  {
   return $this->new_data[$key]=$value;
  }
 }

 public function get()
 {
	return $this->data;
 }
 
 public function put($data,$sublist)
 {
  $post=array_merge($this->new_data,$data);
  if (@$post['num'])
  {
   return $this->table->updateData($post);
  }
  else
  {
	 $post['added']=date('Y-m-d H:i:s');
	 $post['author']=$GLOBALS['USR']->num;
   if ($thread=$this->table->putData($post))
	 {
		$new_post=new VictoriqueSinglePost(null);
		$new_post->parent=$thread;
		return $new_post->put($post,$sublist);
	 }
  }
 }
 
 public function editForm(array $settings)
 {
  $post=new VictoriqueSinglePost(null);
	return $post->editForm($settings);
 }

 private function showPosts()
 {
  $top_data=$this->data;
	$html=file_get_contents(BBBASE.'/templates/postlist.tpl.htm');
  $vars['sectiontitle']="View Topic: ".$top_data->subject;
  $vars['bbroot']=BBROOT;
  $vars['topic_url']=strtolower(urlencode($top_data->subject));
  $ptbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
  $pdata=$ptbl->getData(array('num','name','bbc'),'num='.$top_data->parent,null,1);
  $pdata=$pdata->first();
  $vars['forum_url']=strtolower(urlencode($pdata->name));
	$vars['bbc_level']=$pdata->bbc;
  $vh=new VictoriqueDataHandler($vars,$html);

  if (preg_match_all("/<!-- SECTION:(?P<name>.*)\/\/ -->(?P<body>.*)<!-- \/\/SECTION -->/smU",$html,$matches) > 0) //Find Sections
  {
   $c=0;
   foreach($matches['name'] as $section)
   {
    $vh->parse($section,$matches['body'][$c],$top_data->num);
    $c++;
   }
  }

  return $vh->setInfo();
 }
}

class VictoriqueSinglePost implements MomokoLITEObject
{
 public $num;
 private $table;
 private $info=array();
 private $new_data=array();
 
 public function __construct($path)
 {
  $this->table=new DataBaseTable(DAL_TABLE_PRE.'bb_posts',DAL_DB_DEFAULT);
  $this->num=urldecode($path);
  $data=$this->get();
  $data=$data->toArray();
  if (is_array(@$data[0]) && array_key_exists('num',$data[0]))
  {
   $this->info=$data[0];
   $this->new_data['num']=$data[0]['num'];
  }
  unset($data);
 }
 
 public function __get($key)
 {
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key];
  }
  else
  {
   return false;
  }
 }
 
 public function __set($key,$value)
 {
  $this->new_data[$key]=$value;
 }
 
 public function get()
 {
  return $this->table->getData(null,'num='.$this->num,null,1);
 }
 
 public function put($data,$sublist)
 {
  $post=array_merge($this->new_data,$data);
  $subbers=new VictoriqueNotification(null);
  $subbers->put($sublist,$GLOBALS['USR']->num,'forum-reply',BBROOT);
  if ($_GET['action'] == 'edit')
  {
   return $this->table->updateData($post);
  }
  else
  {
   return $this->table->putData($post);
  }
 }
 
 public function editForm(array $settings)
 {
  if ($_GET['action'] == 'edit')
  {
   $info['title']="Edit Post: ".$this->subject;
  }
	elseif ($_GET['what'] == 'topic')
	{
	 $info['title']="New Topic";
	}
  else
  {
   $info['title']="New Post";
  }
  $info['inner_body']=file_get_contents(BBBASE.'/templates/newpost.tpl.htm');
  $vars['sig_bullets']="<span id=\"sigs\"><input type=radio name=\"show_sig\" checked=checked id=\"ysig\" value=\"y\"><label for=\"ysig\">Yes</label> <input type=radio name=\"show_sig\" id=\"nsig\" value=\"n\"><label for=\"nsig\">No</label></span>";
  $vars['sub_bullets']="<span id=\"subs\"><input type=radio checked=checked name=\"subscribe\" id=\"ysub\" value=\"y\"><label for=\"ysub\">Yes</label> <input type=radio name=\"subscrbe\" id=\"nsub\" value=\"n\"><label for=\"nsub\">No</label></span>";
	$vars=array_merge($vars,$settings);
  if ($_GET['action'] == 'edit')
  {
   $vars=$this->info;
   if ($vars['show_sig'] == 'n')
   {
	$vars['sig_bullets']="<span id=\"sigs\"><input type=radio name=\"show_sig\" id=\"ysig\" value=\"y\"><label for=\"ysig\">Yes</label> <input type=radio name=\"show_sig\" checked=checked id=\"nsig\" value=\"n\"><label for=\"nsig\">No</label></span>";
   }
  }
  if (@$this->new_data['subject'] && !@$vars['subject'])
  {
   $vars['subject']=$this->new_data['subject'];
  }
  $ch=new MomokoCommentHandler($vars);
  $info['inner_body']=$ch->replace($info['inner_body']);
  
  return $info;
 }
}
