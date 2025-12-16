<?php
/**
 * Classe para administração do plugin
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Admin {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * Slug do menu
     */
    private $menu_slug = 'yeshua-conversoes';
    
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
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Adiciona menu e submenus
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('YESHUA Conversões', 'yeshua-conversoes'),
            __('YESHUA', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_main_page'],
            'dashicons-chart-line',
            30
        );
        
        // Submenu - Configurações Gerais
        add_submenu_page(
            $this->menu_slug,
            __('Configurações', 'yeshua-conversoes'),
            __('Configurações', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_main_page']
        );
        
        // Submenu - Conversões
        add_submenu_page(
            $this->menu_slug,
            __('Conversões', 'yeshua-conversoes'),
            __('Conversões', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-conversions',
            [$this, 'render_conversions_page']
        );
        
        // Submenu - Evolution API
        add_submenu_page(
            $this->menu_slug,
            __('Evolution API', 'yeshua-conversoes'),
            __('Evolution API', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-evolution',
            [$this, 'render_evolution_page']
        );
        
        // Submenu - ReCaptcha
        add_submenu_page(
            $this->menu_slug,
            __('ReCaptcha', 'yeshua-conversoes'),
            __('ReCaptcha', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-recaptcha',
            [$this, 'render_recaptcha_page']
        );
        
        // Submenu - SMTP
        add_submenu_page(
            $this->menu_slug,
            __('E-mail/SMTP', 'yeshua-conversoes'),
            __('E-mail/SMTP', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-smtp',
            [$this, 'render_smtp_page']
        );
        
        // Submenu - Formulário WhatsApp
        add_submenu_page(
            $this->menu_slug,
            __('Form WhatsApp', 'yeshua-conversoes'),
            __('Form WhatsApp', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-form-whatsapp',
            [$this, 'render_form_whatsapp_page']
        );
        
        // Submenu - Formulário Lead
        add_submenu_page(
            $this->menu_slug,
            __('Form Lead', 'yeshua-conversoes'),
            __('Form Lead', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-form-lead',
            [$this, 'render_form_lead_page']
        );
    }
    
    /**
     * Registra as configurações
     */
    public function register_settings() {
        // Configurações gerais
        register_setting('yeshua_general', 'yeshua_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_general', 'yeshua_website_id', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_general', 'yeshua_tracking_enabled', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_general', 'yeshua_exclude_admins', ['sanitize_callback' => 'absint']);
        
        // Conversões
        register_setting('yeshua_conversions', 'yeshua_conversions', ['sanitize_callback' => [$this, 'sanitize_conversions']]);
        
        // Evolution API
        register_setting('yeshua_evolution', 'yeshua_evolution_url', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('yeshua_evolution', 'yeshua_evolution_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_evolution', 'yeshua_evolution_instance', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_evolution', 'yeshua_evolution_token', ['sanitize_callback' => 'sanitize_text_field']);
        
        // ReCaptcha
        register_setting('yeshua_recaptcha', 'yeshua_recaptcha_site_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_recaptcha', 'yeshua_recaptcha_secret_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_recaptcha', 'yeshua_recaptcha_version', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_recaptcha', 'yeshua_recaptcha_threshold', ['sanitize_callback' => [$this, 'sanitize_threshold']]);
        
        // SMTP
        register_setting('yeshua_smtp', 'yeshua_smtp_host', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_smtp', 'yeshua_smtp_port', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_smtp', 'yeshua_smtp_encryption', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_smtp', 'yeshua_smtp_user', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_smtp', 'yeshua_smtp_password', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_smtp', 'yeshua_smtp_from_email', ['sanitize_callback' => 'sanitize_email']);
        register_setting('yeshua_smtp', 'yeshua_smtp_from_name', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_smtp', 'yeshua_smtp_to_email', ['sanitize_callback' => 'sanitize_email']);
        
        // Formulário WhatsApp
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_enabled', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_button_text', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_message', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_email_template', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_redirect', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_logo', ['sanitize_callback' => 'esc_url_raw']);
        // Gatilhos avançados WhatsApp
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_trigger_onload', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_trigger_delay', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_trigger_inactivity', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_trigger_scroll', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_trigger_exit_intent', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_send_email', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_custom_css', ['sanitize_callback' => [$this, 'sanitize_css']]);
        
        // Formulário Lead
        register_setting('yeshua_form_lead', 'yeshua_form_lead_enabled', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_button_text', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_extra_fields', ['sanitize_callback' => [$this, 'sanitize_extra_fields']]);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_message', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_email_template', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_success_message', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_redirect', ['sanitize_callback' => 'esc_url_raw']);
        // Gatilhos avançados Lead
        register_setting('yeshua_form_lead', 'yeshua_form_lead_trigger_onload', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_trigger_delay', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_trigger_inactivity', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_trigger_scroll', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_trigger_exit_intent', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_send_email', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_form_lead', 'yeshua_form_lead_custom_css', ['sanitize_callback' => [$this, 'sanitize_css']]);
    }
    
    /**
     * Sanitiza CSS customizado
     */
    public function sanitize_css($css) {
        // Remove tags de script e outros elementos potencialmente perigosos
        $css = wp_strip_all_tags($css);
        // Permite apenas CSS válido
        return $css;
    }
    
    /**
     * Sanitiza conversões
     */
    public function sanitize_conversions($input) {
        if (!is_array($input)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($input as $item) {
            if (!empty($item['page_id']) || !empty($item['url_pattern'])) {
                $sanitized[] = [
                    'page_id' => absint($item['page_id'] ?? 0),
                    'url_pattern' => sanitize_text_field($item['url_pattern'] ?? ''),
                    'conversion_type' => sanitize_text_field($item['conversion_type'] ?? 'lead'),
                    'conversion_id' => sanitize_text_field($item['conversion_id'] ?? ''),
                ];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza campos extras
     */
    public function sanitize_extra_fields($input) {
        if (!is_array($input)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($input as $item) {
            if (!empty($item['label'])) {
                $sanitized[] = [
                    'label' => sanitize_text_field($item['label']),
                    'type' => sanitize_text_field($item['type'] ?? 'text'),
                    'required' => !empty($item['required']),
                    'options' => sanitize_textarea_field($item['options'] ?? ''),
                ];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza threshold
     */
    public function sanitize_threshold($input) {
        $value = floatval($input);
        return max(0, min(1, $value));
    }
    
    /**
     * Carrega assets do admin
     */
    public function enqueue_admin_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Verifica se estamos em uma página do plugin
        if (
            strpos($hook, 'yeshua-conversoes') === false &&
            strpos($page, 'yeshua-conversoes') === false &&
            strpos($page, 'yeshua') === false
        ) {
            return;
        }

        // Media uploader para campos de imagem
        wp_enqueue_media();
        
        wp_enqueue_style(
            'yeshua-admin',
            YESHUA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            YESHUA_VERSION
        );
        
        wp_enqueue_script(
            'yeshua-admin',
            YESHUA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'media-upload', 'media-views'],
            YESHUA_VERSION,
            true
        );
        
        wp_localize_script('yeshua-admin', 'yeshuaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('yeshua/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'validating' => __('Validando...', 'yeshua-conversoes'),
                'valid' => __('API válida!', 'yeshua-conversoes'),
                'invalid' => __('API inválida!', 'yeshua-conversoes'),
                'error' => __('Erro na validação', 'yeshua-conversoes'),
                'testing' => __('Testando...', 'yeshua-conversoes'),
                'testSuccess' => __('Teste realizado com sucesso!', 'yeshua-conversoes'),
                'testFailed' => __('Teste falhou!', 'yeshua-conversoes'),
                'confirm_delete' => __('Tem certeza que deseja remover?', 'yeshua-conversoes'),
            ],
        ]);
    }
    
    /**
     * Renderiza página principal
     */
    public function render_main_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Renderiza página de conversões
     */
    public function render_conversions_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/conversions.php';
    }
    
    /**
     * Renderiza página Evolution API
     */
    public function render_evolution_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/evolution.php';
    }
    
    /**
     * Renderiza página ReCaptcha
     */
    public function render_recaptcha_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/recaptcha.php';
    }
    
    /**
     * Renderiza página SMTP
     */
    public function render_smtp_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/smtp.php';
    }
    
    /**
     * Renderiza página Formulário WhatsApp
     */
    public function render_form_whatsapp_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/form-whatsapp.php';
    }
    
    /**
     * Renderiza página Formulário Lead
     */
    public function render_form_lead_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include YESHUA_PLUGIN_DIR . 'templates/admin/form-lead.php';
    }
    
    /**
     * Obtém todas as páginas do WordPress
     */
    public static function get_all_pages() {
        $pages = get_pages([
            'post_status' => 'publish',
            'sort_column' => 'post_title',
            'sort_order' => 'ASC',
        ]);
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        return array_merge($pages, $posts);
    }
}


