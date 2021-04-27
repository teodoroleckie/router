### Router:
[![Latest Version on Packagist](https://img.shields.io/packagist/v/tleckie/router.svg?style=flat-square)](https://packagist.org/packages/tleckie/router)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/teodoroleckie/router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/teodoroleckie/router/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/teodoroleckie/router/badges/build.png?b=master)](https://scrutinizer-ci.com/g/teodoroleckie/router/build-status/master)
[![Total Downloads](https://img.shields.io/packagist/dt/tleckie/router.svg?style=flat-square)](https://packagist.org/packages/tleckie/router)

Simple and fast router PSR-7, PSR-17, PSR-15

### Installation

You can install the package via composer:

```bash
composer require tleckie/router
```

### Usage

```php
<?php

use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Router;

$router = new Router(
    new ServerRequestFactory(),
    new ResponseFactory(),
    new SapiEmitter()
);


/**
 * Class UserController
 *
 * @author Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class UserController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $params
     * @return ResponseInterface
     */
    public function retrieveUserAction(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $response->getBody()->write(sprintf(" CONTROLLER USER ID: %s", $params['id']));

        return $response->withHeader('Content-Type', 'text/html');
    }
}

// routing with middleware
$router->get('/user/(?<id>[0-9]+)/',
    static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {

        $response = $handler->handle($request);
        $response->getBody()->write(sprintf(' (ROUTES MIDDLEWARE ID: #%s) ', $params['id']));

        return $response;
    },
    [new UserController, 'retrieveUserAction']
);

```

### Add global middleware

The middleware added in the add method is always executed.
```php
<?php

require_once "vendor/autoload.php";

use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Router;

$router = new Router(
    new ServerRequestFactory(),
    new ResponseFactory(),
    new SapiEmitter()
);

/**
 * Class ExampleMiddleware
 * @author Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class ExampleMiddleware implements MiddlewareInterface
{
    /**
     * <strong>Objects that implement MiddlewareInterface will not receive routing parameters</strong>   
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response->getBody()->write(' (GLOBAL 1)');

        return $response;
    }
}

// Add app closure middleware. The closure middleware will receive the routing parameters
$router->add(static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params){
    
    $response = $handler->handle($request);
    $response->getBody()->write(sprintf(' (GLOBAL ID: %s)', $params['id']));

    return $response;
});

// app middleware.
$router->add(new ExampleMiddleware());

```

Support for all methods.
```php
<?php

require_once "vendor/autoload.php";

use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Router;

$router = new Router(
    new ServerRequestFactory(),
    new ResponseFactory(),
    new SapiEmitter()
);

$router->all('/user/(?<id>[0-9]+)/',
static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {

        $response = $handler->handle($request);
        $response->getBody()->write(' (ROUTES MIDDLEWARE #1#)' . $params['id']);

        return $response;
    },
    static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {

        $response = $handler->handle($request);
        $response->getBody()->write(' (ROUTES MIDDLEWARE #2#)' . $params['id']);

        return $response;
    },
    [new UserController, 'retrieveUserAction']
);
```

```php
<?php

require_once "vendor/autoload.php";

use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Exception\RouteNotFoundException;
use Tleckie\Router\Router;

$router = new Router(
    new ServerRequestFactory(),
    new ResponseFactory(),
    new SapiEmitter()
);

$router->post('/user/(?<id>[0-9]+)/',
static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {

        $response = $handler->handle($request);
        $response->getBody()->write(' (ROUTES MIDDLEWARE #1#)' . $params['id']);

        return $response;
    },
    static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {

        $response = $handler->handle($request);
        $response->getBody()->write(' (ROUTES MIDDLEWARE #2#)' . $params['id']);

        return $response;
    },
    [new UserController, 'retrieveUserAction']
);



try {

    $router->run(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REDIRECT_URL'] ?? '/'
    );

} catch (RouteNotFoundException $exception) {
    // handle 404
}
```

### Methods:
GET, POST, HEAD, PATCH, OPTIONS, DELETE, PUT,