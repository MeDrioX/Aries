<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 15/08/2019
 * Time: 20:03
 */

class Aries {

    private static $_instance;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new Aries();
        }
        return self::$_instance;
    }

    public static function load() {
        require ROOT . '/core/Autoload.php';
        \Core\Autoload::register();
        require ROOT . '/app/Autoload.php';
        \App\Autoload::register();
        require ROOT . '/config/config.php';


    }


}