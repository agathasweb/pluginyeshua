/**
 * YESHUA Conversões - Forms JavaScript
 */

(function() {
    'use strict';

    // Will be filled when the localized config is available
    let config = window.yeshuaForms;

    /**
     * Attach click handler to element
     * @param {HTMLElement} el - Element to attach handler
     * @param {string} modalId - Modal ID to open
     */
    function attachModalTrigger(el, modalId) {
        // Check if already has handler (prevent duplicates)
        if (el.dataset.yeshuaModalAttached === 'true') {
            return;
        }
        
        el.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openModal(modalId);
        });
        el.style.cursor = 'pointer';
        el.dataset.yeshuaModalAttached = 'true';
    }

    /**
     * Initialize modal triggers
     */
    function initModalTriggers() {
        // WhatsApp form trigger by ID
        const whatsappTrigger = document.getElementById('formwhatsapp');
        if (whatsappTrigger && config.forms.whatsapp.enabled) {
            attachModalTrigger(whatsappTrigger, 'yeshua-modal-whatsapp');
        }

        // Lead form trigger by ID
        const leadTrigger = document.getElementById('formlead');
        if (leadTrigger && config.forms.lead.enabled) {
            attachModalTrigger(leadTrigger, 'yeshua-modal-lead');
        }

        // Also support class-based triggers for multiple elements
        document.querySelectorAll('.formwhatsapp').forEach(function(el) {
            if (config.forms.whatsapp.enabled) {
                attachModalTrigger(el, 'yeshua-modal-whatsapp');
            }
        });

        document.querySelectorAll('.formlead').forEach(function(el) {
            if (config.forms.lead.enabled) {
                attachModalTrigger(el, 'yeshua-modal-lead');
            }
        });

        // Support for links with href="#formwhatsapp" or href="#formlead"
        document.querySelectorAll('a[href="#formwhatsapp"], a[href*="#formwhatsapp"]').forEach(function(el) {
            if (config.forms.whatsapp.enabled) {
                attachModalTrigger(el, 'yeshua-modal-whatsapp');
            }
        });

        document.querySelectorAll('a[href="#formlead"], a[href*="#formlead"]').forEach(function(el) {
            if (config.forms.lead.enabled) {
                attachModalTrigger(el, 'yeshua-modal-lead');
            }
        });

        // Floating WhatsApp button
        const floatingBtn = document.getElementById('yeshua-whatsapp-fab');
        if (floatingBtn && config.forms.whatsapp && config.forms.whatsapp.enabled) {
            floatingBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const modal = document.getElementById('yeshua-modal-whatsapp');
                if (modal) {
                    openModal('yeshua-modal-whatsapp');
                } else {
                    console.warn('Modal WhatsApp não encontrado');
                }
            });
        }
    }

    /**
     * Open modal by ID
     * @param {string} modalId - Modal element ID
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.body.classList.add('yeshua-modal-open');

        // Focus first input
        setTimeout(function() {
            const firstInput = modal.querySelector('input:not([type="hidden"])');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);

        // Execute reCAPTCHA v3 if enabled
        if (config.recaptcha.enabled && config.recaptcha.version === 'v3') {
            const formType = modalId.includes('whatsapp') ? 'whatsapp' : 'lead';
            if (typeof window.yeshuaExecuteRecaptcha === 'function') {
                window.yeshuaExecuteRecaptcha(formType, formType);
            }
        }
    }

    /**
     * Close modal by ID
     * @param {string} modalId - Modal element ID
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.style.display = 'none';
        document.body.style.overflow = '';

        const hasOpenModal = document.querySelector('.yeshua-modal[style*="flex"]');
        if (!hasOpenModal) {
            document.body.classList.remove('yeshua-modal-open');
        }

        // Reset form
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            form.querySelectorAll('.yeshua-input-error').forEach(function(el) {
                el.classList.remove('yeshua-input-error');
            });
            form.querySelectorAll('.yeshua-error-message').forEach(function(el) {
                el.textContent = '';
            });
            const message = form.querySelector('.yeshua-form-message');
            if (message) {
                message.style.display = 'none';
            }
        }

        // Reset reCAPTCHA
        if (typeof grecaptcha !== 'undefined' && config.recaptcha.version.startsWith('v2')) {
            try {
                grecaptcha.reset();
            } catch (e) {}
        }
    }

    /**
     * Initialize modal close handlers
     */
    function initModalClose() {
        // Close button click
        document.querySelectorAll('.yeshua-modal-close').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = btn.closest('.yeshua-modal');
                if (modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Overlay click
        document.querySelectorAll('.yeshua-modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function() {
                const modal = overlay.closest('.yeshua-modal');
                if (modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.yeshua-modal[style*="flex"]');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        });
    }

    /**
     * Validate form field
     * @param {HTMLElement} field - Form field element
     * @returns {boolean} - Is valid
     */
    function validateField(field) {
        const value = field.value.trim();
        const isRequired = field.hasAttribute('required');
        const type = field.type;
        const errorEl = field.parentElement.querySelector('.yeshua-error-message');
        let isValid = true;
        let errorMessage = '';

        // Required check
        if (isRequired && !value) {
            isValid = false;
            errorMessage = config.i18n.required;
        }

        // Email validation
        if (isValid && value && type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = config.i18n.invalidEmail;
            }
        }

        // Phone validation
        if (isValid && value && field.classList.contains('yeshua-phone-mask')) {
            const digits = value.replace(/\D/g, '');
            if (digits.length < 10 || digits.length > 11) {
                isValid = false;
                errorMessage = config.i18n.invalidPhone;
            }
        }

        // Update UI
        if (isValid) {
            field.classList.remove('yeshua-input-error');
            if (errorEl) errorEl.textContent = '';
        } else {
            field.classList.add('yeshua-input-error');
            if (errorEl) errorEl.textContent = errorMessage;
        }

        return isValid;
    }

    /**
     * Validate entire form
     * @param {HTMLFormElement} form - Form element
     * @returns {boolean} - Is valid
     */
    function validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('.yeshua-input, .yeshua-textarea, .yeshua-select');

        fields.forEach(function(field) {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        // Check reCAPTCHA for v2
        if (isValid && config.recaptcha.enabled && config.recaptcha.version.startsWith('v2')) {
            if (!window.yeshuaRecaptchaToken) {
                isValid = false;
                showFormMessage(form, config.i18n.recaptchaRequired, 'error');
            }
        }

        return isValid;
    }

    /**
     * Show form message
     * @param {HTMLFormElement} form - Form element
     * @param {string} message - Message text
     * @param {string} type - Message type (success, error)
     */
    function showFormMessage(form, message, type) {
        const messageEl = form.querySelector('.yeshua-form-message');
        if (!messageEl) return;

        messageEl.textContent = message;
        messageEl.className = 'yeshua-form-message yeshua-message-' + type;
        messageEl.style.display = 'block';
    }

    /**
     * Set form loading state
     * @param {HTMLFormElement} form - Form element
     * @param {boolean} isLoading - Loading state
     */
    function setFormLoading(form, isLoading) {
        const submitBtn = form.querySelector('.yeshua-submit-btn');
        const btnText = submitBtn.querySelector('.yeshua-btn-text');
        const btnLoading = submitBtn.querySelector('.yeshua-btn-loading');

        if (isLoading) {
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';
        } else {
            submitBtn.disabled = false;
            btnText.style.display = '';
            btnLoading.style.display = 'none';
        }
    }

    /**
     * Get UTM parameters from URL
     * @returns {Object} UTM parameters
     */
    function getUtmParams() {
        const params = new URLSearchParams(window.location.search);
        const utm = {};
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function(key) {
            const value = params.get(key);
            if (value) {
                utm[key] = value;
            }
        });
        return utm;
    }

    /**
     * Read a cookie value by name
     * @param {string} name - Cookie name
     * @returns {string} Cookie value or empty string
     */
    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return '';
    }

    /**
     * Get tracking parameters (gclid, fbclid, _fbp, _fbc) from URL/cookies.
     * Persists to sessionStorage so data survives page navigation.
     * @returns {Object} Tracking parameters
     */
    function getTrackingData() {
        var stored = sessionStorage.getItem('yeshua_tracking');
        if (stored) {
            try { return JSON.parse(stored); } catch (e) {}
        }

        var params = new URLSearchParams(window.location.search);
        var tracking = {};

        var gclid = params.get('gclid');
        if (gclid) tracking.gclid = gclid;

        var fbclid = params.get('fbclid');
        if (fbclid) tracking.fbclid = fbclid;

        var fbp = getCookie('_fbp');
        if (fbp) tracking.fbp = fbp;

        var fbc = getCookie('_fbc');
        if (!fbc && fbclid) {
            fbc = 'fb.1.' + Date.now() + '.' + fbclid;
        }
        if (fbc) tracking.fbc = fbc;

        if (Object.keys(tracking).length > 0) {
            sessionStorage.setItem('yeshua_tracking', JSON.stringify(tracking));
        }

        return tracking;
    }

    /**
     * Submit form via AJAX
     * @param {HTMLFormElement} form - Form element
     */
    async function submitForm(form) {
        if (!validateForm(form)) {
            return;
        }

        setFormLoading(form, true);

        // Collect form data
        const formData = new FormData(form);
        const data = {};

        formData.forEach(function(value, key) {
            // Handle nested keys like campos_extras[field_name]
            if (key.includes('[')) {
                const matches = key.match(/^([^\[]+)\[([^\]]+)\]$/);
                if (matches) {
                    const parent = matches[1];
                    const child = matches[2];
                    if (!data[parent]) {
                        data[parent] = {};
                    }
                    data[parent][child] = value;
                    return;
                }
            }
            data[key] = value;
        });

        // Add reCAPTCHA token
        if (config.recaptcha.enabled) {
            if (config.recaptcha.version === 'v3') {
                // Get fresh token for v3
                if (typeof window.yeshuaExecuteRecaptcha === 'function') {
                    try {
                        const token = await window.yeshuaExecuteRecaptcha(data.form_type, data.form_type);
                        data.recaptcha_token = token;
                    } catch (e) {
                        console.error('reCAPTCHA error:', e);
                    }
                }
            } else {
                data.recaptcha_token = window.yeshuaRecaptchaToken || '';
            }
        }

        // Add UTM parameters
        const utmParams = getUtmParams();
        Object.assign(data, utmParams);

        // Add tracking parameters (gclid, fbclid, fbp, fbc)
        const trackingData = getTrackingData();
        Object.assign(data, trackingData);

        // Submit to API
        try {
            const response = await fetch(config.restUrl + 'submit-form', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showFormMessage(form, result.message, 'success');

                // Fire GTM/GA4/Ads conversion events
                fireConversionEvents(data.form_type || 'lead', data);

                // Handle redirect
                if (result.redirect) {
                    setTimeout(function() {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    // Close modal after delay
                    setTimeout(function() {
                        const modal = form.closest('.yeshua-modal');
                        if (modal) {
                            closeModal(modal.id);
                        }
                    }, 2500);
                }
            } else {
                showFormMessage(form, result.message || config.i18n.error, 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showFormMessage(form, config.i18n.error, 'error');
        } finally {
            setFormLoading(form, false);
        }
    }

    /**
     * Hash a string with SHA-256 for Enhanced Conversions
     * @param {string} value - Value to hash
     * @returns {Promise<string>} - Hex-encoded SHA-256 hash
     */
    async function sha256Hash(value) {
        if (!value || typeof crypto === 'undefined' || !crypto.subtle) return '';
        var normalized = value.trim().toLowerCase();
        if (!normalized) return '';
        var encoder = new TextEncoder();
        var data = encoder.encode(normalized);
        var hashBuffer = await crypto.subtle.digest('SHA-256', data);
        var hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(function(b) { return b.toString(16).padStart(2, '0'); }).join('');
    }

    /**
     * Build Enhanced Conversions user data (hashed)
     * @param {Object} formData - Form data with email, whatsapp/telefone, nome
     * @returns {Promise<Object>} - Hashed user data for Enhanced Conversions
     */
    async function buildEnhancedConversionsData(formData) {
        var userData = {};
        var email = formData.email || '';
        var phone = formData.whatsapp || formData.telefone || '';
        var nome = formData.nome || '';

        if (email) {
            userData.sha256_email_address = await sha256Hash(email);
        }
        if (phone) {
            // Normalize: remove non-digits, add country code
            var digits = phone.replace(/\D/g, '');
            if (digits.length <= 11) digits = '55' + digits;
            userData.sha256_phone_number = await sha256Hash('+' + digits);
        }
        if (nome) {
            var parts = nome.trim().split(/\s+/);
            if (parts.length >= 1) {
                userData.sha256_first_name = await sha256Hash(parts[0]);
            }
            if (parts.length >= 2) {
                userData.sha256_last_name = await sha256Hash(parts[parts.length - 1]);
            }
        }
        return userData;
    }

    /**
     * Fire conversion events on GTM/GA4/Google Ads
     * @param {string} formType - 'whatsapp' or 'lead'
     * @param {Object} formData - Submitted form data
     */
    async function fireConversionEvents(formType, formData) {
        var gtm = config.gtm;
        if (!gtm) return;

        var eventData = {
            event_category: 'form',
            event_label: formType,
            form_type: formType,
            page_path: window.location.pathname
        };

        // Set Enhanced Conversions data (raw - GTM hashes automatically)
        var ecData = {};
        if (formData.email) ecData.email = formData.email;
        var phone = formData.whatsapp || formData.telefone || '';
        if (phone) {
            var digits = phone.replace(/\D/g, '');
            if (digits.length <= 11) digits = '55' + digits;
            ecData.phone_number = '+' + digits;
        }
        if (formData.nome) {
            var parts = formData.nome.trim().split(/\s+/);
            if (parts.length >= 1) ecData.first_name = parts[0];
            if (parts.length >= 2) ecData.last_name = parts[parts.length - 1];
        }
        window.enhanced_conversion_data = ecData;

        // Meta Pixel - InitiateCheckout (client-side, SYNC - before any await)
        if (config.pixel && config.pixel.id && typeof fbq === 'function') {
            fbq('track', 'InitiateCheckout', {
                content_name: formType,
                currency: 'BRL',
                content_category: formType === 'whatsapp' ? 'whatsapp' : 'lead'
            });
        }

        // dataLayer push for GTM (SYNC - must happen before any await)
        if (gtm.gtm_id) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(Object.assign({
                event: 'generate_lead'
            }, eventData));
            window.dataLayer.push(Object.assign({event: 'form_submission'}, eventData));
        }

        // GA4 gtag event (when no GTM)
        if (gtm.ga4_id && !gtm.gtm_id && typeof gtag === 'function') {
            gtag('event', 'generate_lead', eventData);
        }

        // Google Ads conversion with Enhanced Conversions (when no GTM)
        if (gtm.gads_id && gtm.gads_label && !gtm.gtm_id && typeof gtag === 'function') {
            var enhancedData = await buildEnhancedConversionsData(formData);
            gtag('set', 'user_data', {
                sha256_email_address: enhancedData.sha256_email_address || '',
                sha256_phone_number: enhancedData.sha256_phone_number || '',
                address: {
                    sha256_first_name: enhancedData.sha256_first_name || '',
                    sha256_last_name: enhancedData.sha256_last_name || ''
                }
            });
            gtag('event', 'conversion', {
                'send_to': gtm.gads_id + '/' + gtm.gads_label
            });
        }
    }

    /**
     * Initialize form submission handlers
     */
    function initFormSubmission() {
        // WhatsApp form
        const whatsappForm = document.getElementById('yeshua-form-whatsapp');
        if (whatsappForm) {
            whatsappForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(whatsappForm);
            });
        }

        // Lead form
        const leadForm = document.getElementById('yeshua-form-lead');
        if (leadForm) {
            leadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(leadForm);
            });
        }

        // Field validation on blur
        document.querySelectorAll('.yeshua-modal .yeshua-input, .yeshua-modal .yeshua-textarea, .yeshua-modal .yeshua-select').forEach(function(field) {
            field.addEventListener('blur', function() {
                validateField(field);
            });
        });
    }

    /**
     * Track which modals have been auto-triggered this session
     */
    var triggeredModals = {};

    /**
     * Check if modal was already triggered this session
     * @param {string} modalId - Modal ID
     * @returns {boolean}
     */
    function wasModalTriggered(modalId) {
        // Check sessionStorage
        var key = 'yeshua_modal_triggered_' + modalId;
        if (sessionStorage.getItem(key)) {
            return true;
        }
        return triggeredModals[modalId] === true;
    }

    /**
     * Mark modal as triggered
     * @param {string} modalId - Modal ID
     */
    function markModalTriggered(modalId) {
        triggeredModals[modalId] = true;
        sessionStorage.setItem('yeshua_modal_triggered_' + modalId, '1');
    }

    /**
     * Auto-open modal with trigger tracking
     * @param {string} modalId - Modal ID
     */
    function autoOpenModal(modalId) {
        if (wasModalTriggered(modalId)) {
            return;
        }
        openModal(modalId);
        markModalTriggered(modalId);
    }

    /**
     * Initialize advanced triggers for a form
     * @param {string} formType - 'whatsapp' or 'lead'
     * @param {Object} triggers - Trigger configuration
     */
    function initFormTriggers(formType, triggers) {
        var modalId = 'yeshua-modal-' + formType;
        
        if (!triggers || !document.getElementById(modalId)) {
            return;
        }

        // Trigger: On page load
        if (triggers.onload) {
            autoOpenModal(modalId);
        }

        // Trigger: After delay (seconds)
        if (triggers.delay && triggers.delay > 0) {
            setTimeout(function() {
                autoOpenModal(modalId);
            }, triggers.delay * 1000);
        }

        // Trigger: After inactivity (seconds)
        if (triggers.inactivity && triggers.inactivity > 0) {
            var inactivityTimer;
            var resetInactivity = function() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(function() {
                    autoOpenModal(modalId);
                }, triggers.inactivity * 1000);
            };

            // Reset on user activity
            ['mousemove', 'keydown', 'scroll', 'click', 'touchstart'].forEach(function(event) {
                document.addEventListener(event, resetInactivity, { passive: true });
            });

            // Start the timer
            resetInactivity();
        }

        // Trigger: After scroll percentage
        if (triggers.scroll && triggers.scroll > 0) {
            var scrollTriggered = false;
            window.addEventListener('scroll', function() {
                if (scrollTriggered) return;
                
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var scrollPercent = (scrollTop / docHeight) * 100;
                
                if (scrollPercent >= triggers.scroll) {
                    scrollTriggered = true;
                    autoOpenModal(modalId);
                }
            }, { passive: true });
        }

        // Trigger: Exit intent (mouse leaves window)
        if (triggers.exitIntent) {
            var exitIntentTriggered = false;
            document.addEventListener('mouseout', function(e) {
                if (exitIntentTriggered) return;
                
                // Check if mouse is leaving the window (going to top)
                if (e.clientY <= 0 && e.relatedTarget === null) {
                    exitIntentTriggered = true;
                    autoOpenModal(modalId);
                }
            });
        }
    }

    /**
     * Initialize all advanced triggers
     */
    function initAdvancedTriggers() {
        // Skip if in admin area
        if (document.body.classList.contains('wp-admin')) {
            return;
        }

        // WhatsApp triggers
        if (config.forms.whatsapp && config.forms.whatsapp.enabled && config.forms.whatsapp.triggers) {
            initFormTriggers('whatsapp', config.forms.whatsapp.triggers);
        }

        // Lead triggers
        if (config.forms.lead && config.forms.lead.enabled && config.forms.lead.triggers) {
            initFormTriggers('lead', config.forms.lead.triggers);
        }
    }

    /**
     * Watch for dynamically added elements
     */
    function initDynamicTriggers() {
        // Observer for dynamically added elements
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check for ID triggers
                        if (node.id === 'formwhatsapp' && config.forms.whatsapp.enabled) {
                            attachModalTrigger(node, 'yeshua-modal-whatsapp');
                        }
                        if (node.id === 'formlead' && config.forms.lead.enabled) {
                            attachModalTrigger(node, 'yeshua-modal-lead');
                        }
                        
                        // Check for class triggers
                        if (node.classList && node.classList.contains('formwhatsapp') && config.forms.whatsapp.enabled) {
                            attachModalTrigger(node, 'yeshua-modal-whatsapp');
                        }
                        if (node.classList && node.classList.contains('formlead') && config.forms.lead.enabled) {
                            attachModalTrigger(node, 'yeshua-modal-lead');
                        }
                        
                        // Check for href triggers
                        if (node.tagName === 'A') {
                            const href = node.getAttribute('href') || '';
                            if (href === '#formwhatsapp' || href.includes('#formwhatsapp')) {
                                if (config.forms.whatsapp.enabled) {
                                    attachModalTrigger(node, 'yeshua-modal-whatsapp');
                                }
                            }
                            if (href === '#formlead' || href.includes('#formlead')) {
                                if (config.forms.lead.enabled) {
                                    attachModalTrigger(node, 'yeshua-modal-lead');
                                }
                            }
                        }
                        
                        // Check children for triggers
                        if (node.querySelectorAll) {
                            node.querySelectorAll('.formwhatsapp').forEach(function(el) {
                                if (config.forms.whatsapp.enabled) {
                                    attachModalTrigger(el, 'yeshua-modal-whatsapp');
                                }
                            });
                            node.querySelectorAll('.formlead').forEach(function(el) {
                                if (config.forms.lead.enabled) {
                                    attachModalTrigger(el, 'yeshua-modal-lead');
                                }
                            });
                            node.querySelectorAll('a[href="#formwhatsapp"], a[href*="#formwhatsapp"]').forEach(function(el) {
                                if (config.forms.whatsapp.enabled) {
                                    attachModalTrigger(el, 'yeshua-modal-whatsapp');
                                }
                            });
                            node.querySelectorAll('a[href="#formlead"], a[href*="#formlead"]').forEach(function(el) {
                                if (config.forms.lead.enabled) {
                                    attachModalTrigger(el, 'yeshua-modal-lead');
                                }
                            });
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Initialize everything on DOM ready
     */
    function init() {
        initModalTriggers();
        initModalClose();
        initFormSubmission();
        initAdvancedTriggers();
        initDynamicTriggers();
        
        // Verifica novamente o botão flutuante após um pequeno delay
        // para garantir que ele foi renderizado
        setTimeout(function() {
            const floatingBtn = document.getElementById('yeshua-whatsapp-fab');
            if (floatingBtn && config.forms.whatsapp && config.forms.whatsapp.enabled) {
                // Remove event listeners anteriores se houver
                const newBtn = floatingBtn.cloneNode(true);
                floatingBtn.parentNode.replaceChild(newBtn, floatingBtn);
                
                // Adiciona o event listener novamente
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const modal = document.getElementById('yeshua-modal-whatsapp');
                    if (modal) {
                        openModal('yeshua-modal-whatsapp');
                    } else {
                        console.warn('Modal WhatsApp não encontrado');
                    }
                });
            }
        }, 100);
    }

    /**
     * Aguarda configuração ser disponibilizada antes de iniciar
     */
    function waitForConfig(attempt = 0) {
        if (typeof config === 'undefined') {
            config = window.yeshuaForms;
        }

        if (typeof config === 'undefined') {
            if (attempt < 20) {
                setTimeout(function() {
                    waitForConfig(attempt + 1);
                }, 100);
            } else {
                console.warn('Configuração do formulário não encontrada; os modais não foram inicializados.');
            }
            return;
        }

        window.yeshuaFormsAPI = {
            openModal: openModal,
            closeModal: closeModal,
            validateForm: validateForm,
            autoOpenModal: autoOpenModal
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    }

    waitForConfig();

})();
