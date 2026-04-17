<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Pega a meta-key configurada no plugin (se existir),
 * senão usa o padrão.
 */
function app_api_wc_flipbook_meta_key(): string
{
    return function_exists('app_api_flipbook_meta_key')
        ? (string) app_api_flipbook_meta_key()
        : '_app_api_flipbook_id';
}

// Campo opcional usado em prévias sem compra
function app_api_wc_flipbook_meta_key_non(): string
{
    return '_app_api_flipbook_id_non';
}

add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';

    woocommerce_wp_text_input([
        'id' => app_api_wc_flipbook_meta_key(),
        'label' => __('App API – Flipbook (comprado)', 'app-api'),
        'desc_tip' => true,
        'description' => __('ID do post do Flipbook (Real3D). É esse que o app vai abrir.', 'app-api'),
        'type' => 'number',
        'custom_attributes' => ['min' => '1', 'step' => '1'],
    ]);

    woocommerce_wp_text_input([
        'id' => app_api_wc_flipbook_meta_key_non(),
        'label' => __('App API – Flipbook (não comprado) (opcional)', 'app-api'),
        'desc_tip' => true,
        'description' => __('Opcional: prévia/teaser para quem não comprou.', 'app-api'),
        'type' => 'number',
        'custom_attributes' => ['min' => '1', 'step' => '1'],
    ]);

    echo '</div>';
});

/**
 * Heurística: tenta detectar campos do Addon Real3D no $_POST
 * (não dependemos do nome exato do campo).
 */
function app_api_wc_detect_flipbook_id_from_post(array $post, bool $wantPurchased): int
{
    $best = 0;

    foreach ($post as $k => $v) {
        if (!is_scalar($v))
            continue;

        $key = strtolower((string) $k);
        $val = trim((string) $v);

        if ($val === '' || !ctype_digit($val))
            continue;

        $id = absint($val);
        if (!$id)
            continue;

        // Considera apenas campos relacionados a Real3D ou flipbook
        $looksR3D =
            (strpos($key, 'real3d') !== false) ||
            (strpos($key, 'r3d') !== false) ||
            (strpos($key, 'flipbook') !== false);

        if (!$looksR3D)
            continue;

        $isNon =
            (strpos($key, 'non') !== false) ||
            (strpos($key, 'not') !== false) ||
            (strpos($key, 'preview') !== false);

        $isPurchased =
            (strpos($key, 'purchased') !== false) ||
            (strpos($key, 'bought') !== false) ||
            (strpos($key, 'paid') !== false) ||
            (strpos($key, 'buy') !== false);

        if ($wantPurchased && $isNon)
            continue;
        if (!$wantPurchased && $isPurchased)
            continue;

        // Se for um post do flipbook, usa diretamente
        $pt = get_post_type($id);
        if ($pt && in_array($pt, ['r3d', 'real3dflipbook', 'real3d_flipbook'], true)) {
            return $id;
        }

        // Guarda como candidato quando a validação não for conclusiva
        if (!$best)
            $best = $id;
    }

    return $best;
}

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (!$product)
        return;

    $metaKeyPurchased = app_api_wc_flipbook_meta_key();
    $metaKeyNon = app_api_wc_flipbook_meta_key_non();

    // Respeita valores preenchidos ou limpos manualmente no admin
        $rawPurchased = array_key_exists($metaKeyPurchased, $_POST)
        ? trim((string) wp_unslash($_POST[$metaKeyPurchased]))
        : null;
    $rawNon = array_key_exists($metaKeyNon, $_POST)
        ? trim((string) wp_unslash($_POST[$metaKeyNon]))
        : null;

    $skipAutoPurchased = ($rawPurchased !== null && $rawPurchased === '');
    $skipAutoNon = ($rawNon !== null && $rawNon === '');

    $purchased = ($rawPurchased !== null && $rawPurchased !== '') ? absint($rawPurchased) : 0;
    $non = ($rawNon !== null && $rawNon !== '') ? absint($rawNon) : 0;

    // Se não houver valor manual, tenta detectar pelo Addon Real3D
    if (!$purchased && !$skipAutoPurchased && isset($_POST) && is_array($_POST)) {
        $purchased = app_api_wc_detect_flipbook_id_from_post($_POST, true);
    }
    if (!$non && !$skipAutoNon && isset($_POST) && is_array($_POST)) {
        $non = app_api_wc_detect_flipbook_id_from_post($_POST, false);
    }

    // Persiste ou remove os metadados
    if (array_key_exists($metaKeyPurchased, $_POST)) {
        if ($purchased) {
            $product->update_meta_data($metaKeyPurchased, $purchased);
        } else {
            $product->delete_meta_data($metaKeyPurchased);
        }
    } elseif ($purchased) {
        $product->update_meta_data($metaKeyPurchased, $purchased);
    }

    if (array_key_exists($metaKeyNon, $_POST)) {
        if ($non) {
            $product->update_meta_data($metaKeyNon, $non);
        } else {
            $product->delete_meta_data($metaKeyNon);
        }
    } elseif ($non) {
        $product->update_meta_data($metaKeyNon, $non);
    }
}, 20);
