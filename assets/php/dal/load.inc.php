<?php
/****************************************************\
|db/load.inc.php                                     |
|Momo-KO                                             |
|Directs the application to load the correct DAL     |
|driver depending on the constant set at install,    |
|also creates the interfaces used by the drivers to  |
|ensure they comply to the specifcations expected by |
|the application.                                    |
\****************************************************/

if (file_exists($GLOBALS['CFG']->basedir."/assets/etc/dal.conf.txt"))
{
  $DAL=new DALConfig();
  define("DAL_DB_DEFAULT",$DAL->default);
  define("DAL_TABLE_PRE",$DAL->table_pre);
  define("DAL_DB_FILE",$DAL->file);
  require strtolower($DAL->type).".dal.php";
  unset($DAL);
}
else
{
  trigger_error("DAL not configured! Check for a dal.conf.txt or run this app's install script!");
}

interface DALStructure
{
	function __construct($db=DAL_DB_DEFAULT);
	function connect($db);
	function create($db);
	function showTables();
	function addTable($name,$cols=null);
	function dropTable($name);
	function getSize();
	function humanReadableSize($size,$system='si',$retstring='%01.2f %s',$max=null);
}

interface DALTable
{
	function __construct($table,$db=DAL_DB_DEFAULT);
	function connect($db);
	function getFields();
	function getFieldInfo($field);
	function putField($name,$type,$len,$attr);
	function updateField($name,$type,$len,$attr);
	function removeField($name);
	function getPrimaryKey();
	function getData($what=null,$where=null,$sort=null, $limit=null, $offset=null);
	function getDataCustom($query);
	function putData($fieldarray);
	function updateData($fieldarray);
	function removeData($fieldarray);
	function escapeString($string);
	function fetch($query);
	function fetch_row();
	function fetch_assoc();
	function fetchall_assoc();
	function error();
	function close();
}

interface DALDriver
{
  function getDataTypes();
}

class DALConfig
{
  protected $cfg=array();

  public function __construct($file=null)
  {
    if (@!$file)
    {
      $file=$GLOBALS['CFG']->basedir.'/assets/etc/dal.conf.txt';
    }
    $txt=file_get_contents($file);
    if (preg_match_all("/#{(?P<key>.*?):(?P<value>.*?)}/",$txt,$properties) > 0)
    {
      $i=0;
      foreach ($properties['key'] as $key)
      {
	$configuration[$key]=$properties['value'][$i];
	++$i;
      }
    }

    if(is_array(@$configuration))
    {
      $this->cfg=$configuration;
    }
  }

  public function __get($key)
  {
    if (array_key_exists($key,$this->cfg))
    {
      return $this->cfg[$key];
    }
    else
    {
      return false;
    }
  }

  public function __set($key,$value)
  {
    if (array_key_exists($key,$this->cfg))
    {
      $this->cfg[$key]=$value;
      return true;
    }
    else
    {
      return false;
    }
  }
}

class DALResult
{
	protected $tbl_obj;
	protected $result=array();
	private $row_index=0;
	private $curr_index=0;
	private $done=false;

	public function __construct(DALTable $table)
	{
		$this->tbl_obj=$table;
	}

	public function __get($varname)
	{
		if (is_array($this->result[$this->curr_index]) && array_key_exists($varname,$this->result[$this->curr_index]))
		{
			return $this->result[$this->curr_index][$varname];
		}
		else
		{
			return false;
		}
	}

	public function first()
	{
		if(!$this->result)
		{
			$this->result[$this->row_index++]=$this->tbl_obj->fetch_assoc();
		}

		$this->curr_index=0;
		return $this;
	}

	public function next()
	{
		if($this->done)
		{
			return false;
		}

		$offset=$this->curr_index+1;
		if (!$this->result || !@$this->result[$offset])
		{
			$row=$this->tbl_obj->fetch_assoc();
			if(!$row)
			{
				$this->done=true;
				return false;
			}
			$this->result[$offset]=$row;
			++$this->row_index;
			++$this->curr_index;

			return $this;
		}
		else
		{
			++$this->curr_index;
			return $this;
		}
	}

	public function prev()
	{
		if($this->curr_index == 0)
		{
			return false;
		}
		else
		{
			--$this->curr_index;
			return $this;
		}
	}

	public function last()
	{
		if (!$this->done)
		{
			array_push($this->result, $this->tbl_obj->fetchall_assoc());
		}
		$this->done=true;
		$this->currIndex = $this->rowIndex = count($this->result) - 1;
		return $this;
	}

	public function numRows()
	{
	  $i=0;
	  while ($this->next())
	  {
	    $i++;
	  }
	  return $i;
	}

	public function toArray()
	{
	  while($row=$this->tbl_obj->fetch_assoc())
	  {
	    $this->result[$this->row_index]=$row;
	    $this->row_index++;
	  }
	  $this->curr_index=0;
	  return $this->result;
	}
}
