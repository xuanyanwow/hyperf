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

namespace HyperfTest\Rpc;

use Hyperf\Context\Context;
use Hyperf\Rpc\RpcTimeoutContext;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 * @coversNothing
 */
#[CoversNothing]
class RpcTimeoutContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::destroy(RpcTimeoutContext::EXPLICIT_TIMEOUT);
        Context::destroy(RpcTimeoutContext::ACTIVE_TIMEOUT);
    }

    public function testExplicitTimeoutIsConsumedOnce()
    {
        RpcTimeoutContext::setExplicit(30);

        $this->assertSame(30.0, RpcTimeoutContext::pullExplicit());
        $this->assertNull(RpcTimeoutContext::pullExplicit());
    }

    public function testActiveTimeoutIsScopedAndRestored()
    {
        RpcTimeoutContext::setActive(5);

        $result = RpcTimeoutContext::runWithActive(10, function () {
            $this->assertSame(10.0, RpcTimeoutContext::getActive());

            return 'result';
        });

        $this->assertSame('result', $result);
        $this->assertSame(5.0, RpcTimeoutContext::getActive());
    }

    public function testActiveTimeoutIsClearedAfterAnException()
    {
        try {
            RpcTimeoutContext::runWithActive(10, static fn () => throw new RuntimeException('failed'));
        } catch (RuntimeException) {
        }

        $this->assertNull(RpcTimeoutContext::getActive());
    }

    #[DataProvider('invalidTimeoutProvider')]
    public function testRejectsANonPositiveExplicitTimeout(float $timeout)
    {
        $this->expectException(InvalidArgumentException::class);
        RpcTimeoutContext::setExplicit($timeout);
    }

    public static function invalidTimeoutProvider(): array
    {
        return [
            [0.0],
            [-1.0],
        ];
    }
}
