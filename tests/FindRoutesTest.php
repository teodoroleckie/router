<?php

namespace Tleckie\Router\Test;


use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tleckie\Router\Exception\RouteNotFoundException;
use Tleckie\Router\FindRoutes;

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

    /** @var array */
    private array $routes = [];

    public function setUp(): void
    {
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->routes['GET']['/user/(?<id>[0-9]+)/(?<name>[a-z]+)/'] = static function (ServerRequestInterface $request, ResponseInterface $response, $id, $name) {
            $response->getBody()->write("New User. ID:[$id][$name]");

            return $response;
        };


        $this->findRoutes = new FindRoutes(
            $this->requestMock,
            $this->responseMock,
            $this->routes
        );
    }

    /**
     * @test
     */
    public function find(): void
    {
        $item = $this->findRoutes->find('GET', '/user/255/jhon/');

        static::assertEquals($this->routes['GET']['/user/(?<id>[0-9]+)/(?<name>[a-z]+)/'], $item->callable());

        $expectedParam = [$this->requestMock, $this->responseMock, 255, 'jhon'];
        foreach ($item->params() as $index => $param) {
            static::assertEquals($expectedParam[$index], $param);
        }

        static::assertCount(4, $item->params());
        static::assertEquals($this->routes['GET']['/user/(?<id>[0-9]+)/(?<name>[a-z]+)/'], $item->callable());
    }

    /**
     * @test
     */
    public function findThrowException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->findRoutes->find('GET', '/user/255/');
    }
}