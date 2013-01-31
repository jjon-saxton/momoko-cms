<?php

function apply_settings($data)
{
  $utbl=new DataBaseTable(DAL_TABLE_PRE.'users',DAL_DB_DEFAULT);
  if ($GLOBALS['USR']->name != 'guest' || $GLOBALS['USR']->name != 'root')
  {
    $data['num']=$GLOBALS['USR']->num;
  }
  $query=$utbl->getData("num:'= ".$GLOBALS['USR']->num."'",array('name','password'),null,1);
  $cur_data=$query->first();
  
  if (crypt($data['check'],$cur_data->password) == $cur_data->password && $data['newpass1'] == $data['newpass2'])
  {
    $data['password']=crypt($data['newpass2'],$GLOBALS['CFG']->salt);
    unset($data['newpass1'],$data['newpass2']);
  }
  elseif (crypt($data['check'],$cur_data->password) == $cur_data->password && $data['newpass1'] != $data['newpass2'])
  {
    trigger_error("Password not changed! New passwords do not match!",E_USER_WARNING);
    return false;
  }
  
  if ($new=$utbl->updateData($data))
  {
    return true;
  }
  else
  {
    return false;
  }
}