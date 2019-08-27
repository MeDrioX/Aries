<?php


namespace Core\Sagitta;

class SessionManager {

    public static function setSession($name, $value){
        session_start();
        $_SESSION[$name] = $value;
    }

    public static function getSession($name){
        session_start();

        if (isset($_SESSION[$name])) return $_SESSION[$name];
    }

    public static function deleteSession($name = null){
        if($name == null){
            session_start();
            $_SESSION = array();
            session_destroy();
        }else{
            session_start();
            unset($_SESSION[$name]);
        }
    }
}