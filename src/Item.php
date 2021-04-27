<?php

namespace Tleckie\Router;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use function array_unshift;
use function call_user_func_array;

/**
 * Class Item
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class Item
{
    /** @var Closure|callable */
    private $closure;

    /** @var array */
    private array $params;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    /**
     * Item constructor.
     *
     * @param Closure|callable $callable
     * @param array            $params
     * @param array            $middlewares
     */
    public function __construct(Closure|callable $callable, array $params, array $middlewares = [])
    {
        $this->closure = $callable;
        $this->params = $params;
        $this->middlewares = $middlewares;
    }

    /**
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * @return Closure|callable
     */
    public function callable(): Closure|callable
    {
        return $this->closure;
    }

    public function middleware(): array
    {
        return $this->middlewares;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = [$this->params];

        array_unshift($params, $request, $response);

        return call_user_func_array($this->closure, $params);
    }
}
