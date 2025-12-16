<?php
/**
 * Template da página de configurações gerais
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('yeshua_api_key', '');
$website_id = get_option('yeshua_website_id', '');
$tracking_enabled = get_option('yeshua_tracking_enabled', '1');
$exclude_admins = get_option('yeshua_exclude_admins', '1');

// Busca websites se API Key estiver configurada
$websites = [];
if (!empty($api_key)) {
    $api = Yeshua_Api::get_instance();
    $websites = $api->get_websites($api_key);
}
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-chart-line"></span>
            <span class="yeshua-title">YESHUA Conversões</span>
        </div>
        <div class="yeshua-version">v<?php echo YESHUA_VERSION; ?></div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-settings-form">
        <?php settings_fields('yeshua_general'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-network"></span>
                <?php _e('Configurações da API YESHUA', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_api_key"><?php _e('API Key Global', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <div class="yeshua-input-group">
                            <input type="text" 
                                   name="yeshua_api_key" 
                                   id="yeshua_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text"
                                   placeholder="ysh_sua_chave_api_global_aqui">
                            <button type="button" id="yeshua-validate-api" class="button">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Validar API', 'yeshua-conversoes'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Chave API obtida no painel YESHUA em /admin/configuracoes', 'yeshua-conversoes'); ?>
                        </p>
                        <div id="yeshua-api-status" class="yeshua-status-message" style="display: none;"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_website_id"><?php _e('Website', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <select name="yeshua_website_id" id="yeshua_website_id" class="regular-text">
                            <option value=""><?php _e('-- Selecione um website --', 'yeshua-conversoes'); ?></option>
                            <?php foreach ($websites as $website) : ?>
                                <option value="<?php echo esc_attr($website['id']); ?>" <?php selected($website_id, $website['id']); ?>>
                                    <?php echo esc_html($website['nome']); ?> (<?php echo esc_html($website['dominio']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Selecione o website para o qual os dados serão enviados', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Rastreamento de Visitas', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Habilitar Rastreamento', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label class="yeshua-toggle">
                            <input type="checkbox" 
                                   name="yeshua_tracking_enabled" 
                                   id="yeshua_tracking_enabled" 
                                   value="1" 
                                   <?php checked($tracking_enabled, '1'); ?>>
                            <span class="yeshua-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php _e('Registra automaticamente todas as visitas às páginas do site', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Excluir Administradores', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label class="yeshua-toggle">
                            <input type="checkbox" 
                                   name="yeshua_exclude_admins" 
                                   id="yeshua_exclude_admins" 
                                   value="1" 
                                   <?php checked($exclude_admins, '1'); ?>>
                            <span class="yeshua-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php _e('Não rastrear visitas de usuários com perfil de administrador', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card yeshua-card-info">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Status da Integração', 'yeshua-conversoes'); ?>
            </h2>
            
            <div class="yeshua-status-grid">
                <div class="yeshua-status-item">
                    <span class="yeshua-status-label"><?php _e('API YESHUA', 'yeshua-conversoes'); ?></span>
                    <span class="yeshua-status-value <?php echo !empty($api_key) && !empty($website_id) ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo !empty($api_key) && !empty($website_id) ? __('Configurada', 'yeshua-conversoes') : __('Pendente', 'yeshua-conversoes'); ?>
                    </span>
                </div>
                <div class="yeshua-status-item">
                    <span class="yeshua-status-label"><?php _e('Rastreamento', 'yeshua-conversoes'); ?></span>
                    <span class="yeshua-status-value <?php echo $tracking_enabled ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $tracking_enabled ? __('Ativo', 'yeshua-conversoes') : __('Inativo', 'yeshua-conversoes'); ?>
                    </span>
                </div>
                <div class="yeshua-status-item">
                    <span class="yeshua-status-label"><?php _e('Evolution API', 'yeshua-conversoes'); ?></span>
                    <?php $evolution = Yeshua_Evolution::get_instance(); ?>
                    <span class="yeshua-status-value <?php echo $evolution->is_configured() ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $evolution->is_configured() ? __('Configurada', 'yeshua-conversoes') : __('Pendente', 'yeshua-conversoes'); ?>
                    </span>
                </div>
                <div class="yeshua-status-item">
                    <span class="yeshua-status-label"><?php _e('SMTP', 'yeshua-conversoes'); ?></span>
                    <?php $smtp = Yeshua_Smtp::get_instance(); ?>
                    <span class="yeshua-status-value <?php echo $smtp->is_configured() ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $smtp->is_configured() ? __('Configurado', 'yeshua-conversoes') : __('Pendente', 'yeshua-conversoes'); ?>
                    </span>
                </div>
                <div class="yeshua-status-item">
                    <span class="yeshua-status-label"><?php _e('ReCaptcha', 'yeshua-conversoes'); ?></span>
                    <?php $recaptcha = Yeshua_Recaptcha::get_instance(); ?>
                    <span class="yeshua-status-value <?php echo $recaptcha->is_configured() ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $recaptcha->is_configured() ? __('Configurado', 'yeshua-conversoes') : __('Pendente', 'yeshua-conversoes'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'yeshua-conversoes')); ?>
    </form>
</div>





