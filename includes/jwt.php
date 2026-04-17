<?php
if (!defined('ABSPATH'))
    exit;

function app_api_secret(): string
{
    if (defined('APP_API_JWT_SECRET') && APP_API_JWT_SECRET)
        return APP_API_JWT_SECRET;
    // Fallback usando as chaves do WordPress
    return hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY);
}

function app_api_b64url_enc(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function app_api_b64url_dec(string $data): string
{
    $pad = 4 - (strlen($data) % 4);
    if ($pad < 4)
        $data .= str_repeat('=', $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

function app_api_jwt_sign(array $payload): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = app_api_b64url_enc(json_encode($header, JSON_UNESCAPED_SLASHES));
    $p = app_api_b64url_enc(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $sig = hash_hmac('sha256', "$h.$p", app_api_secret(), true);
    $s = app_api_b64url_enc($sig);
    return "$h.$p.$s";
}

function app_api_jwt_verify(string $jwt)
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3)
        return new WP_Error('jwt_invalid', 'Token inválido', ['status' => 401]);

    [$h, $p, $s] = $parts;
    $sig = app_api_b64url_enc(hash_hmac('sha256', "$h.$p", app_api_secret(), true));
    if (!hash_equals($sig, $s))
        return new WP_Error('jwt_invalid', 'Assinatura inválida', ['status' => 401]);

    $payload = json_decode(app_api_b64url_dec($p), true);
    if (!is_array($payload))
        return new WP_Error('jwt_invalid', 'Payload inválido', ['status' => 401]);

    $now = time();
    if (isset($payload['nbf']) && $now < intval($payload['nbf']))
        return new WP_Error('jwt_invalid', 'Token ainda não é válido', ['status' => 401]);
    if (isset($payload['exp']) && $now >= intval($payload['exp']))
        return new WP_Error('jwt_expired', 'Token expirado', ['status' => 401]);

    return $payload;
}
