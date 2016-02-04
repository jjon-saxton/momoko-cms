<?php
/* Quick get, put, and update action to be performed by AJAX */

header("Content-type: text/json");
require_once dirname(__FILE__).'/mk-core/common.inc.php';
if (!empty($_GET['table']))
{
    $table=new DataBaseTable($_GET['table']);
}

switch ($_GET['action'])
{
    case 'fetch_files':
        echo json_encode(fetch_files($_GET['dir'],$_GET['limit']));
        break;
    case 'put':
        $new=$table->putData($_POST);
        echo json_encode($new);
        break;
    case 'update':
        $changed=$table->updateData($_POST);
        echo json_encode($changed);
        break;
    case 'get':
    default:
        $query=$table->getData($_GET['q'],null,$_GET['sort']);
        echo json_encode($query->fetchALL(PDO::FETCH_ASSOC));
        break;
}

?>
