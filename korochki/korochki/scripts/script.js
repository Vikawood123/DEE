// ==========================================
// 1. СЛАЙДЕР
// ==========================================
let slideIndex = 0;

function showSlides() {
    let slides = document.querySelectorAll(".slider-slide");
    let dots = document.querySelectorAll(".dot");

    if (slides.length === 0) return;

    slides.forEach(slide => slide.classList.remove("active"));
    dots.forEach(dot => dot.classList.remove("active"));

    if (slideIndex >= slides.length) { slideIndex = 0; }
    if (slideIndex < 0) { slideIndex = slides.length - 1; }

    slides[slideIndex].classList.add("active");
    if (dots[slideIndex]) {
        dots[slideIndex].classList.add("active");
    }
}

function moveSlide(n) {
    slideIndex += n;
    showSlides();
}

function currentSlide(n) {
    slideIndex = n;
    showSlides();
}

// ==========================================
// 2. МАСКА ТЕЛЕФОНА И АВТОЗАПУСК СЛАЙДЕРА
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Автозапуск слайдера ---
    showSlides();
    setInterval(function() {
        moveSlide(1);
    }, 4000); 

    // --- Маска телефона ---
    const phoneInput = document.getElementById('telefon');
    if (phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            // Оставляем только цифры
            let digits = e.target.value.replace(/\D/g, '');
            
            // Если первая цифра не 8, принудительно ставим 8
            if (digits.length > 0 && digits[0] !== '8') {
                digits = '8' + digits;
            }
            // Ограничиваем до 11 цифр
            if (digits.length > 11) {
                digits = digits.substring(0, 11);
            }

            // Форматируем
            let res = '';
            if (digits.length > 0) res += digits[0]; 
            if (digits.length > 1) res += '(' + digits.substring(1, 4); 
            if (digits.length >= 5) res += ')'; 
            if (digits.length > 4) res += digits.substring(4, 7); 
            if (digits.length > 7) res += '-' + digits.substring(7, 9); 
            if (digits.length > 9) res += '-' + digits.substring(9, 11); 

            e.target.value = res;
        });

        // Запрещаем вводить буквы
        phoneInput.addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key)) {
                e.preventDefault();
            }
        });
    }
});