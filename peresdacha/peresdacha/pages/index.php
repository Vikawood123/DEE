<?php
session_start();
$pageTitle = 'Главная — Пересдачи';
$breadcrumbs = ['Главная' => null];
ob_start();
?>
<div class="slider-container">
    <div class="slider-wrapper">
        <!-- Пути исправлены на одинарные, подставлены твои картинки -->
        <img src="/DEE/peresdacha/media/image16.jpg" class="slider-slide active" alt="Слайд 1">
        <img src="/DEE/peresdacha/media/image05.jpg" class="slider-slide" alt="Слайд 2">
        <img src="/DEE/peresdacha/media/image06.jpg" class="slider-slide" alt="Слайд 3">
        <img src="/DEE/peresdacha/media/image11.jpg" class="slider-slide" alt="Слайд 4">
    </div>
    <button class="slider-btn prev" onclick="moveSlide(-1)">&#10094;</button>
    <button class="slider-btn next" onclick="moveSlide(1)">&#10095;</button>
    <div class="slider-dots">
        <span class="dot active" onclick="currentSlide(0)"></span>
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>
</div>
<div class="hero-text-block">
    <p>Добро пожаловать на портал «Пересдачи». Здесь вы можете записаться на пересдачу экзаменов и зачетов по дисциплинам, по которым имеется академическая задолженность.</p>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';