<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\ApiRequest;

trait DebugsTransactions
{
    /**
     * Debugs a given operation.
     *
     * @param  string  $message
     * @param  array  $context
     */
    protected function log(string $message, array $context = []): void
    {
        $this->transbank->logger->debug($message, $context);
    }

    /**
     * Debugs a transaction before creating it.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     */
    protected function logCreating(ApiRequest $apiRequest): void
    {
        $this->transbank->logger->debug('Creating transaction', ['api_request' => $apiRequest]);
    }

    /**
     * Debugs a given operation.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     * @param  array  $rawResponse
     * @param  string|null  $token
     */
    protected function logResponse(ApiRequest $apiRequest, array $rawResponse, string $token = null): void
    {
        $context = ['api_request' => $apiRequest, 'raw_response' => $rawResponse];

        if ($token) {
            $context['token'] = $token;
        }

        $this->transbank->logger->debug('Response received', $context);
    }
}
