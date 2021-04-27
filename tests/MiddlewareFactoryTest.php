<?php

namespace Tleckie\Router\Test;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tleckie\Router\MiddlewareFactory;

class MiddlewareFactoryTest extends TestCase
{
    /** @var ServerRequestInterface|MockObject */
    private ServerRequestInterface|MockObject $requestMock;

    /** @var RequestHandlerInterface|MockObject */
    private RequestHandlerInterface|MockObject $handlerMock;

    /** @var ResponseInterface|MockObject */
    private ResponseInterface|MockObject $responseMock;

    /** @var Closure */
    private Closure $closure;

    /** @var array */
    private array $params = [
        'name' => 'Mario',
        'id' => 35
    ];

    public function setUp(): void
    {
        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);

        $this->closure = static function (ServerRequestInterface $request, RequestHandlerInterface $handler, array $params) {
            $response = $handler->handle($request);
            $response->getBody()->write(' (MIDDLEWARE)' . $params['name']);

            return $response;
        };
    }

    /**
     * @test
     */
    public function createClosure(): void
    {
        $factory = new MiddlewareFactory();
        $middleware = $factory->create($this->closure, $this->params);

        $this->handlerMock->expects(static::once())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturn($this->responseMock);

        $streamMock = $this->createMock((StreamInterface::class));
        $streamMock->expects(static::once())
            ->method('write')
            ->with(' (MIDDLEWARE)Mario')
            ->willReturn(2);

        $this->responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn($streamMock);

        $middleware->process($this->requestMock, $this->handlerMock);
    }
}
