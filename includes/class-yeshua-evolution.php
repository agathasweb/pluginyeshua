<?php
/**
 * Classe para integração com Evolution API (WhatsApp)
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Evolution {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * URL da API
     */
    private $api_url;
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Nome da instância
     */
    private $instance_name;
    
    /**
     * Token da instância
     */
    private $instance_token;
    
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
        $this->api_url = rtrim(get_option('yeshua_evolution_url', ''), '/');
        $this->api_key = get_option('yeshua_evolution_api_key', '');
        $this->instance_name = get_option('yeshua_evolution_instance', '');
        $this->instance_token = get_option('yeshua_evolution_token', '');
    }
    
    /**
     * Verifica se está configurado
     */
    public function is_configured() {
        return !empty($this->api_url) && !empty($this->api_key) && !empty($this->instance_name);
    }
    
    /**
     * Faz uma requisição para a API
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        if (empty($this->api_url)) {
            return [
                'success' => false,
                'message' => __('URL da Evolution API não configurada', 'yeshua-conversoes'),
            ];
        }
        
        $url = $this->api_url . '/' . ltrim($endpoint, '/');
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'apikey' => $this->api_key,
        ];
        
        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => $headers,
        ];
        
        if ($data !== null && $method !== 'GET') {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
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
                'success' => $code >= 200 && $code < 300,
                'message' => $body,
                'http_code' => $code,
            ];
        }
        
        return array_merge($decoded, [
            'success' => $code >= 200 && $code < 300,
            'http_code' => $code,
        ]);
    }
    
    /**
     * Lista instâncias disponíveis
     */
    public function fetch_instances() {
        $response = $this->request('instance/fetchInstances');
        
        if (isset($response['success']) && $response['success']) {
            // Retorna a lista de instâncias
            if (is_array($response) && !isset($response['message'])) {
                $instances = [];
                foreach ($response as $key => $value) {
                    if (is_array($value) && isset($value['instance'])) {
                        $instances[] = $value['instance'];
                    } elseif (is_array($value) && isset($value['instanceName'])) {
                        $instances[] = [
                            'instanceName' => $value['instanceName'],
                            'status' => $value['status'] ?? 'unknown',
                        ];
                    }
                }
                return $instances;
            }
        }
        
        return [];
    }
    
    /**
     * Verifica conexão com a instância
     */
    public function check_connection() {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => __('Evolution API não configurada', 'yeshua-conversoes'),
            ];
        }
        
        $response = $this->request('instance/connectionState/' . $this->instance_name);
        
        return $response;
    }
    
    /**
     * Envia mensagem de texto
     */
    public function send_text($number, $message) {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => __('Evolution API não configurada', 'yeshua-conversoes'),
            ];
        }
        
        // Formata o número
        $number = $this->format_phone_number($number);
        
        $data = [
            'number' => $number,
            'text' => $message,
        ];
        
        $endpoint = 'message/sendText/' . $this->instance_name;
        
        return $this->request($endpoint, 'POST', $data);
    }
    
    /**
     * Formata número de telefone para padrão internacional
     */
    private function format_phone_number($number) {
        // Remove caracteres não numéricos
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Se começa com 0, remove
        if (substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }
        
        // Se não tem código do país (55), adiciona
        if (strlen($number) <= 11) {
            $number = '55' . $number;
        }
        
        return $number;
    }
    
    /**
     * Retorna saudação baseada no horário
     */
    public static function get_saudacao() {
        $hora = (int) date('H');
        
        if ($hora >= 6 && $hora < 12) {
            return 'Bom dia';
        } elseif ($hora >= 12 && $hora < 18) {
            return 'Boa tarde';
        } else {
            return 'Boa noite';
        }
    }
    
    /**
     * Processa placeholders na mensagem
     */
    public static function process_message($template, $data) {
        $placeholders = [
            '{nome}' => $data['nome'] ?? '',
            '{email}' => $data['email'] ?? '',
            '{whatsapp}' => $data['whatsapp'] ?? $data['telefone'] ?? '',
            '{telefone}' => $data['telefone'] ?? $data['whatsapp'] ?? '',
            '{mensagem}' => $data['mensagem'] ?? '',
            '{saudacao}' => self::get_saudacao(),
            '{data}' => date('d/m/Y'),
            '{hora}' => date('H:i'),
            '{site}' => get_bloginfo('name'),
            '{url}' => home_url(),
        ];
        
        // Adiciona campos extras
        if (isset($data['campos_extras']) && is_array($data['campos_extras'])) {
            $extras_text = '';
            foreach ($data['campos_extras'] as $campo => $valor) {
                $placeholders['{' . $campo . '}'] = $valor;
                $extras_text .= $campo . ': ' . $valor . "\n";
            }
            $placeholders['{campos_extras}'] = trim($extras_text);
        } else {
            $placeholders['{campos_extras}'] = '';
        }
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    /**
     * Gera URL do WhatsApp Web/App
     */
    public static function generate_whatsapp_url($number, $message = '') {
        $number = preg_replace('/[^0-9]/', '', $number);
        
        if (strlen($number) <= 11) {
            $number = '55' . $number;
        }
        
        $url = 'https://wa.me/' . $number;
        
        if (!empty($message)) {
            $url .= '?text=' . rawurlencode($message);
        }
        
        return $url;
    }
}



