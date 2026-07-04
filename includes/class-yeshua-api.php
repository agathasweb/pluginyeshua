<?php
/**
 * Classe para comunicação com a API YESHUA
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Api {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Website ID
     */
    private $website_id;
    
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
        $this->api_key = get_option('yeshua_api_key', '');
        $this->website_id = get_option('yeshua_website_id', '');
    }
    
    /**
     * Faz uma requisição para a API
     */
    public function request($endpoint, $method = 'GET', $data = [], $api_key = null) {
        $url = YESHUA_API_URL . '/' . ltrim($endpoint, '/');
        $key = $api_key ?: $this->api_key;
        
        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'X-API-Key' => $key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
        
        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }
        
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('YESHUA API WP_Error [' . $method . ' ' . $url . ']: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => __('Resposta inválida da API', 'yeshua-conversoes'),
                'raw' => $body,
            ];
        }
        
        $decoded['http_code'] = $code;
        
        return $decoded;
    }
    
    /**
     * Valida a API Key
     */
    public function validate_api_key($api_key = null) {
        $response = $this->request('websites', 'GET', [], $api_key);
        $is_valid = isset($response['success']) && $response['success'] === true;
        if (!$is_valid) {
            error_log('YESHUA validate_api_key FAILED: ' . wp_json_encode([
                'response_success' => $response['success'] ?? 'NOT SET',
                'response_message' => $response['message'] ?? 'NOT SET',
                'http_code' => $response['http_code'] ?? 'NOT SET',
                'raw' => $response['raw'] ?? null,
                'api_url' => YESHUA_API_URL,
                'key_prefix' => $api_key ? substr($api_key, 0, 10) . '...' : 'NULL',
            ]));
        }
        return $is_valid;
    }
    
    /**
     * Lista os websites cadastrados
     */
    public function get_websites($api_key = null) {
        $response = $this->request('websites', 'GET', ['status' => 'ativo'], $api_key);
        
        if (isset($response['success']) && $response['success'] && isset($response['data'])) {
            return $response['data'];
        }
        
        return [];
    }
    
    /**
     * Obtém detalhes de um website
     */
    public function get_website($website_id) {
        $response = $this->request('websites/' . $website_id);
        
        if (isset($response['success']) && $response['success'] && isset($response['data'])) {
            return $response['data'];
        }
        
        return null;
    }
    
    /**
     * Registra lead
     */
    public function register_lead($data) {
        $data['website_id'] = $this->website_id;
        return $this->request('leads', 'POST', $data);
    }
    
    /**
     * Registra conversão
     */
    public function register_conversion($data) {
        $data['website_id'] = $this->website_id;
        return $this->request('conversoes', 'POST', $data);
    }
    
    /**
     * Atualiza status de lead
     */
    public function update_lead_status($lead_id, $status) {
        return $this->request('leads/' . $lead_id . '/status', 'PATCH', [
            'status' => $status,
        ]);
    }
    
    /**
     * Verifica se está configurado
     */
    public function is_configured() {
        return !empty($this->api_key) && !empty($this->website_id);
    }
    
    /**
     * Retorna o website_id
     */
    public function get_website_id() {
        return $this->website_id;
    }
    
    /**
     * Retorna a API Key
     */
    public function get_api_key() {
        return $this->api_key;
    }
    
    /**
     * Detecta o dispositivo
     */
    public static function detect_device() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $user_agent)) {
            return 'tablet';
        }
        
        if (preg_match('/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i', $user_agent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Detecta o navegador
     */
    public static function detect_browser() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $browsers = [
            'Edge' => '/Edge|Edg/i',
            'Opera' => '/Opera|OPR/i',
            'Chrome' => '/Chrome/i',
            'Safari' => '/Safari/i',
            'Firefox' => '/Firefox/i',
            'IE' => '/MSIE|Trident/i',
        ];
        
        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $browser;
            }
        }
        
        return 'Outro';
    }
    
    /**
     * Detecta o sistema operacional
     */
    public static function detect_os() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $os_array = [
            'Windows 11' => '/Windows NT 10.0.*Windows/i',
            'Windows 10' => '/Windows NT 10.0/i',
            'Windows 8.1' => '/Windows NT 6.3/i',
            'Windows 8' => '/Windows NT 6.2/i',
            'Windows 7' => '/Windows NT 6.1/i',
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS X/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
            'Linux' => '/Linux/i',
        ];
        
        foreach ($os_array as $os => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $os;
            }
        }
        
        return 'Outro';
    }
    
    /**
     * Obtém o IP real do visitante
     */
    public static function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Pega o primeiro IP se houver múltiplos
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Valida o IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Gera ID de sessão único
     */
    public static function get_session_id() {
        $cookie_name = 'yeshua_session';

        if (isset($_COOKIE[$cookie_name])) {
            return sanitize_text_field($_COOKIE[$cookie_name]);
        }

        $session_id = wp_generate_uuid4();

        // Define cookie por 30 minutos (somente se headers ainda não foram enviados)
        if (!headers_sent()) {
            setcookie($cookie_name, $session_id, time() + 1800, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }

        return $session_id;
    }
    
    /**
     * Obtém parâmetros UTM da URL
     */
    public static function get_utm_params() {
        $utm_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $result = [];
        
        foreach ($utm_params as $param) {
            if (isset($_GET[$param])) {
                $result[$param] = sanitize_text_field($_GET[$param]);
            }
        }
        
        return $result;
    }
}





