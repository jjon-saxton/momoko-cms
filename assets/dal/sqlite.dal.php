<?php
/********************************************\
|db/sqlite.dal.php                           |
|Momo-KO Version 0.1a                        |
|SQLite2 Driver for the Database Abstraction |
|Layer (DAL)                                 |
\********************************************/

class DataBaseStructure implements DALStructure
{
  private $db;
  private $data;
  private $errors;
 
  function __construct($db=DAL_DB_DEFAULT)
  {
    $this->db=$db;
  }

  function connect($db)
  {
    return new SQLiteDatabase($db);
  }

  function showTables()
  {
    $connection=$this->connect($this->db);
  }
}