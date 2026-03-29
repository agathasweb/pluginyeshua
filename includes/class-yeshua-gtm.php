<?php
/**
 * Classe para integração com GTM, GA4 e Google Ads
 *
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Gtm {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (is_admin()) {
            return;
        }

        add_action('wp_head', [$this, 'inject_head_scripts'], 1);
        add_action('wp_body_open', [$this, 'inject_body_noscript'], 1);
        add_action('wp_footer', [$this, 'inject_thank_you_script'], 99);
    }

    /**
     * Verifica se GTM está configurado
     */
    public function has_gtm() {
        return !empty(get_option('yeshua_gtm_id', ''));
    }

    /**
     * Verifica se GA4 está configurado
     */
    public function has_ga4() {
        return !empty(get_option('yeshua_ga4_id', ''));
    }

    /**
     * Verifica se Google Ads está configurado
     */
    public function has_gads() {
        return !empty(get_option('yeshua_gads_id', '')) && !empty(get_option('yeshua_gads_label', ''));
    }

    /**
     * Deve excluir visitante (admins logados)
     */
    private function should_exclude() {
        $exclude = get_option('yeshua_gtm_exclude_admins', '1');
        return $exclude && is_user_logged_in() && current_user_can('manage_options');
    }

    /**
     * Injeta scripts no <head>
     */
    public function inject_head_scripts() {
        if ($this->should_exclude()) {
            return;
        }

        $gtm_id = get_option('yeshua_gtm_id', '');
        $ga4_id = get_option('yeshua_ga4_id', '');
        $gads_id = get_option('yeshua_gads_id', '');

        // GTM
        if (!empty($gtm_id)) {
            $gtm_id = sanitize_text_field($gtm_id);
            ?>
<!-- Google Tag Manager - YESHUA -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
<!-- End Google Tag Manager -->
            <?php
        }

        // GA4 (gtag.js) - só injeta se não tem GTM (para evitar duplicidade)
        if (!empty($ga4_id) && empty($gtm_id)) {
            $ga4_id = sanitize_text_field($ga4_id);
            ?>
<!-- Google Analytics GA4 - YESHUA -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4_id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo esc_js($ga4_id); ?>');
<?php if (!empty($gads_id)) : ?>
gtag('config', '<?php echo esc_js(sanitize_text_field($gads_id)); ?>');
<?php endif; ?>
</script>
<!-- End Google Analytics GA4 -->
            <?php
        }

        // Google Ads gtag - se tem GTM, o Ads deve ser configurado via GTM
        // Se não tem GTM mas tem GA4, o Ads já foi adicionado acima
        // Se não tem GTM nem GA4, precisa do gtag.js standalone para Ads
        if (!empty($gads_id) && empty($gtm_id) && empty($ga4_id)) {
            $gads_id = sanitize_text_field($gads_id);
            ?>
<!-- Google Ads - YESHUA -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($gads_id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo esc_js($gads_id); ?>');
</script>
<!-- End Google Ads -->
            <?php
        }

        // Inicializa dataLayer se GTM está ativo
        if (!empty($gtm_id)) {
            ?>
<script>window.dataLayer = window.dataLayer || [];</script>
            <?php
        }

        // Meta Pixel base code
        $meta_pixel_id = get_option('yeshua_meta_pixel_id', '');
        if (!empty($meta_pixel_id)) {
            $meta_pixel_id = sanitize_text_field($meta_pixel_id);
            ?>
<!-- Meta Pixel Code - YESHUA -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo esc_js($meta_pixel_id); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr($meta_pixel_id); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
            <?php
        }
    }

    /**
     * Injeta noscript do GTM após <body>
     */
    public function inject_body_noscript() {
        if ($this->should_exclude()) {
            return;
        }

        $gtm_id = get_option('yeshua_gtm_id', '');
        if (empty($gtm_id)) {
            return;
        }

        $gtm_id = sanitize_text_field($gtm_id);
        ?>
<!-- Google Tag Manager (noscript) - YESHUA -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
        <?php
    }

    /**
     * Injeta scripts de monitoramento de formulários externos e detecção de página de obrigado
     */
    public function inject_thank_you_script() {
        if ($this->should_exclude()) {
            return;
        }

        $this->inject_external_forms_script();
        $this->inject_thank_you_detection();
    }

    /**
     * Injeta script para monitorar submissão de formulários externos (Elementor, CF7, WPForms, etc.)
     */
    private function inject_external_forms_script() {
        $selectors_raw = get_option('yeshua_gtm_external_forms', '');
        if (empty($selectors_raw)) {
            return;
        }

        $selectors = array_filter(array_map('trim', explode("\n", $selectors_raw)));
        if (empty($selectors)) {
            return;
        }

        $gtm_id = get_option('yeshua_gtm_id', '');
        $ga4_id = get_option('yeshua_ga4_id', '');
        $gads_id = get_option('yeshua_gads_id', '');
        $gads_label = get_option('yeshua_gads_label', '');

        if (empty($gtm_id) && empty($ga4_id) && empty($gads_id)) {
            return;
        }

        $selectors_json = wp_json_encode($selectors);
        ?>
<!-- YESHUA External Forms Tracking -->
<script>
(function() {
    var selectors = <?php echo $selectors_json; ?>;
    var selectorStr = selectors.join(',');

    function yeshuaCollectFormUserData(formEl) {
        var email = '', phone = '', name = '';
        var inputs = formEl.querySelectorAll('input, textarea, select');
        for (var i = 0; i < inputs.length; i++) {
            var inp = inputs[i];
            var t = (inp.type || '').toLowerCase();
            var n = (inp.name || '').toLowerCase();
            var v = (inp.value || '').trim();
            if (!v) continue;
            if (t === 'email' || n.indexOf('email') !== -1) { email = email || v; }
            else if (t === 'tel' || n.indexOf('phone') !== -1 || n.indexOf('whatsapp') !== -1 || n.indexOf('telefone') !== -1 || n.indexOf('celular') !== -1) { phone = phone || v; }
            else if (n === 'name' || n === 'nome' || n.indexOf('your-name') !== -1 || n.indexOf('full_name') !== -1) { name = name || v; }
        }
        return { email: email, phone: phone, name: name };
    }

    function yeshuaFireFormConversion(formEl) {
        var formId = formEl.id || '';
        var formClass = formEl.className || '';
        var label = 'external_form';
        if (formId) label += '_' + formId;

        var userData = yeshuaCollectFormUserData(formEl);

        // Set Enhanced Conversions data (raw - GTM hashes automatically)
        var ecData = {};
        if (userData.email) ecData.email = userData.email;
        if (userData.phone) {
            var digits = userData.phone.replace(/\D/g, '');
            if (digits.length <= 11) digits = '55' + digits;
            ecData.phone_number = '+' + digits;
        }
        if (userData.name) {
            var parts = userData.name.trim().split(/\s+/);
            if (parts.length >= 1) ecData.first_name = parts[0];
            if (parts.length >= 2) ecData.last_name = parts[parts.length - 1];
        }
        window.enhanced_conversion_data = ecData;

        <?php if (!empty($gtm_id)) : ?>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'generate_lead',
            'event_category': 'form',
            'event_label': label,
            'form_id': formId,
            'form_class': formClass,
            'page_path': window.location.pathname
        });
        window.dataLayer.push({
            'event': 'form_submission',
            'event_category': 'form',
            'event_label': label,
            'form_id': formId,
            'page_path': window.location.pathname
        });
        <?php endif; ?>

        <?php if (!empty($ga4_id) && empty($gtm_id)) : ?>
        if (typeof gtag === 'function') {
            gtag('event', 'generate_lead', {
                event_category: 'form',
                event_label: label,
                form_id: formId,
                page_path: window.location.pathname
            });
        }
        <?php endif; ?>

        <?php if (!empty($gads_id) && !empty($gads_label) && empty($gtm_id)) : ?>
        if (typeof gtag === 'function') {
            gtag('event', 'conversion', {
                'send_to': '<?php echo esc_js(sanitize_text_field($gads_id)); ?>/<?php echo esc_js(sanitize_text_field($gads_label)); ?>'
            });
        }
        <?php endif; ?>
    }

    // Listener nativo de submit
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form && form.matches && form.matches(selectorStr)) {
            yeshuaFireFormConversion(form);
        }
    }, true);

    // Elementor Pro - evento custom após sucesso
    document.addEventListener('submit_success', function(e) {
        var form = e.target || (e.detail && e.detail.formElement);
        if (form && form.matches && form.matches(selectorStr)) {
            yeshuaFireFormConversion(form);
        }
    });

    // jQuery events para Contact Form 7 e WPForms
    if (typeof jQuery !== 'undefined') {
        // Contact Form 7
        jQuery(document).on('wpcf7mailsent', function(e) {
            var form = e.target;
            if (form && jQuery(form).is(selectorStr)) {
                yeshuaFireFormConversion(form);
            }
        });

        // WPForms
        jQuery(document).on('wpformsAjaxSubmitSuccess', function(e, response, form) {
            if (form && jQuery(form).is(selectorStr)) {
                yeshuaFireFormConversion(form[0] || form);
            }
        });
    }

    // Gravity Forms
    if (typeof gform !== 'undefined' || typeof jQuery !== 'undefined') {
        (typeof jQuery !== 'undefined' ? jQuery(document) : document).addEventListener &&
        document.addEventListener('gform_confirmation_loaded', function(e) {
            var formId = e.detail && e.detail.formId;
            if (formId) {
                var form = document.getElementById('gform_' + formId);
                if (form && form.matches && form.matches(selectorStr)) {
                    yeshuaFireFormConversion(form);
                }
            }
        });
    }
})();
</script>
<!-- End YESHUA External Forms Tracking -->
        <?php
    }

    /**
     * Injeta script de detecção de página de obrigado (Thank You page)
     */
    private function inject_thank_you_detection() {
        $thank_you_urls = get_option('yeshua_gtm_thank_you_urls', '');
        if (empty($thank_you_urls)) {
            return;
        }

        $ga4_id = get_option('yeshua_ga4_id', '');
        $gads_id = get_option('yeshua_gads_id', '');
        $gads_label = get_option('yeshua_gads_label', '');
        $gtm_id = get_option('yeshua_gtm_id', '');

        $urls = array_filter(array_map('trim', explode("\n", $thank_you_urls)));
        if (empty($urls)) {
            return;
        }

        $urls_json = wp_json_encode($urls);
        ?>
<!-- YESHUA Thank You Page Detection -->
<script>
(function() {
    var thankYouUrls = <?php echo $urls_json; ?>;
    var currentPath = window.location.pathname;
    var currentUrl = window.location.href;
    var isThankYou = false;

    for (var i = 0; i < thankYouUrls.length; i++) {
        var pattern = thankYouUrls[i];
        if (!pattern) continue;
        if (pattern.indexOf('*') !== -1) {
            var regex = new RegExp(pattern.replace(/\*/g, '.*'), 'i');
            if (regex.test(currentPath) || regex.test(currentUrl)) {
                isThankYou = true;
                break;
            }
        } else if (currentPath === pattern || currentPath.indexOf(pattern) !== -1) {
            isThankYou = true;
            break;
        }
    }

    if (!isThankYou) return;

    <?php if (!empty($gtm_id)) : ?>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        'event': 'generate_lead',
        'event_category': 'conversion',
        'event_label': 'thank_you_page',
        'page_path': currentPath
    });
    window.dataLayer.push({
        'event': 'conversion',
        'event_category': 'conversion',
        'event_label': 'thank_you_page',
        'page_path': currentPath
    });
    <?php endif; ?>

    <?php if (!empty($ga4_id) && empty($gtm_id)) : ?>
    if (typeof gtag === 'function') {
        gtag('event', 'generate_lead', {
            event_category: 'conversion',
            event_label: 'thank_you_page',
            page_path: currentPath
        });
    }
    <?php endif; ?>

    <?php if (!empty($gads_id) && !empty($gads_label) && empty($gtm_id)) : ?>
    if (typeof gtag === 'function') {
        gtag('event', 'conversion', {
            'send_to': '<?php echo esc_js(sanitize_text_field($gads_id)); ?>/<?php echo esc_js(sanitize_text_field($gads_label)); ?>'
        });
    }
    <?php endif; ?>
})();
</script>
<!-- End YESHUA Thank You Page Detection -->
        <?php
    }

    /**
     * Retorna o JavaScript inline para disparar evento de conversão nos formulários
     * Chamado pelo forms.js após submissão bem-sucedida
     */
    public static function get_form_conversion_config() {
        return [
            'gtm_id' => get_option('yeshua_gtm_id', ''),
            'ga4_id' => get_option('yeshua_ga4_id', ''),
            'gads_id' => get_option('yeshua_gads_id', ''),
            'gads_label' => get_option('yeshua_gads_label', ''),
        ];
    }

    /**
     * Gera JSON do container GTM pré-configurado com os IDs do cliente
     */
    public static function generate_gtm_container_json() {
        $ga4_id = sanitize_text_field(get_option('yeshua_ga4_id', ''));
        $gads_id = sanitize_text_field(get_option('yeshua_gads_id', ''));
        $gads_label = sanitize_text_field(get_option('yeshua_gads_label', ''));
        $site_name = sanitize_text_field(get_bloginfo('name'));
        $export_time = gmdate('Y-m-d\TH:i:s.000\Z');
        $fingerprint = (string)time();

        $account_id = '6099999999';
        $container_id = '99999999';

        $tag_id = 100;
        $trigger_id = 100;
        $variable_id = 100;

        // Helper to add common fields
        $base = function($id_key, $id_val) use ($account_id, $container_id, $fingerprint) {
            return [
                'accountId' => $account_id,
                'containerId' => $container_id,
                $id_key => (string)$id_val,
                'fingerprint' => $fingerprint,
            ];
        };

        // --- VARIABLES ---
        $variables = [];

        $dlv_names = ['event_category', 'event_label', 'form_type', 'page_path'];
        foreach ($dlv_names as $dlv_name) {
            $var = $base('variableId', $variable_id++);
            $var['name'] = 'DLV - ' . $dlv_name;
            $var['type'] = 'v';
            $var['parameter'] = [
                ['type' => 'INTEGER', 'key' => 'dataLayerVersion', 'value' => '2'],
                ['type' => 'BOOLEAN', 'key' => 'setDefaultValue', 'value' => 'false'],
                ['type' => 'TEMPLATE', 'key' => 'name', 'value' => $dlv_name],
            ];
            $variables[] = $var;
        }

        // (Enhanced Conversions variables are not needed here -
        //  the plugin sets window.enhanced_conversion_data globally
        //  and a Custom HTML tag in the container handles the gtag setup)

        // --- TRIGGERS ---
        $triggers = [];

        // All Pages
        $trigger_all_pages_id = $trigger_id;
        $t = $base('triggerId', $trigger_id++);
        $t['name'] = 'YESHUA - All Pages';
        $t['type'] = 'PAGEVIEW';
        $triggers[] = $t;

        // Custom event triggers
        $ce_events = [
            'generate_lead' => 'YESHUA - CE generate_lead',
            'form_submission' => 'YESHUA - CE form_submission',
            'conversion' => 'YESHUA - CE conversion',
        ];
        $trigger_ids = [];
        foreach ($ce_events as $event_name => $trigger_name) {
            $trigger_ids[$event_name] = $trigger_id;
            $t = $base('triggerId', $trigger_id++);
            $t['name'] = $trigger_name;
            $t['type'] = 'CUSTOM_EVENT';
            $t['customEventFilter'] = [
                [
                    'type' => 'EQUALS',
                    'parameter' => [
                        ['type' => 'TEMPLATE', 'key' => 'arg0', 'value' => '{{_event}}'],
                        ['type' => 'TEMPLATE', 'key' => 'arg1', 'value' => $event_name],
                    ],
                ],
            ];
            $triggers[] = $t;
        }

        // --- TAGS ---
        $tags = [];

        // GA4 Configuration tag
        if (!empty($ga4_id)) {
            $tag = $base('tagId', $tag_id++);
            $tag['name'] = 'YESHUA - GA4 Configuracao';
            $tag['type'] = 'gaawc';
            $tag['parameter'] = [
                ['type' => 'BOOLEAN', 'key' => 'sendPageView', 'value' => 'true'],
                ['type' => 'BOOLEAN', 'key' => 'enableSendToServerContainer', 'value' => 'false'],
                ['type' => 'TEMPLATE', 'key' => 'measurementId', 'value' => $ga4_id],
            ];
            $tag['firingTriggerId'] = [(string)$trigger_all_pages_id];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tags[] = $tag;

            // GA4 Event - generate_lead
            $tag = $base('tagId', $tag_id++);
            $tag['name'] = 'YESHUA - GA4 Evento generate_lead';
            $tag['type'] = 'gaawe';
            $tag['parameter'] = [
                ['type' => 'TEMPLATE', 'key' => 'eventName', 'value' => 'generate_lead'],
                ['type' => 'TEMPLATE', 'key' => 'measurementIdOverride', 'value' => $ga4_id],
                ['type' => 'LIST', 'key' => 'eventParameters', 'list' => [
                    ['type' => 'MAP', 'map' => [
                        ['type' => 'TEMPLATE', 'key' => 'name', 'value' => 'event_category'],
                        ['type' => 'TEMPLATE', 'key' => 'value', 'value' => '{{DLV - event_category}}'],
                    ]],
                    ['type' => 'MAP', 'map' => [
                        ['type' => 'TEMPLATE', 'key' => 'name', 'value' => 'event_label'],
                        ['type' => 'TEMPLATE', 'key' => 'value', 'value' => '{{DLV - event_label}}'],
                    ]],
                    ['type' => 'MAP', 'map' => [
                        ['type' => 'TEMPLATE', 'key' => 'name', 'value' => 'form_type'],
                        ['type' => 'TEMPLATE', 'key' => 'value', 'value' => '{{DLV - form_type}}'],
                    ]],
                ]],
            ];
            $tag['firingTriggerId'] = [(string)$trigger_ids['generate_lead']];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tags[] = $tag;
        }

        // Google Ads tags
        if (!empty($gads_id)) {
            // Conversion Linker
            $tag = $base('tagId', $tag_id++);
            $tag['name'] = 'YESHUA - Google Ads Conversion Linker';
            $tag['type'] = 'gclidw';
            $tag['parameter'] = [
                ['type' => 'BOOLEAN', 'key' => 'enableCrossDomain', 'value' => 'false'],
                ['type' => 'BOOLEAN', 'key' => 'enableUrlPassthrough', 'value' => 'false'],
            ];
            $tag['firingTriggerId'] = [(string)$trigger_all_pages_id];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tags[] = $tag;

            // Custom HTML tag - Enhanced Conversions data setup
            // Fires on generate_lead to set enhanced_conversion_data for Google Ads
            $ec_html = '<script>' . "\n"
                . 'if(window.enhanced_conversion_data){' . "\n"
                . '  var d=window.enhanced_conversion_data;' . "\n"
                . '  window.google_tag_params=window.google_tag_params||{};' . "\n"
                . '  if(d.email)window.google_tag_params.email=d.email;' . "\n"
                . '  if(d.phone_number)window.google_tag_params.phone_number=d.phone_number;' . "\n"
                . '  if(d.first_name)window.google_tag_params.first_name=d.first_name;' . "\n"
                . '  if(d.last_name)window.google_tag_params.last_name=d.last_name;' . "\n"
                . '}' . "\n"
                . '</script>';

            $ec_tag_id = $tag_id++;
            $tag = $base('tagId', $ec_tag_id);
            $tag['name'] = 'YESHUA - Enhanced Conversions Data';
            $tag['type'] = 'html';
            $tag['parameter'] = [
                ['type' => 'TEMPLATE', 'key' => 'html', 'value' => $ec_html],
                ['type' => 'BOOLEAN', 'key' => 'supportDocumentWrite', 'value' => 'false'],
            ];
            $tag['firingTriggerId'] = [(string)$trigger_ids['generate_lead']];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tag['priority'] = ['type' => 'INTEGER', 'value' => '99'];
            $tags[] = $tag;

            // Google Ads Conversion - generate_lead
            $tag = $base('tagId', $tag_id++);
            $tag['name'] = 'YESHUA - Google Ads Conversao generate_lead';
            $tag['type'] = 'awct';
            $tag['parameter'] = [
                ['type' => 'TEMPLATE', 'key' => 'conversionId', 'value' => str_replace('AW-', '', $gads_id)],
                ['type' => 'BOOLEAN', 'key' => 'enableNewCustomerReporting', 'value' => 'false'],
                ['type' => 'BOOLEAN', 'key' => 'enableEnhancedConversion', 'value' => 'false'],
            ];
            if (!empty($gads_label)) {
                $tag['parameter'][] = ['type' => 'TEMPLATE', 'key' => 'conversionLabel', 'value' => $gads_label];
            }
            $tag['firingTriggerId'] = [(string)$trigger_ids['generate_lead']];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tags[] = $tag;

            // Google Ads Conversion - thank you page
            $tag = $base('tagId', $tag_id++);
            $tag['name'] = 'YESHUA - Google Ads Conversao Thank You Page';
            $tag['type'] = 'awct';
            $tag['parameter'] = [
                ['type' => 'TEMPLATE', 'key' => 'conversionId', 'value' => str_replace('AW-', '', $gads_id)],
                ['type' => 'BOOLEAN', 'key' => 'enableNewCustomerReporting', 'value' => 'false'],
                ['type' => 'BOOLEAN', 'key' => 'enableEnhancedConversion', 'value' => 'false'],
            ];
            if (!empty($gads_label)) {
                $tag['parameter'][] = ['type' => 'TEMPLATE', 'key' => 'conversionLabel', 'value' => $gads_label];
            }
            $tag['firingTriggerId'] = [(string)$trigger_ids['conversion']];
            $tag['tagFiringOption'] = 'ONCE_PER_EVENT';
            $tags[] = $tag;
        }

        $container = [
            'exportFormatVersion' => 2,
            'exportTime' => $export_time,
            'containerVersion' => [
                'path' => 'accounts/' . $account_id . '/containers/' . $container_id . '/versions/0',
                'accountId' => $account_id,
                'containerId' => $container_id,
                'containerVersionId' => '0',
                'fingerprint' => $fingerprint,
                'container' => [
                    'path' => 'accounts/' . $account_id . '/containers/' . $container_id,
                    'accountId' => $account_id,
                    'containerId' => $container_id,
                    'name' => 'YESHUA - ' . $site_name,
                    'publicId' => 'GTM-YESHUA',
                    'usageContext' => ['WEB'],
                    'fingerprint' => $fingerprint,
                    'tagManagerUrl' => '',
                ],
                'tag' => $tags,
                'trigger' => $triggers,
                'variable' => $variables,
            ],
        ];

        return $container;
    }
}
