<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Sanitizers e tela de configuração do plugin no painel do WordPress.
 */

/** Registra a página de configurações no menu administrativo. */
function app_api_register_admin_menu()
{
    add_options_page(
        'App API',
        'App API',
        'manage_options',
        'app-api-settings',
        'app_api_render_settings_page'
    );
}

/** Registra as opções persistidas pelo plugin. */
function app_api_register_settings()
{
    register_setting('app_api_settings', 'app_api_flipbook_meta_key');
    register_setting('app_api_settings', 'app_api_flipbook_shortcode_tpl');

    // Configurações opcionais da camada de IA.
    register_setting('app_api_settings', 'app_api_ai_enabled');
    register_setting('app_api_settings', 'app_api_openai_api_key');
    register_setting('app_api_settings', 'app_api_openai_model');
    register_setting('app_api_settings', 'app_api_ai_max_products');
    register_setting('app_api_settings', 'app_api_ai_cache_ttl_seconds');
    register_setting('app_api_settings', 'app_api_ai_cpad_base_url');
}

/** Renderiza a tela de configuração exibida no admin do WordPress. */
function app_api_render_settings_page()
{
    if (!current_user_can('manage_options'))
        return;

    ?>
    <div class="wrap">
        <h1>App API - Configurações</h1>
        <form method="post" action="options.php">
            <?php settings_fields('app_api_settings'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="app_api_flipbook_meta_key">Meta key do Flipbook no Produto</label></th>
                    <td>
                        <input type="text" id="app_api_flipbook_meta_key" name="app_api_flipbook_meta_key"
                            value="<?php echo esc_attr(get_option('app_api_flipbook_meta_key', '_real3d_flipbook_id')); ?>"
                            class="regular-text" />
                        <p class="description">Ex.: <code>_real3d_flipbook_id</code> (ajuste conforme o seu plugin de
                            vínculo do Real3D com o WooCommerce).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_api_flipbook_shortcode_tpl">Shortcode do Real3D</label></th>
                    <td>
                        <input type="text" id="app_api_flipbook_shortcode_tpl" name="app_api_flipbook_shortcode_tpl"
                            value="<?php echo esc_attr(get_option('app_api_flipbook_shortcode_tpl', '[real3dflipbook id="{ID}"]')); ?>"
                        class="regular-text" />
                        <p class="description">Use <code>{ID}</code> para inserir o flipbook_id.</p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2">
                        <h2 style="margin: 16px 0 0;">IA (Recomendações no PDF)</h2>
                        <p class="description">Sugere produtos a partir do texto da página (enviado pelo app). A IA sugere produtos reais do site CPAD (www.cpad.com.br). Quando a página for imagem (sem texto), o app pode enviar um snapshot (JPEG) para a IA reconhecer o produto.</p>
                    </th>
                </tr>

                <tr>
                    <th scope="row">Habilitar IA</th>
                    <td>
                        <label>
                            <input type="hidden" name="app_api_ai_enabled" value="0" />
                            <input type="checkbox" name="app_api_ai_enabled" value="1" <?php checked( (bool) get_option('app_api_ai_enabled', false) ); ?> />
                            Ativar recomendações e tracking
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_api_openai_api_key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" id="app_api_openai_api_key" name="app_api_openai_api_key"
                            value="<?php echo esc_attr(get_option('app_api_openai_api_key', '')); ?>"
                            class="regular-text" autocomplete="off" />
                        <p class="description">Guarde esta chave com cuidado. Ela é usada apenas no servidor (WordPress).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_api_openai_model">Modelo</label></th>
                    <td>
                        <input type="text" id="app_api_openai_model" name="app_api_openai_model"
                            value="<?php echo esc_attr(get_option('app_api_openai_model', 'gpt-4o-mini')); ?>"
                            class="regular-text" />
                        <p class="description">Ex.: <code>gpt-4o-mini</code>. Você pode trocar por outro modelo compatível.</p>
                    </td>
                </tr>

                
                <tr>
                    <th scope="row"><label for="app_api_ai_cpad_base_url">Base URL (CPAD)</label></th>
                    <td>
                        <input type="text" id="app_api_ai_cpad_base_url" name="app_api_ai_cpad_base_url"
                            value="<?php echo esc_attr(get_option('app_api_ai_cpad_base_url', 'https://www.cpad.com.br')); ?>"
                            class="regular-text" />
                        <p class="description">Padrão: <code>https://www.cpad.com.br</code>. Usado para montar URLs e chamadas ao catálogo (VTEX).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_api_ai_max_products">Máx. produtos por página</label></th>
                    <td>
                        <input type="number" id="app_api_ai_max_products" name="app_api_ai_max_products"
                            value="<?php echo esc_attr((int) get_option('app_api_ai_max_products', 6)); ?>"
                            class="small-text" min="1" max="12" step="1" />
                        <p class="description">Recomendado: 4 a 8. Máximo: 12.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_api_ai_cache_ttl_seconds">Cache (segundos)</label></th>
                    <td>
                        <input type="number" id="app_api_ai_cache_ttl_seconds" name="app_api_ai_cache_ttl_seconds"
                            value="<?php echo esc_attr((int) get_option('app_api_ai_cache_ttl_seconds', 3600)); ?>"
                            class="small-text" min="0" step="60" />
                        <p class="description">0 desativa cache. Use cache para reduzir custo de IA.</p>
                    </td>
                </tr>

            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
