<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;

class TransactionCreating
{
    /**
     * Completed transaction.
     *
     * @var \DarkGhostHunter\Transbank\ApiRequest
     * @example Creation, refunds, captures.
     */
    public $apiRequest;

    /**
     * TransactionStarted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     */
    public function __construct(ApiRequest $apiRequest)
    {
        $this->apiRequest = $apiRequest;
    }
}
