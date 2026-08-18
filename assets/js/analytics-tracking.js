/**
 * LHI Universal Conversion & Event Tracking Script
 * Automatically tracks Call, WhatsApp, Form Starts, Form Submissions, Scroll Depth, and Calculators.
 * Intercepts AJAX/fetch/XHR submissions to track verified leads.
 * Integrates with Google Analytics (gtag) and Meta Pixel (fbq).
 */

(function() {
    'use strict';

    // Track scroll depths
    const trackedDepths = { 25: false, 50: false, 75: false, 90: false };

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

    // Capture dynamic events
    document.addEventListener('DOMContentLoaded', function() {
        // 1. WhatsApp, Call, and Email link clicks
        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href') || '';
            const text = link.innerText.trim();
            
            // Check if WhatsApp
            if (href.includes('wa.me') || href.includes('whatsapp.com') || link.classList.contains('whatsapp-btn') || link.id === 'whatsappBtn') {
                trackEvent('WhatsAppClick', 'whatsapp_click', {
                    page_location: window.location.href,
                    page_path: window.location.pathname,
                    destination: href
                });
            }
            
            // Check if Call Phone (tel:)
            if (href.startsWith('tel:')) {
                trackEvent('CallClick', 'phone_click', {
                    page_location: window.location.href,
                    page_path: window.location.pathname,
                    phone_number: href.replace('tel:', '').trim(),
                    link_text: text
                });
            }

            // Check if Email Mailto (mailto:)
            if (href.startsWith('mailto:')) {
                trackEvent('EmailClick', 'email_click', {
                    page_location: window.location.href,
                    page_path: window.location.pathname,
                    email_address: href.replace('mailto:', '').trim(),
                    link_text: text
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

        // 4. Time-on-page / Blog Read Trigger (45 seconds)
        if (window.location.pathname.includes('/blog') || window.location.pathname.includes('blog-') || window.location.pathname.includes('blog.php') || window.location.pathname.includes('blog-detail.php')) {
            setTimeout(function() {
                trackEvent('BlogRead', 'blog_read', {
                    page_location: window.location.href,
                    page_path: window.location.pathname,
                    page_title: document.title
                });
            }, 45000);
        }

        // 5. Cost Calculator / Estimator Tracking
        const ccAreaInput = document.getElementById("builtUpAreaInput");
        const ccSubmitBtn = document.getElementById("submitCalculatorEstimateBtn");
        
        if (ccAreaInput) {
            let calculatorStarted = false;
            const startCalculator = function() {
                if (!calculatorStarted) {
                    calculatorStarted = true;
                    trackEvent('CalculatorStart', 'calculator_start', {
                        page_location: window.location.href,
                        page_path: window.location.pathname
                    });
                }
            };
            
            // Listen for slider interaction
            ccAreaInput.addEventListener('input', startCalculator, { once: true });
            
            // Listen for package changes
            document.querySelectorAll("input[name='packageInput']").forEach(radio => {
                radio.addEventListener('change', startCalculator, { once: true });
            });
            
            // Listen for floors changes
            document.querySelectorAll("input[name='floorsInput']").forEach(radio => {
                radio.addEventListener('change', startCalculator, { once: true });
            });
            
            // Listen for addons toggles
            ['addon_smart', 'addon_solar', 'addon_theater', 'addon_pool'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', startCalculator, { once: true });
            });
        }
        
        if (ccSubmitBtn) {
            ccSubmitBtn.addEventListener('click', function() {
                trackEvent('CalculatorComplete', 'calculator_complete', {
                    page_location: window.location.href,
                    page_path: window.location.pathname
                });
                // Set flag to denote calculator was completed in this session
                window.calculatorUsed = true;
            });
        }
    });

    // 6. Global AJAX Submission Interceptors for Fetch & XMLHttpRequest
    // Ensures we track successful submissions after validation checks pass

    // A. Intercept Fetch API
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            if (response.ok) {
                try {
                    const url = args[0] || '';
                    const body = args[1] && args[1].body;
                    
                    if (typeof url === 'string' && isFormSubmissionUrl(url)) {
                        handleFormSubmitSuccess(url, body);
                    }
                } catch (e) {
                    console.error('Error tracking fetch:', e);
                }
            }
            return response;
        });
    };

    // B. Intercept XMLHttpRequest
    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url) {
        this._url = url;
        return originalOpen.apply(this, arguments);
    };
    
    XMLHttpRequest.prototype.send = function(body) {
        this.addEventListener('load', function() {
            if (this.status >= 200 && this.status < 300) {
                try {
                    const url = this._url || '';
                    if (typeof url === 'string' && isFormSubmissionUrl(url)) {
                        handleFormSubmitSuccess(url, body);
                    }
                } catch (e) {
                    console.error('Error tracking XHR:', e);
                }
            }
        });
        return originalSend.apply(this, arguments);
    };

    // Check if the target URL is a known form processor
    function isFormSubmissionUrl(url) {
        const lowerUrl = url.toLowerCase();
        return lowerUrl.includes('formbold.com') || 
               lowerUrl.includes('mail.php') || 
               lowerUrl.includes('submit-form.php') || 
               lowerUrl.includes('sendmail.php') ||
               lowerUrl.includes('career_mail.php') ||
               lowerUrl.includes('appointment.php') ||
               lowerUrl.includes('resort-popup-mail.php');
    }

    // Handles form data parsing and maps to appropriate GA4 events
    function handleFormSubmitSuccess(url, body) {
        let formId = 'unknown_form';
        let email = '';
        let phone = '';
        let area = '';
        
        // Extract fields depending on payload type
        if (body instanceof FormData) {
            email = body.get('email') || '';
            phone = body.get('mobile') || body.get('phone') || '';
            area = body.get('built_up_area') || '';
            
            // Search active element form ID or search DOM for match
            const activeForm = document.activeElement ? document.activeElement.closest('form') : null;
            if (activeForm && activeForm.id) {
                formId = activeForm.id;
            } else {
                // Try guessing based on fields
                if (body.get('plot_address') || body.get('expected_ratio')) {
                    formId = 'jvForm';
                } else if (body.get('built_up_area')) {
                    formId = 'premiumLeadForm';
                }
            }
        } else if (typeof body === 'string') {
            try {
                // FormEncoded format parsing e.g. name=val&email=val
                const params = new URLSearchParams(body);
                email = params.get('email') || '';
                phone = params.get('mobile') || params.get('phone') || '';
                area = params.get('built_up_area') || '';
                
                if (params.get('plot_address')) {
                    formId = 'jvForm';
                } else if (params.get('built_up_area')) {
                    formId = 'premiumLeadForm';
                }
            } catch (e) {
                // Not URL-encoded or json
                if (body.includes('built_up_area')) {
                    area = '1';
                }
            }
        }

        // Determine specific event types based on URL and form context
        let eventType = 'contact_form_submit';
        
        // 1. Premium lead popup (exit intent, consultation, or estimator submission)
        if (formId === 'premiumLeadForm' || url.includes('6QXkY') || area !== '') {
            if (area !== '' || window.calculatorUsed) {
                eventType = 'estimator_submit';
            } else {
                eventType = 'consultation_request';
            }
        }
        // 2. Resort popup lead capture
        else if (formId === 'resortLeadForm') {
            eventType = 'consultation_request';
        }
        // 3. Appointment scheduler form
        else if (url.includes('appointment.php') || formId.includes('appointment')) {
            eventType = 'consultation_request';
        }
        // 4. Careers application form
        else if (url.includes('career_mail') || formId.includes('career')) {
            eventType = 'career_form_submit';
        }
        
        // Fire generic Lead conversion
        trackEvent('Lead', 'generate_lead', {
            form_id: formId,
            event_type: eventType,
            page_location: window.location.href,
            page_path: window.location.pathname
        });

        // Fire specific custom event
        trackEvent(eventType, eventType, {
            form_id: formId,
            page_location: window.location.href,
            page_path: window.location.pathname
        });

        // Fire double trigger for quote requests
        if (eventType === 'estimator_submit') {
            trackEvent('QuoteRequest', 'quote_request', {
                form_id: formId,
                page_location: window.location.href,
                page_path: window.location.pathname
            });
        }
        
        // Reset state
        window.calculatorUsed = false;
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
