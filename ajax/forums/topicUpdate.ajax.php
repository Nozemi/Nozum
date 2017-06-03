<?php
use ForumLib\Utilities\Config;
use ForumLib\Database\PSQL;
use ForumLib\Database\PSQLDetails;
use ForumLib\Utilities\MISC;

use ForumLib\Forums\Topic;

use ForumLib\Users\User;
use ForumLib\Users\Permissions;

if(empty($_SESSION)) {
    session_start();
}

if(empty($_SESSION['user'])) {
    echo json_encode(array(
        'message' => 'You need to be logged in to edit a topic.',
        'type'    => 'danger'
    )); exit;
}

if(empty($_REQUEST['title']) || empty($_REQUEST['description'])) {
    echo json_encode(array(
        'message' => 'You need a category title and description.',
        'type'    => 'danger'
    )); exit;
}

function findFile($file) {
    if(!file_exists($file)) {
        for($i = 0; $i < 3; $i++) {
            if(!file_exists($file)) {
                $file = '../' . $file;
            }
        }
    }
    return $file;
}

require(findFile('vendor/autoload.php'));

define('DEBUG', true);

$Config = new Config;
$DBDetails = new PSQLDetails(
    MISC::findKey('dbname', $Config->config),
    MISC::findKey('dbuser', $Config->config),
    MISC::findKey('dbpass', $Config->config),
    MISC::findKey('dbhost', $Config->config),
    MISC::findKey('dbpref', $Config->config)
);

try {
    $SQL = new PSQL($DBDetails->getDetails());
} catch(PDOException $ex) {
    echo json_encode(array(
        'message' => $ex->getMessage(),
        'type'    => 'danger'
    )); exit;
}

$U = new User($SQL);
$user = $U->getUser($_SESSION['user']['id']);

$T = new Topic($SQL);
$top = $T->getTopic($_REQUEST['id']);
$top->setTitle($_REQUEST['title'])
    ->setDescription($_REQUEST['description'])
    ->setOrder((isset($_REQUEST['order']) ? $_REQUEST['order'] : 0));

$PRM = new Permissions($SQL);

if($user->group->admin) {
    if($top->updateTopic()) {
        echo json_encode(array(
            'message' => $top->getLastMessage(),
            'type'   => 'success'
        ));
    } else {
        echo json_encode(array(
            'message' => $top->getLastError(),
            'type'    => 'danger'
        ));
    }
} else {
    echo json_encode(array(
        'message' => 'You don\'t have permission to edit a topic.',
        'type' => 'danger'
    ));
}