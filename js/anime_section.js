document.addEventListener('DOMContentLoaded', function() {
    
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px' 
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // 標題動畫
                const titles = entry.target.querySelectorAll('.anime-section-title, .anime-section-subtitle');
                titles.forEach(title => {
                    title.classList.add('show');
                });
                
                const isMobile = window.innerWidth <= 768;
                
                // 輪播卡片動畫
                if (!isMobile) {
                    const carousel = entry.target.querySelector('#animeProductsCarousel');
                    if (carousel) {
                        // 首個卡片組初始化
                        const activeSlide = carousel.querySelector('.carousel-item.active');
                        if (activeSlide) {
                            activeSlide.querySelectorAll('.col').forEach(col => {
                                col.style.opacity = '1';
                                col.style.transform = 'translateY(0)';
                            });
                        }
                        
                        // 監聽輪播切換
                        carousel.addEventListener('slid.bs.carousel', function(event) {
                            const activeSlide = event.relatedTarget;
                            const cards = activeSlide.querySelectorAll('.col');
                            
                            // 重設狀態
                            cards.forEach(card => {
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(30px)';
                            });
                            
                            // 套用動畫
                            setTimeout(() => {
                                cards.forEach((card, index) => {
                                    setTimeout(() => {
                                        card.style.opacity = '1';
                                        card.style.transform = 'translateY(0)';
                                    }, (index + 1) * 100);
                                });
                            }, 50);
                        });
                    }
                } else {
                    const cards = entry.target.querySelectorAll('.anime-carousel-item .col');
                    cards.forEach(card => {
                        card.style.opacity = '1';
                        card.style.transform = 'none';
                    });
                }
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // 開始觀察模型專區
    const animeSection = document.getElementById('anime-section');
    if (animeSection) {
        observer.observe(animeSection);
    }
    
    // 初始化輪播
    const animeProductsCarousel = document.getElementById('animeProductsCarousel');
    if (animeProductsCarousel) {
        // 建立 Bootstrap 輪播
        const carousel = new bootstrap.Carousel(animeProductsCarousel, {
            interval: false,
            wrap: true
        });
    }
});