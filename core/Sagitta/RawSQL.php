<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 21/08/2019
 * Time: 14:02
 */

namespace Core\Sagitta;


class RawSQL {

    protected $value;

    public static function make($value){
        return new static($value);
    }

    public function __construct($value){
        $this->value = $value;
    }

    public function __toString(){
        return (string) $this->value;
    }

}