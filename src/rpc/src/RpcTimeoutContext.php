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

namespace Hyperf\Rpc;

use Closure;
use Hyperf\Context\Context;
use InvalidArgumentException;

class RpcTimeoutContext
{
    public const EXPLICIT_TIMEOUT = self::class . '.explicit_timeout';

    public const ACTIVE_TIMEOUT = self::class . '.active_timeout';

    public static function setExplicit(float $timeout): void
    {
        self::validate($timeout);
        Context::set(self::EXPLICIT_TIMEOUT, $timeout);
    }

    public static function pullExplicit(): ?float
    {
        $timeout = Context::get(self::EXPLICIT_TIMEOUT);
        Context::destroy(self::EXPLICIT_TIMEOUT);

        return is_float($timeout) ? $timeout : null;
    }

    public static function setActive(float $timeout): void
    {
        self::validate($timeout);
        Context::set(self::ACTIVE_TIMEOUT, $timeout);
    }

    public static function getActive(): ?float
    {
        $timeout = Context::get(self::ACTIVE_TIMEOUT);

        return is_float($timeout) ? $timeout : null;
    }

    /**
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public static function runWithActive(float $timeout, Closure $callback): mixed
    {
        self::validate($timeout);

        $hasPreviousTimeout = Context::has(self::ACTIVE_TIMEOUT);
        $previousTimeout = self::getActive();
        Context::set(self::ACTIVE_TIMEOUT, $timeout);

        try {
            return $callback();
        } finally {
            if ($hasPreviousTimeout && $previousTimeout !== null) {
                Context::set(self::ACTIVE_TIMEOUT, $previousTimeout);
            } else {
                Context::destroy(self::ACTIVE_TIMEOUT);
            }
        }
    }

    private static function validate(float $timeout): void
    {
        if ($timeout <= 0 || ! is_finite($timeout)) {
            throw new InvalidArgumentException('RPC timeout must be a finite number greater than zero.');
        }
    }
}
