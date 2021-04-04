<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Credentials\Container;
use DarkGhostHunter\Transbank\Transbank;

class WebpayMall
{
    use FiresEvents;
    use DebugsTransactions;
    use SendsRequests;
    use WrapsDetail;

    protected const SERVICE_NAME = 'webpayMall';
    protected const ACTION_CREATE = self::SERVICE_NAME . '.create';
    protected const ACTION_COMMIT = self::SERVICE_NAME . '.commit';
    protected const ACTION_STATUS = self::SERVICE_NAME . '.status';
    protected const ACTION_REFUND = self::SERVICE_NAME . '.refund';
    protected const ACTION_CAPTURE = self::SERVICE_NAME . '.capture';

    // Endpoints for the transactions.
    public const ENDPOINT_CREATE = Webpay::ENDPOINT_CREATE;
    public const ENDPOINT_COMMIT = Webpay::ENDPOINT_COMMIT;
    public const ENDPOINT_REFUND = Webpay::ENDPOINT_REFUND;
    public const ENDPOINT_STATUS = Webpay::ENDPOINT_STATUS;
    public const ENDPOINT_CAPTURE = Webpay::ENDPOINT_CAPTURE;

    /**
     * Transbank instance.
     *
     * @var \DarkGhostHunter\Transbank\Transbank
     */
    protected $transbank;

    /**
     * Credential Container instance.
     *
     * @var \DarkGhostHunter\Transbank\Credentials\Container
     */
    protected $container;

    /**
     * Webpay constructor.
     *
     * @param  \DarkGhostHunter\Transbank\Transbank  $transbank
     * @param  \DarkGhostHunter\Transbank\Credentials\Container  $container
     */
    public function __construct(Transbank $transbank, Container $container)
    {
        $this->container = $container;
        $this->transbank = $transbank;
    }

    /**
     * Creates a Webpay Mall transaction.
     *
     * @param  string  $buyOrder
     * @param  string  $returnUrl
     * @param  string  $sessionId
     * @param  array  $details
     * @param  array  $options
     *
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Response
     * @throws \DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function create(
        string $buyOrder,
        string $returnUrl,
        string $sessionId,
        array $details,
        array $options = []
    ): Transactions\Response {
        $apiRequest = new ApiRequest(
            static::ACTION_CREATE,
            [
                'buy_order' => $buyOrder,
                'session_id' => $sessionId,
                'return_url' => $returnUrl,
                'details' => static::wrapDetails($details),
            ]
        );

        $this->logCreating($apiRequest);
        $this->fireCreating($apiRequest);

        $response = $this->send(self::SERVICE_NAME, $apiRequest, 'post', static::ENDPOINT_CREATE, [], $options);

        $transbankResponse = new Transactions\Response($response['token'], $response['url']);

        $this->logResponse($apiRequest, $response);
        $this->fireCreated($apiRequest, $transbankResponse);

        return $transbankResponse;
    }

    /**
     * Commits a Mall transaction from Transbank servers.
     *
     * @param  string  $token
     * @param  array  $options
     *
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     * @throws \DarkGhostHunter\Transbank\Exceptions\TransbankException
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

        $transaction = Transactions\Transaction::createWithDetails(static::ACTION_COMMIT, $response);

        $this->logResponse($apiRequest, $response, $token);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }


    /**
     * Returns the transaction status by its token.
     *
     * @param  string  $token
     * @param  array  $options
     *
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     * @throws \DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function status(string $token, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(static::ACTION_STATUS);

        $this->log('Retrieving transaction status', ['token' => $token, 'api_request' => $apiRequest]);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'get',
            Webpay::ENDPOINT_STATUS,
            ['{token}' => $token],
            $options
        );

        $this->logResponse($apiRequest, $response, $token);

        return Transactions\Transaction::createWithDetails(static::ACTION_STATUS, $response);
    }

    /**
     * Refunds partially or totally a Mall transaction in Transbank.
     *
     * @param  string|int  $commerceCode
     * @param  string  $token
     * @param  string  $buyOrder
     * @param  int|float  $amount
     * @param  array  $options
     *
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     * @throws \DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function refund(
        $commerceCode,
        string $token,
        string $buyOrder,
        $amount,
        array $options = []
    ): Transactions\Transaction {
        $apiRequest = new ApiRequest(
            static::ACTION_REFUND,
            [
                'commerce_code' => $commerceCode,
                'buy_order' => $buyOrder,
                'amount' => $amount,
            ]
        );

        $this->log('Refunding transaction', ['token' => $token, 'api_request' => $apiRequest]);

        $this->fireCreating($apiRequest);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'post',
            static::ENDPOINT_REFUND,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_REFUND, $response);

        $this->logResponse($apiRequest, $response, $token);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Captures an amount of a given transaction by its token.
     *
     * @param  string|int  $commerceCode
     * @param  string  $token
     * @param  string  $buyOrder
     * @param  int|string  $authorizationCode
     * @param  int|float  $captureAmount
     * @param  array  $options
     *
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     * @throws \DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function capture(
$commerceCode,
string $token,
string $buyOrder,
$authorizationCode,
$captureAmount,
array $options = []
    ): Transactions\Transaction {
        $apiRequest = new ApiRequest(
            static::ACTION_CAPTURE,
            [
                'commerce_code' => $commerceCode,
                'buy_order' => $buyOrder,
                'authorization_code' => $authorizationCode,
                'capture_amount' => $captureAmount,
            ]
        );

        $this->log('Capturing transaction', ['token' => $token, 'api_request' => $apiRequest]);

        // If we are on integration, we need to override the credentials.
        $serviceName = $this->transbank->isIntegration() ? static::ACTION_CAPTURE : static::SERVICE_NAME;

        $response = $this->send(
            $serviceName,
            $apiRequest,
            'put',
            Webpay::ENDPOINT_CAPTURE,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_CAPTURE, $response);

        $this->logResponse($apiRequest, $response, $token);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }
}
