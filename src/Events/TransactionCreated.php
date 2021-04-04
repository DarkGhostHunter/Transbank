<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Services\Transactions\Response;

class TransactionCreated
{
    /**
     * API Request sent.
     *
     * @var \DarkGhostHunter\Transbank\ApiRequest
     * @example Creation, refunds, captures.
     */
    public $apiRequest;

    /**
     * Transaction Response
     *
     * @var \DarkGhostHunter\Transbank\Services\Transactions\Response
     */
    public $response;

    /**
     * TransactionCompleted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest  Data sent to Transbank.
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Response  $response  Raw response from Transbank.
     */
    public function __construct(ApiRequest $apiRequest, Response $response)
    {
        $this->response = $response;
        $this->apiRequest = $apiRequest;
    }
}
