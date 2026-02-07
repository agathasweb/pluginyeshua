/**
 * YESHUA Conversões - Phone Mask
 * Brazilian phone mask: (00) 00000-0000 or (00) 0000-0000
 */

(function() {
    'use strict';

    /**
     * Apply phone mask to input value
     * @param {string} value - Raw input value
     * @returns {string} - Masked value
     */
    function applyPhoneMask(value) {
        // Remove all non-digits
        let digits = value.replace(/\D/g, '');
        
        // Limit to 11 digits (Brazilian phone with DDD)
        digits = digits.substring(0, 11);
        
        // Apply mask
        if (digits.length === 0) {
            return '';
        }
        
        let masked = '(';
        
        // DDD (first 2 digits)
        if (digits.length <= 2) {
            masked += digits;
        } else {
            masked += digits.substring(0, 2) + ') ';
            
            // Phone number
            if (digits.length <= 7) {
                // First part
                masked += digits.substring(2);
            } else if (digits.length <= 10) {
                // 8-digit phone: 0000-0000
                masked += digits.substring(2, 6) + '-' + digits.substring(6);
            } else {
                // 9-digit phone: 00000-0000
                masked += digits.substring(2, 7) + '-' + digits.substring(7);
            }
        }
        
        return masked;
    }

    /**
     * Get cursor position after mask is applied
     * @param {string} oldValue - Previous value
     * @param {string} newValue - New masked value
     * @param {number} cursorPos - Original cursor position
     * @returns {number} - New cursor position
     */
    function getCursorPosition(oldValue, newValue, cursorPos) {
        // Count digits before cursor in old value
        const oldDigits = oldValue.substring(0, cursorPos).replace(/\D/g, '').length;
        
        // Find position in new value with same number of digits
        let digitCount = 0;
        let newPos = 0;
        
        for (let i = 0; i < newValue.length; i++) {
            if (/\d/.test(newValue[i])) {
                digitCount++;
                if (digitCount === oldDigits) {
                    newPos = i + 1;
                    break;
                }
            }
        }
        
        // If we didn't find enough digits, put cursor at end
        if (digitCount < oldDigits) {
            newPos = newValue.length;
        }
        
        return newPos;
    }

    /**
     * Initialize phone mask on an input element
     * @param {HTMLInputElement} input - Input element to apply mask
     */
    function initPhoneMask(input) {
        // Handle input event
        input.addEventListener('input', function(e) {
            const cursorPos = input.selectionStart;
            const oldValue = input.value;
            const newValue = applyPhoneMask(oldValue);
            
            if (newValue !== oldValue) {
                input.value = newValue;
                
                // Restore cursor position
                const newCursorPos = getCursorPosition(oldValue, newValue, cursorPos);
                input.setSelectionRange(newCursorPos, newCursorPos);
            }
        });

        // Handle paste event
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cursorPos = input.selectionStart;
            const beforeCursor = input.value.substring(0, input.selectionStart);
            const afterCursor = input.value.substring(input.selectionEnd);
            
            const newValue = applyPhoneMask(beforeCursor + pastedText + afterCursor);
            input.value = newValue;
            
            // Move cursor to end of pasted content
            const newCursorPos = getCursorPosition(beforeCursor + pastedText, newValue, (beforeCursor + pastedText).length);
            input.setSelectionRange(newCursorPos, newCursorPos);
        });

        // Handle keydown for special keys
        input.addEventListener('keydown', function(e) {
            // Allow navigation and control keys
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
            
            if (allowedKeys.includes(e.key)) {
                return;
            }
            
            // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            if (e.ctrlKey || e.metaKey) {
                return;
            }
            
            // Only allow digits
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Set input mode for mobile keyboards
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
    }

    /**
     * Initialize all phone mask inputs
     */
    function initAllPhoneMasks() {
        const inputs = document.querySelectorAll('.yeshua-phone-mask');
        inputs.forEach(initPhoneMask);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllPhoneMasks);
    } else {
        initAllPhoneMasks();
    }

    // Re-initialize when new content is added (for dynamically created forms)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    const inputs = node.querySelectorAll ? node.querySelectorAll('.yeshua-phone-mask:not([data-mask-init])') : [];
                    inputs.forEach(function(input) {
                        input.setAttribute('data-mask-init', 'true');
                        initPhoneMask(input);
                    });
                    
                    // Check if the node itself is a phone input
                    if (node.classList && node.classList.contains('yeshua-phone-mask') && !node.hasAttribute('data-mask-init')) {
                        node.setAttribute('data-mask-init', 'true');
                        initPhoneMask(node);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Expose for external use
    window.yeshuaPhoneMask = {
        apply: applyPhoneMask,
        init: initPhoneMask,
        initAll: initAllPhoneMasks
    };

})();





