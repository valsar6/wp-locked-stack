<?php

declare(strict_types=1);

namespace FixedTermAccess;

use DateTimeImmutable;
use WC_Order_Item_Product;

final class Access
{
    public const string EXPIRY_META = '_fta_expires_at';
    public const string PRODUCT_TERM_META = '_fta_term_days';

    public static function grantFromOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order instanceof \WC_Order || $order->get_user_id() === 0) {
            return;
        }

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }
            $days = self::positiveInt(get_post_meta($item->get_product_id(), self::PRODUCT_TERM_META, true));
            if ($days > 0) {
                self::grant($order->get_user_id(), $days * $item->get_quantity());
            }
        }
    }

    public static function grant(int $userId, int $days): void
    {
        $expiry = Term::extend(self::expiry($userId), $days, new DateTimeImmutable());
        update_user_meta($userId, self::EXPIRY_META, $expiry->getTimestamp());
    }

    public static function remainingDays(int $userId): ?int
    {
        $expiry = self::expiry($userId);

        return $expiry === null ? null : Term::remainingDays($expiry, new DateTimeImmutable());
    }

    private static function expiry(int $userId): ?DateTimeImmutable
    {
        $timestamp = self::positiveInt(get_user_meta($userId, self::EXPIRY_META, true));

        return $timestamp > 0 ? new DateTimeImmutable("@{$timestamp}") : null;
    }

    private static function positiveInt(mixed $value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }
}
