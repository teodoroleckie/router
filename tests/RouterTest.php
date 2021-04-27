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
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\Router;

/**
 * Class RouterTest
 *
 * @package Tleckie\Router\Test
 * @author  Teodoro Leckie Westberg <teodoroleckie@gmail.com>
 */
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

    /** @var Closure */
    private Closure $closureMiddleware;

    /** @var Closure */
    private Closure $otherClosureMiddleware;

    /** @var Closure */
    private Closure $sameClosureMiddleware;

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

        $this->closureMiddleware =
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
                $response = $handler->handle($request);
                $response->getBody()->write('(MIDDLEWARE)' . $params['name']);

                return $response;
            };

        $this->otherClosureMiddleware =
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
                $response = $handler->handle($request);
                $response->getBody()->write('(OTHER-MIDDLEWARE)' . $params['name']);

                return $response;
            };

        $this->closure = static function (ServerRequestInterface $request, ResponseInterface $response, array $params) {
            $response->getBody()->write(sprintf("CONTENT ID:[%s][%s]", $params['id'], $params['name']));

            return $response;
        };
    }

    /**
     * @test
     */
    public function all(): void
    {
        $this->router->all(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );
        $this->router->add($this->closureMiddleware);

        $this->initializeMock('/user/25/jhon/');
        $this->router->run('get', '/user/25/jhon/');
    }

    private function initializeMock(string $path): void
    {
        $uriMock = $this->createUriMock($path);

        $this->createRequestMock($uriMock);

        $responseMock = $this->createResponseMock();

        $this->createStreamMock($responseMock, 4);

        $this->emitterMock
            ->expects(static::once())
            ->method('emit')
            ->with($responseMock);
    }

    /**
     * @param string $path
     * @param int    $calls
     * @return MockObject|UriInterface
     */
    private function createUriMock(string $path, int $calls = 1): MockObject|UriInterface
    {
        $uriMock = $this->createMock(UriInterface::class);

        $uriMock->expects(static::exactly($calls))
            ->method('getPath')
            ->willReturn($path);

        return $uriMock;
    }

    /**
     * @param UriInterface|MockObject $uriMock
     * @param int                     $calls
     */
    private function createRequestMock(UriInterface|MockObject $uriMock, int $calls = 1): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);

        $requestMock->expects(static::exactly($calls))
            ->method('getUri')
            ->willReturn($uriMock);

        $this->requestFactoryMock->expects(static::exactly($calls))
            ->method('createServerRequest')
            ->willReturn($requestMock);
    }

    /**
     * @param int $calls
     * @return MockObject|ResponseInterface
     */
    private function createResponseMock(int $calls = 1): MockObject|ResponseInterface
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->responseFactoryMock->expects(static::exactly($calls))
            ->method('createResponse')
            ->willReturn($responseMock);

        return $responseMock;
    }

    /**
     * @param MockObject|ResponseInterface $responseMock
     * @param int                          $calls
     */
    private function createStreamMock(MockObject|ResponseInterface $responseMock, int $calls): void
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects(static::exactly($calls))
            ->method('write')
            ->withConsecutive(['(MIDDLEWARE)jhon'], ['(MIDDLEWARE)jhon'], ['(OTHER-MIDDLEWARE)jhon'], ['CONTENT ID:[25][jhon]']);

        $responseMock->expects(static::exactly($calls))
            ->method('getBody')
            ->willReturn($streamMock);

        $responseMock->method('withBody')
            ->willReturn($responseMock);
    }

    /**
     * @test
     */
    public function head(): void
    {
        $this->router->head(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );
        $this->router->add($this->closureMiddleware);

        $path = '/user/25/jhon/';

        $uriMock = $this->createUriMock($path);

        $this->createRequestMock($uriMock);

        $responseMock = $this->createResponseMock(2);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects(static::exactly(4))
            ->method('write')
            ->withConsecutive(['(MIDDLEWARE)jhon'], ['(MIDDLEWARE)jhon'], ['(OTHER-MIDDLEWARE)jhon'], ['CONTENT ID:[25][jhon]']);

        $responseMock->expects(static::exactly(5))
            ->method('getBody')
            ->willReturn($streamMock);

        $responseMock->method('withBody')
            ->willReturn($responseMock);

        $this->emitterMock
            ->expects(static::once())
            ->method('emit')
            ->with($responseMock);

        $this->router->run('head', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function get(): void
    {
        $this->router->get(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);

        $this->initializeMock('/user/25/jhon/');
        $this->router->run('get', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function post(): void
    {
        $this->router->post(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('post', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function put(): void
    {
        $this->router->put(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('put', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function delete(): void
    {
        $this->router->delete(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('delete', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function options(): void
    {
        $this->router->options(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('options', '/user/25/jhon/');
    }

    /**
     * @test
     */
    public function patch(): void
    {
        $this->router->patch(
            '/user/(?<id>[0-9]+)/(?<name>[a-z]+)/',
            $this->closureMiddleware,
            $this->otherClosureMiddleware,
            $this->closure
        );

        $this->router->add($this->closureMiddleware);
        $this->initializeMock('/user/25/jhon/');
        $this->router->run('patch', '/user/25/jhon/');
    }

    /**
     *
     */
    public function response(): void
    {
        $uriMock = $this->createMock(UriInterface::class);

        $uriMock->expects(static::once())
            ->method('getPath')
            ->willReturn('/user/25/jhon/');

        $this->createRequestMock($uriMock);

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

        $this->router->get('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', static function () {
        });

        $this->router->run('get', '/user/25/jhon/');
    }

    /**
     * @test
     */
//    public function all(): void
//    {
//        $this->initializeMock('/user/25/jhon/');
//        static::assertNull($this->router->all('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure));
//
//        $this->router->run('get', '/user/25/jhon/');
//    }

    /**
     * @test
     */
//    public function head(): void
//    {
//        $this->router->head('/user/(?<id>[0-9]+)/(?<name>[a-z]+)/', $this->closure);
//        $uriMock = $this->createMock(UriInterface::class);
//
//        $uriMock->expects(static::once())
//            ->method('getPath')
//            ->willReturn('/user/25/jhon/');
//
//        $requestMock = $this->createMock(ServerRequestInterface::class);
//
//        $requestMock->expects(static::once())
//            ->method('getUri')
//            ->willReturn($uriMock);
//
//        $this->requestFactoryMock->expects(static::once())
//            ->method('createServerRequest')
//            ->willReturn($requestMock);
//
//        $responseMock = $this->createMock(ResponseInterface::class);
//
//        $this->responseFactoryMock->expects(static::exactly(2))
//            ->method('createResponse')
//            ->willReturn($responseMock);
//
//        $streamMock = $this->createMock(StreamInterface::class);
//
//        $responseMock->expects(static::exactly(2))
//            ->method('getBody')
//            ->willReturn($streamMock);
//
//        $responseMock->expects(static::once())
//            ->method('withBody')
//            ->willReturn($responseMock);
//
//        $this->emitterMock->expects(static::once())
//            ->method('emit')
//            ->with($responseMock);
//
//        $this->router->run('head', '/user/25/jhon/');
//    }
}
