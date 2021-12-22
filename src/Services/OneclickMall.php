<?php

namespace DarkGhostHunter\Transbank\Services;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Credentials\Container;
use DarkGhostHunter\Transbank\Transbank;

class OneclickMall
{
    use FiresEvents;
    use DebugsTransactions;
    use SendsRequests;
    use WrapsDetail;

    // Service names.
    protected const SERVICE_NAME = 'oneclickMall';
    protected const ACTION_START = self::SERVICE_NAME . '.start';
    protected const ACTION_FINISH = self::SERVICE_NAME . '.finish';
    protected const ACTION_DELETE = self::SERVICE_NAME . '.delete';
    protected const ACTION_AUTHORIZE = self::SERVICE_NAME . '.authorize';
    protected const ACTION_STATUS = self::SERVICE_NAME . '.status';
    protected const ACTION_REFUND = self::SERVICE_NAME . '.refund';
    protected const ACTION_CAPTURE = self::SERVICE_NAME . '.capture';

    /**
     * The API base URI.
     *
     * @var string
     */
    protected const ENDPOINT_BASE = '/rswebpaytransaction/api/oneclick/{api_version}/';

    // Endpoints for the inscriptions.
    public const ENDPOINT_START = self::ENDPOINT_BASE . 'inscriptions';
    public const ENDPOINT_FINISH = self::ENDPOINT_BASE . 'inscriptions/{token}';
    public const ENDPOINT_DELETE = self::ENDPOINT_BASE . 'inscriptions';

    // Endpoints for the transactions.
    public const ENDPOINT_AUTHORIZE = self::ENDPOINT_BASE . 'transactions';
    public const ENDPOINT_STATUS = self::ENDPOINT_BASE . 'transactions/{buyOrder}';
    public const ENDPOINT_REFUND = self::ENDPOINT_BASE . 'transactions/{buyOrder}/refunds';
    public const ENDPOINT_CAPTURE = '/rswebpaytransaction/api/oneclick/mall/{api_version}/transactions/capture';

    /**
     * Webpay constructor.
     *
     * @param  \DarkGhostHunter\Transbank\Transbank  $transbank
     * @param  \DarkGhostHunter\Transbank\Credentials\Container  $container
     */
    public function __construct(protected Transbank $transbank, protected Container $container)
    {
        //
    }

    /**
     * Creates a new pending subscription in Transbank.
     *
     * @param  string  $username
     * @param  string  $email
     * @param  string  $responseUrl
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Response
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function start(
        string $username,
        string $email,
        string $responseUrl,
        array $options = []
    ): Transactions\Response {
        $apiRequest = new ApiRequest(
            static::ACTION_START,
            [
                'username' => $username,
                'email' => $email,
                'response_url' => $responseUrl,
            ]
        );

        $this->log('Creating subscription', ['api_request' => $apiRequest,]);
        $this->fireCreating($apiRequest);

        $response = $this->send(self::SERVICE_NAME, $apiRequest, 'post', static::ENDPOINT_START, [], $options);
        $transaction = new Transactions\Response($response['token'], $response['url_webpay']);

        $this->fireCreated($apiRequest, $transaction);
        $this->logResponse($apiRequest, $response);

        return new Transactions\Response($response['token'], $response['url_webpay']);
    }

    /**
     * Finishes a subscription process in Transbank.
     *
     * @param  string  $token
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function finish(string $token, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(static::ACTION_FINISH);

        $this->log('Finishing subscription', ['token' => $token, 'api_request' => $apiRequest]);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'put',
            static::ENDPOINT_FINISH,
            ['{token}' => $token],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_FINISH, $response);

        $this->logResponse($apiRequest, $response, $token);

        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Deletes a subscription.
     *
     * If the subscription doesn't exist, an exception will be returned.
     *
     * @param  string  $tbkUser
     * @param  string  $username
     * @param  array  $options
     * @return void
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function delete(string $tbkUser, string $username, array $options = []): void
    {
        $apiRequest = new ApiRequest(static::ACTION_DELETE, ['tbk_user' => $tbkUser, 'username' => $username]);

        $this->log('Deleting subscription', ['api_request' => $apiRequest]);

        $response = $this->send(static::SERVICE_NAME, $apiRequest, 'delete', static::ENDPOINT_DELETE, [], $options);

        $this->logResponse($apiRequest, $response);
    }

    /**
     * Authorizes a given set of transactions.
     *
     * @param  string  $tbkUser
     * @param  string  $username
     * @param  string  $buyOrder
     * @param  array  $details
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function authorize(
        string $tbkUser,
        string $username,
        string $buyOrder,
        array $details,
        array $options = []
    ): Transactions\Transaction {
        $apiRequest = new ApiRequest(
            static::ACTION_AUTHORIZE,
            [
                'tbk_user' => $tbkUser,
                'username' => $username,
                'buy_order' => $buyOrder,
                'details' => static::wrapDetails($details),
            ]
        );

        $this->log('Authorizing transaction', ['api_request' => $apiRequest]);

        $this->fireCreating($apiRequest);

        $response = $this->send(static::SERVICE_NAME, $apiRequest, 'post', static::ENDPOINT_AUTHORIZE, [], $options);
        $transaction = Transactions\Transaction::createWithDetails(static::ACTION_AUTHORIZE, $response);

        $this->logResponse($apiRequest, $response);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Retrieves a transaction from Transbank.
     *
     * @param  string  $buyOrder
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function status(string $buyOrder, array $options = []): Transactions\Transaction
    {
        $apiRequest = new ApiRequest(static::ACTION_STATUS);

        $this->log('Retrieving transaction status', ['buy_order' => $buyOrder, 'api_request' => $apiRequest]);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'get',
            static::ENDPOINT_STATUS,
            ['{buyOrder}' => $buyOrder],
            $options
        );

        $this->logResponse($apiRequest, $response);

        return Transactions\Transaction::createWithDetails(static::ACTION_AUTHORIZE, $response);
    }

    /**
     * Refunds a child transaction.
     *
     * @param  string  $buyOrder
     * @param  string  $childCommerceCode
     * @param  string  $childBuyOrder
     * @param  int|float  $amount
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function refund(
        string $buyOrder,
        string $childCommerceCode,
        string $childBuyOrder,
        int|float $amount,
        array $options = []
    ): Transactions\Transaction {
        $apiRequest = new ApiRequest(
            static::ACTION_REFUND,
            [
                'commerce_code' => $childCommerceCode,
                'detail_buy_order' => $childBuyOrder,
                'amount' => $amount,
            ]
        );

        $this->log('Refunding transaction', ['buy_order' => $buyOrder, 'api_request' => $apiRequest,]);

        $this->fireCreating($apiRequest);

        $response = $this->send(
            static::SERVICE_NAME,
            $apiRequest,
            'post',
            static::ENDPOINT_REFUND,
            ['{buyOrder}' => $buyOrder],
            $options
        );

        $transaction = new Transactions\Transaction(static::ACTION_REFUND, $response);

        $this->log('Response received', ['buy_order' => $buyOrder, 'api_request' => $apiRequest, 'response' => $response]);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }

    /**
     * Captures a transaction from Transbank.
     *
     * @param  string  $commerceCode
     * @param  string  $buyOrder
     * @param  string|int  $authorizationCode
     * @param  int|float  $captureAmount
     * @param  array  $options
     * @return \DarkGhostHunter\Transbank\Services\Transactions\Transaction
     *
     * @throws \JsonException|\DarkGhostHunter\Transbank\Exceptions\TransbankException
     */
    public function capture(
        string $commerceCode,
        string $buyOrder,
        string|int $authorizationCode,
        int|float $captureAmount,
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

        $this->log('Capturing transaction', ['api_request' => $apiRequest]);

        // If we are on integration, we need to override the credentials.
        $serviceName = $this->transbank->isIntegration() ? static::ACTION_CAPTURE : static::SERVICE_NAME;

        $response = $this->send($serviceName, $apiRequest, 'put', static::ENDPOINT_CAPTURE, [], $options);
        $transaction = new Transactions\Transaction(static::ACTION_CAPTURE, $response);

        $this->logResponse($apiRequest, $response);
        $this->fireCompleted($apiRequest, $transaction);

        return $transaction;
    }
}
