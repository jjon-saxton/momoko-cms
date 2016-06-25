<?php

class MomokoPageAddin implements MomokoPageAddinInterface
{
  private $user;
  private $config;
  private $tables=array();
  private $settings=array();
  
  public function __construct($settings, MomokoSession $user)
  {
    $this->user=$user;
    $this->config=new MomokoSiteConfig();
    $this->tables['content']=new DataBaseTable('content');
    $this->tables['addin']=new DataBaseTable('addins');
    $this->settings=parse_ini_string($settings);
  }
  
  public function getPage()
  {
    return "<div class=\"alert alert-warning\">Addin-driven pages not yet supported.</div>";
  }
  
  public function getForm()
  {
    $fs=null;
    if ($this->settings['full_summary'] || $_GET['origin'] == 'new')
    {
      $fs="checked=\"checked\" ";
    }
    $un=null;
    if ($this->settings['per_user'] || $_GET['origin'] == 'new')
    {
      $un="checked=\"checked\" ";
    }
    return <<<HTML
<input type="hidden" name="link" value="postpage">
<div class="form-group">
<label for="sort">Sort:</label>
<select id="sort" class="form-control">
<option value="recent">Most Recent</option>
<option value="oldest">Oldest First</option>
<option value="headline">Headline</option>
</select>
</div>
<div class="form-group">
<label for="length">Summary Length:</label>
<input id="length" class="form-control" type="number" name="set[length]">
<input id="full_summary" type="checkbox" name="set[full_length]" {$fs}value=1> <label for="full_summary">Full Summary</label>
</div>
<div class="form-group">
<label for="num">Post per Page:</label>
<input id="num" class="form-control" type="number" name="set[num]">
<input id="per_user" type="checkbox" name="set[user_length]" {$un}value=1> <label for="per_user">Per User Settings</label>
</div>
HTML;
  }
}