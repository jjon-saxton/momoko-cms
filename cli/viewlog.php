#!/usr/bin/php
<?php
require dirname(__FILE__).'/interface.inc.php';

if ($argv[0] && pathinfo($argv[0],PATHINFO_EXTENSION) != 'php')
{
 $argstr=$argv[0];
}
elseif (@$argv[1])
{
 $argstr=$argv[1];
}

$log=new DataBaseTable('log');
$q=$log->getData();

while ($row=$q->fetch(PDO::FETCH_ASSOC))
{
 $rowtxt=null; //re-initialize $rowtext
 foreach ($row as $var=>$val)
 {
  if ($var != "num")
  {
   $rowtxt.=$val."|";
  }
 }
 fwrite(STDOUT,rtrim($rowtxt,"|"));
}
fwrite(STDOUT,"\n");
if (!empty($argstr) && $argstr == "-purge")
{
 $log->emptyData();
}

?>
