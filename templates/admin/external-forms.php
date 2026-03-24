<?php
/**
 * Template da pagina de Formularios Externos
 *
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$mappings = Yeshua_External_Forms::get_mappings();
$detected_plugins = Yeshua_External_Forms::get_detected_plugins();
$supported_plugins = Yeshua_External_Forms::get_supported_plugins();
$message_template = get_option('yeshua_external_forms_message', '');
$email_template = get_option('yeshua_external_forms_email_template', "Novo lead (formulario externo):\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}\nFormulario: {_formulario}\n\n{campos_extras}");
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('yeshua_external_forms'); ?>

    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-forms" style="color: #8B5CF6;"></span>
            <span class="yeshua-title"><?php _e('Formularios Externos', 'yeshua-conversoes'); ?></span>
        </div>
    </div>

    <!-- Plugins Detectados -->
    <div class="yeshua-card">
        <h2 class="yeshua-card-title">
            <span class="dashicons dashicons-plugins-checked"></span>
            <?php _e('Plugins de Formulario Detectados', 'yeshua-conversoes'); ?>
        </h2>

        <?php if (empty($detected_plugins)) : ?>
            <p style="color: #d63638;">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Nenhum plugin de formulario compativel detectado. Plugins suportados: Elementor Pro, Contact Form 7, WPForms, Gravity Forms.', 'yeshua-conversoes'); ?>
            </p>
        <?php else : ?>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($detected_plugins as $slug => $label) : ?>
                    <li><strong><?php echo esc_html($label); ?></strong> <span style="color: #00a32a;">&#10003;</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Mapeamentos -->
    <form method="post" action="" id="yeshua-external-forms-form">
        <?php wp_nonce_field('yeshua_save_external_forms', 'yeshua_external_forms_nonce'); ?>

        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-randomize"></span>
                <?php _e('Mapeamento de Formularios', 'yeshua-conversoes'); ?>
            </h2>

            <p class="description" style="margin-bottom: 16px;">
                <?php _e('Configure quais formularios devem ser capturados e como os campos mapeiam para Nome, E-mail e Telefone. Quando um formulario mapeado for enviado, o lead sera registrado automaticamente na API YESHUA.', 'yeshua-conversoes'); ?>
            </p>

            <div id="yeshua-mappings-list">
                <?php if (!empty($mappings)) : ?>
                    <?php foreach ($mappings as $index => $mapping) : ?>
                        <?php render_mapping_row($index, $mapping, $supported_plugins); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p style="margin-top: 16px;">
                <button type="button" id="yeshua-add-mapping" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt2" style="margin-top: 4px;"></span>
                    <?php _e('Adicionar Mapeamento', 'yeshua-conversoes'); ?>
                </button>
            </p>
        </div>

        <!-- Template de mensagem Evolution -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-whatsapp"></span>
                <?php _e('Mensagem WhatsApp (Evolution API)', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_external_forms_message"><?php _e('Template da Mensagem', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_external_forms_message"
                                  id="yeshua_external_forms_message"
                                  class="large-text"
                                  rows="5"
                                  placeholder="Olá {nome}! Recebemos seu contato via formulario. Em breve retornaremos."><?php echo esc_textarea($message_template); ?></textarea>
                        <p class="description">
                            <?php _e('Variaveis: {nome}, {email}, {whatsapp}, {_formulario}. Deixe vazio para nao enviar mensagem.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Template de e-mail -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Notificacao por E-mail', 'yeshua-conversoes'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_external_forms_email_template"><?php _e('Template do E-mail', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_external_forms_email_template"
                                  id="yeshua_external_forms_email_template"
                                  class="large-text"
                                  rows="6"><?php echo esc_textarea($email_template); ?></textarea>
                        <p class="description">
                            <?php _e('Variaveis: {nome}, {email}, {whatsapp}, {_formulario}, {campos_extras}. Deixe vazio para nao enviar e-mail.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Salvar Configuracoes', 'yeshua-conversoes')); ?>
    </form>
</div>

<!-- Template do Mapeamento (para JS clonar) -->
<script type="text/html" id="tmpl-yeshua-mapping-row">
    <?php render_mapping_row('__INDEX__', [], $supported_plugins); ?>
</script>

<script>
(function($) {
    var mappingIndex = <?php echo count($mappings); ?>;

    // Adicionar novo mapeamento
    $('#yeshua-add-mapping').on('click', function() {
        var template = document.getElementById('tmpl-yeshua-mapping-row').innerHTML;
        template = template.replace(/__INDEX__/g, mappingIndex);
        $('#yeshua-mappings-list').append(template);
        mappingIndex++;
    });

    // Remover mapeamento
    $(document).on('click', '.yeshua-remove-mapping', function() {
        if (confirm('<?php echo esc_js(__('Tem certeza que deseja remover este mapeamento?', 'yeshua-conversoes')); ?>')) {
            $(this).closest('.yeshua-mapping-row').remove();
        }
    });

    // Carregar campos quando plugin + form_id mudam
    $(document).on('change', '.yeshua-mapping-plugin, .yeshua-mapping-form-id', function() {
        var $row = $(this).closest('.yeshua-mapping-row');
        var plugin = $row.find('.yeshua-mapping-plugin').val();
        var formId = $row.find('.yeshua-mapping-form-id').val();

        if (!plugin || !formId) return;

        var $fieldsArea = $row.find('.yeshua-fields-area');
        $fieldsArea.html('<em><?php echo esc_js(__('Carregando campos...', 'yeshua-conversoes')); ?></em>');

        $.ajax({
            url: yeshuaAdmin.restUrl + 'external-forms/fields',
            method: 'GET',
            data: { plugin: plugin, form_id: formId },
            headers: { 'X-WP-Nonce': yeshuaAdmin.nonce },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    var idx = $row.data('index');
                    var currentNome = $row.find('.yeshua-field-nome').val();
                    var currentEmail = $row.find('.yeshua-field-email').val();
                    var currentTelefone = $row.find('.yeshua-field-telefone').val();

                    var html = '<p class="description" style="margin-bottom:8px;"><?php echo esc_js(__('Campos encontrados no formulario. Selecione o mapeamento:', 'yeshua-conversoes')); ?></p>';
                    html += '<table class="widefat striped" style="max-width:600px;">';
                    html += '<thead><tr><th><?php echo esc_js(__('Campo YESHUA', 'yeshua-conversoes')); ?></th><th><?php echo esc_js(__('Campo do Formulario', 'yeshua-conversoes')); ?></th></tr></thead><tbody>';

                    var yeshuaFields = [
                        { key: 'field_nome', label: '<?php echo esc_js(__('Nome', 'yeshua-conversoes')); ?>', current: currentNome },
                        { key: 'field_email', label: '<?php echo esc_js(__('E-mail', 'yeshua-conversoes')); ?>', current: currentEmail },
                        { key: 'field_telefone', label: '<?php echo esc_js(__('Telefone', 'yeshua-conversoes')); ?>', current: currentTelefone }
                    ];

                    yeshuaFields.forEach(function(yf) {
                        html += '<tr><td><strong>' + yf.label + '</strong></td><td>';
                        html += '<select name="yeshua_mappings[' + idx + '][' + yf.key + ']" class="yeshua-field-' + yf.key.replace('field_', '') + '">';
                        html += '<option value=""><?php echo esc_js(__('-- Nao mapear --', 'yeshua-conversoes')); ?></option>';
                        response.data.forEach(function(f) {
                            var selected = (f.id === yf.current) ? ' selected' : '';
                            html += '<option value="' + f.id + '"' + selected + '>' + f.label + ' (' + f.id + ')</option>';
                        });
                        html += '</select></td></tr>';
                    });

                    html += '</tbody></table>';
                    $fieldsArea.html(html);
                } else {
                    $fieldsArea.html('<em><?php echo esc_js(__('Nenhum campo encontrado. Para Elementor, informe o ID ou nome do formulario e mapeie os campos manualmente abaixo.', 'yeshua-conversoes')); ?></em>');
                }
            },
            error: function() {
                $fieldsArea.html('<em style="color:#d63638;"><?php echo esc_js(__('Erro ao carregar campos.', 'yeshua-conversoes')); ?></em>');
            }
        });
    });

    // Carregar formulários quando plugin muda
    $(document).on('change', '.yeshua-mapping-plugin', function() {
        var $row = $(this).closest('.yeshua-mapping-row');
        var plugin = $(this).val();
        var $formSelect = $row.find('.yeshua-form-select-area');
        var idx = $row.data('index');

        if (!plugin) {
            $formSelect.html('');
            return;
        }

        $formSelect.html('<em><?php echo esc_js(__('Carregando formularios...', 'yeshua-conversoes')); ?></em>');

        $.ajax({
            url: yeshuaAdmin.restUrl + 'external-forms/forms',
            method: 'GET',
            data: { plugin: plugin },
            headers: { 'X-WP-Nonce': yeshuaAdmin.nonce },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    var html = '<select class="yeshua-mapping-form-id regular-text" name="yeshua_mappings[' + idx + '][form_id]">';
                    html += '<option value=""><?php echo esc_js(__('-- Selecione --', 'yeshua-conversoes')); ?></option>';
                    response.data.forEach(function(f) {
                        html += '<option value="' + f.id + '">' + f.title + ' (ID: ' + f.id + ')</option>';
                    });
                    html += '</select>';
                    $formSelect.html(html);
                } else {
                    // Sem forms ou plugin sem registry (Elementor)
                    var html = '<input type="text" class="yeshua-mapping-form-id regular-text" name="yeshua_mappings[' + idx + '][form_id]" placeholder="<?php echo esc_js(__('ID ou nome do formulario', 'yeshua-conversoes')); ?>" value="">';
                    if (plugin === 'elementor') {
                        html += '<p class="description"><?php echo esc_js(__('No Elementor, use o ID do widget ou o nome do formulario definido em "Form Name".', 'yeshua-conversoes')); ?></p>';
                    }
                    $formSelect.html(html);
                }
            },
            error: function() {
                var html = '<input type="text" class="yeshua-mapping-form-id regular-text" name="yeshua_mappings[' + idx + '][form_id]" placeholder="<?php echo esc_js(__('ID do formulario', 'yeshua-conversoes')); ?>" value="">';
                $formSelect.html(html);
            }
        });
    });

})(jQuery);
</script>

<?php
/**
 * Renderiza uma linha de mapeamento
 */
function render_mapping_row($index, $mapping, $supported_plugins) {
    $enabled = $mapping['enabled'] ?? '1';
    $plugin = $mapping['plugin'] ?? '';
    $form_id = $mapping['form_id'] ?? '';
    $form_name = $mapping['form_name'] ?? '';
    $field_nome = $mapping['field_nome'] ?? '';
    $field_email = $mapping['field_email'] ?? '';
    $field_telefone = $mapping['field_telefone'] ?? '';
    ?>
    <div class="yeshua-mapping-row yeshua-card" data-index="<?php echo esc_attr($index); ?>" style="border-left: 4px solid #8B5CF6; margin-bottom: 16px; position: relative;">
        <button type="button" class="yeshua-remove-mapping" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: #d63638; cursor: pointer; font-size: 18px;" title="<?php esc_attr_e('Remover', 'yeshua-conversoes'); ?>">
            <span class="dashicons dashicons-dismiss"></span>
        </button>

        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row"><?php _e('Ativo', 'yeshua-conversoes'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="yeshua_mappings[<?php echo esc_attr($index); ?>][enabled]"
                               value="1"
                               <?php checked('1', $enabled); ?>>
                        <?php _e('Capturar leads deste formulario', 'yeshua-conversoes'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nome de Referencia', 'yeshua-conversoes'); ?></th>
                <td>
                    <input type="text"
                           name="yeshua_mappings[<?php echo esc_attr($index); ?>][form_name]"
                           value="<?php echo esc_attr($form_name); ?>"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('Ex: Formulario de Orcamento', 'yeshua-conversoes'); ?>">
                    <p class="description"><?php _e('Nome para identificar este formulario nos leads.', 'yeshua-conversoes'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Plugin', 'yeshua-conversoes'); ?></th>
                <td>
                    <select name="yeshua_mappings[<?php echo esc_attr($index); ?>][plugin]" class="yeshua-mapping-plugin regular-text">
                        <option value=""><?php _e('-- Selecione o Plugin --', 'yeshua-conversoes'); ?></option>
                        <?php foreach ($supported_plugins as $slug => $label) : ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($plugin, $slug); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Formulario', 'yeshua-conversoes'); ?></th>
                <td>
                    <div class="yeshua-form-select-area">
                        <?php if (!empty($form_id)) : ?>
                            <input type="text"
                                   class="yeshua-mapping-form-id regular-text"
                                   name="yeshua_mappings[<?php echo esc_attr($index); ?>][form_id]"
                                   value="<?php echo esc_attr($form_id); ?>"
                                   placeholder="<?php esc_attr_e('ID ou nome do formulario', 'yeshua-conversoes'); ?>">
                        <?php else : ?>
                            <input type="text"
                                   class="yeshua-mapping-form-id regular-text"
                                   name="yeshua_mappings[<?php echo esc_attr($index); ?>][form_id]"
                                   value=""
                                   placeholder="<?php esc_attr_e('Selecione o plugin primeiro', 'yeshua-conversoes'); ?>">
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Mapeamento de Campos', 'yeshua-conversoes'); ?></th>
                <td>
                    <div class="yeshua-fields-area">
                        <table class="widefat striped" style="max-width:600px;">
                            <thead>
                                <tr>
                                    <th><?php _e('Campo YESHUA', 'yeshua-conversoes'); ?></th>
                                    <th><?php _e('ID do Campo no Formulario', 'yeshua-conversoes'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?php _e('Nome', 'yeshua-conversoes'); ?></strong></td>
                                    <td>
                                        <input type="text"
                                               name="yeshua_mappings[<?php echo esc_attr($index); ?>][field_nome]"
                                               value="<?php echo esc_attr($field_nome); ?>"
                                               class="regular-text yeshua-field-nome"
                                               placeholder="<?php esc_attr_e('Ex: name, your-name, field_1', 'yeshua-conversoes'); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('E-mail', 'yeshua-conversoes'); ?></strong></td>
                                    <td>
                                        <input type="text"
                                               name="yeshua_mappings[<?php echo esc_attr($index); ?>][field_email]"
                                               value="<?php echo esc_attr($field_email); ?>"
                                               class="regular-text yeshua-field-email"
                                               placeholder="<?php esc_attr_e('Ex: email, your-email, field_2', 'yeshua-conversoes'); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Telefone', 'yeshua-conversoes'); ?></strong></td>
                                    <td>
                                        <input type="text"
                                               name="yeshua_mappings[<?php echo esc_attr($index); ?>][field_telefone]"
                                               value="<?php echo esc_attr($field_telefone); ?>"
                                               class="regular-text yeshua-field-telefone"
                                               placeholder="<?php esc_attr_e('Ex: phone, your-tel, field_3', 'yeshua-conversoes'); ?>">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php if (!empty($plugin) && !empty($form_id)) : ?>
                            <p class="description" style="margin-top: 8px;">
                                <?php _e('Altere o plugin ou ID do formulario acima para carregar os campos automaticamente.', 'yeshua-conversoes'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <?php
}
?>
