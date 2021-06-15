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

Controller Action: 
```php

// Http GET
$this->get('index', [Controllers\SiteController::class, 'index']);

// Http POST
$this->post('news/add', [Controllers\NewsController::class, 'add']);

```

Closure function Action:

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

### MiddleWare

```php

// Http GET
$this->get('index', [Controllers\SiteController::class, 'index']);

// Http POST
$this->post('news/add', [Controllers\NewsController::class, 'add']);

```

### Suffix & Suffix

### Domain Restrict

