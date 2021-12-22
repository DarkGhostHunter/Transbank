<?php

namespace DarkGhostHunter\Transbank\Credentials;

use LogicException;
use RuntimeException;

/**
 * Class Container
 * ---
 *
 * This class works as a "container" for all services' credentials. This makes
 * easy for each service to get the credentials using a common interface.
 *
 * @package DarkGhostHunter\Transbank\Credentials
 */
class Container
{
    /**
     * Create a new Credential Container instance.
     *
     * @param  \DarkGhostHunter\Transbank\Credentials\Credentials|null  $webpay
     * @param  \DarkGhostHunter\Transbank\Credentials\Credentials|null  $webpayMall
     * @param  \DarkGhostHunter\Transbank\Credentials\Credentials|null  $oneclickMall
     * @param  \DarkGhostHunter\Transbank\Credentials\Credentials|null  $fullTransaction
     */
    public function __construct(
        protected ?Credentials $webpay = null,
        protected ?Credentials $webpayMall = null,
        protected ?Credentials $oneclickMall = null,
        protected ?Credentials $fullTransaction = null,
    )
    {
        //
    }

    /**
     * Fills the credentials for each service.
     *
     * @param  array<string, string>  $credentials
     * @return void
     */
    public function setFromArray(array $credentials): void
    {
        foreach ($credentials as $service => $credential) {
            // Check if the service name exists. If not, bail.
            $this->throwIfCredentialsDoesntExist($service);

            // We need the array declaring the key and the secret. If not, bail.
            if (!isset($credential['key'], $credential['secret'])) {
                throw new LogicException("Credentials for [$service] must have a [key] and [secret].");
            }

            $this->{$service} = Credentials::make($credential['key'], $credential['secret']);
        }
    }

    /**
     * Returns the credentials for a given service.
     *
     * @param  string  $service
     *
     * @return \DarkGhostHunter\Transbank\Credentials\Credentials
     */
    public function getProductionCredentials(string $service): Credentials
    {
        $this->throwIfCredentialsDoesntExist($service);

        return $this->{$service} ?? throw new RuntimeException("Production credentials for [$service] are not set.");
    }

    /**
     * Checks that credentials for a service name exists.
     *
     * @param  string  $service
     * @return void
     * @throws \LogicException
     */
    protected function throwIfCredentialsDoesntExist(string $service): void
    {
        if (!property_exists($this, $service)) {
            throw new LogicException("The Transbank service [$service] doesn't exist for these credentials.");
        }
    }
}
