<?php

namespace Tests\Services;

use DarkGhostHunter\Transbank\ApiRequest;
use DarkGhostHunter\Transbank\Credentials\Credentials;
use DarkGhostHunter\Transbank\Events\TransactionCompleted;
use DarkGhostHunter\Transbank\Events\TransactionCreated;
use DarkGhostHunter\Transbank\Events\TransactionCreating;
use DarkGhostHunter\Transbank\Http\Connector;
use DarkGhostHunter\Transbank\Services\Transactions\Response as TransbankResponse;
use DarkGhostHunter\Transbank\Services\Transactions\Transaction;
use DarkGhostHunter\Transbank\Services\Webpay;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\FormatsToCamelCase;

class WebpayTest extends TestCase
{
    use FormatsToCamelCase;
    use AssertsApiEndpoint;
    use TestsServices;

    public function test_uses_production_credentials(): void
    {
        $this->transbank->connector = Mockery::mock(Connector::class);
        $this->transbank->connector->expects('send')->withArgs(
            function(string $method, string $endpoint, ApiRequest $apiRequest, Credentials $credentials) {
                static::assertEquals('test_key', $credentials->key);
                static::assertEquals('test_secret', $credentials->secret);
                return true;
            }
        )->times(5)->andReturns(['token' => 'test_token', 'url' => 'test_url']);

        $this->logger->allows('debug')->withAnyArgs()->zeroOrMoreTimes()->andReturnNull();
        $this->dispatcher->allows('dispatch')->withAnyArgs()->zeroOrMoreTimes()->andReturnNull();

        $this->transbank->toProduction([
            'webpay' => ['key' => 'test_key', 'secret' => 'test_secret']
        ]);

        $this->transbank->webpay()->create('test_buy_order', 100, 'test_return_url', 'test_session_id');
        $this->transbank->webpay()->status('test_token');
        $this->transbank->webpay()->commit('test_token');
        $this->transbank->webpay()->refund('test_token', 1000);
        $this->transbank->webpay()->capture('test_token', 'test_buy_order', '1234', 1000);
    }

    public function test_uses_integration_credentials_by_default(): void
    {
        $this->transbank->connector = Mockery::mock(Connector::class);
        $this->transbank->connector->expects('send')->withArgs(
            function(string $method, string $endpoint, ApiRequest $apiRequest, Credentials $credentials) {
                static::assertEquals(Credentials::INTEGRATION_KEYS['webpay'], $credentials->key);
                static::assertEquals(Credentials::INTEGRATION_SECRET, $credentials->secret);
                return true;
            }
        )->times(5)->andReturns(['token' => 'test_token', 'url' => 'test_url']);

        $this->logger->allows('debug')->withAnyArgs()->zeroOrMoreTimes()->andReturnNull();
        $this->dispatcher->allows('dispatch')->withAnyArgs()->zeroOrMoreTimes()->andReturnNull();

        $this->transbank->webpay()->create('test_buy_order', 100, 'test_return_url', 'test_session_id');
        $this->transbank->webpay()->status('test_token');
        $this->transbank->webpay()->commit('test_token');
        $this->transbank->webpay()->refund('test_token', 1000);
        $this->transbank->webpay()->capture('test_token', 'test_buy_order', '1234', 1000);
    }

    public function test_create(): void
    {
        $buyOrder = 'test-buyOrder';
        $amount = 100;
        $returnUrl = 'http://app.com/return';
        $sessionId = 'test_session_id';

        $this->handlerStack->setHandler($mockHandler = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode([
                'token' => $token = '01ab1cc073c91fe5fc08a1b3b00ac3f63033a0e3dbdfdb1fde55c044ed8161b6',
                'url' => $url = 'https://webpay3g.transbank.cl/webpayserver/initTransaction',
            ], JSON_THROW_ON_ERROR)),
        ]));

        $this->dispatcher->expects('dispatch')->withArgs(
            function (TransactionCreating $event) use ($buyOrder, $amount, $returnUrl, $sessionId) {
                static::assertEquals('webpay.create', $event->apiRequest->serviceAction);
                static::assertEquals($buyOrder, $event->apiRequest['buy_order']);
                static::assertEquals($amount, $event->apiRequest['amount']);
                static::assertEquals($returnUrl, $event->apiRequest['return_url']);
                static::assertEquals($sessionId, $event->apiRequest['session_id']);

                return true;
            }
        );

        $this->logger->expects('debug')->withArgs(
            function (string $action, array $context) use ($buyOrder, $amount, $returnUrl, $sessionId) {
                static::assertEquals('Creating transaction', $action);
                static::assertEquals($buyOrder, $context['api_request']['buy_order']);
                static::assertEquals($amount, $context['api_request']['amount']);
                static::assertEquals($returnUrl, $context['api_request']['return_url']);
                static::assertEquals($sessionId, $context['api_request']['session_id']);

                return true;
            }
        );

        $this->logger->expects('debug')->withArgs(
            function (string $action, array $context) use ($buyOrder, $amount, $returnUrl, $sessionId, $token, $url) {
                static::assertEquals('Response received', $action);
                static::assertEquals($buyOrder, $context['api_request']['buy_order']);
                static::assertEquals($amount, $context['api_request']['amount']);
                static::assertEquals($returnUrl, $context['api_request']['return_url']);
                static::assertEquals($sessionId, $context['api_request']['session_id']);
                static::assertEquals($token, $context['raw_response']['token']);
                static::assertEquals($url, $context['raw_response']['url']);

                return true;
            }
        );

        $this->dispatcher->expects('dispatch')->withArgs(
            function (TransactionCreated $event) use ($buyOrder, $amount, $returnUrl, $sessionId, $token) {
                static::assertEquals('webpay.create', $event->apiRequest->serviceAction);
                static::assertEquals($buyOrder, $event->apiRequest['buy_order']);
                static::assertEquals($amount, $event->apiRequest['amount']);
                static::assertEquals($returnUrl, $event->apiRequest['return_url']);
                static::assertEquals($sessionId, $event->apiRequest['session_id']);
                static::assertEquals(
                    new TransbankResponse($token, 'https://webpay3g.transbank.cl/webpayserver/initTransaction'),
                    $event->response
                );
                return true;
            }
        );

        $response = $this->transbank->webpay()->create($buyOrder, $amount, $returnUrl, $sessionId, []);

        static::assertEquals($response->getToken(), $token);
        static::assertEquals($response->getUrl(), $url);

        static::assertCount(1, $this->requests);
        static::assertEquals('POST', $this->requests[0]['request']->getMethod());

        static::assertEndpointPath(Webpay::ENDPOINT_CREATE, $this->requests[0]['request']);

        $stream = $mockHandler->getLastRequest()->getBody();
        $stream->rewind();

        static::assertEquals(
            json_encode([
                'buy_order' => 'test-buyOrder',
                'amount' => 100,
                'session_id' => 'test_session_id',
                'return_url' => 'http://app.com/return',
            ]),
            $stream->getContents()
        );
    }

    public function test_commit(): void
    {
        $token = '01abd1b55849b31783b352ebcb6adaf1f7d0dab7476aac499568c01585c5e289';

        $transbankResponse = [
            'vci' => 'TSY',
            'amount' => 10000,
            'status' => 'AUTHORIZED',
            'buy_order' => 'test_buy_order',
            'session_id' => 'test_session',
            'card_detail' => [
                'card_number' => '6623',
            ],
            'accounting_date' => '0324',
            'transaction_date' => '2021-01-24T22:16:48.562Z',
            'authorization_code' => '1213',
            'payment_type_code' => 'VN',
            'response_code' => 0,
            'installments_number' => 0,
        ];

        $this->handlerStack->setHandler($mockHandler = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode($transbankResponse, JSON_THROW_ON_ERROR)),
        ]));

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($token) {
            static::assertEquals('Committing transaction', $action);
            static::assertEquals($token, $context['token']);
            static::assertEquals('webpay.commit', $context['api_request']->serviceAction);

            return true;
        })->andReturnNull();

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($transbankResponse, $token) {
            static::assertEquals('Response received', $action);
            static::assertEquals($token, $context['token']);
            static::assertEquals('webpay.commit', $context['api_request']->serviceAction);
            static::assertEquals($transbankResponse, $context['raw_response']);

            return true;
        })->andReturnNull();

        $this->dispatcher->expects('dispatch')->withArgs(function(TransactionCompleted $event) use ($transbankResponse) {
            static::assertEquals('webpay.commit', $event->apiRequest->serviceAction);
            static::assertEquals(new Transaction('webpay.commit', $transbankResponse), $event->transaction);

            return true;
        })->andReturnNull();

        $response = $this->transbank->webpay()->commit($token);

        static::assertEquals('webpay.commit', $response->serviceAction);

        foreach ($transbankResponse as $key => $value) {
            static::assertEquals($value, $response->{'get' . static::snakeCaseToPascalCase($key)}());
        }

        static::assertCount(1, $this->requests);
        static::assertEquals('PUT', $this->requests[0]['request']->getMethod());

        static::assertEndpointPath(Webpay::ENDPOINT_COMMIT, $this->requests[0]['request'], [
            '{token}' => $token
        ]);

        static::assertRequestContentsEmpty($this->requests[0]['request']);
    }

    public function test_status(): void
    {
        $token = '01abd1b55849b31783b352ebcb6adaf1f7d0dab7476aac499568c01585c5e289';

        $transbankResponse = [
            'vci' => 'TSY',
            'amount' => 10000,
            'status' => 'INITIALIZED',
            'buy_order' => 'test_buy_order',
            'session_id' => 'test_session',
            'card_detail' => [
                'card_number' => '6623',
            ],
            'accounting_date' => '0324',
            'transaction_date' => '2021-01-24T22:16:48.562Z',
            'payment_type_code' => 'VN',
            'installments_number' => 0,
        ];

        $this->handlerStack->setHandler($mockHandler = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode($transbankResponse, JSON_THROW_ON_ERROR)),
        ]));

        $this->dispatcher->allows('dispatch')->never();

        $this->logger->expects('debug')->withArgs(
            function (string $action, array $context) use ($token) {
                static::assertEquals('Retrieving transaction status', $action);
                static::assertEquals($token, $context['token']);
                static::assertEquals('webpay.status', $context['api_request']->serviceAction);

                return true;
            }
        );

        $this->logger->expects('debug')->withArgs(
            function (string $action, array $context) use ($token, $transbankResponse) {
                static::assertEquals('Response received', $action);
                static::assertEquals($token, $context['token']);
                static::assertEquals('webpay.status', $context['api_request']->serviceAction);
                static::assertEquals($transbankResponse, $context['raw_response']);

                return true;
            }
        );

        $response = $this->transbank->webpay()->status($token);

        static::assertEquals('webpay.status', $response->serviceAction);

        foreach ($transbankResponse as $key => $value) {
            static::assertEquals($value, $response->{'get' . static::snakeCaseToPascalCase($key)}());
        }

        static::assertCount(1, $this->requests);
        static::assertEquals('GET', $this->requests[0]['request']->getMethod());

        static::assertEndpointPath(Webpay::ENDPOINT_STATUS, $this->requests[0]['request'], [
            '{token}' => $token
        ]);

        static::assertRequestContentsEmpty($this->requests[0]['request']);
    }

    public function test_refund(): void
    {
        $token = '01abd1b55849b31783b352ebcb6adaf1f7d0dab7476aac499568c01585c5e289';

        $transbankResponse = [
            'type' => 'NULLIFIED',
            'authorization_code' => '123456',
            'authorization_date' => '2019-03-20T20:18:20Z',
            'nullified_amount' => $nullifiedAmount = 1000.00,
            'balance' => 0.00,
            'response_code' => 0,
        ];

        $this->dispatcher->expects('dispatch')->withArgs(function(TransactionCreating $event) use ($nullifiedAmount) {
            static::assertEquals('webpay.refund', $event->apiRequest->serviceAction);
            static::assertEquals($event->apiRequest['amount'], $nullifiedAmount);

            return true;
        })->andReturnNull();

        $this->dispatcher->expects('dispatch')->withArgs(function(TransactionCompleted $event) use ($transbankResponse, $nullifiedAmount) {
            static::assertEquals('webpay.refund', $event->apiRequest->serviceAction);
            static::assertEquals($event->apiRequest['amount'], $nullifiedAmount);
            static::assertEquals(new Transaction('webpay.refund', $transbankResponse), $event->transaction);

            return true;
        })->andReturnNull();

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($nullifiedAmount, $token) {
            static::assertEquals('Refunding transaction', $action);
            static::assertEquals($token, $context['token']);
            static::assertEquals('webpay.refund', $context['api_request']->serviceAction);
            static::assertEquals($nullifiedAmount, $context['api_request']['amount']);

            return true;
        })->andReturnNull();

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($transbankResponse, $nullifiedAmount, $token) {
            static::assertEquals('Response received', $action);
            static::assertEquals($token, $context['token']);
            static::assertEquals('webpay.refund', $context['api_request']->serviceAction);
            static::assertEquals($nullifiedAmount, $context['api_request']['amount']);
            static::assertEquals($transbankResponse, $context['raw_response']);

            return true;
        })->andReturnNull();

        $this->handlerStack->setHandler($mockHandler = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode($transbankResponse, JSON_THROW_ON_ERROR)),
        ]));

        $response = $this->transbank->webpay()->refund($token, $nullifiedAmount);

        static::assertEquals($response->getNullifiedAmount(), $nullifiedAmount);
        static::assertTrue($response->isSuccessful());

        foreach ($transbankResponse as $key => $value) {
            static::assertEquals($value, $response->{'get' . static::snakeCaseToPascalCase($key)}());
        }

        static::assertCount(1, $this->requests);
        static::assertEquals('PUT', $this->requests[0]['request']->getMethod());

        static::assertEndpointPath(Webpay::ENDPOINT_REFUND, $this->requests[0]['request'], [
            '{token}' => $token
        ]);

        static::assertRequestContents(
            [
                'amount' => $nullifiedAmount
            ],
            $this->requests[0]['request']
        );
    }

    public function test_capture(): void
    {
        $buyOrder = 'test_buy_order';
        $authorizationCode = 123456;
        $captureAmount = 1000;
        $token = 'e074d38c628122c63e5c0986368ece22974d6fee1440617d85873b7b4efa48a3';

        $transbankResponse = [
            'token' => $token,
            'authorization_code' => $authorizationCode,
            'authorization_date' => '2019-03-20T20:18:20Z',
            'captured_amount' => $captureAmount,
            'response_code' => 0,
        ];

        $this->dispatcher->expects('dispatch')->withArgs(function(TransactionCompleted $event) use ($captureAmount, $authorizationCode, $buyOrder, $transbankResponse) {
            static::assertEquals('webpay.capture', $event->apiRequest->serviceAction);
            static::assertEquals($event->apiRequest['buy_order'], $buyOrder);
            static::assertEquals($event->apiRequest['authorization_code'], $authorizationCode);
            static::assertEquals($event->apiRequest['capture_amount'], $captureAmount);
            static::assertEquals(new Transaction('webpay.capture', $transbankResponse), $event->transaction);

            return true;
        })->andReturnNull();

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($token) {
            static::assertEquals('Capturing transaction', $action);
            static::assertEquals($token, $context['token']);

            return true;
        })->andReturnNull();

        $this->logger->expects('debug')->withArgs(function(string $action, array $context) use ($transbankResponse, $token) {
            static::assertEquals('Response received', $action);
            static::assertEquals($token, $context['token']);
            static::assertEquals($transbankResponse, $context['raw_response']);

            return true;
        })->andReturnNull();

        $this->handlerStack->setHandler($mockHandler = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode($transbankResponse, JSON_THROW_ON_ERROR)),
        ]));

        $response = $this->transbank->webpay()->capture($token, $buyOrder, $authorizationCode, $captureAmount);

        foreach ($transbankResponse as $key => $value) {
            static::assertEquals($value, $response->{'get' . self::snakeCaseToPascalCase($key)}());
        }

        static::assertCount(1, $this->requests);
        static::assertEquals('PUT', $this->requests[0]['request']->getMethod());

        static::assertEndpointPath(Webpay::ENDPOINT_CAPTURE, $this->requests[0]['request'], [
            '{token}' => $token
        ]);

        static::assertRequestContents(
            [
                'buy_order' => $buyOrder,
                'authorization_code' => $authorizationCode,
                'capture_amount' => $captureAmount,
            ],
            $this->requests[0]['request']
        );
    }
}
