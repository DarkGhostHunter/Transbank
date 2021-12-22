<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Events\TransactionCreated;
use DarkGhostHunter\Transbank\Events\TransactionCompleted;
use DarkGhostHunter\Transbank\Events\TransactionCreating;
use DarkGhostHunter\Transbank\Services\Transactions\Response;
use DarkGhostHunter\Transbank\Services\Transactions\Transaction;

trait FiresEvents
{
    /**
     * Fires a Transaction Started event.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     */
    protected function fireCreating(ApiRequest $apiRequest): void
    {
        $this->transbank->event->dispatch(new TransactionCreating($apiRequest));
    }

    /**
     * Fires a Transaction Created event.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Response  $response
     */
    protected function fireCreated(ApiRequest $apiRequest, Response $response): void
    {
        $this->transbank->event->dispatch(new TransactionCreated($apiRequest, $response));
    }

    /**
     * Fires a Transaction Completed event.
     *
     * @param  \DarkGhostHunter\Transbank\ApiRequest  $apiRequest
     * @param  \DarkGhostHunter\Transbank\Services\Transactions\Transaction  $transaction
     */
    protected function fireCompleted(ApiRequest $apiRequest, Transaction $transaction): void
    {
        $this->transbank->event->dispatch(new TransactionCompleted($apiRequest, $transaction));
    }
}
