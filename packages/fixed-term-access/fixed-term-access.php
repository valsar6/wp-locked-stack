<?php

/**
 * Plugin Name:       Fixed Term Access
 * Description:       Grants time-limited access when a WooCommerce order completes.
 * Version:           1.1.0
 * Requires PHP:      8.4
 * Requires Plugins:  woocommerce
 */

declare(strict_types=1);

namespace FixedTermAccess;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Term.php';
require_once __DIR__ . '/src/Access.php';

add_action('woocommerce_order_status_completed', [Access::class, 'grantFromOrder']);
