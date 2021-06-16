# RouterOne
A independent, easy-to-use, expandable and simple to understand php web application url routing library, mybe augment your development toolkit or provided a bit inspiration for you.

## Features
- Grouped routes
- Route-Map files loading
- URL domain distinguishable
- MidlleWare internal support
- Route path prefix & suffix customization

## Requirements
- PHP >= 7.0
- A class-autoloader implementation depends on your preferred loading mechanism, for example LoaderOne (Strictly speaking, its not required, just for convenience.)

## installation
Download the source code and place right location of your project directory.

## Basic Usage
Get Router Oject Instance
```php
use RouterOne\Router;

$router = Router::getInstance();

```
Create route map in single file and located in specific dir of your prject, in the file `$this` refer to concrete `$router` object instance. For the http request verbs, `RouteOne` only support `GET`, `POST`(just align with PHP's $_GET & $_POST, totally extending if you want or necessary.)

For example, `Foo.php`
```php

// Closure function call
$this->get('/', function () {
    echo 'Hello! RouterOne';
});

// `WelcomeController::hello()` call
$this->get('/', [WeclomeController::class, 'hello']);
  
```

Set route map file directory path & loading it. (The route map file default extension is `.php`)
```php

$router->setIncludePath(`YOUR_ROUTE_MAP_FILE_DIR`);
$router->load(['Foo']); // Just file's name without extension

```
Or call like this

```php

$router->setIncludePath(`YOUR_ROUTE_MAP_FILE_DIR`)->load(['Foo']); // Just file's name without extension

```
Run dispatch and enable all loaded routes, it will be return the result about actually route called.

```php

$res = $router->dispatch();

```

## Explains

### Add One Route

Controller Route Action: 
```php

// Http GET
$this->get('index', [Controllers\SiteController::class, 'index']);

// Http POST
$this->post('news/add', [Controllers\NewsController::class, 'add']);

```

Closure Route Action:

```php

// Http GET
$this->get('index', function () {
    /**
    * Some logic process code here
    */
});

// Http POST
$this->post('news/add', function () {
    /**
    * Some logic process code here
    */
});

```

With Route Parameters, dynamic paramters with `{}` wrapped, then will be transfer to controller method or clousre function paramter in the order of appearance. 

```php

$this->get('test/{param1}/{param2}', [Controllers\TestController::class, 'params']);

class TestController
{
    public function params($param1, $param2)
    {
        // Some code ...
    }
}

```

```php
$this->get('test/{param1}/{param2}', function ($param1, $param2) {
    // Some code
});

```

### MiddleWare

MiddleWare should be a implemetion to `RouteMiddleWareInterface`,  you can locate middle-ware class file in arbitrary directory, such as `MiddleWare` dir;

A typical middle-ware class contain a `handle()` method with route action-`$action` parameter, like below:

```php

use RouterOne\MiddleWare\RouteMiddleWareInterface;

class AuthCheckMiddleWare implements RouteMiddleWareInterface
{
    public static function handle($action)
    {
        if ( ! AdminUser::Logged) {
            exit('Please login first.');
        }
        
        $action();
    }
}

```

In some cases you may want do some process after route action excuted, just place middle-ware logic behind `$action()` call statement.

```php

use RouterOne\MiddleWare\RouteMiddleWareInterface;

class AfterMiddleWare implements RouteMiddleWareInterface
{
    public static function handle($action)
    {
        $action();
        
        echo 'This text will print after route action excuted.';
    }
}

```

When defined middle-ware, and can through router's `middleware()` method setting routes as `grouped` form, `middleware()` has two parameters, the first is a `middle-ware` class name array and support more middle-wares here, and the other is a closure function include common route mapping.
```php

$this->middleware(
    [
        AuthCheckMiddleWare::class,
        ...
        ...
    ], function () {
        $this->get('admin/index', [Controllers\Admin\AdminController::class, 'index']);
        $this->get('admin/news/list', [Controllers\Admin\NewsController::class, 'list']);
        ...
        ...
});

```

Also can `nested`

```php

$this->middleware(
    [
        OuterMiddleWare::class,
        
    ], function () {
        ...
        
        $this->middleware([
            InnerMiddleWare::class
        ], function () {
            $this->get(...
            $this->post(...
            ...
        });
        
        ...
        ...
});

```

### Prefix & Suffix

`prefix()` and `suffix()` method are `grouped` routes too, they can convenient add practical prefix and suffix to specific routes.

Add the prefix `static/`, then urls '://domain/static/page1', '//domain/static/page2' will be matched. 

```php

$this->prefix('static/', function () {
    $this->get('page1', ...);
    $this->get('page2', ...);
    ...
});

```
Add the preifx `the`, then urls '://domain/thepage1', '//domain/thepage2' will be matched. 

```php

$this->prefix('the', function () {
    $this->get('page1', ...);
    $this->get('page2', ...);
    ...
});

```

As same as `prefix()` using, add the suffix `.html`, then url change to '://domain/page1.html'. 

```php

$this->suffix('.html', function () {
    $this->get('page1', ...);
    ...
    ...
});

```
Between `prefix()` and `suffix()` can `nested` each other.

```php

$this->prefix('static/', function () {
    $this->get('page1', ...);  // request url '://domain/static/page1' matched here
    ...
    
    $this->suffix('.html', function () {
        $this->get('page2',  ...); // request url '://domain/static/page2.html' matched here
    });
});

```


### Domain Restrict

