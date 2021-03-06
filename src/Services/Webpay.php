<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Credentials\Container;
use DarkGhostHunter\Transbank\Transbank;

class Webpay
{
    use FiresEvents;
    use DebugsTransactions;
    use SendsRequests;

    // Services names.
    protected const SERVICE_NAME = 'webpay';
    protected const ACTION_CREATE = self::SERVICE_NAME . '.create';
    protected const ACTION_COMMIT = self::SERVICE_NAME . '.commit';
    protected const ACTION_STATUS = self::SERVICE_NAME . '.status';
    protected const ACTION_REFUND = self::SERVICE_NAME . '.refund';
    protected const ACTION_CAPTURE = self::SERVICE_NAME . '.capture';

    /**
     * The API base URI.
     *
     * @var string
     */
    protected const ENDPOINT_BASE = 'rswebpaytransaction/api/webpay/{api_version}/';

    // Endpoints for the transactions.
    public const ENDPOINT_CREATE = self::ENDPOINT_BASE . 'transactions';
    public const ENDPOINT_COMMIT = self::ENDPOINT_BASE . 'transactions/{token}';
    public const ENDPOINT_REFUND = self::ENDPOINT_BASE . 'transactions/{token}/refunds';
    public const ENDPOINT_STATUS = self::ENDPOINT_BASE . 'transactions/{token}';
    public const ENDPOINT_CAPTURE = self::ENDPOINT_BASE . 'transactions/{token}/capture';

    /**
     * Webpay constructor.
     *
     * @param  \DarkGhostHunter\Transbank\Transbank  $transbank
     * @param  \DarkGhostHunter\Transbank\Credentials\Container  $container
     */
    public function __construct(public Transbank $transbank, public Container $container)
    {
        //
    }

    /**
     * Creates a ApiRequest on Transbank, returns a response from it.
     *
     * @param  string  $buyOrder
     * @param  int|float  $amount
     * @param  string  $returnUrl
     * @param  string|null  $sessionId
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Response
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function create(
        string $buyOrder,
        int|float $amount,
        string $returnUrl,
        ?string $sessionId,
        array $options = []
    ): Transactions\Response {
        $apiRequest = new ApiRequest(
            static::ACTION_CREATE,
            [
                'buy_order' => $buyOrder,
                'amount' => $amount,
                'session_id' => $sessionId,
                'return_url' => $returnUrl,
            ]
        );

        $this->logCreating($apiRequest);
        $this->fireCreating($apiRequest);

        $response = $this->send(static::SERVICE_NAME, $apiRequest, 'post', self::ENDPOINT_CREATE, [], $options);
        $transbankResponse = new Transactions\Response($response['token'], $response['url']);

        $this->fireCreated($apiRequest, $transbankResponse);
        $this->logResponse($apiRequest, $response);

        return $transbankResponse;
    }

    /**
     * Commits a transaction immediately
     *
     * @param  string  $token
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function commit(string $token, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(static::ACTION_COMMIT);

        $this->log('Committing transaction', ['token' => $token, 'api_request' => $apiRequest]);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'put',
            static::ENDPOINT_COMMIT,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_COMMIT, $response);

        $this->logResponse($apiRequest, $response, $token);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Returns the status of a non-expired transaction by its token.
     *
     * @param  string  $token
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function status(string $token, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(self::ACTION_STATUS);

        $this->log('Retrieving transaction status', ['token' => $token, 'api_request' => $apiRequest]);

        $response = $this->send(
            self::SERVICE_NAME,
            $apiRequest,
            'get',
            self::ENDPOINT_STATUS,
            ['{token}' => $token],
            $options
        );

        $this->logResponse($apiRequest, $response, $token);

        return new Transactions\Transaction(static::ACTION_STATUS, $response);
    }

    /**
     * Refunds partially or totally a given credit-card charge amount.
     *
     * @param  string  $token
     * @param  int|float  $amount
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function refund(string $token, int|float $amount, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(static::ACTION_REFUND, ['amount' => $amount]);

        $this->log('Refunding transaction', ['token' => $token, 'api_request' => $apiRequest]);

        $this->fireCreating($apiRequest);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'put',
            self::ENDPOINT_REFUND,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_REFUND, $response);

        $this->logResponse($apiRequest, $response, $token);

        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Creates a Capture ApiRequest on Transbank servers, returns a response.
     *
     * This transaction type only works for credit cards, and "holds" the amount up to 7 days.
     *
     * @param  string  $token
     * @param  string  $buyOrder
     * @param  int  $authorizationCode
     * @param  int|float  $captureAmount
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function capture(
        string $token,
        string $buyOrder,
        int $authorizationCode,
        int|float $captureAmount,
        array $options = []
    ): Transactions\Transaction {
        $apiRequest = new ApiRequest(
            static::ACTION_CAPTURE,
            [
                'buy_order' => $buyOrder,
                'authorization_code' => $authorizationCode,
                'capture_amount' => $captureAmount,
            ]
        );

        $this->log('Capturing transaction', ['token' => $token, 'api_request' => $apiRequest]);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'put',
            self::ENDPOINT_CAPTURE,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_CAPTURE, $response);

        $this->logResponse($apiRequest, $response, $token);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }
}
