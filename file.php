<?php
require_once dirname(__FILE__).'/assets/core/common.inc.php';
require_once $GLOBALS['CFG']->basedir.'/assets/core/ximager.inc.php';

if (@$_SERVER['PATH_INFO'] && (pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION) != 'html' || pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSTION) != 'php'))
{
 $img=new MomokoImage($GLOBALS['CFG']->datadir.$_SERVER['PATH_INFO']);
 if ($img->isImage())
 {
  if (!empty($_GET['w']) && empty($_GET['h']))
  {
   $img->resizeToWidth($_GET['w']);
  }
  elseif (empty($_GET['w']) && !empty($_GET['h']))
  {
   $img->resizeToHeight($_GET['h']);
  }
  elseif (!empty($_GET['w']) && !empty($_GET['h']))
  {
   $img->resize($_GET['w'],$_GET['h']);
  }
  elseif (!empty($_GET['scale']))
  {
   $img->scale($_GET['scale']);
  }
  header("Content-type: image/png");
  if (@$_GET['download'] == TRUE)
  {
   header('Content-Disposition: attachment; filename="'.pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME).'.png"');
  }
  else
  {
   header('Content-Disposition: inline; filename="'.pathinfo($_SERVER['PATH_INFO'],PATHINFO_FILENAME).'.png"');
  }
  $data=$img->get();
  unset($img);
 }
 else
 {
  $filename=$GLOBALS['CFG']->datadir.$_SERVER['PATH_INFO'];
  $finfo=new finfo(FILEINFO_MIME);
  $mime=$finfo->file($filename);
  if (@$_GET['download'] == TRUE)
  {
   header('Content-Disposition: attachment');
  }
  header("Content-type: ".$mime);
  $data=file_get_contents($filename);
 }
}

echo($data);

?>
