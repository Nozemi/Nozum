<?php
    use ForumLib\Utilities\Config;
    use ForumLib\Database\PSQL;
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Utilities\MISC;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    use ForumLib\Users\User;
    use ForumLib\Users\Permissions;

    if(empty($_SESSION)) {
        session_start();
    }

    if(empty($_SESSION['user'])) {
        echo json_encode(array(
             'message' => 'You need to be logged in to post a reply.',
             'type'    => 'danger'
        )); exit;
    }

    if(empty($_REQUEST['title']) || empty($_REQUEST['content']) || $_REQUEST['content'] == '<p><br></p>' || empty($_REQUEST['topicId'])) {
        echo json_encode(array(
             'message' => 'You need a thread title and some content in order to post a new thread.',
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

    $T = new Thread($SQL);
    $P = new Post($SQL);

    $F = new Topic($SQL);
    $top = $F->getTopic($_REQUEST['topicId']);

    $U = new User($SQL);
    $user = $U->getUser($_SESSION['user']['id']);

    $PRM = new Permissions($SQL);

    if($PRM->checkPermissions($user, $top)['post']) {
        if(empty($T->getLastError())) {
            $P->setHTML($_REQUEST['content'])
                ->setAuthor($_SESSION['user']['id']);
            $T->setTitle($_REQUEST['title'])
                ->setAuthor($_SESSION['user']['id'])
                ->setTopicId($_REQUEST['topicId'])
                ->createThread($P);

            $C = new Category($SQL);
            $cat = $C->getCategory($top->categoryId);

            $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $T->getURL() . '/';

            echo json_encode(array(
                'message' => 'Thread was successfully posted. You\'re about to be taken to your thread.',
                'type' => 'success',
                'threadUrl' => $url
            ));
        }
    } else {
        echo json_encode(array(
            'perms' => $PRM->checkPermissions($user, $top),
            'message' => 'You don\'t have permission to post here.',
            'type' => 'danger'
        ));
    }