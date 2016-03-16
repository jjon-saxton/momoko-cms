<?php
require dirname(__FILE__)."/mk-core/common.inc.php";
require dirname(__FILE__)."/mk-core/content.inc.php";

$child=new MomokoError($_GET['name']);

$tpl=new MomokoTemplate('/');
echo $tpl->toHTML($child);
