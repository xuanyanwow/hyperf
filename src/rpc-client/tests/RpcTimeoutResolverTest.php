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
use Hyperf\Contract\ConfigInterface;
use Hyperf\RpcClient\RpcTimeoutResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
#[CoversNothing]
class RpcTimeoutResolverTest extends TestCase
{
    public function testResolvesTheTimeoutConfiguredForAServiceMethod()
    {
        $resolver = new RpcTimeoutResolver($this->createConfig([
            [
                'name' => 'test-service',
                'options' => [
                    'method_timeouts' => [
                        'yyyyy' => 10,
                    ],
                ],
            ],
        ]));

        $this->assertSame(10.0, $resolver->resolve('test-service', 'yyyyy'));
        $this->assertNull($resolver->resolve('test-service', 'other'));
        $this->assertNull($resolver->resolve('other-service', 'yyyyy'));
    }

    #[DataProvider('invalidTimeoutProvider')]
    public function testRejectsAnInvalidMethodTimeoutConfiguration(mixed $timeout)
    {
        $this->expectException(InvalidArgumentException::class);

        new RpcTimeoutResolver($this->createConfig([
            [
                'name' => 'test-service',
                'options' => [
                    'method_timeouts' => [
                        'yyyyy' => $timeout,
                    ],
                ],
            ],
        ]));
    }

    public static function invalidTimeoutProvider(): array
    {
        return [
            [0],
            [-1],
            ['invalid'],
        ];
    }

    private function createConfig(array $consumers): ConfigInterface
    {
        return new Config([
            'services' => [
                'consumers' => $consumers,
            ],
        ]);
    }
}
