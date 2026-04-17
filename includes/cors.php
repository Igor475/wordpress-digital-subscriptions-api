<?php
/**
 * CORS para o namespace /app/v1/ (PWA em subdomínio)
 */
if (!defined('ABSPATH'))
    exit;

function app_api_get_allowed_origins()
{
    return [
        'https://app-cpaddigital.vercel.app',
        'https://www.app-cpaddigital.cpaddigital.com.br',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:4173',
        'http://127.0.0.1:4173',
    ];
}

function app_api_is_allowed_cors_origin(?string $origin): bool
{
    $origin = is_string($origin) ? trim($origin) : '';
    if ($origin === '') {
        return false;
    }

    return in_array($origin, app_api_get_allowed_origins(), true);
}

function app_api_allowed_cors_headers(): array
{
    return ['authorization', 'content-type', 'range', 'x-wp-nonce'];
}

function app_api_send_cors_headers()
{
    $origin = get_http_origin();
    if (!app_api_is_allowed_cors_origin($origin)) {
        return false;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin, Access-Control-Request-Headers');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Range, X-WP-Nonce');
    header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages, Link, Accept-Ranges, Content-Range, Content-Length, Content-Disposition');
    header('Access-Control-Max-Age: 86400');
    return true;
}

add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) {
    $route = $request->get_route();

    if (is_string($route) && strpos($route, '/app/v1/') === 0) {
        app_api_send_cors_headers();
    }

    return $served;
}, 10, 4);

add_action('init', function () {
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if (strpos($uri, '/wp-json/app/v1/') === false) {
            return;
        }

        $origin = get_http_origin();
        if (!app_api_is_allowed_cors_origin($origin)) {
            status_header(403);
            exit;
        }

        $requested_headers_raw = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])
            ? (string) $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
            : '';

        if ($requested_headers_raw !== '') {
            $requested_headers = array_values(array_filter(array_map('trim', explode(',', strtolower($requested_headers_raw)))));
            $allowed_headers = app_api_allowed_cors_headers();
            foreach ($requested_headers as $header_name) {
                if (!in_array($header_name, $allowed_headers, true)) {
                    status_header(403);
                    exit;
                }
            }
        }

        app_api_send_cors_headers();
        status_header(204);
        exit;
    }
});
