<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 15/08/2019
 * Time: 20:00
 */

namespace App;

class Autoload {

    static function register(){
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    static function autoload($class){
        if(strpos($class, __NAMESPACE__.'\\') === 0){
            $class = str_replace(__NAMESPACE__.'\\', '', $class);
            $class =str_replace('\\', '/', $class);
            require __DIR__.'/'.$class.'.php';
        }
    }

}