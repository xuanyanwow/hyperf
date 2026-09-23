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

use Closure;
use Hyperf\Context\Context;
use InvalidArgumentException;

class RpcTimeoutContext
{
    public const TIMEOUT = self::class . '.timeout';

    public static function set(float $timeout): void
    {
        self::validate($timeout);
        Context::set(self::TIMEOUT, $timeout);
    }

    public static function get(): ?float
    {
        $timeout = Context::get(self::TIMEOUT);

        return is_float($timeout) ? $timeout : null;
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public static function runWith(float $timeout, Closure $callback): mixed
    {
        self::validate($timeout);

        $hasPreviousTimeout = Context::has(self::TIMEOUT);
        $previousTimeout = self::get();
        Context::set(self::TIMEOUT, $timeout);

        try {
            return $callback();
        } finally {
            if ($hasPreviousTimeout && $previousTimeout !== null) {
                Context::set(self::TIMEOUT, $previousTimeout);
            } else {
                Context::destroy(self::TIMEOUT);
            }
        }
    }

    public static function validate(float $timeout): void
    {
        if ($timeout <= 0 || ! is_finite($timeout)) {
            throw new InvalidArgumentException('RPC timeout must be a finite number greater than zero.');
        }
    }
}
