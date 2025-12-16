<?php
/**
 * Template da página Formulário Lead
 * 
 * @package Yeshua_Conversoes
 */

if (!defined('ABSPATH')) {
    exit;
}

$enabled = get_option('yeshua_form_lead_enabled', '1');
$title = get_option('yeshua_form_lead_title', 'Solicite um Orçamento');
$button_text = get_option('yeshua_form_lead_button_text', 'Enviar');
$extra_fields = get_option('yeshua_form_lead_extra_fields', []);
$message = get_option('yeshua_form_lead_message', "Novo lead recebido!\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}");
$email_template = get_option('yeshua_form_lead_email_template', "Novo lead capturado:\n\nNome: {nome}\nE-mail: {email}\nWhatsApp: {whatsapp}\n\n{campos_extras}");
$success_message = get_option('yeshua_form_lead_success_message', 'Obrigado! Entraremos em contato em breve.');
$redirect = get_option('yeshua_form_lead_redirect', '');

// Gatilhos avançados
$trigger_onload = get_option('yeshua_form_lead_trigger_onload', '0');
$trigger_delay = get_option('yeshua_form_lead_trigger_delay', '');
$trigger_inactivity = get_option('yeshua_form_lead_trigger_inactivity', '');
$trigger_scroll = get_option('yeshua_form_lead_trigger_scroll', '');
$trigger_exit_intent = get_option('yeshua_form_lead_trigger_exit_intent', '0');

// CSS Avançado
$custom_css = get_option('yeshua_form_lead_custom_css', '');
?>

<div class="wrap yeshua-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="yeshua-admin-header">
        <div class="yeshua-logo">
            <span class="dashicons dashicons-id-alt" style="color: #0073aa;"></span>
            <span class="yeshua-title"><?php _e('Formulário de Lead', 'yeshua-conversoes'); ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php" id="yeshua-form-lead-form">
        <?php settings_fields('yeshua_form_lead'); ?>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Configurações Gerais', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Habilitar Formulário', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label class="yeshua-toggle">
                            <input type="checkbox" 
                                   name="yeshua_form_lead_enabled" 
                                   id="yeshua_form_lead_enabled" 
                                   value="1" 
                                   <?php checked($enabled, '1'); ?>>
                            <span class="yeshua-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php _e('Ativa o formulário modal em elementos com id="formlead"', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_title"><?php _e('Título do Modal', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_form_lead_title" 
                               id="yeshua_form_lead_title" 
                               value="<?php echo esc_attr($title); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_button_text"><?php _e('Texto do Botão', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_form_lead_button_text" 
                               id="yeshua_form_lead_button_text" 
                               value="<?php echo esc_attr($button_text); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-forms"></span>
                <?php _e('Campos Extras', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description">
                <?php _e('Além dos campos obrigatórios (Nome, E-mail, WhatsApp), você pode adicionar campos personalizados.', 'yeshua-conversoes'); ?>
            </p>
            
            <div id="yeshua-extra-fields-list" class="yeshua-repeater">
                <?php if (!empty($extra_fields)) : ?>
                    <?php foreach ($extra_fields as $index => $field) : ?>
                        <div class="yeshua-repeater-item" data-index="<?php echo $index; ?>">
                            <div class="yeshua-repeater-row">
                                <div class="yeshua-field">
                                    <label><?php _e('Label', 'yeshua-conversoes'); ?></label>
                                    <input type="text" 
                                           name="yeshua_form_lead_extra_fields[<?php echo $index; ?>][label]" 
                                           value="<?php echo esc_attr($field['label'] ?? ''); ?>" 
                                           class="regular-text">
                                </div>
                                
                                <div class="yeshua-field">
                                    <label><?php _e('Tipo', 'yeshua-conversoes'); ?></label>
                                    <select name="yeshua_form_lead_extra_fields[<?php echo $index; ?>][type]">
                                        <option value="text" <?php selected($field['type'] ?? '', 'text'); ?>><?php _e('Texto', 'yeshua-conversoes'); ?></option>
                                        <option value="textarea" <?php selected($field['type'] ?? '', 'textarea'); ?>><?php _e('Área de Texto', 'yeshua-conversoes'); ?></option>
                                        <option value="select" <?php selected($field['type'] ?? '', 'select'); ?>><?php _e('Select', 'yeshua-conversoes'); ?></option>
                                        <option value="email" <?php selected($field['type'] ?? '', 'email'); ?>><?php _e('E-mail', 'yeshua-conversoes'); ?></option>
                                        <option value="tel" <?php selected($field['type'] ?? '', 'tel'); ?>><?php _e('Telefone', 'yeshua-conversoes'); ?></option>
                                        <option value="number" <?php selected($field['type'] ?? '', 'number'); ?>><?php _e('Número', 'yeshua-conversoes'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="yeshua-field">
                                    <label><?php _e('Obrigatório', 'yeshua-conversoes'); ?></label>
                                    <label class="yeshua-toggle yeshua-toggle-small">
                                        <input type="checkbox" 
                                               name="yeshua_form_lead_extra_fields[<?php echo $index; ?>][required]" 
                                               value="1" 
                                               <?php checked(!empty($field['required'])); ?>>
                                        <span class="yeshua-toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="yeshua-field yeshua-field-wide">
                                    <label><?php _e('Opções (para Select)', 'yeshua-conversoes'); ?></label>
                                    <input type="text" 
                                           name="yeshua_form_lead_extra_fields[<?php echo $index; ?>][options]" 
                                           value="<?php echo esc_attr($field['options'] ?? ''); ?>" 
                                           class="regular-text"
                                           placeholder="Opção 1, Opção 2, Opção 3">
                                </div>
                                
                                <button type="button" class="button yeshua-remove-item" title="<?php _e('Remover', 'yeshua-conversoes'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" id="yeshua-add-field" class="button button-secondary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Adicionar Campo', 'yeshua-conversoes'); ?>
            </button>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-format-chat"></span>
                <?php _e('Mensagem WhatsApp (Evolution API)', 'yeshua-conversoes'); ?>
            </h2>
            
            <div class="notice notice-info inline" style="margin: 0 0 15px 0;">
                <p>
                    <strong><?php _e('Como funciona:', 'yeshua-conversoes'); ?></strong> 
                    <?php _e('Quando o visitante preencher o formulário, a mensagem abaixo será enviada automaticamente para o número WhatsApp que ele informou, usando a instância configurada na Evolution API.', 'yeshua-conversoes'); ?>
                </p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_message"><?php _e('Mensagem para o Visitante', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_form_lead_message" 
                                  id="yeshua_form_lead_message" 
                                  rows="5" 
                                  class="large-text"><?php echo esc_textarea($message); ?></textarea>
                        <p class="description">
                            <?php _e('Placeholders: {saudacao}, {nome}, {email}, {whatsapp}, {campos_extras}, {data}, {hora}, {site}', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Notificação por E-mail', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_send_email"><?php _e('Enviar dados por E-mail?', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                         <input type="checkbox" name="yeshua_form_lead_send_email" id="yeshua_form_lead_send_email" value="yes" <?php checked('yes', get_option('yeshua_form_lead_send_email', 'yes')); ?>>
                        <p class="description">
                            <?php _e('Ative para enviar os dados do lead por e-mail.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
<tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_email_template"><?php _e('Template do E-mail', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_form_lead_email_template" 
                                  id="yeshua_form_lead_email_template" 
                                  rows="6" 
                                  class="large-text"><?php echo esc_textarea($email_template); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Após Envio', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_success_message"><?php _e('Mensagem de Sucesso', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="yeshua_form_lead_success_message" 
                               id="yeshua_form_lead_success_message" 
                               value="<?php echo esc_attr($success_message); ?>" 
                               class="large-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_redirect"><?php _e('URL de Redirecionamento', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="url" 
                               name="yeshua_form_lead_redirect" 
                               id="yeshua_form_lead_redirect" 
                               value="<?php echo esc_attr($redirect); ?>" 
                               class="regular-text"
                               placeholder="https://exemplo.com/obrigado">
                        <p class="description">
                            <?php _e('Opcional. Se preenchido, redireciona após exibir a mensagem de sucesso.', 'yeshua-conversoes'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Gatilhos Avançados -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-controls-play"></span>
                <?php _e('Gatilhos Avançados', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Configure quando o modal deve abrir automaticamente, além do clique em elementos.', 'yeshua-conversoes'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Abrir no Carregamento', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label class="yeshua-toggle">
                            <input type="checkbox" 
                                   name="yeshua_form_lead_trigger_onload" 
                                   value="1" 
                                   <?php checked($trigger_onload, '1'); ?>>
                            <span class="yeshua-toggle-slider"></span>
                        </label>
                        <p class="description"><?php _e('Abre o modal assim que a página carregar.', 'yeshua-conversoes'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_trigger_delay"><?php _e('Após Tempo na Página', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="yeshua_form_lead_trigger_delay" 
                               id="yeshua_form_lead_trigger_delay" 
                               value="<?php echo esc_attr($trigger_delay); ?>" 
                               class="small-text"
                               min="0"
                               placeholder="0"> <?php _e('segundos', 'yeshua-conversoes'); ?>
                        <p class="description"><?php _e('Abre o modal após X segundos na página. Deixe vazio para desativar.', 'yeshua-conversoes'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_trigger_inactivity"><?php _e('Após Inatividade', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="yeshua_form_lead_trigger_inactivity" 
                               id="yeshua_form_lead_trigger_inactivity" 
                               value="<?php echo esc_attr($trigger_inactivity); ?>" 
                               class="small-text"
                               min="0"
                               placeholder="0"> <?php _e('segundos', 'yeshua-conversoes'); ?>
                        <p class="description"><?php _e('Abre o modal após o usuário ficar inativo (sem mover mouse/teclado) por X segundos.', 'yeshua-conversoes'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_trigger_scroll"><?php _e('Após Scroll', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="yeshua_form_lead_trigger_scroll" 
                               id="yeshua_form_lead_trigger_scroll" 
                               value="<?php echo esc_attr($trigger_scroll); ?>" 
                               class="small-text"
                               min="0"
                               max="100"
                               placeholder="0"> <?php _e('% da página', 'yeshua-conversoes'); ?>
                        <p class="description"><?php _e('Abre o modal quando o usuário rolar X% da página. Ex: 50 para metade da página.', 'yeshua-conversoes'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Exit Intent (Intenção de Saída)', 'yeshua-conversoes'); ?></th>
                    <td>
                        <label class="yeshua-toggle">
                            <input type="checkbox" 
                                   name="yeshua_form_lead_trigger_exit_intent" 
                                   value="1" 
                                   <?php checked($trigger_exit_intent, '1'); ?>>
                            <span class="yeshua-toggle-slider"></span>
                        </label>
                        <p class="description"><?php _e('Abre o modal quando o usuário move o mouse para fora da janela (intenção de fechar/sair). Funciona apenas em desktop.', 'yeshua-conversoes'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="notice notice-warning inline" style="margin: 15px 0 0 0;">
                <p>
                    <strong><?php _e('Dica:', 'yeshua-conversoes'); ?></strong> 
                    <?php _e('O modal só abrirá automaticamente uma vez por sessão para não incomodar o visitante. Use com moderação!', 'yeshua-conversoes'); ?>
                </p>
            </div>
        </div>
        
        <!-- CSS Avançado -->
        <div class="yeshua-card">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-editor-code"></span>
                <?php _e('CSS Avançado', 'yeshua-conversoes'); ?>
            </h2>
            
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Personalize a aparência do modal com CSS customizado.', 'yeshua-conversoes'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="yeshua_form_lead_custom_css"><?php _e('CSS Customizado', 'yeshua-conversoes'); ?></label>
                    </th>
                    <td>
                        <textarea name="yeshua_form_lead_custom_css" 
                                  id="yeshua_form_lead_custom_css" 
                                  rows="10" 
                                  class="large-text code"
                                  placeholder="/* Seus estilos aqui */"><?php echo esc_textarea($custom_css); ?></textarea>
                    </td>
                </tr>
            </table>
            
            <h4 style="margin-top: 20px;"><?php _e('Seletores CSS Disponíveis:', 'yeshua-conversoes'); ?></h4>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
                <table class="widefat striped" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php _e('Seletor', 'yeshua-conversoes'); ?></th>
                            <th><?php _e('Elemento', 'yeshua-conversoes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>#yeshua-modal-lead</code></td><td><?php _e('Container principal do modal', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-overlay</code></td><td><?php _e('Fundo escuro/overlay', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-container</code></td><td><?php _e('Container do conteúdo centralizado', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-content</code></td><td><?php _e('Card branco do modal', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-close</code></td><td><?php _e('Botão de fechar (X)', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-header</code></td><td><?php _e('Cabeçalho (ícone + título)', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-icon</code></td><td><?php _e('Ícone circular', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-title</code></td><td><?php _e('Título do modal', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-modal-subtitle</code></td><td><?php _e('Subtítulo do modal', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-form</code></td><td><?php _e('Formulário', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-form-group</code></td><td><?php _e('Grupo de campo (label + input)', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-label</code></td><td><?php _e('Labels dos campos', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-input</code></td><td><?php _e('Campos de input', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-submit-btn</code></td><td><?php _e('Botão de enviar', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-submit-btn-lead</code></td><td><?php _e('Botão azul (Lead)', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-form-message</code></td><td><?php _e('Mensagem de sucesso/erro', 'yeshua-conversoes'); ?></td></tr>
                        <tr><td><code>.yeshua-recaptcha-container</code></td><td><?php _e('Container do reCAPTCHA', 'yeshua-conversoes'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="yeshua-card yeshua-card-info">
            <h2 class="yeshua-card-title">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Placeholders Disponíveis', 'yeshua-conversoes'); ?>
            </h2>
            
            <table class="widefat" style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th><?php _e('Placeholder', 'yeshua-conversoes'); ?></th>
                        <th><?php _e('Descrição', 'yeshua-conversoes'); ?></th>
                        <th><?php _e('Exemplo', 'yeshua-conversoes'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>{saudacao}</code></td><td><?php _e('Saudação dinâmica baseada no horário', 'yeshua-conversoes'); ?></td><td>Bom dia / Boa tarde / Boa noite</td></tr>
                    <tr><td><code>{nome}</code></td><td><?php _e('Nome do visitante', 'yeshua-conversoes'); ?></td><td>João Silva</td></tr>
                    <tr><td><code>{email}</code></td><td><?php _e('E-mail do visitante', 'yeshua-conversoes'); ?></td><td>joao@email.com</td></tr>
                    <tr><td><code>{whatsapp}</code></td><td><?php _e('WhatsApp do visitante', 'yeshua-conversoes'); ?></td><td>11999999999</td></tr>
                    <tr><td><code>{campos_extras}</code></td><td><?php _e('Campos extras formatados', 'yeshua-conversoes'); ?></td><td>Empresa: ACME</td></tr>
                    <tr><td><code>{data}</code></td><td><?php _e('Data atual', 'yeshua-conversoes'); ?></td><td><?php echo date('d/m/Y'); ?></td></tr>
                    <tr><td><code>{hora}</code></td><td><?php _e('Hora atual', 'yeshua-conversoes'); ?></td><td><?php echo date('H:i'); ?></td></tr>
                    <tr><td><code>{site}</code></td><td><?php _e('Nome do site', 'yeshua-conversoes'); ?></td><td><?php echo get_bloginfo('name'); ?></td></tr>
                    <tr><td><code>{url}</code></td><td><?php _e('URL do site', 'yeshua-conversoes'); ?></td><td><?php echo home_url(); ?></td></tr>
                </tbody>
            </table>
            
            <h4><?php _e('Como Usar o Formulário:', 'yeshua-conversoes'); ?></h4>
            <p><?php _e('Adicione o atributo <code>id="formlead"</code>, a classe <code>class="formlead"</code> ou o link <code>href="#formlead"</code> em qualquer elemento HTML para ativar o modal ao clicar.', 'yeshua-conversoes'); ?></p>
            
            <pre><code>&lt;!-- Por ID --&gt;
&lt;button id="formlead"&gt;Solicitar Orçamento&lt;/button&gt;

&lt;!-- Por classe --&gt;
&lt;a href="#" class="formlead"&gt;Solicite agora&lt;/a&gt;

&lt;div class="formlead"&gt;Clique aqui&lt;/div&gt;

&lt;!-- Por href (link com hash) --&gt;
&lt;a href="#formlead"&gt;Solicitar Orçamento&lt;/a&gt;</code></pre>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'yeshua-conversoes')); ?>
    </form>
    
    <!-- Seção de Testes -->
    <div class="yeshua-card" style="margin-top: 30px; border-left: 4px solid #0073aa;">
        <h2 class="yeshua-card-title">
            <span class="dashicons dashicons-welcome-view-site"></span>
            <?php _e('Testar Formulário e Integrações', 'yeshua-conversoes'); ?>
        </h2>
        
        <p class="description" style="margin-bottom: 20px;">
            <?php _e('Use esta seção para testar o modal do formulário e validar o funcionamento das integrações (Evolution API, YESHUA API, E-mail).', 'yeshua-conversoes'); ?>
        </p>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <!-- Preview do Modal -->
            <div style="flex: 1; min-width: 300px;">
                <h4><?php _e('1. Visualizar Modal', 'yeshua-conversoes'); ?></h4>
                <p class="description"><?php _e('Clique para abrir o modal como os visitantes verão.', 'yeshua-conversoes'); ?></p>
                <button type="button" id="yeshua-test-modal-lead" class="button button-secondary">
                    <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                    <?php _e('Abrir Modal Lead', 'yeshua-conversoes'); ?>
                </button>
            </div>
            
            <!-- Preview da Mensagem -->
            <div style="flex: 1; min-width: 300px;">
                <h4><?php _e('2. Preview da Mensagem', 'yeshua-conversoes'); ?></h4>
                <p class="description"><?php _e('Veja como a mensagem será processada com os placeholders.', 'yeshua-conversoes'); ?></p>
                <button type="button" id="yeshua-preview-message-lead" class="button button-secondary">
                    <span class="dashicons dashicons-editor-code" style="vertical-align: middle;"></span>
                    <?php _e('Preview Mensagem', 'yeshua-conversoes'); ?>
                </button>
                <div id="yeshua-preview-result-lead" style="margin-top: 10px; display: none;">
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; white-space: pre-wrap; font-family: monospace;"></div>
                </div>
            </div>
        </div>
        
        <hr style="margin: 25px 0;">
        
        <h4><?php _e('3. Testar Disparo Real', 'yeshua-conversoes'); ?></h4>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Preencha os dados abaixo e teste o disparo real para as APIs configuradas. A mensagem será enviada de verdade!', 'yeshua-conversoes'); ?>
        </p>
        
        <table class="form-table" style="margin-bottom: 0;">
            <tr>
                <th scope="row">
                    <label for="yeshua_test_nome_lead"><?php _e('Nome', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <input type="text" id="yeshua_test_nome_lead" class="regular-text" value="Teste Admin" placeholder="Nome para teste">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="yeshua_test_email_lead"><?php _e('E-mail', 'yeshua-conversoes'); ?></label>
                </th>
                <td>
                    <input type="email" id="yeshua_test_email_lead" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="E-mail para teste">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="yeshua_test_whatsapp_lead"><?php _e('WhatsApp', 'yeshua-conversoes'); ?> <span style="color: red;">*</span></label>
                </th>
                <td>
                    <input type="text" id="yeshua_test_whatsapp_lead" class="regular-text" placeholder="5511999999999" required>
                    <p class="description"><?php _e('Número que receberá a mensagem de teste (com DDI e DDD)', 'yeshua-conversoes'); ?></p>
                </td>
            </tr>
        </table>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
            <button type="button" id="yeshua-test-full-lead" class="button button-primary">
                <span class="dashicons dashicons-controls-play" style="vertical-align: middle;"></span>
                <?php _e('Testar Tudo', 'yeshua-conversoes'); ?>
            </button>
            <button type="button" id="yeshua-test-evolution-lead" class="button button-secondary">
                <span class="dashicons dashicons-whatsapp" style="vertical-align: middle;"></span>
                <?php _e('Apenas Evolution', 'yeshua-conversoes'); ?>
            </button>
            <button type="button" id="yeshua-test-api-lead" class="button button-secondary">
                <span class="dashicons dashicons-cloud" style="vertical-align: middle;"></span>
                <?php _e('Apenas YESHUA API', 'yeshua-conversoes'); ?>
            </button>
            <button type="button" id="yeshua-test-email-lead" class="button button-secondary">
                <span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span>
                <?php _e('Apenas E-mail', 'yeshua-conversoes'); ?>
            </button>
        </div>
        
        <!-- Resultado dos Testes -->
        <div id="yeshua-test-results-lead" style="margin-top: 20px; display: none;">
            <h4><?php _e('Resultado dos Testes:', 'yeshua-conversoes'); ?></h4>
            <div id="yeshua-test-results-content-lead"></div>
        </div>
    </div>
    
    <script>
    (function($) {
        'use strict';
        
        const restUrl = '<?php echo esc_url(rest_url('yeshua/v1/')); ?>';
        const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
        
        // Verificar configuração do reCAPTCHA
        const recaptchaConfig = typeof yeshuaForms !== 'undefined' ? yeshuaForms.recaptcha : null;
        const isV2Checkbox = recaptchaConfig && recaptchaConfig.enabled && recaptchaConfig.version === 'v2_checkbox';
        
        // Abrir modal de teste usando a API do forms.js
        $('#yeshua-test-modal-lead').on('click', function() {
            if (typeof window.yeshuaFormsAPI !== 'undefined' && window.yeshuaFormsAPI.openModal) {
                window.yeshuaFormsAPI.openModal('yeshua-modal-lead');
            } else {
                // Fallback se API não disponível
                const modal = document.getElementById('yeshua-modal-lead');
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } else {
                    alert('<?php _e('Modal não encontrado. Verifique se o formulário está habilitado.', 'yeshua-conversoes'); ?>');
                }
            }
        });
        
        // Preview da mensagem
        $('#yeshua-preview-message-lead').on('click', function() {
            const $btn = $(this);
            const $result = $('#yeshua-preview-result-lead');
            const template = $('#yeshua_form_lead_message').val();
            
            $btn.prop('disabled', true).text('<?php _e('Processando...', 'yeshua-conversoes'); ?>');
            
            $.ajax({
                url: restUrl + 'preview-message',
                method: 'POST',
                headers: { 'X-WP-Nonce': nonce },
                contentType: 'application/json',
                data: JSON.stringify({
                    template: template,
                    nome: 'João Silva',
                    email: 'joao@exemplo.com',
                    whatsapp: '11999999999',
                    campos_extras: { 'Empresa': 'ACME', 'Cargo': 'Gerente' }
                }),
                success: function(response) {
                    $result.show().find('div').html(
                        '<strong><?php _e('Saudação atual:', 'yeshua-conversoes'); ?></strong> ' + response.saudacao_atual + ' (<?php _e('Hora:', 'yeshua-conversoes'); ?> ' + response.hora_atual + ')\n\n' +
                        '<strong><?php _e('Mensagem processada:', 'yeshua-conversoes'); ?></strong>\n' + response.processed
                    );
                },
                error: function(xhr) {
                    alert('<?php _e('Erro ao processar preview', 'yeshua-conversoes'); ?>');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-editor-code" style="vertical-align: middle;"></span> <?php _e('Preview Mensagem', 'yeshua-conversoes'); ?>');
                }
            });
        });
        
        // Função para executar testes
        function runTestLead(testMode) {
            const whatsapp = $('#yeshua_test_whatsapp_lead').val().replace(/\D/g, '');
            
            if (!whatsapp) {
                alert('<?php _e('Preencha o número de WhatsApp para teste', 'yeshua-conversoes'); ?>');
                return;
            }
            
            const $results = $('#yeshua-test-results-lead');
            const $content = $('#yeshua-test-results-content-lead');
            
            $results.show();
            $content.html('<p><span class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></span><?php _e('Executando testes...', 'yeshua-conversoes'); ?></p>');
            
            $.ajax({
                url: restUrl + 'test-form',
                method: 'POST',
                headers: { 'X-WP-Nonce': nonce },
                contentType: 'application/json',
                data: JSON.stringify({
                    form_type: 'lead',
                    test_mode: testMode,
                    nome: $('#yeshua_test_nome_lead').val(),
                    email: $('#yeshua_test_email_lead').val(),
                    whatsapp: whatsapp
                }),
                success: function(response) {
                    let html = '';
                    
                    if (response.tests) {
                        for (const [key, test] of Object.entries(response.tests)) {
                            const icon = test.success ? '✅' : '❌';
                            const statusClass = test.success ? 'notice-success' : 'notice-error';
                            
                            html += '<div class="notice ' + statusClass + '" style="padding: 10px; margin: 5px 0;">';
                            html += '<strong>' + icon + ' ' + test.name + '</strong><br>';
                            html += '<span>' + test.message + '</span>';
                            
                            if (test.sent_message) {
                                html += '<br><br><strong><?php _e('Mensagem enviada:', 'yeshua-conversoes'); ?></strong><br>';
                                html += '<code style="display: block; padding: 10px; background: #f5f5f5; margin-top: 5px; white-space: pre-wrap;">' + test.sent_message + '</code>';
                            }
                            
                            html += '</div>';
                        }
                    }
                    
                    $content.html(html || '<p><?php _e('Nenhum resultado', 'yeshua-conversoes'); ?></p>');
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || '<?php _e('Erro desconhecido', 'yeshua-conversoes'); ?>';
                    $content.html('<div class="notice notice-error" style="padding: 10px;">❌ ' + error + '</div>');
                }
            });
        }
        
        // Botões de teste
        $('#yeshua-test-full-lead').on('click', function() { runTestLead('full'); });
        $('#yeshua-test-evolution-lead').on('click', function() { runTestLead('evolution_only'); });
        $('#yeshua-test-api-lead').on('click', function() { runTestLead('api_only'); });
        $('#yeshua-test-email-lead').on('click', function() { runTestLead('email_only'); });
        
    })(jQuery);
    </script>
    
    <!-- Template para novo campo -->
    <script type="text/template" id="yeshua-field-template">
        <div class="yeshua-repeater-item" data-index="{{index}}">
            <div class="yeshua-repeater-row">
                <div class="yeshua-field">
                    <label><?php _e('Label', 'yeshua-conversoes'); ?></label>
                    <input type="text" 
                           name="yeshua_form_lead_extra_fields[{{index}}][label]" 
                           class="regular-text">
                </div>
                
                <div class="yeshua-field">
                    <label><?php _e('Tipo', 'yeshua-conversoes'); ?></label>
                    <select name="yeshua_form_lead_extra_fields[{{index}}][type]">
                        <option value="text"><?php _e('Texto', 'yeshua-conversoes'); ?></option>
                        <option value="textarea"><?php _e('Área de Texto', 'yeshua-conversoes'); ?></option>
                        <option value="select"><?php _e('Select', 'yeshua-conversoes'); ?></option>
                        <option value="email"><?php _e('E-mail', 'yeshua-conversoes'); ?></option>
                        <option value="tel"><?php _e('Telefone', 'yeshua-conversoes'); ?></option>
                        <option value="number"><?php _e('Número', 'yeshua-conversoes'); ?></option>
                    </select>
                </div>
                
                <div class="yeshua-field">
                    <label><?php _e('Obrigatório', 'yeshua-conversoes'); ?></label>
                    <label class="yeshua-toggle yeshua-toggle-small">
                        <input type="checkbox" 
                               name="yeshua_form_lead_extra_fields[{{index}}][required]" 
                               value="1">
                        <span class="yeshua-toggle-slider"></span>
                    </label>
                </div>
                
                <div class="yeshua-field yeshua-field-wide">
                    <label><?php _e('Opções (para Select)', 'yeshua-conversoes'); ?></label>
                    <input type="text" 
                           name="yeshua_form_lead_extra_fields[{{index}}][options]" 
                           class="regular-text"
                           placeholder="Opção 1, Opção 2, Opção 3">
                </div>
                
                <button type="button" class="button yeshua-remove-item" title="<?php _e('Remover', 'yeshua-conversoes'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
    </script>
</div>



