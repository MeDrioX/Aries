<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 21/08/2019
 * Time: 09:55
 */

namespace Core\Sagitta;

use Core\Config\Config;
use Exception;
use InvalidArgumentException;
use PDO;
use ReflectionClass;

abstract class DataModel {

    protected static $conn;


    protected static $tableName;

    protected static $pkColumn;

    protected static $createdAtColumn;

    protected static $updatedAtColumn;

    protected static $readOnly = false;

    protected static $defaultValues = [];

    protected $reflectionObject;

    protected $loadMethod;

    protected $loadData;

    protected $modifiedFields = [];

    protected $isNew = false;

    protected $ignoreKeyOnUpdate = true;

    protected $ignoreKeyOnInsert = true;

    protected $data = [];

    protected $filteredData = [];

    protected $pkValue;

    protected $inSetTransaction = false;

    const FILTER_IN_PREFIX = 'filterIn';
    const FILTER_OUT_PREFIX = 'filterOut';

    const LOAD_BY_PK = 1;
    const LOAD_BY_ARRAY = 2;
    const LOAD_NEW = 3;
    const LOAD_EMPTY = 4;

    const FETCH_ONE = 1;
    const FETCH_MANY = 2;
    const FETCH_NONE = 3;
    const FETCH_FIELD = 4;


    public function __construct($data = null, $method = self::LOAD_EMPTY) {
        $this->loadData = $data;
        $this->loadMethod = $method;

        switch ($method) {
            case self::LOAD_BY_PK:
                $this->loadByPK();
                break;

            case self::LOAD_BY_ARRAY:
                $this->hydrateEmpty();
                $this->loadByArray();
                break;

            case self::LOAD_NEW:
                $this->hydrateEmpty();
                $this->loadByArray();
                $this->insert();
                break;

            case self::LOAD_EMPTY:
                $this->hydrateEmpty();
                break;
        }

        $this->initialise();

    }


    public static function useConnection(PDO $conn) {
        static::$conn = $conn;
    }

    public static function createConnection(array $options = [], $charset = null) {

        $db_name = Config::getInstance()->getConfig('db_name');
        $db_user = Config::getInstance()->getConfig('db_user');
        $db_pass = Config::getInstance()->getConfig('db_pass');
        $db_host = Config::getInstance()->getConfig('db_host');
        $db_port = Config::getInstance()->getConfig('db_port');

        $db = new \PDO('mysql:dbname='.$db_name.';host='.$db_host.';port='.$db_port.';charset=utf8',$db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES \'UTF8\'');
        static::$conn = $db;
    }


    public static function getConnection() {
        return static::$conn;
    }

    public function getLoadMethod() {
        return $this->loadMethod;
    }

    public function getLoadData() {
        return $this->loadData;
    }

    protected function loadByPK() {
        $this->pkValue = $this->loadData;

        $this->hydrateFromDatabase();
    }

    protected function loadByArray() {
        // set our data
        foreach ($this->loadData AS $key => $value) {
            $this->data[$key] = $value;
        }
        // extract columns
        $this->executeOutputFilters();
    }

    protected function hydrateEmpty() {

        $defaults = static::$defaultValues ? static::$defaultValues : [];

        foreach ($this->getColumnNames() AS $field) {
            $this->data[$field] = array_key_exists($field, $defaults) ? $defaults[$field] : null;
        }

        $this->isNew = true;
    }

    protected function hydrateFromDatabase() {
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s` = '%s';", static::getTableName(), static::getTablePk(), $this->id());
        $result = static::getConnection()->query($sql);

        if (!$result || !$result->rowCount()) {
            throw new Exception(sprintf("%s record not found in database. (PK: %s)", get_called_class(), $this->id()), 2);
        }

        foreach ($result->fetch(PDO::FETCH_ASSOC) AS $key => $value) {
            $this->data[$key] = $value;
        }

        unset($result);

        $this->executeOutputFilters();
    }

    public static function getTableName() {
        return @static::$tableName ? static::$tableName : strtolower(basename(str_replace("\\", DIRECTORY_SEPARATOR, get_called_class())));
    }

    public static function getTablePk() {
        return @static::$pkColumn ? static::$pkColumn : 'id';
    }

    public function id() {
        return $this->pkValue ? $this->pkValue : (
        array_key_exists(static::getTablePk(), $this->data) ? $this->data[static::getTablePk()] : null
        );
    }

    public function isNew() {
        return $this->isNew;
    }

    protected function notNew() {
        $this->isNew = false;
    }

    public function preInsert(array &$data = []) {
        if (static::$createdAtColumn) {
            $data[static::$createdAtColumn] = static::setCurrentTimestampValue();
        }
        if (static::$updatedAtColumn) {
            $data[static::$updatedAtColumn] = static::setCurrentTimestampValue();
        }
    }

    public function postInsert() {

    }

    public function preDelete() {

    }

    public function postDelete() {

    }

    public function preUpdate(array &$data = []) {
        if (static::$updatedAtColumn) {
            $data[static::$updatedAtColumn] = static::setCurrentTimestampValue();
        }
    }

    public function postUpdate() {

    }


    public function initialise() {

    }


    protected function executeOutputFilters() {
        $r = new ReflectionClass(get_class($this));

        $data = $this->data;

        foreach ($r->getMethods() AS $method) {
            if (substr($method->name, 0, strlen(self::FILTER_OUT_PREFIX)) == self::FILTER_OUT_PREFIX) {
                $returnedData = $this->{$method->name}($data);
                $data = (is_array($returnedData) ? $returnedData : []) + $data;
            }
        }

        $this->filteredData = (is_array($data) ? $data : []) + $this->data;
    }

    protected function executeInputFilters($array) {
        $r = new ReflectionClass(get_class($this));

        foreach ($r->getMethods() AS $method) {
            if (substr($method->name, 0, strlen(self::FILTER_IN_PREFIX)) == self::FILTER_IN_PREFIX) {
                $array = $this->{$method->name}($array);
            }
        }

        return $array;
    }

    public function save() {
        if ($this->isNew()) {
            $this->insert();
        } else {
            $this->update();
        }
        return $this;
    }

    protected function insert() {

        if (static::$readOnly) {
            throw new Exception("Cannot write to READ ONLY tables.");
        }

        $array = $this->getRaw();

        if ($this->preInsert($array) === false) {
            return;
        }

        $array = $this->executeInputFilters($array);

        $array = array_intersect_key($array, array_flip($this->getColumnNames()));

        if ($this->ignoreKeyOnInsert === true) {
            unset($array[static::getTablePk()]);
        }

        $fieldNames = $fieldMarkers = $types = $values = [];

        foreach ($array AS $key => $value) {
            $fieldNames[] = sprintf('`%s`', $key);
            if (is_object($value) && $value instanceof RawSQL) {
                $fieldMarkers[] = (string) $value;
            } else {
                $fieldMarkers[] = '?';
                $types[] = $this->parseValueType($value);
                $values[] = &$array[$key];
            }
        }

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", static::getTableName(), implode(', ', $fieldNames), implode(', ', $fieldMarkers));

        static::sql($sql, self::FETCH_NONE, array_values($values));

        $lastId = static::getConnection()->lastInsertId();

        if ($lastId) {
            $this->pkValue = $lastId;
            $this->data[static::getTablePk()] = $lastId;
        }

        $this->isNew = false;

        $this->hydrateFromDatabase($lastId);

        $this->postInsert();
    }

    public function update() {

        if (static::$readOnly) {
            throw new Exception("Cannot write to READ ONLY tables.");
        }

        if ($this->isNew()) {
            return $this->insert();
        }

        $pk = static::getTablePk();
        $id = $this->id();

        $array = $this->getRaw();

        if ($this->preUpdate($array) === false) {
            return;
        }

        $array = $this->executeInputFilters($array);

        $array = array_intersect_key($array, array_flip($this->getColumnNames()));

        if ($this->ignoreKeyOnUpdate === true) {
            unset($array[$pk]);
        }

        $fields = $types = $values = [];

        foreach ($array AS $key => $value) {
            if (is_object($value) && $value instanceof RawSQL) {
                $fields[] = sprintf('`%s` = %s', $key, (string) $value);
            } else {
                $fields[] = sprintf('`%s` = ?', $key);
                $types[] = $this->parseValueType($value);
                $values[] = &$array[$key];
            }
        }

        $types[] = 'i';
        $values[] = &$id;

        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` = ?", static::getTableName(), implode(', ', $fields), $pk);

        static::sql($sql, self::FETCH_NONE, $values);

        $this->modifiedFields = [];

        $this->hydrateFromDatabase();

        $this->postUpdate();
    }

    public function delete() {

        if (static::$readOnly) {
            throw new Exception("Cannot write to READ ONLY tables.");
        }

        if ($this->isNew()) {
            throw new Exception('Unable to delete object, record is new (and therefore doesn\'t exist in the database).');
        }

        if ($this->preDelete() === false) {
            return;
        }

        $sql = sprintf("DELETE FROM `%s` WHERE `%s` = ?", static::getTableName(), static::getTablePk());
        $id = $this->id();

        try {
            static::sql($sql, self::FETCH_NONE, [$id]);
        } catch (Exception $e) {
        }

        $this->postDelete();
    }

    public static function getColumnNames() {
        $conn = static::getConnection();
        $result = $conn->query(sprintf("DESCRIBE %s;", static::getTableName()));

        if ($result === false) {
            throw new Exception(sprintf('Unable to fetch the column names. %s.', $conn->errorCode()));
        }

        $ret = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $ret[] = $row['Field'];
        }

        $result->closeCursor();

        return $ret;
    }

    protected static function parseValueType($value) {
        if (is_int($value)) {
            return 'i';
        }

        if (is_double($value)) {
            return 'd';
        }

        return 's';
    }

    public function revert($return = false) {
        if ($return) {
            $ret = clone $this;
            $ret->revert();

            return $ret;
        }

        $this->hydrateFromDatabase();
    }

    public function get($fieldName = false) {
        if ($fieldName === false) {
            return $this->filteredData;
        }

        return array_key_exists($fieldName, $this->filteredData) ? $this->filteredData[$fieldName] : (
        array_key_exists($fieldName, $this->data) ? $this->data[$fieldName] : $this->{$fieldName}
        );
    }

    public function getRaw($fieldName = false) {
        if ($fieldName === false) {
            return $this->data;
        }

        return array_key_exists($fieldName, $this->data) ? $this->data[$fieldName] : $this->{$fieldName};
    }

    public function set($fieldName, $newValue = null) {
        if (is_array($fieldName)) {
            $this->inSetTransaction = true;
            foreach ($fieldName as $key => $value) {
                $this->set($key, $value);
            }
            $this->data = $this->executeInputFilters($this->data);
            $this->executeOutputFilters();
            $this->inSetTransaction = false;
        } elseif (is_scalar($fieldName)) {
            // if changed, mark object as modified
            if ($this->get($fieldName) != $newValue) {
                $this->modifiedFields($fieldName, $newValue);
            }
            $this->data[$fieldName] = $newValue;
            if (!$this->inSetTransaction) {
                $this->data = $this->executeInputFilters($this->data);
                $this->executeOutputFilters();
            }
        }
        return $this;
    }

    public function isModified() {
        return count($this->modifiedFields) > 0;
        ;
    }

    public function modified() {
        return $this->isModified() ? $this->modifiedFields : null;
    }

    protected function modifiedFields($fieldName, $newValue) {
        if (!isset($this->modifiedFields[$fieldName])) {
            $this->modifiedFields[$fieldName] = $newValue;

            return;
        }

        if (!is_array($this->modifiedFields[$fieldName])) {
            $this->modifiedFields[$fieldName] = [$this->modifiedFields[$fieldName]];
        }

        $this->modifiedFields[$fieldName][] = $newValue;
    }

    public static function sql($sql, $return = self::FETCH_MANY, array $params = null) {
        $sql = str_replace([':table', ':pk'], [static::getTableName(), static::getTablePk()], $sql);

        $stmt = static::getConnection()->prepare($sql);
        if (!$stmt || !$stmt->execute($params)) {
            throw new Exception(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));
        }

        if ($return === self::FETCH_NONE) {
            return;
        }

        $ret = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $obj = $return == self::FETCH_FIELD ? $row : call_user_func_array([get_called_class(), 'hydrate'], [$row, false]);
            $ret[] = $obj;
        }

        $stmt->closeCursor();

        if ($return === self::FETCH_ONE || $return === self::FETCH_FIELD) {
            $ret = isset($ret[0]) ? $ret[0] : null;
        }

        if ($return === self::FETCH_FIELD && $ret) {
            $data = $ret instanceof DataModel ? $ret->get() : $ret;
            $ret = array_values($ret)[0];
        }

        return $ret;
    }

    public static function count($sql = "SELECT count(*) FROM :table") {
        $count = (int) (static::sql($sql, self::FETCH_FIELD));

        return $count > 0 ? $count : 0;
    }

    public static function truncate() {

        if (static::$readOnly) {
            throw new Exception("Cannot write to READ ONLY tables.");
        }

        static::sql('TRUNCATE :table', self::FETCH_NONE);
    }

    public static function all() {
        return static::sql("SELECT * FROM :table");
    }

    public static function retrieveByPK($pk) {
        if (!is_numeric($pk) && !is_string($pk)) {
            throw new InvalidArgumentException('The PK must be an integer or string.');
        }

        return new static($pk, self::LOAD_BY_PK);
    }

    public static function hydrate(array $data, $asNew = true) {
        $reflectionObj = new ReflectionClass(get_called_class());

        $instance = $reflectionObj->newInstanceArgs([$data, self::LOAD_BY_ARRAY]);

        if (!$asNew) {
            $instance->notNew();
        }

        return $instance;
    }

    public static function __callStatic($name, $args) {
        $class = get_called_class();

        if (substr($name, 0, 10) == 'retrieveBy') {
            // prepend field name to args
            $field = strtolower(preg_replace('/\B([A-Z])/', '_${1}', substr($name, 10)));
            array_unshift($args, $field);

            return call_user_func_array([$class, 'retrieveByField'], $args);
        }

        throw new Exception(sprintf('There is no static method named "%s" in the class "%s".', $name, $class));
    }

    public static function retrieveByField($field, $value, $return = self::FETCH_MANY) {
        if (!is_string($field))
            throw new InvalidArgumentException('The field name must be a string.');

        $operator = (strpos($value, '%') === false) ? '=' : 'LIKE';

        $sql = sprintf("SELECT * FROM :table WHERE %s %s '%s'", $field, $operator, $value);

        if ($return === self::FETCH_ONE) {
            $sql .= ' LIMIT 0,1';
        }

        return static::sql($sql, $return);
    }

    protected static function setCurrentTimestampValue() {
        return RawSQL::make('CURRENT_TIMESTAMP');
    }

    public function __get($name) {
        return $this->get($name);
    }

    public function __set($name, $value) {
        if (array_key_exists($name, $this->data)) {
            $this->set($name, $value);
            return;
        }
        throw new Exception(sprintf("Can not set property %s", $name));
    }

}