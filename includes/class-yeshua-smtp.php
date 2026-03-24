<?php
/**
 * Classe para envio de e-mails via SMTP
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Smtp {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * Configurações SMTP
     */
    private $config;
    
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
        $this->config = [
            'host' => get_option('yeshua_smtp_host', ''),
            'port' => get_option('yeshua_smtp_port', '587'),
            'encryption' => get_option('yeshua_smtp_encryption', 'tls'),
            'user' => get_option('yeshua_smtp_user', ''),
            'password' => get_option('yeshua_smtp_password', ''),
            'from_email' => get_option('yeshua_smtp_from_email', ''),
            'from_name' => get_option('yeshua_smtp_from_name', ''),
            'to_email' => get_option('yeshua_smtp_to_email', ''),
        ];
    }
    
    /**
     * Verifica se está configurado
     */
    public function is_configured() {
        return !empty($this->config['host']) 
            && !empty($this->config['user']) 
            && !empty($this->config['password'])
            && !empty($this->config['from_email']);
    }
    
    /**
     * Envia e-mail
     */
    public function send($to, $subject, $message, $headers = []) {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => __('SMTP não configurado', 'yeshua-conversoes'),
            ];
        }
        
        // Configura PHPMailer via hook
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        
        // Headers padrão
        $default_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        // Envia o e-mail
        $result = wp_mail($to, $subject, nl2br($message), $headers);
        
        // Remove o hook após envio
        remove_action('phpmailer_init', [$this, 'configure_phpmailer']);
        
        return [
            'success' => $result,
            'message' => $result ? __('E-mail enviado com sucesso', 'yeshua-conversoes') : __('Falha ao enviar e-mail', 'yeshua-conversoes'),
        ];
    }
    
    /**
     * Configura PHPMailer
     */
    public function configure_phpmailer($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->config['host'];
        $phpmailer->Port = intval($this->config['port']);
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $this->config['user'];
        $phpmailer->Password = $this->config['password'];
        
        // Criptografia
        switch ($this->config['encryption']) {
            case 'ssl':
                $phpmailer->SMTPSecure = 'ssl';
                break;
            case 'tls':
                $phpmailer->SMTPSecure = 'tls';
                break;
            default:
                $phpmailer->SMTPSecure = '';
                $phpmailer->SMTPAutoTLS = false;
                break;
        }
        
        // Remetente
        $phpmailer->From = $this->config['from_email'];
        $phpmailer->FromName = $this->config['from_name'];
    }
    
    /**
     * Converte string de e-mails separados por vírgula em array
     */
    private function parse_recipients($to) {
        if (is_array($to)) {
            return $to;
        }
        return array_filter(array_map('trim', explode(',', $to)));
    }

    /**
     * Envia e-mail de teste
     */
    public function send_test($to = null) {
        $to = $to ?: $this->config['to_email'];

        if (empty($to)) {
            return [
                'success' => false,
                'message' => __('E-mail de destino não configurado', 'yeshua-conversoes'),
            ];
        }
        
        $subject = __('Teste de E-mail - YESHUA Conversões', 'yeshua-conversoes');
        $message = sprintf(
            __("Este é um e-mail de teste enviado pelo plugin YESHUA Conversões.\n\nData/Hora: %s\nSite: %s\n\nSe você recebeu este e-mail, a configuração SMTP está funcionando corretamente!", 'yeshua-conversoes'),
            date('d/m/Y H:i:s'),
            get_bloginfo('name')
        );
        
        return $this->send($this->parse_recipients($to), $subject, $message);
    }

    /**
     * Envia notificação de lead
     */
    public function send_lead_notification($data, $template = null) {
        $to = $this->parse_recipients($this->config['to_email']);
        
        if (empty($to)) {
            return [
                'success' => false,
                'message' => __('E-mail de destino não configurado', 'yeshua-conversoes'),
            ];
        }
        
        // Template padrão
        if (empty($template)) {
            $template = "Novo lead recebido!\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}\n\n{campos_extras}\n\nData: {data} às {hora}\nSite: {site}";
        }
        
        // Processa placeholders
        $message = Yeshua_Evolution::process_message($template, $data);
        
        $subject = sprintf(
            __('Novo Lead - %s', 'yeshua-conversoes'),
            $data['nome'] ?? __('Visitante', 'yeshua-conversoes')
        );
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Retorna configuração
     */
    public function get_config() {
        return $this->config;
    }
}





