document.addEventListener('DOMContentLoaded', function() {
    
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px' 
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // 动画标题
                const titles = entry.target.querySelectorAll('.anime-section-title, .anime-section-subtitle');
                titles.forEach(title => {
                    title.classList.add('show');
                });
                
                const isMobile = window.innerWidth <= 768;
                
                // 动画轮播卡片
                if (!isMobile) {
                    const carousel = entry.target.querySelector('#animeProductsCarousel');
                    if (carousel) {
                        // 初始化时为第一个幻灯片应用动画
                        const activeSlide = carousel.querySelector('.carousel-item.active');
                        if (activeSlide) {
                            activeSlide.querySelectorAll('.col').forEach(col => {
                                col.style.opacity = '1';
                                col.style.transform = 'translateY(0)';
                            });
                        }
                        
                        // 监听轮播切换事件
                        carousel.addEventListener('slid.bs.carousel', function(event) {
                            const activeSlide = event.relatedTarget;
                            const cards = activeSlide.querySelectorAll('.col');
                            
                            // 重置状态
                            cards.forEach(card => {
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(30px)';
                            });
                            
                            // 应用动画
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
    
    // 开始观察模型专区
    const animeSection = document.getElementById('anime-section');
    if (animeSection) {
        observer.observe(animeSection);
    }
    
    // 轮播初始化
    const animeProductsCarousel = document.getElementById('animeProductsCarousel');
    if (animeProductsCarousel) {
        // 初始化Bootstrap轮播
        const carousel = new bootstrap.Carousel(animeProductsCarousel, {
            interval: false,
            wrap: true
        });
    }
});