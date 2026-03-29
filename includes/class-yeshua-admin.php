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
     * E-mails autorizados a ver/gerenciar o plugin
     */
    private static $allowed_emails = [
        'webmaster@agathas.com.br',
    ];

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
     * Verifica se o usuário atual tem permissão para ver o plugin
     */
    public static function current_user_allowed() {
        // wp_get_current_user() só está disponível após 'plugins_loaded'
        if (!function_exists('wp_get_current_user')) {
            return false;
        }
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return false;
        }
        return in_array(strtolower($user->user_email), self::$allowed_emails, true);
    }

    /**
     * Construtor
     */
    private function __construct() {
        // Oculta plugin da lista de plugins para usuários não autorizados
        add_filter('all_plugins', [$this, 'hide_plugin_from_list']);

        // Impede desativação/exclusão por usuários não autorizados
        add_filter('plugin_action_links', [$this, 'hide_plugin_action_links'], 10, 2);

        // Defer hooks que dependem do usuário para quando ele estiver disponível
        add_action('admin_menu', [$this, 'maybe_add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'maybe_register_settings']);
        add_action('admin_init', [$this, 'handle_external_forms_save']);
    }

    /**
     * Registra menu apenas para usuários autorizados
     */
    public function maybe_add_admin_menu() {
        if (self::current_user_allowed()) {
            $this->add_admin_menu();
        }
    }

    /**
     * Registra settings apenas para usuários autorizados
     */
    public function maybe_register_settings() {
        if (self::current_user_allowed()) {
            $this->register_settings();
        }
    }

    /**
     * Oculta o plugin da lista em Plugins > Plugins Instalados
     */
    public function hide_plugin_from_list($plugins) {
        if (!self::current_user_allowed()) {
            unset($plugins[YESHUA_PLUGIN_BASENAME]);
        }
        return $plugins;
    }

    /**
     * Remove action links (Desativar, Editar) para usuários não autorizados
     */
    public function hide_plugin_action_links($actions, $plugin_file) {
        if ($plugin_file === YESHUA_PLUGIN_BASENAME && !self::current_user_allowed()) {
            return [];
        }
        return $actions;
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

        // Submenu - GTM / GA4 / Ads
        add_submenu_page(
            $this->menu_slug,
            __('GTM / GA4 / Ads', 'yeshua-conversoes'),
            __('GTM / GA4 / Ads', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-gtm',
            [$this, 'render_gtm_page']
        );

        // Submenu - Formulários Externos
        add_submenu_page(
            $this->menu_slug,
            __('Forms Externos', 'yeshua-conversoes'),
            __('Forms Externos', 'yeshua-conversoes'),
            'manage_options',
            $this->menu_slug . '-external-forms',
            [$this, 'render_external_forms_page']
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
        register_setting('yeshua_evolution', 'yeshua_evolution_label_id', ['sanitize_callback' => 'sanitize_text_field']);
        
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
        register_setting('yeshua_smtp', 'yeshua_smtp_to_email', ['sanitize_callback' => [$this, 'sanitize_email_list']]);
        
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
        register_setting('yeshua_form_whatsapp', 'yeshua_form_whatsapp_custom_css', ['sanitize_callback' => [$this, 'sanitize_css']]);
        
        // GTM / GA4 / Google Ads
        register_setting('yeshua_gtm', 'yeshua_gtm_id', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_gtm', 'yeshua_ga4_id', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_gtm', 'yeshua_gads_id', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_gtm', 'yeshua_gads_label', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('yeshua_gtm', 'yeshua_gtm_thank_you_urls', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_gtm', 'yeshua_gtm_exclude_admins', ['sanitize_callback' => 'absint']);
        register_setting('yeshua_gtm', 'yeshua_gtm_external_forms', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('yeshua_gtm', 'yeshua_meta_pixel_id', ['sanitize_callback' => 'sanitize_text_field']);

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
     * Sanitiza lista de e-mails separados por vírgula
     */
    public function sanitize_email_list($input) {
        $emails = array_map('trim', explode(',', $input));
        $valid = [];
        foreach ($emails as $email) {
            $sanitized = sanitize_email($email);
            if (!empty($sanitized)) {
                $valid[] = $sanitized;
            }
        }
        return implode(', ', $valid);
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
        if (!self::current_user_allowed()) {
            return;
        }

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
     * Renderiza página GTM / GA4 / Ads
     */
    public function render_gtm_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include YESHUA_PLUGIN_DIR . 'templates/admin/gtm.php';
    }

    /**
     * Renderiza página Formulários Externos
     */
    public function render_external_forms_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include YESHUA_PLUGIN_DIR . 'templates/admin/external-forms.php';
    }

    /**
     * Salva configurações de formulários externos (POST manual, não usa Settings API)
     */
    public function handle_external_forms_save() {
        if (!self::current_user_allowed()) {
            return;
        }

        if (
            empty($_POST['yeshua_external_forms_nonce']) ||
            !wp_verify_nonce($_POST['yeshua_external_forms_nonce'], 'yeshua_save_external_forms')
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Salva mapeamentos
        $raw_mappings = $_POST['yeshua_mappings'] ?? [];
        $mappings = [];

        if (is_array($raw_mappings)) {
            foreach ($raw_mappings as $mapping) {
                if (empty($mapping['plugin']) || empty($mapping['form_id'])) {
                    continue;
                }

                $mappings[] = [
                    'enabled' => !empty($mapping['enabled']) ? '1' : '0',
                    'plugin' => sanitize_text_field($mapping['plugin']),
                    'form_id' => sanitize_text_field($mapping['form_id']),
                    'form_name' => sanitize_text_field($mapping['form_name'] ?? ''),
                    'field_nome' => sanitize_text_field($mapping['field_nome'] ?? ''),
                    'field_email' => sanitize_text_field($mapping['field_email'] ?? ''),
                    'field_telefone' => sanitize_text_field($mapping['field_telefone'] ?? ''),
                ];
            }
        }

        update_option('yeshua_external_forms_mappings', $mappings);
        update_option('yeshua_external_forms_message', sanitize_textarea_field($_POST['yeshua_external_forms_message'] ?? ''));
        update_option('yeshua_external_forms_email_template', sanitize_textarea_field($_POST['yeshua_external_forms_email_template'] ?? ''));

        add_settings_error(
            'yeshua_external_forms',
            'yeshua_external_forms_saved',
            __('Configuracoes salvas com sucesso.', 'yeshua-conversoes'),
            'updated'
        );
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


