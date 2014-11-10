<?php

class MomokoPostsModule extends MomokoModule implements MomokoModuleInterface
{
	public $news_list;
 public $info=array();
 public $opt_keys=array();
 private $table;
 private $settings=array();
 
 public function __construct()
 {
  $this->opt_keys=array('sort'=>array('type'=>'select','options'=>array('recent')),'length'=>array('type'=>'number'),'num'=>array('type'=>'number'));
  $this->info=$this->getInfoFromDB();
  parse_str($this->info->settings,$this->settings);
  $this->table=new DataBaseTable('content');
  $query=$this->table->getData("type:'post'");
  $this->news_list=$query->fetchAll(PDO::FETCH_ASSOC);
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
     $item['timestamp']=time($post['date_modiefied']);
    }
    else
    {
     $item['timestamp']=time($post['date_created']);
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
			case 'rss':
			$uri_root='http://'.$GLOBALS['SET']['baseuri'].'/';
   $dom=new DOMDocument('1.0', 'UTF-8');
			$rss=$dom->appendChild(new DOMElement('rss'));
			$rss_version=$rss->setAttribute('version','2.0');
			$channel=$rss->appendChild(new DOMElement('channel'));
			$ftitle=$channel->appendChild(new DOMElement('title',$GLOBALS['SET']['name'].' News Feed'));
			$flink=$channel->appendChild(new DOMElement('link',$uri_root));
			$fdes=$channel->appendChild(new DOMElement('description',$GLOBALS['SET']['name']." Atom Feed for news items"));
			foreach ($data as $news)
			{
				$item=$channel->appendChild(new DOMElement('item'));
				$title=$item->appendChild(new DOMElement('title',$news['headline']));
				$link=$item->appendChild(new DOMElement('link',$uri_root.$news['file']));
				$pubdate=$item->appendChild(new DOMElement('pubDate',gmdate('Y-m-d\TH:i:s\Z',$news['timestamp'])));
				$guid=$item->appendChild(new DOMElement('guid',$this->generateUUID(null,$news['timestamp'])));
				$des=$item->appendChild(new DOMElement('description',$news['summary']));
			}
			$xml=$dom->saveXML();
			return $xml;
			break;
			case 'atom':
			$uri_root='//'.$GLOBALS['SET']['baseuri'].'/';
   $dom=new DOMDocument('1.0', 'UTF-8');
   $feed=$dom->appendChild(new DOMElement('feed',null,'http://www.w3.org/2005/Atom'));
			$ftitle=$feed->appendChild(new DOMElement('title',$GLOBALS['SET']['name'].' News Feed','http://www.w3.org/2005/Atom'));
			$fstitle=$feed->appendChild(new DOMElement('subtitle',$GLOBALS['SET']['name']." Atom Feed for news items"));
			$flink_self=$feed->appendChild(new DOMElement('link'));
			$flink_self_href=$flink_self->setAttribute('href',$uri_root."index.php/atom.xml");
			$flink_self_rel=$flink_self->setAttribute('rel','self');
			$flink_site=$feed->appendChild(new DOMElement('link'));
			$flink_self_href=$flink_site->setAttribute('href',$uri_root."index.php");
			foreach ($data as $news)
			{
				$entry=$feed->appendChild(new DOMElement('entry',null,'http://www.w3.org/2005/Atom'));
				$title=$entry->appendChild(new DOMElement('title',$news['headline']));
				$link=$entry->appendChild(new DOMElement('link'));
				$link_href=$link->setAttribute('href',$uri_root.$new['href']);
				$link_alt=$entry->appendChild(new DOMElement('link'));
				$link_alt_rel=$link_alt->setAttribute('rel','alternate');
				$link_alt_type=$link_alt->setAttribute('type','text/html');
				$link_alt_href=$link_alt->setAttribute('href',$uri_root.$news['href']);
				$uuid=$entry->appendChild(new DOMElement('id',$this->generateUUID("urn:uuid:",$news['timestamp'])));
				$date=$entry->appendChild(new DOMElement('updated',gmdate('Y-m-d\TH:i:s\Z',$news['timestamp'])));
				$summary=$entry->appendChild(new DOMElement('summary',$news['summary']));
			}
   $xml=$dom->saveXML();
			return $xml;
			break;
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
			 $news['file']=$GLOBALS['SET']['baseuri'].$news['href'];
			 $news['date']=date($GLOBALS['USR']->shortdateformat,$news['timestamp']);
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