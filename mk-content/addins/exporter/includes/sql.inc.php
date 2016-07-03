<?php

function ready_data($name=null)
{
  $conf=new MomokoSiteConfig();
  if (empty($name))
  {
    $name="mk-export-".time();
  }
  
  $db=new DataBaseSchema();
  $file=$conf->basedir.$conf->filedir.$name.".sql";
  
  $db->createBackup($file,'sql');
  
  return $name.".sql";
}