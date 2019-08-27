<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 15/08/2019
 * Time: 20:20
 */

namespace Core\Router;

class Router {

    private $routes = [];

    private $namedRoutes = [];

    protected $matchTypes = array(
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    );

    public function getRoutes() {
        return $this->routes;
    }

    public function add($method, $route, $target, $name = null) {
        if ($name) {
            if(isset($this->namedRoutes[$name])) {
                throw new \Exception("Vous ne pouvez pas redéfinir la route '{$name}'");
            } else {
                $this->namedRoutes[$name] = $route;
                $this->routes[] = array($method, $route, $target, $name);
            }
        }
    }

    public function get($route, $target, $name = null) {
        $this->add('GET', $route, $target, $name);
    }

    public function post($route, $target, $name = null){
        $this->add('POST', $route, $target, $name);
    }

    public function make($name) {
        if(isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        } else {
            return null;
        }
    }

    public function match($requestUrl = null, $requestMethod = null) {
        $params = [];
        $match = false;

        if($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }

        if($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }
        foreach ($this->routes as $handler) {
            list($methods, $route, $target, $name) = $handler;

            $method_match = (stripos($methods, $requestMethod) !== false);
            if (!$method_match) continue;
            if ($route === '*') {
                $match = true;
            } else if (isset($route[0]) && $route[0] === '@') {
                $pattern = '`' . substr($route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params) === 1;
            } elseif (($position = strpos($route, '[')) === false) {
                $match = strcmp($requestUrl, $route) === 0;
            } else {

                if (strncmp($requestUrl, $route, $position) !== 0) {
                    continue;
                }
                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }
            if ($match) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) unset($params[$key]);
                    }
                }
                return array(
                    'method' => $methods,
                    'route' => $route,
                    'target' => $target,
                    'name' => $name,
                    'params' => $params
                );
            }
        }
        return false;
    }

    protected function compileRoute($route) {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;
                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }
                $optional = $optional !== '' ? '?' : null;

                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;
                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }
}