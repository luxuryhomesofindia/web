$(document).ready(function () {

    let slides = $('.slide');
    let totalSlides = slides.length;
    let currentIndex = 0;
    let slideInterval;

    // Create dots
    for (let i = 0; i < totalSlides; i++) {
        $('.slider-dots').append('<span data-index="' + i + '"></span>');
    }

    $('.slider-dots span').first().addClass('active');

    function showSlide(index) {
        slides.removeClass('active prev-slide');
        slides.eq(currentIndex).addClass('prev-slide');

        currentIndex = index;

        if (currentIndex >= totalSlides) currentIndex = 0;
        if (currentIndex < 0) currentIndex = totalSlides - 1;

        slides.eq(currentIndex).addClass('active');

        $('.slider-dots span').removeClass('active');
        $('.slider-dots span').eq(currentIndex).addClass('active');
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    $('.next').click(nextSlide);
    $('.prev').click(prevSlide);

    $('.slider-dots').on('click', 'span', function () {
        let index = $(this).data('index');
        showSlide(index);
    });

    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000); // 5 seconds
    }

    function stopAutoSlide() {
        clearInterval(slideInterval);
    }

    $('.hero-slider').hover(stopAutoSlide, startAutoSlide);

    startAutoSlide();
});
