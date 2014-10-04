<?php
require dirname(__FILE__).'/core/database.inc.php';

$table=new DataBaseTable('settings');

$test=$table->getData("key:'version'");

while ($row=$test->fetch(PDO::FETCH_OBJ))
{
 echo($row->value);
}
