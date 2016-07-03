<?php

function ready_data(array $file)
{
  $conf=new MomokoSiteConfig();
  
  $temp=$conf->basedir.$conf->tempdir;
  if (is_writable($temp))
  {
    $temp=time()."-import.zip";
    move_uploaded_file($file['tmp_name'],$temp) or die ($temp." not moved!");
    $z=new ZipArchive;
    if ($z->open($temp) === TRUE)
    {
      if ($z->locateName('MANIFEST'))
      {
        $importable['page']=true;
        $importable['files']=true;
        $importable['posts']=true;
      }
      
      return $importable;
    }
  }
}
 
function import_data($archive)
{
  $conf=new MomokoSiteConfig();
  $extractto=$conf->basedir.$conf->tempdir.time()."-import";
  $z=new ZipArchive;
  
  if ($z->open($archive) == TRUE)
  {
    mkdir($extractto,0777,true);
    if ($z->extractTo($extractto))
    {
      unlink($archive);
      $manifest=parse_ini_file($extractto."/MANIFEST");
      if ($manifest['version'] >= 2.2)
      {
        $is=parse_ini_file($extractto."/data.mis");
        $db=new DataBaseSchema();
        ($db->restoreBackup($is) && rename($extractto."/mk-content/",$conf->basedir.$conf->filedir))
        {
          rmdirr($extractto);
          return true;
        }
      }
    }
  }
}
