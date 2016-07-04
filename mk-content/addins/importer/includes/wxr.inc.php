<?php

function ready_data(array $file)
{
  $conf=new MomokoSiteConfig();
  $temp=$conf->basedir.$conf->tempdir;
  if (is_writable($temp))
  {
    $temp.=time()."import.xml";
    if (move_uploaded_file($file['tmp_name'],$temp))
    {
      $importable['name']=$temp;
    }
    $importable['posts']=true;
    $importable['pages']=true;
    $importable['files']=false;
    return $importable;
  }
}

function import_data($temp)
{
  $xml=file_get_contents($temp);
  unlink($temp);
  $tagarr=xmltoarray($xml);
  
  foreach ($tagarr as $tag)
  {
    if ($tag['@name'] == 'channel')
    {
      $settings=parse_set($tag['@children']);
    }
    elseif ($tag['@name'] == 'item')
    {
      $content[]=parse_con($tag['@children']);
    }
  }
  
  $set=new DataBaseTable('settings');
  $con=new DataBaseTable('content');
  
  if (!empty($settings['content']) && is_array($settings['content']))
  {
    $content=$settings['content'];
    unset($settings['conent']);
  }
  
  foreach ($settings as $skey=>$sval)
  {
    $f=$set->getData("key:`{$skey}`");
    if (is_object($f))
    {
      $srow=$f->fetch(PDO::FETCH_ASSOC);
      if (!empty($srow['key']))
      {
        $uprow['key']=$srow['key'];
        $uprow['value']=$sval;
        $upset=$set->updateData($uprow);
      }
    }
  }
  
  foreach ($content as $row)
  {
    if (isset($row['num']) //ignore num, import implies adding, user should weed out duplicates.
    {
      unset($row['num'];
    }
    //TODO some keys may need converstion.
    $upcon=$con->updateData($row);
  }
  return true;
}

function parse_set(array $tags)
{
  $set=array();
  foreach ($tags as $tag)
  {
    if ($tag['@name'] == "item")
    {
      $set['content'][]=parse_con($tag['@children']);
    }
    else
    {
      $set[$tag['@name']]=$tag['@text'];
    }
  }
  return $set;
}

function parse_con(array $tags)
{
  $con=array();
  foreach ($tags as $row)
  {
    $con[$tag['@name']]=$tag['@text'];
  }
  return $con;
}