<?php
if (!defined('ABSPATH'))
    exit;

function app_api_register_routes()
{

    // Autenticação
    register_rest_route(APP_API_NS, '/auth/login', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_login',
    ]);

    register_rest_route(APP_API_NS, '/auth/refresh', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_refresh',
    ]);

    register_rest_route(APP_API_NS, '/auth/logout', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_logout',
    ]);

    // Recuperação de senha
    register_rest_route(APP_API_NS, '/auth/forgot-password', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_forgot_password',
    ]);

    register_rest_route(APP_API_NS, '/auth/reset-password/validate', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_reset_password_validate',
    ]);

    register_rest_route(APP_API_NS, '/auth/reset-password', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_auth_reset_password',
    ]);

    // Perfil do usuário autenticado
    register_rest_route(APP_API_NS, '/me', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me',
    ]);

    // Atualização de perfil
    register_rest_route(APP_API_NS, '/me/profile', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_update_profile',
    ]);

    register_rest_route(APP_API_NS, '/me/password', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_change_password',
    ]);

    register_rest_route(APP_API_NS, '/me/avatar', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_update_avatar',
    ]);

    register_rest_route(APP_API_NS, '/me/orders', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_orders',
    ]);

    register_rest_route(APP_API_NS, '/me/subscriptions', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_subscriptions',
    ]);

    register_rest_route(APP_API_NS, '/me/magazines', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_magazines',
    ]);

    register_rest_route(APP_API_NS, '/me/magazines/categories', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_me_magazines_categories',
    ]);

    // Acesso à revista com viewer token
    register_rest_route(APP_API_NS, '/magazines/(?P<product_id>\d+)/access', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_magazine_access',
        'args' => [
            'product_id' => ['validate_callback' => 'is_numeric']
        ]
    ]);

    // Entrega o PDF associado ao flipbook com suporte a Range
    register_rest_route(APP_API_NS, '/magazines/(?P<product_id>\d+)/pdf', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_magazine_pdf',
        'args' => [
            'product_id' => ['validate_callback' => 'is_numeric'],
            'flipbook_id' => ['validate_callback' => 'is_numeric', 'required' => false],
        ]
    ]);

    // Recursos para leitura offline
    register_rest_route(APP_API_NS, '/offline/public-key', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_offline_public_key',
    ]);

    register_rest_route(APP_API_NS, '/magazines/(?P<product_id>\d+)/offline-license', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_magazine_offline_license',
        'args' => [
            'product_id' => ['validate_callback' => 'is_numeric'],
        ]
    ]);

    // Renovação em lote para sincronização offline
    register_rest_route(APP_API_NS, '/offline/licenses/renew', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_offline_renew_licenses',
    ]);


    // Viewer HTML para WebView
    register_rest_route(APP_API_NS, '/viewer/flipbook/(?P<product_id>\d+)', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_viewer_flipbook',
    ]);


    // Catálogo público e checkout do PWA
    register_rest_route(APP_API_NS, '/store/products', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_products',
    ]);

    register_rest_route(APP_API_NS, '/store/products/(?P<product_id>\d+)', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_product',
        'args' => [
            'product_id' => ['validate_callback' => 'is_numeric'],
        ],
    ]);

    register_rest_route(APP_API_NS, '/store/checkout/context', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_checkout_context',
    ]);

    register_rest_route(APP_API_NS, '/store/checkout/submit', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_checkout_submit',
    ]);

    register_rest_route(APP_API_NS, '/store/checkout/customer-lookup', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_checkout_customer_lookup',
    ]);

    register_rest_route(APP_API_NS, '/store/checkout/postcode-lookup', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_checkout_postcode_lookup',
    ]);

    register_rest_route(APP_API_NS, '/store/checkout/installments', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_store_checkout_installments',
    ]);

    // Recomendações por página e tracking
    register_rest_route(APP_API_NS, '/ai/page-recos', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_ai_page_recos',
    ]);

    register_rest_route(APP_API_NS, '/ai/track', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'app_api_ai_track',
    ]);

    register_rest_route(APP_API_NS, '/admin/subscribers', [
        'methods' => 'GET',
        'callback' => 'app_api_admin_subscribers',
        'permission_callback' => 'app_api_permission_admin',
    ]);

    register_rest_route(APP_API_NS, '/admin/users/(?P<user_id>\d+)/magazines', [
        'methods' => 'GET',
        'callback' => 'app_api_admin_user_magazines',
        'permission_callback' => 'app_api_permission_admin',
    ]);

    register_rest_route(APP_API_NS, '/admin/users/(?P<user_id>\d+)/orders', [
        'methods' => 'GET',
        'callback' => 'app_api_admin_user_orders',
        'permission_callback' => 'app_api_permission_admin',
    ]);

    register_rest_route(APP_API_NS, '/admin/orders/(?P<order_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'app_api_admin_order_details',
        'permission_callback' => 'app_api_permission_admin',
    ]);
}

/**
 *  AUTH IMPLEMENTATION
 *  ======================= */

function app_api_issue_access_token(int $user_id, int $minutes = 15): string
{
    $now = time();
    $payload = [
        'typ' => 'access',
        'sub' => $user_id,
        'iat' => $now,
        'nbf' => $now - 5,
        'exp' => $now + ($minutes * 60),
        'jti' => app_api_random_token(16),
    ];
    return app_api_jwt_sign($payload);
}

function app_api_issue_viewer_token(int $user_id, int $product_id, int $flipbook_id = 0, int $seconds = 7200): string
{
    $now = time();
    $payload = [
        'typ' => 'viewer',
        'sub' => $user_id,
        'pid' => $product_id,
        'fbid' => max(0, (int) $flipbook_id),
        'scp' => 'flipbook_view',
        'iat' => $now,
        'nbf' => $now - 5,
        'exp' => $now + $seconds,
        'jti' => app_api_random_token(16),
    ];
    return app_api_jwt_sign($payload);
}

function app_api_store_refresh_token(int $user_id, string $refresh_token, ?string $device_id, ?string $user_agent, ?string $ip, int $days = 30)
{
    global $wpdb;
    $table = $wpdb->prefix . 'app_refresh_tokens';

    $wpdb->insert($table, [
        'user_id' => $user_id,
        'token_hash' => app_api_hash_token($refresh_token),
        'device_id' => $device_id,
        'user_agent' => $user_agent,
        'ip_address' => $ip,
        'created_at' => app_api_now_mysql(),
        'expires_at' => app_api_mysql_plus_days($days),
    ]);
}

function app_api_revoke_refresh_token(string $refresh_token, ?string $replaced_by_hash = null)
{
    global $wpdb;
    $table = $wpdb->prefix . 'app_refresh_tokens';
    $hash = app_api_hash_token($refresh_token);

    $data = ['revoked_at' => app_api_now_mysql()];
    if ($replaced_by_hash)
        $data['replaced_by_hash'] = $replaced_by_hash;

    $wpdb->update($table, $data, ['token_hash' => $hash, 'revoked_at' => null]);

    if (function_exists('app_api_delete_refresh_session_activity')) {
        app_api_delete_refresh_session_activity($refresh_token);
    }
}

function app_api_find_valid_refresh_row(string $refresh_token)
{
    global $wpdb;
    $table = $wpdb->prefix . 'app_refresh_tokens';
    $hash = app_api_hash_token($refresh_token);

    return $wpdb->get_row($wpdb->prepare("
    SELECT * FROM $table
    WHERE token_hash = %s
      AND revoked_at IS NULL
      AND expires_at > %s
    LIMIT 1
  ", $hash, app_api_now_mysql()));
}

function app_api_get_request_refresh_token(WP_REST_Request $req): string
{
    $body = $req->get_json_params();
    $refresh = trim((string) ($body['refresh_token'] ?? ''));
    if ($refresh !== '') {
        return $refresh;
    }

    return (string) (app_api_get_cookie_token('refresh') ?? '');
}


function app_api_get_request_user_agent(): string
{
    return app_api_normalize_client_user_agent((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function app_api_get_request_ip_address(): string
{
    return app_api_normalize_client_ip((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function app_api_refresh_row_matches_client($row): bool
{
    if (!$row) {
        return false;
    }

    if (app_api_auth_validate_refresh_user_agent()) {
        $stored_ua = app_api_normalize_client_user_agent((string) ($row->user_agent ?? ''));
        $current_ua = app_api_get_request_user_agent();
        if ($stored_ua !== '' && $current_ua !== '' && !hash_equals($stored_ua, $current_ua)) {
            return false;
        }
    }

    if (app_api_auth_validate_refresh_ip()) {
        $stored_ip = app_api_normalize_client_ip((string) ($row->ip_address ?? ''));
        $current_ip = app_api_get_request_ip_address();
        if (!app_api_client_ip_matches($stored_ip, $current_ip)) {
            return false;
        }
    }

    return true;
}

function app_api_auth_login(WP_REST_Request $req)
{
    $body = $req->get_json_params();
    if (!is_array($body)) {
        $body = [];
    }

    $email = sanitize_email($body['email'] ?? '');
    $password = (string) ($body['password'] ?? '');
    $device_id = function_exists('app_api_validate_device_id')
        ? app_api_validate_device_id($body['device_id'] ?? '', false)
        : sanitize_text_field($body['device_id'] ?? '');
    if (is_wp_error($device_id)) {
        return $device_id;
    }

    if (!$email || !$password) {
        return new WP_Error('bad_request', 'Email e senha são obrigatórios', ['status' => 400]);
    }

    $ip = function_exists('app_api_get_request_ip_address') ? app_api_get_request_ip_address() : ((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    $rl_key = 'login:' . strtolower($email) . ':' . $ip;
    $rl_ip_key = 'login:ip:' . $ip;
    if (
        function_exists('app_api_rate_limit_hit')
        && (
            app_api_rate_limit_hit($rl_key, 10, 900)
            || app_api_rate_limit_hit($rl_ip_key, 25, 900)
        )
    ) {
        return new WP_Error('too_many_requests', 'Muitas tentativas de login. Tente novamente em alguns minutos.', ['status' => 429]);
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('invalid_credentials', 'Credenciais inválidas', ['status' => 401]);
    }

    $authed = wp_authenticate($user->user_login, $password);
    if (is_wp_error($authed)) {
        return new WP_Error('invalid_credentials', 'Credenciais inválidas', ['status' => 401]);
    }

    // Exige pedido concluído para login, exceto para administradores
    if (!app_api_user_can_use_app((int) $user->ID)) {
        return new WP_Error(
            'forbidden',
            'Acesso ao app permitido apenas para assinantes.',
            ['status' => 403]
        );
    }

    $access = app_api_issue_access_token($user->ID, 15);
    $refresh = app_api_random_token(48);

    app_api_store_refresh_token(
        $user->ID,
        $refresh,
        is_string($device_id) && $device_id !== '' ? $device_id : null,
        app_api_get_request_user_agent(),
        app_api_get_request_ip_address(),
        (int) ceil(app_api_auth_refresh_ttl_seconds() / DAY_IN_SECONDS)
    );

    app_api_set_auth_cookies($access, app_api_auth_access_ttl_seconds(), $refresh, app_api_auth_refresh_ttl_seconds());

    if (function_exists('app_api_touch_refresh_session_activity')) {
        app_api_touch_refresh_session_activity($refresh);
    }

    $me_payload = function_exists('app_api_prime_me_cache') ? app_api_prime_me_cache($user) : null;

    return [
        'access_token' => $access,
        'expires_in' => app_api_auth_access_ttl_seconds(),
        'cookie_auth' => true,
        'user' => is_array($me_payload) ? $me_payload : null,
    ];
}

function app_api_auth_refresh(WP_REST_Request $req)
{
    $refresh = app_api_get_request_refresh_token($req);
    if (!$refresh) {
        app_api_clear_auth_cookies();
        return new WP_Error('bad_request', 'refresh_token é obrigatório', ['status' => 400]);
    }

    $row = app_api_find_valid_refresh_row($refresh);
    if (!$row) {
        app_api_clear_auth_cookies();
        return new WP_Error('unauthorized', 'Refresh token inválido', ['status' => 401]);
    }

    if (!app_api_refresh_row_matches_client($row)) {
        app_api_revoke_refresh_token($refresh);
        app_api_clear_auth_cookies();
        return new WP_Error('unauthorized', 'Sessão inválida para este dispositivo.', ['status' => 401]);
    }

    if (function_exists('app_api_refresh_session_is_idle_expired') && app_api_refresh_session_is_idle_expired($refresh, $row)) {
        app_api_revoke_refresh_token($refresh);
        app_api_clear_auth_cookies();
        return new WP_Error('unauthorized', 'Sessão expirada por inatividade. Faça login novamente.', ['status' => 401]);
    }

    $user_id = intval($row->user_id);
    $user = get_user_by('id', $user_id);
    if (!$user) {
        app_api_clear_auth_cookies();
        return new WP_Error('unauthorized', 'Usuário inválido', ['status' => 401]);
    }

    // Bloqueia refresh quando o usuário perde elegibilidade
    if (!app_api_user_can_use_app((int) $user_id)) {
        app_api_clear_auth_cookies();
        return new WP_Error(
            'forbidden',
            'Acesso ao app permitido apenas para assinantes.',
            ['status' => 403]
        );
    }

    // Rotaciona o refresh token
    $new_refresh = app_api_random_token(48);
    $new_hash = app_api_hash_token($new_refresh);

    app_api_revoke_refresh_token($refresh, $new_hash);
    app_api_store_refresh_token(
        $user_id,
        $new_refresh,
        $row->device_id,
        app_api_get_request_user_agent(),
        app_api_get_request_ip_address(),
        (int) ceil(app_api_auth_refresh_ttl_seconds() / DAY_IN_SECONDS)
    );

    if (function_exists('app_api_migrate_refresh_session_activity')) {
        app_api_migrate_refresh_session_activity($refresh, $new_refresh);
    }

    $access = app_api_issue_access_token($user_id, 15);
    app_api_set_auth_cookies($access, app_api_auth_access_ttl_seconds(), $new_refresh, app_api_auth_refresh_ttl_seconds());

    $me_payload = function_exists('app_api_prime_me_cache') ? app_api_prime_me_cache($user) : null;

    return [
        'access_token' => $access,
        'expires_in' => app_api_auth_access_ttl_seconds(),
        'cookie_auth' => true,
        'user' => is_array($me_payload) ? $me_payload : null,
    ];
}

function app_api_auth_logout(WP_REST_Request $req)
{
    $body = $req->get_json_params();
    $refresh = app_api_get_request_refresh_token($req);
    $logout_all = !empty($body['all_sessions']);
    $row = $refresh ? app_api_find_valid_refresh_row($refresh) : null;

    if ($logout_all) {
        $user = app_api_require_access_user($req);
        $user_id = !is_wp_error($user) ? (int) $user->ID : (int) ($row->user_id ?? 0);
        if ($user_id > 0 && function_exists('app_api_revoke_all_refresh_tokens_for_user')) {
            app_api_revoke_all_refresh_tokens_for_user($user_id);
        }
    } elseif ($refresh) {
        app_api_revoke_refresh_token($refresh);
    }

    app_api_clear_auth_cookies();
    return ['ok' => true];
}

/**
 *  ME ENDPOINTS
 *  ======================= */



// Recuperação de senha

function app_api_auth_forgot_password(WP_REST_Request $req)
{
    // Sempre retorna OK para não revelar a existência do e-mail
    $email = (string) $req->get_param('email');
    $email = trim($email);

    // Rate limit simples por IP e e-mail
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $rl_key = 'forgot:' . strtolower($email) . ':' . $ip;
    if ($email && function_exists('app_api_rate_limit_hit')) {
        if (app_api_rate_limit_hit($rl_key, 5, 3600)) {
            // Mantém a resposta neutra e não envia o e-mail.
            return ['ok' => true];
        }
    }

    if (!$email || !is_email($email)) {
        return ['ok' => true];
    }

    $user = get_user_by('email', $email);
    if (!$user || !($user instanceof WP_User)) {
        return ['ok' => true];
    }

    if (!function_exists('get_password_reset_key')) {
        return ['ok' => true];
    }

    $key = get_password_reset_key($user);
    if (is_wp_error($key) || !$key) {
        return ['ok' => true];
    }

    if (function_exists('app_api_send_password_reset_email')) {
        app_api_send_password_reset_email($user, (string) $key);
    } else {
        // Fallback para o fluxo padrão do WordPress
        if (function_exists('retrieve_password')) {
            retrieve_password($user->user_login);
        }
    }

    return ['ok' => true];
}

function app_api_auth_reset_password_validate(WP_REST_Request $req)
{
    $login = (string) $req->get_param('login');
    $key = (string) $req->get_param('key');
    $login = trim($login);
    $key = trim($key);

    if (!$login || !$key) {
        return new WP_Error('invalid', 'Link inválido.', ['status' => 400]);
    }

    if (!function_exists('check_password_reset_key')) {
        return new WP_Error('invalid', 'Recuperação indisponível.', ['status' => 400]);
    }

    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        return new WP_Error('invalid', 'Link inválido ou expirado.', ['status' => 400]);
    }

    return ['ok' => true];
}

function app_api_auth_reset_password(WP_REST_Request $req)
{
    $login = (string) $req->get_param('login');
    $key = (string) $req->get_param('key');
    $new_password = (string) $req->get_param('new_password');

    $login = trim($login);
    $key = trim($key);

    if (!$login || !$key || strlen($new_password) < 8) {
        return new WP_Error('invalid', 'Dados inválidos. A senha deve ter pelo menos 8 caracteres.', ['status' => 400]);
    }

    if (!function_exists('check_password_reset_key')) {
        return new WP_Error('invalid', 'Recuperação indisponível.', ['status' => 400]);
    }

    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user) || !($user instanceof WP_User)) {
        return new WP_Error('invalid', 'Link inválido ou expirado.', ['status' => 400]);
    }

    // Aplica a redefinição de senha
    if (function_exists('reset_password')) {
        reset_password($user, $new_password);
    } else {
        wp_set_password($new_password, $user->ID);
    }

    // Marca a troca de senha para invalidar access tokens antigos
    update_user_meta((int) $user->ID, 'app_api_pwd_changed_at', time());

    // Revoga refresh tokens para forçar novo login
    if (function_exists('app_api_revoke_all_refresh_tokens_for_user')) {
        app_api_revoke_all_refresh_tokens_for_user((int) $user->ID);
    }

    app_api_clear_auth_cookies();

    // Limpa caches relacionados ao usuário
    if (function_exists('app_api_invalidate_user_caches')) {
        app_api_invalidate_user_caches((int) $user->ID);
    }

    return ['ok' => true];
}

function app_api_me(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    // Expõe dados completos do cliente sem quebrar compatibilidade
    // Mantém os campos já usados pelo app
    // Adiciona documentos, telefones e endereços
    $uid = (int) $user->ID;

    // Usa cache curto para reduzir chamadas duplicadas ao /me
    $no_cache = (string) $req->get_param('no_cache');
    $no_cache = ($no_cache === '1' || $no_cache === 'true' || $no_cache === 'yes');
    $cache_ttl = (int) apply_filters('app_api_me_cache_ttl', 30, $uid); // segundos
    $cache_key = 'app_api_cache_me_' . $uid;

    if (!$no_cache && $cache_ttl > 0) {
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['id']) && (int) ($cached['id'] ?? 0) === $uid) {
            return $cached;
        }
    }

    if (function_exists('app_api_build_me_payload')) {
        $payload = app_api_build_me_payload($user);
        if (!$no_cache && $cache_ttl > 0) {
            set_transient($cache_key, $payload, $cache_ttl);
        }
        return $payload;
    }


    $meta = function ($key) use ($uid) {
        $v = get_user_meta($uid, $key, true);
        if ($v === '' || $v === null)
            return null;
        if (is_array($v) || is_object($v))
            return null;
        return $v;
    };

    $wc = null;
    if (class_exists('WC_Customer')) {
        try {
            $wc = new WC_Customer($uid);
        } catch (Exception $e) {
            $wc = null;
        }
    }

    // CPF/CNPJ podem variar conforme o plugin brasileiro instalado.
    $cpf = $meta('billing_cpf');
    if ($cpf === null)
        $cpf = $meta('_billing_cpf');
    if ($cpf === null)
        $cpf = $meta('cpf');

    $cnpj = $meta('billing_cnpj');
    if ($cnpj === null)
        $cnpj = $meta('_billing_cnpj');
    if ($cnpj === null)
        $cnpj = $meta('cnpj');

    $billing = [
        'first_name' => $meta('billing_first_name') ?? ($wc ? $wc->get_billing_first_name() : null),
        'last_name' => $meta('billing_last_name') ?? ($wc ? $wc->get_billing_last_name() : null),
        'company' => $meta('billing_company') ?? ($wc ? $wc->get_billing_company() : null),
        'address_1' => $meta('billing_address_1') ?? ($wc ? $wc->get_billing_address_1() : null),
        'address_2' => $meta('billing_address_2') ?? ($wc ? $wc->get_billing_address_2() : null),
        // Campos comuns em checkouts brasileiros
        'number' => $meta('billing_number'),
        'neighborhood' => $meta('billing_neighborhood'),
        'city' => $meta('billing_city') ?? ($wc ? $wc->get_billing_city() : null),
        'state' => $meta('billing_state') ?? ($wc ? $wc->get_billing_state() : null),
        'postcode' => $meta('billing_postcode') ?? ($wc ? $wc->get_billing_postcode() : null),
        'country' => $meta('billing_country') ?? ($wc ? $wc->get_billing_country() : null),
        'email' => $meta('billing_email') ?? ($wc ? $wc->get_billing_email() : null),
        'phone' => $meta('billing_phone') ?? ($wc ? $wc->get_billing_phone() : null),

        // Documentos e campos extras
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
        // Campos comuns em checkouts brasileiros
        'number' => $meta('shipping_number'),
        'neighborhood' => $meta('shipping_neighborhood'),
        'city' => $meta('shipping_city') ?? ($wc ? $wc->get_shipping_city() : null),
        'state' => $meta('shipping_state') ?? ($wc ? $wc->get_shipping_state() : null),
        'postcode' => $meta('shipping_postcode') ?? ($wc ? $wc->get_shipping_postcode() : null),
        'country' => $meta('shipping_country') ?? ($wc ? $wc->get_shipping_country() : null),
    ];

    // Também expõe first_name e last_name do WordPress
    $wp_first_name = $meta('first_name');
    $wp_last_name = $meta('last_name');

   // Prioriza avatar enviado pelo app e faz fallback para Gravatar
    $avatar_url = null;
    if (function_exists('app_api_get_user_avatar_url')) {
        $avatar_url = app_api_get_user_avatar_url((int) $user->ID, 256);
    }
    if (!$avatar_url && function_exists('get_avatar_url')) {
        $avatar_url = get_avatar_url($user->ID, ['size' => 256]);
        if (!$avatar_url) $avatar_url = null;
    }

    $payload = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'avatar_url' => $avatar_url,
        'roles' => array_values((array) $user->roles),
        'is_admin' => user_can($user, 'manage_options'),

        // Campos extras opcionais
        'username' => $user->user_login,
        'first_name' => $wp_first_name,
        'last_name' => $wp_last_name,

        // Atalho de leitura
        'cpf' => $billing['cpf'],
        'phone' => $billing['phone'],

        // Estrutura completa do endereço
        'billing' => $billing,
        'shipping' => $shipping,
    ];

    if (!$no_cache && $cache_ttl > 0) {
        set_transient($cache_key, $payload, $cache_ttl);
    }

    return $payload;
}



// Atualização de perfil

function app_api_me_update_profile(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    $uid = (int) $user->ID;
    $old_email = (string) $user->user_email;

    $first = $req->get_param('first_name');
    $last = $req->get_param('last_name');
    $display = $req->get_param('name');
    $email = $req->get_param('email');
    $phone = $req->get_param('phone');

    $first = is_string($first) ? sanitize_text_field($first) : null;
    $last = is_string($last) ? sanitize_text_field($last) : null;
    $display = is_string($display) ? sanitize_text_field($display) : null;
    $email = is_string($email) ? sanitize_email($email) : null;
    $phone = is_string($phone) ? sanitize_text_field($phone) : null;

    $userdata = ['ID' => $uid];

    if ($display !== null && $display !== '') {
        $userdata['display_name'] = $display;
    } else {
        // Recalcula display_name quando first_name ou last_name forem enviados
        $fn = $first !== null ? $first : (string) get_user_meta($uid, 'first_name', true);
        $ln = $last !== null ? $last : (string) get_user_meta($uid, 'last_name', true);
        $dn = trim(trim($fn) . ' ' . trim($ln));
        if ($dn) $userdata['display_name'] = $dn;
    }

    if ($email !== null && $email !== '') {
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'E-mail inválido.', ['status' => 400]);
        }
        // Verifica conflito com e-mail já cadastrado
        $existing = get_user_by('email', $email);
        if ($existing && (int) $existing->ID !== $uid) {
            return new WP_Error('email_exists', 'Este e-mail já está em uso.', ['status' => 400]);
        }
        $userdata['user_email'] = $email;
    }

    $result = wp_update_user($userdata);
    if (is_wp_error($result)) {
        return new WP_Error('update_failed', 'Não foi possível atualizar os dados.', ['status' => 400]);
    }

    if ($first !== null) {
        update_user_meta($uid, 'first_name', $first);
        update_user_meta($uid, 'billing_first_name', $first);
        update_user_meta($uid, 'shipping_first_name', $first);
    }

    if ($last !== null) {
        update_user_meta($uid, 'last_name', $last);
        update_user_meta($uid, 'billing_last_name', $last);
        update_user_meta($uid, 'shipping_last_name', $last);
    }

    if ($email !== null && $email !== '') {
        update_user_meta($uid, 'billing_email', $email);
    }

    if ($phone !== null) {
        update_user_meta($uid, 'billing_phone', $phone);
    }

    if (function_exists('app_api_sync_user_profile_to_wc_orders')) {
        app_api_sync_user_profile_to_wc_orders($uid, [
            'first_name' => $first,
            'last_name' => $last,
            'billing_email' => ($email !== null && $email !== '') ? $email : null,
            'billing_phone' => $phone,
        ], $old_email);
    }

    if (function_exists('app_api_invalidate_user_caches')) {
        app_api_invalidate_user_caches($uid);
    }

    // Retorna o payload atualizado de /me
    $req->set_param('no_cache', '1');
    return app_api_me($req);
}

function app_api_me_change_password(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    $uid = (int) $user->ID;
    $current = (string) $req->get_param('current_password');
    $new = (string) $req->get_param('new_password');

    if (strlen($new) < 8) {
        return new WP_Error('invalid', 'A nova senha deve ter pelo menos 8 caracteres.', ['status' => 400]);
    }

    if (!$current || !wp_check_password($current, $user->user_pass, $uid)) {
        return new WP_Error('invalid', 'Senha atual incorreta.', ['status' => 400]);
    }

    wp_set_password($new, $uid);

    // Marca a troca de senha para invalidar access tokens antigos
    update_user_meta($uid, 'app_api_pwd_changed_at', time());

    // Revoga refresh tokens para forçar novo login
    if (function_exists('app_api_revoke_all_refresh_tokens_for_user')) {
        app_api_revoke_all_refresh_tokens_for_user($uid);
    }

    app_api_clear_auth_cookies();

    if (function_exists('app_api_invalidate_user_caches')) {
        app_api_invalidate_user_caches($uid);
    }

    return ['ok' => true, 'reauth' => true];
}

function app_api_me_update_avatar(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    $uid = (int) $user->ID;

    // Remove o avatar atual
    $remove = (string) $req->get_param('remove');
    $remove = ($remove === '1' || $remove === 'true' || $remove === 'yes');
    if ($remove) {
        $previous_avatar_id = (int) get_user_meta($uid, 'app_api_avatar_id', true);
        delete_user_meta($uid, 'app_api_avatar_id');
        update_user_meta($uid, 'app_api_avatar_updated_at', time());
        if ($previous_avatar_id > 0 && function_exists('app_api_delete_previous_avatar_attachment')) {
            app_api_delete_previous_avatar_attachment($previous_avatar_id, $uid);
        }
        if (function_exists('app_api_invalidate_user_caches')) app_api_invalidate_user_caches($uid);
        $req->set_param('no_cache', '1');
        return app_api_me($req);
    }

    $files = $req->get_file_params();
    $file = $files['avatar'] ?? null;

    if (!$file || !is_array($file) || empty($file['tmp_name']) || empty($file['name'])) {
        return new WP_Error('invalid', 'Envie um arquivo de imagem no campo "avatar".', ['status' => 400]);
    }

    $upload_error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
    if ($upload_error !== UPLOAD_ERR_OK) {
        return new WP_Error('invalid', 'Não foi possível processar o arquivo enviado.', ['status' => 400]);
    }

    if (!is_uploaded_file((string) $file['tmp_name'])) {
        return new WP_Error('invalid', 'Arquivo de upload inválido.', ['status' => 400]);
    }

    // Valida o tamanho máximo do arquivo
    $max = (int) apply_filters('app_api_avatar_max_bytes', 2 * 1024 * 1024, $uid);
    if (!empty($file['size']) && (int) $file['size'] > $max) {
        return new WP_Error('invalid', 'Imagem muito grande. Tente uma imagem menor.', ['status' => 400]);
    }

    // Valida o tipo MIME real do arquivo
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $allowed = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);
    $real_type = (string) ($checked['type'] ?? '');
    if (!$real_type) {
        $real_type = (string) wp_get_image_mime($file['tmp_name']);
    }

    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!$real_type || !in_array($real_type, $allowed_mimes, true)) {
        return new WP_Error('invalid', 'Formato inválido. Use JPG, PNG ou WEBP.', ['status' => 400]);
    }

    $image_size = @getimagesize((string) $file['tmp_name']);
    if (!is_array($image_size) || empty($image_size[0]) || empty($image_size[1])) {
        return new WP_Error('invalid', 'Imagem inválida ou corrompida.', ['status' => 400]);
    }

    $safe_file = $file;
    $safe_file['type'] = $real_type;

    $overrides = [
        'test_form' => false,
        'mimes' => $allowed,
    ];

    $uploaded = wp_handle_upload($safe_file, $overrides);
    if (!is_array($uploaded) || empty($uploaded['file']) || !empty($uploaded['error'])) {
        $msg = is_array($uploaded) && !empty($uploaded['error']) ? (string) $uploaded['error'] : 'Falha no upload.';
        return new WP_Error('upload_failed', $msg, ['status' => 400]);
    }

    $filename = $uploaded['file'];
    $filetype = wp_check_filetype(basename($filename), null);

    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title' => 'Avatar - user ' . $uid,
        'post_content' => '',
        'post_status' => 'inherit',
        'post_author' => $uid,
    ];

    $attach_id = wp_insert_attachment($attachment, $filename);
    if (is_wp_error($attach_id) || !$attach_id) {
        return new WP_Error('upload_failed', 'Falha ao salvar a imagem.', ['status' => 400]);
    }

    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
    if (is_array($attach_data)) {
        wp_update_attachment_metadata($attach_id, $attach_data);
    }

    $previous_avatar_id = (int) get_user_meta($uid, 'app_api_avatar_id', true);

    // Salva no user meta e sincroniza com o avatar do WordPress
    if (function_exists('app_api_sync_custom_avatar_attachment')) {
        app_api_sync_custom_avatar_attachment($uid, (int) $attach_id);
    } else {
        update_user_meta($uid, 'app_api_avatar_id', (int) $attach_id);
        update_user_meta($uid, 'app_api_avatar_updated_at', time());
    }

    if ($previous_avatar_id > 0 && $previous_avatar_id !== (int) $attach_id && function_exists('app_api_delete_previous_avatar_attachment')) {
        app_api_delete_previous_avatar_attachment($previous_avatar_id, $uid);
    }

    if (function_exists('app_api_invalidate_user_caches')) {
        app_api_invalidate_user_caches($uid);
    }

    $req->set_param('no_cache', '1');
    return app_api_me($req);
}

function app_api_me_orders(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    if (!function_exists('wc_get_orders'))
        return [];

    // Usa cache curto para reduzir consultas repetidas ao WooCommerce
    $no_cache = (string) $req->get_param('no_cache');
    $no_cache = ($no_cache === '1' || $no_cache === 'true' || $no_cache === 'yes');
    $cache_ttl = (int) apply_filters('app_api_cache_orders_ttl', 45, (int) $user->ID); // segundos
    $cache_key = 'app_api_cache_orders_v2_' . (int) $user->ID;

    if (!$no_cache && $cache_ttl > 0) {
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }
    }


    $orders = wc_get_orders([
        'customer_id' => $user->ID,
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $out = [];
    foreach ($orders as $o) {
        $status = (string) $o->get_status();
        $currency = (string) $o->get_currency();

        // Adiciona labels e formatos usados pela interface
        $status_label = function_exists('app_api_wc_order_status_label') ? app_api_wc_order_status_label($status) : $status;
        $total_formatted = function_exists('app_api_wc_price_plain') ? app_api_wc_price_plain($o->get_total(), $currency) : (string) $o->get_total();

        $payment_method = method_exists($o, 'get_payment_method') ? (string) $o->get_payment_method() : '';
        $payment_method_title = method_exists($o, 'get_payment_method_title') ? (string) $o->get_payment_method_title() : '';

        $items = [];
        foreach ($o->get_items() as $it) {
            $line_total = (string) $it->get_total();

            // Normaliza variações para o produto pai
            $pid_raw = (int) $it->get_product_id();
            $pid_norm = $pid_raw;
            if ($pid_raw > 0 && get_post_type($pid_raw) === 'product_variation') {
                $parent = wp_get_post_parent_id($pid_raw);
                if ($parent)
                    $pid_norm = (int) $parent;
            }
            $items[] = [
                // Mantém compatibilidade usando product_id normalizado
                'product_id' => $pid_norm,
                // Mantém o ID bruto da variação para depuração
                'product_id_raw' => $pid_raw,
                'name' => $it->get_name(),
                'qty' => (int) $it->get_quantity(),
                'total' => $line_total,
                // Adiciona o total formatado do item
                'total_formatted' => function_exists('app_api_wc_price_plain') ? app_api_wc_price_plain($line_total, $currency) : $line_total,
            ];
        }

        $out[] = [
            'id' => $o->get_id(),
            'status' => $status,
            // Adiciona status amigável do pedido
            'status_label' => $status_label,
            'created_at' => $o->get_date_created() ? $o->get_date_created()->date('c') : null,
            'total' => (string) $o->get_total(),
            'currency' => $currency,
            // Adiciona o total formatado do pedido
            'total_formatted' => $total_formatted,
            // Expõe a forma de pagamento
            'payment_method' => $payment_method,
            'payment_method_title' => $payment_method_title,
            'items' => $items,
        ];
    }

    
    if (!$no_cache && $cache_ttl > 0) {
        set_transient($cache_key, $out, $cache_ttl);
    }

    // Se a chamada usar no_cache, atualiza o transient para as próximas telas
        if ($no_cache && $cache_ttl > 0) {
        set_transient($cache_key, $out, $cache_ttl);
    }

    return $out;
}

function app_api_me_subscriptions(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    // Retorna as assinaturas a partir dos itens de pedidos concluídos
        
    if (!function_exists('wc_get_orders'))
        return [];

    // Usa cache curto para reduzir consultas repetidas ao WooCommerce
    $no_cache = (string) $req->get_param('no_cache');
    $no_cache = ($no_cache === '1' || $no_cache === 'true' || $no_cache === 'yes');
    $cache_ttl = (int) apply_filters('app_api_cache_subs_ttl', 90, (int) $user->ID); // segundos
    $cache_key = 'app_api_cache_subs_v2_' . (int) $user->ID;

    if (!$no_cache && $cache_ttl > 0) {
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
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

    // Heurística para identificar produtos de assinatura
    // - possui categoria raiz do Real3D
    // - possui metadados de flipbook
    $is_subscription_product = function (int $pid): bool {
        if ($pid <= 0)
            return false;

        $root = (string) get_post_meta($pid, 'app_api_r3d_root_category', true);
        if (trim($root) !== '')
            return true;

        $m1 = (string) get_post_meta($pid, 'app_api_flipbook', true);
        $m2 = (string) get_post_meta($pid, '_app_api_flipbook_id', true);
        $m3 = (string) get_post_meta($pid, '_app_api_flipbook_id_non', true);
        if (trim($m1) !== '' || trim($m2) !== '' || trim($m3) !== '')
            return true;

        return false;
    };

    $orders = wc_get_orders([
        'customer_id' => $user->ID,
        'status' => ['completed'],
        'limit' => 200,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    // Agrupa por product_id usando o pedido mais recente
    $by_pid = [];
    foreach ($orders as $o) {
        $order_id = (int) $o->get_id();
        $order_status = (string) $o->get_status();
        $order_date = $o->get_date_created() ? $o->get_date_created()->getTimestamp() : 0;
        $order_date_iso = $o->get_date_created() ? $o->get_date_created()->date('c') : null;
        $currency = (string) $o->get_currency();

        foreach ($o->get_items() as $it) {
            $pid = $normalize_pid($it->get_product_id());
            if (!$pid)
                continue;

            if (!$is_subscription_product($pid))
                continue;

            $name = (string) $it->get_name();
            $qty = (int) $it->get_quantity();
            $line_total = (string) $it->get_total();

            // Imagem do produto, quando disponível
            $product_cover = null;
            $p = function_exists('wc_get_product') ? wc_get_product($pid) : null;
            if ($p && method_exists($p, 'get_image_id')) {
                $cover_id = (int) $p->get_image_id();
                $product_cover = $cover_id ? wp_get_attachment_image_url($cover_id, 'large') : null;
            }
            if (!$product_cover)
                $product_cover = get_the_post_thumbnail_url($pid, 'large');

            if (!isset($by_pid[$pid]) || $order_date > (int) ($by_pid[$pid]['_order_ts'] ?? 0)) {
                $by_pid[$pid] = [
                    '_order_ts' => $order_date,
                    'product_id' => $pid,
                    'name' => $name,
                    'qty' => $qty,
                    'order_id' => $order_id,
                    'order_status' => $order_status,
                    // Label amigável do status
                    'order_status_label' => function_exists('app_api_wc_order_status_label') ? app_api_wc_order_status_label($order_status) : $order_status,
                    // Expõe a forma de pagamento (do pedido)
                    'payment_method' => method_exists($o, 'get_payment_method') ? (string) $o->get_payment_method() : '',
                    'payment_method_title' => method_exists($o, 'get_payment_method_title') ? (string) $o->get_payment_method_title() : '',
                    'created_at' => $order_date_iso,
                    'total' => $line_total,
                    'currency' => $currency,
                    // Total formatado
                    'total_formatted' => function_exists('app_api_wc_price_plain') ? app_api_wc_price_plain($line_total, $currency) : $line_total,
                    'cover_image' => $product_cover,
                    'product_slug' => get_post_field('post_name', $pid),
                ];
            } else {
                // Acumula quantidade para o mesmo produto
                $by_pid[$pid]['qty'] = (int) ($by_pid[$pid]['qty'] ?? 0) + $qty;
            }
        }
    }

    // Enriquece com dados do Woo Subscriptions, se disponível
    if (function_exists('wcs_get_users_subscriptions')) {
        $subs = wcs_get_users_subscriptions($user->ID);
        foreach (($subs ?: []) as $s) {
            $sub_status = (string) $s->get_status();
            $sub_id = (int) $s->get_id();
            $start_date = method_exists($s, 'get_date') && $s->get_date('start') ? $s->get_date('start')->date('c') : null;
            $next_payment = method_exists($s, 'get_date') && $s->get_date('next_payment') ? $s->get_date('next_payment')->date('c') : null;

            foreach ($s->get_items() as $it) {
                $pid = $normalize_pid($it->get_product_id());
                if (!$pid)
                    continue;
                if (!isset($by_pid[$pid]))
                    continue;

                // Anexa informações da assinatura
                $by_pid[$pid]['subscription_id'] = $sub_id;
                $by_pid[$pid]['subscription_status'] = $sub_status;
                $by_pid[$pid]['subscription_start_date'] = $start_date;
                $by_pid[$pid]['subscription_next_payment'] = $next_payment;
            }
        }
    }

    foreach ($by_pid as $pid => &$row) {
        $pid = (int) $pid;
        $row['product_url'] = get_permalink($pid) ?: null;

        if (function_exists('app_api_ihc_get_product_access_state')) {
            $access = app_api_ihc_get_product_access_state((int) $user->ID, $pid);
            $row['access_level_id'] = $access['level_id'] ?? null;
            $row['access_level_label'] = $access['level_label'] ?? null;
            $row['access_status'] = $access['status'] ?? null;
            $row['access_status_label'] = $access['status_label'] ?? null;
            $row['access_is_active'] = !empty($access['is_active']);
            $row['access_is_expired'] = !empty($access['is_expired']);
            $row['access_has_expiration'] = !empty($access['has_expiration']);
            $row['access_expires_at'] = $access['expires_at'] ?? null;
            $row['access_starts_at'] = $access['start_time'] ?? null;
            $row['renewal_url'] = $access['renewal_url'] ?? ($row['product_url'] ?: null);
        }
    }
    unset($row);

    $out = array_values($by_pid);

    // Remove a chave interna de agrupamento
    foreach ($out as &$row) {
        unset($row['_order_ts']);
    }

    // Ordena pelo pedido mais recente
    usort($out, function ($a, $b) {
        $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
        $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
        return $tb <=> $ta;
    });

    
    if (!$no_cache && $cache_ttl > 0) {
        set_transient($cache_key, $out, $cache_ttl);
    }

    // Se a chamada usar no_cache, atualiza o transient para as próximas telas
        if ($no_cache && $cache_ttl > 0) {
        set_transient($cache_key, $out, $cache_ttl);
    }

    return $out;
}

function app_api_debug_preview($value, int $max = 160): string
{
    if ($value === null)
        return '';
    if (is_bool($value))
        return $value ? 'true' : 'false';
    if (is_numeric($value))
        return (string) $value;

    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value);
    }

    $s = (string) $value;
    $s = preg_replace('/\s+/', ' ', $s);
    if (strlen($s) > $max)
        $s = substr($s, 0, $max) . '…';
    return $s;
}

function app_api_extract_ints_from_value($value): array
{
    // tenta desserializar
    if (is_string($value)) {
        $maybe = @maybe_unserialize($value);
        if ($maybe !== $value) {
            return app_api_extract_ints_from_value($maybe);
        }
    }

    $ints = [];

    if (is_numeric($value)) {
        $n = intval($value);
        return $n > 0 ? [$n] : [];
    }

    if (is_array($value)) {
        foreach ($value as $v) {
            $ints = array_merge($ints, app_api_extract_ints_from_value($v));
        }
        return array_values(array_unique(array_filter(array_map('intval', $ints))));
    }

    if (is_object($value)) {
        foreach (get_object_vars($value) as $v) {
            $ints = array_merge($ints, app_api_extract_ints_from_value($v));
        }
        return array_values(array_unique(array_filter(array_map('intval', $ints))));
    }

    if (is_string($value)) {
        // tenta capturar id="123"
        if (preg_match_all('/\bid\s*=\s*["\']?(\d+)["\']?/i', $value, $m)) {
            foreach ($m[1] as $x)
                $ints[] = intval($x);
        }
        // tenta capturar números soltos
        if (preg_match_all('/\b(\d{1,9})\b/', $value, $m2)) {
            foreach ($m2[1] as $x)
                $ints[] = intval($x);
        }
    }

    return array_values(array_unique(array_filter(array_map('intval', $ints))));
}

function app_api_debug_product_real3d_meta(int $product_id): array
{
    $meta = get_post_meta($product_id);
    $hits = [];

    foreach (($meta ?: []) as $k => $arr) {
        $key = strtolower((string) $k);

        // foca no que tem cara de Real3D/Flipbook
        if (strpos($key, 'flipbook') === false && strpos($key, 'real3d') === false && strpos($key, 'r3d') === false) {
            continue;
        }

        $raw = is_array($arr) ? ($arr[0] ?? null) : $arr;
        $ints = app_api_extract_ints_from_value($raw);

        $resolved = [];
        foreach ($ints as $id) {
            $pt = get_post_type($id);
            if (!$pt)
                continue;
            $resolved[] = [
                'id' => $id,
                'post_type' => $pt,
                'permalink' => get_permalink($id),
            ];
        }

        $hits[] = [
            'meta_key' => (string) $k,
            'preview' => app_api_debug_preview($raw),
            'ints' => $ints,
            'resolved' => $resolved,
        ];
    }

    return $hits;
}

function app_api_me_magazines(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    $debug = (string) $req->get_param('debug');
    $debug = ($debug === '1' || $debug === 'true' || $debug === 'yes');

    // ✅ NOVO (performance): cache da lista completa de revistas por usuário
    $no_cache = (string) $req->get_param('no_cache');
    $no_cache = ($no_cache === '1' || $no_cache === 'true' || $no_cache === 'yes');
    $cache_ttl = (int) apply_filters('app_api_cache_magazines_ttl', 120, (int) $user->ID); // segundos
    $cache_key = 'app_api_cache_magazines_v2_' . (int) $user->ID;

    if (!$debug && !$no_cache && $cache_ttl > 0) {
        $cached_pack = get_transient($cache_key);
        if (is_array($cached_pack) && isset($cached_pack['magazines']) && is_array($cached_pack['magazines'])) {
            $magazines_all = array_values($cached_pack['magazines']);
            $categories_all_cached = (isset($cached_pack['categories']) && is_array($cached_pack['categories']))
                ? array_values($cached_pack['categories'])
                : [];

            // -------------------------------------------------------------
            // Mesmo bloco de filtro/paginação (sem recomputar tudo)
            // -------------------------------------------------------------
            $page_param     = $req->get_param('page');
            $per_page_param = $req->get_param('per_page');
            $category_param = $req->get_param('category');
            $all_param      = $req->get_param('all');

            $requested_paging = ($page_param !== null || $per_page_param !== null || $category_param !== null);
            $force_all = ((string)$all_param === '1' || (string)$all_param === 'true' || (string)$all_param === 'yes');

            // Filtro por categoria (ID ou slug), se solicitado
            $magazines_filtered = $magazines_all;
            if ($category_param !== null && $category_param !== '') {
                $cat_raw = (string) $category_param;
                $is_num = is_numeric($cat_raw);
                $cat_id = $is_num ? (int) $cat_raw : 0;
                $cat_slug = $is_num ? '' : $cat_raw;

                $magazines_filtered = array_values(array_filter($magazines_all, function($m) use ($cat_id, $cat_slug, $is_num) {
                    $cats = isset($m['categories']) && is_array($m['categories']) ? $m['categories'] : [];
                    foreach ($cats as $c) {
                        if ($is_num) {
                            if ((int)($c['id'] ?? 0) === $cat_id) return true;
                        } else {
                            if ((string)($c['slug'] ?? '') === $cat_slug) return true;
                        }
                    }
                    return false;
                }));
            }

            // Se o cliente pediu paginação (ou categoria), retornamos objeto com metadados
            if ($requested_paging && !$force_all) {
                $page = max(1, (int)($page_param ?: 1));
                $per_page = (int)($per_page_param ?: 30);
                if ($per_page < 1) $per_page = 30;
                if ($per_page > 100) $per_page = 100;

                $total = count($magazines_filtered);
                $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;
                $offset = ($page - 1) * $per_page;
                $page_items = array_slice($magazines_filtered, $offset, $per_page);

                // categorias completas no payload (para UI)
                $categories_all = !empty($categories_all_cached)
                    ? $categories_all_cached
                    : (function_exists('app_api_magazines_collect_categories')
                        ? app_api_magazines_collect_categories($magazines_all)
                        : []);

                $response = rest_ensure_response([
                    'user_id' => (int) $user->ID,
                    'magazines_count' => count($magazines_all),
                    'magazines' => array_values($page_items),
                    'total' => $total,
                    'total_pages' => $total_pages,
                    'page' => $page,
                    'per_page' => $per_page,
                    'categories' => $categories_all,
                ]);

                $response->header('X-WP-Total', (string) $total);
                $response->header('X-WP-TotalPages', (string) $total_pages);
                $response->header('X-App-Cache', 'HIT');
                return $response;
            }

            // Default: full array (compat)
            return array_values($magazines_all);
        }
    }



    // Paginação/filtro (opcionais). Se não vier page/per_page, mantém o retorno antigo (array).
    $page = $req->get_param('page');
    $per_page = $req->get_param('per_page');
    $all = $req->get_param('all');
    $category = $req->get_param('category');

    $use_pagination = (!$all) && ($page !== null || $per_page !== null || $category !== null);

    $page = max(1, (int) ($page ?: 1));
    $per_page = (int) ($per_page ?: 30);
    if ($per_page < 1) $per_page = 30;
    if ($per_page > 100) $per_page = 100;

    // Dedupe opcional (por padrão DESLIGADO para não "sumir" edições).
    $dedupe = (bool) apply_filters('app_api_magazines_dedupe_flipbooks', false, (int) $user->ID);

    $dedupe_flipbooks = $dedupe;

    // ✅ AJUSTE: SOMENTE produtos de pedidos CONCLUÍDOS
    $ids = function_exists('app_api_get_completed_order_product_ids')
        ? app_api_get_completed_order_product_ids((int) $user->ID)
        : [];

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (function_exists('app_api_filter_active_membership_product_ids')) {
        $ids = app_api_filter_active_membership_product_ids((int) $user->ID, $ids);
    }

    // ✅ NOVO: helper local para inferir ano/mês a partir de categorias (leaf -> root)
    $infer_ym_from_categories = function (array $cats): array {
        $month_map = [
            'janeiro' => 1,
            'jan' => 1,
            'fevereiro' => 2,
            'fev' => 2,
            'março' => 3,
            'marco' => 3,
            'mar' => 3,
            'abril' => 4,
            'abr' => 4,
            'maio' => 5,
            'mai' => 5,
            'junho' => 6,
            'jun' => 6,
            'julho' => 7,
            'jul' => 7,
            'agosto' => 8,
            'ago' => 8,
            'setembro' => 9,
            'set' => 9,
            'outubro' => 10,
            'out' => 10,
            'novembro' => 11,
            'nov' => 11,
            'dezembro' => 12,
            'dez' => 12,
        ];

        $year = null;
        $month = null;

        for ($i = count($cats) - 1; $i >= 0; $i--) {
            $t = strtolower(trim(
                (string) ($cats[$i]['name'] ?? '') . ' ' . (string) ($cats[$i]['slug'] ?? '')
            ));

            if (!$month) {
                foreach ($month_map as $k => $m) {
                    if (strpos($t, $k) !== false) {
                        $month = $m;
                        break;
                    }
                }
                if (!$month && preg_match('/\b(?:mes|m[eê]s)?\s*0?([1-9]|1[0-2])\b/iu', $t, $mm)) {
                    $month = (int) $mm[1];
                }
            }

            if (!$year && preg_match_all('/\b(19\d{2}|20\d{2})\b/', $t, $yy)) {
                $year = max(array_map('intval', $yy[1]));
            }

            if ($year && $month)
                break;
        }

        return ['year' => $year, 'month' => $month];
    };

    $magazines = [];
    // Evita duplicidade quando um flipbook aparece em mais de um produto/categoria
    $seen_flipbooks = [];
    $debug_rows = [];

    // Cache em memória por requisição (evita recalcular pdf_url em listas grandes)
    $pdf_info_cache = [];

    foreach ($ids as $pid) {

        // Para o produto retornar revistas, ele precisa estar vinculado a uma
        // categoria raiz do Real3D (slug em app_api_r3d_root_category).
        // Se estiver vazio, não retornamos nenhum flipbook (ex.: produto 3763 enquanto não for configurado).
        $root_slug = trim((string) get_post_meta((int) $pid, 'app_api_r3d_root_category', true));
        $has_root_category = ($root_slug !== '');

        $flipbook_ids_from_meta_list = function_exists('app_api_get_product_flipbook_ids')
            ? app_api_get_product_flipbook_ids((int) $pid)
            : [];

        $flip_from_real3d = function_exists('app_api_real3d_get_product_flipbooks')
            ? app_api_real3d_get_product_flipbooks((int) $pid)
            : ['purchased' => null, 'non_purchased' => null];

        $flipbook_from_meta_key = null;
        if (empty($flipbook_ids_from_meta_list)) {
            $flipbook_from_meta_key = app_api_get_product_flipbook_id((int) $pid);
        }

        $final_flipbook_ids = $flipbook_ids_from_meta_list;

        if (empty($final_flipbook_ids)) {
            $r3d = $flip_from_real3d['purchased'] ?? null;
            if ($r3d)
                $final_flipbook_ids = [(int) $r3d];
        }

        if (empty($final_flipbook_ids) && $flipbook_from_meta_key) {
            $final_flipbook_ids = [(int) $flipbook_from_meta_key];
        }

        $final_flipbook_ids = array_values(array_unique(array_filter(array_map('intval', $final_flipbook_ids))));

        // Sem categoria raiz definida -> opcionalmente não retorna nenhum flipbook para este produto
        // (Por padrão NÃO exige a categoria raiz para evitar perder edições já existentes.)
        $require_root_category = (bool) apply_filters('app_api_magazines_require_root_category', false, (int) $pid, (int) $user->ID);
        if ($require_root_category && !$has_root_category) {
            $final_flipbook_ids = [];
        }

        $product = function_exists('wc_get_product') ? wc_get_product($pid) : null;

        $product_cover = null;
        if ($product && method_exists($product, 'get_image_id')) {
            $cover_id = (int) $product->get_image_id();
            $product_cover = $cover_id ? wp_get_attachment_image_url($cover_id, 'large') : null;
        }
        if (!$product_cover)
            $product_cover = get_the_post_thumbnail_url($pid, 'large');

        if ($debug) {
            $flipbooks_debug = [];
            foreach ($final_flipbook_ids as $fbid) {
                $fbid = (int) $fbid;

                $is_real3d = function_exists('app_api_is_real3d_flipbook_id')
                    ? app_api_is_real3d_flipbook_id($fbid)
                    : true;

                $flip_name_dbg = function_exists('app_api_real3d_get_flipbook_name')
                    ? app_api_real3d_get_flipbook_name($fbid)
                    : null;

                $flipbooks_debug[] = [
                    'flipbook_id' => $fbid,
                    'is_real3d' => $is_real3d,
                    'post_type' => get_post_type($fbid),
                    'title' => $flip_name_dbg ?: get_the_title($fbid),
                    'cover_image' => function_exists('app_api_real3d_get_flipbook_thumb')
                        ? app_api_real3d_get_flipbook_thumb($fbid)
                        : get_the_post_thumbnail_url($fbid, 'large'),
                    'permalink' => get_permalink($fbid),
                    'categories' => function_exists('app_api_real3d_get_flipbook_categories')
                        ? app_api_real3d_get_flipbook_categories($fbid)
                        : [],
                ];
            }

            $debug_rows[] = [
                'product_id' => (int) $pid,
                'title' => $product ? $product->get_name() : get_the_title($pid),
                'flipbook_ids_from_meta_list' => $flipbook_ids_from_meta_list,
                'flip_real3d' => $flip_from_real3d,
                'flip_from_meta_key' => $flipbook_from_meta_key,
                'final_flipbook_ids' => $final_flipbook_ids,
                'final_flipbooks' => $flipbooks_debug,
                'product_cover_image' => $product_cover,
                'meta_hits' => function_exists('app_api_debug_product_real3d_meta')
                    ? app_api_debug_product_real3d_meta((int) $pid)
                    : [],
            ];
        }

        if (empty($final_flipbook_ids))
            continue;

        $product_title = $product ? $product->get_name() : get_the_title($pid);

        foreach ($final_flipbook_ids as $fbid) {
            $fbid = (int) $fbid;

            if (function_exists('app_api_is_real3d_flipbook_id') && !app_api_is_real3d_flipbook_id($fbid)) {
                continue;
            }

            // Dedupe global (opcional). Por padrão, NÃO removemos itens para manter o total original.
            if ($dedupe_flipbooks) {
                if (isset($seen_flipbooks[$fbid])) {
                    continue;
                }
                $seen_flipbooks[$fbid] = true;
            }

            $flip_name = function_exists('app_api_real3d_get_flipbook_name')
                ? app_api_real3d_get_flipbook_name($fbid)
                : null;

            $ym = function_exists('app_api_parse_year_month_from_text')
                ? app_api_parse_year_month_from_text($flip_name ?: '')
                : ['year' => null, 'month' => null];

            $flip_cover = function_exists('app_api_real3d_get_flipbook_thumb')
                ? app_api_real3d_get_flipbook_thumb($fbid)
                : null;

            $cover = $flip_cover ? $flip_cover : $product_cover;

            $title = $flip_name ? $flip_name : $product_title;

            if (!$flip_name && !empty($ym['year']) && !empty($ym['month'])) {
                $title = $product_title . ' • ' . app_api_month_short_pt((int) $ym['month']) . '/' . (int) $ym['year'];
            }

            $edition_number = function_exists('app_api_parse_edition_number_from_title')
                ? app_api_parse_edition_number_from_title($title)
                : null;

            // ✅ PDF: expõe apenas endpoint autenticado (evita vazamento de URL direta em uploads)
            if (!isset($pdf_info_cache[$fbid])) {
                $pdf_info_cache[$fbid] = ['url' => null];
            }

            $pdf_api_url = rest_url(APP_API_NS . '/magazines/' . (int) $pid . '/pdf?flipbook_id=' . (int) $fbid);
            if (function_exists('app_api_force_https_url')) {
                $pdf_api_url = app_api_force_https_url($pdf_api_url);
            }

            // ✅ categorias do flipbook (pai -> filho -> ...)
            $categories = function_exists('app_api_real3d_get_flipbook_categories')
                ? app_api_real3d_get_flipbook_categories($fbid)
                : [];

            // ✅ NOVO: edition_year/month com fallback pela categoria
            $edition_year = $ym['year'] ? (int) $ym['year'] : null;
            $edition_month = $ym['month'] ? (int) $ym['month'] : null;

            if ((!$edition_year || !$edition_month) && !empty($categories)) {
                $ym2 = $infer_ym_from_categories($categories);
                if (!$edition_year && !empty($ym2['year']))
                    $edition_year = (int) $ym2['year'];
                if (!$edition_month && !empty($ym2['month']))
                    $edition_month = (int) $ym2['month'];
            }

            $magazines[] = [
                'product_id' => (int) $pid,
                'title' => $title,
                'flipbook_id' => $fbid,
                'cover_image' => $cover,
                'edition_year' => $edition_year,
                'edition_month' => $edition_month,
                'edition_number' => $edition_number,

                // ✅ NOVO: PDF info para o app abrir no leitor nativo
                'pdf_url' => null,
                'pdf_api_url' => $pdf_api_url,

                // ✅ AJUSTE: padroniza nome do campo
                'categories' => $categories,
            ];
        }
    }

    $magazines = apply_filters('app_api_magazines_payload', $magazines, $user->ID);

    usort($magazines, function ($a, $b) {
        $an = intval($a['edition_number'] ?? 0);
        $bn = intval($b['edition_number'] ?? 0);

        if ($an && $bn)
            return $bn <=> $an;
        if ($an && !$bn)
            return -1;
        if (!$an && $bn)
            return 1;

        $at = (string) ($a['title'] ?? '');
        $bt = (string) ($b['title'] ?? '');
        return strcmp($bt, $at);
    });

    // ✅ NOVO (performance): grava cache do resultado completo (já ordenado)
    if (!$debug && !$no_cache && $cache_ttl > 0) {
        $categories_all_cached = function_exists('app_api_magazines_collect_categories')
            ? app_api_magazines_collect_categories(array_values($magazines))
            : [];

        set_transient($cache_key, [
            'magazines' => array_values($magazines),
            'categories' => $categories_all_cached,
            'generated_at' => time(),
        ], $cache_ttl);
    }


    // ---------------------------------------------------------------------
    // Optional filtering & pagination (does NOT change default behavior)
    // - Default (no params): returns the full array (compatibility)
    // - If page/per_page/category provided: returns an object with metadata
    // ---------------------------------------------------------------------

    $page_param     = $req->get_param('page');
    $per_page_param = $req->get_param('per_page');
    $category_param = $req->get_param('category');
    $all_param      = $req->get_param('all');

    $requested_paging = ($page_param !== null || $per_page_param !== null || $category_param !== null);
    $force_all = ((string)$all_param === '1' || (string)$all_param === 'true' || (string)$all_param === 'yes');

    $magazines_all = array_values($magazines);

    // Filtro por categoria (ID ou slug), se solicitado
    $magazines_filtered = $magazines_all;
    if ($category_param !== null && $category_param !== '') {
        $cat_raw = (string) $category_param;
        $is_num = is_numeric($cat_raw);
        $cat_id = $is_num ? (int) $cat_raw : 0;
        $cat_slug = $is_num ? '' : $cat_raw;

        $magazines_filtered = array_values(array_filter($magazines_all, function($m) use ($cat_id, $cat_slug, $is_num) {
            $cats = isset($m['categories']) && is_array($m['categories']) ? $m['categories'] : [];
            foreach ($cats as $c) {
                if ($is_num) {
                    if ((int)($c['id'] ?? 0) === $cat_id) return true;
                } else {
                    if ((string)($c['slug'] ?? '') === $cat_slug) return true;
                }
            }
            return false;
        }));
    }

    // Se o cliente pediu paginação (ou categoria), retornamos objeto com metadados
    if ($requested_paging && !$force_all) {
        $page = max(1, (int)($page_param ?: 1));
        $per_page = (int)($per_page_param ?: 30);
        if ($per_page < 1) $per_page = 30;
        if ($per_page > 100) $per_page = 100;

        $total = count($magazines_filtered);
        $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;
        $offset = ($page - 1) * $per_page;
        $page_items = array_slice($magazines_filtered, $offset, $per_page);

        // Para a UI ter todas as categorias desde o início, incluímos categorias completas no payload
        $categories_all = !empty($categories_all_cached)
            ? $categories_all_cached
            : (function_exists('app_api_magazines_collect_categories')
                ? app_api_magazines_collect_categories($magazines_all)
                : []);

        $response = rest_ensure_response([
            'user_id' => (int) $user->ID,
            'magazines_count' => count($magazines_all),
            'magazines' => array_values($page_items),
            'total' => $total,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
            'categories' => $categories_all,
        ]);

        $response->header('X-WP-Total', (string) $total);
        $response->header('X-WP-TotalPages', (string) $total_pages);
        return $response;
    }

    // Default: full array (compat)
    $magazines = $magazines_all;

    if ($debug) {
        return [
            'user_id' => (int) $user->ID,
            'entitled_ids' => $ids,
            'ids_from_orders' => [],
            'merged_ids' => $ids,
            'magazines_count' => count($magazines),
            'magazines' => array_values($magazines),
            'per_product' => $debug_rows,
        ];
    }

    return array_values($magazines);
}

function app_api_me_magazines_categories(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    // Reutiliza o builder existente (mantém total original)
    $magazines = function_exists('app_api_build_magazines_for_user_id')
        ? app_api_build_magazines_for_user_id((int) $user->ID)
        : [];

    $cats = function_exists('app_api_magazines_collect_categories')
        ? app_api_magazines_collect_categories($magazines)
        : [];

    return [
        'user_id' => (int) $user->ID,
        'magazines_count' => count($magazines),
        'categories' => $cats,
    ];
}

function app_api_build_magazines_for_user_id(int $user_id): array
{
    // ✅ Acesso às revistas SOMENTE por pedidos CONCLUÍDOS.
    // (O usuário pode logar com pedidos pending/on-hold, mas não verá revistas.)
    $ids = app_api_get_completed_order_product_ids($user_id);

    // Mantém a variável por compatibilidade/diagnóstico
    $ids_from_orders = $ids;

    if (function_exists('app_api_filter_active_membership_product_ids')) {
        $ids = app_api_filter_active_membership_product_ids($user_id, $ids);
    }

    // ✅ NOVO: helper local para inferir ano/mês a partir de categorias
    $infer_ym_from_categories = function (array $cats): array {
        $month_map = [
            'janeiro' => 1,
            'jan' => 1,
            'fevereiro' => 2,
            'fev' => 2,
            'março' => 3,
            'marco' => 3,
            'mar' => 3,
            'abril' => 4,
            'abr' => 4,
            'maio' => 5,
            'mai' => 5,
            'junho' => 6,
            'jun' => 6,
            'julho' => 7,
            'jul' => 7,
            'agosto' => 8,
            'ago' => 8,
            'setembro' => 9,
            'set' => 9,
            'outubro' => 10,
            'out' => 10,
            'novembro' => 11,
            'nov' => 11,
            'dezembro' => 12,
            'dez' => 12,
        ];

        $year = null;
        $month = null;

        for ($i = count($cats) - 1; $i >= 0; $i--) {
            $t = strtolower(trim(
                (string) ($cats[$i]['name'] ?? '') . ' ' . (string) ($cats[$i]['slug'] ?? '')
            ));

            if (!$month) {
                foreach ($month_map as $k => $m) {
                    if (strpos($t, $k) !== false) {
                        $month = $m;
                        break;
                    }
                }
                if (!$month && preg_match('/\b(?:mes|m[eê]s)?\s*0?([1-9]|1[0-2])\b/iu', $t, $mm)) {
                    $month = (int) $mm[1];
                }
            }

            if (!$year && preg_match_all('/\b(19\d{2}|20\d{2})\b/', $t, $yy)) {
                $year = max(array_map('intval', $yy[1]));
            }

            if ($year && $month)
                break;
        }

        return ['year' => $year, 'month' => $month];
    };

    $magazines = [];

    foreach ($ids as $pid) {

        $flipbook_ids_from_meta_list = function_exists('app_api_get_product_flipbook_ids')
            ? app_api_get_product_flipbook_ids((int) $pid)
            : [];

        $flip_from_real3d = function_exists('app_api_real3d_get_product_flipbooks')
            ? app_api_real3d_get_product_flipbooks((int) $pid)
            : ['purchased' => null, 'non_purchased' => null];

        $flipbook_from_meta_key = null;
        if (empty($flipbook_ids_from_meta_list)) {
            $flipbook_from_meta_key = app_api_get_product_flipbook_id((int) $pid);
        }

        $final_flipbook_ids = $flipbook_ids_from_meta_list;

        if (empty($final_flipbook_ids)) {
            $r3d = $flip_from_real3d['purchased'] ?? null;
            if ($r3d)
                $final_flipbook_ids = [(int) $r3d];
        }

        if (empty($final_flipbook_ids) && $flipbook_from_meta_key) {
            $final_flipbook_ids = [(int) $flipbook_from_meta_key];
        }

        $final_flipbook_ids = array_values(array_unique(array_filter(array_map('intval', $final_flipbook_ids))));
        if (empty($final_flipbook_ids))
            continue;

        $product = function_exists('wc_get_product') ? wc_get_product($pid) : null;

        $product_cover = null;
        if ($product && method_exists($product, 'get_image_id')) {
            $cover_id = (int) $product->get_image_id();
            $product_cover = $cover_id ? wp_get_attachment_image_url($cover_id, 'large') : null;
        }
        if (!$product_cover)
            $product_cover = get_the_post_thumbnail_url($pid, 'large');

        $product_title = $product ? $product->get_name() : get_the_title($pid);

        foreach ($final_flipbook_ids as $fbid) {
            $fbid = (int) $fbid;

            if (function_exists('app_api_is_real3d_flipbook_id') && !app_api_is_real3d_flipbook_id($fbid)) {
                continue;
            }

            $flip_name = function_exists('app_api_real3d_get_flipbook_name')
                ? app_api_real3d_get_flipbook_name($fbid)
                : null;

            $ym = function_exists('app_api_parse_year_month_from_text')
                ? app_api_parse_year_month_from_text($flip_name ?: '')
                : ['year' => null, 'month' => null];

            $flip_cover = function_exists('app_api_real3d_get_flipbook_thumb')
                ? app_api_real3d_get_flipbook_thumb($fbid)
                : null;

            $cover = $flip_cover ? $flip_cover : $product_cover;

            $title = $flip_name ? $flip_name : $product_title;

            $edition_number = function_exists('app_api_parse_edition_number_from_title')
                ? app_api_parse_edition_number_from_title($title)
                : null;

            $categories = function_exists('app_api_real3d_get_flipbook_categories')
                ? app_api_real3d_get_flipbook_categories($fbid)
                : [];

            // ✅ NOVO: edition_year/month com fallback pela categoria
            $edition_year = $ym['year'] ? (int) $ym['year'] : null;
            $edition_month = $ym['month'] ? (int) $ym['month'] : null;

            if ((!$edition_year || !$edition_month) && !empty($categories)) {
                $ym2 = $infer_ym_from_categories($categories);
                if (!$edition_year && !empty($ym2['year']))
                    $edition_year = (int) $ym2['year'];
                if (!$edition_month && !empty($ym2['month']))
                    $edition_month = (int) $ym2['month'];
            }

            $magazines[] = [
                'product_id' => (int) $pid,
                'title' => $title,
                'flipbook_id' => $fbid,
                'cover_image' => $cover,
                'edition_year' => $edition_year,
                'edition_month' => $edition_month,
                'edition_number' => $edition_number,
                'categories' => $categories,
            ];
        }
    }

    usort($magazines, function ($a, $b) {
        $an = intval($a['edition_number'] ?? 0);
        $bn = intval($b['edition_number'] ?? 0);

        if ($an && $bn)
            return $bn <=> $an;
        if ($an && !$bn)
            return -1;
        if (!$an && $bn)
            return 1;

        $at = (string) ($a['title'] ?? '');
        $bt = (string) ($b['title'] ?? '');
        return strcmp($bt, $at);
    });

    return array_values($magazines);
}

function app_api_admin_subscribers(WP_REST_Request $req)
{
    $admin = app_api_require_admin_user($req);
    if (is_wp_error($admin))
        return $admin;

    $limit = max(1, min(200, intval($req->get_param('limit') ?: 50)));
    $offset = max(0, intval($req->get_param('offset') ?: 0));

    $include_magazines = (string) $req->get_param('include_magazines');
    $include_magazines = in_array(strtolower($include_magazines), ['1', 'true', 'yes'], true);

    // padrão: traz somente quem realmente tem revistas (bom pra teste)
    $only_with_magazines = (string) $req->get_param('only_with_magazines');
    $only_with_magazines = ($only_with_magazines === '' || in_array(strtolower($only_with_magazines), ['1', 'true', 'yes'], true));

    // padrão: traz somente quem tem entitlement (assinantes / compradores com acesso)
    $only_entitled = (string) $req->get_param('only_entitled');
    $only_entitled = ($only_entitled === '' || in_array(strtolower($only_entitled), ['1', 'true', 'yes'], true));

    // opcional: filtrar por roles (ex: customer, subscriber). Pode mandar "customer,subscriber"
    $roles = trim((string) $req->get_param('roles'));

    $uq_args = [
        'number' => $limit,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'DESC',
        'fields' => ['ID', 'user_email', 'display_name', 'user_login'],
    ];

    if ($roles !== '') {
        $role_list = array_values(array_filter(array_map('trim', explode(',', $roles))));
        if (!empty($role_list)) {
            $uq_args['role__in'] = $role_list;
        }
    }

    $q = new WP_User_Query($uq_args);
    $users = (array) $q->get_results();
    $total_users = (int) $q->get_total();

    $out = [];
    foreach ($users as $u) {
        $uid = (int) $u->ID;
        if ($uid <= 0)
            continue;

        // 1) “assinante” = tem entitlement (por pedidos + assinaturas + regras)
        $entitled_ids = function_exists('app_api_get_entitled_product_ids')
            ? app_api_get_entitled_product_ids($uid)
            : [];

        if ($only_entitled && empty($entitled_ids)) {
            continue;
        }

        // 2) monta revistas
        $magazines = function_exists('app_api_build_magazines_for_user_id')
            ? app_api_build_magazines_for_user_id($uid)
            : [];

        if ($only_with_magazines && empty($magazines)) {
            continue;
        }

        $row = [
            'user_id' => $uid,
            'email' => (string) $u->user_email,
            'name' => (string) ($u->display_name ?: $u->user_login),
            'entitled_products_count' => is_array($entitled_ids) ? count($entitled_ids) : 0,
            'magazines_count' => is_array($magazines) ? count($magazines) : 0,
        ];

        if ($include_magazines) {
            $row['magazines'] = array_values($magazines);
        } else {
            $row['magazines_sample'] = array_slice(array_map(function ($m) {
                return [
                    'product_id' => $m['product_id'] ?? null,
                    'flipbook_id' => $m['flipbook_id'] ?? null,
                    'title' => $m['title'] ?? null,
                ];
            }, is_array($magazines) ? $magazines : []), 0, 5);
        }

        $out[] = $row;
    }

    $next_offset = ($offset + $limit < $total_users) ? ($offset + $limit) : null;

    return [
        'total_users_scanned' => $total_users,
        'limit' => $limit,
        'offset' => $offset,
        'next_offset' => $next_offset,
        'returned' => count($out),
        'filters' => [
            'only_entitled' => (bool) $only_entitled,
            'only_with_magazines' => (bool) $only_with_magazines,
            'include_magazines' => (bool) $include_magazines,
            'roles' => $roles !== '' ? $roles : null,
        ],
        'users' => array_values($out),
    ];
}

function app_api_admin_user_magazines(WP_REST_Request $req)
{
    $admin = app_api_require_admin_user($req);
    if (is_wp_error($admin))
        return $admin;

    $uid = intval($req['user_id']);
    if (!$uid)
        return new WP_Error('bad_request', 'user_id inválido', ['status' => 400]);

    $u = get_user_by('id', $uid);
    if (!$u)
        return new WP_Error('not_found', 'Usuário não encontrado', ['status' => 404]);

    return [
        'user_id' => $uid,
        'email' => (string) $u->user_email,
        'name' => (string) ($u->display_name ?: $u->user_login),
        'magazines' => app_api_build_magazines_for_user_id($uid),
    ];
}

/**
 *  MAGAZINE ACCESS + VIEWER
 *  ======================= */

function app_api_magazine_access(WP_REST_Request $req)
{
    if (!function_exists('app_api_request_has_bearer_token') || !app_api_request_has_bearer_token($req)) {
        return new WP_Error('unauthorized', 'Authorization Bearer é obrigatório para acessar revistas.', ['status' => 401]);
    }

    $user = app_api_require_access_user($req);
    if (is_wp_error($user))
        return $user;

    $product_id = intval($req['product_id']);
    if (!$product_id)
        return new WP_Error('bad_request', 'product_id inválido', ['status' => 400]);

    if (!app_api_user_can_access_product($user->ID, $product_id, true)) {
        $membership_error = function_exists('app_api_build_membership_access_error')
            ? app_api_build_membership_access_error((int) $user->ID, (int) $product_id)
            : null;
        if (is_wp_error($membership_error)) {
            return $membership_error;
        }

        return new WP_Error('forbidden', 'Sem acesso a esta revista (pedido não concluído)', ['status' => 403]);
    }

    $flipbook_id = function_exists('app_api_resolve_product_flipbook_id')
        ? app_api_resolve_product_flipbook_id($product_id, intval($req->get_param('flipbook_id')))
        : 0;
    if (is_wp_error($flipbook_id)) {
        return $flipbook_id;
    }

    $ticket = wp_generate_password(32, false, false);
    set_transient('app_ticket_' . $ticket, [
        'uid' => (int) $user->ID,
        'product_id' => (int) $product_id,
        'flipbook_id' => (int) $flipbook_id,
    ], 5 * MINUTE_IN_SECONDS);

    $viewer_url = home_url('/app-magic-login?ticket=' . rawurlencode($ticket));
    if (function_exists('app_api_force_https_url')) {
        $viewer_url = app_api_force_https_url($viewer_url);
    }

    $pdf_api_url = rest_url(APP_API_NS . '/magazines/' . (int) $product_id . '/pdf?flipbook_id=' . (int) $flipbook_id);
    if (function_exists('app_api_force_https_url')) {
        $pdf_api_url = app_api_force_https_url($pdf_api_url);
    }

    $reader_ttl = function_exists('app_api_auth_reader_token_ttl_seconds')
        ? app_api_auth_reader_token_ttl_seconds()
        : (2 * HOUR_IN_SECONDS);
    $pdf_session_token = app_api_issue_viewer_token((int) $user->ID, (int) $product_id, (int) $flipbook_id, (int) $reader_ttl);
    $pdf_session_url = add_query_arg([
        'flipbook_id' => (int) $flipbook_id,
        't' => $pdf_session_token,
    ], rest_url(APP_API_NS . '/magazines/' . (int) $product_id . '/pdf'));
    if (function_exists('app_api_force_https_url')) {
        $pdf_session_url = app_api_force_https_url($pdf_session_url);
    }

    $resp = new WP_REST_Response([
        'viewer_url' => $viewer_url,
        'expires_in' => 5 * 60,
        'viewer_expires_in' => (int) $reader_ttl,
        'product_id' => (int) $product_id,
        'flipbook_id' => (int) $flipbook_id,
        'pdf_url' => null,
        'pdf_api_url' => $pdf_api_url,
        'pdf_session_url' => $pdf_session_url,
    ], 200);

    $resp->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
    $resp->header('Pragma', 'no-cache');
    $resp->header('Expires', '0');
    $resp->header('Vary', 'Authorization, Cookie, Origin');
    return $resp;
}

// ==================================
// OFFLINE LICENSE ENDPOINTS
// ==================================

function app_api_offline_public_key(WP_REST_Request $req)
{
    if (!function_exists('app_api_offline_ensure_keys') || !app_api_offline_ensure_keys()) {
        return new WP_Error('offline_unavailable', 'OpenSSL indisponível no servidor. Licença offline não está habilitada.', ['status' => 500]);
    }

    $pub = app_api_offline_get_public_key_pem();
    $kid = app_api_offline_get_kid();

    if (!$pub || !$kid) {
        return new WP_Error('offline_unavailable', 'Chaves offline não configuradas.', ['status' => 500]);
    }

    $resp = new WP_REST_Response([
        'alg' => 'RS256',
        'kid' => $kid,
        'public_key_pem' => $pub,
        'default_max_offline_days' => app_api_offline_default_days(),
    ], 200);

    // Cacheável (public key)
    $resp->header('Cache-Control', 'public, max-age=86400');
    return $resp;
}

function app_api_offline_resolve_flipbook_id(int $product_id, int $requested_flipbook_id = 0)
{
    $allowed_ids = function_exists('app_api_get_product_flipbook_ids')
        ? app_api_get_product_flipbook_ids($product_id)
        : [];

    if (!$allowed_ids) {
        $single = function_exists('app_api_get_product_flipbook_id') ? app_api_get_product_flipbook_id($product_id) : null;
        if ($single) $allowed_ids = [(int) $single];
    }

    $allowed_ids = array_values(array_unique(array_filter(array_map('intval', $allowed_ids))));
    if (!$allowed_ids) {
        return new WP_Error('not_found', 'Flipbook(s) não configurado(s) no produto', ['status' => 404]);
    }

    if ($requested_flipbook_id) {
        if (!in_array($requested_flipbook_id, $allowed_ids, true)) {
            return new WP_Error('forbidden', 'Flipbook não permitido para este produto', ['status' => 403]);
        }
        if (function_exists('app_api_is_real3d_flipbook_id') && !app_api_is_real3d_flipbook_id($requested_flipbook_id)) {
            return new WP_Error('bad_request', 'flipbook_id inválido', ['status' => 400]);
        }
        return $requested_flipbook_id;
    }

    // Seleciona o primeiro Real3D válido
    foreach ($allowed_ids as $id) {
        if (!function_exists('app_api_is_real3d_flipbook_id') || app_api_is_real3d_flipbook_id((int) $id)) {
            return (int) $id;
        }
    }

    return new WP_Error('not_found', 'Nenhum flipbook válido encontrado', ['status' => 404]);
}


if (!function_exists('app_api_offline_build_expiration_window')) {
    function app_api_offline_build_expiration_window(int $user_id, int $product_id, int $requested_days)
    {
        $requested_days = (int) $requested_days;
        if ($requested_days < 1) {
            $requested_days = 1;
        }
        if ($requested_days > 14) {
            $requested_days = 14;
        }

        $now = time();
        $requested_exp = $now + ($requested_days * DAY_IN_SECONDS);
        $effective_exp = $requested_exp;
        $access_expires_at = null;
        $limited_by_access_expiration = false;

        if (function_exists('app_api_ihc_get_product_access_state')) {
            $access = app_api_ihc_get_product_access_state($user_id, $product_id);
            if (is_array($access) && !empty($access['membership_enabled']) && !empty($access['expires_at'])) {
                $access_expires_at = (string) $access['expires_at'];
                $access_exp_ts = strtotime($access_expires_at);
                if ($access_exp_ts && $access_exp_ts < $effective_exp) {
                    $effective_exp = (int) $access_exp_ts;
                    $limited_by_access_expiration = true;
                }
            }
        }

        if ($effective_exp <= $now) {
            $membership_error = function_exists('app_api_build_membership_access_error')
                ? app_api_build_membership_access_error($user_id, $product_id)
                : null;

            return is_wp_error($membership_error)
                ? $membership_error
                : new WP_Error('access_expired', 'Seu acesso a esta revista expirou. Faça uma nova compra para continuar.', [
                    'status' => 403,
                    'product_id' => $product_id,
                    'access_expires_at' => $access_expires_at,
                ]);
        }

        $expires_in = max(0, $effective_exp - $now);
        $granted_days = max(1, (int) ceil($expires_in / DAY_IN_SECONDS));
        if ($granted_days > $requested_days) {
            $granted_days = $requested_days;
        }

        return [
            'now' => $now,
            'exp' => $effective_exp,
            'expires_in' => $expires_in,
            'requested_days' => $requested_days,
            'granted_days' => $granted_days,
            'limited_by_access_expiration' => $limited_by_access_expiration,
            'access_expires_at' => $access_expires_at,
        ];
    }
}

function app_api_magazine_offline_license(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    if (function_exists('app_api_offline_license_rate_ok') && !app_api_offline_license_rate_ok((int) $user->ID)) {
        return new WP_Error('too_many_requests', 'Muitas solicitações. Tente novamente em instantes.', ['status' => 429]);
    }

    $product_id = intval($req['product_id']);
    if (!$product_id) return new WP_Error('bad_request', 'product_id inválido', ['status' => 400]);

    // ✅ Mantém a regra de acesso: somente pedidos concluídos (e/ou assinaturas ativas)
    if (!app_api_user_can_access_product((int) $user->ID, $product_id, true)) {
        return new WP_Error('forbidden', 'Sem acesso a esta revista (pedido não concluído)', ['status' => 403]);
    }

    $body = $req->get_json_params();
    if (!is_array($body)) {
        $body = [];
    }

    $device_id = function_exists('app_api_validate_device_id')
        ? app_api_validate_device_id($body['device_id'] ?? '', true)
        : sanitize_text_field($body['device_id'] ?? '');
    if (is_wp_error($device_id)) {
        return $device_id;
    }

    $requested_flipbook_id = intval($body['flipbook_id'] ?? $req->get_param('flipbook_id') ?? 0);
    $flipbook_id = app_api_offline_resolve_flipbook_id($product_id, $requested_flipbook_id);
    if (is_wp_error($flipbook_id)) return $flipbook_id;

    $days = array_key_exists('days', $body) ? intval($body['days']) : app_api_offline_default_days();
    if ($days < 1 || $days > 14) {
        return new WP_Error('bad_request', 'days deve estar entre 1 e 14.', ['status' => 400]);
    }

    $offline_window = app_api_offline_build_expiration_window((int) $user->ID, (int) $product_id, $days);
    if (is_wp_error($offline_window)) {
        return $offline_window;
    }

    $now = (int) $offline_window['now'];
    $exp = (int) $offline_window['exp'];
    $days = (int) $offline_window['granted_days'];

    $did_hash = substr(hash('sha256', $device_id), 0, 32);

    $payload = [
        'typ' => 'offline',
        'iss' => home_url(),
        'aud' => 'app-cpaddigital',
        'sub' => (int) $user->ID,
        'pid' => (int) $product_id,
        'fbid' => (int) $flipbook_id,
        'did' => $did_hash,
        'iat' => $now,
        'nbf' => $now - 5,
        'exp' => $exp,
        'days' => $days,
        'v' => 1,
        'jti' => app_api_random_token(16),
    ];

    $license = function_exists('app_api_offline_jwt_sign_rs256')
        ? app_api_offline_jwt_sign_rs256($payload)
        : app_api_jwt_sign($payload);

    $resp = new WP_REST_Response([
        'license' => $license,
        'expires_at' => gmdate('c', $exp),
        'expires_in' => (int) $offline_window['expires_in'],
        'max_offline_days' => $days,
        'requested_offline_days' => (int) $offline_window['requested_days'],
        'limited_by_access_expiration' => !empty($offline_window['limited_by_access_expiration']),
        'access_expires_at' => $offline_window['access_expires_at'] ?: null,
        'alg' => 'RS256',
        'kid' => app_api_offline_get_kid(),
    ], 200);

    // Não cachear (user-specific)
    $resp->header('Cache-Control', 'no-store');
    return $resp;
}

function app_api_offline_renew_licenses(WP_REST_Request $req)
{
    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    if (function_exists('app_api_offline_license_rate_ok') && !app_api_offline_license_rate_ok((int) $user->ID)) {
        return new WP_Error('too_many_requests', 'Muitas solicitações. Tente novamente em instantes.', ['status' => 429]);
    }

    $body = $req->get_json_params();
    if (!is_array($body)) {
        $body = [];
    }

    $device_id = function_exists('app_api_validate_device_id')
        ? app_api_validate_device_id($body['device_id'] ?? '', true)
        : sanitize_text_field($body['device_id'] ?? '');
    if (is_wp_error($device_id)) {
        return $device_id;
    }

    // Novo (compatível): aceita "items" = [{product_id, flipbook_id?}, ...]
    // Para suportar múltiplas edições (flipbook) do MESMO produto.
    // Fallback (legado): product_ids + flipbook_map (um flipbook por produto).
    $items = [];
    $incoming_items = $body['items'] ?? null;

    if (is_array($incoming_items) && !empty($incoming_items)) {
        foreach ($incoming_items as $it) {
            if (!is_array($it)) continue;
            $pid = intval($it['product_id'] ?? 0);
            if (!$pid) continue;
            $fbid = intval($it['flipbook_id'] ?? 0);
            $items[] = ['product_id' => $pid, 'flipbook_id' => $fbid];
        }
    } else {
        $product_ids = $body['product_ids'] ?? [];
        if (!is_array($product_ids) || !$product_ids) {
            return new WP_Error('bad_request', 'Informe items (array) ou product_ids (array).', ['status' => 400]);
        }
        if (count($product_ids) > 50) {
            return new WP_Error('bad_request', 'Máximo de 50 itens por renovação.', ['status' => 400]);
        }

        $product_ids = array_slice($product_ids, 0, 50);
        $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));

        foreach ($product_ids as $pid) {
            if (!$pid) continue;
            $requested_flipbook_id = intval($body['flipbook_map'][(string) $pid] ?? 0);
            $items[] = ['product_id' => (int) $pid, 'flipbook_id' => (int) $requested_flipbook_id];
        }
    }

    // limites de segurança (items)
    if (count($items) > 50) {
        return new WP_Error('bad_request', 'Máximo de 50 itens por renovação.', ['status' => 400]);
    }
    $items = array_slice($items, 0, 50);

    // dedupe por (product_id, flipbook_id)
    $uniq = [];
    foreach ($items as $it) {
        $pid = intval($it['product_id'] ?? 0);
        if (!$pid) continue;
        $fbid = intval($it['flipbook_id'] ?? 0);
        $k = $pid . ':' . $fbid;
        $uniq[$k] = ['product_id' => $pid, 'flipbook_id' => $fbid];
    }
    $items = array_values($uniq);

    $days = array_key_exists('days', $body) ? intval($body['days']) : app_api_offline_default_days();
    if ($days < 1 || $days > 14) {
        return new WP_Error('bad_request', 'days deve estar entre 1 e 14.', ['status' => 400]);
    }

    $requested_days = $days;
    $did_hash = substr(hash('sha256', $device_id), 0, 32);

    $results = [];

    foreach ($items as $it) {
        $pid = intval($it['product_id'] ?? 0);
        if (!$pid) continue;

        $requested_flipbook_id = intval($it['flipbook_id'] ?? 0);

        if (!app_api_user_can_access_product((int) $user->ID, (int) $pid, true)) {
            $membership_error = function_exists('app_api_build_membership_access_error')
                ? app_api_build_membership_access_error((int) $user->ID, (int) $pid)
                : null;

            $results[] = [
                'product_id' => (int) $pid,
                'ok' => false,
                // ✅ NOVO: código estável para o app identificar revogação e limpar downloads
                'code' => is_wp_error($membership_error) ? 'access_expired' : 'no_entitlement',
                'reason' => is_wp_error($membership_error) ? $membership_error->get_error_message() : 'Sem acesso (pedido não concluído)',
            ];
            continue;
        }

        $flipbook_id = app_api_offline_resolve_flipbook_id((int) $pid, $requested_flipbook_id);
        if (is_wp_error($flipbook_id)) {
            $results[] = [
                'product_id' => (int) $pid,
                'ok' => false,
                'reason' => $flipbook_id->get_error_message(),
            ];
            continue;
        }

        $offline_window = app_api_offline_build_expiration_window((int) $user->ID, (int) $pid, $requested_days);
        if (is_wp_error($offline_window)) {
            $results[] = [
                'product_id' => (int) $pid,
                'ok' => false,
                'code' => 'access_expired',
                'reason' => $offline_window->get_error_message(),
            ];
            continue;
        }

        $now = (int) $offline_window['now'];
        $exp = (int) $offline_window['exp'];
        $days = (int) $offline_window['granted_days'];

        $payload = [
            'typ' => 'offline',
            'iss' => home_url(),
            'aud' => 'app-cpaddigital',
            'sub' => (int) $user->ID,
            'pid' => (int) $pid,
            'fbid' => (int) $flipbook_id,
            'did' => $did_hash,
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $exp,
            'days' => $days,
            'v' => 1,
            'jti' => app_api_random_token(16),
        ];

        $license = function_exists('app_api_offline_jwt_sign_rs256')
            ? app_api_offline_jwt_sign_rs256($payload)
            : app_api_jwt_sign($payload);

        $results[] = [
            'product_id' => (int) $pid,
            'ok' => true,
            'license' => $license,
            'expires_at' => gmdate('c', $exp),
            'expires_in' => (int) $offline_window['expires_in'],
            'requested_offline_days' => (int) $offline_window['requested_days'],
            'max_offline_days' => $days,
            'limited_by_access_expiration' => !empty($offline_window['limited_by_access_expiration']),
            'access_expires_at' => $offline_window['access_expires_at'] ?: null,
            'flipbook_id' => (int) $flipbook_id,
            'kid' => app_api_offline_get_kid(),
            'alg' => 'RS256',
        ];
    }

    $resp = new WP_REST_Response([
        'results' => $results,
        'max_offline_days' => $requested_days,
    ], 200);

    $resp->header('Cache-Control', 'no-store');
    return $resp;
}

// ==================================
// PDF streaming (native PDF viewer) (native PDF viewer)
// ==================================

if (!function_exists('app_api_stream_file_with_range')) {
    /**
     * Faz streaming de um arquivo local com suporte a HTTP Range.
     * Importante para PDFs grandes (o leitor faz seek/paginação).
     */
    function app_api_stream_file_with_range(string $file_path, string $mime = 'application/pdf')
    {
        // ✅ CORS (necessário pois este endpoint faz streaming e finaliza com exit)
        if (function_exists('app_api_send_cors_headers')) {
            app_api_send_cors_headers();
        }

        if (!is_file($file_path) || !is_readable($file_path)) {
            status_header(404);
            exit;
        }

        // Evita problemas com output buffering
        if (function_exists('wp_ob_end_flush_all')) {
            wp_ob_end_flush_all();
        } else {
            while (ob_get_level()) {
                @ob_end_clean();
            }
        }

        @set_time_limit(0);

        $size = filesize($file_path);
        $start = 0;
        $end = $size - 1;

        // Range: bytes=start-end
        $range_header = $_SERVER['HTTP_RANGE'] ?? '';
        if ($range_header && preg_match('/bytes\s*=\s*(\d*)\s*-\s*(\d*)/i', $range_header, $m)) {
            if ($m[1] !== '') {
                $start = (int) $m[1];
            }
            if ($m[2] !== '') {
                $end = (int) $m[2];
            }

            if ($start < 0) $start = 0;
            if ($end > $size - 1) $end = $size - 1;
            if ($start > $end) {
                // Range inválido
                status_header(416);
                header("Content-Range: bytes */$size");
                exit;
            }

            status_header(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        } else {
            status_header(200);
        }

        $length = ($end - $start) + 1;

        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $length);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        if (function_exists('app_api_send_private_no_store_headers')) {
            app_api_send_private_no_store_headers();
        }

        $fp = fopen($file_path, 'rb');
        if (!$fp) {
            status_header(500);
            exit;
        }

        fseek($fp, $start);

        $chunk = 1024 * 64; // 64KB
        $bytes_sent = 0;
        while (!feof($fp) && $bytes_sent < $length) {
            $remaining = $length - $bytes_sent;
            $read = ($remaining < $chunk) ? $remaining : $chunk;
            $buffer = fread($fp, $read);
            if ($buffer === false) {
                break;
            }
            echo $buffer;
            $bytes_sent += strlen($buffer);
            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
            @flush();
        }
        fclose($fp);
        exit;
    }
}

function app_api_magazine_pdf(WP_REST_Request $req)
{
    $product_id = intval($req['product_id']);
    if (!$product_id)
        return new WP_Error('bad_request', 'product_id inválido', ['status' => 400]);

    $requested_flipbook_id = intval($req->get_param('flipbook_id'));
    $viewer_token = sanitize_text_field((string) $req->get_param('t'));

    $user = null;
    $user_id = 0;

    if ($viewer_token !== '') {
        $payload = app_api_jwt_verify($viewer_token);
        if (is_wp_error($payload)) {
            return $payload;
        }

        if (($payload['typ'] ?? '') !== 'viewer' || ($payload['scp'] ?? '') !== 'flipbook_view') {
            return new WP_Error('unauthorized', 'Token viewer inválido', ['status' => 401]);
        }

        $user_id = intval($payload['sub'] ?? 0);
        $pid_claim = intval($payload['pid'] ?? 0);
        $fbid_claim = intval($payload['fbid'] ?? 0);

        if (!$user_id || $pid_claim !== $product_id) {
            return new WP_Error('unauthorized', 'Token não corresponde ao conteúdo', ['status' => 401]);
        }

        if ($requested_flipbook_id > 0 && $fbid_claim > 0 && $requested_flipbook_id !== $fbid_claim) {
            return new WP_Error('unauthorized', 'Token não corresponde ao flipbook solicitado', ['status' => 401]);
        }

        if (!app_api_user_can_access_product($user_id, $product_id, true)) {
            $membership_error = function_exists('app_api_build_membership_access_error')
                ? app_api_build_membership_access_error((int) $user_id, (int) $product_id)
                : null;
            if (is_wp_error($membership_error)) {
                return $membership_error;
            }

            return new WP_Error('forbidden', 'Sem acesso a esta revista (pedido não concluído)', ['status' => 403]);
        }
    } else {
        if (!function_exists('app_api_request_has_bearer_token') || !app_api_request_has_bearer_token($req)) {
            return new WP_Error('unauthorized', 'Authorization Bearer é obrigatório para abrir o PDF.', ['status' => 401]);
        }

        $user = app_api_require_access_user($req);
        if (is_wp_error($user))
            return $user;

        $user_id = (int) $user->ID;

        if (!app_api_user_can_access_product($user_id, $product_id, true)) {
            $membership_error = function_exists('app_api_build_membership_access_error')
                ? app_api_build_membership_access_error((int) $user_id, (int) $product_id)
                : null;
            if (is_wp_error($membership_error)) {
                return $membership_error;
            }

            return new WP_Error('forbidden', 'Sem acesso a esta revista (pedido não concluído)', ['status' => 403]);
        }
    }

    $flipbook_id = function_exists('app_api_resolve_product_flipbook_id')
        ? app_api_resolve_product_flipbook_id($product_id, $requested_flipbook_id)
        : 0;
    if (is_wp_error($flipbook_id)) {
        return $flipbook_id;
    }

    if ($viewer_token !== '') {
        $payload = app_api_jwt_verify($viewer_token);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $fbid_claim = intval($payload['fbid'] ?? 0);
        if ($fbid_claim > 0 && (int) $flipbook_id !== $fbid_claim) {
            return new WP_Error('unauthorized', 'Token não corresponde ao flipbook resolvido', ['status' => 401]);
        }
    }

    $pdf_info = function_exists('app_api_real3d_get_flipbook_pdf_info')
        ? app_api_real3d_get_flipbook_pdf_info((int) $flipbook_id)
        : null;

    if (!is_array($pdf_info) || empty($pdf_info['url'])) {
        return new WP_Error('not_found', 'PDF não encontrado para este flipbook', ['status' => 404]);
    }

    $pdf_url = (string) $pdf_info['url'];
    if (function_exists('app_api_force_https_url')) {
        $pdf_url = app_api_force_https_url($pdf_url);
    }

    $path = isset($pdf_info['path']) && is_string($pdf_info['path']) ? $pdf_info['path'] : null;

    if ((!$path || !file_exists($path)) && function_exists('app_api_map_upload_url_to_path')) {
        $mapped = app_api_map_upload_url_to_path($pdf_url);
        if ($mapped && file_exists($mapped)) {
            $path = $mapped;
        }
    }

    if (function_exists('app_api_send_cors_headers')) {
        app_api_send_cors_headers();
    }
    if (function_exists('app_api_send_private_no_store_headers')) {
        app_api_send_private_no_store_headers();
    }

    if ($path && file_exists($path)) {
        app_api_stream_file_with_range($path, 'application/pdf');
    }

    return new WP_Error('not_found', 'PDF protegido indisponível para streaming seguro.', ['status' => 404]);
}

function app_api_viewer_flipbook(WP_REST_Request $req)
{
    $t = sanitize_text_field($req->get_param('t') ?? '');
    if (!$t)
        return new WP_Error('unauthorized', 'Sem token', ['status' => 401]);

    $payload = app_api_jwt_verify($t);
    if (is_wp_error($payload))
        return $payload;

    if (($payload['typ'] ?? '') !== 'viewer' || ($payload['scp'] ?? '') !== 'flipbook_view') {
        return new WP_Error('unauthorized', 'Token viewer inválido', ['status' => 401]);
    }

    $user_id = intval($payload['sub'] ?? 0);
    $product_id = intval($req['product_id']);
    $pid_claim = intval($payload['pid'] ?? 0);

    if (!$user_id || !$product_id || $pid_claim !== $product_id) {
        return new WP_Error('unauthorized', 'Token não corresponde ao conteúdo', ['status' => 401]);
    }

    // ✅ AJUSTE: validar acesso apenas por pedido concluído
    if (!app_api_user_can_access_product($user_id, $product_id, true)) {
        $membership_error = function_exists('app_api_build_membership_access_error')
            ? app_api_build_membership_access_error((int) $user_id, (int) $product_id)
            : null;
        if (is_wp_error($membership_error)) {
            return $membership_error;
        }

        return new WP_Error('forbidden', 'Sem acesso (pedido não concluído)', ['status' => 403]);
    }

    // ✅ AJUSTE PRINCIPAL:
    // se o token traz flipbook_id (pid do conteúdo específico), usa ele
    $flipbook_id = intval($payload['fbid'] ?? 0);

    // fallback antigo: pega o 1º do produto
    if (!$flipbook_id) {
        $flipbook_id = function_exists('app_api_get_product_flipbook_id')
            ? (int) app_api_get_product_flipbook_id($product_id)
            : 0;
    }

    if (!$flipbook_id) {
        return new WP_Error('not_found', 'Flipbook não configurado', ['status' => 404]);
    }

    // ✅ garante que o flipbook pertence ao produto
    $allowed_ids = function_exists('app_api_get_product_flipbook_ids') ? app_api_get_product_flipbook_ids($product_id) : [];
    if (!$allowed_ids) {
        $single = function_exists('app_api_get_product_flipbook_id') ? app_api_get_product_flipbook_id($product_id) : null;
        if ($single)
            $allowed_ids = [(int) $single];
    }
    $allowed_ids = array_values(array_unique(array_filter(array_map('intval', $allowed_ids))));
    if ($allowed_ids && !in_array($flipbook_id, $allowed_ids, true)) {
        return new WP_Error('forbidden', 'Flipbook não permitido para este produto', ['status' => 403]);
    }

    if (function_exists('app_api_is_real3d_flipbook_id') && !app_api_is_real3d_flipbook_id($flipbook_id)) {
        return new WP_Error('bad_request', 'flipbook_id inválido', ['status' => 400]);
    }

    $shortcode = str_replace('{ID}', (string) $flipbook_id, app_api_flipbook_shortcode_tpl());
    $content = do_shortcode($shortcode);

    $html = '<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title>Flipbook</title>
<style>
  html, body { margin:0; padding:0; height:100%; background:#000; }
  #wrap { height:100%; }
</style>
</head>
<body>
<div id="wrap">' . $content . '</div>
</body>
</html>';

    $resp = new WP_REST_Response($html, 200);
    $resp->header('Content-Type', 'text/html; charset=UTF-8');
    return $resp;
}

function app_api_admin_user_orders(WP_REST_Request $req)
{
    $admin = app_api_require_admin_user($req);
    if (is_wp_error($admin))
        return $admin;

    $user_id = intval($req['user_id']);
    if (!$user_id)
        return new WP_Error('bad_request', 'user_id inválido', ['status' => 400]);

    if (!function_exists('wc_get_orders')) {
        return new WP_Error('no_woocommerce', 'WooCommerce não disponível', ['status' => 500]);
    }

    $limit = max(1, min(200, intval($req->get_param('limit') ?: 20)));
    $offset = max(0, intval($req->get_param('offset') ?: 0));

    $include_items = in_array(strtolower((string) $req->get_param('include_items')), ['1', 'true', 'yes'], true);
    $include_meta = in_array(strtolower((string) $req->get_param('include_meta')), ['1', 'true', 'yes'], true);
    $include_attr = in_array(strtolower((string) $req->get_param('include_attribution')), ['1', 'true', 'yes'], true);

    // Paginação nativa do wc_get_orders (evita WC_Order_Query direto)
    $qr = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => $limit,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
        'paginate' => true,
    ]);

    $orders = [];
    $total = null;

    if (is_object($qr) && isset($qr->orders)) {
        $orders = $qr->orders;
        $total = isset($qr->total) ? (int) $qr->total : null;
    } elseif (is_array($qr)) {
        $orders = $qr;
    }

    $out = [];
    foreach ($orders as $order) {
        $out[] = app_api_wc_order_to_array($order, [
            'include_items' => $include_items,
            'include_meta' => $include_meta,
            'include_attribution' => $include_attr,
        ]);
    }

    $next_offset = ($total !== null && ($offset + $limit) < $total) ? ($offset + $limit) : null;

    return [
        'user_id' => $user_id,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'next_offset' => $next_offset,
        'orders' => array_values($out),
    ];
}

function app_api_admin_order_details(WP_REST_Request $req)
{
    $admin = app_api_require_admin_user($req);
    if (is_wp_error($admin))
        return $admin;

    $order_id = intval($req['order_id']);
    if (!$order_id)
        return new WP_Error('bad_request', 'order_id inválido', ['status' => 400]);

    if (!function_exists('wc_get_order')) {
        return new WP_Error('no_woocommerce', 'WooCommerce não disponível', ['status' => 500]);
    }

    $order = wc_get_order($order_id);
    if (!$order)
        return new WP_Error('not_found', 'Pedido não encontrado', ['status' => 404]);

    // Por padrão, liga itens + attribution (igual tela)
    $include_items = strtolower((string) $req->get_param('include_items'));
    $include_items = ($include_items === '' || in_array($include_items, ['1', 'true', 'yes'], true));

    $include_attr = strtolower((string) $req->get_param('include_attribution'));
    $include_attr = ($include_attr === '' || in_array($include_attr, ['1', 'true', 'yes'], true));

    $include_meta = in_array(strtolower((string) $req->get_param('include_meta')), ['1', 'true', 'yes'], true);

    return app_api_wc_order_to_array($order, [
        'include_items' => $include_items,
        'include_meta' => $include_meta,
        'include_attribution' => $include_attr,
    ]);
}

/**
 *  STORE (PWA + WooCommerce)
 *  ======================= */

function app_api_store_ensure_woocommerce()
{
    if (!function_exists('wc_get_product') || !function_exists('WC')) {
        return new WP_Error('no_woocommerce', 'WooCommerce não disponível', ['status' => 500]);
    }

    if (function_exists('wc_load_cart')) {
        wc_load_cart();
    }

    return true;
}

function app_api_store_money_value($raw): ?float
{
    if ($raw === '' || $raw === null) {
        return null;
    }

    if (!is_numeric($raw)) {
        return null;
    }

    return (float) $raw;
}


function app_api_store_term_to_array($term): ?array
{
    if (!$term || !is_object($term)) {
        return null;
    }

    return [
        'id' => (int) $term->term_id,
        'name' => (string) $term->name,
        'slug' => (string) $term->slug,
        'count' => isset($term->count) ? (int) $term->count : 0,
    ];
}

function app_api_store_get_product_categories(int $product_id): array
{
    $terms = get_the_terms($product_id, 'product_cat');
    if (!is_array($terms) || !$terms) {
        return [];
    }

    usort($terms, function ($a, $b) {
        return strcasecmp((string) $a->name, (string) $b->name);
    });

    $items = [];
    foreach ($terms as $term) {
        $mapped = app_api_store_term_to_array($term);
        if ($mapped) {
            $items[] = $mapped;
        }
    }

    return array_values($items);
}

function app_api_store_get_categories_for_products(array $product_ids): array
{
    $product_ids = array_values(array_filter(array_map('intval', $product_ids)));
    if (!$product_ids) {
        return [];
    }

    $terms = wp_get_object_terms($product_ids, 'product_cat', [
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (is_wp_error($terms) || !is_array($terms)) {
        return [];
    }

    $items = [];
    foreach ($terms as $term) {
        $mapped = app_api_store_term_to_array($term);
        if ($mapped) {
            $items[$mapped['id']] = $mapped;
        }
    }

    return array_values($items);
}

function app_api_store_get_optional_access_user(WP_REST_Request $req)
{
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if ($current_user instanceof WP_User && (int) $current_user->ID > 0) {
            return $current_user;
        }
    }

    $token = app_api_get_bearer_token($req);
    if (!$token) {
        return null;
    }

    $payload = app_api_jwt_verify($token);
    if (is_wp_error($payload) || (($payload['typ'] ?? '') !== 'access')) {
        return null;
    }

    $user_id = (int) ($payload['sub'] ?? 0);
    if ($user_id <= 0) {
        return null;
    }

    $user = get_user_by('id', $user_id);
    return $user ?: null;
}

function app_api_store_normalize_document(string $value): string
{
    return preg_replace('/\D+/', '', (string) $value);
}

function app_api_store_find_user_by_document_or_email(string $document, string $email = '')
{
    global $wpdb;

    $document = app_api_store_normalize_document($document);

    if ($email !== '' && is_email($email)) {
        $by_email = get_user_by('email', $email);
        if ($by_email instanceof WP_User) {
            return $by_email;
        }
    }

    if ($document !== '') {
        $meta_keys = [ 'billing_cpf', '_billing_cpf', 'billing_cnpj', '_billing_cnpj' ];
        $placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        $params = array_merge($meta_keys, [ $document ]);
        $sql = "
            SELECT user_id
            FROM {$wpdb->usermeta}
            WHERE meta_key IN ($placeholders)
              AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(meta_value, ''), '.', ''), '-', ''), '/', ''), ' ', ''), ',', '') = %s
            LIMIT 1
        ";
        $user_id = (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        if ($user_id > 0) {
            $by_document = get_user_by('id', $user_id);
            if ($by_document instanceof WP_User) {
                return $by_document;
            }
        }
    }

    return null;
}

function app_api_store_build_customer_fields_from_user_id(int $user_id): array
{
    if ($user_id <= 0) {
        return [];
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }

    $get = function (string $key, string $default = '') use ($user_id) {
        $value = get_user_meta($user_id, $key, true);
        return is_scalar($value) ? (string) $value : $default;
    };

    return [
        'billing_first_name' => $get('billing_first_name', (string) $user->first_name),
        'billing_last_name' => $get('billing_last_name', (string) $user->last_name),
        'billing_company' => $get('billing_company'),
        'billing_country' => $get('billing_country', 'BR'),
        'billing_address_1' => $get('billing_address_1'),
        'billing_address_2' => $get('billing_address_2'),
        'billing_city' => $get('billing_city'),
        'billing_state' => $get('billing_state'),
        'billing_postcode' => $get('billing_postcode'),
        'billing_phone' => $get('billing_phone'),
        'billing_email' => $get('billing_email', (string) $user->user_email),
        'billing_cpf' => $get('billing_cpf', $get('_billing_cpf')),
        'shipping_first_name' => $get('shipping_first_name', (string) $user->first_name),
        'shipping_last_name' => $get('shipping_last_name', (string) $user->last_name),
        'shipping_company' => $get('shipping_company'),
        'shipping_country' => $get('shipping_country', $get('billing_country', 'BR')),
        'shipping_address_1' => $get('shipping_address_1', $get('billing_address_1')),
        'shipping_address_2' => $get('shipping_address_2', $get('billing_address_2')),
        'shipping_city' => $get('shipping_city', $get('billing_city')),
        'shipping_state' => $get('shipping_state', $get('billing_state')),
        'shipping_postcode' => $get('shipping_postcode', $get('billing_postcode')),
    ];
}

function app_api_store_get_gateway_meta($gateway): array
{
    if (!$gateway || !is_object($gateway) || empty($gateway->id)) {
        return [];
    }

    $gateway_id = (string) $gateway->id;
    $meta = [
        'kind' => '',
        'brands' => [],
        'field_prefix' => '',
        'requires_installments' => false,
        'show_inline_card_form' => false,
    ];

    $brand_settings = [];
    if ($gateway_id === 'loja5_woo_novo_erede') {
        $brand_settings = (array) ($gateway->settings['meios'] ?? []);
        $meta['kind'] = 'erede_credit';
        $meta['field_prefix'] = 'erede_api';
        $meta['requires_installments'] = true;
        $meta['show_inline_card_form'] = true;
    } elseif ($gateway_id === 'loja5_woo_novo_erede_debito') {
        $brand_settings = (array) ($gateway->settings['meios'] ?? []);
        $meta['kind'] = 'erede_debit';
        $meta['field_prefix'] = 'erede_api_debito';
        $meta['requires_installments'] = true;
        $meta['show_inline_card_form'] = true;
    } elseif ($gateway_id === 'loja5_woo_novo_erede_pix') {
        $meta['kind'] = 'erede_pix';
    }

    foreach ($brand_settings as $brand) {
        $brand = strtolower(trim((string) $brand));
        if ($brand === '') {
            continue;
        }
        $meta['brands'][] = [
            'value' => $brand,
            'label' => ucfirst($brand),
            'icon' => plugins_url('/loja5-woo-novo-erede/images/' . $brand . '.png'),
        ];
    }

    return $meta;
}

function app_api_store_currency_label(float $value, string $currency = 'BRL'): string
{
    return wp_strip_all_tags(html_entity_decode(wc_price($value, ['currency' => $currency])));
}

function app_api_store_build_erede_installments(string $gateway_id, string $brand, float $total, string $currency = 'BRL'): array
{
    $brand = strtolower(trim($brand));
    if ($total <= 0 || $brand === '') {
        return [];
    }

    $is_debit = $gateway_id === 'loja5_woo_novo_erede_debito';
    $config = get_option($is_debit ? 'woocommerce_loja5_woo_novo_erede_debito_settings' : 'woocommerce_loja5_woo_novo_erede_settings');
    if (!is_array($config)) {
        return [];
    }

    $chave = trim((string) ($config['afiliacao'] ?? ''));
    $minimo = (float) ($config['minimo'] ?? 5.0);
    if ($minimo <= 0) {
        $minimo = 5.0;
    }
    $divmax = max(1, (int) ($config['div'] ?? 1));
    $divsem = max(1, (int) ($config['sem'] ?? 1));
    $juros_raw = (string) ($config['juros'] ?? '0');
    $tipo_juros = (string) ($config['tipo_juros'] ?? 'price');
    $desconto = $is_debit ? 0.0 : (defined('LOJA5_NOVO_EREDE_DESC_CRED_AV') ? (float) LOJA5_NOVO_EREDE_DESC_CRED_AV : 0.0);
    $pcom = isset($config['parcelamento']) && $config['parcelamento'] === 'operadora' ? 3 : 2;

    $split = (int) floor($total / $minimo);
    if ($split >= $divmax) {
        $div = $divmax;
    } elseif ($split < $divmax) {
        $div = max(1, $split);
    } else {
        $div = 1;
    }

    if ($is_debit || in_array($brand, ['discover', 'jcb'], true)) {
        $div = 1;
    }

    $parts = explode('|', $juros_raw);
    $juros_p = [];
    if (count($parts) === 1) {
        for ($i = 2; $i <= 12; $i++) {
            $juros_p[$i] = (float) $juros_raw;
        }
    } else {
        for ($i = 2; $i <= 12; $i++) {
            $juros_p[$i] = isset($parts[$i - 1]) ? (float) $parts[$i - 1] : 0.0;
        }
    }

    $make_value = function (int $parcelas, int $tipo, float $valor_total) use ($brand, $chave, $total) {
        $valor_total = (float) number_format($valor_total, 2, '.', '');
        return base64_encode($parcelas . '|' . $tipo . '|' . number_format($valor_total, 2, '.', '') . '|' . base64_encode($brand) . '|' . base64_encode(number_format($total, 2, '.', '')) . '|' . md5(number_format($valor_total, 2, '.', '') . $chave));
    };

    $options = [];
    $avista_total = $total;
    if ($desconto > 0) {
        $desconto_valor = ($total / 100) * $desconto;
        $avista_total = (float) number_format($total - $desconto_valor, 2, '.', '');
        $options[] = [
            'value' => $make_value(1, 1, $avista_total),
            'label' => 'À vista por ' . app_api_store_currency_label($avista_total, $currency) . ' (já com ' . $desconto . '% off)',
        ];
    } else {
        $options[] = [
            'value' => $make_value(1, 1, $avista_total),
            'label' => 'À vista por ' . app_api_store_currency_label($avista_total, $currency),
        ];
    }

    if (!$is_debit && $div >= 2) {
        for ($i = 2; $i <= $div; $i++) {
            if ($i <= $divsem) {
                $parcela = round($total / $i, 2);
                $options[] = [
                    'value' => $make_value($i, 2, $total),
                    'label' => $i . 'x de ' . app_api_store_currency_label($parcela, $currency) . ' sem juros (' . app_api_store_currency_label($total, $currency) . ')',
                ];
                continue;
            }

            $juros_par = isset($juros_p[$i]) ? (float) $juros_p[$i] : (float) $juros_raw;
            if ($juros_par == 0.0) {
                $juros_par = 1.99;
            }

            if ($tipo_juros === 'composto') {
                $parcela_com_juros = calcular_juros_erede_api_composto($total, $juros_par, $i);
            } elseif ($tipo_juros === 'simples') {
                $parcela_com_juros = calcular_juros_erede_api_simples($total, $juros_par, $i);
            } elseif ($tipo_juros === 'porcentagem') {
                $parcela_com_juros = calcular_juros_erede_api_porcentagem($total, $juros_par, $i);
            } else {
                $parcela_com_juros = calcular_juros_erede_api_price($total, $juros_par, $i);
            }

            $valor_total = (float) number_format($parcela_com_juros * $i, 2, '.', '');
            $options[] = [
                'value' => $make_value($i, $pcom, $valor_total),
                'label' => $i . 'x de ' . app_api_store_currency_label($parcela_com_juros, $currency) . ' com juros (' . app_api_store_currency_label($valor_total, $currency) . ')',
            ];
        }
    }

    return $options;
}

function app_api_store_product_to_array($product): ?array
{
    if (!$product || !is_object($product)) {
        return null;
    }

    $image_ids = method_exists($product, 'get_gallery_image_ids') ? (array) $product->get_gallery_image_ids() : [];
    $main_image_id = method_exists($product, 'get_image_id') ? (int) $product->get_image_id() : 0;
    if ($main_image_id > 0) {
        array_unshift($image_ids, $main_image_id);
    }
    $image_ids = array_values(array_unique(array_filter(array_map('intval', $image_ids))));

    $images = [];
    foreach ($image_ids as $image_id) {
        $src = wp_get_attachment_image_url($image_id, 'large');
        if (!$src) {
            continue;
        }
        $images[] = [
            'id' => $image_id,
            'src' => $src,
            'thumbnail' => wp_get_attachment_image_url($image_id, 'medium'),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
        ];
    }

    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL';
    $price = app_api_store_money_value(method_exists($product, 'get_price') ? $product->get_price() : null);
    $regular_price = app_api_store_money_value(method_exists($product, 'get_regular_price') ? $product->get_regular_price() : null);
    $sale_price = app_api_store_money_value(method_exists($product, 'get_sale_price') ? $product->get_sale_price() : null);

    $short_description = method_exists($product, 'get_short_description') ? (string) $product->get_short_description() : '';
    $description = method_exists($product, 'get_description') ? (string) $product->get_description() : '';

    return [
        'id' => (int) $product->get_id(),
        'name' => (string) $product->get_name(),
        'slug' => (string) $product->get_slug(),
        'sku' => method_exists($product, 'get_sku') ? (string) $product->get_sku() : '',
        'type' => method_exists($product, 'get_type') ? (string) $product->get_type() : '',
        'permalink' => method_exists($product, 'get_permalink') ? (string) $product->get_permalink() : '',
        'short_description' => wp_kses_post($short_description),
        'description' => wp_kses_post($description),
        'price' => $price,
        'regular_price' => $regular_price,
        'sale_price' => $sale_price,
        'price_html' => method_exists($product, 'get_price_html') ? wp_kses_post($product->get_price_html()) : '',
        'currency' => $currency,
        'is_purchasable' => method_exists($product, 'is_purchasable') ? (bool) $product->is_purchasable() : false,
        'is_in_stock' => method_exists($product, 'is_in_stock') ? (bool) $product->is_in_stock() : false,
        'stock_status' => method_exists($product, 'get_stock_status') ? (string) $product->get_stock_status() : '',
        'is_virtual' => method_exists($product, 'is_virtual') ? (bool) $product->is_virtual() : false,
        'is_downloadable' => method_exists($product, 'is_downloadable') ? (bool) $product->is_downloadable() : false,
        'categories' => app_api_store_get_product_categories((int) $product->get_id()),
        'images' => $images,
    ];
}

function app_api_store_products(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    $search = sanitize_text_field((string) $req->get_param('search'));
    $category = sanitize_title((string) $req->get_param('category'));
    $include_raw = $req->get_param('include');
    $include = [];

    if (is_array($include_raw)) {
        $include = array_map('intval', $include_raw);
    } elseif (is_string($include_raw) && trim($include_raw) !== '') {
        $include = array_map('intval', array_filter(array_map('trim', explode(',', $include_raw))));
    }
    $include = array_values(array_filter($include));

    $limit = max(1, min(240, (int) $req->get_param('limit') ?: 48));
    $args = [
        'status' => 'publish',
        'limit' => $include ? max(count($include), 1) : $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
        'catalog_visibility' => 'visible',
    ];

    if ($search !== '') {
        $args['search'] = $search;
    }
    if ($include) {
        $args['include'] = $include;
        $args['orderby'] = 'include';
    }
    if ($category !== '') {
        $args['category'] = [$category];
    }

    $products = function_exists('wc_get_products') ? wc_get_products($args) : [];
    $items = [];
    $product_ids = [];
    foreach ((array) $products as $product) {
        $item = app_api_store_product_to_array($product);
        if ($item) {
            $items[] = $item;
            $product_ids[] = (int) $item['id'];
        }
    }

    return [
        'items' => array_values($items),
        'count' => count($items),
        'categories' => app_api_store_get_categories_for_products($product_ids),
    ];
}

function app_api_store_product(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    $product_id = (int) $req['product_id'];
    if ($product_id <= 0) {
        return new WP_Error('bad_request', 'product_id inválido', ['status' => 400]);
    }

    $product = wc_get_product($product_id);
    if (!$product || $product->get_status() !== 'publish') {
        return new WP_Error('not_found', 'Produto não encontrado', ['status' => 404]);
    }

    $item = app_api_store_product_to_array($product);
    if (!$item) {
        return new WP_Error('not_found', 'Produto não encontrado', ['status' => 404]);
    }

    return $item;
}

function app_api_store_checkout_field_to_array(string $key, array $field): array
{
    $options = [];
    if (isset($field['options']) && is_array($field['options'])) {
        foreach ($field['options'] as $option_value => $option_label) {
            $options[] = [
                'value' => (string) $option_value,
                'label' => (string) $option_label,
            ];
        }
    }

    return [
        'key' => $key,
        'type' => isset($field['type']) ? (string) $field['type'] : 'text',
        'label' => isset($field['label']) ? wp_strip_all_tags((string) $field['label']) : $key,
        'placeholder' => isset($field['placeholder']) ? wp_strip_all_tags((string) $field['placeholder']) : '',
        'required' => !empty($field['required']),
        'class' => isset($field['class']) && is_array($field['class']) ? array_values(array_map('strval', $field['class'])) : [],
        'input_class' => isset($field['input_class']) && is_array($field['input_class']) ? array_values(array_map('strval', $field['input_class'])) : [],
        'priority' => isset($field['priority']) ? (int) $field['priority'] : 0,
        'autocomplete' => isset($field['autocomplete']) ? (string) $field['autocomplete'] : '',
        'default' => $field['default'] ?? '',
        'options' => $options,
    ];
}

function app_api_store_checkout_group_to_array(array $fields): array
{
    $out = [];
    foreach ($fields as $key => $field) {
        if (!is_array($field)) {
            continue;
        }
        $out[] = app_api_store_checkout_field_to_array((string) $key, $field);
    }

    usort($out, function ($a, $b) {
        $pa = (int) ($a['priority'] ?? 0);
        $pb = (int) ($b['priority'] ?? 0);
        if ($pa === $pb) {
            return strcmp((string) $a['key'], (string) $b['key']);
        }
        return $pa <=> $pb;
    });

    return $out;
}

function app_api_store_gateway_to_array($gateway): array
{
    $fields_html = '';
    if (method_exists($gateway, 'has_fields') && $gateway->has_fields() && method_exists($gateway, 'payment_fields')) {
        ob_start();
        $gateway->payment_fields();
        $fields_html = (string) ob_get_clean();
    }

    return [
        'id' => (string) $gateway->id,
        'title' => wp_strip_all_tags((string) $gateway->get_title()),
        'description' => wp_kses_post((string) $gateway->get_description()),
        'icon_html' => method_exists($gateway, 'get_icon') ? (string) $gateway->get_icon() : '',
        'order_button_text' => method_exists($gateway, 'get_order_button_text') ? wp_strip_all_tags((string) $gateway->get_order_button_text()) : '',
        'has_fields' => method_exists($gateway, 'has_fields') ? (bool) $gateway->has_fields() : false,
        'fields_html' => (string) $fields_html,
        'meta' => app_api_store_get_gateway_meta($gateway),
    ];
}

function app_api_store_checkout_context(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    $checkout = WC()->checkout();
    $fields = $checkout ? (array) $checkout->get_checkout_fields() : [];
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();

    $payment_methods = [];
    foreach ((array) $gateways as $gateway) {
        if (!$gateway || !is_object($gateway)) {
            continue;
        }
        $payment_methods[] = app_api_store_gateway_to_array($gateway);
    }

    $terms_page_id = function_exists('wc_terms_and_conditions_page_id') ? (int) wc_terms_and_conditions_page_id() : 0;
    $terms_required = (bool) (!empty($terms_page_id));
    $user = app_api_store_get_optional_access_user($req);
    $prefill = $user ? app_api_store_build_customer_fields_from_user_id((int) $user->ID) : [];

    return [
        'checkout_fields' => [
            'billing' => app_api_store_checkout_group_to_array((array) ($fields['billing'] ?? [])),
            'shipping' => app_api_store_checkout_group_to_array((array) ($fields['shipping'] ?? [])),
            'account' => app_api_store_checkout_group_to_array((array) ($fields['account'] ?? [])),
            'order' => app_api_store_checkout_group_to_array((array) ($fields['order'] ?? [])),
        ],
        'payment_methods' => array_values($payment_methods),
        'terms_required' => $terms_required,
        'terms_page_url' => $terms_page_id > 0 ? get_permalink($terms_page_id) : null,
        'create_account_allowed' => (bool) $checkout->is_registration_enabled(),
        'registration_required' => method_exists($checkout, 'is_registration_required') ? (bool) $checkout->is_registration_required() : !wc_string_to_bool((string) get_option('woocommerce_enable_guest_checkout', 'yes')),
        'ship_to_billing_only' => (bool) wc_ship_to_billing_address_only(),
        'base_country' => WC()->countries->get_base_country(),
        'prefill' => $prefill,
        'customer' => [
            'logged_in' => (bool) $user,
        ],
    ];
}


function app_api_store_checkout_customer_lookup(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    $cpf = preg_replace('/\D+/', '', (string) $req->get_param('cpf'));
    $email = sanitize_email((string) $req->get_param('email'));
    $user = app_api_store_get_optional_access_user($req);

    if ($cpf === '' || strlen($cpf) < 11) {
        return ['found' => false, 'account_exists' => false, 'fields' => []];
    }

    if ($user) {
        $user_fields = app_api_store_build_customer_fields_from_user_id((int) $user->ID);
        $user_cpf = preg_replace('/\D+/', '', (string) ($user_fields['billing_cpf'] ?? ''));
        if ($user_cpf !== '' && $user_cpf === $cpf) {
            return [
                'found' => true,
                'account_exists' => true,
                'account_email' => (string) $user->user_email,
                'fields' => $user_fields,
            ];
        }
    }

    $matched_user = app_api_store_find_user_by_document_or_email($cpf, $email);
    if ($matched_user instanceof WP_User) {
        $user_fields = app_api_store_build_customer_fields_from_user_id((int) $matched_user->ID);
        return [
            'found' => true,
            'account_exists' => true,
            'account_email' => (string) $matched_user->user_email,
            'fields' => $user_fields,
        ];
    }

    global $wpdb;

    $order_meta_keys = [ 'billing_cpf', '_billing_cpf', 'billing_cnpj', '_billing_cnpj' ];
    $order_meta_placeholders = implode(', ', array_fill(0, count($order_meta_keys), '%s'));
    $order_statuses = array_map(
        static function ($status) {
            $status = (string) $status;
            return strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
        },
        array_keys(wc_get_order_statuses())
    );
    $order_status_placeholders = implode(', ', array_fill(0, count($order_statuses), '%s'));
    $order_params = array_merge($order_meta_keys, [ $cpf ], $order_statuses);
    $order_ids = $wpdb->get_col($wpdb->prepare(
        "
        SELECT DISTINCT pm.post_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key IN ($order_meta_placeholders)
          AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(pm.meta_value, ''), '.', ''), '-', ''), '/', ''), ' ', ''), ',', '') = %s
          AND p.post_type IN ('shop_order', 'shop_order_placehold')
          AND p.post_status IN ($order_status_placeholders)
        ORDER BY p.post_date DESC
        LIMIT 5
        ",
        ...$order_params
    ));

    $orders = array_map('wc_get_order', array_filter(array_map('intval', (array) $order_ids)));

    foreach ((array) $orders as $order) {
        if (!$order || !is_object($order)) {
            continue;
        }

        $candidate_cpf = preg_replace('/\D+/', '', (string) ($order->get_meta('billing_cpf', true) ?: $order->get_meta('_billing_cpf', true) ?: $order->get_meta('billing_cnpj', true) ?: $order->get_meta('_billing_cnpj', true)));
        if ($candidate_cpf === '' || $candidate_cpf !== $cpf) {
            continue;
        }

        $account_user = null;
        if ((int) $order->get_user_id() > 0) {
            $account_user = get_user_by('id', (int) $order->get_user_id());
        }
        if (!$account_user instanceof WP_User) {
            $account_user = app_api_store_find_user_by_document_or_email($cpf, (string) $order->get_billing_email());
        }

        return [
            'found' => true,
            'account_exists' => $account_user instanceof WP_User,
            'account_email' => $account_user instanceof WP_User ? (string) $account_user->user_email : sanitize_email((string) $order->get_billing_email()),
            'fields' => [
                'billing_first_name' => (string) $order->get_billing_first_name(),
                'billing_last_name' => (string) $order->get_billing_last_name(),
                'billing_company' => (string) $order->get_billing_company(),
                'billing_country' => (string) $order->get_billing_country(),
                'billing_address_1' => (string) $order->get_billing_address_1(),
                'billing_address_2' => (string) $order->get_billing_address_2(),
                'billing_city' => (string) $order->get_billing_city(),
                'billing_state' => (string) $order->get_billing_state(),
                'billing_postcode' => (string) $order->get_billing_postcode(),
                'billing_phone' => (string) $order->get_billing_phone(),
                'billing_email' => (string) $order->get_billing_email(),
                'billing_cpf' => $candidate_cpf,
                'shipping_first_name' => (string) $order->get_shipping_first_name(),
                'shipping_last_name' => (string) $order->get_shipping_last_name(),
                'shipping_company' => (string) $order->get_shipping_company(),
                'shipping_country' => (string) $order->get_shipping_country(),
                'shipping_address_1' => (string) $order->get_shipping_address_1(),
                'shipping_address_2' => (string) $order->get_shipping_address_2(),
                'shipping_city' => (string) $order->get_shipping_city(),
                'shipping_state' => (string) $order->get_shipping_state(),
                'shipping_postcode' => (string) $order->get_shipping_postcode(),
            ],
        ];
    }

    return ['found' => false, 'account_exists' => false, 'fields' => []];
}

function app_api_store_checkout_postcode_lookup(WP_REST_Request $req)
{
    $postcode = preg_replace('/\D+/', '', (string) $req->get_param('postcode'));
    if (strlen($postcode) !== 8) {
        return ['found' => false, 'fields' => []];
    }

    $response = wp_remote_get('https://viacep.com.br/ws/' . $postcode . '/json/', [
        'timeout' => 12,
        'redirection' => 3,
        'user-agent' => 'CPAD PWA Checkout/1.0',
    ]);
    if (is_wp_error($response)) {
        return ['found' => false, 'fields' => []];
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body) || !empty($body['erro'])) {
        return ['found' => false, 'fields' => []];
    }

    return [
        'found' => true,
        'fields' => [
            'address_1' => sanitize_text_field((string) ($body['logradouro'] ?? '')),
            'address_2' => sanitize_text_field((string) ($body['bairro'] ?? '')),
            'city' => sanitize_text_field((string) ($body['localidade'] ?? '')),
            'state' => sanitize_text_field((string) ($body['uf'] ?? '')),
            'postcode' => $postcode,
            'country' => 'BR',
        ],
    ];
}

function app_api_store_checkout_installments(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    $gateway = sanitize_text_field((string) $req->get_param('gateway'));
    $brand = sanitize_text_field((string) $req->get_param('brand'));
    $total = app_api_store_money_value($req->get_param('total'));
    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL';

    if (!in_array($gateway, ['loja5_woo_novo_erede', 'loja5_woo_novo_erede_debito'], true)) {
        return ['gateway' => $gateway, 'brand' => $brand, 'options' => []];
    }

    return [
        'gateway' => $gateway,
        'brand' => strtolower($brand),
        'options' => app_api_store_build_erede_installments($gateway, $brand, (float) ($total ?: 0), $currency),
    ];
}

function app_api_store_is_erede_card_gateway(string $gateway_id): bool
{
    return in_array($gateway_id, ['loja5_woo_novo_erede', 'loja5_woo_novo_erede_debito'], true);
}

function app_api_store_is_erede_pix_gateway(string $gateway_id): bool
{
    return $gateway_id === 'loja5_woo_novo_erede_pix';
}

function app_api_store_luhn_valid(string $value): bool
{
    $digits = preg_replace('/\D+/', '', $value);
    $length = strlen($digits);
    if ($length < 13 || $length > 19) {
        return false;
    }

    $sum = 0;
    $double = false;
    for ($index = $length - 1; $index >= 0; $index--) {
        $digit = (int) $digits[$index];
        if ($double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $double = !$double;
    }

    return ($sum % 10) === 0;
}

function app_api_store_expiry_valid(string $month, string $year): bool
{
    $mm = (int) preg_replace('/\D+/', '', $month);
    $yyyy = (int) preg_replace('/\D+/', '', $year);
    if ($mm < 1 || $mm > 12 || $yyyy < 2000) {
        return false;
    }

    try {
        $expires = new DateTimeImmutable(sprintf('%04d-%02d-01 23:59:59', $yyyy, $mm));
        $expires = $expires->modify('last day of this month');
        $now = new DateTimeImmutable('now', $expires->getTimezone());
        return $expires >= $now;
    } catch (Exception $e) {
        return false;
    }
}

function app_api_store_validate_erede_card_payload(string $payment_method, array $fields)
{
    if (!app_api_store_is_erede_card_gateway($payment_method)) {
        return true;
    }

    $prefix = $payment_method === 'loja5_woo_novo_erede_debito' ? 'erede_api_debito' : 'erede_api';
    $brand = sanitize_text_field((string) ($fields['bandeira_' . $prefix] ?? ''));
    $holder = trim((string) ($fields['titular_' . $prefix] ?? ''));
    $number = preg_replace('/\D+/', '', (string) ($fields['numero_' . $prefix] ?? ''));
    $month = sanitize_text_field((string) ($fields['validade_mes_' . $prefix] ?? ''));
    $year = sanitize_text_field((string) ($fields['validade_ano_' . $prefix] ?? ''));
    $cvv = preg_replace('/\D+/', '', (string) ($fields['cvv_' . $prefix] ?? ''));
    $installment = trim((string) ($fields['parcela_' . $prefix] ?? ''));
    $fiscal = preg_replace('/\D+/', '', (string) ($fields['fiscal_' . $prefix] ?? ''));

    if ($brand === '') {
        return new WP_Error('bad_request', 'Selecione a bandeira desejada.', ['status' => 400]);
    }
    if ($holder === '') {
        return new WP_Error('bad_request', 'Informe o nome do titular.', ['status' => 400]);
    }
    if (!app_api_store_luhn_valid($number)) {
        return new WP_Error('bad_request', 'Informe um número de cartão válido.', ['status' => 400]);
    }
    if (!app_api_store_expiry_valid($month, $year)) {
        return new WP_Error('bad_request', 'Informe uma validade de cartão vigente.', ['status' => 400]);
    }
    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        return new WP_Error('bad_request', 'Informe um CVV válido.', ['status' => 400]);
    }
    if ($installment === '') {
        return new WP_Error('bad_request', 'Selecione a parcela ou condição desejada.', ['status' => 400]);
    }
    if ($fiscal === '' || !in_array(strlen($fiscal), [11, 14], true)) {
        return new WP_Error('bad_request', 'Informe um CPF/CNPJ válido.', ['status' => 400]);
    }

    return true;
}

function app_api_store_checkout_collect_notices(string $type = 'error'): array
{
    $notices = function_exists('wc_get_notices') ? wc_get_notices($type) : [];
    $messages = [];
    foreach ((array) $notices as $notice) {
        if (is_array($notice) && isset($notice['notice'])) {
            $messages[] = wp_strip_all_tags((string) $notice['notice']);
        } elseif (is_string($notice)) {
            $messages[] = wp_strip_all_tags($notice);
        }
    }
    return array_values(array_filter(array_unique($messages)));
}

function app_api_store_is_account_password_message(string $message): bool
{
    $normalized = remove_accents(wp_strip_all_tags($message));
    $normalized = function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);

    return strpos($normalized, 'criar uma senha para sua conta') !== false
        || strpos($normalized, 'senha para sua conta') !== false
        || strpos($normalized, 'account password') !== false;
}

function app_api_store_checkout_reflect($object, string $method, array $args = [])
{
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($object, $args);
}

function app_api_store_payment_text_snippet(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) > 260) {
            return rtrim(mb_substr($text, 0, 257)) . '...';
        }
        return $text;
    }
    if (strlen($text) > 260) {
        return rtrim(substr($text, 0, 257)) . '...';
    }
    return $text;
}

function app_api_store_abs_url(string $url, string $base_url = ''): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    if (strpos($url, '//') === 0) {
        $scheme = wp_parse_url(home_url('/'), PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $url;
    }

    if ($base_url === '') {
        return $url;
    }

    $base_parts = wp_parse_url($base_url);
    if (!$base_parts || empty($base_parts['host'])) {
        return $url;
    }

    $scheme = $base_parts['scheme'] ?? 'https';
    $host = $base_parts['host'];
    $port = isset($base_parts['port']) ? ':' . (int) $base_parts['port'] : '';

    if (strpos($url, '/') === 0) {
        return $scheme . '://' . $host . $port . $url;
    }

    $base_path = isset($base_parts['path']) ? dirname($base_parts['path']) : '';
    if ($base_path === DIRECTORY_SEPARATOR) {
        $base_path = '';
    }

    return $scheme . '://' . $host . $port . rtrim($base_path, '/') . '/' . ltrim($url, '/');
}

function app_api_store_payment_extract_from_string(string $text, string $path, array &$state): void
{
    $text = trim($text);
    if ($text === '') {
        return;
    }

    $normalized_path = strtolower($path);
    $normalized_text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));
    $compact_text = preg_replace('/\s+/u', '', $normalized_text);

    if (empty($state['pix_code'])) {
        if (preg_match('/(000201[A-Z0-9\.\-\/:\+\=]{60,}6304[A-F0-9]{4})/i', strtoupper($compact_text), $m)) {
            $state['pix_code'] = $m[1];
        } elseif ((strpos($normalized_path, 'pix') !== false || strpos($normalized_path, 'copia') !== false || strpos($normalized_path, 'copy') !== false || strpos($normalized_path, 'payload') !== false || strpos($normalized_path, 'emv') !== false) && strlen($compact_text) >= 30) {
            $state['pix_code'] = $compact_text;
        }
    }

    if (empty($state['qr_code_image'])) {
        if (strpos($text, 'data:image/') === 0) {
            $state['qr_code_image'] = $text;
        } elseif (preg_match("#https?://[^\\s\"']+?(?:png|jpg|jpeg|gif|webp)(?:\\?[^\\s\"']*)?#i", $text, $m) && (strpos($normalized_path, 'qr') !== false || strpos($normalized_path, 'pix') !== false || strpos(strtolower($m[0]), 'qr') !== false || strpos(strtolower($m[0]), 'pix') !== false)) {
            $state['qr_code_image'] = $m[0];
        }
    }

    if (empty($state['payment_url']) && preg_match('#^https?://#i', $text) && (strpos($normalized_path, 'redirect') !== false || strpos($normalized_path, 'payment') !== false || strpos($normalized_path, 'checkout') !== false || strpos($normalized_path, 'pix') !== false)) {
        $state['payment_url'] = $text;
    }

    if ((strpos($normalized_path, 'instruction') !== false || strpos($normalized_path, 'message') !== false || strpos($normalized_path, 'pix') !== false || strpos($normalized_path, 'qr') !== false || stripos($normalized_text, 'pix') !== false || stripos($normalized_text, 'qr code') !== false || stripos($normalized_text, 'copia e cola') !== false) && $normalized_text !== '') {
        $snippet = app_api_store_payment_text_snippet($normalized_text);
        if ($snippet !== '') {
            $state['instruction_snippets'][$snippet] = true;
        }
    }
}

function app_api_store_payment_walk_value($value, string $path, array &$state): void
{
    if (is_scalar($value) || $value === null) {
        app_api_store_payment_extract_from_string((string) $value, $path, $state);
        return;
    }

    if (is_array($value)) {
        foreach ($value as $key => $item) {
            app_api_store_payment_walk_value($item, $path . '.' . (string) $key, $state);
        }
        return;
    }

    if (is_object($value)) {
        if ($value instanceof WC_Meta_Data) {
            app_api_store_payment_walk_value($value->get_data(), $path . '.meta', $state);
            return;
        }
        foreach (get_object_vars($value) as $key => $item) {
            app_api_store_payment_walk_value($item, $path . '.' . (string) $key, $state);
        }
    }
}

function app_api_store_payment_extract_from_html(string $html, string $base_url = ''): array
{
    $state = [
        'pix_code' => '',
        'qr_code_image' => '',
        'payment_url' => '',
        'instruction_snippets' => [],
    ];

    if ($html === '') {
        return [
            'pix_code' => '',
            'qr_code_image' => '',
            'payment_url' => '',
            'instructions_html' => '',
        ];
    }

    app_api_store_payment_walk_value($html, 'html.raw', $state);

    if (class_exists('DOMDocument')) {
        $internal_errors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($internal_errors);

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//img') as $img) {
            $src = trim((string) $img->getAttribute('src'));
            $alt = strtolower(trim((string) $img->getAttribute('alt')));
            $class = strtolower(trim((string) $img->getAttribute('class')));
            $id = strtolower(trim((string) $img->getAttribute('id')));
            if ($src !== '' && (strpos(strtolower($src), 'qr') !== false || strpos(strtolower($src), 'pix') !== false || strpos($alt, 'qr') !== false || strpos($alt, 'pix') !== false || strpos($class, 'qr') !== false || strpos($class, 'pix') !== false || strpos($id, 'qr') !== false || strpos($id, 'pix') !== false)) {
                $state['qr_code_image'] = app_api_store_abs_url($src, $base_url);
                break;
            }
        }

        foreach ($xpath->query('//textarea|//input|//div|//p|//span|//strong|//code') as $node) {
            $text = '';
            if ($node->nodeName === 'input') {
                $text = (string) $node->getAttribute('value');
            } else {
                $text = (string) $node->textContent;
            }
            $path = strtolower($node->nodeName . '.' . $node->getAttribute('name') . '.' . $node->getAttribute('id') . '.' . $node->getAttribute('class'));
            app_api_store_payment_extract_from_string($text, $path, $state);
        }
    }

    $instructions = array_keys($state['instruction_snippets']);
    $instructions_html = '';
    if ($instructions) {
        $instructions_html = wp_kses_post('<p>' . implode('</p><p>', array_map('esc_html', array_slice($instructions, 0, 6))) . '</p>');
    }

    return [
        'pix_code' => $state['pix_code'],
        'qr_code_image' => $state['qr_code_image'],
        'payment_url' => $state['payment_url'],
        'instructions_html' => $instructions_html,
    ];
}


function app_api_store_get_loja5_erede_card_payment_data($order, string $redirect_url = ''): array
{
    if (!$order || !is_object($order) || !method_exists($order, 'get_payment_method')) {
        return [];
    }

    $payment_method = (string) $order->get_payment_method();
    if (!app_api_store_is_erede_card_gateway($payment_method)) {
        return [];
    }

    $status = trim((string) $order->get_meta('_erede_status_pedido', true));
    $brand = trim((string) $order->get_meta('_erede_bandeira_pedido', true));
    $installments = trim((string) $order->get_meta('_qtd_parcelas', true));
    $tid = trim((string) $order->get_meta('_erede_api_tid', true));
    $message = trim((string) $order->get_meta('mensagem_erede', true));
    $error = trim((string) $order->get_meta('erro_erede', true));

    $parts = [];
    if ($status === 'Approved') {
        $parts[] = '<p>Pagamento do cartão aprovado com sucesso.</p>';
    } elseif ($status === 'Pending') {
        $parts[] = '<p>Pagamento do cartão autorizado e aguardando a confirmação final do gateway.</p>';
    } elseif ($status === 'Denied') {
        $parts[] = '<p>O emissor do cartão negou a transação.</p>';
    } else {
        $parts[] = '<p>O pedido foi criado e o status do cartão será atualizado assim que o gateway responder.</p>';
    }

    if ($brand !== '') {
        $parts[] = '<p><strong>Bandeira:</strong> ' . esc_html(ucfirst($brand)) . '</p>';
    }
    if ($installments !== '') {
        $parts[] = '<p><strong>Condição:</strong> ' . esc_html($installments) . 'x</p>';
    }
    if ($tid !== '') {
        $parts[] = '<p><strong>TID:</strong> ' . esc_html($tid) . '</p>';
    }
    if ($message !== '') {
        $parts[] = '<p>' . esc_html($message) . '</p>';
    }
    if ($error !== '') {
        $parts[] = '<p>' . esc_html($error) . '</p>';
    }

    return [
        'pix_code' => '',
        'qr_code_image' => '',
        'payment_url' => $redirect_url,
        'instructions_html' => wp_kses_post(implode('', $parts)),
        'gateway_html' => '',
    ];
}

function app_api_store_get_loja5_erede_pix_payment_data($order, string $redirect_url = ''): array
{
    if (!$order || !is_object($order) || !method_exists($order, 'get_payment_method')) {
        return [];
    }

    if ((string) $order->get_payment_method() !== 'loja5_woo_novo_erede_pix') {
        return [];
    }

    $erede_data = $order->get_meta('_dados_erede_api', true);
    if (!is_array($erede_data) || empty($erede_data['qrCodeResponse']['qrCodeData'])) {
        return [];
    }

    $pix_code = trim((string) $erede_data['qrCodeResponse']['qrCodeData']);
    if ($pix_code === '') {
        return [];
    }

    $payment_url = $redirect_url;
    if ($payment_url === '' && method_exists($order, 'get_checkout_payment_url')) {
        $payment_url = (string) $order->get_checkout_payment_url(false);
    }
    if ($payment_url === '' && method_exists($order, 'get_checkout_order_received_url')) {
        $payment_url = (string) $order->get_checkout_order_received_url();
    }

    $instruction_parts = [];
    $tid = trim((string) ($erede_data['tid'] ?? ''));
    if ($tid !== '') {
        $instruction_parts[] = '<p><strong>TID:</strong> ' . esc_html($tid) . '</p>';
    }

    $date_time_expiration = trim((string) ($erede_data['qrCodeResponse']['dateTimeExpiration'] ?? ''));
    if ($date_time_expiration !== '') {
        $timestamp = strtotime($date_time_expiration);
        if ($timestamp) {
            $instruction_parts[] = '<p><strong>Pague até:</strong> ' . esc_html(wp_date('d/m/Y H:i:s', $timestamp)) . '</p>';
        }
    }

    $instruction_parts[] = '<p>Escaneie o QR Code ou use o código Pix copia e cola abaixo para concluir o pagamento sem sair do PWA.</p>';

    return [
        'pix_code' => $pix_code,
        'qr_code_image' => '',
        'payment_url' => $payment_url,
        'instructions_html' => wp_kses_post(implode('', $instruction_parts)),
        'gateway_html' => '',
    ];
}

function app_api_store_get_order_payment_data($order, $payment_result = null, string $redirect_url = ''): array
{
    $payment_method = ($order && is_object($order) && method_exists($order, 'get_payment_method'))
        ? (string) $order->get_payment_method()
        : '';

    if (app_api_store_is_erede_pix_gateway($payment_method)) {
        $gateway_specific = app_api_store_get_loja5_erede_pix_payment_data($order, $redirect_url);
        if (!empty($gateway_specific['pix_code'])) {
            return $gateway_specific;
        }
    }

    if (app_api_store_is_erede_card_gateway($payment_method)) {
        return app_api_store_get_loja5_erede_card_payment_data($order, $redirect_url);
    }

    $state = [
        'pix_code' => '',
        'qr_code_image' => '',
        'payment_url' => '',
        'instruction_snippets' => [],
    ];

    if ($redirect_url !== '') {
        $state['payment_url'] = $redirect_url;
    }

    if ($payment_result !== null) {
        app_api_store_payment_walk_value($payment_result, 'payment_result', $state);
    }

    if ($order && is_object($order) && method_exists($order, 'get_meta_data')) {
        foreach ((array) $order->get_meta_data() as $meta) {
            if (!$meta instanceof WC_Meta_Data) {
                continue;
            }
            $data = $meta->get_data();
            $meta_key = isset($data['key']) ? (string) ($data['key']) : 'meta';
            app_api_store_payment_walk_value($data['value'] ?? null, 'order_meta.' . $meta_key, $state);
        }
    }

    $parsed_html = [
        'pix_code' => '',
        'qr_code_image' => '',
        'payment_url' => '',
        'instructions_html' => '',
    ];

    $candidate_urls = [];
    foreach ([$redirect_url] as $candidate_url) {
        if (is_string($candidate_url) && $candidate_url !== '' && preg_match('#^https?://#i', $candidate_url)) {
            $candidate_urls[] = $candidate_url;
        }
    }

    if ($order && is_object($order)) {
        if (method_exists($order, 'get_checkout_payment_url')) {
            $candidate_url = (string) $order->get_checkout_payment_url(false);
            if ($candidate_url !== '' && preg_match('#^https?://#i', $candidate_url)) {
                $candidate_urls[] = $candidate_url;
            }
        }

        if (method_exists($order, 'get_checkout_order_received_url')) {
            $candidate_url = (string) $order->get_checkout_order_received_url();
            if ($candidate_url !== '' && preg_match('#^https?://#i', $candidate_url)) {
                $candidate_urls[] = $candidate_url;
            }
        }
    }

    $candidate_urls = array_values(array_unique($candidate_urls));

    foreach ($candidate_urls as $candidate_url) {
        if ($state['pix_code'] !== '' && $state['qr_code_image'] !== '') {
            break;
        }

        $response = wp_remote_get($candidate_url, [
            'timeout' => 15,
            'redirection' => 5,
            'user-agent' => 'CPAD PWA Checkout/1.0',
        ]);
        if (is_wp_error($response)) {
            continue;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 400) {
            continue;
        }

        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '') {
            continue;
        }

        $candidate_parsed = app_api_store_payment_extract_from_html($body, $candidate_url);
        if ($parsed_html['pix_code'] === '' && !empty($candidate_parsed['pix_code'])) {
            $parsed_html['pix_code'] = (string) $candidate_parsed['pix_code'];
        }
        if ($parsed_html['qr_code_image'] === '' && !empty($candidate_parsed['qr_code_image'])) {
            $parsed_html['qr_code_image'] = (string) $candidate_parsed['qr_code_image'];
        }
        if ($parsed_html['payment_url'] === '' && !empty($candidate_parsed['payment_url'])) {
            $parsed_html['payment_url'] = (string) $candidate_parsed['payment_url'];
        }
        if ($parsed_html['instructions_html'] === '' && !empty($candidate_parsed['instructions_html'])) {
            $parsed_html['instructions_html'] = (string) $candidate_parsed['instructions_html'];
        }

        if ($state['payment_url'] === '') {
            $state['payment_url'] = $candidate_url;
        }
    }

    $instructions = array_keys($state['instruction_snippets']);
    $instructions_html = $parsed_html['instructions_html'];
    if ($instructions_html === '' && $instructions) {
        $instructions_html = wp_kses_post('<p>' . implode('</p><p>', array_map('esc_html', array_slice($instructions, 0, 6))) . '</p>');
    }

    return [
        'pix_code' => $state['pix_code'] ?: $parsed_html['pix_code'],
        'qr_code_image' => $state['qr_code_image'] ?: $parsed_html['qr_code_image'],
        'payment_url' => $state['payment_url'] ?: $parsed_html['payment_url'] ?: $redirect_url,
        'instructions_html' => $instructions_html,
        'gateway_html' => '',
    ];
}

function app_api_store_checkout_submit(WP_REST_Request $req)
{
    $ok = app_api_store_ensure_woocommerce();
    if (is_wp_error($ok)) {
        return $ok;
    }

    if (!WC()->cart) {
        return new WP_Error('cart_unavailable', 'Carrinho do WooCommerce indisponível', ['status' => 500]);
    }

    $items = $req->get_param('items');
    $fields = $req->get_param('fields');
    $payment_method = sanitize_text_field((string) $req->get_param('payment_method'));
    $create_account = wc_string_to_bool((string) $req->get_param('create_account') ?: 'false');
    $ship_to_different_address = wc_string_to_bool((string) $req->get_param('ship_to_different_address') ?: 'false');
    $terms = wc_string_to_bool((string) $req->get_param('terms') ?: 'false');
    $checkout = WC()->checkout();
    $registration_required = $checkout && method_exists($checkout, 'is_registration_required') ? (bool) $checkout->is_registration_required() : !wc_string_to_bool((string) get_option('woocommerce_enable_guest_checkout', 'yes'));

    if (!is_array($items) || !$items) {
        return new WP_Error('bad_request', 'Carrinho vazio', ['status' => 400]);
    }

    if ($payment_method === '') {
        return new WP_Error('bad_request', 'Escolha uma forma de pagamento', ['status' => 400]);
    }

    if (!is_array($fields)) {
        $fields = [];
    }

    $billing_email = sanitize_email((string) ($fields['billing_email'] ?? ''));
    $billing_cpf = preg_replace('/\D+/', '', (string) ($fields['billing_cpf'] ?? ''));
    $existing_account_password = (string) ($fields['existing_account_password'] ?? '');
    unset($fields['existing_account_password']);

    $access_user = app_api_store_get_optional_access_user($req);
    $matched_user = app_api_store_find_user_by_document_or_email($billing_cpf, $billing_email);
    $acting_user = null;

    if ($access_user instanceof WP_User) {
        $matched_user = $access_user;
        $acting_user = $access_user;
        $create_account = false;
    } elseif ($matched_user instanceof WP_User) {
        $acting_user = $matched_user;
        $create_account = false;
    } elseif ($registration_required) {
        $create_account = true;
    }

    wc_clear_notices();
    WC()->cart->empty_cart(true);

    foreach ($items as $item) {
        $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
        $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;

        if ($product_id <= 0) {
            continue;
        }

        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            return new WP_Error('invalid_product', 'Um dos produtos não está disponível', ['status' => 400]);
        }

        $added = WC()->cart->add_to_cart($product_id, $quantity);
        if (!$added) {
            $errors = app_api_store_checkout_collect_notices('error');
            return new WP_Error('cart_add_failed', $errors ? implode(' ', $errors) : 'Não foi possível adicionar o produto ao carrinho.', ['status' => 400]);
        }
    }

    if (WC()->cart->is_empty()) {
        return new WP_Error('bad_request', 'Carrinho vazio', ['status' => 400]);
    }

    $previous_post = $_POST;
    $_POST = [];

    foreach ($fields as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (is_array($value)) {
            $_POST[$key] = array_map('wc_clean', wp_unslash($value));
        } else {
            $_POST[$key] = wp_unslash((string) $value);
        }
    }

    $_POST['payment_method'] = $payment_method;
    if ($create_account) {
        $_POST['createaccount'] = '1';
    }
    if ($ship_to_different_address) {
        $_POST['ship_to_different_address'] = '1';
    }
    if ($terms) {
        $_POST['terms'] = '1';
        $_POST['terms-field'] = '1';
    }

    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (!isset($available_gateways[$payment_method])) {
        return new WP_Error('invalid_payment_method', 'Forma de pagamento inválida.', ['status' => 400]);
    }

    $inline_card_validation = app_api_store_validate_erede_card_payload($payment_method, $fields);
    if (is_wp_error($inline_card_validation)) {
        return $inline_card_validation;
    }

    WC()->session->set('chosen_payment_method', $payment_method);

    $restore_user_id = get_current_user_id();
    $registration_override_enabled = false;
    if ($acting_user instanceof WP_User) {
        wp_set_current_user((int) $acting_user->ID);
        if (class_exists('WC_Customer')) {
            WC()->customer = new WC_Customer((int) $acting_user->ID, true);
        }
        add_filter('woocommerce_checkout_registration_required', '__return_false', 999);
        add_filter('woocommerce_checkout_registration_enabled', '__return_false', 999);
        $registration_override_enabled = true;
    }

    if (method_exists($available_gateways[$payment_method], 'validate_fields')) {
        $gateway_valid = $available_gateways[$payment_method]->validate_fields();
        $gateway_errors = app_api_store_checkout_collect_notices('error');
        if ($gateway_valid === false || $gateway_errors) {
            return new WP_Error(
                'gateway_validation_failed',
                $gateway_errors ? implode(' ', $gateway_errors) : 'Confira os dados da forma de pagamento.',
                ['status' => 400, 'messages' => $gateway_errors]
            );
        }
    }

    try {
        $checkout = WC()->checkout();
        $errors = new WP_Error();

        if (!$create_account && $checkout) {
            $checkout_fields = (array) $checkout->get_checkout_fields();
            foreach (array_keys((array) ($checkout_fields['account'] ?? [])) as $account_field_key) {
                unset($_POST[$account_field_key]);
            }
            unset($_POST['createaccount']);
        }

        $posted_data = app_api_store_checkout_reflect($checkout, 'get_posted_data');

        try {
            app_api_store_checkout_reflect($checkout, 'update_session', [ $posted_data ]);
        } catch (WC_Data_Exception $e) {
            if ('customer_invalid_billing_email' !== $e->getErrorCode()) {
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        app_api_store_checkout_reflect($checkout, 'validate_checkout', [ &$posted_data, &$errors ]);

        foreach ($errors->errors as $code => $messages) {
            $data = $errors->get_error_data($code);
            foreach ($messages as $message) {
                wc_add_notice($message, 'error', $data);
            }
        }

        $error_messages = app_api_store_checkout_collect_notices('error');
        if ($acting_user instanceof WP_User && $error_messages) {
            $error_messages = array_values(array_filter($error_messages, static function ($message) {
                return !app_api_store_is_account_password_message((string) $message);
            }));
            wc_clear_notices();
            foreach ($error_messages as $message) {
                wc_add_notice($message, 'error');
            }
        }
        if ($error_messages) {
            return new WP_Error('checkout_validation_failed', implode(' ', $error_messages), ['status' => 400, 'messages' => $error_messages]);
        }

        app_api_store_checkout_reflect($checkout, 'process_customer', [ $posted_data ]);
        $order_id = $checkout->create_order($posted_data);
        if (is_wp_error($order_id)) {
            return $order_id;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('order_creation_failed', 'Não foi possível criar o pedido.', ['status' => 500]);
        }
        if ($acting_user instanceof WP_User && method_exists($order, 'set_customer_id')) {
            $order->set_customer_id((int) $acting_user->ID);
            $order->save();
        }

        do_action('woocommerce_checkout_order_processed', $order_id, $posted_data, $order);

        $result = [
            'result' => 'success',
            'order_id' => $order_id,
            'order_key' => $order->get_order_key(),
            'order_status' => $order->get_status(),
            'redirect' => '',
            'payment_method' => $payment_method,
            'payment_method_title' => $order->get_payment_method_title(),
            'total' => html_entity_decode(strip_tags($order->get_formatted_order_total())),
            'order_received_url' => $order->get_checkout_order_received_url(),
        ];

        if (apply_filters('woocommerce_cart_needs_payment', $order->needs_payment(), WC()->cart)) {
            if (!isset($available_gateways[$payment_method])) {
                return new WP_Error('invalid_payment_method', 'Forma de pagamento inválida.', ['status' => 400]);
            }

            WC()->session->set('order_awaiting_payment', $order_id);
            WC()->session->save_data();

            $payment_result = $available_gateways[$payment_method]->process_payment($order_id);
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_Error('order_lookup_failed', 'Não foi possível recarregar o pedido após iniciar o pagamento.', ['status' => 500]);
            }

            if (app_api_store_is_erede_card_gateway($payment_method)) {
                $gateway_error = trim((string) $order->get_meta('erro_erede', true));
                $gateway_status = trim((string) $order->get_meta('_erede_status_pedido', true));
                $gateway_message = trim((string) $order->get_meta('mensagem_erede', true));

                if ($gateway_error !== '') {
                    return new WP_Error('payment_failed', $gateway_error, ['status' => 400]);
                }

                if ($gateway_status === 'Denied') {
                    return new WP_Error(
                        'payment_failed',
                        $gateway_message !== '' ? $gateway_message : 'A operadora negou a transação do cartão.',
                        ['status' => 400]
                    );
                }
            }

            if (is_array($payment_result)) {
                if (isset($payment_result['result'])) {
                    $result['result'] = (string) $payment_result['result'];
                }
                if (isset($payment_result['redirect'])) {
                    $result['redirect'] = (string) $payment_result['redirect'];
                }
            }

            $result = apply_filters('woocommerce_payment_successful_result', $result, $order_id);
            $result['payment_data'] = app_api_store_get_order_payment_data($order, $payment_result, (string) ($result['redirect'] ?? ''));
        } else {
            $order->payment_complete();
            WC()->cart->empty_cart(true);
        }

        if (empty($result['payment_data'])) {
            $result['payment_data'] = app_api_store_get_order_payment_data($order, null, (string) ($result['redirect'] ?? ''));
        }

        if (in_array($result['result'], ['success', 'pending'], true)) {
            WC()->cart->empty_cart(true);
        }

        return $result;
    } catch (Exception $e) {
        $messages = app_api_store_checkout_collect_notices('error');
        $message = $messages ? implode(' ', $messages) : $e->getMessage();
        return new WP_Error('checkout_failed', $message ?: 'Não foi possível finalizar a compra.', ['status' => 400, 'messages' => $messages]);
    } finally {
        if ($registration_override_enabled) {
            remove_filter('woocommerce_checkout_registration_required', '__return_false', 999);
            remove_filter('woocommerce_checkout_registration_enabled', '__return_false', 999);
        }
        wp_set_current_user((int) $restore_user_id);
        $_POST = $previous_post;
    }
}
