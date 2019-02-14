<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;

class Shopware55ApiEnvironmentReaderTest extends TestCase
{
    private $dataArray = [
        'testKey' => 'testValue',
    ];

    private $body;

    private $warning = [
        'code' => -1,
        'detail' => 'No warning.',
    ];

    private $error = [
        'code' => -1,
        'detail' => 'No error.',
    ];

    protected function setUp(): void
    {
        $this->body = json_encode([
            'data' => $this->dataArray,
        ]);
    }

    public function testEnvironmentReaderVerifiedTest(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                '',
                '',
                '',
                '',
                '',
                ['endpoint' => '', 'apiUser' => '', 'apiKey' => ''],
                0,
                0
            )
        );

        $response = $environmentReader->read();
        self::assertSame($response['environmentInformation'], $this->dataArray);
        self::assertSame($response['warning'], $this->warning);
        self::assertSame($response['error'], $this->error);
    }

    public function testEnvironmentReaderNotVerifiedTest(): void
    {
        $warning = [
            'code' => 666,
            'detail' => 'My test exception',
        ];

        $mock = new MockHandler([
            new \Exception($warning['detail'], $warning['code']),
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                '',
                '',
                '',
                '',
                '',
                ['endpoint' => '', 'apiUser' => '', 'apiKey' => ''],
                0,
                0
            )
        );

        $response = $environmentReader->read();
        self::assertSame($response['environmentInformation'], $this->dataArray);
        self::assertSame($response['warning'], $warning);
        self::assertSame($response['error'], $this->error);
    }

    public function testEnvironmentReaderNotConnectedTest(): void
    {
        $error = [
            'code' => 666,
            'detail' => 'My test exception',
        ];

        $mock = new MockHandler([
            new \Exception($error['detail'], $error['code']),
            new \Exception($error['detail'], $error['code']),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                '',
                '',
                '',
                '',
                '',
                ['endpoint' => '', 'apiUser' => '', 'apiKey' => ''],
                0,
                0
            )
        );

        $response = $environmentReader->read();
        self::assertSame($response['environmentInformation'], []);
        self::assertSame($response['error'], $error);
        self::assertSame($response['warning'], $this->warning);
    }
}
