<?php

namespace Tleckie\Router;

use Closure;
use HttpSoft\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tleckie\Router\Exception\RouteNotFoundException;

/**
 * Class Router
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class Router
{
    /** @var array */
    private const METHODS = [
        'GET',
        'POST',
        'HEAD',
        'PATCH',
        'OPTIONS',
        'DELETE',
        'PUT',
    ];

    /** @var array[] */
    private array $items;

    /** @var ServerRequestFactoryInterface */
    private ServerRequestFactoryInterface $requestFactory;

    /** @var ResponseFactoryInterface */
    private ResponseFactoryInterface $responseFactory;

    /** @var MiddlewareFactory */
    private MiddlewareFactory $middlewareFactory;

    /** @var EmitterInterface */
    private EmitterInterface $emitter;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    /**
     * Router constructor.
     *
     * @param ServerRequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface      $responseFactory
     * @param EmitterInterface              $emitter
     */
    public function __construct(
        ServerRequestFactoryInterface $requestFactory,
        ResponseFactoryInterface $responseFactory,
        EmitterInterface $emitter
    ) {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->emitter = $emitter;
        $this->middlewares = [];
        $this->middlewareFactory = new MiddlewareFactory();
    }

    /**
     * @param callable|MiddlewareInterface|Closure $middleware
     * @return $this
     */
    public function add(callable|MiddlewareInterface|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function get(string $path, callable ...$callback): void
    {
        $this->pushRule('GET', $path, ...$callback);
    }

    /**
     * @param string   $method
     * @param string   $path
     * @param callable ...$callback
     */
    private function pushRule(string $method, string $path, callable ...$callback): void
    {
        $method = $this->normalizeMethod($method);
        $this->items[$method][$path] = $callback;
    }

    /**
     * @param string $method
     * @return string
     */
    private function normalizeMethod(string $method): string
    {
        return strtoupper($method);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function all(string $path, callable ...$callback): void
    {
        foreach (static::METHODS as $method) {
            $this->pushRule($method, $path, ...$callback);
        }
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function post(string $path, callable ...$callback): void
    {
        $this->pushRule('POST', $path, ...$callback);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function head(string $path, callable ...$callback): void
    {
        $this->pushRule('HEAD', $path, ...$callback);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function patch(string $path, callable ...$callback): void
    {
        $this->pushRule('PATCH', $path, ...$callback);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function options(string $path, callable ...$callback): void
    {
        $this->pushRule('OPTIONS', $path, ...$callback);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function delete(string $path, callable ...$callback): void
    {
        $this->pushRule('DELETE', $path, ...$callback);
    }

    /**
     * @param string   $path
     * @param callable ...$callback
     */
    public function put(string $path, callable ...$callback): void
    {
        $this->pushRule('PUT', $path, ...$callback);
    }

    /**
     * @param string $method
     * @param        $uri
     * @param array  $serverParams
     * @throws RouteNotFoundException
     */
    public function run(string $method, $uri, array $serverParams = []): void
    {
        $method = $this->normalizeMethod($method);
        $response = $this->responseFactory->createResponse();
        $request = $this->requestFactory->createServerRequest($method, $uri, $serverParams);
        $response = $this->handle($request, $response, $method);

        if ('HEAD' === $method) {
            $response = $response->withBody($this->responseFactory->createResponse()->getBody());
        }

        $this->emitter->emit($response);
    }

    /**
     * @param $request
     * @param $response
     * @param $method
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    private function handle($request, $response, $method): ResponseInterface
    {
        $findRoute = new FindRoutes($this->middlewareFactory, $this->items);

        $item = $findRoute->find($method, $request->getUri()->getPath());

        $middlewareDispatcher = new MiddlewareDispatcher(
            $response,
            ...array_reverse($item->middleware()),
            ...array_reverse($this->createMiddlewareWithParams($item->params()))
        );

        return $item->handle($request, $middlewareDispatcher->handle($request));
    }

    /**
     * @param array $params
     * @return MiddlewareInterface[]
     */
    private function createMiddlewareWithParams(array $params = []): array
    {
        foreach ($this->middlewares as $index => $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                $this->middlewares[$index] = $this->middlewareFactory->create($middleware, $params);
            }
        }

        return $this->middlewares;
    }
}
