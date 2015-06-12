<?php

class DataBaseSchema extends PDO
{
 private $ini;

 public function __construct($schema=null, $file='database.ini')
 {
  if (!$settings=parse_ini_file($file,TRUE)) throw new exception('Unable to open '.$file.'!');

  $dns=$settings['database']['driver'] .
  ':host='.$settings['database']['host'] .
  ((!empty($settings['database']['port'])) ? (';port='.$settings['database']['port']) : '') .
  ((!empty($schema)) ? (';dbname='.$schema) : ';dbname='.$settings['schema']['name']);

   parent::__construct($dns, $settings['schema']['username'],$settings['schema']['password']);

  $this->ini=$settings;
  $this->ini['name']=$file;
  return $settings;
 }

 public function addTable($table,array $cols=null)
 {
  if(!$cols)
  {
   $cols[]="`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
   $cols[]="`name` VARCHAR(50) NOT NULL";
  }
  $cols_stmt=implode(', ',$cols);
  $cols_stmt=rtrim($cols_stmt,', ');
  if ($this->query("CREATE TABLE `".$this->ini['schema']['tableprefix'].$table."` (".$cols_stmt.") ENGINE = MyISAM"))
  {
   return new DataBaseTable($table,null,$this->ini['name']);
  }
  else
  {
   throw new Exception("New Table Not Created!");
  }
 }

 public function dropTable($table)
 {
  if ($this->query("DROP TABLE `".$this->ini['schema']['tableprefix'].$table."`"))
  {
   return true;
  }
  else
  {
   return false;
  }
 }
}

class DataBaseTable extends DataBaseSchema
{
 public $table;
 protected $indices;
 protected $results;

 public function __construct($table,$schema=null,$file='database.ini')
 {
  $settings=parent::__construct($schema,$file);
  $this->table=$settings['schema']['tableprefix'].$table;

  $fieldlist=$this->getFields();
  foreach ($fieldlist as $field)
  {
   $this->fieldlist[]=$field->Field;
   switch ($field->Key)
   {
    case 'PRI':
    $this->indices['primary']=$field->Field;
    break;
   }
  }
  unset($fieldlist);
 }

 public function getFields()
 {
  $describe=$this->query("SHOW COLUMNS FROM ".$this->table);

  return $describe->fetchAll(PDO::FETCH_OBJ);
 }

 public function getData($q=null,array $cols=null,$sort=null,$limit=0,$offset=0, array $keycols=null)
 {
  $fieldlist=$this->fieldlist;
  if (!empty($cols))
  {
   $collist=implode(", ",$cols);
   $collist=rtrim($collist,", ");
  }
  else
  {
   $collist='*';
  }

  $sql="SELECT {$collist} FROM ".$this->table;

  if (preg_match_all("/(?P<key>(?:[a-z][a-z0-9_]*))(:)'(?P<values>.*?)'/is",$q,$filters) > 0)
  {
   $where=array();
   $i=0;
   foreach ($filters['key'] as $key)
   {
    $where[$key]=explode("|",$filters['values'][$i]);
    ++$i;
   }
   $q=trim(preg_replace("/(?P<key>(?:[a-z][a-z0-9_]*))(:)'(?P<values>.*?)'/is","",$q)." ");
  }

  $q=rtrim($q,",");
  if (empty($q))
  {
   $where_sql=" WHERE";
  }
  else
  {
   $where_sql=" WHERE MATCH (".$keycols." AGAINST ('".$q."')";
  }

  if (@is_array($where))
  {
   foreach ($where as $col=>$value)
   {
    if (in_array($col,$fieldlist))
    {
     $where_group=null;
     foreach ($value as $item)
     {
      if (preg_match("/^(<)(>)( )(?P<date1>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2}))( )(?P<date2>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2}))/",$item,$compare) > 0)
      {
       $where_group.="`".$col."` BETWEEN '".$compare['date1']."' AND '".$compare['date2']."'";
      }
      elseif (preg_match("/(<)(>)( )(?P<digit1>\\d+)( )(?P<digit2>\\d+)/",$item,$compare) > 0) // digits between
      {
       $where_group.="`".$col."` BETWEEN ".$compare['digit1']." AND ".$compare['digit2'];
      }
      elseif (preg_match("/^(?P<operator>.*)( )(?P<date>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2})$)/",$item,$compare) > 0) //compare dates
      {
       $where_group.="`".$col."` ".$compare['operator']." '".$compare['date']."'";
      }
      elseif (preg_match("/(?P<operator>.*)( )(?P<digit>\\d+)/",$item,$compare) > 0) //compare digits
      {
       $where_group.="`".$col."` ".$compare['operator']." ".$compare['digit'];
      }
      else
      {
       $where_group.="`".$col."` LIKE '".$item."'";
      }
     }
     $wheres[]="(".$where_group.")";
    }
   }
   $where_sql.=" ".implode($wheres," AND ");
   $where_sql=preg_replace("/WHERE AND/","WHERE",$where_sql); //Prevents illegal WHERE AND combo
   if ($where_sql != "WHERE") //add the where string as long as it appears legal
   {
    $sql.=" ".$where_sql;
   }
  }
  
  if ($sort)
  {
   if (preg_match("/(?P<col>.*)( )(?P<operator><|>)/",$sort,$orderby))
   {
    if ($orderby['operator'] == '>')
    {
     $sql.=" ORDER BY `".$orderby['col']."` DESC";
    }
    else
    {
     $sql.=" ORDER BY `".$orderby['col']."` ASC";
    }
   }
   else
   {
    $sql.=" ORDER BY `".$sort."` ASC";
   }
  }
  if ($limit > 0)
  {
   $sql.=" LIMIT ".$limit;
  }
  if ($offset > 0)
  {
   $sql.=" OFFSET ".$offset;
  }

  try
  {
   $result=$this->query($sql);
  }
  catch (Exception $err)
  {
   trigger_error("SQL Server Error: ".$err->getMessage(),E_USER_ERROR);
  }

  return $result;
 }

 public function updateData(array $fieldarray)
 {
  $update=array();

  $q="UPDATE `{$this->table}` SET ";
  foreach ($fieldarray as $field=>$value)
  {
   if (in_array($field,$this->fieldlist))
   {
    if ($this->indices['primary'] == $field)
    {
<<<<<<< HEAD:core/database.inc.php
     $where_str="WHERE `{$field}`='{$value}'";
=======
     $where="WHERE `{$field}`='{$value}'";
>>>>>>> 2.0-STABLE:mk-core/database.inc.php
     $key=$value;
    }
    else
    {
     $q.=" `{$field}`=:{$field}, ";
     $update[":{$field}"]=$value;
    }
   }
   else
   {
    unset($fieldarray[$field]);
   }
  }
<<<<<<< HEAD:core/database.inc.php
  $query=$this->prepare($q." ".$where_str);
  try
=======
  $q=rtrim($q,", ").' '.$where;
  $query=$this->prepare($q);

  $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  if ($query->execute($update))
>>>>>>> 2.0-STABLE:mk-core/database.inc.php
  {
   $query->execute($update);
   return $key;
  }
  catch (Exception $err)
  {
<<<<<<< HEAD:core/database.inc.php
   trigger_error("SQL Error: ".$err->getMessage(),E_USER_WARNING);
=======
   throw new Exception("Could not update data!");
>>>>>>> 2.0-STABLE:mk-core/database.inc.php
   return false;
  }
 }

 public function deleteData(array $fieldarray)
 {
  $q="DELETE FROM {$this->table}";

  foreach ($fieldarray as $field=>$value)
  {
   if (in_array($field,$this->fieldlist))
   {
    if ($this->indices['primary'] == $field)
    {
     $q.=" WHERE `{$field}`='{$value}'";
    }
   }
  }
  $query=$this->prepare($q);

  $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  if ($query->execute())
  {
   return true;
  }
  else
  {
   throw new Exception("Could not delete data");
   return false;
  }
 }

 public function putData(array $fieldarray)
 {
  $q="INSERT INTO ".$this->table;
  $cols=null;
  $placeholders=null;
  $values=array();

  foreach ($fieldarray as $field=>$value)
  {
   if (in_array($field,$this->fieldlist))
   {
    $cols.="`".$field."`,";
    $placeholders.=":".$field.",";
    $values[':'.$field]=$value;
   } 
  }
  $cols=rtrim($cols,",");
  $placeholders=rtrim($placeholders,",");

  $q.=" ({$cols}) VALUES ({$placeholders})";
  $query=$this->prepare($q);

 $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 if ($query->execute($values))
  {
   $id=$this->lastInsertID();
   if ($id > 0)
   {
    return $id;
   }
   else
   {
    return true;
   }
  }
  else
  {
   throw new Exception("Could not add data");
  }
 }
}
