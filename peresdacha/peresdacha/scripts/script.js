let slideIndex = 0;
function showSlides() {
    let slides = document.querySelectorAll(".slider-slide");
    let dots = document.querySelectorAll(".dot");
    if (slides.length === 0) return;
    slides.forEach(s => s.classList.remove("active"));
    dots.forEach(d => d.classList.remove("active"));
    if (slideIndex >= slides.length) { slideIndex = 0; }
    if (slideIndex < 0) { slideIndex = slides.length - 1; }
    slides[slideIndex].classList.add("active");
    if (dots[slideIndex]) dots[slideIndex].classList.add("active");
}
function moveSlide(n) { slideIndex += n; showSlides(); }
function currentSlide(n) { slideIndex = n; showSlides(); }

document.addEventListener('DOMContentLoaded', function() {
    showSlides();
    setInterval(function() { moveSlide(1); }, 4000); 

    const phoneInput = document.getElementById('telefon');
    if (phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            let digits = e.target.value.replace(/\D/g, '');
            if (digits.length > 0 && digits[0] !== '8') digits = '8' + digits;
            if (digits.length > 11) digits = digits.substring(0, 11);
            let res = '';
            if (digits.length > 0) res += digits[0]; 
            if (digits.length > 1) res += '(' + digits.substring(1, 4); 
            if (digits.length >= 5) res += ')'; 
            if (digits.length > 4) res += digits.substring(4, 7); 
            if (digits.length > 7) res += '-' + digits.substring(7, 9); 
            if (digits.length > 9) res += '-' + digits.substring(9, 11); 
            e.target.value = res;
        });
        phoneInput.addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key)) e.preventDefault();
        });
    }
});