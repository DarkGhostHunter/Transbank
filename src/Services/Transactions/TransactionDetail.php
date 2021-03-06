<?php

namespace DarkGhostHunter\Transbank\Services\Transactions;

use ArrayAccess;
use JsonSerializable;

class TransactionDetail implements ArrayAccess, JsonSerializable
{
    use DynamicallyAccess;

    /**
     * ApiRequest constructor.
     *
     * @param  array  $data
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Checks if the transaction was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return isset($this->data['response_code']) && $this->data['response_code'] === 0;
    }
}
