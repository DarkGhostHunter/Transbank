<?php

namespace Tests\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use DarkGhostHunter\Transbank\Credentials\Container;
use DarkGhostHunter\Transbank\Http\Connector;
use DarkGhostHunter\Transbank\Transbank;

trait TestsServices
{
    /** @var Transbank */
    protected $transbank;
    protected $handlerStack;
    /** @var \Mockery\MockInterface|\Psr\Log\LoggerInterface */
    protected $logger;
    /** @var \Mockery\MockInterface|\Psr\EventDispatcher\EventDispatcherInterface */
    protected $dispatcher;
    /** @var array<array<\GuzzleHttp\Psr7\ServerRequest>> */
    protected $requests;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->requests = [];
        $this->handlerStack = HandlerStack::create();
        $this->handlerStack->push(Middleware::history($this->requests));

        $connector = new Connector(
            new Client(['handler' => $this->handlerStack]), $factory = new Psr17Factory(), $factory
        );

        $this->transbank = new Transbank(new Container(), $this->logger, $this->dispatcher, $connector);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
