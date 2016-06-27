<?php

class MomokoPageAddin implements MomokoPageAddinInterface
{
  private $user;
  private $config;
  private $table;
  private $settings=array();
  
  public function __construct($settings, MomokoSession $user)
  {
    $this->user=$user;
    $this->config=new MomokoSiteConfig();
    $this->table=new DataBaseTable('content');
    $this->settings=parse_ini_string($settings);
  }
  
  public function __get($key)
  {
    return $this->settings[$key];
  }
  
  public function getPage()
  {
    $sort=null; //TODO set sort from page settings
  
    if ($this->num == 0 && $this->user_length == 1)
    {
      $perpage=$this->user->rowspertable;
    }
    elseif ($this->num == 0)
    {
      trigger_error("No posts per page given to addin ('Post Page') from page settings.",E_USER_WARNING);
      $perpage=$this->user->rowspertable;
    }
    else
    {
      $perpage=$this->num;
    }
    
    if ($this->length == 0 && $this->full_length == 1)
    {
     $sum_no_restrict=true;
    }
    elseif ($this->length == 0)
    {
     trigger_error("No summary length set for addin ('Post Page') from page settings.",E_USER_WARNING);
     $sum_no_restrict=false;
     $sum_len=255;
    }
    else
    {
     $sum_len=$this->length;
    }
    
    $full_list=$this->table->getData("type:`post`");
    $total_posts=0;
    while ($full_list->fetch())
    {
     $total_posts++;
    }
    unset($full_list);
    if ($this->tags)
    {
      $tlist=explode(",",$this->tags);
      $tags=new MomokoTags();
      $list=$tags->getContent($tlist,'post',$sort,$perpage,$_GET['offset']);
    }
    else
    {
      echo "No tags";
      $list=$this->table->getData("type:`post`",null,$sort,$perpage,$_GET['offset']);
    }
    $html="<div id=\"PostList\" class=\"panel-group\">\n";
    while ($post=$list->fetch(PDO::FETCH_OBJ))
    {
      if (!empty($post->date_modified))
      {
       $datetime=date($this->user->longdateformat." ".$this->user->timeformat,strtotime($post->date_modified));
      }
      else
      {
        $datetime=date($this->user->longdateformat." ".$this->user->timeformat,strtotime($post->date_created));
      }
      $html.=<<<HTML
<div id="{$post->num}" class="panel panel-default">
  <div class="panel-heading"><strong><a href="{$this->config->siteroot}/?p={$post->num}">{$post->title}</a></strong> - {$datetime}</div>
  <div class="panel-body">{$post->text}</div>
  <div class="panel-footer"><a href="{$this->config->siteroot}/?p={$post->num}" class="btn btn-default">View Post</a></div>
</div>
HTML;
//TODO shorten summary ($post->text) based on page settings
    }
    $html.="</div>\n"; //TODO panginate results
    
    return $html;
  }
  
  public function getForm()
  {
    $fs=null;
    if ($this->full_length || $_GET['origin'] == 'new')
    {
      $fs="checked=\"checked\" ";
    }
    $un=null;
    if ($this->user_length || $_GET['origin'] == 'new')
    {
      $un="checked=\"checked\" ";
    }
    return <<<HTML
<input type="hidden" name="mime_type" value="application/postpage">
<div class="form-group">
<label for="sort">Sort:</label>
<select id="sort" name="set[sort]" class="form-control">
<option value="recent">Most Recent</option>
<option value="oldest">Oldest First</option>
<option value="headline">Headline</option>
</select>
</div>
<div class="form-group">
<label for="length">Summary Length:</label>
<input id="length" class="form-control" type="number" name="set[length]" value="{$this->length}">
<input id="full_summary" type="checkbox" name="set[full_length]" {$fs}value=1> <label for="full_summary">Full Summary</label>
</div>
<div class="form-group">
<label for="num">Post per Page:</label>
<input id="num" class="form-control" type="number" name="set[num]" value="{$this->num}">
<input id="per_user" type="checkbox" name="set[user_length]" {$un}value=1> <label for="per_user">Per User Settings</label>
</div>
<div class="form-group">
<label for="filter">Filter by Tags:</label>
<input type="text" class="form-control" name="set[tags]" value="{$this->tags}">
</div>
HTML;
  }
}