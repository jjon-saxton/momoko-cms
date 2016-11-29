<?php

function ready_data(array $file)
{
  $conf=new MomokoSiteConfig();
  $temp=$conf->basedir.$conf->tempdir;
  if (is_writable($temp))
  {
    $temp.=time()."-import.sql";
    move_uploaded_file($file['tmp_name'],$temp);
    $importable['name']=$temp;
  }
  $importable['pages']=true;
  $importable['posts']=true;
  $importable['files']=false;
  return $importable;
}

function import_data($file)
{
  $lines=file($file);
  $db=new DataBaseSchema();
  
  foreach ($lines as $is)
  {
   $r[]=$db->query($is);
  }
  
  if (!empty($r) && is_array($r))
  {
    return true;
  }
  else
  {
    return false;
  }
}