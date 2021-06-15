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
Create route map in single file, in the file `$this` refer to concrete `$router` object instance. For the http request verbs, `RouteOne` only support `GET`, `POST`(just align with PHP's $_GET & $_POST, totally extending if you want or necessary.)

```php
// Define home page url path with a welcome message using closure funciton
$this->get('/', function () {
    echo 'Hello! RouterOne';
});
  
```

Set route map file directory path & loading it. (The route map file default extension is `.php`)
```php

$router->setIncludePath(`YOUR_ROUTE_MAP_FILE_DIR`);
$router->load(`FOO`, 'BAR'); // Just file's name without extension

```
Or call like this

```php

$router->setIncludePath(`YOUR_ROUTE_MAP_FILE_DIR`)->load(`FOO`, 'BAR'); // Just file's name without extension

```
