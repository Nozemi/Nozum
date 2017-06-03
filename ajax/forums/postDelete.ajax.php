<?php
    use ForumLib\Utilities\Config;
    use ForumLib\Database\PSQL;
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Utilities\MISC;

    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;

    use ForumLib\Users\User;

    if(empty($_SESSION)) {
        session_start();
    }

    if(empty($_SESSION['user'])) {
        echo json_encode(array(
             'message' => 'You need to be logged in to delete a post.',
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
    $post = $P->getPost($_REQUEST['id']);

    if(empty($_REQUEST['id']) || empty($post->post_html)) {
        echo json_encode(array(
             'message' => 'Couldn\'t find that post. Delete failed. (' . $_REQUEST['id'] . ')',
             'type'    => 'danger'
        )); exit;
    }

    $U = new User($SQL);

    $user = $U->getUser($_SESSION['user']['id']);

    if($post->author->id == $_SESSION['user']['id'] || $user->group->admin) {
        if($P->deletePost($_REQUEST['id'])) {
            echo json_encode(array(
                'message' => 'Post was successfully deleted',
                'type'    => 'success'
            ));
        } else {
            echo json_encode(array(
                'message' => 'Failed to delete post: ' . $P->getLastError(),
                'type'    => 'danger'
            ));
        }

        if($post->originalPost) {
            $T = new Thread($SQL);
            $T->deleteThread($post->threadId);
        }
    }