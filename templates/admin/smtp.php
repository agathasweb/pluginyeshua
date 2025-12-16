<?php
/**
 * Template da página SMTP
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$smtp_host = get_option('yeshua_smtp_host', '');
$smtp_port = get_option('yeshua_smtp_port', '587');
$smtp_encryption = get_option('yeshua_smtp_encryption', 'tls');
$smtp_user = get_option('yeshua_smtp_user', '');
$smtp_password = get_option('yeshua_smtp_password', '');
$smtp_from_email = get_option('yeshua_smtp_from_email', '');
$smtp_from_name = get_option('yeshua_smtp_from_name', '');
$smtp_to_email = get_option('yeshua_smtp_to_email', '');
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-email-alt"></span>
            <span class="yeshua-title"><?php _e('Configuração de E-mail/SMTP', 'yeshua-conversoes'); ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-smtp-form">
        <?php settings_fields('yeshua_smtp'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Servidor SMTP', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_host"><?php _e('Servidor SMTP', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_smtp_host" 
                               id="yeshua_smtp_host" 
                               value="<?php echo esc_attr($smtp_host); ?>" 
                               class="regular-text"
                               placeholder="smtp.exemplo.com">
                        <p class="description">
                            <?php _e('Endereço do servidor SMTP', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_port"><?php _e('Porta', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <select name="yeshua_smtp_port" id="yeshua_smtp_port">
                            <option value="25" <?php selected($smtp_port, '25'); ?>>25 (SMTP padrão)</option>
                            <option value="465" <?php selected($smtp_port, '465'); ?>>465 (SSL)</option>
                            <option value="587" <?php selected($smtp_port, '587'); ?>>587 (TLS)</option>
                            <option value="2525" <?php selected($smtp_port, '2525'); ?>>2525 (Alternativa)</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_encryption"><?php _e('Criptografia', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <select name="yeshua_smtp_encryption" id="yeshua_smtp_encryption">
                            <option value="none" <?php selected($smtp_encryption, 'none'); ?>><?php _e('Nenhuma', 'yeshua-conversoes'); ?></option>
                            <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
                        </select>
                        <p class="description">
                            <?php _e('Tipo de criptografia para conexão segura', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_user"><?php _e('Usuário SMTP', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_smtp_user" 
                               id="yeshua_smtp_user" 
                               value="<?php echo esc_attr($smtp_user); ?>" 
                               class="regular-text"
                               placeholder="usuario@exemplo.com">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_password"><?php _e('Senha SMTP', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               name="yeshua_smtp_password" 
                               id="yeshua_smtp_password" 
                               value="<?php echo esc_attr($smtp_password); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Configurações de E-mail', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_from_email"><?php _e('E-mail Remetente', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="email" 
                               name="yeshua_smtp_from_email" 
                               id="yeshua_smtp_from_email" 
                               value="<?php echo esc_attr($smtp_from_email); ?>" 
                               class="regular-text"
                               placeholder="noreply@exemplo.com">
                        <p class="description">
                            <?php _e('E-mail que aparecerá como remetente', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_from_name"><?php _e('Nome Remetente', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_smtp_from_name" 
                               id="yeshua_smtp_from_name" 
                               value="<?php echo esc_attr($smtp_from_name); ?>" 
                               class="regular-text"
                               placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_smtp_to_email"><?php _e('E-mail de Destino', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="email" 
                               name="yeshua_smtp_to_email" 
                               id="yeshua_smtp_to_email" 
                               value="<?php echo esc_attr($smtp_to_email); ?>" 
                               class="regular-text"
                               placeholder="vendas@exemplo.com">
                        <p class="description">
                            <?php _e('E-mail que receberá as notificações de leads', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Testar Configuração', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description">
                <?php _e('Envie um e-mail de teste para verificar se a configuração SMTP está funcionando.', 'yeshua-conversoes'); ?>
            </p>
            
            <div class="yeshua-input-group">
                <input type="email" 
                       id="yeshua-test-email-address" 
                       placeholder="<?php _e('E-mail para teste', 'yeshua-conversoes'); ?>"
                       value="<?php echo esc_attr($smtp_to_email); ?>"
                       class="regular-text">
                <button type="button" id="yeshua-test-smtp" class="button button-secondary">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('Enviar E-mail de Teste', 'yeshua-conversoes'); ?>
                </button>
            </div>
            
            <div id="yeshua-smtp-status" class="yeshua-status-message" style="display: none;"></div>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'yeshua-conversoes')); ?>
    </form>
</div>





