<?php
/**
 * index.php — главная страница портала «Корочки.есть».
 */

session_start();

$pageTitle = 'Корочки.есть — Главная';
$breadcrumbs = ['Главная' => null];
$navButtons = [
    'регистрация' => '/DEE/korochki/korochki/pages/register.php',
    'вход'        => '/DEE/korochki/korochki/pages/auth.php',
];

ob_start();
?>

<div id="mySlider" style="position: relative; max-width: 800px; height: 400px; margin: 30px auto; overflow: hidden; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); background: #eee;">
    
    <!-- Картинки -->
    <img src="/DEE/korochki/korochki/media/image11.jpg" class="my-slide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 1; transition: opacity 0.5s; z-index: 1;">
    <img src="/DEE/korochki/korochki/media/image05.jpg" class="my-slide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s; z-index: 1;">
    <img src="/DEE/korochki/korochki/media/image06.jpg" class="my-slide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s; z-index: 1;">

    <!-- Кнопки -->
    <button onclick="changeSlide(-1)" style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); background: rgba(255,255,255,0.7); border: none; font-size: 24px; padding: 10px 15px; cursor: pointer; z-index: 10; border-radius: 4px;">&#10094;</button>
    <button onclick="changeSlide(1)" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: rgba(255,255,255,0.7); border: none; font-size: 24px; padding: 10px 15px; cursor: pointer; z-index: 10; border-radius: 4px;">&#10095;</button>

</div>

<div style="max-width: 800px; margin: 0 auto 40px; text-align: center; font-size: 16px; color: #444; padding: 0 20px;">
    <p>
        Портал «Корочки.есть» представляет собой информационную систему
        для записи на онлайн курсы дополнительного профессионального
        образования.
    </p>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.my-slide');

    function showSlide(n) {
        // Прячем все
        slides.forEach(img => img.style.opacity = '0');
        
        currentSlide += n;
        
        // Зацикливаем
        if (currentSlide >= slides.length) { currentSlide = 0; }
        if (currentSlide < 0) { currentSlide = slides.length - 1; }
        
        // Показываем нужную
        slides[currentSlide].style.opacity = '1';
    }

    function changeSlide(n) {
        showSlide(n);
    }

    // Автопрокрутка каждые 4 секунды
    setInterval(() => {
        showSlide(1);
    }, 4000);
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';