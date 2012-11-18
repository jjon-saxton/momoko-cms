<?php

class VictoriqueForum implements MomokoLITEObject
{
 public $name;
 public $tbl;
 public $data;
 private $info;

 public function __construct($path=null)
 {
  $this->tbl=new DataBaseTable(DAL_TABLE_PRE.'bb_forums',DAL_DB_DEFAULT);
  $this->name=urldecode($path);
  $data=$this->tbl->getData(null,'name~'.$this->name,null,1);
  $this->data=$data->first();
  $this->info=$this->showForums();
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
  if (array_key_exists($key,$this->info))
  {
   return $this->info[$key]=$value;
  }
  else
  {
   return false;
  }
 }

 public function get()
 {
  return $this->data;
 }
 
 public function put($data)
 {
  $check=$this->data;
  if ($check->num)
  {
   $data['num']=$check->num;
   return $this->tbl->updateData($data);
  }
  else
  {
   return $this->tbl->putData($data);
  }
 }
 
 public function drop()
 {
  $check=$this->data;
  if ($check->num)
  {
   $ctbl=new DataBaseTable(DAL_TABLE_PRE.'bb_threads',DAL_DB_DEFAULT); //oprhan threads
   $clist=$ctbl->getData('num','parent='.$check->num);
   $new['parent']=0;
   while ($child=$clist->next())
   {
	$new['num']=$child->num;
	$ctbl->updateData($new);
   }
   unset($ctbl);
   
   $clist=$this->tbl->getData(array('num','name'),'parent='.$check->num); //remove child forums
   while ($child=$clist->next())
   {
	$child=new VictoriqueForum($child->name);
	$child->drop();
   }
   
   $del['num']=$this->data->num;
   return $this->tbl->removeData($del);
  }
  else
  {
   return false;
  }
 }
 
 public function editForm()
 {
  $data=$this->data;
  
  list($perms['owner'],$perms['group'],$perms['other'])=explode(":",$data->permissions);
  $permchecks="<div id=\"perms\"><table width=75% border=0 cellspacing=1 cellpadding=1>\n<tr>\n<th>&nbsp;</th><th>Other</th><th>Groups</th><th>Owner</th>\n</tr>\n";
  
  $permchecks.="<tr>\n<th>View</th>";
  foreach ($perms as $col=>$val)
  {
   if(preg_match("/r/",$val) > 0)
   {
	$attr=" checked=checked";
   }
   else
   {
	$attr=null;
   }
   $permchecks.="<td><input type=checkbox".$attr." onclick=\"setPerms()\" name=\"permbox\" id=\"".$col."\" value=\"r\"></td>";
  }
  $permchecks.="</tr>\n";
  
  $permchecks.="<tr>\n<th>Post</th>";
  foreach ($perms as $col=>$val)
  {
   if(preg_match("/w/",$val) > 0)
   {
	$attr=" checked=checked";
   }
   else
   {
	$attr=null;
   }
   $permchecks.="<td><input type=checkbox".$attr." onclick=\"setPerms()\" name=\"permbox\" id=\"".$col."\" value=\"w\"></td>";
  }
  $permchecks.="</tr>\n";
  
  $permchecks.="</table></div>";
  $boolopts=array(array('label'=>'Yes','value'=>'y'),array('label'=>'No','value'=>'n'));
  $bbcopts=array(array('label'=>'Full','value'=>'f'),array('label'=>'Light','value'=>'l'),array('label'=>'None','value'=>'n'));
  
  $sigbullets="<span id=\"sigs\">";
  foreach ($boolopts as $option)
  {
   if ($option['value'] == $data->user_signatures)
   {
    $attr=" checked=checked";
   }
   else
   {
	$attr=null;
   }
   $sigbullets.="<input type=radio name=\"user_signatures\"".$attr." id=\"sigs".$option['value']."\" value=\"".$option['value']."\"> <label for=\"sigs".$option['value']."\">".$option['label']."</label> ";
  }
  $sigbullets.="</span>";
  
  $htmlbullets="<span id=\"html\">";
  foreach ($boolopts as $option)
  {
   if ($option['value'] == $data->html)
   {
    $attr=" checked=checked";
   }
   else
   {
	$attr=null;
   }
   $htmlbullets.="<input type=radio name=\"html\"".$attr." id=\"html".$option['value']."\" value=\"".$option['value']."\"> <label for=\"html".$option['value']."\">".$option['label']."</label> ";
  }
  $htmlbullets.="</span>";
  
  $bbcbullets="<span id=\"bbc\">";
  foreach ($bbcopts as $option)
  {
   if ($option['value'] == $data->bbc)
   {
    $attr=" checked=checked";
   }
   else
   {
	$attr=null;
   }
   $bbcbullets.="<input type=radio name=\"bbc\"".$attr." id=\"bbc".$option['value']."\" value=\"".$option['value']."\"> <label for=\"bbc".$option['value']."\">".$option['label']."</label> ";
  }
  $bbcbullets.="</span>";
  
  $html=<<<HTML
<script language="javascript" type="text/javascript">
function setPerms()
{
 perms=new Object();
 $('#perms input:checked').each(function(){
	 var col=$(this).attr('id')
	 var val=$(this).val();
	 if (perms[col])
	 {
	  perms[col]+=val;
	 }
	 else
	 {
	  perms[col]=val;
	 }
 });
 permstring=perms.owner+":"+perms.group+":"+perms.other;
 val=permstring.replace("undefined","-");
 
 $('input[name=permissions]').val(val);
}
</script>
<h2>Edit Forum: {$this->name}</h2>
<form action="#edit" method=post>
<ul id="ForumSettings" class="nobullet">
<li><label for="name">Name:</label> <input type=text name="name" id="name" value="{$data->name}"></li>
<li><label for="groups">Groups:</label> <input type=text name="groups" id="groups" value="{$data->groups}"></li>
<li><label for="perms">Permissions:</label> {$permchecks} <input type=hidden name="permissions" value="{$data->permissions}"></li>
<li><label for="des">Description:</label>
<div id="DesTxt"><textarea name="description" id="des">{$data->description}</textarea></div></li>
<li><label for="sigs">Allow User Signatures?</label> {$sigbullets}</li>
<li><label for="html">Allow HTML in posts?</label> {$htmlbullets}</li>
<li><label for="bbc">Allow BBCode in posts?</label> {$bbcbullets}</li>
</ul>
<div id="MainOption"><input type=submit name="send" value="Edit Forum Settings">
<div id="SecondOptions"><input type=submit name="delete" value="Drop Forum">
</form>
HTML;
  return $html;
 }

 private function showForums()
 {
  if ($this->permissionCheck('r'))
  {
   $html=file_get_contents(BBBASE.'/templates/forumlist.tpl.htm');
   $top_data=$this->tbl->getData(null,'name~'.$this->name,null,1);
   $top_data=$top_data->first();
   $vars['sectiontitle']="View Forum: ".$top_data->name;
   $vars['forum']=$top_data->name;
   $vars['bbroot']=BBROOT;
   $vars['forum_url']=strtolower(urlencode($top_data->name));
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
  else
  {
   $page=new MomokoLITEError('Forbidden');
   $info['title']="Cannot Access Requested Form";
   $info['inner_body']=$page->inner_body;
   return $info;
  }
 }

 public function permissionCheck($p)
 {
  list($admin,$groups,$other)=explode(":",$this->data->permissions);
  $okay=(preg_match("/".$p."/",$other) > 0);
  if ($GLOBALS['USR']->inGroup('admin') && (preg_match("/".$p."/",$admin) > 0))
  {
   $okay=true;
  }
  if (preg_match("/".$p."/",$groups) > 0)
  {
   $glist=explode(",",$this->data->groups);
   foreach ($glist as $group)
   {
    if ($GLOBALS['USR']->inGroup($group))
    {
     $okay=true;
    }
   }
  }

  return $okay;
 }
}
