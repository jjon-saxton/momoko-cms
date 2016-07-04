<?php

function ready_data(array $file)
{
  $conf=new MomokoSiteConfig();
  
  $temp=$conf->basedir.$conf->tempdir;
  if (is_writable($temp))
  {
    $temp.=time()."-import.rss";
    if (move_uploaded_file($file['tmp_name'],$temp);
    {
      $importable['name']=$temp;
    }
    $importable['pages']=true;
    $importable['posts']=true;
    $importable['files']=false;
  }
  
  return $importable;
}

function import_data($temp)
{
  $xmlarr=xmltoarray($temp);
  unlink($temp);
  
  foreach ($xmlarr as $tag)
  {
    if ($tag['@name'] == "channel")
    {
      $settings=parse_set($tag['@children']);
    }
    elseif ($tag['@name'] == "item")
    {
      $content[]=parse_con($tag['@children']);
    }
  }
  
  $set=new DataBaseTable('settings');
  $con=new DataBaseTable('content');
  
  foreach ($settings as $skey=>$sval)
  {
    $srow['key']=$skey;
    $srow['value']=$sval;
    $newset[]=$set->updateData($srow);
  }
  
  foreach ($content as $row)
  {
    unset($row['num']) //Remove num to avoid putData errors
    $newcon[]=$con->putData($row);
  }
  
  if (!empty($newset) || !empty($newcon))
  {
    return true;
  }
  else
  {
    return false;
  }
}

function parse_set(array $xmlarr)
{
}

function parse_con(array $xmlarr)
{
}

