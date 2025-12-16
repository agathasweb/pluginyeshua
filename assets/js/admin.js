/**
 * YESHUA Conversões - Admin JavaScript
 */

(function($) {
    'use strict';

    // Fallback to avoid ReferenceErrors if the localized object is missing
    const adminConfig = window.yeshuaAdmin || {
        ajaxUrl: '',
        restUrl: '',
        nonce: '',
        i18n: {
            validating: 'Validando...',
            valid: 'API válida!',
            invalid: 'API inválida!',
            error: 'Erro na validação',
            testing: 'Testando...',
            testSuccess: 'Teste realizado com sucesso!',
            testFailed: 'Teste falhou!',
            confirm_delete: 'Tem certeza que deseja remover?',
        },
    };

    const hasRestConfig = () => Boolean(adminConfig.restUrl && adminConfig.nonce);

    // API Validation
    $('#yeshua-validate-api').on('click', function() {
        const $btn = $(this);
        const $status = $('#yeshua-api-status');
        const apiKey = $('#yeshua_api_key').val();

        if (!apiKey) {
            $status.removeClass('status-success status-info').addClass('status-error')
                   .text(adminConfig.i18n.invalid).show();
            return;
        }

        if (!hasRestConfig()) {
            $status.removeClass('status-success status-info').addClass('status-error')
                   .text('Configuração do plugin não carregada. Recarregue a página.').show();
            return;
        }

        $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $status.removeClass('status-success status-error').addClass('status-info')
               .text(adminConfig.i18n.validating).show();

        $.ajax({
            url: adminConfig.restUrl + 'validate-api',
            method: 'POST',
            headers: {
                'X-WP-Nonce': adminConfig.nonce
            },
            data: JSON.stringify({ api_key: apiKey }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $status.removeClass('status-info status-error').addClass('status-success')
                           .text(adminConfig.i18n.valid);
                    
                    // Populate websites select
                    const $select = $('#yeshua_website_id');
                    const currentValue = $select.val();
                    $select.find('option:not(:first)').remove();
                    
                    if (response.websites && response.websites.length > 0) {
                        response.websites.forEach(function(website) {
                            $select.append(
                                $('<option></option>')
                                    .val(website.id)
                                    .text(website.nome + ' (' + website.dominio + ')')
                                    .prop('selected', website.id == currentValue)
                            );
                        });
                    }
                } else {
                    $status.removeClass('status-info status-success').addClass('status-error')
                           .text(response.message || adminConfig.i18n.invalid);
                }
            },
            error: function() {
                $status.removeClass('status-info status-success').addClass('status-error')
                       .text(adminConfig.i18n.error);
            },
            complete: function() {
                $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-yes-alt');
            }
        });
    });

    // Fetch Evolution Instances
    $('#yeshua-fetch-instances').on('click', function() {
        const $btn = $(this);
        const url = $('#yeshua_evolution_url').val();
        const apiKey = $('#yeshua_evolution_api_key').val();

        if (!url || !apiKey) {
            alert('Preencha a URL e API Key primeiro');
            return;
        }

        if (!hasRestConfig()) {
            alert('Configuração do plugin não carregada. Recarregue a página.');
            return;
        }

        $btn.prop('disabled', true).find('.dashicons').addClass('spin');

        $.ajax({
            url: adminConfig.restUrl + 'evolution-instances',
            method: 'GET',
            headers: {
                'X-WP-Nonce': adminConfig.nonce
            },
            data: { url: url, api_key: apiKey },
            success: function(response) {
                const $select = $('#yeshua_evolution_instance');
                const currentValue = $select.val();
                $select.find('option:not(:first)').remove();

                if (response.instances && response.instances.length > 0) {
                    response.instances.forEach(function(instance) {
                        const name = instance.instanceName || instance.name || instance;
                        $select.append(
                            $('<option></option>')
                                .val(name)
                                .text(name)
                                .prop('selected', name === currentValue)
                        );
                    });
                }
            },
            error: function(xhr) {
                console.error('Error fetching instances:', xhr);
                alert('Erro ao buscar instâncias. Verifique as credenciais.');
            },
            complete: function() {
                $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            }
        });
    });

    // Toggle instance mode (select/manual)
    $('#yeshua-toggle-instance-mode').on('click', function() {
        const $select = $('#yeshua_evolution_instance');
        const $manual = $('#yeshua_evolution_instance_manual');

        if ($select.is(':visible')) {
            $select.hide();
            $manual.show().val($select.val()).attr('name', 'yeshua_evolution_instance');
            $select.removeAttr('name');
        } else {
            $manual.hide();
            $select.show().attr('name', 'yeshua_evolution_instance');
            $manual.removeAttr('name');
        }
    });

    // Test Evolution Connection
    $('#yeshua-test-evolution').on('click', function() {
        const $btn = $(this);
        const $status = $('#yeshua-evolution-status');

        if (!hasRestConfig()) {
            $status.removeClass('status-success status-error').addClass('status-error')
                   .text('Configuração do plugin não carregada. Recarregue a página.').show();
            return;
        }

        $btn.prop('disabled', true);
        $status.removeClass('status-success status-error').addClass('status-info')
               .text(adminConfig.i18n.testing).show();

        $.ajax({
            url: adminConfig.restUrl + 'test-evolution',
            method: 'POST',
            headers: {
                'X-WP-Nonce': adminConfig.nonce
            },
            success: function(response) {
                if (response.success || response.state === 'open') {
                    $status.removeClass('status-info status-error').addClass('status-success')
                           .text(adminConfig.i18n.testSuccess + ' - ' + (response.state || 'Conectado'));
                } else {
                    $status.removeClass('status-info status-success').addClass('status-error')
                           .text(response.message || adminConfig.i18n.testFailed);
                }
            },
            error: function() {
                $status.removeClass('status-info status-success').addClass('status-error')
                       .text(adminConfig.i18n.testFailed);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Test SMTP
    $('#yeshua-test-smtp').on('click', function() {
        const $btn = $(this);
        const $status = $('#yeshua-smtp-status');
        const email = $('#yeshua-test-email-address').val();

        if (!email) {
            $status.removeClass('status-success status-info').addClass('status-error')
                   .text('Informe um e-mail para teste').show();
            return;
        }

        if (!hasRestConfig()) {
            $status.removeClass('status-success status-info').addClass('status-error')
                   .text('Configuração do plugin não carregada. Recarregue a página.').show();
            return;
        }

        $btn.prop('disabled', true);
        $status.removeClass('status-success status-error').addClass('status-info')
               .text(adminConfig.i18n.testing).show();

        $.ajax({
            url: adminConfig.restUrl + 'test-smtp',
            method: 'POST',
            headers: {
                'X-WP-Nonce': adminConfig.nonce
            },
            data: JSON.stringify({ email: email }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $status.removeClass('status-info status-error').addClass('status-success')
                           .text(response.message || adminConfig.i18n.testSuccess);
                } else {
                    $status.removeClass('status-info status-success').addClass('status-error')
                           .text(response.message || adminConfig.i18n.testFailed);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                $status.removeClass('status-info status-success').addClass('status-error')
                       .text(response.message || adminConfig.i18n.testFailed);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ReCaptcha version toggle
    $('#yeshua_recaptcha_version').on('change', function() {
        const version = $(this).val();
        $('#yeshua-threshold-row').toggle(version === 'v3');
    });

    // Threshold slider
    $('#yeshua_recaptcha_threshold').on('input', function() {
        $('#yeshua-threshold-value').text($(this).val());
    });

    // Repeater - Add Conversion
    let conversionIndex = $('.yeshua-repeater-item[data-index]').length;
    
    $('#yeshua-add-conversion').on('click', function() {
        const template = $('#yeshua-conversion-template').html();
        const html = template.replace(/\{\{index\}\}/g, conversionIndex);
        $('#yeshua-conversions-list').append(html);
        conversionIndex++;
    });

    // Repeater - Add Field
    let fieldIndex = $('#yeshua-extra-fields-list .yeshua-repeater-item').length;
    
    $('#yeshua-add-field').on('click', function() {
        const template = $('#yeshua-field-template').html();
        const html = template.replace(/\{\{index\}\}/g, fieldIndex);
        $('#yeshua-extra-fields-list').append(html);
        fieldIndex++;
    });

    // Repeater - Remove Item
    $(document).on('click', '.yeshua-remove-item', function() {
        if (confirm(adminConfig.i18n.confirm_delete)) {
            $(this).closest('.yeshua-repeater-item').remove();
        }
    });

    // Media uploader for logo fields
    $(document).ready(function() {
        $(document).on('click', '.yeshua-upload-image', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Aguarda wp.media estar disponível
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                console.error('wp.media não está disponível');
                alert('Biblioteca de mídia não carregada. Aguarde alguns segundos e tente novamente.');
                return;
            }

            const target = $(this).data('target');
            const preview = $(this).data('preview');
            const $input = $(target);
            const $preview = $(preview);

            // Cria o frame de mídia
            const frame = wp.media({
                title: 'Selecionar imagem',
                button: {
                    text: 'Usar esta imagem'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });

            // Quando uma imagem é selecionada
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                if (attachment && attachment.url) {
                    $input.val(attachment.url);
                    if ($preview.length) {
                        $preview.html('<img src="' + attachment.url + '" alt="Preview" style="max-width: 140px; height: auto; border-radius: 8px;">');
                    }
                }
            });

            // Abre o frame
            frame.open();
        });
    });

    $(document).on('click', '.yeshua-remove-image', function(e) {
        e.preventDefault();

        const target = $(this).data('target');
        const preview = $(this).data('preview');

        $(target).val('');
        $(preview).empty();
    });

    // Spin animation for dashicons
    $('<style>')
        .text('.dashicons.spin { animation: dashicons-spin 1s linear infinite; } @keyframes dashicons-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);
