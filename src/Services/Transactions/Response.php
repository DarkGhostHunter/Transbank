<?php

namespace DarkGhostHunter\Transbank\Services\Transactions;

class Response
{
    /**
     * Response constructor.
     *
     * @param  string  $token
     * @param  string  $url
     */
    public function __construct(protected string $token, protected string $url)
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
        return $this->url . '?' . http_build_query(['token_ws' => $this->token]);
    }
}
