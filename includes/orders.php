<?php
function app_api_wc_order_to_array($order, array $opts = [])
{
    if (!$order || !is_object($order) || !method_exists($order, 'get_id')) {
        return null;
    }

    $opts = array_merge([
        'include_items' => true,
        'include_meta' => false,
        'include_attribution' => true,
        'include_customer_stats' => true,
    ], $opts);

    $order_id = (int) $order->get_id();
    $customer_id = (int) $order->get_customer_id();

    // Dados de cobrança e entrega
    $billing = [
        'first_name' => (string) $order->get_billing_first_name(),
        'last_name' => (string) $order->get_billing_last_name(),
        'company' => (string) $order->get_billing_company(),
        'address_1' => (string) $order->get_billing_address_1(),
        'address_2' => (string) $order->get_billing_address_2(),
        'city' => (string) $order->get_billing_city(),
        'state' => (string) $order->get_billing_state(),
        'postcode' => (string) $order->get_billing_postcode(),
        'country' => (string) $order->get_billing_country(),
        'email' => (string) $order->get_billing_email(),
        'phone' => (string) $order->get_billing_phone(),
    ];

    $shipping = [
        'first_name' => (string) $order->get_shipping_first_name(),
        'last_name' => (string) $order->get_shipping_last_name(),
        'company' => (string) $order->get_shipping_company(),
        'address_1' => (string) $order->get_shipping_address_1(),
        'address_2' => (string) $order->get_shipping_address_2(),
        'city' => (string) $order->get_shipping_city(),
        'state' => (string) $order->get_shipping_state(),
        'postcode' => (string) $order->get_shipping_postcode(),
        'country' => (string) $order->get_shipping_country(),
    ];

    // Documentos e campos extras, quando disponíveis
    $cpf = $order->get_meta('_billing_cpf', true);
    if (!$cpf)
        $cpf = get_post_meta($order_id, '_billing_cpf', true);

    $items = [];
    if ($opts['include_items'] && method_exists($order, 'get_items')) {
        foreach ($order->get_items() as $it) {
            $pid = (int) $it->get_product_id();
            $vid = method_exists($it, 'get_variation_id') ? (int) $it->get_variation_id() : 0;

            // Resolve o WC_Product do item, incluindo variações
            $product = null;
            if (method_exists($it, 'get_product')) {
                $product = $it->get_product(); // pode ser variação
            }
            if (!$product && function_exists('wc_get_product')) {
                $product = wc_get_product($vid ?: $pid);
            }

            // Resolve a imagem priorizando variação, produto e placeholder
            $image_url = null;
            if ($product && method_exists($product, 'get_image_id')) {
                $img_id = (int) $product->get_image_id();
                if ($img_id) {
                    // Tamanho adequado para listagens do app
                    $image_url = wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail');
                    if (!$image_url)
                        $image_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                    if (!$image_url)
                        $image_url = wp_get_attachment_image_url($img_id, 'full');
                }
            }
            if (!$image_url && function_exists('wc_placeholder_img_src')) {
                $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
            }

            // Link do produto pai
            $permalink = $pid ? get_permalink($pid) : null;

            $items[] = [
                'item_id' => (int) $it->get_id(),
                'product_id' => $pid,
                'variation_id' => $vid ?: null,
                'name' => (string) $it->get_name(),
                'qty' => (int) $it->get_quantity(),
                'total' => (string) $it->get_total(),
                'subtotal' => (string) $it->get_subtotal(),

                // Campos adicionais do item
                'image' => $image_url,
                'permalink' => $permalink,
            ];
        }
    }

    // Metadados de atribuição do WooCommerce
    $attribution = null;
    if ($opts['include_attribution']) {
        $keys = [
            '_wc_order_attribution_source_type',
            '_wc_order_attribution_referrer',
            '_wc_order_attribution_utm_source',
            '_wc_order_attribution_utm_medium',
            '_wc_order_attribution_utm_campaign',
            '_wc_order_attribution_utm_content',
            '_wc_order_attribution_utm_term',
            '_wc_order_attribution_device_type',
            '_wc_order_attribution_session_pages',
        ];

        $attribution = [];
        foreach ($keys as $k) {
            $v = $order->get_meta($k, true);
            if ($v !== '' && $v !== null)
                $attribution[$k] = $v;
        }

        if (empty($attribution))
            $attribution = null;
    }

    // Métricas do cliente exibidas no histórico
    $customer_stats = null;
    if ($opts['include_customer_stats'] && $customer_id > 0) {
        $total_orders = function_exists('wc_get_customer_order_count')
            ? (int) wc_get_customer_order_count($customer_id)
            : null;

        $total_spent = function_exists('wc_get_customer_total_spent')
            ? (string) wc_get_customer_total_spent($customer_id)
            : null;

        $avg = null;
        if ($total_orders && $total_spent !== null && is_numeric($total_spent)) {
            $avg = (string) ((float) $total_spent / (float) $total_orders);
        }

        $customer_stats = [
            'total_orders' => $total_orders,
            'total_spent' => $total_spent,
            'avg_order' => $avg,
        ];
    }

    // Metadados completos do pedido
    $meta = null;
    if ($opts['include_meta']) {
        $meta = [];
        foreach ($order->get_meta_data() as $m) {
            $k = $m->key;
            $v = $m->value;
            if (is_array($v) || is_object($v)) {
                $v = wp_json_encode($v);
            }
            $meta[$k] = $v;
        }
    }

    return [
        'id' => $order_id,
        'number' => (string) $order->get_order_number(),
        'status' => (string) $order->get_status(),

        'created_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
        'updated_at' => $order->get_date_modified() ? $order->get_date_modified()->date('c') : null,
        'paid_at' => $order->get_date_paid() ? $order->get_date_paid()->date('c') : null,

        'currency' => (string) $order->get_currency(),
        'totals' => [
            'subtotal' => (string) $order->get_subtotal(),
            'discount_total' => (string) $order->get_discount_total(),
            'shipping_total' => (string) $order->get_shipping_total(),
            'tax_total' => (string) $order->get_total_tax(),
            'total' => (string) $order->get_total(),
        ],

        'payment' => [
            'method' => (string) $order->get_payment_method(),
            'method_title' => (string) $order->get_payment_method_title(),
            'transaction_id' => (string) $order->get_transaction_id(),
        ],

        'customer' => [
            'user_id' => $customer_id ?: null,
            'ip' => (string) $order->get_customer_ip_address(),
            'user_agent' => (string) $order->get_customer_user_agent(),
        ],

        'billing' => array_merge($billing, [
            'cpf' => $cpf ?: null,
        ]),
        'shipping' => $shipping,

        'items' => $items,

        'attribution' => $attribution,
        'customer_stats' => $customer_stats,
        'meta' => $meta,
    ];
}
