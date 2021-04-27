<?php

namespace Tleckie\Router\Test;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Exception\RouteNotFoundException;
use Tleckie\Router\FindRoutes;
use Tleckie\Router\MiddlewareFactory;

/**
 * Class FindRoutesTest
 *
 * @package Tleckie\Router\Test
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
class FindRoutesTest extends TestCase
{
    /** @var FindRoutes */
    private FindRoutes $findRoutes;

    /** @var ServerRequestInterface|MockObject */
    private ServerRequestInterface|MockObject $requestMock;

    /** @var ResponseInterface|MockObject */
    private ResponseInterface|MockObject $responseMock;

    private MiddlewareFactory|MockObject $middlewareFactoryMock;

    /** @var Closure */
    private Closure $closureMiddleware;

    /** @var array */
    private array $routes = [];

    public function setUp(): void
    {
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->middlewareFactoryMock = $this->createMock(MiddlewareFactory::class);

        $this->closureMiddleware =
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
                $response = $handler->handle($request);
                $response->getBody()->write(' (MIDDLEWARE)' . $params['name']);

                return $response;
            };

        $this->routes['GET']['/user/(?<id>[0-9]+)/(?<name>[a-z]+)/(?<age>[0-9]+)/'] = [
            $this->closureMiddleware,
            $this->closureMiddleware,
            $this->closureMiddleware,
            $this->closureMiddleware,
            static function (ServerRequestInterface $request, ResponseInterface $response, array $params) {
                $response->getBody()->write(
                    sprintf("New User. ID:[%s][%s]", $params['id'], $params['name'])
                );

                return $response;
            }
        ];

        $this->findRoutes = new FindRoutes(
            $this->middlewareFactoryMock,
            $this->routes
        );
    }

    /**
     * @test
     */
    public function find(): void
    {
        $expectedParam = ['id' => '255', 'name' => 'pedro', 'age' => '55'];

        $this->middlewareFactoryMock->expects(static::exactly(4))
            ->method('create')
            ->with($this->closureMiddleware, $expectedParam);

        $item = $this->findRoutes->find('GET', '/user/255/pedro/55/');

        static::assertEquals($expectedParam['id'], $item->params()['id']);

//        foreach ($item->params() as $index => $param) {
//            static::assertEquals($expectedParam[$index], $param);
//        }

        static::assertInstanceOf(Closure::class, $item->callable());
        static::assertCount(4, $item->middleware());
        static::assertCount(3, $item->params());
    }

    /**
     * @test
     */
    public function findThrowException(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $this->findRoutes->find('GET', '/user/255/55/other/value/');
    }
}
