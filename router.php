<?php

class Router
{
    protected $map = [];
    
    protected $currentRoute;
    
    protected $currentAction;
    
    public function parse()
    {
        if (array_key_exists('PATH_INFO', $_SERVER) === true)
        {
            $path_info = $_SERVER['PATH_INFO'];
        } else {
            $path_info = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
        }
         
        $path_info = trim($path_info, '/');
        
        foreach ($this->map as $path => $action) {
            $routePattern = str_replace("/", "\/", $path);
            $routePattern = preg_replace("/\{[A-Za-z0-9-._]+\}/", "[A-Za-z0-9-._]", $routePattern);
            if (preg_match("/{$routePattern}/", $path_info)) {
                $this->currentRoute = $path;
                $this->currentAction = $action;
            }
        }
    }
    
    public function get($path, $action)
    {
        $this->map[$path] = $action;
    }
    
    public function dispatch()
    {
        $this->parse();
        //var_dump($this->currentAction);
        
        call_user_func_array([new $this->currentAction[0], $this->currentAction[1]], []);
        
    }
    

}

$r = new Router();

$r->get('test/first', [TestController::class, 'first']);
$r->get('user/{id}', [TestController::class, 'second']);
$r->get('news/delete/{id}', [TestController::class, 'second']);

//var_dump($r->map, $_SERVER);

$r->dispatch();
