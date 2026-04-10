/**
 * Homepage Swiper: hero banner + product rows.
 */
(function () {
    if (typeof Swiper === 'undefined') {
        return;
    }

    document.querySelectorAll('.home-hero-swiper').forEach(function (el) {
        var slides = el.querySelectorAll('.swiper-slide');
        var count = slides.length;
        var next = el.querySelector('.swiper-button-next');
        var prev = el.querySelector('.swiper-button-prev');
        var pag = el.querySelector('.swiper-pagination');
        var opts = {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: count > 1,
            watchOverflow: true,
            autoplay: count > 1
                ? { delay: 6000, disableOnInteraction: false, pauseOnMouseEnter: true }
                : false,
        };
        if (pag) {
            opts.pagination = { el: pag, clickable: true };
        }
        if (next && prev && count > 1) {
            opts.navigation = { nextEl: next, prevEl: prev };
        }
        new Swiper(el, opts);
    });

    var common = {
        slidesPerView: 2,
        spaceBetween: 15,
        watchOverflow: true,
        breakpoints: {
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 4 },
            1200: { slidesPerView: 5 },
        },
    };

    document.querySelectorAll('.home-product-swiper').forEach(function (el) {
        var next = el.querySelector('.swiper-button-next');
        var prev = el.querySelector('.swiper-button-prev');
        if (!next || !prev) {
            return;
        }

        new Swiper(el, Object.assign({}, common, {
            navigation: {
                nextEl: next,
                prevEl: prev,
            },
        }));
    });
})();
