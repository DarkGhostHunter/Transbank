<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\Credentials\Credentials;

/**
 * Trait HandlesCredentials
 *
 * @package DarkGhostHunter\Transbank\Services
 */
trait HandlesCredentials
{
    /**
     * Returns the set of credentials for the current environment.
     *
     * @param  string  $overrideServiceName
     *
     * @return Credentials
     */
    protected function getEnvironmentCredentials(string $overrideServiceName): Credentials
    {
        if ($this->transbank->isProduction()) {
            return $this->container->getProductionCredentials(static::SERVICE_NAME);
        }

        // If we're running on integration, there is no harm on creating new credentials for each request.
        return Credentials::integrationCredentials($overrideServiceName);
    }
}
