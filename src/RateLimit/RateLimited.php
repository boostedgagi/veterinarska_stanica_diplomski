<?php

namespace App\RateLimit;

/**
 * Limits how often one client IP may call this action; the name is a limiter from
 * config/packages/rate_limiter.yaml. Over the limit the request gets 429 + Retry-After.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class RateLimited
{
    public function __construct(public string $limiter)
    {
    }
}
