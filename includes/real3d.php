<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Descobre post types que parecem ser do Real3D (varia por versão/addon).
 */
function app_api_real3d_guess_flipbook_post_types(): array
{
    $types = get_post_types([], 'names');
    $out = [];

    foreach ($types as $pt) {
        $ptl = strtolower((string) $pt);
        if (strpos($ptl, 'flipbook') !== false || strpos($ptl, 'real3d') !== false || $ptl === 'r3d') {
            $out[] = (string) $pt;
        }
    }

    // Fallback comum
    $out[] = 'real3dflipbook';

    return array_values(array_unique(array_filter($out)));
}

/**
 * Extrai inteiros de valores (suporta serializado, arrays, strings com shortcode id="123").
 */
function app_api_real3d_extract_ints($value): array
{
    if ($value === null || $value === false)
        return [];

    if (is_string($value)) {
        $maybe = @maybe_unserialize($value);
        if ($maybe !== $value) {
            return app_api_real3d_extract_ints($maybe);
        }
    }

    $ints = [];

    if (is_numeric($value)) {
        $n = intval($value);
        return $n > 0 ? [$n] : [];
    }

    if (is_array($value)) {
        foreach ($value as $v)
            $ints = array_merge($ints, app_api_real3d_extract_ints($v));
        return array_values(array_unique(array_filter(array_map('intval', $ints))));
    }

    if (is_object($value)) {
        foreach (get_object_vars($value) as $v)
            $ints = array_merge($ints, app_api_real3d_extract_ints($v));
        return array_values(array_unique(array_filter(array_map('intval', $ints))));
    }

    if (is_string($value)) {
        // id="123"
        if (preg_match_all('/\bid\s*=\s*["\']?(\d+)["\']?/i', $value, $m)) {
            foreach ($m[1] as $x)
                $ints[] = intval($x);
        }
        // números soltos (último recurso)
        if (preg_match_all('/\b(\d{1,9})\b/', $value, $m2)) {
            foreach ($m2[1] as $x)
                $ints[] = intval($x);
        }
    }

    return array_values(array_unique(array_filter(array_map('intval', $ints))));
}

/**
 * Detecta se existe mídia do flipbook no uploads (thumb).
 *
 * Ajuda quando o ID do flipbook colide com um post do WP e/ou quando a opção
 * real3dflipbook_{id} não existe ou está corrompida.
 */
function app_api_real3d_flipbook_media_exists($flipbook_id)
{
    $flipbook_id = (int) $flipbook_id;
    if ($flipbook_id <= 0)
        return false;

    $uploads = wp_upload_dir();
    $base = isset($uploads['basedir']) ? $uploads['basedir'] : '';
    if (!$base)
        return false;

    $base = rtrim($base, '/');

    $thumb = $base . '/real3d-flipbook/flipbook_' . $flipbook_id . '/thumb.jpg';
    if (file_exists($thumb))
        return true;

    // Algumas instalações usam extensões diferentes
    $thumb2 = $base . '/real3d-flipbook/flipbook_' . $flipbook_id . '/thumb.jpeg';
    if (file_exists($thumb2))
        return true;

    $thumb3 = $base . '/real3d-flipbook/flipbook_' . $flipbook_id . '/thumb.png';
    if (file_exists($thumb3))
        return true;

    return false;
}

/**
 * Valida se um ID é de um "flipbook Real3D" (checa post_type).
 */
function app_api_real3d_is_flipbook_post_id(int $id): bool
{
    if ($id <= 0)
        return false;

    $pt = get_post_type($id);
    if (!$pt)
        return false;

    $pt = strtolower((string) $pt);
    $allowed = array_map('strtolower', app_api_real3d_guess_flipbook_post_types());

    // Aceita diretamente itens já liberados
    if (in_array($pt, $allowed, true))
        return true;

    // Heurística adicional
    if (strpos($pt, 'flipbook') !== false || strpos($pt, 'real3d') !== false || $pt === 'r3d')
        return true;

    return false;
}

/**
 * Lê o(s) flipbook(s) vinculados ao produto.
 * Retorna:
 * [
 *   'purchased' => <id|null>,
 *   'non_purchased' => <id|null>,
 * ]
 *
 * Estratégia: varrer TODAS as metas do produto e localizar IDs que são flipbooks.
 */
function app_api_real3d_get_product_flipbooks(int $product_id): array
{
    $product_id = intval($product_id);
    if ($product_id <= 0)
        return ['purchased' => null, 'non_purchased' => null];

    $meta = get_post_meta($product_id);
    $cand_purchased = [];
    $cand_non = [];
    $cand_unknown = [];

    foreach (($meta ?: []) as $meta_key => $arr) {
        $key = strtolower((string) $meta_key);
        $raw = is_array($arr) ? ($arr[0] ?? null) : $arr;

        $ints = app_api_real3d_extract_ints($raw);
        if (!$ints)
            continue;

        foreach ($ints as $id) {
            if (!app_api_real3d_is_flipbook_post_id((int) $id))
                continue;

            // Tenta classificar pelo nome da meta_key
            if (strpos($key, 'non') !== false || strpos($key, 'not') !== false) {
                $cand_non[] = (int) $id;
            } elseif (strpos($key, 'purchased') !== false || strpos($key, 'bought') !== false || strpos($key, 'compr') !== false) {
                $cand_purchased[] = (int) $id;
            } else {
                $cand_unknown[] = (int) $id;
            }
        }
    }

    $cand_purchased = array_values(array_unique($cand_purchased));
    $cand_non = array_values(array_unique($cand_non));
    $cand_unknown = array_values(array_unique($cand_unknown));

    // Escolha final por prioridade
    $picked_p = $cand_purchased[0] ?? $cand_unknown[0] ?? null;
    $picked_n = $cand_non[0] ?? null;

    return [
        'purchased' => $picked_p ? intval($picked_p) : null,
        'non_purchased' => $picked_n ? intval($picked_n) : null,
    ];
}

/**
 * Lê a config do flipbook pelo storage do Real3D (wp_options).
 * Real3D costuma salvar cada flipbook em real3dflipbook_<id>.
 */
function app_api_real3d_get_flipbook_option(int $flipbook_id): ?array
{
    if ($flipbook_id <= 0)
        return null;

    $opt = get_option('real3dflipbook_' . $flipbook_id);
    return is_array($opt) ? $opt : null;
}

/**
 * Nome/título do flipbook (por edição).
 * Tentamos várias chaves comuns; fallback para string vazia.
 */
function app_api_real3d_get_flipbook_name(int $flipbook_id): ?string
{
    $opt = app_api_real3d_get_flipbook_option($flipbook_id);
    if (!$opt)
        return null;

    $keys = [
        'name',
        'title',
        'bookTitle',
        'flipbookTitle',
        'pdfName',
    ];

    foreach ($keys as $k) {
        if (!empty($opt[$k]) && is_string($opt[$k])) {
            $s = trim($opt[$k]);
            if ($s !== '')
                return $s;
        }
    }

    return null;
}

/**
 * Thumbnail/capa do flipbook (se existir). O Real3D usa lightboxThumbnailUrl em vários casos. :contentReference[oaicite:1]{index=1}
 */
function app_api_real3d_get_flipbook_thumb(int $flipbook_id): ?string
{
    $opt = app_api_real3d_get_flipbook_option($flipbook_id);

    // Prioriza a configuração salva pelo Real3D
    if (is_array($opt)) {
        $keys = [
            'lightboxThumbnailUrl',
            'thumbnail',
            'thumb',
            'thumbUrl',
            'cover',
            'coverUrl',
        ];

        foreach ($keys as $k) {
            if (!empty($opt[$k]) && is_string($opt[$k])) {
                $u = trim($opt[$k]);
                if ($u !== '') {
                    if (function_exists('app_api_force_https_url')) {
                        $u = app_api_force_https_url($u);
                    }
                    return esc_url_raw($u);
                }
            }
        }
    }

    // Fallback para thumbs salvas em uploads
    $uploads = wp_upload_dir();
    $basedir = isset($uploads['basedir']) ? rtrim((string) $uploads['basedir'], '/') : '';
    $baseurl = isset($uploads['baseurl']) ? rtrim((string) $uploads['baseurl'], '/') : '';

    if ($basedir && $baseurl) {
        $dir = $basedir . '/real3d-flipbook/flipbook_' . (int) $flipbook_id;
        $cands = [
            $dir . '/thumb.jpg',
            $dir . '/thumb.jpeg',
            $dir . '/thumb.png',
        ];

        foreach ($cands as $fp) {
            if (file_exists($fp)) {
                $rel = str_replace($basedir, '', $fp);
                $rel = ltrim($rel, '/');
                $u = $baseurl . '/' . $rel;
                $u .= '?v=' . @filemtime($fp);
                if (function_exists('app_api_force_https_url')) {
                    $u = app_api_force_https_url($u);
                }
                return esc_url_raw($u);
            }
        }
    }

    return null;
}

function app_api_month_short_pt(int $m): string
{
    $map = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
    return $map[$m] ?? '';
}

/**
 * Tenta inferir mês/ano do texto:
 * - 01/2026, 01-2026
 * - 2026-01
 * - JAN-26, JAN/2026, Janeiro 2026 etc.
 */
function app_api_parse_year_month_from_text($text): array
{
    $s = strtolower(trim((string) $text));
    if ($s === '')
        return ['year' => null, 'month' => null];

    // Formato mm/yyyy
    if (preg_match('/\b(0?[1-9]|1[0-2])\s*[\/\-]\s*(20\d{2})\b/', $s, $m)) {
        return ['month' => (int) $m[1], 'year' => (int) $m[2]];
    }

    // Formato yyyy/mm
    if (preg_match('/\b(20\d{2})\s*[\/\-]\s*(0?[1-9]|1[0-2])\b/', $s, $m)) {
        return ['month' => (int) $m[2], 'year' => (int) $m[1]];
    }

    // Mês por nome em português e ano
    $months = [
        'jan' => 1,
        'janeiro' => 1,
        'fev' => 2,
        'fevereiro' => 2,
        'mar' => 3,
        'março' => 3,
        'marco' => 3,
        'abr' => 4,
        'abril' => 4,
        'mai' => 5,
        'maio' => 5,
        'jun' => 6,
        'junho' => 6,
        'jul' => 7,
        'julho' => 7,
        'ago' => 8,
        'agosto' => 8,
        'set' => 9,
        'setembro' => 9,
        'out' => 10,
        'outubro' => 10,
        'nov' => 11,
        'novembro' => 11,
        'dez' => 12,
        'dezembro' => 12,
    ];

    $month = null;
    foreach ($months as $k => $v) {
        if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $s)) {
            $month = $v;
            break;
        }
    }

    if ($month) {
        // Ano com quatro dígitos
        if (preg_match('/\b(20\d{2})\b/', $s, $m)) {
            return ['month' => $month, 'year' => (int) $m[1]];
        }

        // Ano com dois dígitos
        if (preg_match('/\b(?:jan|fev|mar|março|marco|abr|mai|jun|jul|ago|set|out|nov|dez)[a-zç]*\s*[\/\-]?\s*(\d{2})\b/u', $s, $m)) {
            $yy = (int) $m[1];
            return ['month' => $month, 'year' => 2000 + $yy];
        }

        return ['month' => $month, 'year' => null];
    }

    // Apenas ano
    if (preg_match('/\b(20\d{2})\b/', $s, $m)) {
        return ['month' => null, 'year' => (int) $m[1]];
    }

    return ['month' => null, 'year' => null];
}

function app_api_parse_edition_number_from_title($title): ?int
{
    $t = trim((string)$title);
    if ($t === '') return null;

    // Aceita formatos comuns de numeração
    if (preg_match('/\b(?:n[ºo]\.?|no\.?|n\.?|#)\s*(\d{1,6})\b/iu', $t, $m)) {
        $n = intval($m[1]);
        return $n > 0 ? $n : null;
    }

    // Fallback para o último número do título
    if (preg_match('/(\d{1,6})(?!.*\d)/', $t, $m2)) {
        $n = intval($m2[1]);
        return $n > 0 ? $n : null;
    }

    return null;
}

/**
 * Converte o ID interno do Real3D (ex: 240) para o post_id do WP (post_type=r3d)
 * buscando pelo meta_key = 'flipbook_id'.
 */
function app_api_real3d_post_id_from_flipbook_id(int $flipbook_id): int
{
    $flipbook_id = (int) $flipbook_id;
    if ($flipbook_id <= 0)
        return 0;

    $cache_key = 'app_api_r3d_post_id_' . $flipbook_id;
    $cached = get_transient($cache_key);
    if ($cached !== false)
        return (int) $cached;

    global $wpdb;

    $posts = $wpdb->posts;
    $postmeta = $wpdb->postmeta;

    $post_id = (int) $wpdb->get_var($wpdb->prepare("
        SELECT p.ID
        FROM {$posts} p
        INNER JOIN {$postmeta} pm ON pm.post_id = p.ID
        WHERE p.post_type = %s
          AND pm.meta_key = %s
          AND pm.meta_value = %s
        LIMIT 1
    ", 'r3d', 'flipbook_id', (string) $flipbook_id));

    set_transient($cache_key, $post_id ?: 0, 10 * MINUTE_IN_SECONDS);
    return $post_id ?: 0;
}

/**
 * Pega as categorias (r3d_category) do flipbook e inclui cadeia pai -> filho.
 * Retorna array com [parent, child, ...].
 */
function app_api_real3d_get_flipbook_categories(int $flipbook_id): array
{
    $flipbook_id = (int) $flipbook_id;
    if ($flipbook_id <= 0)
        return [];

    $cache_key = 'app_api_r3d_cats_' . $flipbook_id;
    $cached = get_transient($cache_key);
    if (is_array($cached))
        return $cached;

    if (!taxonomy_exists('r3d_category')) {
        set_transient($cache_key, [], 10 * MINUTE_IN_SECONDS);
        return [];
    }

    // Primeiro tenta buscar termos diretamente pelo object_id
    $terms = wp_get_object_terms($flipbook_id, 'r3d_category', ['hide_empty' => false]);

    // Fallback para instalações que vinculam categoria ao CPT r3d
    if (empty($terms) || is_wp_error($terms)) {
        $post_id = app_api_real3d_post_id_from_flipbook_id($flipbook_id);
        if ($post_id) {
            $terms = wp_get_post_terms($post_id, 'r3d_category', ['hide_empty' => false]);
        } else {
            $terms = [];
        }
    }

if (is_wp_error($terms) || empty($terms)) {
        set_transient($cache_key, [], 10 * MINUTE_IN_SECONDS);
        return [];
    }

    $out = [];
    $seen = [];

    $push = function ($term) use (&$out, &$seen) {
        if (!is_object($term) || empty($term->term_id))
            return;
        $k = 'r3d_category:' . (int) $term->term_id;
        if (isset($seen[$k]))
            return;
        $seen[$k] = true;

        $out[] = [
            'id' => (int) $term->term_id,
            'name' => (string) $term->name,
            'slug' => (string) $term->slug,
            'parent' => (int) $term->parent,
            'taxonomy' => (string) $term->taxonomy, // r3d_category
        ];
    };

    foreach ($terms as $t) {
        // Monta a cadeia de pais
        $stack = [];
        $cur = $t;

        while ($cur && !empty($cur->parent)) {
            $parent = get_term((int) $cur->parent, 'r3d_category');
            if (!$parent || is_wp_error($parent))
                break;
            $stack[] = $parent;
            $cur = $parent;
        }

        // Ordena do termo raiz ao filho
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            $push($stack[$i]);
        }

        // Inclui o termo final
        $push($t);
    }

    set_transient($cache_key, $out, 10 * MINUTE_IN_SECONDS);
    return $out;
}


/**
 * Retorna IDs (internos do Real3D / shortcode) dos flipbooks que estão em uma ou mais categorias (r3d_category).
 * - $term_ids: IDs dos termos
 * - $include_children: inclui subcategorias automaticamente
 */
function app_api_real3d_get_flipbook_ids_by_category_terms(array $term_ids, bool $include_children = true): array
{
    if (!taxonomy_exists('r3d_category'))
        return [];

    $term_ids = array_values(array_unique(array_filter(array_map('intval', $term_ids))));
    if (empty($term_ids))
        return [];

    // Expande filhos quando a relação é feita direto no object_id
    $expanded = $term_ids;
    if ($include_children) {
        foreach ($term_ids as $tid) {
            $children = get_term_children((int) $tid, 'r3d_category');
            if (is_wp_error($children) || empty($children))
                continue;
            foreach ($children as $c)
                $expanded[] = (int) $c;
        }
    }
    $expanded = array_values(array_unique(array_filter(array_map('intval', $expanded))));
    if (empty($expanded))
        return [];

    $cache_key = 'app_api_r3d_fids_terms_' . md5(implode(',', $expanded));
    $cached = get_transient($cache_key);
    if (is_array($cached))
        return $cached;

    global $wpdb;

    // Busca object_id diretamente na relação de termos
    // No Real3D, o object_id costuma ser o ID interno do flipbook.
    $rel = $wpdb->term_relationships;
    $tt  = $wpdb->term_taxonomy;

    $placeholders = implode(',', array_fill(0, count($expanded), '%d'));
    $sql = "
        SELECT DISTINCT tr.object_id
        FROM {$rel} tr
        INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
        WHERE tt.taxonomy = %s
          AND tt.term_id IN ($placeholders)
    ";

    $params = array_merge(['r3d_category'], $expanded);
    $rows = $wpdb->get_col($wpdb->prepare($sql, ...$params));

    $flipbook_ids = [];

    foreach (($rows ?: []) as $oid) {
        $oid = (int) $oid;
        if ($oid <= 0)
            continue;

        // Se existir opção do Real3D, trata como flipbook interno
        $opt = get_option('real3dflipbook_' . $oid, null);
        if ($opt !== null && $opt !== false) {
            $flipbook_ids[] = $oid;
            continue;
        }

        // Se existir mídia em uploads, trata como flipbook interno
                if (app_api_real3d_flipbook_media_exists($oid)) {
            $flipbook_ids[] = $oid;
            continue;
        }

        // Se for post do WordPress, tenta extrair o flipbook_id
        $pt = get_post_type($oid);
        if ($pt) {
            $found = false;

            foreach (['flipbook_id', 'r3d_flipbook_id', 'real3dflipbook_id', '_r3d_flipbook_id'] as $mk) {
                $val = get_post_meta($oid, $mk, true);
                if ($val === '' || $val === null)
                    continue;

                $ints = app_api_real3d_extract_ints($val);
                foreach (($ints ?: []) as $x) {
                    $x = (int) $x;
                    if ($x > 0) {
                        $flipbook_ids[] = $x;
                        $found = true;
                    }
                }

                if ($found)
                    break;
            }

            if (!$found) {
                $post = get_post($oid);
                if ($post && isset($post->post_content) && is_string($post->post_content)) {
                    if (preg_match('/\[real3dflipbook[^\]]*\bid=["\']?(\d+)/i', $post->post_content, $m)) {
                        $fid = (int) $m[1];
                        if ($fid > 0) {
                            $flipbook_ids[] = $fid;
                            $found = true;
                        }
                    }
                }
            }

            // Se for o CPT do flipbook sem meta, usa o object_id
            if (!$found && app_api_real3d_is_flipbook_post_id($oid)) {
                $flipbook_ids[] = $oid;
            }
        }
    }

    $flipbook_ids = array_values(array_unique(array_filter(array_map('intval', $flipbook_ids))));

    set_transient($cache_key, $flipbook_ids, 10 * MINUTE_IN_SECONDS);
    return $flipbook_ids;
}




// =========================================================
// Helpers de PDF para o viewer nativo
// =========================================================

if (!function_exists('app_api_real3d_get_flipbook_pdf_info')) {
    /**
     * Retorna informações do PDF associado ao flipbook.
     * - Se conseguir mapear para arquivo local (uploads), retorna ['url'=>..., 'path'=>...]
     * - Se for URL remota, retorna ['url'=>..., 'path'=>null]
     */
    function app_api_real3d_get_flipbook_pdf_info(int $flipbook_id): ?array
    {
        if ($flipbook_id <= 0) return null;

        $uploads = wp_upload_dir();
        $basedir = isset($uploads['basedir']) ? rtrim((string)$uploads['basedir'], '/') : '';
        $baseurl = isset($uploads['baseurl']) ? rtrim((string)$uploads['baseurl'], '/') : '';

        // Tenta localizar PDF na pasta do flipbook
        if ($basedir && $baseurl) {
            $dir = $basedir . '/real3d-flipbook/flipbook_' . $flipbook_id;
            if (is_dir($dir)) {
                $pdfs = glob($dir . '/*.pdf');
                if (!$pdfs) {
                    $pdfs = glob($dir . '/*.PDF');
                }
                if ($pdfs) {
                    $fp = $pdfs[0];
                    if (file_exists($fp)) {
                        $rel = str_replace($basedir, '', $fp);
                        $rel = ltrim($rel, '/');
                        $u = $baseurl . '/' . $rel;
                        $u = function_exists('app_api_force_https_url') ? app_api_force_https_url($u) : $u;
                        return ['url' => esc_url_raw($u), 'path' => $fp];
                    }
                }
            }
        }

        // Tenta localizar a URL do PDF na configuração do flipbook
        $opt = function_exists('app_api_real3d_get_flipbook_option') ? app_api_real3d_get_flipbook_option($flipbook_id) : null;
        if (is_array($opt)) {
            $candidates = [];

            // Chaves mais comuns
            foreach (['pdfUrl','pdfURL','pdf','pdfSource','source','url'] as $k) {
                if (!empty($opt[$k]) && is_string($opt[$k])) {
                    $candidates[] = trim((string)$opt[$k]);
                }
            }

            // Varredura simples no primeiro nível
            foreach ($opt as $k => $v) {
                if (is_string($v) && stripos($v, '.pdf') !== false) {
                    $candidates[] = trim($v);
                }
            }

            for ($i = 0; $i < count($candidates); $i++) {
                $u = $candidates[$i];
                if (!$u) continue;
                if (stripos($u, '.pdf') === false) continue;

                $u = function_exists('app_api_force_https_url') ? app_api_force_https_url($u) : $u;

                // Tenta mapear a URL para caminho local
                if (function_exists('app_api_map_upload_url_to_path')) {
                    $path = app_api_map_upload_url_to_path($u);
                    if ($path) {
                        return ['url' => esc_url_raw($u), 'path' => $path];
                    }
                }

                return ['url' => esc_url_raw($u), 'path' => null];
            }
        }

        return null;
    }
}
