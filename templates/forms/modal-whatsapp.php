<?php
/**
 * Template do modal WhatsApp
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Modal WhatsApp -->
<div id="yeshua-modal-whatsapp" class="yeshua-modal" style="display: none;">
    <div class="yeshua-modal-overlay"></div>
    <div class="yeshua-modal-container">
        <div class="yeshua-modal-content">
            <button
                type="button"
                class="yeshua-modal-close"
                aria-label="<?php esc_attr_e('Fechar', 'yeshua-conversoes'); ?>"
                style="
                    position:absolute;
                    top:16px;
                    right:16px;
                    background:none;
                    border:none;
                    cursor:pointer;
                    padding:8px;
                    color:#6b7280;
                    border-radius:8px;
                    z-index:10;
                    transition:all 0.2s;
                "
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="yeshua-modal-header">
                <?php if (!empty($logo_url)) : ?>
                    <div class="yeshua-modal-icon yeshua-modal-logo">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Logo do cliente', 'yeshua-conversoes'); ?>">
                    </div>
                <?php else : ?>
                    <div class="yeshua-modal-icon yeshua-modal-icon-whatsapp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                <h2 class="yeshua-modal-title"><?php echo esc_html($title); ?></h2>
                <p class="yeshua-modal-subtitle"><?php _e('Preencha seus dados para iniciar uma conversa', 'yeshua-conversoes'); ?></p>
            </div>
            
            <form id="yeshua-form-whatsapp" class="yeshua-form" novalidate>
                <input type="hidden" name="form_type" value="whatsapp">
                <input type="hidden" name="url_origem" value="<?php echo esc_url(home_url($_SERVER['REQUEST_URI'] ?? '')); ?>">
                
                <div class="yeshua-form-group">
                    <label for="yeshua-whatsapp-nome" class="yeshua-label">
                        <?php _e('Nome', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="text" 
                           id="yeshua-whatsapp-nome" 
                           name="nome" 
                           class="yeshua-input" 
                           required
                           placeholder="<?php esc_attr_e('Seu nome completo', 'yeshua-conversoes'); ?>">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <div class="yeshua-form-group">
                    <label for="yeshua-whatsapp-email" class="yeshua-label">
                        <?php _e('E-mail', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="email" 
                           id="yeshua-whatsapp-email" 
                           name="email" 
                           class="yeshua-input" 
                           required
                           placeholder="<?php esc_attr_e('seu@email.com', 'yeshua-conversoes'); ?>">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <div class="yeshua-form-group">
                    <label for="yeshua-whatsapp-phone" class="yeshua-label">
                        <?php _e('WhatsApp', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="tel" 
                           id="yeshua-whatsapp-phone" 
                           name="whatsapp" 
                           class="yeshua-input yeshua-phone-mask" 
                           required
                           placeholder="(00) 00000-0000">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <?php if ($recaptcha->is_configured()) : ?>
                <div class="yeshua-form-group yeshua-recaptcha-container">
                    <?php echo $recaptcha->render('whatsapp'); ?>
                </div>
                <?php endif; ?>
                
                <div class="yeshua-form-group">
                    <button type="submit" 
                            class="yeshua-submit-btn yeshua-submit-btn-whatsapp<?php echo ($is_v2_checkbox && $recaptcha->is_configured()) ? ' opacity-50 cursor-not-allowed' : ''; ?>"
                            style="
                                width:100%;
                                padding:14px 20px;
                                background:#25D366;
                                color:white;
                                border:none;
                                border-radius:10px;
                                font-size:16px;
                                font-weight:600;
                                cursor:pointer;
                                transition:all 0.2s;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                gap:8px;
                            "
                            <?php echo ($is_v2_checkbox && $recaptcha->is_configured()) ? 'disabled' : ''; ?>>
                        <span class="yeshua-btn-text"><?php echo esc_html($button_text); ?></span>
                        <span class="yeshua-btn-loading" style="display: none;">
                            <svg class="yeshua-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php _e('Enviando...', 'yeshua-conversoes'); ?>
                        </span>
                    </button>
                </div>
                
                <div class="yeshua-form-message" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>



