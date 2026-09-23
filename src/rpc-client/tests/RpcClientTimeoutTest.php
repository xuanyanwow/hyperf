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
use Hyperf\Rpc\RpcTimeoutContext;
use Hyperf\RpcClient\Contract\RpcClientTimeoutInterface;
use Hyperf\RpcClient\Proxy\AbstractProxyService;
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
        Context::destroy(RpcTimeoutContext::EXPLICIT_TIMEOUT);
        Context::destroy(RpcTimeoutContext::ACTIVE_TIMEOUT);
    }

    public function testProxySupportsAChainableOneShotTimeout()
    {
        $proxy = new class extends AbstractProxyService {
            public function __construct()
            {
            }
        };

        $this->assertInstanceOf(RpcClientTimeoutInterface::class, $proxy);
        $this->assertSame($proxy, $proxy->setTimeout(30));
        $this->assertSame(30.0, RpcTimeoutContext::pullExplicit());
    }

    public function testAppliesTheConfiguredMethodTimeoutWhileProcessingAnRpcCall()
    {
        $client = $this->createServiceClient(['yyyyy' => 10], function () {
            $this->assertSame(10.0, RpcTimeoutContext::getActive());

            return 'response';
        });

        $this->assertSame('response', $client->__call('yyyyy', []));
        $this->assertNull(RpcTimeoutContext::getActive());
    }

    public function testExplicitTimeoutOverridesMethodTimeoutAndIsConsumedOnce()
    {
        RpcTimeoutContext::setExplicit(30);

        $client = $this->createServiceClient(['yyyyy' => 10], function () {
            $this->assertSame(30.0, RpcTimeoutContext::getActive());

            return 'response';
        });

        $client->__call('yyyyy', []);

        $this->assertNull(RpcTimeoutContext::pullExplicit());
    }

    public function testLeavesTheDefaultTimeoutUnchangedForUnconfiguredMethods()
    {
        $client = $this->createServiceClient(['yyyyy' => 10], function () {
            $this->assertNull(RpcTimeoutContext::getActive());

            return 'response';
        });

        $this->assertSame('response', $client->__call('other', []));
    }

    /**
     * @param array<string, float|int> $methodTimeouts
     */
    private function createServiceClient(array $methodTimeouts, callable $handler): ServiceClient
    {
        return new class($methodTimeouts, $handler) extends ServiceClient {
            public function __construct(array $methodTimeouts, public $handler)
            {
                $this->serviceName = 'test-service';
                $this->config = new Config([
                    'services' => [
                        'consumers' => [[
                            'name' => 'test-service',
                            'options' => ['method_timeouts' => $methodTimeouts],
                        ]],
                    ],
                ]);
            }

            protected function __request(string $method, array $params, ?string $id = null)
            {
                return ($this->handler)();
            }
        };
    }
}
