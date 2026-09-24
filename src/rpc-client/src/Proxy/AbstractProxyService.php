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

namespace Hyperf\RpcClient\Proxy;

use Hyperf\Contract\ConfigInterface;
use Hyperf\RpcClient\RpcTimeoutResolver;
use Hyperf\RpcClient\ServiceClient;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

abstract class AbstractProxyService
{
    public RpcTimeoutResolver $timeoutResolver;

    protected ServiceClient $client;

    public function __construct(ContainerInterface $container, string $serviceName, string $protocol, array $options = [])
    {
        $this->timeoutResolver = make(RpcTimeoutResolver::class, [
            'config' => $container->get(ConfigInterface::class),
            'proxy' => $this,
        ]);

        $this->client = make(ServiceClient::class, [
            'container' => $container,
            'serviceName' => $serviceName,
            'protocol' => $protocol,
            'options' => $options,
            'timeoutResolver' => $this->timeoutResolver,
        ]);
    }
}
