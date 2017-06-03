<?php
    use ForumLib\Utilities\Config;
    use ForumLib\Database\PSQL;
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Utilities\MISC;
    use ForumLib\Users\User;

    use ReCaptcha\ReCaptcha;

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
    $pKey = MISC::findKey('captchaPrivateKey', $Config->config);

    if(empty($_SESSION)) {
        session_start();
    }

    if(!empty($_SESSION['user'])) {
        echo json_encode(array(
             'message' => 'You\'re already logged in.',
             'type'    => 'danger'
         )); exit;
    }

    if(empty($_REQUEST)) {
        echo json_encode(array(
         'message' => 'You need to supply your account details in order to sign up.',
         'type'    => 'danger'
        )); exit;
    }

    if(empty($_REQUEST['username'])) {
        echo json_encode(array(
            'message' => 'You need to supply a username in order to sign up.',
            'type'    => 'danger'
        )); exit;
    }

    $DBDetails = new PSQLDetails(
        MISC::findKey('dbname', $Config->config),
        MISC::findKey('dbuser', $Config->config),
        MISC::findKey('dbpass', $Config->config),
        MISC::findKey('dbhost', $Config->config),
        MISC::findKey('dbpref', $Config->config)
    );

    $cap = new ReCaptcha($pKey);
    $rsp = $cap->verify($_REQUEST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

    if(!$rsp->isSuccess()) {
        echo json_encode(array(
             'message' => 'Failed to verify your humanity.',
             'type' => 'danger'
        )); exit;
    }

    try {
        $SQL = new PSQL($DBDetails->getDetails());
    } catch(PDOException $ex) {
        echo json_encode(array(
             'message' => $ex->getMessage(),
             'type'    => 'danger'
        )); exit;
    }

    $U = new User($SQL);
    $usr = $U->setUsername($_REQUEST['username'])
        ->setEmail($_REQUEST['email']);
    $usr->setPassword($_REQUEST['password'], $_REQUEST['password_cfrm']);

    if(empty($usr->getErrors())) {
        if($usr->register()) {
            echo json_encode(array(
                'message'   => $usr->getLastMessage(),
                'type'      => 'success'
            ));
        } else {
            echo json_encode(array(
                'message'   => $usr->getLastError(),
                'type'      => 'danger'
            ));
        }
    } else {
        echo json_encode(array(
            'message'   => $usr->getLastError(),
            'type'      => 'danger'
        ));
    }