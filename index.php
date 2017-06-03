<?php
    use ForumLib\ThemeEngine\MainEngine;

    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;

    require('inc/globals.php');

    $dir = MISC::findFile('plugins'); // Gets the library directory's actual position.

    $plgs = array();

    foreach(glob($dir . '/*/*.json') as $file) {
        $directory = dirname($file);
        $config = json_decode(file_get_contents($file), true);

        $plgs[] = array(
            'name'      => $config['name'],
            'priority'  => $config['priority'],
            'directory' => $directory
        );
    }

    // TODO: Add this to the Misc class.
    function array_orderby() {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    $plgs = array_orderby($plgs, 'priority');
    $plugins = array();

    foreach($plgs as $plugin) {
        foreach(glob($plugin['directory'] . '/*.php') as $file) {
            $plugins[] = basename($file, '.php');
            require_once($file);
        }
    }

    /*exit;

    // Gets the classes inside the library directory.
    foreach(glob($dir . '/*.php') as $file) {
        $plugins[] = basename($file, '.php');
        require_once($file);
    }
    foreach(glob($dir . '/\*//*.php') as $file) {
        $plugins[] = basename($file, '.php');
        require_once($file);
    }*/

    if(isset($_COOKIE['themeName'])) {
        $TE = new MainEngine($_COOKIE['themeName'], $SQL, $Config);
    } else {
        $TE = new MainEngine(MISC::findKey('theme', $Config->config), $SQL, $Config);
    }

/**
 * Hook for plugins, add the method 'hook_top(MainEngine $engine)' to your plugin to use this.
 * This will basically do whatever is in that function before executing anything else.
 */
    foreach($plugins as $plugin) {
        if(method_exists($plugin, 'hook_top')) {
            $content = $plugin::hook_top($TE);

            if(is_array($content)) {
                if(!empty($content['content'])) {
                    echo $content['content'];
                } else {
                    if($content['deny404'] == false) {
                        echo $TE->getTemplate('404', 'errors');
                    }
                }

                if($content['exit']) {
                    exit;
                }
            } else {
                if(!empty($content)) {
                    echo $content;
                } else {
                    echo $TE->getTemplate('404', 'errors');
                }
            }
        }
    }

    if(!isset($_GET['page'])) {
        $_GET['page'] = 'portal';
    }

    if(empty($TE->getConfig())) {
        if(empty($_SESSION['user']) && (
                $_GET['page'] == 'settings' || $_GET['page'] == 'signout'
            )) {
            echo $TE->getTemplate('notloggedin', 'errors');
        }

        if(!empty($_SESSION['user']) && (
                $_GET['page'] == 'login' || $_GET['page'] == 'register' ||
                $_GET['page'] == 'signin' || $_GET['page'] == 'signup'
            )) {
            echo $TE->getTemplate('loggedin', 'errors');
        }
    } else {
        if(empty($_SESSION['user']) && in_array($_GET['page'], array_column($TE->getConfig(), 'loginRequired'))) {
            echo $TE->getTemplate('notloggedin', 'errors');
            exit;
        }

        if(!empty($_SESSION['user']) && in_array($_GET['page'], array_column($TE->getConfig(), 'loginDeny'))) {
            echo $TE->getTemplate('loggedin', 'errors');
            exit;
        }
    }

    if($_GET['page'] == 'signout') {
        unset($_SESSION['user']);
        setcookie("rmbrtkn", '', (time()+(60*60*24*128)), '/','eldrios.com');
        header("Location: /portal");
    }

    $U = new User($SQL);

    if(($_GET['page'] == 'profile' && !isset($_GET['username']))
    || $_GET['page'] == 'profile' && !$U->usernameExists(str_replace('_', ' ', $_GET['username']))) {
        echo $TE->getTemplate('profile_not_found', 'user'); exit;
    }

    if(isset($_GET['category'], $_GET['topic'], $_GET['thread']) && $_GET['page'] == 'forums') {

        $C = new Category($SQL);
        $cat = $C->getCategory($_GET['category'], false);

        $T = new Topic($SQL);
        $top = $T->getTopic($_GET['topic'], false, $cat->id);

        $TR = new Thread($SQL);
        if(isset($_GET['threadId'])) {
            $trd = $TR->getThread($_GET['threadId']);
        } else {
            $trd = $TR->getThread($_GET['thread'], false, $top->id);
        }
        $trd->setPosts();

        if(empty($trd->title)) {
            $html = $TE->getTemplate('notfound','forums');
        } else {
            $html = $TE->getTemplate('thread', 'forums');
        }
    } else if(isset($_GET['category'], $_GET['topic']) && $_GET['page'] == 'forums') {

        $C = new Category($SQL);
        $cat = $C->getCategory($_GET['category'], false);

        $T = new Topic($SQL);
        $top = $T->getTopic($_GET['topic'], false, $cat->id);

        if(empty($top->title)) {
            $html = $TE->getTemplate('notfound','forums');
        } else {
            $html = $TE->getTemplate('threads', 'forums');
        }
    } else if(isset($_GET['category']) && $_GET['page'] == 'forums') {

        $C = new Category($SQL);
        $cat = $C->getCategory($_GET['category'], false);

        if(empty($cat->title)) {
            $html = $TE->getTemplate('notfound','forums');
        } else {
            $html = $TE->getTemplate('category', 'forums');
        }
    } else if($_GET['page'] == 'forums') {
        $html = $TE->getTemplate('categories', 'forums');
    } else {
        $html = $TE->getTemplate($_GET['page']);
    }

    if(empty($html)) {
        echo $TE->getTemplate('404', 'errors');
    } else {
        echo $html;
    }
