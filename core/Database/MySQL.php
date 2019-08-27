<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 16/08/2019
 * Time: 13:25
 */

namespace Core\Database;


use Core\Config\Config;
use PDO;

class MySQL {

    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_host;
    private $db_port;
    private $db;
    private static $_instance;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new MySQL();
        }
        return self::$_instance;
    }

    public function __construct(){
        $this->db_name = Config::getInstance()->getConfig('db_name');
        $this->db_user = Config::getInstance()->getConfig('db_user');
        $this->db_pass = Config::getInstance()->getConfig('db_pass');
        $this->db_host = Config::getInstance()->getConfig('db_host');
        $this->db_port = Config::getInstance()->getConfig('db_port');
    }

    public function getDatabase(){
        if ($this->db === null) {
            $db = new \PDO('mysql:host='.$this->db_host.';port='.$this->db_port.';dbname='.$this->db_name.';charset=utf8', $this->db_user, $this->db_pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db = $db;
        }
      return $this->db;
    }

    /*public function query($statement, $class_name = null, $one = false, $fetch = false) {
        $request = $this->getDatabase()->query($statement);
        if ($fetch) {
            if($class_name === null) {
                $request->setFetchMode(PDO::FETCH_OBJ);
            } else {
                $request->setFetchMode(PDO::FETCH_CLASS, $class_name);
            }
            if ($one) {
                $datas = $request->fetch();
            } else {
                $datas = $request->fetchAll();
            }
            return $datas;
        }
    }*/

    public function execute($req, $return_type, $class_name = null){

        $r = $this->getDatabase()->query($req);

        if($class_name === null){
            switch($return_type){
                case 'fetch':
                    $r->setFetchMode(PDO::FETCH_OBJ);
                    return $r->fetch();
                    break;
                case 'rowCount':
                    return $r->rowCount();
                    break;
                default:
                    return 'Ce return type n\'existe pas';
                    break;
            }
        }else{
            switch($return_type){
                case 'fetch':
                    $r->setFetchMode(PDO::FETCH_OBJ, $class_name);
                    return $r->fetch();
                    break;
                case 'rowCount':
                    return $r->rowCount();
                    break;
                default:
                    return 'Ce return type n\'existe pas';
                    break;
            }
        }

    }

}