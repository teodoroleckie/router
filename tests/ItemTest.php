<?php

namespace Tleckie\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Item;
use Tleckie\Router\MiddlewareFactory;

/**
 * Class ItemTest
 *
 * @package Tleckie\Router\Test
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class ItemTest extends TestCase
{
    /**
     * @test
     */
    public function closure(): void
    {
        $closure = static function (ServerRequestInterface $request, ResponseInterface $response, array $params) {
            $response->getBody()->write(' (CLOSURE-CONTROLLER)' . $params['id']);

            return $response;
        };

//        $object = new class() {
//            public function action(ServerRequestInterface $request, RequestHandlerInterface $handler, array $params)
//            {
//                $response = $handler->handle($request);
//                $response->getBody()->write('ACTION-CONTROLLER'.$params['id']);
//                return $response;
//            }
//        };
//
        $middlewareClosure = static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
            $response = $handler->handle($request);
            $response->getBody()->write(' (MIDDLEWARE)' . $params['name']);

            return $response;
        };

        $params = [
            'id' => 25, 'name' => 'Mario'
        ];

        $factory = new MiddlewareFactory();

        foreach ([$closure] as $callable) {
            $middleware = $factory->create($middlewareClosure, $params);
            $item = new Item(
                $callable,
                $params,
                [$middleware]
            );

            static::assertEquals($callable, $item->callable());
            static::assertEquals([$middleware], $item->middleware());
            static::assertEquals($params, $item->params());

            $requestMock = $this->createMock(ServerRequestInterface::class);

            $streamMock = $this->createMock((StreamInterface::class));
            $streamMock->expects(static::once())
                ->method('write')
                ->with(' (CLOSURE-CONTROLLER)25')
                ->willReturn(2);

            $responseMock = $this->createMock(ResponseInterface::class);
            $responseMock->expects(static::once())
                ->method('getBody')
                ->willReturn($streamMock);

            $item->handle($requestMock, $responseMock);
        }
    }


    /**
     * @test
     */
    public function object(): void
    {
        $object = new class() {
            public function action(ServerRequestInterface $request, ResponseInterface $response, array $params)
            {
                $response->getBody()->write(' (CLOSURE-CONTROLLER)' . $params['name']);

                return $response;
            }
        };

        $middlewareClosure = static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
            $response = $handler->handle($request);
            $response->getBody()->write(' (MIDDLEWARE)' . $params['name']);

            return $response;
        };

        $params = [
            'id' => 25, 'name' => 'Mario'
        ];

        $factory = new MiddlewareFactory();

        foreach ([[$object, 'action']] as $callable) {
            $middleware = $factory->create($middlewareClosure, $params);
            $item = new Item(
                $callable,
                $params,
                [$middleware]
            );

            static::assertEquals($callable, $item->callable());
            static::assertEquals([$middleware], $item->middleware());
            static::assertEquals($params, $item->params());

            $requestMock = $this->createMock(ServerRequestInterface::class);

            $streamMock = $this->createMock((StreamInterface::class));
            $streamMock->expects(static::once())
                ->method('write')
                ->with(' (CLOSURE-CONTROLLER)Mario')
                ->willReturn(2);

            $responseMock = $this->createMock(ResponseInterface::class);
            $responseMock->expects(static::once())
                ->method('getBody')
                ->willReturn($streamMock);

            $item->handle($requestMock, $responseMock);
        }
    }
}
