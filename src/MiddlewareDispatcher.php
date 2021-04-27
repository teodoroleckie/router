<?php

namespace Tleckie\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function array_filter;
use function array_shift;

/**
 * Class MiddlewareDispatcher
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middlewares;

    /** @var ResponseInterface */
    private ResponseInterface $response;

    /**
     * MiddlewareDispatcher constructor.
     *
     * @param ResponseInterface   $response
     * @param MiddlewareInterface ...$middlewares
     */
    public function __construct(ResponseInterface $response, MiddlewareInterface ...$middlewares)
    {
        $this->response = $response;
        $this->middlewares = $middlewares;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middlewares);

        return $middleware
            ? $middleware->process($request, $this->withoutMiddleware($middleware))
            : $this->response;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return RequestHandlerInterface
     */
    private function withoutMiddleware(MiddlewareInterface $middleware): RequestHandlerInterface
    {
        $collection = array_filter(
            $this->middlewares,
            static function ($current) use ($middleware) {
                return $middleware !== $current;
            }
        );

        return new self($this->response, ...$collection);
    }
}
