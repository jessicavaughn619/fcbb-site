document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper('.swiper', {
        a11y: true,
        slidesPerView: 'auto',
        centeredSlides: true,
        breakpoints: {
            768: {
                centeredSlides: false
            }
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        on: {
            lock: function() {
                document.querySelector('.swiper-wrapper').classList.add('center-slides');
            },
            unlock: function() {
                document.querySelector('.swiper-wrapper').classList.remove('center-slides');
            },
        },
    });
})

