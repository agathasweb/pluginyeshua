<?php
/**
 * Classe para rastreamento automático de visitas
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Tracking {
    
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
        // Verifica se tracking está habilitado
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        add_action('wp_footer', [$this, 'inject_tracking_script'], 100);
        add_action('template_redirect', [$this, 'check_page_conversion']);
    }
    
    /**
     * Verifica se o tracking está habilitado
     */
    private function is_tracking_enabled() {
        $enabled = get_option('yeshua_tracking_enabled', '1');
        $api_key = get_option('yeshua_api_key', '');
        $website_id = get_option('yeshua_website_id', '');
        
        return $enabled && !empty($api_key) && !empty($website_id);
    }
    
    /**
     * Verifica se deve excluir o visitante
     */
    private function should_exclude_visitor() {
        // Excluir admins
        $exclude_admins = get_option('yeshua_exclude_admins', '1');
        if ($exclude_admins && is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        }
        
        // Excluir bots conhecidos
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $bots = ['bot', 'crawler', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandex', 'baidu'];
        
        foreach ($bots as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Injeta o script de rastreamento
     */
    public function inject_tracking_script() {
        if ($this->should_exclude_visitor()) {
            return;
        }
        
        $api = Yeshua_Api::get_instance();
        
        // Dados para o script
        $tracking_data = [
            'ip' => Yeshua_Api::get_client_ip(),
            'session_id' => Yeshua_Api::get_session_id(),
            'nonce' => wp_create_nonce('yeshua_tracking'),
        ];
        
        ?>
        <script type="text/javascript" id="yeshua-tracking-data">
            window.yeshuaTracking = <?php echo json_encode($tracking_data); ?>;
            window.yeshuaRestUrl = '<?php echo esc_url(rest_url('yeshua/v1/')); ?>';
        </script>
        <script type="text/javascript" src="<?php echo esc_url(YESHUA_PLUGIN_URL . 'assets/js/tracking.js?v=' . YESHUA_VERSION); ?>" defer></script>
        <?php
    }
    
    /**
     * Verifica se a página atual deve disparar conversão
     */
    public function check_page_conversion() {
        if ($this->should_exclude_visitor()) {
            return;
        }
        
        $conversions = get_option('yeshua_conversions', []);
        
        if (empty($conversions)) {
            return;
        }
        
        $current_page_id = get_queried_object_id();
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($conversions as $conversion) {
            $should_convert = false;
            
            // Verifica por page_id
            if (!empty($conversion['page_id']) && $conversion['page_id'] == $current_page_id) {
                $should_convert = true;
            }
            
            // Verifica por padrão de URL
            if (!empty($conversion['url_pattern'])) {
                $pattern = str_replace('*', '.*', $conversion['url_pattern']);
                if (preg_match('#' . $pattern . '#i', $current_url)) {
                    $should_convert = true;
                }
            }
            
            if ($should_convert) {
                $this->trigger_conversion($conversion);
                break; // Dispara apenas uma conversão por página
            }
        }
    }
    
    /**
     * Dispara uma conversão
     */
    private function trigger_conversion($conversion) {
        // Verifica se já disparou conversão nesta sessão para esta página
        $session_id = Yeshua_Api::get_session_id();
        $conversion_key = 'yeshua_conv_' . md5($session_id . $conversion['conversion_id']);
        
        if (isset($_COOKIE[$conversion_key])) {
            return;
        }
        
        // Define cookie para evitar duplicidade
        setcookie($conversion_key, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        
        // Prepara dados da conversão
        $api = Yeshua_Api::get_instance();
        $utm = Yeshua_Api::get_utm_params();
        
        $data = [
            'tipo_conversao' => $conversion['conversion_type'] ?? 'lead',
            'ip' => Yeshua_Api::get_client_ip(),
            'dispositivo' => Yeshua_Api::detect_device(),
            'navegador' => Yeshua_Api::detect_browser(),
            'sistema_operacional' => Yeshua_Api::detect_os(),
            'url_conversao' => home_url($_SERVER['REQUEST_URI'] ?? ''),
            'url_origem' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        ];
        
        // Adiciona UTMs se existirem
        $data = array_merge($data, $utm);
        
        // Define rede de origem baseada no UTM
        if (!empty($utm['utm_source'])) {
            $source_map = [
                'google' => 'google',
                'facebook' => 'facebook',
                'instagram' => 'instagram',
                'linkedin' => 'linkedin',
                'twitter' => 'twitter',
                'tiktok' => 'tiktok',
                'youtube' => 'youtube',
            ];
            
            $source_lower = strtolower($utm['utm_source']);
            $data['rede_origem'] = $source_map[$source_lower] ?? 'outro';
        } else {
            $data['rede_origem'] = 'direto';
        }
        
        // Registra a conversão via hook para processar após o carregamento
        add_action('shutdown', function() use ($api, $data) {
            $api->register_conversion($data);
        });
    }
    
    /**
     * Registra visita via AJAX/REST
     */
    public static function register_visit($data) {
        $api = Yeshua_Api::get_instance();
        
        if (!$api->is_configured()) {
            return ['success' => false, 'message' => 'API não configurada'];
        }
        
        $traffic_data = [
            'ip' => $data['ip'] ?? Yeshua_Api::get_client_ip(),
            'dispositivo' => $data['dispositivo'] ?? Yeshua_Api::detect_device(),
            'navegador' => $data['navegador'] ?? Yeshua_Api::detect_browser(),
            'sistema_operacional' => $data['sistema_operacional'] ?? Yeshua_Api::detect_os(),
            'url_completa' => $data['url_completa'] ?? '',
            'url_path' => $data['url_path'] ?? '',
            'url_query' => $data['url_query'] ?? '',
            'referer' => $data['referer'] ?? '',
            'user_agent' => $data['user_agent'] ?? '',
            'sessao_id' => $data['sessao_id'] ?? Yeshua_Api::get_session_id(),
        ];
        
        // Adiciona UTMs
        $utm_fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utm_fields as $field) {
            if (!empty($data[$field])) {
                $traffic_data[$field] = $data[$field];
            }
        }
        
        return $api->register_traffic($traffic_data);
    }
}





