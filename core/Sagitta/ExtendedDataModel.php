<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 21/08/2019
 * Time: 10:15
 */

namespace Core\Sagitta;


abstract class ExtendedDataModel extends DataModel {
    protected $cache = [];
    protected static $staticCache = [];

    protected function cache($key, $value) {
        $this->cache[$key] = $value;
        return $this;
    }

    protected function isCached($key) {
        return array_key_exists($key, $this->cache);
    }

    protected function cached($key, $default = null) {
        return $this->isCached($key) ? $this->cache[$key] : $default;
    }

    protected function cachedOrCache($key, callable $value) {
        if (!$this->isCached($key)) {
            $this->cache($key, $value());
        }
        return $this->cached($key);
    }

    protected static function assertStaticCacheExists() {
        if (!array_key_exists(get_called_class(), static::$staticCache)) {
            static::$staticCache[get_called_class()] = [];
        }
    }

    protected static function staticCache($key, $value) {
        static::assertStaticCacheExists();
        static::$staticCache[get_called_class()][$key] = $value;
        return $this;
    }

    protected static function staticIsCached($key) {
        static::assertStaticCacheExists();
        return array_key_exists($key, static::$staticCache[get_called_class()]);
    }

    protected static function staticCached($key, $default = null) {
        static::assertStaticCacheExists();
        return static::$staticIsCached($key) ? static::$staticCache[get_called_class()][$key] : $default;
    }

    protected static function staticCachedOrCache($key, callable $value) {
        if (!static::staticIsCached($key)) {
            static::staticCache($key, $value());
        }
        return static::staticCached($key);
    }

    protected function loadRelation($modelClass, $localKey, $foreignKey, $fetchMode = self::FETCH_MANY, $forceReload = false, $key = null, $extraQuery = null, array $extraParams = []) {
        $key = 'fk__' . ($key ?: $modelClass . $localKey . $foreignKey . $fetchMode);
        return $this->cachedOrCache($key, function() use($modelClass, $localKey, $foreignKey, $fetchMode, $extraQuery, $extraParams) {
            return call_user_func_array([$modelClass, "sql"], ["SELECT * FROM :table WHERE {$foreignKey}=?" . ($extraQuery ? " {$extraQuery}" : ""), $fetchMode, array_merge([$this->$localKey], $extraParams)]);
        });
    }
}