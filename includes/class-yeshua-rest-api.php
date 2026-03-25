<?php
/**
 * Classe para endpoints REST API
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Rest_Api {
    
    /**
     * Namespace da API
     */
    const NAMESPACE = 'yeshua/v1';
    
    /**
     * Registra as rotas
     */
    public static function register_routes() {
        // Validar API Key
        register_rest_route(self::NAMESPACE, '/validate-api', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'validate_api'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Listar websites
        register_rest_route(self::NAMESPACE, '/websites', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_websites'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Registrar visita (tracking)
        register_rest_route(self::NAMESPACE, '/track', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'track_visit'],
            'permission_callback' => '__return_true',
        ]);
        
        // Submeter formulário
        register_rest_route(self::NAMESPACE, '/submit-form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'submit_form'],
            'permission_callback' => '__return_true',
        ]);
        
        // Listar instâncias Evolution
        register_rest_route(self::NAMESPACE, '/evolution-instances', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_evolution_instances'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Testar conexão Evolution
        register_rest_route(self::NAMESPACE, '/test-evolution', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'test_evolution'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Testar SMTP
        register_rest_route(self::NAMESPACE, '/test-smtp', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'test_smtp'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Testar formulário (apenas no admin)
        register_rest_route(self::NAMESPACE, '/test-form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'test_form'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Preview de mensagem processada
        register_rest_route(self::NAMESPACE, '/preview-message', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'preview_message'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
        
        // Testar conversão
        register_rest_route(self::NAMESPACE, '/test-conversion', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'test_conversion'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);

        // Buscar labels da Evolution API
        register_rest_route(self::NAMESPACE, '/evolution-labels', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_evolution_labels'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);

        // Exportar container GTM
        register_rest_route(self::NAMESPACE, '/export-gtm-container', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'export_gtm_container'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);

        // Formulários Externos - listar formulários de um plugin
        register_rest_route(self::NAMESPACE, '/external-forms/forms', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_external_forms'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);

        // Formulários Externos - listar campos de um formulário
        register_rest_route(self::NAMESPACE, '/external-forms/fields', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_external_form_fields'],
            'permission_callback' => [__CLASS__, 'admin_permission_check'],
        ]);
    }
    
    /**
     * Verifica permissão de admin
     */
    public static function admin_permission_check() {
        return current_user_can('manage_options');
    }
    
    /**
     * Valida API Key
     */
    public static function validate_api($request) {
        $api_key = $request->get_param('api_key');

        if (empty($api_key)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('API Key não fornecida', 'yeshua-conversoes'),
            ], 400);
        }

        $api = Yeshua_Api::get_instance();
        $response = $api->request('websites', 'GET', [], $api_key);
        $is_valid = isset($response['success']) && $response['success'] === true;

        if ($is_valid) {
            $websites = $api->get_websites($api_key);

            return new WP_REST_Response([
                'success' => true,
                'message' => __('API Key válida', 'yeshua-conversoes'),
                'websites' => $websites,
            ]);
        }

        // Retorna detalhes do erro para diagnóstico
        $error_detail = $response['message'] ?? 'Sem detalhes';
        $http_code = $response['http_code'] ?? 'N/A';

        return new WP_REST_Response([
            'success' => false,
            'message' => sprintf('Falha na validação: %s (HTTP %s | URL: %s)', $error_detail, $http_code, YESHUA_API_URL),
        ]);
    }
    
    /**
     * Lista websites
     */
    public static function get_websites($request) {
        $api_key = $request->get_param('api_key') ?: get_option('yeshua_api_key', '');
        
        if (empty($api_key)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('API Key não configurada', 'yeshua-conversoes'),
            ], 400);
        }
        
        $api = Yeshua_Api::get_instance();
        $websites = $api->get_websites($api_key);
        
        return new WP_REST_Response([
            'success' => true,
            'websites' => $websites,
        ]);
    }
    
    /**
     * Registra visita
     */
    public static function track_visit($request) {
        $data = $request->get_json_params();
        
        // Validação básica
        if (empty($data['url_completa'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('URL não fornecida', 'yeshua-conversoes'),
            ], 400);
        }
        
        $result = Yeshua_Tracking::register_visit($data);
        
        return new WP_REST_Response($result);
    }
    
    /**
     * Submete formulário
     */
    public static function submit_form($request) {
        $data = $request->get_json_params();
        
        // Validação básica
        if (empty($data['nome'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Nome é obrigatório', 'yeshua-conversoes'),
            ], 400);
        }
        
        if (empty($data['email']) && empty($data['whatsapp'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('E-mail ou WhatsApp é obrigatório', 'yeshua-conversoes'),
            ], 400);
        }
        
        $form_type = $data['form_type'] ?? 'lead';
        $result = Yeshua_Forms::process_submission($data, $form_type);
        
        $status = $result['success'] ? 200 : 400;
        
        return new WP_REST_Response($result, $status);
    }
    
    /**
     * Lista instâncias Evolution
     */
    public static function get_evolution_instances($request) {
        $url = $request->get_param('url') ?: get_option('yeshua_evolution_url', '');
        $api_key = $request->get_param('api_key') ?: get_option('yeshua_evolution_api_key', '');
        
        if (empty($url) || empty($api_key)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('URL e API Key são obrigatórios', 'yeshua-conversoes'),
            ], 400);
        }
        
        // Temporariamente atualiza as options para usar a API
        $old_url = get_option('yeshua_evolution_url');
        $old_key = get_option('yeshua_evolution_api_key');
        
        update_option('yeshua_evolution_url', $url);
        update_option('yeshua_evolution_api_key', $api_key);
        
        // Força nova instância
        $evolution = new ReflectionClass('Yeshua_Evolution');
        $instance_prop = $evolution->getProperty('instance');
        $instance_prop->setAccessible(true);
        $instance_prop->setValue(null, null);
        
        $evolution_api = Yeshua_Evolution::get_instance();
        $result = $evolution_api->fetch_instances();

        // Restaura options
        update_option('yeshua_evolution_url', $old_url);
        update_option('yeshua_evolution_api_key', $old_key);

        // Reset instance
        $instance_prop->setValue(null, null);

        if (isset($result['error']) && $result['error']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'] ?? __('Erro ao buscar instâncias', 'yeshua-conversoes'),
                'instances' => [],
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'instances' => $result['instances'] ?? [],
        ]);
    }
    
    /**
     * Busca labels da Evolution API
     */
    public static function get_evolution_labels($request) {
        $evolution = Yeshua_Evolution::get_instance();

        if (!$evolution->is_configured()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Evolution API não configurada', 'yeshua-conversoes'),
            ], 400);
        }

        $labels = $evolution->fetch_labels();

        // Cachear por 5 minutos
        set_transient('yeshua_evolution_labels', $labels, 300);

        return new WP_REST_Response([
            'success' => true,
            'labels' => $labels,
        ]);
    }

    /**
     * Testa conexão Evolution
     */
    public static function test_evolution($request) {
        $evolution = Yeshua_Evolution::get_instance();
        
        if (!$evolution->is_configured()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Evolution API não configurada', 'yeshua-conversoes'),
            ], 400);
        }
        
        $result = $evolution->check_connection();
        
        return new WP_REST_Response($result);
    }
    
    /**
     * Testa SMTP
     */
    public static function test_smtp($request) {
        $email = $request->get_param('email');
        
        $smtp = Yeshua_Smtp::get_instance();
        
        if (!$smtp->is_configured()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('SMTP não configurado', 'yeshua-conversoes'),
            ], 400);
        }
        
        $result = $smtp->send_test($email);
        
        return new WP_REST_Response($result);
    }
    
    /**
     * Testa formulário completo (simula submissão real)
     */
    public static function test_form($request) {
        $data = $request->get_json_params();
        
        $form_type = $data['form_type'] ?? 'whatsapp';
        $test_mode = $data['test_mode'] ?? 'full'; // full, evolution_only, api_only, email_only
        
        // Dados de teste
        $test_data = [
            'nome' => sanitize_text_field($data['nome'] ?? 'Teste Admin'),
            'email' => sanitize_email($data['email'] ?? get_option('admin_email')),
            'whatsapp' => sanitize_text_field($data['whatsapp'] ?? ''),
            'form_type' => $form_type,
            'url_origem' => admin_url(),
        ];
        
        if (empty($test_data['whatsapp'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('WhatsApp é obrigatório para o teste', 'yeshua-conversoes'),
            ], 400);
        }
        
        $results = [
            'success' => true,
            'tests' => [],
        ];
        
        // Prepara dados para mensagens
        $message_data = [
            'nome' => $test_data['nome'],
            'email' => $test_data['email'],
            'whatsapp' => $test_data['whatsapp'],
            'telefone' => $test_data['whatsapp'],
            'campos_extras' => [],
        ];
        
        // Teste Evolution API
        if ($test_mode === 'full' || $test_mode === 'evolution_only') {
            $evolution = Yeshua_Evolution::get_instance();
            
            if ($evolution->is_configured()) {
                $message_template = $form_type === 'whatsapp' 
                    ? get_option('yeshua_form_whatsapp_message', '')
                    : get_option('yeshua_form_lead_message', '');
                
                if (!empty($message_template)) {
                    $message = Yeshua_Evolution::process_message($message_template, $message_data);
                    $evo_result = $evolution->send_text($test_data['whatsapp'], $message);
                    
                    $results['tests']['evolution'] = [
                        'name' => 'Evolution API',
                        'success' => $evo_result['success'] ?? false,
                        'message' => $evo_result['message'] ?? '',
                        'sent_message' => $message,
                    ];
                } else {
                    $results['tests']['evolution'] = [
                        'name' => 'Evolution API',
                        'success' => false,
                        'message' => __('Nenhum template de mensagem configurado', 'yeshua-conversoes'),
                    ];
                }
            } else {
                $results['tests']['evolution'] = [
                    'name' => 'Evolution API',
                    'success' => false,
                    'message' => __('Evolution API não configurada', 'yeshua-conversoes'),
                ];
            }
        }
        
        // Teste YESHUA API
        if ($test_mode === 'full' || $test_mode === 'api_only') {
            $api = Yeshua_Api::get_instance();
            
            if ($api->is_configured()) {
                $lead_data = [
                    'nome' => $test_data['nome'],
                    'email' => $test_data['email'],
                    'telefone' => $test_data['whatsapp'],
                    'origem' => $form_type === 'whatsapp' ? 'whatsapp' : 'formulario',
                    'ip' => Yeshua_Api::get_client_ip(),
                    'dispositivo' => Yeshua_Api::detect_device(),
                    'url_origem' => admin_url(),
                ];
                
                $api_result = $api->register_lead($lead_data);
                
                // Prepara mensagem detalhada
                $message = '';
                if (isset($api_result['success']) && $api_result['success']) {
                    $message = __('Lead registrado com sucesso!', 'yeshua-conversoes');
                    if (isset($api_result['data']['id'])) {
                        $message .= ' ID: ' . $api_result['data']['id'];
                    }
                } else {
                    $message = $api_result['message'] ?? __('Erro desconhecido', 'yeshua-conversoes');
                    
                    // Se houver erros de validação, mostra detalhes
                    if (isset($api_result['errors']) && is_array($api_result['errors'])) {
                        $message .= ' - ' . implode(', ', array_map(function($field, $errors) {
                            return $field . ': ' . (is_array($errors) ? implode(', ', $errors) : $errors);
                        }, array_keys($api_result['errors']), array_values($api_result['errors'])));
                    }
                    
                    // Adiciona informação sobre website_id para debug
                    $message .= sprintf(' (Website ID: %s)', $api->get_website_id() ?: 'não configurado');
                }
                
                $results['tests']['yeshua_api'] = [
                    'name' => 'YESHUA API',
                    'success' => $api_result['success'] ?? false,
                    'message' => $message,
                    'debug' => [
                        'website_id' => $api->get_website_id(),
                        'lead_data' => $lead_data,
                        'response' => $api_result,
                    ],
                ];
            } else {
                $api_key = get_option('yeshua_api_key', '');
                $website_id = get_option('yeshua_website_id', '');
                
                $missing = [];
                if (empty($api_key)) $missing[] = 'API Key';
                if (empty($website_id)) $missing[] = 'Website ID';
                
                $results['tests']['yeshua_api'] = [
                    'name' => 'YESHUA API',
                    'success' => false,
                    'message' => sprintf(
                        __('YESHUA API não configurada. Faltando: %s. Configure em YESHUA TLC > Configurações.', 'yeshua-conversoes'),
                        implode(', ', $missing)
                    ),
                ];
            }
        }
        
        // Teste E-mail
        if ($test_mode === 'full' || $test_mode === 'email_only') {
            $smtp = Yeshua_Smtp::get_instance();
            
            if ($smtp->is_configured()) {
                $email_template = $form_type === 'whatsapp'
                    ? get_option('yeshua_form_whatsapp_email_template', '')
                    : get_option('yeshua_form_lead_email_template', '');
                
                $email_result = $smtp->send_lead_notification($message_data, $email_template);
                
                $results['tests']['email'] = [
                    'name' => 'E-mail (SMTP)',
                    'success' => $email_result['success'] ?? false,
                    'message' => $email_result['message'] ?? '',
                ];
            } else {
                $results['tests']['email'] = [
                    'name' => 'E-mail (SMTP)',
                    'success' => false,
                    'message' => __('SMTP não configurado', 'yeshua-conversoes'),
                ];
            }
        }
        
        // Verifica se pelo menos um teste foi bem-sucedido
        $any_success = false;
        foreach ($results['tests'] as $test) {
            if ($test['success']) {
                $any_success = true;
                break;
            }
        }
        
        $results['success'] = $any_success;
        $results['message'] = $any_success 
            ? __('Testes concluídos', 'yeshua-conversoes')
            : __('Todos os testes falharam', 'yeshua-conversoes');
        
        return new WP_REST_Response($results);
    }
    
    /**
     * Preview de mensagem processada com placeholders
     */
    public static function preview_message($request) {
        $data = $request->get_json_params();
        
        $template = $data['template'] ?? '';
        
        // Dados de exemplo para preview
        $preview_data = [
            'nome' => $data['nome'] ?? 'João Silva',
            'email' => $data['email'] ?? 'joao@exemplo.com',
            'whatsapp' => $data['whatsapp'] ?? '11999999999',
            'telefone' => $data['whatsapp'] ?? '11999999999',
            'mensagem' => $data['mensagem'] ?? 'Mensagem de exemplo',
            'campos_extras' => $data['campos_extras'] ?? [],
        ];
        
        $processed = Yeshua_Evolution::process_message($template, $preview_data);
        
        return new WP_REST_Response([
            'success' => true,
            'original' => $template,
            'processed' => $processed,
            'saudacao_atual' => Yeshua_Evolution::get_saudacao(),
            'hora_atual' => date('H:i'),
        ]);
    }
    
    /**
     * Testa disparo de conversão
     */
    public static function test_conversion($request) {
        $data = $request->get_json_params();
        
        $api = Yeshua_Api::get_instance();
        
        if (!$api->is_configured()) {
            $api_key = get_option('yeshua_api_key', '');
            $website_id = get_option('yeshua_website_id', '');
            
            $missing = [];
            if (empty($api_key)) $missing[] = 'API Key';
            if (empty($website_id)) $missing[] = 'Website ID';
            
            return new WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    __('YESHUA API não configurada. Faltando: %s. Configure em YESHUA TLC > Configurações.', 'yeshua-conversoes'),
                    implode(', ', $missing)
                ),
            ], 400);
        }
        
        // Prepara dados da conversão de teste
        $conversion_data = [
            'tipo_conversao' => sanitize_text_field($data['conversion_type'] ?? 'lead'),
            'ip' => Yeshua_Api::get_client_ip(),
            'dispositivo' => Yeshua_Api::detect_device(),
            'navegador' => Yeshua_Api::detect_browser(),
            'sistema_operacional' => Yeshua_Api::detect_os(),
            'url_conversao' => sanitize_url($data['url_conversao'] ?? admin_url()),
            'url_origem' => sanitize_url($data['url_origem'] ?? ''),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'rede_origem' => sanitize_text_field($data['rede_origem'] ?? 'direto'),
        ];
        
        // Adiciona UTMs de teste se fornecidos
        if (!empty($data['utm_source'])) {
            $conversion_data['utm_source'] = sanitize_text_field($data['utm_source']);
        }
        if (!empty($data['utm_medium'])) {
            $conversion_data['utm_medium'] = sanitize_text_field($data['utm_medium']);
        }
        if (!empty($data['utm_campaign'])) {
            $conversion_data['utm_campaign'] = sanitize_text_field($data['utm_campaign']);
        }
        
        // Registra a conversão
        $result = $api->register_conversion($conversion_data);
        
        // Prepara resposta
        $response = [
            'success' => $result['success'] ?? false,
            'message' => '',
            'debug' => [
                'website_id' => $api->get_website_id(),
                'conversion_data' => $conversion_data,
                'api_response' => $result,
            ],
        ];
        
        if ($response['success']) {
            $response['message'] = __('Conversão registrada com sucesso!', 'yeshua-conversoes');
            if (isset($result['data']['id'])) {
                $response['message'] .= ' ID: ' . $result['data']['id'];
            }
        } else {
            $response['message'] = $result['message'] ?? __('Erro ao registrar conversão', 'yeshua-conversoes');
            
            // Se houver erros de validação, mostra detalhes
            if (isset($result['errors']) && is_array($result['errors'])) {
                $errors = [];
                foreach ($result['errors'] as $field => $error) {
                    $errors[] = $field . ': ' . (is_array($error) ? implode(', ', $error) : $error);
                }
                $response['message'] .= ' - ' . implode('; ', $errors);
            }
        }
        
        return new WP_REST_Response($response, $response['success'] ? 200 : 400);
    }

    /**
     * Exporta container GTM pré-configurado como JSON
     */
    public static function export_gtm_container($request) {
        $container = Yeshua_Gtm::generate_gtm_container_json();

        $site_name = sanitize_file_name(get_bloginfo('name'));
        $filename = 'gtm-container-' . $site_name . '-' . date('Y-m-d') . '.json';

        $response = new WP_REST_Response($container);
        $response->header('Content-Type', 'application/json');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Lista formulários disponíveis de um plugin externo
     */
    public static function get_external_forms($request) {
        $plugin = sanitize_text_field($request->get_param('plugin'));

        if (empty($plugin)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Plugin nao informado.', 'yeshua-conversoes'),
            ], 400);
        }

        $forms = Yeshua_External_Forms::get_available_forms($plugin);

        return new WP_REST_Response([
            'success' => true,
            'data' => $forms,
        ]);
    }

    /**
     * Lista campos de um formulário externo específico
     */
    public static function get_external_form_fields($request) {
        $plugin = sanitize_text_field($request->get_param('plugin'));
        $form_id = sanitize_text_field($request->get_param('form_id'));

        if (empty($plugin) || empty($form_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Plugin e form_id sao obrigatorios.', 'yeshua-conversoes'),
            ], 400);
        }

        $fields = Yeshua_External_Forms::get_form_fields($plugin, $form_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $fields,
        ]);
    }
}
