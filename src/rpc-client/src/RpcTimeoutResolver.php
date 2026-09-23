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

namespace Hyperf\RpcClient;

use Hyperf\Contract\ConfigInterface;
use InvalidArgumentException;

class RpcTimeoutResolver
{
    /**
     * @var array<string, array<string, float>>
     */
    private readonly array $methodTimeouts;

    public function __construct(ConfigInterface $config)
    {
        $methodTimeouts = [];

        foreach ($config->get('services.consumers', []) as $consumer) {
            $serviceName = $consumer['name'] ?? null;
            $timeouts = $consumer['options']['method_timeouts'] ?? [];

            if (! is_string($serviceName) || $serviceName === '' || ! is_array($timeouts)) {
                continue;
            }

            foreach ($timeouts as $method => $timeout) {
                if (! is_string($method) || $method === '' || ! is_numeric($timeout)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid RPC method timeout configured for service [%s].', $serviceName)
                    );
                }

                $timeout = (float) $timeout;
                if ($timeout <= 0 || ! is_finite($timeout)) {
                    throw new InvalidArgumentException(
                        sprintf('RPC method timeout [%s::%s] must be greater than zero.', $serviceName, $method)
                    );
                }

                $methodTimeouts[$serviceName][$method] = $timeout;
            }
        }

        $this->methodTimeouts = $methodTimeouts;
    }

    public function resolve(string $serviceName, string $method): ?float
    {
        return $this->methodTimeouts[$serviceName][$method] ?? null;
    }
}
