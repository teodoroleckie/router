<?php

namespace Tleckie\Router;

use Tleckie\Router\Exception\RouteNotFoundException;
use function array_pop;
use function is_numeric;
use function preg_match_all;

/**
 * Class FindRoutes
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class FindRoutes
{
    /** @var MiddlewareFactory */
    private MiddlewareFactory $middlewareFactory;

    /** @var array */
    private array $routes;

    /**
     * FindRoutes constructor.
     *
     * @param MiddlewareFactory $middlewareFactory
     * @param array             $routes
     */
    public function __construct(
        MiddlewareFactory $middlewareFactory,
        array $routes
    ) {
        $this->middlewareFactory = $middlewareFactory;
        $this->routes = $routes;
    }

    /**
     * @param string $method
     * @param string $path
     * @return Item
     * @throws RouteNotFoundException
     */
    public function find(string $method, string $path): Item
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $closures) {
            if (null !== $params = $this->matchRoute($pattern, $path)) {
                $params = $this->removeIntegerKeyParam($params);

                $closure = $this->extractClosure($closures);

                $middlewares = $this->factorizeMiddleware($closures, $params);

                return new Item($closure, $params, $middlewares);
            }
        }

        throw new RouteNotFoundException('Routes not match');
    }

    /**
     * @param string $pattern
     * @param string $path
     * @return array|bool
     */
    private function matchRoute(string $pattern, string $path): ?array
    {
        if (preg_match_all("#^{$pattern}$#", $path, $matches, PREG_SET_ORDER)) {
            return $matches[0] ?? [];
        }

        return null;
    }

    /**
     * @param array $params
     * @return array
     */
    private function removeIntegerKeyParam(array $params): array
    {
        $returnParams = [];
        foreach ($params as $paramKey => $paramValue) {
            if (!is_numeric($paramKey)) {
                $returnParams[$paramKey] = $paramValue;
            }
        }

        return $returnParams;
    }

    /**
     * @param array $closures
     * @return Closure|callable
     */
    private function extractClosure(array &$closures): Closure|callable
    {
        return array_pop($closures);
    }

    /**
     * @param array $closures
     * @param array $params
     * @return array
     */
    private function factorizeMiddleware(array $closures, array $params): array
    {
        $middleware = [];
        foreach ($closures as $closure) {
            $middleware[] = $this->middlewareFactory->create($closure, $params);
        }

        return $middleware;
    }
}
