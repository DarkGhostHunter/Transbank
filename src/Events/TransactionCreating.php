<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;

class TransactionCreating
{
    /**
     * TransactionStarted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     */
    public function __construct(public ApiRequest $apiRequest)
    {
        //
    }
}
