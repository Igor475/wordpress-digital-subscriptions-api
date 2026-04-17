<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================
 *  IA (Recomendações por página) - CPAD (VTEX)
 * ==========================================
 *
 * Objetivo:
 * - Receber texto da página (quando existir) e/ou snapshot (JPEG base64) do app
 * - Identificar possíveis livros/produtos mencionados/visíveis
 * - Buscar produtos REAIS na loja CPAD (www.cpad.com.br) via VTEX Catalog API
 * - Retornar cards com título, imagem, preço e link real (com UTM)
 * - Registrar eventos (impressão / clique) para métricas
 *
 * Importante:
 * - Se a OpenAI falhar, ainda tentamos heurísticas + busca VTEX.
 */

function app_api_ai_enabled(): bool {
    return (bool) get_option('app_api_ai_enabled', false);
}

function app_api_ai_openai_api_key(): string {
    return (string) get_option('app_api_openai_api_key', '');
}

function app_api_ai_openai_model(): string {
    return (string) get_option('app_api_openai_model', 'gpt-4o-mini');
}

function app_api_ai_cpad_base_url(): string {
    $u = (string) get_option('app_api_ai_cpad_base_url', 'https://www.cpad.com.br');
    $u = trim($u);
    if ($u === '') $u = 'https://www.cpad.com.br';
    return rtrim($u, '/');
}

function app_api_ai_max_products(): int {
    $n = (int) get_option('app_api_ai_max_products', 6);
    if ($n < 1) $n = 1;
    if ($n > 12) $n = 12;
    return $n;
}

function app_api_ai_cache_ttl_seconds(): int {
    $ttl = (int) get_option('app_api_ai_cache_ttl_seconds', 3600);
    if ($ttl < 0) $ttl = 0;
    if ($ttl > 86400 * 7) $ttl = 86400 * 7;
    return $ttl;
}

function app_api_ai_utm_params(int $magazine_product_id, int $page): array {
    return [
        'utm_source' => 'app',
        'utm_medium' => 'pdf_ai',
        'utm_campaign' => 'magazine_' . $magazine_product_id,
        'utm_content' => 'page_' . $page,
    ];
}

function app_api_ai_append_utm(string $url, array $utm): string {
    if (!$url) return $url;

    $parts = wp_parse_url($url);
    $q = [];
    if (!empty($parts['query'])) parse_str($parts['query'], $q);

    foreach ($utm as $k => $v) {
        if (!isset($q[$k])) $q[$k] = $v;
    }

    $newQuery = http_build_query($q);
    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '';
    $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    if (!$host) {
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . $newQuery;
    }

    return $scheme . '://' . $host . $port . $path . ($newQuery ? '?' . $newQuery : '') . $frag;
}

function app_api_ai_reco_id_from_query(string $q): int {
    // Gera um ID estável para tracking, mesmo sem WooCommerce
    return (int) sprintf('%u', crc32($q));
}

function app_api_ai_http_get_json(string $url, int $timeout = 15): array {
    $res = wp_remote_get($url, [
        'timeout' => $timeout,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
    if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];
    $code = (int) wp_remote_retrieve_response_code($res);
    $body = (string) wp_remote_retrieve_body($res);
    $json = json_decode($body, true);
    if ($code < 200 || $code >= 300 || !is_array($json)) {
        return ['ok' => false, 'error' => 'http_' . $code, 'status' => $code, 'body' => $body];
    }
    return ['ok' => true, 'data' => $json];
}

function app_api_ai_slugify_query(string $q): string {
    $q = trim($q);
    $q = preg_replace('/\s+/', ' ', $q);
    return $q;
}

function app_api_ai_normalize(string $s): string {
    $s = (string) $s;
    $s = wp_strip_all_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = mb_strtolower($s, 'UTF-8');
    // Remove acentos
    if (function_exists('remove_accents')) $s = remove_accents($s);
    $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function app_api_ai_tokenize(string $s): array {
    $s = app_api_ai_normalize($s);
    if ($s === '') return [];
    $parts = preg_split('/\s+/', $s);
    $stop = [
        'a','o','os','as','um','uma','uns','umas','de','da','do','das','dos','e','em','no','na','nos','nas',
        'para','por','com','sem','ao','aos','à','às','que','como','sobre','entre','ser','sua','seu','suas','seus',
        'livro','livros','revista','cpad','editora'
    ];
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '' || mb_strlen($p) < 2) continue;
        if (in_array($p, $stop, true)) continue;
        $out[] = $p;
    }
    return array_values(array_unique($out));
}


function app_api_ai_clean_query(string $q): string {
    $q = trim((string) $q);
    $q = preg_replace('/\s+/', ' ', $q);
    // Remove aspas e pontuação periférica comum em OCR
    $q = preg_replace('/^[\"\'\“\”\‘\’\-–—\s]+|[\"\'\“\”\‘\’\-–—\s]+$/u', '', $q);
    $q = trim(preg_replace('/\s+/', ' ', $q));
    return $q;
}

function app_api_ai_is_generic_query(string $q): bool {
    $n = app_api_ai_normalize($q);
    if ($n === '') return true;

    // Ignora termos muito curtos ou com um único token
    $t = app_api_ai_tokenize($q);
    if (count($t) < 2 && mb_strlen($n) < 10) return true;

    // Ignora termos editoriais genéricos
    $generic = [
        'devocional','editorial','sumario','indice','apresentacao','licao','licoes','introducao',
        'mensagem','noticias','artigo','reflexao','conteudo','reportagem','entrevista',
        'em breve','em um piscar de olhos','uma analise precisa','internacional'
    ];
    foreach ($generic as $g) {
        if ($n === $g) return true;
    }

    // Ignora termos excessivamente genéricos
    if (preg_match('/\b(devocional|editorial|sumario|indice|apresentacao)\b/u', $n)) return true;

    return false;
}

function app_api_ai_is_similar_to_title(string $q, string $title, float $threshold = 88.0): bool {
    $qN = app_api_ai_normalize($q);
    $tN = app_api_ai_normalize($title);
    if ($qN === '' || $tN === '') return false;

    // Ignora termos que repetem o título da revista
    if (strpos($qN, $tN) !== false || strpos($tN, $qN) !== false) return true;

    $pct = 0.0;
    similar_text($qN, $tN, $pct);
    return ($pct >= $threshold);
}


function app_api_ai_score_match(string $query, string $title): float {
    $qTokens = app_api_ai_tokenize($query);
    $tTokens = app_api_ai_tokenize($title);

    if (!$qTokens || !$tTokens) {
        // Fallback usando similar_text
        $qN = app_api_ai_normalize($query);
        $tN = app_api_ai_normalize($title);
        $pct = 0.0;
        similar_text($qN, $tN, $pct);
        return (float) $pct;
    }

    $setQ = array_fill_keys($qTokens, true);
    $setT = array_fill_keys($tTokens, true);

    $inter = 0;
    foreach ($setQ as $k => $_) if (isset($setT[$k])) $inter++;

    $precision = $inter / max(1, count($setT));
    $recall = $inter / max(1, count($setQ));
    $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0;

    $qN = implode(' ', $qTokens);
    $tN = implode(' ', $tTokens);
    $pct = 0.0;
    similar_text($qN, $tN, $pct); // 0..100

    // Score de 0 a 100
    return (float) (70.0 * $f1 * 100.0 + 30.0 * $pct);
}

function app_api_ai_vtex_search(string $query, int $from = 0, int $to = 24): array {
    $base = app_api_ai_cpad_base_url();
    $q = rawurlencode(app_api_ai_slugify_query($query));
    $url = $base . '/api/catalog_system/pub/products/search?ft=' . $q . '&_from=' . intval($from) . '&_to=' . intval($to);
    return app_api_ai_http_get_json($url, 20);
}


function app_api_ai_build_search_variants(string $q): array {
    $q = app_api_ai_clean_query($q);
    if ($q === '') return [];

    $vars = [$q];

    // Sem acentos
    if (function_exists('remove_accents')) {
        $ra = remove_accents($q);
        if ($ra && $ra !== $q) $vars[] = $ra;
    }

    // Remove stopwords para melhorar o recall na busca da VTEX
    $tokens = app_api_ai_tokenize($q);
    if (count($tokens) >= 2) {
        $vars[] = implode(' ', array_slice($tokens, 0, 8));
    }

    // Remove caracteres comumente confundidos pelo OCR
    $clean = preg_replace('/[^\p{L}0-9\s]/u', ' ', $q);
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    if ($clean && $clean !== $q) $vars[] = $clean;

    // Remove duplicados
    $out = [];
    $seen = [];
    foreach ($vars as $v) {
        $v = trim((string) $v);
        if ($v === '') continue;
        $k = app_api_ai_normalize($v);
        if ($k === '' || isset($seen[$k])) continue;
        $seen[$k] = true;
        $out[] = $v;
        if (count($out) >= 4) break;
    }
    return $out;
}


function app_api_ai_vtex_to_item(array $p): ?array {
    $base = app_api_ai_cpad_base_url();
    $title = (string) ($p['productName'] ?? $p['productTitle'] ?? $p['product_name'] ?? '');
    if ($title === '') return null;

    $linkText = (string) ($p['linkText'] ?? '');
    $productId = (string) ($p['productId'] ?? $p['product_id'] ?? '');

    $url = '';
    if ($linkText !== '') {
        $url = $base . '/' . ltrim($linkText, '/') . '/p';
    } elseif ($productId !== '') {
        // Fallback para busca semântica no site
        $url = $base . '/' . rawurlencode($title) . '?map=ft';
    } else {
        $url = $base . '/' . rawurlencode($title) . '?map=ft';
    }

    $img = null;
    $price = null;

    // Extrai dados de imagem
    if (!empty($p['items'][0]['images'][0]['imageUrl'])) {
        $img = (string) $p['items'][0]['images'][0]['imageUrl'];
    } elseif (!empty($p['items'][0]['images'][0]['imageUrl'])) {
        $img = (string) $p['items'][0]['images'][0]['imageUrl'];
    }

    // Extrai preço
    $offer = $p['items'][0]['sellers'][0]['commertialOffer'] ?? null;
    if (is_array($offer)) {
        $pr = $offer['Price'] ?? null;
        $list = $offer['ListPrice'] ?? null;
        if (is_numeric($pr)) {
            $price = 'R$ ' . number_format((float) $pr, 2, ',', '.');
            if (is_numeric($list) && (float)$list > (float)$pr) {
                $price .= ' (de R$ ' . number_format((float) $list, 2, ',', '.') . ')';
            }
        }
    }

    return [
        'title' => $title,
        'url' => $url,
        'image_url' => $img,
        'price' => $price,
        'cpad_product_id' => $productId !== '' ? $productId : null,
    ];
}

function app_api_ai_extract_queries_from_text(string $text, int $limit = 10): array {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') return [];

    $candidates = [];

    // Sequências em Title Case
    if (preg_match_all('/\b([A-ZÁÉÍÓÚÂÊÔÃÕÇ][\p{L}\'’\-]+(?:\s+(?:d[aeo]s?|e|da|do|das|dos|de|a|o|as|os))?\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][\p{L}\'’\-]+(?:\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][\p{L}\'’\-]+){0,6})\b/u', $text, $m)) {
        foreach ($m[1] as $s) {
            $s = trim($s);
            if (mb_strlen($s) < 8) continue;
            $candidates[] = $s;
        }
    }

    // Sequências em caixa alta
    if (preg_match_all('/\b([A-ZÁÉÍÓÚÂÊÔÃÕÇ]{3,}(?:\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ]{2,}){1,8})\b/u', $text, $m2)) {
        foreach ($m2[1] as $s) {
            $s = trim($s);
            if (mb_strlen($s) < 8) continue;
            // Descarta ruído comum de capa
            if (preg_match('/\b(CPAD|EDITORA|REVISTA|SALA|LEITURA)\b/u', $s)) continue;
            $candidates[] = $s;
        }
    }

    // Deduplica por normalização
    $out = [];
    $seen = [];
    foreach ($candidates as $c) {
        $k = app_api_ai_normalize($c);
        if ($k === '' || isset($seen[$k])) continue;
        $seen[$k] = true;
        $out[] = $c;
        if (count($out) >= $limit) break;
    }

    return $out;
}

function app_api_ai_openai_chat(array $payload): array {
    $apiKey = app_api_ai_openai_api_key();
    if (!$apiKey) return ['ok' => false, 'error' => 'missing_api_key'];

    $res = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 45,
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = (string) wp_remote_retrieve_body($res);
    $json = json_decode($body, true);

    if ($code < 200 || $code >= 300) {
        $msg = is_array($json) ? (string) ($json['error']['message'] ?? 'openai_error') : 'openai_error';
        $type = is_array($json) ? (string) ($json['error']['type'] ?? '') : '';
        $errCode = is_array($json) ? (string) ($json['error']['code'] ?? '') : '';
        return ['ok' => false, 'error' => $msg, 'error_type' => $type, 'error_code' => $errCode, 'status' => $code, 'body' => $body];
    }

    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'openai_invalid_json', 'status' => $code, 'body' => $body];
    }

    return ['ok' => true, 'data' => $json, 'status' => $code];
}

function app_api_ai_sanitize_openai_error_message(string $msg): string {
    $msg = trim((string) $msg);
    if ($msg === '') return 'openai_error';
    // Remove links para não poluir a UI
    $msg = preg_replace('~https?://\S+~', '', $msg);
    $msg = trim(preg_replace('/\s+/', ' ', $msg));
    return $msg;
}

function app_api_ai_is_quota_error(string $msg, ?string $type = null, ?string $code = null): bool {
    $m = mb_strtolower((string) $msg, 'UTF-8');
    $t = mb_strtolower((string) $type, 'UTF-8');
    $c = mb_strtolower((string) $code, 'UTF-8');
    if ($c === 'insufficient_quota') return true;
    if ($t === 'insufficient_quota') return true;
    return (strpos($m, 'exceeded your current quota') !== false) || (strpos($m, 'insufficient_quota') !== false);
}

/**
 * Vision OCR + extração de anúncios:
 * Retorna produtos visíveis (título do livro/guia/bíblia) e, quando disponível, autor.
 * Saída: ['ok'=>bool, 'products'=>[...], 'error'=>string|null, 'raw_text'=>string|null]
 */
function app_api_ai_extract_products_with_vision(?string $page_text, ?string $title, string $image_b64, string $image_mime, int $limit = 8): array {
    $apiKey = app_api_ai_openai_api_key();
    if (!$apiKey) return ['ok' => false, 'products' => [], 'error' => 'missing_api_key', 'raw_text' => null];

    $model = app_api_ai_openai_model();

    $contextText = trim((string) $page_text);
    if ($contextText !== '') $contextText = mb_substr($contextText, 0, 1500);

    $hintTitle = trim((string) $title);
    if ($hintTitle !== '') $hintTitle = mb_substr($hintTitle, 0, 160);

    $system = "Você é um OCR e extrator de ANÚNCIOS de produtos da CPAD em páginas de revista (imagem).
" .
        "Sua tarefa é LER o que está escrito na página (especialmente CAPAS de livros/anúncios) e extrair SOMENTE nomes de produtos vendáveis.
" .
        "IGNORE: nome da revista, número da edição, número da página, seções como DEVOCIONAL, EDITORIAL, SUMÁRIO, títulos de matérias.
" .
        "Se houver capa de livro/anúncio, priorize o TÍTULO do produto (e autor se estiver muito claro).
" .
        "Retorne APENAS JSON no formato: {\"products\":[{\"title\":\"...\",\"author\":\"...\"}]}.
" .
        "Regras:
" .
        "- NÃO invente produtos.
" .
        "- Corrija OCR básico (acentos podem faltar).
" .
        "- Máximo " . intval($limit) . " produtos.
";

    $userParts = [];
    $userParts[] = ['type' => 'text', 'text' =>
        "Contexto:
" .
        "Título da edição (para ignorar): " . ($hintTitle ?: '(sem título)') . "
" .
        "Texto extraído (pode ajudar, mas pode ser notícia): " . ($contextText ?: '(sem texto)') . "

" .
        "Agora analise a IMAGEM e extraia produtos anunciados (livros/Guias/Bíblias)."
    ];

    $userParts[] = [
        'type' => 'image_url',
        'image_url' => [
            'url' => 'data:' . $image_mime . ';base64,' . $image_b64
        ]
    ];

    $payload = [
        'model' => $model,
        'temperature' => 0.0,
        'max_tokens' => 500,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userParts],
        ],
        'response_format' => ['type' => 'json_object'],
    ];

    $r = app_api_ai_openai_chat($payload);
    if (empty($r['ok']) || !is_array($r['data'] ?? null)) {
        $errRaw = (string) ($r['error'] ?? 'openai_error');
        $errType = isset($r['error_type']) ? (string) $r['error_type'] : null;
        $errCode = isset($r['error_code']) ? (string) $r['error_code'] : null;
        $err = app_api_ai_sanitize_openai_error_message($errRaw);
        if (app_api_ai_is_quota_error($errRaw, $errType, $errCode)) {
            $err = 'OpenAI sem saldo/créditos (insufficient_quota). Verifique faturamento/limites.';
        }
        // Faz fallback de modelo quando não há suporte a imagem
        if (is_string($err) && stripos($err, 'does not support') !== false) {
            $payload['model'] = 'gpt-4o-mini';
            $r2 = app_api_ai_openai_chat($payload);
            if ($r2['ok'] && is_array($r2['data'])) {
                $r = $r2;
            } else {
                return ['ok' => false, 'products' => [], 'error' => $err, 'error_type' => $errType, 'error_code' => $errCode, 'raw_text' => null];
            }
        } else {
            return ['ok' => false, 'products' => [], 'error' => $err, 'error_type' => $errType, 'error_code' => $errCode, 'raw_text' => null];
        }
    }

    $content = (string) ($r['data']['choices'][0]['message']['content'] ?? '');
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        if (preg_match('/\{.*\}/s', $content, $mm)) {
            $parsed = json_decode($mm[0], true);
        }
    }

    $products = [];
    if (is_array($parsed) && isset($parsed['products']) && is_array($parsed['products'])) {
        foreach ($parsed['products'] as $p) {
            if (!is_array($p)) continue;
            $t = app_api_ai_clean_query((string) ($p['title'] ?? ''));
            $a = app_api_ai_clean_query((string) ($p['author'] ?? ''));
            if ($t === '' || mb_strlen($t) < 4) continue;
            $products[] = ['title' => $t, 'author' => ($a !== '' ? $a : null)];
            if (count($products) >= $limit) break;
        }
    }

    return ['ok' => true, 'products' => $products, 'error' => null, 'raw_text' => null];
}

function app_api_ai_products_to_queries(array $products, string $magazine_title = '', int $limit = 15): array {
    $queries = [];
    foreach ($products as $p) {
        if (!is_array($p)) continue;
        $t = app_api_ai_clean_query((string) ($p['title'] ?? ''));
        $a = app_api_ai_clean_query((string) ($p['author'] ?? ''));
        if ($t === '') continue;

        // Filtra termos genéricos e o título da revista
        if ($magazine_title && app_api_ai_is_similar_to_title($t, $magazine_title)) continue;
        if (app_api_ai_is_generic_query($t)) continue;

        $queries[] = $t;
        if ($a !== '' && !$magazine_title) {
            $queries[] = $t . ' ' . $a;
        } elseif ($a !== '') {
            // Tenta novamente incluindo o autor
            $queries[] = $t . ' ' . $a;
        }
    }

    return app_api_ai_dedupe_queries($queries, $limit);
}



function app_api_ai_is_generic_heading(string $q): bool {
    $n = app_api_ai_normalize($q);
    if ($n === '') return true;

    // Remove seções comuns e ruído de PDFs de revista
    $bad = [
        'devocional','editorial','sumario','índice','indice','apresentacao','apresentação','carta ao leitor',
        'licao','lição','introducao','introdução','reflexao','reflexão','artigo','noticias','notícias',
        'jornal','revista','edicao','edição','numero','n','pagina','página','pag',
        'assine','assinatura','assinaturas','digital',
        'mensageiro da paz','ensinador cristao','ensinador cristão','obreiro aprovado'
    ];
    foreach ($bad as $b) {
        $bn = app_api_ai_normalize($b);
        if ($bn !== '' && ($n === $bn || strpos($n, $bn) === 0)) return true;
    }

    if (preg_match('/^\d+$/', $n)) return true;
    if (mb_strlen($n, 'UTF-8') < 6) return true;

    return false;
}

function app_api_ai_is_probably_magazine_title(string $q, string $editionTitle): bool {
    $qN = app_api_ai_normalize($q);
    $tN = app_api_ai_normalize($editionTitle);
    if ($qN === '' || $tN === '') return false;

    $qTokens = app_api_ai_tokenize($qN);
    $tTokens = app_api_ai_tokenize($tN);
    if (!$qTokens || !$tTokens) return false;

    $setQ = array_fill_keys($qTokens, true);
    $setT = array_fill_keys($tTokens, true);
    $inter = 0;
    foreach ($setQ as $k => $_) if (isset($setT[$k])) $inter++;
    $overlap = $inter / max(1, count($setT));

    if ($overlap >= 0.6) return true;
    if (strpos($qN, $tN) === 0) return true;
    return false;
}

function app_api_ai_build_queries_from_vision_products(array $products, string $editionTitle, int $limit = 15): array {
    $out = [];
    foreach ($products as $p) {
        if (!is_array($p)) continue;
        $title = trim((string) ($p['title'] ?? ''));
        $subtitle = trim((string) ($p['subtitle'] ?? ''));
        $author = trim((string) ($p['author'] ?? ''));
        $isbn = preg_replace('/\D+/', '', (string) ($p['isbn'] ?? ''));

        $cands = [];
        if ($isbn && (strlen($isbn) === 10 || strlen($isbn) === 13)) $cands[] = $isbn;
        $full = trim($title . ($subtitle ? (' ' . $subtitle) : ''));
        if ($full !== '') $cands[] = $full;
        if ($title !== '') $cands[] = $title;
        if ($title !== '' && $author !== '') $cands[] = trim($title . ' ' . $author);

        foreach ($cands as $q) {
            $q = trim((string) $q);
            if ($q === '') continue;
            if (app_api_ai_is_generic_heading($q)) continue;
            if ($editionTitle && app_api_ai_is_probably_magazine_title($q, $editionTitle)) continue;
            $out[] = $q;
        }
        if (count($out) >= $limit) break;
    }
    return app_api_ai_dedupe_queries($out, $limit);
}

function app_api_ai_extract_queries_from_image_ocr(?string $page_text, ?string $editionTitle, string $image_b64, string $image_mime, bool $debug = false): array {
    $model = app_api_ai_openai_model();
    $contextText = trim((string) $page_text);
    if ($contextText !== '') $contextText = mb_substr($contextText, 0, 2000);
    $hintTitle = trim((string) $editionTitle);
    if ($hintTitle !== '') $hintTitle = mb_substr($hintTitle, 0, 180);

    $system = "Você é um OCR especializado em anúncios de livros/produtos da CPAD.\n" .
        "Identifique APENAS produtos vendáveis (livros, guias, bíblias, etc.) mostrados na página (capas/anúncios).\n" .
        "Se houver CAPA de livro/produto na imagem, extraia o TÍTULO principal da CAPA (não a chamada do anúncio).\n" .
        "IGNORE: nome da revista, nº da edição, paginação, títulos de seções (ex.: DEVOCIONAL), chamadas genéricas.\n" .
        "Retorne SOMENTE JSON: {\"products\":[{\"title\":\"...\",\"subtitle\":\"...\",\"author\":\"...\",\"isbn\":\"...\",\"confidence\":0.0}]}.\n" .
        "Regras:\n" .
        "- title deve ser o TÍTULO DO LIVRO/PRODUTO como aparece na capa.\n" .
        "- subtitle/author/isbn apenas se estiverem visíveis.\n" .
        "- Se não houver produto anunciável, retorne products:[].\n" .
        "- Máximo 8 produtos.";

    $userParts = [];
    $userParts[] = ['type' => 'text', 'text' => "Contexto: página de revista no app.\nTítulo da edição (para ignorar): " . ($hintTitle ?: '(sem título)') . "\nTexto extraído (pode ser incompleto): " . ($contextText ?: '(sem texto)') . "\n\nExtraia os produtos anunciados visíveis na imagem."];
    $userParts[] = [
        'type' => 'image_url',
        'image_url' => [
            'url' => 'data:' . $image_mime . ';base64,' . $image_b64,
            'detail' => 'high',
        ]
    ];

    $payload = [
        'model' => $model,
        'temperature' => 0.1,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userParts],
        ],
        'response_format' => ['type' => 'json_object'],
    ];

    $r = app_api_ai_openai_chat($payload);
    if (!$r['ok'] || !is_array($r['data'])) {
        return ['queries' => [], 'error' => $r['error'] ?? 'openai_error', 'status' => $r['status'] ?? null, 'raw' => ($debug ? ($r['body'] ?? null) : null)];
    }

    $content = (string) ($r['data']['choices'][0]['message']['content'] ?? '');
    $parsed = json_decode($content, true);
    if (!is_array($parsed) && preg_match('/\{.*\}/s', $content, $mm)) {
        $parsed = json_decode($mm[0], true);
    }
    if (!is_array($parsed) || !isset($parsed['products']) || !is_array($parsed['products'])) {
        return ['queries' => [], 'error' => 'vision_parse_failed', 'raw' => ($debug ? $content : null)];
    }

    $queries = app_api_ai_build_queries_from_vision_products($parsed['products'], (string) $editionTitle, 15);
    return ['queries' => $queries, 'raw' => ($debug ? $parsed : null)];
}

function app_api_ai_extract_queries_with_vision(?string $page_text, ?string $title, ?string $image_b64, ?string $image_mime): array {
    $model = app_api_ai_openai_model();

    $contextText = trim((string) $page_text);
    if ($contextText !== '') $contextText = mb_substr($contextText, 0, 4000);

    $hintTitle = trim((string) $title);
    if ($hintTitle !== '') $hintTitle = mb_substr($hintTitle, 0, 160);

    $system = "Você extrai títulos de produtos/livros cristãos para busca na loja CPAD.\n" .
        "Retorne APENAS JSON no formato: {\"queries\":[\"...\"]}\n" .
        "Regras:\n" .
        "- Extraia TODOS os títulos diferentes que aparecerem (mesmo que sejam 3+).\n" .
        "- Use o texto e a imagem, mas priorize títulos de livros/produtos.\n" .
        "- Não invente ISBN/autor se não estiver visível.\n" .
        "- Máximo 10 queries.";

    $userParts = [];
    $userParts[] = ['type' => 'text', 'text' => "Contexto: revista no app.\nTítulo da edição: " . ($hintTitle ?: '(sem título)') . "\nTexto extraído: " . ($contextText ?: '(sem texto)') . "\n\nExtraia títulos de livros/produtos para buscar no site CPAD."];

    if ($image_b64 && $image_mime) {
        $userParts[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:' . $image_mime . ';base64,' . $image_b64
            ]
        ];
    }

    $payload = [
        'model' => $model,
        'temperature' => 0.1,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userParts],
        ],
        'response_format' => ['type' => 'json_object'],
    ];

    $r = app_api_ai_openai_chat($payload);
    if (!$r['ok'] || !is_array($r['data'])) return [];

    $content = (string) ($r['data']['choices'][0]['message']['content'] ?? '');
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        // Alguns modelos devolvem texto extra mesmo com response_format.
        // Extrai o primeiro objeto JSON do conteúdo.
        if (preg_match('/\{.*\}/s', $content, $mm)) {
            $parsed = json_decode($mm[0], true);
        }
    }
    if (!is_array($parsed) || !isset($parsed['queries']) || !is_array($parsed['queries'])) return [];

    $out = [];
    foreach ($parsed['queries'] as $q) {
        $q = trim((string) $q);
        if ($q === '') continue;
        if (mb_strlen($q) < 4) continue;
        $out[] = $q;
        if (count($out) >= 10) break;
    }
    return $out;
}

/**
 * Deduplica e limita queries vindas de múltiplas fontes (heurística + IA).
 * Mantém a ordem original.
 */
function app_api_ai_dedupe_queries(array $queries, int $limit = 10): array {
    $out = [];
    $seen = [];

    foreach ($queries as $q) {
        $q = trim((string) $q);
        if ($q === '' || mb_strlen($q) < 4) continue;

        $k = app_api_ai_normalize($q);
        if ($k === '' || isset($seen[$k])) continue;
        $seen[$k] = true;
        $out[] = $q;
        if (count($out) >= $limit) break;
    }

    return $out;
}



// Snapshot de página no servidor com Imagick

function app_api_ai_server_snapshot_enabled(): bool {
    // Requer Imagick e Ghostscript para ler PDFs
    return class_exists('Imagick');
}

function app_api_ai_snapshot_cache_dir(): ?string {
    if (!function_exists('wp_upload_dir')) return null;
    $up = wp_upload_dir();
    if (!is_array($up) || empty($up['basedir'])) return null;
    $dir = rtrim((string) $up['basedir'], '/\\') . '/app-ai-cache';
    if (!is_dir($dir)) {
        if (function_exists('wp_mkdir_p')) {
            @wp_mkdir_p($dir);
        } else {
            @mkdir($dir, 0755, true);
        }
    }
    return is_dir($dir) ? $dir : null;
}

function app_api_ai_get_pdf_path_for_flipbook(int $flipbook_id): ?string {
    if ($flipbook_id < 1) return null;

    // Prioriza a função do plugin Real3D
    if (function_exists('app_api_real3d_get_flipbook_pdf_info')) {
        $info = app_api_real3d_get_flipbook_pdf_info($flipbook_id);
        if (is_array($info)) {
            $path = (string) ($info['path'] ?? '');
            if ($path !== '' && file_exists($path)) return $path;

            // Fallback para mapear URL em caminho local
            $url = (string) ($info['url'] ?? '');
            if ($url) {
                $mapped = '';
                if (function_exists('app_api_map_upload_url_to_path')) {
                    $mapped = (string) app_api_map_upload_url_to_path($url);
                } elseif (function_exists('app_api_ai_map_upload_url_to_path_fallback')) {
                    $mapped = (string) app_api_ai_map_upload_url_to_path_fallback($url);
                }

                if ($mapped !== '' && file_exists($mapped)) return $mapped;
            }
        }
    }

    return null;
}

function app_api_ai_render_pdf_page_to_jpeg_base64(string $pdf_path, int $page_one_based): ?string {
    if (!app_api_ai_server_snapshot_enabled()) return null;
    if ($pdf_path === '' || !file_exists($pdf_path)) return null;
    if ($page_one_based < 1) return null;

    $pageIndex = $page_one_based - 1;

    try {
        $im = new Imagick();

        // Resolução maior melhora a leitura, mas aumenta o custo.
        // O intervalo de 144 a 180 costuma equilibrar custo e legibilidade.
        $im->setResolution(160, 160);

        // Melhora o recorte em alguns PDFs
        if (method_exists($im, 'setOption')) {
            @ $im->setOption('pdf:use-cropbox', 'true');
        }

        // Lê apenas a página solicitada
        $im->readImage($pdf_path . '[' . $pageIndex . ']');

        // Converte PDFs em CMYK quando necessário
        if (method_exists($im, 'setImageColorspace')) {
            @ $im->setImageColorspace(Imagick::COLORSPACE_RGB);
        }

        // Reduz o tamanho do payload
        $w = (int) $im->getImageWidth();
        $h = (int) $im->getImageHeight();
        $maxW = 1400;
        $maxH = 1800;
        if (($w > $maxW || $h > $maxH) && method_exists($im, 'thumbnailImage')) {
            $im->thumbnailImage($maxW, $maxH, true, true);
        }

        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(72);
        if (method_exists($im, 'stripImage')) $im->stripImage();

        $blob = $im->getImageBlob();
        $im->clear();
        $im->destroy();

        if (!$blob) return null;

        // Evita respostas excessivamente grandes
        if (strlen($blob) > 2_000_000) {
            // Tenta recomprimir com tamanho menor
            $im2 = new Imagick();
            $im2->readImageBlob($blob);
            $im2->setImageFormat('jpeg');
            $im2->setImageCompressionQuality(60);
            if (method_exists($im2, 'stripImage')) $im2->stripImage();
            $blob2 = $im2->getImageBlob();
            $im2->clear();
            $im2->destroy();
            if ($blob2 && strlen($blob2) < strlen($blob)) $blob = $blob2;
        }

        return base64_encode($blob);
    } catch (Throwable $e) {
        return null;
    }
}

function app_api_ai_try_server_snapshot(int $flipbook_id, int $page_one_based, string $pdf_url = ''): ?array {
    if ($flipbook_id < 1 || $page_one_based < 1) return null;
    if (!app_api_ai_server_snapshot_enabled()) return null;

    $cacheDir = app_api_ai_snapshot_cache_dir();
    $cacheTtl = 600; // 10 min
    $cacheFile = null;
    if ($cacheDir) {
        $cacheFile = rtrim($cacheDir, '/\\') . '/snap_' . $flipbook_id . '_' . $page_one_based . '.jpg';
        if (is_string($cacheFile) && file_exists($cacheFile)) {
            $age = time() - (int) @filemtime($cacheFile);
            if ($age >= 0 && $age <= $cacheTtl) {
                $blob = @file_get_contents($cacheFile);
                if ($blob) {
                    return ['b64' => base64_encode($blob), 'mime' => 'image/jpeg', 'source' => 'server-cache'];
                }
            }
        }
    }

    $pdfPath = app_api_ai_get_pdf_path($flipbook_id, $pdf_url);
    if (!$pdfPath) return null;

    $b64 = app_api_ai_render_pdf_page_to_jpeg_base64($pdfPath, $page_one_based);
    if (!$b64) return null;

    // Persiste o cache do snapshot
    if ($cacheFile) {
        $blob = base64_decode($b64);
        if ($blob) @file_put_contents($cacheFile, $blob);
    }

    return ['b64' => $b64, 'mime' => 'image/jpeg', 'source' => 'server'];
}

// Utilitário para mapear URL de uploads em caminho local

if (!function_exists('app_api_ai_map_upload_url_to_path_fallback')) {
    function app_api_ai_map_upload_url_to_path_fallback(string $url): ?string {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (!function_exists('wp_upload_dir')) return null;

        $up = wp_upload_dir();
        if (!is_array($up)) return null;

        $baseurl = rtrim((string) ($up['baseurl'] ?? ''), '/');
        $basedir = rtrim((string) ($up['basedir'] ?? ''), '/');
        if ($basedir === '') return null;

        // Caso padrão: URL iniciando em baseurl de uploads
        if ($baseurl !== '' && strpos($url, $baseurl) === 0) {
            $rel = ltrim(substr($url, strlen($baseurl)), '/');
            $path = $basedir . '/' . $rel;
            return $path;
        }

        // Fallback que ignora o host e usa apenas o path
        $parts = wp_parse_url($url);
        $pathPart = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
        if ($pathPart !== '') {
            // Exemplo com caminho completo em uploads
            $needle = '/wp-content/uploads/';
            $pos = strpos($pathPart, $needle);
            if ($pos !== false) {
                $rel = ltrim(substr($pathPart, $pos + strlen($needle)), '/');
                if ($rel !== '') return $basedir . '/' . $rel;
            }

            // Exemplo simplificado usado em algumas instalações
            $needle2 = '/uploads/';
            $pos2 = strpos($pathPart, $needle2);
            if ($pos2 !== false) {
                $rel = ltrim(substr($pathPart, $pos2 + strlen($needle2)), '/');
                if ($rel !== '') return $basedir . '/' . $rel;
            }
        }

        return null;
    }
}


// Obtém um PDF local a partir da URL

function app_api_ai_get_pdf_path_from_url(string $pdf_url): ?string {
    $pdf_url = trim((string) $pdf_url);
    if ($pdf_url === '') return null;

    // Tenta mapear para uploads local
    $mapped = null;
    if (function_exists('app_api_map_upload_url_to_path')) {
        $mapped = (string) app_api_map_upload_url_to_path($pdf_url);
    }
    if (!$mapped && function_exists('app_api_ai_map_upload_url_to_path_fallback')) {
        $mapped = (string) app_api_ai_map_upload_url_to_path_fallback($pdf_url);
    }
    if ($mapped && file_exists($mapped)) return $mapped;

    // Se não mapear, baixa o arquivo para cache local
    $cacheDir = app_api_ai_snapshot_cache_dir();
    if (!$cacheDir) return null;

    $hash = md5($pdf_url);
    $cacheFile = rtrim($cacheDir, '/\\') . '/pdf_' . $hash . '.pdf';

    // Reutiliza o PDF baixado por uma hora
    if (file_exists($cacheFile)) {
        $age = time() - (int) @filemtime($cacheFile);
        if ($age >= 0 && $age <= 3600 && filesize($cacheFile) > 0) {
            return $cacheFile;
        }
    }

    // Faz o download do arquivo
    $tmp = wp_tempnam($pdf_url);
    if (!$tmp) return null;

    $res = wp_remote_get($pdf_url, [
        'timeout' => 45,
        'redirection' => 3,
        'stream' => true,
        'filename' => $tmp,
    ]);

    if (is_wp_error($res)) {
        @unlink($tmp);
        return null;
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) {
        @unlink($tmp);
        return null;
    }

    if (!file_exists($tmp) || filesize($tmp) <= 0) {
        @unlink($tmp);
        return null;
    }

    // Move o arquivo para a pasta de cache
    @rename($tmp, $cacheFile);
    if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
        return $cacheFile;
    }

    // Fallback para usar o arquivo temporário
    return file_exists($tmp) ? $tmp : null;
}

function app_api_ai_get_pdf_path(int $flipbook_id, string $pdf_url = ''): ?string {
    // Resolve o flipbook para caminho local
    $p = app_api_ai_get_pdf_path_for_flipbook($flipbook_id);
    if ($p && file_exists($p)) return $p;

    // Usa a URL do PDF enviada pelo app
    if ($pdf_url !== '') {
        $p2 = app_api_ai_get_pdf_path_from_url($pdf_url);
        if ($p2 && file_exists($p2)) return $p2;
    }

    return null;
}

if (!function_exists('app_api_ai_validate_magazine_access')) {
    function app_api_ai_validate_magazine_access(int $user_id, int $product_id, int $flipbook_id = 0)
    {
        if ($user_id <= 0 || $product_id <= 0) {
            return new WP_Error('bad_request', 'product_id inválido', ['status' => 400]);
        }

        if (!function_exists('app_api_user_can_access_product') || !app_api_user_can_access_product($user_id, $product_id, true)) {
            $membership_error = function_exists('app_api_build_membership_access_error')
                ? app_api_build_membership_access_error($user_id, $product_id)
                : null;
            if (is_wp_error($membership_error)) {
                return $membership_error;
            }

            return new WP_Error('forbidden', 'Sem acesso a esta revista.', ['status' => 403]);
        }

        if ($flipbook_id > 0 && function_exists('app_api_resolve_product_flipbook_id')) {
            $resolved = app_api_resolve_product_flipbook_id($product_id, $flipbook_id);
            if (is_wp_error($resolved)) {
                return $resolved;
            }
        }

        return true;
    }
}

if (!function_exists('app_api_ai_validate_pdf_url')) {
    function app_api_ai_validate_pdf_url(string $pdf_url): bool
    {
        $pdf_url = trim((string) $pdf_url);
        if ($pdf_url === '') {
            return true;
        }

        $pdf_parts = wp_parse_url($pdf_url);
        $home_parts = wp_parse_url(home_url('/'));
        if (!$pdf_parts || !$home_parts) {
            return false;
        }

        $pdf_host = strtolower((string) ($pdf_parts['host'] ?? ''));
        $home_host = strtolower((string) ($home_parts['host'] ?? ''));
        if ($pdf_host === '' || $home_host === '' || $pdf_host !== $home_host) {
            return false;
        }

        return true;
    }
}

/**
 * Endpoint: POST /ai/page-recos
 * body:
 * - product_id (int)  -> produto da revista (assinatura)
 * - flipbook_id (int) -> (opcional)
 * - page (int)
 * - mode: "auto" | "manual"
 * - title (string) -> título da edição (fallback)
 * - page_text (string)
 * - page_image_base64 (string) -> JPEG base64 (somente quando necessário)
 * - page_image_mime (string) -> "image/jpeg"
 * - pdf_url (string) -> (opcional) url do PDF para fallback de snapshot no servidor
 * - force (bool) -> ignora cache e força regenerar
 * - max_products (int) -> override do limite (1..12)
 */
function app_api_ai_page_recos(WP_REST_Request $req) {
    if (!function_exists('app_api_request_has_bearer_token') || !app_api_request_has_bearer_token($req)) {
        return new WP_Error('unauthorized', 'Authorization Bearer é obrigatório para sugestões da revista.', ['status' => 401]);
    }

    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    if (!app_api_ai_enabled()) {
        return ['enabled' => false, 'items' => []];
    }

    $body = $req->get_json_params();
    if (!is_array($body)) {
        $body = [];
    }

    $magazine_product_id = isset($body['product_id']) ? absint($body['product_id']) : 0;
    $flipbook_id = isset($body['flipbook_id']) ? absint($body['flipbook_id']) : 0;
    $page = isset($body['page']) ? absint($body['page']) : 0;
    $mode = isset($body['mode']) ? sanitize_key($body['mode']) : 'auto';
    if (!in_array($mode, ['auto', 'manual'], true)) $mode = 'auto';

    $title = isset($body['title']) ? (string) $body['title'] : '';
    $title = trim(wp_strip_all_tags($title));
    if (function_exists('mb_substr') && function_exists('mb_strlen') && mb_strlen($title) > 180) {
        $title = mb_substr($title, 0, 180);
    } else {
        $title = substr($title, 0, 180);
    }

    $page_text = isset($body['page_text']) ? (string) $body['page_text'] : '';
    $page_text = trim(wp_strip_all_tags($page_text));
    $page_text_len = function_exists('mb_strlen') ? mb_strlen($page_text) : strlen($page_text);
    if ($page_text_len > 20000) {
        return new WP_Error('bad_request', 'page_text muito grande.', ['status' => 400]);
    }

    $img_b64 = isset($body['page_image_base64']) ? (string) $body['page_image_base64'] : '';
    $img_b64 = preg_replace('/^data:[^,]+,/', '', trim($img_b64));
    $img_b64 = preg_replace('/\s+/', '', $img_b64);
    $img_mime = isset($body['page_image_mime']) ? sanitize_text_field((string) $body['page_image_mime']) : '';
    if ($img_b64 !== '') {
        $allowed_image_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($img_mime, $allowed_image_mimes, true)) {
            return new WP_Error('bad_request', 'page_image_mime inválido.', ['status' => 400]);
        }

        $estimated_bytes = (int) floor((strlen($img_b64) * 3) / 4);
        if ($estimated_bytes > 6 * 1024 * 1024) {
            return new WP_Error('bad_request', 'page_image_base64 muito grande.', ['status' => 400]);
        }

        if (base64_decode($img_b64, true) === false) {
            return new WP_Error('bad_request', 'page_image_base64 inválido.', ['status' => 400]);
        }
    }

    $pdf_url = isset($body['pdf_url']) ? (string) $body['pdf_url'] : '';
    $pdf_url = trim((string) $pdf_url);
    if (strlen($pdf_url) > 2048) {
        return new WP_Error('bad_request', 'pdf_url inválida para análise.', ['status' => 400]);
    }

    $force = !empty($body['force']);

    $imgFlagInput = (!empty($img_b64) && !empty($img_mime)) ? 'img1' : 'img0';


    if ($page < 1 || $magazine_product_id < 1) {
        return new WP_Error('bad_request', 'product_id e page são obrigatórios', ['status' => 400]);
    }

    $access_ok = function_exists('app_api_ai_validate_magazine_access')
        ? app_api_ai_validate_magazine_access((int) $user->ID, (int) $magazine_product_id, (int) $flipbook_id)
        : true;
    if (is_wp_error($access_ok)) {
        return $access_ok;
    }

    if ($pdf_url !== '' && function_exists('app_api_ai_validate_pdf_url') && !app_api_ai_validate_pdf_url($pdf_url)) {
        return new WP_Error('bad_request', 'pdf_url inválida para análise.', ['status' => 400]);
    }

    $maxItems = app_api_ai_max_products();
    if (isset($body['max_products'])) {
        $overrideMax = absint($body['max_products']);
        if ($overrideMax < 1 || $overrideMax > 12) {
            return new WP_Error('bad_request', 'max_products deve estar entre 1 e 12.', ['status' => 400]);
        }
        $maxItems = $overrideMax;
    }
    $ttl = app_api_ai_cache_ttl_seconds();

    // Usa cache por página no servidor e permite bypass com force=true
    $cacheKey = null;
    if ($ttl > 0 && !$force) {
        $hash = md5($magazine_product_id . '|' . $flipbook_id . '|' . $page . '|' . $maxItems . '|' . $mode . '|' . $imgFlagInput);
        $cacheKey = 'app_ai_recos_v3_' . $hash;
        $cached = get_transient($cacheKey);
        if (is_array($cached)) return $cached;
    }

    $textLen = mb_strlen($page_text);

    // Se necessário, resolve o primeiro flipbook do produto
    if ($flipbook_id < 1 && function_exists('app_api_get_product_flipbook_ids')) {
        $ids = app_api_get_product_flipbook_ids($magazine_product_id);
        if (is_array($ids) && !empty($ids)) {
            $flipbook_id = absint($ids[0]);
        }
    }

    // Recalcula a chave de cache quando o flipbook_id é resolvido depois
        if ($ttl > 0 && !$force) {
        $hash2 = md5($magazine_product_id . '|' . $flipbook_id . '|' . $page . '|' . $maxItems . '|' . $mode . '|' . $imgFlagInput);
        $cacheKey2 = 'app_ai_recos_v3_' . $hash2;
        if ($cacheKey2 !== $cacheKey) {
            $cacheKey = $cacheKey2;
            $cached2 = get_transient($cacheKey);
            if (is_array($cached2)) return $cached2;
        }
    }

    // Faz snapshot no servidor quando a página não traz texto útil
    // No modo AUTO, processa páginas-imagem no servidor com cache.
    // No modo MANUAL, também tenta enriquecer o resultado.
    $snapshotSource = null;
    $snapshotThreshold = ($mode === 'auto') ? 30 : 60;
    if ($textLen < $snapshotThreshold && (!$img_b64 || !$img_mime)) {
        $snap = app_api_ai_try_server_snapshot($flipbook_id, $page, $pdf_url);
        if (is_array($snap) && !empty($snap['b64']) && !empty($snap['mime'])) {
            $img_b64 = (string) $snap['b64'];
            $img_mime = (string) $snap['mime'];
            $snapshotSource = (string) ($snap['source'] ?? 'server');
        }
    }

    $hasImage = $img_b64 && $img_mime && strpos($img_mime, 'image/') === 0;

    // Extrai queries de busca
    $queries = [];
    $usedAi = false;
    $usedVision = false;
    $visionError = null;

    // Com imagem e chave válidas, usa Vision para ler anúncios e capas.
    if ($hasImage && app_api_ai_openai_api_key()) {
        $vision = app_api_ai_extract_products_with_vision($page_text, $title, $img_b64, $img_mime, 8);
        if (!empty($vision['ok'])) {
            $usedVision = true;
            $products = is_array($vision['products'] ?? null) ? $vision['products'] : [];
            if (!empty($products)) {
                $queries = app_api_ai_products_to_queries($products, $title, 15);
            } else {
                // Fallback para o método antigo de extração de queries
                $queries = app_api_ai_extract_queries_with_vision($page_text, $title, $img_b64, $img_mime);
                $queries = app_api_ai_dedupe_queries($queries, 15);
            }
        } else {
            $visionError = (string) ($vision['error'] ?? 'openai_error');
            $visionError = app_api_ai_sanitize_openai_error_message($visionError);
        }

        // filtra queries genéricas e o próprio título da revista
        if (!empty($queries)) {
            $filtered = [];
            foreach ($queries as $q) {
                $q = app_api_ai_clean_query((string) $q);
                if ($q === '') continue;
                if ($title && app_api_ai_is_similar_to_title($q, $title)) continue;
                if (app_api_ai_is_generic_query($q)) continue;
                $filtered[] = $q;
            }
            $queries = app_api_ai_dedupe_queries($filtered, 15);
        }

        $usedAi = !empty($queries);
    }

    // Se não houver imagem útil, usa heurística textual.
    if (!$queries) {
        $queries = app_api_ai_extract_queries_from_text($page_text, 10);
    }

    // Combina heurística e IA textual quando o conteúdo é extenso.
    if (app_api_ai_openai_api_key() && $textLen >= 120 && count($queries) < 3) {
        $extra = app_api_ai_extract_queries_with_vision($page_text, $title, null, null);
        if (!empty($extra)) $usedAi = true;
        $queries = app_api_ai_dedupe_queries(array_merge($queries, $extra), 10);
    }

    if (!$queries && $title) {
        $queries = [trim((string) $title)];
    }

    // Trata páginas sem texto e sem imagem disponível
    if ($textLen < 15 && (!$hasImage || !app_api_ai_openai_api_key())) {
        $resp = [
            'enabled' => true,
            'mode' => $mode,
            'product_id' => $magazine_product_id,
            'flipbook_id' => $flipbook_id,
            'page' => $page,
            'items' => [],
            'force' => $force,
            'max_products' => $maxItems,
            'message' => ($mode === 'auto')
                ? 'Esta página parece ser uma imagem (ou tem pouco texto) e não há snapshot disponível. Toque em "Gerar sugestões" para enviar a imagem da página pelo app (Dev Client) e analisar com Vision.'
                : 'Não foi possível analisar esta página (sem texto e sem imagem). Envie o snapshot pelo app e configure a OpenAI API key para Vision.',
        ];

        // Cacheia respostas vazias para evitar chamadas repetidas
        if ($ttl > 0) {
            $toCache = $resp;
            $toCache['force'] = false;
            set_transient($cacheKey ?: ('app_ai_recos_v3_' . md5($magazine_product_id . '|' . $flipbook_id . '|' . $page . '|' . $maxItems . '|' . $mode . '|' . $imgFlagInput)), $toCache, $ttl);
        }

        return $resp;
    }
    // Busca produtos na CPAD e ranqueia o resultado
    $candidates = []; // productId => item + score

    foreach ($queries as $q) {
        $q = app_api_ai_clean_query((string) $q);
        if ($q === '') continue;

        // Evita buscas por termos genéricos ou pelo título da revista
        if ($title && app_api_ai_is_similar_to_title($q, $title)) continue;
        if (app_api_ai_is_generic_query($q)) continue;

        $variants = app_api_ai_build_search_variants($q);
        if (!$variants) $variants = [$q];

        foreach ($variants as $qv) {
            $sr = app_api_ai_vtex_search($qv, 0, 36);
            if (!$sr['ok'] || !is_array($sr['data'])) continue;

            foreach ($sr['data'] as $p) {
                if (!is_array($p)) continue;
                $item = app_api_ai_vtex_to_item($p);
                if (!$item) continue;

                $pid = (string) ($item['cpad_product_id'] ?? '');
                if ($pid === '') {
                    // Fallback para usar a URL como chave
                    $pid = md5((string) ($item['url'] ?? '') . '|' . (string) ($item['title'] ?? ''));
                }

                $score = app_api_ai_score_match($q, (string) $item['title']);
                if (!isset($candidates[$pid]) || $score > $candidates[$pid]['_score']) {
                    $candidates[$pid] = $item;
                    $candidates[$pid]['_score'] = $score;
                    $candidates[$pid]['_query'] = $q;
                }
            }
        }
    }

    if (!$candidates) {
        $msg = ($usedVision ? 'Analisado por imagem, mas não encontrei produtos correspondentes nesta página.' : 'Não encontrei produtos correspondentes nesta página.');
        if ($visionError) {
            // Retorna mensagem curta quando a quota é excedida
            if (app_api_ai_is_quota_error($visionError, null, null)) {
                $msg .= ' (Vision indisponível: sem saldo/créditos na OpenAI.)';
            } else {
                $msg .= ' (Vision falhou: ' . $visionError . ')';
            }
        }

        $resp = [
            'enabled' => true,
            'mode' => $mode,
            'product_id' => $magazine_product_id,
            'flipbook_id' => $flipbook_id,
            'page' => $page,
            'items' => [],
            'force' => $force,
            'max_products' => $maxItems,
            'message' => $msg,
        ];

        if ($ttl > 0) {
            $toCache = $resp;
            $toCache['force'] = false;
            set_transient($cacheKey ?: ('app_ai_recos_v3_' . md5($magazine_product_id . '|' . $flipbook_id . '|' . $page . '|' . $maxItems . '|' . $mode . '|' . $imgFlagInput)), $toCache, $ttl);
        }

        return $resp;
    }

    // Ordena pelo score em ordem decrescente
    uasort($candidates, function($a, $b) {
        return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
    });

    $utm = app_api_ai_utm_params($magazine_product_id, $page);

    $items = [];
    $seenUrl = [];
    foreach ($candidates as $cand) {
        $q = (string) ($cand['_query'] ?? ($cand['title'] ?? ''));
        $url = (string) ($cand['url'] ?? '');
        if ($url === '') {
            $url = app_api_ai_cpad_base_url() . '/' . rawurlencode($q) . '?map=ft';
        }
        $url = app_api_ai_append_utm($url, $utm);

        $kUrl = (string) $url;
        if ($kUrl !== '' && isset($seenUrl[$kUrl])) {
            continue;
        }
        if ($kUrl !== '') $seenUrl[$kUrl] = true;

        $items[] = [
            'product_id' => (
            !empty($cand['cpad_product_id']) && is_numeric($cand['cpad_product_id'])
                ? (int) $cand['cpad_product_id']
                : (int) sprintf('%u', crc32((string) ($url ?: '') . '|' . (string) ($cand['title'] ?? $q)))
        ),
            'title' => (string) ($cand['title'] ?? $q),
            'query' => $q,
            'url' => $url,
            'image_url' => $cand['image_url'] ?? null,
            'price' => $cand['price'] ?? null,
            'reason' => 'Encontrado na CPAD para: ' . $q,
        ];

        if (count($items) >= $maxItems) break;
    }

    $resp = [
        'enabled' => true,
        'mode' => $mode,
        'product_id' => $magazine_product_id,
        'flipbook_id' => $flipbook_id,
        'page' => $page,
        'items' => $items,
        'force' => $force,
        'max_products' => $maxItems,
    ];

    if ($snapshotSource) {
        $resp['snapshot_source'] = $snapshotSource;
    }

    if ($usedVision) {
        $resp['message'] = 'Analisado por imagem' . ($snapshotSource ? ' (' . $snapshotSource . ')' : '') . '.';
    } elseif ($usedAi) {
        $resp['message'] = 'Analisado com IA e texto.';
    }

    // Atualiza o cache mesmo quando a chamada usa force
    if ($ttl > 0) {
        $toCache = $resp;
        $toCache['force'] = false;
        set_transient($cacheKey ?: ('app_ai_recos_v3_' . md5($magazine_product_id . '|' . $flipbook_id . '|' . $page . '|' . $maxItems . '|' . $mode . '|' . $imgFlagInput)), $toCache, $ttl);
    }

    return $resp;
}

/**
 * Endpoint: POST /ai/track
 * body:
 * - event_type: impression|click
 * - magazine_product_id (int)
 * - page (int)
 * - recommended_product_id (int)   (para click)
 * - recommended_product_ids (array<int>) (para impression)
 */
function app_api_ai_track(WP_REST_Request $req) {
    if (!function_exists('app_api_request_has_bearer_token') || !app_api_request_has_bearer_token($req)) {
        return new WP_Error('unauthorized', 'Authorization Bearer é obrigatório para tracking da revista.', ['status' => 401]);
    }

    $user = app_api_require_access_user($req);
    if (is_wp_error($user)) return $user;

    $body = $req->get_json_params();
    if (!is_array($body)) {
        $body = [];
    }

    $event_type = sanitize_key($body['event_type'] ?? '');
    $magazine_product_id = absint($body['magazine_product_id'] ?? ($body['product_id'] ?? 0));
    $page = absint($body['page'] ?? 0);

    $single = absint($body['recommended_product_id'] ?? 0);
    $many = $body['recommended_product_ids'] ?? null;

    if (!in_array($event_type, ['impression', 'click'], true)) {
        return new WP_Error('bad_request', 'event_type inválido', ['status' => 400]);
    }

    if ($magazine_product_id < 1 || $page < 1 || $page > 10000) {
        return new WP_Error('bad_request', 'magazine_product_id e page são obrigatórios', ['status' => 400]);
    }

    $access_ok = function_exists('app_api_ai_validate_magazine_access')
        ? app_api_ai_validate_magazine_access((int) $user->ID, (int) $magazine_product_id, 0)
        : true;
    if (is_wp_error($access_ok)) {
        return $access_ok;
    }

    $ids = [];
    if ($event_type === 'click') {
        if ($single < 1) return new WP_Error('bad_request', 'recommended_product_id é obrigatório para click', ['status' => 400]);
        $ids = [$single];
    } else {
        if (is_array($many)) {
            if (count($many) > 200) {
                return new WP_Error('bad_request', 'recommended_product_ids muito grande.', ['status' => 400]);
            }
            foreach ($many as $x) {
                $n = absint($x);
                if ($n > 0) $ids[] = $n;
            }
        }
        if (!$ids && $single > 0) $ids = [$single];
        if (!$ids) return ['ok' => true];
        $ids = array_slice(array_values(array_unique($ids)), 0, 30);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'app_ai_events';

    foreach ($ids as $rid) {
        $wpdb->insert($table, [
            'user_id' => (int) $user->ID,
            'event_type' => $event_type,
            'magazine_product_id' => $magazine_product_id,
            'page' => $page,
            'recommended_product_id' => $rid,
            'created_at' => app_api_now_mysql(),
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    return ['ok' => true];
}
