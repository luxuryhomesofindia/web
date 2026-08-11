/**
 * LHI Premium Lead Popup Form Controller
 * Manages triggers (exit intent, scroll, delay, clicks), UTM parsing, validation,
 * and AJAX form submission to submit-lead.php.
 */

(function () {
    'use strict';

    // Global variables to track state
    let popupTriggered = false;
    let popupOverlay = null;
    let popupForm = null;

    // 1. UTM Attribution Capture
    function captureUTM() {
        const urlParams = new URLSearchParams(window.location.search);
        const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        
        utmKeys.forEach(key => {
            const val = urlParams.get(key);
            if (val) {
                sessionStorage.setItem(key, val);
            }
        });
        
        // Also capture referrer if not current host
        if (document.referrer && !document.referrer.includes(window.location.hostname)) {
            sessionStorage.setItem('lhi_referrer', document.referrer);
        }
    }

    // Capture UTM on load
    captureUTM();

    // 2. DOM Ready setup
    document.addEventListener('DOMContentLoaded', function () {
        popupOverlay = document.getElementById('premiumLeadPopupOverlay');
        popupForm = document.getElementById('premiumLeadForm');
        
        if (!popupOverlay || !popupForm) return;

        // Populate hidden attribution fields in the form if they exist
        const utmSourceInput = popupForm.querySelector('input[name="utm_source"]');
        const utmMediumInput = popupForm.querySelector('input[name="utm_medium"]');
        const utmCampaignInput = popupForm.querySelector('input[name="utm_campaign"]');
        const referrerInput = popupForm.querySelector('input[name="referrer_url"]');
        const landingPageInput = popupForm.querySelector('input[name="landing_page_url"]');

        if (utmSourceInput) utmSourceInput.value = sessionStorage.getItem('utm_source') || 'organic';
        if (utmMediumInput) utmMediumInput.value = sessionStorage.getItem('utm_medium') || 'direct';
        if (utmCampaignInput) utmCampaignInput.value = sessionStorage.getItem('utm_campaign') || 'none';
        if (referrerInput) referrerInput.value = sessionStorage.getItem('lhi_referrer') || '';
        if (landingPageInput) landingPageInput.value = window.location.href;

        // Bind exit button click
        const closeBtn = popupOverlay.querySelector('.premium-popup-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closePopup);
        }

        // Close on overlay background click
        popupOverlay.addEventListener('click', function (e) {
            if (e.target === popupOverlay) {
                closePopup();
            }
        });

        // Bind CTA clicks
        bindCTAClicks();

        // 3. Register Auto Triggers
        // A. 30 Seconds timer
        setTimeout(function () {
            triggerPopup('time_delay');
        }, 30000);

        // B. 50% Scroll trigger
        window.addEventListener('scroll', throttle(handleScrollTrigger, 200));

        // C. Exit Intent trigger
        document.addEventListener('mouseleave', handleExitIntent);

        // 4. Form Submit handler
        popupForm.addEventListener('submit', handleFormSubmit);
    });

    // Helper functions
    function openPopup() {
        if (!popupOverlay) return;
        popupOverlay.classList.add('show');
        popupOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePopup() {
        if (!popupOverlay) return;
        popupOverlay.classList.remove('show');
        popupOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Expose globally to intercept legacy triggers
    window.openLhiPremiumPopup = openPopup;
    window.closeLhiPremiumPopup = closePopup;
    window.openLeadPopup = openPopup;
    window.closeLeadPopup = closePopup;

    function triggerPopup(triggerType) {
        if (popupTriggered) return;
        popupTriggered = true;
        
        // Track the trigger type in form
        const triggerInput = popupForm.querySelector('input[name="utm_term"]');
        if (triggerInput && !triggerInput.value) {
            triggerInput.value = 'trigger_' + triggerType;
        }

        // Push event to GA4/FB Pixel
        if (typeof fbq === 'function') {
            fbq('trackCustom', 'LeadPopupTriggered', { type: triggerType });
        }
        if (typeof gtag === 'function') {
            gtag('event', 'lead_popup_trigger', { 'trigger_type': triggerType });
        }

        openPopup();
    }

    function bindCTAClicks() {
        // Find all buttons that direct to enquiry or projects
        const ctaSelectors = [
            '.open-lead-popup', 
            '.city-cta-btn', 
            'a[href="#enquiry"]', 
            'a[href="#contact"]',
            '.quote-btn'
        ];
        
        ctaSelectors.forEach(selector => {
            const btns = document.querySelectorAll(selector);
            btns.forEach(btn => {
                // If it doesn't already open popup or standard page, override it
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    triggerPopup('cta_click');
                    openPopup();
                });
            });
        });
    }

    function handleScrollTrigger() {
        if (popupTriggered) return;
        
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        
        if (scrollPercent >= 50) {
            triggerPopup('scroll_50');
        }
    }

    function handleExitIntent(e) {
        if (popupTriggered) return;
        // Trigger if mouse leaves window top
        if (e.clientY < 20) {
            triggerPopup('exit_intent');
        }
    }

    // Custom Form Submit Handler
    function handleFormSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const msgDiv = document.getElementById('premiumLeadMsg');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Reset errors
        form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
        form.querySelectorAll('.error-feedback').forEach(el => el.remove());
        
        // Client-side Validations
        let valid = true;
        const nameVal = form.querySelector('[name="name"]').value.trim();
        const mobileVal = form.querySelector('[name="mobile"]').value.trim();
        const emailVal = form.querySelector('[name="email"]').value.trim();
        const locVal = form.querySelector('[name="project_location"]').value.trim();
        const consentEl = form.querySelector('[name="consent"]');
        const consentVal = consentEl ? consentEl.checked : true;

        if (nameVal.length < 2) {
            showFieldError(form.querySelector('[name="name"]'), 'Please enter your name.');
            valid = false;
        }
        if (!/^[0-9\+\-\s\(\)]{10,15}$/.test(mobileVal)) {
            showFieldError(form.querySelector('[name="mobile"]'), 'Please enter a valid mobile number (10-15 digits).');
            valid = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            showFieldError(form.querySelector('[name="email"]'), 'Please enter a valid email address.');
            valid = false;
        }
        if (locVal.length < 2) {
            showFieldError(form.querySelector('[name="project_location"]'), 'Please specify your project location.');
            valid = false;
        }
        if (consentEl && !consentVal) {
            showFieldError(consentEl.parentNode, 'You must consent to be contacted.');
            valid = false;
        }

        if (!valid) return;

        // Show loading state
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending enquiry...';
        msgDiv.innerHTML = '';

        const formData = new FormData(form);

        // Fetch to backend script (using origin to resolve absolutely from any subfolder)
        const submitUrl = window.location.origin + '/submit-lead.php';

        fetch(submitUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                // Forward to FormBold client-side to bypass GoDaddy outbound cURL block
                fetch('https://formbold.com/s/6QXkY', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                }).catch(err => console.error('FormBold submission failed:', err));

                // Success actions
                msgDiv.innerHTML = `<div class="success-alert animate-pop-in">
                    <div class="checkmark-circle">
                        <div class="background"></div>
                        <div class="checkmark draw"></div>
                    </div>
                    <p style="color:#d4af37; font-weight:700; margin-top:10px;">${res.message}</p>
                </div>`;
                
                // Track Conversion Events
                if (typeof fbq === 'function') {
                    fbq('track', 'Lead', {
                        content_name: 'Luxury Home Consultation',
                        status: 'Success'
                    });
                }
                if (typeof gtag === 'function') {
                    gtag('event', 'generate_lead', {
                        'event_category': 'Engagement',
                        'event_label': 'Lead Form Submit'
                    });
                }

                form.reset();
                setTimeout(function () {
                    closePopup();
                    msgDiv.innerHTML = '';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }, 3000);
            } else if (res.status === 'validation_error') {
                // Server-side validation errors
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                Object.keys(res.errors).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`) || form.querySelector(`[name="${key}[]"]`);
                    if (field) {
                        showFieldError(field, res.errors[key]);
                    }
                });
            } else {
                // General Server Error
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                msgDiv.innerHTML = `<p class="error-alert">${res.message || 'Form submission failed. Please try again.'}</p>`;
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            msgDiv.innerHTML = '<p class="error-alert">A server network error occurred. Please try again.</p>';
            console.error(err);
        });
    }

    function showFieldError(element, message) {
        element.classList.add('has-error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-feedback';
        errorDiv.style.color = '#ef4444';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '4px';
        errorDiv.style.textAlign = 'left';
        errorDiv.innerHTML = message;
        element.parentNode.appendChild(errorDiv);
    }

    // Scroll throttle helper
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
