<?php
/**
 * Template do modal Lead
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Modal Lead -->
<div id="yeshua-modal-lead" class="yeshua-modal" style="display: none;">
    <div class="yeshua-modal-overlay"></div>
    <div class="yeshua-modal-container">
        <div class="yeshua-modal-content">
            <button type="button" class="yeshua-modal-close" aria-label="<?php esc_attr_e('Fechar', 'yeshua-conversoes'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="yeshua-modal-header">
                <div class="yeshua-modal-icon yeshua-modal-icon-lead">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h2 class="yeshua-modal-title"><?php echo esc_html($title); ?></h2>
                <p class="yeshua-modal-subtitle"><?php _e('Preencha o formulário abaixo', 'yeshua-conversoes'); ?></p>
            </div>
            
            <form id="yeshua-form-lead" class="yeshua-form" novalidate>
                <input type="hidden" name="form_type" value="lead">
                <input type="hidden" name="url_origem" value="<?php echo esc_url(home_url($_SERVER['REQUEST_URI'] ?? '')); ?>">
                
                <div class="yeshua-form-group">
                    <label for="yeshua-lead-nome" class="yeshua-label">
                        <?php _e('Nome', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="text" 
                           id="yeshua-lead-nome" 
                           name="nome" 
                           class="yeshua-input" 
                           required
                           placeholder="<?php esc_attr_e('Seu nome completo', 'yeshua-conversoes'); ?>">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <div class="yeshua-form-group">
                    <label for="yeshua-lead-email" class="yeshua-label">
                        <?php _e('E-mail', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="email" 
                           id="yeshua-lead-email" 
                           name="email" 
                           class="yeshua-input" 
                           required
                           placeholder="<?php esc_attr_e('seu@email.com', 'yeshua-conversoes'); ?>">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <div class="yeshua-form-group">
                    <label for="yeshua-lead-phone" class="yeshua-label">
                        <?php _e('WhatsApp', 'yeshua-conversoes'); ?> <span class="yeshua-required">*</span>
                    </label>
                    <input type="tel" 
                           id="yeshua-lead-phone" 
                           name="whatsapp" 
                           class="yeshua-input yeshua-phone-mask" 
                           required
                           placeholder="(00) 00000-0000">
                    <span class="yeshua-error-message"></span>
                </div>
                
                <?php if (!empty($extra_fields)) : ?>
                    <?php foreach ($extra_fields as $index => $field) : ?>
                        <?php 
                        $field_id = 'yeshua-lead-extra-' . $index;
                        $field_name = 'campos_extras[' . sanitize_title($field['label']) . ']';
                        $is_required = !empty($field['required']);
                        ?>
                        <div class="yeshua-form-group">
                            <label for="<?php echo esc_attr($field_id); ?>" class="yeshua-label">
                                <?php echo esc_html($field['label']); ?>
                                <?php if ($is_required) : ?><span class="yeshua-required">*</span><?php endif; ?>
                            </label>
                            
                            <?php if ($field['type'] === 'textarea') : ?>
                                <textarea id="<?php echo esc_attr($field_id); ?>" 
                                          name="<?php echo esc_attr($field_name); ?>" 
                                          class="yeshua-input yeshua-textarea"
                                          rows="3"
                                          <?php echo $is_required ? 'required' : ''; ?>></textarea>
                            
                            <?php elseif ($field['type'] === 'select') : ?>
                                <select id="<?php echo esc_attr($field_id); ?>" 
                                        name="<?php echo esc_attr($field_name); ?>" 
                                        class="yeshua-input yeshua-select"
                                        <?php echo $is_required ? 'required' : ''; ?>>
                                    <option value=""><?php _e('Selecione...', 'yeshua-conversoes'); ?></option>
                                    <?php 
                                    $options = array_map('trim', explode(',', $field['options'] ?? ''));
                                    foreach ($options as $option) : 
                                        if (!empty($option)) :
                                    ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </select>
                            
                            <?php else : ?>
                                <input type="<?php echo esc_attr($field['type']); ?>" 
                                       id="<?php echo esc_attr($field_id); ?>" 
                                       name="<?php echo esc_attr($field_name); ?>" 
                                       class="yeshua-input<?php echo $field['type'] === 'tel' ? ' yeshua-phone-mask' : ''; ?>"
                                       <?php echo $is_required ? 'required' : ''; ?>>
                            <?php endif; ?>
                            
                            <span class="yeshua-error-message"></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($recaptcha->is_configured()) : ?>
                <div class="yeshua-form-group yeshua-recaptcha-container">
                    <?php echo $recaptcha->render('lead'); ?>
                </div>
                <?php endif; ?>
                
                <div class="yeshua-form-group">
                    <button type="submit" 
                            class="yeshua-submit-btn yeshua-submit-btn-lead<?php echo ($is_v2_checkbox && $recaptcha->is_configured()) ? ' opacity-50 cursor-not-allowed' : ''; ?>"
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



