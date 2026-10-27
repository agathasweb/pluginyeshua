<?php
/**
 * Desligamento automático quando o cliente é encerrado no YESHUA.
 *
 * Uma vez por dia (WP-Cron) — e logo depois de qualquer envio que volte com `encerrado` — o
 * plugin pergunta ao YESHUA se este site ainda é de cliente ativo. Se a resposta for 410 (ou
 * trouxer `encerrado: true`), ele apaga as credenciais salvas e se desativa.
 *
 * Só esse sinal desliga. 401, erro de rede, 404 ou 500 NUNCA desligam: token trocado, YESHUA
 * fora do ar ou site ainda não cadastrado não podem derrubar o plugin de um cliente ativo.
 *
 * Desativa, não exclui: se o cliente voltar, basta reativar e colar o token de novo — as
 * configurações de formulários, conversões e GTM continuam salvas.
 *
 * @package Yeshua_Conversoes
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Encerramento {

    const HOOK = 'yeshua_verificar_encerramento';

    /** Credenciais apagadas no desligamento — nada de segredo fica no WordPress do ex-cliente. */
    const SEGREDOS = [
        'yeshua_api_key',
        'yeshua_evolution_api_key',
        'yeshua_evolution_token',
        'yeshua_smtp_password',
        'yeshua_recaptcha_secret_key',
    ];

    private static $verificar_no_fim = false;
    private static $desligar_no_fim = false;
    private static $em_andamento = false;

    public static function init() {
        add_action(self::HOOK, [__CLASS__, 'verificar']);

        if (!wp_next_scheduled(self::HOOK)) {
            self::agendar();
        }
    }

    /** Horário sorteado no dia para os sites não perguntarem todos no mesmo segundo. */
    public static function agendar() {
        wp_schedule_event(time() + wp_rand(HOUR_IN_SECONDS, DAY_IN_SECONDS), 'daily', self::HOOK);
    }

    public static function desagendar() {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * Chamado pelo Yeshua_Api a cada resposta. Não desliga no meio da requisição do visitante:
     * marca para o `shutdown`, depois que a resposta do formulário já saiu.
     */
    public static function observar_resposta($endpoint, $http_code, $decoded) {
        if (self::$em_andamento || $endpoint === 'plugin/status') {
            return;
        }

        if ((int) $http_code === 410) {
            // 410 só sai para token de site de cliente encerrado: é a ordem, sem margem de dúvida.
            self::$desligar_no_fim = true;
        } elseif (is_array($decoded) && !empty($decoded['encerrado'])) {
            // Com a chave global o site foi achado pelo website_id, que pode estar errado:
            // confirma pelo domínio antes de desligar.
            self::$verificar_no_fim = true;
        } else {
            return;
        }

        if (!has_action('shutdown', [__CLASS__, 'no_fim_da_requisicao'])) {
            add_action('shutdown', [__CLASS__, 'no_fim_da_requisicao']);
        }
    }

    public static function no_fim_da_requisicao() {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        if (self::$desligar_no_fim) {
            self::desligar('resposta 410 do YESHUA');
        } elseif (self::$verificar_no_fim) {
            self::verificar();
        }
    }

    /** Checagem diária: pergunta ao YESHUA pelo domínio deste WordPress. */
    public static function verificar() {
        if (self::$em_andamento || get_option('yeshua_api_key', '') === '') {
            return;
        }

        self::$em_andamento = true;
        $resposta = Yeshua_Api::get_instance()->request('plugin/status', 'GET', ['dominio' => home_url()]);
        self::$em_andamento = false;

        $codigo = (int) ($resposta['http_code'] ?? 0);
        if ($codigo === 410 || ($codigo === 200 && !empty($resposta['encerrado']))) {
            self::desligar('checagem de status');
        }
    }

    /** Apaga as credenciais e desativa o plugin. */
    public static function desligar($motivo) {
        if (self::$em_andamento) {
            return;
        }
        self::$em_andamento = true;

        foreach (self::SEGREDOS as $opcao) {
            update_option($opcao, '');
        }
        update_option('yeshua_encerrado_em', current_time('mysql'));
        self::desagendar();

        error_log('YESHUA: cliente encerrado no YESHUA (' . $motivo . ') — credenciais apagadas e plugin desativado.');

        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $em_rede = is_multisite() && is_plugin_active_for_network(YESHUA_PLUGIN_BASENAME);
        deactivate_plugins(YESHUA_PLUGIN_BASENAME, false, $em_rede);
    }
}
