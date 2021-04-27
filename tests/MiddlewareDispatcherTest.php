<?php

namespace Tleckie\Router\Test;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tleckie\Router\MiddlewareDispatcher;

class MiddlewareDispatcherTest extends TestCase
{
    /** @var MiddlewareDispatcher */
    private MiddlewareDispatcher $dispatcher;

    /** @var ResponseInterface */
    private ResponseInterface $responseMock;

    /** @var MiddlewareInterface|MockObject */
    private MiddlewareInterface|MockObject $middlewareMock;

    /** @var MiddlewareInterface|MockObject */
    private MiddlewareInterface|MockObject $otherMiddlewareMock;

    /**
     * @test
     */
    public function handle(): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);

        $this->middlewareMock->expects(static::once())
            ->method('process')
            ->with($requestMock, $this->dispatcher)
            ->willReturn($this->responseMock);

        $this->otherMiddlewareMock->expects(static::once())
            ->method('process')
            ->with($requestMock, $this->dispatcher)
            ->willReturn($this->responseMock);

        $this->dispatcher->handle($requestMock);
    }

    protected function setUp(): void
    {
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->middlewareMock = $this->createMock(MiddlewareInterface::class);

        $this->otherMiddlewareMock = clone($this->middlewareMock);

        $this->dispatcher = new MiddlewareDispatcher(
            $this->responseMock,
            $this->middlewareMock,
            $this->otherMiddlewareMock
        );
    }
}
