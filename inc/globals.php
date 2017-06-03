<?php
    use ForumLib\Database\PSQLDetails;
    use ForumLib\Database\PSQL;
    use ForumLib\Utilities\Config;
    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;

    if(empty($_SESSION)) {
        session_start();
    }

    if(isset($_COOKIE['rmbrtkn']) && empty($_SESSION['user'])) {
        setcookie("PHPSESSID", $_COOKIE['rmbrtkn']);
        header("Refresh:0");
    }

    $autoload = 'vendor/autoload.php';

    if(!file_exists($autoload)) {
        for($i = 0; $i < 3; $i++) {
            if(!file_exists($autoload)) {
                $autoload = '../' . $autoload;
            }
        }
    }

    require($autoload);

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
        echo $ex->getMessage(); exit;
    }

    define('DEBUG', true);

    $U = new User($SQL);
    $U->setId((isset($_SESSION['user']['id'])) ? $_SESSION['user']['id'] : 0);





























    $U->sessionController();

    echo $U->getLastError();

    //$Lang = new Language('no_NB');
    //$langStrings = $Lang->getLanguage();
