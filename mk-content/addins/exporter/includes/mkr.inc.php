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
  $db->createBackup($temppath."data.ini");
  
  $fullname=$conf->basedir.$conf->filedir.$name.".zip";
  $arch=new ZipArchive;
  $r=$arch->open($fullname,ZipArchive::CREATE);
  if ($r === TRUE)
  {
    $arch->addFromString('MANIFEST',"version='{$conf->version}'");
    $arch->addFile($temppath."data.ini","data.mis");
    $arch->addEmptyDir($conf->filedir);
    $attachments=scandir($conf->basedir.$conf->filedir);
    foreach ($attachments as $aname)
    {
     if (!is_dir($conf->basedir.$conf->filedir.$aname))
     {
      $arch->addFile($conf->basedir.$conf->filedir.$aname,$conf->filedir.$name);
     }
    }
    $arch->close();
    rmdirr($temppath);
    
    return $name.".zip";
  }
  else
  {
    trigger_error("Could not create zip archive, file code: ".$res,E_USER_NOTICE);
    return false;
  }
}