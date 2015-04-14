<?php

function apply_settings($data)
{
  $utbl=new DataBaseTable('users');
  if ($GLOBALS['USR']->name != 'guest' || $GLOBALS['USR']->name != 'root')
  {
    $data['num']=$GLOBALS['USR']->num;
  }
  $query=$utbl->getData("num:'= ".$GLOBALS['USR']->num."'",array('name','password'),null,1);
  $cur_data=$query->fetch();
  
  if (crypt($data['check'],$cur_data->password) == $cur_data->password && $data['newpass1'] == $data['newpass2'])
  {
    $data['password']=crypt($data['newpass2'],$GLOBALS['CFG']->salt);
    momoko_basic_changes($GLOBALS['USR'],"changed","their password");
    unset($data['newpass1'],$data['newpass2']);
  }
  elseif (crypt($data['check'],$cur_data->password) == $cur_data->password && $data['newpass1'] != $data['newpass2'])
  {
    trigger_error("Password not changed! New passwords do not match!",E_USER_WARNING);
    return false;
  }

  if($GLOBALS['USR']->inGroup('admin'))
  {
   $stbl=new DataBaseTable('settings');
   $site_data=$data['site'];
   $site_data['email_server']=http_build_query($site_data['email_server']);
   $site_data['email_from']=http_build_query($site_data['email_from']);

   foreach ($site_data as $key=>$value)
   {
    $new_settings['key']=$key;
    $new_settings['value']=$value;
    try
    {
      $news[]=$stbl->updateData($new_settings);
    }
    catch (Exception $err)
    {
      trigger_error($err->getMessage(),E_USER_ERROR);
    }
   }
   momoko_basic_changes($GLOBALS['USR'],'updated','Site Settings');
   $data['site']=serialize($news); //we could just empty this, but this is far more fun, if it causes to much overhead, we'll remove it!
  }
  
  if ($newu=$utbl->updateData($data))
  {
    return true;
  }
  else
  {
    return false;
  }
}
