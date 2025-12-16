<?php
/**
 * Classe para gerenciamento dos formulários modais
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Forms {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * Retorna instância única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_modals']);
        
        // Também carregar modais no admin para testes
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_footer', [$this, 'render_admin_modals']);
    }
    
    /**
     * Verifica se está em página do plugin no admin
     */
    private function is_plugin_admin_page($hook = null) {
        // Verifica pelo hook (formato: toplevel_page_slug ou yeshua_page_slug)
        if ($hook && strpos($hook, 'yeshua') !== false) {
            return true;
        }
        
        // Verifica pelo parâmetro GET page
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (strpos($page, 'yeshua') !== false) {
            return true;
        }
        
        // Verifica pelo screen
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'yeshua') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Carrega assets no admin para testes
     */
    public function enqueue_admin_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Apenas nas páginas do plugin
        if (strpos($page, 'yeshua') === false && strpos($hook, 'yeshua') === false) {
            return;
        }
        
        // Estilos do formulário
        wp_enqueue_style(
            'yeshua-forms',
            YESHUA_PLUGIN_URL . 'assets/css/forms.css',
            [],
            YESHUA_VERSION
        );
        
        // Adicionar estilos inline para o modal funcionar no admin
        wp_add_inline_style('yeshua-forms', '
            /* Modal Base */
            .yeshua-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999999;
            }
            .yeshua-modal.active {
                display: block !important;
            }
            .yeshua-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 1;
            }
            .yeshua-modal-container {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 2;
                width: 90%;
                max-width: 450px;
            }
            .yeshua-modal-content {
                background: white;
                border-radius: 16px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                position: relative;
            }
            .yeshua-modal-close {
                position: absolute;
                top: 16px;
                right: 16px;
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                color: #6b7280;
                border-radius: 8px;
                z-index: 10;
                transition: all 0.2s;
            }
            .yeshua-modal-close:hover {
                color: #111827;
                background: #f3f4f6;
            }
            .yeshua-modal-close svg {
                width: 20px;
                height: 20px;
            }
            .yeshua-modal-header {
                padding: 32px 24px 16px;
                text-align: center;
            }
            .yeshua-modal-icon {
                width: 64px;
                height: 64px;
                margin: 0 auto 16px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .yeshua-modal-icon-whatsapp {
                background: #25D366;
                color: white;
            }
            .yeshua-modal-icon-lead {
                background: #3b82f6;
                color: white;
            }
            .yeshua-modal-icon.yeshua-modal-logo {
                background: transparent;
                border: none;
                box-shadow: none;
                overflow: visible;
                border-radius: 0 !important;
                width: auto;
                height: auto;
                max-width: 200px;
                display: block;
            }
            .yeshua-modal-logo img {
                width: auto;
                max-width: 100%;
                height: auto;
                max-height: 64px;
                object-fit: contain;
                border-radius: 0 !important;
                display: block;
                margin: 0 auto;
            }
            .yeshua-modal-title {
                font-size: 20px;
                font-weight: 700;
                color: #111827;
                margin: 0 0 8px;
            }
            .yeshua-modal-subtitle {
                font-size: 14px;
                color: #6b7280;
                margin: 0;
            }
            .yeshua-form {
                padding: 0 24px 24px;
            }
            .yeshua-form-group {
                margin-bottom: 16px;
            }
            .yeshua-label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
                color: #374151;
                font-size: 14px;
            }
            .yeshua-required {
                color: #ef4444;
            }
            .yeshua-input {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 15px;
                transition: border-color 0.2s, box-shadow 0.2s;
                box-sizing: border-box;
            }
            .yeshua-input:focus {
                outline: none;
                border-color: #25D366;
                box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
            }
            .yeshua-submit-btn {
                width: 100%;
                padding: 14px 20px;
                background: #25D366;
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            .yeshua-submit-btn:hover {
                background: #1ebe5b;
                transform: translateY(-1px);
            }
            .yeshua-submit-btn:disabled {
                background: #9ca3af;
                cursor: not-allowed;
                transform: none;
            }
            .yeshua-submit-btn-lead {
                background: #3b82f6;
            }
            .yeshua-submit-btn-lead:hover {
                background: #2563eb;
            }
            .yeshua-spinner {
                width: 20px;
                height: 20px;
                animation: yeshua-spin 1s linear infinite;
            }
            @keyframes yeshua-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .yeshua-error-message {
                color: #ef4444;
                font-size: 12px;
                margin-top: 4px;
                display: none;
            }
            .yeshua-form-group.error .yeshua-error-message {
                display: block;
            }
            .yeshua-form-group.error .yeshua-input {
                border-color: #ef4444;
            }
            .yeshua-form-message {
                padding: 12px;
                border-radius: 8px;
                margin-top: 16px;
                font-size: 14px;
                text-align: center;
            }
            .yeshua-form-message.success {
                background: #d1fae5;
                color: #065f46;
            }
            .yeshua-form-message.error {
                background: #fee2e2;
                color: #991b1b;
            }
            .yeshua-recaptcha-container {
                display: flex;
                justify-content: center;
                margin: 16px 0;
            }
            .yeshua-recaptcha-container .g-recaptcha {
                transform-origin: center;
            }
        ');
        
        // Scripts do formulário
        wp_enqueue_script(
            'yeshua-phone-mask',
            YESHUA_PLUGIN_URL . 'assets/js/phone-mask.js',
            [],
            YESHUA_VERSION,
            true
        );
        
        wp_enqueue_script(
            'yeshua-forms',
            YESHUA_PLUGIN_URL . 'assets/js/forms.js',
            ['jquery', 'yeshua-phone-mask'],
            YESHUA_VERSION,
            true
        );
        
        // Carrega o reCAPTCHA no admin para testes
        $recaptcha = Yeshua_Recaptcha::get_instance();
        if ($recaptcha->is_configured()) {
            // Primeiro adiciona as callbacks do reCAPTCHA (devem estar disponíveis antes do script do Google)
            wp_add_inline_script('yeshua-forms', $recaptcha->get_javascript(), 'before');
            
            // Depois carrega o script do Google reCAPTCHA
            wp_enqueue_script(
                'google-recaptcha',
                $recaptcha->get_script_url(),
                ['yeshua-forms'],
                null,
                true
            );
        }
        
        // Passa configurações para o JavaScript
        wp_localize_script('yeshua-forms', 'yeshuaForms', $this->get_js_config());
    }
    
    /**
     * Renderiza modais no admin para testes
     */
    public function render_admin_modals() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Só renderiza nas páginas do plugin
        if (strpos($page, 'yeshua') === false) {
            return;
        }
        
        $recaptcha = Yeshua_Recaptcha::get_instance();
        
        // Renderiza modal WhatsApp na página de configuração
        if ($page === 'yeshua-conversoes-form-whatsapp') {
            $this->render_whatsapp_modal($recaptcha);
        }
        
        // Renderiza modal Lead na página de configuração
        if ($page === 'yeshua-conversoes-form-lead') {
            $this->render_lead_modal($recaptcha);
        }
    }
    
    /**
     * Verifica se algum formulário está habilitado
     */
    private function has_enabled_forms() {
        $whatsapp_enabled = get_option('yeshua_form_whatsapp_enabled', '1');
        $lead_enabled = get_option('yeshua_form_lead_enabled', '1');
        
        return $whatsapp_enabled || $lead_enabled;
    }
    
    /**
     * Carrega assets do frontend
     */
    public function enqueue_assets() {
        if (!$this->has_enabled_forms()) {
            return;
        }
        
        // Tailwind CSS via CDN (com prefixo para evitar conflitos)
        wp_enqueue_script(
            'tailwindcss',
            'https://cdn.tailwindcss.com',
            [],
            null,
            false
        );
        
        // Configuração do Tailwind para usar prefixo (garante objeto antes de setar)
        wp_add_inline_script('tailwindcss', "
            window.tailwind = window.tailwind || {};
            tailwind.config = {
                prefix: 'ys-',
                corePlugins: {
                    preflight: false,
                }
            }
        ", 'before');
        
        // Estilos do formulário
        wp_enqueue_style(
            'yeshua-forms',
            YESHUA_PLUGIN_URL . 'assets/css/forms.css',
            [],
            YESHUA_VERSION
        );
        
        // CSS Customizado
        $custom_css_whatsapp = get_option('yeshua_form_whatsapp_custom_css', '');
        $custom_css_lead = get_option('yeshua_form_lead_custom_css', '');
        $custom_css = trim($custom_css_whatsapp . "\n" . $custom_css_lead);
        
        if (!empty($custom_css)) {
            wp_add_inline_style('yeshua-forms', $custom_css);
        }
        
        // Scripts do formulário
        wp_enqueue_script(
            'yeshua-phone-mask',
            YESHUA_PLUGIN_URL . 'assets/js/phone-mask.js',
            [],
            YESHUA_VERSION,
            true
        );
        
        wp_enqueue_script(
            'yeshua-forms',
            YESHUA_PLUGIN_URL . 'assets/js/forms.js',
            ['yeshua-phone-mask'],
            YESHUA_VERSION,
            true
        );
        
        // ReCaptcha
        $recaptcha = Yeshua_Recaptcha::get_instance();
        if ($recaptcha->is_configured()) {
            wp_enqueue_script(
                'google-recaptcha',
                $recaptcha->get_script_url(),
                [],
                null,
                true
            );
        }
        
        // Passa configurações para o JavaScript
        wp_localize_script('yeshua-forms', 'yeshuaForms', $this->get_js_config());
    }
    
    /**
     * Retorna configurações para JavaScript
     */
    private function get_js_config() {
        $recaptcha = Yeshua_Recaptcha::get_instance();
        
        return [
            'restUrl' => rest_url('yeshua/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'recaptcha' => [
                'enabled' => $recaptcha->is_configured(),
                'version' => $recaptcha->get_version(),
                'siteKey' => $recaptcha->get_site_key(),
            ],
            'forms' => [
                'whatsapp' => [
                    'enabled' => (bool) get_option('yeshua_form_whatsapp_enabled', '1'),
                    'title' => get_option('yeshua_form_whatsapp_title', 'Fale Conosco'),
                    'buttonText' => get_option('yeshua_form_whatsapp_button_text', 'Enviar'),
                    'triggers' => [
                        'onload' => (bool) get_option('yeshua_form_whatsapp_trigger_onload', '0'),
                        'delay' => (int) get_option('yeshua_form_whatsapp_trigger_delay', 0),
                        'inactivity' => (int) get_option('yeshua_form_whatsapp_trigger_inactivity', 0),
                        'scroll' => (int) get_option('yeshua_form_whatsapp_trigger_scroll', 0),
                        'exitIntent' => (bool) get_option('yeshua_form_whatsapp_trigger_exit_intent', '0'),
                    ],
                ],
                'lead' => [
                    'enabled' => (bool) get_option('yeshua_form_lead_enabled', '1'),
                    'title' => get_option('yeshua_form_lead_title', 'Solicite um Orçamento'),
                    'buttonText' => get_option('yeshua_form_lead_button_text', 'Enviar'),
                    'extraFields' => get_option('yeshua_form_lead_extra_fields', []),
                    'successMessage' => get_option('yeshua_form_lead_success_message', 'Obrigado! Entraremos em contato em breve.'),
                    'redirect' => get_option('yeshua_form_lead_redirect', ''),
                    'triggers' => [
                        'onload' => (bool) get_option('yeshua_form_lead_trigger_onload', '0'),
                        'delay' => (int) get_option('yeshua_form_lead_trigger_delay', 0),
                        'inactivity' => (int) get_option('yeshua_form_lead_trigger_inactivity', 0),
                        'scroll' => (int) get_option('yeshua_form_lead_trigger_scroll', 0),
                        'exitIntent' => (bool) get_option('yeshua_form_lead_trigger_exit_intent', '0'),
                    ],
                ],
            ],
            'i18n' => [
                'name' => __('Nome', 'yeshua-conversoes'),
                'email' => __('E-mail', 'yeshua-conversoes'),
                'whatsapp' => __('WhatsApp', 'yeshua-conversoes'),
                'send' => __('Enviar', 'yeshua-conversoes'),
                'sending' => __('Enviando...', 'yeshua-conversoes'),
                'close' => __('Fechar', 'yeshua-conversoes'),
                'required' => __('Campo obrigatório', 'yeshua-conversoes'),
                'invalidEmail' => __('E-mail inválido', 'yeshua-conversoes'),
                'invalidPhone' => __('Telefone inválido', 'yeshua-conversoes'),
                'error' => __('Erro ao enviar. Tente novamente.', 'yeshua-conversoes'),
                'recaptchaRequired' => __('Por favor, complete o ReCaptcha', 'yeshua-conversoes'),
            ],
        ];
    }
    
    /**
     * Renderiza os modais
     */
    public function render_modals() {
        if (!$this->has_enabled_forms()) {
            return;
        }
        
        if (is_admin()) {
            return;
        }
        
        $recaptcha = Yeshua_Recaptcha::get_instance();
        
        // Renderiza modal WhatsApp
        if (get_option('yeshua_form_whatsapp_enabled', '1')) {
            $this->render_whatsapp_modal($recaptcha);
            $this->render_whatsapp_floating_button();
        }
        
        // Renderiza modal Lead
        if (get_option('yeshua_form_lead_enabled', '1')) {
            $this->render_lead_modal($recaptcha);
        }
        
        // Adiciona JavaScript do ReCaptcha
        if ($recaptcha->is_configured()) {
            echo '<script>' . $recaptcha->get_javascript() . '</script>';
        }
    }
    
    /**
     * Renderiza modal WhatsApp
     */
    private function render_whatsapp_modal($recaptcha) {
        $title = get_option('yeshua_form_whatsapp_title', 'Fale Conosco');
        $button_text = get_option('yeshua_form_whatsapp_button_text', 'Enviar');
        $logo_url = get_option('yeshua_form_whatsapp_logo', '');
        // Botão só fica desativado inicialmente para v2_checkbox (requer interação do usuário)
        // v2_invisible e v3 não precisam de interação, então botão fica ativo
        $is_v2_checkbox = $recaptcha->get_version() === 'v2_checkbox';
        
        include YESHUA_PLUGIN_DIR . 'templates/forms/modal-whatsapp.php';
    }
    
    /**
     * Renderiza modal Lead
     */
    private function render_lead_modal($recaptcha) {
        $title = get_option('yeshua_form_lead_title', 'Solicite um Orçamento');
        $button_text = get_option('yeshua_form_lead_button_text', 'Enviar');
        $extra_fields = get_option('yeshua_form_lead_extra_fields', []);
        // Botão só fica desativado inicialmente para v2_checkbox (requer interação do usuário)
        // v2_invisible e v3 não precisam de interação, então botão fica ativo
        $is_v2_checkbox = $recaptcha->get_version() === 'v2_checkbox';
        
        include YESHUA_PLUGIN_DIR . 'templates/forms/modal-lead.php';
    }
    
    /**
     * Processa submissão do formulário
     */
    public static function process_submission($data, $form_type = 'lead') {
        $result = [
            'success' => false,
            'message' => '',
            'redirect' => '',
        ];
        
        // Valida ReCaptcha
        $recaptcha = Yeshua_Recaptcha::get_instance();
        if ($recaptcha->is_configured()) {
            $token = $data['recaptcha_token'] ?? '';
            $verify = $recaptcha->verify($token, $form_type);
            
            if (!$verify['success']) {
                $result['message'] = $verify['message'];
                return $result;
            }
        }
        
        // Prepara dados do lead
        $lead_data = [
            'nome' => sanitize_text_field($data['nome'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'telefone' => sanitize_text_field($data['whatsapp'] ?? ''),
            'origem' => $form_type === 'whatsapp' ? 'whatsapp' : 'formulario',
            'ip' => Yeshua_Api::get_client_ip(),
            'dispositivo' => Yeshua_Api::detect_device(),
            'url_origem' => sanitize_url($data['url_origem'] ?? home_url()),
        ];
        
        // Adiciona UTMs
        $utm_fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utm_fields as $field) {
            if (!empty($data[$field])) {
                $lead_data[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        // Campos extras
        $campos_extras = [];
        if (isset($data['campos_extras']) && is_array($data['campos_extras'])) {
            foreach ($data['campos_extras'] as $campo => $valor) {
                $campos_extras[sanitize_text_field($campo)] = sanitize_text_field($valor);
            }
            $lead_data['dados_extras'] = $campos_extras;
        }
        
        // Registra lead na API YESHUA
        $api = Yeshua_Api::get_instance();
        if ($api->is_configured()) {
            $api_result = $api->register_lead($lead_data);
            
            if (!isset($api_result['success']) || !$api_result['success']) {
                // Log do erro mas continua o processamento
                error_log('YESHUA API Error: ' . print_r($api_result, true));
            }
        }
        
        // Prepara dados para mensagens
        $message_data = array_merge($lead_data, [
            'whatsapp' => $lead_data['telefone'],
            'campos_extras' => $campos_extras,
        ]);
        
        // Envia mensagem via Evolution API
        $evolution = Yeshua_Evolution::get_instance();
        if ($evolution->is_configured()) {
            $message_template = $form_type === 'whatsapp' 
                ? get_option('yeshua_form_whatsapp_message', '')
                : get_option('yeshua_form_lead_message', '');
            
            if (!empty($message_template)) {
                $message = Yeshua_Evolution::process_message($message_template, $message_data);
                $evolution->send_text($lead_data['telefone'], $message);
            }
        }
        
        // Envia e-mail
        $smtp = Yeshua_Smtp::get_instance();
        if ($smtp->is_configured()) {
            $email_template = $form_type === 'whatsapp'
                ? get_option('yeshua_form_whatsapp_email_template', '')
                : get_option('yeshua_form_lead_email_template', '');
            
            $send_email_option = $form_type === 'whatsapp' ? get_option('yeshua_form_whatsapp_send_email', 'yes') : get_option('yeshua_form_lead_send_email', 'yes');
            if ($send_email_option === 'yes') {
                $smtp->send_lead_notification($message_data, $email_template);
            }
        }
        
        // Prepara resposta
        $result['success'] = true;
        
        if ($form_type === 'whatsapp') {
            // Formulário WhatsApp - mensagem já foi enviada via Evolution API para o visitante
            $redirect = get_option('yeshua_form_whatsapp_redirect', '');
            $result['redirect'] = !empty($redirect) ? $redirect : '';
            $result['message'] = __('Mensagem enviada com sucesso! Em breve entraremos em contato.', 'yeshua-conversoes');
        } else {
            $result['message'] = get_option('yeshua_form_lead_success_message', __('Obrigado! Entraremos em contato em breve.', 'yeshua-conversoes'));
            $result['redirect'] = get_option('yeshua_form_lead_redirect', '');
        }
        
        return $result;
    }

    /**
     * Renderiza botão flutuante do WhatsApp
     */
    private function render_whatsapp_floating_button() {
        ?>
        <button
            type="button"
            id="yeshua-whatsapp-fab"
            class="yeshua-whatsapp-fab"
            aria-label="<?php esc_attr_e('Abrir formulário do WhatsApp', 'yeshua-conversoes'); ?>"
            style="
                display:inline-block;
                font-weight:400;
                color:#fff;
                text-align:center;
                white-space:nowrap;
                -webkit-user-select:none;
                -moz-user-select:none;
                user-select:none;
                background:linear-gradient(135deg, #25D366, #128C7E);
                border:1px solid #fff;
                padding:0;
                font-size:16px;
                border-radius:50%;
                transition:all .3s;
                width:62px;
                height:62px;
                box-shadow:0 15px 25px -10px rgba(0,0,0,0.35);
                cursor:pointer;
                z-index:2147480000;
            "
        >
            <span class="yeshua-whatsapp-fab-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </span>
        </button>
        <?php
    }
}



