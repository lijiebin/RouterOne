<?php

namespace RouterOne;

use RouterOne\Exception\RouteNotFoundException;
use RouterOne\Exception\RouteMethodNotAllowedException;

class Router
{
    protected $pathInfo;
    
    protected $routeDomain;
    
    protected $currentRoute;
    
    protected $currentAction;
    
    protected $currentDomain;
    
    protected $map = [];
    
    protected $handlers = [];
    
    protected $routePrefix = [];
    
    protected $routeSuffix = [];
    
    private static $_instance;
    
    public function __construct()
    {
        self::$_instance = $this;
        
        $this->currentDomain = $this->getCurrentDomain();
        $this->map['static'][$this->currentDomain] = [];
        $this->map['dynamic'][$this->currentDomain] = [];
    }
    
    public static function getInstance()
    {
        if (! self::$_instance) self::$_instance = new self();
        
        return self::$_instance;
    }
    
    public function parse()
    {
        if (array_key_exists('PATH_INFO', $_SERVER) === true)
        {
            $pathInfo = $_SERVER['PATH_INFO'];
        } else {
            $pathInfo = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
        }
        
        $this->pathInfo = ltrim($pathInfo, "\/");
        if ( ! $this->pathInfo) $this->pathInfo = '/';
        
        if ( ! $this->matchStaticRoute() && ! $this->matchDynamicRoute() ) {
            throw new RouteNotFoundException("No route matched with url path `{$this->pathInfo}`");
        }
    }
    
    protected function matchStaticRoute()
    {
        $matched = false;
        
        foreach ($this->map['static'][$this->currentDomain] as $path => $routeOptions) {
            if ($this->pathInfo == $path) {
                $this->_setMatchedRouteInfo($path, $routeOptions, []);
                $matched = true;
                break;
            }
        }
        
        return $matched;
    }
    
    protected function matchDynamicRoute()
    {
        $matched = false;
        
        foreach ($this->map['dynamic'][$this->currentDomain] as $path => $routeOptions) {
            $routePattern = str_replace("/", "\/", $path);
            $routePattern = preg_replace("/\{[A-Za-z0-9-._]+\}/", "([A-Za-z0-9-._]+)", $routePattern);
            if (preg_match("/^{$routePattern}$/", $this->pathInfo, $params)) {
                $matched = true;
                $this->_setMatchedRouteInfo($path, $routeOptions, array_slice($params, 1), true);
                break;
            }
        }
        
        return $matched;
    }
    
    private function _setMatchedRouteInfo(string $routePath, array $routeOptions, array $params, bool $isDynamic = false)
    {
        $this->routeOptions = $routeOptions;
        $this->currentPath = $routePath;
        $this->currentRouteParams = $params;
        $this->currentAction = $routeOptions['action'];
    }
    
    public function prefix(string $prefix, \Closure $routes)
    {
        $this->routePrefix[] = $prefix;
        $routes();
        $this->routePrefix = [];
    }
    
    public function suffix(string $suffix, \Closure $routes)
    {
        $this->routeSuffix[] = $suffix;
        $routes();
        $this->routeSuffix = [];
    }
    
    public function domain(string $domain, \Closure $routes, $isSecure = false)
    {
        $this->routeDomain = $domain;
        $routes();
        $this->routeDomain = null;
    }
    
    public function reAction($action)
    {
        $this->currentAction = $action;
    }
    
    public function middleware(array $handlers, \Closure $routes)
    {
        $this->handlers = array_merge($handlers, $this->handlers);
        $routes();
        $this->handlers = [];
    }
    
    public function get(string $path, $action)
    {
        $this->_addRoute('GET', $path, $action);
    }
    
    public function post(string $path, $action)
    {
        $this->_addRoute('POST', $path, $action);
    }
    
    private function _addRoute(string $method, string $path, $action)
    {
        if ($this->routePrefix) {
            $path = implode('', $this->routePrefix) . $path;
        }
        
        if ($this->routeSuffix) {
            $path .= implode('', $this->routeSuffix);
        }
        
        $domain = $this->routeDomain ?? ( $this->routeDomain = $this->currentDomain);
        
        $route = [
            'method' => $method,
            'action' => $action,
            'handlers' => $this->handlers,
        ];
        
        if ($this->_isDynamicRoute($path)) {
            $this->map['dynamic'][$domain][$path] = $route;
        } else {
            $this->map['static'][$domain][$path] = $route;
        }
    }
    
    private function _isDynamicRoute(string $path)
    {
        return preg_match("/\{[A-Za-z0-9-._]+\}/", $path);
    }
    
    public function dispatch()
    {
        $this->parse();
        
        if ($this->currentAction instanceof \Closure) {
            $action = $this->currentAction;
        } else if (is_callable($this->currentAction)) {
            $currentMethod = $this->currentAction[1];
            $currentController = $this->currentAction[0];
                
            $action = [new $currentController, $currentMethod];
        } else {
            $action = function () {
                return $this->currentAction;
            };
        }
        
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if (strcasecmp($this->routeOptions['method'], $_SERVER['REQUEST_METHOD']) != 0) {
            throw new RouteMethodNotAllowedException("Route request with `{$requestMethod}` method is invalid");
        }
        
        if ($this->routeOptions['handlers']) {
            $handlers = array_reverse($this->routeOptions['handlers']);
            foreach ($handlers as $midware) {
                $action = function () use ($action, $midware){
                    return $midware::handle($action);
                };
            }
        }
        
        /** 
         * Equal to `$action(...$this->currentRouteParams)` 
         */
        return call_user_func_array($action, $this->currentRouteParams);
    }
    
    public function getCurrentDomain()
    {
        $domain = ltrim(strtolower($_SERVER['HTTP_HOST']), 'www.');
        
        return $domain;
    }
    
    public function getCurrentRoutePath()
    {
        return $this->currentPath;
    }
    
    public function getCurrentRouteActoin()
    {
        return $this->currentAction;
    }
    
    public function setIncludePath(string $path)
    {
        $this->includePath = rtrim($path, "/ \\") . DIRECTORY_SEPARATOR;
        return $this;
    }
    
    public function load($routeFiles, string $extension = '.php')
    {
        if ( ! is_array($routeFiles)) {
            $routeFiles = [$routeFiles];
        }
        
        $routes = [];
        foreach ($routeFiles as $file) {
            $filePath = $this->includePath . $file . $extension;
            $routes[] = include($filePath);
        }
        return $routes;
    }
    
    public static function url(string $path, $isSecure = false)
    {
        $domain = self::$_instance->currentDomain;
        $protocol = $isSecure ? 'https' : 'http';
        
        return "{$protocol}://{$domain}/{$path}";
    }
}

