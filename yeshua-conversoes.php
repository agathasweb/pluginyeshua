<?php
/**
 * Plugin Name: YESHUA Conversões
 * Plugin URI: https://agathasweb.com
 * Description: Integração completa com API TLC do YESHUA para rastreamento de tráfego, leads e conversões. Inclui formulários modais, integração Evolution API (WhatsApp) e envio de e-mails.
 * Version: 1.0.0
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
define('YESHUA_VERSION', '1.0.0');
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
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-smtp.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-recaptcha.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-forms.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-rest-api.php';
        require_once YESHUA_PLUGIN_DIR . 'includes/class-yeshua-updater.php';
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
            
            // Auto Updater
            $github_repo = defined('YESHUA_GITHUB_REPO') ? YESHUA_GITHUB_REPO : 'agathasweb/pluginyeshua'; // Defina seu repo aqui
            $github_token = defined('YESHUA_GITHUB_TOKEN') ? YESHUA_GITHUB_TOKEN : ''; // Defina seu token aqui

            if (!empty($github_repo)) {
                $parts = explode('/', $github_repo);
                if (count($parts) === 2) {
                    new Yeshua_Updater(__FILE__, $parts[0], $parts[1], $github_token);
                }
            }
        }
        
        // Frontend
        if (!is_admin()) {
            Yeshua_Tracking::get_instance();
        }
        
        // Forms - Carrega no frontend e também no admin para testes
        Yeshua_Forms::get_instance();
        
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    public function init() {
        // Inicializações adicionais se necessário
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



