<?php

namespace DarkGhostHunter\Transbank\Credentials;

use RuntimeException;

class Credentials
{
    /**
     * Integrations Keys for each service.
     *
     * @var array<int>
     */
    public const INTEGRATION_KEYS = [
        'webpay' => 597055555532,
        'webpayMall' => 597055555535,
        'webpayMall.capture' => 597055555531,
        'oneclickMall' => 597055555541,
        'oneclickMall.capture' => 597055555547,
        'fullTransaction' => 597055555530,
        'fullTransaction.capture' => 597055555531,
        'fullTransactionMall' => 597055555551,
        'fullTransactionMall.capture' => 597055555531,
    ];

    /**
     * Integration shared secret.
     *
     * @var string
     */
    public const INTEGRATION_SECRET = '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';

    /**
     * Service key, usually the Commerce Code.
     *
     * @var string|null
     */
    public ?string $key = null;

    /**
     * Service shared secret.
     *
     * @var string|null
     */
    public ?string $secret = null;

    /**
     * Create a new Credentials instance.
     *
     * @param  string|null  $key
     * @param  string|null  $secret
     */
    public function __construct(?string $key = null, ?string $secret = null)
    {
        $this->secret = $secret;
        $this->key = $key;
    }

    /**
     * Instance a new Credential object with the key and secret.
     *
     * @param  string  $key
     * @param  string  $secret
     * @return static
     */
    public static function make(string $key, string $secret): static
    {
        return new static($key, $secret);
    }

    /**
     * Returns integration key for a given service name.
     *
     * @param  string  $service
     * @return static
     */
    public static function integrationCredentials(string $service): static
    {
        if (!isset(static::INTEGRATION_KEYS[$service])) {
            throw new RuntimeException("The integration key for [$service] doesn't exist.");
        }

        return static::make(static::INTEGRATION_KEYS[$service], static::INTEGRATION_SECRET);
    }
}
