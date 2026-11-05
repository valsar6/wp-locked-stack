<?php

declare(strict_types=1);

use FixedTermAccess\Access;

function stack_theme_access_notice(int $userId): string
{
    if ($userId === 0 || !class_exists(Access::class)) {
        return '';
    }

    $days = Access::remainingDays($userId);
    if ($days === null) {
        return '';
    }

    return $days > 0 ? sprintf('Your access ends in %d days.', $days) : 'Your access has ended.';
}
