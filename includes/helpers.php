<?php
if (!defined('ABSPATH'))
    exit;

function app_api_auth_cookie_names(): array
{
    return [
        'access' => apply_filters('app_api_access_cookie_name', 'cpad_app_access'),
        'refresh' => apply_filters('app_api_refresh_cookie_name', 'cpad_app_refresh'),
    ];
}

function app_api_auth_cookie_domain(): string
{
    $filtered = apply_filters('app_api_auth_cookie_domain', null);
    if (is_string($filtered) && $filtered !== '') {
        return $filtered;
    }

    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    $host = strtolower(trim($host));
    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) || in_array($host, ['localhost', '127.0.0.1'], true)) {
        return '';
    }

    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }

    return '.' . ltrim($host, '.');
}

function app_api_auth_cookie_secure(): bool
{
    $home_scheme = (string) wp_parse_url(home_url('/'), PHP_URL_SCHEME);
    $forwarded_proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $secure = is_ssl() || $home_scheme === 'https' || $forwarded_proto === 'https';
    return (bool) apply_filters('app_api_auth_cookie_secure', $secure);
}

function app_api_auth_cookie_samesite(): string
{
    $default = 'Strict';
    $value = (string) apply_filters('app_api_auth_cookie_samesite', $default);
    $value = trim($value) !== '' ? trim($value) : $default;
    return $value;
}

function app_api_auth_access_ttl_seconds(): int
{
    return max(300, (int) apply_filters('app_api_auth_access_ttl_seconds', 15 * MINUTE_IN_SECONDS));
}

function app_api_auth_refresh_ttl_seconds(): int
{
    return max(HOUR_IN_SECONDS, (int) apply_filters('app_api_auth_refresh_ttl_seconds', 7 * DAY_IN_SECONDS));
}

function app_api_auth_session_idle_ttl_seconds(): int
{
    return max(30 * MINUTE_IN_SECONDS, (int) apply_filters('app_api_auth_session_idle_ttl_seconds', 2 * HOUR_IN_SECONDS));
}

function app_api_auth_reader_token_ttl_seconds(): int
{
    $ttl = max(20 * MINUTE_IN_SECONDS, (int) apply_filters('app_api_auth_reader_token_ttl_seconds', 2 * HOUR_IN_SECONDS));
    return min($ttl, app_api_auth_refresh_ttl_seconds());
}

function app_api_refresh_activity_transient_key(string $refresh_token): string
{
    return 'app_api_rt_act_' . app_api_hash_token($refresh_token);
}

function app_api_touch_refresh_session_activity(string $refresh_token, ?int $timestamp = null): void
{
    $ts = $timestamp ?: time();
    set_transient(
        app_api_refresh_activity_transient_key($refresh_token),
        [
            'last_activity_at' => (int) $ts,
            'touched_at' => gmdate('c', (int) $ts),
        ],
        max(HOUR_IN_SECONDS, app_api_auth_refresh_ttl_seconds())
    );
}

function app_api_get_refresh_session_last_activity(string $refresh_token, $row = null): int
{
    $state = get_transient(app_api_refresh_activity_transient_key($refresh_token));
    if (is_array($state) && !empty($state['last_activity_at'])) {
        return max(0, (int) $state['last_activity_at']);
    }

    if ($row && !empty($row->created_at)) {
        $created = strtotime((string) $row->created_at);
        if ($created) {
            return (int) $created;
        }
    }

    return 0;
}

function app_api_delete_refresh_session_activity(string $refresh_token): void
{
    delete_transient(app_api_refresh_activity_transient_key($refresh_token));
}

function app_api_refresh_session_is_idle_expired(string $refresh_token, $row = null): bool
{
    $last_activity = app_api_get_refresh_session_last_activity($refresh_token, $row);
    if ($last_activity <= 0) {
        return false;
    }

    return (time() - $last_activity) >= app_api_auth_session_idle_ttl_seconds();
}

function app_api_migrate_refresh_session_activity(string $previous_refresh_token, string $next_refresh_token): void
{
    $last_activity = app_api_get_refresh_session_last_activity($previous_refresh_token);
    app_api_touch_refresh_session_activity($next_refresh_token, max(time(), (int) $last_activity));
    app_api_delete_refresh_session_activity($previous_refresh_token);
}

function app_api_auth_validate_refresh_user_agent(): bool
{
    return (bool) apply_filters('app_api_auth_validate_refresh_user_agent', true);
}

function app_api_auth_validate_refresh_ip(): bool
{
    return (bool) apply_filters('app_api_auth_validate_refresh_ip', true);
}

function app_api_normalize_client_user_agent(?string $user_agent): string
{
    return substr(trim((string) $user_agent), 0, 255);
}

function app_api_normalize_client_ip(?string $ip): string
{
    return trim((string) $ip);
}

function app_api_client_ip_matches(?string $stored_ip, ?string $current_ip): bool
{
    $stored_ip = app_api_normalize_client_ip($stored_ip);
    $current_ip = app_api_normalize_client_ip($current_ip);

    if ($stored_ip === '' || $current_ip === '') {
        return true;
    }

    if ($stored_ip === $current_ip) {
        return true;
    }

    if (filter_var($stored_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $stored_parts = explode('.', $stored_ip);
        $current_parts = explode('.', $current_ip);
        return count($stored_parts) === 4 && count($current_parts) === 4
            && $stored_parts[0] === $current_parts[0]
            && $stored_parts[1] === $current_parts[1]
            && $stored_parts[2] === $current_parts[2];
    }

    if (filter_var($stored_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $stored_prefix = implode(':', array_slice(explode(':', $stored_ip), 0, 4));
        $current_prefix = implode(':', array_slice(explode(':', $current_ip), 0, 4));
        return $stored_prefix !== '' && $stored_prefix === $current_prefix;
    }

    return false;
}

function app_api_set_http_only_cookie(string $name, string $value, int $expires_at, bool $http_only = true): bool
{
    if (headers_sent()) {
        return false;
    }

    $secure = app_api_auth_cookie_secure();
    $domain = app_api_auth_cookie_domain();
    $path = (string) apply_filters('app_api_auth_cookie_path', '/');
    $same_site = app_api_auth_cookie_samesite();

    if (PHP_VERSION_ID >= 70300) {
        return setcookie($name, $value, [
            'expires' => $expires_at,
            'path' => $path,
            'domain' => $domain !== '' ? $domain : '',
            'secure' => $secure,
            'httponly' => $http_only,
            'samesite' => $same_site,
        ]);
    }

    $cookie_path = $path;
    if ($same_site !== '') {
        $cookie_path .= '; SameSite=' . $same_site;
    }

    return setcookie($name, $value, $expires_at, $cookie_path, $domain, $secure, $http_only);
}

function app_api_set_auth_cookies(string $access_token, int $access_ttl_seconds, string $refresh_token, int $refresh_ttl_seconds): void
{
    $names = app_api_auth_cookie_names();
    $now = time();

    app_api_set_http_only_cookie($names['access'], $access_token, $now + max(60, $access_ttl_seconds), true);
    app_api_set_http_only_cookie($names['refresh'], $refresh_token, $now + max(300, $refresh_ttl_seconds), true);
}

function app_api_clear_auth_cookies(): void
{
    $names = app_api_auth_cookie_names();
    app_api_set_http_only_cookie($names['access'], '', time() - 3600, true);
    app_api_set_http_only_cookie($names['refresh'], '', time() - 3600, true);
}

function app_api_get_cookie_token(string $type): ?string
{
    $names = app_api_auth_cookie_names();
    $name = $names[$type] ?? '';
    if ($name === '' || !isset($_COOKIE[$name])) {
        return null;
    }

    $value = wp_unslash((string) $_COOKIE[$name]);
    $value = trim($value);
    return $value !== '' ? $value : null;
}

function app_api_get_bearer_token(WP_REST_Request $req): ?string
{
    $auth = $req->get_header('authorization');
    if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
        $token = trim((string) $m[1]);
        if ($token !== '') {
            return $token;
        }
    }

    return app_api_get_cookie_token('access');
}

function app_api_require_access_user(WP_REST_Request $req)
{
    $token = app_api_get_bearer_token($req);
    if (!$token)
        return new WP_Error('unauthorized', 'Sem token', ['status' => 401]);

    $payload = app_api_jwt_verify($token);
    if (is_wp_error($payload))
        return $payload;

    if (($payload['typ'] ?? '') !== 'access') {
        return new WP_Error('unauthorized', 'Token inválido', ['status' => 401]);
    }

    $user_id = intval($payload['sub'] ?? 0);
    $user = get_user_by('id', $user_id);
    if (!$user)
        return new WP_Error('unauthorized', 'Usuário não encontrado', ['status' => 401]);

    // Invalida access tokens emitidos antes da troca de senha
    $pwd_changed = get_user_meta((int) $user->ID, 'app_api_pwd_changed_at', true);
    $pwd_changed = is_numeric($pwd_changed) ? (int) $pwd_changed : 0;
    $iat = (int) ($payload['iat'] ?? 0);
    if ($pwd_changed > 0 && $iat > 0 && $iat < $pwd_changed) {
        return new WP_Error('unauthorized', 'Token expirado. Faça login novamente.', ['status' => 401]);
    }

    // Atualiza o marco de troca de senha do usuário
    if (!app_api_user_can_use_app((int) $user->ID)) {
        return new WP_Error(
            'forbidden',
            'Acesso ao app permitido apenas para assinantes.',
            ['status' => 403]
        );
    }

    return $user;
}

function app_api_require_admin_user(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) {
        return $user;
    }

    // Capacidades aceitas para acesso administrativo:
    // - manage_options
    // - manage_woocommerce
    if (user_can($user, 'manage_options') || user_can($user, 'manage_woocommerce')) {
        return $user;
    }

    return new WP_Error('forbidden', 'Apenas administradores', ['status' => 403]);
}

function app_api_permission_admin($req)
{
    $u = app_api_require_admin_user($req);
    return !is_wp_error($u);
}

function app_api_now_mysql(): string
{
    return gmdate('Y-m-d H:i:s');
}

function app_api_mysql_plus_days(int $days): string
{
    return gmdate('Y-m-d H:i:s', time() + ($days * 86400));
}

function app_api_mysql_plus_seconds(int $seconds): string
{
    return gmdate('Y-m-d H:i:s', time() + $seconds);
}

function app_api_hash_token(string $token): string
{
    return hash('sha256', $token);
}

function app_api_random_token(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

/**
 * CONFIG: meta key onde fica o flipbook_id no produto
 */
function app_api_flipbook_meta_key(): string
{
    // Meta padrão do plugin para associação com flipbooks
    return get_option('app_api_flipbook_meta_key', '_app_api_flipbook_id');
}

/**
 * CONFIG: shortcode para renderizar (use {ID})
 */
function app_api_flipbook_shortcode_tpl(): string
{
    return get_option('app_api_flipbook_shortcode_tpl', '[real3dflipbook id="{ID}"]');
}

function app_api_extract_flipbook_id($val): ?int
{
    if (!$val)
        return null;

    // ID numérico direto
    if (is_numeric($val)) {
        $n = intval($val);
        return $n > 0 ? $n : null;
    }

    // ID extraído de shortcode
    if (is_string($val)) {
        if (preg_match('/\bid\s*=\s*["\']?(\d+)["\']?/i', $val, $m))
            return intval($m[1]);
        if (preg_match('/\bflipbook[_-]?id\s*=\s*["\']?(\d+)["\']?/i', $val, $m))
            return intval($m[1]);
        // Fallback: primeiro número encontrado
        if (preg_match('/\b(\d{1,9})\b/', $val, $m)) {
            $n = intval($m[1]);
            return $n > 0 ? $n : null;
        }
    }

    return null;
}


function app_api_get_product_flipbook_ids(int $product_id): array
{
    // Para assinaturas, lista flipbooks apenas pela categoria raiz do Real3D.
    // Se o produto não tiver categoria raiz, não há retorno.
    $root_slug = trim((string) get_post_meta($product_id, 'app_api_r3d_root_category', true));
    if ($root_slug === '') {
        return [];
    }

    // Exige a taxonomia do Real3D disponível
    if (!taxonomy_exists('r3d_category')) {
        return [];
    }

    $term = get_term_by('slug', $root_slug, 'r3d_category');
    if (!$term || is_wp_error($term)) {
        return [];
    }

    if (!function_exists('app_api_real3d_get_flipbook_ids_by_category_terms')) {
        return [];
    }

    $ids = app_api_real3d_get_flipbook_ids_by_category_terms([intval($term->term_id)], true);
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
    return $ids;
}


if (!function_exists('app_api_get_product_allowed_flipbook_ids')) {
    function app_api_get_product_allowed_flipbook_ids(int $product_id): array
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return [];
        }

        $allowed_ids = function_exists('app_api_get_product_flipbook_ids')
            ? app_api_get_product_flipbook_ids($product_id)
            : [];

        if (!$allowed_ids) {
            $single = function_exists('app_api_get_product_flipbook_id') ? app_api_get_product_flipbook_id($product_id) : null;
            if ($single) {
                $allowed_ids = [(int) $single];
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', (array) $allowed_ids))));
    }
}

if (!function_exists('app_api_resolve_product_flipbook_id')) {
    function app_api_resolve_product_flipbook_id(int $product_id, int $requested_flipbook_id = 0)
    {
        $allowed_ids = function_exists('app_api_get_product_allowed_flipbook_ids')
            ? app_api_get_product_allowed_flipbook_ids($product_id)
            : [];

        if (!$allowed_ids) {
            return new WP_Error('not_found', 'Flipbook(s) não configurado(s) no produto', ['status' => 404]);
        }

        if ($requested_flipbook_id > 0) {
            if (!in_array($requested_flipbook_id, $allowed_ids, true)) {
                return new WP_Error('forbidden', 'Flipbook não permitido para este produto', ['status' => 403]);
            }
            if (function_exists('app_api_is_real3d_flipbook_id') && !app_api_is_real3d_flipbook_id($requested_flipbook_id)) {
                return new WP_Error('bad_request', 'flipbook_id inválido', ['status' => 400]);
            }
            return (int) $requested_flipbook_id;
        }

        foreach ($allowed_ids as $id) {
            if (!function_exists('app_api_is_real3d_flipbook_id') || app_api_is_real3d_flipbook_id((int) $id)) {
                return (int) $id;
            }
        }

        return new WP_Error('not_found', 'Nenhum flipbook válido encontrado', ['status' => 404]);
    }
}

if (!function_exists('app_api_request_has_bearer_token')) {
    function app_api_request_has_bearer_token(WP_REST_Request $req): bool
    {
        $auth = $req->get_header('authorization');
        return is_string($auth) && preg_match('/^Bearer\s+.+$/i', trim($auth)) === 1;
    }
}


if (!function_exists('app_api_validate_device_id')) {
    function app_api_validate_device_id($raw_device_id, bool $required = true)
    {
        $device_id = sanitize_text_field((string) $raw_device_id);
        $device_id = trim($device_id);

        if ($device_id === '') {
            return $required
                ? new WP_Error('bad_request', 'device_id é obrigatório', ['status' => 400])
                : null;
        }

        if (strlen($device_id) > 128) {
            return new WP_Error('bad_request', 'device_id muito grande', ['status' => 400]);
        }

        if (!preg_match('/^[A-Za-z0-9._:@-]{8,128}$/', $device_id)) {
            return new WP_Error('bad_request', 'device_id inválido', ['status' => 400]);
        }

        return $device_id;
    }
}

if (!function_exists('app_api_send_private_no_store_headers')) {
    function app_api_send_private_no_store_headers(): void
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Vary: Authorization, Cookie, Origin');
        header('X-Content-Type-Options: nosniff');
    }
}

/**
 * Entitlements: base por pedidos pagos + assinaturas.
 * Você pode estender via filtros para incluir Indeed Membership, etc.
 */
function app_api_get_entitled_product_ids(int $user_id): array
{
    $ids = [];

    $normalize_pid = function ($pid) {
        $pid = intval($pid);
        if ($pid <= 0)
            return 0;

        // Normaliza variação para produto pai
        if (get_post_type($pid) === 'product_variation') {
            $parent = wp_get_post_parent_id($pid);
            if ($parent)
                return intval($parent);
        }
        return $pid;
    };

    // Pedidos pagos
    if (function_exists('wc_get_orders')) {
        $paid = wc_get_is_paid_statuses(); // processing, completed...
        $paid[] = 'on-hold'; // comum em boleto
        $paid = array_values(array_unique($paid));

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array_map(fn($s) => 'wc-' . $s, $paid),
        ]);

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $pid = $normalize_pid($item->get_product_id());
                if ($pid)
                    $ids[] = $pid;

                if (method_exists($item, 'get_variation_id')) {
                    $vid = $normalize_pid($item->get_variation_id());
                    if ($vid)
                        $ids[] = $vid;
                }
            }
        }
    }

    // Assinaturas, quando disponíveis
    $sub_statuses = ['wc-active', 'wc-trial', 'wc-pending-cancel', 'wc-on-hold'];

    if (function_exists('wcs_get_users_subscriptions')) {
        $subs = wcs_get_users_subscriptions($user_id);
        foreach ($subs as $sub) {
            $st = method_exists($sub, 'get_status') ? 'wc-' . $sub->get_status() : null;
            if ($st && !in_array($st, $sub_statuses, true))
                continue;

            foreach ($sub->get_items() as $item) {
                $pid = $normalize_pid($item->get_product_id());
                if ($pid)
                    $ids[] = $pid;

                if (method_exists($item, 'get_variation_id')) {
                    $vid = $normalize_pid($item->get_variation_id());
                    if ($vid)
                        $ids[] = $vid;
                }
            }
        }
    } else {
        // Fallback para assinaturas salvas como posts
        $subs_posts = get_posts([
            'post_type' => 'shop_subscription',
            'post_status' => $sub_statuses,
            'posts_per_page' => 200,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_customer_user',
                    'value' => $user_id,
                    'compare' => '=',
                ]
            ]
        ]);

        foreach ($subs_posts as $sub_id) {
            $sub_order = function_exists('wc_get_order') ? wc_get_order($sub_id) : null;
            if (!$sub_order)
                continue;

            foreach ($sub_order->get_items() as $item) {
                $pid = $normalize_pid($item->get_product_id());
                if ($pid)
                    $ids[] = $pid;

                if (method_exists($item, 'get_variation_id')) {
                    $vid = $normalize_pid($item->get_variation_id());
                    if ($vid)
                        $ids[] = $vid;
                }
            }
        }
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    $ids = apply_filters('app_api_user_entitled_product_ids', $ids, $user_id);
    return $ids;
}

function app_api_get_product_flipbook_id(int $product_id): ?int
{
    $ids = app_api_get_product_flipbook_ids($product_id);
    return $ids[0] ?? null;
}

if (!function_exists('app_api_is_real3d_flipbook_id')) {
    function app_api_is_real3d_flipbook_id(int $id): bool
    {
        if ($id <= 0)
            return false;

        // Se existir opção interna do Real3D, trata como flipbook.
        // Evita colisão com posts do WordPress que usem o mesmo ID.
                        $real3d_opt = get_option('real3dflipbook_' . $id, false);
        if ($real3d_opt !== false)
            return true;

        $pt = get_post_type($id);

        // Se não for post do WordPress, aceita como ID interno do Real3D.
                if (!$pt)
            return true;

        $pt = strtolower((string) $pt);

        // Aceita post types comuns do Real3D
        if ($pt === 'r3d')
            return true;
        if ($pt === 'real3dflipbook')
            return true;

        // Heurística adicional por nome do post type
        if (strpos($pt, 'flipbook') !== false)
            return true;
        if (strpos($pt, 'real3d') !== false)
            return true;

        // Rejeita posts que não aparentam ser flipbooks
        return false;
    }
}

/**
 * Config: exigir pedido "Concluído" para usar o app?
 * (admin sempre passa)
 */
function app_api_require_completed_order_for_app(): bool
{
    $v = get_option('app_api_require_completed_order_for_app', '1'); // default: ligado
    return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
}

/**
 * Cache curto para não consultar Woo toda hora
 * Basta existir 1 pedido concluído
 */
function app_api_user_has_completed_order(int $user_id): bool
{
    if ($user_id <= 0)
        return false;

    // Administradores sempre têm acesso
    $u = get_user_by('id', $user_id);
    if ($u && user_can($u, 'manage_options'))
        return true;

    if (!function_exists('wc_get_orders'))
        return false;

    $cache_key = 'app_api_completed_order_' . $user_id;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached === '1';
    }

    // Compatibilidade com variações do status completed
    $statuses = ['completed', 'wc-completed'];

    // Exige ao menos um pedido concluído
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status' => $statuses,
        'limit' => 1,
        'return' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    // Fallback para pedidos sem vínculo direto com customer_id
    if (empty($orders) && $u) {
        $orders = wc_get_orders([
            'customer' => $user_id,
            'status' => $statuses,
            'limit' => 1,
            'return' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    if (empty($orders) && $u && !empty($u->user_email)) {
        // Tenta query var, se suportada
        $orders = wc_get_orders([
            'billing_email' => $u->user_email,
            'status' => $statuses,
            'limit' => 1,
            'return' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        // Fallback via meta_query
        if (empty($orders)) {
            $orders = wc_get_orders([
                'status' => $statuses,
                'limit' => 1,
                'return' => 'ids',
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => '_billing_email',
                        'value' => $u->user_email,
                        'compare' => '=',
                    ]
                ]
            ]);
        }
    }

    $ok = !empty($orders);

    set_transient($cache_key, $ok ? '1' : '0', 5 * MINUTE_IN_SECONDS);
    return $ok;
}

if (!function_exists('app_api_user_is_admin_like')) {
    function app_api_user_is_admin_like(int $user_id): bool
    {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        return user_can($user, 'manage_options') || user_can($user, 'manage_woocommerce');
    }
}

/**
 * Regra central de acesso ao app
 */
function app_api_user_can_use_app(int $user_id): bool
{
    if (app_api_user_is_admin_like($user_id)) {
        return true;
    }

    if (!app_api_require_completed_order_for_app())
        return true;
    return app_api_user_has_completed_order($user_id);
}

/**
 * Retorna IDs de produtos que o usuário comprou em pedidos "Concluídos".
 * (variações viram o produto pai)
 * Cache curto por performance.
 */
function app_api_get_completed_order_product_ids(int $user_id): array
{
    if ($user_id <= 0)
        return [];

    // Administradores não dependem de pedidos concluídos
    $u = get_user_by('id', $user_id);
    if ($u && user_can($u, 'manage_options'))
        return [];

    if (!function_exists('wc_get_orders'))
        return [];

    $cache_key = 'app_api_completed_pids_' . $user_id;
    // O cache deve refletir a lista calculada nesta chamada.
    
    // Usa transient curto para reduzir consultas repetidas.
    // A invalidação ocorre pelos hooks do WooCommerce.
    // Pode ser desativado pelo filtro app_api_use_completed_pids_cache.
    $use_cache = (bool) apply_filters('app_api_use_completed_pids_cache', true, $user_id);
    if ($use_cache) {
        $cached_ids = get_transient($cache_key);
        if (is_array($cached_ids)) {
            $cached_ids = array_values(array_unique(array_filter(array_map('intval', $cached_ids))));
            return $cached_ids;
        }
    }


    $normalize_pid = function ($pid) {
        $pid = (int) $pid;
        if ($pid <= 0)
            return 0;
        if (get_post_type($pid) === 'product_variation') {
            $parent = wp_get_post_parent_id($pid);
            if ($parent)
                return (int) $parent;
        }
        return $pid;
    };

    // Compatibilidade com variações do status completed
    $statuses = ['completed', 'wc-completed'];

    $query_base = [
        'status' => $statuses,
        'limit' => 1000,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ];

    // Em algumas instalações, parte dos argumentos do WooCommerce pode ser ignorada.
        // Por isso, o resultado é filtrado novamente abaixo.
    
    $orders = [];

    // Pedidos vinculados ao usuário por customer_id
    $orders_customer = wc_get_orders(array_merge($query_base, [
        'customer_id' => $user_id,
    ]));
    if (!empty($orders_customer)) {
        $orders = array_merge($orders, $orders_customer);
    }

    // Compatibilidade com instalações que usam customer
    if ($u) {
        $orders_customer_compat = wc_get_orders(array_merge($query_base, [
            'customer' => $user_id,
        ]));
        if (!empty($orders_customer_compat)) {
            $orders = array_merge($orders, $orders_customer_compat);
        }
    }

    // Fallback por e-mail para pedidos antigos
    if ($u && !empty($u->user_email)) {

        // Tenta query var, se suportada
        $orders_by_email = wc_get_orders(array_merge($query_base, [
            'billing_email' => $u->user_email,
        ]));
        if (!empty($orders_by_email)) {
            $orders = array_merge($orders, $orders_by_email);
        }

        // Fallback via meta_query
        $orders_by_email_meta = wc_get_orders(array_merge($query_base, [
            'meta_query' => [
                [
                    'key' => '_billing_email',
                    'value' => $u->user_email,
                    'compare' => '=',
                ]
            ]
        ]));
        if (!empty($orders_by_email_meta)) {
            $orders = array_merge($orders, $orders_by_email_meta);
        }
    }

    // Filtragem final para evitar pedidos de terceiros
    // - status concluído
    // - vínculo por customer_id ou billing_email
    if (!empty($orders)) {
        $user_email = ($u && !empty($u->user_email)) ? strtolower(trim($u->user_email)) : '';
        $orders = array_values(array_filter($orders, function ($order) use ($user_id, $user_email) {
            if (!$order || !is_a($order, 'WC_Order')) {
                return false;
            }

            // get_status() costuma retornar sem o prefixo wc-.
            $st = $order->get_status();
            if ($st !== 'completed') {
                return false;
            }

            $cid = (int) $order->get_customer_id();
            if ($cid === (int) $user_id && $cid > 0) {
                return true;
            }

            $be = $order->get_billing_email();
            $be = is_string($be) ? strtolower(trim($be)) : '';
            if ($user_email && $be && $be === $user_email) {
                return true;
            }

            return false;
        }));
    }

    $ids = [];
    foreach ($orders as $order) {
        if (!$order || !is_a($order, 'WC_Order'))
            continue;

        foreach ($order->get_items() as $item) {
            $pid = $normalize_pid($item->get_product_id());
            if ($pid)
                $ids[] = $pid;

            if (method_exists($item, 'get_variation_id')) {
                $vid = $normalize_pid($item->get_variation_id());
                if ($vid)
                    $ids[] = $vid;
            }
        }
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    $ids = apply_filters('app_api_user_completed_product_ids', $ids, $user_id);

    set_transient($cache_key, $ids, 5 * MINUTE_IN_SECONDS);
    return $ids;
}

/**
 * Acesso a conteúdo (revistas) SOMENTE se o produto estiver em pedido concluído.
 */
function app_api_user_can_access_product(int $user_id, int $product_id, bool $only_completed = false): bool
{
    $product_id = (int) $product_id;
    if ($product_id <= 0)
        return false;

    // Administradores sempre têm acesso
    $u = get_user_by('id', $user_id);
    if ($u && user_can($u, 'manage_options'))
        return true;

    if ($only_completed) {
        $ids = app_api_get_completed_order_product_ids($user_id);
        if (!in_array($product_id, $ids, true)) {
            return false;
        }

        return app_api_ihc_user_has_active_product_access($user_id, $product_id);
    }

    // Fallback legado
    if (function_exists('app_api_get_entitled_product_ids')) {
        $ids = app_api_get_entitled_product_ids($user_id);
        if (!in_array($product_id, $ids, true)) {
            return false;
        }

        return app_api_ihc_user_has_active_product_access($user_id, $product_id);
    }

    return false;
}



if (!function_exists('app_api_ihc_is_available')) {
    function app_api_ihc_is_available(): bool
    {
        return class_exists('\Indeed\Ihc\UserSubscriptions') && class_exists('\Indeed\Ihc\Db\Memberships');
    }
}

if (!function_exists('app_api_ihc_get_product_level_id')) {
    function app_api_ihc_get_product_level_id(int $product_id): int
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return 0;
        }

        $level_id = (int) get_post_meta($product_id, 'iump_woo_product_level_relation', true);
        return $level_id > 0 ? $level_id : 0;
    }
}

if (!function_exists('app_api_ihc_datetime_to_iso8601')) {
    function app_api_ihc_datetime_to_iso8601($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(wp_timezone_string() ?: 'UTC');
            $dt = new DateTimeImmutable($value, $tz);
            return $dt->format('c');
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('app_api_ihc_effective_expire_mysql')) {
    function app_api_ihc_effective_expire_mysql($expire_time, int $grace_period_days = 0): ?string
    {
        $expire_time = trim((string) $expire_time);
        if ($expire_time === '' || $expire_time === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(wp_timezone_string() ?: 'UTC');
            $dt = new DateTimeImmutable($expire_time, $tz);
            if ($grace_period_days > 0) {
                $dt = $dt->modify('+' . $grace_period_days . ' days');
            }
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('app_api_ihc_get_product_access_state')) {
    function app_api_ihc_get_product_access_state(int $user_id, int $product_id): array
    {
        $product_id = (int) $product_id;
        $level_id = function_exists('app_api_ihc_get_product_level_id') ? app_api_ihc_get_product_level_id($product_id) : 0;
        $product_url = get_permalink($product_id) ?: '';
        $product_name = get_the_title($product_id) ?: '';

        $state = [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'product_url' => $product_url ?: null,
            'renewal_url' => $product_url ?: null,
            'membership_enabled' => false,
            'level_id' => $level_id ?: null,
            'level_label' => null,
            'status' => 'unmanaged',
            'status_label' => 'Acesso sem expiração configurada',
            'is_active' => true,
            'is_expired' => false,
            'has_expiration' => false,
            'has_subscription' => false,
            'start_time' => null,
            'expires_at' => null,
            'raw_expires_at' => null,
            'grace_period_days' => 0,
        ];

        if ($level_id <= 0 || !app_api_ihc_is_available()) {
            return $state;
        }

        $state['membership_enabled'] = true;

        try {
            $level_data = \Indeed\Ihc\Db\Memberships::getOne($level_id);
            if (is_array($level_data) && !empty($level_data['label'])) {
                $state['level_label'] = (string) $level_data['label'];
            }
        } catch (Throwable $e) {
            // Ignora falhas de limpeza de cache
        }

        $current_ts = function_exists('current_time') ? (int) current_time('timestamp') : time();
        $has_subscription = false;
        $start_time = null;
        $expire_time = null;
        $grace_days = 0;
        $is_active = false;

        try {
            if (method_exists('\Indeed\Ihc\UserSubscriptions', 'userHasSubscription')) {
                $has_subscription = (bool) \Indeed\Ihc\UserSubscriptions::userHasSubscription($user_id, $level_id);
            }

            if (method_exists('\Indeed\Ihc\UserSubscriptions', 'getStartAndExpireForSubscription')) {
                $times = \Indeed\Ihc\UserSubscriptions::getStartAndExpireForSubscription($user_id, $level_id);
                if (is_array($times)) {
                    $start_time = $times['start_time'] ?? null;
                    $expire_time = $times['expire_time'] ?? null;
                }
            }

            if (method_exists('\Indeed\Ihc\UserSubscriptions', 'getGracePeriod')) {
                $grace_days = (int) \Indeed\Ihc\UserSubscriptions::getGracePeriod($user_id, $level_id);
            }

            if ($has_subscription && method_exists('\Indeed\Ihc\UserSubscriptions', 'isActive')) {
                $is_active = (bool) \Indeed\Ihc\UserSubscriptions::isActive($user_id, $level_id);
            }
        } catch (Throwable $e) {
            // Ignora falhas de limpeza de cache
        }

        $state['has_subscription'] = $has_subscription;
        $state['grace_period_days'] = max(0, (int) $grace_days);
        $state['start_time'] = app_api_ihc_datetime_to_iso8601($start_time);
        $state['raw_expires_at'] = app_api_ihc_datetime_to_iso8601($expire_time);

        $effective_expire_mysql = app_api_ihc_effective_expire_mysql($expire_time, $state['grace_period_days']);
        $state['expires_at'] = app_api_ihc_datetime_to_iso8601($effective_expire_mysql ?: $expire_time);
        $state['has_expiration'] = !empty($state['expires_at']);

        $start_ts = $start_time && $start_time !== '0000-00-00 00:00:00' ? strtotime((string) $start_time) : 0;
        $expire_ts = $effective_expire_mysql ? strtotime((string) $effective_expire_mysql) : 0;

        if (!$has_subscription) {
            $state['status'] = 'inactive';
            $state['status_label'] = 'Sem acesso ativo';
            $state['is_active'] = false;
            $state['is_expired'] = false;
            return $state;
        }

        if ($start_ts > 0 && $current_ts < $start_ts) {
            $state['status'] = 'scheduled';
            $state['status_label'] = 'Acesso programado';
            $state['is_active'] = false;
            $state['is_expired'] = false;
            return $state;
        }

        if ($is_active) {
            $state['status'] = 'active';
            $state['status_label'] = 'Acesso ativo';
            $state['is_active'] = true;
            $state['is_expired'] = false;
            return $state;
        }

        if ($expire_ts > 0 && $current_ts > $expire_ts) {
            $state['status'] = 'expired';
            $state['status_label'] = 'Acesso expirado';
            $state['is_active'] = false;
            $state['is_expired'] = true;
            return $state;
        }

        $state['status'] = 'inactive';
        $state['status_label'] = 'Sem acesso ativo';
        $state['is_active'] = false;
        $state['is_expired'] = false;
        return $state;
    }
}

if (!function_exists('app_api_ihc_user_has_active_product_access')) {
    function app_api_ihc_user_has_active_product_access(int $user_id, int $product_id): bool
    {
        $state = app_api_ihc_get_product_access_state($user_id, $product_id);
        if (empty($state['membership_enabled'])) {
            return true;
        }
        return !empty($state['is_active']);
    }
}

if (!function_exists('app_api_filter_active_membership_product_ids')) {
    function app_api_filter_active_membership_product_ids(int $user_id, array $product_ids): array
    {
        $out = [];
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0) {
                continue;
            }
            if (app_api_ihc_user_has_active_product_access($user_id, $product_id)) {
                $out[] = $product_id;
            }
        }
        return array_values(array_unique($out));
    }
}

if (!function_exists('app_api_build_membership_access_error')) {
    function app_api_build_membership_access_error(int $user_id, int $product_id)
    {
        $state = app_api_ihc_get_product_access_state($user_id, $product_id);
        if (empty($state['membership_enabled'])) {
            return null;
        }
        if (!empty($state['is_active'])) {
            return null;
        }

        $message = !empty($state['is_expired'])
            ? 'Seu acesso a esta revista expirou. Faça uma nova compra para continuar.'
            : 'Seu acesso a esta revista não está ativo no momento.';

        if (!empty($state['expires_at'])) {
            $message .= ' Data de expiração: ' . $state['expires_at'];
        }

        return new WP_Error('access_expired', $message, [
            'status' => 403,
            'product_id' => (int) $product_id,
            'access' => $state,
        ]);
    }
}

/**
 * (Opcional, mas recomendado) Limpa cache quando pedidos mudam de status
 */
add_action('woocommerce_order_status_changed', function ($order_id) {
    if (!function_exists('wc_get_order'))
        return;
    $order = wc_get_order($order_id);
    if (!$order)
        return;

    $uid = (int) $order->get_customer_id();
    if ($uid > 0) {
        delete_transient('app_api_completed_pids_' . $uid);
        delete_transient('app_api_completed_order_' . $uid);
        // Limpa os caches do app após alterações relevantes
        delete_transient('app_api_cache_me_' . $uid);
        delete_transient('app_api_cache_orders_' . $uid);
        delete_transient('app_api_cache_orders_v2_' . $uid);
        delete_transient('app_api_cache_subs_' . $uid);
        delete_transient('app_api_cache_subs_v2_' . $uid);
        delete_transient('app_api_cache_magazines_' . $uid);
    }
}, 10, 1);

/**
 * Limpa o cache de pedidos concluídos quando um pedido muda para "completed".
 * Isso evita que o app fique mostrando "0 revistas" por alguns minutos após você
 * marcar um pedido como Concluído no WooCommerce.
 */
add_action('woocommerce_order_status_changed', function ($order_id, $from, $to, $order) {
    if ($to !== 'completed' && $to !== 'wc-completed') {
        return;
    }
    if (!function_exists('wc_get_order')) {
        return;
    }
    if (!$order || !is_a($order, 'WC_Order')) {
        $order = wc_get_order($order_id);
    }
    if (!$order) {
        return;
    }
    $uid = (int) $order->get_user_id();
    if ($uid <= 0) {
        return;
    }
    delete_transient('app_api_completed_order_' . $uid);
    delete_transient('app_api_completed_pids_' . $uid);
    // Limpa os caches do app após alterações relevantes
    delete_transient('app_api_cache_me_' . $uid);
    delete_transient('app_api_cache_orders_' . $uid);
        delete_transient('app_api_cache_orders_v2_' . $uid);
    delete_transient('app_api_cache_subs_' . $uid);
        delete_transient('app_api_cache_subs_v2_' . $uid);
    delete_transient('app_api_cache_magazines_' . $uid);
}, 10, 4);


// -----------------------------------------------------------------------------
// Cache invalidation (WooCommerce)
// -----------------------------------------------------------------------------

// Limpa o cache de produtos elegíveis quando o pedido vira completed.
// Evita manter resultado vazio logo após a aprovação do pagamento.
if (!function_exists('app_api_clear_completed_pids_cache_on_order_completed')) {
    function app_api_clear_completed_pids_cache_on_order_completed($order_id)
    {
        if (!function_exists('wc_get_order')) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $uid = (int) $order->get_customer_id();
        if ($uid > 0) {
            delete_transient('app_api_completed_pids_' . $uid);
            // Limpa os caches do app após alterações relevantes
            delete_transient('app_api_cache_me_' . $uid);
            delete_transient('app_api_cache_orders_' . $uid);
        delete_transient('app_api_cache_orders_v2_' . $uid);
            delete_transient('app_api_cache_subs_' . $uid);
        delete_transient('app_api_cache_subs_v2_' . $uid);
            delete_transient('app_api_cache_magazines_' . $uid);
        }
    }
}

add_action('woocommerce_order_status_completed', 'app_api_clear_completed_pids_cache_on_order_completed', 10, 1);

// Também cobre mudanças manuais de status
add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status) {
    if ($new_status === 'completed') {
        app_api_clear_completed_pids_cache_on_order_completed($order_id);
    }
}, 10, 3);


if (!function_exists('app_api_clear_entitlement_caches_for_user')) {
    function app_api_clear_entitlement_caches_for_user(int $user_id): void
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return;
        }

        delete_transient('app_api_completed_pids_' . $user_id);
        delete_transient('app_api_completed_order_' . $user_id);
        delete_transient('app_api_cache_me_' . $user_id);
        delete_transient('app_api_cache_orders_' . $user_id);
        delete_transient('app_api_cache_orders_v2_' . $user_id);
        delete_transient('app_api_cache_subs_' . $user_id);
        delete_transient('app_api_cache_subs_v2_' . $user_id);
        delete_transient('app_api_cache_magazines_' . $user_id);
        delete_transient('app_api_cache_magazines_v2_' . $user_id);
    }
}

add_action('ihc_payment_completed', function ($user_id) {
    app_api_clear_entitlement_caches_for_user((int) $user_id);
}, 10, 1);

add_action('ihc_action_subscription_expired', function ($user_id) {
    app_api_clear_entitlement_caches_for_user((int) $user_id);
}, 10, 1);

add_action('ihc_action_level_has_expired', function ($user_id) {
    app_api_clear_entitlement_caches_for_user((int) $user_id);
}, 10, 1);

add_action('ihc_action_after_subscription_renew_activated', function ($user_id) {
    app_api_clear_entitlement_caches_for_user((int) $user_id);
}, 10, 1);

add_action('ihc_action_after_subscription_first_time_activated', function ($user_id) {
    app_api_clear_entitlement_caches_for_user((int) $user_id);
}, 10, 1);

// -----------------------------------------------------------------------------
// Helpers de agregação de categorias das revistas
// -----------------------------------------------------------------------------

if (!function_exists('app_api_magazines_collect_categories')) {
    /**
     * Coleta categorias únicas (Real3D) a partir do payload de revistas.
     * Retorna lista ordenada por nome e com contagem.
     */
    function app_api_magazines_collect_categories(array $magazines): array
    {
        $map = [];

        foreach ($magazines as $m) {
            $cats = $m['categories'] ?? [];
            if (!is_array($cats) || empty($cats)) {
                continue;
            }

            foreach ($cats as $c) {
                $id = isset($c['id']) ? (int) $c['id'] : 0;
                if ($id <= 0) {
                    continue;
                }

                if (!isset($map[$id])) {
                    $map[$id] = [
                        'id' => $id,
                        'name' => (string) ($c['name'] ?? ''),
                        'slug' => isset($c['slug']) ? (string) $c['slug'] : '',
                        'parent' => isset($c['parent']) ? (int) $c['parent'] : 0,
                        'taxonomy' => isset($c['taxonomy']) ? (string) $c['taxonomy'] : '',
                        'count' => 0,
                    ];
                } else {
                    // Atualiza campos se vierem vazios e aparecerem depois
                    if (!$map[$id]['name'] && !empty($c['name'])) {
                        $map[$id]['name'] = (string) $c['name'];
                    }
                    if (!$map[$id]['slug'] && !empty($c['slug'])) {
                        $map[$id]['slug'] = (string) $c['slug'];
                    }
                    if (!$map[$id]['parent'] && !empty($c['parent'])) {
                        $map[$id]['parent'] = (int) $c['parent'];
                    }
                    if (!$map[$id]['taxonomy'] && !empty($c['taxonomy'])) {
                        $map[$id]['taxonomy'] = (string) $c['taxonomy'];
                    }
                }

                $map[$id]['count']++;
            }
        }

        $cats = array_values($map);
        usort($cats, function ($a, $b) {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $cats;
    }
}


// =========================================================
// URL/Media helpers (added for App PDF + thumbs stability)
// =========================================================

if (!function_exists('app_api_force_https_url')) {
    function app_api_force_https_url(?string $url): ?string
    {
        if (!$url)
            return $url;
        $u = trim((string) $url);
        if ($u === '')
            return $u;

        // Preserve protocol-relative URLs
        if (strpos($u, '//') === 0) {
            return 'https:' . $u;
        }

        // Force https when same host or any http URL (Android blocks cleartext by default)
        $u = preg_replace('#^http://#i', 'https://', $u);
        return $u;
    }
}

if (!function_exists('app_api_map_upload_url_to_path')) {
    function app_api_map_upload_url_to_path(string $url): ?string
    {
        $uploads = wp_upload_dir();
        $baseurl = isset($uploads['baseurl']) ? rtrim((string)$uploads['baseurl'], '/') : '';
        $basedir = isset($uploads['basedir']) ? rtrim((string)$uploads['basedir'], '/') : '';
        if (!$baseurl || !$basedir) return null;

        $norm = app_api_force_https_url($url);
        $baseurl_norm = app_api_force_https_url($baseurl);

        if (stripos($norm, $baseurl_norm) !== 0) return null;

        $rel = substr($norm, strlen($baseurl_norm));
        $rel = ltrim($rel, '/');
        $path = $basedir . '/' . $rel;
        return file_exists($path) ? $path : null;
    }
}


// =========================================================
// OFFLINE LICENSE (RS256) - robusto para leitura offline com expiração
// =========================================================

if (!function_exists('app_api_offline_default_days')) {
    function app_api_offline_default_days(): int
    {
        $days = intval(get_option('app_api_offline_days', 7));
        if ($days < 1) $days = 1;
        if ($days > 14) $days = 14;
        return $days;
    }
}

if (!function_exists('app_api_offline_keys_option_names')) {
    function app_api_offline_keys_option_names(): array
    {
        return [
            'priv' => 'app_api_offline_private_key_pem',
            'pub'  => 'app_api_offline_public_key_pem',
            'kid'  => 'app_api_offline_public_key_kid',
            'created' => 'app_api_offline_keys_created_at',
        ];
    }
}

if (!function_exists('app_api_offline_ensure_keys')) {
    /**
     * Gera e persiste chaves RSA para assinar licenças offline.
     * - private key fica no WP options (autload=no)
     * - public key pode ser exposta via endpoint /offline/public-key
     */
    function app_api_offline_ensure_keys(): bool
    {
        if (!function_exists('openssl_pkey_new')) {
            return false;
        }

        $opt = app_api_offline_keys_option_names();

        $priv = get_option($opt['priv']);
        $pub  = get_option($opt['pub']);
        $kid  = get_option($opt['kid']);

        if (is_string($priv) && $priv && is_string($pub) && $pub && is_string($kid) && $kid) {
            return true;
        }

        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];

        $res = @openssl_pkey_new($config);
        if (!$res) return false;

        $priv_pem = '';
        $ok = @openssl_pkey_export($res, $priv_pem);
        if (!$ok || !$priv_pem) return false;

        $details = @openssl_pkey_get_details($res);
        $pub_pem = (is_array($details) && !empty($details['key'])) ? $details['key'] : null;
        if (!$pub_pem) return false;

        $kid = substr(hash('sha256', $pub_pem), 0, 16);

        // autoload=no para não poluir options carregadas em toda request
        update_option($opt['priv'], $priv_pem, false);
        update_option($opt['pub'], $pub_pem, false);
        update_option($opt['kid'], $kid, false);
        update_option($opt['created'], app_api_now_mysql(), false);

        return true;
    }
}

if (!function_exists('app_api_offline_get_public_key_pem')) {
    function app_api_offline_get_public_key_pem(): ?string
    {
        $opt = app_api_offline_keys_option_names();
        $pub = get_option($opt['pub']);
        return is_string($pub) && $pub ? $pub : null;
    }
}

if (!function_exists('app_api_offline_get_kid')) {
    function app_api_offline_get_kid(): ?string
    {
        $opt = app_api_offline_keys_option_names();
        $kid = get_option($opt['kid']);
        return is_string($kid) && $kid ? $kid : null;
    }
}

if (!function_exists('app_api_offline_jwt_sign_rs256')) {
    /**
     * Assina um JWT (JWS compact) com RS256.
     * Usado apenas para "licença offline" verificável no app sem segredo.
     */
    function app_api_offline_jwt_sign_rs256(array $payload): string
    {
        $opt = app_api_offline_keys_option_names();
        $priv = get_option($opt['priv']);

        if (!is_string($priv) || !$priv) {
            // tenta garantir chaves
            app_api_offline_ensure_keys();
            $priv = get_option($opt['priv']);
        }

        if (!is_string($priv) || !$priv) {
            // fallback (não verificável offline) - melhor do que quebrar (mas app deve preferir RS256)
            return app_api_jwt_sign($payload);
        }

        $kid = app_api_offline_get_kid();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];
        if ($kid) $header['kid'] = $kid;

        $h = app_api_b64url_enc(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = app_api_b64url_enc(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $data = "$h.$p";

        $signature = '';
        $ok = @openssl_sign($data, $signature, $priv, OPENSSL_ALGO_SHA256);
        if (!$ok || !$signature) {
            // fallback HS256 (não ideal)
            return app_api_jwt_sign($payload);
        }

        $s = app_api_b64url_enc($signature);
        return "$h.$p.$s";
    }
}

if (!function_exists('app_api_offline_license_rate_ok')) {
    /**
     * Rate limit simples: evita abusos no endpoint de licença offline.
     * Padrão: 60 emissões por hora por usuário (ajustável via filtro).
     */
    function app_api_offline_license_rate_ok(int $user_id): bool
    {
        $limit = apply_filters('app_api_offline_license_rate_limit', 60, $user_id);
        $window = apply_filters('app_api_offline_license_rate_window_sec', 3600, $user_id);

        $key = 'app_api_offline_rl_' . $user_id;
        $state = get_transient($key);
        if (!is_array($state)) {
            set_transient($key, ['count' => 1, 'ts' => time()], $window);
            return true;
        }

        $count = intval($state['count'] ?? 0) + 1;
        if ($count > intval($limit)) {
            return false;
        }

        $state['count'] = $count;
        set_transient($key, $state, $window);
        return true;
    }
}


// ===============================
// Woo helpers (status/moeda)
// ===============================

if (!function_exists('app_api_wc_order_status_label')) {
    function app_api_wc_order_status_label($status): string
    {
        $status = (string) $status;
        $status = trim($status);
        if ($status === '')
            return '';

        $key = (stripos($status, 'wc-') === 0) ? $status : ('wc-' . $status);

        if (function_exists('wc_get_order_status_name')) {
            $label = (string) wc_get_order_status_name($key);
            return $label !== '' ? $label : $status;
        }

        return $status;
    }
}

if (!function_exists('app_api_wc_price_plain')) {
    /**
     * Retorna preço como string (sem HTML), respeitando formatação do WooCommerce/locale.
     * Ex.: "R$ 29,00"
     */
    function app_api_wc_price_plain($amount, ?string $currency = null): string
    {
        $n = 0.0;
        if (is_numeric($amount)) {
            $n = (float) $amount;
        } elseif (is_string($amount)) {
            // tenta limpar e converter
            $tmp = str_replace([',', ' '], ['.', ''], $amount);
            if (is_numeric($tmp))
                $n = (float) $tmp;
        }

        if (function_exists('wc_price')) {
            $args = [];
            if ($currency)
                $args['currency'] = $currency;

            $html = wc_price($n, $args);
            $plain = html_entity_decode(wp_strip_all_tags((string) $html), ENT_QUOTES, 'UTF-8');
            // normaliza NBSP
            $plain = str_replace("\xc2\xa0", ' ', $plain);
            $plain = preg_replace('/\\s+/', ' ', trim($plain));
            return (string) $plain;
        }

        // fallback simples
        $cur = strtoupper((string) $currency);
        if ($cur === 'BRL') {
            return 'R$ ' . number_format($n, 2, ',', '.');
        }
        if ($cur) {
            return $cur . ' ' . number_format($n, 2, '.', ',');
        }
        return number_format($n, 2, ',', '.');
    }
}


// ======================
// PERFIL (editar dados / avatar / senha)
// ======================

function app_api_get_custom_avatar_url(int $user_id, int $size = 256): ?string
{
    $avatar_updated_at = get_user_meta($user_id, 'app_api_avatar_updated_at', true);
    $avatar_updated_at = is_numeric($avatar_updated_at) ? (int) $avatar_updated_at : 0;

    $avatar_id = get_user_meta($user_id, 'app_api_avatar_id', true);
    $avatar_id = is_numeric($avatar_id) ? (int) $avatar_id : 0;
    if ($avatar_id <= 0) {
        return null;
    }

    $url = null;

    if (function_exists('wp_get_attachment_image_src')) {
        $src = wp_get_attachment_image_src($avatar_id, [max(32, $size), max(32, $size)]);
        if (is_array($src) && !empty($src[0])) {
            $url = (string) $src[0];
        }
    }

    if (!$url) {
        $raw_url = wp_get_attachment_url($avatar_id);
        if ($raw_url) {
            $url = (string) $raw_url;
        }
    }

    if (!$url) {
        return null;
    }

    $ver = $avatar_updated_at > 0 ? $avatar_updated_at : 0;
    if ($ver <= 0) {
        $file = get_attached_file($avatar_id);
        if ($file && file_exists($file)) {
            $ver = (int) @filemtime($file);
        }
    }
    if ($ver > 0) {
        $url = add_query_arg('v', $ver, $url);
    }

    return $url;
}

function app_api_get_user_avatar_url(int $user_id, int $size = 256): ?string
{
    $custom_url = app_api_get_custom_avatar_url($user_id, $size);
    if ($custom_url) {
        return $custom_url;
    }

    // 2) Fallback: avatar padrão do WordPress (Gravatar)
    if (function_exists('get_avatar_url')) {
        $url = get_avatar_url($user_id, ['size' => max(32, $size)]);
        if ($url) return (string) $url;
    }
    return null;
}

function app_api_resolve_user_id_from_avatar_subject($id_or_email): int
{
    if (is_numeric($id_or_email)) {
        return max(0, (int) $id_or_email);
    }

    if ($id_or_email instanceof WP_User) {
        return (int) $id_or_email->ID;
    }

    if ($id_or_email instanceof WP_Comment) {
        return (int) $id_or_email->user_id;
    }

    if (is_object($id_or_email) && !($id_or_email instanceof WP_Post)) {
        if (!empty($id_or_email->user_id)) {
            return (int) $id_or_email->user_id;
        }
        if (!empty($id_or_email->comment_author_email)) {
            $u = get_user_by('email', (string) $id_or_email->comment_author_email);
            return $u ? (int) $u->ID : 0;
        }
    }

    if (is_array($id_or_email)) {
        if (!empty($id_or_email['user_id'])) {
            return (int) $id_or_email['user_id'];
        }
        if (!empty($id_or_email['comment_author_email'])) {
            $u = get_user_by('email', (string) $id_or_email['comment_author_email']);
            return $u ? (int) $u->ID : 0;
        }
    }

    if (is_string($id_or_email) && is_email($id_or_email)) {
        $u = get_user_by('email', $id_or_email);
        return $u ? (int) $u->ID : 0;
    }

    return 0;
}

function app_api_filter_get_avatar_data(array $args, $id_or_email): array
{
    if (!empty($args['force_default'])) {
        return $args;
    }

    $user_id = app_api_resolve_user_id_from_avatar_subject($id_or_email);
    if ($user_id <= 0) {
        return $args;
    }

    $size = !empty($args['size']) ? (int) $args['size'] : 96;
    $custom_url = app_api_get_custom_avatar_url($user_id, max(32, $size));
    if (!$custom_url) {
        return $args;
    }

    $args['url'] = $custom_url;
    $args['found_avatar'] = true;

    if (empty($args['width'])) {
        $args['width'] = $size;
    }
    if (empty($args['height'])) {
        $args['height'] = $size;
    }

    $retina_url = app_api_get_custom_avatar_url($user_id, max(64, $size * 2));
    if ($retina_url) {
        $args['url_2x'] = $retina_url;
    }

    return $args;
}

function app_api_filter_get_avatar_html($avatar, $id_or_email, $size, $default_value, $alt, $args)
{
    $user_id = app_api_resolve_user_id_from_avatar_subject($id_or_email);
    if ($user_id <= 0) {
        return $avatar;
    }

    $custom_url = app_api_get_custom_avatar_url($user_id, max(32, (int) $size));
    if (!$custom_url) {
        return $avatar;
    }

    if (!is_string($avatar) || $avatar == '') {
        return $avatar;
    }

    $avatar = preg_replace('/\s+srcset=("[^"]*"|\'[^\']*\')/i', '', $avatar);
    $avatar = preg_replace('/\s+sizes=("[^"]*"|\'[^\']*\')/i', '', $avatar);

    if (preg_match('/\ssrc=("[^"]*"|\'[^\']*\')/i', $avatar)) {
        $quoted = '"' . esc_url($custom_url) . '"';
        $avatar = preg_replace('/\ssrc=("[^"]*"|\'[^\']*\')/i', ' src=' . $quoted, $avatar, 1);
    }

    return $avatar;
}

function app_api_delete_previous_avatar_attachment(?int $attachment_id, int $user_id): void
{
    $attachment_id = (int) $attachment_id;
    if ($attachment_id <= 0) {
        return;
    }

    $owner_id = (int) get_post_meta($attachment_id, '_app_api_avatar_owner', true);
    if ($owner_id > 0 && $owner_id !== $user_id) {
        return;
    }

    $post = get_post($attachment_id);
    if (!$post || $post->post_type !== 'attachment') {
        return;
    }

    $is_app_avatar = ($owner_id > 0)
        || ((int) $post->post_author === $user_id)
        || (stripos((string) $post->post_title, 'Avatar - user ' . $user_id) === 0);

    if (!$is_app_avatar) {
        return;
    }

    if (!function_exists('wp_delete_attachment')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    wp_delete_attachment($attachment_id, true);
}

function app_api_sync_custom_avatar_attachment(int $user_id, int $attachment_id): void
{
    if ($attachment_id <= 0) {
        return;
    }

    update_post_meta($attachment_id, '_app_api_avatar_owner', $user_id);
    update_post_meta($attachment_id, '_wp_attachment_wp_user_avatar', $user_id);
    update_user_meta($user_id, 'app_api_avatar_id', $attachment_id);
    update_user_meta($user_id, 'app_api_avatar_updated_at', time());
}

function app_api_get_password_reset_expiration_seconds(): int
{
    $default = defined('HOUR_IN_SECONDS') ? 2 * HOUR_IN_SECONDS : 7200;
    $value = (int) get_option('app_api_password_reset_expiration', $default);
    if ($value <= 0) {
        $value = $default;
    }

    $min = defined('MINUTE_IN_SECONDS') ? 15 * MINUTE_IN_SECONDS : 900;
    if ($value < $min) {
        $value = $min;
    }

    return (int) apply_filters('app_api_password_reset_expiration', $value);
}

function app_api_filter_password_reset_expiration($seconds): int
{
    return app_api_get_password_reset_expiration_seconds();
}

function app_api_humanize_duration_pt_br(int $seconds): string
{
    $seconds = max(60, $seconds);

    if ($seconds % DAY_IN_SECONDS === 0) {
        $days = (int) ($seconds / DAY_IN_SECONDS);
        return $days === 1 ? '1 dia' : sprintf('%d dias', $days);
    }

    if ($seconds % HOUR_IN_SECONDS === 0) {
        $hours = (int) ($seconds / HOUR_IN_SECONDS);
        return $hours === 1 ? '1 hora' : sprintf('%d horas', $hours);
    }

    if ($seconds % MINUTE_IN_SECONDS === 0) {
        $minutes = (int) ($seconds / MINUTE_IN_SECONDS);
        return $minutes === 1 ? '1 minuto' : sprintf('%d minutos', $minutes);
    }

    return sprintf('%d segundos', $seconds);
}

function app_api_sync_user_profile_to_wc_orders(int $user_id, array $changes = [], ?string $previous_email = null): int
{
    if (!function_exists('wc_get_orders')) {
        return 0;
    }

    $normalize = function ($key, $fallback = null) use ($user_id, $changes) {
        if (array_key_exists($key, $changes)) {
            $value = $changes[$key];
            if ($value === null) {
                return null;
            }
            return is_string($value) ? trim($value) : $value;
        }

        if ($fallback !== null) {
            return $fallback;
        }

        $value = get_user_meta($user_id, $key, true);
        if ($value === '' || $value === null) {
            return null;
        }
        return is_string($value) ? trim($value) : $value;
    };

    $user = get_user_by('id', $user_id);
    $current_email = $normalize('billing_email', $normalize('user_email', $user ? (string) $user->user_email : null));
    $first = $normalize('first_name', get_user_meta($user_id, 'first_name', true));
    $last = $normalize('last_name', get_user_meta($user_id, 'last_name', true));
    $phone = $normalize('billing_phone', get_user_meta($user_id, 'billing_phone', true));

    $order_ids = [];
    $queries = [
        ['customer_id' => $user_id],
    ];

    foreach ($queries as $args) {
        $ids = wc_get_orders(array_merge([
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
        ], $args));

        foreach ((array) $ids as $oid) {
            $oid = (int) $oid;
            if ($oid > 0) {
                $order_ids[$oid] = $oid;
            }
        }
    }

    $updated = 0;

    foreach (array_values($order_ids) as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            continue;
        }

        $changed = false;

        if ($first !== null && method_exists($order, 'get_billing_first_name') && method_exists($order, 'set_billing_first_name')) {
            if ((string) $order->get_billing_first_name() !== (string) $first) {
                $order->set_billing_first_name((string) $first);
                $changed = true;
            }
            if (method_exists($order, 'get_shipping_first_name') && method_exists($order, 'set_shipping_first_name') && (string) $order->get_shipping_first_name() !== (string) $first) {
                $order->set_shipping_first_name((string) $first);
                $changed = true;
            }
        }

        if ($last !== null && method_exists($order, 'get_billing_last_name') && method_exists($order, 'set_billing_last_name')) {
            if ((string) $order->get_billing_last_name() !== (string) $last) {
                $order->set_billing_last_name((string) $last);
                $changed = true;
            }
            if (method_exists($order, 'get_shipping_last_name') && method_exists($order, 'set_shipping_last_name') && (string) $order->get_shipping_last_name() !== (string) $last) {
                $order->set_shipping_last_name((string) $last);
                $changed = true;
            }
        }

        if ($current_email !== null && method_exists($order, 'get_billing_email') && method_exists($order, 'set_billing_email')) {
            if ((string) $order->get_billing_email() !== (string) $current_email) {
                $order->set_billing_email((string) $current_email);
                $changed = true;
            }
        }

        if ($phone !== null && method_exists($order, 'get_billing_phone') && method_exists($order, 'set_billing_phone')) {
            if ((string) $order->get_billing_phone() !== (string) $phone) {
                $order->set_billing_phone((string) $phone);
                $changed = true;
            }
        }

        if (!$changed) {
            continue;
        }

        try {
            $order->save();
            $updated++;
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('App API: falha ao sincronizar pedido ' . $order_id . ' do usuário ' . $user_id . ': ' . $e->getMessage());
            }
        }
    }

    do_action('app_api_user_profile_synced_to_orders', $user_id, $updated, [
        'first_name' => $first,
        'last_name' => $last,
        'billing_email' => $current_email,
        'billing_phone' => $phone,
        'previous_email' => $previous_email,
    ]);

    return $updated;
}

function app_api_sync_user_profile_after_wp_update(int $user_id, WP_User $old_user_data)
{
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return;
    }

    app_api_sync_user_profile_to_wc_orders($user_id, [
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'billing_email' => get_user_meta($user_id, 'billing_email', true) ?: (string) $user->user_email,
        'billing_phone' => get_user_meta($user_id, 'billing_phone', true),
    ], $old_user_data->user_email ?? null);

    if (function_exists('app_api_invalidate_user_caches')) {
        app_api_invalidate_user_caches($user_id);
    }
}


if (!function_exists('app_api_get_me_cache_ttl')) {
    function app_api_get_me_cache_ttl(int $user_id): int
    {
        return (int) apply_filters('app_api_me_cache_ttl', 30, $user_id);
    }
}


if (!function_exists('app_api_get_user_scalar_meta_map')) {
    function app_api_get_user_scalar_meta_map(int $user_id): array
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return [];
        }

        $all_meta = get_user_meta($user_id);
        if (!is_array($all_meta) || empty($all_meta)) {
            return [];
        }

        $normalized = [];
        foreach ($all_meta as $key => $values) {
            if (!is_array($values) || !array_key_exists(0, $values)) {
                continue;
            }

            $value = maybe_unserialize($values[0]);
            if ($value === '' || $value === null) {
                $normalized[$key] = null;
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $normalized[$key] = null;
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}

if (!function_exists('app_api_build_me_payload')) {
    function app_api_build_me_payload(WP_User $user): array
    {
        $uid = (int) $user->ID;
        $meta_map = function_exists('app_api_get_user_scalar_meta_map')
            ? app_api_get_user_scalar_meta_map($uid)
            : [];

        $meta = function ($key) use ($meta_map, $uid) {
            if (array_key_exists($key, $meta_map)) {
                return $meta_map[$key];
            }

            $v = get_user_meta($uid, $key, true);
            if ($v === '' || $v === null) {
                return null;
            }
            if (is_array($v) || is_object($v)) {
                return null;
            }
            return $v;
        };

        $wc = null;
        $wc_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_state',
            'shipping_postcode',
            'shipping_country',
        ];
        $needs_wc_customer = false;
        foreach ($wc_fields as $wc_field_key) {
            if (!array_key_exists($wc_field_key, $meta_map) || $meta_map[$wc_field_key] === null) {
                $needs_wc_customer = true;
                break;
            }
        }

        if ($needs_wc_customer && class_exists('WC_Customer')) {
            try {
                $wc = new WC_Customer($uid);
            } catch (Exception $e) {
                $wc = null;
            }
        }

        $cpf = $meta('billing_cpf');
        if ($cpf === null) {
            $cpf = $meta('_billing_cpf');
        }
        if ($cpf === null) {
            $cpf = $meta('cpf');
        }

        $cnpj = $meta('billing_cnpj');
        if ($cnpj === null) {
            $cnpj = $meta('_billing_cnpj');
        }
        if ($cnpj === null) {
            $cnpj = $meta('cnpj');
        }

        $billing = [
            'first_name' => $meta('billing_first_name') ?? ($wc ? $wc->get_billing_first_name() : null),
            'last_name' => $meta('billing_last_name') ?? ($wc ? $wc->get_billing_last_name() : null),
            'company' => $meta('billing_company') ?? ($wc ? $wc->get_billing_company() : null),
            'address_1' => $meta('billing_address_1') ?? ($wc ? $wc->get_billing_address_1() : null),
            'address_2' => $meta('billing_address_2') ?? ($wc ? $wc->get_billing_address_2() : null),
            'number' => $meta('billing_number'),
            'neighborhood' => $meta('billing_neighborhood'),
            'city' => $meta('billing_city') ?? ($wc ? $wc->get_billing_city() : null),
            'state' => $meta('billing_state') ?? ($wc ? $wc->get_billing_state() : null),
            'postcode' => $meta('billing_postcode') ?? ($wc ? $wc->get_billing_postcode() : null),
            'country' => $meta('billing_country') ?? ($wc ? $wc->get_billing_country() : null),
            'email' => $meta('billing_email') ?? ($wc ? $wc->get_billing_email() : null),
            'phone' => $meta('billing_phone') ?? ($wc ? $wc->get_billing_phone() : null),
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'rg' => $meta('billing_rg') ?? $meta('_billing_rg'),
            'ie' => $meta('billing_ie') ?? $meta('_billing_ie'),
            'person_type' => $meta('billing_persontype') ?? $meta('_billing_persontype'),
        ];

        $shipping = [
            'first_name' => $meta('shipping_first_name') ?? ($wc ? $wc->get_shipping_first_name() : null),
            'last_name' => $meta('shipping_last_name') ?? ($wc ? $wc->get_shipping_last_name() : null),
            'company' => $meta('shipping_company') ?? ($wc ? $wc->get_shipping_company() : null),
            'address_1' => $meta('shipping_address_1') ?? ($wc ? $wc->get_shipping_address_1() : null),
            'address_2' => $meta('shipping_address_2') ?? ($wc ? $wc->get_shipping_address_2() : null),
            'number' => $meta('shipping_number'),
            'neighborhood' => $meta('shipping_neighborhood'),
            'city' => $meta('shipping_city') ?? ($wc ? $wc->get_shipping_city() : null),
            'state' => $meta('shipping_state') ?? ($wc ? $wc->get_shipping_state() : null),
            'postcode' => $meta('shipping_postcode') ?? ($wc ? $wc->get_shipping_postcode() : null),
            'country' => $meta('shipping_country') ?? ($wc ? $wc->get_shipping_country() : null),
        ];

        $wp_first_name = $meta('first_name');
        $wp_last_name = $meta('last_name');

        $avatar_url = null;
        if (function_exists('app_api_get_user_avatar_url')) {
            $avatar_url = app_api_get_user_avatar_url($uid, 256);
        }
        if (!$avatar_url && function_exists('get_avatar_url')) {
            $avatar_url = get_avatar_url($uid, ['size' => 256]);
            if (!$avatar_url) {
                $avatar_url = null;
            }
        }

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'avatar_url' => $avatar_url,
            'roles' => array_values((array) $user->roles),
            'is_admin' => user_can($user, 'manage_options'),
            'username' => $user->user_login,
            'first_name' => $wp_first_name,
            'last_name' => $wp_last_name,
            'cpf' => $billing['cpf'],
            'phone' => $billing['phone'],
            'billing' => $billing,
            'shipping' => $shipping,
        ];
    }
}

if (!function_exists('app_api_prime_me_cache')) {
    function app_api_prime_me_cache(WP_User $user): array
    {
        $payload = app_api_build_me_payload($user);
        $ttl = app_api_get_me_cache_ttl((int) $user->ID);
        if ($ttl > 0) {
            set_transient('app_api_cache_me_' . (int) $user->ID, $payload, $ttl);
        }
        return $payload;
    }
}

function app_api_invalidate_user_caches(int $user_id)
{
    // cache curto do /me
    delete_transient('app_api_cache_me_' . $user_id);

    // caches de pedidos/assinaturas/revistas (versões existentes)
    delete_transient('app_api_cache_orders_v2_' . $user_id);
    delete_transient('app_api_cache_subs_v2_' . $user_id);
    delete_transient('app_api_cache_mags_v2_' . $user_id);
}

function app_api_revoke_all_refresh_tokens_for_user(int $user_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'app_refresh_tokens';
    // revoga apenas os ativos
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table SET revoked_at = %s WHERE user_id = %d AND revoked_at IS NULL",
            app_api_now_mysql(),
            $user_id
        )
    );
}

function app_api_rate_limit_hit(string $key, int $limit, int $window_seconds): bool
{
    // retorna true se excedeu
    $k = 'app_api_rl_' . md5($key);
    $row = get_transient($k);
    $count = 0;
    $exp = time() + $window_seconds;

    if (is_array($row)) {
        $count = (int) ($row['count'] ?? 0);
        $exp = (int) ($row['exp'] ?? $exp);
    }

    $count++;
    set_transient($k, ['count' => $count, 'exp' => $exp], max(1, $exp - time()));

    return $count > $limit;
}

function app_api_get_pwa_base_url(): string
{
    // Você pode configurar manualmente via option: app_api_pwa_base_url
    $opt = trim((string) get_option('app_api_pwa_base_url', ''));
    if ($opt) return rtrim($opt, '/');

    // Tenta usar o primeiro origin de produção do CORS (sem localhost)
    if (function_exists('app_api_get_allowed_origins')) {
        $origins = (array) app_api_get_allowed_origins();
        foreach ($origins as $o) {
            $o = trim((string) $o);
            if (!$o) continue;
            if (stripos($o, 'localhost') !== false) continue;
            if (stripos($o, '127.0.0.1') !== false) continue;
            return rtrim($o, '/');
        }
        if (!empty($origins[0])) return rtrim((string) $origins[0], '/');
    }

    return rtrim(home_url(), '/');
}

function app_api_send_password_reset_email(WP_User $user, string $key): bool
{
    $pwa = app_api_get_pwa_base_url();
    $login = rawurlencode((string) $user->user_login);
    $k = rawurlencode($key);

    // Rota do APP (PWA)
    $reset_url = $pwa . '/resetar-senha?login=' . $login . '&key=' . $k;

    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $to = $user->user_email;

    $subject = sprintf('[%s] Redefinição de senha', $site_name);
    $expires_in = function_exists('app_api_get_password_reset_expiration_seconds') ? app_api_get_password_reset_expiration_seconds() : (defined('HOUR_IN_SECONDS') ? 2 * HOUR_IN_SECONDS : 7200);
    $expires_human = function_exists('app_api_humanize_duration_pt_br') ? app_api_humanize_duration_pt_br((int) $expires_in) : 'algumas horas';

    $message = "Olá!\n\nRecebemos uma solicitação para redefinir a senha da sua conta no {$site_name}.\n\nPara criar uma nova senha, acesse o link abaixo:\n{$reset_url}\n\nEste link expira em {$expires_human}.\n\nSe você não solicitou a redefinição, pode ignorar este e-mail com segurança.\n\nObrigado.";

    // Cabeçalhos básicos
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    return (bool) wp_mail($to, $subject, $message, $headers);
}
