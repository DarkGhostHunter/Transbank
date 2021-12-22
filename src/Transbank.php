<?php

namespace DarkGhostHunter\Transbank;

use Closure;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionFunction;
use RuntimeException;
use DarkGhostHunter\Transbank\Credentials\Container;
use DarkGhostHunter\Transbank\Events\NullDispatcher;
use DarkGhostHunter\Transbank\Http\Connector;

class Transbank
{
    /**
     * Current SDK version.
     *
     * @var string
     */
    public const VERSION = '2.0';

    /**
     * Transbank instance singleton helper.
     *
     * @var Transbank|null
     */
    protected static ?self $instance = null;

    /**
     * Create a new Transbank instance.
     *
     * @param  \DarkGhostHunter\Transbank\Credentials\Container  $credentials
     * @param  \Psr\Log\LoggerInterface  $logger
     * @param  \Psr\EventDispatcher\EventDispatcherInterface  $event
     * @param  \DarkGhostHunter\Transbank\Http\Connector  $connector
     * @param  \DarkGhostHunter\Transbank\Services\Webpay|null  $webpay
     * @param  \DarkGhostHunter\Transbank\Services\WebpayMall|null  $webpayMall
     * @param  \DarkGhostHunter\Transbank\Services\OneclickMall|null  $oneclickMall
     * @param  bool  $production
     */
    public function __construct(
        protected Container $credentials,
        public LoggerInterface $logger,
        public EventDispatcherInterface $event,
        public Http\Connector $connector,
        protected ?Services\Webpay $webpay = null,
        protected ?Services\WebpayMall $webpayMall = null,
        protected ?Services\OneclickMall $oneclickMall = null,
        protected bool $production = false,
    ) {
        //
    }

    /**
     * Creates a new Transbank instance using Guzzle as HTTP Client.
     *
     * @return static
     * @codeCoverageIgnore
     */
    public static function make(): static
    {
        // Get one of the two clients HTTP Clients and try to use them if they're installed.
        $client = match (true) {
            class_exists(\GuzzleHttp\Client::class) => new \GuzzleHttp\Client(),
            class_exists(\Symfony\Component\HttpClient\Psr18Client::class) => new \Symfony\Component\HttpClient\Psr18Client(),
            default => throw new RuntimeException(
                'The "guzzlehttp/guzzle" or "symfony/http-client" libraries are not present. Install one or use your own PSR-18 HTTP Client.'
            ),
        };

        return new static(
            new Container(),
            new NullLogger(),
            new NullDispatcher(),
            new Connector($client, $factory = new Psr17Factory(), $factory)
        );
    }

    /**
     * Returns the Transbank single instance.
     *
     * @return static
     */
    public static function getInstance(): self
    {
        return static::$instance ??= static::make();
    }

    /**
     * Sets the Transbank instance.
     *
     * @param  static|null  $instance
     * @return void
     */
    public static function setInstance(self $instance = null): void
    {
        static::$instance = $instance;
    }

    /**
     * Sets all the Transbank services to run in production servers.
     *
     * Supported services:
     *      - webpay
     *      - webpayMall
     *      - oneclickMall
     *      - fullTransaction
     *      - fullTransactionMall
     *
     * @param  array<array<string,string>>  $credentials
     * @return $this
     */
    public function toProduction(array $credentials): static
    {
        if (empty($credentials)) {
            throw new LogicException('Cannot set empty credentials for production environment.');
        }

        $this->credentials->setFromArray($credentials);

        $this->production = true;

        $this->logger->debug(
            'Transbank has been set to production environment.',
            ['credentials' => array_keys($credentials)]
        );

        return $this;
    }

    /**
     * Returns the SDK to integration environment.
     *
     * @return $this
     */
    public function toIntegration(): static
    {
        $this->production = false;

        $this->logger->debug('Transbank has been set to integration environment.');

        return $this;
    }

    /**
     * Check if the current Transbank SDK are running in integration environment.
     *
     * @return bool
     */
    public function isIntegration(): bool
    {
        return !$this->isProduction();
    }

    /**
     * Check if the current Transbank SDK are running in production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->production;
    }

    /**
     * Returns the Webpay service.
     *
     * @return \DarkGhostHunter\Transbank\Services\Webpay
     */
    public function webpay(): Services\Webpay
    {
        return $this->webpay ??= new Services\Webpay($this, $this->credentials);
    }

    /**
     * Returns the Webpay Mall service.
     *
     * @return \DarkGhostHunter\Transbank\Services\WebpayMall
     */
    public function webpayMall(): Services\WebpayMall
    {
        return $this->webpayMall ??= new Services\WebpayMall($this, $this->credentials);
    }

    /**
     * Returns the Oneclick Mall service.
     *
     * @return \DarkGhostHunter\Transbank\Services\OneclickMall
     */
    public function oneclickMall(): Services\OneclickMall
    {
        return $this->oneclickMall ??= new Services\OneclickMall($this, $this->credentials);
    }
}
