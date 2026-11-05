<?php

declare(strict_types=1);

namespace LockedStack\Tests;

use DateTimeImmutable;
use FixedTermAccess\Term;
use PHPUnit\Framework\TestCase;

final class TermTest extends TestCase
{
    public function testActiveAccessExtendsFromCurrentExpiry(): void
    {
        $now = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $expiry = new DateTimeImmutable('2026-01-11T00:00:00Z');

        self::assertSame('2026-02-10', Term::extend($expiry, 30, $now)->format('Y-m-d'));
    }

    public function testExpiredAccessRestartsFromNow(): void
    {
        $now = new DateTimeImmutable('2026-03-01T00:00:00Z');
        $expiry = new DateTimeImmutable('2026-01-11T00:00:00Z');

        self::assertSame('2026-03-31', Term::extend($expiry, 30, $now)->format('Y-m-d'));
    }

    public function testFirstGrantStartsFromNow(): void
    {
        $now = new DateTimeImmutable('2026-03-01T00:00:00Z');

        self::assertSame('2026-03-08', Term::extend(null, 7, $now)->format('Y-m-d'));
    }

    public function testPartialDayCountsAsAWholeDay(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');

        self::assertSame(1, Term::remainingDays(new DateTimeImmutable('2026-01-01T13:00:00Z'), $now));
    }

    public function testPastExpiryHasZeroDaysRemaining(): void
    {
        $now = new DateTimeImmutable('2026-01-02T00:00:00Z');

        self::assertSame(0, Term::remainingDays(new DateTimeImmutable('2026-01-01T00:00:00Z'), $now));
    }
}
