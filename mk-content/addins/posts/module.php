<?php

class MomokoPostsModule extends MomokoModule implements MomokoModuleInterface
{
 public $news_list;
 public $info=array();
 public $opt_keys=array();
 private $config;
 private $table;
 private $settings=array();
 
 public function __construct(MomokoSession $user)
 {
  $this->opt_keys=array('sort'=>array('type'=>'select','options'=>array('recent')),'length'=>array('type'=>'number'),'num'=>array('type'=>'number')); //TODO add other sort types
  $this->info=$this->getInfoFromDB();
  parse_str($this->info->settings,$this->settings);
  $this->table=new DataBaseTable('content');
  $query=$this->table->getData("type:'post' status:'public'",null,"date_created >"); //TODO sort should be a variable so we can use other sort types
  $this->news_list=$query->fetchAll(PDO::FETCH_ASSOC);
  $this->user=$user;
  $this->config=new MomokoSiteConfig();
 }
	
 public function getModule ($format='html')
 {
  $data=array();
	
  if (is_array($this->news_list))
  {
   foreach ($this->news_list as $post)
   {
    if ($post['date_modified'])
    {
     $item['timestamp']=strtotime($post['date_modified']);
    }
    else
    {
     $item['timestamp']=strtotime($post['date_created']);
    }
    $item['headline']=$post['title'];
    if ($GLOBALS['SET']['rewrite'])
    {
     $item['href']="post/".urlencode($post['title']).".htm";
    }
    else
    {
     $item['href']="/?content=post&p=".$post['num'];
    }
    $item['summary']=preg_replace("/<h2>(.*?)<\/h2>/smU",'',$post['text']);
    $data[]=$item;
    unset($item);
   }
		}
		
		switch ($format)
		{
			case 'array':
			return $data;
			case 'html':
			default:
		 $html="<div id=\"NewsList\" class=\"news box\">\n";
		
		 if (isset($this->settings['sort']))
		 {
			 switch ($this->settings['sort'])
			 {
				 case 'recent':
				 break;
				 case 'oldest':
				 usort($data,build_sorter('date'));
				 break;
			 }
		 }
		 $max=$this->settings['num'];
		
		 $c=1;
		 foreach($data as $news)
		 {
			 $news['file']=$this->config->baseuri.$news['href'];
			 $news['date']=date($this->user->shortdateformat,$news['timestamp']);
			 if ($max > 0 && $c<=$max)
			 {
			  if (strlen($news['summary']) > $this->settings['length'])
                          {
                           $matches = array();
  			   preg_match("/^(.{1,".$this->settings['length']."})[\s]/i", $news['summary'], $matches);
                           $text=$matches[0].'... <a href="//'.$news['file'].'">more</a>';
                          }
			  else
                          {
                           $text=$news['summary'].' <a href="//'.$news['file'].'">view/comment on article</a>';
                          }
			  $html.=<<<HTML
<div id="{$news['date']}" class="news item">
<h4 class="headline">{$news['headline']}</h4>
<div class="date">{$news['date']}</div>
<div class="summary">
{$text}
</div>
</div>
HTML;
			 }
			 $c++;
		 }
		
		 $html.="</div>";
		 return $html;
		}
	}
		
	public function getInfoFromDB()
	{
	 $table=new DataBaseTable("addins");
	 $query=$table->getData("dir:'".basename(dirname(__FILE__))."'",null,null,1);
	 return $query->fetch(PDO::FETCH_OBJ);
	}
}
