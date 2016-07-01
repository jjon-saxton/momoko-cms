<?php

function ready_data($name=null)
{
  $conf=new MomokoSiteConfig();
  if ($name == NULL)
  {
    $name="mk-export-".time();
  }
  
  $db=new DataBaseSchema();
  $temppath=$conf->basedir.$conf->tempdir.$name."/";
  mkdir($temppath);
  //$db->createBackup($temppath."data.sql"); TODO backup method must be implemented
  
  $fullname=$conf->basedir.$conf->filedir.$name.".zip";
  $arch=new ZipArchive;
  $r=$arch->open($fullname,ZipArchive::CREATE);
  if ($r === TRUE)
  {
    $arch->addFromString('MANIFEST',"version='{$conf->version}'");
    //$arch->addFile($temppath."data.sql","data.sql");
    rmdirr($temppath);
    $arch->addEmptyDir($conf->filedir);
    $attachments=scandir($conf->basedir.$conf->filedir);
    foreach ($attachments as $name)
    {
     if (!is_dir($conf->basedir.$conf->filedir.$name))
     {
      $arch->addFile($conf->basedir.$conf->filedir.$name,$conf->filedir.$name);
     }
    }
    $arch->close();
    
    return $name.".zip";
  }
  else
  {
    trigger_error("Could not create zip archive, file code: ".$res,E_USER_NOTICE);
    return false;
  }
}