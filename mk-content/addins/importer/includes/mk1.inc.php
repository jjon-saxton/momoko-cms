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
    $content=new DataBaseTable('content');
    foreach (scandir($folder) as $item)
    {
        if (is_dir($folder.$item) && ($item != "." || $item != ".."))
        {
            $items[]=add_files_r($folder.$item);
        }
        else
        {
            if (!file_exists($GLOBALS['SET']['baseuri'].$GLOBALS['SET']['filedir'].$item)) // all attachments are store in the filedir, there is no hiarchy there for mk2, as such we need to make file names unique to avoid overwrites
            {
                $file['title']=$item;
            }
            else
            {
                $file['title']=time().$item;
            }
            $file['type']="attachment";
            $file['status']="public";
            $file['date_created']=date("Y-m-d H:i:s");
            $file['author']=$GLOBALS['USR']->num;
            if (class_exists('finfo'))
            {
                $finfo=new finfo(FILEINFO_MIME_TYPE);
                $file['mime_type']=$finfo->file($folder.$item);
            }
            else
            {
                trigger_error("Could not determine mime type of an imported file! finfo class does not exist, so mime type set is not specific. Recommend updating PHP or installing the fileinfo PECL extension to allow for proper mime type settings.",E_USER_NOTICE);
                $finfo['mime_type']="application/octet-stream";
            }
            $file['parent']=0; //since files can only be assigned to pages and 1.x did not support his, we will set all files to root
            $file['link']=$GLOBALS['SET']['baseuri'].$GLOBALS['SET']['filedir'].$file['title'];

            if ($items[]=$content->putData($files))
            {
                rename($folder.$item,$GLOBALS['SET']['basedir'].$GLOBALS['SET']['filedir'].$file['title']);
            }
        }
    }

    return $items;
}

function add_posts($xml)
{
    //TODO add posts from XML file
}
