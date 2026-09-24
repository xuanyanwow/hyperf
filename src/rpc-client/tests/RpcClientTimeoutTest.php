<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\RpcClient;

use Hyperf\Config\Config;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\RpcClient\Proxy\AbstractProxyService;
use Hyperf\RpcClient\RpcTimeoutResolver;
use Hyperf\RpcClient\ServiceClient;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
#[CoversNothing]
class RpcClientTimeoutTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::destroy(RpcTimeoutResolver::TIMEOUT);
    }

    public function testProxyExposesAChainableOneShotTimeout()
    {
        $proxy = $this->createProxyService(['yyyyy' => 10], function () {
            $this->assertSame(30.0, RpcTimeoutResolver::get());

            return 'response';
        });

        $this->assertSame('response', $proxy->timeoutResolver->set(30)->yyyyy());
        $this->assertNull(RpcTimeoutResolver::get());
    }

    public function testTheOneShotTimeoutIsConsumedByASingleCall()
    {
        $expected = 30.0;
        $proxy = $this->createProxyService(['yyyyy' => 10], function () use (&$expected) {
            $this->assertSame($expected, RpcTimeoutResolver::get());

            return 'response';
        });

        $proxy->timeoutResolver->set(30)->yyyyy();

        $expected = 10.0;
        $proxy->yyyyy();
    }

    public function testAppliesTheConfiguredMethodTimeoutWhileProcessingAnRpcCall()
    {
        $client = $this->createServiceClient(['yyyyy' => 10], function () {
            $this->assertSame(10.0, RpcTimeoutResolver::get());

            return 'response';
        });

        $this->assertSame('response', $client->__call('yyyyy', []));
        $this->assertNull(RpcTimeoutResolver::get());
    }

    public function testLeavesTheDefaultTimeoutUnchangedForUnconfiguredMethods()
    {
        $client = $this->createServiceClient(['yyyyy' => 10], function () {
            $this->assertNull(RpcTimeoutResolver::get());

            return 'response';
        });

        $this->assertSame('response', $client->__call('other', []));
    }

    /**
     * @param array<string, float|int> $methodTimeouts
     */
    private function createProxyService(array $methodTimeouts, callable $handler): AbstractProxyService
    {
        return new class($this->createConfig($methodTimeouts), $handler) extends AbstractProxyService {
            public function __construct(ConfigInterface $config, callable $handler)
            {
                $this->timeoutResolver = new RpcTimeoutResolver($config, $this);
                $this->client = RpcClientTimeoutTest::createClient($config, $handler, $this->timeoutResolver);
            }

            public function yyyyy(): mixed
            {
                return $this->client->__call(__FUNCTION__, []);
            }
        };
    }

    /**
     * @param array<string, float|int> $methodTimeouts
     */
    private function createServiceClient(array $methodTimeouts, callable $handler): ServiceClient
    {
        return static::createClient($this->createConfig($methodTimeouts), $handler);
    }

    /**
     * @param array<string, float|int> $methodTimeouts
     */
    private function createConfig(array $methodTimeouts): ConfigInterface
    {
        return new Config([
            'services' => [
                'consumers' => [[
                    'name' => 'test-service',
                    'options' => ['method_timeouts' => $methodTimeouts],
                ]],
            ],
        ]);
    }

    public static function createClient(ConfigInterface $config, callable $handler, ?RpcTimeoutResolver $timeoutResolver = null): ServiceClient
    {
        return new class($config, $handler, $timeoutResolver) extends ServiceClient {
            public function __construct(ConfigInterface $config, public $handler, ?RpcTimeoutResolver $timeoutResolver)
            {
                $this->serviceName = 'test-service';
                $this->config = $config;
                $this->timeoutResolver = $timeoutResolver;
            }

            protected function __request(string $method, array $params, ?string $id = null)
            {
                return ($this->handler)();
            }
        };
    }
}
