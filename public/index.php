<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 16/08/2019
 * Time: 12:21
 */

define('ROOT', dirname(__DIR__));

        require ROOT . '/core/Aries.php';
Aries::load();


$router = new \Core\Router\Router();

require ROOT . '/routes/web.php';

$match = $router->match();

if(is_array($match) && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else if (strpos($match['target'], '@') !== false) {
    list($_controller, $_function) = explode('@', $match['target']);
    $controller = 'App\\Controllers\\' . ucfirst($_controller);
    $controller = new $controller();

    call_user_func_array(array($controller, $_function), $match['params']);
} else if (file_exists(ROOT . '/views/' . $match['target'] . '.pyxis.php')) {
    extract($match['params']);
    require ROOT . '/views/' . $match['target'] . '.pyxis.php';
} else {
    require ROOT . '/views/404.pyxis.php';
}