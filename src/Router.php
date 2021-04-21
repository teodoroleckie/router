<?php

namespace Tleckie\Router;

use HttpSoft\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestFactoryInterface;
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

    /** @var array[string][string][Closure] */
    private array $items;

    /** @var ServerRequestFactoryInterface */
    private ServerRequestFactoryInterface $requestFactory;

    /** @var ResponseFactoryInterface */
    private ResponseFactoryInterface $responseFactory;

    /** @var EmitterInterface */
    private EmitterInterface $emitter;

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
    )
    {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->emitter = $emitter;
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function get(string $path, callable $callback): void
    {
        $this->add('GET', $path, $callback);
    }

    /**
     * @param string   $method
     * @param string   $path
     * @param callable $callback
     */
    private function add(string $method, string $path, callable $callback): void
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
     * @param callable $callback
     */
    public function all(string $path, callable $callback): void
    {
        foreach (static::METHODS as $method) {
            $this->add($method, $path, $callback);
        }
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function post(string $path, callable $callback): void
    {
        $this->add('POST', $path, $callback);
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function head(string $path, callable $callback): void
    {
        $this->add('HEAD', $path, $callback);
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function patch(string $path, callable $callback): void
    {
        $this->add('PATCH', $path, $callback);
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function options(string $path, callable $callback): void
    {
        $this->add('OPTIONS', $path, $callback);
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function delete(string $path, callable $callback): void
    {
        $this->add('DELETE', $path, $callback);
    }

    /**
     * @param string   $path
     * @param callable $callback
     */
    public function put(string $path, callable $callback): void
    {
        $this->add('PUT', $path, $callback);
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

        $route = (new FindRoutes(
            $request,
            $response,
            $this->items)
        )->find($method, $request->getUri()->getPath());

        $response = $route->call();

        if ($response instanceof Response) {
            // This is to be in compliance with RFC 2616, Section 9
            if ('HEAD' === $method) {
                $response = $response->withBody($this->responseFactory->createResponse()->getBody());
            }
            $this->emitter->emit($response);
        }
    }
}