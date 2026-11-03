/**
 * AAJ for Joomla 6 — Frontend JS
 */
(function () {
    'use strict';

    // Lazy-load product images
    if ('IntersectionObserver' in window) {
        const images = document.querySelectorAll('.aaj-product__image[data-src]');
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        images.forEach(function (img) { observer.observe(img); });
    }

    // Track affiliate link clicks (custom event for analytics)
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.aaj-product__btn, .aaj-product__title, .aaj-product__image-link, .aaj-link');
        if (!link) return;

        const product = link.closest('[data-asin]');
        const asin    = product ? product.dataset.asin : '';

        if (asin && typeof window.aajTrack === 'function') {
            window.aajTrack(asin, link.href);
        }

        // Emit custom DOM event
        document.dispatchEvent(new CustomEvent('aaj:click', {
            detail: { asin: asin, href: link.href, element: link }
        }));
    });

})();
