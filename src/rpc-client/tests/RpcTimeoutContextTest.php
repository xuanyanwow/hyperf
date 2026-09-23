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

use Hyperf\Context\Context;
use Hyperf\RpcClient\RpcTimeoutContext;
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
        Context::destroy(RpcTimeoutContext::TIMEOUT);
    }

    public function testTimeoutIsScopedAndRestored()
    {
        RpcTimeoutContext::set(5);

        $result = RpcTimeoutContext::runWith(10, function () {
            $this->assertSame(10.0, RpcTimeoutContext::get());

            return 'result';
        });

        $this->assertSame('result', $result);
        $this->assertSame(5.0, RpcTimeoutContext::get());
    }

    public function testTimeoutIsClearedAfterAnException()
    {
        try {
            RpcTimeoutContext::runWith(10, static fn () => throw new RuntimeException('failed'));
        } catch (RuntimeException) {
        }

        $this->assertNull(RpcTimeoutContext::get());
    }

    #[DataProvider('invalidTimeoutProvider')]
    public function testRejectsANonPositiveTimeout(float $timeout)
    {
        $this->expectException(InvalidArgumentException::class);
        RpcTimeoutContext::set($timeout);
    }

    public static function invalidTimeoutProvider(): array
    {
        return [
            [0.0],
            [-1.0],
        ];
    }
}
