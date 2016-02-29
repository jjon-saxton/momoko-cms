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
        if ($z->locateName('pages/map.xml') > 0 && $z->locateName('pages/news.xml') > 0)
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
    mkdir($extracto,0777,true);
    if ($z->extractTo($extracto))
    {
        if (!empty($_POST['pages']))
        {
            $pages=add_pages_r($extracto);
        }
        if (!empty($_POST['files']))
        {
            $files=add_files_r($extracto."/pages/",$extracto."/pages/map.xml");
        }
        if (!empty($_POST['posts']))
        {
            $posts=add_posts($extracto."/pages/news.xml");
        }

        if (is_array($pages) || is_array($files))
        {
            rmdirr($extracto."/files/"); //remove the temp folder when finished.
            return true;
        }
    }
 }
}

function add_page_r($folder,$xml)
{
    //TODO add pages recursively use XML to get order and hiarchy
}

function add_files_r($folder)
{
    //TODO add files recursively
}

function add_posts($xml)
{
    //TODO add posts from XML file
}
