/**
 * Recent Posts Auto-Scroll Widget
 * Works on every page that has .sidebar-recent-posts
 * No dependencies — vanilla JS only
 */
(function () {
    'use strict';

    const ITEM_HEIGHT   = 86;   // px per post item (image 58px + gap 14px + border)
    const AUTO_INTERVAL = 3000; // ms between auto-scrolls
    const VISIBLE_ITEMS = 4;    // how many posts are visible at once

    function initRecentPostsScroll() {
        const lists = document.querySelectorAll('.sidebar-recent-posts');
        if (!lists.length) return;

        lists.forEach(function (list) {
            const items = list.querySelectorAll('.sidebar-post-item');
            if (items.length <= VISIBLE_ITEMS) return; // no scroll needed

            /* ── Wrap the list ── */
            const wrapper = document.createElement('div');
            wrapper.className = 'sidebar-recent-posts-wrapper';

            const scrollBox = document.createElement('div');
            scrollBox.className = 'sidebar-recent-posts-scroll';

            list.parentNode.insertBefore(wrapper, list);
            scrollBox.appendChild(list);
            wrapper.appendChild(scrollBox);

            /* ── Arrow buttons ── */
            const controls = document.createElement('div');
            controls.className = 'sidebar-recent-posts-controls';
            controls.innerHTML =
                '<button class="rp-arrow-btn rp-up" aria-label="Previous posts" title="Previous">' +
                    '<i class="fa-solid fa-chevron-up"></i>' +
                '</button>' +
                '<button class="rp-arrow-btn rp-down" aria-label="Next posts" title="Next">' +
                    '<i class="fa-solid fa-chevron-down"></i>' +
                '</button>';

            /* Insert controls into the parent sidebar-widget-title row */
            const box = wrapper.closest('.blog-sidebar-box');
            if (box) {
                const titleEl = box.querySelector('.sidebar-widget-title');
                if (titleEl) {
                    titleEl.style.position = 'relative';
                    titleEl.style.paddingRight = '68px';
                    titleEl.appendChild(controls);
                    controls.style.position = 'absolute';
                    controls.style.top      = '50%';
                    controls.style.right    = '0';
                    controls.style.transform = 'translateY(-50%)';
                }
            } else {
                wrapper.insertBefore(controls, scrollBox);
            }

            const btnUp   = controls.querySelector('.rp-up');
            const btnDown = controls.querySelector('.rp-down');

            let currentIndex = 0;
            const maxIndex   = items.length - VISIBLE_ITEMS;
            let autoTimer    = null;

            function scrollTo(index) {
                currentIndex = Math.max(0, Math.min(index, maxIndex));
                const targetItem = items[currentIndex];
                const offset = targetItem ? targetItem.offsetTop : 0;
                list.style.transform = 'translateY(-' + offset + 'px)';
                btnUp.disabled   = currentIndex === 0;
                btnDown.disabled = currentIndex >= maxIndex;
            }

            function startAuto() {
                stopAuto();
                autoTimer = setInterval(function () {
                    const next = currentIndex >= maxIndex ? 0 : currentIndex + 1;
                    scrollTo(next);
                }, AUTO_INTERVAL);
            }

            function stopAuto() {
                if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
            }

            /* Initial state */
            scrollTo(0);
            startAuto();

            /* Arrow clicks */
            btnUp.addEventListener('click', function () {
                stopAuto();
                scrollTo(currentIndex - 1);
                startAuto();
            });

            btnDown.addEventListener('click', function () {
                stopAuto();
                scrollTo(currentIndex + 1);
                startAuto();
            });

            /* Pause on hover */
            wrapper.addEventListener('mouseenter', stopAuto);
            wrapper.addEventListener('mouseleave', startAuto);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRecentPostsScroll);
    } else {
        initRecentPostsScroll();
    }
})();
