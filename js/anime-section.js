document.addEventListener('DOMContentLoaded', function() {
    
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px' 
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Title animation
                const titles = entry.target.querySelectorAll('.anime-section-title, .anime-section-subtitle');
                titles.forEach(title => {
                    title.classList.add('show');
                });
                
                const isMobile = window.innerWidth <= 768;
                
                // Carousel card animation
                if (!isMobile) {
                    const carousel = entry.target.querySelector('#animeProductsCarousel');
                    if (carousel) {
                        // Initialize first slide cards
                        const activeSlide = carousel.querySelector('.carousel-item.active');
                        if (activeSlide) {
                            activeSlide.querySelectorAll('.col').forEach(col => {
                                col.style.opacity = '1';
                                col.style.transform = 'translateY(0)';
                            });
                        }
                        
                        // On carousel slide change
                        carousel.addEventListener('slid.bs.carousel', function(event) {
                            const activeSlide = event.relatedTarget;
                            const cards = activeSlide.querySelectorAll('.col');
                            
                            // Reset state
                            cards.forEach(card => {
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(30px)';
                            });
                            
                            // Apply animation
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
    
    // Observe anime section
    const animeSection = document.getElementById('anime-section');
    if (animeSection) {
        observer.observe(animeSection);
    }
    
    // Init carousel
    const animeProductsCarousel = document.getElementById('animeProductsCarousel');
    if (animeProductsCarousel) {
        // Bootstrap carousel instance
        const carousel = new bootstrap.Carousel(animeProductsCarousel, {
            interval: false,
            wrap: true
        });
    }
});