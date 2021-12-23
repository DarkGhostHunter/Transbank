<?php

namespace DarkGhostHunter\Transbank\Services\Transactions;

use Stringable;

class Response implements Stringable
{
    /**
     * Response constructor.
     *
     * @param  string  $token
     * @param  string  $url
     * @param  string  $tokenName
     */
    public function __construct(
        protected string $token,
        protected string $url,
        protected string $tokenName = 'token_ws')
    {
        //
    }

    /**
     * Returns the transaction token that identifies it on Transbank.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Returns the transaction URL where the transaction can be retrieved.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Transforms the Response into a String for Webpay GET redirects.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->url . '?' . http_build_query([$this->tokenName => $this->token]);
    }
}
