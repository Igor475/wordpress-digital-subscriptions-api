<?php
if (!defined('ABSPATH'))
    exit;

/**
 * ===========================
 * APP MAGIC LOGIN (ajustado)
 * ===========================
 *
 * Este template_redirect só roda quando a URL /app-magic-login é acessada.
 * Ele lê o transient app_ticket_* e:
 * - se existir redirect => redireciona pra ele
 * - senão => monta /app-viewer?product_id=...&flipbook_id=...
 */

// Mantém a rewrite do app-magic-login, se ainda não existir.

add_action('init', function () {
    add_rewrite_rule('^app-magic-login/?$', 'index.php?app_magic_login=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'app_magic_login';
    return $vars;
});

add_action('template_redirect', function () {
    if (!get_query_var('app_magic_login'))
        return;

    $ticket = isset($_GET['ticket']) ? sanitize_text_field(wp_unslash($_GET['ticket'])) : '';
    if (!$ticket)
        wp_die('forbidden', 'Forbidden', ['response' => 403]);

    $data = get_transient('app_ticket_' . $ticket);
    if (!is_array($data) || empty($data['uid'])) {
        wp_die('expired', 'Expired', ['response' => 403]);
    }

    $uid = intval($data['uid']);

    // Usa o redirect informado ou monta a URL padrão do viewer
    $redirect = !empty($data['redirect']) ? esc_url_raw($data['redirect']) : '';
    $product_id = intval($data['product_id'] ?? 0);
    $flipbook_id = intval($data['flipbook_id'] ?? 0);

    if (!$product_id || !$flipbook_id) {
        delete_transient('app_ticket_' . $ticket);
        wp_die('bad_request', 'Bad request', ['response' => 400]);
    }

    if (!function_exists('app_api_user_can_access_product') || !app_api_user_can_access_product($uid, $product_id, true)) {
        delete_transient('app_ticket_' . $ticket);
        wp_die('forbidden', 'Forbidden', ['response' => 403]);
    }

    $resolved_flipbook_id = function_exists('app_api_resolve_product_flipbook_id')
        ? app_api_resolve_product_flipbook_id($product_id, $flipbook_id)
        : new WP_Error('forbidden', 'Forbidden', ['status' => 403]);
    if (is_wp_error($resolved_flipbook_id)) {
        delete_transient('app_ticket_' . $ticket);
        wp_die('forbidden', 'Forbidden', ['response' => 403]);
    }

    // Token de uso único
    delete_transient('app_ticket_' . $ticket);

    if (!$redirect) {
        $redirect = home_url('/app-viewer?product_id=' . $product_id . '&flipbook_id=' . (int) $resolved_flipbook_id);
    }

    // Permite redirecionamento apenas para URLs internas
    $home = wp_parse_url(home_url());
    $to = wp_parse_url($redirect);
    if (!$to || empty($to['host']) || strtolower($to['host']) !== strtolower($home['host'])) {
        wp_die('bad_redirect', 'Bad redirect', ['response' => 400]);
    }

    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, false, is_ssl());

    wp_safe_redirect($redirect);
    exit;
}, 0);


/**
 * ===========================
 * APP VIEWER (nova página)
 * ===========================
 *
 * URL: /app-viewer?product_id=XXX&flipbook_id=YYY
 */

add_action('init', function () {
    add_rewrite_rule('^app-viewer/?$', 'index.php?app_viewer=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'app_viewer';
    return $vars;
});

add_action('template_redirect', function () {
    if (!get_query_var('app_viewer'))
        return;

    if (!is_user_logged_in()) {
        wp_die('forbidden', 'Forbidden', ['response' => 403]);
    }

    $user_id = get_current_user_id();
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $flipbook_id = isset($_GET['flipbook_id']) ? intval($_GET['flipbook_id']) : 0;

    if (!$product_id || !$flipbook_id) {
        wp_die('bad_request', 'Bad request', ['response' => 400]);
    }

    // Valida o direito de acesso ao conteúdo
    if (!function_exists('app_api_user_can_access_product') || !app_api_user_can_access_product($user_id, $product_id, true)) {
        wp_die('forbidden', 'Forbidden', ['response' => 403]);
    }

    $resolved_flipbook_id = function_exists('app_api_resolve_product_flipbook_id')
        ? app_api_resolve_product_flipbook_id($product_id, $flipbook_id)
        : new WP_Error('forbidden', 'Forbidden', ['status' => 403]);
    if (is_wp_error($resolved_flipbook_id)) {
        wp_die('forbidden', 'Forbidden', ['response' => 403]);
    }

    $flipbook_id = (int) $resolved_flipbook_id;

    // Renderiza o shortcode do Real3D
    $tpl = function_exists('app_api_flipbook_shortcode_tpl')
        ? app_api_flipbook_shortcode_tpl()
        : '[real3dflipbook id="{ID}"]';

    $shortcode = str_replace('{ID}', (string) $flipbook_id, $tpl);
    $content = do_shortcode($shortcode);

    status_header(200);
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);

    echo '<!doctype html><html><head><meta charset="utf-8" />';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
    echo '<title>Flipbook</title>';
    echo '<style>html,body{margin:0;padding:0;height:100%;background:#000}#wrap{height:100%}</style>';
    wp_head();
    echo '</head><body><div id="wrap">' . $content . '</div>';
    wp_footer();
    echo '</body></html>';
    exit;
}, 0);
