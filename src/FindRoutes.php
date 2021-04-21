<?php

namespace Tleckie\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tleckie\Router\Exception\RouteNotFoundException;

/**
 * Class FindRoutes
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class FindRoutes
{
    /** @var ServerRequestInterface */
    private ServerRequestInterface $request;

    /** @var ResponseInterface */
    private ResponseInterface $response;

    /** @var array */
    private array $routes;

    /**
     * FindRoutes constructor.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $routes
     */
    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routes
    )
    {
        $this->request = $request;
        $this->response = $response;
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
        foreach ($this->routes[$method] ?? [] as $pattern => $closure) {
            if (null !== $value = $this->matchRoute($pattern, $path)) {
                return new Item($closure, $value);
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
            $params = array_slice($matches[0], 1) ?? [];
            foreach ([$this->response, $this->request] as $item) {
                array_unshift($params, $item);
            }

            return $params;
        }

        return null;
    }
}