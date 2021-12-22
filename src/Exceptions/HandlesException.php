<?php

namespace DarkGhostHunter\Transbank\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use DarkGhostHunter\Transbank\ApiRequest;

trait HandlesException
{
    /**
     * Transbank Exception constructor.
     *
     * @param  string  $message
     * @param  \DarkGhostHunter\Transbank\ApiRequest|null  $apiRequest
     * @param  \Psr\Http\Message\ServerRequestInterface|null  $request
     * @param  \Psr\Http\Message\ResponseInterface|null  $response
     * @param  Throwable|null  $previous
     */
    public function __construct(
        string $message = '',
        protected ?ApiRequest $apiRequest = null,
        protected ?ServerRequestInterface $request = null,
        protected ?ResponseInterface $response = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, static::LOG_LEVEL, $previous);
    }

    /**
     * Returns the ApiRequest of this exception, if any.
     *
     * @return \DarkGhostHunter\Transbank\ApiRequest|null
     */
    public function getApiRequest(): ?ApiRequest
    {
        return $this->apiRequest;
    }

    /**
     * Returns the Server Request sent to Transbank, if any.
     *
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    public function getServerRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Returns the Response from Transbank, if any.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
