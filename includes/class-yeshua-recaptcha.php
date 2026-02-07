<?php
/**
 * Classe para integração com Google ReCaptcha
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Recaptcha {
    
    /**
     * Instância única
     */
    private static $instance = null;
    
    /**
     * Site Key
     */
    private $site_key;
    
    /**
     * Secret Key
     */
    private $secret_key;
    
    /**
     * Versão do ReCaptcha
     */
    private $version;
    
    /**
     * Threshold para v3
     */
    private $threshold;
    
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
        $this->site_key = get_option('yeshua_recaptcha_site_key', '');
        $this->secret_key = get_option('yeshua_recaptcha_secret_key', '');
        $this->version = get_option('yeshua_recaptcha_version', 'v2_checkbox');
        $this->threshold = floatval(get_option('yeshua_recaptcha_threshold', '0.5'));
    }
    
    /**
     * Verifica se está configurado
     */
    public function is_configured() {
        return !empty($this->site_key) && !empty($this->secret_key);
    }
    
    /**
     * Retorna a site key
     */
    public function get_site_key() {
        return $this->site_key;
    }
    
    /**
     * Retorna a versão
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Retorna o threshold
     */
    public function get_threshold() {
        return $this->threshold;
    }
    
    /**
     * Retorna URL do script
     */
    public function get_script_url() {
        $url = 'https://www.google.com/recaptcha/api.js';
        
        if ($this->version === 'v3') {
            $url .= '?render=' . $this->site_key;
        } elseif ($this->version === 'v2_invisible') {
            $url .= '?onload=yeshuaRecaptchaOnload&render=explicit';
        }
        
        return $url;
    }
    
    /**
     * Renderiza o widget ReCaptcha
     */
    public function render($form_id = 'yeshua-form') {
        if (!$this->is_configured()) {
            return '';
        }
        
        $html = '';
        
        switch ($this->version) {
            case 'v2_checkbox':
                $html = sprintf(
                    '<div class="g-recaptcha" data-sitekey="%s" data-callback="yeshuaRecaptchaCallback" data-expired-callback="yeshuaRecaptchaExpired"></div>',
                    esc_attr($this->site_key)
                );
                break;
                
            case 'v2_invisible':
                $html = sprintf(
                    '<div id="recaptcha-%s" class="g-recaptcha" data-sitekey="%s" data-callback="yeshuaRecaptchaCallback" data-size="invisible"></div>',
                    esc_attr($form_id),
                    esc_attr($this->site_key)
                );
                break;
                
            case 'v3':
                $html = sprintf(
                    '<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-%s">',
                    esc_attr($form_id)
                );
                break;
        }
        
        return $html;
    }
    
    /**
     * Verifica o token do ReCaptcha
     */
    public function verify($token, $action = null) {
        if (!$this->is_configured()) {
            return [
                'success' => true, // Se não configurado, permite passar
                'message' => __('ReCaptcha não configurado', 'yeshua-conversoes'),
            ];
        }
        
        if (empty($token)) {
            return [
                'success' => false,
                'message' => __('Token ReCaptcha não fornecido', 'yeshua-conversoes'),
            ];
        }
        
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secret_key,
                'response' => $token,
                'remoteip' => Yeshua_Api::get_client_ip(),
            ],
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['success'])) {
            return [
                'success' => false,
                'message' => __('Resposta inválida do ReCaptcha', 'yeshua-conversoes'),
            ];
        }
        
        // Para v3, verifica o score
        if ($this->version === 'v3') {
            $score = isset($body['score']) ? floatval($body['score']) : 0;
            
            if ($score < $this->threshold) {
                return [
                    'success' => false,
                    'message' => __('Score ReCaptcha muito baixo', 'yeshua-conversoes'),
                    'score' => $score,
                ];
            }
            
            // Verifica a action se fornecida
            if ($action !== null && isset($body['action']) && $body['action'] !== $action) {
                return [
                    'success' => false,
                    'message' => __('Action ReCaptcha inválida', 'yeshua-conversoes'),
                ];
            }
        }
        
        return [
            'success' => $body['success'],
            'message' => $body['success'] ? __('ReCaptcha válido', 'yeshua-conversoes') : __('ReCaptcha inválido', 'yeshua-conversoes'),
            'score' => $body['score'] ?? null,
        ];
    }
    
    /**
     * Gera o JavaScript necessário
     */
    public function get_javascript() {
        if (!$this->is_configured()) {
            return '';
        }
        
        $js = '';
        
        switch ($this->version) {
            case 'v2_checkbox':
                $js = <<<JS
                    window.yeshuaRecaptchaCallback = function(token) {
                        document.querySelectorAll('.yeshua-submit-btn').forEach(function(btn) {
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        });
                        window.yeshuaRecaptchaToken = token;
                    };
                    window.yeshuaRecaptchaExpired = function() {
                        document.querySelectorAll('.yeshua-submit-btn').forEach(function(btn) {
                            btn.disabled = true;
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                        });
                        window.yeshuaRecaptchaToken = null;
                    };
JS;
                break;
                
            case 'v2_invisible':
                $js = <<<JS
                    window.yeshuaRecaptchaWidgets = {};
                    window.yeshuaRecaptchaOnload = function() {
                        document.querySelectorAll('.g-recaptcha').forEach(function(el) {
                            var widgetId = grecaptcha.render(el.id, {
                                'sitekey': '{$this->site_key}',
                                'callback': 'yeshuaRecaptchaCallback',
                                'size': 'invisible'
                            });
                            window.yeshuaRecaptchaWidgets[el.id] = widgetId;
                        });
                    };
                    window.yeshuaRecaptchaCallback = function(token) {
                        window.yeshuaRecaptchaToken = token;
                        if (window.yeshuaPendingForm) {
                            window.yeshuaPendingForm.submit();
                        }
                    };
                    window.yeshuaExecuteRecaptcha = function(formId) {
                        var widgetId = window.yeshuaRecaptchaWidgets['recaptcha-' + formId];
                        if (widgetId !== undefined) {
                            grecaptcha.execute(widgetId);
                        }
                    };
JS;
                break;
                
            case 'v3':
                $js = <<<JS
                    window.yeshuaExecuteRecaptcha = function(action, formId) {
                        return new Promise(function(resolve) {
                            grecaptcha.ready(function() {
                                grecaptcha.execute('{$this->site_key}', {action: action}).then(function(token) {
                                    var input = document.getElementById('g-recaptcha-response-' + formId);
                                    if (input) {
                                        input.value = token;
                                    }
                                    window.yeshuaRecaptchaToken = token;
                                    resolve(token);
                                });
                            });
                        });
                    };
                    // Auto-enable submit buttons for v3
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('.yeshua-submit-btn').forEach(function(btn) {
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        });
                    });
JS;
                break;
        }
        
        return $js;
    }
}





