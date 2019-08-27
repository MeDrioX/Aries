<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 15/08/2019
 * Time: 19:59
 */

namespace Core\Pyxis;

class  Pyxis {

    public static function include($file){
        $_file = ROOT . '/views/' . $file . '.pyxis.php';
        if (file_exists($_file)) {
            include $_file;
        }
    }

}