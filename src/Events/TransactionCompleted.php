<?php

namespace DarkGhostHunter\Transbank\Events;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Services\Transactions\Transaction;

class TransactionCompleted
{
    /**
     * TransactionCompleted constructor.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest  Data sent to Transbank.
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Transaction  $transaction
     */
    public function __construct(public ApiRequest $apiRequest, public Transaction $transaction)
    {
        //
    }
}
