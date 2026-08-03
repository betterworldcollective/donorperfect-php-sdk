<?php

namespace DonorPerfect\Exceptions;

/**
 * A genuine credential rejection — DP answered `Invalid token.` or `login failed`.
 *
 * Extends DonorPerfectException so `catch (DonorPerfectException)` still covers every
 * DP failure, while callers that want to single out a dead API key can catch this.
 * Note that DP's "user not authorized for this api call." is NOT this — that message
 * is its mask for SQL-level failures. See DetectsDonorPerfectFailure.
 */
class UnauthorizedException extends DonorPerfectException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}
