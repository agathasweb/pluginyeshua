<?php
/**
 * Template da página Evolution API
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$evolution_url = get_option('yeshua_evolution_url', '');
$evolution_api_key = get_option('yeshua_evolution_api_key', '');
$evolution_instance = get_option('yeshua_evolution_instance', '');
$evolution_token = get_option('yeshua_evolution_token', '');
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-whatsapp" style="color: #25D366;"></span>
            <span class="yeshua-title"><?php _e('Configuração Evolution API', 'yeshua-conversoes'); ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-evolution-form">
        <?php settings_fields('yeshua_evolution'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php _e('Conexão com Evolution API', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_evolution_url"><?php _e('URL da API', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="url" 
                               name="yeshua_evolution_url" 
                               id="yeshua_evolution_url" 
                               value="<?php echo esc_attr($evolution_url); ?>" 
                               class="regular-text"
                               placeholder="https://evolution.exemplo.com">
                        <p class="description">
                            <?php _e('URL base da sua Evolution API (sem barra no final)', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_evolution_api_key"><?php _e('API Key Global', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <div class="yeshua-input-group">
                            <input type="text" 
                                   name="yeshua_evolution_api_key" 
                                   id="yeshua_evolution_api_key" 
                                   value="<?php echo esc_attr($evolution_api_key); ?>" 
                                   class="regular-text"
                                   placeholder="sua_api_key_evolution">
                            <button type="button" id="yeshua-fetch-instances" class="button">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Buscar Instâncias', 'yeshua-conversoes'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('API Key global da Evolution (configurada no ambiente)', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_evolution_instance"><?php _e('Nome da Instância', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <div class="yeshua-input-group">
                            <select name="yeshua_evolution_instance" id="yeshua_evolution_instance" class="regular-text">
                                <option value=""><?php _e('-- Selecione ou digite --', 'yeshua-conversoes'); ?></option>
                                <?php if (!empty($evolution_instance)) : ?>
                                    <option value="<?php echo esc_attr($evolution_instance); ?>" selected>
                                        <?php echo esc_html($evolution_instance); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <input type="text" 
                                   id="yeshua_evolution_instance_manual" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Ou digite manualmente', 'yeshua-conversoes'); ?>"
                                   style="display: none;">
                            <button type="button" id="yeshua-toggle-instance-mode" class="button" title="<?php _e('Alternar modo', 'yeshua-conversoes'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Nome da instância WhatsApp a ser utilizada', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_evolution_token"><?php _e('Token da Instância', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_evolution_token" 
                               id="yeshua_evolution_token" 
                               value="<?php echo esc_attr($evolution_token); ?>" 
                               class="regular-text"
                               placeholder="token_da_instancia (opcional)">
                        <p class="description">
                            <?php _e('Token específico da instância (opcional, dependendo da configuração)', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="yeshua_evolution_label_id"><?php _e('Etiqueta para Leads', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <div class="yeshua-input-group">
                            <select name="yeshua_evolution_label_id" id="yeshua_evolution_label_id" class="regular-text">
                                <option value=""><?php _e('-- Nenhuma (desativado) --', 'yeshua-conversoes'); ?></option>
                                <?php
                                $current_label = get_option('yeshua_evolution_label_id', '');
                                $cached_labels = get_transient('yeshua_evolution_labels');
                                if ($cached_labels && is_array($cached_labels)) {
                                    foreach ($cached_labels as $label) {
                                        $selected = selected($current_label, $label['id'], false);
                                        echo '<option value="' . esc_attr($label['id']) . '"' . $selected . '>' . esc_html($label['name']) . '</option>';
                                    }
                                } elseif (!empty($current_label)) {
                                    echo '<option value="' . esc_attr($current_label) . '" selected>' . esc_html__('Label ID: ', 'yeshua-conversoes') . esc_html($current_label) . '</option>';
                                }
                                ?>
                            </select>
                            <button type="button" id="yeshua-fetch-labels" class="button">
                                <span class="dashicons dashicons-tag"></span>
                                <?php _e('Buscar Etiquetas', 'yeshua-conversoes'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Após enviar mensagem ao lead, o chat receberá esta etiqueta no WhatsApp para sinalizar ao atendente.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Testar Conexão', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description">
                <?php _e('Clique no botão abaixo para verificar se a conexão com a Evolution API está funcionando.', 'yeshua-conversoes'); ?>
            </p>
            
            <p>
                <button type="button" id="yeshua-test-evolution" class="button button-secondary">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('Testar Conexão', 'yeshua-conversoes'); ?>
                </button>
            </p>
            
            <div id="yeshua-evolution-status" class="yeshua-status-message" style="display: none;"></div>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'yeshua-conversoes')); ?>
    </form>
</div>





