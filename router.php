<?php

namespace RouterOne;

class Router
{
    protected $map = [];
    
    protected $handlers;
    
    protected $pathInfo;
    
    protected $routePrefix;
    
    protected $routeSuffix;
    
    protected $routeDomain;
    
    protected $currentRoute;
    
    protected $currentAction;
    
    private static $_instance;
    
    public function __construct()
    {
        self::$_instance = $this;
    }
    
    public static function getInstance()
    {
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
            throw new \Exception("No route matched with url path `{$this->pathInfo}`");
        }
    }
    
    protected function matchStaticRoute()
    {
        $matched = false;
        
        foreach ($this->map['static'][$this->getCurrentDomain()] as $path => $routeOptions) {
            if ($this->pathInfo == $path) {
                $this->currentPath = $path;
                $this->routeOptions = $routeOptions;
                $this->currentRouteParams = [];
                $this->currentAction = $routeOptions['action'];
                $matched = true;
                break;
            }
        }
        
        return $matched;
    }
    
    protected function matchDynamicRoute()
    {
        $matched = false;
        
        foreach ($this->map['dynamic'][$this->getCurrentDomain()] as $path => $routeOptions) {
            $routePattern = str_replace("/", "\/", $path);
            $routePattern = preg_replace("/\{[A-Za-z0-9-._]+\}/", "([A-Za-z0-9-._]+)", $routePattern);
            if (preg_match("/^{$routePattern}$/", $this->pathInfo, $params)) {
                $this->currentPath = $path;
                $this->routeOptions = $routeOptions;
                $this->currentRouteParams = array_slice($params, 1);
                $this->currentAction = $routeOptions['action'];
                $matched = true;
                break;
            }
        }
        
        return $matched;
    }
    
    public function prefix(string $prefix, \Closure $routes)
    {
        $this->routePrefix = $prefix;
        $routes();
        $this->routePrefix = null;
    }
    
    public function suffix(string $suffix, \Closure $routes)
    {
        $this->routeSuffix = $suffix;
        $routes();
        $this->routeSuffix = null;
    }
    
    public function domain(string $domain, \Closure $routes)
    {
        $this->routeDomain = $domain;
        $routes();
        $this->routeDomain = null;
    }
    
    public function reAction($action)
    {
        $this->currentAction = $action;
    }
    
    public function middleware($handlers, $routes)
    {
        $this->handlers = $handlers;
        $routes();
        $this->handlers = null;
    }
    
    public function withMiddleWare($handlers)
    {
        $this->handlers = $handlers;
    }
    
    public function get($path, $action)
    {
        $this->method = 'GET';
        
        $this->_addRoute($path, $action);
    }
    
    public function post($path, $action)
    {
        $this->method = 'POST';
        
        $this->_addRoute($path, $action);
    }
    
    private function _addRoute($path, $action)
    {
        if ($this->routePrefix) {
            $path = "{$this->routePrefix}/{$path}";
        }
        
        if ($this->routeSuffix) {
            $path = "{$path}{$this->routeSuffix}";
        }
        
        $domain = $this->routeDomain ?? $this->getCurrentDomain();
        
        $route = [
            'action' => $action,
            'method' => $this->method,
            'handlers' => $this->handlers,
        ];
        
        if ($this->_isDynamicRoute($path)) {
            $this->map['dynamic'][$domain][$path] = $route;
        } else {
            $this->map['static'][$domain][$path] = $route;
        }
    }
    
    private function _isDynamicRoute($path)
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
        
        if ($this->routeOptions['handlers']) {
            $handlers = array_reverse($this->routeOptions['handlers']);
            foreach ($handlers as $midware) {
                $action = function () use ($action, $midware){
                    $midware::handle($action);
                };
            }
        }
        
        /** 
         * @example Equal to `$action(...$this->currentRouteParams)` 
         */
        return call_user_func_array($action, $this->currentRouteParams);
    }
    
    public function getCurrentDomain()
    {
        $domain = strtolower($_SERVER['HTTP_HOST']);
        
        if (strstr($domain, 'www.')) {
            $domain = ltrim($domain, 'www.');
        }
        
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
    
    public function setIncludePath($path)
    {
        $this->includePath = rtrim($path, "/ \\") . DIRECTORY_SEPARATOR;
        return $this;
    }
    
    public function load($routeFiles, $extension = '.php')
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
}
