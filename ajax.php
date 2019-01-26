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
    case 'autosave':
        $name=$config->basedir.$config->tempdir.$auth->name."-page".$_GET['p'].".bck.htm";
        if (file_put_contents($name,$_POST['html']))
        {
            echo json_encode(array('status' => 'ok','content' => $_POST['html']));
        }
        else
        {
            echo json_encode(array('status' => 'error','content' => 'Could not autosave page for recovery! unknown error writing '.$name));
        }
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
