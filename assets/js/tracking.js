/**
 * YESHUA Conversões - Tracking Script
 */

(function() {
    'use strict';

    // Check if tracking data is available
    if (typeof window.yeshuaTracking === 'undefined' || typeof window.yeshuaRestUrl === 'undefined') {
        return;
    }

    // Detect device type
    function detectDevice() {
        const ua = navigator.userAgent;
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
            return 'tablet';
        }
        if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i.test(ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    // Detect browser
    function detectBrowser() {
        const ua = navigator.userAgent;
        if (/Edge|Edg/i.test(ua)) return 'Edge';
        if (/Opera|OPR/i.test(ua)) return 'Opera';
        if (/Chrome/i.test(ua)) return 'Chrome';
        if (/Safari/i.test(ua)) return 'Safari';
        if (/Firefox/i.test(ua)) return 'Firefox';
        if (/MSIE|Trident/i.test(ua)) return 'IE';
        return 'Outro';
    }

    // Detect OS
    function detectOS() {
        const ua = navigator.userAgent;
        if (/Windows NT 10/i.test(ua)) return 'Windows 10';
        if (/Windows/i.test(ua)) return 'Windows';
        if (/Macintosh|Mac OS X/i.test(ua)) return 'macOS';
        if (/iPhone|iPad|iPod/i.test(ua)) return 'iOS';
        if (/Android/i.test(ua)) return 'Android';
        if (/Linux/i.test(ua)) return 'Linux';
        return 'Outro';
    }

    // Get UTM parameters
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

    // Check if already tracked this page in this session
    function hasTrackedPage() {
        const sessionKey = 'yeshua_tracked_' + window.location.pathname;
        if (sessionStorage.getItem(sessionKey)) {
            return true;
        }
        sessionStorage.setItem(sessionKey, '1');
        return false;
    }

    // Send tracking data
    function sendTracking() {
        // Don't track if already tracked in this session
        if (hasTrackedPage()) {
            return;
        }

        const utm = getUtmParams();
        
        const data = {
            ip: window.yeshuaTracking.ip || '',
            sessao_id: window.yeshuaTracking.session_id || '',
            dispositivo: detectDevice(),
            navegador: detectBrowser(),
            sistema_operacional: detectOS(),
            url_completa: window.location.href,
            url_path: window.location.pathname,
            url_query: window.location.search,
            referer: document.referrer || '',
            user_agent: navigator.userAgent
        };

        // Merge UTM params
        Object.assign(data, utm);

        // Send to REST API
        fetch(window.yeshuaRestUrl + 'track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
            keepalive: true
        }).catch(function(error) {
            console.debug('YESHUA Tracking error:', error);
        });
    }

    // Track page view on load
    if (document.readyState === 'complete') {
        sendTracking();
    } else {
        window.addEventListener('load', sendTracking);
    }

    // Track page visibility changes (for SPA-like behavior)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // Reset tracking for new page views
            sessionStorage.removeItem('yeshua_tracked_' + window.location.pathname);
        }
    });

})();





