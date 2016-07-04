<?php

function ready_data(array $file)
{
  return array('pages'=>true,'posts'=>true,'files'=>false);
}

function import_data($file)
{
  $lines=file($file);
  $db=new DataBaseSchema();
  
  foreach ($lines as $is)
  {
   $r[]=$db->query($is);
  }
  
  if (!empty($r) && is_array($r))
  {
    return true;
  }
  else
  {
    return false;
  }
}