<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Services\Transactions\Transaction;

class TransactionCompleted
{
    /**
     * Completed transaction.
     *
     * @var \DarkGhostHunter\Transbank\ApiRequest
     * @example Creation, refunds, captures.
     */
    public $apiRequest;

    /**
     * Transaction from Transbank.
     *
     * @var array
     */
    public $transaction;

    /**
     * TransactionCompleted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest  Data sent to Transbank.
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Transaction  $transaction
     */
    public function __construct(ApiRequest $apiRequest, Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->apiRequest = $apiRequest;
    }
}
