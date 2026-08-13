/* =============================================================================
   Luxury Homes of India (LHI) Landing Pages Controller
   Handles user interface and presentation behavior on landing pages.
   Does NOT alter lead handling or FormBold logic.
   ============================================================================= */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. FAQ Accordion Handler
        initFaqAccordions();

        // 2. Smooth Scrolling to Case Studies / Projects
        initSmoothScrolling();

        // 3. Calculator Dynamic Estimator (if present on the page)
        initSimpleCalculator();
    });

    function initFaqAccordions() {
        const faqHeaders = document.querySelectorAll('.lhi-lp .faq-header');
        
        faqHeaders.forEach(header => {
            header.addEventListener('click', function () {
                const item = this.parentElement;
                const body = item.querySelector('.faq-body');
                const icon = this.querySelector('.faq-icon');
                
                // Toggle active state
                const isActive = item.classList.contains('active');
                
                // Collapse all other items
                const allItems = document.querySelectorAll('.lhi-lp .faq-item');
                allItems.forEach(el => {
                    el.classList.remove('active');
                    const b = el.querySelector('.faq-body');
                    if (b) b.style.maxHeight = null;
                });
                
                if (!isActive) {
                    item.classList.add('active');
                    // Set max height dynamically for CSS transition
                    body.style.maxHeight = body.scrollHeight + 'px';
                }
            });
        });
    }

    function initSmoothScrolling() {
        const viewWorkBtn = document.querySelector('.lhi-lp-hero .btn-outline');
        if (viewWorkBtn) {
            viewWorkBtn.addEventListener('click', function (e) {
                const portfolioSec = document.querySelector('.lhi-lp-portfolio');
                if (portfolioSec) {
                    e.preventDefault();
                    portfolioSec.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
    }

    function initSimpleCalculator() {
        const calculatorForm = document.getElementById('lhiLpCalcForm');
        if (!calculatorForm) return;

        const areaInput = document.getElementById('lhiCalcArea');
        const packageSelect = document.getElementById('lhiCalcPackage');
        const resultVal = document.getElementById('lhiCalcResultValue');

        if (!areaInput || !packageSelect || !resultVal) return;

        const calculateEstimate = () => {
            const area = parseFloat(areaInput.value) || 0;
            const rate = parseFloat(packageSelect.value) || 2450;
            const total = area * rate;
            
            if (total > 0) {
                // Format currency
                resultVal.textContent = '₹' + total.toLocaleString('en-IN', {
                    maximumFractionDigits: 0
                });
            } else {
                resultVal.textContent = '₹0';
            }
        };

        areaInput.addEventListener('input', calculateEstimate);
        packageSelect.addEventListener('change', calculateEstimate);
    }
})();
