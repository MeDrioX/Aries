<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 16/08/2019
 * Time: 13:33
 */

namespace Core\Config;


class Config {

    private static $config = [];
    private static $_instance;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new Config();
        }
        return self::$_instance;
    }

    public static function setConfig($key, $value){
        self::$config[$key] = $value;
    }


    public function getConfig($key){
        return self::$config[$key];
    }

}