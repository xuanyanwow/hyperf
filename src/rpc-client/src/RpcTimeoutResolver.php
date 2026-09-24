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

use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\RpcClient\Proxy\AbstractProxyService;
use InvalidArgumentException;

class RpcTimeoutResolver
{
    public const TIMEOUT = self::class . '.timeout';

    /**
     * @var array<string, array<string, float>>
     */
    private readonly array $methodTimeouts;

    public function __construct(ConfigInterface $config, private readonly ?AbstractProxyService $proxy = null)
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
                self::validate($timeout, sprintf('RPC method timeout [%s::%s] must be greater than zero.', $serviceName, $method));

                $methodTimeouts[$serviceName][$method] = $timeout;
            }
        }

        $this->methodTimeouts = $methodTimeouts;
    }

    /**
     * Set the timeout of the next rpc call.
     */
    public static function set(float $timeout): void
    {
        self::validate($timeout);
        Context::set(self::TIMEOUT, $timeout);
    }

    /**
     * Set the timeout of the next rpc call, then return the proxy service to keep the call chainable.
     */
    public function with(float $timeout): ?AbstractProxyService
    {
        self::set($timeout);

        return $this->proxy;
    }

    public function resolve(string $serviceName, string $method): ?float
    {
        return $this->methodTimeouts[$serviceName][$method] ?? null;
    }

    public static function get(): ?float
    {
        $timeout = Context::get(self::TIMEOUT);

        return is_float($timeout) ? $timeout : null;
    }

    public static function validate(float $timeout, ?string $message = null): void
    {
        if ($timeout <= 0 || ! is_finite($timeout)) {
            throw new InvalidArgumentException($message ?? 'RPC timeout must be a finite number greater than zero.');
        }
    }
}
