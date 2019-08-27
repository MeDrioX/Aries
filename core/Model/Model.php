<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 21/08/2019
 * Time: 00:23
 */

namespace Core\Model;

use Core\Database\MySQL;

class Model {

    protected $model;
    protected $db;

    public function __construct($db){
        $this->db = new MySQL();

        if(is_null($this->model)){
            $parts = explode("\\", get_class($this));
            $class_name = end($parts);
            $this->model = strtolower(str_replace('Model', '', $class_name)).'s';
        }
    }

    public function findAll(){
        return $this->query("SELECT * FROM {$this->model}", "fetch");
    }

    public function findById($id){
        return $this->query("SELECT * FROM {$this->model} WHERE id = $id", "fetch");
    }

    public function query($statement, $return_type, $params = [], $one = false) {
        if ($params) {
            //return $this->db->prepare($statement, $params, str_replace('Model', 'Entity', get_class($this)), $one, $fetch);
            return $this->query($statement, $return_type, str_replace('Model', 'Entity', get_class($this)));
        } else {
            //return $this->db->query($statement, str_replace('Model', 'Entity', get_class($this)), $one, $fetch);
        }
    }

}