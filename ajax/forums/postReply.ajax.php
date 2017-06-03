<?php
    use ForumLib\Utilities\Config;
    use ForumLib\Database\PSQL;
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Utilities\MISC;
    use ForumLib\Forums\Post;

    if(empty($_SESSION)) {
        session_start();
    }

    if(empty($_SESSION['user'])) {
        echo json_encode(array(
             'message' => 'You need to be logged in to post a reply.',
             'type'    => 'danger'
        )); exit;
    }

    if(empty($_REQUEST['reply']) || empty($_REQUEST['threadId']) || $_REQUEST['reply'] == '<p><br></p>') {
        echo json_encode(array(
             'message' => 'You need to fill in the reply field in order to reply. If you have done so, please report this to an administrator.',
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

    $P = new Post($SQL);
    $P->setThreadId($_REQUEST['threadId'])
        ->setHTML($_REQUEST['reply'])
        ->setAuthor($_SESSION['user']['id']);

    if($P->createPost()) {
        echo json_encode(array(
             'message' => $P->getLastMessage(),
             'type'    => 'success'
        )); exit;
    } else {
        echo json_encode(array(
             'message' => $P->getLastError(),
             'type'    => 'danger'
        )); exit;
    }