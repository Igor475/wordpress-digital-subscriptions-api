<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Meta key configurada no admin do plugin.
 * (Seu print mostra _app_api_flipbook_id)
 */
function app_api_product_flipbooks_meta_key(): string
{
    return function_exists('app_api_flipbook_meta_key')
        ? (string) app_api_flipbook_meta_key()
        : '_app_api_flipbook_id';
}

add_action('woocommerce_product_options_general_product_data', function () {
    $key = app_api_product_flipbooks_meta_key();

    echo '<div class="options_group">';

    woocommerce_wp_textarea_input([
        'id' => $key,
        'label' => __('App API – Flipbooks (IDs)', 'app-api'),
        'description' => __('Cole aqui os IDs do Real3D (do shortcode). Pode ser 1 ou vários separados por vírgula. Ex: 240,239,238', 'app-api'),
        'desc_tip' => true,
    ]);

    woocommerce_wp_text_input([
        'id' => 'app_api_r3d_root_category',
        'label' => __('App API – Categoria raiz (Real3D)', 'app-api'),
        'description' => __('Slug da categoria raiz do Real3D (taxonomia r3d_category). Ex: jornal-mensageiro-da-paz, revista-obreiro, revista-ensinador. Quando preenchido, a API inclui automaticamente TODOS os flipbooks dessa categoria e de todas as filhas.', 'app-api'),
        'desc_tip' => true,
    ]);

    echo '</div>';
});

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (!$product)
        return;

    $key = app_api_product_flipbooks_meta_key();

    // IDs de flipbooks
    if (isset($_POST[$key])) {
        $raw = wp_unslash($_POST[$key]);
        $raw = is_string($raw) ? $raw : '';

        // Aceita lista simples, CSV ou shortcode
        preg_match_all('/\b(\d{1,9})\b/', $raw, $m);
        $ids = array_values(array_unique(array_filter(array_map('intval', $m[1] ?? []))));

        // Salva em formato CSV
        $value = implode(',', $ids);
        $product->update_meta_data($key, $value);
    }

    // Categoria raiz do Real3D
    if (isset($_POST['app_api_r3d_root_category'])) {
        $raw_slug = wp_unslash($_POST['app_api_r3d_root_category']);
        $raw_slug = is_string($raw_slug) ? trim($raw_slug) : '';

        // Normaliza o valor para slug
        $slug = $raw_slug !== '' ? sanitize_title($raw_slug) : '';
        $product->update_meta_data('app_api_r3d_root_category', $slug);
    }

    // Limpa o cache do produto após alterações
    if (method_exists($product, 'get_id')) {
        delete_transient('app_api_allowed_flipbooks_' . (int) $product->get_id());
    }
}, 20);
