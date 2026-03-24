<?php
/**
 * Template da pagina GTM / GA4 / Google Ads
 *
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$gtm_id = get_option('yeshua_gtm_id', '');
$ga4_id = get_option('yeshua_ga4_id', '');
$gads_id = get_option('yeshua_gads_id', '');
$gads_label = get_option('yeshua_gads_label', '');
$thank_you_urls = get_option('yeshua_gtm_thank_you_urls', '');
$exclude_admins = get_option('yeshua_gtm_exclude_admins', '1');
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-chart-bar" style="color: #4285F4;"></span>
            <span class="yeshua-title"><?php _e('Google Tag Manager / GA4 / Google Ads', 'yeshua-conversoes'); ?></span>
        </div>
    </div>

    <form method="post" action="options.php" id="yeshua-gtm-form">
        <?php settings_fields('yeshua_gtm'); ?>

        <!-- GTM -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-tag"></span>
                <?php _e('Google Tag Manager', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_gtm_id"><?php _e('GTM Container ID', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="yeshua_gtm_id"
                               id="yeshua_gtm_id"
                               value="<?php echo esc_attr($gtm_id); ?>"
                               class="regular-text"
                               placeholder="GTM-XXXXXXX">
                        <p class="description">
                            <?php _e('O snippet do GTM sera inserido automaticamente no site. Se preenchido, o GA4 e Google Ads devem ser configurados dentro do GTM.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- GA4 -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('Google Analytics 4 (GA4)', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_ga4_id"><?php _e('Measurement ID', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="yeshua_ga4_id"
                               id="yeshua_ga4_id"
                               value="<?php echo esc_attr($ga4_id); ?>"
                               class="regular-text"
                               placeholder="G-XXXXXXXXXX">
                        <p class="description">
                            <?php if (!empty($gtm_id)) : ?>
                                <strong style="color: #d63638;"><?php _e('GTM esta ativo. Configure a tag do GA4 dentro do GTM para evitar duplicidade. O ID aqui sera usado apenas para disparar eventos via gtag() quando o GTM nao estiver presente.', 'yeshua-conversoes'); ?></strong>
                            <?php else : ?>
                                <?php _e('O script do GA4 (gtag.js) sera inserido automaticamente no site.', 'yeshua-conversoes'); ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Google Ads -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Google Ads - Conversao', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_gads_id"><?php _e('Conversion ID', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="yeshua_gads_id"
                               id="yeshua_gads_id"
                               value="<?php echo esc_attr($gads_id); ?>"
                               class="regular-text"
                               placeholder="AW-XXXXXXXXX">
                        <p class="description">
                            <?php _e('ID de conversao do Google Ads (encontrado em Ferramentas > Conversoes).', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="yeshua_gads_label"><?php _e('Conversion Label', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="yeshua_gads_label"
                               id="yeshua_gads_label"
                               value="<?php echo esc_attr($gads_label); ?>"
                               class="regular-text"
                               placeholder="AbCdEfGhIjKlMn">
                        <p class="description">
                            <?php _e('Label da conversao do Google Ads.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Pagina de Obrigado -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Paginas de Obrigado (Thank You)', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_gtm_thank_you_urls"><?php _e('URLs das paginas', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_gtm_thank_you_urls"
                                  id="yeshua_gtm_thank_you_urls"
                                  class="large-text"
                                  rows="5"
                                  placeholder="/obrigado&#10;/thank-you&#10;/confirmacao"><?php echo esc_textarea($thank_you_urls); ?></textarea>
                        <p class="description">
                            <?php _e('Uma URL por linha. Pode ser caminho parcial (ex: /obrigado) ou usar * como curinga (ex: /obrigado*). Quando o visitante acessar essas paginas, os seguintes eventos serao disparados automaticamente:', 'yeshua-conversoes'); ?>
                        </p>
                        <ul style="list-style: disc; margin-left: 20px; margin-top: 8px;">
                            <li><code>generate_lead</code> — <?php _e('Evento GA4 padrao para leads', 'yeshua-conversoes'); ?></li>
                            <li><code>conversion</code> — <?php _e('Evento de conversao (dataLayer para GTM)', 'yeshua-conversoes'); ?></li>
                            <?php if (!empty($gads_id) && !empty($gads_label)) : ?>
                            <li><code>Google Ads Conversion</code> — <?php echo sprintf(__('send_to: %s/%s', 'yeshua-conversoes'), esc_html($gads_id), esc_html($gads_label)); ?></li>
                            <?php endif; ?>
                        </ul>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Formularios Externos -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-forms"></span>
                <?php _e('Formularios Externos', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_gtm_external_forms"><?php _e('Seletores CSS', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_gtm_external_forms"
                                  id="yeshua_gtm_external_forms"
                                  class="large-text"
                                  rows="5"
                                  placeholder=".elementor-form&#10;.wpcf7-form&#10;.wpforms-form&#10;#meu-formulario"><?php echo esc_textarea(get_option('yeshua_gtm_external_forms', '')); ?></textarea>
                        <p class="description">
                            <?php _e('Um seletor CSS por linha. Quando esses formularios forem submetidos, os eventos de conversao (generate_lead, Google Ads) serao disparados automaticamente. Funciona com Elementor, Contact Form 7, WPForms, Gravity Forms e qualquer formulario HTML.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Eventos automaticos -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-controls-play"></span>
                <?php _e('Eventos Automaticos nos Formularios', 'yeshua-conversoes'); ?>
            </h2>

            <p class="description" style="margin-bottom: 16px;">
                <?php _e('Quando um formulario YESHUA (WhatsApp ou Lead) ou um formulario externo configurado acima e submetido com sucesso, os seguintes eventos sao disparados automaticamente:', 'yeshua-conversoes'); ?>
            </p>

            <table class="widefat striped" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th><?php _e('Plataforma', 'yeshua-conversoes'); ?></th>
                        <th><?php _e('Evento', 'yeshua-conversoes'); ?></th>
                        <th><?php _e('Status', 'yeshua-conversoes'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>dataLayer (GTM)</strong></td>
                        <td><code>generate_lead</code></td>
                        <td>
                            <?php if (!empty($gtm_id)) : ?>
                                <span style="color: #00a32a;">&#10003; <?php _e('Ativo', 'yeshua-conversoes'); ?></span>
                            <?php else : ?>
                                <span style="color: #999;">&#8212; <?php _e('GTM nao configurado', 'yeshua-conversoes'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>GA4 (gtag)</strong></td>
                        <td><code>generate_lead</code></td>
                        <td>
                            <?php if (!empty($ga4_id)) : ?>
                                <span style="color: #00a32a;">&#10003; <?php _e('Ativo', 'yeshua-conversoes'); ?></span>
                            <?php else : ?>
                                <span style="color: #999;">&#8212; <?php _e('GA4 nao configurado', 'yeshua-conversoes'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Google Ads</strong></td>
                        <td><code>conversion</code></td>
                        <td>
                            <?php if (!empty($gads_id) && !empty($gads_label)) : ?>
                                <span style="color: #00a32a;">&#10003; <?php _e('Ativo', 'yeshua-conversoes'); ?> (<?php echo esc_html($gads_id); ?>)</span>
                            <?php else : ?>
                                <span style="color: #999;">&#8212; <?php _e('Google Ads nao configurado', 'yeshua-conversoes'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Opcoes -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Opcoes', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Excluir Administradores', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label for="yeshua_gtm_exclude_admins">
                            <input name="yeshua_gtm_exclude_admins" type="checkbox" id="yeshua_gtm_exclude_admins" value="1" <?php checked('1', $exclude_admins); ?>>
                            <?php _e('Nao carregar scripts de tracking para usuarios administradores logados', 'yeshua-conversoes'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Salvar Configuracoes', 'yeshua-conversoes')); ?>
    </form>

    <!-- Exportar Container GTM -->
    <div class="yeshua-card" style="margin-top: 20px;">
        <h2 class="yeshua-card-title">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Exportar Container GTM Pre-configurado', 'yeshua-conversoes'); ?>
        </h2>

        <p class="description" style="margin-bottom: 16px;">
            <?php _e('Gere um arquivo JSON para importar diretamente no Google Tag Manager. O container ja vem configurado com as tags, acionadores e variaveis baseados nos IDs preenchidos acima.', 'yeshua-conversoes'); ?>
        </p>

        <?php if (empty($ga4_id) && empty($gads_id)) : ?>
            <p style="color: #d63638;">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Preencha pelo menos o GA4 Measurement ID ou o Google Ads Conversion ID acima antes de exportar.', 'yeshua-conversoes'); ?>
            </p>
        <?php else : ?>
            <p style="margin-bottom: 12px;">
                <?php _e('O container incluira:', 'yeshua-conversoes'); ?>
            </p>
            <ul style="list-style: disc; margin-left: 20px; margin-bottom: 16px;">
                <?php if (!empty($ga4_id)) : ?>
                    <li><strong>GA4 Configuracao</strong> — <?php echo esc_html($ga4_id); ?> (pageview em todas as paginas)</li>
                    <li><strong>GA4 Evento generate_lead</strong> — <?php _e('Dispara quando formulario e enviado', 'yeshua-conversoes'); ?></li>
                <?php endif; ?>
                <?php if (!empty($gads_id)) : ?>
                    <li><strong>Google Ads Conversion Linker</strong> — <?php _e('Necessario para rastreamento de conversoes', 'yeshua-conversoes'); ?></li>
                    <li><strong>Google Ads Conversao (formulario)</strong> — <?php _e('Com Enhanced Conversions (email, telefone, nome)', 'yeshua-conversoes'); ?></li>
                    <li><strong>Google Ads Conversao (thank you page)</strong> — <?php _e('Pagina de obrigado', 'yeshua-conversoes'); ?></li>
                <?php endif; ?>
                <li><strong><?php _e('Variaveis', 'yeshua-conversoes'); ?></strong> — event_category, event_label, form_type, page_path<?php if (!empty($gads_id)) echo ', Enhanced Conversion Data (JS)'; ?></li>
                <li><strong><?php _e('Acionadores', 'yeshua-conversoes'); ?></strong> — generate_lead, form_submission, conversion</li>
            </ul>

            <a href="<?php echo esc_url(rest_url('yeshua/v1/export-gtm-container') . '?_wpnonce=' . wp_create_nonce('wp_rest')); ?>"
               class="button button-primary"
               id="yeshua-export-gtm"
               download>
                <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                <?php _e('Baixar Container GTM (.json)', 'yeshua-conversoes'); ?>
            </a>

            <p class="description" style="margin-top: 12px;">
                <strong><?php _e('Como importar:', 'yeshua-conversoes'); ?></strong><br>
                1. <?php _e('Abra o Google Tag Manager (tagmanager.google.com)', 'yeshua-conversoes'); ?><br>
                2. <?php _e('Va em Administrador > Importar Container', 'yeshua-conversoes'); ?><br>
                3. <?php _e('Selecione o arquivo JSON baixado', 'yeshua-conversoes'); ?><br>
                4. <?php _e('Escolha o workspace e selecione "Mesclar" > "Renomear tags em conflito"', 'yeshua-conversoes'); ?><br>
                5. <?php _e('Revise as tags importadas e clique em "Publicar"', 'yeshua-conversoes'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
