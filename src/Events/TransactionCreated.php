<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Services\Transactions\Response;

class TransactionCreated
{
    /**
     * TransactionCompleted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest  Data sent to Transbank.
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Response  $response  Raw response from Transbank.
     */
    public function __construct(public ApiRequest $apiRequest, public Response $response)
    {
        //
    }
}
