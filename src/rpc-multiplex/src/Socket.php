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

namespace Hyperf\RpcMultiplex;

use Hyperf\RpcClient\RpcTimeoutResolver;
use Multiplex\Contract\IdGeneratorInterface;
use Multiplex\Contract\PackerInterface;
use Multiplex\Contract\SerializerInterface;
use Multiplex\Exception\ChannelClosedException;
use Multiplex\Exception\ChannelLostException;
use Multiplex\Exception\RecvTimeoutException;
use Multiplex\Socket\Client;
use Psr\Container\ContainerInterface;

class Socket extends Client
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct(
            '',
            80,
            $container->get(IdGeneratorInterface::class),
            $container->get(SerializerInterface::class),
            $container->get(PackerInterface::class)
        );
    }

    public function recv(int $id): mixed
    {
        // 讓底層包支持單次timeout傳入
        // parent::recv($id);
        $this->loop();

        $manager = $this->getChannelManager();
        $chan = $manager->get($id);
        if ($chan === null) {
            throw new ChannelLostException();
        }

        try {
            $timeout = RpcTimeoutResolver::get() ?? $this->config['recv_timeout'] ?? 10;
            $data = $chan->pop($timeout);
            if ($chan->isTimeout()) {
                throw new RecvTimeoutException(sprintf('Recv channel [%d] pop timeout after %s seconds.', $id, $timeout));
            }

            if ($chan->isClosing()) {
                throw new ChannelClosedException(sprintf('Recv channel [%d] closed.', $id));
            }
        } finally {
            $manager->close($id);
        }

        return $data;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }
}
