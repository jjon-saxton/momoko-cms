<?php

function ready_data(array $file)
{
 //TODO actually parse the data into the temporary folder and get it ready to add to MK2's database
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
            $pages=add_pages_r($extracto,$extracto."/pages/map.xml");
        }
        if (!empty($_POST['files']))
        {
            $files=add_files_r($extracto."/pages/");
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

function add_pages_r($folder,$map)
{
    $order=1;
    $content=new DataBaseTable('content');
    $map=xmltoarray($map);

    $p_query=$content->getData("name: '".basename($folder)."'",array('num'));
    if ($p_query->rowCount() > 0)
    {
        $parent=$p_query->fetch(PDO::FETCH_ARRAY);
        $p=$parent['num'];
    }
    else
    {
        $p=0; //Assume root
    }
    foreach ($map as $tag)
    {
        if (!empty($tag['@children']) && is_array($tag['children']))
        {
            $child=$folder.basename($tag['@attribute']['href']);
            $items[]=add_pages_r($child,$tag['children']);
        }
        else
        {
            $pageloc=$folder.basename($tag['@attribute']['href']);
            $html=file_get_contents($pageloc);
            $page=parse_page($html);
            $page['order']=$order;
            $page['type']="page";
            $page['date_created']=date("Y-m-d H:i:s");
            $page['status']="public";
            $page['author']=$GLOBALS['USR']->num;
            $page['mime_type']="text/html";
            $page['parent']=$p;
            $page['text']=$page['inner_body'];

            $order++;

            $items=$content->putData($page);
            unlink($pageloc);
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

function add_posts($xml)
{
    $order=1;
    $content=new DataBaseTable('content');
    $posts=xmltoarray($xml);

    //TODO add posts to database
    foreach ($posts as $item)
    {
        foreach ($item['@children'] as $values) //changes array form
        {
            $temp[$values['@name']]=$values['@text'];
        }
        $article[]=$temp;

        $post['title']=$article['headline'];
        $post['order']=$order;
        $post['type']="post";
        $post['date_created']=date("Y-m-d H:i:s",strtotime($article['update']));
        $post['status']="public";
        $post['author']=$GLOBALS['USR']->num;
        $post['mime_type']="text/html";
        $post['parent']=0; //Posts don't have a hiarchy so all posts are under root
        $post['text']=$article['article'];

        $order++;

        $items[]=$content->putData($post);
    }

    return $items;
}
