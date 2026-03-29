<?php
/**
 * Plugin Name: YESHUA Conversões
 * Plugin URI: https://agathasweb.com
 * Description: Integração completa com API TLC do YESHUA para rastreamento de tráfego, leads e conversões. Inclui formulários modais, integração Evolution API (WhatsApp) e envio de e-mails.
 * Version: 1.6.1
 * Author: Agathas Web
 * Author URI: https://agathasweb.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yeshua-conversoes
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('YESHUA_VERSION', '1.6.1');
define('YESHUA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YESHUA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YESHUA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('YESHUA_API_URL', 'https://yeshua.agathasweb.com/api');

/**
 * Classe principal do plugin
 */
final class Yeshua_Conversoes {
    
    /**
     * Instância única da classe
     */
    private static $instance = null;
    
    /**
     * Retorna a instância única da classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_update_checker();
        $this->init_hooks();
    }
    
    /**
     * Carrega as dependências do plugin
     */
    private function load_dependencies() {
        // Classes principais
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-api.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-admin.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-tracking.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-evolution.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-gtm.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-smtp.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-recaptcha.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-forms.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-external-forms.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-rest-api.php';
    }

    /**
     * Inicializa o verificador de atualizações (Plugin Update Checker)
     */
    private function init_update_checker() {
        // Caminho para a biblioteca Plugin Update Checker
        // Os arquivos devem estar em includes/updates/
        $puc_file = YESHUA_PLUGIN_DIR . 'includes/updates/plugin-update-checker.php';
        
        if (file_exists($puc_file)) {
            require $puc_file;
            
            /* 
             * CONFIGURAÇÃO DE ATUALIZAÇÃO AUTOMÁTICA
             */
            $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                'https://github.com/agathasweb/pluginyeshua', // URL do Repositório GitHub
                __FILE__,
                'yeshua-conversoes'
            );

            // Opcional: Se o repositório for privado, descomente a linha abaixo e coloque o token
            // $myUpdateChecker->setAuthentication('SEU_TOKEN_GITHUB');
        }
    }
    
    /**
     * Inicializa os hooks do WordPress
     */
    private function init_hooks() {
        // Ativação e desativação
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Inicialização
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        
        // Admin
        if (is_admin()) {
            Yeshua_Admin::get_instance();
        }
        
        // Frontend
        if (!is_admin()) {
            Yeshua_Tracking::get_instance();
            Yeshua_Gtm::get_instance();
        }
        
        // Forms - Carrega no frontend e também no admin para testes
        Yeshua_Forms::get_instance();

        // External Forms - Hooks server-side para Elementor, CF7, WPForms, Gravity Forms
        Yeshua_External_Forms::get_instance();
        
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Inicialização do plugin
     */
    public function init() {
        // Popula seletores de formulários externos se estiver vazio (upgrade de versões anteriores)
        $selectors = get_option('yeshua_gtm_external_forms', '');
        if (empty($selectors)) {
            update_option('yeshua_gtm_external_forms', ".elementor-form\n.wpcf7-form\n.wpforms-form");
        }
    }
    
    /**
     * Carrega o textdomain para tradução
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'yeshua-conversoes',
            false,
            dirname(YESHUA_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Registra as rotas da REST API
     */
    public function register_rest_routes() {
        Yeshua_Rest_Api::register_routes();
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Criar opções padrão
        $default_options = [
            'yeshua_api_key' => '',
            'yeshua_website_id' => '',
            'yeshua_tracking_enabled' => '1',
            'yeshua_exclude_admins' => '1',
            'yeshua_conversions' => [],
            'yeshua_evolution_url' => '',
            'yeshua_evolution_api_key' => '',
            'yeshua_evolution_instance' => '',
            'yeshua_evolution_token' => '',
            'yeshua_recaptcha_site_key' => '',
            'yeshua_recaptcha_secret_key' => '',
            'yeshua_recaptcha_version' => 'v2_checkbox',
            'yeshua_recaptcha_threshold' => '0.5',
            'yeshua_smtp_host' => '',
            'yeshua_smtp_port' => '587',
            'yeshua_smtp_encryption' => 'tls',
            'yeshua_smtp_user' => '',
            'yeshua_smtp_password' => '',
            'yeshua_smtp_from_email' => '',
            'yeshua_smtp_from_name' => '',
            'yeshua_smtp_to_email' => '',
            'yeshua_form_whatsapp_enabled' => '1',
            'yeshua_form_whatsapp_title' => 'Fale Conosco',
            'yeshua_form_whatsapp_button_text' => 'Enviar',
            'yeshua_form_whatsapp_message' => 'Olá {nome}! Recebemos seu contato. Em breve retornaremos.',
            'yeshua_form_whatsapp_email_template' => "Novo contato via WhatsApp:\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}",
            'yeshua_form_whatsapp_redirect' => '',
            'yeshua_form_lead_enabled' => '1',
            'yeshua_form_lead_title' => 'Solicite um Orçamento',
            'yeshua_form_lead_button_text' => 'Enviar',
            'yeshua_form_lead_extra_fields' => [],
            'yeshua_form_lead_message' => 'Novo lead recebido!\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}',
            'yeshua_form_lead_email_template' => "Novo lead capturado:\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}\n\n{campos_extras}",
            'yeshua_form_lead_success_message' => 'Obrigado! Entraremos em contato em breve.',
            'yeshua_form_lead_redirect' => '',
            'yeshua_gtm_id' => '',
            'yeshua_ga4_id' => '',
            'yeshua_gads_id' => '',
            'yeshua_gads_label' => '',
            'yeshua_gtm_thank_you_urls' => '',
            'yeshua_gtm_exclude_admins' => '1',
            'yeshua_gtm_external_forms' => ".elementor-form\n.wpcf7-form\n.wpforms-form",
            'yeshua_external_forms_mappings' => [],
            'yeshua_external_forms_message' => '',
            'yeshua_external_forms_email_template' => "Novo lead (formulario externo):\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}\nFormulario: {_formulario}\n\n{campos_extras}",
        ];
        
        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Limpar rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Retorna uma opção do plugin
     */
    public static function get_option($key, $default = '') {
        return get_option('yeshua_' . $key, $default);
    }
    
    /**
     * Atualiza uma opção do plugin
     */
    public static function update_option($key, $value) {
        return update_option('yeshua_' . $key, $value);
    }
}

/**
 * Retorna a instância do plugin
 */
function yeshua_conversoes() {
    return Yeshua_Conversoes::get_instance();
}

// Inicializa o plugin
yeshua_conversoes();



