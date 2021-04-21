### Router:

```php
<?php

require_once "vendor/autoload.php";

use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tleckie\Router\Exception\RouteNotFoundException;
use Tleckie\Router\Router;


$router = new Router(
    new ServerRequestFactory(),
    new ResponseFactory(),
    new SapiEmitter()
);


$router->put('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', function(Request $request, Response $response, $id, $name): Response{

    $response->getBody()->write("New User. ID:[$id] NAME:[$name]");

    return $response->withHeader('Content-Type', 'text/html');
});

class UserController
{
    public function retrieveUserAction(Request $request, Response $response, $id): Response
    {
        $response->getBody()->write("Retrieve user. ID:[$id]");
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteUserAction(Request $request, Response $response, $id): Response
    {
        $response->getBody()->write("User deleted. ID:[$id]");
        return $response->withHeader('Content-Type', 'text/html');
    }
}

$router->get('/user/(?<id>[0-9]+)/', [new UserController,'retrieveUserAction']);
$router->delete('/user/(?<id>[0-9]+)/', [new UserController,'deleteUserAction']);


try {

    $router->run(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REDIRECT_URL'] ?? '/'
    );

}catch(RouteNotFoundException $exception){
    // handle 404
}
```