<?php
/********************************************\
|db/mysql.dal.php                            |
|Momo-KO Version 1.0.98b                     |
|MySQL Driver for the Database Abstraction   |
|Layer (DAL)                                 |
\********************************************/

class DataBaseStructure implements DALStructure
{
 private $db;
 private $data;
 private $errors;
 private $cfg;
 
 function __construct($db=null)
 {
  $this->cfg=new DALConfig();
  if ($db == NULL)
  {
    $this->db=$this->cfg->default;
  }
  else
  {
    $this->db=$db;
  }
 }
 
 function connect($db)
 {
  if (@!$okay)
  {
   $okay=mysql_connect($this->cfg->host,$this->cfg->user,$this->cfg->password);
  }
  
  if (@!$okay)
  {
   return false;
  }
  elseif (!mysql_select_db($db))
  {
   if ($this->create($db))
   {
    return true;
   }
   else
   {
    return false;
   }
  }
  else
  {
   return $okay;
  }
 }

 function create($db)
 {
  if (defined('INSTALL_DB_ROOT') && $this->cfg->root != NULL)
  {
    $root=mysql_connect($this->cfg->host,'root',$this->cfg->root);
    if(mysql_query("CREATE DATABASE ".$db,$root) && mysql_query("GRANT SELECT , INSERT , UPDATE , DELETE , CREATE , DROP , INDEX , ALTER ON `".$db."` . * TO '".$this->cfg->user."'@'%'",$root))
    {
      $this->connect($db);
      return true;
    }
    else
    {
      return false;
    }
  }
  else
  {
    $user=mysql_connect($this->cfg->host,$this->cfg->user,$this->cfg->password);
    if (mysql_query("CREATE DATABASE ".$db,$user))
    {
      $this->connect($db);
      return true;
    }
    else
    {
      return false;
    }
  }
 }

 function showTables()
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $result=mysql_query("SHOW TABLES");
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC))
  {
   $this->data[]=$row;
  }

  mysql_free_result($result);

  return $this->data;
 }

 public function addTable($name,$cols=null)
 {
	$connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
	if(!$cols)
	{
		$cols[]="`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
		$cols[]="`permissions` VARCHAR(50) NOT NULL";
	}
	$cols_stmt=implode(', ',$cols);
	$cols_stmt=rtrim($cols_stmt,', ');
	if(mysql_query("CREATE TABLE `".$this->db."`.`".$name."` (".$cols_stmt.") ENGINE = MyISAM;"))
	{
		return true;
	}
	else
	{
		print mysql_error();
	}
 }

 public function dropTable($name)
 {
	$connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
	if (mysql_query("DROP TABLE `".$this->db."`.`".$name."`"))
	{
		return true;
	}
	else
	{
		print mysql_error();
	}
 }
 
 function getSize()
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $result=mysql_query("SHOW TABLE STATUS");
  $dbsize=0;
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC))
  {
   $dbsize+=$row["Data_length"]+$row["Index_length"];
  }
  
  return $this->humanReadableSize($dbsize,'bi');
 }
 
 function humanReadableSize($size,$system='si',$retstring='%01.2f %s',$max=null)
 {
  // Pick units
  $systems['si']['prefix'] = array('B', 'K', 'MB', 'GB', 'TB', 'PB');
  $systems['si']['size']   = 1000;
  $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
  $systems['bi']['size']   = 1024;
  $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];

  // Max unit to display
  $depth = count($sys['prefix']) - 1;
  if ($max && false !== $d = array_search($max, $sys['prefix']))
  {
   $depth = $d;
  }
	 
  // Loop
  $i = 0;
  while ($size >= $sys['size'] && $i < $depth)
  {
   $size /= $sys['size'];
   $i++;
  }
	 
  return sprintf($retstring, $size, $sys['prefix'][$i]);
 }
}

class DataBaseTable implements DALTable
{
 private $table;             // table name
 private $db;                // database name
 private $fieldlist;         // list of fields in this table
 private $numrows;           // number of rows returned by a query
 protected $result;            // result id from a query
 private $errors;            // array of error messages
 private $cfg;		//configuration object

 function __construct($table,$db=null)
 {
  $this->cfg=new DALConfig();
  $this->table=$table;
  if ($db == NULL)
  {
    $this->db=$this->cfg->db_default;
  }
  else
  {
    $this->db=$db;
  }
  $this->rows_per_page=10;

  $fieldlist=$this->getFields();
  foreach ($fieldlist as $field)
  {
   $this->fieldlist[]=$field->name;
   if ($field->primary_key == 1)
   {
    $this->fieldlist[$field->name]=array('pkey'=>'y');
   }
   unset($fieldlist);
  }
 }

 function connect($db)
 {
  if (!isset($okaty) || empty($okay))
  {
   $okay=mysql_connect($this->cfg->host,$this->cfg->user,$this->cfg->password);
  }
  
  if (@!$okay)
  {
   return false;
  }
  elseif (!mysql_select_db($db))
  {
   return false;
  }
  else
  {
   return $okay;
  }
 }

 function getFields()
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);

  $table=$this->table;
  $result=mysql_query("SELECT * FROM ".$table." LIMIT 1",$connection) or die(mysql_error());
  $describe=mysql_query("SHOW COLUMNS FROM ".$table,$connection);
  $num=mysql_num_fields($result);
  $output=array();

  for($i = 0; $i < $num; ++$i)
  {
   $field=mysql_fetch_field($result,$i);
   $field->auto_increment = (strpos(mysql_result($describe, $i, 'Extra'), 'auto_increment') === FALSE ? 0 : 1);

   $field->definition = mysql_result($describe, $i, 'Type');
   $field->sql_type = mysql_result($describe, $i, 'Type');
   if ($field->not_null && !$field->primary_key) $field->definition .= ' NOT NULL';
   if ($field->def) $field->definition .= " DEFAULT '" . $this->escapeString($field->def) . "'";
   if ($field->auto_increment) $field->definition .= ' AUTO_INCREMENT';
   if ($key = mysql_result($describe, $i, 'Key'))
   {
    if ($field->primary_key) $field->definition .= ' PRIMARY KEY';
    else $field->definition .= ' UNIQUE KEY';
   }
   $field->len = mysql_field_len($result, $i);
   $field->sql_type = rtrim($field->sql_type,"(".$field->len.")");
   $output[$field->name] = $field;  
  }

  return $output;
 }
 
 public function getFieldInfo($field)
 {
	$fieldlist=$this->getFields();
	return $fieldlist[$field];
 }

 function putField($name,$type,$len=null,$attr=null)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  if ($len || $len != "")
  {
   $len="(".$len.")";
  }

  if ($attr || $attr != "")
  {
    $attr=" ".$attr;
  }
  else
  {
    $attr=" NOT NULL";
  }
  if (mysql_query("ALTER TABLE `".$this->table."` ADD `".$name."` ".$type.$len.$attr))
  {
    return true;
  }
  else
  {
    return false;
  }
 }
 
 function updateField($name,$type,$len=null,$attr=null)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  if ($len || $len != "")
  {
   $len="(".$len.")";
  }

  if ($attr || $attr != "")
  {
    $attr=" ".$attr;
  }
  else
  {
    $attr=" NOT NULL";
  }
  if (mysql_query("ALTER TABLE `".$this->table."` CHANGE `".$name."` `".$name."` ".$type.$len.$attr))
  {
    return true;
  }
  else
  {
    return false;
  }
 }
 
 function removeField($name)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  if (mysql_query("ALTER TABLE `".$this->table."` DROP `".$name."`"))
  {
    return true;
  }
  else
  {
    return false;
  }
 }

 public function getPrimaryKey()
 {
  foreach($this->fieldlist as $item => $value)
  {
   if (isset($item['pkey']))
   {
    return $item;
   }
  }
 }

 function getData($what=null,$where=null,$sort=null, $limit=null, $offset=null)
 {
  $this->data=array();
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  if (!is_array($what))
  {
   $what=explode(', ',$what);
  }

  if(empty($what[0]) || $what[0] == NULL)
  {
   $select_str="*";
  }
  else
  {
   foreach ($what as $quoted)
   {
    if (preg_match("/`(?P<name>.*?)`/",$quoted,$matches) <= 0)
    {
      $quote[]="`".$quoted."`";
    }
    else
    {
      $quote[]=$quoted;
    }
   }
   if (is_array($quote))
   {
    $what=$quote;
   }
   $select_str=implode(", ",$what);
   $select_str=rtrim($select_str,", ");
  }

  if(empty($where) || $where == NULL)
  {
   $where_str=null;
  }
  else
  {
    if (!is_array($where))
    {
      $where=array($where);
    }
    $where_str="WHERE ";
    foreach ($where as $statement)
    {
      if (preg_match("/=/",$statement))
      {
	$tmp=explode('=',$statement);
	$where_str.="`".$tmp[0]."`='".$tmp[1]."' AND ";
      }
      elseif (preg_match("/~/",$statement))
      {
	$tmp=explode('~',$statement);
	$where_str.="`".$tmp[0]."` LIKE '".$tmp[1]."' AND ";
      }
      else
      {
	$where_str=$where." AND ";
      }
    }
    $where_str=strrtrim($where_str," AND ");
  }

  if(empty($sort) || $sort == NULL)
  {
   $sort_str=null;
  }
  else
  {
   $sort=explode('>',$sort);
   if (@$sort[1] == "descending")
   {
    $sort[1]="DESC";
   }
   else
   {
    $sort[1]="ASC";
   }
   $sort_str="ORDER BY `".$sort[0]."` ".$sort[1];
  }

  if(empty($limit) || $limit == NULL)
  {
   $limit_str=null;
  }
  elseif (empty($offest) || $offest == NULL)
  {
   $limit_str="LIMIT ".$limit;
  }
  else
  {
    $limit_str="LIMIT ".$offset.",".$limit;
  }

  $query="SELECT ".$select_str." FROM ".$this->table." ".$where_str." ".$sort_str." ".$limit_str;

  $this->result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE);
  $this->numrows=mysql_num_rows($this->result);
  return new DALResult($this);
 }
 
 public function getDataMatch($what=null,$query=null,$keycols=null,$sort=null,$limit=null,$offset=null)
 {
  $this->data=array();
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $fieldlist=$this->fieldlist;
  
  if (!is_array($what))
  {
   $what=explode(', ',$what);
  }

  if(empty($what[0]) || $what[0] == NULL)
  {
   $select_str="*";
  }
  else
  {
   foreach ($what as $quoted)
   {
    if (preg_match("/`(?P<name>.*?)`/",$quoted,$matches) <= 0)
    {
      $quote[]="`".$quoted."`";
    }
    else
    {
      $quote[]=$quoted;
    }
   }
   if (is_array($quote))
   {
    $what=$quote;
   }
   $select_str=implode(", ",$what);
   $select_str=rtrim($select_str,", ");
  }

  if(empty($sort) || $sort == NULL)
  {
   $sort_str=null;
  }
  else
  {
   $sort=explode('>',$sort);
   if (@$sort[1] == "descending")
   {
    $sort[1]="DESC";
   }
   else
   {
    $sort[1]="ASC";
   }
   $sort_str="ORDER BY `".$sort[0]."` ".$sort[1];
  }

  if(empty($limit) || $limit == NULL)
  {
   $limit_str=null;
  }
  elseif (empty($offest) || $offest == NULL)
  {
   $limit_str="LIMIT ".$limit;
  }
  else
  {
    $limit_str="LIMIT ".$offset.",".$limit;
  }
  
  if (!empty($query) && $keycols == NULL)
  {
   $result=mysql_query("SELECT `COLUMN_NAME` FROM information_schema.STATISTICS WHERE `TABLE_SCHEMA`='".$this->db."' AND `TABLE_NAME`='".$this->table."' AND `INDEX_TYPE`='FULLTEXT'",$connection) or  trigger_error("SQL Error: ".mysql_error(), E_USER_NOTICE);
   while ($data=mysql_fetch_assoc($result))
   {
     $keycols.=$data['COLUMN_NAME'].",";
   }
   $keycols=trim($keycols,",");
  }
  elseif (is_array($keycols))
  {
    $keycols=implode(",",$keycols);
  }
  
  if (preg_match_all("/(?P<key>(?:[a-z][a-z0-9_-]*))(:)\[(?P<values>.*.?)\]/",$query,$filters) > 0)
  {
    $where=array();
    $i=0;
    foreach ($filters['key'] as $key)
    {
      $where[$key]=explode(",",$filters['values'][$i]);
      ++$i;
    }
    $query=trim(preg_replace("/(?P<key>(?:[a-z][a-z0-9_-]*))(:)\[(?P<values>.*.?)\]/","",$query)." ");
  }
  
  if (preg_match_all("/(?P<key>(?:[a-z][a-z0-9_-]*))(:)(?P<value>.(?:[a-z][a-z0-9_-]*))/",$query,$filters) > 0)
  {
    $where=array();
    $i=0;
    foreach ($filters['key'] as $key)
    {
      $where[$key]=$filters['value'][$i];
      ++$i;
    }
    $query=trim(preg_replace("/(?P<key>(?:[a-z][a-z0-9_-]*))(:)(?P<value>.(?:[a-z][a-z0-9_-]*))/","",$query)." ");
  }
  
  if (empty($query))
  {
    $where_str="WHERE";
  }
  else
  {
    $where_str="WHERE MATCH (".$keycols.") AGAINST ('".$query."')";
  }
  
  foreach($where as $field => $value)
  {
   if (!in_array($field,$fieldlist))
   {
    unset($where[$field]);
   }
  }
  
  if (@is_array($where))
  {
    foreach ($where as $col=>$value)
    {
      if (in_array($col,$fieldlist))
      {
       if (is_array($value))
       {
	 $where_group=null;
	 foreach ($value as $item)
	 {
	   $where_group.="`".$col."` LIKE '".$item."' OR";
	 }
	 $where_str.=" AND (".trim($where_group," OR").")";
       }
       else
       {
         $where_str.=" AND `".$col."` LIKE '".$value."'";
       }
      }
    }
  }
  
  $where_str=preg_replace("/WHERE AND/","WHERE",$where_str);

  $query="SELECT ".$select_str." FROM ".$this->table." ".$where_str." ".$sort_str." ".$limit_str;
  var_dump($query);

  $this->result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE);
  $this->numrows=mysql_num_rows($this->result);
  return new DALResult($this);
 }

 public function getDataCustom($query)
 {
  $this->data=array();
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $this->result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE);
  return new DALResult($this);
 }

 function putData($fieldarray)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $fieldlist=$this->fieldlist;

  foreach($fieldarray as $field => $value)
  {
   if (!in_array($field,$fieldlist))
   {
    unset($fieldarray[$field]);
   }
  }

  $query="INSERT INTO ".$this->table." SET ";
  foreach($fieldarray as $item => $value)
  {
   $query.="`".$item."`='".$this->escapeString($value)."', ";
  }
  $query=rtrim($query, ", ");

  if($result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE))
  {
    if (!mysql_insert_id())
    {
      return true;
    }
    else
    {
      return mysql_insert_id();
    }
  }
  else
  {
   if (mysql_errno() != 0)
   {
    if (mysql_errno() == 1062)
    {
     $this->errors[]="A record already exists with this ID!";
    }
   }
  }
 }

 function updateData($fieldarray)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $fieldlist=$this->fieldlist;
  $where=null;
  $update=null;

  foreach($fieldarray as $field => $value)
  {
   if (!in_array($field,$fieldlist))
   {
    unset($fieldarray[$field]);
   }
  }
 
  foreach($fieldarray as $item => $value)
  {
   if (isset($fieldlist[$item]['pkey']))
   {
    $where.="`".$item."`='".$this->escapeString($value)."' AND ";
    $key=$value;
   }
   else
   {
    $update.="`".$item."`='".$this->escapeString($value)."', ";
   }
  }
  $where=rtrim($where, " AND ");
  $update=rtrim($update, ", ");
  $query="UPDATE ".$this->table." SET ".$update." WHERE ".$where;

  if ($result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE))
  {
      return $key;
  }
  else
  {
      return false;
  }
 }

 function removeData($fieldarray)
 {
  $connection=$this->connect($this->db) or trigger_error("SQL: ".mysql_error(), E_USER_ERROR);
  $fieldlist=$this->fieldlist;
  $where=null;

  foreach($fieldarray as $field => $value)
  {
   if (!in_array($field,$fieldlist))
   {
    unset($fieldarray[$field]);
   }
  }
 
  foreach($fieldarray as $item => $value)
  {
   if (isset($fieldlist[$item]['pkey']))
   {
    $where.=$item."='".$this->escapeString($value)."' AND ";
   }
  }
  $where=rtrim($where, " AND ");
  $query="DELETE FROM ".$this->table." WHERE ".$where;

  if($result=mysql_query($query,$connection) or trigger_error("SQL: ".mysql_error()." in ".$query, E_USER_NOTICE))
  {
	return true;
  }
 }

 function escapeString($string)
 {
  $string=mysql_real_escape_string($string);
  return $string;
 }

 public function fetch($query) //provided for special cases where query string cannot or should not be built using DALTable::GetData()
 {
	$this->result=mysql_query($query);
	return new DALResult ($this);
 }

 public function fetch_row()
 {
	if (!$this->result)
	{
		throw new Exception("Query not executed");
	}
	return mysel_fetch_row($this->result);
 }

 public function fetch_assoc()
 {
	return mysql_fetch_assoc($this->result);
 }

 public function fetchall_assoc()
 {
	$retval=array();
	while ($row=$this->fetch_assoc())
	{
		$retval[]= $row;
	}
	return $retval;
 }

 function error()
 {
  $message=mysql_error();
  return $message;
 }

 function close()
 {
  if (mysql_close())
  {
   return true;
  }
  else
  {
   return false;
  }
 }
}

class DataBaseDriver implements DALDriver
{
  private $capabilities;
  private $options;
  private $datatypes;

  public function __construct()
  {
    $this->capabilities['binary_storage']=true;
    $this->capabilities['binary_retrival']=true;

    $this->datatypes=array(
      'numeric'=>array('TINYINT','SMALLINT','MEDIUMINT','INT','BIGINT','FLOAT','DOUBLE','DECIMAL','REAL','BIT','BOOLEAN','SERIAL'),
      'date/time'=>array('DATE','DATETIME','TIMESTAMP','TIME','YEAR'),
      'string'=>array('CHAR','VARCHAR','TINYTEXT','TEXT','MEDIUMTEXT','LONGTEXT','BINARY','VARBINARY','TINYBLOB','MEDIUMBLOB','BLOB','LONGBLOB','ENUM','SET'),
      'spatial'=>array('GEOMETRY','POINT','LINESTRING','POLYGON','MULTIPOINT','MULTILINESTRING','MULTIPOLYGON','GEOMETRYCOLLECTION')
    );
  }

  public function __get($var)
  {
    if (array_key_exists($this->capabilities[$var]))
    {
      return $this->capabilities[$var];
    }
    elseif (array_key_exists($this->options[$var]))
    {
      return $this->options[$var];
    }
    else
    {
      return false;
    }
  }

  public function getDataTypes()
  {
    return $this->datatypes;
  }
}