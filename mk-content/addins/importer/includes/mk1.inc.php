<?php

function ready_data(array $file)
{
 //TODO actually parse the data into the temporary folder and get it ready to add to MK2's database
 $temp=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'];
 if (is_writeable($temp))
 {
    $temp.="import-".time().".zip";
    move_uploaded_file($file['tempname'],$temp);
    $z=new ZipArchive;
    if ($z->open($temp) === TRUE)
    {
        if ($z->locateName('map.xml') > 0 && $z->locateName('news.xml') > 0)
        {
            return $temp;
        }
        else
        {
            unlink($temp); //clean-up
            //TODO throw exception, arhive not correct
        }
    }
    else
    {
        //TODO Throw exception, zip file could not be opened!
    }
 }
}

function import_data($archive)
{
 var_dump($archive);
 $extracto=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'].'import-'.time();
 $z=new ZipArchive;
 if ($z->open($archive))
 {
    mkdir($extracto,0777,true);)
    if ($z->extractTo($extracto))
    {
        //TODO read files and add them to the database, starting with pages.
        rmdirr($extracto); //remove the temp folder when finished.
        return true;
    }
 }
}
