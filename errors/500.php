<?php
    use ForumLib\ThemeEngine\MainEngine;
    use ForumLib\Utilities\MISC;

    require('../inc/globals.php');

    if(isset($_COOKIE['themeName'])) {
        $TE = new MainEngine($_COOKIE['themeName'], $SQL, $Config);
    } else {
        $TE = new MainEngine(MISC::findKey('theme', $Config->config), $SQL, $Config);
    }

    echo $TE->getTemplate('500', 'errors');