<?php
if (!defined('ABSPATH'))
    exit;

function app_api_activate()
{
    global $wpdb;
    $table = $wpdb->prefix . 'app_refresh_tokens';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    device_id VARCHAR(128) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    replaced_by_hash CHAR(64) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    UNIQUE KEY token_hash (token_hash)
  ) $charset_collate;";

    dbDelta($sql);

    // Tabela de eventos de recomendação e interação
    $ai_table = $wpdb->prefix . 'app_ai_events';

    $sql_ai = "CREATE TABLE $ai_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        event_type VARCHAR(24) NOT NULL,
        magazine_product_id BIGINT UNSIGNED NOT NULL,
        page INT UNSIGNED NOT NULL,
        recommended_product_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY magazine_product_id (magazine_product_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql_ai);

    // Atualiza as regras de rewrite após a ativação
    if (function_exists('flush_rewrite_rules')) {
        flush_rewrite_rules();
    }

    // Gera as chaves usadas nas licenças offline
    if (function_exists('app_api_offline_ensure_keys')) {
        app_api_offline_ensure_keys();
    }
}
