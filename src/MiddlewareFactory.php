<?php

namespace Tleckie\Router;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function call_user_func;

/**
 * Class MiddlewareFactory
 *
 * @package Tleckie\Router
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class MiddlewareFactory
{
    /**
     * @param callable|Closure $callable
     * @param array            $params
     * @return MiddlewareInterface
     */
    public function create(callable|Closure $callable, array $params = []): MiddlewareInterface
    {
        return new class($callable, $params) implements MiddlewareInterface {
            /** @var callable */
            private $callable;

            /** @var array */
            private array $params;

            /**
             *  constructor.
             *
             * @param callable $callable
             * @param array    $params
             */
            public function __construct(callable $callable, array $params)
            {
                $this->callable = $callable;
                $this->params = $params;
            }

            /**
             * @param ServerRequestInterface  $request
             * @param RequestHandlerInterface $handler
             * @return ResponseInterface
             */
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return call_user_func($this->callable, $request, $handler, $this->params);
            }
        };
    }
}
