/**
 * LHI Universal Conversion & Event Tracking Script
 * Automatically tracks Call, WhatsApp, Form Starts, Scroll Depth, and Blog Reads
 * Integrates with Google Analytics (gtag) and Meta Pixel (fbq).
 */

(function() {
    'use strict';

    // Track scroll depths
    const trackedDepths = { 25: false, 50: false, 75: false, 90: false };

    document.addEventListener('DOMContentLoaded', function() {
        // 1. WhatsApp Clicks
        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href') || '';
            
            // Check if WhatsApp
            if (href.includes('wa.me') || href.includes('whatsapp.com') || link.classList.contains('whatsapp-btn') || link.id === 'whatsappBtn') {
                trackEvent('WhatsAppClick', 'whatsapp_click', {
                    page_path: window.location.pathname,
                    destination: href
                });
            }
            
            // Check if Call Phone
            if (href.startsWith('tel:')) {
                trackEvent('CallClick', 'call_click', {
                    page_path: window.location.pathname,
                    phone_number: href.replace('tel:', '')
                });
            }
        });

        // 2. Form Input Start Tracking
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            let formStarted = false;
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (!formStarted) {
                        formStarted = true;
                        trackEvent('FormStart', 'form_start', {
                            form_id: form.id || 'unknown_form',
                            page_path: window.location.pathname
                        });
                    }
                }, { once: true });
            });
        });

        // 3. Scroll Depth Tracking
        window.addEventListener('scroll', throttle(trackScrollDepth, 250));

        // 4. Time-on-page / Blog Read Trigger
        // If it's a blog page and user stays for 45s, count as a "Blog Read"
        if (window.location.pathname.includes('/blog') || window.location.pathname.includes('blog-')) {
            setTimeout(function() {
                trackEvent('BlogRead', 'blog_read', {
                    page_path: window.location.pathname,
                    page_title: document.title
                });
            }, 45000); // 45 seconds
        }
    });

    // Event Dispatcher
    function trackEvent(pixelEventName, googleEventName, params = {}) {
        // 1. Meta Pixel Tracking
        if (typeof fbq === 'function') {
            fbq('trackCustom', pixelEventName, params);
        } else {
            console.log(`[Tracking Pending] Meta Pixel not loaded yet for: ${pixelEventName}`, params);
        }

        // 2. Google Analytics Tracking
        if (typeof gtag === 'function') {
            gtag('event', googleEventName, params);
        } else {
            console.log(`[Tracking Pending] Google Analytics not loaded yet for: ${googleEventName}`, params);
        }
    }

    // Scroll Depth Logic
    function trackScrollDepth() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        if (scrollHeight <= 0) return;
        
        const scrollPercent = Math.round((scrollTop / scrollHeight) * 100);

        [25, 50, 75, 90].forEach(depth => {
            if (scrollPercent >= depth && !trackedDepths[depth]) {
                trackedDepths[depth] = true;
                trackEvent(`Scroll_${depth}%`, 'scroll_depth', {
                    depth_percentage: depth,
                    page_path: window.location.pathname
                });
            }
        });
    }

    // Throttle helper
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }
})();
