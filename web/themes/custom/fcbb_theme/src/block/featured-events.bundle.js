document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper('.swiper', {
        a11y: true,
        slidesPerView: 'auto',
        centeredSlides: true,
        initialSlide: 0,
        breakpoints: {
            768: {
                initialSlide: 1,
            }
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        // on: {
        //     lock: function() {
        //         document.querySelector('.view-content.swiper').classList.add('center-slides');
        //     },
        //     unlock: function() {
        //         document.querySelector('.view-content.swiper').classList.remove('center-slides');
        //     },
        // },
    });
})

