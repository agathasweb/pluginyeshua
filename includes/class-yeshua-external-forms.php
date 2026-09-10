<?php
/**
 * Integração server-side com formulários externos (Elementor, CF7, WPForms, Gravity Forms)
 *
 * Captura submissões via hooks PHP e registra como leads na API YESHUA.
 *
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_External_Forms {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_hooks();
    }

    /**
     * Registra hooks dos plugins de formulário suportados
     */
    private function register_hooks() {
        // Elementor Pro Forms
        add_action('elementor_pro/forms/new_record', [$this, 'handle_elementor'], 10, 2);

        // Contact Form 7
        add_action('wpcf7_mail_sent', [$this, 'handle_cf7'], 10, 1);

        // WPForms
        add_action('wpforms_process_complete', [$this, 'handle_wpforms'], 10, 4);

        // Gravity Forms
        add_action('gform_after_submission', [$this, 'handle_gravity_forms'], 10, 2);
    }

    /**
     * Retorna os mapeamentos configurados
     */
    public static function get_mappings() {
        $mappings = get_option('yeshua_external_forms_mappings', []);
        if (!is_array($mappings)) {
            return [];
        }
        return $mappings;
    }

    /**
     * Encontra mapeamento por plugin e form_id
     */
    private function find_mapping($plugin, $form_id) {
        $mappings = self::get_mappings();
        $form_id = (string) $form_id;

        foreach ($mappings as $mapping) {
            if (
                !empty($mapping['enabled']) &&
                $mapping['plugin'] === $plugin &&
                (string) $mapping['form_id'] === $form_id
            ) {
                return $mapping;
            }
        }

        return null;
    }

    /**
     * Processa lead capturado de formulário externo
     */
    private function process_external_lead($mapping, $fields) {
        $nome = '';
        $email = '';
        $telefone = '';
        $campos_extras = [];

        // Mapeia campos principais
        if (!empty($mapping['field_nome']) && isset($fields[$mapping['field_nome']])) {
            $nome = $fields[$mapping['field_nome']];
        }
        if (!empty($mapping['field_email']) && isset($fields[$mapping['field_email']])) {
            $email = $fields[$mapping['field_email']];
        }
        if (!empty($mapping['field_telefone']) && isset($fields[$mapping['field_telefone']])) {
            $telefone = $fields[$mapping['field_telefone']];
        }

        // Sem nome nem email nem telefone = nada a registrar
        if (empty($nome) && empty($email) && empty($telefone)) {
            return;
        }

        // Campos extras (todos os outros campos que não foram mapeados)
        $mapped_fields = array_filter([
            $mapping['field_nome'] ?? '',
            $mapping['field_email'] ?? '',
            $mapping['field_telefone'] ?? '',
        ]);
        foreach ($fields as $key => $value) {
            if (!in_array($key, $mapped_fields, true) && $value !== '') {
                $campos_extras[$key] = $value;
            }
        }

        // Monta dados no formato do process_submission
        $lead_data = [
            'nome' => sanitize_text_field($nome),
            'email' => sanitize_email($email),
            'telefone' => sanitize_text_field($telefone),
            'origem' => 'formulario',
            'ip' => Yeshua_Api::get_client_ip(),
            'dispositivo' => Yeshua_Api::detect_device(),
            'url_origem' => sanitize_url(wp_get_referer() ?: home_url()),
        ];

        // Adiciona info do formulário
        $form_label = !empty($mapping['form_name']) ? $mapping['form_name'] : $mapping['plugin'] . ' #' . $mapping['form_id'];
        $campos_extras['_formulario'] = $form_label;
        $campos_extras['_plugin'] = $mapping['plugin'];

        if (!empty($campos_extras)) {
            $lead_data['dados_extras'] = array_map('sanitize_text_field', $campos_extras);
        }

        // Origem da visita: UTMs e também os click ids.
        //
        // O `gclid`/`fbclid` faltava aqui, e é ele que permite a conversão offline e o
        // casamento do lead com o clique pago — sem ele o relatório sabe que veio do Google,
        // mas não de qual clique. Em requisição de AJAX (que é como todo formulário externo
        // envia) o `$_GET` está vazio: quem responde é o cookie de primeiro toque gravado
        // por Yeshua_Tracking::persistir_origem().
        foreach (Yeshua_Tracking::campos_de_origem() as $field) {
            if (!empty($_GET[$field])) {
                $lead_data[$field] = sanitize_text_field(wp_unslash($_GET[$field]));
            } elseif (!empty($_COOKIE['yeshua_' . $field])) {
                $lead_data[$field] = sanitize_text_field(wp_unslash($_COOKIE['yeshua_' . $field]));
            }
        }

        // Identificador do evento, gerado no navegador no mesmo instante do `fbq('track')`.
        //
        // É o que impede a Meta de contar DUAS conversões pelo mesmo lead: o evento do
        // navegador e o da API de Conversões chegam com o mesmo `event_id` e são deduplicados.
        // Sem ele, ligar o Pixel no formulário externo dobraria o número de conversões
        // relatado — parecendo melhora, sendo contagem em dobro.
        // Lido do POST cru, e não de `$fields`: cada plugin de formulário entrega ao PHP
        // apenas os campos que ELE conhece (o Elementor devolve só os seus `form_fields`), e
        // o campo oculto é nosso. No POST ele está sempre, venha de qual formulário vier.
        $event_id = '';
        if (!empty($_POST['yeshua_event_id'])) {
            $event_id = sanitize_text_field(wp_unslash($_POST['yeshua_event_id']));
        } elseif (!empty($fields['yeshua_event_id'])) {
            $event_id = sanitize_text_field($fields['yeshua_event_id']);
        }

        if ($event_id !== '') {
            $extras_atuais = isset($lead_data['dados_extras']) ? $lead_data['dados_extras'] : [];
            $extras_atuais['event_id'] = $event_id;
            // O campo oculto já entrou como campo extra do formulário; sai daqui para não
            // aparecer duas vezes na ficha do lead com nomes diferentes.
            unset($extras_atuais['yeshua_event_id']);
            $lead_data['dados_extras'] = $extras_atuais;
        }

        // Registra lead na API YESHUA
        $api = Yeshua_Api::get_instance();
        if ($api->is_configured()) {
            $api_result = $api->register_lead($lead_data);

            if (!isset($api_result['success']) || !$api_result['success']) {
                error_log('YESHUA External Forms API Error: ' . wp_json_encode($api_result));
            } else {
                error_log('YESHUA External Forms Lead registered: ID=' . ($api_result['data']['id'] ?? 'N/A') . ' Plugin=' . ($mapping['plugin'] ?? ''));
            }
        } else {
            error_log('YESHUA API not configured - external form lead NOT registered. API Key: ' . ($api->get_api_key() ? 'SET' : 'EMPTY') . ', Website ID: ' . ($api->get_website_id() ?: 'EMPTY'));
        }

        // Prepara dados para mensagens (compatível com templates)
        $message_data = array_merge($lead_data, [
            'whatsapp' => $lead_data['telefone'],
            'campos_extras' => $campos_extras,
        ]);

        // Envia mensagem via Evolution API
        $evolution = Yeshua_Evolution::get_instance();
        if ($evolution->is_configured() && !empty($lead_data['telefone'])) {
            $message_template = get_option('yeshua_external_forms_message', '');
            if (!empty($message_template)) {
                $message = Yeshua_Evolution::process_message($message_template, $message_data);
                $evolution->send_text($lead_data['telefone'], $message);
            }
        }

        // Envia e-mail
        $smtp = Yeshua_Smtp::get_instance();
        if ($smtp->is_configured()) {
            $email_template = get_option('yeshua_external_forms_email_template', '');
            $smtp->send_lead_notification($message_data, $email_template);
        }
    }

    // ─── ELEMENTOR PRO ────────────────────────────────────────────

    /**
     * Hook: elementor_pro/forms/new_record
     */
    public function handle_elementor($record, $handler) {
        $form_name = $record->get_form_settings('form_name');
        $form_id = $record->get_form_settings('id');

        error_log('YESHUA External Forms: Elementor form submitted - form_id=' . $form_id . ', form_name=' . $form_name);

        // Tenta encontrar pelo ID do elemento
        $mapping = $this->find_mapping('elementor', $form_id);

        // Fallback: tenta pelo nome do formulário
        if (!$mapping && $form_name) {
            $mapping = $this->find_mapping('elementor', $form_name);
        }

        if (!$mapping) {
            error_log('YESHUA External Forms: No mapping found for elementor form_id=' . $form_id . ', form_name=' . $form_name);
            return;
        }

        // Extrai campos do Elementor
        $raw_fields = $record->get('fields');
        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
            // Também mapeia pelo título do campo (facilita config)
            if (!empty($field['title'])) {
                $title_key = sanitize_title($field['title']);
                $fields[$title_key] = $field['value'];
            }
        }

        $this->process_external_lead($mapping, $fields);
    }

    // ─── CONTACT FORM 7 ──────────────────────────────────────────

    /**
     * Hook: wpcf7_mail_sent
     */
    public function handle_cf7($contact_form) {
        $form_id = $contact_form->id();

        $mapping = $this->find_mapping('cf7', $form_id);
        if (!$mapping) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $posted = $submission->get_posted_data();
        $fields = [];
        foreach ($posted as $key => $value) {
            // CF7 pode retornar arrays para checkboxes
            $fields[$key] = is_array($value) ? implode(', ', $value) : $value;
        }

        $this->process_external_lead($mapping, $fields);
    }

    // ─── WPFORMS ─────────────────────────────────────────────────

    /**
     * Hook: wpforms_process_complete
     */
    public function handle_wpforms($fields, $entry, $form_data, $entry_id) {
        $form_id = $form_data['id'];

        $mapping = $this->find_mapping('wpforms', $form_id);
        if (!$mapping) {
            return;
        }

        $parsed = [];
        foreach ($fields as $field) {
            $key = (string) $field['id'];
            $parsed[$key] = $field['value'] ?? '';
            // Também mapeia pelo nome do campo
            if (!empty($field['name'])) {
                $parsed[sanitize_title($field['name'])] = $field['value'] ?? '';
            }
        }

        $this->process_external_lead($mapping, $parsed);
    }

    // ─── GRAVITY FORMS ───────────────────────────────────────────

    /**
     * Hook: gform_after_submission
     */
    public function handle_gravity_forms($entry, $form) {
        $form_id = $form['id'];

        $mapping = $this->find_mapping('gravityforms', $form_id);
        if (!$mapping) {
            return;
        }

        $fields = [];
        foreach ($form['fields'] as $field) {
            $field_id = (string) $field->id;
            $value = rgar($entry, $field_id);
            $fields[$field_id] = $value;
            // Também mapeia pelo label (slug)
            if (!empty($field->label)) {
                $fields[sanitize_title($field->label)] = $value;
            }
        }

        $this->process_external_lead($mapping, $fields);
    }

    // ─── HELPERS PARA ADMIN ──────────────────────────────────────

    /**
     * Retorna plugins de formulário detectados no site
     */
    public static function get_detected_plugins() {
        $plugins = [];

        if (defined('ELEMENTOR_PRO_VERSION')) {
            $plugins['elementor'] = 'Elementor Pro ' . ELEMENTOR_PRO_VERSION;
        }

        if (defined('WPCF7_VERSION')) {
            $plugins['cf7'] = 'Contact Form 7 ' . WPCF7_VERSION;
        }

        if (defined('WPFORMS_VERSION')) {
            $plugins['wpforms'] = 'WPForms ' . WPFORMS_VERSION;
        }

        if (class_exists('GFForms')) {
            $plugins['gravityforms'] = 'Gravity Forms ' . (defined('GF_VERSION') ? GF_VERSION : '');
        }

        return $plugins;
    }

    /**
     * Lista formulários disponíveis de um plugin específico
     */
    public static function get_available_forms($plugin) {
        $forms = [];

        switch ($plugin) {
            case 'elementor':
                // Elementor armazena forms nos widgets dos posts
                // Não tem um registry centralizado — o usuário informa o ID/nome
                break;

            case 'cf7':
                if (class_exists('WPCF7_ContactForm')) {
                    $cf7_forms = WPCF7_ContactForm::find();
                    foreach ($cf7_forms as $form) {
                        $forms[] = [
                            'id' => $form->id(),
                            'title' => $form->title(),
                        ];
                    }
                }
                break;

            case 'wpforms':
                if (function_exists('wpforms')) {
                    $wpforms = wpforms()->form->get('', ['orderby' => 'title']);
                    if ($wpforms) {
                        foreach ($wpforms as $form) {
                            $forms[] = [
                                'id' => $form->ID,
                                'title' => $form->post_title,
                            ];
                        }
                    }
                }
                break;

            case 'gravityforms':
                if (class_exists('GFAPI')) {
                    $gf_forms = GFAPI::get_forms();
                    foreach ($gf_forms as $form) {
                        $forms[] = [
                            'id' => $form['id'],
                            'title' => $form['title'],
                        ];
                    }
                }
                break;
        }

        return $forms;
    }

    /**
     * Lista campos de um formulário específico
     */
    public static function get_form_fields($plugin, $form_id) {
        $fields = [];

        switch ($plugin) {
            case 'elementor':
                // Elementor: buscar no post meta do template/page
                $posts = get_posts([
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_key' => '_elementor_data',
                ]);
                foreach ($posts as $post) {
                    $data = get_post_meta($post->ID, '_elementor_data', true);
                    if ($data) {
                        $found = self::find_elementor_form_fields($data, $form_id);
                        if (!empty($found)) {
                            $fields = $found;
                            break;
                        }
                    }
                }
                break;

            case 'cf7':
                if (class_exists('WPCF7_ContactForm')) {
                    $form = WPCF7_ContactForm::get_instance($form_id);
                    if ($form) {
                        $tags = $form->scan_form_tags();
                        foreach ($tags as $tag) {
                            if (!empty($tag->name)) {
                                $fields[] = [
                                    'id' => $tag->name,
                                    'label' => $tag->name,
                                    'type' => $tag->basetype,
                                ];
                            }
                        }
                    }
                }
                break;

            case 'wpforms':
                if (function_exists('wpforms')) {
                    $form = wpforms()->form->get($form_id);
                    if ($form) {
                        $form_data = json_decode($form->post_content, true);
                        if (!empty($form_data['fields'])) {
                            foreach ($form_data['fields'] as $field) {
                                $fields[] = [
                                    'id' => (string) $field['id'],
                                    'label' => $field['label'] ?? '',
                                    'type' => $field['type'] ?? 'text',
                                ];
                            }
                        }
                    }
                }
                break;

            case 'gravityforms':
                if (class_exists('GFAPI')) {
                    $form = GFAPI::get_form($form_id);
                    if ($form && !empty($form['fields'])) {
                        foreach ($form['fields'] as $field) {
                            $fields[] = [
                                'id' => (string) $field->id,
                                'label' => $field->label,
                                'type' => $field->type,
                            ];
                        }
                    }
                }
                break;
        }

        return $fields;
    }

    /**
     * Busca campos de formulário dentro do JSON do Elementor (recursivo)
     */
    private static function find_elementor_form_fields($data, $form_id) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $element) {
            // Verifica se é um widget de formulário
            if (
                isset($element['widgetType']) &&
                $element['widgetType'] === 'form' &&
                isset($element['settings'])
            ) {
                $settings = $element['settings'];
                $el_id = $settings['id'] ?? ($element['id'] ?? '');
                $el_name = $settings['form_name'] ?? '';

                if ($el_id === $form_id || $el_name === $form_id) {
                    $fields = [];
                    if (!empty($settings['form_fields'])) {
                        foreach ($settings['form_fields'] as $field) {
                            $fields[] = [
                                'id' => $field['custom_id'] ?? $field['_id'] ?? '',
                                'label' => $field['field_label'] ?? '',
                                'type' => $field['field_type'] ?? 'text',
                            ];
                        }
                    }
                    return $fields;
                }
            }

            // Recursão nos filhos
            if (!empty($element['elements'])) {
                $found = self::find_elementor_form_fields($element['elements'], $form_id);
                if (!empty($found)) {
                    return $found;
                }
            }
        }

        return [];
    }

    /**
     * Plugins suportados (labels para UI)
     */
    public static function get_supported_plugins() {
        return [
            'elementor' => 'Elementor Pro',
            'cf7' => 'Contact Form 7',
            'wpforms' => 'WPForms',
            'gravityforms' => 'Gravity Forms',
        ];
    }
}
