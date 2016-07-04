<?php

function ready_data(array $file)
{
 $temp=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'];
 if (is_writeable($temp))
 {
    $temp.=time()."import.zip";
    move_uploaded_file($file['tmp_name'],$temp) or die($temp." not moved!");
    $z=new ZipArchive;
    if ($z->open($temp) === TRUE)
    {
        $importable['name']=$temp;
        if ($z->locateName('pages/map.xml'))
        {
            $importable['pages']=true;
        }
        //TODO find a way to actually check if their are files in this archive
        $importable['files']=true;
        if ($z->locateName('pages/news.xml'))
        {
            $importable['posts']=true;
        }

        return $importable;
    }
    else
    {
        //TODO Throw exception, zip file could not be opened!
        echo ($temp." Not opened!");
    }
 }
}

function import_data($archive)
{
 $extracto=$GLOBALS['SET']['basedir'].$GLOBALS['SET']['tempdir'].time().'import';
 $z=new ZipArchive;
 if ($z->open($archive))
 {
    mkdir($extracto,0777,true);
    if ($z->extractTo($extracto))
    {
        unlink($archive);
        if (!empty($_POST['pages']))
        {
            $map=xmltoarray($extracto."/pages/map.xml");
            $pages=add_pages_r($extracto."/pages/",$map);
        }
        if (!empty($_POST['files']))
        {
            $files=add_files_r($extracto);
        }
        if (!empty($_POST['posts']))
        {
            $posts=add_posts($extracto."/pages/news.xml");
        }

        rmdirr($extracto); //remove the temp folder when finished.
        return true;
    }
 }
}

function add_pages_r($folder,$xml_obj,$p=0)
{
    $order=1;
    $content=new DataBaseTable('content');

    foreach ($xml_obj as $tag)
    {
        if ($tag['@name'] == 'site' && !empty($tag['@children']) && is_array($tag['@children']))
        {

            if (!empty($tag['@attributes']['dir']))
            {
                $page['title']=$tag['@text'];
                $page['order']=$order;
                $page['type']="page";
                $page['date_created']=date("Y-m-d H:i:s");
                $page['status']="public";
                $page['author']=$GLOBALS['USR']->num;
                $page['mime_type']="text/html";
                $page['parent']=$p;
                $me=$content->putData($page);
                unset($page);

                $child=$folder.basename($tag['@attributes']['dir']);
            }
            else
            {
                $page['title']=$tag['@text'];
                $page['order']=$order;
                $page['type']="page";
                $page['date_created']=date("Y-m-d H:i:s");
                $page['status']="public";
                $page['author']=$GLOBALS['USR']->num;
                $page['mime_type']="text/html";
                $page['parent']=$p;

                if (empty($tag['@attributes']['file']))
                {
                    $pageloc=$folder."/".basename($tag['@attributes']['uri']);
                }
                else
                {
                    $pageloc=$folder."/".basename($tag['@attributes']['file']);
                }

                if ($html=file_get_contents($pageloc))
                {
                    $info=parse_page($html);
                    $page['title']=$info['title'];
                    $page['text']=$info['inner_body'];
                }
                $me=$content->putData($page);
                unset($page);

                $path=dirname($tag['@attributes']['file']);
                $child=rtrim(basename($path),"/");
                $child=$folder.$child;
            }
            $items=add_pages_r($child,$tag['@children'],$me);
        }
        else
        {
            if (empty($tag['@attributes']['file']))
            {
                $pageloc=$folder."/".basename($tag['@attributes']['uri']);
            }
            else
            {
                $pageloc=$folder."/".basename($tag['@attributes']['file']);
            }
            if ($html=file_get_contents($pageloc))
            {
                $page=parse_page($html);
                $page['order']=$order;
                $page['type']="page";
                $page['date_created']=date("Y-m-d H:i:s");
                $page['status']="public";
                $page['author']=$GLOBALS['USR']->num;
                $page['mime_type']="text/html";
                $page['parent']=$p['num'];
                $page['text']=$page['inner_body'];

                ++$order;
                $items=$content->putData($page);
                unset($page);
            }
            else
            {
                trigger_error($pageloc."not imported! The file could not be located in the archive!", E_USER_NOTICE);
            }
        }
    }
    return $items;
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

function add_posts($file)
{
    $order=1;
    $content=new DataBaseTable('content');
    $posts=xmltoarray($file);

    //TODO add posts to database
    foreach ($posts as $item)
    {
        foreach ($item['@children'] as $values) //changes array form
        {
            $temp[$values['@name']]=$values['@text'];
        }

        $post['title']=$temp['headline'];
        $post['order']=$order;
        $post['type']="post";
        $post['date_created']=date("Y-m-d H:i:s",strtotime($temp['update']));
        $post['status']="public";
        $post['author']=$GLOBALS['USR']->num;
        $post['mime_type']="text/html";
        $post['parent']=0; //Posts don't have a hiarchy so all posts are under root
        $post['text']=$temp['article'];

        $order++;

        $items[]=$content->putData($post);
    }

    return $items;
}
