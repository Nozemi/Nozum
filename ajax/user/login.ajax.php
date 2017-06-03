<?php
    use ForumLib\Utilities\Config;
    use ForumLib\Database\PSQL;
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Utilities\MISC;
    use ForumLib\Users\User;

    if(empty($_SESSION)) {
        session_start();
    }

    if(!empty($_SESSION['user'])) {
        echo json_encode(array(
            'message' => 'You\'re already logged in.',
            'type'    => 'danger'
        )); exit;
    }

    if(empty($_REQUEST)
        || empty($_REQUEST['username'])
        || empty($_REQUEST['password'])) {
        echo json_encode(array(
            'message' => 'You need to supply a username and password in order to login.',
            'type'    => 'danger'
        )); exit;
    }

    if(isset($_COOKIE['devkey']) != 'g4r39poiuhtyo8934hrgo8it5h907gh3tg357gpgh7r3458') {
        echo json_encode(array(
            'message' => 'You don\'t have permission to perform this action right now.',
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

    $U = new User($SQL);
    $U->setUsername($_REQUEST['username'])
      ->setPassword($_REQUEST['password'], null, true);

    $user = $U->login();

    if($user) {
        $_SESSION['user'] = array(
            'id'        => $user->id,
            'username'  => $user->username,
            'avatar'    => $user->avatar
        );

        if($_REQUEST['rememberMe']) {
            setcookie("rmbrtkn", session_id(), (time()+(60*60*24*128)), '/','eldrios.com');
        }

        echo json_encode(array(
            'message' => $U->getLastMessage(),
            'type'    => 'success'
        ));
    } else {
        echo json_encode(array(
            'message' => $U->getLastError(),
            'type'    => 'danger'
        ));
    }
