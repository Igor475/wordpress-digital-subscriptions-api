<?php
/**
 * Plugin Name: App API (Woo + Real3D)
 * Description: API segura para App (JWT access/refresh), pedidos, assinaturas e revistas (Real3D Flipbook).
 * Version: 1.0.0
 * Author: Igor | CPAD
 */

if (!defined('ABSPATH')) exit;

define('APP_API_VERSION', '1.0.0');
define('APP_API_NS', 'app/v1');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/jwt.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/real3d.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/routes.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/woocommerce.php';
require_once __DIR__ . '/includes/cors.php';
$pf = __DIR__ . '/includes/product-flipbooks.php';
if (file_exists($pf)) {
    require_once $pf;
}

$ml = __DIR__ . '/includes/magic-login.php';
if (file_exists($ml)) {
    require_once $ml;
}

require_once __DIR__ . '/includes/orders.php';

register_activation_hook(__FILE__, 'app_api_activate');

add_action('rest_api_init', 'app_api_register_routes');
add_action('admin_menu', 'app_api_register_admin_menu');
add_action('admin_init', 'app_api_register_settings');

add_filter('password_reset_expiration', 'app_api_filter_password_reset_expiration', 10, 1);
add_action('profile_update', 'app_api_sync_user_profile_after_wp_update', 20, 2);

add_filter('get_avatar_data', 'app_api_filter_get_avatar_data', 20, 2);
add_filter('get_avatar', 'app_api_filter_get_avatar_html', 20, 6);
