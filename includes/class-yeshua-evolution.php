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
            'sslverify' => false,
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

        $is_success = $code >= 200 && $code < 300;

        $is_assoc = is_array($decoded) && !empty($decoded) && array_keys($decoded) !== range(0, count($decoded) - 1);
        if ($is_assoc) {
            $decoded['success'] = $is_success;
            $decoded['http_code'] = $code;
            return $decoded;
        }

        return [
            'success' => $is_success,
            'http_code' => $code,
            'data' => $decoded,
        ];
    }
    
    /**
     * Lista instâncias disponíveis
     */
    public function fetch_instances() {
        $response = $this->request('instance/fetchInstances');

        if (!isset($response['success']) || !$response['success']) {
            return [
                'error' => true,
                'message' => $response['message'] ?? __('Erro ao conectar com a Evolution API', 'yeshua-conversoes'),
                'http_code' => $response['http_code'] ?? 0,
            ];
        }

        $items = $response['data'] ?? $response;
        if (!is_array($items)) {
            return [
                'error' => true,
                'message' => __('Resposta inesperada da Evolution API', 'yeshua-conversoes'),
            ];
        }

        $instances = [];
        foreach ($items as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $name = $value['instance']['instanceName']
                ?? $value['instanceName']
                ?? $value['name']
                ?? null;
            if ($name) {
                $instances[] = [
                    'instanceName' => $name,
                    'status' => $value['instance']['status']
                        ?? $value['connectionStatus']
                        ?? $value['status']
                        ?? 'unknown',
                ];
            }
        }

        return [
            'instances' => $instances,
        ];
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
        
        $response = $this->request($endpoint, 'POST', $data);
        
        if (isset($response['success']) && $response['success']) {
            $remoteJid = $response['key']['remoteJid'] ?? null;
            $messageId = $response['key']['id'] ?? null;

            // Tenta marcar como não lida
            if ($remoteJid && $messageId) {
                $this->mark_chat_unread($remoteJid, $messageId);
            }

            // Adiciona etiqueta "Leads" ao chat automaticamente
            $this->ensure_leads_label($number);
        }

        return $response;
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
    /**
     * Marca o chat como não lido via Evolution API (payload Baileys)
     */
    public function mark_chat_unread($remoteJid, $messageKeyId) {
        if (!$this->is_configured()) {
            return false;
        }

        $endpoint = 'chat/markChatUnread/' . $this->instance_name;

        $data = [
            'lastMessage' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe' => true,
                    'id' => $messageKeyId,
                ],
            ],
        ];

        $response = $this->request($endpoint, 'POST', $data);

        if (!isset($response['success']) || !$response['success']) {
            error_log('[YESHUA Evolution] markChatUnread falhou: ' . wp_json_encode($response));
        }

        return isset($response['success']) && $response['success'];
    }

    /**
     * Adiciona uma etiqueta/label ao chat via Evolution API
     */
    public function add_label_to_chat($number, $labelId) {
        if (!$this->is_configured()) {
            return false;
        }

        $endpoint = 'label/handleLabel/' . $this->instance_name;

        $data = [
            'number' => $number,
            'labelId' => $labelId,
            'action' => 'add',
        ];

        $response = $this->request($endpoint, 'POST', $data);

        if (!isset($response['success']) || !$response['success']) {
            error_log('[YESHUA Evolution] addLabel falhou: ' . wp_json_encode($response));
        }

        return isset($response['success']) && $response['success'];
    }

    /**
     * Busca labels disponíveis na instância
     */
    public function fetch_labels() {
        if (!$this->is_configured()) {
            return [];
        }

        $response = $this->request('label/findLabels/' . $this->instance_name);

        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        if (is_array($response) && isset($response[0]['id'])) {
            return $response;
        }

        return [];
    }

    /**
     * Garante que a label "Leads" existe e adiciona ao chat.
     * Busca na API apenas uma vez e cacheia o ID no wp_options.
     */
    private function ensure_leads_label($number) {
        $label_id = get_option('yeshua_evolution_leads_label_id', '');

        // Se já temos o ID cacheado, usa direto
        if (!empty($label_id)) {
            $this->add_label_to_chat($number, $label_id);
            return;
        }

        // Busca labels na Evolution API
        $labels = $this->fetch_labels();

        // Procura label "Leads" (case-insensitive)
        foreach ($labels as $label) {
            if (strcasecmp($label['name'] ?? '', 'Leads') === 0) {
                $label_id = $label['id'];
                update_option('yeshua_evolution_leads_label_id', $label_id);
                $this->add_label_to_chat($number, $label_id);
                return;
            }
        }

        // Label "Leads" não existe — loga aviso (precisa criar manualmente no WhatsApp Business)
        error_log('[YESHUA Evolution] Label "Leads" não encontrada no WhatsApp. Crie a etiqueta "Leads" no WhatsApp Business para ativar esta funcionalidade.');
    }
}



