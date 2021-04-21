<?php

namespace Tleckie\Router\Test;

use Closure;
use HttpSoft\Emitter\EmitterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Tleckie\Router\Router;


class RouterTest extends TestCase
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

    /** @var ServerRequestFactoryInterface|MockObject */
    private ServerRequestFactoryInterface|MockObject $requestFactoryMock;

    /** @var ResponseFactoryInterface|MockObject */
    private ResponseFactoryInterface|MockObject $responseFactoryMock;

    /** @var EmitterInterface|MockObject */
    private EmitterInterface|MockObject $emitterMock;

    /** @var Router|MockObject */
    private Router|MockObject $router;

    /** @var Closure|callable */
    private $closure;

    public function setUp(): void
    {
        $this->requestFactoryMock = $this->createMock(ServerRequestFactoryInterface::class);
        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->emitterMock = $this->createMock(EmitterInterface::class);

        $this->router = new Router(
            $this->requestFactoryMock,
            $this->responseFactoryMock,
            $this->emitterMock
        );

        $this->closure = static function (ServerRequestInterface $request, ResponseInterface $response, $id, $name) {
            $response->getBody()->write("CONTENT ID:[$id][$name]");

            return $response;
        };
    }

    /**
     * @test
     */
    public function get(): void
    {
        $this->router->get('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('get', '/user/25/jhon/');
    }

    private function initializeMock(string $path): void
    {
        $uriMock = $this->createMock(UriInterface::class);

        $uriMock->expects(static::once())
            ->method('getPath')
            ->willReturn($path);

        $requestMock = $this->createMock(ServerRequestInterface::class);

        $requestMock->expects(static::once())
            ->method('getUri')
            ->willReturn($uriMock);

        $this->requestFactoryMock->expects(static::once())
            ->method('createServerRequest')
            ->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->responseFactoryMock->expects(static::once())
            ->method('createResponse')
            ->willReturn($responseMock);

        $streamMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->emitterMock->expects(static::once())
            ->method('emit')
            ->with($responseMock);
    }

    /**
     * @test
     */
    public function post(): void
    {
        $this->router->post('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('post', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function put(): void
    {
        $this->router->put('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('put', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function delete(): void
    {
        $this->router->delete('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('delete', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function options(): void
    {
        $this->router->options('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('options', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function patch(): void
    {
        $this->router->patch('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('patch', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function response(): void
    {
        $uriMock = $this->createMock(UriInterface::class);

        $uriMock->expects(static::once())
            ->method('getPath')
            ->willReturn('/user/25/jhon/');

        $requestMock = $this->createMock(ServerRequestInterface::class);

        $requestMock->expects(static::once())
            ->method('getUri')
            ->willReturn($uriMock);

        $this->requestFactoryMock->expects(static::once())
            ->method('createServerRequest')
            ->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->responseFactoryMock->expects(static::once())
            ->method('createResponse')
            ->willReturn($responseMock);

        $streamMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(static::never())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->emitterMock->expects(static::never())
            ->method('emit')
            ->with($responseMock);

        $this->router->get('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', function () {
        });

        $this->router->run('get', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function all(): void
    {
        $this->initializeMock('/user/25/jhon/');
        static::assertNull($this->router->all('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure));

        $this->router->run('get', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function head(): void
    {
        $this->router->head('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
        $uriMock = $this->createMock(UriInterface::class);

        $uriMock->expects(static::once())
            ->method('getPath')
            ->willReturn('/user/25/jhon/');

        $requestMock = $this->createMock(ServerRequestInterface::class);

        $requestMock->expects(static::once())
            ->method('getUri')
            ->willReturn($uriMock);

        $this->requestFactoryMock->expects(static::once())
            ->method('createServerRequest')
            ->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->responseFactoryMock->expects(static::exactly(2))
            ->method('createResponse')
            ->willReturn($responseMock);

        $streamMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(static::exactly(2))
            ->method('getBody')
            ->willReturn($streamMock);

        $responseMock->expects(static::once())
            ->method('withBody')
            ->willReturn($responseMock);

        $this->emitterMock->expects(static::once())
            ->method('emit')
            ->with($responseMock);

        $this->router->run('head', '/user/25/jhon/');
    }


}