<?php
/**
 * Template da página de conversões
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$conversions = get_option('yeshua_conversions', []);
$pages = Yeshua_Admin::get_all_pages();
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-chart-line"></span>
            <span class="yeshua-title"><?php _e('Configuração de Conversões', 'yeshua-conversoes'); ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-conversions-form">
        <?php settings_fields('yeshua_conversions'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Regras de Conversão', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description">
                <?php _e('Configure quais páginas devem disparar conversões automaticamente quando acessadas.', 'yeshua-conversoes'); ?>
            </p>
            
            <div id="yeshua-conversions-list" class="yeshua-repeater">
                <?php if (!empty($conversions)) : ?>
                    <?php foreach ($conversions as $index => $conversion) : ?>
                        <div class="yeshua-repeater-item" data-index="<?php echo $index; ?>">
                            <div class="yeshua-repeater-row">
                                <div class="yeshua-field">
                                    <label><?php _e('Página', 'yeshua-conversoes'); ?></label>
                                    <select name="yeshua_conversions[<?php echo $index; ?>][page_id]" class="yeshua-conversion-page">
                                        <option value=""><?php _e('-- Selecione ou use padrão URL --', 'yeshua-conversoes'); ?></option>
                                        <?php foreach ($pages as $page) : ?>
                                            <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($conversion['page_id'] ?? '', $page->ID); ?>>
                                                <?php echo esc_html($page->post_title); ?> (<?php echo $page->post_type; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="yeshua-field">
                                    <label><?php _e('Padrão URL', 'yeshua-conversoes'); ?></label>
                                    <input type="text" 
                                           name="yeshua_conversions[<?php echo $index; ?>][url_pattern]" 
                                           value="<?php echo esc_attr($conversion['url_pattern'] ?? ''); ?>" 
                                           placeholder="/obrigado/*"
                                           class="regular-text">
                                </div>
                                
                                <div class="yeshua-field">
                                    <label><?php _e('Tipo', 'yeshua-conversoes'); ?></label>
                                    <select name="yeshua_conversions[<?php echo $index; ?>][conversion_type]">
                                        <option value="lead" <?php selected($conversion['conversion_type'] ?? '', 'lead'); ?>><?php _e('Lead', 'yeshua-conversoes'); ?></option>
                                        <option value="compra" <?php selected($conversion['conversion_type'] ?? '', 'compra'); ?>><?php _e('Compra', 'yeshua-conversoes'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="yeshua-field">
                                    <label><?php _e('ID da Conversão', 'yeshua-conversoes'); ?></label>
                                    <input type="text" 
                                           name="yeshua_conversions[<?php echo $index; ?>][conversion_id]" 
                                           value="<?php echo esc_attr($conversion['conversion_id'] ?? ''); ?>" 
                                           placeholder="conversion_id"
                                           class="regular-text">
                                </div>
                                
                                <button type="button" class="button yeshua-remove-item" title="<?php _e('Remover', 'yeshua-conversoes'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" id="yeshua-add-conversion" class="button button-secondary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Adicionar Regra', 'yeshua-conversoes'); ?>
            </button>
        </div>
        
        <div class="yeshua-card yeshua-card-info">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Como Funciona', 'yeshua-conversoes'); ?>
            </h2>
            
            <ul class="yeshua-info-list">
                <li><strong><?php _e('Página:', 'yeshua-conversoes'); ?></strong> <?php _e('Selecione uma página específica do WordPress.', 'yeshua-conversoes'); ?></li>
                <li><strong><?php _e('Padrão URL:', 'yeshua-conversoes'); ?></strong> <?php _e('Use * como curinga. Ex: /obrigado/* para todas as URLs que começam com /obrigado/', 'yeshua-conversoes'); ?></li>
                <li><strong><?php _e('Tipo:', 'yeshua-conversoes'); ?></strong> <?php _e('Lead para capturas de contato, Compra para vendas.', 'yeshua-conversoes'); ?></li>
                <li><strong><?php _e('ID da Conversão:', 'yeshua-conversoes'); ?></strong> <?php _e('Identificador único para rastrear esta conversão específica.', 'yeshua-conversoes'); ?></li>
            </ul>
        </div>
        
        <?php submit_button(__('Salvar Conversões', 'yeshua-conversoes')); ?>
    </form>
    
    <!-- Seção de Teste -->
    <div class="yeshua-card" style="margin-top: 30px; border-left: 4px solid #f59e0b;">
        <h2 class="yeshua-card-title">
            <span class="dashicons dashicons-welcome-view-site"></span>
            <?php _e('Testar Disparo de Conversão', 'yeshua-conversoes'); ?>
        </h2>
        
        <p class="description" style="margin-bottom: 20px;">
            <?php _e('Use esta seção para testar o disparo de conversões para a YESHUA API. Isso enviará uma conversão de teste real!', 'yeshua-conversoes'); ?>
        </p>
        
        <table class="form-table" style="margin-bottom: 0;">
            <tr>
                <th scope="row">
                    <label for="yeshua_test_conversion_type"><?php _e('Tipo de Conversão', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <select id="yeshua_test_conversion_type" class="regular-text">
                        <option value="lead"><?php _e('Lead', 'yeshua-conversoes'); ?></option>
                        <option value="compra"><?php _e('Compra', 'yeshua-conversoes'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="yeshua_test_url_conversao"><?php _e('URL de Conversão', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="yeshua_test_url_conversao" 
                           class="large-text" 
                           value="<?php echo esc_url(home_url('/obrigado/')); ?>"
                           placeholder="<?php echo esc_url(home_url('/obrigado/')); ?>">
                    <p class="description"><?php _e('URL onde a conversão ocorreu (página de obrigado, confirmação, etc)', 'yeshua-conversoes'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="yeshua_test_rede_origem"><?php _e('Rede de Origem', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <select id="yeshua_test_rede_origem" class="regular-text">
                        <option value="direto"><?php _e('Direto', 'yeshua-conversoes'); ?></option>
                        <option value="google">Google</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                        <option value="outro"><?php _e('Outro', 'yeshua-conversoes'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('UTMs (opcional)', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" id="yeshua_test_utm_source" placeholder="utm_source" class="small-text" style="width: 120px;">
                        <input type="text" id="yeshua_test_utm_medium" placeholder="utm_medium" class="small-text" style="width: 120px;">
                        <input type="text" id="yeshua_test_utm_campaign" placeholder="utm_campaign" class="small-text" style="width: 150px;">
                    </div>
                    <p class="description"><?php _e('Parâmetros UTM para rastrear a origem da conversão', 'yeshua-conversoes'); ?></p>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 20px;">
            <button type="button" id="yeshua-test-conversion" class="button button-primary">
                <span class="dashicons dashicons-megaphone" style="vertical-align: middle;"></span>
                <?php _e('Disparar Conversão de Teste', 'yeshua-conversoes'); ?>
            </button>
        </div>
        
        <!-- Resultado do Teste -->
        <div id="yeshua-conversion-test-result" style="margin-top: 20px; display: none;">
            <h4><?php _e('Resultado:', 'yeshua-conversoes'); ?></h4>
            <div id="yeshua-conversion-test-content"></div>
        </div>
    </div>
    
    <script>
    (function($) {
        'use strict';
        
        const restUrl = '<?php echo esc_url(rest_url('yeshua/v1/')); ?>';
        const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
        
        $('#yeshua-test-conversion').on('click', function() {
            const $btn = $(this);
            const $result = $('#yeshua-conversion-test-result');
            const $content = $('#yeshua-conversion-test-content');
            
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> <?php _e('Enviando...', 'yeshua-conversoes'); ?>');
            $result.show();
            $content.html('<p><?php _e('Processando...', 'yeshua-conversoes'); ?></p>');
            
            $.ajax({
                url: restUrl + 'test-conversion',
                method: 'POST',
                headers: { 'X-WP-Nonce': nonce },
                contentType: 'application/json',
                data: JSON.stringify({
                    conversion_type: $('#yeshua_test_conversion_type').val(),
                    url_conversao: $('#yeshua_test_url_conversao').val(),
                    rede_origem: $('#yeshua_test_rede_origem').val(),
                    utm_source: $('#yeshua_test_utm_source').val(),
                    utm_medium: $('#yeshua_test_utm_medium').val(),
                    utm_campaign: $('#yeshua_test_utm_campaign').val()
                }),
                success: function(response) {
                    const icon = response.success ? '✅' : '❌';
                    const statusClass = response.success ? 'notice-success' : 'notice-error';
                    
                    let html = '<div class="notice ' + statusClass + '" style="padding: 15px; margin: 0;">';
                    html += '<strong>' + icon + ' ' + response.message + '</strong>';
                    
                    if (response.debug && response.debug.conversion_data) {
                        html += '<br><br><strong><?php _e('Dados enviados:', 'yeshua-conversoes'); ?></strong>';
                        html += '<pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; overflow-x: auto;">' + JSON.stringify(response.debug.conversion_data, null, 2) + '</pre>';
                    }
                    
                    html += '</div>';
                    $content.html(html);
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    const message = response.message || '<?php _e('Erro desconhecido', 'yeshua-conversoes'); ?>';
                    
                    let html = '<div class="notice notice-error" style="padding: 15px; margin: 0;">';
                    html += '<strong>❌ ' + message + '</strong>';
                    
                    if (response.debug) {
                        html += '<br><br><strong><?php _e('Debug:', 'yeshua-conversoes'); ?></strong>';
                        html += '<pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; overflow-x: auto;">' + JSON.stringify(response.debug, null, 2) + '</pre>';
                    }
                    
                    html += '</div>';
                    $content.html(html);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone" style="vertical-align: middle;"></span> <?php _e('Disparar Conversão de Teste', 'yeshua-conversoes'); ?>');
                }
            });
        });
    })(jQuery);
    </script>
    
    <!-- Template para nova linha -->
    <script type="text/template" id="yeshua-conversion-template">
        <div class="yeshua-repeater-item" data-index="{{index}}">
            <div class="yeshua-repeater-row">
                <div class="yeshua-field">
                    <label><?php _e('Página', 'yeshua-conversoes'); ?></label>
                    <select name="yeshua_conversions[{{index}}][page_id]" class="yeshua-conversion-page">
                        <option value=""><?php _e('-- Selecione ou use padrão URL --', 'yeshua-conversoes'); ?></option>
                        <?php foreach ($pages as $page) : ?>
                            <option value="<?php echo esc_attr($page->ID); ?>">
                                <?php echo esc_html($page->post_title); ?> (<?php echo $page->post_type; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="yeshua-field">
                    <label><?php _e('Padrão URL', 'yeshua-conversoes'); ?></label>
                    <input type="text" 
                           name="yeshua_conversions[{{index}}][url_pattern]" 
                           placeholder="/obrigado/*"
                           class="regular-text">
                </div>
                
                <div class="yeshua-field">
                    <label><?php _e('Tipo', 'yeshua-conversoes'); ?></label>
                    <select name="yeshua_conversions[{{index}}][conversion_type]">
                        <option value="lead"><?php _e('Lead', 'yeshua-conversoes'); ?></option>
                        <option value="compra"><?php _e('Compra', 'yeshua-conversoes'); ?></option>
                    </select>
                </div>
                
                <div class="yeshua-field">
                    <label><?php _e('ID da Conversão', 'yeshua-conversoes'); ?></label>
                    <input type="text" 
                           name="yeshua_conversions[{{index}}][conversion_id]" 
                           placeholder="conversion_id"
                           class="regular-text">
                </div>
                
                <button type="button" class="button yeshua-remove-item" title="<?php _e('Remover', 'yeshua-conversoes'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
    </script>
</div>



