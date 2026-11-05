<?php

declare(strict_types=1);

namespace FixedTermAccess;

use DateTimeImmutable;

final class Term
{
    private const int SECONDS_PER_DAY = 86400;

    public static function extend(?DateTimeImmutable $currentExpiry, int $days, DateTimeImmutable $now): DateTimeImmutable
    {
        $start = $currentExpiry !== null && $currentExpiry > $now ? $currentExpiry : $now;

        return $start->modify("+{$days} days");
    }

    public static function remainingDays(DateTimeImmutable $expiry, DateTimeImmutable $now): int
    {
        $seconds = $expiry->getTimestamp() - $now->getTimestamp();

        return $seconds <= 0 ? 0 : (int) ceil($seconds / self::SECONDS_PER_DAY);
    }
}
