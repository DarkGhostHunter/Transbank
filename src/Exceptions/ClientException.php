<?php

namespace DarkGhostHunter\Transbank\Exceptions;

use RuntimeException;

class ClientException extends RuntimeException implements TransbankException
{
    use HandlesException;

    /**
     * The log level to report to the app.
     *
     * @var string
     */
    public const LOG_LEVEL = LOG_ERR;
}
