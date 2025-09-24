<?php
// Страница примерки ковра в комнате
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Примерка ковра в комнате</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<header>
    <h1>Виртуальная примерка ковра</h1>
    <p class="hint">Загрузите фотографии комнаты и ковра, укажите реальные размеры и перемещайте ковёр поверх комнаты.</p>
</header>
<main>
    <section class="forms">
        <form class="form-card" id="roomForm">
            <h2>Комната</h2>
            <label>Фотография комнаты
                <input type="file" id="roomImageInput" accept="image/*">
            </label>
            <div class="dimensions">
                <label>Длина (м)
                    <input type="number" id="roomLength" min="0.1" step="0.1" placeholder="Напр. 6">
                </label>
                <label>Ширина (м)
                    <input type="number" id="roomWidth" min="0.1" step="0.1" placeholder="Напр. 4">
                </label>
            </div>
            <p class="hint">Длина соответствует горизонтальному размеру, ширина — вертикальному.</p>
        </form>
        <form class="form-card" id="rugForm">
            <h2>Ковёр</h2>
            <label>Фотография ковра
                <input type="file" id="rugImageInput" accept="image/*">
            </label>
            <div class="dimensions">
                <label>Длина (м)
                    <input type="number" id="rugLength" min="0.1" step="0.1" placeholder="Напр. 2.5">
                </label>
                <label>Ширина (м)
                    <input type="number" id="rugWidth" min="0.1" step="0.1" placeholder="Напр. 1.6">
                </label>
            </div>
            <p class="hint">Эти значения используются для масштабирования ковра внутри комнаты.</p>
        </form>
    </section>

    <section id="workspace">
        <h2>Интерфейс примерки</h2>
        <div class="legend">
            <span><i class="room-color"></i>Комната отображается без искажений, в пропорциях фотографии.</span>
            <span><i class="rug-color"></i>Ковёр масштабируется по размерам и перемещается внутри границ комнаты.</span>
        </div>
        <div id="plannerWrapper">
            <div id="planner">
                <img id="roomImage" alt="Комната">
                <img id="rugImage" alt="Ковёр">
            </div>
            <div id="placeholder">
                Загрузите изображения и заполните размеры, чтобы увидеть ковёр поверх комнаты. После этого перетаскивайте ковёр, чтобы найти оптимальное расположение.
            </div>
        </div>
    </section>
</main>
</body>
</html>
