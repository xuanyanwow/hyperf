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

namespace HyperfTest\RpcMultiplex\Cases;

use Hyperf\Context\Context;
use Hyperf\RpcClient\RpcTimeoutResolver;
use Hyperf\RpcMultiplex\Socket;
use HyperfTest\RpcMultiplex\Stub\ContainerStub;
use Multiplex\Exception\RecvTimeoutException;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 * @coversNothing
 */
#[CoversNothing]
class SocketTimeoutTest extends AbstractTestCase
{
    protected function tearDown(): void
    {
        Context::destroy(RpcTimeoutResolver::TIMEOUT);
        parent::tearDown();
    }

    public function testForwardsTheContextTimeoutToTheUnderlyingClient()
    {
        $container = ContainerStub::mockContainer();

        $socket = new class($container) extends Socket {
            protected function loop(): void
            {
            }
        };
        $socket->getChannelManager()->get(1, true);
        RpcTimeoutResolver::set(0.01);

        try {
            $socket->recv(1);
            $this->fail('Expected RecvTimeoutException was not thrown.');
        } catch (RecvTimeoutException $exception) {
            $this->assertStringContainsString('0.01', $exception->getMessage());
        }
    }

    public function testKeepsTheConfiguredTimeoutWhenContextIsEmpty()
    {
        $container = ContainerStub::mockContainer();

        $socket = new class($container) extends Socket {
            protected function loop(): void
            {
            }
        };
        $socket->set(['recv_timeout' => 0.01]);
        $socket->getChannelManager()->get(1, true);

        try {
            $socket->recv(1);
            $this->fail('Expected RecvTimeoutException was not thrown.');
        } catch (RecvTimeoutException $exception) {
            $this->assertStringContainsString('0.01', $exception->getMessage());
        }
    }
}
