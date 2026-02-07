<?php
/**
 * Template da página ReCaptcha
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_key = get_option('yeshua_recaptcha_site_key', '');
$secret_key = get_option('yeshua_recaptcha_secret_key', '');
$version = get_option('yeshua_recaptcha_version', 'v2_checkbox');
$threshold = get_option('yeshua_recaptcha_threshold', '0.5');
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-shield-alt"></span>
            <span class="yeshua-title"><?php _e('Configuração Google ReCaptcha', 'yeshua-conversoes'); ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-recaptcha-form">
        <?php settings_fields('yeshua_recaptcha'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-lock"></span>
                <?php _e('Chaves do ReCaptcha', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description">
                <?php printf(
                    __('Obtenha suas chaves em %s', 'yeshua-conversoes'),
                    '<a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin</a>'
                ); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_recaptcha_version"><?php _e('Versão', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <select name="yeshua_recaptcha_version" id="yeshua_recaptcha_version" class="regular-text">
                            <option value="v2_checkbox" <?php selected($version, 'v2_checkbox'); ?>>
                                <?php _e('reCAPTCHA v2 - Checkbox ("Não sou um robô")', 'yeshua-conversoes'); ?>
                            </option>
                            <option value="v2_invisible" <?php selected($version, 'v2_invisible'); ?>>
                                <?php _e('reCAPTCHA v2 - Invisível', 'yeshua-conversoes'); ?>
                            </option>
                            <option value="v3" <?php selected($version, 'v3'); ?>>
                                <?php _e('reCAPTCHA v3 - Score-based', 'yeshua-conversoes'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Escolha a versão do ReCaptcha. Cada versão requer chaves específicas.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_recaptcha_site_key"><?php _e('Site Key (Pública)', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_recaptcha_site_key" 
                               id="yeshua_recaptcha_site_key" 
                               value="<?php echo esc_attr($site_key); ?>" 
                               class="regular-text"
                               placeholder="6Lc...">
                        <p class="description">
                            <?php _e('Chave pública do site (visível no frontend)', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_recaptcha_secret_key"><?php _e('Secret Key (Privada)', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               name="yeshua_recaptcha_secret_key" 
                               id="yeshua_recaptcha_secret_key" 
                               value="<?php echo esc_attr($secret_key); ?>" 
                               class="regular-text"
                               placeholder="6Lc...">
                        <p class="description">
                            <?php _e('Chave privada do site (usada no servidor)', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr id="yeshua-threshold-row" style="<?php echo $version !== 'v3' ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="yeshua_recaptcha_threshold"><?php _e('Threshold (v3)', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="range" 
                               name="yeshua_recaptcha_threshold" 
                               id="yeshua_recaptcha_threshold" 
                               value="<?php echo esc_attr($threshold); ?>" 
                               min="0" 
                               max="1" 
                               step="0.1"
                               class="yeshua-range">
                        <span id="yeshua-threshold-value"><?php echo esc_html($threshold); ?></span>
                        <p class="description">
                            <?php _e('Score mínimo para aprovar (0.0 = menos restritivo, 1.0 = mais restritivo). Recomendado: 0.5', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card yeshua-card-info">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Informações sobre as versões', 'yeshua-conversoes'); ?>
            </h2>
            
            <div class="yeshua-info-grid">
                <div class="yeshua-info-item">
                    <h4><?php _e('v2 Checkbox', 'yeshua-conversoes'); ?></h4>
                    <p><?php _e('O usuário precisa clicar em "Não sou um robô". Mais visível, pode impactar UX.', 'yeshua-conversoes'); ?></p>
                </div>
                <div class="yeshua-info-item">
                    <h4><?php _e('v2 Invisível', 'yeshua-conversoes'); ?></h4>
                    <p><?php _e('Validação automática. O desafio só aparece se houver suspeita de bot.', 'yeshua-conversoes'); ?></p>
                </div>
                <div class="yeshua-info-item">
                    <h4><?php _e('v3 Score-based', 'yeshua-conversoes'); ?></h4>
                    <p><?php _e('Totalmente invisível. Retorna um score de 0 a 1 que você define o limite mínimo.', 'yeshua-conversoes'); ?></p>
                </div>
            </div>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'yeshua-conversoes')); ?>
    </form>
</div>





